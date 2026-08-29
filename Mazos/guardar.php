<?php
session_start();
include("../conexion.php");
require_once("../csrf.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'profesor') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: crear.php");
    exit();
}

if (!csrf_validar($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = "Sesión de formulario inválida, intenta de nuevo";
    header("Location: crear.php");
    exit();
}

$nombre = trim($_POST['nombre'] ?? '');

if ($nombre === '') {
    $_SESSION['error'] = "El nombre del mazo es obligatorio";
    header("Location: crear.php");
    exit();
}

$stmt = $conexion->prepare("INSERT INTO mazos (nombre, profesor_id) VALUES (?, ?)");
$stmt->bind_param("si", $nombre, $_SESSION['usuario_id']);

if ($stmt->execute()) {
    $nuevo_id = $conexion->insert_id;
    $_SESSION['success'] = "Mazo creado correctamente. Ahora agrégale preguntas.";
    header("Location: ../Preguntas/index.php?mazo_id=$nuevo_id");
} else {
    $_SESSION['error'] = "Error al crear el mazo";
    header("Location: crear.php");
}
exit();