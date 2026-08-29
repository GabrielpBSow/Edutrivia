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
    header("Location: index.php");
    exit();
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit();
}

/* El profesor solo puede borrar SUS PROPIOS mazos; el admin cualquiera */
if ($_SESSION['rol'] === 'profesor') {
    $check = $conexion->prepare("SELECT id FROM mazos WHERE id = ? AND profesor_id = ?");
    $check->bind_param("ii", $id, $_SESSION['usuario_id']);
    $check->execute();

    if ($check->get_result()->num_rows === 0) {
        $_SESSION['error'] = "No tienes permiso para eliminar ese mazo";
        header("Location: index.php");
        exit();
    }
}

$stmt = $conexion->prepare("DELETE FROM mazos WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Mazo eliminado correctamente";
} else {
    $_SESSION['error'] = "Error al eliminar el mazo";
}

header("Location: index.php");
exit();