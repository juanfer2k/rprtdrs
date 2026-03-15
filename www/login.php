<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { height: 100vh; display: flex; align-items: center; justify-content: center; background: #f4f7f6; }
        .login-card { width: 100%; max-width: 400px; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); background: white; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <h2>🚀 Repartidores</h2>
        <p class="text-muted">Ingresa a tu panel de control</p>
    </div>
    
    <div id="alert" class="alert alert-danger d-none"></div>

    <form id="login-form">
        <div class="mb-3">
            <label class="form-label">Usuario</label>
            <input type="text" id="username" class="form-control" required placeholder="admin">
        </div>
        <div class="mb-4">
            <label class="form-label">Contraseña</label>
            <input type="password" id="password" class="form-control" required placeholder="password">
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2">Entrar al Sistema</button>
    </form>
</div>

<script>
    document.getElementById('login-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const alert = document.getElementById('alert');
        alert.classList.add('d-none');

        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        try {
            const res = await fetch('api/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password })
            });
            const data = await res.json();

            if (data.status === "success" && data.rol === "admin") {
                localStorage.setItem('admin_token', data.token);
                window.location.href = 'panel.php';
            } else {
                alert.textContent = "Acceso denegado o credenciales incorrectas.";
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
