<?php
// public/fichajes.php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'], $_SESSION['rol'])) {
    header('Location: ../public/login.php'); exit;
}
$userId   = $_SESSION['user_id'];
$userRole = $_SESSION['rol'];

$canAdmin = in_array($userRole, ['entrenador_principal','entrenador_ayudante']);
$canScout = $userRole === 'ojeador';
$canView  = in_array($userRole, ['entrenador_principal','entrenador_ayudante','ojeador']);

$pdo = getConnection(DB_NAME_FUTBOL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($canAdmin && isset($_POST['action_sug'])) {
        if ($_POST['action_sug'] === 'create') {
            $stmt = $pdo->prepare("INSERT INTO sugerencias_fichajes_futbol (entrenador_id, descripcion, posicion) VALUES (?,?,?)");
            $stmt->execute([$userId, trim($_POST['descripcion']), $_POST['posicion']]);
        } elseif ($_POST['action_sug'] === 'edit') {
            $stmt = $pdo->prepare("UPDATE sugerencias_fichajes_futbol SET descripcion=?, posicion=? WHERE id=?");
            $stmt->execute([trim($_POST['descripcion']), $_POST['posicion'], (int)$_POST['sug_id']]);
        } elseif ($_POST['action_sug'] === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM sugerencias_fichajes_futbol WHERE id=?");
            $stmt->execute([(int)$_POST['sug_id']]);
        }
    }
    if ($canScout && isset($_POST['action_fichar'], $_POST['libre_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM jugadores_libres_futbol WHERE id=?");
        $stmt->execute([(int)$_POST['libre_id']]);
        $libre = $stmt->fetch();
        if ($libre) {
            $hash = password_hash('default123', PASSWORD_DEFAULT);
            $insU = $pdo->prepare("INSERT INTO usuarios_futbol (nombre, contrasena, rol) VALUES (?,?, 'jugador')");
            $insU->execute([$libre['nombre'], $hash]);
            $newUid = $pdo->lastInsertId();
            $insJ = $pdo->prepare("INSERT INTO jugadores_futbol (usuario_id,posicion,edad,altura,peso,lesionado) VALUES (?,?,?,?,?,0)");
            $insJ->execute([$newUid, $libre['posicion'], $libre['edad'], $libre['altura'], $libre['peso']]);
            $del = $pdo->prepare("DELETE FROM jugadores_libres_futbol WHERE id=?");
            $del->execute([(int)$_POST['libre_id']]);
        }
    }
}

$sugs = $pdo->query(
    "SELECT s.id, s.descripcion, s.posicion, u.nombre AS entrenador
     FROM sugerencias_fichajes_futbol s
     JOIN usuarios_futbol u ON s.entrenador_id=u.id
     ORDER BY s.id DESC"
)->fetchAll();

$libres = [];
if ($canView) {
    $libres = $pdo->query("SELECT * FROM jugadores_libres_futbol ORDER BY nombre")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Fichajes</title>
<style>
body {
  background: #111;
  color: #fff;
  font-family: Arial, sans-serif;
  margin: 0;
  margin-left: 1.5cm;
  margin-right: 1.5cm;
  height: 100vh;
  display: flex;
  flex-direction: column;
}
.navbar { background: #1a1a1a; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f00; width: 100vw; box-sizing: border-box; position: relative; left: 50%; right: 50%; margin-left: -50vw; margin-right: -50vw; margin-bottom: 30px;}
.navbar-logo { font-size: 28px; font-weight: bold; color: #f00; }
.navbar a { color: #fff; text-decoration: none; margin-left: 20px; }
.main { flex: 1; display: flex; overflow: hidden; }
.container { display: flex; gap: 40px; max-width: 1400px; margin: 0 3cm; flex: 1; height: 700px}
.left-panel, .right-panel { background: #1a1a1a; border-radius: 10px; display: flex; flex-direction: column; }
.left-panel { flex: 1; max-width: 900px;}
.panel-header, .panel-footer { padding: 15px 20px; flex-shrink: 0; }
.panel-header { border-bottom: 1px solid #333; display: flex; justify-content: space-between; align-items: center; }
.panel-header h2 { margin: 0; font-size: 24px; color: #f99; margin-top: 20px;}
.panel-header .controls button { margin-left: 10px; }
.card-container {
      flex:1;
      overflow-y: auto;
      padding: 10px 10px;
    }
.card {
      display:flex;
      align-items:center;
      background:#222;
      border-radius:10px;
      padding:10px;
      margin-bottom:8px;
      box-shadow:0 0 10px rgba(255,0,0,0.4);
      cursor:pointer;
    }
.card.selected { border: 2px solid #0f0; }
.card h3 { margin: 0; font-size: 18px; color: #f99; flex: 2; }
.card span { font-size: 14px; color: #ccc; margin-right: 10px; flex: 1; }
.card button { background: #f00; color: #fff; border: none; border-radius: 5px; padding: 6px 10px; cursor: pointer; }
.card button:hover { background: #d00; }
.panel-footer { border-top: 1px solid #333; display: flex; flex-direction: column; gap: 10px; }
.panel-footer input, .panel-footer select, .panel-footer textarea {
      background:#222;
      color:#fff;
      border:none;
      border-radius:5px;
      padding:8px;
      font-size:14px;
      width:calc(100% - 16px);
    }
.panel-footer button { align-self: flex-end; padding: 10px 20px; background: #f00; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
.panel-footer button:disabled { background: #444; cursor: not-allowed; }
.right-panel { flex: 0 0 450px; display: flex; flex-direction: column; width: 600px; }
.right-panel h2, .right-panel .table-header { flex-shrink: 0; padding: 15px 20px; border-bottom: 1px solid #333; }
.right-panel .table-container { flex: 1; overflow-y: auto; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 8px; text-align: left; }
.table th { background: #222; position: sticky; top: 0; }
.table tr:nth-child(even) td { background: #1a1a1a; }
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
  <div class="navbar-logo">Sugerencias // Fichajes</div>
</nav>
<div class="main">
  <div class="container">
    <?php if ($canView): ?>
    <div class="left-panel">
      <div class="panel-header">
        <h2>Sugerencias</h2>
        <?php if ($canAdmin): ?>
        <div class="controls">
          <button class="btn btn-save" style="width:40px;">+</button>
          <button id="editBtn" class="btn btn-save" disabled>✎</button>
        </div>
        <?php endif; ?>
      </div>
      <?php if ($canAdmin): ?>
      <form id="sugForm" method="post" class="panel-footer hidden">
        <input type="hidden" name="action_sug" value="create">
        <input type="hidden" name="sug_id" id="edit_sug_id">
        <select name="posicion" id="posSelect">
          <option>Portero</option><option>Defensa</option><option>Mediocentro</option><option>Delantero</option>
        </select>
        <textarea name="descripcion" id="descInput" rows="3" placeholder="Descripción..."></textarea>
        <button type="submit" class="btn-save">Guardar</button>
      </form>
      <?php endif; ?>
      <div class="card-container">
        <?php foreach ($sugs as $s): ?>
        <div class="card" data-id="<?= $s['id'] ?>" data-desc="<?= htmlspecialchars($s['descripcion'], ENT_QUOTES) ?>" data-pos="<?= $s['posicion'] ?>">
          <h3><?= htmlspecialchars($s['descripcion']) ?></h3>
          <span><?= htmlspecialchars($s['posicion']) ?></span>
          <span>por <?= htmlspecialchars($s['entrenador']) ?></span>
          <?php if ($canAdmin): ?>
          <form method="post" style="margin:0 0 0 10px; display:inline;">
            <input type="hidden" name="action_sug" value="delete">
            <input type="hidden" name="sug_id" value="<?= $s['id'] ?>">
            <button type="submit">Eliminar</button>
          </form>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($canView): ?>
    <div class="right-panel">
      <h2>Jugadores Libres</h2>
      <div class="table-container">
        <table class="table">
          <thead>
            <tr><th>Nombre</th><th>Posición</th><th>Edad</th><th>Altura</th><th>Peso</th><?php if ($canScout): ?><th>Acción</th><?php endif; ?></tr>
          </thead>
          <tbody>
          <?php if ($libres): foreach ($libres as $l): ?>
            <tr>
              <td><?= htmlspecialchars($l['nombre']) ?></td>
              <td><?= htmlspecialchars($l['posicion']) ?></td>
              <td><?= $l['edad'] ?></td>
              <td><?= $l['altura'] ?> cm</td>
              <td><?= $l['peso'] ?> kg</td>
              <?php if ($canScout): ?>
              <td><form method="post" style="margin:0;"><input type="hidden" name="action_fichar" value="1"><input type="hidden" name="libre_id" value="<?= $l['id'] ?>"><button class="btn-save" type="submit">Fichar</button></form></td>
              <?php endif; ?>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="<?= $canScout ? 6 : 5 ?>">No hay jugadores libres.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
<a href="#" class="back-btn" onclick="history.back(); return false;" aria-label="Volver">
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
  </svg></a>
<script>
let selectedCard = null;
function toggleForm() { document.getElementById('sugForm').classList.toggle('hidden'); }
document.querySelectorAll('.panel-header .btn-save').forEach(btn => btn.addEventListener('click', toggleForm));
document.querySelectorAll('.card').forEach(card => {
  card.addEventListener('click', () => {
    if (selectedCard) selectedCard.classList.remove('selected');
    selectedCard = card; card.classList.add('selected');
    document.getElementById('editBtn').disabled = false;
  });
});
document.getElementById('editBtn').addEventListener('click', () => {
  if (!selectedCard) return;
  document.getElementById('sugForm').classList.remove('hidden');
  document.querySelector('#sugForm input[name=action_sug]').value = 'edit';
  document.getElementById('edit_sug_id').value = selectedCard.dataset.id;
  document.getElementById('descInput').value = selectedCard.dataset.desc;
  document.getElementById('posSelect').value = selectedCard.dataset.pos;
});
</script>
</body>
</html>
