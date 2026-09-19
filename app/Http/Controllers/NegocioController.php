<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\VerificaPropietarioNegocio;
use App\Models\Negocio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NegocioController extends Controller
{
    use VerificaPropietarioNegocio;

    public function index()
    {
        abort_unless(session('user_id'), 401, 'No autenticado');

        $negocios = Negocio::where('user_id', session('user_id'))
            ->orderBy('nombre')
            ->get();

        return response()->json($negocios);
    }

    public function store(Request $request)
    {
        abort_unless(session('user_id'), 401, 'No autenticado');

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'moneda' => 'sometimes|string|size:3',
        ]);

        $negocio = Negocio::create([
            ...$validated,
            'user_id' => session('user_id'),
        ]);

        $this->auditar($request, 'NEGOCIO_CREADO', [
            'negocio_id' => $negocio->id,
            'negocio_nombre' => $negocio->nombre,
        ]);

        return response()->json(['message' => '✅ Negocio creado', 'negocio' => $negocio], 201);
    }

    public function show(int $negocio)
    {
        return response()->json($this->negocioDelUsuario($negocio));
    }

    public function update(Request $request, int $negocio)
    {
        $n = $this->negocioDelUsuario($negocio);

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'moneda' => 'sometimes|string|size:3',
            'activo' => 'sometimes|boolean',
        ]);

        $antes = $n->only(['nombre', 'moneda', 'activo']);
        $n->update($validated);

        $this->auditar($request, 'NEGOCIO_ACTUALIZADO', [
            'negocio_id' => $n->id,
            'antes' => $antes,
            'despues' => $n->only(['nombre', 'moneda', 'activo']),
        ]);

        return response()->json(['message' => '✅ Negocio actualizado', 'negocio' => $n]);
    }

    public function destroy(Request $request, int $negocio)
    {
        $n = $this->negocioDelUsuario($negocio);

        $this->auditar($request, 'NEGOCIO_ELIMINADO', [
            'negocio_id' => $n->id,
            'negocio_nombre' => $n->nombre,
        ]);

        $n->delete();

        return response()->json(['message' => '✅ Negocio eliminado']);
    }

    private function auditar(Request $request, string $accion, array $detalle): void
    {
        Log::channel('auditoria')->info($accion, array_merge([
            'user_id' => session('user_id'),
            'ip' => $request->ip(),
        ], $detalle));
    }
}
