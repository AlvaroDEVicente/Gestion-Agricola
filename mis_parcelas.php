<?php
/**
 * ARCHIVO: mis_parcelas.php
 * PROYECTO: Gestión Agrícola 2.0
 * DESCRIPCIÓN:
 * Panel de gestión de propiedades para el agricultor.
 * Funcionalidades:
 * 1. Visualización de sus tierras en mapa (Leaflet).
 * 2. Solicitud de servicios agrícolas (Genera una orden de trabajo pendiente).
 */
session_start();
require("conexion.php");

if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 2) {
    header("Location: index.php");
    exit();
}

$mi_login = $_SESSION['usuario'];
$mensaje = "";

// -----------------------------------------------------------------------------
// 1. PROCESAR SOLICITUD DE SERVICIO (Nueva Orden de Trabajo)
// -----------------------------------------------------------------------------
if (isset($_POST['btn_solicitar'])) {
    $id_parcela = (int) $_POST['parcela_id'];
    $tipo_labor = mysqli_real_escape_string($conexion, $_POST['tipo_labor']);
    $fecha_sol = date('Y-m-d');

    // Se crea el trabajo en estado 0 (Pendiente) y sin recursos asignados (NULL)
    // El administrador asignará la máquina posteriormente.
    $sql_req = "INSERT INTO trabajos (tipo_trabajo, estado, fecha_solicitud, codigo_parcela, codigo_maquina, login_maquinista, horas_reales)
                VALUES ('$tipo_labor', 0, '$fecha_sol', $id_parcela, NULL, NULL, 0.00)";

    if (mysqli_query($conexion, $sql_req)) {
        $mensaje = "<div class='mensaje alerta-verde'>Solicitud enviada. El administrador procesará su petición.</div>";
    } else {
        $mensaje = "<div class='mensaje alerta-roja'>Error al solicitar servicio: " . mysqli_error($conexion) . "</div>";
    }
}

include("includes/header.php");
?>

<h1>Mis Tierras y Servicios</h1>
<?php echo $mensaje; ?>

<div class="contenedor-mapa">

    <div class="col-form" style="flex: 1.2;">
        <h3>Gestionar Propiedades</h3>
        <p>Seleccione una parcela para ubicarla o solicite una labor.</p>

        <table style="font-size: 0.85em;">
            <thead>
                <tr>
                    <th>Parcela</th>
                    <th>Datos Catastrales</th>
                    <th>Solicitar Servicio</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT * FROM parcelas WHERE login_agricultor = '$mi_login' ORDER BY nombre ASC";
                $res = mysqli_query($conexion, $sql);
                $geojson_data = array();

                if (mysqli_num_rows($res) == 0) {
                    echo "<tr><td colspan='3'>No tiene parcelas registradas. Contacte con el administrador.</td></tr>";
                }

                while ($p = mysqli_fetch_array($res)) {
                    // Datos para JS
                    if (!empty($p['borde_poligono'])) {
                        $geojson_data[] = array(
                            "id" => $p['codigo_parcela'],
                            "nombre" => $p['nombre'],
                            "coords" => json_decode($p['borde_poligono'])
                        );
                    }

                    echo "<tr onclick='focalizarParcela(" . $p['codigo_parcela'] . ")' style='cursor:pointer;'>";

                    // Info Parcela
                    echo "<td><b>" . $p['nombre'] . "</b><br><small>" . $p['municipio'] . "</small></td>";
                    echo "<td>Ref: " . $p['referencia_catastral'] . "<br>" . $p['hectareas'] . " ha</td>";

                    // Formulario de Solicitud
                    echo "<td onclick='event.stopPropagation();' style='background:#f9f9f9; padding:5px;'>";
                    echo "<form method='POST' style='display:flex; flex-direction:column; gap:5px; margin:0;'>";
                    echo "<input type='hidden' name='parcela_id' value='" . $p['codigo_parcela'] . "'>";
                    echo "<select name='tipo_labor' required style='padding:3px; font-size:0.9em;'>
                            <option value=''>-- Seleccionar --</option>
                            <option value='Arado'>Arado</option>
                            <option value='Siembra'>Siembra</option>
                            <option value='Fumigación'>Fumigación</option>
                            <option value='Cosecha'>Cosecha</option>
                            <option value='Desbroce'>Desbroce</option>
                          </select>";
                    echo "<button type='submit' name='btn_solicitar' class='btn-accion btn-azul-oscuro' style='width:100%;'>Solicitar</button>";
                    echo "</form>";
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="col-visor">
        <div class="herramientas-mapa" style="background: #28a745;">VISOR DE EXPLOTACIONES</div>
        <div id="mapa"></div>
    </div>
</div>

<?php include("includes/footer.php"); ?>

<script>
    var map = L.map('mapa').setView([40.4167, -3.7032], 6);
    L.tileLayer.wms('https://www.ign.es/wms-inspire/pnoa-ma?', {layers: 'OI.OrthoimageCoverage', format: 'image/png', transparent: false}).addTo(map);
    L.tileLayer.wms('https://ovc.catastro.meh.es/Cartografia/WMS/ServidorWMS.aspx', {layers: 'PARCELA', format: 'image/png', transparent: true}).addTo(map);

    var parcelas = <?php echo json_encode($geojson_data); ?>;
    var capas = {};
    var grupo = L.featureGroup();

    parcelas.forEach(function (p) {
        if (p.coords) {
            var poly = L.polygon(p.coords, {color: '#ffc107', fillColor: '#ffc107', fillOpacity: 0.3, weight: 2}).bindPopup("<b>" + p.nombre + "</b>").addTo(map);
            capas[p.id] = poly;
            grupo.addLayer(poly);
        }
    });

    if (parcelas.length > 0) {
        map.fitBounds(grupo.getBounds(), {padding: [50, 50]});
    }

    function focalizarParcela(id) {
        var capa = capas[id];
        if (capa) {
            map.flyToBounds(capa.getBounds(), {padding: [50, 50], duration: 1.5});
            capa.openPopup();
        }
    }
</script>