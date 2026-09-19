<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\VerificaPropietarioNegocio;
use App\Models\Cuenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CuentaController extends Controller
{
    use VerificaPropietarioNegocio;

    public function index(int $negocio)
    {
        $n = $this->negocioDelUsuario($negocio);

        $cuentas = $n->cuentas()->orderBy('nombre')->get()->map(fn (Cuenta $c) => [
            ...$c->toArray(),
            'saldo_actual' => $c->saldoActual(),
        ]);

        return response()->json($cuentas);
    }

    public function store(Request $request, int $negocio)
    {
        $n = $this->negocioDelUsuario($negocio);

        $validated = $request->validate([
            'nombre' => [
                'required', 'string', 'max:255',
                Rule::unique('cuentas')->where(fn ($q) => $q->where('negocio_id', $n->id)),
            ],
            'tipo' => 'required|in:efectivo,banco,tarjeta,otro',
            'saldo_inicial' => 'sometimes|numeric',
        ], [
            'nombre.unique' => 'Ya existe una cuenta con ese nombre en este negocio.',
        ]);

        $cuenta = $n->cuentas()->create($validated);

        $this->auditar($request, 'CUENTA_CREADA', [
            'negocio_id' => $n->id,
            'cuenta_id' => $cuenta->id,
        ]);

        return response()->json(['message' => '✅ Cuenta creada', 'cuenta' => $cuenta], 201);
    }

    public function update(Request $request, int $negocio, int $cuenta)
    {
        $n = $this->negocioDelUsuario($negocio);
        $c = $n->cuentas()->findOrFail($cuenta);

        $validated = $request->validate([
            'nombre' => [
                'sometimes', 'string', 'max:255',
                Rule::unique('cuentas')->where(fn ($q) => $q->where('negocio_id', $n->id))->ignore($c->id),
            ],
            'tipo' => 'sometimes|in:efectivo,banco,tarjeta,otro',
            'saldo_inicial' => 'sometimes|numeric',
            'activa' => 'sometimes|boolean',
        ], [
            'nombre.unique' => 'Ya existe una cuenta con ese nombre en este negocio.',
        ]);

        $c->update($validated);

        $this->auditar($request, 'CUENTA_ACTUALIZADA', [
            'negocio_id' => $n->id,
            'cuenta_id' => $c->id,
        ]);

        return response()->json(['message' => '✅ Cuenta actualizada', 'cuenta' => $c]);
    }

    public function destroy(Request $request, int $negocio, int $cuenta)
    {
        $n = $this->negocioDelUsuario($negocio);
        $c = $n->cuentas()->findOrFail($cuenta);

        $this->auditar($request, 'CUENTA_ELIMINADA', [
            'negocio_id' => $n->id,
            'cuenta_id' => $c->id,
        ]);

        $c->delete();

        return response()->json(['message' => '✅ Cuenta eliminada']);
    }

    private function auditar(Request $request, string $accion, array $detalle): void
    {
        Log::channel('auditoria')->info($accion, array_merge([
            'user_id' => session('user_id'),
            'ip' => $request->ip(),
        ], $detalle));
    }
}
