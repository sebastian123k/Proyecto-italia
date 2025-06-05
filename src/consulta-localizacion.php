<?php
session_start();
require '../php/conection.php';

// ----------------------------------------------------
// 1. CARGAR LISTAS DE UNIDADES PARA LOS SELECTS
//    (Manzanas, Filas, Cuadros con sus coordenadas DMS)
// ----------------------------------------------------

// 1.1. Manzanas: cada cordenadaX viene en formato DMS "latDMS lonDMS"
$allManzanas = [];
$rsM = $conexion->query("
  SELECT id, numero, cordenada1, cordenada2, cordenada3, cordenada4
  FROM manzana
  ORDER BY numero ASC
");
if ($rsM) {
    $allManzanas = $rsM->fetch_all(MYSQLI_ASSOC);
    $rsM->close();
}

// 1.2. Filas (cada cordenadaX es DMS)
$allFilas = [];
$rsF = $conexion->query("
  SELECT id, numero, idManzana, cordenada1, cordenada2, cordenada3, cordenada4
  FROM fila
  ORDER BY idManzana ASC, numero ASC
");
if ($rsF) {
    $allFilas = $rsF->fetch_all(MYSQLI_ASSOC);
    $rsF->close();
}

// 1.3. Cuadros (cada cordenadaX es DMS)
$allCuadros = [];
$rsC = $conexion->query("
  SELECT id, numero, idFila, cordenada1, cordenada2, cordenada3, cordenada4
  FROM cuadro
  ORDER BY idFila ASC, numero ASC
");
if ($rsC) {
    $allCuadros = $rsC->fetch_all(MYSQLI_ASSOC);
    $rsC->close();
}

$conexion->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Victorio Grave Search – Localización</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../css/consulta-localizacion.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

  <style>
    body { background-color: #f8f9fa; }
    .form-container {
      max-width: 700px;
      margin: auto;
      background: #fff;
      padding: 2rem;
      border-radius: 0.5rem;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    #map {
      height: 500px;
      width: 100%;
      border-radius: 0.5rem;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      margin-top: 2rem;
    }
    .map-error {
      height: 500px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #dc3545;
      font-weight: bold;
    }
    .btn-gold {
      background-color: #d4af37;
      color: #000;
    }
    .btn-gold:hover {
      background-color: #b5982f;
    }
    .icono-usuario {
      width: 24px;
      height: 24px;
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
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <?php if (isset($_SESSION['usuario_id'])): ?>
          <li class="nav-item">
            <a class="nav-link d-flex align-items-center" href="configuracion-usuario.php">
              <i class="fas fa-user icono-usuario me-1"></i>
              <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="inicion-sesion-usuarios.php">Iniciar Sesión</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="registro-usuarios.php">Registrarse</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </nav>

  <section class="py-5 bg-light text-center">
    <h1 class="fw-bold">Búsqueda por Localización</h1>
    <p class="lead">Encuentra tumbas por manzana, fila o cuadro</p>
  </section>

  <main class="container my-5">
    <div class="form-container">
      <h4 class="mb-4"><i class="fas fa-map-marker-alt me-2"></i>Formulario de Localización</h4>
      <form id="searchForm">
        <div class="row g-3">
          <!-- SELECT Manzana -->
          <div class="col-md-6">
            <label class="form-label">Manzana</label>
            <select id="selManzana" class="form-select">
              <option value="">— Seleccionar —</option>
              <?php foreach ($allManzanas as $m): ?>
                <option value="<?= htmlspecialchars($m['id']) ?>">
                  <?= htmlspecialchars($m['numero']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <!-- SELECT Fila -->
          <div class="col-md-6" id="divFila" style="display:none;">
            <label class="form-label">Fila</label>
            <select id="selFila" class="form-select" disabled>
              <option value="">— Seleccionar —</option>
            </select>
          </div>
          <!-- SELECT Cuadro -->
          <div class="col-md-6" id="divCuadro" style="display:none;">
            <label class="form-label">Cuadro</label>
            <select id="selCuadro" class="form-select" disabled>
              <option value="">— Seleccionar —</option>
            </select>
          </div>
          <!-- Número opcional -->
          <div class="col-md-6">
            <label class="form-label">Número (opcional)</label>
            <input type="text" id="numero" name="numero" class="form-control" placeholder="Ej. 33">
          </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
          <button type="reset" class="btn btn-outline-secondary">Limpiar</button>
          <button type="submit" class="btn btn-gold">Buscar</button>
        </div>
      </form>
      <div id="map"></div>
    </div>
  </main>

  <footer class="bg-dark text-white text-center py-3">
    © 2025 Cementerio Perlas de La Paz — Todos los derechos reservados.
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    // 1) Convertir datos PHP → JS
    const jsManzanas = <?= json_encode($allManzanas, JSON_UNESCAPED_UNICODE) ?>;
    const jsFilas    = <?= json_encode($allFilas,    JSON_UNESCAPED_UNICODE) ?>;
    const jsCuadros  = <?= json_encode($allCuadros,  JSON_UNESCAPED_UNICODE) ?>;

    // 2) Convertir DMS a decimal (para marcadores de difuntos)
    function dmsToDecimal(dms) {
      if (!dms) return null;
      // Acepta: 24°09'10.4"N  o 24° 09'  10.4 N (con o sin comillas)
      const parts = dms.match(/(\d+)°\s*(\d+)'s*([\d.]+)"?\s*([NSEW])/i);
      if (!parts) return null;
      let deg = parseFloat(parts[1]);
      let min = parseFloat(parts[2]);
      let sec = parseFloat(parts[3]);
      let dir = parts[4].toUpperCase();
      let dec = deg + (min / 60) + (sec / 3600);
      if (dir === 'S' || dir === 'W') dec = -dec;
      return dec;
    }

    // 3) Poblar selects de Filas / Cuadros
    function poblarFilas(selectElem, manzanaId) {
      selectElem.innerHTML = '<option value="">— Seleccionar —</option>';
      if (!manzanaId) {
        selectElem.disabled = true;
        return;
      }
      let filas = jsFilas.filter(f => parseInt(f.idManzana) === parseInt(manzanaId));
      if (filas.length === 0) {
        selectElem.innerHTML = '<option value="">(Sin filas)</option>';
        selectElem.disabled = true;
        return;
      }
      filas.forEach(f => {
        let opt = document.createElement('option');
        opt.value = f.id;
        opt.textContent = f.numero;
        selectElem.appendChild(opt);
      });
      selectElem.disabled = false;
    }

    function poblarCuadros(selectElem, filaId) {
      selectElem.innerHTML = '<option value="">— Seleccionar —</option>';
      if (!filaId) {
        selectElem.disabled = true;
        return;
      }
      let cuadros = jsCuadros.filter(c => parseInt(c.idFila) === parseInt(filaId));
      if (cuadros.length === 0) {
        selectElem.innerHTML = '<option value="">(Sin cuadros)</option>';
        selectElem.disabled = true;
        return;
      }
      cuadros.forEach(c => {
        let opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = c.numero;
        selectElem.appendChild(opt);
      });
      selectElem.disabled = false;
    }

    // 4) Lógica de selects encadenados
    document.addEventListener('DOMContentLoaded', () => {
      const selManzana = document.getElementById('selManzana');
      const selFila    = document.getElementById('selFila');
      const selCuadro  = document.getElementById('selCuadro');
      const divFila    = document.getElementById('divFila');
      const divCuadro  = document.getElementById('divCuadro');

      selManzana.addEventListener('change', () => {
        const idM = selManzana.value;
        if (!idM) {
          divFila.style.display = 'none';
          divCuadro.style.display = 'none';
          selFila.innerHTML = '<option value="">— Seleccionar —</option>';
          selCuadro.innerHTML = '<option value="">— Seleccionar —</option>';
          selFila.disabled = true;
          selCuadro.disabled = true;
        } else {
          divFila.style.display = 'block';
          poblarFilas(selFila, idM);
          selCuadro.innerHTML = '<option value="">— Seleccionar —</option>';
          selCuadro.disabled = true;
          divCuadro.style.display = 'none';
        }
      });

      selFila.addEventListener('change', () => {
        const idF = selFila.value;
        if (!idF) {
          divCuadro.style.display = 'none';
          selCuadro.innerHTML = '<option value="">— Seleccionar —</option>';
          selCuadro.disabled = true;
        } else {
          divCuadro.style.display = 'block';
          poblarCuadros(selCuadro, idF);
        }
      });
    });

    // 5) Inicializar mapa Leaflet
    const map = L.map('map').setView([24.15277778, -110.245305], 15);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
      maxZoom: 19,
      attribution: '© OpenStreetMap'
    }).addTo(map);

    // 6) Ícono de lápida (PNG de tumba)
    const tombIcon = L.icon({
      iconUrl: 'https://cdn-icons-png.flaticon.com/512/15081/15081676.png',
      iconSize: [32, 32],
      iconAnchor: [16, 32],
      popupAnchor: [0, -32]
    });

    let currentShapes = { manzana: null, fila: null, cuadro: null };

    // 7) Dibuja rectángulo usando las cuatro esquinas, cada esquina en DMS "latDMS lonDMS"
    function drawRectCornersDMS(c1, c2, c3, c4, color) {
      function parseCorner(cornerStr) {
        const parts = cornerStr.trim().split(/\s+/);
        if (parts.length < 2) return [null, null];
        const lat = dmsToDecimal(parts[0]);
        const lon = dmsToDecimal(parts[1]);
        return [lat, lon];
      }

      const [lat1, lon1] = parseCorner(c1);
      const [lat2, lon2] = parseCorner(c2);
      const [lat3, lon3] = parseCorner(c3);
      const [lat4, lon4] = parseCorner(c4);

      if ([lat1, lon1, lat2, lon2, lat3, lon3, lat4, lon4].every(v => v !== null)) {
        return L.polygon([
          [lat1, lon1],  // NW
          [lat2, lon2],  // NE
          [lat3, lon3],  // SE
          [lat4, lon4]   // SW
        ], {
          color: color,
          weight: 3,
          fill: false
        }).addTo(map);
      }
      return null;
    }

    // 8) Cargar marcadores y polígonos
    async function loadMarkers(filters = {}) {
      // 8.1. Borrar anteriores L.Markers y L.Polygons (no borra tileLayer)
      map.eachLayer(layer => {
        if (layer instanceof L.Marker || layer instanceof L.Polygon) {
          map.removeLayer(layer);
        }
      });
      currentShapes = { manzana: null, fila: null, cuadro: null };

      // 8.2. Obtener difuntos vía AJAX
      const qs = new URLSearchParams(filters);
      try {
        const res = await fetch(`../php/getDifuntos.php?${qs}`);
        const datos = await res.json();
        datos.forEach(g => {
          if (!g.cordenadas) return;
          const parts = g.cordenadas.trim().split(/\s+/);
          if (parts.length < 2) return;
          const lat = dmsToDecimal(parts[0]);
          const lon = dmsToDecimal(parts[1]);
          if (lat == null || lon == null) return;
          L.marker([lat, lon], { icon: tombIcon })
            .addTo(map)
            .bindPopup(`
              <b>${g.nombre} ${g.apellidoPaterno}</b><br>
              Manzana: ${g.manzana}<br>
              Cuadro: ${g.cuadro}<br>
              Fila: ${g.fila}<br>
              Número: ${g.numero}<br>
              <a href="resultado.php?id=${g.id}" class="btn btn-sm btn-primary mt-2">Ver detalles</a>
            `);
        });
      } catch (err) {
        console.error(err);
        document.getElementById('map').innerHTML =
          '<div class="map-error">Error al cargar los datos</div>';
        return;
      }

      // 8.3. Dibujar polígonos: manzana → fila → cuadro
      const idMan = document.getElementById('selManzana').value;
      const idFil = document.getElementById('selFila').value;
      const idCua = document.getElementById('selCuadro').value;

      if (idMan) {
        const m = jsManzanas.find(x => String(x.id) === idMan);
        if (m) {
          currentShapes.manzana = drawRectCornersDMS(
            m.cordenada1, m.cordenada2, m.cordenada3, m.cordenada4,
            '#d4af37'
          );
        }
      }
      if (idFil) {
        const f = jsFilas.find(x => String(x.id) === idFil);
        if (f) {
          currentShapes.fila = drawRectCornersDMS(
            f.cordenada1, f.cordenada2, f.cordenada3, f.cordenada4,
            '#007bff'
          );
        }
      }
      if (idCua) {
        const c = jsCuadros.find(x => String(x.id) === idCua);
        if (c) {
          currentShapes.cuadro = drawRectCornersDMS(
            c.cordenada1, c.cordenada2, c.cordenada3, c.cordenada4,
            '#28a745'
          );
        }
      }

      // 8.4. Ajustar vista: si hay polígonos, fitBounds; si no, vista inicial
      const shapes = Object.values(currentShapes).filter(s => s !== null);
      if (shapes.length > 0) {
        const group = L.featureGroup(shapes);
        map.fitBounds(group.getBounds().pad(0.2));
      } else {
        map.setView([24.15277778, -110.245305], 15);
      }
    }

    // 9) Al hacer “Buscar”
    document.getElementById('searchForm').addEventListener('submit', e => {
      e.preventDefault();
      const selManzana = document.getElementById('selManzana');
      const selFila    = document.getElementById('selFila');
      const selCuadro  = document.getElementById('selCuadro');
      let manzanaTxt = '', filaTxt = '', cuadroTxt = '';

      if (selManzana.value) {
        const m = jsManzanas.find(x => String(x.id) === selManzana.value);
        manzanaTxt = m ? m.numero : '';
      }
      if (selFila.value) {
        const f = jsFilas.find(x => String(x.id) === selFila.value);
        filaTxt = f ? f.numero : '';
      }
      if (selCuadro.value) {
        const c = jsCuadros.find(x => String(x.id) === selCuadro.value);
        cuadroTxt = c ? c.numero : '';
      }

      loadMarkers({
        manzana: manzanaTxt,
        cuadro:  cuadroTxt,
        fila:    filaTxt,
        numero:  document.getElementById('numero').value.trim()
      });
    });

    // 10) Carga inicial (solo marcadores, sin polígonos)
    loadMarkers();
  </script>
</body>
</html>
