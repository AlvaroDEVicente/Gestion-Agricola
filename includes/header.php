<?php
/**
 * ARCHIVO: includes/header.php
 * PROYECTO: Gestión Agrícola 2.0
 * DESCRIPCIÓN:
 * Cabecera global de la aplicación.
 * - Inicia la estructura HTML5.
 * - Carga hojas de estilo (CSS propio y Leaflet para mapas).
 * - Incluye el menú de navegación superior.
 * * NOTA: Este archivo asume que session_start() ya ha sido llamado en el script principal.
 */
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gestión Agrícola</title>

        <link rel="stylesheet" href="css/estilos.css">

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
              integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
              crossorigin=""/>
    </head>
    <body>

        <div class="contenedor-principal">

            <?php
            // Incluimos la barra de navegación
            include("menu.php");
            ?>

            <div class="contenido-pagina">