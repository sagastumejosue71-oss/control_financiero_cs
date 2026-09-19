<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\VerificaPropietarioNegocio;
use App\Models\Negocio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

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
            'nombre' => [
                'required', 'string', 'max:255',
                Rule::unique('negocios')->where(fn ($q) => $q->where('user_id', session('user_id'))),
            ],
            'moneda' => 'sometimes|string|size:3',
        ], [
            'nombre.unique' => 'Ya tienes un negocio con ese nombre.',
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
            'nombre' => [
                'sometimes', 'string', 'max:255',
                Rule::unique('negocios')->where(fn ($q) => $q->where('user_id', session('user_id')))->ignore($n->id),
            ],
            'moneda' => 'sometimes|string|size:3',
            'activo' => 'sometimes|boolean',
        ], [
            'nombre.unique' => 'Ya tienes un negocio con ese nombre.',
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

    public function resumen(int $negocio)
    {
        $n = $this->negocioDelUsuario($negocio);

        $saldoTotal = $n->cuentas()->get()->sum(fn ($c) => $c->saldoActual());

        $ingresosTotales = (float) $n->movimientos()->where('tipo', 'ingreso')->sum('monto');
        $gastosTotales   = (float) $n->movimientos()->where('tipo', 'gasto')->sum('monto');

        $inicioMes = now()->startOfMonth()->toDateString();
        $finMes    = now()->endOfMonth()->toDateString();

        $ingresosMes = (float) $n->movimientos()
            ->where('tipo', 'ingreso')
            ->whereBetween('fecha', [$inicioMes, $finMes])
            ->sum('monto');

        $gastosMes = (float) $n->movimientos()
            ->where('tipo', 'gasto')
            ->whereBetween('fecha', [$inicioMes, $finMes])
            ->sum('monto');

        return response()->json([
            'saldo_total'      => round($saldoTotal, 2),
            'ingresos_totales' => round($ingresosTotales, 2),
            'gastos_totales'   => round($gastosTotales, 2),
            'neto_total'       => round($ingresosTotales - $gastosTotales, 2),
            'ingresos_mes'     => round($ingresosMes, 2),
            'gastos_mes'       => round($gastosMes, 2),
            'neto_mes'         => round($ingresosMes - $gastosMes, 2),
        ]);
    }

    public function estadisticasMensuales(int $negocio)
    {
        $n = $this->negocioDelUsuario($negocio);

        $meses = collect(range(5, 0))->map(function ($i) use ($n) {
            $inicio = now()->subMonths($i)->startOfMonth();
            $fin = now()->subMonths($i)->endOfMonth();

            $ingresos = (float) $n->movimientos()
                ->where('tipo', 'ingreso')
                ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
                ->sum('monto');

            $gastos = (float) $n->movimientos()
                ->where('tipo', 'gasto')
                ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
                ->sum('monto');

            return [
                'mes' => $inicio->format('m/Y'),
                'ingresos' => round($ingresos, 2),
                'gastos' => round($gastos, 2),
            ];
        });

        return response()->json($meses);
    }

    private function auditar(Request $request, string $accion, array $detalle): void
    {
        Log::channel('auditoria')->info($accion, array_merge([
            'user_id' => session('user_id'),
            'ip' => $request->ip(),
        ], $detalle));
    }
}
