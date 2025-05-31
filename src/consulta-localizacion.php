<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Victorio Grave Search - Localización</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../css/consulta-localizacion.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

  <style>
    #map {
      height: 500px;
      width: 100%;
      border-radius: 0.5rem;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .leaflet-marker-icon { filter: hue-rotate(160deg); }
    .map-error {
      height: 500px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #dc3545;
      font-weight: bold;
    }
  </style>
</head>

<body>
  <nav class="navbar navbar-dark bg-dark shadow-sm px-4">
    <div class="d-flex align-items-center">
      <a href="../index.html">
        <img src="../img/logo.png" alt="logo" class="me-2" width="40" height="40" />
      </a>
      <span class="navbar-brand fs-4 fw-bold">Victorio Grave Search</span>
    </div>
  </nav>

  <section class="bg-image-banner d-flex align-items-center justify-content-center text-white text-center">
    <div class="bg-overlay"></div>
    <div class="banner-content position-relative z-2">
      <h1 class="fw-bold display-5">Búsqueda por Localización</h1>
      <p class="lead">Encuentra tumbas por manzana, cuadro, fila o número</p>
    </div>
  </section>

  <main class="container my-5">
    <div class="bg-white shadow-lg rounded-4 p-4 mx-auto" style="max-width: 700px;">
      <h4 class="fw-bold mb-4"><i class="fas fa-map-marker-alt me-2"></i>Formulario de Localización</h4>
      <form id="searchForm">
        <div class="row g-4">
          <div class="col-md-6">
            <label class="form-label">Manzana</label>
            <input type="text" class="form-control" id="manzana" name="manzana" placeholder="Ej. 105">
          </div>
          <div class="col-md-6">
            <label class="form-label">Cuadro</label>
            <input type="text" class="form-control" id="cuadro" name="cuadro" placeholder="Ej. 07">
          </div>
          <div class="col-md-6">
            <label class="form-label">Fila</label>
            <input type="text" class="form-control" id="fila" name="fila" placeholder="Ej. 0129">
          </div>
          <div class="col-md-6">
            <label class="form-label">Número</label>
            <input type="text" class="form-control" id="numero" name="numero" placeholder="Ej. 33">
          </div>
        </div>
        <div class="d-flex justify-content-end gap-3 mt-4">
          <button type="reset" class="btn btn-outline-secondary px-4">Limpiar</button>
          <button type="submit" class="btn btn-gold px-4">Buscar</button>
        </div>
      </form>

      <hr class="my-5" />
      <h5 class="text-center fw-bold mb-3">Mapa del Cementerio</h5>
      <div id="map"></div>
    </div>
  </main>

  <footer class="bg-dark text-white text-center py-4 mt-5">
    © 2025 Cementerio Perlas de La Paz — Todos los derechos reservados.
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    const map = L.map('map').setView([24.15277778, -110.245305], 15);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
      maxZoom: 19,
      attribution: '© OpenStreetMap'
    }).addTo(map);

    function dmsToDecimal(dmsStr) {
      if (!dmsStr) return null;
      const cleaned = dmsStr.trim()
        .replace(/"/g, '')
        .replace(/\s+/g, ' ')
        .replace(/([NSEW])/i, ' $1');
      const parts = cleaned.match(/^(\d+)°\s*(\d+)'\s*([\d.]+)\s*([NSEW])$/i);
      if (!parts) return null;
      const deg = parseFloat(parts[1]);
      const min = parseFloat(parts[2]);
      const sec = parseFloat(parts[3]);
      const dir = parts[4].toUpperCase();
      let decimal = deg + min / 60 + sec / 3600;
      if (dir === 'S' || dir === 'W') decimal = -decimal;
      return decimal;
    }

    async function loadMarkers(filters = {}) {
      map.eachLayer(layer => {
        if (layer instanceof L.Marker) map.removeLayer(layer);
      });

      const qs = new URLSearchParams(filters);
      try {
        const res = await fetch(`../php/getDifuntos.php?${qs}`);
        const datos = await res.json();

        datos.forEach(g => {
          if (!g.cordenadas) return;
          const [latDMS, lngDMS] = g.cordenadas.trim().split(/\s+/);
          const lat = dmsToDecimal(latDMS);
          const lon = dmsToDecimal(lngDMS);
          if (lat == null || lon == null) return;

          const marker = L.marker([lat, lon]).addTo(map);
          marker.bindPopup(`
            <b>${g.nombre} ${g.apellidoPaterno}</b><br>
            Manzana: ${g.manzana}<br>
            Cuadro: ${g.cuadro}<br>
            Fila: ${g.fila}<br>
            Número: ${g.numero}<br>
            <a href="resultado.php?id=${g.id}" class="btn btn-sm btn-primary mt-2">Ver detalles</a>
          `);
        });
      } catch (e) {
        console.error(e);
        document.getElementById('map').innerHTML =
          '<div class="map-error">Error al cargar los datos</div>';
      }
    }

    document.getElementById('searchForm').addEventListener('submit', e => {
      e.preventDefault();
      loadMarkers({
        manzana: document.getElementById('manzana').value,
        cuadro:  document.getElementById('cuadro').value,
        fila:    document.getElementById('fila').value,
        numero:  document.getElementById('numero').value
      });
    });

    loadMarkers();
  </script>
</body>
</html>
