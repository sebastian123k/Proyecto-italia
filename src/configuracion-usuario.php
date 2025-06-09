<?php
require 'controlador-sesiones.php';


require '../php/conection.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: inicion-sesion-usuarios.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$error = '';
$mensaje = '';
$mostrar_formulario = isset($_GET['editar']) && $_GET['editar'] == '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
    $nuevo_nombre = trim($_POST['nombre'] ?? '');
    $nuevo_correo = trim($_POST['correo'] ?? '');

    if (empty($nuevo_nombre) || empty($nuevo_correo)) {
        $error = 'Los campos no pueden estar vacíos.';
        $mostrar_formulario = true;
    } else {
        $sql_check = "SELECT id FROM usuarios WHERE correo = ? AND id != ?";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->bind_param("si", $nuevo_correo, $usuario_id);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        if ($res_check->num_rows > 0) {
            $error = 'El correo ya está en uso por otro usuario.';
            $stmt_check->close();
            $mostrar_formulario = true;
        } else {
            $stmt_check->close();
            $sql_upd = "UPDATE usuarios SET nombre = ?, correo = ? WHERE id = ?";
            $stmt_upd = $conexion->prepare($sql_upd);
            $stmt_upd->bind_param("ssi", $nuevo_nombre, $nuevo_correo, $usuario_id);
            if ($stmt_upd->execute()) {
                $mensaje = 'Perfil actualizado correctamente.';
                $_SESSION['usuario_nombre'] = $nuevo_nombre;
                $mostrar_formulario = false;
            } else {
                $error = 'Error al actualizar el perfil. Intenta nuevamente.';
                $mostrar_formulario = true;
            }
            $stmt_upd->close();
        }
    }
}

$sql_get = "SELECT nombre, correo FROM usuarios WHERE id = ?";
$stmt_get = $conexion->prepare($sql_get);
$stmt_get->bind_param("i", $usuario_id);
$stmt_get->execute();
$result_get = $stmt_get->get_result();
$datos_usuario = $result_get->fetch_assoc();
$stmt_get->close();

$sql_com = "
  SELECT c.id AS comentario_id, c.texto, c.idDifunto, d.nombre AS nom_difunto, d.apellidoPaterno
  FROM comentarios AS c
  JOIN difuntos AS d ON c.idDifunto = d.id
  WHERE c.idUsuario = ?
  ORDER BY c.id DESC
";
$stmt_com = $conexion->prepare($sql_com);
$stmt_com->bind_param("i", $usuario_id);
$stmt_com->execute();
$result_com = $stmt_com->get_result();
$comentarios = $result_com->fetch_all(MYSQLI_ASSOC);
$stmt_com->close();

