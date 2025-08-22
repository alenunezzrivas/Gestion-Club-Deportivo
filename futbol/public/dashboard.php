<?php
// public/dashboard.php
session_start();

// 1) Verificar sesión
if (!isset($_SESSION['user_id'], $_SESSION['rol'])) {
    header('Location: ../login.php');
    exit;
}
$userRole = $_SESSION['rol'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Club Deportivo</title>
  <style>
    * { box-sizing: border-box; }
    body {
      font-family: Arial, sans-serif;
      margin: 0; padding: 0;
      background-color: rgb(15, 15, 15);
    }
    .navbar {
      background: #1a1a1a;
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 2px solid #f00;
    }
    .navbar-logo {
      font-size: 28px; font-weight: bold; color: #f00;
    }
    .navbar-links { list-style: none; display: flex; gap: 20px; margin: 0; padding: 0; }
.navbar-links li a { color: rgba(155, 0, 0, 0.9); text-decoration: none; font-size: 20px; position: absolute; right: 10%; top:3%; font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;}
.navbar-links li a:hover { color:  rgba(255,0,0,0.9); }
    .main {
      display: flex;
      justify-content: center;
      padding: 40px 0;
    }
    .container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 20px;
      width: 100%;
      max-width: 1500px;
      padding: 0 20px;
    }
    .fisio-panel {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 30px;
      width: 900px;
      position: absolute;
      top: 53%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
    .panel {
      background: #fff;
            border-radius: 12px;
            text-align: center;
            padding: 20px;
            cursor: pointer;
            transition: transform .3s, box-shadow .3s;
            text-decoration: none;
            font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 600px;
            max-width: 400px; 
            background-color: rgb(114, 0, 0);
    }
    .fisio-panel .panel {
      width: 450px; 
      height: 600px; 
    }
    .panel:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 16px rgba(0,0,0,0.2);
      background-color: rgb(182, 0, 0);
    }
    .panel img {
      width: 200px; height: 200px;
      border-radius: 50%;
      object-fit: cover;
      margin: 110px 0 20px;
      border: 3px solid rgba(0,0,0,0.2);
    }
    .panel span {
      font-size: 1.5rem;
      font-weight: bold;
      color: #fff;
      margin-bottom: 20px;
      margin-top: 30px;
    }
    /* Botón “volver atrás” */
        .back-btn {
          position: absolute;
          top: 20px;
          right: 20px;
          width: 120px;
          height: 30px;
          background: rgba(118, 0, 0, 0.9);
          display: flex;
          align-items: center;
          justify-content: center;
          cursor: pointer;
          z-index: 1000;
          transition: background .2s;
          border-style: none;
        }
        .back-btn:hover {
          background: rgba(255,0,0,0.9);
        }
        .back-btn svg {
          width: 24px; height: 24px; color: black;
        }
  </style>
</head>
<body>

  <nav class="navbar">
    <div class="navbar-logo">Dashboard</div>
    <?php if (in_array($userRole, ['jugador'])): ?>
        <ul class="navbar-links">
        <li><a href="ejercicios.php">EJERCICIOS</a></li>
      </ul>
      <?php endif; ?>
  </nav>

  <div class="main">
    <div class="container">
      <?php if (in_array($userRole, ['entrenador_principal','entrenador_ayudante','jugador'])): ?>
        <a href="convocatorias.php" class="panel">
          <img src="../assets/convocatorias.jpg" alt="">
          <span>CONVOCATORIAS</span>
        </a>
      <?php endif; ?>

      <?php if (in_array($userRole, ['entrenador_principal','entrenador_ayudante','ojeador'])): ?>
        <a href="fichajes.php" class="panel">
          <img src="../assets/fichajes.jpg" alt="">
          <span>FICHAJES</span>
        </a>
      <?php endif; ?>

      <?php if (in_array($userRole, ['entrenador_principal','entrenador_ayudante','jugador'])): ?>
        <a href="estadisticas.php" class="panel">
          <img src="../assets/estadisticas.png" alt="">
          <span>ESTADÍSTICAS</span>
        </a>
      <?php endif; ?>

      <?php if (in_array($userRole, ['entrenador_principal','entrenador_ayudante','jugador'])): ?>
        <a href="tareas_futbol.php" class="panel">
          <img src="../assets/tareas.jpg" alt="">
          <span>TAREAS</span>
        </a>
      <?php endif; ?>
        <div class="fisio-panel">
      <?php if (in_array($userRole, ['fisioterapeuta'])): ?>
        <a href="ejercicios.php" class="panel">
          <img src="../assets/ejercicios.avif" alt="">
          <span>EJERCICIOS</span>
        </a>
      <?php endif; ?>

      <?php if ($userRole === 'fisioterapeuta'): ?>
        <a href="../public/lesionados.php" class="panel">
          <img src="../assets/lesionados.png" alt="">
          <span>INFORME DE LESIÓN</span>
        </a>
      <?php endif; ?>
      </div>
    </div>
  </div>

  <button class="back-btn" onclick="history.back();return false();" aria-label="Volver">
    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
  </button>

</body>
</html>
