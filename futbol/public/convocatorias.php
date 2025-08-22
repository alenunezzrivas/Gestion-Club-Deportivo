<?php
// public/convocatorias.php
session_start();
require_once __DIR__ . '/../config/db.php';

// 1) Verificar sesión y roles
if (!isset($_SESSION['user_id'], $_SESSION['rol'])) {
    header('Location: ../public/login.php'); exit;
}
$userId   = $_SESSION['user_id'];
$userRole = $_SESSION['rol'];

$canConvocar = $userRole === 'entrenador_principal';
$canView     = in_array($userRole, ['entrenador_principal','entrenador_ayudante','jugador']);

$pdo = getConnection(DB_NAME_FUTBOL);

// 2) Procesar guardar convocatoria
if ($canConvocar && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected = $_POST['jugadores'] ?? [];
    if (count($selected) === 11) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO convocatorias_futbol (fecha, descripcion) VALUES (CURDATE(), ?)");
        $stmt->execute([trim($_POST['descripcion'] ?? 'Convocatoria')]);
        $convId = $pdo->lastInsertId();
        $ins = $pdo->prepare("INSERT INTO convocatoria_jugadores_futbol (convocatoria_id, jugador_id) VALUES (?, ?)");
        foreach ($selected as $j) {
            $ins->execute([$convId, (int)$j]);
        }
        $pdo->commit();
        $success = "Convocatoria guardada.";
    } else {
        $error = "Selecciona exactamente 11 jugadores. Has seleccionado " . count($selected);
    }
}

