<?php
session_start();
require '../php/conection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['correo'] ?? '';
    $pass   = $_POST['pass']   ?? '';

    // Buscamos al admin que tenga este correo
    $sql  = "SELECT id, correo, contraseña FROM admins WHERE correo = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        // Aquí comparamos en texto plano
        if ($pass === $usuario['contraseña']) {
            $_SESSION['usuario_id'] = $usuario['id'];
            header("Location: admin-tumbas.php");
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Login - Victorio Grave Search</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
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
      color: #d4af37;
      font-weight: 700;
    }
  </style>
</head>
<body>

<div class="login-card text-center">
  <div class="logo justify-content-center">
    <img src="../img/logo.png" width="40" />
    Victorio’s grave search
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
      <input type="password" class="form-control" id="pass" name="pass" placeholder="Contraseña" maxlength="12" required>
      <label for="pass">Contraseña</label>
    </div>
    <button type="submit" class="btn btn-dark w-100">Entrar</button>
  </form>
</div>

</body>
</html>