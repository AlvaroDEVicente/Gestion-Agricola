<?php
/**
 * ARCHIVO: inicio.php
 * PROYECTO: Gestión Agrícola 2.0
 * DESCRIPCIÓN:
 * Panel de control principal (Dashboard).
 * Calcula y visualiza estadísticas en tiempo real según el perfil del usuario logueado.
 */
session_start();
require("conexion.php");

// Verificación de seguridad
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

include("includes/header.php");

$rol_usuario = $_SESSION['tipo'];
$mi_login = $_SESSION['usuario'];
?>

<div class="bienvenida" style="margin-bottom: 30px;">
    <h1>Panel de Control</h1>
    <p>Bienvenido, <b><?php echo $_SESSION['nombre']; ?></b>. Resumen de actividad:</p>
</div>

<div class="dashboard">

    <?php
    // =========================================================================
    // ROL 0: ADMINISTRADOR (Visión Global)
    // =========================================================================
    if ($rol_usuario == 0) {

        // KPI 1: Solicitudes pendientes de asignación (Sin maquinista)
        $sql_pend = "SELECT COUNT(*) as n FROM trabajos WHERE login_maquinista IS NULL";
        $n_pend = mysqli_fetch_assoc(mysqli_query($conexion, $sql_pend))['n'];

        // KPI 2: Total de dinero pendiente de cobro
        $sql_money = "SELECT SUM(total) as t FROM facturas WHERE estado_pago = 'Pendiente'";
        $row_money = mysqli_fetch_assoc(mysqli_query($conexion, $sql_money));
        $money = $row_money['t'] ? number_format($row_money['t'], 2) : "0.00";

        // KPI 3: Total de Parcelas
        $sql_parc = "SELECT COUNT(*) as n FROM parcelas";
        $n_parc = mysqli_fetch_assoc(mysqli_query($conexion, $sql_parc))['n'];

        // KPI 4: Flota de Máquinas
        $sql_maq = "SELECT COUNT(*) as n FROM maquinas";
        $n_maq = mysqli_fetch_assoc(mysqli_query($conexion, $sql_maq))['n'];

        // KPI 5: Usuarios Totales
        $sql_users = "SELECT COUNT(*) as n FROM usuarios";
        $n_users = mysqli_fetch_assoc(mysqli_query($conexion, $sql_users))['n'];
        ?>
        <div class="card" onclick="window.location = 'gestion_trabajos.php';" style="cursor:pointer; border-top-color:#ffc107;">
            <h3>Solicitudes Nuevas</h3>
            <p style="font-size:2.5em; font-weight:bold; color:#ffc107;"><?php echo $n_pend; ?></p>
            <small>Pendientes de asignar recursos</small>
        </div>

        <div class="card" onclick="window.location = 'gestion_facturas.php';" style="cursor:pointer; border-top-color:#dc3545;">
            <h3>Por Cobrar</h3>
            <p style="font-size:2.5em; font-weight:bold; color:#dc3545;"><?php echo $money; ?> €</p>
            <small>Facturas emitidas pendientes</small>
        </div>

        <div class="card" onclick="window.location = 'gestion_parcelas.php';" style="cursor:pointer; border-top-color:#28a745;">
            <h3>Fincas Registradas</h3>
            <p style="font-size:2.5em; font-weight:bold; color:#28a745;"><?php echo $n_parc; ?></p>
            <small>Superficie gestionada</small>
        </div>

        <div class="card" onclick="window.location = 'gestion_maquinas.php';" style="cursor:pointer; border-top-color:#6f42c1;">
            <h3>Flota de Máquinas</h3>
            <p style="font-size:2.5em; font-weight:bold; color:#6f42c1;"><?php echo $n_maq; ?></p>
            <small>Equipos activos</small>
        </div>

        <div class="card" onclick="window.location = 'gestion_usuarios.php';" style="cursor:pointer; border-top-color:#007bff;">
            <h3>Usuarios</h3>
            <p style="font-size:2.5em; font-weight:bold; color:#007bff;"><?php echo $n_users; ?></p>
            <small>Total registrados</small>
        </div>

        <?php
        // =========================================================================
        // ROL 1: MAQUINISTA (Visión Operativa)
        // =========================================================================
    } elseif ($rol_usuario == 1) {

        // KPI 1: Pendientes de Inicio
        $sql_mp = "SELECT COUNT(*) as n FROM trabajos WHERE login_maquinista='$mi_login' AND estado=0";
        $n_mp = mysqli_fetch_assoc(mysqli_query($conexion, $sql_mp))['n'];

        // KPI 2: En Curso
        $sql_mc = "SELECT COUNT(*) as n FROM trabajos WHERE login_maquinista='$mi_login' AND estado=1";
        $n_mc = mysqli_fetch_assoc(mysqli_query($conexion, $sql_mc))['n'];

        // KPI 3: Finalizados
        $sql_mf = "SELECT COUNT(*) as n FROM trabajos WHERE login_maquinista='$mi_login' AND estado=2";
        $n_mf = mysqli_fetch_assoc(mysqli_query($conexion, $sql_mf))['n'];
        ?>
        <div class="card" onclick="window.location = 'mis_trabajos.php';" style="cursor:pointer; border-top-color:#ffc107;">
            <h3>Tareas Pendientes</h3>
            <p style="font-size:3em; font-weight:bold; color:#ffc107;"><?php echo $n_mp; ?></p>
            <small>Trabajos asignados sin empezar</small>
        </div>

        <div class="card" onclick="window.location = 'mis_trabajos.php';" style="cursor:pointer; border-top-color:#007bff;">
            <h3>En Curso</h3>
            <p style="font-size:3em; font-weight:bold; color:#007bff;"><?php echo $n_mc; ?></p>
            <small>Trabajos activos ahora</small>
        </div>

        <div class="card" onclick="window.location = 'mis_trabajos.php';" style="cursor:pointer; border-top-color:#28a745;">
            <h3>Historial</h3>
            <p style="font-size:3em; font-weight:bold; color:#28a745;"><?php echo $n_mf; ?></p>
            <small>Trabajos completados</small>
        </div>

        <?php
        // =========================================================================
        // ROL 2: AGRICULTOR (Visión Cliente)
        // =========================================================================
    } elseif ($rol_usuario == 2) {

        // KPI 1: Parcelas Propias
        $n_tierras = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM parcelas WHERE login_agricultor='$mi_login'"));

        // KPI 2: Estado de sus solicitudes (Desglose)
        // NOTA: Contamos estados >= 2 (Finalizado, Fact. Solicitada, Facturado) como 'Finalizados'
        $sql_stats = "SELECT
                        SUM(CASE WHEN t.estado = 0 THEN 1 ELSE 0 END) as pend,
                        SUM(CASE WHEN t.estado = 1 THEN 1 ELSE 0 END) as curso,
                        SUM(CASE WHEN t.estado >= 2 THEN 1 ELSE 0 END) as fin
                      FROM trabajos t
                      JOIN parcelas p ON t.codigo_parcela = p.codigo_parcela
                      WHERE p.login_agricultor = '$mi_login'";
        $stats = mysqli_fetch_assoc(mysqli_query($conexion, $sql_stats));

        $t_pend = $stats['pend'] ? $stats['pend'] : 0;
        $t_curso = $stats['curso'] ? $stats['curso'] : 0;
        $t_fin = $stats['fin'] ? $stats['fin'] : 0;
        $t_total = $t_pend + $t_curso + $t_fin;

        // KPI 3: Facturas Pendientes de Pago
        $sql_fac = "SELECT COUNT(*) as n FROM facturas f
                    JOIN trabajos t ON f.codigo_trabajo = t.codigo_trabajo
                    JOIN parcelas p ON t.codigo_parcela = p.codigo_parcela
                    WHERE p.login_agricultor='$mi_login' AND f.estado_pago='Pendiente'";
        $n_fac = mysqli_fetch_assoc(mysqli_query($conexion, $sql_fac))['n'];
        ?>

        <div class="card" onclick="window.location = 'mis_parcelas.php';" style="cursor:pointer; border-top-color:#28a745;">
            <h3>Mis Tierras</h3>
            <p style="font-size:3em; font-weight:bold; color:#28a745;"><?php echo $n_tierras; ?></p>
            <small>Fincas registradas</small>
        </div>

        <div class="card" onclick="window.location = 'estado_trabajos.php';" style="cursor:pointer; border-top-color:#007bff;">
            <h3>Actividad / Trabajos</h3>
            <p style="font-size:2.5em; font-weight:bold; margin:5px 0 10px 0; color:#333;">
                <?php echo $t_total; ?>
            </p>
            <div style="display:flex; justify-content:space-around; background:#f8f9fa; padding:5px; border-radius:5px;">
                <div style="text-align:center;">
                    <b style="color:#ffc107; font-size:1.2em;"><?php echo $t_pend; ?></b><br>
                    <small>Pend.</small>
                </div>
                <div style="text-align:center;">
                    <b style="color:#007bff; font-size:1.2em;"><?php echo $t_curso; ?></b><br>
                    <small>Curso</small>
                </div>
                <div style="text-align:center;">
                    <b style="color:#28a745; font-size:1.2em;"><?php echo $t_fin; ?></b><br>
                    <small>Fin</small>
                </div>
            </div>
        </div>

        <div class="card" onclick="window.location = 'mis_facturas.php';" style="cursor:pointer; border-top-color:#dc3545;">
            <h3>Facturas Pendientes</h3>
            <p style="font-size:3em; font-weight:bold; color:#dc3545;"><?php echo $n_fac; ?></p>
            <small>Recibos por abonar</small>
        </div>

    <?php } ?>

</div>

<style>
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        transition: transform 0.2s, box-shadow 0.2s;
    }
</style>

<?php include("includes/footer.php"); ?>