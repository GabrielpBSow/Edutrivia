<?php
session_start();
include("../conexion.php");
require_once("../csrf.php");

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

/* Confirmar que la clase exista y sea del profesor (o que sea admin) */
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

/* No tiene sentido dar acceso a un juego si la clase todavía no tiene
   ningún mazo activado: no habría preguntas de dónde sacar el juego */
$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM clase_mazos WHERE clase_id = ?");
$stmt->bind_param("i", $clase_id);
$stmt->execute();
$total_mazos = $stmt->get_result()->fetch_assoc()['total'];

if ($total_mazos == 0) {
    $_SESSION['error'] = "Esta clase todavía no tiene ningún mazo activado. Actívalo primero antes de dar acceso a los juegos.";
    header("Location: ver.php?id={$clase_id}");
    exit();
}

/* Confirmar que el alumno pertenezca a esta clase */
$stmt = $conexion->prepare("SELECT id FROM inscripciones WHERE clase_id = ? AND estudiante_id = ?");
$stmt->bind_param("ii", $clase_id, $estudiante_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $_SESSION['error'] = "Ese alumno no pertenece a esta clase.";
    header("Location: ver.php?id={$clase_id}");
    exit();
}

/* Otorgar (o renovar) el acceso: un intento nuevo */
$stmt = $conexion->prepare("
    INSERT INTO accesos_juego (clase_id, estudiante_id, juego, habilitado, otorgado_en, usado_en)
    VALUES (?, ?, ?, 1, NOW(), NULL)
    ON DUPLICATE KEY UPDATE habilitado = 1, otorgado_en = NOW(), usado_en = NULL
");
$stmt->bind_param("iis", $clase_id, $estudiante_id, $juego);
$stmt->execute();

$_SESSION['success'] = "Acceso otorgado.";
header("Location: ver.php?id={$clase_id}");
exit();