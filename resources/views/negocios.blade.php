<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mis Negocios — Finanzas GT</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/vendor/sweetalert2/sweetalert2.min.css">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Inter', system-ui, sans-serif; background: #f1f5f9; color: #0f172a; }
        #topbar {
            background: #0f172a; color: #f8fafc; padding: 16px 28px;
            display: flex; align-items: center; justify-content: space-between;
        }
        #topbar h1 { font-size: 18px; margin: 0; }
        #topbar a { color: #93c5fd; text-decoration: none; font-size: 13px; }
        #content { padding: 24px 28px; max-width: 1100px; margin: 0 auto; }

        .card { background: #fff; border-radius: 12px; padding: 18px 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .card h2 { font-size: 15px; margin: 0 0 14px; display: flex; align-items: center; gap: 8px; }

        .selector-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        select, input, button { font-family: inherit; font-size: 13px; }
        select, input {
            padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff;
        }
        button {
            padding: 8px 14px; border: none; border-radius: 8px; cursor: pointer;
            background: #1d4ed8; color: #fff; font-weight: 600;
        }
        button.secondary { background: #e2e8f0; color: #334155; }
        button.danger { background: #fee2e2; color: #b91c1c; padding: 5px 9px; }
        button:disabled { opacity: .5; cursor: not-allowed; }

        .form-inline { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; align-items: center; }
        .form-inline input, .form-inline select { flex: 1; min-width: 110px; }

        .grid-cuentas { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; margin-bottom: 6px; }
        .cuenta-tile { border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; position: relative; }
        .cuenta-tile .nombre { font-weight: 600; font-size: 13px; }
        .cuenta-tile .tipo { font-size: 11px; color: #64748b; text-transform: uppercase; }
        .cuenta-tile .saldo { font-size: 18px; font-weight: 700; margin-top: 6px; }
        .cuenta-tile .saldo.neg { color: #dc2626; }
        .cuenta-tile .saldo.pos { color: #16a34a; }
        .cuenta-tile button.danger { position: absolute; top: 8px; right: 8px; }

        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 8px 6px; text-align: left; border-bottom: 1px solid #f1f5f9; }
        th { color: #64748b; font-size: 11px; text-transform: uppercase; }
        .tag { font-size: 11px; padding: 2px 8px; border-radius: 999px; font-weight: 600; }
        .tag.ingreso { background: #dcfce7; color: #166534; }
        .tag.gasto { background: #fee2e2; color: #991b1b; }

        .empty-state { color: #64748b; font-size: 13px; padding: 10px 0; }
        .resumen-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; }
        .resumen-tile { text-align: center; }
        .resumen-tile .label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
        .resumen-tile .valor { font-size: 20px; font-weight: 700; margin-top: 4px; }
        .resumen-tile .valor.neg { color: #dc2626; }
        .resumen-tile .valor.pos { color: #16a34a; }
        .oculto { display: none; }
    </style>
</head>
<body>

<div id="topbar">
    <h1>💼 Mis Negocios</h1>
    <a href="/finanzas"><i class="bi bi-arrow-left"></i> Volver a Finanzas</a>
</div>

<div id="content">

    <div class="card">
        <h2><i class="bi bi-briefcase"></i> Negocio activo</h2>
        <div class="selector-row">
            <select id="selNegocio" onchange="seleccionarNegocio(this.value)"></select>
            <button class="secondary" onclick="mostrarFormNegocio()"><i class="bi bi-plus-lg"></i> Nuevo negocio</button>
            <button class="secondary" onclick="editarNegocioActual()"><i class="bi bi-pencil"></i> Editar</button>
            <button class="danger" onclick="eliminarNegocioActual()"><i class="bi bi-trash"></i> Eliminar</button>
        </div>
        <div id="formNegocio" class="form-inline oculto" style="margin-top:12px;">
            <input id="negNombre" placeholder="Nombre del negocio">
            <input id="negMoneda" placeholder="Moneda (ej. GTQ)" maxlength="3" style="max-width:110px;">
            <button onclick="guardarNegocio()">Guardar</button>
            <button class="secondary" onclick="ocultarFormNegocio()">Cancelar</button>
        </div>
        <div id="sinNegocios" class="empty-state oculto">Todavía no tienes ningún negocio registrado. Crea el primero arriba.</div>
    </div>

    <div id="cardResumen" class="card oculto">
        <h2><i class="bi bi-graph-up"></i> Resumen del negocio</h2>
        <div class="resumen-grid">
            <div class="resumen-tile"><div class="label">Saldo total</div><div class="valor" id="rSaldoTotal">—</div></div>
            <div class="resumen-tile"><div class="label">Ingresos (mes)</div><div class="valor pos" id="rIngresosMes">—</div></div>
            <div class="resumen-tile"><div class="label">Gastos (mes)</div><div class="valor neg" id="rGastosMes">—</div></div>
            <div class="resumen-tile"><div class="label">Neto (mes)</div><div class="valor" id="rNetoMes">—</div></div>
        </div>
        <div style="margin-top:16px;">
            <canvas id="graficaMensual" height="90"></canvas>
        </div>
        <div style="margin-top:14px;display:flex;gap:8px;">
            <a id="btnExportarPdf" class="secondary" style="text-decoration:none;padding:8px 14px;border-radius:8px;display:inline-flex;align-items:center;gap:6px;" href="#" target="_blank"><i class="bi bi-file-earmark-pdf"></i> Exportar PDF</a>
            <a id="btnExportarWord" class="secondary" style="text-decoration:none;padding:8px 14px;border-radius:8px;display:inline-flex;align-items:center;gap:6px;" href="#" target="_blank"><i class="bi bi-file-earmark-word"></i> Exportar Word</a>
        </div>
    </div>

    <div id="panelesNegocio" class="oculto">

        <div class="card">
            <h2><i class="bi bi-wallet2"></i> Cuentas</h2>
            <div class="form-inline">
                <input id="cuentaNombre" placeholder="Nombre (ej. Caja, BI Cuenta X)">
                <select id="cuentaTipo">
                    <option value="efectivo">Efectivo</option>
                    <option value="banco" selected>Banco</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="otro">Otro</option>
                </select>
                <input id="cuentaSaldoInicial" type="number" step="0.01" placeholder="Saldo inicial" style="max-width:130px;">
                <button onclick="crearCuenta()">Agregar cuenta</button>
            </div>
            <div id="listaCuentas" class="grid-cuentas"></div>
            <div id="sinCuentas" class="empty-state oculto">No hay cuentas todavía. Agrega la primera arriba.</div>
        </div>

        <div class="card">
            <h2><i class="bi bi-tags"></i> Categorías</h2>
            <div class="form-inline">
                <input id="catNombre" placeholder="Nombre (ej. Ventas, Renta local)">
                <select id="catTipo">
                    <option value="ingreso">Ingreso</option>
                    <option value="gasto">Gasto</option>
                </select>
                <button id="btnCategoria" onclick="crearCategoria()">Agregar categoría</button>
                <button id="btnCancelarCategoria" class="secondary oculto" onclick="cancelarEdicionCategoria()">Cancelar</button>
            </div>
            <div id="listaCategorias"></div>
            <div id="sinCategorias" class="empty-state oculto">No hay categorías todavía.</div>
        </div>

        <div class="card">
            <h2><i class="bi bi-arrow-left-right"></i> Movimientos</h2>
            <div class="form-inline">
                <select id="movCuenta"></select>
                <select id="movTipo" onchange="filtrarCategoriasPorTipo()">
                    <option value="ingreso">Ingreso</option>
                    <option value="gasto">Gasto</option>
                </select>
                <select id="movCategoria"></select>
                <input id="movMonto" type="number" step="0.01" placeholder="Monto" style="max-width:110px;">
                <input id="movFecha" type="date">
                <input id="movDescripcion" placeholder="Descripción (opcional)">
                <button id="btnMovimiento" onclick="crearMovimiento()">Registrar</button>
                <button id="btnCancelarMovimiento" class="secondary oculto" onclick="cancelarEdicionMovimiento()">Cancelar</button>
            </div>
            <table>
                <thead><tr><th>Fecha</th><th>Cuenta</th><th>Categoría</th><th>Tipo</th><th>Monto</th><th>Descripción</th><th></th></tr></thead>
                <tbody id="listaMovimientos"></tbody>
            </table>
            <div id="sinMovimientos" class="empty-state oculto">No hay movimientos registrados todavía.</div>
        </div>

        <div class="card">
            <h2><i class="bi bi-credit-card"></i> Deudas</h2>
            <div class="form-inline">
                <input id="deudaAcreedor" placeholder="Acreedor">
                <input id="deudaMontoTotal" type="number" step="0.01" placeholder="Monto total" style="max-width:120px;">
                <input id="deudaSaldoPendiente" type="number" step="0.01" placeholder="Saldo pendiente" style="max-width:130px;">
                <input id="deudaFechaLimite" type="date" title="Fecha límite (opcional)">
                <button id="btnDeuda" onclick="crearDeuda()">Agregar deuda</button>
                <button id="btnCancelarDeuda" class="secondary oculto" onclick="cancelarEdicionDeuda()">Cancelar</button>
            </div>
            <table>
                <thead><tr><th>Acreedor</th><th>Total</th><th>Pendiente</th><th>Límite</th><th>Estado</th><th></th></tr></thead>
                <tbody id="listaDeudas"></tbody>
            </table>
            <div id="sinDeudas" class="empty-state oculto">No hay deudas registradas.</div>
        </div>

    </div>
</div>

<script src="/vendor/chartjs/chart.umd.js"></script>
<script src="/vendor/sweetalert2/sweetalert2.all.min.js"></script>
<script>
function _csrf() { return document.querySelector('meta[name="csrf-token"]').content; }
function _toast(title, icon = 'success') {
    Swal.fire({ icon, title, toast: true, position: 'top-end', showConfirmButton: false, timer: 2500, timerProgressBar: true });
}
function _confirm(text) {
    return Swal.fire({ title: '¿Confirmar?', text, icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonColor: '#6b7280', confirmButtonText: 'Sí, continuar', cancelButtonText: 'Cancelar' });
}

async function _api(url, method = 'GET', body = null) {
    const opts = { method, headers: { 'Accept': 'application/json' } };
    if (body !== null) {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(body);
    }
    if (method !== 'GET') opts.headers['X-CSRF-TOKEN'] = _csrf();

    const res = await fetch(url, opts);
    let json = null;
    try { json = await res.json(); } catch (e) { /* respuesta vacía */ }

    if (!res.ok) {
        let msg = 'Ocurrió un error inesperado.';
        if (json) {
            const primerError = json.errors ? Object.values(json.errors)[0] : null;
            if (Array.isArray(primerError) && primerError.length) msg = primerError[0];
            else if (json.message || json.error) msg = json.message || json.error;
        }
        _toast(msg, 'error');
        throw new Error(msg);
    }
    return json;
}

let negocioActual = null;
let categoriasCache = [];
let negociosCache = [];
let negEditId = null;
let catEditId = null;
let movimientosCache = [];
let movEditId = null;
let deudasCache = [];
let deudaEditId = null;

// ── Negocios ─────────────────────────────────────────────────────────────
async function cargarNegocios(seleccionarId = null) {
    negociosCache = await _api('/api/negocios');
    const sel = document.getElementById('selNegocio');
    sel.innerHTML = '';

    if (!negociosCache.length) {
        document.getElementById('sinNegocios').classList.remove('oculto');
        document.getElementById('panelesNegocio').classList.add('oculto');
        document.getElementById('cardResumen').classList.add('oculto');
        return;
    }
    document.getElementById('sinNegocios').classList.add('oculto');

    negociosCache.forEach(n => {
        const opt = document.createElement('option');
        opt.value = n.id;
        opt.textContent = `${n.nombre} (${n.moneda})`;
        sel.appendChild(opt);
    });

    const idAUsar = seleccionarId || negocioActual || negociosCache[0].id;
    sel.value = idAUsar;
    await seleccionarNegocio(idAUsar);
}

function mostrarFormNegocio() {
    negEditId = null;
    document.getElementById('negNombre').value = '';
    document.getElementById('negMoneda').value = '';
    document.getElementById('formNegocio').classList.remove('oculto');
}
function ocultarFormNegocio() {
    document.getElementById('formNegocio').classList.add('oculto');
    document.getElementById('negNombre').value = '';
    document.getElementById('negMoneda').value = '';
    negEditId = null;
}

function editarNegocioActual() {
    const n = negociosCache.find(x => x.id == negocioActual);
    if (!n) { _toast('Selecciona un negocio primero', 'warning'); return; }
    negEditId = n.id;
    document.getElementById('negNombre').value = n.nombre;
    document.getElementById('negMoneda').value = n.moneda;
    document.getElementById('formNegocio').classList.remove('oculto');
}

async function guardarNegocio() {
    const nombre = document.getElementById('negNombre').value.trim();
    const moneda = document.getElementById('negMoneda').value.trim().toUpperCase() || 'GTQ';
    if (!nombre) { _toast('Ponle un nombre al negocio', 'warning'); return; }

    if (negEditId) {
        await _api(`/api/negocios/${negEditId}`, 'PUT', { nombre, moneda });
        ocultarFormNegocio();
        _toast('Negocio actualizado');
        await cargarNegocios(negEditId);
    } else {
        const data = await _api('/api/negocios', 'POST', { nombre, moneda });
        ocultarFormNegocio();
        _toast('Negocio creado');
        await cargarNegocios(data.negocio.id);
    }
}

async function eliminarNegocioActual() {
    if (!negocioActual) { _toast('Selecciona un negocio primero', 'warning'); return; }
    const r = await _confirm('Se eliminará el negocio y TODAS sus cuentas, categorías, movimientos y deudas.');
    if (!r.isConfirmed) return;

    await _api(`/api/negocios/${negocioActual}`, 'DELETE');
    _toast('Negocio eliminado');
    negocioActual = null;
    await cargarNegocios();
}

async function seleccionarNegocio(id) {
    negocioActual = id;
    document.getElementById('panelesNegocio').classList.remove('oculto');
    document.getElementById('cardResumen').classList.remove('oculto');
    await Promise.all([cargarCuentas(), cargarCategorias(), cargarDeudas(), cargarResumen()]);
    await cargarMovimientos();
}

async function cargarResumen() {
    const r = await _api(`/api/negocios/${negocioActual}/resumen`);
    document.getElementById('rSaldoTotal').textContent = `Q ${r.saldo_total.toFixed(2)}`;
    document.getElementById('rIngresosMes').textContent = `Q ${r.ingresos_mes.toFixed(2)}`;
    document.getElementById('rGastosMes').textContent = `Q ${r.gastos_mes.toFixed(2)}`;
    const netoEl = document.getElementById('rNetoMes');
    netoEl.textContent = `Q ${r.neto_mes.toFixed(2)}`;
    netoEl.className = 'valor ' + (r.neto_mes < 0 ? 'neg' : 'pos');

    document.getElementById('btnExportarPdf').href = `/api/negocios/${negocioActual}/exportar-pdf`;
    document.getElementById('btnExportarWord').href = `/api/negocios/${negocioActual}/exportar-word`;

    await cargarGraficaMensual();
}

let _chartMensual = null;
async function cargarGraficaMensual() {
    const meses = await _api(`/api/negocios/${negocioActual}/estadisticas-mensuales`);
    const ctx = document.getElementById('graficaMensual');

    if (_chartMensual) _chartMensual.destroy();
    _chartMensual = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: meses.map(m => m.mes),
            datasets: [
                { label: 'Ingresos', data: meses.map(m => m.ingresos), backgroundColor: '#16a34a' },
                { label: 'Gastos',   data: meses.map(m => m.gastos),   backgroundColor: '#dc2626' },
            ],
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true } },
        },
    });
}

// ── Cuentas ──────────────────────────────────────────────────────────────
async function cargarCuentas() {
    const cuentas = await _api(`/api/negocios/${negocioActual}/cuentas`);
    const cont = document.getElementById('listaCuentas');
    cont.innerHTML = '';
    document.getElementById('sinCuentas').classList.toggle('oculto', cuentas.length > 0);

    const selMov = document.getElementById('movCuenta');
    selMov.innerHTML = '';

    cuentas.forEach(c => {
        const saldo = Number(c.saldo_actual);
        const tile = document.createElement('div');
        tile.className = 'cuenta-tile';
        tile.innerHTML = `
            <button class="danger" onclick="eliminarCuenta(${c.id})"><i class="bi bi-trash"></i></button>
            <div class="nombre">${c.nombre}</div>
            <div class="tipo">${c.tipo}</div>
            <div class="saldo ${saldo < 0 ? 'neg' : 'pos'}">Q ${saldo.toFixed(2)}</div>
        `;
        cont.appendChild(tile);

        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = c.nombre;
        selMov.appendChild(opt);
    });
}

async function crearCuenta() {
    const nombre = document.getElementById('cuentaNombre').value.trim();
    const tipo = document.getElementById('cuentaTipo').value;
    const saldo_inicial = parseFloat(document.getElementById('cuentaSaldoInicial').value) || 0;
    if (!nombre) { _toast('Ponle un nombre a la cuenta', 'warning'); return; }

    await _api(`/api/negocios/${negocioActual}/cuentas`, 'POST', { nombre, tipo, saldo_inicial });
    document.getElementById('cuentaNombre').value = '';
    document.getElementById('cuentaSaldoInicial').value = '';
    _toast('Cuenta creada');
    await cargarCuentas();
    await cargarResumen();
}

async function eliminarCuenta(id) {
    const r = await _confirm('Se eliminará la cuenta y todos sus movimientos.');
    if (!r.isConfirmed) return;
    await _api(`/api/negocios/${negocioActual}/cuentas/${id}`, 'DELETE');
    _toast('Cuenta eliminada');
    await cargarCuentas();
    await cargarMovimientos();
    await cargarResumen();
}

// ── Categorías ───────────────────────────────────────────────────────────
async function cargarCategorias() {
    categoriasCache = await _api(`/api/negocios/${negocioActual}/categorias`);
    const cont = document.getElementById('listaCategorias');
    cont.innerHTML = '';
    document.getElementById('sinCategorias').classList.toggle('oculto', categoriasCache.length > 0);

    categoriasCache.forEach(cat => {
        const row = document.createElement('div');
        row.style.cssText = 'display:flex;align-items:center;gap:10px;padding:6px 0;';
        row.innerHTML = `
            <span class="tag ${cat.tipo}">${cat.tipo}</span>
            <span style="flex:1;">${cat.nombre}</span>
            <button class="secondary" onclick="editarCategoria(${cat.id})"><i class="bi bi-pencil"></i></button>
            <button class="danger" onclick="eliminarCategoria(${cat.id})"><i class="bi bi-trash"></i></button>
        `;
        cont.appendChild(row);
    });

    filtrarCategoriasPorTipo();
}

function filtrarCategoriasPorTipo() {
    const tipo = document.getElementById('movTipo').value;
    const sel = document.getElementById('movCategoria');
    sel.innerHTML = '<option value="">(sin categoría)</option>';
    categoriasCache.filter(c => c.tipo === tipo).forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = c.nombre;
        sel.appendChild(opt);
    });
}

function editarCategoria(id) {
    const cat = categoriasCache.find(c => c.id === id);
    if (!cat) return;
    catEditId = id;
    document.getElementById('catNombre').value = cat.nombre;
    document.getElementById('catTipo').value = cat.tipo;
    document.getElementById('btnCategoria').textContent = 'Guardar cambios';
    document.getElementById('btnCancelarCategoria').classList.remove('oculto');
}

function cancelarEdicionCategoria() {
    catEditId = null;
    document.getElementById('catNombre').value = '';
    document.getElementById('catTipo').value = 'ingreso';
    document.getElementById('btnCategoria').textContent = 'Agregar categoría';
    document.getElementById('btnCancelarCategoria').classList.add('oculto');
}

async function crearCategoria() {
    const nombre = document.getElementById('catNombre').value.trim();
    const tipo = document.getElementById('catTipo').value;
    if (!nombre) { _toast('Ponle un nombre a la categoría', 'warning'); return; }

    if (catEditId) {
        await _api(`/api/negocios/${negocioActual}/categorias/${catEditId}`, 'PUT', { nombre, tipo });
        _toast('Categoría actualizada');
        cancelarEdicionCategoria();
    } else {
        await _api(`/api/negocios/${negocioActual}/categorias`, 'POST', { nombre, tipo });
        document.getElementById('catNombre').value = '';
        _toast('Categoría creada');
    }
    await cargarCategorias();
}

async function eliminarCategoria(id) {
    const r = await _confirm('Se eliminará la categoría.');
    if (!r.isConfirmed) return;
    await _api(`/api/negocios/${negocioActual}/categorias/${id}`, 'DELETE');
    _toast('Categoría eliminada');
    await cargarCategorias();
}

// ── Movimientos ──────────────────────────────────────────────────────────
async function cargarMovimientos() {
    const json = await _api(`/api/negocios/${negocioActual}/movimientos`);
    const movimientos = json.data || [];
    movimientosCache = movimientos;
    const tbody = document.getElementById('listaMovimientos');
    tbody.innerHTML = '';
    document.getElementById('sinMovimientos').classList.toggle('oculto', movimientos.length > 0);

    movimientos.forEach(m => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${m.fecha}</td>
            <td>${m.cuenta ? m.cuenta.nombre : '—'}</td>
            <td>${m.categoria ? m.categoria.nombre : '—'}</td>
            <td><span class="tag ${m.tipo}">${m.tipo}</span></td>
            <td>Q ${Number(m.monto).toFixed(2)}</td>
            <td>${m.descripcion || ''}</td>
            <td style="white-space:nowrap;">
                <button class="secondary" onclick="editarMovimiento(${m.id})"><i class="bi bi-pencil"></i></button>
                <button class="danger" onclick="eliminarMovimiento(${m.id})"><i class="bi bi-trash"></i></button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function editarMovimiento(id) {
    const m = movimientosCache.find(x => x.id === id);
    if (!m) return;
    movEditId = id;
    document.getElementById('movTipo').value = m.tipo;
    filtrarCategoriasPorTipo();
    document.getElementById('movCuenta').value = m.cuenta_id;
    document.getElementById('movCategoria').value = m.categoria_id || '';
    document.getElementById('movMonto').value = m.monto;
    document.getElementById('movFecha').value = m.fecha;
    document.getElementById('movDescripcion').value = m.descripcion || '';
    document.getElementById('btnMovimiento').textContent = 'Guardar cambios';
    document.getElementById('btnCancelarMovimiento').classList.remove('oculto');
}

function cancelarEdicionMovimiento() {
    movEditId = null;
    document.getElementById('movMonto').value = '';
    document.getElementById('movDescripcion').value = '';
    document.getElementById('btnMovimiento').textContent = 'Registrar';
    document.getElementById('btnCancelarMovimiento').classList.add('oculto');
}

async function crearMovimiento() {
    const cuenta_id = document.getElementById('movCuenta').value;
    const tipo = document.getElementById('movTipo').value;
    const categoria_id = document.getElementById('movCategoria').value || null;
    const monto = parseFloat(document.getElementById('movMonto').value);
    const fecha = document.getElementById('movFecha').value || new Date().toISOString().slice(0, 10);
    const descripcion = document.getElementById('movDescripcion').value.trim() || null;

    if (!cuenta_id) { _toast('Primero crea una cuenta', 'warning'); return; }
    if (!monto || monto <= 0) { _toast('Ingresa un monto válido', 'warning'); return; }

    if (movEditId) {
        await _api(`/api/negocios/${negocioActual}/movimientos/${movEditId}`, 'PUT', { cuenta_id, categoria_id, tipo, monto, fecha, descripcion });
        _toast('Movimiento actualizado');
        cancelarEdicionMovimiento();
    } else {
        await _api(`/api/negocios/${negocioActual}/movimientos`, 'POST', { cuenta_id, categoria_id, tipo, monto, fecha, descripcion });
        document.getElementById('movMonto').value = '';
        document.getElementById('movDescripcion').value = '';
        _toast('Movimiento registrado');
    }
    await cargarMovimientos();
    await cargarCuentas();
    await cargarResumen();
}

async function eliminarMovimiento(id) {
    const r = await _confirm('Se eliminará el movimiento y se recalculará el saldo de la cuenta.');
    if (!r.isConfirmed) return;
    await _api(`/api/negocios/${negocioActual}/movimientos/${id}`, 'DELETE');
    _toast('Movimiento eliminado');
    await cargarMovimientos();
    await cargarCuentas();
    await cargarResumen();
}

// ── Deudas ───────────────────────────────────────────────────────────────
async function cargarDeudas() {
    const deudas = await _api(`/api/negocios/${negocioActual}/deudas`);
    deudasCache = deudas;
    const tbody = document.getElementById('listaDeudas');
    tbody.innerHTML = '';
    document.getElementById('sinDeudas').classList.toggle('oculto', deudas.length > 0);

    deudas.forEach(d => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${d.acreedor}</td>
            <td>Q ${Number(d.monto_total).toFixed(2)}</td>
            <td>Q ${Number(d.saldo_pendiente).toFixed(2)}</td>
            <td>${d.fecha_limite || '—'}</td>
            <td>${d.saldada ? '<span class="tag ingreso">Saldada</span>' : '<span class="tag gasto">Pendiente</span>'}</td>
            <td style="white-space:nowrap;">
                ${d.saldada ? '' : `<button class="secondary" onclick="marcarDeudaSaldada(${d.id})">Marcar pagada</button>`}
                <button class="secondary" onclick="editarDeuda(${d.id})"><i class="bi bi-pencil"></i></button>
                <button class="danger" onclick="eliminarDeuda(${d.id})"><i class="bi bi-trash"></i></button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function editarDeuda(id) {
    const d = deudasCache.find(x => x.id === id);
    if (!d) return;
    deudaEditId = id;
    document.getElementById('deudaAcreedor').value = d.acreedor;
    document.getElementById('deudaMontoTotal').value = d.monto_total;
    document.getElementById('deudaSaldoPendiente').value = d.saldo_pendiente;
    document.getElementById('deudaFechaLimite').value = d.fecha_limite || '';
    document.getElementById('btnDeuda').textContent = 'Guardar cambios';
    document.getElementById('btnCancelarDeuda').classList.remove('oculto');
}

function cancelarEdicionDeuda() {
    deudaEditId = null;
    document.getElementById('deudaAcreedor').value = '';
    document.getElementById('deudaMontoTotal').value = '';
    document.getElementById('deudaSaldoPendiente').value = '';
    document.getElementById('deudaFechaLimite').value = '';
    document.getElementById('btnDeuda').textContent = 'Agregar deuda';
    document.getElementById('btnCancelarDeuda').classList.add('oculto');
}

async function crearDeuda() {
    const acreedor = document.getElementById('deudaAcreedor').value.trim();
    const monto_total = parseFloat(document.getElementById('deudaMontoTotal').value);
    const saldo_pendiente = parseFloat(document.getElementById('deudaSaldoPendiente').value);
    const fecha_limite = document.getElementById('deudaFechaLimite').value || null;

    if (!acreedor) { _toast('Ponle un nombre al acreedor', 'warning'); return; }
    if (!monto_total || !saldo_pendiente) { _toast('Ingresa monto total y saldo pendiente', 'warning'); return; }

    if (deudaEditId) {
        await _api(`/api/negocios/${negocioActual}/deudas/${deudaEditId}`, 'PUT', { acreedor, monto_total, saldo_pendiente, fecha_limite });
        _toast('Deuda actualizada');
        cancelarEdicionDeuda();
    } else {
        await _api(`/api/negocios/${negocioActual}/deudas`, 'POST', { acreedor, monto_total, saldo_pendiente, fecha_limite });
        document.getElementById('deudaAcreedor').value = '';
        document.getElementById('deudaMontoTotal').value = '';
        document.getElementById('deudaSaldoPendiente').value = '';
        _toast('Deuda registrada');
    }
    await cargarDeudas();
}

async function marcarDeudaSaldada(id) {
    await _api(`/api/negocios/${negocioActual}/deudas/${id}`, 'PUT', { saldada: true, saldo_pendiente: 0 });
    _toast('Deuda marcada como pagada');
    await cargarDeudas();
}

async function eliminarDeuda(id) {
    const r = await _confirm('Se eliminará esta deuda.');
    if (!r.isConfirmed) return;
    await _api(`/api/negocios/${negocioActual}/deudas/${id}`, 'DELETE');
    _toast('Deuda eliminada');
    await cargarDeudas();
}

cargarNegocios();
</script>
</body>
</html>
