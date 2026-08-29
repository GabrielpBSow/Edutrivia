<?php
session_start();
include("../conexion.php");
require_once("../csrf.php");

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['administrador', 'profesor'])) {
    header("Location: ../login.php");
    exit();
}

$mazo_id = (int)($_GET['mazo_id'] ?? 0);

if ($mazo_id <= 0) {
    header("Location: ../Mazos/index.php");
    exit();
}

/* Verificar que el mazo exista y (si eres profesor) que sea tuyo */
$stmt = $conexion->prepare("SELECT id, nombre, profesor_id FROM mazos WHERE id = ?");
$stmt->bind_param("i", $mazo_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header("Location: ../Mazos/index.php");
    exit();
}

$mazo = $res->fetch_assoc();

if ($_SESSION['rol'] === 'profesor' && $mazo['profesor_id'] !== $_SESSION['usuario_id']) {
    $_SESSION['error'] = "No tienes permiso para ver ese mazo";
    header("Location: ../Mazos/index.php");
    exit();
}

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
$token = csrf_token();

$stmt = $conexion->prepare("SELECT * FROM preguntas WHERE mazo_id = ? ORDER BY categoria ASC, id DESC");
$stmt->bind_param("i", $mazo_id);
$stmt->execute();
$preguntas = $stmt->get_result();
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
    <title>Preguntas de <?= htmlspecialchars($mazo['nombre']) ?></title>
</head>
<body class="fondo">

<div class="contenedor-gestion">

    <a href="../Mazos/index.php">
        <img class="img_before" src="../img/before_writh.png" alt="Volver" style="max-width:42px;">
    </a>

    <h1 class="pagina-titulo"><i class='bx bx-list-ul'></i> <?= htmlspecialchars($mazo['nombre']) ?></h1>

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
                    <th>Categoría</th>
                    <th>Pregunta</th>
                    <th>Respuesta</th>
                    <th>Puntaje</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($preguntas->num_rows === 0) { ?>
                    <tr><td colspan="5">Este mazo todavía no tiene preguntas.</td></tr>
                <?php } ?>

                <?php while ($p = $preguntas->fetch_assoc()) { ?>
                    <tr>
                        <td><?= htmlspecialchars($p['categoria']) ?></td>
                        <td><?= htmlspecialchars($p['pregunta']) ?></td>
                        <td><?= htmlspecialchars($p['respuesta']) ?></td>
                        <td><?= (int)$p['puntaje'] ?></td>
                        <td>
                            <a href="editar.php?id=<?= (int)$p['id'] ?>">✏️</a>
                            <a href="#" onclick="confirmarAccion('eliminar.php?id=<?= (int)$p['id'] ?>&mazo_id=<?= $mazo_id ?>&csrf_token=<?= urlencode($token) ?>', '¿Seguro que quieres borrar esta pregunta?'); return false;">🗑️</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="btn-wrap">
        <a href="crear.php?mazo_id=<?= $mazo_id ?>" class="btn">➕ Nueva Pregunta</a>
    </div>

</div>

<script src="../js/confirmar.js"></script>

</body>
</html>