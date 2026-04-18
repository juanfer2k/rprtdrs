# Arquitectura Reestructurada - RPRTDRS

## Estructura Actual (Correcta)

```
rprtdrs/
├── .git/                          (repositorio)
├── android/                       (proyecto Android/Capacitor build)
│   └── app/src/main/assets/public/  (web assets sincronizados desde www)
├── www/                           ⭐ ÚNICA FUENTE WEB PUBLICABLE
│   ├── api/                       (endpoints JSON)
│   │   ├── login.php              (autenticación)
│   │   ├── track.php              (tracking de ubicación)
│   │   ├── get_admin_data.php     (datos para panel admin)
│   │   └── update_profile.php     (actualización de perfil)
│   ├── assets/                    (imágenes, favicons, etc)
│   ├── js/                        (scripts frontend)
│   ├── *.php                      (páginas: login.php, panel.php, repartidor.html, etc)
│   ├── conex.php                  (conexión BD producción)
│   ├── conex.local.php            (conexión BD local/xampp)
│   └── conex-switch.php           (selector entorno)
│
├── uploads/                       (datos de usuario: fotos de perfil)
│   └── perfil_user_*.jpg          (imágenes subidas por usuarios)
│
├── conex.php                      (copia para entornos que no usan www como docroot)
├── conex.local.php                (copia para entornos que no usan www como docroot)
├── conex-switch.php               (copia para entornos que no usan www como docroot)
│
├── capacitor.config.json          (configuración base de Capacitor para APK)
├── httpd.conf                     (configuración Apache: Alias /rprtdrs → www/)
├── index.html                     (landing page raíz opcional)
├── BLACKBOX.md, README.md         (documentación)
├── sync_android_assets.ps1        (script: sincronizar www → android/assets)
├── cleanup_duplicates.ps1         (script: limpiar duplicados)
└── other-config...                (dp.sh, etc)
```

## Flujo de Despliegue

### Local Development (XAMPP)
```
http://localhost/rprtdrs/
    ↓
Apache alias apunta a: C:/Users/Rosa/projects/rprtdrs/www/
    ↓
Carga: www/login.php
    ↓
Incluye: www/conex-switch.php
    ↓
Detecta SERVER_NAME=localhost → carga www/conex.local.php (BD local)
```

### Producción (elcerritovalle.org)
```
https://elcerritovalle.org/rprtdrs/
    ↓
DocumentRoot o equivalente → www/
    ↓
Carga: www/login.php
    ↓
Incluye: www/conex-switch.php
    ↓
Detecta SERVER_NAME!=localhost → carga www/conex.php (BD producción remota)
```

### APK (Android)
```
Capacitor carga desde: android/app/src/main/assets/public/
    ↓
Incluye: repartidor.html + js + assets sincronizados desde www/
    ↓
Conecta a: API en https://elcerritovalle.org/rprtdrs/ (servidor remoto)
    ↓
No necesita conex.php nativo (call HTTP a endpoints del servidor)
```

## Lo que NO va más en la Raíz

❌ `actualizar.php`, `actualizar_estado.php`, `admin.php`, `check_db.php`
❌ `crear_perfil.php`, `gestionar_repartidores.php`, `get_repartidores.php`
❌ `index.php`, `manage_apk.php`, `obtener_ubicaciones.php`
❌ `repartidor.html`, `style.css`, `login.php`, `panel.php`
❌ `manifest.json`, `leaflet.*`
❌ Carpetas: `api/`, `assets/`, `js/`

✅ Esos archivos ahora viven SOLO en `www/`

## Lo que SÍ va en la Raíz

✅ `conex*.php` (copias para legado/compatibilidad)
✅ `android/` (proyecto móvil)
✅ `uploads/` (datos de usuario)
✅ `.git`, `.gitignore`, `capacitor.config.json`
✅ Scripts: `sync_android_assets.ps1`, `cleanup_duplicates.ps1`
✅ Config: `httpd.conf`, `README.md`, `BLACKBOX.md`
✅ `index.html` (landing page opcional raíz)

## Cambios en httpd.conf

**Antes:**
```apache
Alias /rprtdrs "C:/Users/Rosa/projects/rprtdrs"
<Directory "C:/Users/Rosa/projects/rprtdrs">
```

**Después:**
```apache
Alias /rprtdrs "C:/Users/Rosa/projects/rprtdrs/www"
<Directory "C:/Users/Rosa/projects/rprtdrs/www">
```

## Ciclo de Actualización

### Cambios en www (código web)
1. Editar archivo en `www/` (ej. `www/login.php`)
2. Git commit/push
3. Despliegue: copiar contenido de `www/` al servidor web

### Cambios en APK
1. Ejecutar `./sync_android_assets.ps1` → copia www/ a android/assets
2. Compilar/firmar APK en Android Studio
3. Publicar `.apk`

### Cambios en BD o entorno
1. Actualizar `conex.php` (producción) o `conex.local.php` (local)
2. Usar `conex-switch.php` para seleccionar automáticamente

## Validación Post-Reestructura

- ✅ Raíz limpia (solo metadata + config + build)
- ✅ www/ es autónomo (contiene toda la app web)
- ✅ Apache alias apunta a www/
- ✅ Archivos sincronizados entre root/www/conex* para compatibilidad
- ✅ Android assets poblados
- ✅ Sin archivos duplicados innecesarios
