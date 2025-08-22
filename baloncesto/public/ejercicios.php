<?php
// public/ejercicios.php
session_start();
require_once __DIR__ . '/../config/db.php';

// 1) Verificar sesión y roles
if (!isset($_SESSION['user_id'], $_SESSION['rol']) ||
    !in_array($_SESSION['rol'], ['fisioterapeuta','jugador'])) {
    header('Location: login.php');
    exit;
}
$userId   = $_SESSION['user_id'];
$userRole = $_SESSION['rol'];

$isPhysio = $userRole === 'fisioterapeuta';

// 2) Conexión
$pdo = getConnection(DB_NAME_BALONCESTO);

// 3) CRUD de ejercicios (solo fisioterapeuta)
if ($isPhysio && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $stmt = $pdo->prepare("
            INSERT INTO ejercicios_baloncesto 
              (titulo, descripcion, tipo, intensidad)
            VALUES (:titulo, :descripcion, :tipo, :intensidad)
        ");
        $stmt->execute([
            ':titulo'      => trim($_POST['titulo']),
            ':descripcion' => trim($_POST['descripcion']),
            ':tipo'        => $_POST['tipo'],
            ':intensidad'  => $_POST['intensidad'],
        ]);
    }
    if ($action === 'edit' && !empty($_POST['eid'])) {
        $stmt = $pdo->prepare("
            UPDATE ejercicios_baloncesto
               SET titulo = :titulo,
                   descripcion = :descripcion,
                   tipo = :tipo,
                   intensidad = :intensidad
             WHERE id = :id
        ");
        $stmt->execute([
            ':titulo'      => trim($_POST['titulo']),
            ':descripcion' => trim($_POST['descripcion']),
            ':tipo'        => $_POST['tipo'],
            ':intensidad'  => $_POST['intensidad'],
            ':id'          => (int)$_POST['eid'],
        ]);
    }
    if ($action === 'delete' && !empty($_POST['eid'])) {
        $stmt = $pdo->prepare("DELETE FROM ejercicios_baloncesto WHERE id = ?");
        $stmt->execute([(int)$_POST['eid']]);
    }
    header('Location: ejercicios.php');
    exit;
}

// 4) Recuperar todos los ejercicios
$ejercicios = $pdo->query("
    SELECT id, titulo, descripcion, tipo, intensidad
      FROM ejercicios_baloncesto
  ORDER BY creado_en DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ejercicios - Club Deportivo</title>
  <style>
html, body {
  margin: 0;
  padding: 0;
}
    body { background: #111; color: #fff; font-family: Arial, sans-serif; padding: 0px; }
    .navbar { background: #1a1a1a; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom:2px solid #f00; margin-bottom:20px; }
    .navbar-logo { font-size:28px; font-weight:bold; color:#f00; }
    .container { display:flex; max-width:1500px; margin:0 auto; margin-left: 95px; margin-right: 95px; gap:30px}
    .admin-panel { flex:1; background:#1a1a1a; border-radius:8px; padding:20px; display:flex; flex-direction:column; margin-top: 50px; height: 450px; }
    .user-panel  { flex:4; background:#1a1a1a; border-radius:8px; padding:20px; display:flex; flex-direction:column; height: 620px; margin-top: 25px; }
    .form-admin { display:none; flex-direction:column; gap:10px; margin-bottom:20px; }
    .form-admin input, .form-admin select, .form-admin textarea {
      background:#222; color:#fff; border:none; padding:8px; border-radius:4px;
    }
    .form-admin input{ margin-left: 15px;}
    .form-admin textarea { resize:none; width: 250px; margin-top: 20px;}
    .form-admin label { font-size:14px; margin-top:20px; }
    .form-admin input[type="text"], .form-admin textarea { margin-top: 15px; }
    .btn { background:#f00; color:#fff; border:none; border-radius:5px; padding:8px 12px; cursor:pointer; margin-top: 20px;}
    .btn-edit { background:#444; }
    .btn-edit:hover { background:#666; }
    .btn-delete { background:#600; }
    .btn-delete:hover { background:#800; }
    .card-list { flex:1; overflow-y: scroll; overflow-x: hidden;display:grid; grid-template-columns: repeat(auto-fill,minmax(280px,1fr)); gap:15px; margin-top: 25px; margin-bottom: 25px;}
    .card { background:#222; border-radius:8px; padding:15px; display:flex; flex-direction:column; justify-content:space-between; box-shadow:0 0 10px rgba(255,0,0,0.4); transition:transform .2s; }
    .card:hover { transform:scale(1.02); }
    .card h3 { margin:0 0 10px; color:#f99; font-size:18px; }
    .card small { color:#ccc; }
    .card p { flex:1; font-size:14px; color:#ddd; margin:10px 0; }
    .card .actions { display:flex; gap:10px; justify-content:flex-end; }
            /* Botón “volver atrás” */
.back-btn {
  position: absolute;
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
  border-style: none;
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
    <div class="navbar-logo">Ejercicios</div>
  </nav>
  <div class="container">
    <?php if ($isPhysio): ?>
    <div class="admin-panel">
      <button id="newBtn" class="btn">+ Nuevo Ejercicio</button>
      <form id="formAdmin" class="form-admin" method="post">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="eid" id="eid">
        <label>Título<input type="text" name="titulo" id="titulo" required></label>
        <label>Descripción<textarea name="descripcion" id="descripcion" rows="3" required></textarea></label>
        <label>Tipo
          <select name="tipo" id="tipo">
            <option>Estiramiento</option>
            <option>Fortalecimiento</option>
            <option>Movilidad</option>
            <option>Cardio</option>
          </select>
        </label>
        <label>Intensidad
          <select name="intensidad" id="intensidad">
            <option>Baja</option>
            <option>Media</option>
            <option>Alta</option>
          </select>
        </label>
        <div style="display:flex; gap:10px; margin-top:10px;">
          <button type="submit" class="btn">Guardar</button>
          <button type="button" id="cancelBtn" class="btn btn-delete">Cancelar</button>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <div class="user-panel">
      <div class="card-list">
        <?php foreach($ejercicios as $e): ?>
          <div class="card" data-id="<?= $e['id'] ?>"
               data-titulo="<?= htmlspecialchars($e['titulo'], ENT_QUOTES) ?>"
               data-desc="<?= htmlspecialchars($e['descripcion'], ENT_QUOTES) ?>"
               data-tipo="<?= $e['tipo'] ?>"
               data-intens="<?= $e['intensidad'] ?>">
            <h3><?= htmlspecialchars($e['titulo']) ?></h3>
            <small>Tipo: <?= htmlspecialchars($e['tipo']) ?> | Intensidad: <?= htmlspecialchars($e['intensidad']) ?></small>
            <p><?= nl2br(htmlspecialchars($e['descripcion'])) ?></p>
            <?php if ($isPhysio): ?>
            <div class="actions">
              <button class="btn-edit btnEdit">✎</button>
              <form method="post" onsubmit="return confirm('Eliminar ejercicio?');" style="display:inline">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="eid" value="<?= $e['id'] ?>">
                <button type="submit" class="btn-delete">🗑️</button>
              </form>
            </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

<a href="#" class="back-btn" onclick="history.back(); return false;" aria-label="Volver">
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
  </svg></a>

  <script>
    const newBtn    = document.getElementById('newBtn');
    const formAdmin = document.getElementById('formAdmin');
    const cancelBtn = document.getElementById('cancelBtn');
    const eidField  = document.getElementById('eid');
    const titleFld  = document.getElementById('titulo');
    const descFld   = document.getElementById('descripcion');
    const tipoFld   = document.getElementById('tipo');
    const intenFld  = document.getElementById('intensidad');

    if (newBtn) {
      newBtn.onclick = () => {
        formAdmin.style.display = 'flex';
        document.querySelector('input[name=action]').value = 'create';
        eidField.value = '';
        titleFld.value = '';
        descFld.value = '';
        tipoFld.selectedIndex = 0;
        intenFld.selectedIndex = 0;
      };
    }
    if (cancelBtn) {
      cancelBtn.onclick = () => formAdmin.style.display = 'none';
    }

    document.querySelectorAll('.btnEdit').forEach(btn => {
      btn.onclick = () => {
        const card = btn.closest('.card');
        formAdmin.style.display = 'flex';
        document.querySelector('input[name=action]').value = 'edit';
        eidField.value  = card.dataset.id;
        titleFld.value = card.dataset.titulo;
        descFld.value  = card.dataset.desc;
        tipoFld.value  = card.dataset.tipo;
        intenFld.value = card.dataset.intens;
      };
    });
  </script>
</body>
</html>
