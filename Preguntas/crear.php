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
    header("Location: ../Mazos/index.php");
    exit();
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" type="img/png" href="../img/LogoEdutrivia (1).png">
    <link rel="stylesheet" href="../css/app.css">
    <title>Nueva Pregunta</title>
</head>
<body class="fondo">

<div class="contenedor-gestion">

    <a href="index.php?mazo_id=<?= $mazo_id ?>">
        <img class="img_before" src="../img/before_writh.png" alt="Volver" style="max-width:42px;">
    </a>

    <h1 class="pagina-titulo"><i class='bx bx-plus-circle'></i> Nueva Pregunta</h1>
    <p style="color:#dcd0ea; text-align:center; margin-top:-1.5rem; margin-bottom:1.5rem;">
        en "<?= htmlspecialchars($mazo['nombre']) ?>"
    </p>

    <?php if ($error) { ?>
        <p style="color:#ffb3b3; text-align:center; font-weight:bold;"><?= htmlspecialchars($error) ?></p>
    <?php } ?>

    <form method="POST" action="guardar.php">
        <?= csrf_field() ?>
        <input type="hidden" name="mazo_id" value="<?= $mazo_id ?>">

        <label>Categoría</label>
        <input class="name_input" type="text" name="categoria" placeholder="Ej. Historia" required>

        <label>Pregunta</label>
        <input class="name_input" type="text" name="pregunta" placeholder="Escribe la pregunta" required>

        <label>Respuesta</label>
        <input class="name_input" type="text" name="respuesta" placeholder="Escribe la respuesta correcta" required>

        <label>Puntaje</label>
        <select name="puntaje" required>
            <option value="100">100</option>
            <option value="200">200</option>
            <option value="300">300</option>
            <option value="400">400</option>
            <option value="500">500</option>
        </select>

        <button class="btn" type="submit">Guardar Pregunta</button>
    </form>

</div>

</body>
</html>