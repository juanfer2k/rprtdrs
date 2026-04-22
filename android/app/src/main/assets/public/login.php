<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            color-scheme: dark;
            --bg-1: #0b1020;
            --bg-2: #151c33;
            --card-bg: rgba(20, 28, 52, 0.78);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #e9eefc;
            --text-soft: #a9b4d0;
            --input-bg: rgba(255, 255, 255, 0.06);
            --input-border: rgba(255, 255, 255, 0.16);
            --input-focus: #7aa2ff;
            --primary-1: #3b82f6;
            --primary-2: #2563eb;
            --shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            font-family: "Inter", "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--text-main);
            background:
                radial-gradient(1100px 500px at -10% -10%, #22315e 0%, transparent 60%),
                radial-gradient(850px 450px at 110% 120%, #1a2f66 0%, transparent 60%),
                linear-gradient(135deg, var(--bg-1), var(--bg-2));
            padding: 24px;
        }

        .login-card {
            width: 100%;
            max-width: 430px;
            padding: 34px 30px 28px;
            border-radius: 22px;
            border: 1px solid var(--card-border);
            background: var(--card-bg);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            box-shadow: var(--shadow);
            animation: fadeUp .45s ease;
        }

        .brand-title {
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: 0.2px;
            margin-bottom: 6px;
        }

        .brand-subtitle {
            color: var(--text-soft) !important;
            margin-bottom: 0;
        }

        .form-label {
            color: #d7e2ff;
            font-weight: 500;
        }

        .form-control {
            color: var(--text-main);
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 12px;
            padding: 11px 13px;
            transition: .2s ease;
        }

        .form-control::placeholder {
            color: #95a2c3;
        }

        .form-control:focus {
            background-color: rgba(255, 255, 255, 0.09);
            border-color: var(--input-focus);
            box-shadow: 0 0 0 0.2rem rgba(122, 162, 255, 0.25);
            color: #fff;
        }

        .btn-login {
            border: none;
            border-radius: 12px;
            font-weight: 600;
            letter-spacing: 0.2px;
            background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.35);
            transition: transform .15s ease, box-shadow .2s ease, filter .2s ease;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(37, 99, 235, 0.45);
            filter: brightness(1.03);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 12px;
            border: none;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.16);
            color: #fecaca;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #theme-btn {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 1000;
            background: rgba(15, 22, 44, 0.7);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            padding: 6px 12px;
            backdrop-filter: blur(10px);
            cursor: pointer;
            font-size: 1.1rem;
            line-height: 1;
            color: var(--text-main);
        }

        html[data-bs-theme="light"] {
            color-scheme: light;
            --bg-1: #eef3fb;
            --bg-2: #dfe7f5;
            --card-bg: rgba(255, 255, 255, 0.92);
            --card-border: rgba(8, 21, 51, 0.12);
            --text-main: #0f172a;
            --text-soft: #475569;
            --input-bg: #ffffff;
            --input-border: rgba(15, 23, 42, 0.18);
            --input-focus: #2563eb;
            --shadow: 0 16px 40px rgba(15, 23, 42, 0.14);
        }

        [data-bs-theme="light"] .form-label {
            color: #1f2a44;
        }

        [data-bs-theme="light"] .form-control {
            color: #0f172a;
            background-color: #ffffff;
        }

        [data-bs-theme="light"] .form-control:focus {
            color: #0f172a;
            background-color: #f8faff;
        }

        [data-bs-theme="light"] .form-control::placeholder {
            color: #64748b;
        }

        [data-bs-theme="light"] .alert-danger {
            background: #fee2e2;
            color: #7f1d1d;
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 26px 20px 22px;
                border-radius: 18px;
            }
            .brand-title {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>

<button id="theme-btn" onclick="toggleTheme()" title="Cambiar tema">🌙</button>

<div class="login-card">
    <div class="text-center mb-4">
        <h2 class="brand-title">🚀 Repartidores</h2>
        <p class="brand-subtitle">Ingresa a tu panel de control</p>
    </div>

    <div id="alert" class="alert alert-danger d-none"></div>

    <form id="login-form">
        <div class="mb-3">
            <label class="form-label">Usuario</label>
            <input type="text" id="username" class="form-control" required placeholder="admin" autocomplete="username">
        </div>
        <div class="mb-4">
            <label class="form-label">Contraseña</label>
            <input type="password" id="password" class="form-control" required placeholder="password" autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-login w-100 py-2">Entrar al Sistema</button>
    </form>
</div>

<script>
    const FALLBACK_NATIVE_BASE_URL = 'https://elcerritovalle.org/rprtdrs/';

    function applyTheme(dark) {
        document.documentElement.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
        document.getElementById('theme-btn').textContent = dark ? '☀️' : '🌙';
        localStorage.setItem('theme', dark ? 'dark' : 'light');
    }

    function toggleTheme() {
        applyTheme(localStorage.getItem('theme') !== 'dark');
    }

    applyTheme(localStorage.getItem('theme') !== 'light');

    function normalizeBaseUrl(url) {
        if (!url) return '';
        return url.endsWith('/') ? url : `${url}/`;
    }

    function resolveApiBaseUrl() {
        const configuredBaseUrl = normalizeBaseUrl(localStorage.getItem('api_base_url') || '');
        if (configuredBaseUrl) return configuredBaseUrl;

        if (window.Capacitor && window.Capacitor.isNativePlatform()) {
            return FALLBACK_NATIVE_BASE_URL;
        }

        const currentPathBase = `${window.location.origin}${window.location.pathname.replace(/[^/]*$/, '')}`;
        return normalizeBaseUrl(currentPathBase);
    }

    document.getElementById('login-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const alert = document.getElementById('alert');
        alert.classList.add('d-none');

        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const apiBaseUrl = resolveApiBaseUrl();

        try {
            const res = await fetch(`${apiBaseUrl}api/login.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password })
            });
            const data = await res.json();

            if (data.status === "success") {
                localStorage.setItem('api_token', data.token);
                localStorage.setItem('api_base_url', apiBaseUrl);
                if (data.id_repartidor) {
                    localStorage.setItem('repartidor_id', data.id_repartidor);
                } else {
                    localStorage.setItem('repartidor_id', data.id);
                }

                if (data.rol === "admin") {
                    localStorage.setItem('admin_token', data.token);
                    window.location.href = `${apiBaseUrl}panel.php`;
                } else if (data.rol === "repartidor") {
                    window.location.href = `${apiBaseUrl}repartidor.html`;
                } else {
                    alert.textContent = "Rol no reconocido.";
                    alert.classList.remove('d-none');
                }
            } else {
                alert.textContent = "Credenciales incorrectas.";
                alert.classList.remove('d-none');
            }
        } catch (error) {
            alert.textContent = "Error de conexión con el servidor.";
            alert.classList.remove('d-none');
        }
    });
</script>

</body>
</html>
