<?php
// public/estadisticas.php
session_start();
require_once __DIR__ . '/../config/db.php';

// Roles permitidos
if (!isset($_SESSION['user_id'], $_SESSION['rol']) || !in_array($_SESSION['rol'], ['entrenador_principal','entrenador_ayudante','jugador'])) {
    header('Location: login.php'); exit;
}
$userRole = $_SESSION['rol'];
$canEdit  = $userRole === 'entrenador_ayudante';
$pdo      = getConnection(DB_NAME_FUTBOL);

// Procesar estadísticas equipo
if ($canEdit && isset($_POST['action']) && $_POST['action'] === 'save_team') {
    $pdo->prepare("DELETE FROM estadisticas_equipo_futbol")->execute();
    $ins = $pdo->prepare(
        "INSERT INTO estadisticas_equipo_futbol (goles_favor,goles_contra,tarjetas_amarillas,tarjetas_rojas,victorias,derrotas,partidos_jugados)
         VALUES (?,?,?,?,?,?,?)"
    );
    $ins->execute([
        $_POST['goles_favor'], $_POST['goles_contra'], $_POST['tarjetas_amarillas'],
        $_POST['tarjetas_rojas'], $_POST['victorias'], $_POST['derrotas'], $_POST['partidos_jugados']
    ]);
    $msg_team = 'Estadísticas de equipo actualizadas.';
}

// Procesar estadísticas jugador
if ($canEdit && isset($_POST['action']) && $_POST['action'] === 'save_player') {
    $jugId = (int) ($_POST['jugador_id'] ?? 0);
    $goles       = isset($_POST['goles'])       && $_POST['goles']       !== '' ? (int) $_POST['goles']       : 0;
    $asistencias = isset($_POST['asistencias']) && $_POST['asistencias'] !== '' ? (int) $_POST['asistencias'] : 0;
    $pj          = isset($_POST['pj'])          && $_POST['pj']          !== '' ? (int) $_POST['pj']          : 0;

    $upd = $pdo->prepare(
        "UPDATE jugadores_futbol
         SET goles = ?, asistencias = ?, partidos_jugados = ?
         WHERE id = ?"
    );
    $upd->execute([$goles, $asistencias, $pj, $jugId]);

    header('Location: estadisticas.php');
    exit;
}

// Cargar estadísticas equipo y jugadores
$team = $pdo->query("SELECT * FROM estadisticas_equipo_futbol LIMIT 1")->fetch();

