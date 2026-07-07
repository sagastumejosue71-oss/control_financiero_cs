@extends('admin.layout')

@section('title', 'Mi Perfil — Admin')
@section('page-title', 'Mi Perfil')
@section('page-subtitle', 'Actualiza tu información y contraseña')
@section('nav-perfil', 'active')

@section('content')

<div style="max-width:620px;">

    {{-- Avatar / resumen --}}
    <div class="section-card" style="margin-bottom:20px;">
        <div style="padding:24px 24px 20px;display:flex;align-items:center;gap:18px;">
            @if($currentUser->avatar)
                <img src="{{ $currentUser->avatar }}"
                    style="width:64px;height:64px;border-radius:50%;border:3px solid #6366f1;object-fit:cover;flex-shrink:0;">
            @else
                <div style="width:64px;height:64px;border-radius:50%;background:#4f46e5;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:800;color:#fff;flex-shrink:0;">
                    {{ strtoupper(substr($currentUser->name, 0, 1)) }}
                </div>
            @endif
            <div>
                <div style="color:#f8fafc;font-size:17px;font-weight:700;">{{ $currentUser->name }}</div>
                <div style="color:#64748b;font-size:13px;margin-top:2px;">{{ $currentUser->email }}</div>
                <span style="display:inline-block;margin-top:6px;background:rgba(99,102,241,.15);border:1px solid #6366f1;color:#a5b4fc;border-radius:999px;padding:2px 12px;font-size:11px;font-weight:700;text-transform:uppercase;">
                    {{ $currentUser->role }}
                </span>
                @if($currentUser->google_id)
                    <span style="display:inline-block;margin-top:6px;margin-left:6px;background:rgba(59,130,246,.1);border:1px solid #2563eb;color:#93c5fd;border-radius:999px;padding:2px 12px;font-size:11px;font-weight:700;">
                        🔗 Google
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Información personal --}}
    <div class="section-card" style="margin-bottom:20px;">
        <div class="section-header">
            <h3><i class="bi bi-person-fill"></i> Información personal</h3>
        </div>
        <div class="form-section">

            @if(session('success'))
            <div style="background:rgba(16,185,129,.12);border:1px solid #10b981;border-radius:10px;padding:12px 16px;margin-bottom:18px;color:#6ee7b7;font-size:13px;">
                {{ session('success') }}
            </div>
            @endif

            @if($errors->has('name') || $errors->has('email'))
            <div style="background:rgba(239,68,68,.12);border:1px solid #ef4444;border-radius:10px;padding:12px 16px;margin-bottom:18px;color:#fca5a5;font-size:13px;">
                @foreach($errors->only(['name','email']) as $e)<div>{{ $e }}</div>@endforeach
            </div>
            @endif

            <form method="POST" action="/admin/perfil/actualizar">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nombre *</label>
                        <input type="text" name="name" value="{{ old('name', $currentUser->name) }}"
                            placeholder="Tu nombre completo">
                    </div>
                    <div class="form-group">
                        <label>Correo electrónico *</label>
                        <input type="email" name="email" value="{{ old('email', $currentUser->email) }}"
                            placeholder="tu@correo.com">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Cambiar contraseña --}}
    <div class="section-card">
        <div class="section-header">
            <h3><i class="bi bi-key-fill"></i> Cambiar contraseña</h3>
        </div>
        <div class="form-section">

            @if($currentUser->google_id && !$currentUser->password)
            <div style="background:rgba(245,158,11,.1);border:1px solid #f59e0b;border-radius:10px;padding:14px 18px;color:#fcd34d;font-size:13px;">
                <i class="bi bi-info-circle"></i>
                Tu cuenta fue creada con Google y no tiene contraseña propia. Puedes seguir iniciando sesión con Google sin problema.
            </div>

            @else

            @if(session('pw_success'))
            <div style="background:rgba(16,185,129,.12);border:1px solid #10b981;border-radius:10px;padding:12px 16px;margin-bottom:18px;color:#6ee7b7;font-size:13px;">
                {{ session('pw_success') }}
            </div>
            @endif

            @if(session('pw_error'))
            <div style="background:rgba(239,68,68,.12);border:1px solid #ef4444;border-radius:10px;padding:12px 16px;margin-bottom:18px;color:#fca5a5;font-size:13px;">
                {{ session('pw_error') }}
            </div>
            @endif

            @if($errors->has('password'))
            <div style="background:rgba(239,68,68,.12);border:1px solid #ef4444;border-radius:10px;padding:12px 16px;margin-bottom:18px;color:#fca5a5;font-size:13px;">
                @foreach($errors->only(['password']) as $e)<div>{{ $e }}</div>@endforeach
            </div>
            @endif

            <form method="POST" action="/admin/perfil/cambiar-password">
                @csrf
                <div class="form-grid">
                    <div class="form-group" style="grid-column:1/-1;">
                        <label>Contraseña actual *</label>
                        <input type="password" name="password_actual" autocomplete="current-password"
                            placeholder="••••••••">
                    </div>
                    <div class="form-group">
                        <label>Nueva contraseña *</label>
                        <input type="password" name="password" autocomplete="new-password"
                            placeholder="Mínimo 6 caracteres">
                    </div>
                    <div class="form-group">
                        <label>Confirmar nueva contraseña *</label>
                        <input type="password" name="password_confirmation" autocomplete="new-password"
                            placeholder="Repite la nueva contraseña">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-shield-lock"></i> Cambiar contraseña
                    </button>
                </div>
            </form>

            @endif
        </div>
    </div>

</div>

@endsection
