<?php // panel.php ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor de Repartidores</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --sb-bg: #ffffff;
            --sb-border: #eeeeee;
            --body-bg: #f4f7f6;
            --text: #212529;
            --muted: #6c757d;
        }
        [data-theme="dark"] {
            --sb-bg: #1e2130;
            --sb-border: #2e3347;
            --body-bg: #141722;
            --text: #e4e6eb;
            --muted: #8b949e;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, Roboto, sans-serif;
            background: var(--body-bg);
            color: var(--text);
            transition: background 0.2s, color 0.2s;
        }
        /* ── Sidebar ── */
        #sidebar {
            width: 300px;
            min-width: 300px;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            background: var(--sb-bg);
            border-right: 1px solid var(--sb-border);
            padding: 20px;
            transition: width 0.25s ease, min-width 0.25s ease, padding 0.25s ease, opacity 0.2s;
            flex-shrink: 0;
        }
        #sidebar.collapsed {
            width: 0;
            min-width: 0;
            padding: 0;
            opacity: 0;
            pointer-events: none;
        }
        #sidebar-inner { min-width: 260px; }
        /* ── Layout ── */
        #layout { display: flex; height: 100vh; overflow: hidden; }
        #main-col { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 16px; }
        /* ── Toggle button ── */
        #sidebar-toggle {
            position: fixed;
            top: 50%;
            left: 300px;
            transform: translateY(-50%) translateX(-50%);
            z-index: 1000;
            background: var(--sb-bg);
            border: 1px solid var(--sb-border);
            border-radius: 50%;
            width: 28px; height: 28px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: left 0.25s ease;
            box-shadow: 0 2px 6px rgba(0,0,0,.15);
            font-size: 12px;
            color: var(--muted);
            padding: 0;
        }
        #sidebar-toggle.collapsed { left: 0; }
        /* ── Map ── */
        #map { flex: 1; min-height: 400px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,.1); border: 2px solid var(--sb-bg); }
        /* ── Navbar ── */
        .top-bar {
            background: var(--sb-bg);
            border-bottom: 1px solid var(--sb-border);
            border-radius: 10px;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            flex-shrink: 0;
            font-size: .875rem;
        }
        .top-bar .top-bar-actions {
            margin-left: auto;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
        }
        @media (max-width: 576px) {
            .top-bar .top-bar-title { display: none; }
            .top-bar .btn { font-size: .75rem; padding: 3px 7px; }
        }
        /* ── Cards / table ── */
        .order-card { cursor: pointer; transition: 0.2s; border-left: 5px solid #0d6efd; margin-bottom: 10px; border-radius: 8px; background: var(--sb-bg); color: var(--text); }
        .order-card:hover { transform: translateX(4px); box-shadow: 0 4px 8px rgba(0,0,0,.1); }
        .driver-row { cursor: pointer; }
        .driver-row:hover { background: rgba(13,110,253,.06) !important; }
        .search-box { margin-bottom: 16px; }
        [data-theme="dark"] .table { --bs-table-color: var(--text); --bs-table-bg: transparent; }
        [data-theme="dark"] .table-light { --bs-table-bg: #252a3a; --bs-table-color: var(--text); }
        [data-theme="dark"] .form-control { background: #252a3a; color: var(--text); border-color: var(--sb-border); }
        [data-theme="dark"] .card { background: var(--sb-bg); border-color: var(--sb-border); color: var(--text); }
        /* ── Theme button ── */
        #theme-btn { font-size: 1rem; line-height: 1; padding: 4px 8px; }
        .logo-img { height: 28px; width: 28px; object-fit: contain; }
    </style>
</head>
<body>

<script>
    if (!localStorage.getItem('admin_token') && !localStorage.getItem('api_token')) {
        window.location.href = 'login.php';
    }
    // Apply saved theme before render to avoid flash
    if (localStorage.getItem('theme') === 'dark') document.documentElement.setAttribute('data-theme','dark');
</script>

<div id="layout">

    <!-- ── Sidebar ─────────────────────────────────────────────── -->
    <div id="sidebar">
        <div id="sidebar-inner">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center gap-2">
                    <img src="assets/imgs/logo.png" class="logo-img" alt="Logo">
                    <strong>Logística</strong>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary btn-sm" onclick="abrirCambioPwdAdmin()" title="Cambiar contraseña">🔑</button>
                    <button class="btn btn-outline-danger btn-sm" onclick="logout()">Salir</button>
                </div>
            </div>

            <div class="search-box">
                <input type="text" id="global-search" class="form-control form-control-sm"
                    placeholder="🔍 Buscar pedido o repartidor..." oninput="filterData()">
            </div>

            <h6 class="text-uppercase fw-semibold mb-3" style="font-size:.75rem;letter-spacing:.06em;color:var(--muted)">Pedidos activos</h6>
            <div id="order-list">
                <div class="text-center py-4">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                </div>
            </div>

            <hr class="my-3">

            <h6 class="text-uppercase fw-semibold mb-3" style="font-size:.75rem;letter-spacing:.06em;color:var(--muted)">Estado de la flota</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Repartidor</th><th>Estado</th></tr>
                    </thead>
                    <tbody id="driver-table"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ── Toggle button ───────────────────────────────────────── -->
    <button id="sidebar-toggle" onclick="toggleSidebar()" title="Contraer panel">‹</button>

    <!-- ── Main ────────────────────────────────────────────────── -->
    <div id="main-col">

        <div class="top-bar">
            <img src="assets/imgs/logo.png" class="logo-img" alt="Logo">
            <span class="fw-bold top-bar-title">Monitor de Repartidores</span>
            <div class="top-bar-actions">
                <span id="status-tag" class="badge bg-success">Conectado</span>
                <a href="admin.php" class="btn btn-outline-secondary btn-sm">⚙️ Gestionar</a>
                <button class="btn btn-primary btn-sm" onclick="fetchData()">↺</button>
                <button id="theme-btn" class="btn btn-outline-secondary btn-sm" onclick="toggleTheme()" title="Cambiar tema">🌙</button>
            </div>
        </div>

        <div id="map"></div>
    </div>
</div>

<!-- ── Modal: cambiar contraseña admin ──────────────────────────────────── -->
<div class="modal fade" id="modalPwdAdmin" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form id="form-pwd-admin">
                <div class="modal-header">
                    <h5 class="modal-title">Cambiar mi contraseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Nueva contraseña</label>
                    <input type="password" class="form-control" id="admin-pwd-nueva" required minlength="6">
                    <label class="form-label mt-2">Confirmar contraseña</label>
                    <input type="password" class="form-control" id="admin-pwd-confirmar" required minlength="6">
                    <div id="admin-pwd-error" class="alert alert-danger mt-2 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function () {
    // ── Sidebar toggle ────────────────────────────────────────────────────────
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebar-toggle');
    let sidebarOpen = localStorage.getItem('sidebar') !== 'closed';

    function applySidebar() {
        if (sidebarOpen) {
            sidebar.classList.remove('collapsed');
            toggleBtn.classList.remove('collapsed');
            toggleBtn.textContent = '‹';
            toggleBtn.style.left = '300px';
        } else {
            sidebar.classList.add('collapsed');
            toggleBtn.classList.add('collapsed');
            toggleBtn.textContent = '›';
            toggleBtn.style.left = '0';
        }
        setTimeout(() => map.invalidateSize(), 300);
    }

    window.toggleSidebar = function () {
        sidebarOpen = !sidebarOpen;
        localStorage.setItem('sidebar', sidebarOpen ? 'open' : 'closed');
        applySidebar();
    };

    applySidebar();

    // ── Dark mode ─────────────────────────────────────────────────────────────
    function applyTheme() {
        const dark = localStorage.getItem('theme') === 'dark';
        document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
        document.getElementById('theme-btn').textContent = dark ? '☀️' : '🌙';
    }

    window.toggleTheme = function () {
        const isDark = localStorage.getItem('theme') === 'dark';
        localStorage.setItem('theme', isDark ? 'light' : 'dark');
        applyTheme();
    };

    applyTheme();

    // ── Mapa ──────────────────────────────────────────────────────────────────
    var map = L.map('map').setView([-34.6037, -58.3816], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(pos => {
            map.setView([pos.coords.latitude, pos.coords.longitude], 14);
        });
    }

    var markers = {};
    var lastData = { repartidores: [], pedidos: [] };

    // ── Datos ─────────────────────────────────────────────────────────────────
    async function fetchData() {
        const tag = document.getElementById('status-tag');
        try {
            const res = await fetch('api/get_admin_data.php');
            const text = await res.text();
            let data;
            try { data = JSON.parse(text); } catch (_) {
                tag.className = 'badge bg-danger';
                tag.title = text.substring(0, 200);
                tag.textContent = 'Error PHP';
                return;
            }
            if (data.status === 'success') {
                lastData = data;
                filterData();
                renderMap(data.repartidores);
                tag.className = 'badge bg-success';
                tag.textContent = 'Conectado';
                tag.title = '';
            } else {
                tag.className = 'badge bg-danger';
                tag.textContent = 'Error BD';
                tag.title = data.message || '';
                console.error('get_admin_data error:', data.message);
            }
        } catch (e) {
            tag.className = 'badge bg-danger';
            tag.textContent = 'Sin conexión';
            tag.title = e.message;
        }
    }

    window.filterData = function () {
        const q = document.getElementById('global-search').value.toLowerCase();
        const fRep = lastData.repartidores.filter(r => r.nombre_completo.toLowerCase().includes(q));
        const fPed = lastData.pedidos.filter(p =>
            p.cliente_nombre.toLowerCase().includes(q) || p.direccion_entrega.toLowerCase().includes(q));
        renderOrders(fPed);
        renderTable(fRep);
    };

    function renderMap(repartidores) {
        repartidores.forEach(r => {
            if (r.latitud && r.longitud) {
                if (markers[r.id_repartidor]) {
                    markers[r.id_repartidor].setLatLng([r.latitud, r.longitud]);
                    markers[r.id_repartidor].getPopup().setContent(`<b>${r.nombre_completo}</b><br>${r.estado}`);
                } else {
                    markers[r.id_repartidor] = L.marker([r.latitud, r.longitud])
                        .addTo(map)
                        .bindPopup(`<b>${r.nombre_completo}</b><br>${r.estado}`);
                }
            }
        });
    }

    function renderOrders(pedidos) {
        const list = document.getElementById('order-list');
        list.innerHTML = pedidos.map(p => `
            <div class="card order-card shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="fw-semibold small">#${p.id_pedido} ${p.cliente_nombre}</span>
                    <span class="badge bg-primary" style="font-size:.7rem">${p.estado}</span>
                </div>
                <a href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(p.direccion_entrega)}"
                   target="_blank" class="small d-block mt-1" style="color:var(--muted);word-break:break-word"
                   title="Buscar en Google Maps">📍 ${p.direccion_entrega}</a>
            </div>
        `).join('') || '<p class="text-center py-4" style="color:var(--muted)">Sin pedidos activos</p>';
    }

    function renderTable(repartidores) {
        document.getElementById('driver-table').innerHTML = repartidores.map(r => {
            const hasCoords = r.latitud && r.longitud;
            const click = hasCoords
                ? `onclick="map.setView([${r.latitud},${r.longitud}],16);if(markers[${r.id_repartidor}])markers[${r.id_repartidor}].openPopup()"`
                : '';
            const ok = r.estado === 'Disponible' || r.estado === 'libre';
            const style = hasCoords ? 'cursor:pointer' : 'opacity:.6';
            return `<tr class="driver-row" ${click} title="${hasCoords ? 'Ver en mapa' : 'Sin ubicación'}" style="${style}">
                <td><strong>${r.nombre_completo}</strong></td>
                <td><span class="badge ${ok ? 'bg-success' : 'bg-warning text-dark'}" style="font-size:.7rem">${r.estado}</span></td>
            </tr>`;
        }).join('');
    }

    window.logout = function () {
        localStorage.removeItem('admin_token');
        localStorage.removeItem('api_token');
        window.location.href = 'login.php';
    };

    window.abrirCambioPwdAdmin = function () {
        document.getElementById('admin-pwd-nueva').value = '';
        document.getElementById('admin-pwd-confirmar').value = '';
        document.getElementById('admin-pwd-error').classList.add('d-none');
        new bootstrap.Modal(document.getElementById('modalPwdAdmin')).show();
    };

    document.getElementById('form-pwd-admin').addEventListener('submit', async (e) => {
        e.preventDefault();
        const errEl = document.getElementById('admin-pwd-error');
        errEl.classList.add('d-none');
        const nueva = document.getElementById('admin-pwd-nueva').value;
        const confirmar = document.getElementById('admin-pwd-confirmar').value;
        if (nueva !== confirmar) {
            errEl.textContent = 'Las contraseñas no coinciden';
            errEl.classList.remove('d-none');
            return;
        }
        const uid = parseInt(localStorage.getItem('repartidor_id') || '0');
        const token = localStorage.getItem('admin_token') || localStorage.getItem('api_token') || '';
        try {
            const res = await fetch('api/admin_repartidores.php?action=change_password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
                body: JSON.stringify({ uid, password: nueva })
            });
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message);
            bootstrap.Modal.getInstance(document.getElementById('modalPwdAdmin')).hide();
        } catch (err) {
            errEl.textContent = err.message;
            errEl.classList.remove('d-none');
        }
    });

    window.fetchData = fetchData;
    setInterval(fetchData, 5000);
    fetchData();
})();
</script>

</body>
</html>
