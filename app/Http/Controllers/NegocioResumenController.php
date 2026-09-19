<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\VerificaPropietarioNegocio;
use App\Models\Categoria;
use App\Models\Negocio;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

/**
 * Exporta el resumen de UN negocio (PDF real y "Word" — mismo truco que
 * ResumenWordController: HTML servido con Content-Type de Word, Office lo
 * abre igual). No toca ResumenWordController (ese sigue siendo el resumen
 * personal); este es su equivalente por negocio.
 */
class NegocioResumenController extends Controller
{
    use VerificaPropietarioNegocio;

    public function exportarPdf(int $negocio)
    {
        $n = $this->negocioDelUsuario($negocio);
        $html = $this->renderHtml($n);

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'dpi' => 150,
            ]);

        return $pdf->download($this->filename($n, 'pdf'));
    }

    public function exportarWord(int $negocio)
    {
        $n = $this->negocioDelUsuario($negocio);
        $html = $this->renderHtml($n);

        return response($html, 200, [
            'Content-Type' => 'application/msword; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $this->filename($n, 'doc') . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    private function filename(Negocio $n, string $ext): string
    {
        return 'resumen_' . Str::slug($n->nombre) . '_' . date('Y-m-d') . '.' . $ext;
    }

    private function money(float $q, string $moneda): string
    {
        return $moneda . ' ' . number_format($q, 2, '.', ',');
    }

    private function e(?string $s): string
    {
        return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function renderHtml(Negocio $n): string
    {
        $moneda = $n->moneda;

        $cuentas = $n->cuentas()->get()->map(fn ($c) => [
            'nombre' => $c->nombre,
            'tipo' => $c->tipo,
            'saldo' => $c->saldoActual(),
        ]);
        $saldoTotal = $cuentas->sum('saldo');

        $ingresosTotales = (float) $n->movimientos()->where('tipo', 'ingreso')->sum('monto');
        $gastosTotales = (float) $n->movimientos()->where('tipo', 'gasto')->sum('monto');
        $neto = $ingresosTotales - $gastosTotales;

        $movimientos = $n->movimientos()->with(['cuenta', 'categoria'])
            ->orderByDesc('fecha')->limit(30)->get();

        $deudasPendientes = $n->deudas()->where('saldada', false)->orderBy('fecha_limite')->get();
        $totalDeudaPendiente = (float) $deudasPendientes->sum('saldo_pendiente');

        $colores = ['#ef4444', '#f59e0b', '#7c3aed', '#0ea5e9', '#16a34a', '#64748b'];
        $gastosPorCategoria = $n->movimientos()->where('tipo', 'gasto')
            ->selectRaw('categoria_id, SUM(monto) as total')
            ->groupBy('categoria_id')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->values()
            ->map(function ($row, $i) use ($colores) {
                $cat = $row->categoria_id ? Categoria::find($row->categoria_id) : null;
                return [
                    'label' => $cat?->nombre ?? 'Sin categoría',
                    'value' => (float) $row->total,
                    'color' => $colores[$i % count($colores)],
                ];
            })->all();

        $donutSvg = $this->buildSvgDonut($gastosPorCategoria);
        $fechaHoy = date('d/m/Y H:i');
        $nombreNegocio = $this->e($n->nombre);

        ob_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Resumen — <?= $nombreNegocio ?></title>
<style>
body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 10pt; margin: 0; padding: 20px; }
h1 { color: #065f46; font-size: 20pt; margin: 0 0 4pt; }
h2 { color: #0f172a; font-size: 13pt; margin: 16pt 0 6pt; border-bottom: 1.5pt solid #065f46; padding-bottom: 3pt; }
p.meta { color: #64748b; font-size: 9pt; margin: 0 0 14pt; }
table { border-collapse: collapse; width: 100%; margin: 4pt 0 10pt; }
th { background: #065f46; color: #fff; font-size: 9pt; text-align: left; padding: 5pt 7pt; }
td { border: 0.5pt solid #cbd5e1; padding: 4pt 7pt; font-size: 9pt; vertical-align: top; }
tr:nth-child(even) td { background: #f8fafc; }
.cards { width: 100%; }
.cards td { border: 0.5pt solid #cbd5e1; padding: 8pt; text-align: center; width: 25%; }
.lbl { font-size: 8pt; color: #64748b; text-transform: uppercase; }
.val { font-size: 14pt; font-weight: bold; margin-top: 3pt; }
.green { color: #16a34a; } .red { color: #dc2626; } .muted { color: #64748b; font-style: italic; }
.chart-row { width: 100%; margin: 10pt 0; }
.chart-row td { border: none; vertical-align: middle; }
</style>
</head>
<body>

<h1>💼 Resumen de Negocio</h1>
<p class="meta">
    <strong><?= $nombreNegocio ?></strong> · Moneda: <?= $this->e($moneda) ?><br>
    Generado: <?= $fechaHoy ?>
</p>

<h2>Panorama General</h2>
<table class="cards">
<tr>
<td><div class="lbl">Saldo total</div><div class="val"><?= $this->e($this->money($saldoTotal, $moneda)) ?></div></td>
<td><div class="lbl">Ingresos totales</div><div class="val green"><?= $this->e($this->money($ingresosTotales, $moneda)) ?></div></td>
<td><div class="lbl">Gastos totales</div><div class="val red"><?= $this->e($this->money($gastosTotales, $moneda)) ?></div></td>
<td><div class="lbl">Neto</div><div class="val <?= $neto < 0 ? 'red' : 'green' ?>"><?= $this->e($this->money($neto, $moneda)) ?></div></td>
</tr>
</table>

<h2>Gastos por categoría</h2>
<table class="chart-row"><tr><td style="width:190px;"><?= $donutSvg ?></td><td>
<?php if (empty($gastosPorCategoria)): ?>
    <p class="muted">Sin gastos registrados todavía.</p>
<?php else: foreach ($gastosPorCategoria as $s): ?>
    <div style="margin-bottom:4pt;"><span style="display:inline-block;width:9pt;height:9pt;background:<?= $s['color'] ?>;margin-right:5pt;"></span><?= $this->e($s['label']) ?> — <?= $this->e($this->money($s['value'], $moneda)) ?></div>
<?php endforeach; endif; ?>
</td></tr></table>

<h2>Cuentas</h2>
<table>
<tr><th>Cuenta</th><th>Tipo</th><th>Saldo</th></tr>
<?php foreach ($cuentas as $c): ?>
<tr><td><?= $this->e($c['nombre']) ?></td><td><?= $this->e($c['tipo']) ?></td><td><?= $this->e($this->money($c['saldo'], $moneda)) ?></td></tr>
<?php endforeach; ?>
</table>

<h2>Deudas pendientes (total: <?= $this->e($this->money($totalDeudaPendiente, $moneda)) ?>)</h2>
<?php if ($deudasPendientes->isEmpty()): ?>
<p class="muted">No hay deudas pendientes.</p>
<?php else: ?>
<table>
<tr><th>Acreedor</th><th>Monto total</th><th>Saldo pendiente</th><th>Fecha límite</th></tr>
<?php foreach ($deudasPendientes as $d): ?>
<tr><td><?= $this->e($d->acreedor) ?></td><td><?= $this->e($this->money((float)$d->monto_total, $moneda)) ?></td><td><?= $this->e($this->money((float)$d->saldo_pendiente, $moneda)) ?></td><td><?= $this->e($d->fecha_limite?->format('d/m/Y') ?? '—') ?></td></tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<h2>Movimientos recientes (últimos <?= $movimientos->count() ?>)</h2>
<?php if ($movimientos->isEmpty()): ?>
<p class="muted">No hay movimientos registrados todavía.</p>
<?php else: ?>
<table>
<tr><th>Fecha</th><th>Cuenta</th><th>Categoría</th><th>Tipo</th><th>Monto</th><th>Descripción</th></tr>
<?php foreach ($movimientos as $m): ?>
<tr>
<td><?= $this->e($m->fecha->format('d/m/Y')) ?></td>
<td><?= $this->e($m->cuenta?->nombre ?? '—') ?></td>
<td><?= $this->e($m->categoria?->nombre ?? '—') ?></td>
<td><?= $m->tipo === 'ingreso' ? 'Ingreso' : 'Gasto' ?></td>
<td class="<?= $m->tipo === 'ingreso' ? 'green' : 'red' ?>"><?= $this->e($this->money((float)$m->monto, $moneda)) ?></td>
<td><?= $this->e($m->descripcion ?? '') ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

</body>
</html>
<?php
        return ob_get_clean();
    }

    private function buildSvgDonut(array $slices): string
    {
        $slices = array_filter($slices, fn ($s) => $s['value'] > 0);
        $total = array_sum(array_column($slices, 'value'));
        if ($total <= 0) {
            return '<svg viewBox="0 0 200 200" width="160" height="160"><circle cx="100" cy="100" r="80" fill="#e2e8f0"/><circle cx="100" cy="100" r="50" fill="white"/></svg>';
        }

        $cx = 100; $cy = 100; $R = 80; $r = 50;
        $angle = -M_PI / 2;
        $paths = '';

        foreach ($slices as $s) {
            $sweep = ($s['value'] / $total) * 2 * M_PI;
            $end = $angle + $sweep;
            $large = $sweep > M_PI ? 1 : 0;
            $x1 = $cx + $R * cos($angle); $y1 = $cy + $R * sin($angle);
            $x2 = $cx + $R * cos($end); $y2 = $cy + $R * sin($end);
            $ix1 = $cx + $r * cos($end); $iy1 = $cy + $r * sin($end);
            $ix2 = $cx + $r * cos($angle); $iy2 = $cy + $r * sin($angle);
            $d = sprintf('M%.2f %.2f A%d %d 0 %d 1 %.2f %.2f L%.2f %.2f A%d %d 0 %d 0 %.2f %.2f Z',
                $x1, $y1, $R, $R, $large, $x2, $y2, $ix1, $iy1, $r, $r, $large, $ix2, $iy2);
            $paths .= '<path d="' . $d . '" fill="' . htmlspecialchars($s['color']) . '"/>';
            $angle = $end;
        }

        return '<svg viewBox="0 0 200 200" width="160" height="160">'
            . $paths
            . '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="white"/>'
            . '</svg>';
    }
}
