<?php
// public/tareas_futbol.php

session_start();
require_once __DIR__ . '/../config/db.php';

// 1) Verificar sesión
if (!isset($_SESSION['user_id'], $_SESSION['nombre'], $_SESSION['rol'])) {
    header('Location: login.php');
    exit;
}

// Roles permitidos
if (!in_array($_SESSION['rol'], ['entrenador_principal', 'entrenador_ayudante', 'jugador'])) {
    header('Location: login.php');
    exit;
}

$userId   = $_SESSION['user_id'];
$userName = $_SESSION['nombre'];
$userRole = $_SESSION['rol'];

// 2) Conexión
$pdo = getConnection(DB_NAME_FUTBOL);

// 3) Determinar permisos
$isTrainer = in_array($userRole, ['entrenador_principal', 'entrenador_ayudante']);

// 4) Si es jugador, obtener su posición
$userPos = null;
if (!$isTrainer && $userRole === 'jugador') {
    $stmt = $pdo->prepare("SELECT posicion FROM jugadores_futbol WHERE usuario_id = ?");
    $stmt->execute([$userId]);
    $userPos = $stmt->fetchColumn();
}

// 5) Procesar creación/eliminación de tareas
if ($isTrainer && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $stmt = $pdo->prepare(
            "INSERT INTO tareas_futbol (titulo, descripcion, position_group)
             VALUES (:titulo, :descripcion, :group)"
        );
        $stmt->execute([
            ':titulo'      => trim($_POST['titulo']),
            ':descripcion' => trim($_POST['descripcion']),
            ':group'       => $_POST['position_group'],
        ]);
    }
    if ($action === 'delete' && !empty($_POST['task_id'])) {
        $stmt = $pdo->prepare("DELETE FROM tareas_futbol WHERE id = ?");
        $stmt->execute([intval($_POST['task_id'])]);
    }
    header('Location: tareas_futbol.php');
    exit;
}

