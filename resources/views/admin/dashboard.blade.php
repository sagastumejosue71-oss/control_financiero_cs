@extends('admin.layout')

@section('title', 'Dashboard — Admin')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Resumen del sistema')
@section('nav-dashboard', 'active')


@section('content')

<div class="stats-grid">
    <div class="card stat primary">
        <div class="icon-bubble"><i class="bi bi-people-fill"></i></div>
        <div>
            <div class="label">Usuarios totales</div>
            <div class="value">{{ $totalUsers }}</div>
        </div>
    </div>
    <div class="card stat success">
        <div class="icon-bubble"><i class="bi bi-check-circle-fill"></i></div>
        <div>
            <div class="label">Usuarios activos</div>
            <div class="value">{{ $activeUsers }}</div>
        </div>
    </div>
    <div class="card stat warning">
        <div class="icon-bubble"><i class="bi bi-shield-fill-check"></i></div>
        <div>
            <div class="label">Administradores</div>
            <div class="value">{{ $adminUsers }}</div>
        </div>
    </div>
</div>

<div class="section-card">
    <div class="section-header">
        <h3><i class="bi bi-lightning-charge-fill"></i> Acciones rápidas</h3>
    </div>
    <div class="form-section" style="display:flex; flex-wrap:wrap; gap:12px;">
        <a class="btn btn-primary" href="/admin/usuarios">
            <i class="bi bi-people"></i> Gestionar usuarios
        </a>
        <a class="btn btn-outline" href="/finanzas">
            <i class="bi bi-cash-coin"></i> Abrir app financiera
        </a>
    </div>
</div>

{{-- ── Gráficas circulares ── --}}
@php
    $inactiveUsers  = $totalUsers - $activeUsers;
    $activeRegular  = max(0, $activeUsers - $adminUsers);
@endphp
<div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:4px;">

    {{-- Donut 1: Estado de usuarios --}}
    <div class="section-card">
        <div class="section-header">
            <h3><i class="bi bi-pie-chart-fill"></i> Estado de Usuarios</h3>
        </div>
        <div style="padding:24px;display:flex;align-items:center;justify-content:center;gap:28px;flex-wrap:wrap;">
            <div style="position:relative;width:180px;height:180px;flex-shrink:0;">
                <canvas id="chart-estado" width="180" height="180"></canvas>
                <div id="chart-estado-center" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;text-align:center;">
                    <div style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Total</div>
                    <div style="font-size:22px;font-weight:800;color:#e5e7eb;">{{ $totalUsers }}</div>
                    <div style="font-size:11px;color:#9ca3af;">usuarios</div>
                </div>
            </div>
            <div id="legend-estado" style="display:flex;flex-direction:column;gap:10px;min-width:130px;"></div>
        </div>
    </div>

    {{-- Donut 2: Distribución de roles --}}
    <div class="section-card">
        <div class="section-header">
            <h3><i class="bi bi-shield-fill"></i> Distribución de Roles</h3>
        </div>
        <div style="padding:24px;display:flex;align-items:center;justify-content:center;gap:28px;flex-wrap:wrap;">
            <div style="position:relative;width:180px;height:180px;flex-shrink:0;">
                <canvas id="chart-roles" width="180" height="180"></canvas>
                <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;text-align:center;">
                    <div style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Activos</div>
                    <div style="font-size:22px;font-weight:800;color:#e5e7eb;">{{ $activeUsers }}</div>
                    <div style="font-size:11px;color:#9ca3af;">de {{ $totalUsers }}</div>
                </div>
            </div>
            <div id="legend-roles" style="display:flex;flex-direction:column;gap:10px;min-width:130px;"></div>
        </div>
    </div>
</div>

<div class="section-card" style="margin-top:4px;">
    <div class="section-header">
        <h3><i class="bi bi-info-circle"></i> Información</h3>
    </div>
    <div class="form-section" style="color:#cbd5e1; line-height:1.6; font-size:14px;">
        <p style="margin:0 0 10px;">
            Este panel está reservado para el rol <strong style="color:#a5b4fc;">administrador</strong>.
            Desde aquí puedes crear, editar, activar/desactivar y eliminar cuentas de usuario.
        </p>
        <p style="margin:0; color:#94a3b8; font-size:13px;">
            La aplicación financiera principal está disponible en
            <a href="/finanzas" style="color:#a5b4fc; text-decoration:underline;">/finanzas</a>.
        </p>
    </div>
</div>

@endsection

@push('scripts')
<script src="/vendor/chartjs/chart.umd.js"></script>
<script>
(function () {
    const donutOpts = (labels, data, colors) => ({
        type: 'doughnut',
        data: {
            labels,
            datasets: [{ data, backgroundColor: colors, borderWidth: 2, borderColor: '#111827', hoverOffset: 6 }],
        },
        options: {
            cutout: '62%',
            plugins: { legend: { display: false }, tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.label}: ${ctx.raw} (${((ctx.raw / ctx.dataset.data.reduce((a,b)=>a+b,0))*100).toFixed(1)}%)`
                }
            }},
        },
    });

    function buildLegend(legendId, labels, data, colors) {
        const total  = data.reduce((a, b) => a + b, 0);
        const legend = document.getElementById(legendId);
        if (!legend) return;
        legend.innerHTML = labels.map((l, i) => `
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:12px;height:12px;border-radius:50%;background:${colors[i]};flex-shrink:0;"></div>
                <div>
                    <div style="font-size:12px;font-weight:600;color:#e5e7eb;">${l}</div>
                    <div style="font-size:11px;color:#9ca3af;">${data[i]} · ${total > 0 ? ((data[i]/total)*100).toFixed(1) : 0}%</div>
                </div>
            </div>`).join('');
    }

    // Chart 1 — Estado de usuarios (activos / admins / inactivos)
    const estadoData   = [{{ $activeRegular }}, {{ $adminUsers }}, {{ $inactiveUsers }}];
    const estadoLabels = ['Activos', 'Admins', 'Inactivos'];
    const estadoColors = ['#10b981', '#6366f1', '#ef4444'];
    new Chart(document.getElementById('chart-estado'), donutOpts(estadoLabels, estadoData, estadoColors));
    buildLegend('legend-estado', estadoLabels, estadoData, estadoColors);

    // Chart 2 — Distribución de roles (usuarios / administradores)
    const rolesData   = [{{ $activeRegular }}, {{ $adminUsers }}];
    const rolesLabels = ['Usuarios', 'Administradores'];
    const rolesColors = ['#3b82f6', '#f59e0b'];
    new Chart(document.getElementById('chart-roles'), donutOpts(rolesLabels, rolesData, rolesColors));
    buildLegend('legend-roles', rolesLabels, rolesData, rolesColors);
})();
</script>
@endpush
