@extends('admin.layout')

@section('title', 'Expansiones — Admin')
@section('page-title', 'Gestión de Expansiones')
@section('page-subtitle', 'Crea, edita y elimina expansiones de la plataforma')
@section('nav-expansiones', 'active')

@section('content')

<!-- ============ CREAR EXPANSIÓN ============ -->
<div class="section-card" id="create-card" style="display:none;">
    <div class="section-header">
        <h3><i class="bi bi-plus-circle-fill"></i> Nueva expansión</h3>
        <button class="btn btn-outline btn-sm" onclick="toggleCreate(false)">
            <i class="bi bi-x-lg"></i> Cerrar
        </button>
    </div>
    <div class="form-section">
        <div class="form-grid">
            <div class="form-group">
                <label>Nombre *</label>
                <input type="text" id="new-nombre" placeholder="Ej: Módulo de inversiones">
            </div>
            <div class="form-group">
                <label>Orden</label>
                <input type="number" id="new-orden" placeholder="0" value="0" min="0">
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label>Descripción</label>
                <input type="text" id="new-descripcion" placeholder="Descripción breve (opcional)">
            </div>
        </div>
        <div class="form-actions">
            <button class="btn btn-success" onclick="createExpansion()">
                <i class="bi bi-save"></i> Crear expansión
            </button>
            <button class="btn btn-outline" onclick="toggleCreate(false)">Cancelar</button>
        </div>
    </div>
</div>

