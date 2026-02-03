<?php

/**
 * ARCHIVO: logout.php
 * PROYECTO: Gestión Agrícola 2.0
 * DESCRIPCIÓN:
 * Destruye la sesión actual del servidor y redirige al usuario a la pantalla de login.
 */
session_start();

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión finalmente
session_destroy();

// Redirección al índice
header("Location: index.php");
exit();
?>