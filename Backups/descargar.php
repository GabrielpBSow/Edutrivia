<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../login.php");
    exit();
}

$nombre = basename($_GET['archivo'] ?? '');
$ruta = __DIR__ . '/archivos/' . $nombre;

/* Solo se aceptan nombres con el formato exacto que nosotros generamos,
   para evitar cualquier intento de acceder a otro archivo del servidor */
if ($nombre === '' || !preg_match('/^respaldo_[0-9\-_]+\.sql$/', $nombre) || !file_exists($ruta)) {
    $_SESSION['error'] = "Archivo de respaldo inválido.";
    header("Location: index.php");
    exit();
}

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $nombre . '"');
header('Content-Length: ' . filesize($ruta));
header('Cache-Control: no-cache, must-revalidate');
readfile($ruta);
exit();