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

$nombreArchivo = 'reporte_' . $tipo . '_' . date('Y-m-d') . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF"; // BOM para que Excel respete los acentos y la ñ en UTF-8

$totalColumnas = max(count($datos['columnas']), 1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        .header-institucional {
            background-color: #1e1b4b;
            color: #c7d2fe;
            font-size: 11pt;
            font-weight: bold;
            padding: 8px 12px;
            text-align: left;
            border: 1px solid #1e1b4b;
        }
        .titulo-reporte {
            background-color: #f1f5f9;
            color: #0f172a;
            font-size: 14pt;
            font-weight: bold;
            padding: 12px;
            text-align: left;
            border: 1px solid #cbd5e1;
        }
        .subtitulo-fecha {
            background-color: #ffffff;
            color: #64748b;
            font-size: 9pt;
            font-style: italic;
            padding: 6px 12px;
            text-align: left;
            border: 1px solid #cbd5e1;
        }
        th {
            background-color: #4f46e5;
            color: #ffffff;
            font-weight: bold;
            font-size: 10pt;
            padding: 10px 14px;
            text-align: center;
            border: 1px solid #4338ca;
        }
        td {
            font-size: 10pt;
            color: #1e293b;
            padding: 8px 14px;
            text-align: center;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }
        .fila-par {
            background-color: #ffffff;
        }
        .fila-impar {
            background-color: #f8fafc;
        }
        .sin-datos {
            color: #64748b;
            font-style: italic;
            padding: 16px;
        }
    </style>
</head>
<body>

<table>
    <!-- Encabezado superior institucional -->
    <tr>
        <td colspan="<?= $totalColumnas ?>" class="header-institucional">
            EDUTRIVIA — REPORTE OFICIAL ACADÉMICO
        </td>
    </tr>
    <tr>
        <td colspan="<?= $totalColumnas ?>" class="titulo-reporte">
            <?= htmlspecialchars($datos['titulo']) ?>
        </td>
    </tr>
    <tr>
        <td colspan="<?= $totalColumnas ?>" class="subtitulo-fecha">
            Generado el: <?= date('d/m/Y H:i:s') ?>
        </td>
    </tr>
    <tr>
        <td colspan="<?= $totalColumnas ?>" style="border:none; height:10px;"></td>
    </tr>

    <!-- Cabecera de columnas -->
    <tr>
        <?php foreach ($datos['columnas'] as $col) { ?>
            <th><?= htmlspecialchars($col) ?></th>
        <?php } ?>
    </tr>

    <!-- Filas de datos -->
    <?php if (count($datos['filas']) === 0) { ?>
        <tr>
            <td colspan="<?= $totalColumnas ?>" class="sin-datos">
                No hay datos disponibles para este reporte.
            </td>
        </tr>
    <?php } else { ?>
        <?php foreach ($datos['filas'] as $index => $fila) { 
            $claseFila = ($index % 2 === 0) ? 'fila-par' : 'fila-impar';
        ?>
            <tr class="<?= $claseFila ?>">
                <?php foreach ($fila as $valor) { ?>
                    <td><?= htmlspecialchars((string)$valor) ?></td>
                <?php } ?>
            </tr>
        <?php } ?>
    <?php } ?>
</table>

</body>
</html>
<?php
exit();