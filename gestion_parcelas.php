<?php
/**
 * ARCHIVO: gestion_parcelas.php
 * PROYECTO: Gestión Agrícola 2.0
 * DESCRIPCIÓN:
 * Interfaz GIS (Sistema de Información Geográfica) para la digitalización
 * de parcelas agrícolas utilizando la librería Leaflet y capas WMS.
 */
session_start();
require("conexion.php");

if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 0) {
    header("Location: index.php");
    exit();
}

$mensaje_global = "";

// -----------------------------------------------------------------------------
// 1. GUARDAR PARCELA (INSERT con GeoJSON)
// -----------------------------------------------------------------------------
if (isset($_POST['btn_guardar'])) {
    $login_agricultor = $_POST['login_agricultor'];
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $poligono = $_POST['poligono'];
    $referencia = $_POST['referencia'];
    $hectareas = $_POST['hectareas'];
    $municipio = mysqli_real_escape_string($conexion, $_POST['municipio']);

    // Datos espaciales
    $lat = $_POST['latitud'];
    $lon = $_POST['longitud'];
    $borde = $_POST['borde_poligono']; // Cadena JSON de coordenadas

    if (empty($login_agricultor) || empty($nombre) || empty($borde)) {
        $mensaje_global = "<div class='mensaje alerta-roja'>ERROR: Es necesario dibujar la parcela en el mapa.</div>";
    } else {
        $sql = "INSERT INTO parcelas (login_agricultor, nombre, poligono, referencia_catastral, hectareas, municipio, latitud, longitud, borde_poligono)
                VALUES ('$login_agricultor', '$nombre', '$poligono', '$referencia', '$hectareas', '$municipio', '$lat', '$lon', '$borde')";

        if (mysqli_query($conexion, $sql)) {
            $mensaje_global = "<div class='mensaje alerta-verde'>Finca registrada y digitalizada correctamente.</div>";
        } else {
            $mensaje_global = "<div class='mensaje alerta-roja'>Error SQL: " . mysqli_error($conexion) . "</div>";
        }
    }
}

// -----------------------------------------------------------------------------
// 2. ELIMINAR PARCELA
// -----------------------------------------------------------------------------
if (isset($_POST['id_borrar'])) {
    $id = $_POST['id_borrar'];

    // Verificar dependencias antes de borrar
    $check = mysqli_query($conexion, "SELECT * FROM trabajos WHERE codigo_parcela = $id");
    if (mysqli_num_rows($check) > 0) {
        $mensaje_global = "<div class='mensaje alerta-roja'>No se puede borrar: Existen trabajos asociados a esta tierra.</div>";
    } else {
        mysqli_query($conexion, "DELETE FROM parcelas WHERE codigo_parcela = $id");
        $mensaje_global = "<div class='mensaje alerta-verde'>Parcela eliminada.</div>";
    }
}

include("includes/header.php");
?>

<h1>Digitalización de Tierras (GIS)</h1>
<?php echo $mensaje_global; ?>

<div class="contenedor-mapa">
    <div class="col-form">
        <h3>Alta de Finca</h3>
        <p style="font-size:0.9em; color:#666;">Marque los vértices en el mapa para definir el perímetro.</p>

        <form action="gestion_parcelas.php" method="POST">
            <label>Propietario:</label>
            <select name="login_agricultor" required>
                <option value="">-- Seleccionar Cliente --</option>
                <?php
                $res_cli = mysqli_query($conexion, "SELECT * FROM usuarios WHERE tipo=2 ORDER BY nombre");
                while ($c = mysqli_fetch_array($res_cli)) {
                    echo "<option value='" . $c['login'] . "'>" . $c['nombre'] . " " . $c['apellidos'] . "</option>";
                }
                ?>
            </select>

            <label>Nombre Finca:</label>
            <input type="text" name="nombre" required placeholder="Ej: La Vaguada">

            <div style="display:flex; gap:10px;">
                <div style="flex:1"><input type="text" name="municipio" placeholder="Municipio"></div>
                <div style="flex:1"><input type="text" name="poligono" placeholder="Polígono"></div>
            </div>
            <div style="display:flex; gap:10px;">
                <div style="flex:1"><input type="text" name="referencia" placeholder="Ref. Catastral"></div>
                <div style="flex:1"><input type="text" name="hectareas" placeholder="Hectáreas"></div>
            </div>

            <input type="hidden" name="latitud" id="lat">
            <input type="hidden" name="longitud" id="lon">
            <input type="hidden" name="borde_poligono" id="borde_poligono">

            <input type="text" id="estado_dibujo" readonly value="Esperando trazo..." style="background:#f1f1f1; border:none; width:100%; margin-bottom:10px; font-style:italic;">
            <input type="submit" name="btn_guardar" value="GUARDAR DATOS" style="width:100%;">
        </form>
    </div>

    <div class="col-visor">
        <div class="herramientas-mapa" style="display:flex; justify-content:space-between; align-items:center;">
            <span>MODO SATÉLITE</span>
            <button type="button" onclick="borrarDibujo()" style="background:#dc3545; color:white; border:none; padding:5px 10px; cursor:pointer;">BORRAR TRAZO</button>
        </div>
        <div id="mapa" class="modo-dibujo"></div>
    </div>
