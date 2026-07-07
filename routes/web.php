<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Support\FinanzasCrypto;

Route::get('/', function () {
    if (!session('user_id')) return redirect('/login');
    $user = \App\Models\User::find(session('user_id'), ['*']);
    return redirect($user?->isAdmin() ? '/admin' : '/finanzas');
});

Route::get('/login', function () {
    if (session('user_id')) {
        $user = \App\Models\User::find(session('user_id'), ['*']);
        return redirect($user?->isAdmin() ? '/admin' : '/finanzas');
    }
    return view('login');
});

Route::post('/login', function (Request $request) {
    // Rate limiting: máx 5 intentos por minuto por IP (protección fuerza bruta)
    $key = 'login.' . $request->ip();
    if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5)) {
        $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
        Log::channel('accesos')->warning('LOGIN_BLOQUEADO_RATE_LIMIT', [
            'ip'      => $request->ip(),
            'espera'  => $seconds,
        ]);
        return back()->with('error', "Demasiados intentos. Espera {$seconds} segundos.");
    }

    $email    = strtolower(trim($request->input('email', '')));
    $password = $request->input('password', '');
    $remember = $request->boolean('remember_me');

    $user = \App\Models\User::where('email', $email)->first();

    if ($user && \Illuminate\Support\Facades\Hash::check($password, $user->password) && $user->isActive()) {
        \Illuminate\Support\Facades\RateLimiter::clear($key);
        session()->regenerate();
        session(['user_id' => $user->id, 'finanzas_auth' => true]);

        Log::channel('accesos')->info('LOGIN_EXITOSO', [
            'usuario'    => $user->name,
            'email'      => $user->email,
            'rol'        => $user->role,
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $response = redirect($user->isAdmin() ? '/admin' : '/finanzas');

        if ($remember) {
            $token = Str::random(60);
            $user->update(['remember_token' => $token]);
            $response = $response->withCookie(
                cookie('remember_me', $user->id . '|' . $token, 60 * 24 * 30)
            );
        }

        return $response;
    }

    \Illuminate\Support\Facades\RateLimiter::hit($key, 60);

    Log::channel('accesos')->warning('INTENTO_FALLIDO', [
        'email_intentado' => $email,
        'ip'              => $request->ip(),
        'user_agent'      => $request->userAgent(),
        'usuario_existe'  => $user ? true : false,
        'cuenta_activa'   => $user ? $user->isActive() : null,
    ]);

    return back()->withInput($request->only('email'))->with('error', '❌ Email o contraseña incorrectos.');
});

// ── Registro manual ─────────────────────────────────────────────────────────
Route::get('/register', function () {
    if (session('user_id')) return redirect('/finanzas');
    return view('register');
});

Route::post('/register', function (Request $request) {
    // Rate limiting: máx 5 registros por minuto por IP (evita creación masiva de cuentas)
    $key = 'register.' . $request->ip();
    if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5)) {
        $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
        Log::channel('accesos')->warning('REGISTRO_BLOQUEADO_RATE_LIMIT', [
            'ip'     => $request->ip(),
            'espera' => $seconds,
        ]);
        return back()->withInput($request->only('name', 'email'))
            ->with('error', "Demasiados intentos. Espera {$seconds} segundos.");
    }
    \Illuminate\Support\Facades\RateLimiter::hit($key, 60);

    $request->validate([
        'name'                  => 'required|string|max:255',
        'email'                 => 'required|email|unique:users,email',
        'password'              => 'required|string|min:8|confirmed',
    ], [
        'email.unique'          => 'Este correo ya está registrado.',
        'password.confirmed'    => 'Las contraseñas no coinciden.',
        'password.min'          => 'La contraseña debe tener al menos 8 caracteres.',
    ]);

    $user = \App\Models\User::create([
        'name'     => $request->name,
        'email'    => $request->email,
        'password' => $request->password,
        'role'     => 'user',
        'active'   => true,
    ]);

    session(['user_id' => $user->id, 'finanzas_auth' => true]);

    Log::channel('accesos')->info('REGISTRO_NUEVO', [
        'usuario' => $user->name,
        'email'   => $user->email,
        'ip'      => $request->ip(),
    ]);

    return redirect('/finanzas');
});