// 3) Obtener plantilla y última convocatoria
$players = $pdo->query("
    SELECT j.id, u.nombre, j.posicion, j.lesionado
      FROM jugadores_futbol j
      JOIN usuarios_futbol u ON j.usuario_id=u.id
  ORDER BY u.nombre
")->fetchAll();
$conv     = $pdo->query("SELECT id, descripcion, fecha FROM convocatorias_futbol ORDER BY id DESC LIMIT 1")->fetch();
$selectedIds = [];
if ($conv) {
    $sel = $pdo->prepare("SELECT jugador_id FROM convocatoria_jugadores_futbol WHERE convocatoria_id=?");
    $sel->execute([$conv['id']]);
    $selectedIds = $sel->fetchAll(PDO::FETCH_COLUMN);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Convocatorias - Club Fútbol</title>
  <style>
    html, body {
  margin: 0;
  padding: 0;
}
    body { background: #111; color: #fff; font-family: Arial, sans-serif; padding: 0px; }
    .navbar { background: #1a1a1a; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom:2px solid #f00; margin-bottom:20px; }
    .navbar-logo { font-size:28px; font-weight:bold; color:#f00; }
    .container { display: flex; gap:40px; max-width:1300px; margin:0 auto; align-content: center; margin-top: 50px; }
    .left-panel, .right-panel { background:#1a1a1a; border-radius:10px; display:flex; flex-direction:column; }
    .left-panel { flex:1; height:650px; }
    .panel-header, .panel-footer { padding:15px 20px; }
    .panel-header { border-bottom:1px solid #333; display:flex; justify-content:space-between; align-items:center; }
    .panel-header h2 { margin:0; font-size:24px; color:#f99; }
    .counter { font-size:16px; }
    .card-container { overflow-y:auto; padding:10px 20px; flex:1; }
    .card { display:flex; align-items:center; background:#222; border-radius:10px; padding:10px; margin-bottom:10px; box-shadow:0 0 10px rgba(255,0,0,0.4); transition: opacity .3s ease; }
    .card.lesionado { opacity:0.5; }
    .card img { width:40px; height:40px; border-radius:50%; background:#000; margin-right:20px; }
    .card h3 { margin:0; font-size:18px; color:#f99; flex:1; }
    .card span { font-size:14px; color:#ccc; margin-right:10px; }
    .card input[type=checkbox] { width:20px; height:20px; margin-right: 30px; }
    .card input[type=checkbox]:disabled { cursor:not-allowed; }
    .panel-footer { border-top:1px solid #333; }
    .btn-save { padding:10px 20px; background:#f00; color:#fff; border:none; border-radius:5px; font-size:16px; cursor:pointer; width:100%; margin-top:15px; margin-bottom: 15px;}
    .btn-save:disabled { background:#444; cursor:not-allowed; }
    .right-panel { flex:0 0 350px; padding:20px; }
    .table { width:100%; border-collapse:collapse; }
    .table th, .table td { padding:8px; text-align:left; }
    .table th { background:#222; }
    .table tr:nth-child(even) td { background:#1a1a1a; }
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
    <div class="navbar-logo">Convocatorias</div>
  </nav>
  <div class="container">
    <?php if ($canView): ?>
    <div class="left-panel">
      <div class="panel-header">
        <h2>Plantilla</h2>
        <div class="counter">Seleccionados: <span id="count"><?= count($selectedIds) ?></span>/11</div>
      </div>
      <form id="convForm" method="post" class="card-container">
        <?php foreach ($players as $p): 
          $disabled = $p['lesionado'] ? 'disabled' : '';
          $cls      = $p['lesionado'] ? 'lesionado' : '';
        ?>
          <div class="card <?= $cls ?>">
            <img src="../assets/perfil.webp" alt="">
            <h3><?= htmlspecialchars($p['nombre']) ?></h3>
            <span><?= htmlspecialchars($p['posicion']) ?></span>
            <?php if ($canConvocar): ?>
            <input type="checkbox" name="jugadores[]" value="<?= $p['id'] ?>"
              <?= in_array($p['id'],$selectedIds)?'checked':'' ?> <?= $disabled ?>>
              <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </form>
      <div class="panel-footer">
        <?php if ($canConvocar): ?>
        <button type="submit" form="convForm" class="btn-save" id="saveBtn" disabled>Guardar Convocatoria</button>
        <?php endif; ?>
        <?php if (!empty($success)): ?><p style="color:lightgreen;margin-top:10px;"><?= htmlspecialchars($success) ?></p><?php endif; ?>
        <?php if (!empty($error)):   ?><p style="color:salmon;margin-top:10px;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($canView): ?>
    <div class="right-panel">
      <h2>Convocados</h2>
      <table class="table">
        <tr><th>Nombre</th><th>Posición</th></tr>
        <?php
          $order = ['Portero','Defensa','Mediocentro','Delantero'];
          $rows = [];
          foreach ($selectedIds as $id) {
            foreach ($players as $p) if ($p['id']==$id) $rows[]=$p;
          }
          usort($rows, fn($a,$b)=>array_search($a['posicion'],$order)-array_search($b['posicion'],$order));
          if ($rows):
            foreach ($rows as $p): ?>
              <tr><td><?= htmlspecialchars($p['nombre']) ?></td><td><?= htmlspecialchars($p['posicion']) ?></td></tr>
            <?php endforeach;
          else: ?>
            <tr><td colspan="2">Sin convocatoria.</td></tr>
        <?php endif; ?>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <a href="#" class="back-btn" onclick="history.back(); return false;" aria-label="Volver">
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
  </svg></a>

  <script>
    const form      = document.getElementById('convForm');
    const checkboxes = form.querySelectorAll('input[type=checkbox]:not(:disabled)');
    const saveBtn    = document.getElementById('saveBtn');
    const countEl    = document.getElementById('count');

    function updateCount(){
      const cnt = Array.from(checkboxes).filter(c=>c.checked).length;
      countEl.textContent = cnt;
      saveBtn.disabled = cnt !== 11;
    }
    checkboxes.forEach(cb=>cb.addEventListener('change', updateCount));
    window.addEventListener('load', updateCount);
  </script>
</body>
</html>
