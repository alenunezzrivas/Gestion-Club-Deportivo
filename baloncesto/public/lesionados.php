<?php
// public/lesionados.php
session_start();
require_once __DIR__ . '/../config/db.php';

// Solo fisioterapeuta
if (!isset($_SESSION['user_id'], $_SESSION['rol']) || $_SESSION['rol'] !== 'fisioterapeuta') {
    header('Location: ../public/login.php');
    exit;
}

$pdo = getConnection(DB_NAME_BALONCESTO);

// Flash message
$message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);

// Procesar informe de lesión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['jugador_id'])) {
    $jugId = (int) $_POST['jugador_id'];

    if ($_POST['action'] === 'save_lesion') {
        $titulo = trim($_POST['titulo']);
        $desc   = trim($_POST['descripcion']);
        try {
            $ins = $pdo->prepare("
                INSERT INTO lesiones_baloncesto (jugador_id, titulo, descripcion)
                VALUES (?, ?, ?)
            ");
            $ins->execute([$jugId, $titulo, $desc]);
        } catch (\PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $upd = $pdo->prepare("
                    UPDATE lesiones_baloncesto
                       SET titulo = ?, descripcion = ?
                     WHERE jugador_id = ?
                ");
                $upd->execute([$titulo, $desc, $jugId]);
            } else {
                throw $e;
            }
        }
        $pdo->prepare("UPDATE jugadores_baloncesto SET lesionado = 1 WHERE id = ?")
            ->execute([$jugId]);

        $_SESSION['flash_message'] = 'Informe de lesión guardado.';
    }

    if ($_POST['action'] === 'delete_lesion') {
        $pdo->prepare("DELETE FROM lesiones_baloncesto WHERE jugador_id = ?")
            ->execute([$jugId]);
        $pdo->prepare("UPDATE jugadores_baloncesto SET lesionado = 0 WHERE id = ?")
            ->execute([$jugId]);

        $_SESSION['flash_message'] = 'Informe de lesión eliminado.';
    }

    header('Location: lesionados.php');
    exit;
}

// Obtener jugadores y lesiones
$jugadores = $pdo->query("
    SELECT j.id, u.nombre, j.posicion, j.edad, j.altura, j.peso, j.lesionado,
           l.titulo, l.descripcion AS lesion_desc
      FROM jugadores_baloncesto j
      JOIN usuarios_baloncesto u ON j.usuario_id = u.id
 LEFT JOIN lesiones_baloncesto l ON j.id = l.jugador_id
 ORDER BY u.nombre
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Lesionados - Club Baloncesto</title>
  <style>
    html, body { margin:0; padding:0; }

    body {
      background: #111;
      color: #fff;
      font-family: Arial, sans-serif;
    }
    .navbar {
      background: #1a1a1a;
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom:2px solid #f00;
    }
    .navbar-logo {
      font-size:28px;
      font-weight:bold;
      color:#f00;
    }
    .flash {
      padding: 10px 20px;
      background: #1a1a1a;
      color: lightgreen;
      margin: 15px;
      border-radius: 4px;
    }

    .container {
      display: flex;
      gap: 20px;
      padding: 20px;
      height: 700px;
    }
    .table-wrapper {
      flex: 0 0 65%;
      overflow-y: auto;
      overflow-x: hidden;
    }
    #lesion-form {
      flex: 0 0 35%;
      background: #222;
      border: 1px solid #333;
      padding: 15px;
      border-radius: 8px;
      display: none;
      box-sizing: border-box;
    }
    #lesion-form h3 {
      font-size: 28px;
      margin-bottom: 30px;
      margin-top: 10px;
      color:#f99;
      margin-left: 5px;
    }
    #lesion-form label {
      display: block;
      margin-bottom: 20px;
    }
    #lesion-form input[type="text"],
    #lesion-form textarea {
      width: 100%;
      background: #111;
      color: #fff;
      border: 1px solid #555;
      padding: 8px;
      border-radius: 4px;
      margin-bottom: 10px;
      box-sizing: border-box;
      resize: vertical;
      font-size: 16px;
    }

    #lesion-form input[type="text"] {
      margin-top: 10px;
    }

    #lesion-form textarea {
      height: 300px;
      margin-top: 20px;
    }

    #lesion-form button {
      margin-right: 10px;
      padding: 8px 16px;
      background: #f00;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
      align-self: center;
    }
    #deleteBtn { background: #600; }
    #deleteBtn:hover { background: #800; }

    .table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }
    .table th, .table td {
      padding: 8px;
      text-align: left;
      border-bottom: 1px solid #333;
      word-wrap: break-word;
    }
    .table th {
      background: #1a1a1a;
      position: sticky;
      top: 0;
      z-index: 1;
    }
    .table tr.lesionado td {
      background: rgba(255,0,0,0.2);
    }
    .btn-lesion {
      padding:4px 8px;
      background:#f99;
      color:#111;
      border:none;
      border-radius:4px;
      cursor:pointer;
    }
    .btn-lesion:hover {
      background:#f00;
      color:#fff;
    }
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
    <div class="navbar-logo">Lesionados</div>
  </nav>

  <?php if ($message): ?>
    <div class="flash"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="container">
    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>Nombre</th><th>Posición</th><th>Edad</th>
            <th>Altura</th><th>Peso</th><th>Acción</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($jugadores as $j): ?>
          <tr class="<?= $j['lesionado'] ? 'lesionado' : '' ?>">
            <td><?= htmlspecialchars($j['nombre']) ?></td>
            <td><?= htmlspecialchars($j['posicion']) ?></td>
            <td><?= htmlspecialchars($j['edad']) ?></td>
            <td><?= htmlspecialchars($j['altura']) ?> cm</td>
            <td><?= htmlspecialchars($j['peso']) ?> kg</td>
            <td>
              <button
                class="btn-lesion"
                data-id="<?= $j['id'] ?>"
                data-titulo="<?= htmlspecialchars($j['titulo'],ENT_QUOTES) ?>"
                data-desc="<?= htmlspecialchars($j['lesion_desc'],ENT_QUOTES) ?>">
                Informar lesión
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div id="lesion-form">
      <h3>Informe de lesión</h3>
      <form method="post">
        <input type="hidden" name="jugador_id" id="jugador_id">
        <label>
          Título:
          <input type="text" name="titulo" id="titulo" maxlength="25" required>
        </label>
        <label>
          Descripción:
          <textarea name="descripcion" id="descripcion" rows="4" required></textarea>
        </label>
        <button type="submit" name="action" value="save_lesion">Guardar Informe</button>
        <button type="submit" name="action" value="delete_lesion" id="deleteBtn">Eliminar Informe</button>
      </form>
    </div>
  </div>

  <button class="back-btn" onclick="history.back();return false();" aria-label="Volver">
    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
  </button>

  <script>
    const form = document.getElementById('lesion-form');
    const deleteBtn = document.getElementById('deleteBtn');

    document.querySelectorAll('.btn-lesion').forEach(btn => {
      btn.addEventListener('click', () => {
        form.style.display = 'block';
        document.getElementById('jugador_id').value  = btn.dataset.id;
        document.getElementById('titulo').value      = btn.dataset.titulo || '';
        document.getElementById('descripcion').value = btn.dataset.desc   || '';
        deleteBtn.style.display = btn.dataset.desc ? 'inline-block' : 'none';
        form.scrollIntoView({ behavior: 'smooth' });
      });
    });
  </script>
</body>
</html>
