<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Victorio Grave Search</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/index.css" />
  <style>
    .icono-usuario {
      width: 24px;
      height: 24px;
    }
  </style>
</head>

<body>

  <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm px-4">
    <a class="navbar-brand d-flex align-items-center" href="#">
      <img src="img/logo.png" alt="logo" width="40" height="40" class="me-2" />
      <span class="fs-4">Victorio Grave Search</span>
    </a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <?php if (isset($_SESSION['usuario_id'])): ?>
          <li class="nav-item">
            <a class="nav-link d-flex align-items-center" href="src/configuracion-usuario.php">
              <i class="fas fa-user icono-usuario me-1"></i>
              <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="src/inicion-sesion-usuarios.php">Iniciar Sesión</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="src/registro-usuarios.php">Registrarse</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </nav>

  <header class="hero-section text-white text-center d-flex align-items-center justify-content-center">
    <div class="overlay"></div>
    <div class="position-relative z-1">
      <h1 class="display-4 fw-bold">Localizador de Tumbas</h1>
      <p class="lead">Encuentra fácilmente la ubicación de tus seres queridos en los cementerios de La Paz</p>
      <a href="#busqueda" class="btn btn-sober btn-lg mt-3 px-4 rounded-pill">Comenzar búsqueda</a>
    </div>
  </header>

  <section class="py-5 bg-light">
    <div class="container text-center">
      <h2 class="mb-5 fw-semibold">¿Qué puedes hacer aquí?</h2>
      <div class="row g-4">
        <div class="col-md-4">
          <i class="fas fa-search-location fa-3x mb-3 text-dark"></i>
          <h5>Búsqueda por nombre</h5>
          <p>Encuentra tumbas ingresando el nombre del difunto.</p>
        </div>
        <div class="col-md-4">
          <i class="fas fa-map-marked-alt fa-3x mb-3 text-dark"></i>
          <h5>Ubicación exacta</h5>
          <p>Visualiza la localización precisa en el mapa interactivo.</p>
        </div>
        <div class="col-md-4">
          <i class="fas fa-clock fa-3x mb-3 text-dark"></i>
          <h5>Información histórica</h5>
          <p>Consulta fechas, notas y visitas recientes.</p>
        </div>
      </div>
    </div>
  </section>

  <section id="busqueda" class="container py-5">
    <div class="text-center mb-5">
      <h2 class="fw-semibold">Buscar una Tumba</h2>
      <p class="text-muted">Selecciona el método de búsqueda</p>
    </div>
    <div class="row justify-content-center g-4">
      <div class="col-md-5">
        <div class="card shadow-lg card-img border-0" style="background-image: url('img/horizonte.avif');">
          <div class="card-overlay text-center d-flex flex-column justify-content-center">
            <h4 class="mb-3">Búsqueda por Persona</h4>
            <a href="src/consulta-persona.php" class="btn btn-outline-light rounded-pill px-4">Iniciar</a>
          </div>
        </div>
      </div>
      <div class="col-md-5">
        <div class="card shadow-lg card-img border-0" style="background-image: url('img/mapa.jpg');">
          <div class="card-overlay text-center d-flex flex-column justify-content-center">
            <h4 class="mb-3">Búsqueda por Mapa</h4>
            <a href="src/consulta-localizacion.php" class="btn btn-outline-light rounded-pill px-4">Iniciar</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  
  <footer class="bg-secondary text-white text-center py-3">
    <div class="container">
      <p class="mb-0">© 2025 La Paz, Baja California Sur — Todos los derechos reservados.</p>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
