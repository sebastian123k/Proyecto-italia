<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Ficha de Difunto</title>
  <style>
    body {
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f9f9f9;
      color: #333;
      margin: 40px;
    }

    .card {
      max-width: 800px;
      margin: auto;
      background-color: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      padding: 30px;
    }

    .header {
      display: flex;
      align-items: center;
      border-bottom: 1px solid #ddd;
      padding-bottom: 15px;
      margin-bottom: 25px;
    }

    .header-logo {
      width: 60px;
      height: 60px;
    }

    .titulo {
      font-size: 1.8rem;
      font-weight: bold;
      margin-left: 15px;
      color: #2c3e50;
    }

    .content {
      display: flex;
      flex-wrap: wrap;
    }

    .foto-section {
      flex: 1;
      min-width: 200px;
      text-align: center;
    }

    .foto {
      width: 130px;
      height: 130px;
      object-fit: cover;
      border-radius: 50%;
      border: 3px solid #ccc;
      margin-bottom: 10px;
    }

    .nombre {
      font-weight: 700;
      font-size: 1.1rem;
      margin-top: 8px;
    }

    .data-section {
      flex: 2;
      min-width: 300px;
      padding-left: 25px;
    }

    .seccion {
      margin-bottom: 25px;
    }

    .seccion h3 {
      font-size: 1.2rem;
      margin-bottom: 10px;
      color: #555;
      border-left: 4px solid #3498db;
      padding-left: 8px;
    }

    .campo {
      margin-bottom: 6px;
    }

    .etiqueta {
      font-weight: 600;
      color: #555;
    }

    .valor {
      color: #222;
      margin-left: 4px;
    }

    footer {
      text-align: center;
      margin-top: 40px;
      font-size: 0.8rem;
      color: #777;
    }
  </style>
</head>
<body>

  <div class="card">
    <div class="header">
      <img src="<?= $logoUrl ?>" class="header-logo" alt="Logo" />
      <div class="titulo">Ficha de Difunto</div>
    </div>

    <div class="content">
      <div class="foto-section">
        <img src="<?= $photoPath ?: $avatarUrl ?>" class="foto" alt="Foto" />
        <div class="nombre"><?= htmlspecialchars("{$fila['nombre']} {$fila['apellidoPaterno']} {$fila['apellidoMaterno']}") ?></div>
      </div>

      <div class="data-section">
        <div class="seccion">
          <h3>Datos Biográficos</h3>
          <div class="campo"><span class="etiqueta">Fecha Nacimiento:</span><span class="valor"><?= htmlspecialchars($fila['fechaNacimiento']) ?></span></div>
          <div class="campo"><span class="etiqueta">Fecha Defunción:</span><span class="valor"><?= htmlspecialchars($fila['fechaDefuncion']) ?></span></div>
          <div class="campo"><span class="etiqueta">Tipo de Restos:</span><span class="valor"><?= htmlspecialchars($fila['Restos']) ?></span></div>
        </div>

        <div class="seccion">
          <h3>Ubicación en el Cementerio</h3>
          <div class="campo"><span class="etiqueta">Manzana:</span><span class="valor"><?= htmlspecialchars($fila['manzana']) ?></span></div>
          <div class="campo"><span class="etiqueta">Cuadro:</span><span class="valor"><?= htmlspecialchars($fila['cuadro']) ?></span></div>
          <div class="campo"><span class="etiqueta">Fila:</span><span class="valor"><?= htmlspecialchars($fila['fila']) ?></span></div>
          <div class="campo"><span class="etiqueta">Número:</span><span class="valor"><?= htmlspecialchars($fila['numero']) ?></span></div>
          <?php if (!empty($fila['cordenadas'])): ?>
            <div class="campo"><span class="etiqueta">Coordenadas:</span><span class="valor"><?= htmlspecialchars($fila['cordenadas']) ?></span></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <footer>
      Generado por Victorio Grave Search • <?= date('Y-m-d') ?>
    </footer>
  </div>

</body>
</html>
