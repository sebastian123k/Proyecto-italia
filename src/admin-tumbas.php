
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
    $manzana = trim($_POST['manzana'] ?? '');
    $cuadro = trim($_POST['cuadro'] ?? '');
    $fila = trim($_POST['fila'] ?? '');
    $numero = trim($_POST['numero'] ?? '');
    $apellidoPaterno = trim($_POST['apellidoPaterno'] ?? '');
    $apellidoMaterno = trim($_POST['apellidoMaterno'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $fechaNacimiento = trim($_POST['fechaNacimiento'] ?? '');
    $fechaDefuncion = trim($_POST['fechaDefuncion'] ?? '');
    $tipoRestos = trim($_POST['tipoRestos'] ?? '');
    $coordenadas = trim($_POST['coordenadas'] ?? '');

    // Validar campos obligatorios
    $camposObligatorios = [$manzana, $cuadro, $fila, $numero, $apellidoPaterno, $nombre];
    
    if (in_array('', $camposObligatorios, true)) {
        $error = 'Los campos marcados con * son obligatorios';
    } else {
        $stmt = $conexion->prepare(
            "INSERT INTO graves (
                manzana, cuadro, fila, numero, apellidoPaterno, apellidoMaterno,
                nombre, fechaNacimiento, fechaDefuncion, tipoRestos, coordenadas
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        $stmt->bind_param(
            "sssssssssss",
            $manzana,
            $cuadro,
            $fila,
            $numero,
            $apellidoPaterno,
            $apellidoMaterno,
            $nombre,
            $fechaNacimiento,
            $fechaDefuncion,
            $tipoRestos,
            $coordenadas
        );
        
        if ($stmt->execute()) {
            header("Location: admin-tumbas.php?exito=1");
            exit();
        } else {
            $error = "Error al crear el registro: " . $conexion->error;
        }
    }
}

// 3. Eliminar tumba
if (isset($_GET['eliminar'])) {
    $id = (int) $_GET['eliminar'];
    $stmt = $conexion->prepare("DELETE FROM graves WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

// 4. Obtener lista de tumbas
$tumbas = [];
$sql = "SELECT id, manzana, cuadro, fila, numero, apellidoPaterno, 
               apellidoMaterno, nombre, fechaNacimiento, fechaDefuncion, 
               tipoRestos, coordenadas 
        FROM graves";
$result = $conexion->query($sql);
if ($result) {
    $tumbas = $result->fetch_all(MYSQLI_ASSOC);
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Admin - Tumbas</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
    .sidebar { background: #111; color: white; min-height: 100vh; padding: 20px; }
    .sidebar a { color: white; text-decoration: none; display: block; padding: 10px; }
    .sidebar a:hover { background: #222; }
    .active { background: #d4af37; color: black !important; }
    .text-gold { color: #d4af37; }
    .table-hover tbody tr:hover { background-color: rgba(0,0,0,0.05); }
    .btn-gold { background: #d4af37; color: black; }
    .btn-gold:hover { background: #b5982f; }
  </style>
</head>
<body>

<div class="container-fluid">
  <div class="row">
    <!-- Barra lateral -->
    <nav class="col-md-3 col-lg-2 sidebar">
      <div class="text-center mb-4">
        <img src="../img/logo.png" width="40">
        <div class="text-gold fw-bold mt-2">Victorio's</div>
        <small>grave search</small>
      </div>
      <a href="admin-usuarios.php">Usuarios</a>
      <a href="admin-tumbas.php" class="active">Tumbas</a>
      <a href="logout.php" class="text-danger mt-4">Cerrar Sesión</a>
    </nav>

    <!-- Contenido principal -->
    <main class="col-md-9 col-lg-10 p-4">
      <h3 class="mb-4">Administración de Tumbas</h3>

      <?php if(isset($_GET['exito'])): ?>
        <div class="alert alert-success">Tumba registrada exitosamente!</div>
      <?php endif; ?>

      <?php if($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- Formulario de registro -->
      <form method="POST" class="row g-3 mb-4 bg-white p-3 rounded shadow">
        <div class="col-md-3">
          <label>Manzana <span class="text-danger">*</span></label>
          <input type="text" name="manzana" class="form-control" required>
        </div>
        
        <div class="col-md-3">
          <label>Cuadro <span class="text-danger">*</span></label>
          <input type="text" name="cuadro" class="form-control" required>
        </div>
        
        <div class="col-md-3">
          <label>Fila <span class="text-danger">*</span></label>
          <input type="text" name="fila" class="form-control" required>
        </div>
        
        <div class="col-md-3">
          <label>Número <span class="text-danger">*</span></label>
          <input type="text" name="numero" class="form-control" required>
        </div>

        <div class="col-md-4">
          <label>Primer apellido <span class="text-danger">*</span></label>
          <input type="text" name="apellidoPaterno" class="form-control" required>
        </div>
        
        <div class="col-md-4">
          <label>Segundo apellido</label>
          <input type="text" name="apellidoMaterno" class="form-control">
        </div>
        
        <div class="col-md-4">
          <label>Nombre <span class="text-danger">*</span></label>
          <input type="text" name="nombre" class="form-control" required>
        </div>

        <div class="col-md-3">
          <label>Fecha nacimiento</label>
          <input type="date" name="fechaNacimiento" class="form-control">
        </div>
        
        <div class="col-md-3">
          <label>Fecha defunción</label>
          <input type="date" name="fechaDefuncion" class="form-control">
        </div>

        <div class="col-md-3">
          <label>Tipo de restos</label>
          <select name="tipoRestos" class="form-select">
            <option value="">Seleccionar</option>
            <option value="Cuerpo">Cuerpo</option>
            <option value="Cenizas">Cenizas</option>
          </select>
        </div>

        <div class="col-md-3">
          <label>Coordenadas</label>
          <input type="text" name="coordenadas" class="form-control" 
                 placeholder="Ej: 24.123456,-110.123456">
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-dark">Registrar Tumba</button>
        </div>
      </form>

      <!-- Tabla de tumbas -->
      <div class="bg-white rounded shadow p-3">
        <table class="table table-hover">
          <thead class="table-dark">
            <tr>
              <th>Manzana</th>
              <th>Cuadro</th>
              <th>Fila</th>
              <th>Número</th>
              <th>Nombre</th>
              <th>1° Apellido</th>
              <th>2° Apellido</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($tumbas as $tumba): ?>
              <tr>
                <td><?= htmlspecialchars($tumba['manzana']) ?></td>
                <td><?= htmlspecialchars($tumba['cuadro']) ?></td>
                <td><?= htmlspecialchars($tumba['fila']) ?></td>
                <td><?= htmlspecialchars($tumba['numero']) ?></td>
                <td><?= htmlspecialchars($tumba['nombre']) ?></td>
                <td><?= htmlspecialchars($tumba['apellidoPaterno']) ?></td>
                <td><?= htmlspecialchars($tumba['apellidoMaterno']) ?></td>
                <td>
                  <a href="?eliminar=<?= $tumba['id'] ?>" 
                     class="text-danger"
                     onclick="return confirm('¿Eliminar este registro?')">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>