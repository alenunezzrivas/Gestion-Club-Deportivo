<?php
// public/login.php
session_start();
require_once __DIR__ . '/../config/db.php';

$error = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $password = $_POST['contrasena'] ?? '';

    // Validar campos
    if (empty($nombre) || empty($password)) {
        $error = 'Credenciales inválidas.';
    } else {
        // Conexión a base de datos
        $pdo = getConnection(DB_NAME_FUTBOL);

        // Comprobar credenciales
        $stmt = $pdo->prepare('SELECT id, nombre, rol, contrasena FROM usuarios_futbol WHERE nombre = ?');
        $stmt->execute([$nombre]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['contrasena'])) {
            // Guardar datos de sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['rol'] = $user['rol'];

            // Redirigir según rol
            switch ($user['rol']) {
                case 'entrenador_principal':
                    header('Location: ../public/dashboard.php');
                    break;
                case 'entrenador_ayudante':
                    header('Location: ../public/dashboard.php');
                    break;
                case 'fisioterapeuta':
                    header('Location: ../public/dashboard.php');
                    break;
                case 'jugador':
                    header('Location: ../public/dashboard.php');
                    break;
                case 'ojeador':
                    header('Location: ../public/fichajes.php');
                    break;
                default:
                    $error = 'Rol no reconocido.';
            }
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Club Management</title>
    <style>
        html, body {
  margin: 0;
  padding: 0;
}
        body {
            margin-top: 0px;
            font-family: Arial, sans-serif;
            background-color: black;
            color: aliceblue;
            text-align: center;
        }
        .navbar {
            background-color: #1a1a1a;
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #fff;
            border-bottom: 2px solid red;
            margin-bottom: 40px;
        }
        .navbar-logo {
            font-size: 24px;
            font-weight: bold;
            color: #f00;
        }
        .navbar-links {
            list-style: none;
            display: flex;
            gap: 20px;
            margin: 0;
            padding: 0;
        }
        .navbar-links li a {
            color: #fff;
            text-decoration: none;
            font-size: 18px;
        }
        .navbar-links li a:hover {
            color: #f00;
        }
        h1 {
            margin-top: 80px;
            margin-bottom: 60px;
            color: rgba(245, 5, 5, 0.65);
            font-size: 45px;
        }
        form {
            display: inline-block;
            text-align: left;
        }
        label, input {
            display: block;
            margin: 10px 0;
            padding: 10px;
            width: 300px;
            font-size: 18px;
        }
        input[type="text"], input[type="password"] {
            background-color: #333;
            color: aliceblue;
            border: 1px solid #555;
            border-radius: 5px;
        }
        button {
            background-color: rgba(245, 5, 5, 0.65);
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
            margin: 20px auto;
            display: block;
        }
        button:hover {
            background-color: red;
        }
        .error {
            color: red;
            margin-top: 10px;
            font-size: 18px;
        }
                /* Botón “volver atrás” */
.back-btn {
  position: fixed;
  top: 20px;
  right: 20px;
  width: 120px;
  height: 30px;
  background: rgba(118, 0, 0, 0.9);
  border-radius: 0%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 1000;
  transition: background .2s;
}
.back-btn:hover {
  background: rgba(255,0,0,0.9);
}
.back-btn svg {
  width: 24px;
  height: 24px;
  color: #fff;
}
    </style>
</head>

<body>

    <!-- Barra de navegación -->
    <nav class="navbar">
        <div class="navbar-logo">Inicio Sesión</div>
        <ul class="navbar-links"></ul>
    </nav>

    <h1>Bienvenido a Club Deportivo</h1>

    <!-- Formulario de inicio de sesión -->
    <form method="POST">
        <label for="nombre">Usuario:</label>
        <input type="text" name="nombre" id="nombre" required>

        <label for="contrasena">Contraseña:</label>
        <input type="password" name="contrasena" id="contrasena" required>

        <button type="submit">Entrar</button>
    </form>

    <!-- Mensaje de error si existe -->
    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <a href="#" class="back-btn" onclick="history.back(); return false;" aria-label="Volver">
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
  </svg>


</body>
</html>
