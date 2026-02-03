<?php
/**
 * ARCHIVO: gestion_facturas.php
 * PROYECTO: Gestión Agrícola 2.0
 * DESCRIPCIÓN:
 * Panel administrativo de facturación.
 * Permite gestionar solicitudes de facturas de clientes, visualizar el historial
 * y controlar el estado de los pagos.
 */
session_start();
require("conexion.php");

if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 0) {
    header("Location: index.php");
    exit();
}

$mensaje_global = "";

// -----------------------------------------------------------------------------
// A. GENERAR FACTURA (NUEVA LÓGICA: De Solicitud a Factura Real)
// -----------------------------------------------------------------------------
if (isset($_POST['btn_generar'])) {
    $id_trabajo = $_POST['id_trabajo'];

    // 1. Obtenemos datos para calcular: Horas Reales * Precio Máquina
    // Usamos JOIN para obtener el precio de la máquina usada en ese trabajo
    $q_datos = mysqli_query($conexion, "SELECT t.horas_reales, m.precio_hora
                                        FROM trabajos t
                                        JOIN maquinas m ON t.codigo_maquina = m.codigo_maquina
                                        WHERE t.codigo_trabajo = $id_trabajo");
    $d = mysqli_fetch_assoc($q_datos);

    // 2. Cálculos económicos
    $base = $d['horas_reales'] * $d['precio_hora'];
    $iva = $base * 0.21; // 21% IVA
    $total = $base + $iva;
    $fecha = date('Y-m-d');

    // 3. Insertar Factura
    $sql_ins = "INSERT INTO facturas (codigo_trabajo, fecha_emision, base_imponible, iva, total, estado_pago)
                VALUES ($id_trabajo, '$fecha', '$base', '$iva', '$total', 'Pendiente')";

    if (mysqli_query($conexion, $sql_ins)) {
        // 4. Actualizar estado del trabajo a 4 (Facturado)
        mysqli_query($conexion, "UPDATE trabajos SET estado = 4 WHERE codigo_trabajo = $id_trabajo");
        $mensaje_global = "<div class='mensaje alerta-verde'>Factura generada correctamente. El cliente ya puede verla.</div>";
    } else {
        $mensaje_global = "<div class='mensaje alerta-roja'>Error al generar factura: " . mysqli_error($conexion) . "</div>";
    }
}

// -----------------------------------------------------------------------------
// B. MARCAR COMO PAGADA (Update)
// -----------------------------------------------------------------------------
if (isset($_POST['btn_pagar_manual'])) {
    $id_fac = $_POST['id_factura'];
    $sql = "UPDATE facturas SET estado_pago = 'Pagada' WHERE codigo_factura = $id_fac";

    if (mysqli_query($conexion, $sql)) {
        $mensaje_global = "<div class='mensaje alerta-verde'>Factura marcada como cobrada.</div>";
    } else {
        $mensaje_global = "<div class='mensaje alerta-roja'>Error: " . mysqli_error($conexion) . "</div>";
    }
}

// -----------------------------------------------------------------------------
// C. ELIMINAR FACTURA (Delete)
// -----------------------------------------------------------------------------
if (isset($_POST['btn_borrar_factura'])) {
    $id_fac = $_POST['id_factura'];

    // Primero obtenemos el ID del trabajo asociado para "liberarlo"
    $q_trab = mysqli_query($conexion, "SELECT codigo_trabajo FROM facturas WHERE codigo_factura = $id_fac");
    $r_trab = mysqli_fetch_assoc($q_trab);
    $id_trab = $r_trab['codigo_trabajo'];

    $sql = "DELETE FROM facturas WHERE codigo_factura = $id_fac";

    if (mysqli_query($conexion, $sql)) {
        // Al borrar la factura, devolvemos el trabajo a estado 2 (Finalizado)
        // para que pueda volver a solicitarse o facturarse si fue un error.
        if ($id_trab) {
            mysqli_query($conexion, "UPDATE trabajos SET estado = 2 WHERE codigo_trabajo = $id_trab");
        }
        $mensaje_global = "<div class='mensaje alerta-verde'>Factura eliminada. El trabajo ha vuelto a estado 'Finalizado'.</div>";
    } else {
        $mensaje_global = "<div class='mensaje alerta-roja'>Error al eliminar.</div>";
    }
}

include("includes/header.php");
?>

<h1>Control de Facturación</h1>
<?php echo $mensaje_global; ?>

<div class="panel-formulario" style="border-left: 5px solid #007bff; background: #e9f7fe;">
    <h3 style="color: #0056b3;">📄 Solicitudes de Facturación</h3>
    <p>Los siguientes clientes solicitan la emisión de factura por trabajos finalizados.</p>

    <table>
        <thead>
            <tr>
                <th>Fecha Trabajo</th>
                <th>Cliente</th>
                <th>Labor / Finca</th>
                <th>Horas / Tarifa</th>
                <th>Total Est. (IVA inc)</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Buscamos trabajos en Estado 3 (Factura Solicitada)
            $sql_req = "SELECT t.*, u.nombre, u.apellidos, m.precio_hora, m.nombre as maq_nom, p.nombre as parc_nom
                        FROM trabajos t
                        JOIN parcelas p ON t.codigo_parcela = p.codigo_parcela
                        JOIN usuarios u ON p.login_agricultor = u.login
                        JOIN maquinas m ON t.codigo_maquina = m.codigo_maquina
                        WHERE t.estado = 3
                        ORDER BY t.fecha_solicitud ASC";

            $res_req = mysqli_query($conexion, $sql_req);

            if (mysqli_num_rows($res_req) == 0) {
                echo "<tr><td colspan='6' style='text-align:center; color:#666;'>No hay solicitudes pendientes.</td></tr>";
            }

            while ($r = mysqli_fetch_array($res_req)) {
                // Cálculo estimado para previsualizar
                $est_base = $r['horas_reales'] * $r['precio_hora'];
                $est_total = $est_base * 1.21;

                echo "<tr>";
                echo "<td>" . date("d/m/Y", strtotime($r['fecha_solicitud'])) . "</td>";
                echo "<td><b>" . $r['nombre'] . " " . $r['apellidos'] . "</b></td>";
                echo "<td>" . $r['tipo_trabajo'] . "<br><small>" . $r['parc_nom'] . "</small></td>";
                echo "<td>" . $r['horas_reales'] . " h <small>x " . $r['precio_hora'] . "€</small></td>";
                echo "<td style='font-weight:bold; color:#007bff;'>" . number_format($est_total, 2) . " €</td>";
                echo "<td>
                        <form method='POST' style='margin:0;'>
                            <input type='hidden' name='id_trabajo' value='" . $r['codigo_trabajo'] . "'>
                            <button type='submit' name='btn_generar' class='btn-accion btn-azul-oscuro'>Generar Factura</button>
                        </form>
                      </td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<br><hr><br>

<div class="dashboard" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 30px;">
    <?php
    $q_tot = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT SUM(total) as t FROM facturas"));
    $q_cob = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT SUM(total) as t FROM facturas WHERE estado_pago='Pagada'"));
    $q_pen = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT SUM(total) as t FROM facturas WHERE estado_pago='Pendiente'"));

    $total_emitido = $q_tot['t'] ?? 0;
    $total_cobrado = $q_cob['t'] ?? 0;
    $total_deuda = $q_pen['t'] ?? 0;
    ?>
    <div class="card">
        <h3>Total Facturado</h3>
        <p style="font-size:1.5em; font-weight:bold; color:#333;"><?php echo number_format($total_emitido, 2); ?> €</p>
    </div>
    <div class="card">
        <h3>Cobrado</h3>
        <p style="font-size:1.5em; font-weight:bold; color:#28a745;"><?php echo number_format($total_cobrado, 2); ?> €</p>
    </div>
    <div class="card">
        <h3>Pendiente</h3>
        <p style="font-size:1.5em; font-weight:bold; color:#dc3545;"><?php echo number_format($total_deuda, 2); ?> €</p>
    </div>
</div>

<h3>Historial de Documentos</h3>

<table>
    <thead>
        <tr>
            <th>Factura</th>
            <th>Fecha</th>
            <th>Cliente</th>
            <th>Concepto</th>
            <th>Importe</th>
            <th>Estado</th>
            <th>Gestión</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $sql = "SELECT f.*, t.tipo_trabajo, u.nombre, u.apellidos
                FROM facturas f
                JOIN trabajos t ON f.codigo_trabajo = t.codigo_trabajo
                JOIN parcelas p ON t.codigo_parcela = p.codigo_parcela
                JOIN usuarios u ON p.login_agricultor = u.login
                ORDER BY f.fecha_emision DESC";

        $res = mysqli_query($conexion, $sql);

        if (mysqli_num_rows($res) == 0) {
            echo "<tr><td colspan='7' style='text-align:center'>No hay facturas emitidas.</td></tr>";
        }

        while ($f = mysqli_fetch_array($res)) {
            $estado = $f['estado_pago'];
            $css_estado = ($estado == 'Pagada') ? 'estado-finalizado' : 'estado-pendiente';

            echo "<tr>";
            echo "<td><b>FAC-" . str_pad($f['codigo_factura'], 4, "0", STR_PAD_LEFT) . "</b></td>";
            echo "<td>" . date("d/m/Y", strtotime($f['fecha_emision'])) . "</td>";
            echo "<td>" . $f['nombre'] . " " . $f['apellidos'] . "</td>";
            echo "<td>Servicio de " . $f['tipo_trabajo'] . "</td>";
            echo "<td style='font-weight:bold;'>" . number_format($f['total'], 2) . " €</td>";
            echo "<td><span class='etiqueta-estado $css_estado'>" . strtoupper($estado) . "</span></td>";

            echo "<td><div class='flex-acciones'>";

            // Ver PDF
            echo "<a href='ver_factura.php?id=" . $f['codigo_factura'] . "' target='_blank' class='btn-accion btn-ver'>Ver PDF</a>";

            // Marcar Pagada
            if ($estado == 'Pendiente') {
                echo "<form method='POST' style='margin:0;'>
                        <input type='hidden' name='id_factura' value='" . $f['codigo_factura'] . "'>
                        <button type='submit' name='btn_pagar_manual' class='btn-accion btn-pagar'>✔ Pagada</button>
                      </form>";
            }

            // Borrar
            echo "<form method='POST' onsubmit='return confirm(\"¿Eliminar factura permanentemente?\");' style='margin:0;'>
                    <input type='hidden' name='id_factura' value='" . $f['codigo_factura'] . "'>
                    <button type='submit' name='btn_borrar_factura' class='btn-accion btn-borrar'>Borrar</button>
                  </form>";

            echo "</div></td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

<?php include("includes/footer.php"); ?>