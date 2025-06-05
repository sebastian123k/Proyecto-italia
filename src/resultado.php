<?php
// src/resultado.php

require '../php/conection.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "ID no proporcionado.";
    exit;
}

// 1) Obtener datos del difunto
$sql = "SELECT 
          t.manzana, 
          t.cuadro, 
          t.fila, 
          t.numero, 
          t.cordenadas, 
          d.apellidoPaterno, 
          d.apellidoMaterno, 
          d.nombre, 
          d.fechaNacimiento, 
          d.fechaDefuncion, 
          d.Restos,
          d.imagen
        FROM difuntos d
        JOIN tumbas t ON d.idTumba = t.id
        WHERE d.id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
if ($resultado->num_rows === 0) {
    echo "No se encontró el registro.";
    exit;
}
$fila = $resultado->fetch_assoc();
$stmt->close();
$conexion->close();

// 2) Determinar ruta de la foto (URL relativa para el navegador)
$photoPath = '';
if (!empty($fila['imagen'])) {
    $candidate = __DIR__ . '/../photos/' . $fila['imagen'];
    if (file_exists($candidate)) {
        $photoPath = '../photos/' . rawurlencode($fila['imagen']);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Resultado de Búsqueda - Victorio Grave Search</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../css/resultado.css" />

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

  <style>
    #map {
      height: 400px;
      width: 100%;
      border-radius: 0.5rem;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .map-error {
      height: 400px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #dc3545;
      font-weight: bold;
    }
    .photo-display {
      width: 120px;
      height: 120px;
      object-fit: cover;
      border-radius: 50%;
      border: 2px solid #ddd;
      box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }
  </style>
</head>

<body class="d-flex flex-column min-vh-100">

  <nav class="navbar navbar-dark bg-dark shadow-sm px-4">
    <div class="d-flex align-items-center">
      <a href="../index.html"><img src="../img/logo.png" alt="logo" class="me-2" width="40" height="40" /></a>
      <span class="navbar-brand fs-4 fw-bold">Victorio Grave Search</span>
    </div>
  </nav>

  <main class="flex-grow-1 d-flex align-items-center justify-content-center bg-light py-5">
    <div class="result-card bg-white shadow rounded-4 p-4" style="width: 100%; max-width: 900px;">
      <div class="d-flex flex-column flex-md-row align-items-start gap-4">

        <div class="text-center">
          <?php if ($photoPath): ?>
            <img src="<?= $photoPath ?>" alt="Foto del fallecido" class="photo-display" />
          <?php else: ?>
            <img src="../img/avatar.png" alt="Foto ilustrativa" class="photo-display" />
          <?php endif; ?>
          <?php if ($photoPath): ?>
            <p class="mt-2 small text-muted">Foto real</p>
          <?php else: ?>
            <p class="mt-2 small text-muted">Foto no disponible</p>
          <?php endif; ?>
        </div>

        <div class="flex-grow-1">
          <h4 class="fw-bold mb-3">
            <i class="fas fa-user me-2 text-secondary"></i>
            <?= htmlspecialchars("{$fila['nombre']} {$fila['apellidoPaterno']} {$fila['apellidoMaterno']}") ?>
          </h4>
          <div class="row gy-2">
            <div class="col-md-6">
              <strong><i class="fas fa-map me-1 text-gold"></i>Manzana:</strong> <?= htmlspecialchars($fila['manzana']) ?>
            </div>
            <div class="col-md-6">
              <strong><i class="fas fa-map me-1 text-gold"></i>Cuadro:</strong> <?= htmlspecialchars($fila['cuadro']) ?>
            </div>
            <div class="col-md-6">
              <strong><i class="fas fa-layer-group me-1 text-gold"></i>Fila:</strong> <?= htmlspecialchars($fila['fila']) ?>
            </div>
            <div class="col-md-6">
              <strong><i class="fas fa-cross me-1 text-gold"></i>Número:</strong> <?= htmlspecialchars($fila['numero']) ?>
            </div>
            <div class="col-md-6">
              <strong><i class="fas fa-calendar-day me-1 text-gold"></i>Fecha Nacimiento:</strong> <?= htmlspecialchars($fila['fechaNacimiento']) ?>
            </div>
            <div class="col-md-6">
              <strong><i class="fas fa-calendar-day me-1 text-gold"></i>Fecha Defunción:</strong> <?= htmlspecialchars($fila['fechaDefuncion']) ?>
            </div>
            <div class="col-md-6">
              <strong><i class="fas fa-skull-crossbones me-1 text-gold"></i>Tipo de Restos:</strong> <?= htmlspecialchars($fila['Restos']) ?>
            </div>
          </div>
        </div>

        <div class="text-md-end d-flex flex-column flex-md-row gap-2 justify-content-md-end">
          <a href="consulta-persona.php" class="btn btn-gold fw-semibold px-4">
            <i class="fas fa-search-location me-2"></i>Otra búsqueda
          </a>
          <a href="resultado-pdf.php?id=<?= htmlspecialchars($id) ?>" class="btn btn-primary fw-semibold px-4">
            <i class="fas fa-file-pdf me-2"></i>Descargar PDF
          </a>
        </div>
      </div>

      <hr class="my-4" />

      <div>
        <h5 class="fw-bold mb-3"><i class="fas fa-map-location-dot me-2"></i>Ubicación en el cementerio</h5>
        <div id="map"></div>
      </div>

      <script>
        function dmsToDecimal(dmsStr) {
          const cleaned = dmsStr.trim().replace(/"/g, '').replace(/\s+/g, ' ').replace(/([NSEW])/i, ' $1');
          const parts = cleaned.match(/^(\d+)°\s*(\d+)'s*([\d.]+)\s*([NSEW])$/i);
          if (!parts) return null;
          const [_, d, m, s, dir] = parts;
          let dec = Number(d) + Number(m)/60 + Number(s)/3600;
          if (dir.toUpperCase() === 'S' || dir.toUpperCase() === 'W') dec = -dec;
          return dec;
        }

        const coordsDMS = <?= json_encode($fila['cordenadas'] ?? '') ?>;

        if (!coordsDMS || coordsDMS.trim() === '') {
          document.getElementById('map').innerHTML = '<div class="map-error">Ubicación no disponible</div>';
        } else {
          try {
            const [latDMS, lngDMS] = coordsDMS.trim().split(/\s+/);
            const lat = dmsToDecimal(latDMS);
            const lng = dmsToDecimal(lngDMS);
            if (lat === null || lng === null) throw new Error('Coordenadas inválidas');

            const map = L.map('map').setView([24.15277778, -110.245305], 15);

            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
              maxZoom: 19,
              attribution: '© OpenStreetMap'
            }).addTo(map);

            const tombIcon = L.icon({
              iconUrl: 'https://cdn-icons-png.flaticon.com/512/15081/15081676.png',
              iconSize: [32, 32],
              iconAnchor: [16, 32],
              popupAnchor: [0, -32]
            });

            L.marker([lat, lng], { icon: tombIcon })
              .addTo(map)
              .bindPopup('<?= htmlspecialchars($fila['nombre'] . " " . $fila['apellidoPaterno']) ?>')
              .openPopup();

          } catch (e) {
            console.error(e);
            document.getElementById('map').innerHTML = '<div class="map-error">Error al mostrar la ubicación</div>';
          }
        }
      </script>

    </div>
  </main>

  <footer class="bg-dark text-white text-center py-3 mt-auto">
    © 2025 Cementerio Perlas de La Paz
  </footer>

</body>
</html>