<!-- ============ LISTA DE EXPANSIONES ============ -->
<div class="section-card">
    <div class="section-header">
        <h3><i class="bi bi-boxes"></i> Expansiones registradas</h3>
        <button class="btn btn-primary" onclick="toggleCreate(true)">
            <i class="bi bi-plus-lg"></i> Nueva expansión
        </button>
    </div>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th style="width:50px;">#</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th style="width:80px;">Orden</th>
                    <th style="width:110px;">Estado</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody id="exp-tbody">
                <tr class="empty-row"><td colspan="6">Cargando expansiones…</td></tr>
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    function toggleCreate(show) {
        document.getElementById('create-card').style.display = show ? 'block' : 'none';
        if (!show) {
            document.getElementById('new-nombre').value     = '';
            document.getElementById('new-descripcion').value = '';
            document.getElementById('new-orden').value      = '0';
        } else {
            setTimeout(() => document.getElementById('new-nombre').focus(), 50);
        }
    }

    let EXP_CACHE = {};

    async function loadExpansiones() {
        const tbody = document.getElementById('exp-tbody');
        tbody.innerHTML = '<tr class="empty-row"><td colspan="6">Cargando…</td></tr>';
        try {
            const list = await apiFetch('/admin/api/expansiones');
            EXP_CACHE = {};
            if (!list || list.length === 0) {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="6">No hay expansiones registradas. Crea la primera.</td></tr>';
                return;
            }
            list.forEach(e => { EXP_CACHE[e.id] = e; });
            tbody.innerHTML = list.map(e => `
                <tr>
                    <td style="color:#64748b;">#${e.id}</td>
                    <td><strong>${escapeHtml(e.nombre)}</strong></td>
                    <td style="color:#94a3b8;font-size:13px;">${escapeHtml(e.descripcion ?? '—')}</td>
                    <td style="text-align:center;">
                        <span style="background:#1e293b;border:1px solid #334155;border-radius:6px;padding:2px 10px;font-size:12px;font-weight:700;">
                            ${e.orden}
                        </span>
                    </td>
                    <td>
                        <span class="badge ${e.activa ? 'badge-active' : 'badge-inactive'}">
                            ${e.activa ? '● Activa' : '○ Inactiva'}
                        </span>
                    </td>
                    <td>
                        <div class="row-actions" style="justify-content:flex-end;">
                            <button class="btn btn-outline btn-sm" data-act="edit" data-id="${e.id}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn ${e.activa ? 'btn-outline' : 'btn-success'} btn-sm" data-act="toggle" data-id="${e.id}">
                                <i class="bi bi-${e.activa ? 'pause-fill' : 'play-fill'}"></i>
                                ${e.activa ? 'Desactivar' : 'Activar'}
                            </button>
                            <button class="btn btn-danger btn-sm" data-act="del" data-id="${e.id}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>`
            ).join('');
        } catch (err) {
            tbody.innerHTML = `<tr class="empty-row"><td colspan="6">Error al cargar: ${escapeHtml(err.message)}</td></tr>`;
        }
    }

    // Delegación de eventos
    document.getElementById('exp-tbody').addEventListener('click', e => {
        const btn = e.target.closest('button[data-act]');
        if (!btn) return;
        const id  = parseInt(btn.dataset.id, 10);
        const exp = EXP_CACHE[id];
        if (!exp) return;
        switch (btn.dataset.act) {
            case 'edit':   openEdit(exp); break;
            case 'toggle': toggleActiva(exp.id, !exp.activa); break;
            case 'del':    deleteExpansion(exp.id, exp.nombre); break;
        }
    });

    async function createExpansion() {
        const nombre     = document.getElementById('new-nombre').value.trim();
        const descripcion = document.getElementById('new-descripcion').value.trim();
        const orden      = parseInt(document.getElementById('new-orden').value, 10) || 0;

        if (!nombre) {
            return Swal.fire({ icon:'error', title:'Falta el nombre', text:'El nombre de la expansión es obligatorio.' });
        }
        try {
            await apiFetch('/admin/api/expansiones', {
                method: 'POST',
                body: JSON.stringify({ nombre, descripcion: descripcion || null, orden }),
            });
            toast('Expansión creada correctamente');
            toggleCreate(false);
            loadExpansiones();
        } catch (err) {
            Swal.fire({ icon:'error', title:'Error', text: err.message });
        }
    }

    async function openEdit(exp) {
        const { value: formValues } = await Swal.fire({
            title: `Editar expansión #${exp.id}`,
            html: `
                <div style="text-align:left;display:flex;flex-direction:column;gap:10px;">
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;color:#475569;">Nombre *</label>
                    <input id="swal-nombre" class="swal2-input" style="margin:0;" value="${escapeHtml(exp.nombre)}">
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;color:#475569;">Descripción</label>
                    <input id="swal-descripcion" class="swal2-input" style="margin:0;" value="${escapeHtml(exp.descripcion ?? '')}" placeholder="Opcional">
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;color:#475569;">Orden</label>
                    <input id="swal-orden" class="swal2-input" style="margin:0;" type="number" min="0" value="${exp.orden}">
                </div>`,
            showCancelButton: true,
            confirmButtonText: 'Guardar cambios',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const nombre = document.getElementById('swal-nombre').value.trim();
                if (!nombre) { Swal.showValidationMessage('El nombre es obligatorio.'); return false; }
                return {
                    nombre,
                    descripcion: document.getElementById('swal-descripcion').value.trim() || null,
                    orden: parseInt(document.getElementById('swal-orden').value, 10) || 0,
                };
            }
        });
        if (!formValues) return;
        try {
            await apiFetch(`/admin/api/expansiones/${exp.id}`, {
                method: 'PUT',
                body: JSON.stringify(formValues),
            });
            toast('Expansión actualizada');
            loadExpansiones();
        } catch (err) {
            Swal.fire({ icon:'error', title:'Error', text: err.message });
        }
    }

    async function toggleActiva(id, activa) {
        try {
            await apiFetch(`/admin/api/expansiones/${id}/toggle`, { method:'POST' });
            toast(activa ? 'Expansión activada' : 'Expansión desactivada');
            loadExpansiones();
        } catch (err) {
            Swal.fire({ icon:'error', title:'Error', text: err.message });
        }
    }

    async function deleteExpansion(id, nombre) {
        const result = await Swal.fire({
            icon: 'warning',
            title: `¿Eliminar "${escapeHtml(nombre)}"?`,
            text: 'Se eliminará permanentemente y no podrá recuperarse.',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
        });
        if (!result.isConfirmed) return;
        try {
            await apiFetch(`/admin/api/expansiones/${id}`, { method:'DELETE' });
            toast('Expansión eliminada');
            loadExpansiones();
        } catch (err) {
            Swal.fire({ icon:'error', title:'Error', text: err.message });
        }
    }

    loadExpansiones();
</script>
@endpush
