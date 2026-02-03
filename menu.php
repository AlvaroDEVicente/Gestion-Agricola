<?php
/**
 * ARCHIVO: menu.php
 * DESCRIPCIÓN:
 * Barra de navegación dinámica basada en el rol del usuario.
 * Se incluye dentro de header.php.
 */
// Verificación de seguridad: Evitar carga directa sin sesión
if (!isset($_SESSION['usuario'])) {
    exit("Acceso denegado.");
}

// Recuperación de datos de sesión
$nombre_usuario = $_SESSION['nombre'];
$tipo_usuario = $_SESSION['tipo']; // 0: Admin, 1: Maquinista, 2: Agricultor
// Mapeo de roles a texto legible
$roles_texto = array(
    0 => "ADMINISTRADOR",
    1 => "MAQUINISTA",
    2 => "AGRICULTOR"
);
$rol_actual = isset($roles_texto[$tipo_usuario]) ? $roles_texto[$tipo_usuario] : "USUARIO";
?>

<nav class="barra-navegacion">

    <div class="nav-izquierda">

        <div class="info-usuario">
            <strong>USUARIO</strong> |
            <?php echo $nombre_usuario; ?>
            <small>(<?php echo $rol_actual; ?>)</small>
        </div>

        <a href="inicio.php">Inicio</a>

        <?php if ($tipo_usuario == 0) { ?>
            <span class="separador">|</span>
            <a href="gestion_usuarios.php">Usuarios</a>
            <a href="gestion_maquinas.php">Máquinas</a>
            <a href="gestion_parcelas.php">Parcelas</a>
            <a href="gestion_trabajos.php">Trabajos</a>
            <a href="gestion_facturas.php">Facturación</a>
        <?php } ?>

        <?php if ($tipo_usuario == 1) { ?>
            <span class="separador">|</span>
            <a href="mis_trabajos.php">Mis Trabajos</a>
        <?php } ?>

        <?php if ($tipo_usuario == 2) { ?>
            <span class="separador">|</span>
            <a href="mis_parcelas.php">Mis Tierras</a>
            <a href="estado_trabajos.php">Estado Trabajos</a>
            <a href="mis_facturas.php">Facturas</a>
        <?php } ?>

    </div>

    <div class="nav-derecha">
        <form action="logout.php" method="POST" style="margin:0;">
            <input type="submit" value="Cerrar Sesión" class="btn-salir">
        </form>
    </div>

</nav>