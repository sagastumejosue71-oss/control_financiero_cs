@extends('admin.layout')

@section('title', 'Usuarios — Admin')
@section('page-title', 'Gestión de Usuarios')
@section('page-subtitle', 'Crea, edita y elimina cuentas')
@section('nav-users', 'active')

@section('content')

<!-- ============ CREAR USUARIO ============ -->
<div class="section-card" id="create-card" style="display:none;">
    <div class="section-header">
        <h3><i class="bi bi-person-plus-fill"></i> Crear usuario</h3>
        <button class="btn btn-outline btn-sm" onclick="toggleCreate(false)"><i class="bi bi-x-lg"></i> Cerrar</button>
    </div>
    <div class="form-section">
        <div class="form-grid">
            <div class="form-group">
                <label>Nombre *</label>
                <input type="text" id="new-name" placeholder="Nombre completo">
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" id="new-email" placeholder="usuario@correo.com">
            </div>
            <div class="form-group">
                <label>Contraseña *</label>
                <div style="position:relative;">
                    <input type="password" id="new-password" placeholder="Mínimo 6 caracteres" style="padding-right:40px;">
                    <button type="button" class="pw-toggle" onclick="togglePw('new-password', this)" aria-label="Mostrar contraseña">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label>Rol</label>
                <select id="new-role">
                    <option value="user">Usuario</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select id="new-active">
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button class="btn btn-success" onclick="createUser()"><i class="bi bi-save"></i> Crear usuario</button>
            <button class="btn btn-outline" onclick="toggleCreate(false)">Cancelar</button>
        </div>
    </div>
</div>