$players = $pdo->query("
  SELECT j.id, u.nombre, j.posicion,
         j.goles, j.asistencias, j.partidos_jugados
    FROM jugadores_futbol j
    JOIN usuarios_futbol u ON j.usuario_id = u.id
   ORDER BY u.nombre
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Estadísticas - Club Fútbol</title>
<style>
html, body {
  margin: 0;
  padding: 0;
}
    body { background: #111; color: #fff; font-family: Arial, sans-serif; padding: 0px; }
    .navbar { background: #1a1a1a; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom:2px solid #f00; margin-bottom:20px; }
    .navbar-logo { font-size:28px; font-weight:bold; color:#f00; }
.main { flex:1; display:flex; gap:30px; overflow:hidden; padding-top:25px; height: 670px;}
.panel{ background:#1a1a1a; border-radius:8px; display:flex; flex-direction:column; overflow:hidden; }
.team-panel{ flex:0.85; margin-left: 150px; }
.players-panel{ flex:2; margin-right: 150px;}
.panel-header{ padding:10px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #333; }
.panel-header h2{ margin:0; font-size:20px; color:#f99; margin-top: 20px; margin-bottom: 20px; margin-left: 20px;}
.panel-body{ flex:1; overflow-y:auto; padding:10px; overflow-x: hidden;}
.stats-readout{ display:grid; grid-template-columns:repeat(3,1fr); gap:10px; }
.stat-box{ background:#222; padding:10px; border-radius:4px; text-align:center; }
.form-team input{ width:60px; background:#111; color:#fff; border:1px solid #333; padding:4px; border-radius:4px; text-align:center; }
.form-team{ display:flex; flex-wrap:wrap; gap:8px; align-items:center; margin-bottom: 50px; margin-top: 50px;}
.form-team button{ padding:6px 12px; background:#f00; border:none; border-radius:4px; cursor:pointer; color:#fff; }
.form-team label{ display:flex; flex-direction:column; }
.table{ width:100%; border-collapse:collapse; }
.table th, .table td{ padding:6px; border-bottom:1px solid #333; }
.table th{ background:#222; position: relative; top:0; }
.btn-edit{ background:#f00; color:#fff; border:none; padding:4px 8px; border-radius:4px; cursor:pointer; }
.players-panel .table { table-layout: fixed; overflow-x: hidden; margin-left: 30px; width: 100%;}
.val-goles, .val-asist, .val-pj { width: 50px; text-align: center; }
.inline-form td { padding: 16px; border: none; position: relative; left: 31%; overflow-x: hidden;}
.inline-form input{ width:60px; background:#111; color:#fff; border:1px solid #333; padding:2px; border-radius:4px; text-align:center; font-size: 16px;}
.inline-form button{ background:#f00; color:#fff; border:none; padding:4px 8px; border-radius:4px; cursor:pointer; margin-left: 30px; }
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
        <?php foreach(['goles_favor'=>'GF','goles_contra'=>'GC','tarjetas_amarillas'=>'TA','tarjetas_rojas'=>'TR','victorias'=>'V','derrotas'=>'D','partidos_jugados'=>'PJ'] as $field=>$label): ?>
          <label><?= $label ?><input type="number" name="<?= $field ?>" value="<?= $team[$field] ?? 0 ?>" required></label>
        <?php endforeach; ?>
        <button type="submit">Guardar</button>
      </form>
      <?php endif; ?>
      <div class="stats-readout">
        <?php foreach(['goles_favor'=>'Goles a favor','goles_contra'=>'Goles en contra','tarjetas_amarillas'=>'Tarjetas Amarillas','tarjetas_rojas'=>'Tarjetas Rojas','victorias'=>'Victorias','derrotas'=>'Derrotas','partidos_jugados'=>'PJ'] as $field=>$label): ?>
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
        <thead><tr><th>Nombre</th><th>Posición</th><th>Goles</th><th>Asist</th><th>PJ</th><th></th></tr></thead>
        <tbody>
        <?php foreach($players as $p): ?>
          <tr data-id="<?= $p['id'] ?>">
            <td><?= htmlspecialchars($p['nombre']) ?></td>
            <td><?= htmlspecialchars($p['posicion']) ?></td>
            <td class="val-goles"><?= htmlspecialchars($p['goles'] ?? 0) ?></td>
            <td class="val-asist"><?= htmlspecialchars($p['asistencias'] ?? 0) ?></td>
            <td class="val-pj"><?= htmlspecialchars($p['partidos_jugados'] ?? 0) ?></td>
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

<script>
// Toggle team form
const btnTeam = document.getElementById('btnEditTeam');
const formTeam = document.getElementById('formTeam');
if (btnTeam) btnTeam.onclick = () => formTeam.style.display = formTeam.style.display === 'none' ? 'flex' : 'none';

// Inline edit player
document.querySelectorAll('.btnEditPlayer').forEach(btn => {
  btn.onclick = () => {
    const tr = btn.closest('tr');
    if (tr.nextElementSibling && tr.nextElementSibling.classList.contains('inline-form')) {
      tr.nextElementSibling.remove(); return;
    }
    document.querySelectorAll('.inline-form').forEach(f => f.remove());

    const id = tr.dataset.id;
    const goles = tr.querySelector('.val-goles').textContent.trim();
    const asist = tr.querySelector('.val-asist').textContent.trim();
    const pj = tr.querySelector('.val-pj').textContent.trim();

    const formRow = document.createElement('tr');
    formRow.className = 'inline-form';
    formRow.innerHTML = `
      <td colspan="6">
        <form method="post" style="display:flex; gap:10px; align-items:center;">
          <input type="hidden" name="action" value="save_player">
          <input type="hidden" name="jugador_id" value="${id}">
          Goles: <input type="number" name="goles" value="${goles}" required>
          Asistenc: <input type="number" name="asistencias" value="${asist}" required>
          Partidos: <input type="number" name="pj" value="${pj}" required>
          <button type="submit" class="btn-edit">Guardar</button>
        </form>
      </td>
    `;
    tr.after(formRow);
  };
});
</script>
</body>
</html>
