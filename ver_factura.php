<?php
/**
 * ARCHIVO: ver_factura.php
 * PROYECTO: Gestión Agrícola 2.0
 * DESCRIPCIÓN:
 * Vista de impresión de factura.
 * Diseño minimalista (tipo papel A4) sin menús ni navegación.
 */
session_start();
require("conexion.php");

if (!isset($_SESSION['usuario']) || !isset($_GET['id'])) {
    die("Acceso denegado.");
}

$id_factura = $_GET['id'];

// Consultamos TODOS los datos necesarios relacionando las 5 tablas
$sql = "SELECT f.*,
               t.tipo_trabajo, t.horas_reales, t.fecha_solicitud,
               m.nombre as maquina, m.precio_hora, m.matricula,
               p.nombre as parcela, p.municipio, p.referencia_catastral,
               u.nombre as cliente_nombre, u.apellidos as cliente_apellidos, u.dni as cliente_dni, u.email
        FROM facturas f
        JOIN trabajos t ON f.codigo_trabajo = t.codigo_trabajo
        JOIN maquinas m ON t.codigo_maquina = m.codigo_maquina
        JOIN parcelas p ON t.codigo_parcela = p.codigo_parcela
        JOIN usuarios u ON p.login_agricultor = u.login
        WHERE f.codigo_factura = $id_factura";

$res = mysqli_query($conexion, $sql);

if (!$fila = mysqli_fetch_array($res)) {
    die("Factura no encontrada.");
}
?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Factura FAC-<?php echo $id_factura; ?></title>
        <style>
            body {
                font-family: 'Courier New', Courier, monospace;
                background: #555;
                padding: 20px;
            }
            .hoja-factura {
                background: white;
                width: 210mm;
                min-height: 297mm; /* A4 */
                margin: 0 auto;
                padding: 20mm;
                box-sizing: border-box;
                box-shadow: 0 0 10px rgba(0,0,0,0.5);
                position: relative;
            }
            .header-fac {
                display: flex;
                justify-content: space-between;
                border-bottom: 2px solid #333;
                padding-bottom: 20px;
                margin-bottom: 40px;
            }
            .empresa h1 {
                margin: 0;
                color: #2c3e50;
            }
            .datos-cliente {
                margin-bottom: 40px;
                border: 1px solid #ddd;
                padding: 15px;
                border-radius: 5px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            th {
                background: #eee;
                text-align: left;
                padding: 10px;
                border-bottom: 1px solid #333;
            }
            td {
                padding: 10px;
                border-bottom: 1px solid #eee;
            }
            .totales {
                margin-top: 40px;
                text-align: right;
            }
            .totales table {
                width: 300px;
                margin-left: auto;
            }
            .sello-pagado {
                position: absolute;
                top: 30%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-15deg);
                border: 5px solid #28a745;
                color: #28a745;
                font-size: 4em;
                font-weight: bold;
                padding: 10px 20px;
                opacity: 0.3;
                text-transform: uppercase;
            }
            @media print {
                body {
                    background: white;
                    padding: 0;
                }
                .hoja-factura {
                    box-shadow: none;
                    margin: 0;
                    width: 100%;
                }
            }
        </style>
    </head>
    <body>

        <div class="hoja-factura">

            <?php if ($fila['estado_pago'] == 'Pagada') { ?>
                <div class="sello-pagado">PAGADA</div>
            <?php } ?>

            <div class="header-fac">
                <div class="empresa">
                    <h1>AGRO-ATAULFO S.L.</h1>
                    <p> P.º Menéndez Pelayo, 4<br>
                        39700 Castro-Urdiales (Cantabria)<br>
                        CIF: B-12345678</p>
                </div>
                <div class="meta-fac" style="text-align: right;">
                    <h2>FACTURA</h2>
                    <p><strong>Nº:</strong> FAC-<?php echo str_pad($fila['codigo_factura'], 4, "0", STR_PAD_LEFT); ?><br>
                        <strong>Fecha:</strong> <?php echo date("d/m/Y", strtotime($fila['fecha_emision'])); ?></p>
                </div>
            </div>

            <div class="datos-cliente">
                <strong>CLIENTE:</strong><br>
                <?php echo $fila['cliente_nombre'] . " " . $fila['cliente_apellidos']; ?><br>
                DNI/CIF: <?php echo $fila['cliente_dni']; ?><br>
                Email: <?php echo $fila['email']; ?>
            </div>

            <h3>DETALLE DEL SERVICIO</h3>
            <table>
                <thead>
                    <tr>
                        <th>Concepto</th>
                        <th>Cantidad (Horas)</th>
                        <th>Precio Unit.</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <b><?php echo $fila['tipo_trabajo']; ?></b><br>
                            <small>Máquina: <?php echo $fila['maquina']; ?> (<?php echo $fila['matricula']; ?>)</small><br>
                            <small>Lugar: <?php echo $fila['parcela']; ?> (<?php echo $fila['municipio']; ?>)</small>
                        </td>
                        <td><?php echo $fila['horas_reales']; ?> h</td>
                        <td><?php echo number_format($fila['precio_hora'], 2); ?> €</td>
                        <td><?php echo number_format($fila['base_imponible'], 2); ?> €</td>
                    </tr>
                </tbody>
            </table>

            <div class="totales">
                <table>
                    <tr>
                        <td>Base Imponible:</td>
                        <td><?php echo number_format($fila['base_imponible'], 2); ?> €</td>
                    </tr>
                    <tr>
                        <td>IVA (21%):</td>
                        <td><?php echo number_format($fila['iva'], 2); ?> €</td>
                    </tr>
                    <tr style="font-size: 1.2em; font-weight: bold; background: #eee;">
                        <td>TOTAL A PAGAR:</td>
                        <td><?php echo number_format($fila['total'], 2); ?> €</td>
                    </tr>
                </table>
            </div>

            <div style="margin-top: 50px; font-size: 0.8em; color: gray; text-align: center;">
                <p>Gracias por su confianza. Documento generado electrónicamente.</p>
            </div>

        </div>

    </body>
</html>