// ── Google OAuth ─────────────────────────────────────────────────────────────
Route::get('/auth/google', function () {
    return Socialite::driver('google')->redirect();
});

Route::get('/auth/google/callback', function () {
    try {
        $googleUser = Socialite::driver('google')->user();
    } catch (\Exception $e) {
        return redirect('/login')->with('error', '❌ Error al autenticar con Google.');
    }

    $user = \App\Models\User::where('google_id', $googleUser->getId())->first()
        ?? \App\Models\User::where('email', $googleUser->getEmail())->first();

    if ($user) {
        if (!$user->isActive()) {
            return redirect('/login')->with('error', '❌ Tu cuenta está desactivada.');
        }
        $user->update([
            'google_id' => $googleUser->getId(),
            'avatar'    => $googleUser->getAvatar(),
        ]);
    } else {
        $user = \App\Models\User::create([
            'name'      => $googleUser->getName(),
            'email'     => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'avatar'    => $googleUser->getAvatar(),
            'password'  => Str::random(32),
            'role'      => 'user',
            'active'    => true,
        ]);
    }

    session(['user_id' => $user->id, 'finanzas_auth' => true]);

    Log::channel('accesos')->info('LOGIN_GOOGLE', [
        'usuario' => $user->name,
        'email'   => $user->email,
        'ip'      => request()->ip(),
    ]);

    return redirect($user->isAdmin() ? '/admin' : '/finanzas');
});

// ── Perfil de usuario ────────────────────────────────────────────────────────
Route::get('/perfil', function () {
    if (!session('user_id')) return redirect('/login');
    $user = \App\Models\User::find(session('user_id'), ['*']);
    return view('perfil', ['currentUser' => $user]);
});

Route::post('/perfil/actualizar', function (Request $request) {
    if (!session('user_id')) return redirect('/login');
    $user = \App\Models\User::find(session('user_id'), ['*']);

    $request->validate([
        'name'  => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . $user->id,
    ], [
        'email.unique' => 'Este correo ya lo usa otra cuenta.',
    ]);

    $user->update([
        'name'  => $request->name,
        'email' => $request->email,
    ]);

    return back()->with('success', '✅ Perfil actualizado correctamente.');
});

Route::post('/perfil/cambiar-password', function (Request $request) {
    if (!session('user_id')) return redirect('/login');
    $user = \App\Models\User::find(session('user_id'), ['*']);

    if ($user->google_id && !$user->password) {
        return back()->with('error', '❌ Tu cuenta usa Google. Establece una contraseña primero.');
    }

    $request->validate([
        'password_actual' => 'required|string',
        'password'        => 'required|string|min:8|confirmed',
    ], [
        'password.confirmed' => 'Las contraseñas nuevas no coinciden.',
        'password.min'       => 'La nueva contraseña debe tener al menos 8 caracteres.',
    ]);

    if (!\Illuminate\Support\Facades\Hash::check($request->password_actual, $user->password)) {
        return back()->with('error', '❌ La contraseña actual es incorrecta.');
    }

    $user->update(['password' => $request->password]);

    return back()->with('success', '✅ Contraseña cambiada correctamente.');
});

Route::post('/logout', function () {
    $userId = session('user_id');
    if ($userId) {
        \App\Models\User::where('id', '=', $userId)->update(['remember_token' => null]);
    }
    session()->forget(['user_id', 'finanzas_auth']);
    return redirect('/login')->withCookie(cookie()->forget('remember_me', '/'));
});

Route::get('/finanzas', function () {
    if (!session('user_id')) return redirect('/login');
    $user = \App\Models\User::find(session('user_id'), ['*']);
    return view('finanzas', [
        'isAdmin' => $user?->isAdmin() === true,
        'currentUser' => $user,
    ]);
});

/**
 * Devuelve la ruta del archivo de datos para el usuario actualmente logueado.
 * Cada usuario tiene su propio JSON aislado: storage/app/finanzas_data_{user_id}.json
 * De esta manera, un usuario nuevo NO ve datos de otros y empieza con todo en 0.
 */
