<?php
/**
 * ARCHIVO: estado_trabajos.php
 * PROYECTO: Gestión Agrícola 2.0
 * DESCRIPCIÓN:
 * Panel de seguimiento para el agricultor.
 * Muestra el estado de los trabajos y permite SOLICITAR FACTURA cuando finalizan.
 */
session_start();
require("conexion.php");

// 1. SEGURIDAD
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 2) {
    header("Location: index.php");
    exit();
}
$mi_login = $_SESSION['usuario'];
$mensaje = "";

// -----------------------------------------------------------------------------
// 2. LÓGICA: SOLICITAR FACTURA (Cambia Estado 2 -> 3)
// -----------------------------------------------------------------------------
if (isset($_POST['btn_pedir_factura'])) {
    $id_trabajo = $_POST['id_trabajo'];
    // Pasamos a estado 3 ("Solicitado Facturación")
    // Esto hará que le aparezca al administrador en su panel
    $sql_upd = "UPDATE trabajos SET estado = 3 WHERE codigo_trabajo = $id_trabajo";

    if (mysqli_query($conexion, $sql_upd)) {
        $mensaje = "<div class='mensaje alerta-verde'>Solicitud enviada a administración. Procesarán su factura en breve.</div>";
    } else {
        $mensaje = "<div class='mensaje alerta-roja'>Error al solicitar: " . mysqli_error($conexion) . "</div>";
    }
}

include("includes/header.php");
?>

<h1>Seguimiento de Operaciones</h1>
<?php echo $mensaje; ?>

<div class="panel-formulario" style="padding:0; overflow:hidden;">
    <table style="margin:0; box-shadow:none;">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Finca</th>
                <th>Labor</th>
                <th>Recursos</th>
                <th>Estado</th>
                <th>Acciones</th> </tr>
        </thead>
        <tbody>
            <?php
            // Consulta: Trabajos vinculados a las tierras de este agricultor
            $sql = "SELECT t.*, p.nombre as nom_par, m.nombre as nom_maq, u.nombre as nom_ope
                    FROM trabajos t
                    JOIN parcelas p ON t.codigo_parcela = p.codigo_parcela
                    LEFT JOIN maquinas m ON t.codigo_maquina = m.codigo_maquina
                    LEFT JOIN usuarios u ON t.login_maquinista = u.login
                    WHERE p.login_agricultor = '$mi_login'
                    ORDER BY t.fecha_solicitud DESC";

            $res = mysqli_query($conexion, $sql);

            if (mysqli_num_rows($res) == 0) {
                echo "<tr><td colspan='6' style='text-align:center; padding:20px;'>No hay registros de actividad.</td></tr>";
            }

            while ($r = mysqli_fetch_array($res)) {

                // Variables por defecto
                $estado_txt = "PENDIENTE";
                $css = "estado-pendiente";
                $accion_html = "<span style='color:#ccc;'>-</span>";

                // --- ESTADO 0: PENDIENTE ---
                if ($r['estado'] == 0) {
                    $estado_txt = "PENDIENTE";
                    $css = "estado-pendiente";
                    $accion_html = "<small style='color:#999;'>Esperando asignación</small>";
                }

                // --- ESTADO 1: EN CURSO ---
                if ($r['estado'] == 1) {
                    $estado_txt = "EN CURSO";
                    $css = "estado-curso";
                    $accion_html = "<small style='color:#007bff;'>Trabajo en progreso...</small>";
                }

                // --- ESTADO 2: FINALIZADO (Aquí mostramos el botón) ---
                if ($r['estado'] == 2) {
                    $estado_txt = "FINALIZADO";
                    $css = "estado-finalizado";
                    // Botón para solicitar factura
                    $accion_html = "<form method='POST' style='margin:0;'>
                                        <input type='hidden' name='id_trabajo' value='" . $r['codigo_trabajo'] . "'>
                                        <button type='submit' name='btn_pedir_factura' class='btn-accion btn-azul-oscuro' title='Solicitar emisión de factura'>
                                            Solicitar Factura
                                        </button>
                                    </form>";
                }

                // --- ESTADO 3: SOLICITADO (Esperando al admin) ---
                if ($r['estado'] == 3) {
                    $estado_txt = "FACT. SOLICITADA";
                    $css = "estado-pendiente"; // Amarillo/Naranja
                    $accion_html = "<small style='color:#856404;'>Procesando admin...</small>";
                }

                // --- ESTADO 4: FACTURADO (Ya existe factura) ---
                if ($r['estado'] == 4) {
                    $estado_txt = "FACTURADO";
                    $css = "estado-finalizado";
                    $accion_html = "<a href='mis_facturas.php' class='btn-accion btn-ver'>Ver Facturas</a>";
                }

                echo "<tr>";
                echo "<td>" . date("d/m/Y", strtotime($r['fecha_solicitud'])) . "</td>";
                echo "<td><b>" . $r['nom_par'] . "</b></td>";
                echo "<td>" . $r['tipo_trabajo'] . "</td>";
                echo "<td>
                        <div style='font-size:0.9em;'>Mq: " . ($r['nom_maq'] ? $r['nom_maq'] : "---") . "</div>
                        <div style='font-size:0.9em;'>Op: " . ($r['nom_ope'] ? $r['nom_ope'] : "---") . "</div>
                      </td>";
                echo "<td><span class='etiqueta-estado $css'>$estado_txt</span></td>";

                // Columna de Acciones
                echo "<td>$accion_html</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<div style="margin-top: 15px; font-size: 0.85em; color: #666; background: #fff; padding: 10px; border-radius: 4px;">
    <strong>Leyenda de Estados:</strong>
    <ul style="margin: 5px 0 0 20px;">
        <li><span style="color:#856404; font-weight:bold;">PENDIENTE</span>: Orden creada, esperando recursos.</li>
        <li><span style="color:#0c5460; font-weight:bold;">EN CURSO</span>: El operario está trabajando.</li>
        <li><span style="color:#155724; font-weight:bold;">FINALIZADO</span>: Tarea completada. <b>Debe solicitar la factura.</b></li>
        <li><span style="color:#333; font-weight:bold;">FACTURADO</span>: Proceso cerrado, disponible en 'Facturas'.</li>
    </ul>
</div>

<?php include("includes/footer.php"); ?>