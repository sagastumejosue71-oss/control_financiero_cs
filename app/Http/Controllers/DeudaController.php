<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\VerificaPropietarioNegocio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeudaController extends Controller
{
    use VerificaPropietarioNegocio;

    public function index(int $negocio)
    {
        $n = $this->negocioDelUsuario($negocio);

        return response()->json(
            $n->deudas()->orderBy('saldada')->orderBy('fecha_limite')->get()
        );
    }

    public function store(Request $request, int $negocio)
    {
        $n = $this->negocioDelUsuario($negocio);

        $validated = $request->validate([
            'acreedor' => 'required|string|max:255',
            'monto_total' => 'required|numeric|min:0',
            'saldo_pendiente' => 'required|numeric|min:0',
            'tasa_interes' => 'nullable|numeric|min:0|max:100',
            'fecha_inicio' => 'nullable|date',
            'fecha_limite' => 'nullable|date',
        ]);

        $deuda = $n->deudas()->create($validated);

        $this->auditar($request, 'DEUDA_CREADA', [
            'negocio_id' => $n->id,
            'deuda_id' => $deuda->id,
        ]);

        return response()->json(['message' => '✅ Deuda registrada', 'deuda' => $deuda], 201);
    }

    public function update(Request $request, int $negocio, int $deuda)
    {
        $n = $this->negocioDelUsuario($negocio);
        $d = $n->deudas()->findOrFail($deuda);

        $validated = $request->validate([
            'acreedor' => 'sometimes|string|max:255',
            'monto_total' => 'sometimes|numeric|min:0',
            'saldo_pendiente' => 'sometimes|numeric|min:0',
            'tasa_interes' => 'sometimes|nullable|numeric|min:0|max:100',
            'fecha_inicio' => 'sometimes|nullable|date',
            'fecha_limite' => 'sometimes|nullable|date',
            'saldada' => 'sometimes|boolean',
        ]);

        $d->update($validated);

        $this->auditar($request, 'DEUDA_ACTUALIZADA', [
            'negocio_id' => $n->id,
            'deuda_id' => $d->id,
        ]);

        return response()->json(['message' => '✅ Deuda actualizada', 'deuda' => $d]);
    }

    public function destroy(Request $request, int $negocio, int $deuda)
    {
        $n = $this->negocioDelUsuario($negocio);
        $d = $n->deudas()->findOrFail($deuda);

        $this->auditar($request, 'DEUDA_ELIMINADA', [
            'negocio_id' => $n->id,
            'deuda_id' => $d->id,
        ]);

        $d->delete();

        return response()->json(['message' => '✅ Deuda eliminada']);
    }

    private function auditar(Request $request, string $accion, array $detalle): void
    {
        Log::channel('auditoria')->info($accion, array_merge([
            'user_id' => session('user_id'),
            'ip' => $request->ip(),
        ], $detalle));
    }
}
