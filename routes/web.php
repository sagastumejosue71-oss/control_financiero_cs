<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Support\FinanzasCrypto;
use App\Support\FinanzasDataDefaults;

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

Route::get('/api/finanzas-data', function () {
    $userId = session('user_id');
    if (!$userId) return response()->json(['error' => 'No autorizado'], 401);

    $row = DB::table('finanzas_data')->where('user_id', $userId)->first();

    if (!$row) {
        return response()->json(FinanzasDataDefaults::array());
    }

    $data   = FinanzasCrypto::decode($row->data);
    $result = is_array($data) ? array_merge(FinanzasDataDefaults::array(), $data) : FinanzasDataDefaults::array();
    $result['_rev'] = $row->rev;

    return response()->json($result);
});

Route::post('/api/finanzas-data', function (Request $request) {
    $userId = session('user_id');
    if (!$userId) return response()->json(['error' => 'No autorizado'], 401);

    $data = $request->all();
    $clientRev = (int) ($data['_rev'] ?? 0);
    unset($data['_rev']); // el rev vive en su propia columna, no dentro del blob

    // Transacción + lock de fila: control de concurrencia optimista real a
    // nivel de base de datos (equivalente al flock() que usábamos con
    // archivos, pero portable entre SQLite/MySQL/Postgres).
    $result = DB::transaction(function () use ($userId, $data, $clientRev) {
        $row = DB::table('finanzas_data')->where('user_id', $userId)->lockForUpdate()->first();
        $currentRev = $row?->rev ?? 0;

        if ($currentRev > $clientRev) {
            return ['status' => 409, 'body' => [
                'error'   => 'conflict',
                'message' => 'Estos datos se actualizaron en otra sesión.',
            ]];
        }

        $newRev = $currentRev + 1;
        $encrypted = FinanzasCrypto::encode($data);

        DB::table('finanzas_data')->updateOrInsert(
            ['user_id' => $userId],
            ['data' => $encrypted, 'rev' => $newRev, 'updated_at' => now(), 'created_at' => $row?->created_at ?? now()]
        );

        return ['status' => 200, 'body' => ['ok' => true, 'rev' => $newRev]];
    });

    return response()->json($result['body'], $result['status']);
});

Route::get('/api/exportar-resumen-word', [\App\Http\Controllers\ResumenWordController::class, 'exportar']);
Route::get('/api/exportar-resumen-pdf',  [\App\Http\Controllers\ResumenWordController::class, 'exportarPdf']);

// Chat con IA (Groq) — asistente financiero
Route::get('/api/chat',       [\App\Http\Controllers\ChatController::class, 'index']);
Route::post('/api/chat',      [\App\Http\Controllers\ChatController::class, 'send']);
Route::delete('/api/chat',    [\App\Http\Controllers\ChatController::class, 'clear']);

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
