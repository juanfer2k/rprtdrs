<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Carga limpia de Estilos y Leaflet desde el CDN -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="style.css?v=1.3" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body class="admin-view">

    <header class="admin-nav">
        <h2>Panel de Administración</h2>
        <nav>
            <a href="#" class="nav-link active" data-view="dashboard">Dashboard</a>
            <a href="#" class="nav-link" data-view="gestion">Gestión de Repartidores</a>
            <a href="repartidor.html" target="_blank">Abrir App Repartidor</a>
        </nav>
    </header>

    <main id="main-content">
        <!-- VISTA DASHBOARD -->
        <div id="dashboard-view" class="view active">
            <div id="map-container">
                <div id="map"></div>
            </div>
            <div id="sidebar">
                <h3>Estado en Tiempo Real</h3>
                <div id="repartidores-list-sidebar"></div>
                <hr>
                <h3>Estadísticas</h3>
                <div id="stats-container">
                    <div class="stat-card"><h4>Total Activos</h4><p id="total-repartidores">0</p></div>
                    <div class="stat-card"><h4>Disponibles</h4><p id="repartidores-disponibles">0</p></div>
                    <div class="stat-card"><h4>En Recogida</h4><p id="repartidores-recogida">0</p></div>
                    <div class="stat-card"><h4>En Entrega</h4><p id="repartidores-entrega">0</p></div>
                </div>
            </div>
        </div>

        <!-- VISTA GESTIÓN DE REPARTIDORES -->
        <div id="gestion-view" class="view">
            <div class="form-container">
                <h3>Crear Nuevo Repartidor</h3>
                <form id="crear-repartidor-form" enctype="multipart/form-data">
                    <input type="text" name="nombre_completo" placeholder="Nombre Completo" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="text" name="telefono" placeholder="Teléfono">
                    <label for="foto-input" style="display:block; margin-top:10px;">Foto (opcional):</label>
                    <input type="file" id="foto-input" name="foto" accept="image/*">
                    <button type="submit">Crear Perfil</button>
                </form>
            </div>
            <div class="list-container">
                <h3>Repartidores Registrados</h3>
                <div id="repartidores-list-gestion"></div>
            </div>
        </div>
    </main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- ENDPOINTS ---
        const DASHBOARD_ENDPOINT = 'obtener_ubicaciones.php';
        const GESTION_READ_ENDPOINT = 'gestionar_repartidores.php?action=read';
        const GESTION_CREATE_ENDPOINT = 'crear_perfil.php';

        let map = null;
        let repartidoresMarkers = {};
        let dashboardInterval = null;

        const navLinks = document.querySelectorAll('.nav-link');
        const views = document.querySelectorAll('.view');

        function initMap() {
            if (map) return; // Si el mapa ya existe, no hacer nada
            try {
                map = L.map('map').setView([4.60971, -74.08175], 12);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(map);
            } catch (e) {
                console.error("Error al inicializar el mapa de Leaflet:", e);
                const mapContainer = document.getElementById('map-container');
                if(mapContainer) mapContainer.innerHTML = '<p style="color:red; padding: 20px;">No se pudo cargar el mapa. Verifique la conexión a Internet y la consola.</p>';
            }
        }

        function switchView(viewName) {
            views.forEach(v => v.classList.remove('active'));
            document.getElementById(`${viewName}-view`).classList.add('active');
            navLinks.forEach(nl => nl.classList.remove('active'));
            document.querySelector(`.nav-link[data-view="${viewName}"]`).classList.add('active');

            if (viewName === 'dashboard') {
                initMap();
                // Forzar al mapa a recalcular su tamaño después de ser mostrado
                setTimeout(() => { if (map) map.invalidateSize(); }, 100);
                startDashboardUpdates();
            } else {
                stopDashboardUpdates();
            }
            if (viewName === 'gestion') {
                cargarRepartidoresParaGestion();
            }
        }

        navLinks.forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                const viewName = link.getAttribute('data-view');
                if (viewName) switchView(viewName);
            });
        });

        function startDashboardUpdates() {
            if (dashboardInterval) return;
            actualizarDatosDashboard();
            dashboardInterval = setInterval(actualizarDatosDashboard, 10000);
        }

        function stopDashboardUpdates() {
            clearInterval(dashboardInterval);
            dashboardInterval = null;
        }

        async function actualizarDatosDashboard() {
            try {
                const response = await fetch(DASHBOARD_ENDPOINT);
                if (!response.ok) throw new Error(`Error en el servidor: ${response.status}`);
                const repartidores = await response.json();
                document.querySelector('#repartidores-list-sidebar .error-msg')?.remove();
                actualizarEstadisticas(repartidores);
                actualizarMapaYLista(repartidores);
            } catch (error) {
                console.error("Error al cargar datos del dashboard:", error);
                const sidebar = document.getElementById('repartidores-list-sidebar');
                if (!sidebar.querySelector('.error-msg')) {
                    sidebar.innerHTML = '<p class="error-msg" style="color:red; font-weight:bold;">Error al cargar los datos. Revisa la consola.</p>';
                }
            }
        }

        const crearForm = document.getElementById('crear-repartidor-form');
        crearForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(crearForm);
            const submitButton = crearForm.querySelector('button');
            submitButton.disabled = true;
            submitButton.textContent = 'Creando...';
            try {
                const response = await fetch(GESTION_CREATE_ENDPOINT, { method: 'POST', body: formData });
                const result = await response.json();
                alert(result.message);
                if (response.ok) {
                    crearForm.reset();
                    cargarRepartidoresParaGestion();
                }
            } catch (error) {
                alert('Error de conexión al crear el repartidor.');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Crear Perfil';
            }
        });

        async function cargarRepartidoresParaGestion() { /* ... código sin cambios ... */ }
        function actualizarEstadisticas(repartidores) { /* ... código sin cambios ... */ }
        function actualizarMapaYLista(repartidores) { /* ... código sin cambios ... */ }

        // --- Pegar aquí las funciones sin cambios para completitud ---
        async function cargarRepartidoresParaGestion() {
            const listContainer = document.getElementById('repartidores-list-gestion');
            try {
                const response = await fetch(GESTION_READ_ENDPOINT);
                const repartidores = await response.json();
                let listHTML = '<table><thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Foto</th><th>Activo</th><th>URL App</th></tr></thead><tbody>';
                for (const rep of repartidores) {
                    const appUrl = `repartidor.html?id=${rep.id_repartidor}`;
                    const fotoTag = rep.foto_url ? `<img src="${rep.foto_url}" width="40" style="border-radius:50%; vertical-align: middle;">` : 'No';
                    listHTML += `<tr><td>${rep.id_repartidor}</td><td>${rep.nombre_completo}</td><td>${rep.email}</td><td>${fotoTag}</td><td>${rep.activo ? 'Sí' : 'No'}</td><td><a href="${appUrl}" target="_blank">Abrir App</a></td></tr>`;
                }
                listHTML += '</tbody></table>';
                listContainer.innerHTML = listHTML;
            } catch (error) {
                listContainer.innerHTML = "Error al cargar repartidores.";
            }
        }

        function actualizarEstadisticas(repartidores) {
            const activos = repartidores.filter(r => r.activo);
            document.getElementById('total-repartidores').textContent = activos.length;
            document.getElementById('repartidores-disponibles').textContent = activos.filter(r => r.estado === 'Disponible').length;
            document.getElementById('repartidores-recogida').textContent = activos.filter(r => r.estado === 'En camino a recoger').length;
            document.getElementById('repartidores-entrega').textContent = activos.filter(r => r.estado === 'En camino a entrega').length;
        }

        function actualizarMapaYLista(repartidores) {
            const sidebarList = document.getElementById('repartidores-list-sidebar');
            let sidebarHTML = '';
            const repartidoresActivosEnElMapa = new Set();
            repartidores.filter(r => r.activo).forEach(rep => {
                repartidoresActivosEnElMapa.add(String(rep.id_repartidor));
                const estadoClass = rep.estado ? rep.estado.toLowerCase().replace(/\s/g, '-') : 'desconocido';
                const fecha = rep.ultimo_update ? new Date(rep.ultimo_update.replace(/-/g, '/')).toLocaleTimeString() : 'N/A';
                sidebarHTML += `<div class="repartidor-card ${estadoClass}" data-id="${rep.id_repartidor}"><h5>${rep.nombre_completo}</h5><p>Estado: ${rep.estado || 'Desconocido'}</p><small>Última vez: ${fecha}</small></div>`;
                if (rep.latitud && rep.longitud) {
                    const popupContent = `<b>${rep.nombre_completo}</b><br>Estado: ${rep.estado}`;
                    if (repartidoresMarkers[rep.id_repartidor]) {
                        repartidoresMarkers[rep.id_repartidor].setLatLng([rep.latitud, rep.longitud]).setPopupContent(popupContent);
                    } else {
                        repartidoresMarkers[rep.id_repartidor] = L.marker([rep.latitud, rep.longitud]).addTo(map).bindPopup(popupContent);
                    }
                }
            });
            for (const id in repartidoresMarkers) {
                if (!repartidoresActivosEnElMapa.has(id)) {
                    map.removeLayer(repartidoresMarkers[id]);
                    delete repartidoresMarkers[id];
                }
            }
            sidebarList.innerHTML = sidebarHTML || '<p>No hay repartidores activos.</p>';
        }

        // --- INICIO ---
        switchView('dashboard');
    });
</script>
</body>
</html>
