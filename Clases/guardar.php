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
    $_SESSION['error'] = "El nombre de la clase es obligatorio";
    header("Location: crear.php");
    exit();
}

/* Generar un código único de 7 caracteres (letras y números, sin
   caracteres confusos como 0/O o 1/I) */
function generarCodigoUnico($conexion) {
    $caracteres = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $codigo = '';
        for ($i = 0; $i < 7; $i++) {
            $codigo .= $caracteres[random_int(0, strlen($caracteres) - 1)];
        }
        $stmt = $conexion->prepare("SELECT id FROM clases WHERE codigo = ?");
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $existe = $stmt->get_result()->num_rows > 0;
    } while ($existe);
    return $codigo;
}

$codigo = generarCodigoUnico($conexion);

$stmt = $conexion->prepare("INSERT INTO clases (nombre, codigo, profesor_id) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $nombre, $codigo, $_SESSION['usuario_id']);

if ($stmt->execute()) {
    $_SESSION['success'] = "Clase creada. Comparte este código con tus alumnos: $codigo";
    header("Location: index.php");
} else {
    $_SESSION['error'] = "Error al crear la clase";
    header("Location: crear.php");
}
exit();