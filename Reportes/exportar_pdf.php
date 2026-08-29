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

/*
 * Descarga FPDF gratis desde https://www.fpdf.org/ (no requiere registro)
 * y coloca el archivo fpdf.php dentro de Edutrivia/libs/fpdf/fpdf.php
 */
require_once __DIR__ . '/../libs/fpdf/fpdf.php';

class PDFReporte extends FPDF {
    public $tituloReporte = '';

    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 12, $this->tituloReporte, 0, 1, 'C');
        $this->Ln(4);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Edutrivia - Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

$pdf = new PDFReporte();
// FPDF no soporta UTF-8 de forma nativa; utf8_decode() evita que los acentos salgan raros
$pdf->tituloReporte = utf8_decode($datos['titulo']);
$pdf->AliasNbPages();
$pdf->AddPage('L'); // horizontal, para que quepan más columnas
$pdf->SetFont('Arial', '', 10);

$columnas = $datos['columnas'];
$anchoDisponible = $pdf->GetPageWidth() - 20;
$anchoColumna = $anchoDisponible / max(count($columnas), 1);

$pdf->SetFillColor(91, 44, 140); // morado de la universidad
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 10);
foreach ($columnas as $col) {
    $pdf->Cell($anchoColumna, 8, utf8_decode($col), 1, 0, 'C', true);
}
$pdf->Ln();

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 9);

if (count($datos['filas']) === 0) {
    $pdf->Cell($anchoDisponible, 8, 'No hay datos para este reporte.', 1, 1, 'C');
} else {
    foreach ($datos['filas'] as $fila) {
        foreach ($fila as $valor) {
            $pdf->Cell($anchoColumna, 7, utf8_decode((string)$valor), 1, 0, 'C');
        }
        $pdf->Ln();
    }
}

$nombreArchivo = 'reporte_' . $tipo . '_' . date('Y-m-d') . '.pdf';
$pdf->Output('D', $nombreArchivo);