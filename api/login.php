<?php
/**
 * Login Multi-tenant
 *
 * Valida usuario contra BD Master (sige_two_terwoo)
 * usando TER_IdTercero (número) + TWO_Pass
 */

// Cargar bootstrap
require_once __DIR__ . '/../bootstrap.php';

use App\Container;
use App\Auth\AuthService;

$auth = Container::get(AuthService::class);
$error = '';

// Si ya está logueado, redirigir al index
if (isAuthenticated()) {
    header('Location: /index.php');
    exit();
}

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clienteId = (int)($_POST['cliente_id'] ?? 0);
    $password = $_POST['password'] ?? '';

    if ($clienteId <= 0) {
        $error = 'Ingresá un ID de cliente válido';
    } elseif (empty($password)) {
        $error = 'Ingresá tu contraseña';
    } else {
        if ($auth->login($clienteId, $password)) {
            header('Location: /index.php');
            exit();
        } else {
            $error = 'ID o contraseña incorrectos';
        }
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Sincronización</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: #1e293b;
            border-radius: 12px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            border: 1px solid #334155;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            font-size: 24px;
            color: #3b82f6;
            margin-bottom: 5px;
        }

        .logo span {
            font-size: 12px;
            color: #64748b;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 13px;
            color: #94a3b8;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 14px 16px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            font-size: 14px;
            color: #e2e8f0;
            transition: border-color 0.2s;
        }

        input:focus {
            outline: none;
            border-color: #3b82f6;
        }

        input::placeholder {
            color: #64748b;
        }

        button {
            width: 100%;
            padding: 14px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        button:hover {
            background: #2563eb;
        }

        .error {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .help-text {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #334155;
            font-size: 12px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>Sistema de Sincronización</h1>
            <span>Ingresá tus credenciales para continuar</span>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="cliente_id">ID de Cliente</label>
                <input type="number"
                       id="cliente_id"
                       name="cliente_id"
                       placeholder="Ingresá tu número de cliente"
                       required
                       autofocus
                       min="1"
                       value="<?= htmlspecialchars($_POST['cliente_id'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password"
                       id="password"
                       name="password"
                       placeholder="Ingresá tu contraseña"
                       required>
            </div>

            <button type="submit">Iniciar Sesión</button>
        </form>

        <div class="help-text">
            Contactá al administrador si no tenés acceso
        </div>
    </div>
</body>
</html>
