<?php
/**
 * ARCHIVO: mis_trabajos.php
 * PROYECTO: Gestión Agrícola 2.0
 * DESCRIPCIÓN:
 * Interfaz operativa para el maquinista.
 * Permite visualizar tareas asignadas en el mapa y cambiar su estado:
 * Pendiente -> En Curso -> Finalizado (registrando horas).
 */
session_start();
require("conexion.php");

// 1. CONTROL DE ACCESO (Solo Maquinistas)
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 1) {
    header("Location: index.php");
    exit();
}

$mi_login = $_SESSION['usuario'];
$mensaje_global = "";

// -----------------------------------------------------------------------------
// 2. LOGICA DE CAMBIO DE ESTADO (Workflow)
// -----------------------------------------------------------------------------
if (isset($_POST['accion'])) {
    $id_trabajo = (int) $_POST['id_trabajo'];
    $accion = $_POST['accion'];

    // ACCIÓN: INICIAR TRABAJO
    if ($accion == 'iniciar') {
        $sql = "UPDATE trabajos SET estado = 1 WHERE codigo_trabajo = $id_trabajo AND login_maquinista = '$mi_login'";
        if (mysqli_query($conexion, $sql)) {
            $mensaje_global = "<div class='mensaje alerta-verde'>Trabajo iniciado. El cronómetro comienza.</div>";
        }
    }

    // ACCIÓN: FINALIZAR TRABAJO
    if ($accion == 'finalizar') {
        $horas = (float) $_POST['horas_reales'];
        if ($horas > 0) {
            $sql = "UPDATE trabajos SET estado = 2, horas_reales = '$horas'
                    WHERE codigo_trabajo = $id_trabajo AND login_maquinista = '$mi_login'";
            if (mysqli_query($conexion, $sql)) {
                $mensaje_global = "<div class='mensaje alerta-verde'>Trabajo cerrado. Parte de horas guardado.</div>";
            } else {
                $mensaje_global = "<div class='mensaje alerta-roja'>Error al guardar.</div>";
            }
        } else {
            $mensaje_global = "<div class='mensaje alerta-roja'>ERROR: Indique un número de horas válido.</div>";
        }
    }
}

include("includes/header.php");
?>

<h1>Mis Asignaciones y Ruta</h1>
<?php echo $mensaje_global; ?>

