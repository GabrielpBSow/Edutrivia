<?php
session_start();
include("../conexion.php");
require_once("../csrf.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'estudiante') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

if (!csrf_validar($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = "Sesión de formulario inválida, intenta de nuevo";
    header("Location: index.php");
    exit();
}

$codigo = strtoupper(trim($_POST['codigo'] ?? ''));

if ($codigo === '') {
    $_SESSION['error'] = "Ingresa un código";
    header("Location: index.php");
    exit();
}

$stmt = $conexion->prepare("SELECT id, nombre FROM clases WHERE codigo = ?");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    $_SESSION['error'] = "No existe ninguna clase con ese código";
    header("Location: index.php");
    exit();
}

$clase = $res->fetch_assoc();

/* Evitar duplicados: si ya está inscrito, no hacer nada raro */
$check = $conexion->prepare("SELECT id FROM inscripciones WHERE clase_id = ? AND estudiante_id = ?");
$check->bind_param("ii", $clase['id'], $_SESSION['usuario_id']);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    $_SESSION['error'] = "Ya estás inscrito en esa clase";
    header("Location: index.php");
    exit();
}

$stmt = $conexion->prepare("INSERT INTO inscripciones (clase_id, estudiante_id) VALUES (?, ?)");
$stmt->bind_param("ii", $clase['id'], $_SESSION['usuario_id']);

if ($stmt->execute()) {
    $_SESSION['success'] = "Te uniste a la clase: " . $clase['nombre'];
} else {
    $_SESSION['error'] = "Error al unirte a la clase";
}

header("Location: index.php");
exit();