</div>

<hr>

<h3>Listado de Parcelas</h3>
<table id="tablaParcelas">
    <thead>
        <tr>
            <th>ID</th>
            <th>Finca</th>
            <th>Propietario</th>
            <th>Ubicación</th>
            <th>Hectáreas</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $sql_list = "SELECT p.*, u.nombre as n_prop, u.apellidos as a_prop
                     FROM parcelas p
                     LEFT JOIN usuarios u ON p.login_agricultor = u.login
                     ORDER BY p.codigo_parcela DESC";
        $res_list = mysqli_query($conexion, $sql_list);
        $geojson_data = array();

        while ($p = mysqli_fetch_array($res_list)) {
            // Preparar datos para JS
            if (!empty($p['borde_poligono'])) {
                $geojson_data[] = array(
                    "id" => $p['codigo_parcela'],
                    "nombre" => $p['nombre'],
                    "owner" => $p['n_prop'] . " " . $p['a_prop'],
                    "coords" => json_decode($p['borde_poligono'])
                );
            }

            echo "<tr onclick='focalizarParcela(" . $p['codigo_parcela'] . ")' style='cursor:pointer;'>";
            echo "<td>" . $p['codigo_parcela'] . "</td>";
            echo "<td><b>" . $p['nombre'] . "</b></td>";
            echo "<td>" . ($p['n_prop'] ? $p['n_prop'] . " " . $p['a_prop'] : "Usuario borrado") . "</td>";
            echo "<td>" . $p['municipio'] . " (Pol: " . $p['poligono'] . ")</td>";
            echo "<td>" . $p['hectareas'] . " ha</td>";
            echo "<td onclick='event.stopPropagation();'>
                    <form method='POST' onsubmit='return confirm(\"¿Eliminar finca?\");' style='margin:0;'>
                        <input type='hidden' name='id_borrar' value='" . $p['codigo_parcela'] . "'>
                        <input type='submit' value='Eliminar' class='btn-accion btn-borrar'>
                    </form>
                  </td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

<?php include("includes/footer.php"); ?>

<script>
    // Configuración inicial (Coordenadas solicitadas conservadas)
    var map = L.map('mapa').setView([40.11234572387146, -6.0852821008638065], 14);

    // Capas Base (PNOA y Catastro)
    L.tileLayer.wms('https://www.ign.es/wms-inspire/pnoa-ma?', {layers: 'OI.OrthoimageCoverage', format: 'image/png', transparent: false}).addTo(map);
    L.tileLayer.wms('https://ovc.catastro.meh.es/Cartografia/WMS/ServidorWMS.aspx', {layers: 'PARCELA', format: 'image/png', transparent: true}).addTo(map);

    // Variables de dibujo
    var puntos = [];
    var polyActual = null;
    var markers = [];

    // Evento Clic en Mapa (Dibujo)
    map.on('click', function (e) {
        puntos.push([e.latlng.lat, e.latlng.lng]);

        var m = L.circleMarker([e.latlng.lat, e.latlng.lng], {color: 'white', radius: 4}).addTo(map);
        markers.push(m);

        if (polyActual)
            map.removeLayer(polyActual);
        polyActual = L.polygon(puntos, {color: '#dc3545', fillColor: '#dc3545', fillOpacity: 0.4}).addTo(map);

        actualizarInput();
    });

    function actualizarInput() {
        document.getElementById('borde_poligono').value = JSON.stringify(puntos);

        // Centroide simple
        if (puntos.length > 0) {
            var lat = 0, lon = 0;
            puntos.forEach(p => {
                lat += p[0];
                lon += p[1];
            });
            document.getElementById('lat').value = (lat / puntos.length).toFixed(6);
            document.getElementById('lon').value = (lon / puntos.length).toFixed(6);
            document.getElementById('estado_dibujo').value = puntos.length + " vértices capturados.";
        }
    }

    function borrarDibujo() {
        puntos = [];
        if (polyActual)
            map.removeLayer(polyActual);
        markers.forEach(m => map.removeLayer(m));
        markers = [];
        document.getElementById('borde_poligono').value = "";
        document.getElementById('estado_dibujo').value = "Dibujo reiniciado.";
    }

    // Carga de parcelas existentes (Solo lectura visual)
    var datosDB = <?php echo json_encode($geojson_data); ?>;
    var capas = {};

    datosDB.forEach(function (d) {
        if (d.coords) {
            var p = L.polygon(d.coords, {color: '#007bff', fillColor: '#007bff', fillOpacity: 0.2, weight: 1})
                    .bindPopup("<b>" + d.nombre + "</b><br>" + d.owner)
                    .addTo(map);
            capas[d.id] = p;
        }
    });

    function focalizarParcela(id) {
        var c = capas[id];
        if (c) {
            map.flyToBounds(c.getBounds(), {padding: [50, 50], duration: 1.5});
            c.openPopup();
            document.querySelector('.contenedor-mapa').scrollIntoView({behavior: 'smooth'});
        }
    }
</script>