function _finanzasDataPath(): ?string {
    $userId = session('user_id');
    if (!$userId) return null;
    return storage_path('app/finanzas_data_' . (int) $userId . '.json');
}

function _finanzasDataDefault(): array {
    return [
        'ingresos'             => [],
        'gastos_fijos'         => [],
        'gastos_variables'     => [],
        'deudas'               => [],
        'pagos_realizados'     => [],
        'historial_mensual'    => [],
        'metas_ahorro'         => [],
        'expansion_scenarios'  => [],
        'expansion_active_id'  => null,
        'exchange_rate'        => 7.70,
        '_rev'                 => 0,
    ];
}

Route::get('/api/finanzas-data', function () {
    $path = _finanzasDataPath();
    if (!$path) return response()->json(['error' => 'No autorizado'], 401);

    // Migración suave: el primer admin (id menor) hereda el archivo legacy compartido
    $legacy = storage_path('app/finanzas_data.json');
    if (!file_exists($path) && file_exists($legacy)) {
        $firstAdmin = \App\Models\User::where('role', 'admin')->orderBy('id', 'asc')->first();
        if ($firstAdmin && (int) session('user_id') === (int) $firstAdmin->id) {
            @mkdir(dirname($path), 0775, true);
            @copy($legacy, $path);
            @rename($legacy, $legacy . '.migrated_' . date('Ymd_His'));
        }
    }

    if (!file_exists($path)) {
        return response()->json(_finanzasDataDefault());
    }

    // Lectura con lock compartido: evita leer un archivo a medio escribir.
    $fp  = fopen($path, 'r');
    $raw = false;
    if ($fp) {
        flock($fp, LOCK_SH);
        $raw = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }
    $data = $raw !== false ? FinanzasCrypto::decode($raw) : null;
    return response()->json(is_array($data) ? array_merge(_finanzasDataDefault(), $data) : _finanzasDataDefault());
});

Route::post('/api/finanzas-data', function (Request $request) {
    $path = _finanzasDataPath();
    if (!$path) return response()->json(['error' => 'No autorizado'], 401);
    @mkdir(dirname($path), 0775, true);

    $data = $request->all();
    $clientRev = (int) ($data['_rev'] ?? 0);

    // Escritura con lock exclusivo + control de versión optimista: si otra
    // pestaña/sesión del mismo usuario ya guardó una versión más nueva,
    // rechazamos este guardado en vez de sobreescribirla silenciosamente.
    $fp = fopen($path, 'c+');
    if ($fp === false) {
        return response()->json(['error' => 'No se pudo abrir el archivo de datos'], 500);
    }

    flock($fp, LOCK_EX);
    $currentRaw = stream_get_contents($fp);
    $current    = $currentRaw !== '' ? FinanzasCrypto::decode($currentRaw) : null;
    $currentRev = is_array($current) ? (int) ($current['_rev'] ?? 0) : 0;

    if ($currentRaw !== '' && !is_array($current)) {
        // El archivo existente está corrupto: no lo pisamos a ciegas,
        // mejor fallar explícitamente para poder investigar.
        flock($fp, LOCK_UN);
        fclose($fp);
        return response()->json(['error' => 'Datos existentes corruptos, contacta al administrador'], 500);
    }

    if ($currentRev > $clientRev) {
        flock($fp, LOCK_UN);
        fclose($fp);
        return response()->json([
            'error'   => 'conflict',
            'message' => 'Estos datos se actualizaron en otra sesión.',
        ], 409);
    }

    $data['_rev'] = $currentRev + 1;
    $encrypted = FinanzasCrypto::encode($data);

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, $encrypted);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return response()->json(['ok' => true, 'rev' => $data['_rev']]);
});

Route::get('/api/exportar-resumen-word', [\App\Http\Controllers\ResumenWordController::class, 'exportar']);
Route::get('/api/exportar-resumen-pdf',  [\App\Http\Controllers\ResumenWordController::class, 'exportarPdf']);

