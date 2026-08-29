<?php
session_start();
require_once("../csrf.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../login.php");
    exit();
}

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
$token = csrf_token();

$carpeta = __DIR__ . '/archivos';
if (!is_dir($carpeta)) {
    mkdir($carpeta, 0755, true);
}

$archivos = [];
foreach (glob($carpeta . '/*.sql') as $ruta) {
    $archivos[] = [
        'nombre' => basename($ruta),
        'tamano' => filesize($ruta),
        'fecha'  => filemtime($ruta),
    ];
}
usort($archivos, fn($a, $b) => $b['fecha'] <=> $a['fecha']);

function formatearTamano($bytes) {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Respaldo de Base de Datos</title>
</head>
<body class="fondo">

<div class="contenedor-gestion">

    <a href="../Administrador/index.php">
        <img class="img_before" src="../img/before_writh.png" alt="Volver" style="max-width:42px;">
    </a>

    <h1 class="pagina-titulo"><i class='bx bx-cloud-download'></i> Respaldo de Base de Datos</h1>

    <?php if ($error) { ?>
        <p style="color:#ffb3b3; text-align:center; font-weight:bold;"><?= htmlspecialchars($error) ?></p>
    <?php } ?>
    <?php if ($success) { ?>
        <p style="color:#b3ffb3; text-align:center; font-weight:bold;"><?= htmlspecialchars($success) ?></p>
    <?php } ?>

    <div class="btn-wrap">
        <a class="btn" href="generar.php?csrf_token=<?= urlencode($token) ?>">💾 Generar nuevo respaldo</a>
    </div>

    <div class="table-container" style="margin-top:1.5rem;">
        <table>
            <thead>
                <tr>
                    <th>Archivo</th>
                    <th>Tamaño</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($archivos) === 0) { ?>
                    <tr><td colspan="4">Todavía no hay respaldos generados.</td></tr>
                <?php } ?>

                <?php foreach ($archivos as $a) { ?>
                    <tr>
                        <td><?= htmlspecialchars($a['nombre']) ?></td>
                        <td><?= formatearTamano($a['tamano']) ?></td>
                        <td><?= date('d/m/Y H:i', $a['fecha']) ?></td>
                        <td>
                            <a href="descargar.php?archivo=<?= urlencode($a['nombre']) ?>&csrf_token=<?= urlencode($token) ?>">⬇️ Descargar</a>
                            &nbsp;
                            <a href="#" onclick="confirmarAccion('eliminar.php?archivo=<?= urlencode($a['nombre']) ?>&csrf_token=<?= urlencode($token) ?>', '¿Eliminar el respaldo <?= htmlspecialchars(addslashes($a['nombre'])) ?>? Esta acción no se puede deshacer.'); return false;">🗑️</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

</div>

<script src="../js/confirmar.js"></script>

</body>
</html>