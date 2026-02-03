<?php
/**
 * ARCHIVO: gestion_usuarios.php
 * PROYECTO: Gestión Agrícola 2.0
 * DESCRIPCIÓN:
 * Módulo de administración de cuentas de usuario (CRUD).
 * Permite registrar nuevos actores en el sistema y eliminarlos, gestionando
 * la integridad referencial si tienen datos asociados.
 */
session_start();
require("conexion.php");

// 1. CONTROL DE ACCESO
// Verificación estricta del rol de Administrador (0).
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 0) {
    header("Location: index.php");
    exit();
}

$mensaje_global = "";

// -----------------------------------------------------------------------------
// 2. LÓGICA DE NEGOCIO: REGISTRO DE USUARIO (INSERT)
// -----------------------------------------------------------------------------
if (isset($_POST['btn_guardar'])) {

    // Saneamiento básico de entradas
    $login = mysqli_real_escape_string($conexion, $_POST['login']);
    $pass = mysqli_real_escape_string($conexion, $_POST['password']);
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $apellidos = mysqli_real_escape_string($conexion, $_POST['apellidos']);
    $dni = mysqli_real_escape_string($conexion, $_POST['dni']);
    $email = mysqli_real_escape_string($conexion, $_POST['email']);
    $tipo = (int) $_POST['tipo'];

    // Validación de campos requeridos
    if (empty($login) || empty($pass) || empty($nombre)) {
        $mensaje_global = "<div class='mensaje alerta-roja'>ERROR: Login, Contraseña y Nombre son obligatorios.</div>";
    } else {
        // Verificación de duplicidad de clave primaria (Login)
        $check_sql = "SELECT login FROM usuarios WHERE login = '$login'";
        if (mysqli_num_rows(mysqli_query($conexion, $check_sql)) > 0) {
            $mensaje_global = "<div class='mensaje alerta-roja'>El usuario <b>'$login'</b> ya existe.</div>";
        } else {
            // Inserción
            $sql_insert = "INSERT INTO usuarios (login, password, nombre, apellidos, dni, email, tipo)
                           VALUES ('$login', '$pass', '$nombre', '$apellidos', '$dni', '$email', '$tipo')";

            if (mysqli_query($conexion, $sql_insert)) {
                $mensaje_global = "<div class='mensaje alerta-verde'>Usuario registrado con éxito.</div>";
            } else {
                $mensaje_global = "<div class='mensaje alerta-roja'>Error en base de datos: " . mysqli_error($conexion) . "</div>";
            }
        }
    }
}

// -----------------------------------------------------------------------------
// 3. LÓGICA DE NEGOCIO: ELIMINACIÓN DE USUARIO (DELETE)
// -----------------------------------------------------------------------------
if (isset($_POST['id_borrar'])) {
    $id_borrar = mysqli_real_escape_string($conexion, $_POST['id_borrar']);

    // Protección: Evitar que el admin se borre a sí mismo
    if ($id_borrar == $_SESSION['usuario']) {
        $mensaje_global = "<div class='mensaje alerta-roja'>Operación denegada: No puede eliminar su propia cuenta.</div>";
    } else {
        try {
            $sql_delete = "DELETE FROM usuarios WHERE login = '$id_borrar'";
            mysqli_query($conexion, $sql_delete);
            $mensaje_global = "<div class='mensaje alerta-verde'>Usuario eliminado correctamente.</div>";
        } catch (mysqli_sql_exception $e) {
            // Manejo de restricción de clave foránea (ON DELETE RESTRICT/NO ACTION si no fuera CASCADE)
            // Aunque la BD está en CASCADE, es buena práctica capturar excepciones generales.
            $mensaje_global = "<div class='mensaje alerta-roja'>Error al eliminar: Verifique dependencias de datos.</div>";
        }
    }
}

include("includes/header.php");
?>

<h1>Gestión de Usuarios</h1>
<?php echo $mensaje_global; ?>

<div class="panel-formulario">
    <h3>Registrar Nuevo Usuario</h3>
    <form action="gestion_usuarios.php" method="POST">
        <div style="display:flex; gap:20px;">
            <div style="flex:1">
                <label>Login (Usuario) *:</label>
                <input type="text" name="login" placeholder="Ej: agricultor1" required>
            </div>
            <div style="flex:1">
                <label>Contraseña *:</label>
                <input type="password" name="password" placeholder="******" required>
            </div>
        </div>
        <div style="display:flex; gap:20px;">
            <div style="flex:1">
                <label>Nombre *:</label>
                <input type="text" name="nombre" required>
            </div>
            <div style="flex:1">
                <label>Apellidos:</label>
                <input type="text" name="apellidos">
            </div>
        </div>
        <div style="display:flex; gap:20px;">
            <div style="flex:1">
                <label>DNI:</label>
                <input type="text" name="dni">
            </div>
            <div style="flex:1">
                <label>Email:</label>
                <input type="email" name="email">
            </div>
        </div>
        <label>Rol del Sistema:</label>
        <select name="tipo">
            <option value="2">Agricultor (Cliente)</option>
            <option value="1">Maquinista (Empleado)</option>
            <option value="0">Administrador</option>
        </select>
        <input type="submit" name="btn_guardar" value="Crear Usuario" style="width:100%; margin-top:15px;">
    </form>
</div>

<hr>

<h3>Directorio de Usuarios</h3>
<table>
    <thead>
        <tr>
            <th>Login</th>
            <th>Nombre Completo</th>
            <th>Contacto</th>
            <th>Rol</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $sql = "SELECT * FROM usuarios ORDER BY tipo ASC, nombre ASC";
        $res = mysqli_query($conexion, $sql);

        while ($fila = mysqli_fetch_array($res)) {
            // Renderizado de etiquetas de rol
            $rol_str = "";
            $bg_color = "";
            switch ($fila['tipo']) {
                case 0: $rol_str = "ADMINISTRADOR";
                    $bg_color = "#343a40";
                    break;
                case 1: $rol_str = "MAQUINISTA";
                    $bg_color = "#17a2b8";
                    break;
                case 2: $rol_str = "AGRICULTOR";
                    $bg_color = "#28a745";
                    break;
            }

            echo "<tr>";
            echo "<td><b>" . $fila['login'] . "</b></td>";
            echo "<td>" . $fila['nombre'] . " " . $fila['apellidos'] . "</td>";
            echo "<td><small>" . $fila['email'] . "<br>" . $fila['dni'] . "</small></td>";
            echo "<td><span style='background:$bg_color; color:white; padding:4px 8px; border-radius:4px; font-size:0.8em;'>" . $rol_str . "</span></td>";

            echo "<td>";
            if ($fila['login'] != $_SESSION['usuario']) {
                echo "<form method='POST' onsubmit='return confirm(\"¿Eliminar usuario?\");' style='margin:0;'>
                        <input type='hidden' name='id_borrar' value='" . $fila['login'] . "'>
                        <input type='submit' value='Eliminar' class='btn-borrar-tabla' style='color:#dc3545; background:none; border:none; cursor:pointer; font-weight:bold;'>
                      </form>";
            } else {
                echo "<span style='color:#999; font-style:italic;'>Sesión Actual</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

<?php include("includes/footer.php"); ?>