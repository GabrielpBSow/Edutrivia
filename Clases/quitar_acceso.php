<?php
session_start();
include("../conexion.php");

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['administrador', 'profesor'])) {
    header("Location: ../login.php");
    exit();
}

$clase_id = (int)($_GET['clase_id'] ?? 0);
$estudiante_id = (int)($_GET['estudiante_id'] ?? 0);
$juego = $_GET['juego'] ?? '';
$token = $_GET['csrf_token'] ?? '';

$juegos_validos = ['jeopardy', 'ahorcado', 'carrera', 'memorama'];

if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    $_SESSION['error'] = "Token inválido, intenta de nuevo.";
    header("Location: ver.php?id={$clase_id}");
    exit();
}

if ($clase_id <= 0 || $estudiante_id <= 0 || !in_array($juego, $juegos_validos)) {
    $_SESSION['error'] = "Solicitud inválida.";
    header("Location: ver.php?id={$clase_id}");
    exit();
}

$stmt = $conexion->prepare("SELECT profesor_id FROM clases WHERE id = ?");
$stmt->bind_param("i", $clase_id);
$stmt->execute();
$clase = $stmt->get_result()->fetch_assoc();

if (!$clase) {
    header("Location: index.php");
    exit();
}

if ($_SESSION['rol'] === 'profesor' && $clase['profesor_id'] !== $_SESSION['usuario_id']) {
    $_SESSION['error'] = "No tienes permiso para esta clase.";
    header("Location: index.php");
    exit();
}

$stmt = $conexion->prepare("UPDATE accesos_juego SET habilitado = 0 WHERE clase_id = ? AND estudiante_id = ? AND juego = ?");
$stmt->bind_param("iis", $clase_id, $estudiante_id, $juego);
$stmt->execute();

$_SESSION['success'] = "Acceso quitado.";
header("Location: ver.php?id={$clase_id}");
exit();