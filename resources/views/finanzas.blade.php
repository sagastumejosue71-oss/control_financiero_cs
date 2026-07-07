<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Finanzas GT — Control Financiero Personal</title>
    <meta name="description" content="Control financiero personal: ingresos, gastos, deudas y metas de ahorro en un solo panel.">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E%F0%9F%92%BC%3C/text%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="/vendor/sweetalert2/sweetalert2.min.css">
    <style>
        :root { --sidebar-w: 260px; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Inter', system-ui, sans-serif; background: #f1f5f9; }
        #app { display: flex; height: 100vh; overflow: hidden; }

        /* SIDEBAR */
        #sidebar {
            width: var(--sidebar-w);
            background: #0f172a;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            overflow-y: auto;
        }
        .sidebar-logo { padding: 22px 20px 18px; border-bottom: 1px solid #1e293b; }
        .sidebar-logo h1 { color: #f8fafc; font-size: 16px; font-weight: 700; margin: 0; }
        .sidebar-logo p { color: #64748b; font-size: 11px; margin: 3px 0 0; }
        .nav-section { padding: 16px 12px 6px; color: #475569; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; margin: 2px 8px;
            border-radius: 8px; cursor: pointer;
            color: #94a3b8; font-size: 14px; font-weight: 500;
            transition: all 0.15s; text-decoration: none;
        }
        .nav-item:hover { background: #1e293b; color: #e2e8f0; }
        .nav-item.active { background: #1d4ed8; color: #fff; }
        .nav-item .icon { font-size: 17px; width: 22px; text-align: center; flex-shrink: 0; }

        /* EXCHANGE RATE WIDGET */
        .exchange-widget {
            margin: auto 12px 16px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 12px 14px;
        }
        .exchange-widget .ew-label { color: #64748b; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px; }
        .exchange-widget .ew-row { display: flex; align-items: center; gap: 6px; }
        .exchange-widget .ew-badge { background: #0f172a; color: #22d3ee; font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 6px; }
        .exchange-widget input {
            width: 70px; background: #0f172a; border: 1px solid #475569;
            color: #f8fafc; padding: 5px 8px; border-radius: 6px; font-size: 13px;
            font-weight: 600; outline: none;
        }
        .exchange-widget input:focus { border-color: #3b82f6; }
        .exchange-widget .ew-hint { color: #475569; font-size: 10px; margin-top: 6px; }

        /* MAIN */
        #main { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        #topbar {
            background: white; border-bottom: 1px solid #e2e8f0;
            padding: 14px 28px;
            display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;
        }
        #topbar h2 { font-size: 20px; font-weight: 700; color: #0f172a; margin: 0; }
        #topbar .subtitle { font-size: 12px; color: #64748b; }
        #content { flex: 1; overflow-y: auto; padding: 24px 28px; }

        /* PAGES */
        .page { display: none; }
        .page.active { display: block; }

        /* CARDS */
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(195px, 1fr)); gap: 14px; margin-bottom: 20px; }
        .card { background: white; border-radius: 12px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .card-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .card-value { font-size: 24px; font-weight: 700; margin: 5px 0 2px; }
        .card-sub { font-size: 11px; color: #94a3b8; }
        .card.income .card-value { color: #16a34a; }
        .card.expense .card-value { color: #dc2626; }
        .card.debt .card-value { color: #d97706; }
        .card.available .card-value { color: #2563eb; }

        /* SECTIONS */
        .section-card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 20px; overflow: hidden; }
        .section-header { display: flex; align-items: center; justify-content: space-between; padding: 15px 20px; border-bottom: 1px solid #f1f5f9; }
        .section-header h3 { font-size: 15px; font-weight: 700; color: #0f172a; margin: 0; }

        /* TABLES */
        table { width: 100%; border-collapse: collapse; }
        th { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; padding: 10px 12px; text-align: left; border-bottom: 2px solid #f1f5f9; white-space: nowrap; }
        td { padding: 11px 12px; font-size: 13px; color: #334155; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }
        .empty-row td { text-align: center; color: #94a3b8; padding: 32px; font-size: 14px; }

        /* BADGES */
        .badge { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-green { background: #dcfce7; color: #16a34a; }
        .badge-red { background: #fee2e2; color: #dc2626; }
        .badge-yellow { background: #fef9c3; color: #ca8a04; }
        .badge-blue { background: #dbeafe; color: #2563eb; }
        .badge-gray { background: #f1f5f9; color: #475569; }
        .badge-cyan { background: #cffafe; color: #0e7490; }
        .badge-usd { background: #f0fdf4; color: #15803d; border: 1px solid #86efac; }
        .badge-gtq { background: #eff6ff; color: #1d4ed8; border: 1px solid #93c5fd; }

        /* BUTTONS */
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: all 0.15s; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-success { background: #22c55e; color: white; }
        .btn-success:hover { background: #16a34a; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-ghost { background: transparent; color: #64748b; padding: 5px 8px; }
        .btn-ghost:hover { background: #f1f5f9; color: #0f172a; }
        .btn-outline { background: white; color: #374151; border: 1px solid #d1d5db; }
        .btn-outline:hover { background: #f9fafb; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }

        /* FORMS */
        .form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(185px, 1fr)); gap: 12px; }
        .form-group { display: flex; flex-direction: column; gap: 4px; }
        .form-group label { font-size: 12px; font-weight: 600; color: #374151; }
        .form-group input, .form-group select {
            padding: 8px 11px; border: 1px solid #d1d5db;
            border-radius: 8px; font-size: 13px; color: #111827;
            outline: none; transition: border 0.15s; background: white;
        }
        .form-group input:focus, .form-group select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .form-section { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 18px; margin-bottom: 18px; display: none; }
        .form-section.open { display: block; }
        .form-actions { display: flex; gap: 10px; margin-top: 14px; }

        /* CURRENCY PILL */
        .currency-pill { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 700; }
        .pill-usd { background: #dcfce7; color: #15803d; }
        .pill-gtq { background: #dbeafe; color: #1d4ed8; }

        /* ALERTS */
        .alert { display: flex; align-items: flex-start; gap: 10px; padding: 11px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 8px; }
        .alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .alert-warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
        .alert-info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }

        /* PROGRESS BAR */
        .progress-bar { height: 7px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 4px; }

        /* TOTALS */
        .total-row td { font-weight: 700; color: #0f172a; background: #f8fafc; border-top: 2px solid #e2e8f0 !important; }

        /* MODAL */
        #modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100; align-items: center; justify-content: center; }
        #modal-overlay.open { display: flex; }
        #modal { background: white; border-radius: 14px; padding: 26px; width: 520px; max-width: 95vw; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        #modal h3 { font-size: 17px; font-weight: 700; margin: 0 0 18px; color: #0f172a; }

        /* CHART */
        .bar-col { display:flex; flex-direction:column; align-items:center; flex:1; min-width:44px; max-width:70px; }
        .bar-stack { width:100%; display:flex; flex-direction:column-reverse; border-radius:4px 4px 0 0; overflow:hidden; cursor:pointer; transition:opacity .15s; }
        .bar-stack:hover { opacity:.85; }
        .bar-seg { width:100%; transition:height .4s; }
        .bar-label { font-size:9px; color:#64748b; text-align:center; padding-top:4px; white-space:nowrap; }
        .bar-event { font-size:8px; color:#7c3aed; font-weight:700; text-align:center; }
        .bar-col.debt-free .bar-label { color:#16a34a; font-weight:700; }

        /* DONUT CHARTS */
        .donut-slice { transition:opacity .15s, transform .15s; transform-origin:110px 110px; cursor:pointer; }
        .donut-slice:hover { opacity:.85; transform:scale(1.04); }
        .donut-legend-item { display:flex; align-items:center; gap:8px; padding:5px 8px; border-radius:8px; cursor:pointer; transition:background .15s; }
        .donut-legend-item:hover { background:#f1f5f9; }
        .donut-legend-dot { width:12px; height:12px; border-radius:50%; flex-shrink:0; }
        .donut-legend-name { font-size:12px; color:#334155; flex:1; }
        .donut-legend-val { font-size:12px; font-weight:700; color:#0f172a; }
        .donut-legend-pct { font-size:11px; color:#94a3b8; min-width:36px; text-align:right; }

        /* TIMELINE */
        .timeline { position:relative; padding-left:28px; }
        .timeline::before { content:''; position:absolute; left:10px; top:8px; bottom:8px; width:2px; background:#e2e8f0; }
        .tl-item { position:relative; margin-bottom:18px; }
        .tl-dot { position:absolute; left:-22px; top:3px; width:14px; height:14px; border-radius:50%; border:2px solid white; box-shadow:0 0 0 2px currentColor; }
        .tl-date { font-size:11px; font-weight:700; color:#64748b; margin-bottom:2px; }
        .tl-title { font-size:14px; font-weight:700; }
        .tl-sub { font-size:12px; color:#64748b; margin-top:2px; }

        /* STAT BANNER */
        .stat-banner-free { background:linear-gradient(135deg,#16a34a,#059669); color:white; border-radius:14px; padding:20px 28px; display:flex; align-items:center; justify-content:space-between; }
        .stat-banner-nodebts { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:16px 20px; color:#166534; }
        .stat-banner-nodebt { background:#eff6ff; border:1px solid #bfdbfe; border-radius:12px; padding:14px 20px; color:#1e40af; font-size:13px; }

        /* ====== MODO OSCURO ====== */
        body.dark-mode { background: #0f172a !important; }
        body.dark-mode #main { background: #0f172a; }
        body.dark-mode #content { background: #0f172a; }
        body.dark-mode #topbar { background: #1e293b !important; border-color: #334155 !important; }
        body.dark-mode #topbar h2 { color: #f8fafc !important; }
        body.dark-mode #topbar .subtitle { color: #64748b !important; }
        body.dark-mode #topbar button[type="submit"] { background: #0f172a !important; border-color: #334155 !important; color: #94a3b8 !important; }
        body.dark-mode #hamburger { color: #94a3b8 !important; }
        body.dark-mode #hamburger:hover { background: #334155 !important; }
        body.dark-mode .card { background: #1e293b !important; box-shadow: 0 1px 3px rgba(0,0,0,.3) !important; }
        body.dark-mode .card-label { color: #64748b !important; }
        body.dark-mode .card-sub { color: #475569 !important; }
        body.dark-mode .section-card { background: #1e293b !important; box-shadow: 0 1px 3px rgba(0,0,0,.3) !important; }
        body.dark-mode .section-header { border-color: #334155 !important; }
        body.dark-mode .section-header h3 { color: #e2e8f0 !important; }
        body.dark-mode table th { background: #0f172a !important; color: #64748b !important; border-color: #334155 !important; }
        body.dark-mode table td { border-color: #334155 !important; color: #cbd5e1 !important; }
        body.dark-mode .total-row { background: #0f172a !important; }
        body.dark-mode .total-row td { color: #e2e8f0 !important; }
        body.dark-mode .empty-row td { background: #1e293b !important; color: #475569 !important; }
        body.dark-mode .form-section { background: #1e293b !important; border-color: #3b82f6 !important; }
        body.dark-mode .form-section h4 { color: #e2e8f0 !important; }
        body.dark-mode .form-group label { color: #94a3b8 !important; }
        body.dark-mode input:not([type="range"]), body.dark-mode select, body.dark-mode textarea { background: #0f172a !important; border-color: #475569 !important; color: #f8fafc !important; }
        body.dark-mode input::placeholder { color: #475569 !important; }
        body.dark-mode .alert-info { background: #1e3a5f !important; border-color: #1d4ed8 !important; color: #93c5fd !important; }
        body.dark-mode .alert-warning { background: #431407 !important; border-color: #d97706 !important; color: #fcd34d !important; }
        body.dark-mode .alert-danger { background: #450a0a !important; border-color: #dc2626 !important; color: #fca5a5 !important; }
        body.dark-mode .alert-success { background: #052e16 !important; border-color: #16a34a !important; color: #86efac !important; }
        body.dark-mode .btn-outline { border-color: #475569 !important; color: #94a3b8 !important; }
        body.dark-mode .btn-outline:hover { background: #334155 !important; }
        body.dark-mode .btn-ghost { color: #64748b !important; }
        body.dark-mode .btn-ghost:hover { background: #334155 !important; }
        body.dark-mode .progress-bar { background: #334155 !important; }
        body.dark-mode .badge-yellow { background: #431407 !important; color: #fcd34d !important; }
        body.dark-mode .badge-green { background: #052e16 !important; color: #86efac !important; }
        body.dark-mode .badge-red { background: #450a0a !important; color: #fca5a5 !important; }
        body.dark-mode .badge-blue { background: #1e3a5f !important; color: #93c5fd !important; }
        body.dark-mode .tl-date { color: #64748b !important; }
        body.dark-mode .tl-sub { color: #475569 !important; }
        body.dark-mode .tl-title { color: #e2e8f0 !important; }
        body.dark-mode .timeline::before { background: #334155 !important; }
        body.dark-mode .stat-banner-nodebt { background: #1e3a5f !important; border-color: #1d4ed8 !important; color: #93c5fd !important; }
        body.dark-mode #modal { background: #1e293b !important; }
        body.dark-mode #modal h3 { color: #f8fafc !important; }
        body.dark-mode .exchange-widget { background: #0f172a !important; }
        body.dark-mode .ew-hint { color: #334155 !important; }

        /* HAMBURGER + MOBILE OVERLAY */
        #hamburger { display: none; background: none; border: none; cursor: pointer; padding: 6px 8px; font-size: 22px; color: #475569; border-radius: 8px; line-height: 1; }
        #hamburger:hover { background: #f1f5f9; }
        #mobile-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 199; }

        @media (max-width: 768px) {
            #sidebar {
                position: fixed; left: -280px; top: 0; bottom: 0;
                z-index: 200; width: 260px !important;
                transition: left 0.25s ease;
            }
            #sidebar.mobile-open { left: 0; box-shadow: 6px 0 30px rgba(0,0,0,.45); }
            #mobile-overlay.show { display: block; }
            #hamburger { display: inline-flex; }
            #content { padding: 14px 12px 80px; }
            #topbar { padding: 10px 14px; }
            .cards-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
        }
    </style>
</head>
<body>
<div id="app">

    <!-- SIDEBAR -->
    <aside id="sidebar">
        <div class="sidebar-logo">
            <h1>💼 Finanzas GT</h1>
            <p>Control Personal</p>
        </div>
        <div class="nav-section">Menú</div>
        <a class="nav-item active" onclick="navigate('dashboard')" data-page="dashboard">
            <span class="icon"><i class="bi bi-speedometer2"></i></span><span>Dashboard</span>
        </a>
        <a class="nav-item" onclick="navigate('ingresos')" data-page="ingresos">
            <span class="icon"><i class="bi bi-cash-coin"></i></span><span>Ingresos</span>
        </a>
        <a class="nav-item" onclick="navigate('gastos-fijos')" data-page="gastos-fijos">
            <span class="icon"><i class="bi bi-house-door"></i></span><span>Gastos Fijos</span>
        </a>
        <a class="nav-item" onclick="navigate('gastos-variables')" data-page="gastos-variables">
            <span class="icon"><i class="bi bi-cart3"></i></span><span>Gastos Variables</span>
        </a>
        <a class="nav-item" onclick="navigate('deudas')" data-page="deudas">
            <span class="icon"><i class="bi bi-credit-card"></i></span><span>Deudas</span>
        </a>
        <a class="nav-item" onclick="navigate('estrategia')" data-page="estrategia">
            <span class="icon"><i class="bi bi-bullseye"></i></span><span>Intereses & Estrategia</span>
        </a>
        <a class="nav-item" onclick="navigate('estadisticas')" data-page="estadisticas">
            <span class="icon"><i class="bi bi-graph-up"></i></span><span>Estadísticas</span>
        </a>
        @if($isAdmin)
        <a class="nav-item" href="/admin" style="background:#1e3a8a;color:#bfdbfe;">
            <span class="icon"><i class="bi bi-gear-fill"></i></span><span>Panel de Admin</span>
        </a>
        @endif
        <div class="nav-section">Seguimiento</div>
        <a class="nav-item" onclick="navigate('pagos')" data-page="pagos">
            <span class="icon"><i class="bi bi-cash-stack"></i></span><span>Pagos Reales</span>
        </a>
        <a class="nav-item" onclick="navigate('historial')" data-page="historial">
            <span class="icon"><i class="bi bi-calendar3"></i></span><span>Historial</span>
        </a>
        <div class="nav-section">Herramientas</div>
        <a class="nav-item" onclick="navigate('metas')" data-page="metas">
            <span class="icon"><i class="bi bi-piggy-bank"></i></span><span>Metas de Ahorro</span>
        </a>
        <a class="nav-item" onclick="navigate('calculadora')" data-page="calculadora">
            <span class="icon"><i class="bi bi-calculator"></i></span><span>Calculadora</span>
        </a>
        <a class="nav-item" onclick="navigate('tarjetas')" data-page="tarjetas">
            <span class="icon"><i class="bi bi-wallet2"></i></span><span>Mis Tarjetas</span>
        </a>
        <a class="nav-item" onclick="toggleDarkMode()" id="dark-mode-btn" data-page="_dm" style="margin-top:4px;">
            <span class="icon" id="dark-mode-icon"><i class="bi bi-moon-stars"></i></span><span id="dark-mode-label">Modo Oscuro</span>
        </a>

        <!-- TIPO DE CAMBIO -->
        <div class="exchange-widget" style="margin-top:auto;">
            <div class="ew-label">Tipo de Cambio</div>
            <div class="ew-row">
                <span class="ew-badge">1 USD</span>
                <span style="color:#64748b;font-size:12px;">=</span>
                <input type="number" id="exchange-rate" step="0.01" min="1" placeholder="7.70"
                       onchange="saveExchangeRate(this.value)" oninput="saveExchangeRate(this.value)">
                <span style="color:#94a3b8;font-size:12px;font-weight:700;">Q</span>
            </div>
            <div class="ew-hint">Actualiza para recalcular todo en Q</div>
        </div>

        <!-- DESCARGAR RESUMEN -->
        <div class="exchange-widget" style="margin-top:8px;">
            <div class="ew-label"><i class="bi bi-file-earmark-arrow-down"></i> Descargar Resumen</div>
            <a href="/api/exportar-resumen-pdf"
                style="width:100%;background:#dc2626;color:white;border:none;border-radius:6px;padding:8px 10px;font-size:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;text-decoration:none;box-sizing:border-box;margin-bottom:6px;">
                <i class="bi bi-file-earmark-pdf"></i> Descargar PDF
            </a>
            <a href="/api/exportar-resumen-word"
                style="width:100%;background:#2563eb;color:white;border:none;border-radius:6px;padding:8px 10px;font-size:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;text-decoration:none;box-sizing:border-box;">
                <i class="bi bi-file-earmark-word"></i> Descargar Word
            </a>
        </div>
    </aside>

    <div id="mobile-overlay" onclick="closeMobileMenu()"></div>

    <!-- MAIN -->
    <div id="main">
        <div id="topbar">
            <div style="display:flex;align-items:center;gap:10px;">
                <button id="hamburger" onclick="openMobileMenu()"><i class="bi bi-list"></i></button>
                <div>
                    <h2 id="page-title">Dashboard</h2>
                    <div class="subtitle" id="page-subtitle">Resumen financiero del mes · Todo en Quetzales (Q)</div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <div id="topbar-actions"></div>
                @if($isAdmin)
                <a href="/admin" style="background:#1e3a8a;border:1px solid #1e40af;color:#bfdbfe;font-size:12px;font-weight:700;padding:6px 12px;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                    <i class="bi bi-gear-fill"></i> Panel Admin
                </a>
                @endif
                <a href="/perfil" style="background:#1e293b;border:1px solid #334155;color:#94a3b8;font-size:12px;font-weight:600;padding:6px 12px;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:border-color .15s;"
                    onmouseover="this.style.borderColor='#3b82f6';this.style.color='#f8fafc'"
                    onmouseout="this.style.borderColor='#334155';this.style.color='#94a3b8'">
                    @if($currentUser->avatar)
                        <img src="{{ $currentUser->avatar }}" style="width:18px;height:18px;border-radius:50%;object-fit:cover;">
                    @else
                        <i class="bi bi-person-circle"></i>
                    @endif
                    {{ $currentUser->name }}
                </a>
                <form method="POST" action="/logout" style="margin:0;">
                    @csrf
                    <button type="submit" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;font-size:12px;font-weight:600;padding:6px 12px;border-radius:8px;cursor:pointer;">
                        <i class="bi bi-box-arrow-right"></i> Salir
                    </button>
                </form>
            </div>
        </div>
        <div id="content">

            <!-- ============ DASHBOARD ============ -->
            <div class="page active" id="page-dashboard">
                <div class="cards-grid" id="dash-cards"></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
                    <div class="section-card">
                        <div class="section-header"><h3><i class="bi bi-list-ul"></i> Distribución del Presupuesto</h3></div>
                        <div style="padding:18px;" id="dash-budget"></div>
                    </div>
                    <div class="section-card">
                        <div class="section-header"><h3><i class="bi bi-exclamation-triangle-fill"></i> Alertas</h3></div>
                        <div style="padding:18px;" id="dash-alerts"></div>
                    </div>
                </div>
                <!-- ── Gráficas circulares del dashboard ── -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:18px;">
                    <div class="section-card">
                        <div class="section-header"><h3><i class="bi bi-pie-chart-fill"></i> Distribución del Presupuesto</h3></div>
                        <div style="padding:18px;">
                            <div style="display:flex;align-items:flex-start;gap:16px;flex-wrap:wrap;">
                                <div style="position:relative;flex-shrink:0;">
                                    <svg id="donut-dash-presup" viewBox="0 0 220 220" width="190" height="190" style="display:block;"></svg>
                                    <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;text-align:center;">
                                        <div id="dp-dash-label" style="font-size:10px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.04em;"></div>
                                        <div id="dp-dash-value" style="font-size:13px;font-weight:700;color:#0f172a;"></div>
                                        <div id="dp-dash-pct" style="font-size:11px;color:#64748b;"></div>
                                    </div>
                                </div>
                                <div id="donut-dash-presup-legend" style="flex:1;min-width:120px;padding-top:8px;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="section-card">
                        <div class="section-header"><h3><i class="bi bi-credit-card-2-front"></i> Composición de Deudas</h3></div>
                        <div style="padding:18px;">
                            <div style="display:flex;align-items:flex-start;gap:16px;flex-wrap:wrap;">
                                <div style="position:relative;flex-shrink:0;">
                                    <svg id="donut-dash-deudas" viewBox="0 0 220 220" width="190" height="190" style="display:block;"></svg>
                                    <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;text-align:center;">
                                        <div id="dd-dash-label" style="font-size:10px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.04em;"></div>
                                        <div id="dd-dash-value" style="font-size:13px;font-weight:700;color:#0f172a;"></div>
                                        <div id="dd-dash-pct" style="font-size:11px;color:#64748b;"></div>
                                    </div>
                                </div>
                                <div id="donut-dash-deudas-legend" style="flex:1;min-width:120px;padding-top:8px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:18px;">
                    <div class="section-card">
                        <div class="section-header"><h3><i class="bi bi-bell-fill"></i> Próximos Vencimientos</h3></div>
                        <div style="padding:14px 18px;" id="dash-vencimientos"></div>
                    </div>
                    <div class="section-card">
                        <div class="section-header"><h3><i class="bi bi-piggy-bank"></i> Metas de Ahorro</h3></div>
                        <div style="padding:14px 18px;" id="dash-metas-mini"></div>
                    </div>
                </div>
                <div class="section-card" style="margin-top:18px;">
                    <div class="section-header"><h3><i class="bi bi-graph-up-arrow"></i> Proyección de Deudas (en Q)</h3></div>
                    <div style="padding:0;overflow-x:auto;">
                        <table>
                            <thead><tr>
                                <th>Deuda</th><th>Moneda</th><th>Saldo</th><th>Saldo en Q</th>
                                <th>Pago/Mes</th><th>Meses</th><th>Liquidación</th><th>Intereses (Q)</th><th>Estado</th>
                            </tr></thead>
                            <tbody id="dash-proyecciones"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ============ INGRESOS ============ -->
            <div class="page" id="page-ingresos">
                <div class="form-section" id="form-ingreso">
                    <h4 style="margin:0 0 14px;font-size:14px;font-weight:700;color:#374151;"><i class="bi bi-plus-circle-fill"></i> Agregar Ingreso</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nombre / Fuente *</label>
                            <input type="text" id="ing-nombre" placeholder="Ej: Salario, Freelance...">
                        </div>
                        <div class="form-group">
                            <label>Monto *</label>
                            <input type="number" id="ing-monto" placeholder="0.00" step="0.01" min="0">
                        </div>
                        <div class="form-group">
                            <label>Moneda *</label>
                            <select id="ing-moneda">
                                <option value="GTQ">🇬🇹 Quetzal (Q)</option>
                                <option value="USD">🇺🇸 Dólar (USD)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Frecuencia *</label>
                            <select id="ing-frecuencia">
                                <option value="mensual">Mensual</option>
                                <option value="quincenal">Quincenal</option>
                                <option value="semanal">Semanal</option>
                                <option value="diario">Diario</option>
                                <option value="anual">Anual</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Notas (opcional)</label>
                            <input type="text" id="ing-notas" placeholder="Descripción adicional...">
                        </div>
                    </div>
                    <!-- PERÍODO LIMITADO -->
                    <div style="margin-top:12px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;color:#374151;">
                            <input type="checkbox" id="ing-temporal" onchange="toggleIngresoPeriodo()"
                                style="width:16px;height:16px;accent-color:#3b82f6;cursor:pointer;">
                            Solo por un período de tiempo (ingreso temporal)
                        </label>
                        <div id="ing-periodo-fields" style="display:none;margin-top:10px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:14px;">
                            <div style="font-size:12px;color:#1e40af;margin-bottom:10px;">⏳ Este ingreso solo se recibirá durante el período indicado.</div>
                            <div class="form-grid" style="grid-template-columns:1fr 1fr;">
                                <div class="form-group">
                                    <label>Mes de inicio</label>
                                    <input type="month" id="ing-fecha-inicio">
                                </div>
                                <div class="form-group">
                                    <label>Duración (meses)</label>
                                    <input type="number" id="ing-duracion" placeholder="Ej: 3, 6, 12" min="1" max="360">
                                </div>
                            </div>
                            <div id="ing-periodo-preview" style="margin-top:8px;font-size:12px;color:#1e40af;"></div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-success" onclick="saveIngreso()"><i class="bi bi-save-fill"></i> Guardar</button>
                        <button class="btn btn-outline" onclick="toggleForm('form-ingreso')">Cancelar</button>
                    </div>
                    <input type="hidden" id="ing-edit-id">
                </div>
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="bi bi-cash-coin"></i> Mis Ingresos</h3>
                        <span id="ing-total-badge" class="badge badge-green"></span>
                    </div>
                    <div style="padding:0;overflow-x:auto;">
                        <table>
                            <thead><tr>
                                <th>Nombre / Fuente</th><th>Monto</th><th>Moneda</th>
                                <th>Frecuencia</th><th>Equiv. Mensual</th><th>En Q</th><th>Período</th><th>Notas</th><th>Acciones</th>
                            </tr></thead>
                            <tbody id="tabla-ingresos">
                                <tr class="empty-row"><td colspan="8">No hay ingresos registrados.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ============ GASTOS FIJOS ============ -->
            <div class="page" id="page-gastos-fijos">
                <div class="form-section" id="form-gasto-fijo">
                    <h4 style="margin:0 0 14px;font-size:14px;font-weight:700;color:#374151;"><i class="bi bi-plus-circle-fill"></i> Agregar Gasto Fijo</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nombre del Gasto *</label>
                            <input type="text" id="gf-nombre" placeholder="Ej: Renta, Internet...">
                        </div>
                        <div class="form-group">
                            <label>Monto Mensual *</label>
                            <input type="number" id="gf-monto" placeholder="0.00" step="0.01" min="0">
                        </div>
                        <div class="form-group">
                            <label>Moneda *</label>
                            <select id="gf-moneda">
                                <option value="GTQ">🇬🇹 Quetzal (Q)</option>
                                <option value="USD">🇺🇸 Dólar (USD)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Categoría</label>
                            <select id="gf-categoria">
                                <option value="vivienda">Vivienda</option>
                                <option value="servicios">Servicios</option>
                                <option value="transporte">Transporte</option>
                                <option value="suscripciones">Suscripciones</option>
                                <option value="educacion">Educación</option>
                                <option value="salud">Salud</option>
                                <option value="seguros">Seguros</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Día de Pago (aprox.)</label>
                            <input type="number" id="gf-dia-pago" placeholder="Ej: 1, 15, 30" min="1" max="31">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-success" onclick="saveGastoFijo()"><i class="bi bi-save-fill"></i> Guardar</button>
                        <button class="btn btn-outline" onclick="toggleForm('form-gasto-fijo')">Cancelar</button>
                    </div>
                    <input type="hidden" id="gf-edit-id">
                </div>
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="bi bi-house-door"></i> Gastos Fijos Mensuales</h3>
                        <span id="gf-total-badge" class="badge badge-red"></span>
                    </div>
                    <div style="padding:0;overflow-x:auto;">
                        <table>
                            <thead><tr>
                                <th>Nombre</th><th>Categoría</th><th>Monto</th><th>Moneda</th><th>En Q</th><th>Día Pago</th><th>Acciones</th>
                            </tr></thead>
                            <tbody id="tabla-gastos-fijos">
                                <tr class="empty-row"><td colspan="7">No hay gastos fijos registrados.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ============ GASTOS VARIABLES ============ -->
            <div class="page" id="page-gastos-variables">
                <div class="form-section" id="form-gasto-variable">
                    <h4 style="margin:0 0 14px;font-size:14px;font-weight:700;color:#374151;"><i class="bi bi-plus-circle-fill"></i> Agregar Gasto Variable</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Categoría *</label>
                            <select id="gv-categoria">
                                <option value="">-- Seleccionar --</option>
                                <option value="Alimentación">Alimentación</option>
                                <option value="Transporte">Transporte</option>
                                <option value="Entretenimiento">Entretenimiento</option>
                                <option value="Ropa">Ropa y Calzado</option>
                                <option value="Salud">Salud / Farmacia</option>
                                <option value="Restaurantes">Restaurantes</option>
                                <option value="Hogar">Hogar / Limpieza</option>
                                <option value="Personal">Cuidado Personal</option>
                                <option value="Tecnología">Tecnología</option>
                                <option value="Regalos">Regalos</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nombre personalizado</label>
                            <input type="text" id="gv-nombre" placeholder="Ej: Gym, Mascotas...">
                        </div>
                        <div class="form-group">
                            <label>Monto Estimado Mensual *</label>
                            <input type="number" id="gv-monto" placeholder="0.00" step="0.01" min="0">
                        </div>
                        <div class="form-group">
                            <label>Moneda *</label>
                            <select id="gv-moneda">
                                <option value="GTQ">🇬🇹 Quetzal (Q)</option>
                                <option value="USD">🇺🇸 Dólar (USD)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Límite Mensual (opcional)</label>
                            <input type="number" id="gv-limite" placeholder="Dejar vacío = sin límite" step="0.01" min="0">
                        </div>
                        <div class="form-group">
                            <label>Notas (opcional)</label>
                            <input type="text" id="gv-notas" placeholder="Descripción...">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-success" onclick="saveGastoVariable()"><i class="bi bi-save-fill"></i> Guardar</button>
                        <button class="btn btn-outline" onclick="toggleForm('form-gasto-variable')">Cancelar</button>
                    </div>
                    <input type="hidden" id="gv-edit-id">
                </div>
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="bi bi-cart3"></i> Gastos Variables Estimados</h3>
                        <span id="gv-total-badge" class="badge badge-yellow"></span>
                    </div>
                    <div style="padding:0;overflow-x:auto;">
                        <table>
                            <thead><tr>
                                <th>Categoría</th><th>Nombre</th><th>Monto</th><th>Moneda</th><th>En Q</th><th>% del Total</th><th>Límite</th><th>Notas</th><th>Acciones</th>
                            </tr></thead>
                            <tbody id="tabla-gastos-variables">
                                <tr class="empty-row"><td colspan="8">No hay gastos variables registrados.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ============ DEUDAS ============ -->
            <div class="page" id="page-deudas">
                <div class="form-section" id="form-deuda">
                    <h4 style="margin:0 0 14px;font-size:14px;font-weight:700;color:#374151;"><i class="bi bi-plus-circle-fill"></i> Agregar Deuda / Tarjeta / Préstamo</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nombre *</label>
                            <input type="text" id="d-nombre" placeholder="Ej: Tarjeta Visa...">
                        </div>
                        <div class="form-group">
                            <label>Tipo *</label>
                            <select id="d-tipo" onchange="updateDeudaFormFields()">
                                <option value="tarjeta">Tarjeta de Crédito</option>
                                <option value="prestamo">Préstamo Personal</option>
                                <option value="hipoteca">Hipoteca</option>
                                <option value="automotriz">Crédito Automotriz</option>
                                <option value="nomina">Préstamo de Nómina</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Moneda de la Deuda *</label>
                            <select id="d-moneda">
                                <option value="GTQ">🇬🇹 Quetzal (Q)</option>
                                <option value="USD">🇺🇸 Dólar (USD)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Saldo Actual *</label>
                            <input type="number" id="d-saldo" placeholder="0.00" step="0.01" min="0">
                        </div>
                        <div class="form-group">
                            <label>Tasa de Interés Anual (%) *</label>
                            <input type="number" id="d-tasa" placeholder="Ej: 24.5" step="0.01" min="0" max="200">
                        </div>
                        <div class="form-group">
                            <label>Pago Mínimo Mensual *</label>
                            <input type="number" id="d-pago-minimo" placeholder="0.00" step="0.01" min="0">
                        </div>
                        <div class="form-group">
                            <label>Pago Objetivo Mensual *</label>
                            <input type="number" id="d-pago-objetivo" placeholder="0.00" step="0.01" min="0">
                        </div>
                        <div class="form-group" id="group-limite-credito" style="display:none;">
                            <label>Límite de Crédito (opcional)</label>
                            <input type="number" id="d-limite-credito" placeholder="Ej: 10000" step="0.01" min="0">
                        </div>
                        <div class="form-group" id="group-fecha-corte">
                            <label id="label-fecha-corte">Día de Corte</label>
                            <input type="number" id="d-fecha-corte" placeholder="Ej: 5" min="1" max="31">
                        </div>
                        <div class="form-group" id="group-fecha-pago">
                            <label id="label-fecha-pago">Día de Pago</label>
                            <input type="number" id="d-fecha-pago" placeholder="Ej: 25" min="1" max="31">
                        </div>
                    </div>
                    <div id="deuda-tipo-hint" style="margin-top:10px;font-size:12px;padding:9px 13px;border-radius:8px;display:none;"></div>
                    <div class="form-actions">
                        <button class="btn btn-success" onclick="saveDeuda()"><i class="bi bi-save-fill"></i> Guardar</button>
                        <button class="btn btn-outline" onclick="toggleForm('form-deuda')">Cancelar</button>
                    </div>
                    <input type="hidden" id="d-edit-id">
                </div>
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="bi bi-credit-card"></i> Mis Deudas</h3>
                        <span id="d-total-badge" class="badge badge-red"></span>
                    </div>
                    <div style="padding:0;overflow-x:auto;">
                        <table>
                            <thead><tr>
                                <th>Nombre</th><th>Tipo</th><th>Moneda</th><th>Saldo</th><th>Saldo en Q</th>
                                <th>Tasa</th><th>Pago Mín.</th><th>Pago Obj.</th><th>Corte/Pago</th><th>Proyección</th><th>Acciones</th>
                            </tr></thead>
                            <tbody id="tabla-deudas">
                                <tr class="empty-row"><td colspan="11">No hay deudas registradas.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ============ INTERESES & ESTRATEGIA ============ -->
            <div class="page" id="page-estrategia">

                <!-- CARDS RESUMEN -->
                <div class="cards-grid" id="est-cards"></div>

                <!-- DESGLOSE MENSUAL -->
                <div class="section-card" style="margin-bottom:20px;">
                    <div class="section-header">
                        <h3><i class="bi bi-search"></i> ¿Cuánto pagas de interés cada mes?</h3>
                        <span style="font-size:12px;color:#64748b;">Desglose por deuda · mes actual</span>
                    </div>
                    <div style="padding:0;overflow-x:auto;">
                        <table>
                            <thead><tr>
                                <th>Deuda</th><th>Tasa Anual</th><th>Saldo</th>
                                <th>Pago Mensual</th><th>Va a Interés</th><th>Va a Capital</th>
                                <th>% que es Interés</th><th>Estado</th>
                            </tr></thead>
                            <tbody id="est-tabla-intereses"></tbody>
                        </table>
                    </div>
                </div>

                <!-- SIMULADOR + ESTRATEGIAS -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:20px;">

                    <!-- SIMULADOR DE PAGO EXTRA -->
                    <div class="section-card">
                        <div class="section-header"><h3><i class="bi bi-lightning-charge-fill"></i> Simulador: ¿Qué pasa si pago más?</h3></div>
                        <div style="padding:18px;">
                            <p style="font-size:13px;color:#475569;margin:0 0 14px;">Ingresa cuánto extra podrías pagar al mes (en Q) aplicado a la deuda de mayor tasa:</p>
                            <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                                <div class="form-group" style="flex:1;margin:0;">
                                    <label>Pago extra mensual (Q)</label>
                                    <input type="number" id="sim-extra" placeholder="Ej: 200" min="0" step="50"
                                           oninput="renderSimulador()" style="font-size:15px;font-weight:600;">
                                </div>
                            </div>
                            <div id="sim-resultado"></div>
                        </div>
                    </div>

                    <!-- ORDEN DE ATAQUE RECOMENDADO -->
                    <div class="section-card">
                        <div class="section-header"><h3><i class="bi bi-trophy-fill"></i> Orden de Ataque Recomendado</h3></div>
                        <div style="padding:18px;" id="est-orden-ataque"></div>
                    </div>
                </div>

                <!-- MÉTODO AVALANCHA vs BOLA DE NIEVE -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:20px;">
                    <div class="section-card">
                        <div class="section-header">
                            <h3><i class="bi bi-droplet-fill"></i> Método Avalancha</h3>
                            <span class="badge badge-green">Ahorra más dinero</span>
                        </div>
                        <div style="padding:18px;" id="est-avalancha"></div>
                    </div>
                    <div class="section-card">
                        <div class="section-header">
                            <h3><i class="bi bi-snow2"></i> Método Bola de Nieve</h3>
                            <span class="badge badge-blue">Más motivación</span>
                        </div>
                        <div style="padding:18px;" id="est-snowball"></div>
                    </div>
                </div>

                <!-- RECOMENDACIONES PERSONALIZADAS -->
                <div class="section-card">
                    <div class="section-header"><h3><i class="bi bi-lightbulb-fill"></i> Recomendaciones Personalizadas</h3></div>
                    <div style="padding:18px;" id="est-recomendaciones"></div>
                </div>

            </div><!-- /page-estrategia -->

            <!-- ============ ESTADÍSTICAS ============ -->
            <div class="page" id="page-estadisticas">

                <!-- CARDS CLAVE -->
                <div class="cards-grid" id="stat-cards"></div>

                <!-- FECHA LIBRE DE DEUDAS (banner) -->
                <div id="stat-banner" style="margin-bottom:20px;"></div>

                <!-- GRÁFICAS CIRCULARES -->
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px;margin-bottom:20px;">

                    <!-- Donut 1: Distribución del presupuesto -->
                    <div class="section-card">
                        <div class="section-header">
                            <h3><i class="bi bi-pie-chart-fill"></i> Distribución del Presupuesto</h3>
                        </div>
                        <div style="padding:20px;display:flex;flex-direction:column;align-items:center;">
                            <div style="position:relative;width:220px;height:220px;cursor:pointer;" id="donut-presupuesto-wrap">
                                <svg id="donut-presupuesto" width="220" height="220" viewBox="0 0 220 220"></svg>
                                <div id="donut-presupuesto-center" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none;">
                                    <div style="font-size:12px;color:#64748b;font-weight:600;" id="dp-label">Total</div>
                                    <div style="font-size:18px;font-weight:800;color:#0f172a;" id="dp-value"></div>
                                    <div style="font-size:11px;color:#94a3b8;" id="dp-pct"></div>
                                </div>
                            </div>
                            <div id="donut-presupuesto-legend" style="margin-top:16px;width:100%;display:flex;flex-direction:column;gap:6px;"></div>
                        </div>
                    </div>

                    <!-- Donut 2: Composición de deudas -->
                    <div class="section-card">
                        <div class="section-header">
                            <h3><i class="bi bi-credit-card-2-front"></i> Composición de Deudas</h3>
                        </div>
                        <div style="padding:20px;display:flex;flex-direction:column;align-items:center;">
                            <div style="position:relative;width:220px;height:220px;cursor:pointer;" id="donut-deudas-wrap">
                                <svg id="donut-deudas" width="220" height="220" viewBox="0 0 220 220"></svg>
                                <div id="donut-deudas-center" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none;">
                                    <div style="font-size:12px;color:#64748b;font-weight:600;" id="dd-label">Total</div>
                                    <div style="font-size:18px;font-weight:800;color:#0f172a;" id="dd-value"></div>
                                    <div style="font-size:11px;color:#94a3b8;" id="dd-pct"></div>
                                </div>
                            </div>
                            <div id="donut-deudas-legend" style="margin-top:16px;width:100%;display:flex;flex-direction:column;gap:6px;"></div>
                        </div>
                    </div>

                </div>

                <!-- GRÁFICO: PROYECCIÓN MENSUAL -->
                <div class="section-card" style="margin-bottom:20px;">
                    <div class="section-header">
                        <h3><i class="bi bi-bar-chart-fill"></i> Proyección mes a mes</h3>
                        <div style="display:flex;align-items:center;gap:12px;font-size:11px;flex-wrap:wrap;">
                            <span><span style="display:inline-block;width:10px;height:10px;background:#ef4444;border-radius:2px;margin-right:4px;"></span>Gastos Fijos</span>
                            <span><span style="display:inline-block;width:10px;height:10px;background:#f59e0b;border-radius:2px;margin-right:4px;"></span>Deudas</span>
                            <span><span style="display:inline-block;width:10px;height:10px;background:#7c3aed;border-radius:2px;margin-right:4px;"></span>Gastos Variables</span>
                            <span><span style="display:inline-block;width:10px;height:10px;background:#3b82f6;border-radius:2px;margin-right:4px;"></span>Superávit</span>
                            <span><span style="display:inline-block;width:10px;height:10px;background:#dc2626;border-radius:2px;margin-right:4px;"></span>Déficit</span>
                        </div>
                    </div>
                    <div style="padding:20px 20px 10px;overflow-x:auto;">
                        <div id="stat-chart" style="display:flex;align-items:flex-end;gap:6px;height:220px;min-width:500px;"></div>
                        <div id="stat-chart-labels" style="display:flex;gap:6px;padding-top:6px;min-width:500px;"></div>
                    </div>
                </div>

                <!-- TIMELINE DE LIQUIDACIÓN -->
                <div class="section-card" style="margin-bottom:20px;">
                    <div class="section-header"><h3><i class="bi bi-calendar-week"></i> Línea de tiempo — ¿Cuándo pagas cada deuda?</h3></div>
                    <div style="padding:20px;" id="stat-timeline"></div>
                </div>

                <!-- TABLA DETALLADA MES A MES -->
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="bi bi-list-ul"></i> Detalle mensual completo</h3>
                        <span style="font-size:12px;color:#64748b;">Todos los montos en Quetzales (Q)</span>
                    </div>
                    <div style="padding:0;overflow-x:auto;">
                        <table>
                            <thead><tr>
                                <th>Mes</th><th>Ingresos</th><th>Gastos Fijos</th>
                                <th>Pagos Deudas</th><th>G. Variables</th><th>Superávit</th><th>Deudas Activas</th><th>Evento</th>
                            </tr></thead>
                            <tbody id="stat-tabla"></tbody>
                        </table>
                    </div>
                </div>

            </div><!-- /page-estadisticas -->

            <!-- ============ METAS DE AHORRO ============ -->
            <div class="page" id="page-metas">
                <div class="form-section" id="form-meta">
                    <h4 style="margin:0 0 14px;font-size:14px;font-weight:700;color:#374151;"><i class="bi bi-piggy-bank"></i> Nueva Meta de Ahorro</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nombre de la Meta *</label>
                            <input type="text" id="meta-nombre" placeholder="Ej: Carro, Viaje, Fondo de emergencia...">
                        </div>
                        <div class="form-group">
                            <label>Monto Objetivo (Q) *</label>
                            <input type="number" id="meta-objetivo" placeholder="0.00" step="0.01" min="0">
                        </div>
                        <div class="form-group">
                            <label>Monto Ahorrado hasta hoy (Q)</label>
                            <input type="number" id="meta-ahorrado" placeholder="0.00" step="0.01" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label>Fecha Objetivo</label>
                            <input type="month" id="meta-fecha">
                        </div>
                        <div class="form-group">
                            <label>Descripción (opcional)</label>
                            <input type="text" id="meta-desc" placeholder="Notas sobre esta meta...">
                        </div>
                        <div class="form-group">
                            <label>Color</label>
                            <select id="meta-color">
                                <option value="#2563eb">🔵 Azul</option>
                                <option value="#16a34a">🟢 Verde</option>
                                <option value="#d97706">🟡 Amarillo</option>
                                <option value="#dc2626">🔴 Rojo</option>
                                <option value="#7c3aed">🟣 Morado</option>
                                <option value="#0891b2">🩵 Celeste</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-success" onclick="saveMeta()"><i class="bi bi-save-fill"></i> Guardar Meta</button>
                        <button class="btn btn-outline" onclick="toggleForm('form-meta')">Cancelar</button>
                    </div>
                    <input type="hidden" id="meta-edit-id">
                </div>
                <div class="cards-grid" id="metas-resumen"></div>
                <div id="metas-lista" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;"></div>
            </div>

            <!-- ============ CALCULADORA DE PRÉSTAMOS ============ -->
            <div class="page" id="page-calculadora">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="section-card">
                        <div class="section-header"><h3><i class="bi bi-calculator"></i> Parámetros del Préstamo</h3></div>
                        <div style="padding:20px;">
                            <div class="form-group" style="margin-bottom:14px;">
                                <label>Capital (monto del préstamo)</label>
                                <input type="number" id="calc-capital" placeholder="Ej: 50000" step="100" min="0" oninput="calcularPrestamo()">
                            </div>
                            <div class="form-group" style="margin-bottom:14px;">
                                <label>Tasa de Interés Anual (%)</label>
                                <input type="number" id="calc-tasa" placeholder="Ej: 18" step="0.01" min="0" oninput="calcularPrestamo()">
                            </div>
                            <div class="form-group" style="margin-bottom:14px;">
                                <label>Plazo (meses)</label>
                                <input type="number" id="calc-plazo" placeholder="Ej: 60" step="1" min="1" oninput="calcularPrestamo()">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>¿Cuánto puedes pagar al mes? (opcional — para comparar)</label>
                                <input type="number" id="calc-pago-extra" placeholder="0.00" step="0.01" min="0" oninput="calcularPrestamo()">
                            </div>
                        </div>
                    </div>
                    <div id="calc-resultado" class="section-card">
                        <div class="section-header"><h3><i class="bi bi-bar-chart-fill"></i> Resultado</h3></div>
                        <div style="padding:20px;color:#94a3b8;text-align:center;">Ingresa los datos del préstamo para ver el cálculo.</div>
                    </div>
                </div>
                <div class="section-card" style="margin-top:20px;">
                    <div class="section-header"><h3><i class="bi bi-table"></i> Tabla de Amortización (primeros 24 meses)</h3></div>
                    <div style="padding:0;overflow-x:auto;">
                        <table>
                            <thead><tr><th>#</th><th>Cuota</th><th>Capital</th><th>Interés</th><th>Saldo Restante</th></tr></thead>
                            <tbody id="calc-amortizacion"><tr class="empty-row"><td colspan="5">Ingresa los datos para ver la tabla.</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ============ MIS TARJETAS ============ -->
            <div class="page" id="page-tarjetas">
                <div class="alert alert-info" style="margin-bottom:16px;">
                    <i class="bi bi-lightbulb-fill"></i> Esta vista muestra tus tarjetas de crédito con límite, utilización y próximas fechas. Para agregar tarjetas ve a <strong>Deudas</strong> y selecciona tipo "Tarjeta de Crédito".
                </div>
                <div id="tarjetas-alertas" style="margin-bottom:16px;"></div>
                <div id="tarjetas-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:18px;"></div>
                <div id="tarjetas-sin-datos" style="display:none;">
                    <div class="alert alert-info">No tienes tarjetas de crédito registradas. Ve a Deudas → Agregar Deuda → Tipo: Tarjeta de Crédito.</div>
                </div>
            </div>

            <!-- ============ PAGOS REALES ============ -->
            <div class="page" id="page-pagos">
                <div class="form-section" id="form-pago">
                    <h4 style="margin:0 0 14px;font-size:14px;font-weight:700;color:#374151;"><i class="bi bi-plus-circle-fill"></i> Registrar Pago Realizado</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Deuda *</label>
                            <select id="pago-deuda-id"></select>
                        </div>
                        <div class="form-group">
                            <label>Mes *</label>
                            <input type="month" id="pago-mes">
                        </div>
                        <div class="form-group">
                            <label>Monto Pagado *</label>
                            <input type="number" id="pago-monto" placeholder="0.00" step="0.01" min="0">
                        </div>
                        <div class="form-group">
                            <label>Moneda</label>
                            <select id="pago-moneda">
                                <option value="GTQ">🇬🇹 Quetzal (Q)</option>
                                <option value="USD">🇺🇸 Dólar (USD)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Notas (opcional)</label>
                            <input type="text" id="pago-notas" placeholder="Ej: pago parcial, cargo extra...">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-success" onclick="savePago()"><i class="bi bi-save-fill"></i> Registrar Pago</button>
                        <button class="btn btn-outline" onclick="toggleForm('form-pago')">Cancelar</button>
                    </div>
                </div>
                <div class="cards-grid" id="pagos-cards"></div>
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="bi bi-cash-stack"></i> Historial de Pagos Realizados</h3>
                        <select id="pagos-filtro-mes" onchange="renderPagos()" style="padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;background:white;"></select>
                    </div>
                    <div style="padding:0;overflow-x:auto;">
                        <table>
                            <thead><tr>
                                <th>Mes</th><th>Deuda</th><th>Monto Pagado</th><th>vs Objetivo</th><th>Notas</th><th>Acciones</th>
                            </tr></thead>
                            <tbody id="tabla-pagos"><tr class="empty-row"><td colspan="6">No hay pagos registrados.</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ============ HISTORIAL ============ -->
            <div class="page" id="page-historial">
                <div class="section-card" style="margin-bottom:20px;">
                    <div class="section-header">
                        <h3><i class="bi bi-camera-fill"></i> Estado Financiero Actual</h3>
                        <button class="btn btn-primary" onclick="saveHistorial()"><i class="bi bi-save-fill"></i> Guardar Snapshot del Mes</button>
                    </div>
                    <div style="padding:16px 20px;">
                        <div class="cards-grid" id="historial-preview"></div>
                        <p style="font-size:12px;color:#94a3b8;margin:10px 0 0;">Guarda un snapshot al final de cada mes para ver tu progreso a lo largo del tiempo.</p>
                    </div>
                </div>
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="bi bi-clock-history"></i> Snapshots Guardados</h3>
                        <span id="historial-count" class="badge badge-yellow"></span>
                    </div>
                    <div id="historial-lista" style="padding:20px;"></div>
                </div>
            </div>

        </div><!-- /content -->
    </div><!-- /main -->
</div><!-- /app -->

<!-- MODAL -->
<div id="modal-overlay" onclick="closeModal(event)">
    <div id="modal">
        <h3 id="modal-title">Confirmar</h3>
        <div id="modal-body"></div>
        <div id="modal-actions" style="display:flex;gap:10px;margin-top:18px;justify-content:flex-end;"></div>
    </div>
</div>

<script>
// ============================================================
// ESCAPE DE HTML — evita XSS al insertar texto libre del usuario
// (nombre, notas, etc.) dentro de innerHTML.
// ============================================================
function esc(str) {
    return String(str ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
}

// ============================================================
// STORAGE
// ============================================================
// ============================================================
// CACHÉ EN MEMORIA + PERSISTENCIA EN SERVIDOR
// ============================================================
let _cache = {
    ingresos: [], gastos_fijos: [], gastos_variables: [], deudas: [],
    pagos_realizados: [], historial_mensual: [], metas_ahorro: [],
    exchange_rate: 7.70, _rev: 0
};

const DB = {
    get: (key) => (_cache[key] || []),
    set: (key, data) => { _cache[key] = data; _guardarEnServidor(); },
    genId: () => '_' + Math.random().toString(36).substr(2, 9)
};

let _saveTimer = null;
let _conflictoMostrado = false;
function _guardarEnServidor() {
    clearTimeout(_saveTimer);
    _saveTimer = setTimeout(async () => {
        try {
            const res = await fetch('/api/finanzas-data', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(_cache)
            });

            if (res.status === 409) {
                if (_conflictoMostrado) return;
                _conflictoMostrado = true;
                Swal.fire({
                    icon: 'warning',
                    title: 'Cambios desde otra sesión',
                    text: 'Estos datos se actualizaron en otra pestaña o dispositivo. Vamos a recargar para mostrarte la versión más reciente y evitar perder información.',
                    confirmButtonText: 'Recargar ahora',
                    allowOutsideClick: false
                }).then(() => location.reload());
                return;
            }

            const json = await res.json();
            if (json && typeof json.rev === 'number') _cache._rev = json.rev;
        } catch (e) {
            console.warn('No se pudo guardar en el servidor:', e);
        }
    }, 400);
}

async function _cargarDelServidor() {
    try {
        const res  = await fetch('/api/finanzas-data');
        const data = await res.json();
        _cache = {
            ingresos:             Array.isArray(data.ingresos)            ? data.ingresos            : [],
            gastos_fijos:         Array.isArray(data.gastos_fijos)        ? data.gastos_fijos        : [],
            gastos_variables:     Array.isArray(data.gastos_variables)    ? data.gastos_variables    : [],
            deudas:               Array.isArray(data.deudas)              ? data.deudas              : [],
            pagos_realizados:     Array.isArray(data.pagos_realizados)    ? data.pagos_realizados    : [],
            historial_mensual:    Array.isArray(data.historial_mensual)   ? data.historial_mensual   : [],
            metas_ahorro:         Array.isArray(data.metas_ahorro)        ? data.metas_ahorro        : [],
            exchange_rate:        parseFloat(data.exchange_rate)          || 7.70,
            _rev:                 typeof data._rev === 'number'          ? data._rev                : 0
        };
    } catch(e) {
        console.warn('No se pudo cargar datos:', e);
    }
}

// ============================================================
// SWEETALERT2 HELPERS
// ============================================================
function _toast(title, icon = 'success') {
    Swal.fire({ icon, title, toast: true, position: 'top-end', showConfirmButton: false, timer: 2500, timerProgressBar: true });
}
function _confirm(text) {
    return Swal.fire({ title: '¿Confirmar?', text, icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonColor: '#6b7280', confirmButtonText: 'Sí, continuar', cancelButtonText: 'Cancelar' });
}

// ============================================================
// TIPO DE CAMBIO
// ============================================================
function getExchangeRate() {
    return parseFloat(_cache.exchange_rate || 7.70);
}
function saveExchangeRate(val) {
    const r = parseFloat(val);
    if (!isNaN(r) && r > 0) {
        _cache.exchange_rate = r;
        _guardarEnServidor();
        if (document.getElementById('page-dashboard').classList.contains('active')) renderDashboard();
    }
}
function toGTQ(amount, currency) {
    if (currency === 'USD') return amount * getExchangeRate();
    return amount;
}

// ============================================================
// EXPORTAR / IMPORTAR DATOS
// ============================================================
function exportarDatos() {
    const datos = { version: 1, fecha: new Date().toISOString(), ..._cache };
    const blob  = new Blob([JSON.stringify(datos, null, 2)], { type: 'application/json' });
    const url   = URL.createObjectURL(blob);
    const a     = document.createElement('a');
    a.href      = url;
    a.download  = `finanzas_respaldo_${new Date().toISOString().slice(0,10)}.json`;
    a.click();
    URL.revokeObjectURL(url);
}

function importarDatos(event) {
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = async function(e) {
        try {
            const datos = JSON.parse(e.target.result);
            if (!datos.version) throw new Error('Archivo no válido');
            _cache = {
                ingresos:             Array.isArray(datos.ingresos)            ? datos.ingresos            : [],
                gastos_fijos:         Array.isArray(datos.gastos_fijos)        ? datos.gastos_fijos        : [],
                gastos_variables:     Array.isArray(datos.gastos_variables)    ? datos.gastos_variables    : [],
                deudas:               Array.isArray(datos.deudas)              ? datos.deudas              : [],
                pagos_realizados:     Array.isArray(datos.pagos_realizados)    ? datos.pagos_realizados    : [],
                historial_mensual:    Array.isArray(datos.historial_mensual)   ? datos.historial_mensual   : [],
                metas_ahorro:         Array.isArray(datos.metas_ahorro)        ? datos.metas_ahorro        : [],
                exchange_rate:        parseFloat(datos.exchange_rate)          || 7.70,
                _rev:                 _cache._rev || 0
            };
            _guardarEnServidor();
            document.getElementById('exchange-rate').value = getExchangeRate().toFixed(2);
            renderDashboard(); renderIngresos(); renderGastosFijos(); renderGastosVariables(); renderDeudas();
            Swal.fire({ icon: 'success', title: 'Importación exitosa', html:
                `Ingresos: <b>${_cache.ingresos.length}</b><br>` +
                `Gastos Fijos: <b>${_cache.gastos_fijos.length}</b><br>` +
                `Gastos Variables: <b>${_cache.gastos_variables.length}</b><br>` +
                `Deudas: <b>${_cache.deudas.length}</b>`,
                confirmButtonColor: '#2563eb' });
        } catch(err) {
            Swal.fire({ icon: 'error', title: 'Error al importar', text: err.message, confirmButtonColor: '#dc2626' });
        }
        event.target.value = '';
    };
    reader.readAsText(file);
}

// ============================================================
// FORMAT HELPERS
// ============================================================
const fmt = {
    money(n, currency) {
        if (isNaN(n) || n === null) n = 0;
        if (currency === 'USD') {
            return '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        return 'Q ' + Number(n).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    moneyQ(n) { return this.money(n, 'GTQ'); },
    pct: (n) => (!isFinite(n) || isNaN(n)) ? '0%' : n.toFixed(1) + '%',
    date: (d) => d ? new Intl.DateTimeFormat('es-GT', { month: 'short', year: 'numeric' }).format(d) : '-',
    freq: { mensual: 'Mensual', quincenal: 'Quincenal', semanal: 'Semanal', diario: 'Diario', anual: 'Anual' },
    tipo: {
        tarjeta: '<i class="bi bi-credit-card"></i> Tarjeta', prestamo: '<i class="bi bi-bank2"></i> Préstamo',
        hipoteca: '<i class="bi bi-house-door"></i> Hipoteca', automotriz: '<i class="bi bi-car-front"></i> Automotriz',
        nomina: '<i class="bi bi-briefcase"></i> Nómina', otro: '<i class="bi bi-tag"></i> Otro',
    },
    cat: {
        vivienda: '<i class="bi bi-house-door"></i> Vivienda', servicios: '<i class="bi bi-lightning-charge"></i> Servicios',
        transporte: '<i class="bi bi-bus-front"></i> Transporte', suscripciones: '<i class="bi bi-phone"></i> Suscripciones',
        educacion: '<i class="bi bi-book"></i> Educación', salud: '<i class="bi bi-heart-pulse"></i> Salud',
        seguros: '<i class="bi bi-shield-check"></i> Seguros', otro: '<i class="bi bi-tag"></i> Otro',
    },
    currBadge: (c) => c === 'USD'
        ? '<span class="currency-pill pill-usd">$ USD</span>'
        : '<span class="currency-pill pill-gtq">Q GTQ</span>'
};

// ============================================================
// CALCULATIONS (all results in GTQ)
// ============================================================
const Calc = {
    toMonthly(amount, freq) {
        const map = { mensual: 1, quincenal: 2, semanal: 4.33, diario: 30, anual: 1/12 };
        return amount * (map[freq] || 1);
    },
    ingresoActivoEnMes(ingreso, offsetMeses = 0) {
        if (!ingreso.temporal || !ingreso.fecha_inicio || !ingreso.duracion_meses) return true;
        const hoy = new Date();
        const mesEval = new Date(hoy.getFullYear(), hoy.getMonth() + offsetMeses, 1);
        const inicio  = new Date(ingreso.fecha_inicio + '-01');
        const fin     = new Date(inicio.getFullYear(), inicio.getMonth() + ingreso.duracion_meses, 1);
        return mesEval >= inicio && mesEval < fin;
    },
    totalIngresos(offsetMeses = 0) {
        return DB.get('ingresos').reduce((s, i) => {
            if (!this.ingresoActivoEnMes(i, offsetMeses)) return s;
            return s + toGTQ(this.toMonthly(i.monto, i.frecuencia), i.moneda || 'GTQ');
        }, 0);
    },
    totalGastosFijos() {
        return DB.get('gastos_fijos').reduce((s, g) =>
            s + toGTQ(g.monto, g.moneda || 'GTQ'), 0);
    },
    totalGastosVariables() {
        return DB.get('gastos_variables').reduce((s, g) =>
            s + toGTQ(g.monto, g.moneda || 'GTQ'), 0);
    },
    totalDeudas() {
        return DB.get('deudas').reduce((s, d) =>
            s + toGTQ(d.saldo_actual, d.moneda || 'GTQ'), 0);
    },
    totalPagosDeudas() {
        return DB.get('deudas').reduce((s, d) =>
            s + toGTQ(d.pago_objetivo_mensual, d.moneda || 'GTQ'), 0);
    },
    disponible() {
        return this.totalIngresos() - this.totalGastosFijos() - this.totalPagosDeudas();
    },
    superavit() {
        return this.disponible() - this.totalGastosVariables();
    },
    gastoDiario() {
        const hoy = new Date();
        const dias = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0).getDate();
        // Gasto diario = lo que queda después de variables estimadas ÷ días
        return this.superavit() / dias;
    },
    proyeccionDeuda(d) {
        if (!d.saldo_actual || d.saldo_actual <= 0) return { months: 0, totalInterest: 0, impossible: false };
        const rate = getExchangeRate();
        // Work in original currency for precision
        const monthlyRate = d.tasa_interes_anual / 100 / 12;
        const payment = d.pago_objetivo_mensual;
        const minRequired = d.saldo_actual * monthlyRate;
        if (payment <= minRequired && d.saldo_actual > 0) return { months: Infinity, totalInterest: Infinity, impossible: true };

        let balance = d.saldo_actual;
        let months = 0;
        let totalInterest = 0;
        while (balance > 0.01 && months < 600) {
            const interest = balance * monthlyRate;
            totalInterest += interest;
            balance = balance + interest - payment;
            months++;
        }
        const endDate = new Date();
        endDate.setMonth(endDate.getMonth() + months);
        // Convert interest to GTQ
        const interestGTQ = toGTQ(Math.max(0, totalInterest), d.moneda || 'GTQ');
        return { months, totalInterest, totalInterestGTQ: interestGTQ, endDate, impossible: false };
    }
};

// ============================================================
// NAVIGATION
// ============================================================
const pages = {
    dashboard: { title: 'Dashboard', subtitle: 'Resumen financiero del mes · Todo en Quetzales (Q)' },
    ingresos: { title: 'Ingresos', subtitle: 'Gestiona tus fuentes de ingresos (Q y USD)' },
    'gastos-fijos': { title: 'Gastos Fijos', subtitle: 'Pagos recurrentes mensuales (Q y USD)' },
    'gastos-variables': { title: 'Gastos Variables', subtitle: 'Estimados de gastos no fijos (Q y USD)' },
    deudas: { title: 'Deudas y Préstamos', subtitle: 'Tarjetas y créditos en Q y USD' },
    estrategia: { title: 'Intereses & Estrategia', subtitle: 'Cuánto pagas de interés y cómo salir más rápido de tus deudas' },
    estadisticas: { title: 'Estadísticas', subtitle: 'Proyección mes a mes de ingresos, gastos y deudas hasta quedar libre' },
    pagos: { title: 'Pagos Reales', subtitle: 'Registra los pagos que realmente realizas cada mes a tus deudas' },
    historial: { title: 'Historial Mensual', subtitle: 'Snapshots de tu situación financiera mes a mes' },
    metas: { title: 'Metas de Ahorro', subtitle: 'Define objetivos financieros y sigue tu progreso' },
    calculadora: { title: 'Calculadora de Préstamos', subtitle: 'Simula cuotas, intereses y amortización de cualquier préstamo' },
    tarjetas: { title: 'Mis Tarjetas de Crédito', subtitle: 'Utilización, límites y próximas fechas de corte y pago' }
};
const addBtns = {
    ingresos: `<button class="btn btn-primary" onclick="toggleForm('form-ingreso')">+ Agregar Ingreso</button>`,
    'gastos-fijos': `<button class="btn btn-primary" onclick="toggleForm('form-gasto-fijo')">+ Agregar Gasto</button>`,
    'gastos-variables': `<button class="btn btn-primary" onclick="toggleForm('form-gasto-variable')">+ Agregar Categoría</button>`,
    deudas: `<button class="btn btn-primary" onclick="toggleForm('form-deuda')">+ Agregar Deuda</button>`,
    pagos: `<button class="btn btn-primary" onclick="toggleForm('form-pago')">+ Registrar Pago</button>`,
    metas: `<button class="btn btn-primary" onclick="toggleForm('form-meta')">+ Nueva Meta</button>`,
};

function navigate(page) {
    closeMobileMenu();
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    document.getElementById('page-' + page).classList.add('active');
    document.querySelector(`[data-page="${page}"]`).classList.add('active');
    document.getElementById('page-title').textContent = pages[page].title;
    document.getElementById('page-subtitle').textContent = pages[page].subtitle;
    document.getElementById('topbar-actions').innerHTML = addBtns[page] || '';
    if (page === 'dashboard') renderDashboard();
    if (page === 'estrategia') renderEstrategia();
    if (page === 'estadisticas') renderEstadisticas();
    if (page === 'pagos') renderPagos();
    if (page === 'historial') renderHistorial();
    if (page === 'metas') renderMetas();
    if (page === 'calculadora') calcularPrestamo();
    if (page === 'tarjetas') renderTarjetas();
}

function openMobileMenu() {
    document.getElementById('sidebar').classList.add('mobile-open');
    document.getElementById('mobile-overlay').classList.add('show');
}
function closeMobileMenu() {
    document.getElementById('sidebar').classList.remove('mobile-open');
    document.getElementById('mobile-overlay').classList.remove('show');
}

function toggleForm(id) {
    const f = document.getElementById(id);
    f.classList.toggle('open');
    if (!f.classList.contains('open')) resetForm(id);
}
function resetForm(id) {
    document.getElementById(id).querySelectorAll('input, select').forEach(el => {
        if (el.type === 'hidden') el.value = '';
        else if (el.tagName === 'SELECT') el.selectedIndex = 0;
        else el.value = '';
    });
}

// Gestión de usuarios movida al panel de administración: /admin/usuarios


// ============================================================
// MODAL
// ============================================================
function openModal(title, bodyHtml, actions) {
    document.getElementById('modal-title').textContent = title;
    document.getElementById('modal-body').innerHTML = bodyHtml;
    document.getElementById('modal-actions').innerHTML = actions;
    document.getElementById('modal-overlay').classList.add('open');
}
function closeModal(e) {
    if (!e || e.target === document.getElementById('modal-overlay'))
        document.getElementById('modal-overlay').classList.remove('open');
}

// ============================================================
// INGRESOS CRUD
// ============================================================
function toggleIngresoPeriodo() {
    const checked = document.getElementById('ing-temporal').checked;
    document.getElementById('ing-periodo-fields').style.display = checked ? 'block' : 'none';
    if (checked) {
        const hoy = new Date();
        const mesActual = hoy.toISOString().slice(0, 7);
        if (!document.getElementById('ing-fecha-inicio').value)
            document.getElementById('ing-fecha-inicio').value = mesActual;
        actualizarPreviewPeriodo();
    }
}
function actualizarPreviewPeriodo() {
    const inicio = document.getElementById('ing-fecha-inicio').value;
    const dur = parseInt(document.getElementById('ing-duracion').value);
    const el = document.getElementById('ing-periodo-preview');
    if (!inicio || !dur || dur < 1) { el.textContent = ''; return; }
    const fechaInicio = new Date(inicio + '-01');
    const fechaFin = new Date(fechaInicio);
    fechaFin.setMonth(fechaFin.getMonth() + dur);
    fechaFin.setDate(0); // último día del mes anterior
    const opts = { month: 'long', year: 'numeric' };
    el.innerHTML = `<i class="bi bi-calendar-event"></i> Activo del <strong>${fechaInicio.toLocaleDateString('es-GT', opts)}</strong> al <strong>${fechaFin.toLocaleDateString('es-GT', opts)}</strong> (${dur} meses)`;
}
function saveIngreso() {
    const nombre    = document.getElementById('ing-nombre').value.trim();
    const monto     = parseFloat(document.getElementById('ing-monto').value);
    const moneda    = document.getElementById('ing-moneda').value;
    const frecuencia = document.getElementById('ing-frecuencia').value;
    const notas     = document.getElementById('ing-notas').value.trim();
    const temporal  = document.getElementById('ing-temporal').checked;
    const fechaInicio = temporal ? document.getElementById('ing-fecha-inicio').value : null;
    const duracion  = temporal ? parseInt(document.getElementById('ing-duracion').value) || null : null;
    const editId    = document.getElementById('ing-edit-id').value;
    if (!nombre || isNaN(monto) || monto <= 0) { Swal.fire({ icon:'warning', title:'Campos incompletos', text:'Completa Nombre y Monto.', confirmButtonColor:'#2563eb' }); return; }
    if (temporal && (!fechaInicio || !duracion)) { Swal.fire({ icon:'warning', title:'Campos incompletos', text:'Ingresa el mes de inicio y la duración.', confirmButtonColor:'#2563eb' }); return; }
    const list = DB.get('ingresos');
    const item = { nombre, monto, moneda, frecuencia, notas, temporal, fecha_inicio: fechaInicio, duracion_meses: duracion };
    if (editId) { const i = list.findIndex(x => x.id === editId); if (i >= 0) list[i] = { ...list[i], ...item }; }
    else list.push({ id: DB.genId(), ...item });
    DB.set('ingresos', list);
    toggleForm('form-ingreso');
    renderIngresos();
    _toast('Ingreso guardado');
}
function editIngreso(id) {
    const x = DB.get('ingresos').find(i => i.id === id); if (!x) return;
    document.getElementById('form-ingreso').classList.add('open');
    document.getElementById('ing-nombre').value   = x.nombre;
    document.getElementById('ing-monto').value    = x.monto;
    document.getElementById('ing-moneda').value   = x.moneda || 'GTQ';
    document.getElementById('ing-frecuencia').value = x.frecuencia;
    document.getElementById('ing-notas').value    = x.notas || '';
    document.getElementById('ing-edit-id').value  = id;
    document.getElementById('ing-temporal').checked = !!x.temporal;
    document.getElementById('ing-periodo-fields').style.display = x.temporal ? 'block' : 'none';
    if (x.temporal) {
        document.getElementById('ing-fecha-inicio').value = x.fecha_inicio || '';
        document.getElementById('ing-duracion').value = x.duracion_meses || '';
        actualizarPreviewPeriodo();
    }
    document.getElementById('form-ingreso').scrollIntoView({ behavior: 'smooth' });
}
async function deleteIngreso(id) {
    const { isConfirmed } = await _confirm('¿Eliminar este ingreso?');
    if (!isConfirmed) return;
    DB.set('ingresos', DB.get('ingresos').filter(i => i.id !== id));
    renderIngresos(); _toast('Ingreso eliminado', 'error');
}
function renderIngresos() {
    const list = DB.get('ingresos');
    const total = Calc.totalIngresos();
    document.getElementById('ing-total-badge').textContent = `Total: ${fmt.moneyQ(total)}/mes`;
    const tbody = document.getElementById('tabla-ingresos');
    if (!list.length) { tbody.innerHTML = '<tr class="empty-row"><td colspan="9">No hay ingresos registrados.</td></tr>'; return; }
    tbody.innerHTML = list.map(i => {
        const mensual = Calc.toMonthly(i.monto, i.frecuencia);
        const enQ = toGTQ(mensual, i.moneda || 'GTQ');
        const activo = Calc.ingresoActivoEnMes(i, 0);

        // Badge de período
        let periodoBadge = '<span style="color:#94a3b8;font-size:12px;">Indefinido</span>';
        if (i.temporal && i.fecha_inicio && i.duracion_meses) {
            const inicio = new Date(i.fecha_inicio + '-01');
            const fin = new Date(inicio.getFullYear(), inicio.getMonth() + i.duracion_meses, 0);
            const opts = { month: 'short', year: '2-digit' };
            periodoBadge = `<span class="badge ${activo ? 'badge-blue' : 'badge-gray'}" style="font-size:10px;">
                ⏳ ${inicio.toLocaleDateString('es-GT', opts)} → ${fin.toLocaleDateString('es-GT', opts)}
                <br>${i.duracion_meses} mes(es)${activo ? '' : ' · EXPIRADO'}
            </span>`;
        }

        return `<tr style="${!activo ? 'opacity:0.5;background:#f8fafc;' : ''}">
            <td>
                <strong>${esc(i.nombre)}</strong>
                ${!activo ? '<br><span style="font-size:10px;color:#dc2626;font-weight:600;">EXPIRADO</span>' : ''}
                ${i.temporal && activo ? '<br><span style="font-size:10px;color:#2563eb;">temporal</span>' : ''}
            </td>
            <td>${fmt.money(i.monto, i.moneda || 'GTQ')}</td>
            <td>${fmt.currBadge(i.moneda || 'GTQ')}</td>
            <td><span class="badge badge-blue">${fmt.freq[i.frecuencia]}</span></td>
            <td>${fmt.money(mensual, i.moneda || 'GTQ')}</td>
            <td style="color:${activo?'#16a34a':'#94a3b8'};font-weight:600;">${fmt.moneyQ(enQ)}</td>
            <td style="font-size:11px;">${periodoBadge}</td>
            <td style="color:#94a3b8;font-size:12px;">${esc(i.notas) || '-'}</td>
            <td>
                <button class="btn btn-ghost btn-sm" onclick="editIngreso('${i.id}')"><i class="bi bi-pencil-fill"></i></button>
                <button class="btn btn-ghost btn-sm" onclick="deleteIngreso('${i.id}')"><i class="bi bi-trash-fill"></i></button>
            </td>
        </tr>`;
    }).join('') + `<tr class="total-row"><td colspan="5"><strong>TOTAL MENSUAL EN Q (activos)</strong></td><td colspan="4" style="color:#16a34a;font-weight:700;">${fmt.moneyQ(total)}</td></tr>`;
}

// ============================================================
// GASTOS FIJOS CRUD
// ============================================================
function saveGastoFijo() {
    const nombre = document.getElementById('gf-nombre').value.trim();
    const monto = parseFloat(document.getElementById('gf-monto').value);
    const moneda = document.getElementById('gf-moneda').value;
    const categoria = document.getElementById('gf-categoria').value;
    const diaPago = parseInt(document.getElementById('gf-dia-pago').value) || null;
    const editId = document.getElementById('gf-edit-id').value;
    if (!nombre || isNaN(monto) || monto <= 0) { Swal.fire({ icon:'warning', title:'Campos incompletos', text:'Completa Nombre y Monto.', confirmButtonColor:'#2563eb' }); return; }
    const list = DB.get('gastos_fijos');
    const item = { nombre, monto, moneda, categoria, dia_pago: diaPago };
    if (editId) { const i = list.findIndex(x => x.id === editId); if (i >= 0) list[i] = { ...list[i], ...item }; }
    else list.push({ id: DB.genId(), ...item });
    DB.set('gastos_fijos', list);
    toggleForm('form-gasto-fijo');
    renderGastosFijos();
    _toast('Gasto fijo guardado');
}
function editGastoFijo(id) {
    const x = DB.get('gastos_fijos').find(i => i.id === id); if (!x) return;
    document.getElementById('form-gasto-fijo').classList.add('open');
    document.getElementById('gf-nombre').value = x.nombre;
    document.getElementById('gf-monto').value = x.monto;
    document.getElementById('gf-moneda').value = x.moneda || 'GTQ';
    document.getElementById('gf-categoria').value = x.categoria;
    document.getElementById('gf-dia-pago').value = x.dia_pago || '';
    document.getElementById('gf-edit-id').value = id;
    document.getElementById('form-gasto-fijo').scrollIntoView({ behavior: 'smooth' });
}
async function deleteGastoFijo(id) {
    const { isConfirmed } = await _confirm('¿Eliminar este gasto fijo?');
    if (!isConfirmed) return;
    DB.set('gastos_fijos', DB.get('gastos_fijos').filter(g => g.id !== id));
    renderGastosFijos(); _toast('Gasto eliminado', 'error');
}
function renderGastosFijos() {
    const list = DB.get('gastos_fijos');
    const total = Calc.totalGastosFijos();
    document.getElementById('gf-total-badge').textContent = `Total: ${fmt.moneyQ(total)}/mes`;
    const tbody = document.getElementById('tabla-gastos-fijos');
    if (!list.length) { tbody.innerHTML = '<tr class="empty-row"><td colspan="7">No hay gastos fijos registrados.</td></tr>'; return; }
    tbody.innerHTML = list.map(g => {
        const enQ = toGTQ(g.monto, g.moneda || 'GTQ');
        return `<tr>
            <td><strong>${esc(g.nombre)}</strong></td>
            <td>${fmt.cat[g.categoria] || g.categoria}</td>
            <td style="font-weight:600;color:#dc2626;">${fmt.money(g.monto, g.moneda || 'GTQ')}</td>
            <td>${fmt.currBadge(g.moneda || 'GTQ')}</td>
            <td style="color:#dc2626;font-size:12px;">${g.moneda === 'USD' ? fmt.moneyQ(enQ) : '-'}</td>
            <td>${g.dia_pago ? `Día ${g.dia_pago}` : '-'}</td>
            <td>
                <button class="btn btn-ghost btn-sm" onclick="editGastoFijo('${g.id}')"><i class="bi bi-pencil-fill"></i></button>
                <button class="btn btn-ghost btn-sm" onclick="deleteGastoFijo('${g.id}')"><i class="bi bi-trash-fill"></i></button>
            </td>
        </tr>`;
    }).join('') + `<tr class="total-row"><td colspan="4"><strong>TOTAL MENSUAL EN Q</strong></td><td colspan="3" style="color:#dc2626;font-weight:700;">${fmt.moneyQ(total)}</td></tr>`;
}

// ============================================================
// GASTOS VARIABLES CRUD
// ============================================================
function saveGastoVariable() {
    const categoria = document.getElementById('gv-categoria').value;
    const nombre = document.getElementById('gv-nombre').value.trim();
    const monto = parseFloat(document.getElementById('gv-monto').value);
    const moneda = document.getElementById('gv-moneda').value;
    const notas = document.getElementById('gv-notas').value.trim();
    const limite = parseFloat(document.getElementById('gv-limite').value) || 0;
    const editId = document.getElementById('gv-edit-id').value;
    if (!categoria || isNaN(monto) || monto <= 0) { Swal.fire({ icon: 'warning', title: 'Campos incompletos', text: 'Selecciona categoría y monto.', confirmButtonColor: '#2563eb' }); return; }
    const list = DB.get('gastos_variables');
    const item = { categoria, nombre, monto, moneda, notas, limite };
    if (editId) { const i = list.findIndex(x => x.id === editId); if (i >= 0) list[i] = { ...list[i], ...item }; }
    else list.push({ id: DB.genId(), ...item });
    DB.set('gastos_variables', list);
    toggleForm('form-gasto-variable');
    renderGastosVariables();
    _toast('Gasto variable guardado');
}
function editGastoVariable(id) {
    const x = DB.get('gastos_variables').find(i => i.id === id); if (!x) return;
    document.getElementById('form-gasto-variable').classList.add('open');
    document.getElementById('gv-categoria').value = x.categoria;
    document.getElementById('gv-nombre').value = x.nombre || '';
    document.getElementById('gv-monto').value = x.monto;
    document.getElementById('gv-moneda').value = x.moneda || 'GTQ';
    document.getElementById('gv-limite').value = x.limite || '';
    document.getElementById('gv-notas').value = x.notas || '';
    document.getElementById('gv-edit-id').value = id;
    document.getElementById('form-gasto-variable').scrollIntoView({ behavior: 'smooth' });
}
async function deleteGastoVariable(id) {
    const { isConfirmed } = await _confirm('¿Eliminar esta categoría de gasto?');
    if (!isConfirmed) return;
    DB.set('gastos_variables', DB.get('gastos_variables').filter(g => g.id !== id));
    renderGastosVariables(); _toast('Gasto eliminado', 'error');
}
function renderGastosVariables() {
    const list = DB.get('gastos_variables');
    const total = Calc.totalGastosVariables();
    document.getElementById('gv-total-badge').textContent = `Total: ${fmt.moneyQ(total)}/mes`;
    const tbody = document.getElementById('tabla-gastos-variables');
    if (!list.length) { tbody.innerHTML = '<tr class="empty-row"><td colspan="9">No hay gastos variables registrados.</td></tr>'; return; }
    tbody.innerHTML = list.map(g => {
        const enQ    = toGTQ(g.monto, g.moneda || 'GTQ');
        const pct    = total > 0 ? (enQ / total * 100) : 0;
        const limite = g.limite ? toGTQ(g.limite, g.moneda || 'GTQ') : 0;
        const sobreLimite = limite > 0 && enQ > limite;
        const pctLimite   = limite > 0 ? Math.min(150, enQ / limite * 100) : 0;
        return `<tr style="${sobreLimite ? 'background:#fef2f2;' : ''}">
            <td>${g.categoria}</td>
            <td>${esc(g.nombre) || '-'}</td>
            <td style="color:#d97706;font-weight:600;">${fmt.money(g.monto, g.moneda || 'GTQ')}</td>
            <td>${fmt.currBadge(g.moneda || 'GTQ')}</td>
            <td style="color:#d97706;font-size:12px;">${g.moneda === 'USD' ? fmt.moneyQ(enQ) : '-'}</td>
            <td>
                <div style="display:flex;align-items:center;gap:7px;">
                    <div class="progress-bar" style="width:60px;"><div class="progress-fill" style="width:${pct.toFixed(1)}%;background:#f59e0b;"></div></div>
                    <span style="font-size:11px;color:#64748b;">${pct.toFixed(1)}%</span>
                </div>
            </td>
            <td>
                ${limite > 0 ? `
                <div style="display:flex;align-items:center;gap:6px;">
                    <div class="progress-bar" style="width:60px;">
                        <div class="progress-fill" style="width:${Math.min(100,pctLimite).toFixed(0)}%;background:${sobreLimite?'#dc2626':'#22c55e'};"></div>
                    </div>
                    <span style="font-size:10px;font-weight:700;color:${sobreLimite?'#dc2626':'#16a34a'};">
                        ${sobreLimite ? '<i class="bi bi-exclamation-triangle-fill"></i> +'+fmt.moneyQ(enQ-limite) : '<i class="bi bi-check-circle-fill" style="color:#16a34a;"></i>'}
                    </span>
                </div>
                <div style="font-size:10px;color:#94a3b8;">Límite: ${fmt.moneyQ(limite)}</div>` : '<span style="color:#cbd5e1;font-size:11px;">—</span>'}
            </td>
            <td style="color:#94a3b8;font-size:12px;">${esc(g.notas) || '-'}</td>
            <td>
                <button class="btn btn-ghost btn-sm" onclick="editGastoVariable('${g.id}')"><i class="bi bi-pencil-fill"></i></button>
                <button class="btn btn-ghost btn-sm" onclick="deleteGastoVariable('${g.id}')"><i class="bi bi-trash-fill"></i></button>
            </td>
        </tr>`;
    }).join('') + `<tr class="total-row"><td colspan="4"><strong>TOTAL EN Q</strong></td><td colspan="5" style="color:#d97706;font-weight:700;">${fmt.moneyQ(total)}</td></tr>`;
}

// ============================================================
// DEUDAS CRUD
// ============================================================

// Configuración de campos por tipo de deuda
const DEUDA_CONFIG = {
    tarjeta: {
        corte: true,  pago: true,
        labelCorte: 'Día de Corte',
        labelPago: 'Día límite de Pago',
        hint: null
    },
    prestamo: {
        corte: false, pago: true,
        labelPago: 'Día de Débito / Vencimiento (opcional)',
        hint: { bg: '#eff6ff', border: '#bfdbfe', color: '#1e40af', msg: 'Los préstamos personales generalmente tienen un día fijo de pago o débito automático. No tienen fecha de corte.' }
    },
    hipoteca: {
        corte: false, pago: true,
        labelPago: 'Día de Pago Mensual',
        hint: { bg: '#eff6ff', border: '#bfdbfe', color: '#1e40af', msg: 'Las hipotecas se pagan en una fecha fija cada mes. No tienen fecha de corte.' }
    },
    automotriz: {
        corte: false, pago: true,
        labelPago: 'Día de Pago Mensual',
        hint: { bg: '#eff6ff', border: '#bfdbfe', color: '#1e40af', msg: 'Los créditos automotrices tienen cuota fija mensual. No tienen fecha de corte.' }
    },
    nomina: {
        corte: false, pago: false,
        hint: { bg: '#f0fdf4', border: '#bbf7d0', color: '#166534', msg: 'Préstamo de nómina: se descuenta automáticamente de tu salario. No requiere fecha de corte ni día de pago manual.' }
    },
    otro: {
        corte: false, pago: true,
        labelPago: 'Día de Pago (opcional)',
        hint: null
    }
};

function updateDeudaFormFields() {
    const tipo = document.getElementById('d-tipo').value;
    const cfg = DEUDA_CONFIG[tipo] || DEUDA_CONFIG.otro;

    const grpCorte = document.getElementById('group-fecha-corte');
    const grpPago  = document.getElementById('group-fecha-pago');
    const hint     = document.getElementById('deuda-tipo-hint');

    // Límite de crédito (solo tarjetas)
    const grpLimite = document.getElementById('group-limite-credito');
    if (grpLimite) grpLimite.style.display = (tipo === 'tarjeta') ? '' : 'none';

    // Fecha de corte
    grpCorte.style.display = cfg.corte ? '' : 'none';
    if (!cfg.corte) document.getElementById('d-fecha-corte').value = '';

    // Fecha de pago
    grpPago.style.display = (cfg.pago !== false) ? '' : 'none';
    if (cfg.pago === false) document.getElementById('d-fecha-pago').value = '';
    if (cfg.labelPago) document.getElementById('label-fecha-pago').textContent = cfg.labelPago;

    // Hint informativo
    if (cfg.hint) {
        hint.style.display = 'block';
        hint.style.background = cfg.hint.bg;
        hint.style.border = `1px solid ${cfg.hint.border}`;
        hint.style.color = cfg.hint.color;
        hint.textContent = cfg.hint.msg;
    } else {
        hint.style.display = 'none';
    }
}

function saveDeuda() {
    const nombre = document.getElementById('d-nombre').value.trim();
    const tipo = document.getElementById('d-tipo').value;
    const moneda = document.getElementById('d-moneda').value;
    const saldo_actual = parseFloat(document.getElementById('d-saldo').value);
    const tasa_interes_anual = parseFloat(document.getElementById('d-tasa').value);
    const pago_minimo_mensual = parseFloat(document.getElementById('d-pago-minimo').value) || 0;
    const pago_objetivo_mensual = parseFloat(document.getElementById('d-pago-objetivo').value);
    const fecha_corte    = parseInt(document.getElementById('d-fecha-corte').value) || null;
    const fecha_pago     = parseInt(document.getElementById('d-fecha-pago').value) || null;
    const limite_credito = parseFloat(document.getElementById('d-limite-credito').value) || null;
    const editId = document.getElementById('d-edit-id').value;
    if (!nombre || isNaN(saldo_actual) || isNaN(tasa_interes_anual) || isNaN(pago_objetivo_mensual))
        { Swal.fire({ icon: 'warning', title: 'Campos incompletos', text: 'Completa todos los campos requeridos (*).', confirmButtonColor: '#2563eb' }); return; }
    const list = DB.get('deudas');
    const item = { nombre, tipo, moneda, saldo_actual, tasa_interes_anual, pago_minimo_mensual, pago_objetivo_mensual, fecha_corte, fecha_pago, limite_credito };
    if (editId) { const i = list.findIndex(x => x.id === editId); if (i >= 0) list[i] = { ...list[i], ...item }; }
    else list.push({ id: DB.genId(), ...item });
    DB.set('deudas', list);
    toggleForm('form-deuda');
    renderDeudas();
    _toast('Deuda guardada');
}
function editDeuda(id) {
    const x = DB.get('deudas').find(i => i.id === id); if (!x) return;
    document.getElementById('form-deuda').classList.add('open');
    document.getElementById('d-nombre').value = x.nombre;
    document.getElementById('d-tipo').value = x.tipo;
    updateDeudaFormFields();
    document.getElementById('d-moneda').value = x.moneda || 'GTQ';
    document.getElementById('d-saldo').value = x.saldo_actual;
    document.getElementById('d-tasa').value = x.tasa_interes_anual;
    document.getElementById('d-pago-minimo').value = x.pago_minimo_mensual;
    document.getElementById('d-pago-objetivo').value = x.pago_objetivo_mensual;
    document.getElementById('d-fecha-corte').value  = x.fecha_corte || '';
    document.getElementById('d-fecha-pago').value   = x.fecha_pago || '';
    document.getElementById('d-limite-credito').value = x.limite_credito || '';
    document.getElementById('d-edit-id').value = id;
    document.getElementById('form-deuda').scrollIntoView({ behavior: 'smooth' });
}
async function deleteDeuda(id) {
    const { isConfirmed } = await _confirm('¿Eliminar esta deuda del registro?');
    if (!isConfirmed) return;
    DB.set('deudas', DB.get('deudas').filter(d => d.id !== id));
    renderDeudas(); _toast('Deuda eliminada', 'error');
}
function renderDeudas() {
    const list = DB.get('deudas');
    const totalQ = Calc.totalDeudas();
    document.getElementById('d-total-badge').textContent = `Total: ${fmt.moneyQ(totalQ)}`;
    const tbody = document.getElementById('tabla-deudas');
    if (!list.length) { tbody.innerHTML = '<tr class="empty-row"><td colspan="11">No hay deudas registradas.</td></tr>'; return; }
    tbody.innerHTML = list.map(d => {
        const proj = Calc.proyeccionDeuda(d);
        const saldoQ = toGTQ(d.saldo_actual, d.moneda || 'GTQ');
        const tasaClass = d.tasa_interes_anual > 30 ? 'badge-red' : d.tasa_interes_anual > 20 ? 'badge-yellow' : 'badge-green';
        const proyStr = proj.impossible ? '<span class="badge badge-red"><i class="bi bi-exclamation-triangle-fill"></i> Sin liquidar</span>' : `${proj.months} meses (${fmt.date(proj.endDate)})`;
        return `<tr>
            <td><strong>${esc(d.nombre)}</strong></td>
            <td>${fmt.tipo[d.tipo] || d.tipo}</td>
            <td>${fmt.currBadge(d.moneda || 'GTQ')}</td>
            <td style="font-weight:600;color:#dc2626;">${fmt.money(d.saldo_actual, d.moneda || 'GTQ')}</td>
            <td style="color:#dc2626;font-size:12px;">${d.moneda === 'USD' ? fmt.moneyQ(saldoQ) : '-'}</td>
            <td><span class="badge ${tasaClass}">${d.tasa_interes_anual}%</span></td>
            <td>${fmt.money(d.pago_minimo_mensual, d.moneda || 'GTQ')}</td>
            <td style="font-weight:600;color:#2563eb;">${fmt.money(d.pago_objetivo_mensual, d.moneda || 'GTQ')}</td>
            <td style="font-size:11px;color:#64748b;">
                ${d.fecha_corte ? `Corte: ${d.fecha_corte}` : ''}${d.fecha_corte && d.fecha_pago ? '<br>' : ''}${d.fecha_pago ? `Pago: ${d.fecha_pago}` : ''}${!d.fecha_corte && !d.fecha_pago ? '-' : ''}
            </td>
            <td style="font-size:12px;">${proyStr}</td>
            <td>
                <button class="btn btn-ghost btn-sm" onclick="editDeuda('${d.id}')"><i class="bi bi-pencil-fill"></i></button>
                <button class="btn btn-ghost btn-sm" onclick="deleteDeuda('${d.id}')"><i class="bi bi-trash-fill"></i></button>
            </td>
        </tr>`;
    }).join('') + `<tr class="total-row"><td colspan="3"><strong>TOTAL EN Q</strong></td><td colspan="8" style="color:#dc2626;font-weight:700;">${fmt.moneyQ(totalQ)}</td></tr>`;
}

// ============================================================
// DASHBOARD
// ============================================================
function renderDashboard() {
    const ingresos         = Calc.totalIngresos();
    const gastosFijos      = Calc.totalGastosFijos();
    const pagosDeudas      = Calc.totalPagosDeudas();
    const gastosVariables  = Calc.totalGastosVariables();
    const disponible       = Calc.disponible();
    const superavit        = Calc.superavit();
    const gastoDiario      = Calc.gastoDiario();
    const totalDeudas      = Calc.totalDeudas();

    document.getElementById('dash-cards').innerHTML = `
        <div class="card income">
            <div class="card-label">Ingresos Mensuales</div>
            <div class="card-value">${fmt.moneyQ(ingresos)}</div>
            <div class="card-sub">${DB.get('ingresos').length} fuente(s) · en Q</div>
        </div>
        <div class="card expense">
            <div class="card-label">Gastos Fijos</div>
            <div class="card-value">${fmt.moneyQ(gastosFijos)}</div>
            <div class="card-sub">${ingresos > 0 ? fmt.pct(gastosFijos/ingresos*100) : '0%'} de ingresos</div>
        </div>
        <div class="card debt">
            <div class="card-label">Pago de Deudas</div>
            <div class="card-value">${fmt.moneyQ(pagosDeudas)}</div>
            <div class="card-sub">Saldo total: ${fmt.moneyQ(totalDeudas)}</div>
        </div>
        <div class="card available">
            <div class="card-label">Superávit Real (después de variables)</div>
            <div class="card-value" style="font-size:${superavit < 0 ? '20px' : '24px'};color:${superavit < 0 ? '#dc2626' : '#2563eb'};">${fmt.moneyQ(superavit)}</div>
            <div class="card-sub">${superavit < 0 ? '<i class="bi bi-exclamation-triangle-fill"></i> Déficit: gastos superan ingresos' : `≈ ${fmt.moneyQ(Math.max(0, gastoDiario))}/día extra`}</div>
        </div>
    `;

    const rows = [
        { label: '<i class="bi bi-cash-coin"></i> Ingresos Totales', value: ingresos, pct: null, color: '#16a34a' },
        { label: '<i class="bi bi-house-door"></i> Gastos Fijos', value: gastosFijos, pct: ingresos > 0 ? gastosFijos/ingresos*100 : 0, color: '#dc2626' },
        { label: '<i class="bi bi-credit-card"></i> Pagos de Deudas', value: pagosDeudas, pct: ingresos > 0 ? pagosDeudas/ingresos*100 : 0, color: '#d97706' },
        { label: '<i class="bi bi-cart3"></i> Gastos Variables (est.)', value: gastosVariables, pct: ingresos > 0 ? gastosVariables/ingresos*100 : 0, color: '#7c3aed' },
        { label: superavit >= 0 ? '<i class="bi bi-check-circle-fill"></i> Superávit / Ahorro' : '<i class="bi bi-exclamation-triangle-fill"></i> Déficit', value: superavit, pct: ingresos > 0 ? superavit/ingresos*100 : 0, color: superavit >= 0 ? '#2563eb' : '#dc2626' },
    ];
    document.getElementById('dash-budget').innerHTML = `
        <table style="margin-bottom:14px;">
            <thead><tr><th>Concepto</th><th>Monto (Q)</th><th>% Ingresos</th></tr></thead>
            <tbody>${rows.map(r => `<tr>
                <td>${r.label}</td>
                <td style="font-weight:600;color:${r.color};">${fmt.moneyQ(r.value)}</td>
                <td>${r.pct !== null ? fmt.pct(r.pct) : '-'}</td>
            </tr>`).join('')}</tbody>
        </table>
        <div style="background:${superavit<0?'#fef2f2':'#f0fdf4'};border:1px solid ${superavit<0?'#fecaca':'#bbf7d0'};border-radius:8px;padding:14px;text-align:center;">
            <div style="font-size:12px;color:${superavit<0?'#991b1b':'#166534'};font-weight:600;">
                ${superavit<0?'<i class="bi bi-exclamation-triangle-fill"></i> Gastas más de lo que ingresás':'Superávit Diario Estimado'}
            </div>
            <div style="font-size:26px;font-weight:700;color:${superavit<0?'#dc2626':'#16a34a'};">${fmt.moneyQ(gastoDiario)}</div>
            <div style="font-size:11px;color:${superavit<0?'#991b1b':'#166534'};">
                (Ingresos − Fijos − Deudas − Variables) ÷ días del mes
            </div>
        </div>
        <div style="margin-top:10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;">
            <div style="font-size:11px;color:#475569;font-weight:600;margin-bottom:6px;">DESGLOSE MENSUAL</div>
            <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px;"><span style="color:#64748b;">Presupuesto para variables:</span><span style="font-weight:600;color:#7c3aed;">${fmt.moneyQ(disponible)}</span></div>
            <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px;"><span style="color:#64748b;">Variables estimadas:</span><span style="font-weight:600;color:#7c3aed;">− ${fmt.moneyQ(gastosVariables)}</span></div>
            <div style="display:flex;justify-content:space-between;font-size:13px;border-top:1px solid #e2e8f0;padding-top:4px;margin-top:4px;"><span style="color:#374151;font-weight:700;">Superávit / Ahorro:</span><span style="font-weight:700;color:${superavit<0?'#dc2626':'#2563eb'};">${fmt.moneyQ(superavit)}</span></div>
        </div>
        <div style="margin-top:12px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px 14px;font-size:12px;color:#1e40af;">
            <i class="bi bi-currency-exchange"></i> Tipo de cambio activo: <strong>1 USD = Q ${getExchangeRate().toFixed(2)}</strong>
        </div>
    `;

    // Alerts
    const alerts = [];
    const deudas = DB.get('deudas');
    if (ingresos === 0) alerts.push({ type: 'info', msg: 'No tienes ingresos registrados.' });
    if (disponible < 0) alerts.push({ type: 'danger', msg: `<i class="bi bi-exclamation-triangle-fill"></i> DÉFICIT: tus compromisos superan tus ingresos en ${fmt.moneyQ(Math.abs(disponible))}` });
    if (ingresos > 0 && (gastosFijos + pagosDeudas) / ingresos > 0.7) alerts.push({ type: 'warning', msg: `El ${fmt.pct((gastosFijos+pagosDeudas)/ingresos*100)} va a gastos fijos + deudas. Máx. recomendado: 70%.` });
    deudas.forEach(d => {
        const proj = Calc.proyeccionDeuda(d);
        if (proj.impossible) alerts.push({ type: 'danger', msg: `<i class="bi bi-exclamation-octagon-fill"></i> "${esc(d.nombre)}" (${d.moneda}): el pago no cubre los intereses. ¡Aumenta el pago!` });
        if (d.tasa_interes_anual > 40) alerts.push({ type: 'warning', msg: `<i class="bi bi-thermometer-high"></i> "${esc(d.nombre)}" tiene una tasa muy alta: ${d.tasa_interes_anual}% anual.` });
        if (d.pago_objetivo_mensual < d.pago_minimo_mensual) alerts.push({ type: 'danger', msg: `"${esc(d.nombre)}": pago objetivo menor al mínimo requerido.` });
    });
    if (!alerts.length) alerts.push({ type: 'success', msg: '<i class="bi bi-check-circle-fill"></i> Sin alertas importantes. Tus finanzas están en orden.' });
    const alertClass = { danger: 'alert-danger', warning: 'alert-warning', info: 'alert-info', success: 'alert-success' };
    document.getElementById('dash-alerts').innerHTML = alerts.map(a => `<div class="alert ${alertClass[a.type]}">${a.msg}</div>`).join('');

    // ── PRÓXIMOS VENCIMIENTOS ──
    const hoy = new Date();
    const diaHoy = hoy.getDate();
    const vencimientos = [];
    deudas.forEach(d => {
        if (d.fecha_corte) {
            let diasFaltan = d.fecha_corte - diaHoy;
            if (diasFaltan < 0) diasFaltan += 30;
            vencimientos.push({ nombre: d.nombre, tipo: 'Corte', dia: d.fecha_corte, dias: diasFaltan });
        }
        if (d.fecha_pago) {
            let diasFaltan = d.fecha_pago - diaHoy;
            if (diasFaltan < 0) diasFaltan += 30;
            vencimientos.push({ nombre: d.nombre, tipo: 'Pago', dia: d.fecha_pago, dias: diasFaltan });
        }
    });
    vencimientos.sort((a, b) => a.dias - b.dias);
    const proxEl = document.getElementById('dash-vencimientos');
    if (proxEl) {
        if (!vencimientos.length) {
            proxEl.innerHTML = '<div style="color:#94a3b8;font-size:13px;padding:8px 0;">No hay fechas de corte o pago configuradas en tus deudas.</div>';
        } else {
            proxEl.innerHTML = vencimientos.slice(0, 6).map(v => {
                const urgente = v.dias <= 5;
                const pronto  = v.dias <= 10;
                const color   = urgente ? '#dc2626' : pronto ? '#d97706' : '#16a34a';
                const bg      = urgente ? '#fef2f2' : pronto ? '#fffbeb' : '#f0fdf4';
                const icon    = v.tipo === 'Corte' ? '<i class="bi bi-scissors"></i>' : '<i class="bi bi-credit-card"></i>';
                return `<div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:${bg};border-radius:8px;margin-bottom:6px;">
                    <div style="font-size:13px;"><strong>${icon} ${esc(v.nombre)}</strong> — ${v.tipo}</div>
                    <div style="text-align:right;">
                        <div style="font-size:12px;color:#64748b;">Día ${v.dia}</div>
                        <div style="font-size:12px;font-weight:700;color:${color};">${v.dias === 0 ? '¡HOY!' : `en ${v.dias} día(s)`}</div>
                    </div>
                </div>`;
            }).join('');
        }
    }

    // ── METAS (mini resumen en dashboard) ──
    const metas = DB.get('metas_ahorro');
    const metasEl = document.getElementById('dash-metas-mini');
    if (metasEl && metas.length) {
        metasEl.innerHTML = metas.slice(0, 3).map(m => {
            const pct = Math.min(100, (m.monto_ahorrado || 0) / m.monto_objetivo * 100);
            return `<div style="margin-bottom:8px;">
                <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px;">
                    <span style="font-weight:600;color:#0f172a;">${esc(m.nombre)}</span>
                    <span style="color:#64748b;">${pct.toFixed(0)}%</span>
                </div>
                <div class="progress-bar" style="height:8px;">
                    <div class="progress-fill" style="width:${pct}%;background:${m.color||'#2563eb'};height:8px;border-radius:4px;"></div>
                </div>
            </div>`;
        }).join('') + (metas.length > 3 ? `<div style="font-size:11px;color:#94a3b8;text-align:right;margin-top:4px;">+${metas.length-3} más en Metas</div>` : '');
    } else if (metasEl) {
        metasEl.innerHTML = '<div style="font-size:12px;color:#94a3b8;">No hay metas definidas aún.</div>';
    }

    // ── GRÁFICAS CIRCULARES DEL DASHBOARD ──
    const presupSlices = [
        { label: 'Gastos Fijos',    value: gastosFijos,     color: '#ef4444' },
        { label: 'Pagos Deudas',    value: pagosDeudas,     color: '#f59e0b' },
        { label: 'Gastos Variables',value: gastosVariables,  color: '#7c3aed' },
        { label: superavit >= 0 ? 'Superávit' : 'Déficit', value: Math.max(0, superavit), color: '#3b82f6' },
    ].filter(s => s.value > 0);
    buildDonutChart(
        'donut-dash-presup', 'donut-dash-presup-legend', null,
        'dp-dash-label', 'dp-dash-value', 'dp-dash-pct',
        presupSlices
    );

    const debtColors = ['#dc2626','#d97706','#2563eb','#7c3aed','#16a34a','#0891b2','#db2777','#65a30d'];
    const deudaSlices = DB.get('deudas').map((d, i) => ({
        label: d.nombre,
        value: toGTQ(parseFloat(d.saldo_actual) || 0, d.moneda || 'GTQ'),
        color: debtColors[i % debtColors.length],
    })).filter(s => s.value > 0);
    buildDonutChart(
        'donut-dash-deudas', 'donut-dash-deudas-legend', null,
        'dd-dash-label', 'dd-dash-value', 'dd-dash-pct',
        deudaSlices
    );

    // Proyecciones
    const tbody = document.getElementById('dash-proyecciones');
    if (!deudas.length) { tbody.innerHTML = '<tr class="empty-row"><td colspan="9">No hay deudas registradas.</td></tr>'; return; }
    let totalInteresesQ = 0;
    tbody.innerHTML = deudas.map(d => {
        const proj = Calc.proyeccionDeuda(d);
        const saldoQ = toGTQ(d.saldo_actual, d.moneda || 'GTQ');
        if (!proj.impossible) totalInteresesQ += proj.totalInterestGTQ;
        const status = proj.impossible ? '<span class="badge badge-red">Sin liquidar</span>'
            : proj.months <= 6 ? '<span class="badge badge-green">Próximo</span>'
            : proj.months <= 24 ? '<span class="badge badge-blue">A plazo</span>'
            : '<span class="badge badge-yellow">Largo plazo</span>';
        return `<tr>
            <td><strong>${esc(d.nombre)}</strong></td>
            <td>${fmt.currBadge(d.moneda || 'GTQ')}</td>
            <td>${fmt.money(d.saldo_actual, d.moneda || 'GTQ')}</td>
            <td style="color:#dc2626;">${fmt.moneyQ(saldoQ)}</td>
            <td>${fmt.money(d.pago_objetivo_mensual, d.moneda || 'GTQ')}</td>
            <td>${proj.impossible ? '∞' : proj.months + ' meses'}</td>
            <td>${proj.impossible ? '-' : fmt.date(proj.endDate)}</td>
            <td style="color:#d97706;">${proj.impossible ? '-' : fmt.moneyQ(proj.totalInterestGTQ)}</td>
            <td>${status}</td>
        </tr>`;
    }).join('') + `<tr class="total-row">
        <td colspan="7"><strong>Total Intereses Estimados (Q)</strong></td>
        <td style="color:#d97706;font-weight:700;">${fmt.moneyQ(totalInteresesQ)}</td>
        <td></td>
    </tr>`;
}


// ============================================================
// ESTADÍSTICAS — simulación mes a mes
// ============================================================

function simulateMonths() {
    const deudasOrig = DB.get('deudas');
    if (!deudasOrig.length) return { months: [], debtFreeDate: null, debtPayoffDates: [] };

    const deudas          = deudasOrig.map(d => ({ ...d }));
    const gastosFijos     = Calc.totalGastosFijos();
    const gastosVariables = Calc.totalGastosVariables();
    const months = [];
    const debtPayoffDates = {}; // nombre → date
    let debtFreeDate = null;
    let m = 0;
    const MAX = 600; // límite de seguridad

    while (m < MAX) {
        const date = new Date();
        date.setDate(1);
        date.setMonth(date.getMonth() + m);

        let pagosDeudas = 0;
        const nombresActivos = [];
        const eventos = [];

        deudas.forEach(d => {
            if (d.saldo_actual <= 0.005) return;
            const monthlyRate = d.tasa_interes_anual / 100 / 12;
            const interes = d.saldo_actual * monthlyRate;
            const pagoReal = Math.min(d.pago_objetivo_mensual, d.saldo_actual + interes);
            d.saldo_actual = Math.max(0, d.saldo_actual + interes - d.pago_objetivo_mensual);
            pagosDeudas += toGTQ(pagoReal, d.moneda || 'GTQ');

            if (d.saldo_actual <= 0.005 && !debtPayoffDates[d.nombre]) {
                debtPayoffDates[d.nombre] = new Date(date);
                eventos.push(`<i class="bi bi-check-circle-fill"></i> "${esc(d.nombre)}" ¡PAGADA!`);
            } else {
                nombresActivos.push(d.nombre);
            }
        });

        const ingresos   = Calc.totalIngresos(m);
        const disponible = ingresos - gastosFijos - pagosDeudas;
        const superavit  = disponible - gastosVariables;
        const allPaid = deudas.every(d => d.saldo_actual <= 0.005);

        months.push({
            date: new Date(date),
            label: date.toLocaleDateString('es-GT', { month: 'short', year: '2-digit' }),
            labelFull: date.toLocaleDateString('es-GT', { month: 'long', year: 'numeric' }),
            ingresos, gastosFijos, pagosDeudas, gastosVariables,
            disponible, superavit,
            activas: nombresActivos.length,
            eventos, allPaid
        });

        if (allPaid) {
            debtFreeDate = new Date(date);
            for (let x = 1; x <= 6; x++) {
                const fd = new Date(date);
                fd.setMonth(fd.getMonth() + x);
                const dispLibre = ingresos - gastosFijos;
                months.push({
                    date: fd,
                    label: fd.toLocaleDateString('es-GT', { month: 'short', year: '2-digit' }),
                    labelFull: fd.toLocaleDateString('es-GT', { month: 'long', year: 'numeric' }),
                    ingresos, gastosFijos, pagosDeudas: 0, gastosVariables,
                    disponible: dispLibre, superavit: dispLibre - gastosVariables,
                    activas: 0, eventos: [], allPaid: true
                });
            }
            break;
        }
        m++;
    }

    return { months, debtFreeDate, debtPayoffDates };
}

// ============================================================
// GRÁFICAS CIRCULARES (DONUT)
// ============================================================

function buildDonutChart(svgId, legendId, centerId, labelId, valueId, pctId, slices) {
    const svg    = document.getElementById(svgId);
    const legend = document.getElementById(legendId);
    if (!svg || !legend) return;

    const cx = 110, cy = 110, R = 90, r = 58;
    const total = slices.reduce((s, d) => s + d.value, 0);

    // default center text
    const setCenter = (label, value, pct) => {
        document.getElementById(labelId).textContent = label;
        document.getElementById(valueId).textContent = value;
        document.getElementById(pctId).textContent   = pct;
    };
    setCenter('Total', fmt.moneyQ(total), '100%');

    if (total <= 0) {
        svg.innerHTML = `<circle cx="${cx}" cy="${cy}" r="${R}" fill="#e2e8f0"/><circle cx="${cx}" cy="${cy}" r="${r}" fill="white"/>`;
        legend.innerHTML = '<div style="color:#94a3b8;font-size:12px;font-style:italic;text-align:center;">Sin datos</div>';
        return;
    }

    const paths = [];
    let angle = -Math.PI / 2;

    slices.forEach((s, i) => {
        const sweep = (s.value / total) * 2 * Math.PI;
        const endAngle = angle + sweep;
        const large = sweep > Math.PI ? 1 : 0;

        const x1 = cx + R * Math.cos(angle),   y1 = cy + R * Math.sin(angle);
        const x2 = cx + R * Math.cos(endAngle), y2 = cy + R * Math.sin(endAngle);
        const ix1 = cx + r * Math.cos(endAngle), iy1 = cy + r * Math.sin(endAngle);
        const ix2 = cx + r * Math.cos(angle),    iy2 = cy + r * Math.sin(angle);

        const d = `M${x1.toFixed(2)} ${y1.toFixed(2)} A${R} ${R} 0 ${large} 1 ${x2.toFixed(2)} ${y2.toFixed(2)} L${ix1.toFixed(2)} ${iy1.toFixed(2)} A${r} ${r} 0 ${large} 0 ${ix2.toFixed(2)} ${iy2.toFixed(2)} Z`;
        paths.push({ d, color: s.color, label: s.label, value: s.value, pct: ((s.value/total)*100).toFixed(1) });
        angle = endAngle;
    });

    svg.innerHTML = paths.map((p, i) =>
        `<path class="donut-slice" d="${p.d}" fill="${p.color}" data-i="${i}"/>`
    ).join('') + `<circle cx="${cx}" cy="${cy}" r="${r}" fill="white"/>`;

    // hover on slices
    svg.querySelectorAll('.donut-slice').forEach((el, i) => {
        const p = paths[i];
        el.addEventListener('mouseenter', () => setCenter(p.label, fmt.moneyQ(p.value), p.pct + '%'));
        el.addEventListener('mouseleave', () => setCenter('Total', fmt.moneyQ(total), '100%'));
    });

    // legend
    legend.innerHTML = paths.map((p, i) => `
        <div class="donut-legend-item" data-li="${i}">
            <div class="donut-legend-dot" style="background:${p.color};"></div>
            <span class="donut-legend-name">${p.label}</span>
            <span class="donut-legend-val">${fmt.moneyQ(p.value)}</span>
            <span class="donut-legend-pct">${p.pct}%</span>
        </div>`).join('');

    // hover on legend
    legend.querySelectorAll('.donut-legend-item').forEach((el, i) => {
        const slice = svg.querySelectorAll('.donut-slice')[i];
        const p = paths[i];
        el.addEventListener('mouseenter', () => {
            if (slice) slice.style.opacity = '.7';
            setCenter(p.label, fmt.moneyQ(p.value), p.pct + '%');
        });
        el.addEventListener('mouseleave', () => {
            if (slice) slice.style.opacity = '';
            setCenter('Total', fmt.moneyQ(total), '100%');
        });
    });
}

function renderEstadisticas() {
    const deudas = DB.get('deudas');
    const ingresos = Calc.totalIngresos();
    const gastosFijos = Calc.totalGastosFijos();

    if (!deudas.length) {
        document.getElementById('stat-banner').innerHTML = `<div class="stat-banner-nodebt">No tienes deudas registradas. ¡Excelente! Usa esta sección una vez que registres tus deudas.</div>`;
        ['stat-cards','stat-chart','stat-chart-labels','stat-timeline','stat-tabla'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '';
        });
        // show presupuesto donut even without debts
        const gf = Calc.totalGastosFijos();
        const gv = Calc.totalGastosVariables();
        const sup = Math.max(0, ingresos - gf - gv);
        buildDonutChart('donut-presupuesto','donut-presupuesto-legend','donut-presupuesto-center','dp-label','dp-value','dp-pct',
            [{ label:'Gastos Fijos', value:gf, color:'#ef4444' }, { label:'Gastos Variables', value:gv, color:'#7c3aed' }, { label:'Superávit', value:sup, color:'#3b82f6' }].filter(s=>s.value>0));
        buildDonutChart('donut-deudas','donut-deudas-legend','donut-deudas-center','dd-label','dd-value','dd-pct', []);
        return;
    }

    const { months, debtFreeDate, debtPayoffDates } = simulateMonths();
    if (!months.length) return;

    // ── CARDS ──
    const totalMeses = months.findIndex(m => m.allPaid) + 1 || months.length;
    let totalInteresesVida = 0;
    deudas.forEach(d => { const p = Calc.proyeccionDeuda(d); if (!p.impossible) totalInteresesVida += p.totalInterestGTQ; });
    const liberadoMensual = ingresos - gastosFijos;
    const totalDeudaQ = Calc.totalDeudas();

    document.getElementById('stat-cards').innerHTML = `
        <div class="card debt">
            <div class="card-label">Meses hasta ser libre</div>
            <div class="card-value">${totalMeses}</div>
            <div class="card-sub">${debtFreeDate ? fmt.date(debtFreeDate) : 'Ver proyección'}</div>
        </div>
        <div class="card expense">
            <div class="card-label">Total intereses a pagar</div>
            <div class="card-value">${fmt.moneyQ(totalInteresesVida)}</div>
            <div class="card-sub">Con pagos actuales</div>
        </div>
        <div class="card income">
            <div class="card-label">Dinero libre al terminar</div>
            <div class="card-value">${fmt.moneyQ(liberadoMensual)}</div>
            <div class="card-sub">Disponible cada mes sin deudas</div>
        </div>
        <div class="card available">
            <div class="card-label">Total deuda actual</div>
            <div class="card-value">${fmt.moneyQ(totalDeudaQ)}</div>
            <div class="card-sub">Pagarás ${fmt.moneyQ(totalDeudaQ + totalInteresesVida)} en total</div>
        </div>
    `;

    // ── BANNER FECHA LIBRE ──
    const bannerEl = document.getElementById('stat-banner');
    if (debtFreeDate) {
        const mesesHumano = totalMeses === 1 ? '1 mes' : `${totalMeses} meses`;
        bannerEl.innerHTML = `
            <div class="stat-banner-free">
                <div>
                    <div style="font-size:13px;opacity:.85;margin-bottom:4px;"><i class="bi bi-bullseye"></i> Fecha estimada en que quedarás completamente libre de deudas</div>
                    <div style="font-size:28px;font-weight:800;">${debtFreeDate.toLocaleDateString('es-GT',{month:'long',year:'numeric'}).toUpperCase()}</div>
                    <div style="font-size:13px;opacity:.85;margin-top:4px;">En aproximadamente ${mesesHumano} · si mantienes los pagos actuales</div>
                </div>
                <div style="font-size:64px;opacity:.3;"><i class="bi bi-trophy-fill"></i></div>
            </div>`;
    } else {
        bannerEl.innerHTML = `<div class="stat-banner-nodebt"><i class="bi bi-exclamation-triangle-fill"></i> No se pudo calcular la fecha de liquidación. Verifica que los pagos cubran los intereses en cada deuda.</div>`;
    }

    // ── DONUT 1: Distribución del presupuesto ──
    const gastosVariablesTotal = Calc.totalGastosVariables();
    const pagosDeudas0 = months[0] ? months[0].pagosDeudas : 0;
    const superavit0   = Math.max(0, ingresos - gastosFijos - pagosDeudas0 - gastosVariablesTotal);
    const presupSlices = [
        { label: 'Gastos Fijos',     value: gastosFijos,          color: '#ef4444' },
        { label: 'Pagos Deudas',     value: pagosDeudas0,         color: '#f59e0b' },
        { label: 'Gastos Variables', value: gastosVariablesTotal,  color: '#7c3aed' },
        { label: 'Superávit',        value: superavit0,            color: '#3b82f6' },
    ].filter(s => s.value > 0);
    buildDonutChart('donut-presupuesto','donut-presupuesto-legend','donut-presupuesto-center','dp-label','dp-value','dp-pct', presupSlices);

    // ── DONUT 2: Composición de deudas ──
    const debtColors = ['#dc2626','#d97706','#2563eb','#7c3aed','#16a34a','#0891b2','#db2777','#65a30d'];
    const deudaSlices = deudas.map((d, i) => ({
        label: d.nombre,
        value: toGTQ(d.saldo_actual, d.moneda || 'GTQ'),
        color: debtColors[i % debtColors.length]
    })).filter(s => s.value > 0);
    buildDonutChart('donut-deudas','donut-deudas-legend','donut-deudas-center','dd-label','dd-value','dd-pct', deudaSlices);

    // ── GRÁFICO DE BARRAS ──
    // Máximo de columnas a mostrar: hasta libre de deuda + 3, o máx 36
    const maxCol = Math.min(months.length, 36);
    const monthsChart = months.slice(0, maxCol);
    const maxVal = Math.max(...monthsChart.map(m => m.ingresos), 1);

    const chartEl  = document.getElementById('stat-chart');
    const labelsEl = document.getElementById('stat-chart-labels');

    chartEl.innerHTML = monthsChart.map(m => {
        const pctGF  = Math.max(0, Math.min(100, m.gastosFijos     / maxVal * 100));
        const pctD   = Math.max(0, Math.min(100, m.pagosDeudas     / maxVal * 100));
        const pctGV  = Math.max(0, Math.min(100, m.gastosVariables / maxVal * 100));
        const pctSup = Math.max(0, Math.min(100, m.superavit       / maxVal * 100));
        const deficit  = m.superavit < 0;
        const isFree   = m.allPaid && m.pagosDeudas === 0;
        const hasEvent = m.eventos.length > 0;

        return `<div class="bar-col${isFree?' debt-free':''}"
            title="${m.labelFull}
Ingresos:        ${fmt.moneyQ(m.ingresos)}
Gastos Fijos:    ${fmt.moneyQ(m.gastosFijos)}
Pagos Deudas:    ${fmt.moneyQ(m.pagosDeudas)}
Gastos Variables:${fmt.moneyQ(m.gastosVariables)}
Superávit:       ${fmt.moneyQ(m.superavit)}">
            ${hasEvent ? `<div class="bar-event"><i class="bi bi-star-fill"></i></div>` : '<div style="height:14px;"></div>'}
            <div class="bar-stack" style="height:190px;background:#f1f5f9;">
                <div class="bar-seg" style="height:${pctSup.toFixed(1)}%;background:${deficit?'#dc2626':'#3b82f6'};"></div>
                <div class="bar-seg" style="height:${pctGV.toFixed(1)}%;background:#7c3aed;"></div>
                <div class="bar-seg" style="height:${pctD.toFixed(1)}%;background:${isFree?'transparent':'#f59e0b'};"></div>
                <div class="bar-seg" style="height:${pctGF.toFixed(1)}%;background:#ef4444;"></div>
            </div>
        </div>`;
    }).join('');

    labelsEl.innerHTML = monthsChart.map(m => {
        const isFree = m.allPaid && m.pagosDeudas === 0;
        return `<div class="bar-col"><div class="bar-label" style="${isFree?'color:#16a34a;font-weight:700;':''}">${m.label}</div></div>`;
    }).join('');

    // ── LÍNEA DE TIEMPO ──
    const tlEl = document.getElementById('stat-timeline');
    const sortedPayoffs = Object.entries(debtPayoffDates).sort((a, b) => a[1] - b[1]);
    const colors = ['#dc2626','#d97706','#2563eb','#7c3aed','#16a34a','#0891b2'];

    let tlHtml = '<div class="timeline">';
    sortedPayoffs.forEach(([nombre, date], i) => {
        const deuda = deudas.find(d => d.nombre === nombre);
        const color = colors[i % colors.length];
        const hoy = new Date();
        const mesesRestantes = Math.round((date - hoy) / (1000 * 60 * 60 * 24 * 30.5));
        tlHtml += `
            <div class="tl-item">
                <div class="tl-dot" style="background:${color};color:${color};"></div>
                <div class="tl-date">${date.toLocaleDateString('es-GT',{month:'long',year:'numeric'})}</div>
                <div class="tl-title" style="color:${color};"><i class="bi bi-check-circle-fill"></i> "${esc(nombre)}" — LIQUIDADA</div>
                <div class="tl-sub">En ~${mesesRestantes} meses · ${deuda ? fmt.money(deuda.saldo_actual, deuda.moneda||'GTQ')+' saldo actual' : ''}</div>
            </div>`;
    });

    if (debtFreeDate) {
        tlHtml += `
            <div class="tl-item">
                <div class="tl-dot" style="background:#16a34a;color:#16a34a;width:18px;height:18px;left:-24px;top:2px;"></div>
                <div class="tl-date" style="color:#16a34a;font-size:13px;">${debtFreeDate.toLocaleDateString('es-GT',{weekday:'long',month:'long',year:'numeric'})}</div>
                <div class="tl-title" style="color:#16a34a;font-size:18px;"><i class="bi bi-trophy-fill"></i> ¡LIBRE DE DEUDAS!</div>
                <div class="tl-sub" style="color:#16a34a;">A partir de este mes tendrás <strong>${fmt.moneyQ(liberadoMensual)}/mes</strong> completamente disponibles.</div>
            </div>`;
    }
    tlHtml += '</div>';
    tlEl.innerHTML = tlHtml;

    // ── TABLA DETALLADA ──
    const tbody = document.getElementById('stat-tabla');
    tbody.innerHTML = months.map((m, i) => {
        const deficit = m.superavit < 0;
        const isFreeMonth = m.allPaid && m.pagosDeudas === 0;
        const eventoHtml = m.eventos.length
            ? m.eventos.map(e => `<span class="badge badge-green" style="margin:1px;">${e}</span>`).join('')
            : (isFreeMonth ? '<span class="badge badge-green"><i class="bi bi-trophy-fill"></i> Sin deudas</span>' : '');
        return `<tr style="${isFreeMonth?'background:#f0fdf4;':m.eventos.length?'background:#fffbeb;':deficit?'background:#fef2f2;':''}">
            <td style="font-weight:600;white-space:nowrap;">${m.labelFull}</td>
            <td style="color:#16a34a;font-weight:600;">${fmt.moneyQ(m.ingresos)}</td>
            <td style="color:#dc2626;">${fmt.moneyQ(m.gastosFijos)}</td>
            <td style="color:#d97706;">${m.pagosDeudas > 0 ? fmt.moneyQ(m.pagosDeudas) : '<span style="color:#94a3b8;">—</span>'}</td>
            <td style="color:#7c3aed;">${fmt.moneyQ(m.gastosVariables)}</td>
            <td style="font-weight:700;color:${deficit?'#dc2626':isFreeMonth?'#16a34a':'#2563eb'};">
                ${fmt.moneyQ(m.superavit)}${deficit?' <i class="bi bi-exclamation-triangle-fill"></i>':''}
            </td>
            <td style="text-align:center;">
                ${m.activas > 0 ? `<span class="badge badge-yellow">${m.activas} activa(s)</span>` : '<span class="badge badge-green">0</span>'}
            </td>
            <td>${eventoHtml}</td>
        </tr>`;
    }).join('');
}

// ============================================================
// ESTRATEGIA & INTERESES
// ============================================================

function renderEstrategia() {
    const deudas = DB.get('deudas');

    if (!deudas.length) {
        ['est-cards','est-tabla-intereses','est-avalancha','est-snowball','est-orden-ataque','est-recomendaciones','sim-resultado'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<div class="alert alert-info">No hay deudas registradas aún.</div>';
        });
        return;
    }

    // ── Desglose mensual de intereses ──
    const breakdown = deudas.map(d => {
        const monthlyRate = d.tasa_interes_anual / 100 / 12;
        const interes = d.saldo_actual * monthlyRate;
        const capital = d.pago_objetivo_mensual - interes;
        const interesQ = toGTQ(interes, d.moneda || 'GTQ');
        const capitalQ = toGTQ(Math.max(0, capital), d.moneda || 'GTQ');
        const pctInteres = d.pago_objetivo_mensual > 0 ? Math.min(100, interes / d.pago_objetivo_mensual * 100) : 100;
        const imposible = capital <= 0;
        return { ...d, interes, capital, interesQ, capitalQ, pctInteres, imposible };
    });

    const totalInteresQ = breakdown.reduce((s, d) => s + d.interesQ, 0);
    const totalCapitalQ = breakdown.reduce((s, d) => s + Math.max(0, d.capitalQ), 0);
    const totalPagoQ    = Calc.totalPagosDeudas();
    const totalDeudaQ   = Calc.totalDeudas();

    let totalInteresesVida = 0;
    deudas.forEach(d => {
        const p = Calc.proyeccionDeuda(d);
        if (!p.impossible) totalInteresesVida += p.totalInterestGTQ;
    });

    // Cards resumen
    document.getElementById('est-cards').innerHTML = `
        <div class="card debt">
            <div class="card-label">Interés que pagas este mes</div>
            <div class="card-value">${fmt.moneyQ(totalInteresQ)}</div>
            <div class="card-sub">${totalPagoQ > 0 ? ((totalInteresQ/totalPagoQ)*100).toFixed(1) : 0}% de tu pago mensual va a interés</div>
        </div>
        <div class="card income">
            <div class="card-label">Capital que abonás este mes</div>
            <div class="card-value">${fmt.moneyQ(totalCapitalQ)}</div>
            <div class="card-sub">Reducción real de deuda</div>
        </div>
        <div class="card expense">
            <div class="card-label">Total intereses (toda la vida)</div>
            <div class="card-value">${fmt.moneyQ(totalInteresesVida)}</div>
            <div class="card-sub">Si mantienes los pagos actuales</div>
        </div>
        <div class="card available">
            <div class="card-label">Pagarás en total</div>
            <div class="card-value">${fmt.moneyQ(totalDeudaQ + totalInteresesVida)}</div>
            <div class="card-sub">Capital ${fmt.moneyQ(totalDeudaQ)} + Intereses ${fmt.moneyQ(totalInteresesVida)}</div>
        </div>
    `;

    // Tabla desglose mensual
    const tbody = document.getElementById('est-tabla-intereses');
    tbody.innerHTML = breakdown.map(d => {
        const barPct = Math.min(100, d.pctInteres).toFixed(0);
        const estadoHtml = d.imposible
            ? '<span class="badge badge-red"><i class="bi bi-exclamation-triangle-fill"></i> Pago insuficiente</span>'
            : d.pctInteres > 70
                ? '<span class="badge badge-yellow"><i class="bi bi-exclamation-triangle-fill"></i> Mucho interés</span>'
                : '<span class="badge badge-green"><i class="bi bi-check-circle-fill"></i> OK</span>';
        return `<tr>
            <td><strong>${esc(d.nombre)}</strong><br><span style="font-size:11px;color:#94a3b8;">${fmt.currBadge(d.moneda||'GTQ')}</span></td>
            <td><span class="badge ${d.tasa_interes_anual>30?'badge-red':d.tasa_interes_anual>20?'badge-yellow':'badge-green'}">${d.tasa_interes_anual}%</span></td>
            <td>${fmt.money(d.saldo_actual, d.moneda||'GTQ')}</td>
            <td>${fmt.money(d.pago_objetivo_mensual, d.moneda||'GTQ')}</td>
            <td style="color:#dc2626;font-weight:600;">${fmt.money(d.interes, d.moneda||'GTQ')}
                ${d.moneda==='USD'?`<br><span style="font-size:11px;color:#94a3b8;">${fmt.moneyQ(d.interesQ)}</span>`:''}
            </td>
            <td style="color:${d.imposible?'#dc2626':'#16a34a'};font-weight:600;">
                ${d.imposible ? '<span style="color:#dc2626">−'+fmt.money(Math.abs(d.capital),d.moneda||'GTQ')+'</span>' : fmt.money(d.capital,d.moneda||'GTQ')}
            </td>
            <td>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div class="progress-bar" style="width:90px;">
                        <div class="progress-fill" style="width:${barPct}%;background:${d.pctInteres>70?'#ef4444':d.pctInteres>40?'#f59e0b':'#22c55e'};"></div>
                    </div>
                    <span style="font-size:12px;font-weight:700;color:${d.pctInteres>70?'#dc2626':d.pctInteres>40?'#d97706':'#16a34a'};">${barPct}%</span>
                </div>
            </td>
            <td>${estadoHtml}</td>
        </tr>`;
    }).join('') + `<tr class="total-row">
        <td colspan="4"><strong>TOTAL MENSUAL</strong></td>
        <td style="color:#dc2626;font-weight:700;">${fmt.moneyQ(totalInteresQ)}</td>
        <td style="color:#16a34a;font-weight:700;">${fmt.moneyQ(totalCapitalQ)}</td>
        <td colspan="2"></td>
    </tr>`;

    // Método Avalancha (mayor tasa primero)
    const avalanche = [...deudas].sort((a, b) => b.tasa_interes_anual - a.tasa_interes_anual);
    document.getElementById('est-avalancha').innerHTML = `
        <p style="font-size:13px;color:#475569;margin:0 0 12px;">Paga el mínimo en todas las deudas y <strong>concentra el pago extra en la de mayor tasa</strong>. Minimiza el total de intereses pagados.</p>
        <ol style="margin:0;padding-left:20px;">
            ${avalanche.map((d, i) => {
                const proj = Calc.proyeccionDeuda(d);
                return `<li style="margin-bottom:10px;">
                    <div style="font-weight:700;color:#0f172a;">${esc(d.nombre)}</div>
                    <div style="font-size:12px;color:#64748b;">
                        Tasa: <strong style="color:#dc2626;">${d.tasa_interes_anual}%</strong> ·
                        Saldo: ${fmt.money(d.saldo_actual, d.moneda||'GTQ')} ·
                        ${proj.impossible?'<span style="color:#dc2626;"><i class="bi bi-exclamation-triangle-fill"></i> Sin liquidar</span>':`~${proj.months} meses`}
                    </div>
                </li>`;
            }).join('')}
        </ol>
        <div style="margin-top:12px;padding:10px 12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;font-size:12px;color:#166534;">
            <i class="bi bi-check-circle-fill"></i> <strong>Recomendado si quieres ahorrar el máximo en intereses.</strong>
        </div>`;

    // Método Bola de Nieve (menor saldo primero)
    const snowball = [...deudas].sort((a, b) => toGTQ(a.saldo_actual, a.moneda||'GTQ') - toGTQ(b.saldo_actual, b.moneda||'GTQ'));
    document.getElementById('est-snowball').innerHTML = `
        <p style="font-size:13px;color:#475569;margin:0 0 12px;">Paga el mínimo en todas y <strong>concentra el pago extra en la de menor saldo</strong>. Liquidas deudas completas más rápido, lo que da motivación.</p>
        <ol style="margin:0;padding-left:20px;">
            ${snowball.map((d, i) => {
                const proj = Calc.proyeccionDeuda(d);
                return `<li style="margin-bottom:10px;">
                    <div style="font-weight:700;color:#0f172a;">${esc(d.nombre)}</div>
                    <div style="font-size:12px;color:#64748b;">
                        Saldo: <strong style="color:#2563eb;">${fmt.money(d.saldo_actual, d.moneda||'GTQ')}</strong> ·
                        Tasa: ${d.tasa_interes_anual}% ·
                        ${proj.impossible?'<span style="color:#dc2626;"><i class="bi bi-exclamation-triangle-fill"></i> Sin liquidar</span>':`~${proj.months} meses`}
                    </div>
                </li>`;
            }).join('')}
        </ol>
        <div style="margin-top:12px;padding:10px 12px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;font-size:12px;color:#1e40af;">
            <i class="bi bi-lightbulb-fill"></i> <strong>Recomendado si necesitas victorias rápidas para mantenerte motivado.</strong>
        </div>`;

    // Orden de ataque personalizado
    const urgente = [...breakdown].sort((a, b) => b.tasa_interes_anual - a.tasa_interes_anual);
    document.getElementById('est-orden-ataque').innerHTML = `
        <p style="font-size:12px;color:#64748b;margin:0 0 12px;">Basado en tus deudas actuales, este es el orden más eficiente para atacarlas:</p>
        ${urgente.map((d, i) => {
            const colorMap = ['#dc2626','#d97706','#ca8a04','#2563eb','#7c3aed','#16a34a'];
            const color = colorMap[Math.min(i, colorMap.length-1)];
            const proj = Calc.proyeccionDeuda(d);
            return `<div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f1f5f9;">
                <div style="width:28px;height:28px;border-radius:50%;background:${color};color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;">${i+1}</div>
                <div style="flex:1;">
                    <div style="font-weight:700;font-size:13px;color:#0f172a;">${esc(d.nombre)}</div>
                    <div style="font-size:11px;color:#64748b;">
                        ${d.tasa_interes_anual}% anual · Interés/mes: <strong style="color:#dc2626;">${fmt.money(d.interes, d.moneda||'GTQ')}</strong>
                        ${proj.impossible ? ' · <span style="color:#dc2626;"><i class="bi bi-exclamation-triangle-fill"></i> ¡Pago insuficiente!</span>' : ''}
                    </div>
                </div>
            </div>`;
        }).join('')}
    `;

    // Recomendaciones personalizadas
    renderRecomendaciones(breakdown, totalInteresQ, totalPagoQ, totalDeudaQ, totalInteresesVida);
    renderSimulador();
}

// ── Simula plan actual (sin cascada) ──
function simularPlanActual() {
    const deudas = DB.get('deudas').map(d => ({ ...d }));
    let months = 0, totalInterestQ = 0;
    while (deudas.some(d => d.saldo_actual > 0.01) && months < 600) {
        months++;
        deudas.forEach(d => {
            if (d.saldo_actual <= 0.01) return;
            const interes = d.saldo_actual * (d.tasa_interes_anual / 100 / 12);
            totalInterestQ += toGTQ(interes, d.moneda || 'GTQ');
            d.saldo_actual = Math.max(0, d.saldo_actual + interes - d.pago_objetivo_mensual);
        });
    }
    return { months, totalInterestQ };
}

// ── Simula con cascada (cuando termina una deuda, su pago va a la siguiente) ──
function simularConCascada(sortFn) {
    const deudasOrig = DB.get('deudas');
    if (!deudasOrig.length) return { months: 0, totalInterestQ: 0, orden: [] };
    const sorted = [...deudasOrig].sort(sortFn);
    const deudas = sorted.map(d => ({ ...d }));
    let months = 0, totalInterestQ = 0;

    while (deudas.some(d => d.saldo_actual > 0.01) && months < 600) {
        months++;
        // Suma de pagos de deudas ya liquidadas (efecto cascada)
        const cascadaQ = deudas
            .filter(d => d.saldo_actual <= 0.01)
            .reduce((s, d) => s + toGTQ(d.pago_objetivo_mensual, d.moneda || 'GTQ'), 0);

        // Primera deuda activa en el orden = recibe la cascada
        const prioridad = deudas.find(d => d.saldo_actual > 0.01);

        deudas.forEach(d => {
            if (d.saldo_actual <= 0.01) return;
            const interes = d.saldo_actual * (d.tasa_interes_anual / 100 / 12);
            totalInterestQ += toGTQ(interes, d.moneda || 'GTQ');
            let pagoQ = toGTQ(d.pago_objetivo_mensual, d.moneda || 'GTQ');
            if (d === prioridad) pagoQ += cascadaQ;
            const pagoMoneda = d.moneda === 'USD' ? pagoQ / getExchangeRate() : pagoQ;
            d.saldo_actual = Math.max(0, d.saldo_actual + interes - pagoMoneda);
        });
    }
    return { months, totalInterestQ, orden: sorted };
}

function renderRecomendaciones(breakdown, totalInteresQ, totalPagoQ, totalDeudaQ, totalInteresesVida) {
    const ingresos = Calc.totalIngresos();
    const deudas   = DB.get('deudas');

    // ── COMPARAR 3 ESCENARIOS ──
    const planActual  = simularPlanActual();
    const avalancha   = simularConCascada((a, b) => b.tasa_interes_anual - a.tasa_interes_anual);
    const bolaNieve   = simularConCascada((a, b) =>
        toGTQ(a.saldo_actual, a.moneda||'GTQ') - toGTQ(b.saldo_actual, b.moneda||'GTQ'));

    // Mejor estrategia = la que termina en menos meses (desempate: menos interés)
    let ganador = 'avalancha', ganadorData = avalancha;
    if (bolaNieve.months < avalancha.months ||
       (bolaNieve.months === avalancha.months && bolaNieve.totalInterestQ < avalancha.totalInterestQ)) {
        ganador = 'bolaNieve'; ganadorData = bolaNieve;
    }

    const ahorraMeses    = planActual.months - ganadorData.months;
    const ahorraInteresQ = planActual.totalInterestQ - ganadorData.totalInterestQ;
    const ordenGanador   = ganadorData.orden || [];

    // ── CUADRO ESTRATEGIA ÓPTIMA ──
    const pasos = ordenGanador.map((d, i) => {
        const pagoQ = toGTQ(d.pago_objetivo_mensual, d.moneda || 'GTQ');
        const criterio = ganador === 'avalancha'
            ? `${d.tasa_interes_anual}% anual`
            : `Saldo ${fmt.money(d.saldo_actual, d.moneda||'GTQ')}`;
        return `<div style="display:flex;align-items:flex-start;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.15);">
            <span style="display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;width:24px;height:24px;border-radius:50%;background:rgba(255,255,255,.22);font-size:12px;font-weight:800;">${i+1}</span>
            <div style="flex:1;">
                <div style="font-weight:700;font-size:14px;">${esc(d.nombre)}</div>
                <div style="font-size:12px;opacity:.85;">${criterio} · Pago: ${fmt.money(d.pago_objetivo_mensual, d.moneda||'GTQ')}
                    ${i === 0 && ordenGanador.length > 1 ? ' <strong>+ pagos de deudas anteriores al liquidarlas</strong>' : ''}
                </div>
            </div>
            ${i === 0 ? '<span style="background:rgba(255,255,255,.2);color:white;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;">PRIORIDAD</span>' : ''}
        </div>`;
    }).join('');

    const comparativa = `
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin:14px 0 0;">
            <div style="background:rgba(0,0,0,.2);border-radius:8px;padding:12px;text-align:center;">
                <div style="font-size:10px;opacity:.7;font-weight:700;text-transform:uppercase;margin-bottom:4px;">Plan Actual</div>
                <div style="font-size:20px;font-weight:700;">${planActual.months} meses</div>
                <div style="font-size:11px;opacity:.75;">${fmt.moneyQ(planActual.totalInterestQ)} intereses</div>
            </div>
            <div style="background:rgba(0,0,0,.2);border-radius:8px;padding:12px;text-align:center;${ganador==='avalancha'?'border:2px solid rgba(255,255,255,.5);':''}">
                <div style="font-size:10px;opacity:.7;font-weight:700;text-transform:uppercase;margin-bottom:4px;"><i class="bi bi-droplet-fill"></i> Avalancha</div>
                <div style="font-size:20px;font-weight:700;">${avalancha.months} meses</div>
                <div style="font-size:11px;opacity:.75;">${fmt.moneyQ(avalancha.totalInterestQ)} intereses</div>
            </div>
            <div style="background:rgba(0,0,0,.2);border-radius:8px;padding:12px;text-align:center;${ganador==='bolaNieve'?'border:2px solid rgba(255,255,255,.5);':''}">
                <div style="font-size:10px;opacity:.7;font-weight:700;text-transform:uppercase;margin-bottom:4px;"><i class="bi bi-snow2"></i> Bola de Nieve</div>
                <div style="font-size:20px;font-weight:700;">${bolaNieve.months} meses</div>
                <div style="font-size:11px;opacity:.75;">${fmt.moneyQ(bolaNieve.totalInterestQ)} intereses</div>
            </div>
        </div>`;

    const estrategiaHTML = `
    <div style="background:linear-gradient(135deg,#1e40af,#1d4ed8,#2563eb);border-radius:14px;padding:22px 24px;margin-bottom:20px;color:white;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;opacity:.75;letter-spacing:.08em;">Estrategia Óptima Recomendada</div>
            <span style="background:rgba(255,255,255,.2);font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;">
                ${ganador === 'avalancha' ? '<i class="bi bi-droplet-fill"></i> MÉTODO AVALANCHA' : '<i class="bi bi-snow2"></i> MÉTODO BOLA DE NIEVE'}
            </span>
        </div>
        <div style="font-size:22px;font-weight:800;margin-bottom:2px;">
            Terminas ${ahorraMeses > 0 ? ahorraMeses + ' meses antes' : 'igual de rápido'} y ahorras ${fmt.moneyQ(Math.max(0, ahorraInteresQ))} en intereses
        </div>
        <div style="font-size:13px;opacity:.85;margin-bottom:16px;">
            ${ganador === 'avalancha'
                ? 'Ataca primero la deuda con mayor tasa de interés. Cuando la termines, todo ese pago va a la siguiente. Así pagas el menor total de intereses posible.'
                : 'Ataca primero la deuda con menor saldo. La liquidas rápido, liberas ese pago y lo cascadeas a la siguiente. Más motivación y rapidez.'}
        </div>
        <div style="font-size:13px;font-weight:700;opacity:.9;margin-bottom:8px;"><i class="bi bi-list-ol"></i> Orden de ataque (con efecto cascada):</div>
        ${pasos}
        ${comparativa}
        ${ahorraMeses > 0 ? `
        <div style="margin-top:14px;background:rgba(255,255,255,.15);border-radius:8px;padding:12px;font-size:13px;text-align:center;">
            <i class="bi bi-lightbulb-fill"></i> <strong>Clave:</strong> Cuando liquides una deuda, <strong>NO uses ese dinero para gastar</strong> — envíalo inmediatamente a la siguiente deuda de la lista.
        </div>` : ''}
    </div>`;

    // ── ALERTAS ADICIONALES ──
    const recs = [];
    const insuficientes = breakdown.filter(d => d.imposible);
    insuficientes.forEach(d => {
        const minPago = d.saldo_actual * (d.tasa_interes_anual / 100 / 12);
        recs.push({ tipo: 'danger',
            titulo: `<i class="bi bi-exclamation-octagon-fill"></i> "${esc(d.nombre)}" — Tu pago no cubre los intereses`,
            msg: `Pagas ${fmt.money(d.pago_objetivo_mensual, d.moneda||'GTQ')}/mes pero los intereses son ${fmt.money(minPago, d.moneda||'GTQ')}/mes. La deuda <strong>crece</strong> en lugar de bajar. Necesitas pagar al menos ${fmt.money(minPago * 1.05, d.moneda||'GTQ')}/mes.`
        });
    });
    breakdown.filter(d => !d.imposible && d.pctInteres > 60).forEach(d => {
        recs.push({ tipo: 'warning',
            titulo: `<i class="bi bi-exclamation-triangle-fill"></i> "${esc(d.nombre)}" — El ${d.pctInteres.toFixed(0)}% de tu pago va a puro interés`,
            msg: `Pagas ${fmt.money(d.pago_objetivo_mensual, d.moneda||'GTQ')}/mes pero solo ${fmt.money(d.capital, d.moneda||'GTQ')} reduce el saldo real. Aumentar el pago mensual marca una gran diferencia.`
        });
    });
    breakdown.filter(d => d.tasa_interes_anual > 36).forEach(d => {
        recs.push({ tipo: 'warning',
            titulo: `<i class="bi bi-thermometer-high"></i> "${esc(d.nombre)}" — Tasa muy alta: ${d.tasa_interes_anual}% anual`,
            msg: `Considera: (1) refinanciar con un préstamo a menor tasa, (2) negociar con el banco, o (3) atacarla con prioridad máxima.`
        });
    });
    if (ingresos > 0 && totalPagoQ / ingresos > 0.35) {
        recs.push({ tipo: 'warning',
            titulo: `<i class="bi bi-bar-chart-fill"></i> El ${(totalPagoQ/ingresos*100).toFixed(1)}% de tus ingresos va a deudas`,
            msg: `Lo recomendado es no superar el 30-35%. Busca reducir gastos o aumentar ingresos para liberar dinero extra.`
        });
    }
    if (totalDeudaQ > 0 && totalInteresesVida > 0) {
        const pct = totalInteresesVida / (totalDeudaQ + totalInteresesVida) * 100;
        recs.push({ tipo: pct > 40 ? 'warning' : 'info',
            titulo: `<i class="bi bi-cash-stack"></i> Pagarás ${fmt.moneyQ(totalInteresesVida)} en intereses (${pct.toFixed(0)}% del total)`,
            msg: `Por cada Q100 de deuda pagarás Q${(100*(1+totalInteresesVida/totalDeudaQ)).toFixed(0)}. Cada quetzal extra que abonás hoy te ahorra varios en interés futuro.`
        });
    }
    // Variables vs disponible
    const gastosVarQ  = Calc.totalGastosVariables();
    const dispQ       = Calc.disponible();
    const superavitQ  = Calc.superavit();
    if (gastosVarQ > 0) {
        if (superavitQ < 0) {
            recs.push({ tipo: 'danger',
                titulo: `<i class="bi bi-exclamation-octagon-fill"></i> Tus gastos variables superan lo disponible en ${fmt.moneyQ(Math.abs(superavitQ))}`,
                msg: `Después de gastos fijos y deudas te quedan ${fmt.moneyQ(dispQ)}, pero tus variables estimadas son ${fmt.moneyQ(gastosVarQ)}. Cada mes entras en déficit. Reduce gastos variables o busca aumentar ingresos.`
            });
        } else if (gastosVarQ / dispQ > 0.85) {
            recs.push({ tipo: 'warning',
                titulo: `<i class="bi bi-exclamation-triangle-fill"></i> El ${(gastosVarQ/dispQ*100).toFixed(0)}% de tu presupuesto libre va a gastos variables`,
                msg: `Solo te quedan ${fmt.moneyQ(superavitQ)}/mes para ahorrar o emergencias. Intenta reducir variables para tener más margen.`
            });
        } else {
            recs.push({ tipo: 'success',
                titulo: `<i class="bi bi-check-circle-fill"></i> Gastos variables bajo control`,
                msg: `Tus variables (${fmt.moneyQ(gastosVarQ)}) representan el ${(gastosVarQ/dispQ*100).toFixed(0)}% de tu presupuesto libre. Te quedan ${fmt.moneyQ(superavitQ)}/mes de superávit.`
            });
        }
    }

    if (!recs.length) {
        recs.push({ tipo: 'success', titulo: '<i class="bi bi-check-circle-fill"></i> Sin alertas críticas', msg: 'Aplica la estrategia óptima de arriba y mantén los pagos constantes.' });
    }

    const colorClass = { danger:'alert-danger', warning:'alert-warning', info:'alert-info', success:'alert-success' };
    document.getElementById('est-recomendaciones').innerHTML = estrategiaHTML + recs.map(r => `
        <div class="alert ${colorClass[r.tipo]}" style="flex-direction:column;align-items:flex-start;margin-bottom:10px;">
            <strong style="font-size:14px;">${r.titulo}</strong>
            <p style="margin:5px 0 0;line-height:1.5;">${r.msg}</p>
        </div>`).join('');
}

function renderSimulador() {
    const extra = parseFloat(document.getElementById('sim-extra')?.value) || 0;
    const deudas = DB.get('deudas');
    const el = document.getElementById('sim-resultado');
    if (!el || !deudas.length) return;

    if (extra <= 0) {
        el.innerHTML = `<div style="text-align:center;color:#94a3b8;font-size:13px;padding:20px;">Ingresa un monto para ver el impacto.</div>`;
        return;
    }

    // Aplicar extra a la deuda de mayor tasa (avalancha)
    const objetivo = [...deudas].sort((a, b) => b.tasa_interes_anual - a.tasa_interes_anual)[0];
    const extraEnMoneda = objetivo.moneda === 'USD' ? extra / getExchangeRate() : extra;

    // Proyección SIN extra
    const sinExtra = Calc.proyeccionDeuda(objetivo);
    // Proyección CON extra
    const conExtraDeuda = { ...objetivo, pago_objetivo_mensual: objetivo.pago_objetivo_mensual + extraEnMoneda };
    const conExtra = Calc.proyeccionDeuda(conExtraDeuda);

    if (sinExtra.impossible) {
        el.innerHTML = `<div class="alert alert-danger">Primero soluciona el pago insuficiente en "${esc(objetivo.nombre)}" antes de simular extra.</div>`;
        return;
    }

    const mesesAhorrados = sinExtra.months - conExtra.months;
    const interesAhorradoQ = sinExtra.totalInterestGTQ - (conExtra.impossible ? sinExtra.totalInterestGTQ : conExtra.totalInterestGTQ);

    el.innerHTML = `
        <div style="font-size:12px;color:#64748b;margin-bottom:12px;">Aplicando <strong>${fmt.moneyQ(extra)}</strong> extra/mes a: <strong>"${esc(objetivo.nombre)}"</strong> (${objetivo.tasa_interes_anual}% — mayor tasa)</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">
            <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px;text-align:center;">
                <div style="font-size:11px;color:#991b1b;font-weight:600;">SIN PAGO EXTRA</div>
                <div style="font-size:20px;font-weight:700;color:#dc2626;">${sinExtra.months} meses</div>
                <div style="font-size:11px;color:#991b1b;">Intereses: ${fmt.moneyQ(sinExtra.totalInterestGTQ)}</div>
            </div>
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px;text-align:center;">
                <div style="font-size:11px;color:#166534;font-weight:600;">CON PAGO EXTRA</div>
                <div style="font-size:20px;font-weight:700;color:#16a34a;">${conExtra.impossible ? '∞' : conExtra.months + ' meses'}</div>
                <div style="font-size:11px;color:#166534;">Intereses: ${conExtra.impossible ? '-' : fmt.moneyQ(conExtra.totalInterestGTQ)}</div>
            </div>
        </div>
        ${mesesAhorrados > 0 ? `
        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px;text-align:center;">
            <div style="font-size:13px;font-weight:700;color:#1e40af;">
                <i class="bi bi-stars"></i> Terminas <strong>${mesesAhorrados} meses antes</strong> y ahorras <strong>${fmt.moneyQ(interesAhorradoQ)}</strong> en intereses
            </div>
        </div>` : ''}
    `;
}

// ============================================================
// MODO OSCURO
// ============================================================
function toggleDarkMode() {
    const isDark = document.body.classList.toggle('dark-mode');
    localStorage.setItem('finanzas_dark_mode', isDark ? '1' : '0');
    document.getElementById('dark-mode-label').textContent = isDark ? 'Modo Claro' : 'Modo Oscuro';
    document.getElementById('dark-mode-icon').innerHTML = isDark ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon-stars"></i>';
}

// ============================================================
// METAS DE AHORRO
// ============================================================
function renderMetas() {
    const metas    = DB.get('metas_ahorro');
    const superavit = Calc.superavit();

    // Resumen cards
    const totalObjetivo = metas.reduce((s, m) => s + m.monto_objetivo, 0);
    const totalAhorrado = metas.reduce((s, m) => s + (m.monto_ahorrado || 0), 0);
    const metasCompletas = metas.filter(m => (m.monto_ahorrado || 0) >= m.monto_objetivo).length;
    document.getElementById('metas-resumen').innerHTML = metas.length ? `
        <div class="card income">
            <div class="card-label">Total ahorrado</div>
            <div class="card-value">${fmt.moneyQ(totalAhorrado)}</div>
            <div class="card-sub">de ${fmt.moneyQ(totalObjetivo)} en total</div>
        </div>
        <div class="card available">
            <div class="card-label">Metas completadas</div>
            <div class="card-value">${metasCompletas} / ${metas.length}</div>
            <div class="card-sub">${metas.length - metasCompletas} en progreso</div>
        </div>
        <div class="card ${superavit > 0 ? 'income' : 'expense'}">
            <div class="card-label">Puedes ahorrar hoy</div>
            <div class="card-value">${fmt.moneyQ(Math.max(0, superavit))}</div>
            <div class="card-sub">Tu superávit mensual actual</div>
        </div>` : '';

    const lista = document.getElementById('metas-lista');
    if (!metas.length) {
        lista.innerHTML = '<div class="alert alert-info" style="grid-column:1/-1;">No tienes metas definidas. Presiona "+ Nueva Meta" para crear tu primera meta de ahorro.</div>';
        return;
    }

    lista.innerHTML = metas.map(m => {
        const ahorrado   = m.monto_ahorrado || 0;
        const pct        = Math.min(100, ahorrado / m.monto_objetivo * 100);
        const falta      = Math.max(0, m.monto_objetivo - ahorrado);
        const completada = ahorrado >= m.monto_objetivo;
        const color      = m.color || '#2563eb';

        let fechaInfo = '';
        if (m.fecha_objetivo) {
            const [anio, mes] = m.fecha_objetivo.split('-').map(Number);
            const fechaObj = new Date(anio, mes - 1, 1);
            const hoy      = new Date();
            const mesesFaltan = Math.max(0, (fechaObj.getFullYear() - hoy.getFullYear()) * 12 + fechaObj.getMonth() - hoy.getMonth());
            const porMes   = mesesFaltan > 0 ? falta / mesesFaltan : falta;
            fechaInfo = `<div style="font-size:11px;color:#64748b;margin-top:4px;">
                <i class="bi bi-calendar-event"></i> ${m.fecha_objetivo} · ${mesesFaltan} meses restantes
                ${falta > 0 && mesesFaltan > 0 ? `· Necesitas <strong style="color:${color};">${fmt.moneyQ(porMes)}/mes</strong>` : ''}
                ${superavit > 0 && porMes > 0 ? (porMes <= superavit ? ' <i class="bi bi-check-circle-fill" style="color:#16a34a;"></i> <span style="color:#16a34a;">Alcanzable</span>' : ' <i class="bi bi-exclamation-triangle-fill" style="color:#d97706;"></i> <span style="color:#d97706;">Ajusta el plazo</span>') : ''}
            </div>`;
        }

        return `<div style="background:white;border-radius:14px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.08);border-top:4px solid ${color};position:relative;">
            ${completada ? '<div style="position:absolute;top:12px;right:14px;font-size:20px;"><i class="bi bi-trophy-fill"></i></div>' : ''}
            <div style="font-size:16px;font-weight:800;color:#0f172a;margin-bottom:2px;">${esc(m.nombre)}</div>
            ${m.desc ? `<div style="font-size:12px;color:#64748b;margin-bottom:8px;">${m.desc}</div>` : ''}
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                <span style="font-size:13px;font-weight:700;color:${color};">${fmt.moneyQ(ahorrado)}</span>
                <span style="font-size:12px;color:#94a3b8;">de ${fmt.moneyQ(m.monto_objetivo)}</span>
            </div>
            <div style="background:#f1f5f9;border-radius:6px;height:12px;overflow:hidden;margin-bottom:6px;">
                <div style="height:100%;width:${pct.toFixed(1)}%;background:${color};border-radius:6px;transition:width .4s;"></div>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:11px;color:#94a3b8;margin-bottom:8px;">
                <span>${pct.toFixed(0)}% completado</span>
                <span>${completada ? '<i class="bi bi-check-circle-fill"></i> ¡Meta alcanzada!' : `Faltan ${fmt.moneyQ(falta)}`}</span>
            </div>
            ${fechaInfo}
            <div style="display:flex;gap:8px;margin-top:12px;align-items:center;">
                <div style="flex:1;">
                    <input type="number" placeholder="Actualizar monto ahorrado (Q)" step="0.01" min="0"
                        style="width:100%;padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;"
                        id="meta-act-${m.id}" value="${ahorrado}">
                </div>
                <button class="btn btn-success" style="padding:7px 12px;font-size:12px;" onclick="actualizarMeta('${m.id}')"><i class="bi bi-check-lg"></i></button>
                <button class="btn btn-ghost btn-sm" onclick="editMeta('${m.id}')"><i class="bi bi-pencil-fill"></i></button>
                <button class="btn btn-ghost btn-sm" onclick="deleteMeta('${m.id}')"><i class="bi bi-trash-fill"></i></button>
            </div>
        </div>`;
    }).join('');
}

function saveMeta() {
    const nombre   = document.getElementById('meta-nombre').value.trim();
    const objetivo = parseFloat(document.getElementById('meta-objetivo').value);
    const ahorrado = parseFloat(document.getElementById('meta-ahorrado').value) || 0;
    const fecha    = document.getElementById('meta-fecha').value;
    const desc     = document.getElementById('meta-desc').value.trim();
    const color    = document.getElementById('meta-color').value;
    const editId   = document.getElementById('meta-edit-id').value;

    if (!nombre || isNaN(objetivo) || objetivo <= 0) { Swal.fire({ icon: 'warning', title: 'Campos incompletos', text: 'Ingresa nombre y monto objetivo.', confirmButtonColor: '#2563eb' }); return; }
    const list = DB.get('metas_ahorro');
    const item = { nombre, monto_objetivo: objetivo, monto_ahorrado: ahorrado, fecha_objetivo: fecha, desc, color };
    if (editId) { const i = list.findIndex(x => x.id === editId); if (i >= 0) list[i] = { ...list[i], ...item }; }
    else list.push({ id: DB.genId(), ...item });
    DB.set('metas_ahorro', list);
    toggleForm('form-meta');
    renderMetas();
    _toast('Meta guardada');
}

function actualizarMeta(id) {
    const val  = parseFloat(document.getElementById(`meta-act-${id}`)?.value) || 0;
    const list = DB.get('metas_ahorro');
    const idx  = list.findIndex(m => m.id === id);
    if (idx >= 0) { list[idx].monto_ahorrado = val; DB.set('metas_ahorro', list); renderMetas(); }
}

function editMeta(id) {
    const m = DB.get('metas_ahorro').find(x => x.id === id); if (!m) return;
    document.getElementById('form-meta').classList.add('open');
    document.getElementById('meta-nombre').value   = m.nombre;
    document.getElementById('meta-objetivo').value = m.monto_objetivo;
    document.getElementById('meta-ahorrado').value = m.monto_ahorrado || 0;
    document.getElementById('meta-fecha').value    = m.fecha_objetivo || '';
    document.getElementById('meta-desc').value     = m.desc || '';
    document.getElementById('meta-color').value    = m.color || '#2563eb';
    document.getElementById('meta-edit-id').value  = id;
    document.getElementById('form-meta').scrollIntoView({ behavior: 'smooth' });
}

async function deleteMeta(id) {
    const { isConfirmed } = await _confirm('¿Eliminar esta meta de ahorro?');
    if (!isConfirmed) return;
    DB.set('metas_ahorro', DB.get('metas_ahorro').filter(m => m.id !== id));
    renderMetas(); _toast('Meta eliminada', 'error');
}

// ============================================================
// CALCULADORA DE PRÉSTAMOS
// ============================================================
function calcularPrestamo() {
    const capital = parseFloat(document.getElementById('calc-capital')?.value) || 0;
    const tasa    = parseFloat(document.getElementById('calc-tasa')?.value)    || 0;
    const plazo   = parseInt(document.getElementById('calc-plazo')?.value)     || 0;
    const pagoExtra = parseFloat(document.getElementById('calc-pago-extra')?.value) || 0;

    const resEl  = document.getElementById('calc-resultado');
    const tablaEl = document.getElementById('calc-amortizacion');
    if (!resEl || !tablaEl) return;

    if (!capital || !tasa || !plazo) {
        resEl.innerHTML  = '<div class="section-header"><h3><i class="bi bi-bar-chart-fill"></i> Resultado</h3></div><div style="padding:20px;color:#94a3b8;text-align:center;">Ingresa los datos del préstamo para ver el cálculo.</div>';
        tablaEl.innerHTML = '<tr class="empty-row"><td colspan="5">Ingresa los datos para ver la tabla.</td></tr>';
        return;
    }

    const r       = tasa / 100 / 12;
    const cuota   = capital * r * Math.pow(1+r, plazo) / (Math.pow(1+r, plazo) - 1);
    const totalPagado   = cuota * plazo;
    const totalIntereses = totalPagado - capital;

    // Con pago extra
    let resExtra = '';
    if (pagoExtra > cuota) {
        let saldo = capital, mesesExtra = 0, interesesExtra = 0;
        while (saldo > 0.01 && mesesExtra < 600) {
            const int = saldo * r;
            interesesExtra += int;
            saldo = Math.max(0, saldo + int - pagoExtra);
            mesesExtra++;
        }
        const ahorraMeses    = plazo - mesesExtra;
        const ahorraIntereses = totalIntereses - interesesExtra;
        resExtra = `
        <div style="margin-top:14px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;">
            <div style="font-size:12px;font-weight:700;color:#166534;margin-bottom:8px;"><i class="bi bi-lightbulb-fill"></i> Pagando ${fmt.moneyQ(pagoExtra)}/mes:</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <div style="text-align:center;">
                    <div style="font-size:10px;color:#166534;font-weight:700;">TERMINAS EN</div>
                    <div style="font-size:18px;font-weight:800;color:#16a34a;">${mesesExtra} meses</div>
                    <div style="font-size:10px;color:#16a34a;">${ahorraMeses > 0 ? ahorraMeses+' meses antes' : 'igual'}</div>
                </div>
                <div style="text-align:center;">
                    <div style="font-size:10px;color:#166534;font-weight:700;">AHORRAS EN INTERESES</div>
                    <div style="font-size:18px;font-weight:800;color:#16a34a;">${fmt.moneyQ(Math.max(0, ahorraIntereses))}</div>
                </div>
            </div>
        </div>`;
    }

    resEl.innerHTML = `
        <div class="section-header"><h3><i class="bi bi-bar-chart-fill"></i> Resultado</h3></div>
        <div style="padding:18px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                <div style="background:#eff6ff;border-radius:10px;padding:14px;text-align:center;">
                    <div style="font-size:10px;color:#1e40af;font-weight:700;text-transform:uppercase;">Cuota Mensual</div>
                    <div style="font-size:26px;font-weight:800;color:#2563eb;">${fmt.moneyQ(cuota)}</div>
                </div>
                <div style="background:#fef2f2;border-radius:10px;padding:14px;text-align:center;">
                    <div style="font-size:10px;color:#991b1b;font-weight:700;text-transform:uppercase;">Total Intereses</div>
                    <div style="font-size:26px;font-weight:800;color:#dc2626;">${fmt.moneyQ(totalIntereses)}</div>
                </div>
            </div>
            <div style="background:#f8fafc;border-radius:10px;padding:12px;font-size:13px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                    <span style="color:#64748b;">Capital prestado:</span>
                    <strong>${fmt.moneyQ(capital)}</strong>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                    <span style="color:#64748b;">Total a pagar:</span>
                    <strong>${fmt.moneyQ(totalPagado)}</strong>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                    <span style="color:#64748b;">Costo del préstamo:</span>
                    <strong style="color:#dc2626;">${((totalIntereses/capital)*100).toFixed(1)}% del capital</strong>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#64748b;">Duración:</span>
                    <strong>${plazo} meses (${(plazo/12).toFixed(1)} años)</strong>
                </div>
            </div>
            ${resExtra}
        </div>`;

    // Tabla amortización (máx 24 meses)
    let saldo = capital;
    const rows = [];
    for (let i = 1; i <= Math.min(plazo, 24); i++) {
        const int  = saldo * r;
        const cap  = cuota - int;
        saldo = Math.max(0, saldo - cap);
        rows.push({ i, cuota, cap, int, saldo });
    }
    tablaEl.innerHTML = rows.map(r => `<tr>
        <td style="text-align:center;font-weight:600;color:#64748b;">${r.i}</td>
        <td style="font-weight:600;">${fmt.moneyQ(r.cuota)}</td>
        <td style="color:#16a34a;">${fmt.moneyQ(r.cap)}</td>
        <td style="color:#dc2626;">${fmt.moneyQ(r.int)}</td>
        <td style="font-weight:600;color:#0f172a;">${fmt.moneyQ(r.saldo)}</td>
    </tr>`).join('') + (plazo > 24 ? `<tr><td colspan="5" style="text-align:center;color:#94a3b8;font-size:12px;padding:10px;">... ${plazo-24} meses más hasta completar el plazo</td></tr>` : '');
}

// ============================================================
// RESUMEN DE TARJETAS
// ============================================================
function renderTarjetas() {
    const deudas   = DB.get('deudas');
    const tarjetas = deudas.filter(d => d.tipo === 'tarjeta');
    const grid   = document.getElementById('tarjetas-grid');
    const sinDat = document.getElementById('tarjetas-sin-datos');
    const alertasEl = document.getElementById('tarjetas-alertas');

    if (!tarjetas.length) {
        grid.style.display   = 'none';
        sinDat.style.display = '';
        if (alertasEl) alertasEl.innerHTML = '';
        return;
    }
    grid.style.display   = '';
    sinDat.style.display = 'none';

    const hoy    = new Date();
    const diaHoy = hoy.getDate();
    const urgentes = [];

    grid.innerHTML = tarjetas.map(t => {
        const saldoQ   = toGTQ(t.saldo_actual, t.moneda || 'GTQ');
        const limiteQ  = t.limite_credito ? toGTQ(t.limite_credito, t.moneda || 'GTQ') : 0;
        const disponQ  = limiteQ > 0 ? Math.max(0, limiteQ - saldoQ) : 0;
        const pctUso   = limiteQ > 0 ? Math.min(100, saldoQ / limiteQ * 100) : null;
        const colorUso = pctUso === null ? '#94a3b8' : pctUso > 75 ? '#dc2626' : pctUso > 40 ? '#d97706' : '#16a34a';
        const bgUso    = pctUso === null ? '#f8fafc' : pctUso > 75 ? '#fef2f2' : pctUso > 40 ? '#fffbeb' : '#f0fdf4';

        let diasCorte = null, diasPago = null;
        if (t.fecha_corte) { diasCorte = t.fecha_corte - diaHoy; if (diasCorte < 0) diasCorte += 30; }
        if (t.fecha_pago)  { diasPago  = t.fecha_pago  - diaHoy; if (diasPago  < 0) diasPago  += 30; }

        if (diasPago !== null && diasPago <= 5)  urgentes.push({ nombre: t.nombre, tipo: 'PAGO',  dias: diasPago });
        if (diasCorte !== null && diasCorte <= 5) urgentes.push({ nombre: t.nombre, tipo: 'CORTE', dias: diasCorte });

        const proj = Calc.proyeccionDeuda(t);

        return `<div style="background:white;border-radius:16px;padding:22px;box-shadow:0 2px 8px rgba(0,0,0,.08);border:1px solid #f1f5f9;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px;">
                <div>
                    <div style="font-size:17px;font-weight:800;color:#0f172a;">${esc(t.nombre)}</div>
                    <div style="font-size:12px;color:#64748b;margin-top:2px;">${fmt.currBadge(t.moneda||'GTQ')} · ${t.tasa_interes_anual}% anual</div>
                </div>
                <span style="font-size:28px;"><i class="bi bi-credit-card-fill"></i></span>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">
                <div style="background:#fef2f2;border-radius:10px;padding:12px;text-align:center;">
                    <div style="font-size:10px;color:#991b1b;font-weight:700;text-transform:uppercase;">Saldo Actual</div>
                    <div style="font-size:18px;font-weight:800;color:#dc2626;">${fmt.money(t.saldo_actual, t.moneda||'GTQ')}</div>
                </div>
                <div style="background:${bgUso};border-radius:10px;padding:12px;text-align:center;">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:${colorUso};">
                        ${limiteQ > 0 ? 'Disponible' : 'Sin límite definido'}
                    </div>
                    <div style="font-size:18px;font-weight:800;color:${colorUso};">
                        ${limiteQ > 0 ? fmt.money(t.limite_credito, t.moneda||'GTQ') : '—'}
                    </div>
                </div>
            </div>

            ${limiteQ > 0 ? `
            <div style="margin-bottom:12px;">
                <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:4px;">
                    <span style="color:#64748b;">Utilización</span>
                    <span style="font-weight:700;color:${colorUso};">${pctUso.toFixed(0)}%</span>
                </div>
                <div style="background:#f1f5f9;border-radius:6px;height:10px;overflow:hidden;">
                    <div style="height:100%;width:${pctUso.toFixed(1)}%;background:${colorUso};border-radius:6px;transition:width .4s;"></div>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:10px;color:#94a3b8;margin-top:3px;">
                    <span>Usado: ${fmt.moneyQ(saldoQ)}</span>
                    <span>Disponible: ${fmt.moneyQ(disponQ)}</span>
                </div>
            </div>` : ''}

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px;">
                ${t.fecha_corte ? `<div style="background:${diasCorte<=5?'#fef2f2':'#f8fafc'};border-radius:8px;padding:8px 10px;text-align:center;">
                    <div style="font-size:9px;color:#64748b;font-weight:700;"><i class="bi bi-scissors"></i> CORTE</div>
                    <div style="font-size:14px;font-weight:700;color:${diasCorte<=5?'#dc2626':'#374151'};">Día ${t.fecha_corte}</div>
                    <div style="font-size:10px;color:${diasCorte<=5?'#dc2626':'#94a3b8'};">${diasCorte===0?'¡Hoy!':diasCorte+' días'}</div>
                </div>` : '<div></div>'}
                ${t.fecha_pago ? `<div style="background:${diasPago<=5?'#fef2f2':'#f0fdf4'};border-radius:8px;padding:8px 10px;text-align:center;">
                    <div style="font-size:9px;color:#64748b;font-weight:700;"><i class="bi bi-credit-card"></i> PAGO</div>
                    <div style="font-size:14px;font-weight:700;color:${diasPago<=5?'#dc2626':'#16a34a'};">Día ${t.fecha_pago}</div>
                    <div style="font-size:10px;color:${diasPago<=5?'#dc2626':'#16a34a'};">${diasPago===0?'¡Hoy!':diasPago+' días'}</div>
                </div>` : '<div></div>'}
            </div>

            <div style="border-top:1px solid #f1f5f9;padding-top:10px;display:flex;justify-content:space-between;font-size:12px;">
                <span style="color:#64748b;">Pago objetivo: <strong>${fmt.money(t.pago_objetivo_mensual, t.moneda||'GTQ')}/mes</strong></span>
                <span style="color:#94a3b8;">${proj.impossible?'<i class="bi bi-exclamation-triangle-fill"></i> Sin liquidar':proj.months+' meses p/ liquidar'}</span>
            </div>
        </div>`;
    }).join('');

    // Alertas urgentes
    if (alertasEl) {
        alertasEl.innerHTML = urgentes.map(u => `
            <div class="alert alert-danger" style="margin-bottom:8px;">
                <i class="bi bi-exclamation-octagon-fill"></i> <strong>${esc(u.nombre)}</strong> — ${u.tipo} ${u.dias === 0 ? '¡ES HOY!' : `en ${u.dias} día(s)`}
            </div>`).join('');
    }
}

// ============================================================
// PAGOS REALIZADOS
// ============================================================
function renderPagos() {
    const pagos  = DB.get('pagos_realizados');
    const deudas = DB.get('deudas');

    // Dropdown de deudas
    const deudaSelect = document.getElementById('pago-deuda-id');
    if (deudaSelect) {
        const cur = deudaSelect.value;
        deudaSelect.innerHTML = '<option value="">-- Seleccionar deuda --</option>' +
            deudas.map(d => `<option value="${d.id}" ${d.id===cur?'selected':''}>${esc(d.nombre)} (${fmt.money(d.saldo_actual, d.moneda||'GTQ')})</option>`).join('');
    }
    // Default mes actual
    const mesInput = document.getElementById('pago-mes');
    if (mesInput && !mesInput.value) mesInput.value = new Date().toISOString().slice(0,7);

    // Filtro de mes
    const filtroEl = document.getElementById('pagos-filtro-mes');
    const meses    = [...new Set(pagos.map(p => p.mes_str))].sort().reverse();
    if (filtroEl) {
        const cur = filtroEl.value;
        filtroEl.innerHTML = '<option value="">Todos los meses</option>' +
            meses.map(m => `<option value="${m}" ${m===cur?'selected':''}>${m}</option>`).join('');
        if (cur) filtroEl.value = cur;
    }

    const filtro   = filtroEl ? filtroEl.value : '';
    const filtrados = filtro ? pagos.filter(p => p.mes_str === filtro) : pagos;

    // Cards resumen
    const mesActual   = new Date().toISOString().slice(0,7);
    const esteMes     = pagos.filter(p => p.mes_str === mesActual);
    const pagadoMesQ  = esteMes.reduce((s,p) => s + toGTQ(p.monto, p.moneda||'GTQ'), 0);
    const objetivoQ   = Calc.totalPagosDeudas();
    const diferencia  = pagadoMesQ - objetivoQ;
    const totalHistQ  = pagos.reduce((s,p) => s + toGTQ(p.monto, p.moneda||'GTQ'), 0);

    document.getElementById('pagos-cards').innerHTML = `
        <div class="card income">
            <div class="card-label">Pagado este mes</div>
            <div class="card-value">${fmt.moneyQ(pagadoMesQ)}</div>
            <div class="card-sub">${esteMes.length} pago(s) en ${mesActual}</div>
        </div>
        <div class="card debt">
            <div class="card-label">Objetivo mensual</div>
            <div class="card-value">${fmt.moneyQ(objetivoQ)}</div>
            <div class="card-sub">Suma de todos los pagos objetivo</div>
        </div>
        <div class="card ${diferencia >= 0 ? 'income' : 'expense'}">
            <div class="card-label">${diferencia >= 0 ? '<i class="bi bi-check-circle-fill"></i> Por encima del objetivo' : '<i class="bi bi-exclamation-triangle-fill"></i> Falta por pagar'}</div>
            <div class="card-value">${fmt.moneyQ(Math.abs(diferencia))}</div>
            <div class="card-sub">${diferencia >= 0 ? 'Excelente, superaste la meta' : 'Para alcanzar el objetivo'}</div>
        </div>
        <div class="card available">
            <div class="card-label">Total histórico pagado</div>
            <div class="card-value">${fmt.moneyQ(totalHistQ)}</div>
            <div class="card-sub">${pagos.length} registros en total</div>
        </div>
    `;

    // Tabla
    const tbody = document.getElementById('tabla-pagos');
    if (!filtrados.length) {
        tbody.innerHTML = '<tr class="empty-row"><td colspan="6">No hay pagos registrados.</td></tr>';
        return;
    }
    const sorted = [...filtrados].sort((a,b) => b.mes_str.localeCompare(a.mes_str));
    tbody.innerHTML = sorted.map(p => {
        const deuda     = deudas.find(d => d.id === p.deuda_id);
        const objetivoD = deuda ? toGTQ(deuda.pago_objetivo_mensual, deuda.moneda||'GTQ') : 0;
        const pagadoQ   = toGTQ(p.monto, p.moneda||'GTQ');
        const diff      = pagadoQ - objetivoD;
        const pct       = objetivoD > 0 ? Math.min(150, pagadoQ / objetivoD * 100) : 100;
        return `<tr>
            <td style="font-weight:600;">${p.mes_str}</td>
            <td>${p.deuda_nombre}</td>
            <td style="color:#16a34a;font-weight:600;">${fmt.money(p.monto, p.moneda||'GTQ')}</td>
            <td>
                <div style="display:flex;align-items:center;gap:7px;">
                    <div class="progress-bar" style="width:70px;">
                        <div class="progress-fill" style="width:${Math.min(100,pct).toFixed(0)}%;background:${pct>=100?'#22c55e':'#f59e0b'};"></div>
                    </div>
                    <span style="font-size:11px;font-weight:700;color:${diff>=0?'#16a34a':'#dc2626'};">
                        ${diff>=0?'+':''}${fmt.moneyQ(diff)}
                    </span>
                </div>
                ${objetivoD>0 ? `<div style="font-size:10px;color:#94a3b8;">Obj: ${fmt.moneyQ(objetivoD)}</div>` : ''}
            </td>
            <td style="font-size:12px;color:#64748b;">${esc(p.notas)||'—'}</td>
            <td>
                <button class="btn btn-ghost btn-sm" onclick="deletePago('${p.id}')"><i class="bi bi-trash-fill"></i></button>
            </td>
        </tr>`;
    }).join('');
}

function savePago() {
    const deudaId = document.getElementById('pago-deuda-id').value;
    const mesStr  = document.getElementById('pago-mes').value;
    const monto   = parseFloat(document.getElementById('pago-monto').value);
    const moneda  = document.getElementById('pago-moneda').value;
    const notas   = document.getElementById('pago-notas').value.trim();
    if (!deudaId || !mesStr || isNaN(monto) || monto <= 0) { Swal.fire({ icon: 'warning', title: 'Campos incompletos', text: 'Completa: deuda, mes y monto.', confirmButtonColor: '#2563eb' }); return; }
    const deuda = DB.get('deudas').find(d => d.id === deudaId);
    const list  = DB.get('pagos_realizados');
    list.push({ id: DB.genId(), deuda_id: deudaId, deuda_nombre: deuda?.nombre || deudaId, mes_str: mesStr, monto, moneda, notas });
    DB.set('pagos_realizados', list);
    toggleForm('form-pago');
    document.getElementById('pago-monto').value = '';
    document.getElementById('pago-notas').value = '';
    renderPagos();
    _toast('Pago registrado');
}

async function deletePago(id) {
    const { isConfirmed } = await _confirm('¿Eliminar este registro de pago?');
    if (!isConfirmed) return;
    DB.set('pagos_realizados', DB.get('pagos_realizados').filter(p => p.id !== id));
    renderPagos(); _toast('Pago eliminado', 'error');
}

// ============================================================
// HISTORIAL MES A MES
// ============================================================
function renderHistorial() {
    // Preview estado actual
    const ingresosQ   = Calc.totalIngresos();
    const fijosQ      = Calc.totalGastosFijos();
    const pagosQ      = Calc.totalPagosDeudas();
    const variablesQ  = Calc.totalGastosVariables();
    const superavitQ  = Calc.superavit();
    const totalDeudaQ = Calc.totalDeudas();

    document.getElementById('historial-preview').innerHTML = `
        <div class="card income"><div class="card-label">Ingresos</div><div class="card-value">${fmt.moneyQ(ingresosQ)}</div><div class="card-sub">Activos este mes</div></div>
        <div class="card expense"><div class="card-label">Gastos Fijos</div><div class="card-value">${fmt.moneyQ(fijosQ)}</div></div>
        <div class="card debt"><div class="card-label">Pagos a Deudas</div><div class="card-value">${fmt.moneyQ(pagosQ)}</div></div>
        <div class="card ${superavitQ>=0?'income':'expense'}">
            <div class="card-label">Superávit</div>
            <div class="card-value" style="color:${superavitQ>=0?'#16a34a':'#dc2626'}">${fmt.moneyQ(superavitQ)}</div>
        </div>
        <div class="card available"><div class="card-label">Deuda Total</div><div class="card-value">${fmt.moneyQ(totalDeudaQ)}</div></div>
    `;

    // Lista de snapshots
    const historial = DB.get('historial_mensual');
    document.getElementById('historial-count').textContent = historial.length + ' snapshot(s)';
    const el = document.getElementById('historial-lista');
    if (!historial.length) {
        el.innerHTML = '<div class="alert alert-info">No hay snapshots guardados aún. Presiona "Guardar Snapshot del Mes" para registrar tu estado actual.</div>';
        return;
    }
    const sorted = [...historial].sort((a,b) => b.fecha.localeCompare(a.fecha));
    el.innerHTML = sorted.map(s => {
        const sc   = s.superavit >= 0 ? '#16a34a' : '#dc2626';
        const when = new Date(s.created_at).toLocaleDateString('es-GT', {day:'numeric',month:'long',year:'numeric'});
        return `
        <div style="border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px;margin-bottom:14px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
                <div>
                    <div style="font-size:17px;font-weight:800;color:#0f172a;">${s.fecha}</div>
                    <div style="font-size:11px;color:#94a3b8;">Guardado el ${when}</div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="text-align:right;">
                        <div style="font-size:11px;color:#64748b;">Superávit</div>
                        <div style="font-size:18px;font-weight:800;color:${sc};">${fmt.moneyQ(s.superavit)}</div>
                    </div>
                    <button class="btn btn-ghost btn-sm" onclick="deleteHistorial('${s.id}')"><i class="bi bi-trash-fill"></i></button>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:8px;">
                <div style="background:#f0fdf4;border-radius:8px;padding:10px;text-align:center;">
                    <div style="font-size:10px;color:#166534;font-weight:700;text-transform:uppercase;">Ingresos</div>
                    <div style="font-size:14px;font-weight:700;color:#16a34a;">${fmt.moneyQ(s.ingresos)}</div>
                </div>
                <div style="background:#fef2f2;border-radius:8px;padding:10px;text-align:center;">
                    <div style="font-size:10px;color:#991b1b;font-weight:700;text-transform:uppercase;">Gastos Fijos</div>
                    <div style="font-size:14px;font-weight:700;color:#dc2626;">${fmt.moneyQ(s.gastos_fijos)}</div>
                </div>
                <div style="background:#fffbeb;border-radius:8px;padding:10px;text-align:center;">
                    <div style="font-size:10px;color:#92400e;font-weight:700;text-transform:uppercase;">Deudas/mes</div>
                    <div style="font-size:14px;font-weight:700;color:#d97706;">${fmt.moneyQ(s.pagos_deudas)}</div>
                </div>
                <div style="background:#f5f3ff;border-radius:8px;padding:10px;text-align:center;">
                    <div style="font-size:10px;color:#5b21b6;font-weight:700;text-transform:uppercase;">Variables</div>
                    <div style="font-size:14px;font-weight:700;color:#7c3aed;">${fmt.moneyQ(s.gastos_variables)}</div>
                </div>
                <div style="background:#eff6ff;border-radius:8px;padding:10px;text-align:center;">
                    <div style="font-size:10px;color:#1e40af;font-weight:700;text-transform:uppercase;">Deuda Total</div>
                    <div style="font-size:14px;font-weight:700;color:#2563eb;">${fmt.moneyQ(s.total_deuda)}</div>
                </div>
            </div>
        </div>`;
    }).join('');
}

async function saveHistorial() {
    const now   = new Date();
    const fecha = now.toISOString().slice(0,7);
    const list  = DB.get('historial_mensual');
    if (list.some(s => s.fecha === fecha)) {
        const { isConfirmed } = await _confirm(`Ya existe un snapshot para ${fecha}. ¿Reemplazarlo?`);
        if (!isConfirmed) return;
        DB.set('historial_mensual', list.filter(s => s.fecha !== fecha));
    }
    const snapshot = {
        id: DB.genId(), fecha,
        ingresos:        Calc.totalIngresos(),
        gastos_fijos:    Calc.totalGastosFijos(),
        pagos_deudas:    Calc.totalPagosDeudas(),
        gastos_variables:Calc.totalGastosVariables(),
        superavit:       Calc.superavit(),
        total_deuda:     Calc.totalDeudas(),
        created_at:      now.toISOString()
    };
    const updated = [...DB.get('historial_mensual'), snapshot];
    DB.set('historial_mensual', updated);
    renderHistorial();
    _toast(`Snapshot de ${fecha} guardado`);
}

async function deleteHistorial(id) {
    const { isConfirmed } = await _confirm('¿Eliminar este registro del historial?');
    if (!isConfirmed) return;
    DB.set('historial_mensual', DB.get('historial_mensual').filter(h => h.id !== id));
    renderHistorial(); _toast('Historial eliminado', 'error');
}

// ============================================================
// INIT
// ============================================================
async function init() {
    await _cargarDelServidor();
    // Restaurar modo oscuro
    if (localStorage.getItem('finanzas_dark_mode') === '1') {
        document.body.classList.add('dark-mode');
        document.getElementById('dark-mode-label').textContent = 'Modo Claro';
        document.getElementById('dark-mode-icon').innerHTML = '<i class="bi bi-sun"></i>';
    }
    document.getElementById('exchange-rate').value = getExchangeRate().toFixed(2);
    updateDeudaFormFields();
    renderDashboard();
    renderIngresos();
    renderGastosFijos();
    renderGastosVariables();
    renderDeudas();
}

init();
</script>
<!-- SweetAlert2 -->
<script src="/vendor/sweetalert2/sweetalert2.all.min.js"></script>
</body>
</html>