<div class="contenedor-mapa">
    <div class="col-form" style="overflow-y: auto; max-height: 80vh;">
        <h3>Órdenes de Trabajo</h3>
        <p>Haga clic en una tarea para ver la ubicación.</p>

        <table style="font-size: 0.85em;">
            <thead>
                <tr>
                    <th>Tarea / Fecha</th>
                    <th>Ubicación / Máquina</th>
                    <th>Estado / Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Obtener trabajos asignados + geometría de la parcela
                $sql = "SELECT t.*, p.nombre as nom_par, p.municipio, p.hectareas, p.borde_poligono, m.nombre as nom_maq
                        FROM trabajos t
                        JOIN parcelas p ON t.codigo_parcela = p.codigo_parcela
                        JOIN maquinas m ON t.codigo_maquina = m.codigo_maquina
                        WHERE t.login_maquinista = '$mi_login'
                        ORDER BY t.estado ASC, t.fecha_solicitud DESC";

                $res = mysqli_query($conexion, $sql);
                $geojson_trabajos = array();

                if (mysqli_num_rows($res) == 0) {
                    echo "<tr><td colspan='3' style='text-align:center; padding:20px;'>No tiene tareas pendientes.</td></tr>";
                }

                while ($fila = mysqli_fetch_array($res)) {
                    // Datos Mapa
                    if (!empty($fila['borde_poligono'])) {
                        $geojson_trabajos[] = array(
                            "id" => $fila['codigo_trabajo'],
                            "parcela" => $fila['nom_par'],
                            "labor" => $fila['tipo_trabajo'],
                            "coords" => json_decode($fila['borde_poligono']),
                            "estado" => $fila['estado']
                        );
                    }

                    // Estados visuales
                    $css_est = "estado-pendiente";
                    $txt_est = "PENDIENTE";
                    if ($fila['estado'] == 1) {
                        $css_est = "estado-curso";
                        $txt_est = "EN CURSO";
                    }
                    if ($fila['estado'] == 2) {
                        $css_est = "estado-finalizado";
                        $txt_est = "FINALIZADO";
                    }

                    echo "<tr style='cursor:pointer;' onclick='focalizarTrabajo(" . $fila['codigo_trabajo'] . ")'>";

                    echo "<td><b>" . $fila['tipo_trabajo'] . "</b><br><small>" . date("d/m/Y", strtotime($fila['fecha_solicitud'])) . "</small></td>";

                    echo "<td><b>" . $fila['nom_par'] . "</b><br><small>" . $fila['municipio'] . " (" . $fila['hectareas'] . " ha)</small><br><small style='color:#007bff;'>" . $fila['nom_maq'] . "</small></td>";

                    echo "<td onclick='event.stopPropagation();'>";
                    echo "<span class='etiqueta-estado $css_est' style='margin-bottom:5px; display:inline-block;'>$txt_est</span><br>";

                    echo "<div class='flex-acciones'>";
                    if ($fila['estado'] == 0) {
                        echo "<form method='POST' style='margin:0;'>
                                <input type='hidden' name='id_trabajo' value='" . $fila['codigo_trabajo'] . "'>
                                <input type='hidden' name='accion' value='iniciar'>
                                <button type='submit' class='btn-accion btn-azul-oscuro'>▶ Iniciar</button>
                              </form>";
                    } elseif ($fila['estado'] == 1) {
                        echo "<form method='POST' style='margin:0; display:flex; align-items:center;'>
                                <input type='hidden' name='id_trabajo' value='" . $fila['codigo_trabajo'] . "'>
                                <input type='hidden' name='accion' value='finalizar'>
                                <input type='number' name='horas_reales' class='input-mini' placeholder='Hrs' step='0.5' min='0.5' required>
                                <button type='submit' class='btn-accion btn-verde'>✔ Fin</button>
                              </form>";
                    } else {
                        echo "<small><b>" . $fila['horas_reales'] . " h</b> imputadas</small>";
                    }
                    echo "</div></td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="col-visor">
        <div class="herramientas-mapa" style="background: #343a40;">UBICACIÓN DE OBJETIVO</div>
        <div id="mapa"></div>
    </div>
</div>

<?php include("includes/footer.php"); ?>

<script>
    var map = L.map('mapa').setView([40.4167, -3.7032], 6);
    L.tileLayer.wms('https://www.ign.es/wms-inspire/pnoa-ma?', {layers: 'OI.OrthoimageCoverage', format: 'image/png', transparent: false}).addTo(map);
    L.tileLayer.wms('https://ovc.catastro.meh.es/Cartografia/WMS/ServidorWMS.aspx', {layers: 'PARCELA', format: 'image/png', transparent: true}).addTo(map);

    var datos = <?php echo json_encode($geojson_trabajos); ?>;
    var capas = {};
    var grupo = L.featureGroup();

    datos.forEach(function (t) {
        if (t.coords) {
            // Color según estado (Amarillo, Azul, Verde)
            var color = '#ffc107';
            if (t.estado == 1)
                color = '#007bff';
            if (t.estado == 2)
                color = '#28a745';

            var p = L.polygon(t.coords, {color: color, fillColor: color, fillOpacity: 0.5, weight: 3}).addTo(map);
            p.bindPopup("<b>" + t.parcela + "</b><br>" + t.labor);

            capas[t.id] = p;
            grupo.addLayer(p);
        }
    });

    if (datos.length > 0) {
        map.fitBounds(grupo.getBounds(), {padding: [50, 50]});
    }

    function focalizarTrabajo(id) {
        var c = capas[id];
        if (c) {
            map.flyToBounds(c.getBounds(), {padding: [100, 100], duration: 1.5});
            c.openPopup();
        }
    }
</script>