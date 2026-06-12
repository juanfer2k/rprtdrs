# Ubicación en segundo plano (APK)

El JS de `www/repartidor.html` ya detecta el plugin y lo usa solo cuando corre como
app nativa. En navegador sigue usando `navigator.geolocation` como siempre.
Si el APK instalado no tiene el plugin, cae automáticamente al GPS del WebView
(comportamiento actual). Por eso puedes desplegar la web ya y recompilar el APK cuando quieras.

## 1. Instalar el plugin (en tu máquina, raíz del proyecto)

```bash
npm install @capacitor-community/background-geolocation
npx cap sync android
```

## 2. Permisos en `android/app/src/main/AndroidManifest.xml`

Dentro de `<manifest>`, junto a los demás `<uses-permission>`:

```xml
<uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION" />
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" />
<uses-permission android:name="android.permission.FOREGROUND_SERVICE" />
<uses-permission android:name="android.permission.FOREGROUND_SERVICE_LOCATION" />
<uses-permission android:name="android.permission.POST_NOTIFICATIONS" />
```

**Nota:** NO se necesita `ACCESS_BACKGROUND_LOCATION`. El plugin corre como
*foreground service* con notificación persistente, y eso cuenta como "app en uso"
para Android. Al repartidor le basta conceder **"Permitir solo mientras se usa la app"**.
En Android 13+ también pedirá permiso de notificaciones (necesario para la
notificación del servicio).

## 3. Recompilar el APK

```bash
npx cap copy android
cd android && ./gradlew assembleRelease
# o assembleDebug para pruebas, o desde Android Studio
```

El APK resultante queda en `android/app/build/outputs/apk/`.
Súbelo por `manage_apk.php` como de costumbre.

## 4. Comportamiento

- Al cargar el perfil arranca el rastreo: notificación persistente
  **"Rastreo activo — Compartiendo tu ubicación con el panel"**.
- Sigue enviando posición a `api/track.php` cada 15 s (mismo throttle de siempre)
  con pantalla apagada o app en background.
- Estado **"No disponible"** → se detiene el servicio y desaparece la notificación
  (ahorra batería y respeta el descanso del repartidor).
- Cualquier otro estado → el servicio se reactiva solo.
- Cerrar sesión también detiene el servicio.
- Si el usuario niega el permiso, la app ofrece abrir Ajustes directamente.

## 5. Tip para celulares Xiaomi/Huawei/Oppo

Estas marcas matan servicios en background agresivamente. Si un repartidor reporta
que "deja de aparecer en el mapa", pídele excluir la app de la optimización de
batería: Ajustes → Batería → Repartidores → Sin restricciones.
