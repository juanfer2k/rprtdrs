// --- SCRIPT DE DIAGNÓSTICO ---
// El único objetivo de este script es probar si JavaScript se ejecuta correctamente.

document.addEventListener('DOMContentLoaded', () => {
    console.log("Diagnóstico: El script app.js se está ejecutando.");

    const loadingScreen = document.getElementById('loading-screen');
    const mainAppContainer = document.getElementById('main-app-container');

    if (loadingScreen && mainAppContainer) {
        console.log("Diagnóstico: Elementos encontrados. Ocultando carga y mostrando app.");

        // Oculta la pantalla de carga
        loadingScreen.style.display = 'none';

        // Muestra el contenedor principal y un mensaje de éxito
        mainAppContainer.style.display = 'block';
        mainAppContainer.innerHTML = '<div style="text-align: center; padding-top: 50%;"><h1>¡La App Arrancó!</h1><p>El problema está en la inicialización del mapa. Ahora podemos solucionarlo.</p></div>';

    } else {
        // Si esto aparece, hay un problema fundamental con el HTML o el tiempo de carga.
        console.error("Diagnóstico: No se encontraron los elementos #loading-screen o #main-app-container.");
    }
});
