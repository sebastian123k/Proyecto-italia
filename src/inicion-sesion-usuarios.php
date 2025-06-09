<?php
require 'controlador-sesiones.php';
require '../php/conection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['correo'] ?? '';
    $pass   = $_POST['pass']   ?? '';

    // Buscamos al usuario que tenga este correo
    $sql  = "SELECT id, nombre, correo, contraseña FROM usuarios WHERE correo = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        // Comparamos en texto plano (igual que el ejemplo)
        if ($pass === $usuario['contraseña']) {
            $_SESSION['usuario_id']     = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            header("Location: ../index.php");
            exit();
        } else {
            $error = 'Credenciales incorrectas';
        }
    } else {
        $error = 'Usuario no encontrado';
    }

    $stmt->close();
    $conexion->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login de Usuario</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f4f4f4;
      font-family: 'Segoe UI', sans-serif;
    }
    .login-card {
      max-width: 400px;
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

<div class="login-card text-center">
  <div class="logo justify-content-center">
    <img src="../img/logo.png" width="40" alt="Logo" />
    Victorio’s Grave Search
  </div>
  <h4 class="mb-4 fw-bold">Iniciar Sesión</h4>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
  <?php endif; ?>

  <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
    <div class="form-floating mb-3">
      <input type="email" class="form-control" id="correo" name="correo" placeholder="Correo" required>
      <label for="correo">Correo</label>
    </div>
    <div class="form-floating mb-4">
      <input type="password" class="form-control" id="pass" name="pass" placeholder="Contraseña" maxlength = 12 required>
      <label for="pass">Contraseña</label>
    </div>
    <button type="submit" class="btn btn-dark w-100">Entrar</button>
    <div class="mt-3">
      <small>¿No tienes cuenta? <a href="registro-usuarios.php">Crear cuenta</a></small>
    </div>
  </form>
</div>

</body>
</html>
