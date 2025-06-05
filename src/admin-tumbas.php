<?php
session_start();
require '../php/conection.php';

// 1. Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: admin-login.php");
    exit();
}

$error   = '';
$success = '';

// ----------------------------------------------------
// 2. CARGAR LISTAS DE MANZANAS / FILAS / CUADROS PARA
//    LOS DROPDOWNS (con 'numero' como texto)
// ----------------------------------------------------

// 2.1. Todas las manzanas (id + numero) para el dropdown
$allManzanas = [];
$rsM = $conexion->query("SELECT id, numero FROM manzana ORDER BY numero ASC");
if ($rsM) {
    $allManzanas = $rsM->fetch_all(MYSQLI_ASSOC);
    $rsM->close();
}

// 2.2. Todas las filas (id, numero, idManzana, y manzana_numero) para el dropdown
$allFilas = [];
$rsF = $conexion->query("
    SELECT f.id, f.numero, f.idManzana,
           m.numero AS manzana_numero
    FROM fila f
    JOIN manzana m ON f.idManzana = m.id
    ORDER BY m.numero ASC, f.numero ASC
");
if ($rsF) {
    $allFilas = $rsF->fetch_all(MYSQLI_ASSOC);
    $rsF->close();
}

// 2.3. Todos los cuadros (id, numero, idFila, fila_numero, manzana_numero) para el dropdown
$allCuadros = [];
$rsC = $conexion->query("
    SELECT c.id, c.numero, c.idFila,
           f.numero AS fila_numero,
           m.numero AS manzana_numero
    FROM cuadro c
    JOIN fila f      ON c.idFila   = f.id
    JOIN manzana m   ON f.idManzana = m.id
    ORDER BY m.numero ASC, f.numero ASC, c.numero ASC
");
if ($rsC) {
    $allCuadros = $rsC->fetch_all(MYSQLI_ASSOC);
    $rsC->close();
}

// ----------------------------------------------------
// 3. PROCESO DE INSERT / UPDATE EN TUMBAS
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ------------------------------------------------
    // 3.1. AGREGAR TUMBA
    // ------------------------------------------------
    if ($action === 'add') {
        $manzana_txt  = trim($_POST['manzana_txt']  ?? '');
        $fila_txt     = trim($_POST['fila_txt']     ?? '');
        $cuadro_txt   = trim($_POST['cuadro_txt']   ?? '');
        $numero       = trim($_POST['numero']       ?? '');
        $cordenadas   = trim($_POST['cordenadas']   ?? '');

        // Validar campos obligatorios
        if ($manzana_txt === '' || $fila_txt === '' || $cuadro_txt === '' || $numero === '') {
            $error = 'Los campos <strong>Manzana</strong>, <strong>Fila</strong>, <strong>Cuadro</strong> y <strong>Número</strong> son obligatorios.';
        } else {
            // Validar formato de coordenadas (si se ingresó algo)
            if ($cordenadas !== '') {
                if (!preg_match('/^\d{1,2}°\d{2}\'\d{2}(\.\d+)?\"[NS] \d{1,3}°\d{2}\'\d{2}(\.\d+)?\"[EW]$/', $cordenadas)) {
                    $error = 'El formato de las coordenadas es inválido. Usa este formato: 24°09\'10.3"N 110°14\'46.6"W';
                }
            }

            if ($error === '') {
                $stmt = $conexion->prepare("
                    INSERT INTO tumbas (manzana, fila, cuadro, numero, cordenadas)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("sssss",
                    $manzana_txt,
                    $fila_txt,
                    $cuadro_txt,
                    $numero,
                    $cordenadas
                );
                if ($stmt->execute()) {
                    $success = 'Tumba registrada exitosamente.';
                } else {
                    $error = 'Error al crear la tumba: ' . $conexion->error;
                }
                $stmt->close();
            }
        }
    }

    // ------------------------------------------------
    // 3.2. EDITAR TUMBA
    // ------------------------------------------------
    if ($action === 'edit') {
        $edit_id      = (int)($_POST['edit_id']    ?? 0);
        $manzana_txt  = trim($_POST['manzana_txt']  ?? '');
        $fila_txt     = trim($_POST['fila_txt']     ?? '');
        $cuadro_txt   = trim($_POST['cuadro_txt']   ?? '');
        $numero       = trim($_POST['numero']       ?? '');
        $cordenadas   = trim($_POST['cordenadas']   ?? '');

        if ($edit_id <= 0 || $manzana_txt === '' || $fila_txt === '' || $cuadro_txt === '' || $numero === '') {
            $error = 'Todos los campos (ID, Manzana, Fila, Cuadro, Número) son obligatorios para editar.';
        } else {
            // Verificar formato de coordenadas (si se ingresó algo)
            if ($cordenadas !== '') {
                if (!preg_match('/^\d{1,2}°\d{2}\'\d{2}(\.\d+)?\"[NS] \d{1,3}°\d{2}\'\d{2}(\.\d+)?\"[EW]$/', $cordenadas)) {
                    $error = 'Formato de coordenadas inválido para editar. Ejemplo: 24°09\'10.3"N 110°14\'46.6"W';
                }
            }

            if ($error === '') {
                // Verificar que la tumba existe
                $stmt = $conexion->prepare("SELECT id FROM tumbas WHERE id = ?");
                $stmt->bind_param("i", $edit_id);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows === 0) {
                    $error = 'La tumba que intentas editar no existe.';
                    $stmt->close();
                } else {
                    $stmt->close();
                    // Hacemos el UPDATE con los textos
                    $stmt = $conexion->prepare("
                        UPDATE tumbas
                        SET manzana    = ?,
                            fila       = ?,
                            cuadro     = ?,
                            numero     = ?,
                            cordenadas = ?
                        WHERE id = ?
                    ");
                    $stmt->bind_param("ssssi",
                        $manzana_txt,
                        $fila_txt,
                        $cuadro_txt,
                        $numero,
                        $cordenadas,
                        $edit_id
                    );
                    if ($stmt->execute()) {
                        $success = 'Tumba actualizada correctamente.';
                    } else {
                        $error = 'Error al actualizar la tumba: ' . $conexion->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// ----------------------------------------------------
// 4. PROCESAR ELIMINACIÓN (GET)
// ----------------------------------------------------
if (isset($_GET['eliminar'])) {
    $elim_id = (int)$_GET['eliminar'];
    if ($elim_id > 0) {
        $stmt = $conexion->prepare("DELETE FROM tumbas WHERE id = ?");
        $stmt->bind_param("i", $elim_id);
        $stmt->execute();
        $stmt->close();
        $success = 'Tumba eliminada.';
    }
}

// ----------------------------------------------------
// 5. FILTRAR (BÚSQUEDA) PARA LISTAR TUMBAS
// ----------------------------------------------------
$filterManzana = trim($_GET['filterManzana'] ?? '');
$filterFila    = trim($_GET['filterFila']    ?? '');
$filterCuadro  = trim($_GET['filterCuadro']  ?? '');
$filterNumero  = trim($_GET['filterNumero']  ?? '');

$tumbas = [];
$sql   = "SELECT id, manzana, fila, cuadro, numero, cordenadas
          FROM tumbas
          WHERE 1=1";
if ($filterManzana !== '') {
    $v    = $conexion->real_escape_string($filterManzana);
    $sql .= " AND manzana LIKE '%$v%'";
}
if ($filterFila !== '') {
    $v    = $conexion->real_escape_string($filterFila);
    $sql .= " AND fila LIKE '%$v%'";
}
if ($filterCuadro !== '') {
    $v    = $conexion->real_escape_string($filterCuadro);
    $sql .= " AND cuadro LIKE '%$v%'";
}
if ($filterNumero !== '') {
    $v    = $conexion->real_escape_string($filterNumero);
    $sql .= " AND numero LIKE '%$v%'";
}

$rsT = $conexion->query($sql);
if ($rsT) {
    $tumbas = $rsT->fetch_all(MYSQLI_ASSOC);
    $rsT->close();
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Panel Admin - Tumbas</title>
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
      <a href="admin-usuarios.php">Usuarios</a>
      <a href="admin-tumbas.php" class="active">Tumbas</a>
      <a href="admin-difuntos.php">Difuntos</a>
      <a href="admin-ubicaciones.php">Ubicaciones</a>
      <a href="logout.php" class="text-danger mt-4">Cerrar Sesión</a>
    </nav>

    <!-- Contenido principal -->
    <main class="col-md-9 col-lg-10 p-4">
      <h3 class="mb-4">Administración de Tumbas</h3>

      <!-- Mensajes de éxito o error -->
      <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- ====================================== -->
      <!-- 1. FORMULARIO DE ALTA DE TUMBA         -->
      <!-- ====================================== -->
      <form method="POST" class="row g-3 mb-4 bg-white p-3 rounded shadow">
        <input type="hidden" name="action" value="add">

        <!-- 1.1 Dropdown Manzana (texto) -->
        <div class="col-md-3">
          <label class="form-label">Manzana <span class="text-danger">*</span></label>
          <select id="selManzana" name="manzana_txt" class="form-select" required>
            <option value="">Seleccionar manzana</option>
            <?php foreach ($allManzanas as $m): ?>
              <option value="<?= htmlspecialchars($m['numero']) ?>"
                      data-id="<?= $m['id'] ?>">
                ID <?= $m['id'] ?> — <?= htmlspecialchars($m['numero']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- 1.2 Dropdown Fila (texto, filtrado por manzana) -->
        <div class="col-md-3">
          <label class="form-label">Fila <span class="text-danger">*</span></label>
          <select id="selFila" name="fila_txt" class="form-select" disabled required>
            <option value="">Primero selecciona manzana</option>
          </select>
        </div>

        <!-- 1.3 Dropdown Cuadro (texto, filtrado por fila) -->
        <div class="col-md-3">
          <label class="form-label">Cuadro <span class="text-danger">*</span></label>
          <select id="selCuadro" name="cuadro_txt" class="form-select" disabled required>
            <option value="">Primero selecciona fila</option>
          </select>
        </div>

        <!-- 1.4 Número de Tumba -->
        <div class="col-md-3">
          <label class="form-label">Número <span class="text-danger">*</span></label>
          <input type="number" name="numero" class="form-control" placeholder="Ej. 001" required maxlength="12">
        </div>

        <!-- 1.5 Coordenadas -->
        <div class="col-md-6">
          <label class="form-label">Coordenadas</label>
          <input type="text" name="cordenadas" class="form-control"
                 placeholder="Ej: 24°09'10.3&quot;N 110°14'46.6&quot;W">
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-dark">Registrar Tumba</button>
        </div>
      </form>

      <!-- ====================================== -->
      <!-- 2. PANEL DE BÚSQUEDA DE TUMBAS         -->
      <!-- ====================================== -->
      <div class="bg-white rounded shadow p-3 mb-4">
        <h5 class="fw-semibold mb-3">Buscar Tumbas</h5>
        <form method="GET" class="row g-3">
          <!-- Filtrar por Manzana (texto) -->
          <div class="col-md-3">
            <label class="form-label">Manzana</label>
            <select name="filterManzana" class="form-select">
              <option value="">Todas</option>
              <?php foreach ($allManzanas as $m): ?>
                <option value="<?= htmlspecialchars($m['numero']) ?>"
                  <?= $filterManzana === $m['numero'] ? 'selected' : '' ?>>
                  ID <?= $m['id'] ?> — <?= htmlspecialchars($m['numero']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <!-- Filtrar por Fila (texto) -->
          <div class="col-md-3">
            <label class="form-label">Fila</label>
            <select name="filterFila" class="form-select">
              <option value="">Todas</option>
              <?php foreach ($allFilas as $f): ?>
                <option value="<?= htmlspecialchars($f['numero']) ?>"
                  <?= $filterFila === $f['numero'] ? 'selected' : '' ?>>
                  ID <?= $f['id'] ?> — <?= htmlspecialchars($f['numero']) ?>
                  (M <?= htmlspecialchars($f['manzana_numero']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <!-- Filtrar por Cuadro (texto) -->
          <div class="col-md-3">
            <label class="form-label">Cuadro</label>
            <select name="filterCuadro" class="form-select">
              <option value="">Todos</option>
              <?php foreach ($allCuadros as $c): ?>
                <option value="<?= htmlspecialchars($c['numero']) ?>"
                  <?= $filterCuadro === $c['numero'] ? 'selected' : '' ?>>
                  ID <?= $c['id'] ?> — <?= htmlspecialchars($c['numero']) ?>
                  (F <?= htmlspecialchars($c['fila_numero']) ?> / M <?= htmlspecialchars($c['manzana_numero']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <!-- Filtrar por Número textual -->
          <div class="col-md-3">
            <label class="form-label">Número de tumba</label>
            <input type="text" name="filterNumero" value="<?= htmlspecialchars($filterNumero) ?>"
                   class="form-control" placeholder="Ej. 001" maxlength="12">
          </div>
          <div class="col-12 text-end">
            <button type="reset" class="btn btn-outline-secondary">Limpiar</button>
            <button type="submit" class="btn btn-gold">Buscar</button>
          </div>
        </form>
      </div>

      <!-- ====================================== -->
      <!-- 3. TABLA DE TUMBAS                    -->
      <!-- ====================================== -->
      <div class="bg-white rounded shadow p-3">
        <h5 class="fw-semibold mb-3">Listado de Tumbas</h5>
        <div class="table-responsive">
          <table class="table table-hover align-middle text-center">
            <thead class="table-dark">
              <tr>
                <th>ID</th>
                <th>Manzana</th>
                <th>Fila</th>
                <th>Cuadro</th>
                <th>Número</th>
                <th>Coordenadas</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($tumbas)): ?>
                <tr>
                  <td colspan="7">No se encontraron tumbas.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($tumbas as $t): ?>
                  <tr>
                    <td><?= htmlspecialchars($t['id']) ?></td>
                    <td><?= htmlspecialchars($t['manzana']) ?></td>
                    <td><?= htmlspecialchars($t['fila']) ?></td>
                    <td><?= htmlspecialchars($t['cuadro']) ?></td>
                    <td><?= htmlspecialchars($t['numero']) ?></td>
                    <td><?= htmlspecialchars($t['cordenadas']) ?></td>
                    <td>
                      <a href="?eliminar=<?= $t['id'] ?>"
                         class="text-danger me-3"
                         onclick="return confirm('¿Eliminar esta tumba?')">
                        Eliminar
                      </a>
                      <!-- Botón Editar abre modal -->
                      <button
                        class="btn btn-sm btn-outline-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#editModal<?= $t['id'] ?>">
                        Editar
                      </button>
                    </td>
                  </tr>

                  <!-- ================================== -->
                  <!-- MODAL DE EDICIÓN PARA ESTA TUMBA  -->
                  <!-- ================================== -->
                  <div class="modal fade" id="editModal<?= $t['id'] ?>" tabindex="-1"
                       aria-labelledby="editModalLabel<?= $t['id'] ?>" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                      <div class="modal-content">
                        <form method="POST" class="row g-3 p-3">
                          <input type="hidden" name="action" value="edit">
                          <input type="hidden" name="edit_id" value="<?= $t['id'] ?>">

                          <!-- Modal Header -->
                          <div class="modal-header">
                            <h5 class="modal-title" id="editModalLabel<?= $t['id'] ?>">
                              Editar Tumba #<?= $t['id'] ?>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                          </div>

                          <!-- Modal Body: -->
                          <div class="modal-body">

                            <!-- 3.1. Manzana (dropdown, texto) -->
                            <div class="mb-3">
                              <label class="form-label">Manzana <span class="text-danger">*</span></label>
                              <select id="selManzanaEdit<?= $t['id'] ?>"
                                      name="manzana_txt"
                                      class="form-select selManzanaEdit"
                                      data-row-id="<?= $t['id'] ?>"
                                      required>
                                <option value="">Seleccionar manzana</option>
                                <?php foreach ($allManzanas as $m): ?>
                                  <option value="<?= htmlspecialchars($m['numero']) ?>"
                                          data-id="<?= $m['id'] ?>"
                                          <?= $m['numero'] === $t['manzana'] ? 'selected' : '' ?>>
                                    ID <?= $m['id'] ?> — <?= htmlspecialchars($m['numero']) ?>
                                  </option>
                                <?php endforeach; ?>
                              </select>
                            </div>

                            <!-- 3.2. Fila (dropdown, texto) -->
                            <div class="mb-3">
                              <label class="form-label">Fila <span class="text-danger">*</span></label>
                              <select id="selFilaEdit<?= $t['id'] ?>"
                                      name="fila_txt"
                                      class="form-select selFilaEdit"
                                      data-row-id="<?= $t['id'] ?>"
                                      data-current="<?= htmlspecialchars($t['fila']) ?>"
                                      required>
                                <option value="">Primero selecciona manzana</option>
                              </select>
                            </div>

                            <!-- 3.3. Cuadro (dropdown, texto) -->
                            <div class="mb-3">
                              <label class="form-label">Cuadro <span class="text-danger">*</span></label>
                              <select id="selCuadroEdit<?= $t['id'] ?>"
                                      name="cuadro_txt"
                                      class="form-select selCuadroEdit"
                                      data-row-id="<?= $t['id'] ?>"
                                      data-current="<?= htmlspecialchars($t['cuadro']) ?>"
                                      required>
                                <option value="">Primero selecciona fila</option>
                              </select>
                            </div>

                            <!-- 3.4. Número textual -->
                            <div class="mb-3">
                              <label class="form-label">Número <span class="text-danger">*</span></label>
                              <input type="text" name="numero" class="form-control"
                                     value="<?= htmlspecialchars($t['numero']) ?>" required maxlength="12">
                            </div>

                            <!-- 3.5. Coordenadas -->
                            <div class="mb-3">
                              <label class="form-label">Coordenadas</label>
                              <input type="text" name="cordenadas" class="form-control"
                                     value="<?= htmlspecialchars($t['cordenadas']) ?>"
                                     placeholder="Ej: 24°09'10.3&quot;N 110°14'46.6&quot;W">
                            </div>

                          </div> <!-- /.modal-body -->

                          <!-- Modal Footer -->
                          <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                              Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                              Guardar cambios
                            </button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                  <!-- /FIN MODAL EDICIÓN -->
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </main>
  </div>
</div>

<!-- ====================================== -->
<!-- 6. JavaScript para cascada de selects   -->
<!-- ====================================== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // 6.1. Pasamos las listas PHP a arrays JS
  const jsManzanas = <?= json_encode($allManzanas, JSON_UNESCAPED_UNICODE) ?>;
  const jsFilas    = <?= json_encode($allFilas,    JSON_UNESCAPED_UNICODE) ?>;
  const jsCuadros  = <?= json_encode($allCuadros,  JSON_UNESCAPED_UNICODE) ?>;

  // 6.2. Función para poblar <select> de Filas dado el ID de manzana
  function poblarFilas(selectElement, manzanaId, selectedFilaTxt = null) {
    selectElement.innerHTML = '';
    if (!manzanaId) {
      let opt = document.createElement('option');
      opt.value = '';
      opt.textContent = 'Primero selecciona manzana';
      selectElement.appendChild(opt);
      selectElement.disabled = true;
      return;
    }
    // Filtrar jsFilas por idManzana
    let filasFiltradas = jsFilas.filter(f => parseInt(f.idManzana) === parseInt(manzanaId));
    if (filasFiltradas.length === 0) {
      let opt = document.createElement('option');
      opt.value = '';
      opt.textContent = 'No hay filas para esta manzana';
      selectElement.appendChild(opt);
      selectElement.disabled = true;
      return;
    }
    selectElement.disabled = false;
    let optVacio = document.createElement('option');
    optVacio.value = '';
    optVacio.textContent = 'Seleccionar fila';
    selectElement.appendChild(optVacio);

    filasFiltradas.forEach(f => {
      let opt = document.createElement('option');
      opt.value = f.numero; // guardamos el texto 'numero'
      opt.textContent = `ID ${f.id} — ${f.numero}`;
      if (selectedFilaTxt && selectedFilaTxt === f.numero) {
        opt.selected = true;
      }
      // Para saber internamente el ID, asignamos data-id
      opt.dataset.id = f.id;
      selectElement.appendChild(opt);
    });
  }

  // 6.3. Función para poblar <select> de Cuadros dado el ID de fila
  function poblarCuadros(selectElement, filaId, selectedCuadroTxt = null) {
    selectElement.innerHTML = '';
    if (!filaId) {
      let opt = document.createElement('option');
      opt.value = '';
      opt.textContent = 'Primero selecciona fila';
      selectElement.appendChild(opt);
      selectElement.disabled = true;
      return;
    }
    // Filtrar jsCuadros por idFila
    let cuadrosFiltrados = jsCuadros.filter(c => parseInt(c.idFila) === parseInt(filaId));
    if (cuadrosFiltrados.length === 0) {
      let opt = document.createElement('option');
      opt.value = '';
      opt.textContent = 'No hay cuadros para esta fila';
      selectElement.appendChild(opt);
      selectElement.disabled = true;
      return;
    }
    selectElement.disabled = false;
    let optVacio = document.createElement('option');
    optVacio.value = '';
    optVacio.textContent = 'Seleccionar cuadro';
    selectElement.appendChild(optVacio);

    cuadrosFiltrados.forEach(c => {
      let opt = document.createElement('option');
      opt.value = c.numero; // guardamos el texto 'numero'
      opt.textContent = `ID ${c.id} — ${c.numero}`;
      if (selectedCuadroTxt && selectedCuadroTxt === c.numero) {
        opt.selected = true;
      }
      opt.dataset.id = c.id; // para saber internamente el ID
      selectElement.appendChild(opt);
    });
  }

  // 6.4. Inicializar cascada en formulario de ALTA
  document.addEventListener('DOMContentLoaded', () => {
    const selManzana = document.getElementById('selManzana');
    const selFila    = document.getElementById('selFila');
    const selCuadro  = document.getElementById('selCuadro');

    selManzana.addEventListener('change', () => {
      let manzanaId   = selManzana.selectedOptions[0]?.dataset.id || null;
      poblarFilas(selFila, manzanaId);
      // Limpiar cuadro
      selCuadro.innerHTML = '';
      let opt = document.createElement('option');
      opt.value = '';
      opt.textContent = 'Primero selecciona fila';
      selCuadro.appendChild(opt);
      selCuadro.disabled = true;
    });

    selFila.addEventListener('change', () => {
      let filaId = selFila.selectedOptions[0]?.dataset.id || null;
      poblarCuadros(selCuadro, filaId);
    });
  });

  // 6.5. Inicializar cascada en cada modal de EDICIÓN
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.selManzanaEdit').forEach(selManzana => {
      let rowId = selManzana.dataset.rowId;
      let selFila   = document.getElementById('selFilaEdit' + rowId);
      let selCuadro = document.getElementById('selCuadroEdit' + rowId);

      // Cuando cambie la manzana en el modal:
      selManzana.addEventListener('change', () => {
        let manzanaId = selManzana.selectedOptions[0]?.dataset.id || null;
        poblarFilas(selFila, manzanaId);
        // Limpiar cuadro
        selCuadro.innerHTML = '';
        let opt = document.createElement('option');
        opt.value = '';
        opt.textContent = 'Primero selecciona fila';
        selCuadro.appendChild(opt);
        selCuadro.disabled = true;
      });

      // Cuando cambie la fila en el modal:
      selFila.addEventListener('change', () => {
        let filaId = selFila.selectedOptions[0]?.dataset.id || null;
        poblarCuadros(selCuadro, filaId);
      });

      // Al mostrar el modal (evento de Bootstrap):
      let modalElem = document.getElementById('editModal' + rowId);
      modalElem.addEventListener('show.bs.modal', function () {
        // Valores textuales actuales
        let textualManzana = selManzana.value;
        let textualFila    = selFila.getAttribute('data-current');
        let textualCuadro  = selCuadro.getAttribute('data-current');

        // Si hay textualManzana, buscamos su ID interno:
        let opcionM = Array.from(selManzana.options)
                            .find(o => o.value === textualManzana);
        let manzanaId = opcionM?.dataset.id || null;

        // Llenar filas con preselección
        poblarFilas(selFila, manzanaId, textualFila);

        // Ahora buscamos ID de fila para llenar cuadros
        let opcionF = Array.from(selFila.options)
                            .find(o => o.value === textualFila);
        let filaId = opcionF?.dataset.id || null;

        // Llenar cuadros con preselección
        poblarCuadros(selCuadro, filaId, textualCuadro);
      });
    });
  });

  // 6.6. VALIDACIÓN DE COORDENADAS EN TODOS LOS FORMULARIOS
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form').forEach(form => {
      form.addEventListener('submit', function(e) {
        const coordInput = this.querySelector('input[name="cordenadas"]');
        if (coordInput) {
          const val = coordInput.value.trim();
          if (val !== '') {
            // Regex: dd°dd'dd(.dd)"[N/S] ddd°dd'dd(.dd)"[E/W]
            const regex = /^\d{1,2}°\d{2}'\d{2}(\.\d+)?\"[NS] \d{1,3}°\d{2}'\d{2}(\.\d+)?\"[EW]$/;
            if (!regex.test(val)) {
              e.preventDefault();
              alert('Formato inválido para coordenadas. Ejemplo: 24°09\'10.3\"N 110°14\'46.6\"W');
              coordInput.focus();
            }
          }
        }
      });
    });
  });
</script>
</body>
</html>