$conexion->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Configuración de Usuario</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
  <style>
    body { background-color: #f4f4f4; font-family: 'Segoe UI', sans-serif; }
    .config-card { max-width: 500px; margin: auto; margin-top: 8vh; padding: 2rem; background-color: #fff; border-radius: 1rem; box-shadow: 0 0 20px rgba(0, 0, 0, 0.1); }
    .comentarios-section { max-width: 800px; margin: 2rem auto; }
    .comentario-text { white-space: pre-wrap; }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm px-4">
  <a class="navbar-brand" href="../index.php">
    <img src="../img/logo.png" alt="logo" width="40" height="40" class="me-2" />
    Victorio Grave Search
  </a>
  <div class="ms-auto">
    <a href="cerrar-sesion-usuario.php" class="btn btn-outline-light">Cerrar sesión</a>
  </div>
</nav>

<div class="config-card">
  <h4 class="mb-4 text-center">Configuración de Usuario</h4>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php elseif ($mensaje): ?>
    <div class="alert alert-success"><?= $mensaje ?></div>
  <?php endif; ?>

  <?php if ($mostrar_formulario): ?>
    <form method="POST">
      <div class="mb-3">
        <label for="nombre" class="form-label">Nombre</label>
        <input type="text" class="form-control" name="nombre" value="<?= htmlspecialchars($datos_usuario['nombre']) ?>" required>
      </div>
      <div class="mb-3">
        <label for="correo" class="form-label">Correo</label>
        <input type="email" class="form-control" name="correo" value="<?= htmlspecialchars($datos_usuario['correo']) ?>" required>
      </div>
      <div class="d-flex justify-content-between">
        <a href="configuracion-usuario.php" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" name="actualizar_perfil" class="btn btn-primary">Guardar Cambios</button>
      </div>
    </form>
  <?php else: ?>
    <p><strong>Nombre:</strong> <?= htmlspecialchars($datos_usuario['nombre']) ?></p>
    <p><strong>Correo:</strong> <?= htmlspecialchars($datos_usuario['correo']) ?></p>
    <a href="?editar=1" class="btn btn-warning">Editar Datos</a>
  <?php endif; ?>
</div>

<div class="comentarios-section">
  <h5 class="text-center">Mis Comentarios</h5>
  <?php if (empty($comentarios)): ?>
    <p class="text-muted text-center">No has dejado comentarios aún.</p>
  <?php else: ?>
    <ul class="list-group">
      <?php foreach ($comentarios as $c): ?>
        <li class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
          <div class="flex-grow-1">
            <div><strong><?= htmlspecialchars($c['nom_difunto'] . " " . $c['apellidoPaterno']) ?></strong></div>
            <p class="comentario-text" id="texto-<?= $c['comentario_id'] ?>"><?= htmlspecialchars($c['texto']) ?></p>
          </div>
          <div class="d-flex gap-2 mt-2 mt-md-0">
            <button class="btn btn-sm btn-outline-secondary editar-btn" data-id="<?= $c['comentario_id'] ?>" data-texto="<?= htmlspecialchars($c['texto'], ENT_QUOTES) ?>">Editar</button>
            <button class="btn btn-sm btn-outline-danger borrar-btn" data-id="<?= $c['comentario_id'] ?>">Borrar</button>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>

<!-- MODAL para editar comentario -->
<div class="modal fade" id="editarModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editarComentarioForm">
        <div class="modal-header">
          <h5 class="modal-title">Editar Comentario</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="comentarioIdInput">
          <textarea id="nuevoTexto" class="form-control" rows="4" required></textarea>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Guardar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<footer class="bg-dark text-white text-center py-3 mt-5">
  © 2025 Cementerio Perlas de La Paz — Todos los derechos reservados.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Eliminar comentario
  document.querySelectorAll('.borrar-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
      if (!confirm('¿Eliminar este comentario?')) return;
      const id = btn.dataset.id;
      const res = await fetch('comentarios-ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `accion=eliminar&id=${id}`
      });
      const txt = await res.text();
      if (txt === 'ok') {
        btn.closest('li').remove();
      } else {
        alert('Error al eliminar comentario.');
      }
    });
  });

  // Editar comentario
  const modal = new bootstrap.Modal(document.getElementById('editarModal'));
  document.querySelectorAll('.editar-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('comentarioIdInput').value = btn.dataset.id;
      document.getElementById('nuevoTexto').value = btn.dataset.texto;
      modal.show();
    });
  });

  document.getElementById('editarComentarioForm').addEventListener('submit', async e => {
    e.preventDefault();
    const id = document.getElementById('comentarioIdInput').value;
    const texto = document.getElementById('nuevoTexto').value;
    const res = await fetch('comentarios-ajax.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `accion=editar&id=${id}&texto=${encodeURIComponent(texto)}`
    });
    const txt = await res.text();
    if (txt === 'ok') {
      document.getElementById(`texto-${id}`).textContent = texto;
      modal.hide();
    } else {
      alert('Error al actualizar el comentario.');
    }
  });
});
</script>
</body>
</html>
