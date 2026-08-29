<?php
session_start();
require_once("../csrf.php");
require_once("../conexion.php"); // nos da $host, $usuario_db, $password_db, $base_datos, $es_local

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../login.php");
    exit();
}

$token = $_GET['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    $_SESSION['error'] = "Token inválido, intenta de nuevo.";
    header("Location: index.php");
    exit();
}

$carpeta = __DIR__ . '/archivos';
if (!is_dir($carpeta)) {
    mkdir($carpeta, 0755, true);
}

/*
 * ¡IMPORTANTE! Ajusta esta ruta si tu instalación es distinta.
 * - XAMPP en Windows suele ser:      C:\xampp\mysql\bin\mysqldump.exe
 * - XAMPP en Mac (con symlink):      /Applications/XAMPP/xamppfiles/bin/mysqldump
 * - XAMPP en Linux:                  /opt/lampp/bin/mysqldump
 * - En producción, si mysqldump ya está en el PATH del servidor, deja 'mysqldump'.
 *
 * Este es justo el bug #1 que arrastraba el proyecto original (apuntaba a WAMP
 * en vez de XAMPP) — revisa esta ruta primero si el respaldo te falla.
 */
if ($es_local) {
    $mysqldump_bin = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
} else {
    $mysqldump_bin = 'mysqldump';
}

$archivo  = 'respaldo_' . date('Y-m-d_His') . '.sql';
$ruta_sql = $carpeta . '/' . $archivo;
$ruta_err = $ruta_sql . '.err';

$partes_comando = [
    escapeshellarg($mysqldump_bin),
    '--host=' . escapeshellarg($host),
    '--user=' . escapeshellarg($usuario_db),
];

if ($password_db !== '') {
    $partes_comando[] = '--password=' . escapeshellarg($password_db);
}

$partes_comando[] = escapeshellarg($base_datos);

$comando = implode(' ', $partes_comando)
    . ' > ' . escapeshellarg($ruta_sql)
    . ' 2> ' . escapeshellarg($ruta_err);

exec($comando, $salida, $codigo_salida);

/*
 * Verificación real de éxito (bug #2 del proyecto original: comparaba mal
 * el resultado). Aquí exigimos LAS TRES cosas a la vez:
 *   1) el comando terminó con código 0 (sin errores)
 *   2) el archivo .sql realmente se creó
 *   3) el archivo no está vacío (un mysqldump fallido a veces deja un archivo de 0 bytes)
 */
$exito = ($codigo_salida === 0) && file_exists($ruta_sql) && filesize($ruta_sql) > 0;

if ($exito) {
    @unlink($ruta_err);
    $_SESSION['success'] = "Respaldo generado correctamente: {$archivo}";
} else {
    $detalle_error = @file_get_contents($ruta_err);
    @unlink($ruta_sql); // no dejar un archivo vacío/corrupto en la carpeta
    @unlink($ruta_err);
    $_SESSION['error'] = "No se pudo generar el respaldo."
        . ($detalle_error ? " Detalle: " . htmlspecialchars(trim($detalle_error)) : " Revisa la ruta de mysqldump en generar.php.");
}

header("Location: index.php");
exit();