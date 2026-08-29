<?php
session_start();
include("../conexion.php");
require_once("../csrf.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'profesor') {
    header("Location: ../login.php");
    exit();
}

$clase_id = (int)($_GET['clase_id'] ?? 0);

if (!csrf_validar($_GET['csrf_token'] ?? '')) {
    $_SESSION['error'] = "Enlace inválido o expirado, intenta de nuevo";
    header("Location: ver.php?id=$clase_id");
    exit();
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0 || $clase_id <= 0) {
    header("Location: index.php");
    exit();
}

/* Validar que la clase sea del profesor antes de tocar nada */
$stmt = $conexion->prepare("SELECT profesor_id FROM clases WHERE id = ?");
$stmt->bind_param("i", $clase_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0 || $res->fetch_assoc()['profesor_id'] !== $_SESSION['usuario_id']) {
    $_SESSION['error'] = "No tienes permiso sobre esa clase";
    header("Location: index.php");
    exit();
}

$stmt = $conexion->prepare("DELETE FROM clase_mazos WHERE id = ? AND clase_id = ?");
$stmt->bind_param("ii", $id, $clase_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Mazo desactivado de la clase";
} else {
    $_SESSION['error'] = "Error al quitar el mazo";
}

header("Location: ver.php?id=$clase_id");
exit();