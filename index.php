<?php
/**
 * ARCHIVO: index.php
 * PROYECTO: Gestión Agrícola 2.0
 * DESCRIPCIÓN:
 * Controlador de acceso. Verifica credenciales contra la tabla 'usuarios'
 * y redirige al panel principal según el rol detectado.
 */
session_start();
require("conexion.php");

// Si el usuario ya tiene sesión activa, redirigir directamente al panel.
if (isset($_SESSION['usuario'])) {
    header("Location: inicio.php");
    exit();
}

$mensaje_feedback = "";

// LÓGICA DE AUTENTICACIÓN
if (isset($_POST['btn_login'])) {

    // Sanitización básica de entradas
    $user_login = mysqli_real_escape_string($conexion, $_POST['usuario']);
    $user_pass = mysqli_real_escape_string($conexion, $_POST['password']);

    // Consulta de verificación
    $sql = "SELECT * FROM usuarios WHERE login = '$user_login' AND password = '$user_pass'";
    $resultado = mysqli_query($conexion, $sql);

    if (mysqli_num_rows($resultado) == 1) {
        // Credenciales correctas: Inicializamos sesión
        $fila = mysqli_fetch_assoc($resultado);

        $_SESSION['usuario'] = $fila['login'];
        $_SESSION['nombre'] = $fila['nombre'];
        $_SESSION['tipo'] = $fila['tipo']; // 0:Admin, 1:Maquinista, 2:Agricultor

        header("Location: inicio.php");
        exit();
    } else {
        // Credenciales incorrectas
        $mensaje_feedback = "Usuario o contraseña no válidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acceso - Gestión Agrícola</title>
        <link rel="stylesheet" href="css/estilos.css">
    </head>
    <body>
        <div class="caja-login">
            <h2 style="color:var(--color-primario);">Gestión Agrícola</h2>
            <p>Plataforma de Agricultura de Precisión</p>

            <?php if (!empty($mensaje_feedback)) { ?>
                <div class="mensaje alerta-roja">
                    <?php echo $mensaje_feedback; ?>
                </div>
            <?php } ?>

            <form action="index.php" method="POST">
                <label for="usuario">Usuario:</label>
                <input type="text" id="usuario" name="usuario" placeholder="Ej: admin" required autofocus>

                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" placeholder="******" required>

                <input type="submit" name="btn_login" value="Iniciar Sesión">
            </form>
        </div>
    </body>
</html>