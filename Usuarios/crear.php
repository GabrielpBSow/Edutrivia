<?php
session_start();
require_once("../csrf.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'administrador') {
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
    <title>Crear Usuario</title>
</head>
<body class="fondo">

    <a href="../Administrador/index.php">
        <img class="img_before" src="../img/before_writh.png" alt="Volver" style="max-width:60px;">
    </a>

    <h1 class="pagina-titulo">➕ Nuevo Usuario</h1>

    <?php if ($error) { ?>
        <p style="color:#961818; text-align:center; font-weight:bold;"><?= htmlspecialchars($error) ?></p>
    <?php } ?>

    <form method="POST" action="guardar.php">
        <?= csrf_field() ?>

        <label>Nombre completo</label>
        <input class="name_input" type="text" name="nombre" placeholder="Nombre completo" required>

        <label>Correo</label>
        <input class="name_input" type="email" name="correo" placeholder="correo@ejemplo.com" required>

        <label>Contraseña</label>
        <input class="name_input" type="password" name="contrasena" placeholder="Mínimo 8 caracteres" minlength="8" required>

        <label>Rol</label>
        <select name="rol" required>
            <option value="estudiante">Estudiante</option>
            <option value="profesor">Profesor</option>
            <option value="administrador">Administrador</option>
        </select>

        <br><br>
        <button class="btn" type="submit">Guardar Usuario</button>
    </form>

</body>
</html>