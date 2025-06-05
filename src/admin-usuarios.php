<?php
session_start();
require '../php/conection.php';

// 1. Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: admin-login.php");
    exit();
}

// 2. Procesar formulario de creación
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Evitar undefined index si viene GET o sin ese campo
    $nombre     = trim($_POST['nombre']     ?? '');
    $correo     = trim($_POST['correo']     ?? '');
    $contrasena =           $_POST['contrasena'] ?? '';

    // Validar campos
    if ($nombre === '' || $correo === '' || $contrasena === '') {
        $error = 'Todos los campos son obligatorios';
    } else {
        // Guardar la contraseña tal cual (texto plano)
        $stmt = $conexion->prepare(
            "INSERT INTO admins (Name, correo, contraseña) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sss", $nombre, $correo, $contrasena);

        if ($stmt->execute()) {
            header("Location: admin-usuarios.php?exito=1");
            exit();
        } else {
            $error = "Error al crear el usuario: " . $stmt->error;
        }
        $stmt->close();
    }
}

// 3. Eliminar usuario
if (isset($_GET['eliminar'])) {
    $id = (int) $_GET['eliminar'];
    $stmt = $conexion->prepare("DELETE FROM admins WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin-usuarios.php");
    exit();
}

// 4. Obtener lista de usuarios
$usuarios = [];
// Alias Name→nombre para usar siempre ['nombre']
$sql = "SELECT id, Name AS nombre, correo FROM admins";
if ($result = $conexion->query($sql)) {
    $usuarios = $result->fetch_all(MYSQLI_ASSOC);
    $result->close();
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel Admin - Usuarios</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
    .sidebar { background: #111; color: white; min-height: 100vh; padding: 20px; }
    .sidebar a { color: white; text-decoration: none; display: block; padding: 10px; }
    .sidebar a:hover { background: #222; }
    .active { background: #d4af37; color: black !important; }
    .text-gold { color: #d4af37; }
    .table-hover tbody tr:hover { background-color: rgba(0,0,0,0.05); }
  </style>
</head>
<body>

<div class="container-fluid">
  <div class="row">
    <!-- Barra lateral -->
    <nav class="col-md-3 col-lg-2 sidebar">
      <div class="text-center mb-4">
        <img src="../img/logo.png" width="40" alt="Logo">
        <div class="text-gold fw-bold mt-2">Victorio's</div>
        <small>grave search</small>
      </div>
      <a href="admin-usuarios.php" class="active">Usuarios</a>
      <a href="admin-tumbas.php">Tumbas</a>
      <a href="admin-difuntos.php">Difuntos</a>
      <a href="admin-manzanas-filas-cuadros.php">Ubicaciones</a>
      <a href="logout.php" class="text-danger mt-4">Cerrar Sesión</a>
    </nav>

    <!-- Contenido principal -->
    <main class="col-md-9 col-lg-10 p-4">
      <h3 class="mb-4">Administración de Usuarios</h3>

      <?php if (isset($_GET['exito'])): ?>
        <div class="alert alert-success">Usuario creado exitosamente!</div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- Formulario de creación -->
      <form method="POST" class="row g-3 mb-4 bg-white p-3 rounded shadow">
        <div class="col-md-4">
          <input type="text" name="nombre"     class="form-control" placeholder="Nombre"     required>
        </div>
        <div class="col-md-4">
          <input type="email" name="correo"     class="form-control" placeholder="Correo"     required>
        </div>
        <div class="col-md-4">
          <input type="text" name="contrasena" class="form-control" placeholder="Contraseña" required>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-dark">Crear Usuario</button>
        </div>
      </form>

      <!-- Tabla de usuarios -->
      <div class="bg-white rounded shadow p-3">
        <table class="table table-hover">
          <thead class="table-dark">
            <tr>
              <th>Nombre</th>
              <th>Correo</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($usuarios as $usuario): ?>
              <tr>
                <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                <td><?= htmlspecialchars($usuario['correo']) ?></td>
                <td>
                  <a href="?eliminar=<?= $usuario['id'] ?>"
                     class="text-danger"
                     onclick="return confirm('¿Eliminar este usuario?')">
                    Eliminar
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</div>

</body>
</html>
