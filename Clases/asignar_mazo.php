<?php
session_start();
include("../conexion.php");
require_once("../csrf.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'profesor') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$clase_id = (int)($_POST['clase_id'] ?? 0);

if (!csrf_validar($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = "Sesión de formulario inválida, intenta de nuevo";
    header("Location: ver.php?id=$clase_id");
    exit();
}

/* Validar que la clase sea del profesor */
$stmt = $conexion->prepare("SELECT profesor_id FROM clases WHERE id = ?");
$stmt->bind_param("i", $clase_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0 || $res->fetch_assoc()['profesor_id'] !== $_SESSION['usuario_id']) {
    $_SESSION['error'] = "No tienes permiso sobre esa clase";
    header("Location: index.php");
    exit();
}

$mazo_id = (int)($_POST['mazo_id'] ?? 0);
$cantidad_preguntas = (int)($_POST['cantidad_preguntas'] ?? 0);

/* Validar que el mazo sea del profesor, y cuántas preguntas tiene de verdad */
$stmt = $conexion->prepare("
    SELECT profesor_id, (SELECT COUNT(*) FROM preguntas WHERE mazo_id = mazos.id) AS total
    FROM mazos WHERE id = ?
");
$stmt->bind_param("i", $mazo_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    $_SESSION['error'] = "El mazo no existe";
    header("Location: ver.php?id=$clase_id");
    exit();
}

$mazo = $res->fetch_assoc();

if ($mazo['profesor_id'] !== $_SESSION['usuario_id']) {
    $_SESSION['error'] = "Ese mazo no es tuyo";
    header("Location: ver.php?id=$clase_id");
    exit();
}

/* No permitir pedir más preguntas de las que el mazo realmente tiene */
if ($cantidad_preguntas <= 0 || $cantidad_preguntas > $mazo['total']) {
    $_SESSION['error'] = "La cantidad de preguntas no es válida para ese mazo (tiene {$mazo['total']})";
    header("Location: ver.php?id=$clase_id");
    exit();
}

$stmt = $conexion->prepare("INSERT INTO clase_mazos (clase_id, mazo_id, cantidad_preguntas) VALUES (?, ?, ?)");
$stmt->bind_param("iii", $clase_id, $mazo_id, $cantidad_preguntas);

if ($stmt->execute()) {
    $_SESSION['success'] = "Mazo activado en la clase correctamente";
} else {
    $_SESSION['error'] = "Error al activar el mazo (¿ya estaba activado en esta clase?)";
}

header("Location: ver.php?id=$clase_id");
exit();