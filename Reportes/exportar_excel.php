<?php
session_start();
require_once("../conexion.php");
require_once(__DIR__ . '/lib_reportes.php');

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['administrador', 'profesor'])) {
    header("Location: ../login.php");
    exit();
}

$tipo = $_GET['tipo'] ?? '';
$id = (int)($_GET['id'] ?? 0);

$datos = obtener_datos_reporte($conexion, $tipo, $id);

if (isset($datos['error'])) {
    $_SESSION['error'] = $datos['error'];
    header("Location: index.php");
    exit();
}

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="reporte_' . $tipo . '_' . date('Y-m-d') . '.xls"');

echo "\xEF\xBB\xBF"; // BOM para que Excel respete los acentos en UTF-8

echo '<table border="1">';
echo '<tr>';
foreach ($datos['columnas'] as $col) {
    echo '<th style="background:#5B2C8C; color:#fff;">' . htmlspecialchars($col) . '</th>';
}
echo '</tr>';

if (count($datos['filas']) === 0) {
    echo '<tr><td colspan="' . count($datos['columnas']) . '">No hay datos para este reporte.</td></tr>';
} else {
    foreach ($datos['filas'] as $fila) {
        echo '<tr>';
        foreach ($fila as $valor) {
            echo '<td>' . htmlspecialchars((string)$valor) . '</td>';
        }
        echo '</tr>';
    }
}

echo '</table>';
exit();