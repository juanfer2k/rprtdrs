# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

"Repartidores" is a real-time delivery tracking system for El Cerrito Valle. It has two user-facing surfaces:

- **Admin panel** (`panel.php`): map view showing all riders' live positions and active orders
- **Rider app** (`repartidor.html`): mobile interface for the delivery person to broadcast GPS location and update status

The Android APK is built with **Capacitor** (app ID `org.elcerrito.rprtdrs`, `webDir: www`). Production URL: `https://elcerritovalle.org/rprtdrs/`

## Stack

- **Backend**: PHP 8 (no framework), PDO/MySQL, no Composer
- **Frontend**: Vanilla HTML/CSS/JS (no build step), Bootstrap 5, Leaflet.js (loaded from CDN)
- **Database**: MySQL (`elcerrit_rprtdrs`)
- **Mobile**: Capacitor wrapping the `www/` folder as a native Android app

## Local Development

The project has **no build step**. Serve via a local PHP server or WAMP/XAMPP pointing at the project root.

For local DB, use `conex.local.php` (Docker MySQL at `host.docker.internal:3307`). Swap `conex.php` → `conex.local.php` as needed:

```bash
# Start local MySQL via Docker (port 3307 mapped to container)
docker run -d -p 3307:3306 --name mysql-local -e MYSQL_ROOT_PASSWORD=... mysql:8

# Quick PHP dev server (serves root, not www/)
php -S localhost:8080
```

Production DB config is in `conex.php` and reads env vars (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`) with hardcoded fallbacks.

## Deployment

Run `dp.sh` **on the production server**. It pulls from GitHub `master` and rsyncs `www/` to `/home2/elcerrit/elcerritovalle.org/rprtdrs/`.

```bash
bash dp.sh
```

The `www/` folder is the canonical source for production — it mirrors the root-level PHP files and is what Capacitor packages. **Always keep `www/` in sync with root-level changes.**

## Architecture: Key Files

| File | Role |
|---|---|
| `conex.php` | DB connection (production). Required by all API files via `require_once '../conex.php'`. |
| `api/login.php` | Authenticates user, returns `api_token` + `rol` from `usuarios` table. |
| `api/track.php` | **Primary rider endpoint.** Accepts `{lat, lng, estado}` + Bearer token; updates `repartidores` and inserts into `posiciones_historial`. |
| `api/get_admin_data.php` | Returns all repartidores (with coords) + active pedidos. Polled every 5 s by `panel.php`. |
| `panel.php` | Admin dashboard. Reads `admin_token` from localStorage; redirects to `login.php` if absent. |
| `repartidor.html` | Rider UI. Self-contained — all JS is inline. Polls `api/track.php` every 15 s via `watchPosition`. |
| `login.php` | Shared login page. Calls `api/login.php`, stores `api_token` + `repartidor_id` in localStorage. |
| `actualizar_estado.php` | **Legacy** endpoint (pre-token auth). Accepts `id_repartidor` directly. Still active but superseded by `api/track.php`. |

## Database Schema (key tables)

```sql
usuarios        -- id, username, password_hash (bcrypt), rol ('admin'|'repartidor'), api_token, activo
repartidores    -- id_repartidor, nombre_completo, latitud, longitud, estado, ultima_actualizacion
pedidos         -- id_pedido, cliente_nombre, direccion_entrega, estado, id_repartidor, fecha_creacion
posiciones_historial -- id_repartidor, latitud, longitud, (timestamp)
```

Authentication uses a static `api_token` column (not JWT). `id_repartidor` in `repartidores` matches `id` in `usuarios`.

## API Base URL Resolution (frontend JS)

Both `login.php` and `repartidor.html` use the same pattern:

1. Check `localStorage.getItem('api_base_url')` (manual override)
2. If running as a Capacitor native app → hardcoded `https://elcerritovalle.org/rprtdrs/`
3. Otherwise → derive from `window.location` (relative, works for any server)

This logic lives inline in both files — if you change it, update both.
