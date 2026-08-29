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

/* Si es estudiante, confirmar que esté inscrito en esa clase */
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

/* Traer los mazos activos en esta clase, con cuántas preguntas usar de cada uno */
$stmt = $conexion->prepare("SELECT mazo_id, cantidad_preguntas FROM clase_mazos WHERE clase_id = ?");
$stmt->bind_param("i", $clase_id);
$stmt->execute();
$mazos_activos = $stmt->get_result();

$pool = [];

while ($cm = $mazos_activos->fetch_assoc()) {
    /* De cada mazo, sacar N preguntas al azar (la cantidad que el profesor configuró) */
    $stmt2 = $conexion->prepare("SELECT categoria, pregunta, respuesta, puntaje FROM preguntas WHERE mazo_id = ? ORDER BY RAND() LIMIT ?");
    $stmt2->bind_param("ii", $cm['mazo_id'], $cm['cantidad_preguntas']);
    $stmt2->execute();
    $res2 = $stmt2->get_result();

    while ($p = $res2->fetch_assoc()) {
        $pool[] = $p;
    }
}

if (count($pool) === 0) {
    echo json_encode([]);
    exit();
}

/* Si por casualidad hay dos preguntas con la MISMA categoría y el
   MISMO puntaje (ej. dos preguntas de "Historia" en 200), el tablero
   solo puede mostrar una casilla ahí — elegimos una al azar entre
   las que compiten por ese mismo lugar */
$agrupado = [];
foreach ($pool as $p) {
    $clave = $p['categoria'] . '|' . $p['puntaje'];
    if (!isset($agrupado[$clave])) {
        $agrupado[$clave] = [];
    }
    $agrupado[$clave][] = $p;
}

$preguntas_finales = [];
foreach ($agrupado as $grupo) {
    $preguntas_finales[] = $grupo[array_rand($grupo)];
}

/* Formato que espera el JavaScript del tablero (mismos nombres que
   el proyecto original, con mayúscula inicial) */
$respuesta = [];
$i = 0;
foreach ($preguntas_finales as $p) {
    $respuesta[] = [
        'id' => $i,
        'Categoria' => $p['categoria'],
        'Pregunta' => $p['pregunta'],
        'Respuesta' => $p['respuesta'],
        'Puntaje' => (int)$p['puntaje'],
    ];
    $i++;
}

echo json_encode($respuesta);