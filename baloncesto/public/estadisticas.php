<?php
// public/estadisticas.php
session_start();
require_once __DIR__ . '/../config/db.php';

// Roles permitidos
if (!isset($_SESSION['user_id'], $_SESSION['rol']) ||
    !in_array($_SESSION['rol'], ['entrenador_principal','entrenador_ayudante','jugador'])) {
    header('Location: login.php');
    exit;
}
$userRole = $_SESSION['rol'];
$canEdit  = $userRole === 'entrenador_ayudante';
$pdo      = getConnection(DB_NAME_BALONCESTO);

// Procesar estadísticas del equipo
if ($canEdit && isset($_POST['action']) && $_POST['action'] === 'save_team') {
    // Limpiamos registro existente
    $pdo->prepare("DELETE FROM estadisticas_equipo_baloncesto")->execute();
    // Insertamos nuevas estadísticas
    $ins = $pdo->prepare(
        "INSERT INTO estadisticas_equipo_baloncesto
         (puntos_favor, puntos_contra, rebotes, asistencias, victorias, derrotas, partidos_jugados)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $ins->execute([
        $_POST['puntos_favor'],
        $_POST['puntos_contra'],
        $_POST['rebotes'],
        $_POST['asistencias'],
        $_POST['victorias'],
        $_POST['derrotas'],
        $_POST['partidos_jugados']
    ]);
    $msg_team = 'Estadísticas de equipo actualizadas.';
}

// Procesar estadísticas de jugador
if ($canEdit && isset($_POST['action']) && $_POST['action'] === 'save_player') {
    $jugId      = (int) ($_POST['jugador_id'] ?? 0);
    $puntos     = isset($_POST['puntos'])     && $_POST['puntos']     !== '' ? (int) $_POST['puntos']     : 0;
    $asistTot   = isset($_POST['asistencias'])&& $_POST['asistencias'] !== '' ? (int) $_POST['asistencias'] : 0;
    $rebTot     = isset($_POST['rebotes'])    && $_POST['rebotes']    !== '' ? (int) $_POST['rebotes']    : 0;
    $pj         = isset($_POST['pj'])         && $_POST['pj']         !== '' ? (int) $_POST['pj']         : 0;

    $upd = $pdo->prepare(
        "UPDATE jugadores_baloncesto
            SET puntos_totales   = ?,
                asistencias_totales = ?,
                rebotes_totales   = ?,
                partidos_jugados  = ?
          WHERE id = ?"
    );
    $upd->execute([$puntos, $asistTot, $rebTot, $pj, $jugId]);

    header('Location: estadisticas.php');
    exit;
}

// Cargar estadísticas del equipo
$team = $pdo->query("SELECT * FROM estadisticas_equipo_baloncesto LIMIT 1")->fetch();

// Cargar estadísticas de jugadores
$players = $pdo->query("
  SELECT j.id, u.nombre, j.posicion,
         j.puntos_totales AS puntos,
         j.asistencias_totales AS asistencias,
         j.rebotes_totales AS rebotes,
         j.partidos_jugados AS pj
    FROM jugadores_baloncesto j
    JOIN usuarios_baloncesto u ON j.usuario_id = u.id
   ORDER BY u.nombre
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Estadísticas - Club Baloncesto</title>
<style>
html, body {
  margin: 0;
  padding: 0;
}
body {
  background: #111;
  color: #fff;
  font-family: Arial, sans-serif;
  padding: 0px;
}
.navbar {
  background: #1a1a1a;
  padding: 15px 30px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom:2px solid #f00;
  margin-bottom:20px;
}
.navbar-logo {
  font-size:28px;
  font-weight:bold;
  color:#f00;
}
.main {
  flex:1;
  display:flex;
  gap:30px;
  overflow:hidden;
  padding-top:25px;
  height: 670px;
}
.panel {
  background:#1a1a1a;
  border-radius:8px;
  display:flex;
  flex-direction:column;
  overflow:hidden;
}
.team-panel {
  flex:0.85;
  margin-left: 150px;
}
.players-panel {
  flex:2;
  margin-right: 150px;
}
.panel-header {
  padding:10px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  border-bottom:1px solid #333;
}
.panel-header h2 {
  margin:0;
  font-size:20px;
  color:#f99;
  margin-top: 20px;
  margin-bottom: 20px;
  margin-left: 20px;
}
.panel-body {
  flex:1;
  overflow-y:auto;
  padding:10px;
  overflow-x: hidden;
}
.stats-readout {
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:10px;
}
.stat-box {
  background:#222;
  padding:10px;
  border-radius:4px;
  text-align:center;
}
.form-team input {
  width:60px;
  background:#111;
  color:#fff;
  border:1px solid #333;
  padding:4px;
  border-radius:4px;
  text-align:center;
}
.form-team {
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  align-items:center;
  margin-bottom: 50px;
  margin-top: 50px;
}
.form-team button {
  padding:6px 12px;
  background:#f00;
  border:none;
  border-radius:4px;
  cursor:pointer;
  color:#fff;
}
.form-team label {
  display:flex;
  flex-direction:column;
}
.table {
  width:100%;
  border-collapse:collapse;
}
.table th, .table td {
  padding:6px;
  border-bottom:1px solid #333;
}
.table th {
  background:#222;
  position: relative;
  top:0;
}
.btn-edit {
  background:#f00;
  color:#fff;
  border:none;
  padding:4px 8px;
  border-radius:4px;
  cursor:pointer;
}
.players-panel .table {
  table-layout: fixed;
  overflow-x: hidden;
  margin-left: 30px;
  width: 100%;
}
.val-puntos, .val-asist, .val-reb, .val-pj {
  width: 50px;
  text-align: center;
}
.inline-form td {
  padding: 16px;
  border: none;
  position: relative;
  left: 28%;
  overflow-x: hidden;
}
.inline-form input {
  width:60px;
  background:#111;
  color:#fff;
  border:1px solid #333;
  padding:2px;
  border-radius:4px;
  text-align:center;
  font-size: 16px;
}
.inline-form button {
  background:#f00;
  color:#fff;
  border:none;
  padding:4px 8px;
  border-radius:4px;
  cursor:pointer;
  margin-left: 30px;
}
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
  <div class="navbar-logo">Estadísticas</div>
</nav>
<div class="main">
  <!-- Panel Equipo -->
  <div class="panel team-panel">
    <div class="panel-header">
      <h2>Estadísticas del Equipo</h2>
      <?php if($canEdit): ?>
      <button id="btnEditTeam" class="btn-edit">✎</button>
      <?php endif; ?>
    </div>
    <div class="panel-body">
      <?php if($canEdit): ?>
      <form id="formTeam" class="form-team" method="post" style="display:none;">
        <input type="hidden" name="action" value="save_team">
        <?php foreach([
            'puntos_favor'   => 'PF',
            'puntos_contra'  => 'PC',
            'rebotes'        => 'R',
            'asistencias'    => 'A',
            'victorias'      => 'V',
            'derrotas'       => 'D',
            'partidos_jugados'=> 'PJ'
            ] as $field => $label): ?>
          <label><?= $label ?><input type="number" name="<?= $field ?>" value="<?= $team[$field] ?? 0 ?>" required></label>
        <?php endforeach; ?>
        <button type="submit">Guardar</button>
      </form>
      <?php endif; ?>
      <div class="stats-readout">
        <?php foreach([
            'puntos_favor'   => 'Puntos a favor',
            'puntos_contra'  => 'Puntos en contra',
            'rebotes'        => 'Rebotes',
            'asistencias'    => 'Asistencias',
            'victorias'      => 'Victorias',
            'derrotas'       => 'Derrotas',
            'partidos_jugados'=> 'PJ'
            ] as $field => $label): ?>
          <div class="stat-box">
            <strong><?= $team[$field] ?? 0 ?></strong><br><small><?= $label ?></small>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Panel Jugadores -->
  <div class="panel players-panel">
    <div class="panel-header">
      <h2>Estadísticas de Jugadores</h2>
    </div>
    <div class="panel-body">
      <table class="table">
        <thead>
          <tr>
            <th>Nombre</th><th>Posición</th><th>Puntos</th><th>Asist</th><th>Reb</th><th>PJ</th><th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($players as $p): ?>
          <tr data-id="<?= $p['id'] ?>">
            <td><?= htmlspecialchars($p['nombre']) ?></td>
            <td><?= htmlspecialchars($p['posicion']) ?></td>
            <td class="val-puntos"><?= htmlspecialchars($p['puntos'] ?? 0) ?></td>
            <td class="val-asist"><?= htmlspecialchars($p['asistencias'] ?? 0) ?></td>
            <td class="val-reb"><?= htmlspecialchars($p['rebotes'] ?? 0) ?></td>
            <td class="val-pj"><?= htmlspecialchars($p['pj'] ?? 0) ?></td>
            <td><?php if($canEdit): ?><button class="btn-edit btnEditPlayer">✎</button><?php endif; ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<a href="#" class="back-btn" onclick="history.back(); return false;" aria-label="Volver">
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
  </svg>
</a>

<script>
// Toggle formulario de equipo
const btnTeam = document.getElementById('btnEditTeam');
const formTeam = document.getElementById('formTeam');
if (btnTeam) {
  btnTeam.onclick = () => {
    formTeam.style.display = formTeam.style.display === 'none' ? 'flex' : 'none';
  };
}

// Inline edit jugador
document.querySelectorAll('.btnEditPlayer').forEach(btn => {
  btn.onclick = () => {
    const tr = btn.closest('tr');
    if (tr.nextElementSibling && tr.nextElementSibling.classList.contains('inline-form')) {
      tr.nextElementSibling.remove();
      return;
    }
    document.querySelectorAll('.inline-form').forEach(f => f.remove());

    const id    = tr.dataset.id;
    const puntos= tr.querySelector('.val-puntos').textContent.trim();
    const asist = tr.querySelector('.val-asist').textContent.trim();
    const reb   = tr.querySelector('.val-reb').textContent.trim();
    const pj    = tr.querySelector('.val-pj').textContent.trim();

    // Crear fila de formulario
    const formRow = document.createElement('tr');
    formRow.className = 'inline-form';
    formRow.innerHTML = `
      <td colspan="7">
        <form method="post" style="display:flex; gap:10px; align-items:center;">
          <input type="hidden" name="action" value="save_player">
          <input type="hidden" name="jugador_id" value="${id}">
          Pts: <input type="number" name="puntos" value="${puntos}" required>
          Asts: <input type="number" name="asistencias" value="${asist}" required>
          Reb:    <input type="number" name="rebotes" value="${reb}" required>
          PJ:     <input type="number" name="pj" value="${pj}" required>
          <button type="submit" class="btn-edit">Guardar</button>
        </form>
      </td>`;
    tr.after(formRow);
  };
});
</script>
</body>
</html>