// 6) Recuperar todas las tareas
$tareas = $pdo->query("SELECT * FROM tareas_futbol ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tareas Fútbol - Club Deportivo</title>
<style>
  html, body {
    margin: 0; padding: 0; height: 100%;
    background: #111; color: #fff; font-family: Arial, sans-serif;
  }
  .navbar {
    background: #1a1a1a; padding: 15px 30px;
    display: flex; justify-content: space-between; align-items: center;
    border-bottom: 2px solid #f00;
  }
  .navbar-logo { font-size:28px; font-weight:bold; color:#f00; }

  .container {
    max-width: 1200px; margin: 30px auto;
    padding: 0 20px;
  }

  /* BOTÓN FLOTANTE */
  <?php if($isTrainer): ?>
  #openModalBtn {
    position: fixed; bottom: 110px; right: 30px;
    background: #f00; color: #fff; border: none;
    border-radius: 50%; width: 60px; height: 60px;
    font-size: 36px; cursor: pointer; box-shadow: 0 0 10px rgba(255,0,0,0.5);
  }
  <?php endif; ?>

  /* GRID DE TARJETAS */
  .task-grid {
    margin-top: 20px; padding: 20px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px,1fr));
    gap: 20px;
  }
  .task-card {
    background: #222; border-radius: 10px; padding: 20px;
    box-shadow: 0 0 10px rgba(255,0,0,0.4);
    transition: transform .2s ease;
  }
  .task-card.hoverable:hover {
    transform: scale(1.03);
    box-shadow: 0 0 15px rgba(255,255,0,0.6);
  }
  .task-header { display: flex; justify-content: space-between; align-items: baseline; }
  .task-header h3 { margin: 0; color: #f99; font-size: 20px; }
  .task-group { font-size: 14px; color: #ccc; }
  .task-desc { margin: 15px 0; font-size: 15px; color: #ddd; }

  /* MODAL */
  .modal-overlay {
    position: fixed; top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,0.7); display:none; justify-content:center; align-items:center;
  }
  .modal {
    background: #1a1a1a; border-radius:8px; padding:30px;
    width: 90%; max-width: 600px; box-shadow: 0 0 20px rgba(0,0,0,0.8);
    display: flex; flex-direction: column;
  }
  .modal h2 { color: #f99; margin: 0 0 20px; }

  .form-admin {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
  }
  .form-admin label { display: flex; flex-direction: column; font-size:14px; }
  .form-admin textarea { grid-column: 1 / -1; resize:none; }
  .form-admin input, .form-admin select, .form-admin textarea {
    background: #222; color:#fff; border:none; border-radius:4px;
    padding:8px; font-size:14px;
  }
  .form-admin input:focus,
  .form-admin select:focus,
  .form-admin textarea:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(255,0,0,0.5);
    transition: box-shadow .2s ease;
  }

  .modal input, textarea, select{ margin-top: 10px;}

  .modal-buttons {
    grid-column: 1 / -1; display:flex; justify-content:flex-end; gap: 10px; 
  }
  .btn, .btn-close {
    padding: 10px 16px; border:none; border-radius:5px; cursor:pointer; font-size:16px;
  }
  .btn-create { background:#f00; color:#fff; }
  .btn-create:hover { background:#d00; }
  .btn-close { background:#444; color:#fff; }
  .btn-close:hover { background:#666; }

  /* Botón “volver atrás” */
.back-btn {
  position: fixed;
  bottom: 30px;
  right: 30px;
  width: 60px;
  height: 60px;
  background: rgba(109, 1, 1, 0.8);
  border-radius: 50%;
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

  <nav class="navbar">
    <div class="navbar-logo">Tareas</div>
  </nav>

  <div class="container">
    <div class="task-grid">
      <?php foreach ($tareas as $t):
        $classes = ($userPos && $t['position_group'] === $userPos) ? 'hoverable' : '';
      ?>
      <div class="task-card <?= $classes ?>">
        <div class="task-header">
          <h3><?= htmlspecialchars($t['titulo']) ?></h3>
          <span class="task-group"><?= htmlspecialchars($t['position_group']) ?></span>
        </div>
        <div class="task-desc"><?= nl2br(htmlspecialchars($t['descripcion'])) ?></div>
        <?php if ($isTrainer): ?>
          <form method="post" style="text-align:right;">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
            <button type="submit" class="btn-close">Eliminar</button>
          </form>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ($isTrainer): ?>
    <button id="openModalBtn">+</button>

    <div id="modalOverlay" class="modal-overlay">
      <div class="modal">
        <h2>Crear Nueva Tarea</h2>
        <form method="post" class="form-admin">
          <input type="hidden" name="action" value="create">
          <label>
            Título
            <input type="text" name="titulo" required>
          </label>
          <label>
            Grupo de Jugadores
            <select name="position_group">
              <option>Portero</option>
              <option>Defensa</option>
              <option>Mediocentro</option>
              <option>Delantero</option>
            </select>
          </label>
          <label>
            Descripción
            <textarea name="descripcion" rows="4" required></textarea>
          </label>
          <div class="modal-buttons">
            <button type="button" class="btn-close" id="closeModalBtn">Cancelar</button>
            <button type="submit" class="btn btn-create">Guardar</button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <a href="#" class="back-btn" onclick="history.back(); return false;" aria-label="Volver">
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
  </svg>

  <script>
    <?php if ($isTrainer): ?>
    const openBtn  = document.getElementById('openModalBtn');
    const closeBtn = document.getElementById('closeModalBtn');
    const overlay  = document.getElementById('modalOverlay');
    openBtn.onclick  = () => overlay.style.display = 'flex';
    closeBtn.onclick = () => overlay.style.display = 'none';
    // cerrar al hacer click fuera del modal
    overlay.onclick = e => { if (e.target === overlay) overlay.style.display = 'none'; }
    <?php endif; ?>
  </script>
</body>
</html>
