<?php
session_start();
require '../php/conection.php';

/**
 * Valida una coordenada en formato:
 *  DD°MM'SS.S"S [N|S]  DDD°MM'SS.S"S [E|W]
 * Ejemplo: 24°09'10.0"N 110°14'47.5"W
 */
function isValidCoordinate(string $coord): bool {
    $pattern = '/^(?:[0-8]?\d|90)°(?:[0-5]\d)\'(?:[0-5]\d(?:\.\d+)?)\"(?:[NS])\s+(?:1[0-7]\d|[0-9]?\d)°(?:[0-5]\d)\'(?:[0-5]\d(?:\.\d+)?)\"(?:[EW])$/';
    return (bool)preg_match($pattern, $coord);
}

// 0) Determinamos la pestaña que debe quedar activa
$activeTab = 'manzanas';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (strpos($action, 'manzana') !== false) {
        $activeTab = 'manzanas';
    } elseif (strpos($action, 'fila') !== false) {
        $activeTab = 'filas';
    } elseif (strpos($action, 'cuadro') !== false) {
        $activeTab = 'cuadros';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        isset($_GET['filterFilaNumero']) ||
        isset($_GET['filterFilaCordenada']) ||
        isset($_GET['filterFilaManzana'])
    ) {
        $activeTab = 'filas';
    } elseif (
        isset($_GET['filterCuaNumero']) ||
        isset($_GET['filterCuaCordenada']) ||
        isset($_GET['filterCuaFila'])
    ) {
        $activeTab = 'cuadros';
    }
}

// 1) Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: admin-login.php");
    exit();
}

$error   = '';
$success = '';

