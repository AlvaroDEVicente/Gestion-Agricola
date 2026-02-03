<?php
/**
 * ARCHIVO: gestion_maquinas.php
 * PROYECTO: Gestión Agrícola 2.0
 * DESCRIPCIÓN:
 * CRUD de maquinaria. Permite al administrador mantener el inventario de
 * vehículos y aperos disponibles para la asignación de tareas.
 */
session_start();
require("conexion.php");

if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 0) {
    header("Location: index.php");
    exit();
}

$mensaje_global = "";

// -----------------------------------------------------------------------------
// 1. ALTA DE MAQUINARIA
// -----------------------------------------------------------------------------
if (isset($_POST['btn_guardar'])) {
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $matricula = mysqli_real_escape_string($conexion, $_POST['matricula']);
    $tipo = $_POST['tipo_maquina'];
    $precio = $_POST['precio'];

    if (empty($nombre) || empty($matricula) || !is_numeric($precio)) {
        $mensaje_global = "<div class='mensaje alerta-roja'>ERROR: Datos incompletos o precio inválido.</div>";
    } else {
        $sql = "INSERT INTO maquinas (nombre, matricula, tipo_maquina, precio_hora)
                VALUES ('$nombre', '$matricula', '$tipo', '$precio')";

        if (mysqli_query($conexion, $sql)) {
            $mensaje_global = "<div class='mensaje alerta-verde'>Máquina añadida al inventario.</div>";
        } else {
            $mensaje_global = "<div class='mensaje alerta-roja'>Error SQL: " . mysqli_error($conexion) . "</div>";
        }
    }
}

// -----------------------------------------------------------------------------
// 2. BAJA DE MAQUINARIA
// -----------------------------------------------------------------------------
if (isset($_POST['id_borrar'])) {
    $id = $_POST['id_borrar'];

    try {
        $sql_del = "DELETE FROM maquinas WHERE codigo_maquina = $id";
        mysqli_query($conexion, $sql_del);
        $mensaje_global = "<div class='mensaje alerta-verde'>Máquina eliminada.</div>";
    } catch (mysqli_sql_exception $e) {
        $mensaje_global = "<div class='mensaje alerta-roja'>No se puede eliminar: Esta máquina tiene trabajos históricos asociados.</div>";
    }
}

include("includes/header.php");
?>

<h1>Inventario de Maquinaria</h1>
<?php echo $mensaje_global; ?>

<div class="panel-formulario">
    <h3>Nueva Máquina</h3>
    <form action="gestion_maquinas.php" method="POST">
        <div style="display:flex; gap:20px;">
            <div style="flex:1">
                <label>Modelo/Nombre *:</label>
                <input type="text" name="nombre" required placeholder="Ej: John Deere 6155M">
            </div>
            <div style="flex:1">
                <label>Matrícula *:</label>
                <input type="text" name="matricula" required placeholder="E-0000-XXX">
            </div>
        </div>
        <div style="display:flex; gap:20px;">
            <div style="flex:1">
                <label>Tipo:</label>
                <select name="tipo_maquina">
                    <option value="Tractor">Tractor</option>
                    <option value="Cosechadora">Cosechadora</option>
                    <option value="Sembradora">Sembradora</option>
                    <option value="Fumigadora">Fumigadora</option>
                    <option value="Remolque">Remolque</option>
                </select>
            </div>
            <div style="flex:1">
                <label>Coste Hora (€):</label>
                <input type="text" name="precio" required placeholder="0.00">
            </div>
        </div>
        <input type="submit" name="btn_guardar" value="Añadir Máquina" style="width:100%; margin-top:10px;">
    </form>
</div>

<h3>Flota Actual</h3>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Modelo</th>
            <th>Matrícula</th>
            <th>Tipo</th>
            <th>Tarifa</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $res = mysqli_query($conexion, "SELECT * FROM maquinas ORDER BY tipo_maquina, nombre");
        if (mysqli_num_rows($res) == 0)
            echo "<tr><td colspan='6' style='text-align:center'>Sin registros.</td></tr>";

        while ($row = mysqli_fetch_array($res)) {
            // Estilos de categoría
            $color = "#333";
            if ($row['tipo_maquina'] == 'Tractor')
                $color = "#007bff";
            if ($row['tipo_maquina'] == 'Cosechadora')
                $color = "#e83e8c";
            if ($row['tipo_maquina'] == 'Sembradora')
                $color = "#28a745";
            if ($row['tipo_maquina'] == 'Fumigadora')
                $color = "#17a2b8";

            echo "<tr>";
            echo "<td>" . $row['codigo_maquina'] . "</td>";
            echo "<td><b>" . $row['nombre'] . "</b></td>";
            echo "<td>" . $row['matricula'] . "</td>";
            echo "<td><span style='background:$color; color:white; padding:3px 8px; border-radius:4px; font-size:0.8em;'>" . $row['tipo_maquina'] . "</span></td>";
            echo "<td>" . number_format($row['precio_hora'], 2) . " €/h</td>";

            echo "<td>
                    <form method='POST' onsubmit='return confirm(\"¿Dar de baja?\");' style='margin:0;'>
                        <input type='hidden' name='id_borrar' value='" . $row['codigo_maquina'] . "'>
                        <input type='submit' name='btn_borrar' value='Baja' class='btn-accion btn-rojo'>
                    </form>
                  </td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

<?php include("includes/footer.php"); ?>