<!-- ============ LISTA DE USUARIOS ============ -->
<div class="section-card">
    <div class="section-header">
        <h3><i class="bi bi-people-fill"></i> Usuarios registrados</h3>
        <button class="btn btn-primary" onclick="toggleCreate(true)"><i class="bi bi-plus-lg"></i> Nuevo usuario</button>
    </div>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th style="width:60px;">#</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody id="users-tbody">
                <tr class="empty-row"><td colspan="6">Cargando usuarios…</td></tr>
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('styles')
<style>
    .pw-toggle {
        position: absolute; right: 4px; top: 50%; transform: translateY(-50%);
        background: none; border: none; color: #64748b; cursor: pointer;
        padding: 6px 8px; font-size: 14px; display: flex; align-items: center;
        transition: color .15s;
    }
    .pw-toggle:hover { color: #e2e8f0; }
</style>
@endpush

@push('scripts')
<script>
    const CURRENT_USER_ID = {{ (int) ($currentUser->id ?? 0) }};

    function togglePw(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon  = btn.querySelector('i');
        const show  = input.type === 'password';
        input.type = show ? 'text' : 'password';
        icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        btn.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function toggleCreate(show) {
        document.getElementById('create-card').style.display = show ? 'block' : 'none';
        if (!show) {
            ['new-name','new-email','new-password'].forEach(id => document.getElementById(id).value = '');
            document.getElementById('new-role').value = 'user';
            document.getElementById('new-active').value = '1';
        }
    }

    let USERS_CACHE = {};

    async function loadUsers() {
        const tbody = document.getElementById('users-tbody');
        tbody.innerHTML = '<tr class="empty-row"><td colspan="6">Cargando usuarios…</td></tr>';
        try {
            const users = await apiFetch('/admin/api/users');
            USERS_CACHE = {};
            if (!users || users.length === 0) {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="6">No hay usuarios registrados.</td></tr>';
                return;
            }
            users.forEach(u => { USERS_CACHE[u.id] = u; });
            tbody.innerHTML = users.map(u => {
                const isSelf = u.id === CURRENT_USER_ID;
                return `
                <tr>
                    <td style="color:#64748b;">#${u.id}</td>
                    <td><strong>${escapeHtml(u.name)}</strong>${isSelf ? ' <span class="badge badge-admin" style="margin-left:6px;">tú</span>' : ''}</td>
                    <td>${escapeHtml(u.email)}</td>
                    <td><span class="badge ${u.role === 'admin' ? 'badge-admin' : 'badge-user'}">${u.role}</span></td>
                    <td><span class="badge ${u.active ? 'badge-active' : 'badge-inactive'}">${u.active ? 'Activo' : 'Inactivo'}</span></td>
                    <td>
                        <div class="row-actions" style="justify-content:flex-end;">
                            <button class="btn btn-outline btn-sm" data-act="edit"    data-id="${u.id}"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-warning btn-sm" data-act="pass"    data-id="${u.id}"><i class="bi bi-key"></i></button>
                            <button class="btn ${u.active ? 'btn-outline' : 'btn-success'} btn-sm" data-act="toggle" data-id="${u.id}">
                                <i class="bi bi-${u.active ? 'pause-fill' : 'play-fill'}"></i>
                                ${u.active ? 'Desactivar' : 'Activar'}
                            </button>
                            <button class="btn btn-danger btn-sm" data-act="del" data-id="${u.id}" ${isSelf ? 'disabled style="opacity:.4;cursor:not-allowed;"' : ''}>
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
            }).join('');
        } catch (err) {
            tbody.innerHTML = `<tr class="empty-row"><td colspan="6">Error: ${escapeHtml(err.message)}</td></tr>`;
        }
    }

    // Delegación de eventos para los botones de la tabla
    document.getElementById('users-tbody').addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-act]');
        if (!btn) return;
        const id = parseInt(btn.dataset.id, 10);
        const user = USERS_CACHE[id];
        if (!user) return;
        switch (btn.dataset.act) {
            case 'edit':   openEdit(user); break;
            case 'pass':   openPassword(user.id, user.name); break;
            case 'toggle': toggleActive(user.id, !user.active); break;
            case 'del':    deleteUser(user.id, user.name); break;
        }
    });

    async function createUser() {
        const payload = {
            name: document.getElementById('new-name').value.trim(),
            email: document.getElementById('new-email').value.trim(),
            password: document.getElementById('new-password').value,
            role: document.getElementById('new-role').value,
            active: document.getElementById('new-active').value === '1',
        };
        if (!payload.name || !payload.email || !payload.password) {
            return Swal.fire({ icon:'error', title:'Faltan datos', text:'Nombre, email y contraseña son obligatorios.' });
        }
        if (payload.password.length < 6) {
            return Swal.fire({ icon:'error', title:'Contraseña inválida', text:'Debe tener al menos 6 caracteres.' });
        }
        try {
            await apiFetch('/admin/api/users', { method:'POST', body: JSON.stringify(payload) });
            toast('Usuario creado correctamente');
            toggleCreate(false);
            loadUsers();
        } catch (err) {
            Swal.fire({ icon:'error', title:'Error', text:err.message });
        }
    }

    async function openEdit(u) {
        const { value: formValues } = await Swal.fire({
            title: `Editar usuario #${u.id}`,
            html: `
                <div style="text-align:left;display:flex;flex-direction:column;gap:10px;">
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;color:#475569;">Nombre</label>
                    <input id="swal-name" class="swal2-input" style="margin:0;" value="${escapeHtml(u.name)}">
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;color:#475569;">Email</label>
                    <input id="swal-email" class="swal2-input" style="margin:0;" value="${escapeHtml(u.email)}">
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;color:#475569;">Rol</label>
                    <select id="swal-role" class="swal2-select" style="margin:0;">
                        <option value="user"  ${u.role === 'user'  ? 'selected' : ''}>Usuario</option>
                        <option value="admin" ${u.role === 'admin' ? 'selected' : ''}>Administrador</option>
                    </select>
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;color:#475569;">Estado</label>
                    <select id="swal-active" class="swal2-select" style="margin:0;">
                        <option value="1" ${u.active ? 'selected' : ''}>Activo</option>
                        <option value="0" ${!u.active ? 'selected' : ''}>Inactivo</option>
                    </select>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Guardar cambios',
            cancelButtonText: 'Cancelar',
            preConfirm: () => ({
                name: document.getElementById('swal-name').value.trim(),
                email: document.getElementById('swal-email').value.trim(),
                role: document.getElementById('swal-role').value,
                active: document.getElementById('swal-active').value === '1',
            })
        });
        if (!formValues) return;
        try {
            await apiFetch(`/admin/api/users/${u.id}`, { method:'PUT', body: JSON.stringify(formValues) });
            toast('Usuario actualizado');
            loadUsers();
        } catch (err) {
            Swal.fire({ icon:'error', title:'Error', text:err.message });
        }
    }

    async function openPassword(userId, userName) {
        const { value: pw } = await Swal.fire({
            title: 'Nueva contraseña',
            text: `Cambiar contraseña de ${userName} — úsalo si olvidó la suya o tuvo algún problema para entrar.`,
            html: `
                <div style="text-align:left;display:flex;flex-direction:column;gap:10px;">
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;color:#475569;">Nueva contraseña</label>
                    <div style="position:relative;">
                        <input id="swal-pw-new" type="password" class="swal2-input" style="margin:0;padding-right:40px;" placeholder="Mínimo 6 caracteres" autocomplete="new-password">
                        <button type="button" class="pw-toggle" onclick="togglePw('swal-pw-new', this)" aria-label="Mostrar contraseña">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;color:#475569;">Confirmar contraseña</label>
                    <div style="position:relative;">
                        <input id="swal-pw-confirm" type="password" class="swal2-input" style="margin:0;padding-right:40px;" placeholder="Repite la contraseña" autocomplete="new-password">
                        <button type="button" class="pw-toggle" onclick="togglePw('swal-pw-confirm', this)" aria-label="Mostrar contraseña">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Actualizar',
            cancelButtonText: 'Cancelar',
            focusConfirm: false,
            preConfirm: () => {
                const nueva     = document.getElementById('swal-pw-new').value;
                const confirmar = document.getElementById('swal-pw-confirm').value;
                if (!nueva || nueva.length < 6) {
                    Swal.showValidationMessage('Debe tener al menos 6 caracteres');
                    return false;
                }
                if (nueva !== confirmar) {
                    Swal.showValidationMessage('Las contraseñas no coinciden');
                    return false;
                }
                return nueva;
            },
        });
        if (!pw) return;
        try {
            await apiFetch(`/admin/api/users/${userId}/change-password`, { method:'POST', body: JSON.stringify({ password: pw }) });
            toast('Contraseña actualizada');
        } catch (err) {
            Swal.fire({ icon:'error', title:'Error', text:err.message });
        }
    }

    async function toggleActive(userId, active) {
        try {
            await apiFetch(`/admin/api/users/${userId}`, { method:'PUT', body: JSON.stringify({ active }) });
            toast(active ? 'Usuario activado' : 'Usuario desactivado');
            loadUsers();
        } catch (err) {
            Swal.fire({ icon:'error', title:'Error', text:err.message });
        }
    }

    async function deleteUser(userId, userName) {
        if (userId === CURRENT_USER_ID) {
            return Swal.fire({ icon:'warning', title:'No permitido', text:'No puedes eliminar tu propia cuenta.' });
        }
        const result = await Swal.fire({
            icon:'warning',
            title: `¿Eliminar a ${userName}?`,
            text: 'Esta acción es permanente.',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
        });
        if (!result.isConfirmed) return;
        try {
            await apiFetch(`/admin/api/users/${userId}`, { method:'DELETE' });
            toast('Usuario eliminado');
            loadUsers();
        } catch (err) {
            Swal.fire({ icon:'error', title:'Error', text:err.message });
        }
    }

    loadUsers();
</script>
@endpush
