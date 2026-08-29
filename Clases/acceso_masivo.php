<?php
session_start();
include("../conexion.php");

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['administrador', 'profesor'])) {
    header("Location: ../login.php");
    exit();
}

$clase_id = (int)($_GET['clase_id'] ?? 0);
$juego = $_GET['juego'] ?? '';
$accion = $_GET['accion'] ?? '';
$token = $_GET['csrf_token'] ?? '';

$juegos_validos = ['jeopardy', 'ahorcado', 'carrera', 'memorama'];
$acciones_validas = ['habilitar', 'deshabilitar'];

if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    $_SESSION['error'] = "Token inválido, intenta de nuevo.";
    header("Location: ver.php?id={$clase_id}");
    exit();
}

if ($clase_id <= 0 || !in_array($juego, $juegos_validos) || !in_array($accion, $acciones_validas)) {
    $_SESSION['error'] = "Solicitud inválida.";
    header("Location: ver.php?id={$clase_id}");
    exit();
}

$stmt = $conexion->prepare("SELECT profesor_id FROM clases WHERE id = ?");
$stmt->bind_param("i", $clase_id);
$stmt->execute();
$clase = $stmt->get_result()->fetch_assoc();

if (!$clase) {
    header("Location: index.php");
    exit();
}

if ($_SESSION['rol'] === 'profesor' && $clase['profesor_id'] !== $_SESSION['usuario_id']) {
    $_SESSION['error'] = "No tienes permiso para esta clase.";
    header("Location: index.php");
    exit();
}

if ($accion === 'habilitar') {
    $stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM clase_mazos WHERE clase_id = ?");
    $stmt->bind_param("i", $clase_id);
    $stmt->execute();
    $total_mazos = $stmt->get_result()->fetch_assoc()['total'];

    if ($total_mazos == 0) {
        $_SESSION['error'] = "Esta clase todavía no tiene ningún mazo activado. Actívalo primero antes de dar acceso a los juegos.";
        header("Location: ver.php?id={$clase_id}");
        exit();
    }

    /* Crea el registro de acceso para cada alumno inscrito que todavía no
       tenga uno, y renueva (habilita de nuevo) el de los que ya lo tenían */
    $stmt = $conexion->prepare("
        INSERT INTO accesos_juego (clase_id, estudiante_id, juego, habilitado, otorgado_en, usado_en)
        SELECT ?, i.estudiante_id, ?, 1, NOW(), NULL
        FROM inscripciones i
        WHERE i.clase_id = ?
        ON DUPLICATE KEY UPDATE habilitado = 1, otorgado_en = NOW(), usado_en = NULL
    ");
    $stmt->bind_param("isi", $clase_id, $juego, $clase_id);
    $stmt->execute();
    $_SESSION['success'] = "Acceso habilitado para todos los alumnos.";
} else {
    $stmt = $conexion->prepare("UPDATE accesos_juego SET habilitado = 0 WHERE clase_id = ? AND juego = ?");
    $stmt->bind_param("is", $clase_id, $juego);
    $stmt->execute();
    $_SESSION['success'] = "Acceso quitado a todos los alumnos.";
}

header("Location: ver.php?id={$clase_id}");
exit();