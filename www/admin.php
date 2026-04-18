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
                                    <th class="ps-3">ID</th>
                                    <th>Nombre</th>
                                    <th>Usuario</th>
                                    <th>Teléfono</th>
                                    <th>Estado</th>
                                    <th>Activo</th>
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
                    <label class="form-label">Nueva contraseña</label>
                    <input type="password" class="form-control" id="pwd-nueva" required>
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

    // ── Cargar tabla ──────────────────────────────────────────────────────────
    async function cargarRepartidores() {
        try {
            const res = await fetch('api/admin_repartidores.php?action=list', { headers: apiHeaders() });
            if (res.status === 401 || res.status === 403) {
                window.location.href = 'login.php';
                return;
            }
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message);

            const rows = data.repartidores;
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
                    <td class="ps-3" style="color:var(--muted)">#${r.id_repartidor}</td>
                    <td><strong>${esc(r.nombre_completo)}</strong></td>
                    <td style="color:var(--muted)">${esc(r.username)}</td>
                    <td>${esc(r.telefono ?? '—')}</td>
                    <td><span class="badge status-badge ${estadoBadge(r.estado)}">${esc(r.estado)}</span></td>
                    <td>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch"
                                ${r.activo == 1 ? 'checked' : ''}
                                onchange="toggleActivo(${r.id_repartidor}, this)">
                        </div>
                    </td>
                    <td>
                        <button class="btn btn-outline-secondary btn-sm" onclick="abrirCambioPwd(${r.id_repartidor})" title="Cambiar contraseña">🔑</button>
                        <button class="btn btn-outline-danger btn-sm ms-1" onclick="eliminarRepartidor(${r.id_repartidor}, '${esc(r.nombre_completo)}')" title="Eliminar">🗑</button>
                    </td>
                </tr>
            `).join('');
        } catch (e) {
            console.error(e);
        }
    }

    function estadoBadge(estado) {
        return { 'Disponible':'bg-success', 'No disponible':'bg-secondary', 'En camino a recoger':'bg-warning text-dark', 'En camino a entrega':'bg-info text-dark', 'Pedido Entregado':'bg-primary' }[estado] ?? 'bg-light text-dark';
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

    // ── Toggle activo ─────────────────────────────────────────────────────────
    window.toggleActivo = async (id, cb) => {
        const activo = cb.checked ? 1 : 0;
        try {
            const res = await fetch('api/admin_repartidores.php?action=toggle', {
                method: 'POST', headers: apiHeaders(),
                body: JSON.stringify({ id_repartidor: id, activo })
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
    window.abrirCambioPwd = (id) => {
        document.getElementById('pwd-id').value = id;
        document.getElementById('pwd-nueva').value = '';
        document.getElementById('pwd-error').classList.add('d-none');
        new bootstrap.Modal(document.getElementById('modalPwd')).show();
    };

    document.getElementById('form-pwd').addEventListener('submit', async (e) => {
        e.preventDefault();
        const errEl = document.getElementById('pwd-error');
        errEl.classList.add('d-none');
        try {
            const res = await fetch('api/admin_repartidores.php?action=change_password', {
                method: 'POST', headers: apiHeaders(),
                body: JSON.stringify({
                    id_repartidor: parseInt(document.getElementById('pwd-id').value),
                    password: document.getElementById('pwd-nueva').value
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
    window.eliminarRepartidor = async (id, nombre) => {
        if (!confirm(`¿Eliminar a "${nombre}"? Esta acción no se puede deshacer.`)) return;
        try {
            const res = await fetch('api/admin_repartidores.php?action=delete', {
                method: 'POST', headers: apiHeaders(),
                body: JSON.stringify({ id_repartidor: id })
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
})();
</script>

</body>
</html>
