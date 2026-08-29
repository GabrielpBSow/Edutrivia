<?php
session_start();
include("../conexion.php");
require_once("../csrf.php");

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['administrador', 'profesor'])) {
    header("Location: ../login.php");
    exit();
}

if (!csrf_validar($_GET['csrf_token'] ?? '')) {
    $_SESSION['error'] = "Enlace inválido o expirado, intenta de nuevo";
    header("Location: ../Mazos/index.php");
    exit();
}

$id = (int)($_GET['id'] ?? 0);
$mazo_id = (int)($_GET['mazo_id'] ?? 0);

if ($id <= 0) {
    header("Location: ../Mazos/index.php");
    exit();
}

$stmt = $conexion->prepare("
    SELECT m.profesor_id
    FROM preguntas p
    INNER JOIN mazos m ON m.id = p.mazo_id
    WHERE p.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header("Location: ../Mazos/index.php");
    exit();
}

$dueno = $res->fetch_assoc();

if ($_SESSION['rol'] === 'profesor' && $dueno['profesor_id'] !== $_SESSION['usuario_id']) {
    header("Location: ../Mazos/index.php");
    exit();
}

$stmt = $conexion->prepare("DELETE FROM preguntas WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Pregunta eliminada correctamente";
} else {
    $_SESSION['error'] = "Error al eliminar la pregunta";
}

header("Location: index.php?mazo_id=$mazo_id");
exit();