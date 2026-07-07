<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi perfil — Finanzas GT</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E%F0%9F%92%BC%3C/text%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <style>
        *, *::before, *::after { font-family: 'Inter', system-ui, sans-serif; box-sizing: border-box; }
        .pw-toggle {
            position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: #64748b; cursor: pointer;
            padding: 6px 8px; font-size: 15px; display: flex; align-items: center;
            transition: color .15s;
        }
        .pw-toggle:hover { color: #cbd5e1; }
    </style>
</head>
<body style="min-height:100vh;margin:0;background:#0f172a;">

    {{-- Navbar --}}
    <nav style="background:#1e293b;border-bottom:1px solid #334155;padding:14px 24px;display:flex;align-items:center;justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:10px;">
            <span style="font-size:22px;">💼</span>
            <span style="color:#f8fafc;font-weight:700;font-size:15px;">Finanzas GT</span>
        </div>
        <div style="display:flex;align-items:center;gap:12px;">
            <a href="/finanzas" style="color:#94a3b8;font-size:13px;text-decoration:none;font-weight:500;">← Volver</a>
            <form method="POST" action="/logout" style="margin:0;">
                @csrf
                <button type="submit" style="background:#1e3a5f;border:1px solid #2563eb;color:#93c5fd;border-radius:8px;padding:6px 14px;font-size:12px;cursor:pointer;font-weight:600;">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </nav>

    <div style="max-width:560px;margin:40px auto;padding:0 16px;">

        {{-- Mensajes --}}
        @if(session('success'))
        <div style="background:rgba(34,197,94,0.15);border:1px solid #22c55e;border-radius:12px;padding:14px 18px;margin-bottom:20px;color:#86efac;font-size:14px;">
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div style="background:rgba(220,38,38,0.15);border:1px solid #dc2626;border-radius:12px;padding:14px 18px;margin-bottom:20px;color:#fca5a5;font-size:14px;">
            {{ session('error') }}
        </div>
        @endif

        {{-- Avatar + nombre --}}
        <div style="background:#1e293b;border:1px solid #334155;border-radius:20px;padding:32px;margin-bottom:20px;text-align:center;">
            @if($currentUser->avatar)
                <img src="{{ $currentUser->avatar }}" alt="Avatar"
                    style="width:80px;height:80px;border-radius:50%;border:3px solid #3b82f6;object-fit:cover;margin:0 auto 16px;">
            @else
                <div style="width:80px;height:80px;border-radius:50%;background:#1d4ed8;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:32px;font-weight:700;color:white;">
                    {{ strtoupper(substr($currentUser->name, 0, 1)) }}
                </div>
            @endif
            <h2 style="color:#f8fafc;font-size:18px;font-weight:700;margin:0 0 4px;">{{ $currentUser->name }}</h2>
            <span style="display:inline-block;background:{{ $currentUser->isAdmin() ? 'rgba(234,179,8,0.15)' : 'rgba(59,130,246,0.15)' }};border:1px solid {{ $currentUser->isAdmin() ? '#ca8a04' : '#2563eb' }};color:{{ $currentUser->isAdmin() ? '#fde68a' : '#93c5fd' }};border-radius:20px;padding:3px 12px;font-size:12px;font-weight:600;">
                {{ $currentUser->isAdmin() ? 'Administrador' : 'Usuario' }}
            </span>
            @if($currentUser->google_id)
            <div style="margin-top:10px;color:#64748b;font-size:12px;">🔗 Cuenta vinculada con Google</div>
            @endif
        </div>

        {{-- Datos generales --}}
        <div style="background:#1e293b;border:1px solid #334155;border-radius:20px;padding:28px;margin-bottom:20px;">
            <h3 style="color:#f8fafc;font-size:15px;font-weight:700;margin:0 0 20px;">Información personal</h3>

            <form method="POST" action="/perfil/actualizar">
                @csrf
                <div style="margin-bottom:16px;">
                    <label style="display:block;color:#94a3b8;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;">
                        Nombre
                    </label>
                    <input type="text" name="name" value="{{ old('name', $currentUser->name) }}"
                        style="width:100%;background:#0f172a;border:1px solid #475569;color:#f8fafc;border-radius:10px;padding:11px 16px;font-size:14px;outline:none;box-sizing:border-box;transition:border-color .15s;"
                        onfocus="this.style.borderColor='#3b82f6'"
                        onblur="this.style.borderColor='#475569'">
                    @error('name')<div style="color:#fca5a5;font-size:12px;margin-top:4px;">{{ $message }}</div>@enderror
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:block;color:#94a3b8;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;">
                        Correo electrónico
                    </label>
                    <input type="email" name="email" value="{{ old('email', $currentUser->email) }}"
                        style="width:100%;background:#0f172a;border:1px solid #475569;color:#f8fafc;border-radius:10px;padding:11px 16px;font-size:14px;outline:none;box-sizing:border-box;transition:border-color .15s;"
                        onfocus="this.style.borderColor='#3b82f6'"
                        onblur="this.style.borderColor='#475569'">
                    @error('email')<div style="color:#fca5a5;font-size:12px;margin-top:4px;">{{ $message }}</div>@enderror
                </div>
                <button type="submit"
                    style="background:#1d4ed8;color:white;border:none;border-radius:10px;padding:11px 24px;font-size:14px;font-weight:700;cursor:pointer;transition:background .15s;"
                    onmouseover="this.style.background='#2563eb'"
                    onmouseout="this.style.background='#1d4ed8'">
                    Guardar cambios
                </button>
            </form>
        </div>

        {{-- Cambiar contraseña --}}
        <div style="background:#1e293b;border:1px solid #334155;border-radius:20px;padding:28px;margin-bottom:40px;">
            <h3 style="color:#f8fafc;font-size:15px;font-weight:700;margin:0 0 6px;">Cambiar contraseña</h3>

            @if($currentUser->google_id && !$currentUser->password)
            <div style="background:rgba(234,179,8,0.1);border:1px solid #ca8a04;border-radius:10px;padding:12px 16px;color:#fde68a;font-size:13px;margin-bottom:0;">
                Tu cuenta fue creada con Google y no tiene contraseña propia. Puedes seguir usando Google para iniciar sesión.
            </div>
            @else
            <p style="color:#64748b;font-size:13px;margin:0 0 18px;">Escribe tu contraseña actual y la nueva que deseas usar.</p>
            <form method="POST" action="/perfil/cambiar-password">
                @csrf
                <div style="margin-bottom:14px;">
                    <label style="display:block;color:#94a3b8;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;">
                        Contraseña actual
                    </label>
                    <div style="position:relative;">
                        <input type="password" name="password_actual" id="pw-actual" autocomplete="current-password"
                            style="width:100%;background:#0f172a;border:1px solid #475569;color:#f8fafc;border-radius:10px;padding:11px 40px 11px 16px;font-size:14px;outline:none;box-sizing:border-box;transition:border-color .15s;"
                            placeholder="••••••••"
                            onfocus="this.style.borderColor='#3b82f6'"
                            onblur="this.style.borderColor='#475569'">
                        <button type="button" class="pw-toggle" onclick="togglePw('pw-actual', this)" aria-label="Mostrar contraseña">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div style="margin-bottom:14px;">
                    <label style="display:block;color:#94a3b8;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;">
                        Nueva contraseña
                    </label>
                    <div style="position:relative;">
                        <input type="password" name="password" id="pw-nueva" autocomplete="new-password"
                            style="width:100%;background:#0f172a;border:1px solid #475569;color:#f8fafc;border-radius:10px;padding:11px 40px 11px 16px;font-size:14px;outline:none;box-sizing:border-box;transition:border-color .15s;"
                            placeholder="Mínimo 6 caracteres"
                            onfocus="this.style.borderColor='#3b82f6'"
                            onblur="this.style.borderColor='#475569'">
                        <button type="button" class="pw-toggle" onclick="togglePw('pw-nueva', this)" aria-label="Mostrar contraseña">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    @error('password')<div style="color:#fca5a5;font-size:12px;margin-top:4px;">{{ $message }}</div>@enderror
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:block;color:#94a3b8;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;">
                        Confirmar nueva contraseña
                    </label>
                    <div style="position:relative;">
                        <input type="password" name="password_confirmation" id="pw-confirmar" autocomplete="new-password"
                            style="width:100%;background:#0f172a;border:1px solid #475569;color:#f8fafc;border-radius:10px;padding:11px 40px 11px 16px;font-size:14px;outline:none;box-sizing:border-box;transition:border-color .15s;"
                            placeholder="Repite la nueva contraseña"
                            onfocus="this.style.borderColor='#3b82f6'"
                            onblur="this.style.borderColor='#475569'">
                        <button type="button" class="pw-toggle" onclick="togglePw('pw-confirmar', this)" aria-label="Mostrar contraseña">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit"
                    style="background:#0f4c1a;border:1px solid #16a34a;color:#86efac;border-radius:10px;padding:11px 24px;font-size:14px;font-weight:700;cursor:pointer;transition:all .15s;"
                    onmouseover="this.style.background='#166534'"
                    onmouseout="this.style.background='#0f4c1a'">
                    Cambiar contraseña
                </button>
            </form>
            @endif
        </div>

    </div>

    <script>
        function togglePw(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon  = btn.querySelector('i');
            const show  = input.type === 'password';
            input.type = show ? 'text' : 'password';
            icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
            btn.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
        }
    </script>
</body>
</html>
