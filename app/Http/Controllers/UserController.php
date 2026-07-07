<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::orderBy('id', 'asc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'sometimes|in:admin,user',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => $validated['password'],
            'role'     => $validated['role'] ?? 'user',
            'active'   => $request->input('active', true) ? true : false,
        ]);

        $this->auditar($request, 'USUARIO_CREADO', [
            'usuario_id'    => $user->id,
            'usuario_email' => $user->email,
            'rol'           => $user->role,
        ]);

        return response()->json([
            'message' => 'Usuario creado exitosamente',
            'user'    => $user,
        ], 201);
    }

    public function show(User $user)
    {
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'role' => 'sometimes|in:admin,user',
            'active' => 'sometimes|boolean',
        ]);

        $antes = $user->only(['name', 'email', 'role', 'active']);
        $user->update($validated);

        $this->auditar($request, 'USUARIO_ACTUALIZADO', [
            'usuario_id'    => $user->id,
            'usuario_email' => $user->email,
            'antes'         => $antes,
            'despues'       => $user->only(['name', 'email', 'role', 'active']),
        ]);

        return response()->json([
            'message' => 'Usuario actualizado',
            'user' => $user,
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        if ((int) session('user_id') === (int) $user->id) {
            return response()->json([
                'message' => 'No puedes eliminar tu propia cuenta mientras estas conectado.'
            ], 422);
        }

        if ($user->isAdmin()) {
            $otrosAdminsActivos = User::where('role', 'admin')
                ->where('active', true)
                ->where('id', '!=', $user->id)
                ->count();
            if ($otrosAdminsActivos === 0) {
                return response()->json([
                    'message' => 'No puedes eliminar al unico administrador activo.'
                ], 422);
            }
        }

        $dataFile = storage_path('app/finanzas_data_' . (int) $user->id . '.json');
        if (file_exists($dataFile)) {
            @unlink($dataFile);
        }

        $this->auditar($request, 'USUARIO_ELIMINADO', [
            'usuario_id'    => $user->id,
            'usuario_email' => $user->email,
            'rol'           => $user->role,
        ]);

        $user->delete();
        return response()->json(['message' => 'Usuario eliminado']);
    }

    public function changePassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:6',
        ]);

        $user->update([
            'password' => $validated['password'],
        ]);

        $this->auditar($request, 'CONTRASENA_CAMBIADA_POR_ADMIN', [
            'usuario_id'    => $user->id,
            'usuario_email' => $user->email,
        ]);

        return response()->json(['message' => 'Contrasena actualizada']);
    }

    /**
     * Deja constancia en el log de auditoría de qué admin hizo qué,
     * sobre qué usuario y desde dónde.
     */
    private function auditar(Request $request, string $accion, array $detalle): void
    {
        $adminId = session('user_id');
        $admin   = $adminId ? User::find($adminId) : null;

        Log::channel('auditoria')->info($accion, array_merge([
            'admin_id'    => $adminId,
            'admin_email' => $admin?->email,
            'ip'          => $request->ip(),
        ], $detalle));
    }
}
