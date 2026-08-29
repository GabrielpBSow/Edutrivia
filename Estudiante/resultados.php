<?php
session_start();
include("../conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'estudiante') {
    header("Location: ../login.php");
    exit();
}

$stmt = $conexion->prepare("
    SELECT c.id, c.nombre,
           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = ? AND r.clase_id = c.id AND r.juego = 'jeopardy') AS mejor_jeopardy,
           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = ? AND r.clase_id = c.id AND r.juego = 'ahorcado') AS mejor_ahorcado,
           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = ? AND r.clase_id = c.id AND r.juego = 'carrera') AS mejor_carrera,
           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = ? AND r.clase_id = c.id AND r.juego = 'memorama') AS mejor_memorama,
           (SELECT MIN(r.tiempo_segundos) FROM resultados r WHERE r.estudiante_id = ? AND r.clase_id = c.id AND r.juego = 'memorama' AND r.tiempo_segundos IS NOT NULL) AS mejor_tiempo_memorama
    FROM inscripciones i
    INNER JOIN clases c ON c.id = i.clase_id
    WHERE i.estudiante_id = ?
    ORDER BY c.nombre ASC
");
$stmt->bind_param("iiiiii", $_SESSION['usuario_id'], $_SESSION['usuario_id'], $_SESSION['usuario_id'], $_SESSION['usuario_id'], $_SESSION['usuario_id'], $_SESSION['usuario_id']);
$stmt->execute();
$clases = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" type="img/png" href="../img/LogoEdutrivia (1).png">
    <link rel="stylesheet" href="../css/app.css">
    <title>Mis Resultados</title>
</head>
<body class="fondo">

<div class="contenedor-gestion">

    <a href="index.php">
        <img class="img_before" src="../img/before_writh.png" alt="Volver" style="max-width:42px;">
    </a>

    <h1 class="pagina-titulo"><i class='bx bx-trophy'></i> Mis Resultados</h1>
    <p style="color:#dcd0ea; text-align:center; margin-top:-1.5rem; margin-bottom:2rem;">
        Tu mejor puntaje en cada clase a la que perteneces
    </p>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Clase</th>
                    <th>Mejor puntaje Jeopardy</th>
                    <th>Mejor puntaje Ahorcado</th>
                    <th>Mejor puntaje Carrera</th>
                    <th>Mejor puntaje Memorama</th>
                    <th>Mejor tiempo Memorama</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($clases->num_rows === 0) { ?>
                    <tr><td colspan="6">Todavía no estás inscrito en ninguna clase.</td></tr>
                <?php } ?>

                <?php while ($c = $clases->fetch_assoc()) { ?>
                    <tr>
                        <td><?= htmlspecialchars($c['nombre']) ?></td>
                        <td><?= $c['mejor_jeopardy'] !== null ? (int)$c['mejor_jeopardy'] : '—' ?></td>
                        <td><?= $c['mejor_ahorcado'] !== null ? (int)$c['mejor_ahorcado'] : '—' ?></td>
                        <td><?= $c['mejor_carrera'] !== null ? (int)$c['mejor_carrera'] : '—' ?></td>
                        <td><?= $c['mejor_memorama'] !== null ? (int)$c['mejor_memorama'] : '—' ?></td>
                        <td><?= $c['mejor_tiempo_memorama'] !== null ? sprintf('%02d:%02d', intdiv($c['mejor_tiempo_memorama'], 60), $c['mejor_tiempo_memorama'] % 60) : '—' ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>