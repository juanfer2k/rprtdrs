<?php // panel.php ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Monitor de Repartidores</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            font-family: 'Inter', sans-serif;
            background: var(--body-bg);
            color: var(--text);
            transition: background 0.2s, color 0.2s;
        }
        /* ── Sidebar ── */
        #sidebar {
            width: 300px;
            min-width: 300px;
            height: 100vh;
            height: 100dvh;
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
        #layout { display: flex; height: 100vh; height: 100dvh; overflow: hidden; }
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
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
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
        /* ── Backdrop del drawer móvil ── */
        #sidebar-backdrop {
            position: fixed; inset: 0; background: rgba(0,0,0,.45);
            z-index: 1040; opacity: 0; pointer-events: none; transition: opacity .25s;
        }
        #sidebar-backdrop.show { opacity: 1; pointer-events: auto; }
        #menu-btn { display: none; }
        /* ── Móvil / tablet ── */
        @media (max-width: 991px) {
            #menu-btn { display: inline-flex; }
            #sidebar-toggle { display: none !important; }
            #sidebar {
                position: fixed; top: 0; left: 0; bottom: 0;
                width: min(85vw, 320px); min-width: 0;
                z-index: 1050;
                transform: translateX(-100%);
                transition: transform .25s ease;
                opacity: 1 !important; pointer-events: auto !important;
                padding: 20px !important;
                padding-top: calc(20px + env(safe-area-inset-top)) !important;
                box-shadow: 4px 0 24px rgba(0,0,0,.3);
            }
            #sidebar.open { transform: translateX(0); }
            #main-col { padding: 10px; gap: 10px; }
            #main-col { padding-top: calc(10px + env(safe-area-inset-top)); }
            .top-bar { flex-wrap: wrap; row-gap: 8px; padding: 8px 12px; }
            #map { min-height: 0; border-radius: 10px; }
        }
        @media (max-width: 480px) {
            .top-bar .fw-bold { font-size: .9rem; }
        }
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
                <button class="btn btn-outline-danger btn-sm" onclick="logout()">Salir</button>
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

    <!-- ── Toggle button (desktop) ─────────────────────────────── -->
    <button id="sidebar-toggle" onclick="toggleSidebar()" title="Contraer panel">‹</button>
    <!-- ── Backdrop (móvil) ────────────────────────────────────── -->
    <div id="sidebar-backdrop" onclick="toggleSidebar()"></div>

    <!-- ── Main ────────────────────────────────────────────────── -->
    <div id="main-col">

        <div class="top-bar">
            <button id="menu-btn" class="btn btn-outline-secondary btn-sm" onclick="toggleSidebar()" title="Abrir panel">☰</button>
            <img src="assets/imgs/logo.png" class="logo-img" alt="Logo">
            <span class="fw-bold">Monitor de Repartidores</span>
            <div class="ms-auto d-flex align-items-center gap-2">
                <span id="status-tag" class="badge bg-success">Conectado</span>
                <a href="admin.php" class="btn btn-outline-secondary btn-sm">⚙️ Gestionar</a>
                <button class="btn btn-primary btn-sm" onclick="fetchData()">↺ Refrescar</button>
                <button id="theme-btn" class="btn btn-outline-secondary btn-sm" onclick="toggleTheme()" title="Cambiar tema">🌙</button>
            </div>
        </div>

        <div id="map"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function () {
    // ── Sidebar toggle ────────────────────────────────────────────────────────
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebar-toggle');
    const backdrop = document.getElementById('sidebar-backdrop');
    const mq = window.matchMedia('(max-width: 991px)');
    let sidebarOpen = mq.matches ? false : (localStorage.getItem('sidebar') !== 'closed');

    function applySidebar() {
        const mobile = mq.matches;
        // Drawer móvil
        sidebar.classList.toggle('open', mobile && sidebarOpen);
        backdrop.classList.toggle('show', mobile && sidebarOpen);
        // Colapso desktop
        sidebar.classList.toggle('collapsed', !mobile && !sidebarOpen);
        if (!mobile) {
            toggleBtn.classList.toggle('collapsed', !sidebarOpen);
            toggleBtn.textContent = sidebarOpen ? '‹' : '›';
            toggleBtn.style.left = sidebarOpen ? '300px' : '0';
        }
        setTimeout(() => map.invalidateSize(), 300);
    }

    window.toggleSidebar = function () {
        sidebarOpen = !sidebarOpen;
        if (!mq.matches) localStorage.setItem('sidebar', sidebarOpen ? 'open' : 'closed');
        applySidebar();
    };

    mq.addEventListener('change', function () {
        sidebarOpen = mq.matches ? false : (localStorage.getItem('sidebar') !== 'closed');
        applySidebar();
    });

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
    var map = L.map('map').setView([3.68472, -76.31361], 14);
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
                <p class="small mb-0" style="color:var(--muted)">${p.direccion_entrega}</p>
            </div>
        `).join('') || '<p class="text-center py-4" style="color:var(--muted)">Sin pedidos activos</p>';
    }

    function renderTable(repartidores) {
        document.getElementById('driver-table').innerHTML = repartidores.map(r => {
            const hasCoords = r.latitud && r.longitud;
            const click = hasCoords ? `onclick="map.setView([${r.latitud},${r.longitud}],16); if (window.matchMedia('(max-width: 991px)').matches) toggleSidebar();"` : '';
            const ok = r.estado === 'Disponible' || r.estado === 'libre';
            return `<tr class="driver-row" ${click} title="Ver en mapa">
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

    window.fetchData = fetchData;
    setInterval(fetchData, 5000);
    fetchData();
})();
</script>

</body>
</html>
