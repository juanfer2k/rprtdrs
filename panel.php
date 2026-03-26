<?php
// --- Archivo: www/panel.php (VERSIÓN CORREGIDA CON CDN Y SEGURIDAD) ---
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Logística</title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Leaflet CSS desde CDN -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body { 
            background-color: #f4f7f6; 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        #map { height: 600px; width: 100%; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 2px solid #fff; }
        .sidebar { height: 100vh; overflow-y: auto; background: white; border-right: 1px solid #eee; padding: 20px; }
        .order-card { cursor: pointer; transition: 0.2s; border-left: 5px solid #0d6efd; margin-bottom: 12px; border-radius: 8px; }
        .order-card:hover { transform: translateX(5px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .driver-row { cursor: pointer; transition: 0.2s; }
        .driver-row:hover { background-color: #f0f7ff !important; }
        .navbar { background: #fff; border-bottom: 1px solid #eee; margin-bottom: 20px; }
        .search-box { margin-bottom: 20px; }
    </style>
</head>
<body>

<script>
    if (!localStorage.getItem('admin_token')) {
        window.location.href = 'login.php';
    }
</script>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 sidebar">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="m-0">📦 Logística</h4>
                <button class="btn btn-outline-danger btn-sm" onclick="logout()">Salir</button>
            </div>
            
            <div class="search-box">
                <input type="text" id="global-search" class="form-control" placeholder="🔍 Buscar pedido o repartidor..." oninput="filterData()">
            </div>

            <h5>📋 Pedidos Activos</h5>
            <div id="order-list">
                <div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div></div>
            </div>
            
            <hr class="my-4">
            
            <h5>🚚 Estado de la Flota</h5>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Repartidor</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="driver-table"></tbody>
                </table>
            </div>
        </div>

        <!-- Main -->
        <div class="col-md-9 p-4">
            <nav class="navbar navbar-expand-lg px-3 rounded shadow-sm">
                <span class="navbar-brand mb-0 h1">🛰️ Monitor de Repartidores</span>
                <div class="ms-auto">
                    <span id="status-tag" class="badge bg-success">Conectado</span>
                    <button class="btn btn-primary btn-sm ms-3" onclick="fetchData()">🔄 Refrescar</button>
                </div>
            </nav>
            
            <div id="map"></div>
        </div>
    </div>
</div>

<!-- Leaflet JS desde CDN -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
    var map = L.map('map').setView([-34.6037, -58.3816], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    // Centrar en ubicación del usuario
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(pos => {
            map.setView([pos.coords.latitude, pos.coords.longitude], 14);
        });
    }

    var markers = {};
    var lastData = { repartidores: [], pedidos: [] };

    async function fetchData() {
        try {
            const res = await fetch('api/get_admin_data.php');
            const data = await res.json();
            if (data.status === "success") {
                lastData = data;
                filterData();
                renderMap(data.repartidores);
            }
        } catch (e) { console.error(e); }
    }

    function filterData() {
        const query = document.getElementById('global-search').value.toLowerCase();
        const fRep = lastData.repartidores.filter(r => r.nombre_completo.toLowerCase().includes(query));
        const fPed = lastData.pedidos.filter(p => p.cliente_nombre.toLowerCase().includes(query) || p.direccion_entrega.toLowerCase().includes(query));
        renderOrders(fPed);
        renderTable(fRep);
    }

    function renderMap(repartidores) {
        repartidores.forEach(r => {
            if (r.latitud && r.longitud) {
                if (markers[r.id_repartidor]) {
                    markers[r.id_repartidor].setLatLng([r.latitud, r.longitud]);
                } else {
                    markers[r.id_repartidor] = L.marker([r.latitud, r.longitud]).addTo(map)
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
                    <h6 class="mb-1">#${p.id_pedido} ${p.cliente_nombre}</h6>
                    <span class="badge bg-primary">${p.estado}</span>
                </div>
                <p class="small text-muted mb-0">${p.direccion_entrega}</p>
            </div>
        `).join('') || '<p class="text-center py-5">Sin pedidos activos</p>';
    }

    function renderTable(repartidores) {
        document.getElementById('driver-table').innerHTML = repartidores.map(r => {
            const hasCoords = r.latitud && r.longitud;
            const clickAction = hasCoords ? `onclick="map.setView([${r.latitud},${r.longitud}], 16)"` : '';
            const isAvailable = r.estado === 'Disponible' || r.estado === 'libre';
            return `
                <tr class="driver-row" ${clickAction} title="Click para ver en mapa">
                    <td><strong>${r.nombre_completo}</strong></td>
                    <td><span class="badge ${isAvailable ? 'bg-success' : 'bg-warning'}">${r.estado}</span></td>
                </tr>
            `;
        }).join('');
    }

    function logout() {
        localStorage.removeItem('admin_token');
        window.location.href = 'login.php';
    }

    setInterval(fetchData, 5000);
    fetchData();
</script>

</body>
</html>
