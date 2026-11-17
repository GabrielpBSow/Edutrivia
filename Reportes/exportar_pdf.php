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
        // Franja superior decorativa institucional
        $this->SetFillColor(30, 27, 75); // Morado oscuro / elegante
        $this->Rect(0, 0, 297, 8, 'F'); // 297mm es el ancho aproximado en horizontal (A4 Landscape)

        $this->Ln(12);
        // Logo o marca de agua simulada de texto
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(99, 102, 241); // Índigo moderno
        $this->Cell(0, 6, utf8_decode('EDUTRIVIA — REPORTE OFICIAL ACADÉMICO'), 0, 1, 'L');

        // Título principal del reporte
        $this->SetFont('Arial', 'B', 15);
        $this->SetTextColor(15, 23, 42); // Texto principal oscuro
        $this->Cell(0, 10, utf8_decode($this->tituloReporte), 0, 1, 'L');

        // Línea divisoria elegante
        $this->SetDrawColor(226, 232, 240);
        $this->SetLineWidth(0.6);
        $this->Line(10, $this->GetY(), 287, $this->GetY());
        $this->Ln(6);
    }

    function Footer() {
        $this->SetY(-15);
        // Línea superior del footer
        $this->SetDrawColor(226, 232, 240);
        $this->SetLineWidth(0.4);
        $this->Line(10, $this->GetY(), 287, $this->GetY());

        $this->SetY(-12);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 116, 139);
        $this->Cell(130, 10, utf8_decode('Generado el: ' . date('d/m/Y H:i:s')), 0, 0, 'L');
        $this->Cell(147, 10, utf8_decode('Página ' . $this->PageNo() . ' de {nb}'), 0, 0, 'R');
    }
}

$pdf = new PDFReporte();
$pdf->tituloReporte = utf8_decode($datos['titulo']);
$pdf->AliasNbPages();
$pdf->AddPage('L'); // Horizontal para aprovechar mejor el espacio
$pdf->SetFont('Arial', '', 10);

$columnas = $datos['columnas'];
$anchoDisponible = $pdf->GetPageWidth() - 20; // Márgenes de 10mm a cada lado
$anchoColumna = $anchoDisponible / max(count($columnas), 1);

// Cabecera de la Tabla
$pdf->SetFillColor(79, 70, 229); // Color índigo principal moderno
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 9);
foreach ($columnas as $col) {
    $pdf->Cell($anchoColumna, 9, utf8_decode($col), 1, 0, 'C', true);
}
$pdf->Ln();

// Filas de la Tabla con efecto cebra (filas alternas con fondo gris muy suave)
$pdf->SetTextColor(15, 23, 42);
$pdf->SetFont('Arial', '', 9);
$fill = false;

if (count($datos['filas']) === 0) {
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Cell($anchoDisponible, 10, utf8_decode('No hay datos disponibles para este reporte.'), 1, 1, 'C', true);
} else {
    foreach ($datos['filas'] as $fila) {
        // Color de fondo alterno para las filas
        $pdf->SetFillColor(248, 250, 252); 
        foreach ($fila as $valor) {
            $pdf->Cell($anchoColumna, 8, utf8_decode((string)$valor), 1, 0, 'C', $fill);
        }
        $pdf->Ln();
        $fill = !$fill; // Alternar color
    }
}

$nombreArchivo = 'reporte_' . $tipo . '_' . date('Y-m-d') . '.pdf';
$pdf->Output('D', $nombreArchivo);