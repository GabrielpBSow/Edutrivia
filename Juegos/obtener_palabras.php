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

/* Categorías únicas de todos los mazos activos en esta clase, junto con
   el nombre del mazo al que pertenecen (se usa como "Tema" / pista,
   sin regalar la respuesta). Se reciclan como las "palabras" del Ahorcado. */
$stmt = $conexion->prepare("
    SELECT DISTINCT p.categoria, m.nombre AS mazo_nombre
    FROM preguntas p
    INNER JOIN clase_mazos cm ON cm.mazo_id = p.mazo_id
    INNER JOIN mazos m ON m.id = p.mazo_id
    WHERE cm.clase_id = ?
");
$stmt->bind_param("i", $clase_id);
$stmt->execute();
$result = $stmt->get_result();

$categorias = [];
$vistas = [];
while ($row = $result->fetch_assoc()) {
    // Evita duplicados si la misma categoría aparece en más de un mazo activo
    if (!in_array($row['categoria'], $vistas)) {
        $vistas[] = $row['categoria'];
        $categorias[] = [
            'categoria' => $row['categoria'],
            'mazo' => $row['mazo_nombre']
        ];
    }
}

echo json_encode(['categorias' => $categorias]);