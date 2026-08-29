<?php
session_start();
include("../conexion.php");

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['administrador', 'profesor'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['rol'] === 'administrador') {
    /* El admin ve las encuestas de TODOS los alumnos del sistema */
    $stmt = $conexion->prepare("SELECT opcion, COUNT(*) AS total FROM encuestas GROUP BY opcion");
    $stmt->execute();
} else {
    /* El profesor solo ve las encuestas de alumnos inscritos en SUS clases
       (la tabla encuestas no guarda clase_id, así que filtramos por alumno) */
    $stmt = $conexion->prepare("
        SELECT e.opcion, COUNT(*) AS total
        FROM encuestas e
        WHERE e.usuario_id IN (
            SELECT DISTINCT i.estudiante_id
            FROM inscripciones i
            INNER JOIN clases c ON c.id = i.clase_id
            WHERE c.profesor_id = ?
        )
        GROUP BY e.opcion
    ");
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
}

$resultado = $stmt->get_result();

$conteo = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
while ($row = $resultado->fetch_assoc()) {
    $conteo[(int)$row['opcion']] = (int)$row['total'];
}

$total_votos = array_sum($conteo);

$opciones = [
    1 => ['emoji' => '🤩', 'texto' => 'Excelente', 'color' => '#21C87A'],
    2 => ['emoji' => '🙂', 'texto' => 'Bueno',     'color' => '#F7A600'],
    3 => ['emoji' => '😐', 'texto' => 'Regular',   'color' => '#F7A600'],
    4 => ['emoji' => '😕', 'texto' => 'Malo',      'color' => '#EF3E36'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" type="img/png" href="../img/LogoEdutrivia (1).png">
    <link rel="stylesheet" href="../css/app.css">
    <title>Encuestas de Satisfacción</title>
</head>
<body class="fondo">

<div class="contenedor-gestion">

    <a href="index.php">
        <img class="img_before" src="../img/before_writh.png" alt="Volver" style="max-width:42px;">
    </a>

    <h1 class="pagina-titulo"><i class='bx bx-message-rounded-dots'></i> Encuestas de Satisfacción</h1>
    <p style="color:#dcd0ea; text-align:center; margin-top:-1.5rem; margin-bottom:1.2rem;">
        Total de respuestas: <b><?= $total_votos ?></b>
        <?= $_SESSION['rol'] === 'profesor' ? '(solo de tus alumnos)' : '(de todo el sistema)' ?>
    </p>

    <div class="btn-wrap" style="display:flex; gap:.6rem; justify-content:center; margin-bottom:1.5rem;">
        <a class="btn" href="exportar_pdf.php?tipo=encuestas">📄 Exportar PDF</a>
        <a class="btn" href="exportar_excel.php?tipo=encuestas">📊 Exportar Excel</a>
    </div>

    <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
        <?php foreach ($opciones as $valor => $o) {
            $cantidad = $conteo[$valor];
            $porcentaje = $total_votos > 0 ? round($cantidad / $total_votos * 100) : 0;
        ?>
        <div style="background:rgba(255,255,255,.06); border-radius:1.2rem; padding:1.4rem 1.6rem; text-align:center; color:#fff; min-width:150px;">
            <div style="font-size:2.2rem;"><?= $o['emoji'] ?></div>
            <div style="font-size:.85rem; color:#dcd0ea; margin:.3rem 0;"><?= $o['texto'] ?></div>
            <div style="font-size:1.8rem; font-weight:800; color:<?= $o['color'] ?>;"><?= $cantidad ?></div>
            <div style="font-size:.85rem; color:#dcd0ea;"><?= $porcentaje ?>%</div>
        </div>
        <?php } ?>
    </div>

    <?php if ($total_votos === 0) { ?>
        <p style="color:#dcd0ea; text-align:center; margin-top:2rem;">Todavía no hay respuestas de encuesta registradas.</p>
    <?php } ?>

</div>

</body>
</html>