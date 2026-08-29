<?php
session_start();
include("../conexion.php");

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$clase_id = (int)($_GET['clase_id'] ?? 0);

if ($clase_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta la clase']);
    exit();
}

if ($_SESSION['rol'] === 'estudiante') {
    $stmt = $conexion->prepare("SELECT id FROM inscripciones WHERE clase_id = ? AND estudiante_id = ?");
    $stmt->bind_param("ii", $clase_id, $_SESSION['usuario_id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['error' => 'No perteneces a esa clase']);
        exit();
    }
}

$stmt = $conexion->prepare("
    SELECT p.pregunta, p.respuesta, p.puntaje
    FROM preguntas p
    INNER JOIN clase_mazos cm ON cm.mazo_id = p.mazo_id
    WHERE cm.clase_id = ?
    ORDER BY RAND()
");
$stmt->bind_param("i", $clase_id);
$stmt->execute();
$result = $stmt->get_result();

$pool = [];
while ($row = $result->fetch_assoc()) {
    $pool[] = $row;
}

if (count($pool) < 2) {
    echo json_encode(['error' => 'Esta clase necesita al menos 2 preguntas activas en sus mazos para jugar Memorama.']);
    exit();
}

/* Sin importar cuántas preguntas tenga el mazo, el tablero se limita a un
   máximo jugable de parejas (si el profesor metió 20, usamos 10; si metió
   6, usamos esas 6) */
$MAX_PAREJAS = 10;
$seleccionadas = array_slice($pool, 0, $MAX_PAREJAS);

$cartas = [];
$id = 0;
foreach ($seleccionadas as $indice => $p) {
    $cartas[] = ['id' => $id++, 'pareja_id' => $indice, 'tipo' => 'pregunta', 'texto' => $p['pregunta'], 'puntaje' => (int)$p['puntaje']];
    $cartas[] = ['id' => $id++, 'pareja_id' => $indice, 'tipo' => 'respuesta', 'texto' => $p['respuesta'], 'puntaje' => (int)$p['puntaje']];
}

shuffle($cartas);

echo json_encode($cartas);