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
    $action = $_POST['action'] ?? '';

    // Campos comunes
    $nombre          = trim($_POST['nombre'] ?? '');
    $apellidoPaterno = trim($_POST['apellidoPaterno'] ?? '');
    $apellidoMaterno = trim($_POST['apellidoMaterno'] ?? '');
    $fechaNacimiento = trim($_POST['fechaNacimiento'] ?? '');
    $fechaDefuncion  = trim($_POST['fechaDefuncion'] ?? '');
    $restos          = trim($_POST['restos'] ?? '');
    $idTumba         = (int) ($_POST['idTumba'] ?? 0);

    // Preparar variable $imagen según si suben archivo o no
    $imagen = '';

    // Si es edición, obtener la imagen original para conservarla si no suben nueva
    if ($action === 'edit') {
        $edit_id = (int)($_POST['edit_id'] ?? 0);
        if ($edit_id > 0) {
            $stmtImg = $conexion->prepare("SELECT imagen FROM difuntos WHERE id = ?");
            $stmtImg->bind_param("i", $edit_id);
            $stmtImg->execute();
            $stmtImg->bind_result($existingImageName);
            $stmtImg->fetch();
            $stmtImg->close();
            $imagen = $existingImageName; // imagen por defecto
        }
    }

    // Procesar subida de archivo (tanto para alta como edición)
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['imagen']['tmp_name'];
        $origName = basename($_FILES['imagen']['name']);
        $ext = pathinfo($origName, PATHINFO_EXTENSION);
        // Generar nombre único: timestamp + id temporal + extensión
        $uniqueName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destPath = $uploadDir . $uniqueName;

        if (move_uploaded_file($tmpName, $destPath)) {
            $imagen = $uniqueName;
        } else {
            $error = 'Error al subir la imagen.';
        }
    }

    // Validación mínima de campos obligatorios
    if ($nombre === '' || $apellidoPaterno === '') {
        $error = 'Los campos marcados con * son obligatorios.';
    } else {
        // Verificar que el idTumba existe
        if ($idTumba <= 0) {
            $error = 'Debes seleccionar una tumba válida.';
        } else {
            $stmtCheck = $conexion->prepare("SELECT id FROM tumbas WHERE id = ?");
            $stmtCheck->bind_param("i", $idTumba);
            $stmtCheck->execute();
            $stmtCheck->store_result();
            if ($stmtCheck->num_rows === 0) {
                $error = "La tumba seleccionada no existe.";
            }
            $stmtCheck->close();
        }

        // Si no hubo error tras la verificación de la tumba, procedemos
        if ($error === '') {
            if ($action === 'add') {
                // --- ALTA DE UN NUEVO DIFUNTO ---
                $stmt = $conexion->prepare("
                    INSERT INTO difuntos (
                        nombre, apellidoPaterno, apellidoMaterno,
                        fechaNacimiento, fechaDefuncion, Restos,
                        imagen, idTumba
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    "sssssssi",
                    $nombre,
                    $apellidoPaterno,
                    $apellidoMaterno,
                    $fechaNacimiento,
                    $fechaDefuncion,
                    $restos,
                    $imagen,
                    $idTumba
                );
                if ($stmt->execute()) {
                    $success = 'Difunto registrado exitosamente.';
                } else {
                    $error = 'Error al registrar difunto: ' . $conexion->error;
                }
                $stmt->close();

            } elseif ($action === 'edit') {
                // --- EDICIÓN DE UN DIFUNTO EXISTENTE ---
                $edit_id = (int) ($_POST['edit_id'] ?? 0);
                if ($edit_id <= 0) {
                    $error = 'ID de difunto inválido para edición.';
                } else {
                    // Verificar que el difunto existe
                    $stmt = $conexion->prepare("SELECT id FROM difuntos WHERE id = ?");
                    $stmt->bind_param("i", $edit_id);
                    $stmt->execute();
                    $stmt->store_result();
                    if ($stmt->num_rows === 0) {
                        $error = 'El difunto que intentas editar no existe.';
                        $stmt->close();
                    } else {
                        $stmt->close();
                        // Ya validamos idTumba arriba; ahora hacemos UPDATE
                        $stmtUpd = $conexion->prepare("
                            UPDATE difuntos
                            SET
                                nombre          = ?,
                                apellidoPaterno = ?,
                                apellidoMaterno = ?,
                                fechaNacimiento = ?,
                                fechaDefuncion  = ?,
                                Restos          = ?,
                                imagen          = ?,
                                idTumba         = ?
                            WHERE id = ?
                        ");
                        $stmtUpd->bind_param(
                            "sssssssii",
                            $nombre,
                            $apellidoPaterno,
                            $apellidoMaterno,
                            $fechaNacimiento,
                            $fechaDefuncion,
                            $restos,
                            $imagen,
                            $idTumba,
                            $edit_id
                        );
                        if ($stmtUpd->execute()) {
                            $success = 'Difunto actualizado correctamente.';
                        } else {
                            $error = 'Error al actualizar difunto: ' . $conexion->error;
                        }
                        $stmtUpd->close();
                    }
                }
            }
        }
    }
}

// ----------------------------------------------------
// 3. Procesar eliminación
// ----------------------------------------------------
if (isset($_GET['eliminar'])) {
    $elim_id = (int) $_GET['eliminar'];
    if ($elim_id > 0) {
        // Antes de borrar la base, recuperar el nombre de la imagen para eliminar el archivo físico
        $stmtImg = $conexion->prepare("SELECT imagen FROM difuntos WHERE id = ?");
        $stmtImg->bind_param("i", $elim_id);
        $stmtImg->execute();
        $stmtImg->bind_result($imgToDelete);
        $stmtImg->fetch();
        $stmtImg->close();

        // Borrar registro de la BD
        $stmt = $conexion->prepare("DELETE FROM difuntos WHERE id = ?");
        $stmt->bind_param("i", $elim_id);
        $stmt->execute();
        $stmt->close();

        // Borrar archivo físico si existe
        if ($imgToDelete && file_exists($uploadDir . $imgToDelete)) {
            unlink($uploadDir . $imgToDelete);
        }

        $success = 'Difunto eliminado.';
    }
}

// ----------------------------------------------------
// 4. Preparar filtros de búsqueda (GET)
// ----------------------------------------------------
$filterNombre   = trim($_GET['filterNombre'] ?? '');
$filterApeP     = trim($_GET['filterApeP']   ?? '');
$filterApeM     = trim($_GET['filterApeM']   ?? '');
$filterRestos   = trim($_GET['filterRestos'] ?? '');
$filterIdTumba  = trim($_GET['filterIdTumba']  ?? '');

// ----------------------------------------------------
// 5. Obtener lista de difuntos con filtros dinámicos
// ----------------------------------------------------
$difuntos = [];
$sql = "
    SELECT 
        id, nombre, apellidoPaterno, apellidoMaterno,
        fechaNacimiento, fechaDefuncion, Restos, imagen, idTumba
    FROM difuntos
    WHERE 1=1
";
if ($filterNombre !== '') {
    $v    = $conexion->real_escape_string($filterNombre);
    $sql .= " AND nombre LIKE '%$v%'";
}
if ($filterApeP !== '') {
    $v    = $conexion->real_escape_string($filterApeP);
    $sql .= " AND apellidoPaterno LIKE '%$v%'";
}
if ($filterApeM !== '') {
    $v    = $conexion->real_escape_string($filterApeM);
    $sql .= " AND apellidoMaterno LIKE '%$v%'";
}
if ($filterRestos !== '') {
    $v    = $conexion->real_escape_string($filterRestos);
    $sql .= " AND Restos LIKE '%$v%'";
}
if ($filterIdTumba !== '') {
    $v    = (int) $filterIdTumba;
    $sql .= " AND idTumba = $v";
}

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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Panel Admin - Difuntos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
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
    .foto-thumb { max-width: 60px; max-height: 60px; }
  </style>
</head>
<body>

<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <nav class="col-md-3 col-lg-2 sidebar">
      <div class="text-center mb-4">
        <img src="../img/logo.png" width="40" alt="Logo">
        <div class="text-gold fw-bold mt-2">Victorio's</div>
        <small>grave search</small>
      </div>
      <a href="admin-usuarios.php">Usuarios</a>
      <a href="admin-tumbas.php" >Tumbas</a>
      <a href="admin-difuntos.php" class="active">Difuntos</a>
      <a href="admin-ubicaciones.php">Ubicaciones</a>
      <a href="logout.php" class="text-danger mt-4">Cerrar Sesión</a>
    </nav>

    <!-- Contenido principal -->
    <main class="col-md-9 col-lg-10 p-4">
      <h3 class="mb-4">Administración de Difuntos</h3>

      <!-- Mensajes de éxito o error -->
      <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- ====================================== -->
      <!-- 1) Formulario de registro de difunto   -->
      <!-- ====================================== -->
      <form method="POST" enctype="multipart/form-data" class="row g-3 mb-4 bg-white p-3 rounded shadow">
        <input type="hidden" name="action" value="add">

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

        <!-- ===================== -->
        <!-- SELECT de ID Tumba   -->
        <!-- ===================== -->
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
        <!-- ===================== -->

        <div class="col-12">
          <button type="submit" class="btn btn-dark">Registrar Difunto</button>
        </div>
      </form>

      <!-- ====================================== -->
      <!-- 2) Panel de búsqueda                 -->
      <!-- ====================================== -->
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

      <!-- ====================================== -->
      <!-- 3) Tabla de difuntos                  -->
      <!-- ====================================== -->
      <div class="bg-white rounded shadow p-3">
        <h5 class="fw-semibold mb-3">Listado de Difuntos</h5>
        <div class="table-responsive">
          <table class="table table-hover align-middle text-center">
            <thead class="table-dark">
              <tr>
                <th>Nombre</th>
                <th>1° Apellido</th>
                <th>2° Apellido</th>
                <th>F. Nacimiento</th>
                <th>F. Defunción</th>
                <th>Restos</th>
                <th>Foto</th>
                <th>ID Tumba</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($difuntos)): ?>
                <tr>
                  <td colspan="9">No se encontraron difuntos.</td>
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
                         class="text-danger me-3"
                         onclick="return confirm('¿Eliminar este registro?')">
                        Eliminar
                      </a>
                      <button
                        class="btn btn-sm btn-outline-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#editModal<?= $d['id'] ?>">
                        Editar
                      </button>
                    </td>
                  </tr>

                  <!-- ================================== -->
                  <!-- Modal de edición para este difunto -->
                  <!-- ================================== -->
                  <div class="modal fade" id="editModal<?= $d['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $d['id'] ?>" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                      <div class="modal-content">
                        <form method="POST" enctype="multipart/form-data">
                          <input type="hidden" name="action" value="edit">
                          <input type="hidden" name="edit_id" value="<?= $d['id'] ?>">
                          <div class="modal-header">
                            <h5 class="modal-title" id="editModalLabel<?= $d['id'] ?>">Editar Difunto #<?= $d['id'] ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                          </div>
                          <div class="modal-body">
                            <div class="mb-3">
                              <label class="form-label">Nombre <span class="text-danger">*</span></label>
                              <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($d['nombre']) ?>" required>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Primer apellido <span class="text-danger">*</span></label>
                              <input type="text" name="apellidoPaterno" class="form-control" value="<?= htmlspecialchars($d['apellidoPaterno']) ?>" required>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Segundo apellido</label>
                              <input type="text" name="apellidoMaterno" class="form-control" value="<?= htmlspecialchars($d['apellidoMaterno']) ?>">
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Fecha nacimiento</label>
                              <input type="date" name="fechaNacimiento" class="form-control" value="<?= htmlspecialchars($d['fechaNacimiento']) ?>">
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Fecha defunción</label>
                              <input type="date" name="fechaDefuncion" class="form-control" value="<?= htmlspecialchars($d['fechaDefuncion']) ?>">
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Tipo de restos</label>
                              <select name="restos" class="form-select">
                                <option value="" <?= $d['Restos'] === '' ? 'selected' : '' ?>>Seleccionar</option>
                                <option value="Cuerpo" <?= $d['Restos'] === 'Cuerpo' ? 'selected' : '' ?>>Cuerpo</option>
                                <option value="Cenizas" <?= $d['Restos'] === 'Cenizas' ? 'selected' : '' ?>>Cenizas</option>
                              </select>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Foto actual</label>
                              <div>
                                <?php if ($d['imagen'] && file_exists($uploadDir . $d['imagen'])): ?>
                                  <img src="../photos/<?= htmlspecialchars($d['imagen']) ?>" class="foto-thumb mb-2" alt="Foto">
                                <?php else: ?>
                                  <span>Sin foto</span>
                                <?php endif; ?>
                              </div>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Cambiar foto</label>
                              <input type="file" name="imagen" accept="image/*" class="form-control">
                              <small class="form-text text-muted">Si no seleccionas ninguna, se conservará la foto actual.</small>
                            </div>

                            <!-- ===================== -->
                            <!-- SELECT de ID Tumba   -->
                            <!-- ===================== -->
                            <div class="mb-3">
                              <label class="form-label">ID Tumba <span class="text-danger">*</span></label>
                              <select name="idTumba" class="form-select" required>
                                <option value="">Seleccionar tumba</option>
                                <?php foreach ($allTumbas as $t): ?>
                                  <option value="<?= $t['id'] ?>"
                                    <?= $t['id'] === (int)$d['idTumba'] ? 'selected' : '' ?>>
                                    ID <?= $t['id'] ?> &mdash;
                                    Mza <?= htmlspecialchars($t['manzana']) ?>,
                                    Cdr <?= htmlspecialchars($t['cuadro']) ?>,
                                    Fila <?= htmlspecialchars($t['fila']) ?>,
                                    Núm <?= htmlspecialchars($t['numero']) ?>
                                  </option>
                                <?php endforeach; ?>
                              </select>
                            </div>
                            <!-- ===================== -->

                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar cambios</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                  <!-- / Fin Modal -->
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