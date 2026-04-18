# RPRTDRS - Pizza Delivery Tracking & Admin Panel

RPRTDRS is a PHP-based tracking system for pizza deliveries with:

- **Delivery view** for repartidores (drivers) to report location and order status.
- **Web admin panel** to manage users/repartidores and monitor live order/location activity.
- **API endpoints** for login, tracking, and admin data.
- **Web + Android packaging structure** (`www/`, `android/`) for hybrid/mobile deployment.

Production path mentioned by project owner:
- `elcerritovalle.org/rprtdrs`

---

## Project Overview

This repository contains multiple mirrored copies of the app:

- Root files (main editable source used on server in many setups)
- `www/` copy (web build/public distribution)
- `www/uploads/` copy (deployed/uploaded snapshot)
- `android/` project artifacts (Capacitor/Android build output and assets)

Main stack:

- **PHP** (backend + server-rendered/admin pages)
- **MySQL** (via `conex.php` / `conex.local.php`)
- **JavaScript + Leaflet** (map/location display)
- **Static assets** (icons, logos, manifest)

---

## Key Files and Responsibilities

### Core PHP pages (root)

- `index.php` / `index.html` - entry points
- `login.php` - authentication UI/flow
- `admin.php` / `panel.php` - admin interfaces
- `repartidor.html` - delivery driver tracking interface
- `gestionar_repartidores.php` - user/driver management
- `obtener_ubicaciones.php` - fetch tracked locations
- `actualizar_estado.php`, `actualizar.php` - order/location/status updates
- `crear_perfil.php` - profile creation
- `manage_apk.php` - APK/admin-related management
- `check_db.php` - database connectivity checks
- `conex.php` / `conex.local.php` - DB connection config

### API (JSON/backend endpoints)

- `api/login.php`
- `api/track.php`
- `api/get_admin_data.php`

### Frontend assets

- `js/app.js`
- `style.css`
- `leaflet.js`, `leaflet.css`
- `manifest.json`
- `assets/` (icons/images/favicons)

### Mirrored copies

Equivalent structures are also present in:

- `www/`
- `www/uploads/`

---

## Why It May Not Run Locally (Common Causes)

If the project works on hosting but fails locally, check:

1. **Database credentials mismatch**
   - Local should typically use `conex.local.php`.
   - Production usually uses `conex.php`.
2. **PHP server root mismatch**
   - App may expect to run at a subpath (e.g. `/rprtdrs`).
3. **Missing database schema/data**
   - Tables required by login/tracking/admin may not exist locally.
4. **File permission differences**
   - Especially for logs/uploads.
5. **Hardcoded URLs**
   - API or asset paths may still target production domain.
6. **Duplicated source confusion**
   - Editing `www/` while serving root (or vice versa).

---

## Local Development Setup (Windows + PHP)

## 1) Requirements

- PHP 7.4+ (or 8.x)
- MySQL / MariaDB
- Optional: Apache/XAMPP/WAMP (or PHP built-in server)

## 2) Clone / place project

Place project folder as:

- `c:/Users/Rosa/projects/rprtdrs`

## 3) Configure database connection

Review and adjust:

- `conex.local.php`
- `conex.php`

Set local DB host/user/password/database values.

## 4) Create/import database

Import your SQL schema/data into local MySQL database.  
(If you do not have a dump yet, export from production before testing full flow.)

## 5) Serve the project locally

From project root:

```bash
php -S localhost:8000
```

Then open:

- `http://localhost:8000/index.php`
- `http://localhost:8000/login.php`
- `http://localhost:8000/admin.php`

If the app expects subfolder routing, serve from an Apache virtual host or place folder under your web root as `/rprtdrs`.

---

## Deployment Notes

Production target path:

- `https://elcerritovalle.org/rprtdrs`

Typical deployment options:

1. Upload root app contents to server directory mapped to `/rprtdrs`.
2. Ensure PHP version compatibility.
3. Set production DB credentials in `conex.php`.
4. Confirm file/folder permissions for logs/uploads.
5. Validate API routes:
   - `/rprtdrs/api/login.php`
   - `/rprtdrs/api/track.php`
   - `/rprtdrs/api/get_admin_data.php`

---

## Android / Hybrid App Notes

Repository includes:

- `android/` (Android build-related files)
- `www/` (web assets often used for Capacitor/Cordova packaging)
- `js/capacitor-welcome.js`

If packaging mobile app:

1. Ensure web assets are current in `www/`.
2. Sync/copy latest web files before Android build.
3. Build/sign APK through your Android toolchain.

---

## Recommended Cleanup (Optional but Helpful)

To reduce maintenance issues:

- Define one **single source of truth** (root or `www/`) and generate others.
- Remove stale duplicate files in `www/uploads/` if no longer needed.
- Add `.env`-based config strategy for local vs production DB settings.
- Add SQL schema dump to repo (without sensitive data).

---

## Quick Troubleshooting Checklist

- [ ] `check_db.php` confirms DB connection
- [ ] API endpoints return expected responses
- [ ] Login works for admin and repartidor roles
- [ ] Driver location updates appear in admin panel
- [ ] Order status updates persist in DB
- [ ] Leaflet map loads correctly (JS/CSS paths valid)
- [ ] No hardcoded production URL blocking local usage

---

## Security Notes

Before publishing/sharing repository:

- Remove plaintext credentials from `conex*.php`.
- Restrict direct access to debug/log files (`debug_log.txt`, `error_log`).
- Validate and sanitize all request input in API/PHP endpoints.
- Add authentication/authorization checks to admin-only pages.

---

## Maintainer Note

This README was reconstructed from current repository structure because no readable prior `README.md` was present. It is intended as a practical operational guide for local debugging and deployment continuity.
