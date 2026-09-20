<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\VerificaPropietarioNegocio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MovimientoController extends Controller
{
    use VerificaPropietarioNegocio;

    public function index(Request $request, int $negocio)
    {
        $n = $this->negocioDelUsuario($negocio);

        $query = $n->movimientos()->with(['cuenta', 'categoria'])->orderByDesc('fecha');

        if ($cuentaId = $request->query('cuenta_id')) {
            $query->where('cuenta_id', $cuentaId);
        }
        if ($desde = $request->query('desde')) {
            $query->where('fecha', '>=', $desde);
        }
        if ($hasta = $request->query('hasta')) {
            $query->where('fecha', '<=', $hasta);
        }

        return response()->json($query->paginate(50));
    }

    public function store(Request $request, int $negocio)
    {
        $n = $this->negocioDelUsuario($negocio);

        $validated = $request->validate([
            'cuenta_id' => 'required|integer',
            'categoria_id' => 'nullable|integer',
            'tipo' => 'required|in:ingreso,gasto',
            'monto' => 'required|numeric|min:0.01',
            'descripcion' => 'nullable|string|max:500',
            'fecha' => 'required|date',
        ]);

        // La cuenta y la categoría deben pertenecer a ESTE negocio (no a
        // otro, aunque sea del mismo dueño). Si no pertenecen, el where()
        // scoped por relación no encuentra nada y abortamos.
        abort_unless(
            $n->cuentas()->whereKey($validated['cuenta_id'])->exists(),
            422,
            'La cuenta no pertenece a este negocio.'
        );
        if (!empty($validated['categoria_id'])) {
            abort_unless(
                $n->categorias()->whereKey($validated['categoria_id'])->exists(),
                422,
                'La categoría no pertenece a este negocio.'
            );
        }

        $movimiento = $n->movimientos()->create([
            ...$validated,
            'created_by' => session('user_id'),
        ]);

        $this->auditar($request, 'MOVIMIENTO_CREADO', [
            'negocio_id' => $n->id,
            'movimiento_id' => $movimiento->id,
            'tipo' => $movimiento->tipo,
            'monto' => $movimiento->monto,
        ]);

        return response()->json(['message' => '✅ Movimiento registrado', 'movimiento' => $movimiento], 201);
    }

    public function update(Request $request, int $negocio, int $movimiento)
    {
        $n = $this->negocioDelUsuario($negocio);
        $m = $n->movimientos()->findOrFail($movimiento);

        $validated = $request->validate([
            'cuenta_id' => 'required|integer',
            'categoria_id' => 'nullable|integer',
            'tipo' => 'required|in:ingreso,gasto',
            'monto' => 'required|numeric|min:0.01',
            'descripcion' => 'nullable|string|max:500',
            'fecha' => 'required|date',
        ]);

        abort_unless(
            $n->cuentas()->whereKey($validated['cuenta_id'])->exists(),
            422,
            'La cuenta no pertenece a este negocio.'
        );
        if (!empty($validated['categoria_id'])) {
            abort_unless(
                $n->categorias()->whereKey($validated['categoria_id'])->exists(),
                422,
                'La categoría no pertenece a este negocio.'
            );
        }

        $m->update($validated);

        $this->auditar($request, 'MOVIMIENTO_ACTUALIZADO', [
            'negocio_id' => $n->id,
            'movimiento_id' => $m->id,
        ]);

        return response()->json(['message' => '✅ Movimiento actualizado', 'movimiento' => $m->fresh(['cuenta', 'categoria'])]);
    }

    public function destroy(Request $request, int $negocio, int $movimiento)
    {
        $n = $this->negocioDelUsuario($negocio);
        $m = $n->movimientos()->findOrFail($movimiento);

        $this->auditar($request, 'MOVIMIENTO_ELIMINADO', [
            'negocio_id' => $n->id,
            'movimiento_id' => $m->id,
            'monto' => $m->monto,
        ]);

        $m->delete();

        return response()->json(['message' => '✅ Movimiento eliminado']);
    }

    private function auditar(Request $request, string $accion, array $detalle): void
    {
        Log::channel('auditoria')->info($accion, array_merge([
            'user_id' => session('user_id'),
            'ip' => $request->ip(),
        ], $detalle));
    }
}
