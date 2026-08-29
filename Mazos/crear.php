<?php
session_start();
require_once("../csrf.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'profesor') {
    header("Location: ../login.php");
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
    <title>Nuevo Mazo</title>
</head>
<body class="fondo">

<div class="contenedor-gestion">

    <a href="index.php">
        <img class="img_before" src="../img/before_writh.png" alt="Volver" style="max-width:42px;">
    </a>

    <h1 class="pagina-titulo"><i class='bx bx-plus-circle'></i> Nuevo Mazo</h1>

    <?php if ($error) { ?>
        <p style="color:#ffb3b3; text-align:center; font-weight:bold;"><?= htmlspecialchars($error) ?></p>
    <?php } ?>

    <form method="POST" action="guardar.php">
        <?= csrf_field() ?>

        <label>Nombre del mazo</label>
        <input class="name_input" type="text" name="nombre" placeholder="Ej. Álgebra básica" required>

        <button class="btn" type="submit">Crear Mazo</button>
    </form>

</div>

</body>
</html>