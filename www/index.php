<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="style.css?v=6.0" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>

<header class="admin-nav">
    <h2>Panel de Administración</h2>
    <nav>
        <a href="#" class="nav-link active" data-view="dashboard">Dashboard</a>
        <a href="#" class="nav-link" data-view="gestion">Gestión de Repartidores</a>
        <a href="repartidor.html" download>Descargar APK (reemplazar por APK real)</a>
    </nav>
</header>

<main id="main-content">
    <div id="dashboard-view" class="view active">
        <div class="main-container">
            <div id="map-container"><div id="map"></div></div>
            <div id="sidebar">
                <h3>Estadísticas</h3>
                <div id="stats-container">
                    <div class="stat-card"><h3>Total Activos</h3><p id="total-repartidores">0</p></div>
                    <div class="stat-card"><h3>Disponibles</h3><p id="repartidores-disponibles">0</p></div>
                    <div class="stat-card"><h3>En Recogida</h3><p id="repartidores-recogida">0</p></div>
                    <div class="stat-card"><h3>En Entrega</h3><p id="repartidores-entrega">0</p></div>
                    <div class="stat-card"><h3>Total Pedidos Hoy</h3><p id="total-pedidos-entregados">0</p></div>
                </div>
            </div>
        </div>
    </div>
    <div id="gestion-view" class="view">
        <div class="gestion-container">
            <div class="form-container">
                <h3>Crear Nuevo Repartidor</h3>
                <form id="crear-repartidor-form" enctype="multipart/form-data">
                    <input type="text" name="nombre_completo" placeholder="Nombre Completo" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="text" name="telefono" placeholder="Teléfono">
                    <input type="text" name="tipo_vehiculo" placeholder="Tipo de Vehículo">
                    <input type="text" name="placa_vehiculo" placeholder="Placa">
                    <label for="foto-input">Foto de Perfil (opcional):</label>
                    <input type="file" id="foto-input" name="foto" accept="image/*">
                    <button type="submit">Crear Perfil</button>
                </form>
            </div>
            <div class="list-container">
                <h3>Repartidores Registrados</h3>
                <div id="repartidores-list"></div>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const mapElement = document.getElementById('map');
        let map = L.map(mapElement).setView([4.60971, -74.08175], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(map);
        let repartidoresMarkers = {};

        const navLinks = document.querySelectorAll('.nav-link');
        const views = document.querySelectorAll('.view');

        navLinks.forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                const viewName = link.getAttribute('data-view');
                if (!viewName || link.classList.contains('active')) return;

                views.forEach(v => v.classList.remove('active'));
                document.getElementById(`${viewName}-view`).classList.add('active');
                navLinks.forEach(nl => nl.classList.remove('active'));
                link.classList.add('active');

                if (viewName === 'gestion') cargarRepartidores();
                if (viewName === 'dashboard') setTimeout(() => map.invalidateSize(), 10);
            });
        });

        async function actualizarDatosDashboard() {
            try {
                const response = await fetch('obtener_ubicaciones.php');
                const repartidores = await response.json();
                actualizarEstadisticas(repartidores);
                actualizarMapa(repartidores);
            } catch (error) {
                console.error("Error al cargar datos del dashboard:", error);
            }
        }

        function actualizarEstadisticas(repartidores) {
            document.getElementById('total-repartidores').textContent = repartidores.length;
            document.getElementById('repartidores-disponibles').textContent = repartidores.filter(r => r.estado === 'Disponible').length;
            document.getElementById('repartidores-recogida').textContent = repartidores.filter(r => r.estado === 'En camino a recoger').length;
            document.getElementById('repartidores-entrega').textContent = repartidores.filter(r => r.estado === 'En camino a entrega').length;
            const totalPedidos = repartidores.reduce((sum, rep) => sum + parseInt(rep.pedidos_entregados || 0), 0);
            document.getElementById('total-pedidos-entregados').textContent = totalPedidos;
        }

        function actualizarMapa(repartidores) {
            const currentIds = Object.keys(repartidoresMarkers);
            const newIds = repartidores.map(r => r.id_repartidor);
            currentIds.filter(id => !newIds.includes(id)).forEach(id => { map.removeLayer(repartidoresMarkers[id]); delete repartidoresMarkers[id]; });
            repartidores.forEach(rep => {
                if (rep.latitud && rep.longitud) {
                    const popupContent = `<b>${rep.nombre_completo}</b><br>Estado: ${rep.estado}<br>Entregados: ${rep.pedidos_entregados || 0}`;
                    if (repartidoresMarkers[rep.id_repartidor]) {
                        repartidoresMarkers[rep.id_repartidor].setLatLng([rep.latitud, rep.longitud]).setPopupContent(popupContent);
                    } else {
                        repartidoresMarkers[rep.id_repartidor] = L.marker([rep.latitud, rep.longitud]).addTo(map).bindPopup(popupContent);
                    }
                }
            });
        }

        const crearRepartidorForm = document.getElementById('crear-repartidor-form');
        const repartidoresList = document.getElementById('repartidores-list');

        async function cargarRepartidores() {
            try {
                const response = await fetch('gestionar_repartidores.php?action=read');
                const repartidores = await response.json();
                let listHTML = '<table><thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Activo</th><th>URL App</th></tr></thead><tbody>';
                for (const rep of repartidores) {
                    const appUrl = `repartidor.html?id=${rep.id_repartidor}`;
                    listHTML += `<tr><td>${rep.id_repartidor}</td><td>${rep.nombre_completo}</td><td>${rep.email}</td><td>${rep.activo ? 'Sí' : 'No'}</td><td><a href="${appUrl}" target="_blank">Abrir</a></td></tr>`;
                }
                listHTML += '</tbody></table>';
                repartidoresList.innerHTML = listHTML;
            } catch (error) {
                repartidoresList.innerHTML = "Error al cargar repartidores.";
            }
        }

        crearRepartidorForm.addEventListener('submit', async e => {
            e.preventDefault();
            const formData = new FormData(crearRepartidorForm);
            try {
                const response = await fetch('crear_perfil.php', { method: 'POST', body: formData });
                const result = await response.json();
                alert(result.message);
                if (result.status === 'success') {
                    crearRepartidorForm.reset();
                    cargarRepartidores();
                }
            } catch (error) {
                alert('Error al crear el perfil.');
            }
        });

        // --- Carga Inicial ---
        actualizarDatosDashboard();
        setInterval(actualizarDatosDashboard, 15000);
    });
</script>

</body>
</html>
