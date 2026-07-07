<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\FinanzasCrypto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ResumenWordController extends Controller
{
    /**
     * Genera el resumen financiero como PDF.
     */
    public function exportarPdf(Request $request)
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json(['error' => 'No autorizado'], 401);
        }

        $user = User::find($userId);
        $data = $this->loadData($userId);

        $html     = $this->renderPdfHtml($user, $data);
        $filename = 'resumen_finanzas_' . date('Y-m-d') . '.pdf';

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'     => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'dpi'             => 150,
            ]);

        return $pdf->download($filename);
    }

    /**
     * Genera un resumen financiero del usuario logueado y lo devuelve
     * como archivo .doc (HTML interpretado por Microsoft Word).
     */
    public function exportar(Request $request)
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json(['error' => 'No autorizado'], 401);
        }

        $user = User::find($userId);
        $data = $this->loadData($userId);

        $html = $this->renderHtml($user, $data);
        $filename = 'resumen_finanzas_' . date('Y-m-d') . '.doc';

        return response($html, 200, [
            'Content-Type'        => 'application/msword; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
            'Pragma'              => 'no-cache',
        ]);
    }

    private function loadData(int $userId): array
    {
        $default = [
            'ingresos' => [], 'gastos_fijos' => [], 'gastos_variables' => [],
            'deudas' => [], 'pagos_realizados' => [], 'historial_mensual' => [],
            'metas_ahorro' => [], 'expansion_scenarios' => [],
            'expansion_active_id' => null, 'exchange_rate' => 7.70,
        ];

        $row = DB::table('finanzas_data')->where('user_id', $userId)->first();
        if (!$row) return $default;

        $raw = FinanzasCrypto::decode($row->data);
        return is_array($raw) ? array_merge($default, $raw) : $default;
    }

    /** Convierte cualquier monto en USD a Quetzales */
    private function toGTQ(float $monto, string $moneda, float $rate): float
    {
        return $moneda === 'USD' ? $monto * $rate : $monto;
    }

    /** Normaliza la frecuencia de un ingreso/gasto a mensual */
    private function toMonthly(float $monto, string $frecuencia): float
    {
        return match (strtolower($frecuencia)) {
            'diario'    => $monto * 30,
            'semanal'   => $monto * 4.33,
            'quincenal' => $monto * 2,
            'anual'     => $monto / 12,
            default     => $monto, // 'mensual'
        };
    }

    private function money(float $q): string
    {
        return 'Q ' . number_format($q, 2, '.', ',');
    }

    private function e(?string $s): string
    {
        return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function renderPdfHtml(?User $user, array $d): string
    {
        $rate = (float) ($d['exchange_rate'] ?? 7.70);

        $totalIngresosQ = 0.0;
        foreach ($d['ingresos'] as $i) {
            if (!empty($i['temporal'])) continue;
            $totalIngresosQ += $this->toMonthly($this->toGTQ((float)($i['monto'] ?? 0), $i['moneda'] ?? 'GTQ', $rate), $i['frecuencia'] ?? 'mensual');
        }
        $totalGastosFijosQ = 0.0;
        foreach ($d['gastos_fijos'] as $g) {
            $totalGastosFijosQ += $this->toMonthly($this->toGTQ((float)($g['monto'] ?? 0), $g['moneda'] ?? 'GTQ', $rate), $g['frecuencia'] ?? 'mensual');
        }
        $totalGastosVarQ = 0.0;
        foreach ($d['gastos_variables'] as $g) {
            $totalGastosVarQ += $this->toGTQ((float)($g['monto'] ?? 0), $g['moneda'] ?? 'GTQ', $rate);
        }
        $totalDeudasQ = 0.0;
        $totalPagoMensualQ = 0.0;
        foreach ($d['deudas'] as $de) {
            $totalDeudasQ      += $this->toGTQ((float)($de['saldo_actual'] ?? 0), $de['moneda'] ?? 'GTQ', $rate);
            $totalPagoMensualQ += $this->toGTQ((float)($de['pago_objetivo_mensual'] ?? 0), $de['moneda'] ?? 'GTQ', $rate);
        }
        $superavitQ  = $totalIngresosQ - $totalGastosFijosQ - $totalPagoMensualQ - $totalGastosVarQ;
        $userName    = $this->e($user?->name ?? 'Usuario');
        $userEmail   = $this->e($user?->email ?? '');
        $fechaHoy    = date('d/m/Y H:i');

        // Gráfica donut SVG (presupuesto) generada en servidor
        $donutSvg = $this->buildSvgDonut([
            ['label' => 'Gastos Fijos',    'value' => $totalGastosFijosQ, 'color' => '#ef4444'],
            ['label' => 'Pagos Deudas',    'value' => $totalPagoMensualQ, 'color' => '#f59e0b'],
            ['label' => 'Gastos Variables','value' => $totalGastosVarQ,   'color' => '#7c3aed'],
            ['label' => 'Superávit',       'value' => max(0, $superavitQ),'color' => '#3b82f6'],
        ]);

        ob_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Resumen Financiero — <?= $userName ?></title>
<style>
body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 10pt; margin: 0; padding: 20px; }
h1 { color: #1d4ed8; font-size: 20pt; margin: 0 0 4pt; }
h2 { color: #0f172a; font-size: 13pt; margin: 16pt 0 6pt; border-bottom: 1.5pt solid #1d4ed8; padding-bottom: 3pt; }
p.meta { color: #64748b; font-size: 9pt; margin: 0 0 14pt; }
table { border-collapse: collapse; width: 100%; margin: 4pt 0 10pt; }
th { background: #1d4ed8; color: #fff; font-size: 9pt; text-align: left; padding: 5pt 7pt; }
td { border: 0.5pt solid #cbd5e1; padding: 4pt 7pt; font-size: 9pt; vertical-align: top; }
tr:nth-child(even) td { background: #f8fafc; }
.cards { width: 100%; }
.cards td { border: 0.5pt solid #cbd5e1; padding: 8pt; text-align: center; width: 25%; }
.lbl { font-size: 8pt; color: #64748b; text-transform: uppercase; }
.val { font-size: 14pt; font-weight: bold; margin-top: 3pt; }
.green { color: #16a34a; } .red { color: #dc2626; } .orange { color: #d97706; } .blue { color: #2563eb; }
.muted { color: #64748b; font-style: italic; } .right { text-align: right; } .center { text-align: center; }
.chart-row { width: 100%; margin: 10pt 0; }
.chart-row td { border: none; vertical-align: middle; }
</style>
</head>
<body>

<h1>📊 Resumen Financiero</h1>
<p class="meta">
    <strong><?= $userName ?></strong><?= $userEmail ? ' · ' . $userEmail : '' ?><br>
    Generado: <?= $fechaHoy ?> · Tipo de cambio: 1 USD = <?= number_format($rate, 4) ?> Q
</p>

<h2>Panorama General (mensual)</h2>
<table class="cards">
    <tr>
        <td><div class="lbl">Ingresos</div><div class="val green"><?= $this->money($totalIngresosQ) ?></div></td>
        <td><div class="lbl">Gastos Fijos</div><div class="val red"><?= $this->money($totalGastosFijosQ) ?></div></td>
        <td><div class="lbl">Pago Deudas</div><div class="val orange"><?= $this->money($totalPagoMensualQ) ?></div></td>
        <td><div class="lbl">Gastos Variables</div><div class="val red"><?= $this->money($totalGastosVarQ) ?></div></td>
    </tr>
    <tr>
        <td colspan="2"><div class="lbl">Superávit / (Déficit)</div><div class="val <?= $superavitQ >= 0 ? 'green' : 'red' ?>"><?= $this->money($superavitQ) ?></div></td>
        <td colspan="2"><div class="lbl">Deuda Total Pendiente</div><div class="val blue"><?= $this->money($totalDeudasQ) ?></div></td>
    </tr>
</table>

<h2>Distribución del Presupuesto</h2>
<table class="chart-row">
    <tr>
        <td style="width:200pt;"><?= $donutSvg ?></td>
        <td>
            <table style="width:100%;">
                <thead><tr><th>Concepto</th><th class="right">Monto (Q)</th><th class="right">% Ingresos</th></tr></thead>
                <tbody>
                    <tr><td>Gastos Fijos</td><td class="right"><?= $this->money($totalGastosFijosQ) ?></td><td class="right"><?= $totalIngresosQ > 0 ? number_format($totalGastosFijosQ/$totalIngresosQ*100,1).'%' : '-' ?></td></tr>
                    <tr><td>Pagos Deudas</td><td class="right"><?= $this->money($totalPagoMensualQ) ?></td><td class="right"><?= $totalIngresosQ > 0 ? number_format($totalPagoMensualQ/$totalIngresosQ*100,1).'%' : '-' ?></td></tr>
                    <tr><td>Gastos Variables</td><td class="right"><?= $this->money($totalGastosVarQ) ?></td><td class="right"><?= $totalIngresosQ > 0 ? number_format($totalGastosVarQ/$totalIngresosQ*100,1).'%' : '-' ?></td></tr>
                    <tr><td><?= $superavitQ >= 0 ? 'Superávit' : 'Déficit' ?></td><td class="right <?= $superavitQ >= 0 ? 'green' : 'red' ?>"><?= $this->money(abs($superavitQ)) ?></td><td class="right"><?= $totalIngresosQ > 0 ? number_format(abs($superavitQ)/$totalIngresosQ*100,1).'%' : '-' ?></td></tr>
                </tbody>
            </table>
        </td>
    </tr>
</table>

<h2>💰 Ingresos</h2>
<?php if (empty($d['ingresos'])): ?><p class="muted">No hay ingresos registrados.</p>
<?php else: ?>
<table>
    <thead><tr><th>Fuente</th><th>Monto</th><th>Frecuencia</th><th class="right">Mensual (Q)</th><th>Notas</th></tr></thead>
    <tbody><?php foreach ($d['ingresos'] as $i):
        $moneda = $i['moneda'] ?? 'GTQ'; $monto = (float)($i['monto'] ?? 0);
        $mensualQ = $this->toMonthly($this->toGTQ($monto, $moneda, $rate), $i['frecuencia'] ?? 'mensual');
    ?><tr>
        <td><?= $this->e($i['nombre'] ?? '') ?><?= !empty($i['temporal']) ? ' <em>(temporal)</em>' : '' ?></td>
        <td><?= ($moneda === 'USD' ? 'USD ' : 'Q ') . number_format($monto, 2) ?></td>
        <td><?= $this->e($i['frecuencia'] ?? '') ?></td>
        <td class="right"><?= $this->money($mensualQ) ?></td>
        <td><?= $this->e($i['notas'] ?? '') ?></td>
    </tr><?php endforeach; ?></tbody>
</table>
<?php endif; ?>

<h2>🏠 Gastos Fijos</h2>
<?php if (empty($d['gastos_fijos'])): ?><p class="muted">No hay gastos fijos registrados.</p>
<?php else: ?>
<table>
    <thead><tr><th>Concepto</th><th>Monto</th><th>Frecuencia</th><th class="right">Mensual (Q)</th><th>Notas</th></tr></thead>
    <tbody><?php foreach ($d['gastos_fijos'] as $g):
        $moneda = $g['moneda'] ?? 'GTQ'; $monto = (float)($g['monto'] ?? 0);
        $mensualQ = $this->toMonthly($this->toGTQ($monto, $moneda, $rate), $g['frecuencia'] ?? 'mensual');
    ?><tr>
        <td><?= $this->e($g['nombre'] ?? '') ?></td>
        <td><?= ($moneda === 'USD' ? 'USD ' : 'Q ') . number_format($monto, 2) ?></td>
        <td><?= $this->e($g['frecuencia'] ?? '') ?></td>
        <td class="right"><?= $this->money($mensualQ) ?></td>
        <td><?= $this->e($g['notas'] ?? '') ?></td>
    </tr><?php endforeach; ?></tbody>
</table>
<?php endif; ?>

<h2>🛒 Gastos Variables</h2>
<?php if (empty($d['gastos_variables'])): ?><p class="muted">No hay gastos variables registrados.</p>
<?php else: ?>
<table>
    <thead><tr><th>Categoría</th><th>Monto</th><th class="right">En Q</th><th>Notas</th></tr></thead>
    <tbody><?php foreach ($d['gastos_variables'] as $g):
        $moneda = $g['moneda'] ?? 'GTQ'; $monto = (float)($g['monto'] ?? 0);
    ?><tr>
        <td><?= $this->e($g['nombre'] ?? '') ?></td>
        <td><?= ($moneda === 'USD' ? 'USD ' : 'Q ') . number_format($monto, 2) ?></td>
        <td class="right"><?= $this->money($this->toGTQ($monto, $moneda, $rate)) ?></td>
        <td><?= $this->e($g['notas'] ?? '') ?></td>
    </tr><?php endforeach; ?></tbody>
</table>
<?php endif; ?>

<h2>💳 Deudas</h2>
<?php if (empty($d['deudas'])): ?><p class="muted">No hay deudas registradas.</p>
<?php else: ?>
<table>
    <thead><tr><th>Deuda</th><th>Saldo</th><th class="right">En Q</th><th>Tasa anual</th><th>Pago/mes</th><th>Notas</th></tr></thead>
    <tbody><?php foreach ($d['deudas'] as $de):
        $moneda = $de['moneda'] ?? 'GTQ'; $saldo = (float)($de['saldo_actual'] ?? 0);
        $pago   = (float)($de['pago_objetivo_mensual'] ?? 0);
    ?><tr>
        <td><?= $this->e($de['nombre'] ?? '') ?></td>
        <td><?= ($moneda === 'USD' ? 'USD ' : 'Q ') . number_format($saldo, 2) ?></td>
        <td class="right"><?= $this->money($this->toGTQ($saldo, $moneda, $rate)) ?></td>
        <td><?= number_format((float)($de['tasa_interes_anual'] ?? 0), 2) ?>%</td>
        <td><?= ($moneda === 'USD' ? 'USD ' : 'Q ') . number_format($pago, 2) ?></td>
        <td><?= $this->e($de['notas'] ?? '') ?></td>
    </tr><?php endforeach; ?></tbody>
</table>
<?php endif; ?>

<h2>🎯 Metas de Ahorro</h2>
<?php if (empty($d['metas_ahorro'])): ?><p class="muted">No hay metas registradas.</p>
<?php else: ?>
<table>
    <thead><tr><th>Meta</th><th class="right">Objetivo (Q)</th><th class="right">Ahorrado (Q)</th><th class="right">Progreso</th><th>Fecha</th></tr></thead>
    <tbody><?php foreach ($d['metas_ahorro'] as $m):
        $obj = (float)($m['objetivo'] ?? 0); $aho = (float)($m['ahorrado'] ?? 0);
        $pct = $obj > 0 ? min(100, ($aho/$obj)*100) : 0;
    ?><tr>
        <td><?= $this->e($m['nombre'] ?? '') ?></td>
        <td class="right"><?= $this->money($obj) ?></td>
        <td class="right"><?= $this->money($aho) ?></td>
        <td class="right"><?= number_format($pct, 1) ?>%</td>
        <td><?= $this->e($m['fecha'] ?? '') ?></td>
    </tr><?php endforeach; ?></tbody>
</table>
<?php endif; ?>

<h2>📅 Historial Mensual</h2>
<?php if (empty($d['historial_mensual'])): ?><p class="muted">No hay snapshots de historial.</p>
<?php else:
    $hist = $d['historial_mensual'];
    usort($hist, fn($a, $b) => strcmp($b['fecha'] ?? '', $a['fecha'] ?? ''));
?><table>
    <thead><tr><th>Mes</th><th class="right">Ingresos</th><th class="right">G. Fijos</th><th class="right">Pago Deudas</th><th class="right">Variables</th><th class="right">Superávit</th><th class="right">Deuda Total</th></tr></thead>
    <tbody><?php foreach ($hist as $h): ?><tr>
        <td><?= $this->e($h['fecha'] ?? '') ?></td>
        <td class="right"><?= $this->money((float)($h['ingresos'] ?? 0)) ?></td>
        <td class="right"><?= $this->money((float)($h['gastos_fijos'] ?? 0)) ?></td>
        <td class="right"><?= $this->money((float)($h['pagos_deudas'] ?? 0)) ?></td>
        <td class="right"><?= $this->money((float)($h['gastos_variables'] ?? 0)) ?></td>
        <td class="right <?= ((float)($h['superavit'] ?? 0)) >= 0 ? 'green' : 'red' ?>"><?= $this->money((float)($h['superavit'] ?? 0)) ?></td>
        <td class="right"><?= $this->money((float)($h['total_deuda'] ?? 0)) ?></td>
    </tr><?php endforeach; ?></tbody>
</table><?php endif; ?>

<p class="muted center" style="margin-top:20pt;">— Fin del reporte — · Generado por Finanzas GT</p>
</body>
</html>
<?php
        return ob_get_clean();
    }

    private function buildSvgDonut(array $slices): string
    {
        $slices = array_filter($slices, fn($s) => $s['value'] > 0);
        $total  = array_sum(array_column($slices, 'value'));
        if ($total <= 0) {
            return '<svg viewBox="0 0 200 200" width="180" height="180"><circle cx="100" cy="100" r="80" fill="#e2e8f0"/><circle cx="100" cy="100" r="50" fill="white"/></svg>';
        }

        $cx = 100; $cy = 100; $R = 80; $r = 50;
        $angle = -M_PI / 2;
        $paths = '';

        foreach ($slices as $s) {
            $sweep    = ($s['value'] / $total) * 2 * M_PI;
            $end      = $angle + $sweep;
            $large    = $sweep > M_PI ? 1 : 0;
            $x1  = $cx + $R * cos($angle);  $y1  = $cy + $R * sin($angle);
            $x2  = $cx + $R * cos($end);    $y2  = $cy + $R * sin($end);
            $ix1 = $cx + $r * cos($end);    $iy1 = $cy + $r * sin($end);
            $ix2 = $cx + $r * cos($angle);  $iy2 = $cy + $r * sin($angle);
            $d = sprintf('M%.2f %.2f A%d %d 0 %d 1 %.2f %.2f L%.2f %.2f A%d %d 0 %d 0 %.2f %.2f Z',
                $x1, $y1, $R, $R, $large, $x2, $y2, $ix1, $iy1, $r, $r, $large, $ix2, $iy2);
            $paths .= '<path d="' . $d . '" fill="' . htmlspecialchars($s['color']) . '"/>';
            $angle = $end;
        }

        // Leyenda debajo
        $legend = '';
        foreach ($slices as $s) {
            $pct = number_format($s['value'] / $total * 100, 1);
            $legend .= '<text x="10" y="' . (200 + 14) . '" fill="#64748b" font-size="9">'
                . htmlspecialchars($s['label']) . ': ' . $pct . '%</text>';
        }

        return '<svg viewBox="0 0 200 200" width="180" height="180">'
            . $paths
            . '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="white"/>'
            . '</svg>';
    }

    private function renderHtml(?User $user, array $d): string
    {
        $rate = (float) ($d['exchange_rate'] ?? 7.70);

        // Totales en Q
        $totalIngresosQ = 0.0;
        foreach ($d['ingresos'] as $i) {
            if (!empty($i['temporal'])) continue;
            $monto = (float) ($i['monto'] ?? 0);
            $totalIngresosQ += $this->toMonthly($this->toGTQ($monto, $i['moneda'] ?? 'GTQ', $rate), $i['frecuencia'] ?? 'mensual');
        }

        $totalGastosFijosQ = 0.0;
        foreach ($d['gastos_fijos'] as $g) {
            $monto = (float) ($g['monto'] ?? 0);
            $totalGastosFijosQ += $this->toMonthly($this->toGTQ($monto, $g['moneda'] ?? 'GTQ', $rate), $g['frecuencia'] ?? 'mensual');
        }

        $totalGastosVarQ = 0.0;
        foreach ($d['gastos_variables'] as $g) {
            $monto = (float) ($g['monto'] ?? 0);
            $totalGastosVarQ += $this->toGTQ($monto, $g['moneda'] ?? 'GTQ', $rate);
        }

        $totalDeudasQ = 0.0;
        $totalPagoMensualDeudasQ = 0.0;
        foreach ($d['deudas'] as $de) {
            $totalDeudasQ          += $this->toGTQ((float) ($de['saldo_actual'] ?? 0), $de['moneda'] ?? 'GTQ', $rate);
            $totalPagoMensualDeudasQ += $this->toGTQ((float) ($de['pago_objetivo_mensual'] ?? 0), $de['moneda'] ?? 'GTQ', $rate);
        }

        $superavitQ = $totalIngresosQ - $totalGastosFijosQ - $totalPagoMensualDeudasQ - $totalGastosVarQ;

        $userName  = $this->e($user?->name ?? 'Usuario');
        $userEmail = $this->e($user?->email ?? '');
        $fechaHoy  = date('d/m/Y H:i');

        ob_start();
        ?>
<!DOCTYPE html>
<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
<meta charset="UTF-8">
<title>Resumen Financiero — <?= $userName ?></title>
<!--[if gte mso 9]><xml>
<w:WordDocument><w:View>Print</w:View><w:Zoom>100</w:Zoom></w:WordDocument>
</xml><![endif]-->
<style>
@page WordSection1 { size: 21cm 29.7cm; margin: 2cm 2cm 2cm 2cm; }
div.WordSection1 { page: WordSection1; }
body { font-family: 'Calibri', sans-serif; color: #1f2937; font-size: 11pt; }
h1 { color: #1d4ed8; font-size: 22pt; margin: 0 0 6pt; }
h2 { color: #0f172a; font-size: 15pt; margin: 18pt 0 8pt; border-bottom: 1.5pt solid #1d4ed8; padding-bottom: 4pt; }
h3 { color: #334155; font-size: 12pt; margin: 12pt 0 6pt; }
p.meta { color: #64748b; font-size: 9pt; margin: 0 0 14pt; }
table { border-collapse: collapse; width: 100%; margin: 6pt 0 10pt; }
th { background: #1d4ed8; color: #fff; font-size: 10pt; text-align: left; padding: 6pt 8pt; }
td { border: 0.5pt solid #cbd5e1; padding: 5pt 8pt; font-size: 10pt; vertical-align: top; }
tr:nth-child(even) td { background: #f8fafc; }
.cards { width: 100%; margin: 6pt 0 12pt; }
.cards td { border: 0.5pt solid #cbd5e1; padding: 10pt; text-align: center; width: 25%; }
.cards .lbl { font-size: 9pt; color: #64748b; text-transform: uppercase; }
.cards .val { font-size: 16pt; font-weight: bold; margin-top: 4pt; }
.green { color: #16a34a; }
.red { color: #dc2626; }
.orange { color: #d97706; }
.blue { color: #2563eb; }
.muted { color: #64748b; font-style: italic; }
.right { text-align: right; }
.center { text-align: center; }
</style>
</head>
<body>
<div class="WordSection1">

<h1>📊 Resumen Financiero</h1>
<p class="meta">
    <strong><?= $userName ?></strong><?= $userEmail ? ' &middot; ' . $userEmail : '' ?><br>
    Generado: <?= $fechaHoy ?> &middot; Tipo de cambio: 1 USD = <?= number_format($rate, 4) ?> Q
</p>

<h2>Panorama General (mensual)</h2>
<table class="cards">
    <tr>
        <td>
            <div class="lbl">Ingresos</div>
            <div class="val green"><?= $this->money($totalIngresosQ) ?></div>
        </td>
        <td>
            <div class="lbl">Gastos Fijos</div>
            <div class="val red"><?= $this->money($totalGastosFijosQ) ?></div>
        </td>
        <td>
            <div class="lbl">Pago Deudas</div>
            <div class="val orange"><?= $this->money($totalPagoMensualDeudasQ) ?></div>
        </td>
        <td>
            <div class="lbl">Gastos Variables</div>
            <div class="val red"><?= $this->money($totalGastosVarQ) ?></div>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <div class="lbl">Superávit / (Déficit)</div>
            <div class="val <?= $superavitQ >= 0 ? 'green' : 'red' ?>"><?= $this->money($superavitQ) ?></div>
        </td>
        <td colspan="2">
            <div class="lbl">Deuda Total Pendiente</div>
            <div class="val blue"><?= $this->money($totalDeudasQ) ?></div>
        </td>
    </tr>
</table>

<h2>💰 Ingresos</h2>
<?php if (empty($d['ingresos'])): ?>
<p class="muted">No hay ingresos registrados.</p>
<?php else: ?>
<table>
    <thead><tr><th>Fuente</th><th>Monto</th><th>Frecuencia</th><th class="right">Equivalente Mensual (Q)</th><th>Notas</th></tr></thead>
    <tbody>
    <?php foreach ($d['ingresos'] as $i):
        $moneda = $i['moneda'] ?? 'GTQ';
        $monto  = (float) ($i['monto'] ?? 0);
        $mensualQ = $this->toMonthly($this->toGTQ($monto, $moneda, $rate), $i['frecuencia'] ?? 'mensual');
    ?>
        <tr>
            <td><?= $this->e($i['nombre'] ?? '') ?><?= !empty($i['temporal']) ? ' <span class="muted">(temporal)</span>' : '' ?></td>
            <td><?= ($moneda === 'USD' ? 'USD ' : 'Q ') . number_format($monto, 2) ?></td>
            <td><?= $this->e($i['frecuencia'] ?? '') ?></td>
            <td class="right"><?= $this->money($mensualQ) ?></td>
            <td><?= $this->e($i['notas'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<h2>🏠 Gastos Fijos</h2>
<?php if (empty($d['gastos_fijos'])): ?>
<p class="muted">No hay gastos fijos registrados.</p>
<?php else: ?>
<table>
    <thead><tr><th>Concepto</th><th>Monto</th><th>Frecuencia</th><th class="right">Equivalente Mensual (Q)</th><th>Notas</th></tr></thead>
    <tbody>
    <?php foreach ($d['gastos_fijos'] as $g):
        $moneda = $g['moneda'] ?? 'GTQ';
        $monto  = (float) ($g['monto'] ?? 0);
        $mensualQ = $this->toMonthly($this->toGTQ($monto, $moneda, $rate), $g['frecuencia'] ?? 'mensual');
    ?>
        <tr>
            <td><?= $this->e($g['nombre'] ?? '') ?></td>
            <td><?= ($moneda === 'USD' ? 'USD ' : 'Q ') . number_format($monto, 2) ?></td>
            <td><?= $this->e($g['frecuencia'] ?? '') ?></td>
            <td class="right"><?= $this->money($mensualQ) ?></td>
            <td><?= $this->e($g['notas'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<h2>🛒 Gastos Variables</h2>
<?php if (empty($d['gastos_variables'])): ?>
<p class="muted">No hay gastos variables registrados.</p>
<?php else: ?>
<table>
    <thead><tr><th>Categoría</th><th>Monto</th><th class="right">En Q</th><th>Notas</th></tr></thead>
    <tbody>
    <?php foreach ($d['gastos_variables'] as $g):
        $moneda = $g['moneda'] ?? 'GTQ';
        $monto  = (float) ($g['monto'] ?? 0);
        $enQ    = $this->toGTQ($monto, $moneda, $rate);
    ?>
        <tr>
            <td><?= $this->e($g['nombre'] ?? '') ?></td>
            <td><?= ($moneda === 'USD' ? 'USD ' : 'Q ') . number_format($monto, 2) ?></td>
            <td class="right"><?= $this->money($enQ) ?></td>
            <td><?= $this->e($g['notas'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<h2>💳 Deudas</h2>
<?php if (empty($d['deudas'])): ?>
<p class="muted">No hay deudas registradas.</p>
<?php else: ?>
<table>
    <thead><tr><th>Deuda</th><th>Saldo</th><th class="right">Saldo en Q</th><th>Tasa anual</th><th>Pago/mes</th><th>Notas</th></tr></thead>
    <tbody>
    <?php foreach ($d['deudas'] as $de):
        $moneda    = $de['moneda'] ?? 'GTQ';
        $saldo     = (float) ($de['saldo_actual'] ?? 0);
        $saldoQ    = $this->toGTQ($saldo, $moneda, $rate);
        $pago      = (float) ($de['pago_objetivo_mensual'] ?? 0);
    ?>
        <tr>
            <td><?= $this->e($de['nombre'] ?? '') ?></td>
            <td><?= ($moneda === 'USD' ? 'USD ' : 'Q ') . number_format($saldo, 2) ?></td>
            <td class="right"><?= $this->money($saldoQ) ?></td>
            <td><?= number_format((float)($de['tasa_interes_anual'] ?? 0), 2) ?>%</td>
            <td><?= ($moneda === 'USD' ? 'USD ' : 'Q ') . number_format($pago, 2) ?></td>
            <td><?= $this->e($de['notas'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<h2>🎯 Metas de Ahorro</h2>
<?php if (empty($d['metas_ahorro'])): ?>
<p class="muted">No hay metas registradas.</p>
<?php else: ?>
<table>
    <thead><tr><th>Meta</th><th class="right">Objetivo (Q)</th><th class="right">Ahorrado (Q)</th><th class="right">Progreso</th><th>Fecha</th></tr></thead>
    <tbody>
    <?php foreach ($d['metas_ahorro'] as $m):
        $obj  = (float) ($m['objetivo'] ?? 0);
        $aho  = (float) ($m['ahorrado'] ?? 0);
        $pct  = $obj > 0 ? min(100, ($aho / $obj) * 100) : 0;
    ?>
        <tr>
            <td><?= $this->e($m['nombre'] ?? '') ?></td>
            <td class="right"><?= $this->money($obj) ?></td>
            <td class="right"><?= $this->money($aho) ?></td>
            <td class="right"><?= number_format($pct, 1) ?>%</td>
            <td><?= $this->e($m['fecha'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<h2>🏗️ Escenarios de Expansión</h2>
<?php if (empty($d['expansion_scenarios'])): ?>
<p class="muted">No hay escenarios guardados.</p>
<?php else: ?>
<table>
    <thead><tr><th>Nombre</th><th>Costo / proyecto</th><th>% Financiado</th><th>Tasa</th><th>Plazo</th><th># Expansiones</th><th>Renta/apto</th></tr></thead>
    <tbody>
    <?php foreach ($d['expansion_scenarios'] as $s):
        $c = $s['config'] ?? [];
        $isActive = isset($d['expansion_active_id']) && $s['id'] === $d['expansion_active_id'];
    ?>
        <tr>
            <td><?= ($isActive ? '✓ ' : '') . $this->e($s['nombre'] ?? '') ?></td>
            <td>Q <?= number_format((float)($c['costo'] ?? 0), 0) ?></td>
            <td><?= round(((float)($c['pctFinanciado'] ?? 0)) * 100) ?>%</td>
            <td><?= number_format((float)($c['tasa'] ?? 0), 2) ?>%</td>
            <td><?= (int)($c['plazo'] ?? 0) ?> meses</td>
            <td><?= (int)($c['numExpansiones'] ?? 0) ?></td>
            <td>Q <?= number_format((float)($c['rentaApto'] ?? 0), 0) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<h2>📅 Historial Mensual</h2>
<?php if (empty($d['historial_mensual'])): ?>
<p class="muted">No hay snapshots de historial.</p>
<?php else:
    $hist = $d['historial_mensual'];
    usort($hist, fn($a, $b) => strcmp($b['fecha'] ?? '', $a['fecha'] ?? ''));
?>
<table>
    <thead><tr><th>Mes</th><th class="right">Ingresos</th><th class="right">G. Fijos</th><th class="right">Pago Deudas</th><th class="right">G. Variables</th><th class="right">Superávit</th><th class="right">Deuda Total</th></tr></thead>
    <tbody>
    <?php foreach ($hist as $h): ?>
        <tr>
            <td><?= $this->e($h['fecha'] ?? '') ?></td>
            <td class="right"><?= $this->money((float)($h['ingresos'] ?? 0)) ?></td>
            <td class="right"><?= $this->money((float)($h['gastos_fijos'] ?? 0)) ?></td>
            <td class="right"><?= $this->money((float)($h['pagos_deudas'] ?? 0)) ?></td>
            <td class="right"><?= $this->money((float)($h['gastos_variables'] ?? 0)) ?></td>
            <td class="right <?= ((float)($h['superavit'] ?? 0)) >= 0 ? 'green' : 'red' ?>"><?= $this->money((float)($h['superavit'] ?? 0)) ?></td>
            <td class="right"><?= $this->money((float)($h['total_deuda'] ?? 0)) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<h2>💸 Pagos Realizados (últimos 30)</h2>
<?php if (empty($d['pagos_realizados'])): ?>
<p class="muted">No hay pagos registrados.</p>
<?php else:
    $pagos = $d['pagos_realizados'];
    usort($pagos, fn($a, $b) => strcmp($b['mes_str'] ?? '', $a['mes_str'] ?? ''));
    $pagos = array_slice($pagos, 0, 30);
?>
<table>
    <thead><tr><th>Mes</th><th>Deuda</th><th>Monto</th><th class="right">En Q</th><th>Notas</th></tr></thead>
    <tbody>
    <?php foreach ($pagos as $p):
        $moneda = $p['moneda'] ?? 'GTQ';
        $monto  = (float) ($p['monto'] ?? 0);
        $enQ    = $this->toGTQ($monto, $moneda, $rate);
    ?>
        <tr>
            <td><?= $this->e($p['mes_str'] ?? '') ?></td>
            <td><?= $this->e($p['deuda_nombre'] ?? '') ?></td>
            <td><?= ($moneda === 'USD' ? 'USD ' : 'Q ') . number_format($monto, 2) ?></td>
            <td class="right"><?= $this->money($enQ) ?></td>
            <td><?= $this->e($p['notas'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<p class="meta" style="margin-top:24pt; text-align:center;">
    — Fin del reporte — &middot; Generado por Finanzas GT
</p>

</div>
</body>
</html>
<?php
        return ob_get_clean();
    }
}
