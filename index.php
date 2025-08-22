<?php
// TFG/index.php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Club Deportivo – Elige tu módulo</title>
  <style>
    html, body { margin: 0; padding: 0; height: 100%; background: #111; color: #fff; font-family: Arial, sans-serif; }
    .wrapper {
      display: flex; flex-direction: column;
      align-items: center;
      height: 100%;
      text-align: center;
    }
    h1 { font-size: 4rem; color: #f00; margin-bottom: 5rem; margin-top: 170px;}
    .choices {
      display: flex; gap: 2rem;
    }
    .card {
      display: flex; flex-direction: column; align-items: center;
      background: #1a1a1a; border: 2px solid #f00; border-radius: 12px;
      width: 220px; height: 220px; justify-content: center;
      text-decoration: none; color: #fff;
      transition: transform .2s, background .2s;
    }
    .card:hover {
      background: #333; transform: scale(1.05);
    }
    .card img {
      width: 90px; height: 90px; margin-bottom: .5rem;
    }
    .card span {
      font-size: 1.2rem; font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <h1>Club Deportivo</h1>
    <div class="choices">
      <a href="futbol/public/login.php" class="card">
        <img src="futbol/assets/futbol.webp" alt="Fútbol">
        <span>Fútbol</span>
      </a>
      <a href="baloncesto/public/login.php" class="card">
        <img src="baloncesto/assets/baloncesto.png" alt="Baloncesto">
        <span>Baloncesto</span>
      </a>
    </div>
  </div>
</body>
</html>
