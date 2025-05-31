<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Búsqueda por Persona</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/consulta-persona.css" />
</head>
<body>

<nav class="navbar navbar-dark bg-dark shadow-sm px-4">
  <div class="d-flex align-items-center">
    <a href="../index.html"><img src="../img/logo.png" alt="logo" class="me-2" width="40" height="40" /></a>
    <span class="navbar-brand fs-4 fw-bold">Victorio Grave Search</span>
  </div>
</nav>

<header class="hero-banner d-flex align-items-center justify-content-center text-white text-center">
  <div class="overlay"></div>
  <div class="z-1 position-relative">
    <h1 class="display-5 fw-bold">Búsqueda por Persona</h1>
    <p class="lead">Sistema de consulta avanzada del panteón Perlas de La Paz</p>
  </div>
</header>

<main class="container my-5">
  <div class="search-panel shadow-lg rounded-4 p-4 bg-white mb-5">
    <h4 class="fw-bold mb-4"><i class="fas fa-user-search me-2"></i>Filtro de búsqueda</h4>
    <form class="row g-3" method="GET">
      <div class="col-md-3">
        <label class="form-label">Nombre</label>
        <input type="text" class="form-control" name="nombre" placeholder="Nombre">
      </div>
      <div class="col-md-3">
        <label class="form-label">1° Apellido</label>
        <input type="text" class="form-control" name="apellidoP" placeholder="Primer apellido">
      </div>
      <div class="col-md-3">
        <label class="form-label">2° Apellido</label>
        <input type="text" class="form-control" name="apellidoM" placeholder="Segundo apellido">
      </div>
      <div class="col-md-3">
        <label class="form-label">Fecha de Nacimiento</label>
        <input type="date" class="form-control" name="fechaNac">
      </div>
      <div class="col-md-3">
        <label class="form-label">Fecha de Defunción</label>
        <input type="date" class="form-control" name="fechaDef">
      </div>
      <div class="col-md-9 d-flex align-items-end justify-content-end gap-2 mt-4">
        <button type="reset" class="btn btn-outline-secondary">Limpiar</button>
        <button type="submit" class="btn btn-gold">Buscar</button>
      </div>
    </form>
  </div>

  <div class="results-table bg-white shadow-lg rounded-4 p-4">
    <h5 class="fw-semibold mb-4"><i class="fas fa-table me-2"></i>Resultados encontrados</h5>
    <div class="table-responsive">
      <table class="table table-bordered text-center align-middle">
        <thead class="table-dark">
          <tr>
            <th>Manzana</th>
            <th>Cuadro</th>
            <th>Fila</th>
            <th>Número</th>
            <th>1° Apellido</th>
            <th>2° Apellido</th>
            <th>Nombre</th>
            <th>F. Nac.</th>
            <th>F. Def.</th>
            <th>Tipo Restos</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php
            require '../php/conection.php'; 

            $nombre = $_GET['nombre'] ?? '';
            $apellidoP = $_GET['apellidoP'] ?? '';
            $apellidoM = $_GET['apellidoM'] ?? '';
            $fechaNac = $_GET['fechaNac'] ?? '';
            $fechaDef = $_GET['fechaDef'] ?? '';

            $sql = "SELECT 
                      t.manzana, t.cuadro, t.fila, t.numero, 
                      d.id, d.apellidoPaterno, d.apellidoMaterno, 
                      d.nombre, d.fechaNacimiento, d.fechaDefuncion, 
                      d.Restos 
                    FROM difuntos d
                    JOIN tumbas t ON d.idTumba = t.id
                    WHERE 1=1";

            if (!empty($nombre)) {
                $sql .= " AND d.nombre LIKE '%$nombre%'";
            }
            if (!empty($apellidoP)) {
                $sql .= " AND d.apellidoPaterno LIKE '%$apellidoP%'";
            }
            if (!empty($apellidoM)) {
                $sql .= " AND d.apellidoMaterno LIKE '%$apellidoM%'";
            }
            if (!empty($fechaNac)) {
                $sql .= " AND d.fechaNacimiento = '$fechaNac'";
            }
            if (!empty($fechaDef)) {
                $sql .= " AND d.fechaDefuncion = '$fechaDef'";
            }

            $resultado = $conexion->query($sql);

            if ($resultado && $resultado->num_rows > 0) {
                while ($fila = $resultado->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>{$fila['manzana']}</td>";
                    echo "<td>{$fila['cuadro']}</td>";
                    echo "<td>{$fila['fila']}</td>";
                    echo "<td>{$fila['numero']}</td>";
                    echo "<td>{$fila['apellidoPaterno']}</td>";
                    echo "<td>{$fila['apellidoMaterno']}</td>";
                    echo "<td>{$fila['nombre']}</td>";
                    echo "<td>{$fila['fechaNacimiento']}</td>";
                    echo "<td>{$fila['fechaDefuncion']}</td>";
                    echo "<td>{$fila['Restos']}</td>";
                    echo "<td><a href='resultado.php?id={$fila['id']}' class='btn btn-sm btn-outline-primary'>Ver</a></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='11'>No se encontraron resultados.</td></tr>";
            }

            $conexion->close();
          ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<footer class="bg-dark text-white text-center py-4 mt-5">
  © 2025 Cementerio Perlas de La Paz — Todos los derechos reservados.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
