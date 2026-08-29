<?php
session_start();
include("../conexion.php");
require_once("../csrf.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'administrador') {
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

if ($id === (int)$_SESSION['usuario_id']) {
    $_SESSION['error'] = "No puedes eliminar tu propia cuenta";
    header("Location: index.php");
    exit();
}

$stmt = $conexion->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$usuario_objetivo = $res->fetch_assoc();

if ($usuario_objetivo['rol'] === 'administrador') {
    $stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'administrador' AND activo = 1 AND id != ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $otros_admins = $stmt->get_result()->fetch_assoc()['total'];

    if ($otros_admins == 0) {
        $_SESSION['error'] = "No puedes eliminar al único administrador del sistema";
        header("Location: index.php");
        exit();
    }
}

$stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Usuario eliminado correctamente";
} else {
    $_SESSION['error'] = "Error al eliminar el usuario";
}

header("Location: index.php");
exit();