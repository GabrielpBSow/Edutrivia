<?php
session_start();
include("../conexion.php");

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['administrador', 'profesor'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['rol'] === 'administrador') {
    $stmt = $conexion->prepare("
        SELECT c.id, c.nombre, u.nombre AS profesor_nombre,
               (SELECT COUNT(*) FROM inscripciones i WHERE i.clase_id = c.id) AS total_alumnos,
               (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'jeopardy') AS promedio_jeopardy,
               (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'ahorcado') AS promedio_ahorcado,
               (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'carrera') AS promedio_carrera,
               (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'memorama') AS promedio_memorama
        FROM clases c
        INNER JOIN usuarios u ON u.id = c.profesor_id
        ORDER BY c.nombre ASC
    ");
    $stmt->execute();
} else {
    $stmt = $conexion->prepare("
        SELECT c.id, c.nombre, NULL AS profesor_nombre,
               (SELECT COUNT(*) FROM inscripciones i WHERE i.clase_id = c.id) AS total_alumnos,
               (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'jeopardy') AS promedio_jeopardy,
               (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'ahorcado') AS promedio_ahorcado,
               (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'carrera') AS promedio_carrera,
               (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'memorama') AS promedio_memorama
        FROM clases c
        WHERE c.profesor_id = ?
        ORDER BY c.nombre ASC
    ");
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
}

$clases = $stmt->get_result();
$panel = $_SESSION['rol'] === 'administrador' ? '../Administrador/index.php' : '../Profesor/index.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" type="img/png" href="../img/LogoEdutrivia (1).png">
    <link rel="stylesheet" href="../css/app.css">
    <title>Reportes</title>
</head>
<body class="fondo">

<div class="contenedor-gestion">

    <a href="<?= $panel ?>">
        <img class="img_before" src="../img/before_writh.png" alt="Volver" style="max-width:42px;">
    </a>

    <h1 class="pagina-titulo"><i class='bx bx-bar-chart-alt-2'></i> Reportes</h1>
    <p style="color:#dcd0ea; text-align:center; margin-top:-1.5rem; margin-bottom:1rem;">
        Promedio de puntaje por clase — entra a una clase para ver el ranking completo de alumnos
    </p>

    <div class="btn-wrap" style="margin-bottom:1rem;">
        <a class="btn" href="encuestas.php">🙂 Ver encuestas de satisfacción</a>
    </div>

    <div class="btn-wrap" style="display:flex; gap:.6rem; justify-content:center; margin-bottom:1.5rem;">
        <a class="btn" href="exportar_pdf.php?tipo=promedio_clases">📄 Exportar PDF</a>
        <a class="btn" href="exportar_excel.php?tipo=promedio_clases">📊 Exportar Excel</a>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Clase</th>
                    <?php if ($_SESSION['rol'] === 'administrador') { ?><th>Profesor</th><?php } ?>
                    <th>Alumnos</th>
                    <th>Promedio Jeopardy</th>
                    <th>Promedio Ahorcado</th>
                    <th>Promedio Carrera</th>
                    <th>Promedio Memorama</th>
                    <th>Ranking</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($clases->num_rows === 0) { ?>
                    <tr><td colspan="8">Todavía no hay clases con resultados que reportar.</td></tr>
                <?php } ?>

                <?php while ($c = $clases->fetch_assoc()) { ?>
                    <tr>
                        <td><?= htmlspecialchars($c['nombre']) ?></td>
                        <?php if ($_SESSION['rol'] === 'administrador') { ?><td><?= htmlspecialchars($c['profesor_nombre']) ?></td><?php } ?>
                        <td><?= (int)$c['total_alumnos'] ?></td>
                        <td><?= $c['promedio_jeopardy'] !== null ? round($c['promedio_jeopardy']) : '—' ?></td>
                        <td><?= $c['promedio_ahorcado'] !== null ? round($c['promedio_ahorcado']) : '—' ?></td>
                        <td><?= $c['promedio_carrera'] !== null ? round($c['promedio_carrera']) : '—' ?></td>
                        <td><?= $c['promedio_memorama'] !== null ? round($c['promedio_memorama']) : '—' ?></td>
                        <td><a href="ver.php?id=<?= (int)$c['id'] ?>">Ver ranking →</a></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <h2 class="pagina-titulo" style="font-size:1.3rem; margin-top:3rem;"><i class='bx bx-list-ul'></i> Otros reportes</h2>

    <div class="table-container">
        <table>
            <thead><tr><th>Reporte</th><th></th></tr></thead>
            <tbody>
                <?php if ($_SESSION['rol'] === 'administrador') { ?>
                <tr><td>Usuarios por Rol</td><td><a href="tabla.php?tipo=usuarios">Ver reporte →</a></td></tr>
                <?php } ?>
                <tr><td>Inventario de Mazos</td><td><a href="tabla.php?tipo=mazos">Ver reporte →</a></td></tr>
                <tr><td>Reporte General de Calificaciones</td><td><a href="tabla.php?tipo=general">Ver reporte →</a></td></tr>
                <tr><td>Participación en Juegos</td><td><a href="tabla.php?tipo=participacion">Ver reporte →</a></td></tr>
                <tr><td>Clases sin Actividad</td><td><a href="tabla.php?tipo=clases_inactivas">Ver reporte →</a></td></tr>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>