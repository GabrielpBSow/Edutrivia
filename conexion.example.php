<?php
/*
 * Plantilla de conexión — copia este archivo como conexion.php y rellena
 * tus credenciales reales ahí. conexion.php NO se sube a Git (está en
 * .gitignore); este archivo (conexion.example.php) sí se sube, para que
 * cualquiera que clone el repo sepa qué variables tiene que llenar.
 */

$es_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);

if ($es_local) {
    $host        = 'localhost';
    $usuario_db  = 'root';
    $password_db = '';
    $base_datos  = 'edutrivia';
} else {
    // Rellenar cuando exista el hosting real
    $host        = 'TU_SERVIDOR_MYSQL_AQUI';
    $usuario_db  = 'TU_USUARIO_MYSQL_AQUI';
    $password_db = 'TU_PASSWORD_AQUI';
    $base_datos  = 'TU_BASE_DE_DATOS_AQUI';
}

$conexion = new mysqli($host, $usuario_db, $password_db, $base_datos);

if ($conexion->connect_error) {
    error_log("Error de conexión MySQL: " . $conexion->connect_error);
    die("Error de conexión. Intenta más tarde.");
}

$conexion->set_charset("utf8mb4");
