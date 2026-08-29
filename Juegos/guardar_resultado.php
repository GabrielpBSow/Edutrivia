<?php
session_start();
include("../conexion.php");

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$clase_id = (int)($data['clase_id'] ?? 0);
$juego = $data['juego'] ?? '';
$puntaje = (int)($data['puntaje'] ?? 0);
$tiempo = isset($data['tiempo']) && $data['tiempo'] !== null ? (int)$data['tiempo'] : null;

$juegos_validos = ['jeopardy', 'ahorcado', 'carrera', 'memorama'];

if ($clase_id <= 0 || !in_array($juego, $juegos_validos)) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit();
}

$stmt = $conexion->prepare("INSERT INTO resultados (estudiante_id, clase_id, juego, puntaje, tiempo_segundos) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iisii", $_SESSION['usuario_id'], $clase_id, $juego, $puntaje, $tiempo);
$stmt->execute();

echo json_encode(['ok' => true]);