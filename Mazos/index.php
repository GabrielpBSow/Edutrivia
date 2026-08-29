<?php
session_start();
include("../conexion.php");
require_once("../csrf.php");

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['administrador', 'profesor'])) {
    header("Location: ../login.php");
    exit();
}

$rol = $_SESSION['rol'];
$uid = $_SESSION['usuario_id'];
$es_admin = $rol === 'administrador';
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
$token = csrf_token();

if ($es_admin) {
    $mazos = $conexion->query("
        SELECT m.id, m.nombre, m.creado_en, u.nombre AS profesor_nombre,
               (SELECT COUNT(*) FROM preguntas p WHERE p.mazo_id = m.id) AS total_preguntas
        FROM mazos m
        INNER JOIN usuarios u ON u.id = m.profesor_id
        ORDER BY m.creado_en DESC
    ");
} else {
    $stmt = $conexion->prepare("
        SELECT m.id, m.nombre, m.creado_en,
               (SELECT COUNT(*) FROM preguntas p WHERE p.mazo_id = m.id) AS total_preguntas
        FROM mazos m
        WHERE m.profesor_id = ?
        ORDER BY m.creado_en DESC
    ");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $mazos = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" type="img/png" href="../img/LogoEdutrivia (1).png">
    <link rel="stylesheet" href="../css/app.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Mazos de Preguntas</title>
</head>
<body class="fondo">

<div class="contenedor-gestion">

    <a href="<?= $es_admin ? '../Administrador/index.php' : '../Profesor/index.php' ?>">
        <img class="img_before" src="../img/before_writh.png" alt="Volver" style="max-width:42px;">
    </a>

    <h1 class="pagina-titulo"><i class='bx bx-collection'></i> <?= $es_admin ? 'Todos los Mazos' : 'Mis Mazos' ?></h1>

    <?php if ($error) { ?>
        <p style="color:#ffb3b3; text-align:center; font-weight:bold;"><?= htmlspecialchars($error) ?></p>
    <?php } ?>
    <?php if ($success) { ?>
        <p style="color:#b3ffb3; text-align:center; font-weight:bold;"><?= htmlspecialchars($success) ?></p>
    <?php } ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Mazo</th>
                    <?php if ($es_admin) { ?><th>Profesor</th><?php } ?>
                    <th>Preguntas</th>
                    <th>Creado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($mazos->num_rows === 0) { ?>
                    <tr><td colspan="5">Todavía no hay mazos creados.</td></tr>
                <?php } ?>

                <?php while ($m = $mazos->fetch_assoc()) { ?>
                    <tr>
                        <td><?= htmlspecialchars($m['nombre']) ?></td>
                        <?php if ($es_admin) { ?><td><?= htmlspecialchars($m['profesor_nombre']) ?></td><?php } ?>
                        <td><?= (int)$m['total_preguntas'] ?></td>
                        <td><?= htmlspecialchars($m['creado_en']) ?></td>
                        <td>
                            <a href="../Preguntas/index.php?mazo_id=<?= (int)$m['id'] ?>">📋</a>
                            <a href="#" onclick="confirmarAccion('eliminar.php?id=<?= (int)$m['id'] ?>&csrf_token=<?= urlencode($token) ?>', '¿Seguro? Esto borra el mazo Y todas sus preguntas.'); return false;">🗑️</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <?php if (!$es_admin) { ?>
    <div class="btn-wrap">
        <a href="crear.php" class="btn">➕ Nuevo Mazo</a>
    </div>
    <?php } ?>

</div>

<script src="../js/confirmar.js"></script>

</body>
</html>