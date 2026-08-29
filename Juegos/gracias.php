<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../css/juegos.css?v=2">
    <link rel="stylesheet" href="../css/app.css?v=2">
    <link rel="icon" type="img/png" href="../img/LogoEdutrivia (1).png">
    <title>Gracias</title>
</head>
<body class="pantalla-encuesta">

    <div class="encuesta-card">
        <div class="encuesta-icono" style="color:#2ecc71;"><i class='bx bx-badge-check'></i></div>
        <h2>¡Gracias por tu voto!</h2>
        <p class="encuesta-sub">Sigue aprendiendo mientras juegas 🎉</p>
        <a href="../Clases/index.php" class="btn">Volver a mi panel</a>
    </div>

</body>
</html>