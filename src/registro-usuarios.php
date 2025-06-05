<?php
session_start();
require '../php/conection.php';

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $pass   = $_POST['pass'] ?? '';
    $pass2  = $_POST['pass2'] ?? '';

    // 1) Validar campos no vacíos
    if (empty($nombre) || empty($correo) || empty($pass) || empty($pass2)) {
        $error = 'Todos los campos son obligatorios.';
    }
    // 2) Verificar que la contraseña tenga entre 8 y 12 caracteres y solo contenga letras y números
    elseif (!preg_match('/^[A-Za-z0-9]{8,12}$/', $pass)) {
        $error = 'La contraseña debe tener entre 8 y 12 caracteres y contener solo letras y números.';
    }
    // 3) Verificar que las contraseñas coincidan
    elseif ($pass !== $pass2) {
        $error = 'Las contraseñas no coinciden.';
    }
    else {
        // 4) Verificar que no exista un usuario con ese correo
        $sql_check = "SELECT id FROM usuarios WHERE correo = ?";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->bind_param("s", $correo);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        if ($res_check->num_rows > 0) {
            $error = 'Ya existe una cuenta con ese correo.';
            $stmt_check->close();
        } else {
            $stmt_check->close();
            // 5) Insertar usuario (contraseña en texto plano, igual que el login)
            $sql_ins = "INSERT INTO usuarios (nombre, correo, contraseña) VALUES (?, ?, ?)";
            $stmt_ins = $conexion->prepare($sql_ins);
            $stmt_ins->bind_param("sss", $nombre, $correo, $pass);
            if ($stmt_ins->execute()) {
                $exito = 'Cuenta creada correctamente. Puedes iniciar sesión.';
                // Limpiar campos
                $nombre = $correo = '';
            } else {
                $error = 'Error al crear la cuenta. Intenta nuevamente.';
            }
            $stmt_ins->close();
        }
    }
    $conexion->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Register - Victorio Grave Search</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/index.css" />
  <style>
    body {
      background-color: #f4f4f4;
      font-family: 'Segoe UI', sans-serif;
    }
    .navbar-custom {
      background-color: #343a40;
    }
    .navbar-custom .navbar-brand,
    .navbar-custom .nav-link {
      color: #fff;
    }
    .icono-usuario {
      width: 24px;
      height: 24px;
    }
    .register-card {
      max-width: 450px;
      margin: auto;
      margin-top: 10vh;
      padding: 2rem;
      background-color: #fff;
      border-radius: 1rem;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }
    .logo {
      display: flex;
      align-items: center;
      margin-bottom: 2rem;
      gap: 0.5rem;
      color: #343a40;
      font-weight: 700;
    }
  </style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm px-4">
    <a class="navbar-brand d-flex align-items-center" href="../index.php">
      <img src="../img/logo.png" alt="logo" width="40" height="40" class="me-2">
      <span class="fs-4 fw-bold">Victorio Grave Search</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
</nav>

<div class="register-card text-center">
    <div class="logo justify-content-center">
      <img src="../img/logo.png" width="40" alt="Logo" />
      Victorio’s Grave Search
    </div>
    <h4 class="mb-4 fw-bold">Crear Cuenta</h4>

    <?php if ($exito): ?>
      <div class="alert alert-success"><?php echo $exito; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
      <div class="form-floating mb-3">
        <input type="text" class="form-control" id="nombre" name="nombre"
               placeholder="Nombre completo"
               value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>" required>
        <label for="nombre">Nombre</label>
      </div>
      <div class="form-floating mb-3">
        <input type="email" class="form-control" id="correo" name="correo"
               placeholder="Correo"
               value="<?php echo isset($correo) ? htmlspecialchars($correo) : ''; ?>" required>
        <label for="correo">Correo</label>
      </div>
      <div class="form-floating mb-3">
        <input type="password"
               class="form-control"
               id="pass"
               name="pass"
               placeholder="Contraseña"
               pattern="[A-Za-z0-9]{8,12}"
               title="Solo letras y números, de 8 a 12 caracteres"
               maxlength = 12
               required>
        <label for="pass">Contraseña</label>
      </div>
      <div class="form-floating mb-4">
        <input type="password" class="form-control" id="pass2" name="pass2"
               placeholder="Repite la contraseña"  maxlength = 12 required>
        <label for="pass2">Repetir Contraseña</label>
      </div>
      <button type="submit" class="btn btn-dark w-100">Registrarme</button>
      <div class="mt-3">
        <small>¿Ya tienes cuenta? <a href="inicion-sesion-usuarios.php">Iniciar Sesión</a></small>
      </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>