// =====================================================================
// PANEL DE ADMINISTRACIÓN (interfaz separada)
// =====================================================================
Route::middleware(['admin'])->group(function () {

    // --- Vistas HTML del panel admin ---
    Route::get('/admin', function () {
        $admin = \App\Models\User::find(session('user_id'), ['*']);
        return view('admin.dashboard', [
            'currentUser'   => $admin,
            'totalUsers'    => \App\Models\User::count(),
            'activeUsers'   => \App\Models\User::where('active', true)->count(),
            'adminUsers'    => \App\Models\User::where('role', 'admin')->count(),
        ]);
    });

    Route::get('/admin/usuarios', function () {
        $admin = \App\Models\User::find(session('user_id'), ['*']);
        return view('admin.usuarios', ['currentUser' => $admin]);
    });

    Route::get('/admin/expansiones', function () {
        $admin = \App\Models\User::find(session('user_id'), ['*']);
        return view('admin.expansiones', ['currentUser' => $admin]);
    });

    Route::get('/admin/perfil', function () {
        $admin = \App\Models\User::find(session('user_id'), ['*']);
        return view('admin.perfil', ['currentUser' => $admin]);
    });

    Route::post('/admin/perfil/actualizar', function (Request $request) {
        $admin = \App\Models\User::find(session('user_id'), ['*']);
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $admin->id,
        ], ['email.unique' => 'Ese correo ya lo usa otra cuenta.']);
        $admin->update(['name' => $request->name, 'email' => $request->email]);
        return back()->with('success', '✅ Perfil actualizado correctamente.');
    });

    Route::post('/admin/perfil/cambiar-password', function (Request $request) {
        $admin = \App\Models\User::find(session('user_id'), ['*']);
        $request->validate([
            'password_actual' => 'required|string',
            'password'        => 'required|string|min:8|confirmed',
        ], [
            'password.confirmed' => 'Las contraseñas nuevas no coinciden.',
            'password.min'       => 'La nueva contraseña debe tener al menos 8 caracteres.',
        ]);
        if (!\Illuminate\Support\Facades\Hash::check($request->password_actual, $admin->password)) {
            return back()->with('pw_error', '❌ La contraseña actual es incorrecta.');
        }
        $admin->update(['password' => $request->password]);
        return back()->with('pw_success', '✅ Contraseña cambiada correctamente.');
    });

    // --- API JSON (consumida por la interfaz admin) ---
    // Gestión de usuarios
    Route::get('/admin/api/users',    [\App\Http\Controllers\UserController::class, 'index']);
    Route::post('/admin/api/users',   [\App\Http\Controllers\UserController::class, 'store']);
    Route::get('/admin/api/users/{user}',    [\App\Http\Controllers\UserController::class, 'show']);
    Route::put('/admin/api/users/{user}',    [\App\Http\Controllers\UserController::class, 'update']);
    Route::delete('/admin/api/users/{user}', [\App\Http\Controllers\UserController::class, 'destroy']);
    Route::post('/admin/api/users/{user}/change-password', [\App\Http\Controllers\UserController::class, 'changePassword']);

    // Gestión de expansiones
    Route::get('/admin/api/expansiones',    [\App\Http\Controllers\ExpansionController::class, 'index']);
    Route::post('/admin/api/expansiones',   [\App\Http\Controllers\ExpansionController::class, 'store']);
    Route::get('/admin/api/expansiones/{expansion}',    [\App\Http\Controllers\ExpansionController::class, 'show']);
    Route::put('/admin/api/expansiones/{expansion}',    [\App\Http\Controllers\ExpansionController::class, 'update']);
    Route::delete('/admin/api/expansiones/{expansion}', [\App\Http\Controllers\ExpansionController::class, 'destroy']);
    Route::post('/admin/api/expansiones/{expansion}/toggle', [\App\Http\Controllers\ExpansionController::class, 'toggle']);

    // --- Aliases legacy (compatibilidad hacia atrás) ---
    Route::get('/admin/users',    [\App\Http\Controllers\UserController::class, 'index']);
    Route::post('/admin/users',   [\App\Http\Controllers\UserController::class, 'store']);
    Route::put('/admin/users/{user}',    [\App\Http\Controllers\UserController::class, 'update']);
    Route::delete('/admin/users/{user}', [\App\Http\Controllers\UserController::class, 'destroy']);
    Route::post('/admin/users/{user}/change-password', [\App\Http\Controllers\UserController::class, 'changePassword']);
});
