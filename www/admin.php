<?php // admin.php ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Repartidores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f4f7f6;
            --card-bg: #ffffff;
            --border: #eeeeee;
            --text: #212529;
            --muted: #6c757d;
        }
        [data-theme="dark"] {
            --bg: #141722;
            --card-bg: #1e2130;
            --border: #2e3347;
            --text: #e4e6eb;
            --muted: #8b949e;
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); transition: background .2s, color .2s; }
        .top-nav { background: var(--card-bg); border-bottom: 1px solid var(--border); padding: 10px 20px; display: flex; align-items: center; gap: 12px; }
        .card { background: var(--card-bg); border-color: var(--border); color: var(--text); }
        .card-header { background: var(--card-bg) !important; border-color: var(--border); }
        .table th { font-size: .78rem; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); }
        .table th.sortable { cursor: pointer; user-select: none; white-space: nowrap; }
        .table th.sortable:hover { color: var(--text); }
        .sort-icon { margin-left: 4px; opacity: .4; font-style: normal; }
        .table th.asc  .sort-icon { opacity: 1; content: '↑'; }
        .table th.desc .sort-icon { opacity: 1; }
        [data-theme="dark"] .table { --bs-table-color: var(--text); --bs-table-bg: transparent; }
        [data-theme="dark"] .table-light { --bs-table-bg: #252a3a; --bs-table-color: var(--text); }
        [data-theme="dark"] .table-hover > tbody > tr:hover > * { --bs-table-bg-state: rgba(255,255,255,.05); }
        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select { background: #252a3a; color: var(--text); border-color: var(--border); }
        [data-theme="dark"] .modal-content { background: var(--card-bg); color: var(--text); border-color: var(--border); }
        [data-theme="dark"] .modal-header,
        [data-theme="dark"] .modal-footer { border-color: var(--border); }
        #toast-container { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999; display: flex; flex-direction: column; gap: 8px; }
        .logo-img { height: 28px; width: 28px; object-fit: contain; }
        .status-badge { font-size: .72rem; }
    </style>
</head>
<body>

<script>
    // Apply theme before render to avoid flash
    if (localStorage.getItem('theme') === 'dark') document.documentElement.setAttribute('data-theme', 'dark');

    // Use api_token (always set on login) with fallback to admin_token for legacy sessions
    const _tok = localStorage.getItem('api_token') || localStorage.getItem('admin_token');
    if (!_tok) window.location.href = 'login.php';
</script>

<!-- ── Navbar ─────────────────────────────────────────────────────────────── -->
<nav class="top-nav shadow-sm mb-4">
    <img src="assets/imgs/logo.png" class="logo-img" alt="Logo">
    <strong class="me-2">Repartidores</strong>
    <span style="color:var(--muted);font-size:.85rem">/ Gestión</span>
    <div class="ms-auto d-flex align-items-center gap-2">
        <a href="panel.php" class="btn btn-outline-primary btn-sm">🗺 Monitor</a>
        <button id="theme-btn" class="btn btn-outline-secondary btn-sm" onclick="toggleTheme()" title="Cambiar tema">🌙</button>
        <button class="btn btn-outline-danger btn-sm" onclick="logout()">Salir</button>
    </div>
</nav>

<!-- ── Content ────────────────────────────────────────────────────────────── -->
<div class="container-fluid px-4">
    <div class="row g-4">

        <!-- Tabla -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0">🚚 Repartidores</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevo">
                        + Nuevo
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 sortable" data-col="id_repartidor">ID <em class="sort-icon">↕</em></th>
                                    <th class="sortable" data-col="nombre_completo">Nombre <em class="sort-icon">↕</em></th>
                                    <th class="sortable" data-col="username">Usuario <em class="sort-icon">↕</em></th>
                                    <th class="sortable" data-col="telefono">Teléfono <em class="sort-icon">↕</em></th>
                                    <th class="sortable" data-col="estado">Estado <em class="sort-icon">↕</em></th>
                                    <th class="sortable" data-col="activo">Activo <em class="sort-icon">↕</em></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="tabla-repartidores">
                                <tr><td colspan="7" class="text-center py-5">
                                    <div class="spinner-border spinner-border-sm text-primary"></div>
                                </td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumen -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header py-3"><h5 class="mb-0">📊 Resumen</h5></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span style="color:var(--muted)">Total registrados</span>
                        <strong id="stat-total">—</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span style="color:var(--muted)">Activos</span>
                        <strong id="stat-activos" class="text-success">—</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span style="color:var(--muted)">Disponibles ahora</span>
                        <strong id="stat-disponibles" class="text-primary">—</strong>
                    </div>
                    <hr style="border-color:var(--border)">
                    <p class="small mb-0" style="color:var(--muted)">Actualización automática cada 10 s.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Pedidos ────────────────────────────────────────────────────────────── -->
<div class="container-fluid px-4 mt-2 mb-5">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0">📦 Pedidos activos</h5>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoPedido">+ Nuevo pedido</button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Cliente</th>
                            <th>Dirección</th>
                            <th>Estado</th>
                            <th>Repartidor</th>
                            <th>Fecha</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="tabla-pedidos">
                        <tr><td colspan="7" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal: nuevo pedido ────────────────────────────────────────────────── -->
<div class="modal fade" id="modalNuevoPedido" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-nuevo-pedido">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Cliente <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="ped-cliente" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dirección de entrega <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="ped-direccion" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Asignar repartidor (opcional)</label>
                        <select class="form-select" id="ped-repartidor">
                            <option value="">— Sin asignar —</option>
                        </select>
                    </div>
                    <div id="ped-nuevo-error" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear pedido</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal: editar pedido ───────────────────────────────────────────────── -->
<div class="modal fade" id="modalEditPedido" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-edit-pedido">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit-ped-id">
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select class="form-select" id="edit-ped-estado">
                            <option value="pendiente">Pendiente</option>
                            <option value="en camino">En camino</option>
                            <option value="entregado">Entregado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Repartidor asignado</label>
                        <select class="form-select" id="edit-ped-repartidor">
                            <option value="">— Sin asignar —</option>
                        </select>
                    </div>
                    <div id="ped-edit-error" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal: nuevo repartidor ────────────────────────────────────────────── -->
<div class="modal fade" id="modalNuevo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-nuevo" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Repartidor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="inp-nombre" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Usuario (login) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="inp-username" required autocomplete="off">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="inp-password" required autocomplete="new-password">
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-6">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="inp-telefono">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="inp-email">
                        </div>
                    </div>
                    <div id="form-error" class="alert alert-danger mt-3 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn-guardar">Crear</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal: editar perfil completo ─────────────────────────────────────── -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-edit" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">✏️ Editar Repartidor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit-uid">
                    <div class="mb-3">
                        <label class="form-label">Nombre completo</label>
                        <input type="text" class="form-control" id="edit-nombre">
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Usuario (login)</label>
                            <input type="text" class="form-control" id="edit-username" autocomplete="off">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="edit-telefono">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit-email">
                    </div>
                    <div id="edit-error" class="alert alert-danger mt-3 d-none"></div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </div>
                    <button type="button" class="btn btn-outline-warning btn-sm" id="btn-cambiar-pwd-desde-edit">🔑 Cambiar contraseña</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal: cambiar contraseña ─────────────────────────────────────────── -->
<div class="modal fade" id="modalPwd" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form id="form-pwd">
                <div class="modal-header">
                    <h5 class="modal-title">Cambiar Contraseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="pwd-id">
                    <input type="hidden" id="pwd-uid">
                    <label class="form-label">Nueva contraseña</label>
                    <input type="password" class="form-control" id="pwd-nueva" required minlength="6">
                    <label class="form-label mt-2">Confirmar contraseña</label>
                    <input type="password" class="form-control" id="pwd-confirmar" required minlength="6">
                    <div id="pwd-error" class="alert alert-danger mt-2 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="toast-container"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const token = localStorage.getItem('api_token') || localStorage.getItem('admin_token');

    function apiHeaders() {
        return { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token };
    }

    // ── Theme ─────────────────────────────────────────────────────────────────
    function applyTheme() {
        const dark = localStorage.getItem('theme') === 'dark';
        document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
        document.getElementById('theme-btn').textContent = dark ? '☀️' : '🌙';
    }
    window.toggleTheme = function () {
        localStorage.setItem('theme', localStorage.getItem('theme') === 'dark' ? 'light' : 'dark');
        applyTheme();
    };
    applyTheme();

    // ── Toast ─────────────────────────────────────────────────────────────────
    function toast(msg, ok = true) {
        const el = document.createElement('div');
        el.className = `alert alert-${ok ? 'success' : 'danger'} py-2 px-3 shadow mb-0`;
        el.textContent = msg;
        document.getElementById('toast-container').appendChild(el);
        setTimeout(() => el.remove(), 3500);
    }

    // ── Estado de sort ────────────────────────────────────────────────────────
    let allRows = [];
    let sortCol = 'id_repartidor';
    let sortDir = 'asc';

    // ── Cargar tabla ──────────────────────────────────────────────────────────
    async function cargarRepartidores() {
        try {
            const res = await fetch('api/admin_repartidores.php?action=list', { headers: apiHeaders() });
            if (res.status === 401 || res.status === 403) { window.location.href = 'login.php'; return; }
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message);
            allRows = data.repartidores;
            window._repartidores = data.repartidores;
            renderTabla();
        } catch (e) { console.error(e); }
    }

    function renderTabla() {
        const rows = [...allRows].sort((a, b) => {
            let va = a[sortCol] ?? '';
            let vb = b[sortCol] ?? '';
            if (sortCol === 'id_repartidor' || sortCol === 'activo') {
                va = Number(va); vb = Number(vb);
            } else {
                va = String(va).toLowerCase(); vb = String(vb).toLowerCase();
            }
            return sortDir === 'asc' ? (va > vb ? 1 : va < vb ? -1 : 0)
                                     : (va < vb ? 1 : va > vb ? -1 : 0);
        });

        document.getElementById('stat-total').textContent = rows.length;
        document.getElementById('stat-activos').textContent = rows.filter(r => r.activo == 1).length;
        document.getElementById('stat-disponibles').textContent = rows.filter(r => r.estado === 'Disponible').length;

        const tbody = document.getElementById('tabla-repartidores');
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4" style="color:var(--muted)">Sin repartidores registrados.</td></tr>';
            return;
        }
        tbody.innerHTML = rows.map(r => `
            <tr>
                <td class="ps-3" style="color:var(--muted)">#${r.uid || r.id_repartidor}</td>
                <td><strong>${esc(r.nombre_completo)}</strong></td>
                <td style="color:var(--muted)">${r.username ? esc(r.username) : '<em style="opacity:.4">sin usuario</em>'}</td>
                <td>${esc(r.telefono) || '—'}</td>
                <td><span class="badge status-badge ${estadoBadge(r.estado)}">${esc(r.estado)}</span></td>
                <td>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch"
                            ${r.activo == 1 ? 'checked' : ''}
                            onchange="toggleActivo(${r.id_repartidor}, ${r.uid || 0}, this)">
                    </div>
                </td>
                <td class="text-nowrap">
                    <button class="btn btn-outline-primary btn-sm"    onclick="abrirEdit(${r.uid})"                                            title="Editar perfil">✏️</button>
                    <button class="btn btn-outline-secondary btn-sm ms-1" onclick="abrirCambioPwd(${r.uid}, ${r.uid})"                         title="Cambiar contraseña">🔑</button>
                    <button class="btn btn-outline-danger btn-sm ms-1"    onclick="eliminarRepartidor(${r.uid}, ${r.uid}, '${esc(r.nombre_completo)}')" title="Eliminar">🗑</button>
                </td>
            </tr>
        `).join('');

        // Actualizar íconos de sort en headers
        document.querySelectorAll('th.sortable').forEach(th => {
            const icon = th.querySelector('.sort-icon');
            th.classList.remove('asc','desc');
            if (th.dataset.col === sortCol) {
                th.classList.add(sortDir);
                icon.textContent = sortDir === 'asc' ? '↑' : '↓';
            } else {
                icon.textContent = '↕';
            }
        });
    }

    // Listeners de sort
    document.querySelectorAll('th.sortable').forEach(th => {
        th.addEventListener('click', () => {
            if (sortCol === th.dataset.col) {
                sortDir = sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                sortCol = th.dataset.col;
                sortDir = 'asc';
            }
            renderTabla();
        });
    });

    function estadoBadge(estado) {
        const m = { 'Disponible':'bg-success', 'No disponible':'bg-secondary',
                    'En camino a recoger':'bg-warning text-dark',
                    'En camino a entrega':'bg-info text-dark', 'Pedido Entregado':'bg-primary' };
        return m[estado] || 'bg-light text-dark';
    }

    function esc(str) {
        return str == null ? '' : String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Crear ─────────────────────────────────────────────────────────────────
    document.getElementById('form-nuevo').addEventListener('submit', async (e) => {
        e.preventDefault();
        const errEl = document.getElementById('form-error');
        errEl.classList.add('d-none');
        const btn = document.getElementById('btn-guardar');
        btn.disabled = true; btn.textContent = 'Guardando...';

        try {
            const res = await fetch('api/admin_repartidores.php?action=create', {
                method: 'POST', headers: apiHeaders(),
                body: JSON.stringify({
                    nombre_completo: document.getElementById('inp-nombre').value.trim(),
                    username:        document.getElementById('inp-username').value.trim(),
                    password:        document.getElementById('inp-password').value,
                    telefono:        document.getElementById('inp-telefono').value.trim(),
                    email:           document.getElementById('inp-email').value.trim(),
                })
            });
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message);
            bootstrap.Modal.getInstance(document.getElementById('modalNuevo')).hide();
            document.getElementById('form-nuevo').reset();
            toast('Repartidor creado');
            cargarRepartidores();
        } catch (err) {
            errEl.textContent = err.message;
            errEl.classList.remove('d-none');
        } finally {
            btn.disabled = false; btn.textContent = 'Crear';
        }
    });

    // ── Editar perfil ─────────────────────────────────────────────────────────
    window.abrirEdit = (uid) => {
        const r = allRows.find(x => x.uid == uid);
        if (!r) return;
        document.getElementById('edit-uid').value      = uid;
        document.getElementById('edit-nombre').value   = r.nombre_completo || '';
        document.getElementById('edit-username').value = r.username || '';
        document.getElementById('edit-telefono').value = r.telefono || '';
        document.getElementById('edit-email').value    = r.email || '';
        document.getElementById('edit-error').classList.add('d-none');
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    };

    document.getElementById('form-edit').addEventListener('submit', async (e) => {
        e.preventDefault();
        const errEl = document.getElementById('edit-error');
        errEl.classList.add('d-none');
        const uid = parseInt(document.getElementById('edit-uid').value);
        try {
            const res = await fetch('api/admin_repartidores.php?action=update_user', {
                method: 'POST', headers: apiHeaders(),
                body: JSON.stringify({
                    uid,
                    nombre_completo: document.getElementById('edit-nombre').value.trim(),
                    username:        document.getElementById('edit-username').value.trim(),
                    telefono:        document.getElementById('edit-telefono').value.trim(),
                    email:           document.getElementById('edit-email').value.trim(),
                })
            });
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message);
            bootstrap.Modal.getInstance(document.getElementById('modalEdit')).hide();
            toast('Perfil actualizado');
            cargarRepartidores();
        } catch (err) {
            errEl.textContent = err.message;
            errEl.classList.remove('d-none');
        }
    });

    document.getElementById('btn-cambiar-pwd-desde-edit').addEventListener('click', () => {
        const uid = parseInt(document.getElementById('edit-uid').value);
        bootstrap.Modal.getInstance(document.getElementById('modalEdit')).hide();
        setTimeout(() => abrirCambioPwd(uid, uid), 300);
    });

    // ── Toggle activo ─────────────────────────────────────────────────────────
    window.toggleActivo = async (id, uid, cb) => {
        const activo = cb.checked ? 1 : 0;
        try {
            const res = await fetch('api/admin_repartidores.php?action=toggle', {
                method: 'POST', headers: apiHeaders(),
                body: JSON.stringify({ id_repartidor: id, uid: uid, activo })
            });
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message);
            toast(activo ? 'Repartidor activado' : 'Repartidor desactivado');
        } catch (err) {
            cb.checked = !cb.checked;
            toast(err.message, false);
        }
    };

    // ── Cambiar pwd ───────────────────────────────────────────────────────────
    window.abrirCambioPwd = (id, uid) => {
        document.getElementById('pwd-id').value = id;
        document.getElementById('pwd-uid').value = uid;
        document.getElementById('pwd-nueva').value = '';
        document.getElementById('pwd-confirmar').value = '';
        document.getElementById('pwd-error').classList.add('d-none');
        new bootstrap.Modal(document.getElementById('modalPwd')).show();
    };

    document.getElementById('form-pwd').addEventListener('submit', async (e) => {
        e.preventDefault();
        const errEl = document.getElementById('pwd-error');
        errEl.classList.add('d-none');
        const nueva = document.getElementById('pwd-nueva').value;
        const confirmar = document.getElementById('pwd-confirmar').value;
        if (nueva !== confirmar) {
            errEl.textContent = 'Las contraseñas no coinciden';
            errEl.classList.remove('d-none');
            return;
        }
        try {
            const res = await fetch('api/admin_repartidores.php?action=change_password', {
                method: 'POST', headers: apiHeaders(),
                body: JSON.stringify({
                    id_repartidor: parseInt(document.getElementById('pwd-id').value),
                    uid:           parseInt(document.getElementById('pwd-uid').value),
                    password:      nueva
                })
            });
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message);
            bootstrap.Modal.getInstance(document.getElementById('modalPwd')).hide();
            toast('Contraseña actualizada');
        } catch (err) {
            errEl.textContent = err.message;
            errEl.classList.remove('d-none');
        }
    });

    // ── Eliminar ──────────────────────────────────────────────────────────────
    window.eliminarRepartidor = async (id, uid, nombre) => {
        if (!confirm(`¿Eliminar a "${nombre}"? Esta acción no se puede deshacer.`)) return;
        try {
            const res = await fetch('api/admin_repartidores.php?action=delete', {
                method: 'POST', headers: apiHeaders(),
                body: JSON.stringify({ id_repartidor: id, uid: uid })
            });
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message);
            toast('Repartidor eliminado');
            cargarRepartidores();
        } catch (err) { toast(err.message, false); }
    };

    // ── Logout ────────────────────────────────────────────────────────────────
    window.logout = () => {
        localStorage.removeItem('admin_token');
        localStorage.removeItem('api_token');
        window.location.href = 'login.php';
    };

    cargarRepartidores();
    setInterval(cargarRepartidores, 10000);

    // ── Pedidos ───────────────────────────────────────────────────────────────
    const ESTADOS_BADGE = {
        'pendiente':  'warning',
        'en camino':  'primary',
        'entregado':  'success',
        'cancelado':  'secondary',
    };

    async function cargarPedidos() {
        const res  = await fetch('api/pedidos.php?action=todos', { headers: apiHeaders() });
        const data = await res.json();
        if (data.status !== 'success') return;
        const tbody = document.getElementById('tabla-pedidos');
        if (!data.pedidos.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">Sin pedidos activos</td></tr>';
            return;
        }
        tbody.innerHTML = data.pedidos.map(p => `
            <tr>
                <td class="ps-3">${p.id_pedido}</td>
                <td>${esc(p.cliente_nombre)}</td>
                <td>${esc(p.direccion_entrega)}</td>
                <td><span class="badge bg-${ESTADOS_BADGE[p.estado]||'secondary'}">${esc(p.estado)}</span></td>
                <td>${esc(p.repartidor_nombre || '—')}</td>
                <td style="font-size:.8rem">${p.fecha_creacion ? p.fecha_creacion.slice(0,16) : ''}</td>
                <td class="text-nowrap">
                    <button class="btn btn-outline-primary btn-sm" onclick="abrirEditPedido(${p.id_pedido},'${esc(p.estado)}',${p.id_repartidor||0})" title="Editar">✏️</button>
                    <button class="btn btn-outline-danger btn-sm ms-1" onclick="eliminarPedido(${p.id_pedido},'${esc(p.cliente_nombre)}')" title="Eliminar">🗑</button>
                </td>
            </tr>`).join('');
    }

    function poblarSelectsRepartidores(selectId) {
        const sel = document.getElementById(selectId);
        const currentVal = sel.value;
        sel.innerHTML = '<option value="">— Sin asignar —</option>';
        (window._repartidores || []).forEach(r => {
            if (!r.activo) return;
            const opt = document.createElement('option');
            opt.value = r.uid;
            opt.textContent = r.nombre_completo || r.username;
            sel.appendChild(opt);
        });
        sel.value = currentVal;
    }

    document.getElementById('modalNuevoPedido').addEventListener('show.bs.modal', () => {
        poblarSelectsRepartidores('ped-repartidor');
        document.getElementById('ped-cliente').value = '';
        document.getElementById('ped-direccion').value = '';
        document.getElementById('ped-repartidor').value = '';
        document.getElementById('ped-nuevo-error').classList.add('d-none');
    });

    document.getElementById('form-nuevo-pedido').addEventListener('submit', async (e) => {
        e.preventDefault();
        const errEl = document.getElementById('ped-nuevo-error');
        errEl.classList.add('d-none');
        try {
            const res = await fetch('api/pedidos.php?action=crear', {
                method: 'POST', headers: apiHeaders(),
                body: JSON.stringify({
                    cliente_nombre:    document.getElementById('ped-cliente').value.trim(),
                    direccion_entrega: document.getElementById('ped-direccion').value.trim(),
                    id_repartidor:     document.getElementById('ped-repartidor').value || null,
                })
            });
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message);
            bootstrap.Modal.getInstance(document.getElementById('modalNuevoPedido')).hide();
            toast('Pedido creado');
            cargarPedidos();
        } catch(err) {
            errEl.textContent = err.message;
            errEl.classList.remove('d-none');
        }
    });

    window.abrirEditPedido = (id, estado, idRep) => {
        document.getElementById('edit-ped-id').value = id;
        document.getElementById('edit-ped-estado').value = estado;
        poblarSelectsRepartidores('edit-ped-repartidor');
        document.getElementById('edit-ped-repartidor').value = idRep || '';
        document.getElementById('ped-edit-error').classList.add('d-none');
        new bootstrap.Modal(document.getElementById('modalEditPedido')).show();
    };

    document.getElementById('form-edit-pedido').addEventListener('submit', async (e) => {
        e.preventDefault();
        const errEl = document.getElementById('ped-edit-error');
        errEl.classList.add('d-none');
        const id = parseInt(document.getElementById('edit-ped-id').value);
        try {
            const [r1, r2] = await Promise.all([
                fetch('api/pedidos.php?action=update_estado', {
                    method: 'POST', headers: apiHeaders(),
                    body: JSON.stringify({ id_pedido: id, estado: document.getElementById('edit-ped-estado').value })
                }),
                fetch('api/pedidos.php?action=asignar', {
                    method: 'POST', headers: apiHeaders(),
                    body: JSON.stringify({ id_pedido: id, id_repartidor: document.getElementById('edit-ped-repartidor').value || 0 })
                }),
            ]);
            const d1 = await r1.json();
            if (d1.status !== 'success') throw new Error(d1.message);
            bootstrap.Modal.getInstance(document.getElementById('modalEditPedido')).hide();
            toast('Pedido actualizado');
            cargarPedidos();
        } catch(err) {
            errEl.textContent = err.message;
            errEl.classList.remove('d-none');
        }
    });

    window.eliminarPedido = async (id, cliente) => {
        if (!confirm(`¿Eliminar pedido de "${cliente}"?`)) return;
        const res  = await fetch('api/pedidos.php?action=eliminar', {
            method: 'POST', headers: apiHeaders(),
            body: JSON.stringify({ id_pedido: id })
        });
        const data = await res.json();
        if (data.status === 'success') { toast('Pedido eliminado'); cargarPedidos(); }
        else toast(data.message, false);
    };

    cargarPedidos();
    setInterval(cargarPedidos, 15000);
})();
</script>

</body>
</html>
