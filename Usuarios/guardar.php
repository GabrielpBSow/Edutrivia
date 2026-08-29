<?php
session_start();
include("../conexion.php");
require_once("../csrf.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'administrador') {
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
$correo = trim($_POST['correo'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';
$rol = $_POST['rol'] ?? '';

$roles_validos = ['administrador', 'profesor', 'estudiante'];

if ($nombre === '' || $correo === '' || $contrasena === '' || !in_array($rol, $roles_validos)) {
    $_SESSION['error'] = "Todos los campos son obligatorios";
    header("Location: crear.php");
    exit();
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "El correo no es válido";
    header("Location: crear.php");
    exit();
}

if (strlen($contrasena) < 8) {
    $_SESSION['error'] = "La contraseña debe tener al menos 8 caracteres";
    header("Location: crear.php");
    exit();
}

$check = $conexion->prepare("SELECT id FROM usuarios WHERE correo = ?");
$check->bind_param("s", $correo);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    $_SESSION['error'] = "Ya existe un usuario con ese correo";
    header("Location: crear.php");
    exit();
}

$hash = password_hash($contrasena, PASSWORD_DEFAULT);

$stmt = $conexion->prepare("INSERT INTO usuarios (nombre, correo, contrasena, rol) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $nombre, $correo, $hash, $rol);

if ($stmt->execute()) {
    $_SESSION['success'] = "Usuario creado correctamente";
    header("Location: index.php");
} else {
    $_SESSION['error'] = "Error al crear el usuario";
    header("Location: crear.php");
}
exit();