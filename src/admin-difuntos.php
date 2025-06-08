<?php
session_start();
require '../php/conection.php';

// 1. Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: admin-login.php");
    exit();
}

// ----------------------------------------------------
// Asegurarnos de que exista la carpeta 'photos/'
// ----------------------------------------------------
$uploadDir = __DIR__ . '/../photos/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// ----------------------------------------------------
// 1.b Cargar todas las tumbas para el <select> de ID
// ----------------------------------------------------
$allTumbas = [];
$rsT = $conexion->query("
    SELECT 
      id, manzana, cuadro, fila, numero 
    FROM tumbas 
    ORDER BY manzana ASC, cuadro ASC, fila ASC, numero ASC
");
if ($rsT) {
    $allTumbas = $rsT->fetch_all(MYSQLI_ASSOC);
    $rsT->close();
}

// ----------------------------------------------------
// 2. Procesar formulario de alta y edición
// ----------------------------------------------------
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action          = $_POST['action']           ?? '';
    $nombre          = trim($_POST['nombre']          ?? '');
    $apellidoPaterno = trim($_POST['apellidoPaterno'] ?? '');
    $apellidoMaterno = trim($_POST['apellidoMaterno'] ?? '');
    $fechaNacimiento = trim($_POST['fechaNacimiento'] ?? '');
    $fechaDefuncion  = trim($_POST['fechaDefuncion']  ?? '');
    $restos          = trim($_POST['restos']          ?? '');
    $idTumba         = (int) ($_POST['idTumba']       ?? 0);

    // ——— nuevos campos de proveedor ———
    $tipoProveedor   = trim($_POST['tipoProveedor']   ?? '');
    $nombreProveedor = trim($_POST['nombreProveedor'] ?? '');
    $fechaProvision  = trim($_POST['fechaProvision']  ?? '');

    // Preparar variable $imagen según si suben archivo o no
    $imagen = '';

    // Si es edición, obtener la imagen original para conservarla
    if ($action === 'edit') {
        $edit_id = (int)($_POST['edit_id'] ?? 0);
        if ($edit_id > 0) {
            $stmtImg = $conexion->prepare("SELECT imagen FROM difuntos WHERE id = ?");
            $stmtImg->bind_param("i", $edit_id);
            $stmtImg->execute();
            $stmtImg->bind_result($existingImageName);
            $stmtImg->fetch();
            $stmtImg->close();
            $imagen = $existingImageName;
        }
    }

    // Procesar subida de archivo
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['imagen']['tmp_name'];
        $origName = basename($_FILES['imagen']['name']);
        $ext = pathinfo($origName, PATHINFO_EXTENSION);
        $allowedTypes = ['image/jpeg','image/png','image/gif'];
        $detectedType = mime_content_type($tmpName);
        if (!in_array($detectedType, $allowedTypes)) {
            $error = 'Solo se permiten imágenes JPEG, PNG o GIF.';
        } else {
            $uniqueName = time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
            $destPath = $uploadDir . $uniqueName;
            if (move_uploaded_file($tmpName, $destPath)) {
                $imagen = $uniqueName;
            } else {
                $error = 'Error al subir la imagen.';
            }
        }
    } elseif (!empty($_FILES['imagen']['error']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        $error = 'Error en la subida de la imagen (código '.$_FILES['imagen']['error'].').';
    }

    // Validación mínima
    if ($error === '' && ($nombre === '' || $apellidoPaterno === '')) {
        $error = 'Los campos marcados con * son obligatorios.';
    }

    // Validación de fechas
    if ($error === '') {
        $today = date('Y-m-d');
        if ($fechaNacimiento !== '' && $fechaNacimiento > $today) {
            $error = 'La fecha de nacimiento no puede ser futura.';
        } elseif ($fechaDefuncion !== '' && $fechaDefuncion > $today) {
            $error = 'La fecha de defunción no puede ser futura.';
        } elseif ($fechaNacimiento !== '' && $fechaDefuncion !== '' && $fechaNacimiento > $fechaDefuncion) {
            $error = 'La fecha de nacimiento no puede ser posterior a la defunción.';
        }
    }

    // Validar tumba
    if ($error === '') {
        if ($idTumba <= 0) {
            $error = 'Debes seleccionar una tumba válida.';
        } else {
            $stmtCheck = $conexion->prepare("SELECT id FROM tumbas WHERE id = ?");
            $stmtCheck->bind_param("i", $idTumba);
            $stmtCheck->execute();
            $stmtCheck->store_result();
            if ($stmtCheck->num_rows === 0) {
                $error = 'La tumba seleccionada no existe.';
            }
            $stmtCheck->close();
        }
    }

    // Tumba ocupada
    if ($error === '') {
        $excluir = ($action==='edit') ? " AND id != ".(int)$edit_id : "";
        $stmtO = $conexion->prepare("SELECT id FROM difuntos WHERE idTumba = ? $excluir");
        $stmtO->bind_param("i", $idTumba);
        $stmtO->execute();
        $stmtO->store_result();
        if ($stmtO->num_rows>0) {
            $error = 'Esta tumba ya tiene un difunto asignado.';
        }
        $stmtO->close();
    }

    // —— Validar proveedor ——
    if ($error === '') {
        if (!in_array($tipoProveedor, ['Persona','Institucion'])) {
            $error = 'Tipo de proveedor inválido.';
        } elseif ($nombreProveedor==='') {
            $error = 'Nombre de proveedor es obligatorio.';
        } elseif ($fechaProvision==='') {
            $error = 'Fecha de provisión es obligatoria.';
        } elseif ($fechaDefuncion!=='' && $fechaProvision < $fechaDefuncion) {
            $error = 'La fecha de provisión no puede ser anterior a la defunción.';
        }
    }

    // Si todo ok, insertar o actualizar
    if ($error === '') {
        if ($action === 'add') {
            // Insert difunto
            $stmt = $conexion->prepare("
                INSERT INTO difuntos
                  (nombre, apellidoPaterno, apellidoMaterno,
                   fechaNacimiento, fechaDefuncion, Restos,
                   imagen, idTumba)
                VALUES (?,?,?,?,?,?,?,?)
            ");
            $stmt->bind_param(
                "sssssssi",
                $nombre, $apellidoPaterno, $apellidoMaterno,
                $fechaNacimiento, $fechaDefuncion, $restos,
                $imagen, $idTumba
            );
            if ($stmt->execute()) {
                $lastId = $stmt->insert_id;
                // Insert proveedor
                $p = $conexion->prepare("
                    INSERT INTO proveedores
                      (difunto_id, tipo, nombre, fecha_provision)
                    VALUES (?,?,?,?)
                ");
                $p->bind_param(
                    "isss",
                    $lastId,
                    $tipoProveedor,
                    $nombreProveedor,
                    $fechaProvision
                );
                $p->execute();
                $p->close();

                $success = 'Difunto y proveedor registrados exitosamente.';
            } else {
                $error = 'Error al registrar difunto: '.$conexion->error;
            }
            $stmt->close();

        } elseif ($action === 'edit') {
            // Update difunto
            $stmt = $conexion->prepare("
                UPDATE difuntos SET
                  nombre=?, apellidoPaterno=?, apellidoMaterno=?,
                  fechaNacimiento=?, fechaDefuncion=?, Restos=?,
                  imagen=?, idTumba=?
                WHERE id=?
            ");
            $stmt->bind_param(
                "sssssssii",
                $nombre, $apellidoPaterno, $apellidoMaterno,
                $fechaNacimiento, $fechaDefuncion, $restos,
                $imagen, $idTumba,
                $edit_id
            );
            if ($stmt->execute()) {
                // Update proveedor
                $u = $conexion->prepare("
                    UPDATE proveedores SET
                      tipo=?, nombre=?, fecha_provision=?
                    WHERE difunto_id=?
                ");
                $u->bind_param(
                    "sssi",
                    $tipoProveedor,
                    $nombreProveedor,
                    $fechaProvision,
                    $edit_id
                );
                $u->execute();
                $u->close();

                $success = 'Difunto y proveedor actualizados correctamente.';
            } else {
                $error = 'Error al actualizar difunto: '.$conexion->error;
            }
            $stmt->close();
        }
    }
}

// ----------------------------------------------------
// 3. Eliminar
// ----------------------------------------------------
if (isset($_GET['eliminar'])) {
    $elim_id = (int) $_GET['eliminar'];
    if ($elim_id > 0) {
        // Borrar difunto (proveedor se borra por FK CASCADE)
        $stmt = $conexion->prepare("DELETE FROM difuntos WHERE id = ?");
        $stmt->bind_param("i", $elim_id);
        $stmt->execute();
        $stmt->close();
        $success = 'Difunto y proveedor eliminados.';
    }
}

// ----------------------------------------------------
// 4. Filtros
// ----------------------------------------------------
$filterNombre      = trim($_GET['filterNombre']      ?? '');
$filterApeP        = trim($_GET['filterApeP']        ?? '');
$filterApeM        = trim($_GET['filterApeM']        ?? '');
$filterRestos      = trim($_GET['filterRestos']      ?? '');
$filterIdTumba     = trim($_GET['filterIdTumba']     ?? '');

// ----------------------------------------------------
// 5. Obtener lista con JOIN a proveedores
// ----------------------------------------------------
$difuntos = [];
$sql = "
    SELECT 
      d.id, d.nombre, d.apellidoPaterno, d.apellidoMaterno,
      d.fechaNacimiento, d.fechaDefuncion, d.Restos, d.imagen, d.idTumba,
      p.tipo   AS proveedor_tipo,
      p.nombre AS proveedor_nombre,
      p.fecha_provision
    FROM difuntos d
    LEFT JOIN proveedores p ON p.difunto_id = d.id
    WHERE 1=1
";
if ($filterNombre!=='') {
    $v = $conexion->real_escape_string($filterNombre);
    $sql .= " AND d.nombre LIKE '%$v%'";
}
if ($filterApeP!=='') {
    $v = $conexion->real_escape_string($filterApeP);
    $sql .= " AND d.apellidoPaterno LIKE '%$v%'";
}
if ($filterApeM!=='') {
    $v = $conexion->real_escape_string($filterApeM);
    $sql .= " AND d.apellidoMaterno LIKE '%$v%'";
}
if ($filterRestos!=='') {
    $v = $conexion->real_escape_string($filterRestos);
    $sql .= " AND d.Restos LIKE '%$v%'";
}
if ($filterIdTumba!=='') {
    $v = (int)$filterIdTumba;
    $sql .= " AND d.idTumba = $v";
}
$sql .= " ORDER BY d.id DESC";

$result = $conexion->query($sql);
if ($result) {
    $difuntos = $result->fetch_all(MYSQLI_ASSOC);
    $result->close();
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Panel Admin - Difuntos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
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
    .foto-thumb { max-width: 60px; max-height: 60px; object-fit: cover; border-radius: 4px; }

    @media (min-width: 768px) {
  .sidebar-custom {
    min-height: 100vh;
    position: sticky;
    top: 0;
  }
}

.sidebar-custom a {
  display: block;
  padding: 0.5rem 1rem;
  text-decoration: none;
  border-radius: 4px;
  color: white;
}

.sidebar-custom a.active {
  background-color: #ffc107; /* dorado */
  color: #000;
}

.sidebar-custom a:hover {
  background-color: #343a40; /* gris muy oscuro */
  color: white;
}
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <!-- Sidebar estático SOLO en md+ -->
    <nav class="d-none d-md-flex col-md-3 col-lg-2 bg-dark text-white p-3 flex-column sidebar-custom">
      <div class="text-center mb-4">
        <img src="../img/logo.png" width="40" alt="Logo">
        <div class="text-gold fw-bold mt-2">Victorio's</div>
        <small>grave search</small>
      </div>
      <a href="admin-usuarios.php" class="text-white mb-2">Administradores</a>
      <a href="admin-tumbas.php" class="text-white mb-2 ">Tumbas</a>
      <a href="admin-difuntos.php" class="text-white mb-2 active">Difuntos</a>
      <a href="admin-ubicaciones.php" class="text-white mb-2">Ubicaciones</a>
      <a href="logout.php" class="text-danger mt-4">Cerrar Sesión</a>
    </nav>

    <!-- Offcanvas SOLO en móvil -->
    <div class="d-md-none">
      <!-- Botón toggle -->
      <button class="btn btn-dark m-2" type="button"
              data-bs-toggle="offcanvas" data-bs-target="#mobileMenu"
              aria-controls="mobileMenu">
        ☰ Menú
      </button>

      <!-- Offcanvas panel -->
      <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu"
           aria-labelledby="mobileMenuLabel">
        <div class="offcanvas-header bg-dark text-white">
          <h5 class="offcanvas-title" id="mobileMenuLabel">Menú</h5>
          <button type="button" class="btn-close btn-close-white"
                  data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
        </div>
        <div class="offcanvas-body bg-dark text-white p-3">
          <div class="text-center mb-4">
            <img src="../img/logo.png" width="40" alt="Logo">
            <div class="text-gold fw-bold mt-2">Victorio's</div>
            <small>grave search</small>
          </div>
          <a href="admin-usuarios.php" class="text-white mb-2 ">Administradores</a>
          <a href="admin-tumbas.php" class="text-white mb-2 ">Tumbas</a>
          <a href="admin-difuntos.php" class="text-white mb-2 active">Difuntos</a>
          <a href="admin-ubicaciones.php" class="text-white mb-2">Ubicaciones</a>
          <a href="logout.php" class="text-danger mt-4">Cerrar Sesión</a>
        </div>
      </div>
    </div>

    <!-- Contenido principal -->
    <main class="col-md-9 col-lg-10 p-4">
      <h3 class="mb-4">Administración de Difuntos</h3>

      <!-- Mensajes -->
      <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- Formulario Alta -->
      <form method="POST" enctype="multipart/form-data" class="row g-3 mb-4 bg-white p-3 rounded shadow">
        <input type="hidden" name="action" value="add">

        <!-- Datos del difunto -->
        <div class="col-md-4">
          <label>Nombre <span class="text-danger">*</span></label>
          <input type="text" name="nombre" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label>Primer apellido <span class="text-danger">*</span></label>
          <input type="text" name="apellidoPaterno" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label>Segundo apellido</label>
          <input type="text" name="apellidoMaterno" class="form-control">
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
          <select name="restos" class="form-select">
            <option value="">Seleccionar</option>
            <option value="Cuerpo">Cuerpo</option>
            <option value="Cenizas">Cenizas</option>
          </select>
        </div>
        <div class="col-md-3">
          <label>Foto del difunto</label>
          <input type="file" name="imagen" accept="image/*" class="form-control">
        </div>

        <!-- Datos de proveedor -->
        <div class="col-md-4">
          <label>Tipo de proveedor <span class="text-danger">*</span></label>
          <select name="tipoProveedor" class="form-select" required>
            <option value="">Seleccionar</option>
            <option value="Persona">Persona</option>
            <option value="Institucion">Institución</option>
          </select>
        </div>
        <div class="col-md-4">
          <label>Nombre de proveedor <span class="text-danger">*</span></label>
          <input type="text" name="nombreProveedor" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label>Fecha provisión <span class="text-danger">*</span></label>
          <input type="date" name="fechaProvision" class="form-control" required>
        </div>

        <!-- Select de Tumba -->
        <div class="col-md-4">
          <label>ID Tumba <span class="text-danger">*</span></label>
          <select name="idTumba" class="form-select" required>
            <option value="">Seleccionar tumba</option>
            <?php foreach ($allTumbas as $t): ?>
              <option value="<?= $t['id'] ?>">
                ID <?= $t['id'] ?> &mdash;
                Mza <?= htmlspecialchars($t['manzana']) ?>,
                Cdr <?= htmlspecialchars($t['cuadro']) ?>,
                Fila <?= htmlspecialchars($t['fila']) ?>,
                Núm <?= htmlspecialchars($t['numero']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-dark">Registrar Difunto</button>
        </div>
      </form>

      <!-- Panel de búsqueda -->
      <div class="bg-white rounded shadow p-3 mb-4">
        <h5 class="fw-semibold mb-3">Buscar Difuntos</h5>
        <form method="GET" class="row g-3">
          <div class="col-md-3">
            <label>Nombre</label>
            <input type="text" name="filterNombre" value="<?= htmlspecialchars($filterNombre) ?>" class="form-control" placeholder="Ej. José">
          </div>
          <div class="col-md-3">
            <label>Primer apellido</label>
            <input type="text" name="filterApeP" value="<?= htmlspecialchars($filterApeP) ?>" class="form-control" placeholder="Ej. Pérez">
          </div>
          <div class="col-md-3">
            <label>Segundo apellido</label>
            <input type="text" name="filterApeM" value="<?= htmlspecialchars($filterApeM) ?>" class="form-control" placeholder="Ej. García">
          </div>
          <div class="col-md-3">
            <label>Tipo de restos</label>
            <input type="text" name="filterRestos" value="<?= htmlspecialchars($filterRestos) ?>" class="form-control" placeholder="Ej. Cenizas">
          </div>
          <div class="col-md-3">
            <label>ID Tumba</label>
            <input type="number" name="filterIdTumba" value="<?= htmlspecialchars($filterIdTumba) ?>" class="form-control" placeholder="Ej. 42">
          </div>
          <div class="col-md-9 text-end">
            <button type="reset" class="btn btn-outline-secondary">Limpiar</button>
            <button type="submit" class="btn btn-gold">Buscar</button>
          </div>
        </form>
      </div>

      <!-- Tabla de difuntos -->
      <div class="bg-white rounded shadow p-3">
        <h5 class="fw-semibold mb-3">Listado de Difuntos</h5>
        <div class="table-responsive">
          <table class="table table-hover align-middle text-center">
            <thead class="table-dark">
              <tr>
                <th>Nombre</th>
                <th>1° Apellido</th>
                <th>2° Apellido</th>
                <th>F. Nac.</th>
                <th>F. Def.</th>
                <th>Restos</th>
                <th>Proveedor</th>
                <th>Tipo Prov.</th>
                <th>F. Provisión</th>
                <th>Foto</th>
                <th>ID Tumba</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($difuntos)): ?>
                <tr>
                  <td colspan="12">No se encontraron difuntos.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($difuntos as $d): ?>
                  <tr>
                    <td><?= htmlspecialchars($d['nombre']) ?></td>
                    <td><?= htmlspecialchars($d['apellidoPaterno']) ?></td>
                    <td><?= htmlspecialchars($d['apellidoMaterno']) ?></td>
                    <td><?= htmlspecialchars($d['fechaNacimiento']) ?></td>
                    <td><?= htmlspecialchars($d['fechaDefuncion']) ?></td>
                    <td><?= htmlspecialchars($d['Restos']) ?></td>
                    <td><?= htmlspecialchars($d['proveedor_nombre'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($d['proveedor_tipo'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($d['fecha_provision'] ?? '—') ?></td>
                    <td>
                      <?php if ($d['imagen'] && file_exists($uploadDir . $d['imagen'])): ?>
                        <img src="../photos/<?= htmlspecialchars($d['imagen']) ?>" class="foto-thumb" alt="Foto">
                      <?php else: ?>
                        —
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($d['idTumba']) ?></td>
                    <td>
                      <a href="?eliminar=<?= $d['id'] ?>"
                         class="text-danger me-2"
                         onclick="return confirm('¿Eliminar este registro?')">
                        Eliminar
                      </a>
                      <button class="btn btn-sm btn-outline-primary"
                              data-bs-toggle="modal"
                              data-bs-target="#editModal<?= $d['id'] ?>">
                        Editar
                      </button>
                    </td>
                  </tr>

                  <!-- Modal de edición -->
                  <div class="modal fade" id="editModal<?= $d['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $d['id'] ?>" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                      <div class="modal-content">
                        <form method="POST" enctype="multipart/form-data">
                          <input type="hidden" name="action" value="edit">
                          <input type="hidden" name="edit_id" value="<?= $d['id'] ?>">
                          <div class="modal-header">
                            <h5 class="modal-title" id="editModalLabel<?= $d['id'] ?>">Editar Difunto #<?= $d['id'] ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                          </div>
                          <div class="modal-body">
                            <!-- Datos difunto -->
                            <div class="mb-2">
                              <label>Nombre <span class="text-danger">*</span></label>
                              <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($d['nombre']) ?>" required>
                            </div>
                            <div class="mb-2">
                              <label>1° apellido <span class="text-danger">*</span></label>
                              <input type="text" name="apellidoPaterno" class="form-control" value="<?= htmlspecialchars($d['apellidoPaterno']) ?>" required>
                            </div>
                            <div class="mb-2">
                              <label>2° apellido</label>
                              <input type="text" name="apellidoMaterno" class="form-control" value="<?= htmlspecialchars($d['apellidoMaterno']) ?>">
                            </div>
                            <div class="mb-2">
                              <label>Fecha nacimiento</label>
                              <input type="date" name="fechaNacimiento" class="form-control" value="<?= htmlspecialchars($d['fechaNacimiento']) ?>">
                            </div>
                            <div class="mb-2">
                              <label>Fecha defunción</label>
                              <input type="date" name="fechaDefuncion" class="form-control" value="<?= htmlspecialchars($d['fechaDefuncion']) ?>">
                            </div>
                            <div class="mb-2">
                              <label>Tipo de restos</label>
                              <select name="restos" class="form-select">
                                <option value="" <?= $d['Restos']===''?'selected':'' ?>>Seleccionar</option>
                                <option value="Cuerpo"  <?= $d['Restos']==='Cuerpo'?'selected':'' ?>>Cuerpo</option>
                                <option value="Cenizas" <?= $d['Restos']==='Cenizas'?'selected':'' ?>>Cenizas</option>
                              </select>
                            </div>
                            <div class="mb-2">
                              <label>Foto actual</label>
                              <div>
                                <?php if ($d['imagen'] && file_exists($uploadDir . $d['imagen'])): ?>
                                  <img src="../photos/<?= htmlspecialchars($d['imagen']) ?>" class="foto-thumb mb-2">
                                <?php else: ?>
                                  <span>Sin foto</span>
                                <?php endif; ?>
                              </div>
                            </div>
                            <div class="mb-2">
                              <label>Cambiar foto</label>
                              <input type="file" name="imagen" accept="image/*" class="form-control">
                            </div>

                            <!-- Datos proveedor -->
                            <div class="mb-2">
                              <label>Tipo de proveedor <span class="text-danger">*</span></label>
                              <select name="tipoProveedor" class="form-select" required>
                                <option value="">Seleccionar</option>
                                <option value="Persona"    <?= $d['proveedor_tipo']==='Persona'    ?'selected':'' ?>>Persona</option>
                                <option value="Institucion"<?= $d['proveedor_tipo']==='Institucion'?'selected':'' ?>>Institución</option>
                              </select>
                            </div>
                            <div class="mb-2">
                              <label>Nombre de proveedor <span class="text-danger">*</span></label>
                              <input type="text" name="nombreProveedor" class="form-control" value="<?= htmlspecialchars($d['proveedor_nombre']) ?>" required>
                            </div>
                            <div class="mb-2">
                              <label>Fecha provisión <span class="text-danger">*</span></label>
                              <input type="date" name="fechaProvision" class="form-control" value="<?= htmlspecialchars($d['fecha_provision']) ?>" required>
                            </div>

                            <!-- Select de Tumba -->
                            <div class="mb-2">
                              <label>ID Tumba <span class="text-danger">*</span></label>
                              <select name="idTumba" class="form-select" required>
                                <option value="">Seleccionar tumba</option>
                                <?php foreach ($allTumbas as $t): ?>
                                  <option value="<?= $t['id'] ?>" <?= $t['id']==(int)$d['idTumba']?'selected':''?>>
                                    ID <?= $t['id'] ?> — Mza <?= htmlspecialchars($t['manzana']) ?>, Cdr <?= htmlspecialchars($t['cuadro']) ?>, Fila <?= htmlspecialchars($t['fila']) ?>, Núm <?= htmlspecialchars($t['numero']) ?>
                                  </option>
                                <?php endforeach; ?>
                              </select>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar cambios</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                  <!-- /Fin modal -->
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
