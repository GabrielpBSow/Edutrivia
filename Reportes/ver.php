<?php
session_start();
include("../conexion.php");

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['administrador', 'profesor'])) {
    header("Location: ../login.php");
    exit();
}

$clase_id = (int)($_GET['id'] ?? 0);

if ($clase_id <= 0) {
    header("Location: index.php");
    exit();
}

$stmt = $conexion->prepare("SELECT id, nombre, profesor_id FROM clases WHERE id = ?");
$stmt->bind_param("i", $clase_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$clase = $res->fetch_assoc();

if ($_SESSION['rol'] === 'profesor' && $clase['profesor_id'] !== $_SESSION['usuario_id']) {
    $_SESSION['error'] = "No tienes permiso para ver esa clase";
    header("Location: index.php");
    exit();
}

/* Promedios de la clase (para los dos "cards" de arriba) */
$stmt = $conexion->prepare("SELECT AVG(puntaje) AS promedio FROM resultados WHERE clase_id = ? AND juego = 'jeopardy'");
$stmt->bind_param("i", $clase_id);
$stmt->execute();
$promedio_jeopardy = $stmt->get_result()->fetch_assoc()['promedio'];

$stmt = $conexion->prepare("SELECT AVG(puntaje) AS promedio FROM resultados WHERE clase_id = ? AND juego = 'ahorcado'");
$stmt->bind_param("i", $clase_id);
$stmt->execute();
$promedio_ahorcado = $stmt->get_result()->fetch_assoc()['promedio'];

$stmt = $conexion->prepare("SELECT AVG(puntaje) AS promedio FROM resultados WHERE clase_id = ? AND juego = 'carrera'");
$stmt->bind_param("i", $clase_id);
$stmt->execute();
$promedio_carrera = $stmt->get_result()->fetch_assoc()['promedio'];

$stmt = $conexion->prepare("SELECT AVG(puntaje) AS promedio FROM resultados WHERE clase_id = ? AND juego = 'memorama'");
$stmt->bind_param("i", $clase_id);
$stmt->execute();
$promedio_memorama = $stmt->get_result()->fetch_assoc()['promedio'];

/* Alumnos con su mejor puntaje por juego */
$stmt = $conexion->prepare("
    SELECT u.nombre, u.correo,
           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = ? AND r.juego = 'jeopardy') AS mejor_jeopardy,
           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = ? AND r.juego = 'ahorcado') AS mejor_ahorcado,
           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = ? AND r.juego = 'carrera') AS mejor_carrera,
           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = ? AND r.juego = 'memorama') AS mejor_memorama
    FROM inscripciones i
    INNER JOIN usuarios u ON u.id = i.estudiante_id
    WHERE i.clase_id = ?
");
$stmt->bind_param("iiiii", $clase_id, $clase_id, $clase_id, $clase_id, $clase_id);
$stmt->execute();
$res_alumnos = $stmt->get_result();

$alumnos = [];
while ($a = $res_alumnos->fetch_assoc()) {
    $a['total'] = (int)($a['mejor_jeopardy'] ?? 0) + (int)($a['mejor_ahorcado'] ?? 0) + (int)($a['mejor_carrera'] ?? 0) + (int)($a['mejor_memorama'] ?? 0);
    $alumnos[] = $a;
}

/* Ranking: de mayor a menor puntaje combinado */
usort($alumnos, fn($x, $y) => $y['total'] <=> $x['total']);

$medallas = ['🥇', '🥈', '🥉'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" type="img/png" href="../img/LogoEdutrivia (1).png">
    <link rel="stylesheet" href="../css/app.css">
    <title>Reporte — <?= htmlspecialchars($clase['nombre']) ?></title>
</head>
<body class="fondo">

<div class="contenedor-gestion">

    <a href="index.php">
        <img class="img_before" src="../img/before_writh.png" alt="Volver" style="max-width:42px;">
    </a>

    <h1 class="pagina-titulo"><i class='bx bx-bar-chart-alt-2'></i> <?= htmlspecialchars($clase['nombre']) ?></h1>

    <div class="btn-wrap" style="display:flex; gap:.6rem; flex-wrap:wrap; justify-content:center; margin-bottom:1.5rem;">
        <a class="btn" href="exportar_pdf.php?tipo=ranking_clase&id=<?= $clase_id ?>">📄 Exportar PDF</a>
        <a class="btn" href="exportar_excel.php?tipo=ranking_clase&id=<?= $clase_id ?>">📊 Exportar Excel</a>
        <a class="btn" href="tabla.php?tipo=actividad&id=<?= $clase_id ?>">🎮 Ver actividad de alumnos</a>
    </div>

    <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap; margin-bottom:2rem;">
        <div style="background:rgba(255,255,255,.06); border-radius:1rem; padding:1rem 1.5rem; text-align:center; color:#fff;">
            <div style="font-size:.85rem; color:#dcd0ea;">Promedio Jeopardy</div>
            <div style="font-size:1.8rem; font-weight:800; color:#F7A600;"><?= $promedio_jeopardy !== null ? round($promedio_jeopardy) : '—' ?></div>
        </div>
        <div style="background:rgba(255,255,255,.06); border-radius:1rem; padding:1rem 1.5rem; text-align:center; color:#fff;">
            <div style="font-size:.85rem; color:#dcd0ea;">Promedio Ahorcado</div>
            <div style="font-size:1.8rem; font-weight:800; color:#F7A600;"><?= $promedio_ahorcado !== null ? round($promedio_ahorcado) : '—' ?></div>
        </div>
        <div style="background:rgba(255,255,255,.06); border-radius:1rem; padding:1rem 1.5rem; text-align:center; color:#fff;">
            <div style="font-size:.85rem; color:#dcd0ea;">Promedio Carrera</div>
            <div style="font-size:1.8rem; font-weight:800; color:#F7A600;"><?= $promedio_carrera !== null ? round($promedio_carrera) : '—' ?></div>
        </div>
        <div style="background:rgba(255,255,255,.06); border-radius:1rem; padding:1rem 1.5rem; text-align:center; color:#fff;">
            <div style="font-size:.85rem; color:#dcd0ea;">Promedio Memorama</div>
            <div style="font-size:1.8rem; font-weight:800; color:#F7A600;"><?= $promedio_memorama !== null ? round($promedio_memorama) : '—' ?></div>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Mejor Jeopardy</th>
                    <th>Mejor Ahorcado</th>
                    <th>Mejor Carrera</th>
                    <th>Mejor Memorama</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($alumnos) === 0) { ?>
                    <tr><td colspan="8">Todavía no hay alumnos inscritos en esta clase.</td></tr>
                <?php } ?>

                <?php foreach ($alumnos as $idx => $a) { ?>
                    <tr>
                        <td><?= $medallas[$idx] ?? ($idx + 1) ?></td>
                        <td><?= htmlspecialchars($a['nombre']) ?></td>
                        <td><?= htmlspecialchars($a['correo']) ?></td>
                        <td><?= $a['mejor_jeopardy'] !== null ? (int)$a['mejor_jeopardy'] : '—' ?></td>
                        <td><?= $a['mejor_ahorcado'] !== null ? (int)$a['mejor_ahorcado'] : '—' ?></td>
                        <td><?= $a['mejor_carrera'] !== null ? (int)$a['mejor_carrera'] : '—' ?></td>
                        <td><?= $a['mejor_memorama'] !== null ? (int)$a['mejor_memorama'] : '—' ?></td>
                        <td><b><?= $a['total'] ?></b></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>