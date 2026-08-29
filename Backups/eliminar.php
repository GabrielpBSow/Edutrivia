<?php
session_start();
require_once("../csrf.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../login.php");
    exit();
}

$nombre = basename($_GET['archivo'] ?? '');
$token = $_GET['csrf_token'] ?? '';

if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    $_SESSION['error'] = "Token inválido, intenta de nuevo.";
    header("Location: index.php");
    exit();
}

$ruta = __DIR__ . '/archivos/' . $nombre;

if ($nombre !== '' && preg_match('/^respaldo_[0-9\-_]+\.sql$/', $nombre) && file_exists($ruta)) {
    unlink($ruta);
    $_SESSION['success'] = "Respaldo eliminado.";
} else {
    $_SESSION['error'] = "Archivo inválido.";
}

header("Location: index.php");
exit();