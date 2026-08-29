<?php
session_start();
include("../conexion.php");

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: encuesta.php");
    exit();
}

$opcion = (int)($_POST['opcion'] ?? 0);

// Solo aceptamos las 4 opciones válidas (1=Excelente, 2=Bueno, 3=Regular, 4=Malo)
if ($opcion < 1 || $opcion > 4) {
    header("Location: encuesta.php");
    exit();
}

$stmt = $conexion->prepare("INSERT INTO encuestas (opcion, usuario_id) VALUES (?, ?)");
$stmt->bind_param("ii", $opcion, $_SESSION['usuario_id']);
$stmt->execute();

header("Location: gracias.php");
exit();