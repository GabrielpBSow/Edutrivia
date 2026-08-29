<?php
session_start();
include("../conexion.php");
require_once("../csrf.php");

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['administrador', 'profesor'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../Mazos/index.php");
    exit();
}

$mazo_id = (int)($_POST['mazo_id'] ?? 0);

if (!csrf_validar($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = "Sesión de formulario inválida, intenta de nuevo";
    header("Location: crear.php?mazo_id=$mazo_id");
    exit();
}

/* Verificar dueño del mazo otra vez, por si acaso manipulan el mazo_id a mano */
$stmt = $conexion->prepare("SELECT profesor_id FROM mazos WHERE id = ?");
$stmt->bind_param("i", $mazo_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header("Location: ../Mazos/index.php");
    exit();
}

$mazo = $res->fetch_assoc();

if ($_SESSION['rol'] === 'profesor' && $mazo['profesor_id'] !== $_SESSION['usuario_id']) {
    $_SESSION['error'] = "No tienes permiso sobre ese mazo";
    header("Location: ../Mazos/index.php");
    exit();
}

$categoria = trim($_POST['categoria'] ?? '');
$pregunta = trim($_POST['pregunta'] ?? '');
$respuesta = trim($_POST['respuesta'] ?? '');
$puntaje = (int)($_POST['puntaje'] ?? 0);

if ($categoria === '' || $pregunta === '' || $respuesta === '' || $puntaje <= 0) {
    $_SESSION['error'] = "Todos los campos son obligatorios";
    header("Location: crear.php?mazo_id=$mazo_id");
    exit();
}

$stmt = $conexion->prepare("INSERT INTO preguntas (mazo_id, categoria, pregunta, respuesta, puntaje) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("isssi", $mazo_id, $categoria, $pregunta, $respuesta, $puntaje);

if ($stmt->execute()) {
    $_SESSION['success'] = "Pregunta agregada correctamente";
} else {
    $_SESSION['error'] = "Error al guardar la pregunta";
}

header("Location: index.php?mazo_id=$mazo_id");
exit();