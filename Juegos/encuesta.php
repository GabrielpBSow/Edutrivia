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
    <title>Encuesta de Satisfacción</title>
</head>
<body class="pantalla-encuesta">

    <div class="encuesta-card">
        <div class="encuesta-icono"><i class='bx bx-message-rounded-dots'></i></div>
        <h2>¿Qué te pareció el juego?</h2>
        <p class="encuesta-sub">Tu opinión ayuda a tu profesor a mejorar las próximas partidas</p>

        <form id="encuestaForm" method="POST" action="guardar_encuesta.php">
            <div class="options">
                <label class="option">
                    <input type="radio" name="opcion" value="1" onchange="seleccionar(this)">
                    <span class="option-emoji">🤩</span>
                    <span class="option-texto">Excelente</span>
                </label>
                <label class="option">
                    <input type="radio" name="opcion" value="2" onchange="seleccionar(this)">
                    <span class="option-emoji">🙂</span>
                    <span class="option-texto">Bueno</span>
                </label>
                <label class="option">
                    <input type="radio" name="opcion" value="3" onchange="seleccionar(this)">
                    <span class="option-emoji">😐</span>
                    <span class="option-texto">Regular</span>
                </label>
                <label class="option">
                    <input type="radio" name="opcion" value="4" onchange="seleccionar(this)">
                    <span class="option-emoji">😕</span>
                    <span class="option-texto">Malo</span>
                </label>
            </div>

            <button type="submit" id="submitBtn" class="btn" disabled>Enviar mi opinión</button>
        </form>
    </div>

    <script>
        function seleccionar(input) {
            document.querySelectorAll('.option').forEach(el => el.classList.remove('option-elegida'));
            input.closest('.option').classList.add('option-elegida');
            document.getElementById('submitBtn').disabled = false;
        }
    </script>
</body>
</html>