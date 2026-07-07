<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso — Finanzas GT</title>
    <meta name="description" content="Inicia sesión en Finanzas GT, tu control financiero personal.">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E%F0%9F%92%BC%3C/text%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { font-family: 'Inter', system-ui, sans-serif; box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
    </style>
</head>
<body style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0f172a;padding:24px;">
    <div style="background:#1e293b;border:1px solid #334155;border-radius:20px;padding:40px 36px;width:100%;max-width:380px;box-shadow:0 25px 60px rgba(0,0,0,0.5);">

        <div style="text-align:center;margin-bottom:32px;">
            <div style="font-size:48px;margin-bottom:14px;">💼</div>
            <h1 style="color:#f8fafc;font-size:24px;font-weight:800;margin:0 0 6px;letter-spacing:-0.5px;">Finanzas GT</h1>
            <p style="color:#64748b;font-size:13px;margin:0;font-weight:500;letter-spacing:0.02em;">Control Financiero Personal</p>
        </div>

        @if(session('error'))
        <div style="background:rgba(220,38,38,0.15);border:1px solid #dc2626;border-radius:10px;padding:12px 16px;margin-bottom:20px;color:#fca5a5;font-size:13px;">
            {{ session('error') }}
        </div>
        @endif

        <form method="POST" action="/login">
            @csrf
            <div style="margin-bottom:18px;">
                <label style="display:block;color:#94a3b8;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.1em;margin-bottom:8px;font-family:'Inter',sans-serif;">
                    Correo electrónico
                </label>
                <input type="email" name="email" value="{{ old('email') }}" autofocus autocomplete="username"
                    style="width:100%;background:#0f172a;border:1px solid #475569;color:#f1f5f9;border-radius:10px;padding:12px 16px;font-size:15px;font-weight:400;font-family:'Inter',sans-serif;outline:none;transition:border-color .15s;"
                    placeholder="usuario@correo.com"
                    onfocus="this.style.borderColor='#3b82f6'"
                    onblur="this.style.borderColor='#475569'">
            </div>
            <div style="margin-bottom:18px;">
                <label style="display:block;color:#94a3b8;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.1em;margin-bottom:8px;font-family:'Inter',sans-serif;">
                    Contraseña
                </label>
                <input type="password" name="password" autocomplete="current-password"
                    style="width:100%;background:#0f172a;border:1px solid #475569;color:#f1f5f9;border-radius:10px;padding:12px 16px;font-size:15px;font-weight:400;font-family:'Inter',sans-serif;outline:none;transition:border-color .15s;"
                    placeholder="••••••••"
                    onfocus="this.style.borderColor='#3b82f6'"
                    onblur="this.style.borderColor='#475569'">
            </div>

            {{-- Recordarme --}}
            <label id="remember-label"
                style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-bottom:20px;user-select:none;"
                onclick="toggleRemember()">
                <span id="remember-box"
                    style="width:20px;height:20px;min-width:20px;border-radius:6px;border:2px solid #475569;background:#0f172a;display:flex;align-items:center;justify-content:center;transition:all .15s;">
                    <svg id="remember-check" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12 12"
                        style="width:12px;height:12px;display:none;">
                        <polyline points="1.5,6 5,9.5 10.5,2.5" fill="none" stroke="white" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span style="color:#94a3b8;font-size:13px;font-weight:500;font-family:'Inter',sans-serif;">Recordarme</span>
                <input type="checkbox" name="remember_me" id="remember-input"
                    style="position:absolute;opacity:0;width:0;height:0;">
            </label>

            <button type="submit"
                style="width:100%;background:#1d4ed8;color:white;border:none;border-radius:10px;padding:13px;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;letter-spacing:0.03em;cursor:pointer;transition:background .15s;margin-top:4px;"
                onmouseover="this.style.background='#2563eb'"
                onmouseout="this.style.background='#1d4ed8'">
                Entrar →
            </button>

            <div style="margin:20px 0;display:flex;align-items:center;gap:12px;">
                <div style="flex:1;height:1px;background:#334155;"></div>
                <span style="color:#475569;font-size:12px;">o</span>
                <div style="flex:1;height:1px;background:#334155;"></div>
            </div>

            <a href="/auth/google"
                style="display:flex;align-items:center;justify-content:center;gap:10px;width:100%;background:#0f172a;border:1px solid #334155;color:#f1f5f9;border-radius:10px;padding:12px;font-size:14px;font-weight:500;font-family:'Inter',sans-serif;cursor:pointer;text-decoration:none;transition:border-color .15s;"
                onmouseover="this.style.borderColor='#3b82f6'"
                onmouseout="this.style.borderColor='#334155'">
                <svg width="18" height="18" viewBox="0 0 48 48">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.35-8.16 2.35-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                </svg>
                Continuar con Google
            </a>
        </form>

        <script>
        function toggleRemember() {
            const input = document.getElementById('remember-input');
            const box   = document.getElementById('remember-box');
            const check = document.getElementById('remember-check');
            input.checked = !input.checked;
            if (input.checked) {
                box.style.background = '#1d4ed8';
                box.style.borderColor = '#1d4ed8';
                check.style.display = 'block';
            } else {
                box.style.background = '#0f172a';
                box.style.borderColor = '#475569';
                check.style.display = 'none';
            }
        }
        </script>

        <p style="text-align:center;color:#64748b;font-size:13px;font-weight:400;font-family:'Inter',sans-serif;margin-top:20px;">
            ¿No tienes cuenta?
            <a href="/register" style="color:#3b82f6;text-decoration:none;font-weight:600;">Regístrate</a>
        </p>
        <p style="text-align:center;color:#475569;font-size:11.5px;font-weight:400;font-family:'Inter',sans-serif;margin-top:8px;">
            ¿Olvidaste tu contraseña? Solicítala a un administrador.
        </p>
    </div>
</body>
</html>
