<?php
/**
 * ARCHIVO: gestion_trabajos.php
 * PROYECTO: Gestión Agrícola 2.0
 * DESCRIPCIÓN:
 * Panel de gestión de órdenes de servicio.
 * Permite al administrador asignar maquinaria y personal a las solicitudes
 * recibidas de los clientes (agricultores).
 */
session_start();
require("conexion.php");

if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 0) {
    header("Location: index.php");
    exit();
}

$mensaje_global = "";

// -----------------------------------------------------------------------------
// 1. ASIGNACIÓN DE RECURSOS (UPDATE)
// -----------------------------------------------------------------------------
if (isset($_POST['btn_asignar'])) {
    $id_trabajo = $_POST['id_trabajo'];
    $maquinista = $_POST['login_maquinista'];
    $maquina = $_POST['codigo_maquina'];

    // Validar selección
    if (empty($maquinista) || empty($maquina)) {
        $mensaje_global = "<div class='mensaje alerta-roja'>Debe seleccionar un maquinista y una máquina.</div>";
    } else {
        // Al asignar recursos, el estado se mantiene en 0 (Pendiente de inicio)
        // pero deja de ser una solicitud "huérfana".
        $sql = "UPDATE trabajos
                SET login_maquinista = '$maquinista',
                    codigo_maquina = '$maquina'
                WHERE codigo_trabajo = $id_trabajo";

        if (mysqli_query($conexion, $sql)) {
            $mensaje_global = "<div class='mensaje alerta-verde'>Orden asignada correctamente.</div>";
        } else {
            $mensaje_global = "<div class='mensaje alerta-roja'>Error: " . mysqli_error($conexion) . "</div>";
        }
    }
}

include("includes/header.php");
?>

<h1>Gestión de Órdenes de Trabajo</h1>
<?php echo $mensaje_global; ?>

<div class="panel-formulario" style="border-left: 5px solid #ffc107; background: #fffbe6;">
    <h3 style="color: #856404;">🔔 Solicitudes Pendientes de Asignación</h3>
    <p>Trabajos solicitados por clientes que requieren asignación de personal y maquinaria.</p>

    <table>
        <thead>
            <tr>
                <th>Fecha / Cliente</th>
                <th>Parcela</th>
                <th>Labor Solicitada</th>
                <th>Asignar Recursos</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Filtramos trabajos donde NO hay maquinista asignado (IS NULL)
            $sql_pend = "SELECT t.*, p.nombre as parcela, p.municipio, u.nombre as cli_nom, u.apellidos as cli_ape
                         FROM trabajos t
                         JOIN parcelas p ON t.codigo_parcela = p.codigo_parcela
                         JOIN usuarios u ON p.login_agricultor = u.login
                         WHERE t.login_maquinista IS NULL
                         ORDER BY t.fecha_solicitud ASC";

            $res_pend = mysqli_query($conexion, $sql_pend);

            if (mysqli_num_rows($res_pend) == 0) {
                echo "<tr><td colspan='4' style='text-align:center; color:#999;'>No hay solicitudes nuevas.</td></tr>";
            }

            while ($row = mysqli_fetch_array($res_pend)) {
                echo "<tr>";
                echo "<td>" . date("d/m/Y", strtotime($row['fecha_solicitud'])) . "<br><b>" . $row['cli_nom'] . " " . $row['cli_ape'] . "</b></td>";
                echo "<td>" . $row['parcela'] . "<br><small>" . $row['municipio'] . "</small></td>";
                echo "<td><span class='etiqueta-estado estado-pendiente'>" . $row['tipo_trabajo'] . "</span></td>";

                // Formulario en línea para asignación rápida
                echo "<td>
                        <form method='POST' style='display:flex; gap:5px; margin:0; align-items:center;'>
                            <input type='hidden' name='id_trabajo' value='" . $row['codigo_trabajo'] . "'>

                            <select name='login_maquinista' required style='padding:5px;'>
                                <option value=''>-- Maquinista --</option>";
                $q_m = mysqli_query($conexion, "SELECT * FROM usuarios WHERE tipo=1");
                while ($m = mysqli_fetch_array($q_m))
                    echo "<option value='" . $m['login'] . "'>" . $m['nombre'] . "</option>";
                echo "      </select>

                            <select name='codigo_maquina' required style='padding:5px;'>
                                <option value=''>-- Máquina --</option>";
                $q_mc = mysqli_query($conexion, "SELECT * FROM maquinas");
                while ($mc = mysqli_fetch_array($q_mc))
                    echo "<option value='" . $mc['codigo_maquina'] . "'>" . $mc['nombre'] . " (" . $mc['tipo_maquina'] . ")</option>";
                echo "      </select>

                            <input type='submit' name='btn_asignar' value='ASIGNAR' class='btn-accion btn-verde'>
                        </form>
                      </td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<br><hr><br>

<h3>Planificación Activa e Historial</h3>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Fecha</th>
            <th>Parcela</th>
            <th>Labor</th>
            <th>Recursos Asignados</th>
            <th>Estado Actual</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Filtramos trabajos que SÍ tienen recursos asignados
        $sql_hist = "SELECT t.*, p.nombre as nom_par, u.nombre as nom_maq, m.nombre as nom_vehi
                     FROM trabajos t
                     JOIN parcelas p ON t.codigo_parcela = p.codigo_parcela
                     LEFT JOIN usuarios u ON t.login_maquinista = u.login
                     LEFT JOIN maquinas m ON t.codigo_maquina = m.codigo_maquina
                     WHERE t.login_maquinista IS NOT NULL
                     ORDER BY t.codigo_trabajo DESC";

        $res_hist = mysqli_query($conexion, $sql_hist);

        while ($r = mysqli_fetch_array($res_hist)) {
            // Lógica de estados visuales
            $estado_txt = "PENDIENTE INICIO";
            $css = "estado-pendiente";

            if ($r['estado'] == 1) {
                $estado_txt = "EN CURSO";
                $css = "estado-curso";
            }
            if ($r['estado'] == 2) {
                $estado_txt = "FINALIZADO";
                $css = "estado-finalizado";
            }

            echo "<tr>";
            echo "<td>" . $r['codigo_trabajo'] . "</td>";
            echo "<td>" . date("d/m/Y", strtotime($r['fecha_solicitud'])) . "</td>";
            echo "<td>" . $r['nom_par'] . "</td>";
            echo "<td>" . $r['tipo_trabajo'] . "</td>";
            echo "<td>" . $r['nom_maq'] . "<br><small style='color:#666;'>" . $r['nom_vehi'] . "</small></td>";
            echo "<td><span class='etiqueta-estado $css'>$estado_txt</span></td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

<?php include("includes/footer.php"); ?>