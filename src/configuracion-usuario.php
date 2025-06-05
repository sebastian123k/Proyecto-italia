<?php
// src/configuracion-usuario.php
session_start();
require '../php/conection.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: inicion-sesion-usuarios.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$error = '';
$mensaje = '';
$mostrar_formulario = isset($_GET['editar']) && $_GET['editar'] == '1';

// 1) Procesar actualización de perfil
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

// 2) Obtener datos actuales del usuario
$sql_get = "SELECT nombre, correo FROM usuarios WHERE id = ?";
$stmt_get = $conexion->prepare($sql_get);
$stmt_get->bind_param("i", $usuario_id);
$stmt_get->execute();
$result_get = $stmt_get->get_result();
$datos_usuario = $result_get->fetch_assoc();
$stmt_get->close();

// 3) Obtener comentarios del usuario
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
  <title>Configuración de Usuario - Victorio Grave Search</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />

  <style>
    body {
      background-color: #f4f4f4;
      font-family: 'Segoe UI', sans-serif;
    }
    .navbar-brand img {
      margin-right: 8px;
    }
    .icono-usuario {
      width: 24px;
      height: 24px;
    }
    .config-card {
      max-width: 500px;
      margin: auto;
      margin-top: 8vh;
      padding: 2rem;
      background-color: #fff;
      border-radius: 1rem;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }
    .info-label {
      font-weight: 600;
      color: #555;
    }
    .comentarios-section {
      max-width: 800px;
      margin: 2rem auto;
    }
    .comentario-text {
      white-space: pre-wrap;
    }
  </style>
</head>
<body>

  <!-- HEADER DINÁMICO -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm px-4">
    <a class="navbar-brand d-flex align-items-center" href="../index.php">
      <img src="../img/logo.png" alt="logo" width="40" height="40" />
      <span class="fs-4 fw-bold">Victorio Grave Search</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <?php if (isset($_SESSION['usuario_id'])): ?>
          <li class="nav-item">
            <a class="nav-link d-flex align-items-center" href="configuracion-usuario.php">
              <i class="fas fa-user icono-usuario me-1"></i>
              <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="logout.php">Cerrar Sesión</a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="inicion-sesion-usuarios.php">Iniciar Sesión</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="registro-usuarios.php">Registrarse</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </nav>

  <!-- FORMULARIO / VISTA ESTÁTICA DE DATOS -->
  <div class="config-card">
    <h4 class="mb-4 fw-bold text-center"><i class="fas fa-user-cog me-2"></i>Configuración de Usuario</h4>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php elseif ($mensaje): ?>
      <div class="alert alert-success"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <?php if ($mostrar_formulario): ?>
      <form method="POST" action="configuracion-usuario.php">
        <div class="mb-3">
          <label for="nombre" class="form-label">Nombre</label>
          <input type="text" class="form-control" id="nombre" name="nombre"
                 value="<?php echo htmlspecialchars($datos_usuario['nombre']); ?>" required>
        </div>
        <div class="mb-3">
          <label for="correo" class="form-label">Correo</label>
          <input type="email" class="form-control" id="correo" name="correo"
                 value="<?php echo htmlspecialchars($datos_usuario['correo']); ?>" required>
        </div>
        <div class="d-flex justify-content-between">
          <a href="configuracion-usuario.php" class="btn btn-outline-secondary">Cancelar</a>
          <button type="submit" name="actualizar_perfil" class="btn btn-primary">Guardar Cambios</button>
        </div>
      </form>
    <?php else: ?>
      <div class="mb-3">
        <span class="info-label">Nombre:</span>
        <span><?php echo htmlspecialchars($datos_usuario['nombre']); ?></span>
      </div>
      <div class="mb-4">
        <span class="info-label">Correo:</span>
        <span><?php echo htmlspecialchars($datos_usuario['correo']); ?></span>
      </div>
      <div class="text-center">
        <a href="configuracion-usuario.php?editar=1" class="btn btn-gold px-4">
          <i class="fas fa-edit me-2"></i>Editar Datos
        </a>
      </div>
    <?php endif; ?>
  </div>

  <!-- SECCIÓN DE COMENTARIOS DEL USUARIO -->
  <div class="comentarios-section">
    <h5 class="fw-bold mb-3 text-center"><i class="fas fa-comments me-2"></i>Mis Comentarios</h5>
    <?php if (empty($comentarios)): ?>
      <p class="text-center text-muted">No has dejado comentarios aún.</p>
    <?php else: ?>
      <ul class="list-group">
        <?php foreach ($comentarios as $c): ?>
          <li class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
            <div class="flex-grow-1">
              <div class="fw-semibold">
                <?= htmlspecialchars($c['nom_difunto'] . " " . $c['apellidoPaterno']) ?>
              </div>
              <p class="mb-1 comentario-text"><?= htmlspecialchars($c['texto']) ?></p>
            </div>
            <div class="d-flex gap-2 mt-2 mt-md-0">
              <a href="editar_comentario.php?id=<?= htmlspecialchars($c['comentario_id']) ?>"
                 class="btn btn-sm btn-outline-secondary">Editar</a>
              <a href="borrar_comentario.php?id=<?= htmlspecialchars($c['comentario_id']) ?>"
                 class="btn btn-sm btn-outline-danger">Borrar</a>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <footer class="bg-dark text-white text-center py-3 mt-auto">
    © 2025 Cementerio Perlas de La Paz — Todos los derechos reservados.
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
