<?php

namespace App\Http\Controllers;

use App\Models\Expansion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExpansionController extends Controller
{
    /**
     * Get all expansions
     */
    public function index()
    {
        $expansiones = Expansion::orderBy('orden', 'asc')->get();
        return response()->json($expansiones);
    }

    /**
     * Create a new expansion (admin only)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|unique:expansiones|max:255',
            'descripcion' => 'nullable|string',
            'orden' => 'sometimes|integer|min:0',
        ]);

        $expansion = Expansion::create($validated);

        $this->auditar($request, 'EXPANSION_CREADA', [
            'expansion_id'     => $expansion->id,
            'expansion_nombre' => $expansion->nombre,
        ]);

        return response()->json([
            'message' => '✅ Expansión creada exitosamente',
            'expansion' => $expansion
        ], 201);
    }

    /**
     * Get expansion details
     */
    public function show(Expansion $expansion)
    {
        return response()->json($expansion);
    }

    /**
     * Update expansion (admin only)
     */
    public function update(Request $request, Expansion $expansion)
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|string|unique:expansiones,nombre,' . $expansion->id . '|max:255',
            'descripcion' => 'nullable|string',
            'activa' => 'sometimes|boolean',
            'orden' => 'sometimes|integer|min:0',
        ]);

        $antes = $expansion->only(['nombre', 'descripcion', 'activa', 'orden']);
        $expansion->update($validated);

        $this->auditar($request, 'EXPANSION_ACTUALIZADA', [
            'expansion_id'     => $expansion->id,
            'expansion_nombre' => $expansion->nombre,
            'antes'            => $antes,
            'despues'          => $expansion->only(['nombre', 'descripcion', 'activa', 'orden']),
        ]);

        return response()->json([
            'message' => '✅ Expansión actualizada',
            'expansion' => $expansion
        ]);
    }

    /**
     * Delete expansion (admin only)
     */
    public function destroy(Request $request, Expansion $expansion)
    {
        $this->auditar($request, 'EXPANSION_ELIMINADA', [
            'expansion_id'     => $expansion->id,
            'expansion_nombre' => $expansion->nombre,
        ]);

        Expansion::destroy($expansion->id);
        return response()->json(['message' => '✅ Expansión eliminada']);
    }

    /**
     * Toggle expansion active status
     */
    public function toggle(Request $request, Expansion $expansion)
    {
        $expansion->update(['activa' => !$expansion->activa]);

        $this->auditar($request, 'EXPANSION_TOGGLE', [
            'expansion_id'     => $expansion->id,
            'expansion_nombre' => $expansion->nombre,
            'activa'           => $expansion->activa,
        ]);

        return response()->json([
            'message' => '✅ Estado actualizado',
            'expansion' => $expansion
        ]);
    }

    /**
     * Deja constancia en el log de auditoría de qué admin hizo qué.
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
