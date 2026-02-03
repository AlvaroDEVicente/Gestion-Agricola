<?php

/**
 * ARCHIVO: conexion.php
 * PROYECTO: Gestión Agrícola 2.0
 * DESCRIPCIÓN:
 * Módulo de conexión a la base de datos MySQL utilizando la extensión MySQLi.
 * Define las credenciales y establece el juego de caracteres a UTF-8.
 */
// 1. CREDENCIALES DE LA BASE DE DATOS
$servidor = "localhost";
$usuario = "root";
$clave = "";
$base_datos = "agricultura_db";
$puerto = "3306"; // Puerto específico
// 2. CREACIÓN DE LA CONEXIÓN
// Utilizamos el operador @ para suprimir errores nativos y manejarlos condicionalmente.
$conexion = @mysqli_connect($servidor, $usuario, $clave, $base_datos, $puerto);

// 3. VERIFICACIÓN DE ESTADO
if (!$conexion) {
    die("<div style='background:red; color:white; padding:10px;'>
            <strong>ERROR CRÍTICO:</strong> No se pudo conectar a la base de datos.<br>
            <i>" . mysqli_connect_error() . "</i>
         </div>");
}

// 4. CONFIGURACIÓN DE CARACTERES
// Forzamos UTF-8 para visualizar correctamente tildes y caracteres especiales.
if (!mysqli_set_charset($conexion, "utf8")) {
    exit("Error cargando el conjunto de caracteres UTF-8: " . mysqli_error($conexion));
}
?>