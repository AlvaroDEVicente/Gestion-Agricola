<?php
/**
 * ARCHIVO: mis_facturas.php
 * PROYECTO: Gestión Agrícola 2.0
 * DESCRIPCIÓN:
 * Módulo financiero para el cliente.
 * Muestra el histórico de facturación y permite realizar el pago de recibos pendientes
 * mediante una simulación de pasarela de pago (actualización de estado en BD).
 */
session_start();
require("conexion.php");

// 1. CONTROL DE ACCESO (Solo Agricultores)
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 2) {
    header("Location: index.php");
    exit();
}

$mi_login = $_SESSION['usuario'];
$mensaje_global = "";

// -----------------------------------------------------------------------------
// 2. LÓGICA DE PAGO (SIMULACIÓN)
// -----------------------------------------------------------------------------
if (isset($_POST['btn_pagar'])) {
    $id_factura = (int) $_POST['id_factura'];

    // Actualización segura: Solo marcamos pagada si la factura pertenece
    // a una parcela cuyo dueño es el usuario actual (JOIN de validación).
    $sql_pago = "UPDATE facturas f
                 JOIN trabajos t ON f.codigo_trabajo = t.codigo_trabajo
                 JOIN parcelas p ON t.codigo_parcela = p.codigo_parcela
                 SET f.estado_pago = 'Pagada'
                 WHERE f.codigo_factura = $id_factura AND p.login_agricultor = '$mi_login'";

    if (mysqli_query($conexion, $sql_pago)) {
        if (mysqli_affected_rows($conexion) > 0) {
            $mensaje_global = "<div class='mensaje alerta-verde'>Pago aceptado. Su factura consta como abonada.</div>";
        } else {
            $mensaje_global = "<div class='mensaje alerta-roja'>Error: No se pudo verificar la propiedad de la factura.</div>";
        }
    } else {
        $mensaje_global = "<div class='mensaje alerta-roja'>Error técnico en la transacción.</div>";
    }
}

include("includes/header.php");
?>

<h1>Mis Facturas</h1>
<?php echo $mensaje_global; ?>

<div class="panel-formulario">
    <p>Historial de cargos por servicios agrícolas realizados en sus explotaciones.</p>
</div>

<table>
    <thead>
        <tr>
            <th>Factura Nº</th>
            <th>Fecha</th>
            <th>Servicio / Concepto</th>
            <th>Importe</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Recuperamos facturas vinculadas a las parcelas del usuario logueado
        $sql = "SELECT f.*, t.tipo_trabajo, p.nombre as nombre_parcela
                FROM facturas f
                JOIN trabajos t ON f.codigo_trabajo = t.codigo_trabajo
                JOIN parcelas p ON t.codigo_parcela = p.codigo_parcela
                WHERE p.login_agricultor = '$mi_login'
                ORDER BY f.fecha_emision DESC";

        $res = mysqli_query($conexion, $sql);

        if (mysqli_num_rows($res) == 0) {
            echo "<tr><td colspan='6' style='text-align:center; padding:20px;'>No tiene facturas pendientes.</td></tr>";
        }

        while ($fac = mysqli_fetch_array($res)) {
            $estado = $fac['estado_pago'];
            // Lógica visual de estados
            $css_est = ($estado == 'Pagada') ? 'estado-finalizado' : 'estado-pendiente';
            $txt_est = strtoupper($estado);

            echo "<tr>";
            echo "<td><b>FAC-" . str_pad($fac['codigo_factura'], 4, "0", STR_PAD_LEFT) . "</b></td>";
            echo "<td>" . date("d/m/Y", strtotime($fac['fecha_emision'])) . "</td>";
            echo "<td>" . $fac['tipo_trabajo'] . "<br><small>Finca: " . $fac['nombre_parcela'] . "</small></td>";
            echo "<td style='font-weight:bold; font-size:1.1em;'>" . number_format($fac['total'], 2) . " €</td>";
            echo "<td><span class='etiqueta-estado $css_est'>" . $txt_est . "</span></td>";

            // Botonera
            echo "<td><div class='flex-acciones'>";

            // 1. Visualizar PDF
            echo "<a href='ver_factura.php?id=" . $fac['codigo_factura'] . "' target='_blank' class='btn-accion btn-ver'>Ver PDF</a>";

            // 2. Pagar (Solo si está pendiente)
            if ($estado == 'Pendiente') {
                echo "<form method='POST' style='margin:0;'>
                        <input type='hidden' name='id_factura' value='" . $fac['codigo_factura'] . "'>
                        <button type='submit' name='btn_pagar' class='btn-accion btn-pagar'>Pagar Ahora</button>
                      </form>";
            }
            echo "</div></td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

<?php include("includes/footer.php"); ?>