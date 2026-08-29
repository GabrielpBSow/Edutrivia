<?php
session_start();
include("../conexion.php");
require_once(__DIR__ . '/lib_reportes.php');

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['administrador', 'profesor'])) {
    header("Location: ../login.php");
    exit();
}

$tipo = $_GET['tipo'] ?? '';
$id = (int)($_GET['id'] ?? 0);

$datos = obtener_datos_reporte($conexion, $tipo, $id);

if (isset($datos['error'])) {
    $_SESSION['error'] = $datos['error'];
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" type="img/png" href="../img/LogoEdutrivia (1).png">
    <link rel="stylesheet" href="../css/app.css">
    <title><?= htmlspecialchars($datos['titulo']) ?></title>
</head>
<body class="fondo">

<div class="contenedor-gestion">

    <a href="<?= $id > 0 ? '../Clases/ver.php?id=' . $id : 'index.php' ?>">
        <img class="img_before" src="../img/before_writh.png" alt="Volver" style="max-width:42px;">
    </a>

    <h1 class="pagina-titulo"><i class='bx bx-bar-chart-alt-2'></i> <?= htmlspecialchars($datos['titulo']) ?></h1>

    <div class="btn-wrap" style="display:flex; gap:.6rem; justify-content:center; margin-bottom:1.5rem;">
        <a class="btn" href="exportar_pdf.php?tipo=<?= urlencode($tipo) ?>&id=<?= $id ?>">📄 Exportar PDF</a>
        <a class="btn" href="exportar_excel.php?tipo=<?= urlencode($tipo) ?>&id=<?= $id ?>">📊 Exportar Excel</a>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr><?php foreach ($datos['columnas'] as $c) { ?><th><?= htmlspecialchars($c) ?></th><?php } ?></tr>
            </thead>
            <tbody>
                <?php if (count($datos['filas']) === 0) { ?>
                    <tr><td colspan="<?= count($datos['columnas']) ?>">No hay datos para este reporte todavía.</td></tr>
                <?php } ?>
                <?php foreach ($datos['filas'] as $fila) { ?>
                    <tr><?php foreach ($fila as $v) { ?><td><?= htmlspecialchars((string)$v) ?></td><?php } ?></tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>