// 2) Proceso de formularios (alta y edición)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ---------------------------------------
    // 2.1. ALTA / EDICIÓN DE MANZANA
    // ---------------------------------------
    if ($action === 'add-manzana' || $action === 'edit-manzana') {
        $numero       = trim($_POST['man_numero']       ?? '');
        $cord1        = trim($_POST['man_cordenada1']   ?? '');
        $cord2        = trim($_POST['man_cordenada2']   ?? '');
        $cord3        = trim($_POST['man_cordenada3']   ?? '');
        $cord4        = trim($_POST['man_cordenada4']   ?? '');

        // 2.1.1. Validar formato de coordenadas (si no están vacías)
        foreach (['cord1' => $cord1, 'cord2' => $cord2, 'cord3' => $cord3, 'cord4' => $cord4] as $label => $c) {
            if ($c !== '' && !isValidCoordinate($c)) {
                $error = "Formato de coordenada inválido en {$label}. Use algo como: 24°09'10.0\"N 110°14'47.5\"W";
                break;
            }
        }

        if ($error === '') {
            if ($numero === '') {
                $error = 'El campo <strong>número</strong> es obligatorio para Manzana.';
            } else {
                if ($action === 'add-manzana') {
                    $stmt = $conexion->prepare("
                        INSERT INTO manzana (
                            numero, cordenada1, cordenada2, cordenada3, cordenada4
                        ) VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("sssss", $numero, $cord1, $cord2, $cord3, $cord4);
                    if ($stmt->execute()) {
                        $success = 'Manzana creada exitosamente.';
                    } else {
                        $error = 'Error al crear Manzana: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    // EDITAR MANZANA
                    $edit_id = (int)($_POST['man_edit_id'] ?? 0);
                    if ($edit_id <= 0) {
                        $error = 'ID de Manzana inválido para edición.';
                    } else {
                        $stmt = $conexion->prepare("
                            UPDATE manzana
                            SET
                                numero     = ?,
                                cordenada1 = ?,
                                cordenada2 = ?,
                                cordenada3 = ?,
                                cordenada4 = ?
                            WHERE id = ?
                        ");
                        $stmt->bind_param("sssssi", $numero, $cord1, $cord2, $cord3, $cord4, $edit_id);
                        if ($stmt->execute()) {
                            $success = 'Manzana actualizada correctamente.';
                        } else {
                            $error = 'Error al actualizar Manzana: ' . $stmt->error;
                        }
                        $stmt->close();
                    }
                }
            }
        }
    }

    // ---------------------------------------
    // 2.2. ALTA / EDICIÓN DE FILA
    // ---------------------------------------
    if ($action === 'add-fila' || $action === 'edit-fila') {
        $numero    = trim($_POST['fila_numero']       ?? '');
        $cord1     = trim($_POST['fila_cordenada1']   ?? '');
        $cord2     = trim($_POST['fila_cordenada2']   ?? '');
        $cord3     = trim($_POST['fila_cordenada3']   ?? '');
        $cord4     = trim($_POST['fila_cordenada4']   ?? '');
        $idManzana = (int)($_POST['fila_idManzana']  ?? 0);

        // 2.2.1. Validar formato de coordenadas
        foreach (['cord1' => $cord1, 'cord2' => $cord2, 'cord3' => $cord3, 'cord4' => $cord4] as $label => $c) {
            if ($c !== '' && !isValidCoordinate($c)) {
                $error = "Formato de coordenada inválido en {$label}. Use algo como: 24°09'10.0\"N 110°14'47.5\"W";
                break;
            }
        }

        if ($error === '') {
            if ($numero === '' || $idManzana <= 0) {
                $error = 'El <strong>número</strong> y la <strong>Manzana</strong> son obligatorios para Fila.';
            } else {
                if ($action === 'add-fila') {
                    $stmt = $conexion->prepare("
                        INSERT INTO fila (
                            numero, cordenada1, cordenada2, cordenada3, cordenada4, idManzana
                        ) VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("sssssi", $numero, $cord1, $cord2, $cord3, $cord4, $idManzana);
                    if ($stmt->execute()) {
                        $success = 'Fila creada exitosamente.';
                    } else {
                        $error = 'Error al crear Fila: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    // EDITAR FILA
                    $edit_id = (int)($_POST['fila_edit_id'] ?? 0);
                    if ($edit_id <= 0) {
                        $error = 'ID de Fila inválido para edición.';
                    } else {
                        $stmt = $conexion->prepare("
                            UPDATE fila
                            SET
                                numero     = ?,
                                cordenada1 = ?,
                                cordenada2 = ?,
                                cordenada3 = ?,
                                cordenada4 = ?,
                                idManzana  = ?
                            WHERE id = ?
                        ");
                        $stmt->bind_param("ssssssi", $numero, $cord1, $cord2, $cord3, $cord4, $idManzana, $edit_id);
                        if ($stmt->execute()) {
                            $success = 'Fila actualizada correctamente.';
                        } else {
                            $error = 'Error al actualizar Fila: ' . $stmt->error;
                        }
                        $stmt->close();
                    }
                }
            }
        }
    }

    // ---------------------------------------
    // 2.3. ALTA / EDICIÓN DE CUADRO
    // ---------------------------------------
    if ($action === 'add-cuadro' || $action === 'edit-cuadro') {
        $numero = trim($_POST['cua_numero']       ?? '');
        $cord1  = trim($_POST['cua_cordenada1']   ?? '');
        $cord2  = trim($_POST['cua_cordenada2']   ?? '');
        $cord3  = trim($_POST['cua_cordenada3']   ?? '');
        $cord4  = trim($_POST['cua_cordenada4']   ?? '');
        $idFila = (int)($_POST['cua_idFila']     ?? 0);

        // 2.3.1. Validar formato de coordenadas
        foreach (['cord1' => $cord1, 'cord2' => $cord2, 'cord3' => $cord3, 'cord4' => $cord4] as $label => $c) {
            if ($c !== '' && !isValidCoordinate($c)) {
                $error = "Formato de coordenada inválido en {$label}. Use algo como: 24°09'10.0\"N 110°14'47.5\"W";
                break;
            }
        }

        if ($error === '') {
            if ($numero === '' || $idFila <= 0) {
                $error = 'El <strong>número</strong> y la <strong>Fila</strong> son obligatorios para Cuadro.';
            } else {
                if ($action === 'add-cuadro') {
                    $stmt = $conexion->prepare("
                        INSERT INTO cuadro (
                            numero, cordenada1, cordenada2, cordenada3, cordenada4, idFila
                        ) VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("sssssi", $numero, $cord1, $cord2, $cord3, $cord4, $idFila);
                    if ($stmt->execute()) {
                        $success = 'Cuadro creado exitosamente.';
                    } else {
                        $error = 'Error al crear Cuadro: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    // EDITAR CUADRO
                    $edit_id = (int)($_POST['cua_edit_id'] ?? 0);
                    if ($edit_id <= 0) {
                        $error = 'ID de Cuadro inválido para edición.';
                    } else {
                        $stmt = $conexion->prepare("
                            UPDATE cuadro
                            SET
                                numero     = ?,
                                cordenada1 = ?,
                                cordenada2 = ?,
                                cordenada3 = ?,
                                cordenada4 = ?,
                                idFila     = ?
                            WHERE id = ?
                        ");
                        $stmt->bind_param("ssssssi", $numero, $cord1, $cord2, $cord3, $cord4, $idFila, $edit_id);
                        if ($stmt->execute()) {
                            $success = 'Cuadro actualizado correctamente.';
                        } else {
                            $error = 'Error al actualizar Cuadro: ' . $stmt->error;
                        }
                        $stmt->close();
                    }
                }
            }
        }
    }
}

// ---------------------------------------
// 3) Proceso de eliminación (GET)
// ---------------------------------------
if (isset($_GET['eliminar-manzana'])) {
    $elim_id = (int)$_GET['eliminar-manzana'];
    if ($elim_id > 0) {
        $stmt = $conexion->prepare("DELETE FROM manzana WHERE id = ?");
        $stmt->bind_param("i", $elim_id);
        $stmt->execute();
        $stmt->close();
        $success = 'Manzana eliminada.';
    }
}

if (isset($_GET['eliminar-fila'])) {
    $elim_id = (int)$_GET['eliminar-fila'];
    if ($elim_id > 0) {
        $stmt = $conexion->prepare("DELETE FROM fila WHERE id = ?");
        $stmt->bind_param("i", $elim_id);
        $stmt->execute();
        $stmt->close();
        $success = 'Fila eliminada.';
    }
}

if (isset($_GET['eliminar-cuadro'])) {
    $elim_id = (int)$_GET['eliminar-cuadro'];
    if ($elim_id > 0) {
        $stmt = $conexion->prepare("DELETE FROM cuadro WHERE id = ?");
        $stmt->bind_param("i", $elim_id);
        $stmt->execute();
        $stmt->close();
        $success = 'Cuadro eliminado.';
    }
}

// ---------------------------------------
// 4) Preparar filtros GET para cada sección
// ---------------------------------------
$filterManNumero    = trim($_GET['filterManNumero']    ?? '');
$filterManCordenada = trim($_GET['filterManCordenada'] ?? '');

$filterFilaNumero    = trim($_GET['filterFilaNumero']    ?? '');
$filterFilaCordenada = trim($_GET['filterFilaCordenada'] ?? '');
$filterFilaManzana   = trim($_GET['filterFilaManzana']   ?? '');

$filterCuaNumero    = trim($_GET['filterCuaNumero']    ?? '');
$filterCuaCordenada = trim($_GET['filterCuaCordenada'] ?? '');
$filterCuaFila      = trim($_GET['filterCuaFila']      ?? '');

// ---------------------------------------
// 5) Obtener lista de Manzanas con filtros
// ---------------------------------------
$manzanas = [];
$sqlM = "SELECT id, numero, cordenada1, cordenada2, cordenada3, cordenada4
         FROM manzana
         WHERE 1=1";
if ($filterManNumero !== '') {
    $v = $conexion->real_escape_string($filterManNumero);
    $sqlM .= " AND numero LIKE '%$v%'";
}
if ($filterManCordenada !== '') {
    $v = $conexion->real_escape_string($filterManCordenada);
    $sqlM .= " AND (
        cordenada1 LIKE '%$v%' OR
        cordenada2 LIKE '%$v%' OR
        cordenada3 LIKE '%$v%' OR
        cordenada4 LIKE '%$v%'
    )";
}
$resM = $conexion->query($sqlM);
if ($resM) {
    $manzanas = $resM->fetch_all(MYSQLI_ASSOC);
    $resM->close();
}

// ---------------------------------------
// 6) Obtener lista de Filas con filtros
// ---------------------------------------
$filas = [];
$sqlF = "SELECT id, numero, cordenada1, cordenada2, cordenada3, cordenada4, idManzana
         FROM fila
         WHERE 1=1";
if ($filterFilaNumero !== '') {
    $v = $conexion->real_escape_string($filterFilaNumero);
    $sqlF .= " AND numero LIKE '%$v%'";
}
if ($filterFilaCordenada !== '') {
    $v = $conexion->real_escape_string($filterFilaCordenada);
    $sqlF .= " AND (
        cordenada1 LIKE '%$v%' OR
        cordenada2 LIKE '%$v%' OR
        cordenada3 LIKE '%$v%' OR
        cordenada4 LIKE '%$v%'
    )";
}
if ($filterFilaManzana !== '') {
    $vid = (int)$filterFilaManzana;
    $sqlF .= " AND idManzana = $vid";
}
$resF = $conexion->query($sqlF);
if ($resF) {
    $filas = $resF->fetch_all(MYSQLI_ASSOC);
    $resF->close();
}

// ---------------------------------------
// 7) Obtener lista de Cuadros con filtros
// ---------------------------------------
$cuadros = [];
$sqlC = "SELECT id, numero, cordenada1, cordenada2, cordenada3, cordenada4, idFila
         FROM cuadro
         WHERE 1=1";
if ($filterCuaNumero !== '') {
    $v = $conexion->real_escape_string($filterCuaNumero);
    $sqlC .= " AND numero LIKE '%$v%'";
}
if ($filterCuaCordenada !== '') {
    $v = $conexion->real_escape_string($filterCuaCordenada);
    $sqlC .= " AND (
        cordenada1 LIKE '%$v%' OR
        cordenada2 LIKE '%$v%' OR
        cordenada3 LIKE '%$v%' OR
        cordenada4 LIKE '%$v%'
    )";
}
if ($filterCuaFila !== '') {
    $vid = (int)$filterCuaFila;
    $sqlC .= " AND idFila = $vid";
}
$resC = $conexion->query($sqlC);
if ($resC) {
    $cuadros = $resC->fetch_all(MYSQLI_ASSOC);
    $resC->close();
}

// ---------------------------------------
// 8) Obtener listas para los dropdowns
//    - Para Filas: listar todas las manzanas (id/numero)
//    - Para Cuadros: listar todas las filas (id/numero + mostrar manzana en texto)
// ---------------------------------------
$allManzanas = [];
$qr = $conexion->query("SELECT id, numero FROM manzana");
if ($qr) {
    $allManzanas = $qr->fetch_all(MYSQLI_ASSOC);
    $qr->close();
}

$allFilas = [];
$qr2 = $conexion->query("
    SELECT f.id, f.numero, f.idManzana, m.numero AS manzana_num 
    FROM fila f
    LEFT JOIN manzana m ON f.idManzana = m.id
    ORDER BY m.numero, f.numero
");
if ($qr2) {
    $allFilas = $qr2->fetch_all(MYSQLI_ASSOC);
    $qr2->close();
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Panel Admin - Ubicaciones (Manzanas, Filas, Cuadros)</title>
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
    .tab-pane { padding-top: 1rem; }

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
      <a href="admin-difuntos.php" class="text-white mb-2 ">Difuntos</a>
      <a href="admin-ubicaciones.php" class="text-white mb-2 active">Ubicaciones</a>
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
          <a href="admin-difuntos.php" class="text-white mb-2">Difuntos</a>
          <a href="admin-ubicaciones.php" class="text-white mb-2 active">Ubicaciones</a>
          <a href="logout.php" class="text-danger mt-4">Cerrar Sesión</a>
        </div>
      </div>
    </div>


    <!-- Contenido principal -->
    <main class="col-md-9 col-lg-10 p-4">
      <h3 class="mb-4">Administración de Ubicaciones</h3>

      <!-- Mensajes -->
      <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- Nav tabs -->
      <ul class="nav nav-tabs mb-4" id="tabUbicaciones" role="tablist">
        <li class="nav-item" role="presentation">
          <button
            class="nav-link <?= $activeTab === 'manzanas' ? 'active' : '' ?>"
            id="tab-manzanas"
            data-bs-toggle="tab"
            data-bs-target="#manzanas"
            type="button" role="tab"
            aria-controls="manzanas"
            aria-selected="<?= $activeTab === 'manzanas' ? 'true' : 'false' ?>">
            Manzanas
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button
            class="nav-link <?= $activeTab === 'filas' ? 'active' : '' ?>"
            id="tab-filas"
            data-bs-toggle="tab"
            data-bs-target="#filas"
            type="button" role="tab"
            aria-controls="filas"
            aria-selected="<?= $activeTab === 'filas' ? 'true' : 'false' ?>">
            Filas
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button
            class="nav-link <?= $activeTab === 'cuadros' ? 'active' : '' ?>"
            id="tab-cuadros"
            data-bs-toggle="tab"
            data-bs-target="#cuadros"
            type="button" role="tab"
            aria-controls="cuadros"
            aria-selected="<?= $activeTab === 'cuadros' ? 'true' : 'false' ?>">
            Cuadros
          </button>
        </li>
      </ul>

      <div class="tab-content">
        <!-- =============================================== -->
        <!-- 1) PESTAÑA MANZANAS                            -->
        <!-- =============================================== -->
        <div
          class="tab-pane fade <?= $activeTab === 'manzanas' ? 'show active' : '' ?>"
          id="manzanas"
          role="tabpanel"
          aria-labelledby="tab-manzanas">

          <!-- 1.1 Formulario ALTA Manzana -->
          <form method="POST" class="row g-3 mb-4 bg-white p-3 rounded shadow">
            <input type="hidden" name="action" value="add-manzana">
            <div class="col-md-3">
              <label class="form-label">Número <span class="text-danger">*</span></label>
              <input type="text" name="man_numero" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Cordenada 1</label>
              <input type="text" name="man_cordenada1" class="form-control" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
               <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                target="_blank"
                class="d-block text-center text-decoration-none mt-1 small text-primary">
                🔍 Buscar en mapa
              </a>
            </div>
            <div class="col-md-3">
              <label class="form-label">Cordenada 2</label>
              <input type="text" name="man_cordenada2" class="form-control" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
              <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                target="_blank"
                class="d-block text-center text-decoration-none mt-1 small text-primary">
                🔍 Buscar en mapa
              </a>
            </div>
            <div class="col-md-3">
              <label class="form-label">Cordenada 3</label>
              <input type="text" name="man_cordenada3" class="form-control" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
              <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                target="_blank"
                class="d-block text-center text-decoration-none mt-1 small text-primary">
                🔍 Buscar en mapa
              </a>
            </div>
            <div class="col-md-3">
              <label class="form-label">Cordenada 4</label>
              <input type="text" name="man_cordenada4" class="form-control" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
              <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                target="_blank"
                class="d-block text-center text-decoration-none mt-1 small text-primary">
                🔍 Buscar en mapa
              </a>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-dark">Registrar Manzana</button>
            </div>
          </form>

          <!-- 1.2 Buscador Manzanas -->
          <div class="bg-white rounded shadow p-3 mb-4">
            <h5 class="fw-semibold mb-3">Buscar Manzanas</h5>
            <form method="GET" class="row g-3">
              <div class="col-md-3">
                <label class="form-label">Número</label>
                <input type="text" name="filterManNumero" value="<?= htmlspecialchars($filterManNumero) ?>" class="form-control" placeholder="Ej. 105">
              </div>
              <div class="col-md-3">
                <label class="form-label">Cualquier Cordenada</label>
                <input type="text" name="filterManCordenada" value="<?= htmlspecialchars($filterManCordenada) ?>" class="form-control" placeholder="Ej. 24°09'">
              </div>
              <div class="col-md-6 text-end">
                <button type="reset" class="btn btn-outline-secondary">Limpiar</button>
                <button type="submit" class="btn btn-gold">Buscar</button>
              </div>
            </form>
          </div>

          <!-- 1.3 Tabla Manzanas -->
          <div class="bg-white rounded shadow p-3">
            <h5 class="fw-semibold mb-3">Listado de Manzanas</h5>
            <div class="table-responsive">
              <table class="table table-hover align-middle text-center">
                <thead class="table-dark">
                  <tr>
                    <th>ID</th>
                    <th>Número</th>
                    <th>C1</th>
                    <th>C2</th>
                    <th>C3</th>
                    <th>C4</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($manzanas)): ?>
                    <tr>
                      <td colspan="7">No se encontraron manzanas.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($manzanas as $m): ?>
                      <tr>
                        <td><?= htmlspecialchars($m['id']) ?></td>
                        <td><?= htmlspecialchars($m['numero']) ?></td>
                        <td><?= htmlspecialchars($m['cordenada1']) ?></td>
                        <td><?= htmlspecialchars($m['cordenada2']) ?></td>
                        <td><?= htmlspecialchars($m['cordenada3']) ?></td>
                        <td><?= htmlspecialchars($m['cordenada4']) ?></td>
                        <td>
                          <a href="?eliminar-manzana=<?= $m['id'] ?>"
                             class="text-danger me-3"
                             onclick="return confirm('¿Eliminar esta manzana?')">
                            Eliminar
                          </a>
                          <button
                            class="btn btn-sm btn-outline-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#editManzanaModal<?= $m['id'] ?>">
                            Editar
                          </button>
                        </td>
                      </tr>

                      <!-- Modal Edición Manzana -->
                      <div class="modal fade" id="editManzanaModal<?= $m['id'] ?>" tabindex="-1" aria-labelledby="editManzanaLabel<?= $m['id'] ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                          <div class="modal-content">
                            <form method="POST">
                              <input type="hidden" name="action" value="edit-manzana">
                              <input type="hidden" name="man_edit_id" value="<?= $m['id'] ?>">
                              <div class="modal-header">
                                <h5 class="modal-title" id="editManzanaLabel<?= $m['id'] ?>">Editar Manzana #<?= $m['id'] ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                              </div>
                              <div class="modal-body">
                                <div class="mb-3">
                                  <label class="form-label">Número <span class="text-danger">*</span></label>
                                  <input type="text" name="man_numero" class="form-control" value="<?= htmlspecialchars($m['numero']) ?>" required>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label">Cordenada 1</label>
                                  <input type="text" name="man_cordenada1" class="form-control" value="<?= htmlspecialchars($m['cordenada1']) ?>" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
                                  <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                                    target="_blank"
                                    class="d-block text-center text-decoration-none mt-1 small text-primary">
                                    🔍 Buscar en mapa
                                  </a>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label">Cordenada 2</label>
                                  <input type="text" name="man_cordenada2" class="form-control" value="<?= htmlspecialchars($m['cordenada2']) ?>" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
                                  <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                                    target="_blank"
                                    class="d-block text-center text-decoration-none mt-1 small text-primary">
                                    🔍 Buscar en mapa
                                  </a>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label">Cordenada 3</label>
                                  <input type="text" name="man_cordenada3" class="form-control" value="<?= htmlspecialchars($m['cordenada3']) ?>" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
                                  <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                                    target="_blank"
                                    class="d-block text-center text-decoration-none mt-1 small text-primary">
                                    🔍 Buscar en mapa
                                  </a>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label">Cordenada 4</label>
                                  <input type="text" name="man_cordenada4" class="form-control" value="<?= htmlspecialchars($m['cordenada4']) ?>" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
                                  <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                                    target="_blank"
                                    class="d-block text-center text-decoration-none mt-1 small text-primary">
                                    🔍 Buscar en mapa
                                  </a>
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
                      <!-- /Fin Modal Edición Manzana -->

                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <!-- /FIN PESTAÑA MANZANAS -->

        <!-- =============================================== -->
        <!-- 2) PESTAÑA FILAS                               -->
        <!-- =============================================== -->
        <div
          class="tab-pane fade <?= $activeTab === 'filas' ? 'show active' : '' ?>"
          id="filas"
          role="tabpanel"
          aria-labelledby="tab-filas">

          <!-- 2.1 Formulario ALTA Fila -->
          <form method="POST" class="row g-3 mb-4 bg-white p-3 rounded shadow">
            <input type="hidden" name="action" value="add-fila">
            <div class="col-md-3">
              <label class="form-label">Número <span class="text-danger">*</span></label>
              <input type="text" name="fila_numero" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Cordenada 1</label>
              <input type="text" name="fila_cordenada1" class="form-control" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
              <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                target="_blank"
                class="d-block text-center text-decoration-none mt-1 small text-primary">
                🔍 Buscar en mapa
              </a>
            </div>
            <div class="col-md-3">
              <label class="form-label">Cordenada 2</label>
              <input type="text" name="fila_cordenada2" class="form-control" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
              <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                target="_blank"
                class="d-block text-center text-decoration-none mt-1 small text-primary">
                🔍 Buscar en mapa
              </a>
            </div>
            <div class="col-md-3">
              <label class="form-label">Cordenada 3</label>
              <input type="text" name="fila_cordenada3" class="form-control" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
              <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                target="_blank"
                class="d-block text-center text-decoration-none mt-1 small text-primary">
                🔍 Buscar en mapa
              </a>
            </div>
            <div class="col-md-3">
              <label class="form-label">Cordenada 4</label>
              <input type="text" name="fila_cordenada4" class="form-control" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
              <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                target="_blank"
                class="d-block text-center text-decoration-none mt-1 small text-primary">
                🔍 Buscar en mapa
              </a>
            </div>
            <div class="col-md-3">
              <label class="form-label">Manzana <span class="text-danger">*</span></label>
              <select name="fila_idManzana" class="form-select" required>
                <option value="">Seleccionar</option>
                <?php foreach ($allManzanas as $m): ?>
                  <option value="<?= $m['id'] ?>">Manzana <?= htmlspecialchars($m['numero']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-dark">Registrar Fila</button>
            </div>
          </form>

          <!-- 2.2 Buscador Filas -->
          <div class="bg-white rounded shadow p-3 mb-4">
            <h5 class="fw-semibold mb-3">Buscar Filas</h5>
            <form method="GET" class="row g-3">
              <div class="col-md-3">
                <label class="form-label">Número</label>
                <input type="text" name="filterFilaNumero" value="<?= htmlspecialchars($filterFilaNumero) ?>" class="form-control" placeholder="Ej. 0129">
              </div>
              <div class="col-md-3">
                <label class="form-label">Cualquier Cordenada</label>
                <input type="text" name="filterFilaCordenada" value="<?= htmlspecialchars($filterFilaCordenada) ?>" class="form-control" placeholder="Ej. 24°09'">
              </div>
              <div class="col-md-3">
                <label class="form-label">Manzana (ID)</label>
                <select name="filterFilaManzana" class="form-select">
                  <option value="">Todas</option>
                  <?php foreach ($allManzanas as $m): ?>
                    <option value="<?= $m['id'] ?>" <?= $filterFilaManzana == $m['id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($m['numero']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3 text-end">
                <button type="reset" class="btn btn-outline-secondary">Limpiar</button>
                <button type="submit" class="btn btn-gold">Buscar</button>
              </div>
            </form>
          </div>

          <div class="bg-white rounded shadow p-3">
  <h5 class="fw-semibold mb-3">Listado de Filas</h5>
  <div class="table-responsive">
    <table class="table table-striped table-hover table-sm align-middle text-center text-nowrap">
      <thead class="table-dark">
        <tr>
          <th class="px-2">ID</th>
          <th class="px-2">Número</th>
          <th class="px-2">C1</th>
          <th class="px-2">C2</th>
          <th class="px-2">C3</th>
          <th class="px-2">C4</th>
          <th class="px-2">ID Manzana</th>
          <th class="px-2">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($filas)): ?>
          <tr>
            <td colspan="8">No se encontraron filas.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($filas as $f): ?>
            <tr>
              <td><?= htmlspecialchars($f['id']) ?></td>
              <td><?= htmlspecialchars($f['numero']) ?></td>
              <td><?= htmlspecialchars($f['cordenada1']) ?></td>
              <td><?= htmlspecialchars($f['cordenada2']) ?></td>
              <td><?= htmlspecialchars($f['cordenada3']) ?></td>
              <td><?= htmlspecialchars($f['cordenada4']) ?></td>
              <td><?= htmlspecialchars($f['idManzana']) ?></td>
              <td>
                <a href="?eliminar-fila=<?= $f['id'] ?>"
                   class="text-danger me-3"
                   onclick="return confirm('¿Eliminar esta fila?')">
                  Eliminar
                </a>
                <button
                  class="btn btn-sm btn-outline-primary"
                  data-bs-toggle="modal"
                  data-bs-target="#editFilaModal<?= $f['id'] ?>">
                  Editar
                </button>
              </td>
            </tr>

                      <!-- Modal Edición Fila -->
                      <div class="modal fade" id="editFilaModal<?= $f['id'] ?>" tabindex="-1" aria-labelledby="editFilaLabel<?= $f['id'] ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                          <div class="modal-content">
                            <form method="POST">
                              <input type="hidden" name="action" value="edit-fila">
                              <input type="hidden" name="fila_edit_id" value="<?= $f['id'] ?>">
                              <div class="modal-header">
                                <h5 class="modal-title" id="editFilaLabel<?= $f['id'] ?>">Editar Fila #<?= $f['id'] ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                              </div>
                              <div class="modal-body">
                                <div class="mb-3">
                                  <label class="form-label">Número <span class="text-danger">*</span></label>
                                  <input type="text" name="fila_numero" class="form-control" value="<?= htmlspecialchars($f['numero']) ?>" required>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label">Cordenada 1</label>
                                  <input type="text" name="fila_cordenada1" class="form-control" value="<?= htmlspecialchars($f['cordenada1']) ?>" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
                                  <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                                    target="_blank"
                                    class="d-block text-center text-decoration-none mt-1 small text-primary">
                                    🔍 Buscar en mapa
                                  </a>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label">Cordenada 2</label>
                                  <input type="text" name="fila_cordenada2" class="form-control" value="<?= htmlspecialchars($f['cordenada2']) ?>" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
                                   <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                                    target="_blank"
                                    class="d-block text-center text-decoration-none mt-1 small text-primary">
                                    🔍 Buscar en mapa
                                  </a>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label">Cordenada 3</label>
                                  <input type="text" name="fila_cordenada3" class="form-control" value="<?= htmlspecialchars($f['cordenada3']) ?>" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
                                   <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                                    target="_blank"
                                    class="d-block text-center text-decoration-none mt-1 small text-primary">
                                    🔍 Buscar en mapa
                                  </a>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label">Cordenada 4</label>
                                  <input type="text" name="fila_cordenada4" class="form-control" value="<?= htmlspecialchars($f['cordenada4']) ?>" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
                                   <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                                    target="_blank"
                                    class="d-block text-center text-decoration-none mt-1 small text-primary">
                                    🔍 Buscar en mapa
                                  </a>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label">Manzana <span class="text-danger">*</span></label>
                                  <select name="fila_idManzana" class="form-select" required>
                                    <option value="">Seleccionar</option>
                                    <?php foreach ($allManzanas as $m): ?>
                                      <option value="<?= $m['id'] ?>" <?= $m['id'] == $f['idManzana'] ? 'selected' : '' ?>>
                                        Manzana <?= htmlspecialchars($m['numero']) ?>
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
                      <!-- /Fin Modal Edición Fila -->

                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <!-- /FIN PESTAÑA FILAS -->

        <!-- =============================================== -->
        <!-- 3) PESTAÑA CUADROS                            -->
        <!-- =============================================== -->
        <div
          class="tab-pane fade <?= $activeTab === 'cuadros' ? 'show active' : '' ?>"
          id="cuadros"
          role="tabpanel"
          aria-labelledby="tab-cuadros">

          <!-- 3.1 Formulario ALTA Cuadro -->
          <form method="POST" class="row g-3 mb-4 bg-white p-3 rounded shadow">
            <input type="hidden" name="action" value="add-cuadro">
            <div class="col-md-3">
              <label class="form-label">Número <span class="text-danger">*</span></label>
              <input type="text" name="cua_numero" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Cordenada 1</label>
              <input type="text" name="cua_cordenada1" class="form-control" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
               <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                target="_blank"
                class="d-block text-center text-decoration-none mt-1 small text-primary">
                🔍 Buscar en mapa
              </a>
            </div>
            <div class="col-md-3">
              <label class="form-label">Cordenada 2</label>
              <input type="text" name="cua_cordenada2" class="form-control" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
              <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                target="_blank"
                class="d-block text-center text-decoration-none mt-1 small text-primary">
                🔍 Buscar en mapa
              </a>
            </div>
            <div class="col-md-3">
              <label class="form-label">Cordenada 3</label>
              <input type="text" name="cua_cordenada3" class="form-control" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
              <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                target="_blank"
                class="d-block text-center text-decoration-none mt-1 small text-primary">
                🔍 Buscar en mapa
              </a>
            </div>
            <div class="col-md-3">
              <label class="form-label">Cordenada 4</label>
              <input type="text" name="cua_cordenada4" class="form-control" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
              <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                target="_blank"
                class="d-block text-center text-decoration-none mt-1 small text-primary">
                🔍 Buscar en mapa
              </a>
            </div>
            <div class="col-md-3">
              <label class="form-label">Fila <span class="text-danger">*</span></label>
              <select name="cua_idFila" class="form-select" required>
                <option value="">Seleccionar</option>
                <?php foreach ($allFilas as $frow): ?>
                  <option value="<?= $frow['id'] ?>">
                    Fila <?= htmlspecialchars($frow['numero']) ?> (Manzana <?= htmlspecialchars($frow['manzana_num']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-dark">Registrar Cuadro</button>
            </div>
          </form>

          <!-- 3.2 Buscador Cuadros -->
          <div class="bg-white rounded shadow p-3 mb-4">
            <h5 class="fw-semibold mb-3">Buscar Cuadros</h5>
            <form method="GET" class="row g-3">
              <div class="col-md-3">
                <label class="form-label">Número</label>
                <input type="text" name="filterCuaNumero" value="<?= htmlspecialchars($filterCuaNumero) ?>" class="form-control" placeholder="Ej. 1004">
              </div>
              <div class="col-md-3">
                <label class="form-label">Cualquier Cordenada</label>
                <input type="text" name="filterCuaCordenada" value="<?= htmlspecialchars($filterCuaCordenada) ?>" class="form-control" placeholder="Ej. 24°09'">
              </div>
              <div class="col-md-3">
                <label class="form-label">Fila (ID)</label>
                <select name="filterCuaFila" class="form-select">
                  <option value="">Todas</option>
                  <?php foreach ($allFilas as $frow): ?>
                    <option value="<?= $frow['id'] ?>" <?= $filterCuaFila == $frow['id'] ? 'selected' : '' ?>>
                      Fila <?= htmlspecialchars($frow['numero']) ?> (M <?= htmlspecialchars($frow['manzana_num']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3 text-end">
                <button type="reset" class="btn btn-outline-secondary">Limpiar</button>
                <button type="submit" class="btn btn-gold">Buscar</button>
              </div>
            </form>
          </div>

          <!-- 3.3 Tabla Cuadros -->
          <div class="bg-white rounded shadow p-3">
            <h5 class="fw-semibold mb-3">Listado de Cuadros</h5>
            <div class="table-responsive">
              <table class="table table-hover align-middle text-center">
                <thead class="table-dark">
                  <tr>
                    <th>ID</th>
                    <th>Número</th>
                    <th>C1</th>
                    <th>C2</th>
                    <th>C3</th>
                    <th>C4</th>
                    <th>ID Fila</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($cuadros)): ?>
                    <tr>
                      <td colspan="8">No se encontraron cuadros.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($cuadros as $c): ?>
                      <tr>
                        <td><?= htmlspecialchars($c['id']) ?></td>
                        <td><?= htmlspecialchars($c['numero']) ?></td>
                        <td><?= htmlspecialchars($c['cordenada1']) ?></td>
                        <td><?= htmlspecialchars($c['cordenada2']) ?></td>
                        <td><?= htmlspecialchars($c['cordenada3']) ?></td>
                        <td><?= htmlspecialchars($c['cordenada4']) ?></td>
                        <td><?= htmlspecialchars($c['idFila']) ?></td>
                        <td>
                          <a href="?eliminar-cuadro=<?= $c['id'] ?>"
                             class="text-danger me-3"
                             onclick="return confirm('¿Eliminar este cuadro?')">
                            Eliminar
                          </a>
                          <button
                            class="btn btn-sm btn-outline-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#editCuadroModal<?= $c['id'] ?>">
                            Editar
                          </button>
                        </td>
                      </tr>

                      <!-- Modal Edición Cuadro -->
                      <div class="modal fade" id="editCuadroModal<?= $c['id'] ?>" tabindex="-1" aria-labelledby="editCuadroLabel<?= $c['id'] ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                          <div class="modal-content">
                            <form method="POST">
                              <input type="hidden" name="action" value="edit-cuadro">
                              <input type="hidden" name="cua_edit_id" value="<?= $c['id'] ?>">
                              <div class="modal-header">
                                <h5 class="modal-title" id="editCuadroLabel<?= $c['id'] ?>">Editar Cuadro #<?= $c['id'] ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                              </div>
                              <div class="modal-body">
                                <div class="mb-3">
                                  <label class="form-label">Número <span class="text-danger">*</span></label>
                                  <input type="text" name="cua_numero" class="form-control" value="<?= htmlspecialchars($c['numero']) ?>" required>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label">Cordenada 1</label>
                                  <input type="text" name="cua_cordenada1" class="form-control" value="<?= htmlspecialchars($c['cordenada1']) ?>" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
                                  <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                                    target="_blank"
                                    class="d-block text-center text-decoration-none mt-1 small text-primary">
                                    🔍 Buscar en mapa
                                  </a>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label">Cordenada 2</label>
                                  <input type="text" name="cua_cordenada2" class="form-control" value="<?= htmlspecialchars($c['cordenada2']) ?>" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
                                  <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                                    target="_blank"
                                    class="d-block text-center text-decoration-none mt-1 small text-primary">
                                    🔍 Buscar en mapa
                                  </a>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label">Cordenada 3</label>
                                  <input type="text" name="cua_cordenada3" class="form-control" value="<?= htmlspecialchars($c['cordenada3']) ?>" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
                                  <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                                    target="_blank"
                                    class="d-block text-center text-decoration-none mt-1 small text-primary">
                                    🔍 Buscar en mapa
                                  </a>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label">Cordenada 4</label>
                                  <input type="text" name="cua_cordenada4" class="form-control" value="<?= htmlspecialchars($c['cordenada4']) ?>" placeholder="Ej. 24°09'10.0&quot;N 110°14'47.5&quot;W">
                                  <a href="https://www.google.com/maps/place/24%C2%B009'11.1%22N+110%C2%B014'43.7%22W/@24.1530655,-110.2455975,17z/data=!4m4!3m3!8m2!3d24.15309!4d-110.245458?entry=ttu&g_ep=EgoyMDI1MDYwNC4wIKXMDSoASAFQAw%3D%3D"
                                    target="_blank"
                                    class="d-block text-center text-decoration-none mt-1 small text-primary">
                                    🔍 Buscar en mapa
                                  </a>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label">Fila <span class="text-danger">*</span></label>
                                  <select name="cua_idFila" class="form-select" required>
                                    <option value="">Seleccionar</option>
                                    <?php foreach ($allFilas as $frow): ?>
                                      <option value="<?= $frow['id'] ?>" <?= $frow['id'] == $c['idFila'] ? 'selected' : '' ?>>
                                        Fila <?= htmlspecialchars($frow['numero']) ?> (M <?= htmlspecialchars($frow['manzana_num']) ?>)
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
                      <!-- /Fin Modal Edición Cuadro -->

                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <!-- /FIN PESTAÑA CUADROS -->

      </div>
    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>