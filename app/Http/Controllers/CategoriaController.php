<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\VerificaPropietarioNegocio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoriaController extends Controller
{
    use VerificaPropietarioNegocio;

    public function index(int $negocio)
    {
        $n = $this->negocioDelUsuario($negocio);

        return response()->json(
            $n->categorias()->orderBy('tipo')->orderBy('nombre')->get()
        );
    }

    public function store(Request $request, int $negocio)
    {
        $n = $this->negocioDelUsuario($negocio);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|in:ingreso,gasto',
        ]);

        $categoria = $n->categorias()->create($validated);

        $this->auditar($request, 'CATEGORIA_CREADA', [
            'negocio_id' => $n->id,
            'categoria_id' => $categoria->id,
        ]);

        return response()->json(['message' => '✅ Categoría creada', 'categoria' => $categoria], 201);
    }

    public function destroy(Request $request, int $negocio, int $categoria)
    {
        $n = $this->negocioDelUsuario($negocio);
        $c = $n->categorias()->findOrFail($categoria);

        $this->auditar($request, 'CATEGORIA_ELIMINADA', [
            'negocio_id' => $n->id,
            'categoria_id' => $c->id,
        ]);

        $c->delete();

        return response()->json(['message' => '✅ Categoría eliminada']);
    }

    private function auditar(Request $request, string $accion, array $detalle): void
    {
        Log::channel('auditoria')->info($accion, array_merge([
            'user_id' => session('user_id'),
            'ip' => $request->ip(),
        ], $detalle));
    }
}
