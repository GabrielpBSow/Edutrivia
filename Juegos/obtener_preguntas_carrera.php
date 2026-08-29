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

/* Traer todas las preguntas de los mazos activos en esta clase, en orden aleatorio */
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
    echo json_encode(['error' => 'Esta clase necesita al menos 2 preguntas activas en sus mazos para jugar Carrera.']);
    exit();
}

/* Hasta 10 preguntas para la carrera */
$seleccionadas = array_slice($pool, 0, 10);

$preguntas = [];
foreach ($seleccionadas as $p) {
    /* La opción incorrecta es la respuesta de otra pregunta cualquiera del pool,
       siempre que el texto sea distinto a la respuesta correcta */
    $candidatos = array_values(array_filter(
        $pool,
        fn($x) => mb_strtolower(trim($x['respuesta'])) !== mb_strtolower(trim($p['respuesta']))
    ));

    if (count($candidatos) === 0) {
        continue;
    }

    $incorrecta = $candidatos[array_rand($candidatos)]['respuesta'];
    $carrilCorrecto = random_int(0, 1); // 0 = izquierda, 1 = derecha

    $preguntas[] = [
        'pregunta' => $p['pregunta'],
        'opcion_izquierda' => $carrilCorrecto === 0 ? $p['respuesta'] : $incorrecta,
        'opcion_derecha' => $carrilCorrecto === 1 ? $p['respuesta'] : $incorrecta,
        'carril_correcto' => $carrilCorrecto,
        'puntaje' => (int)$p['puntaje'],
    ];
}

if (count($preguntas) === 0) {
    echo json_encode(['error' => 'No se pudieron generar preguntas para la carrera con las preguntas disponibles.']);
    exit();
}

echo json_encode($preguntas);