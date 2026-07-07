<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel de Administración')</title>
    <meta name="description" content="Panel de administración de Finanzas GT.">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E%E2%9A%99%EF%B8%8F%3C/text%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/vendor/sweetalert2/sweetalert2.min.css">
    <script src="/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <style>
        :root {
            --bg:#0b1220;
            --panel:#111827;
            --panel-2:#1f2937;
            --border:#1f2937;
            --text:#e5e7eb;
            --muted:#9ca3af;
            --primary:#6366f1;
            --primary-2:#4f46e5;
            --success:#10b981;
            --warning:#f59e0b;
            --danger:#ef4444;
        }
        * { box-sizing: border-box; }
        body { margin:0; font-family:'Inter',system-ui,sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }
        a { color:inherit; text-decoration:none; }

        /* SHELL */
        .shell { display:flex; min-height:100vh; }
        .sidebar {
            width:260px; background:#0f172a; border-right:1px solid #1e293b;
            display:flex; flex-direction:column; flex-shrink:0;
        }
        .brand { padding:22px 22px 16px; border-bottom:1px solid #1e293b; }
        .brand h1 { margin:0; font-size:16px; font-weight:800; color:#fff; letter-spacing:.02em; }
        .brand p { margin:4px 0 0; font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:.1em; }

        .nav-section { padding:18px 16px 6px; color:#475569; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; }
        .nav-item {
            display:flex; align-items:center; gap:10px;
            padding:10px 14px; margin:2px 10px; border-radius:8px; cursor:pointer;
            color:#94a3b8; font-size:14px; font-weight:500; transition:all .15s;
        }
        .nav-item:hover { background:#1e293b; color:#e2e8f0; }
        .nav-item.active { background:var(--primary-2); color:#fff; }
        .nav-item i { font-size:17px; width:22px; text-align:center; }

        .sidebar-footer {
            margin-top:auto; padding:14px 16px; border-top:1px solid #1e293b;
            display:flex; flex-direction:column; gap:8px;
        }
        .user-card {
            background:#1e293b; border:1px solid #334155; border-radius:10px;
            padding:10px 12px; display:flex; align-items:center; gap:10px;
        }
        .user-avatar {
            width:34px; height:34px; border-radius:50%;
            background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center;
            font-weight:800; font-size:14px;
        }
        .user-meta { flex:1; min-width:0; }
        .user-meta .name { font-size:13px; font-weight:700; color:#f1f5f9; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .user-meta .role { font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:.08em; }

        .btn-logout, .btn-go-app {
            display:flex; align-items:center; justify-content:center; gap:6px;
            padding:9px 12px; border-radius:8px; font-size:12px; font-weight:700;
            border:none; cursor:pointer; transition:background .15s;
        }
        .btn-go-app { background:#334155; color:#e2e8f0; }
        .btn-go-app:hover { background:#475569; }
        .btn-logout { background:#7f1d1d; color:#fecaca; }
        .btn-logout:hover { background:#991b1b; color:#fff; }

        /* MAIN */
        .main { flex:1; display:flex; flex-direction:column; min-width:0; }
        .topbar {
            background:#0f172a; border-bottom:1px solid #1e293b;
            padding:16px 28px; display:flex; align-items:center; justify-content:space-between;
        }
        .topbar h2 { margin:0; font-size:20px; font-weight:700; color:#f8fafc; }
        .topbar .subtitle { color:#94a3b8; font-size:12px; margin-top:2px; }
        .topbar .pill {
            background:#1e3a8a; color:#bfdbfe; padding:5px 11px; border-radius:999px;
            font-size:11px; font-weight:700; letter-spacing:.05em; text-transform:uppercase;
        }
        .content { flex:1; padding:28px; overflow-y:auto; }

        /* COMMON ELEMENTS */
        .card {
            background:var(--panel); border:1px solid var(--border); border-radius:14px;
            padding:20px 22px; box-shadow:0 1px 3px rgba(0,0,0,.25);
        }
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:24px; }
        .stat { display:flex; align-items:center; gap:14px; }
        .stat .icon-bubble {
            width:48px; height:48px; border-radius:12px;
            display:flex; align-items:center; justify-content:center; font-size:22px;
            background:#1e293b;
        }
        .stat .label { color:#94a3b8; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; }
        .stat .value { color:#f8fafc; font-size:24px; font-weight:800; margin-top:2px; }

        .stat.primary  .icon-bubble { background:rgba(99,102,241,.15); color:#a5b4fc; }
        .stat.success  .icon-bubble { background:rgba(16,185,129,.15); color:#6ee7b7; }
        .stat.warning  .icon-bubble { background:rgba(245,158,11,.15); color:#fcd34d; }

        .section-card {
            background:var(--panel); border:1px solid var(--border); border-radius:14px;
            margin-bottom:20px; overflow:hidden;
        }
        .section-header {
            padding:16px 22px; border-bottom:1px solid var(--border);
            display:flex; align-items:center; justify-content:space-between;
        }
        .section-header h3 { margin:0; font-size:15px; font-weight:700; color:#f8fafc; }

        /* FORMS */
        .form-section { padding:20px 22px; }
        .form-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; }
        .form-group label {
            display:block; margin-bottom:6px; font-size:11px; font-weight:700;
            text-transform:uppercase; letter-spacing:.06em; color:#94a3b8;
        }
        .form-group input, .form-group select {
            width:100%; background:#0f172a; border:1px solid #334155;
            color:#f8fafc; border-radius:8px; padding:10px 12px; font-size:14px;
            outline:none; transition:border-color .15s;
        }
        .form-group input:focus, .form-group select:focus { border-color:var(--primary); }
        .form-actions { margin-top:18px; display:flex; gap:10px; flex-wrap:wrap; }

        /* BUTTONS */
        .btn {
            display:inline-flex; align-items:center; gap:6px;
            padding:9px 16px; border-radius:8px; border:none; cursor:pointer;
            font-size:13px; font-weight:700; transition:all .15s;
        }
        .btn-primary  { background:var(--primary-2); color:#fff; }
        .btn-primary:hover  { background:var(--primary); }
        .btn-success  { background:var(--success);   color:#fff; }
        .btn-success:hover  { background:#059669; }
        .btn-warning  { background:var(--warning);   color:#000; }
        .btn-warning:hover  { background:#d97706; }
        .btn-danger   { background:var(--danger);    color:#fff; }
        .btn-danger:hover   { background:#dc2626; }
        .btn-outline  { background:transparent; color:#cbd5e1; border:1px solid #334155; }
        .btn-outline:hover  { background:#1e293b; }
        .btn-sm { padding:6px 10px; font-size:11px; }

        /* TABLES */
        table { width:100%; border-collapse:collapse; }
        thead th {
            font-size:11px; font-weight:700; color:#94a3b8;
            text-transform:uppercase; letter-spacing:.06em;
            padding:12px 16px; text-align:left; border-bottom:1px solid var(--border); background:#0f172a;
        }
        tbody td { padding:14px 16px; font-size:14px; color:#e5e7eb; border-bottom:1px solid var(--border); }
        tbody tr:last-child td { border-bottom:none; }
        tbody tr:hover td { background:rgba(99,102,241,.05); }
        .empty-row td { text-align:center; color:#64748b; padding:32px 16px; font-style:italic; }

        /* BADGES */
        .badge {
            display:inline-flex; align-items:center; gap:4px;
            padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700;
        }
        .badge-admin   { background:rgba(99,102,241,.15);  color:#a5b4fc; }
        .badge-user    { background:rgba(148,163,184,.15); color:#cbd5e1; }
        .badge-active  { background:rgba(16,185,129,.15);  color:#6ee7b7; }
        .badge-inactive{ background:rgba(239,68,68,.15);   color:#fca5a5; }

        .row-actions { display:flex; gap:6px; flex-wrap:wrap; }

        /* MOBILE */
        .hamburger { display:none; background:none; border:none; color:#fff; font-size:22px; cursor:pointer; }
        .overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:40; }
        .overlay.show { display:block; }

        @media (max-width: 900px) {
            .sidebar {
                position:fixed; top:0; left:0; bottom:0; z-index:50;
                transform:translateX(-100%); transition:transform .2s;
            }
            .sidebar.open { transform:translateX(0); }
            .hamburger { display:block; }
            .content { padding:18px; }
            .topbar { padding:12px 16px; }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="shell">

    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <h1>⚙️ Panel Admin</h1>
            <p>Finanzas GT</p>
        </div>

        <div class="nav-section">Administración</div>
        <a class="nav-item @yield('nav-dashboard')" href="/admin">
            <i class="bi bi-speedometer2"></i><span>Dashboard</span>
        </a>
        <a class="nav-item @yield('nav-users')" href="/admin/usuarios">
            <i class="bi bi-people-fill"></i><span>Usuarios</span>
        </a>
        <a class="nav-item @yield('nav-expansiones')" href="/admin/expansiones">
            <i class="bi bi-boxes"></i><span>Expansiones</span>
        </a>

        <div class="nav-section">Otros</div>
        <a class="nav-item" href="/finanzas">
            <i class="bi bi-cash-coin"></i><span>App Financiera</span>
        </a>

        <div class="sidebar-footer">
            <a href="/admin/perfil" style="text-decoration:none;">
                <div class="user-card" style="transition:border-color .15s;cursor:pointer;"
                    onmouseover="this.style.borderColor='#6366f1'"
                    onmouseout="this.style.borderColor='#334155'">
                    @if(isset($currentUser) && $currentUser->avatar)
                        <img src="{{ $currentUser->avatar }}" style="width:34px;height:34px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                    @else
                        <div class="user-avatar">{{ strtoupper(substr($currentUser->name ?? 'A', 0, 1)) }}</div>
                    @endif
                    <div class="user-meta">
                        <div class="name">{{ $currentUser->name ?? 'Admin' }}</div>
                        <div class="role" style="display:flex;align-items:center;gap:4px;">
                            {{ $currentUser->role ?? 'admin' }}
                            <span style="color:#475569;font-size:9px;">· ver perfil</span>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right" style="color:#475569;font-size:12px;margin-left:auto;"></i>
                </div>
            </a>
            <a class="btn-go-app" href="/finanzas"><i class="bi bi-arrow-right-circle"></i> Ir a la App</a>
            <form method="POST" action="/logout" style="margin:0;">
                @csrf
                <button type="submit" class="btn-logout" style="width:100%;"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</button>
            </form>
        </div>
    </aside>

    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>

    <div class="main">
        <div class="topbar">
            <div style="display:flex; align-items:center; gap:12px;">
                <button class="hamburger" onclick="openSidebar()">☰</button>
                <div>
                    <h2>@yield('page-title', 'Dashboard')</h2>
                    <div class="subtitle">@yield('page-subtitle', 'Bienvenido al panel de administración')</div>
                </div>
            </div>
            <div class="pill"><i class="bi bi-shield-lock-fill"></i> Modo Admin</div>
        </div>
        <div class="content">
            @yield('content')
        </div>
    </div>
</div>

<script>
    function openSidebar()  { document.getElementById('sidebar').classList.add('open');  document.getElementById('overlay').classList.add('show'); }
    function closeSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('overlay').classList.remove('show'); }

    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    async function apiFetch(url, opts = {}) {
        opts.headers = Object.assign({
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': CSRF,
        }, opts.headers || {});
        const res = await fetch(url, opts);
        let data = null;
        try { data = await res.json(); } catch (_) {}
        if (!res.ok) throw new Error((data && data.message) || (data && data.error) || 'Error en la petición');
        return data;
    }
    function toast(msg, icon = 'success') {
        Swal.fire({ toast:true, position:'top-end', icon, title:msg, showConfirmButton:false, timer:2500, timerProgressBar:true });
    }
</script>

@stack('scripts')
</body>
</html>
