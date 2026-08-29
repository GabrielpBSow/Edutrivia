<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'estudiante') {
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
    <link rel="icon" type="img/png" href="../img/LogoEdutrivia (1).png">
    <link rel="stylesheet" href="../css/app.css">
    <title>Panel del Estudiante</title>
</head>
<body>

    <header class="header-mobile">
        <span class="logo">Edutrivia</span>
        <button class="open-menu" id="open-menu"><i class='bx bx-menu'></i></button>
    </header>

    <div class="wrapper">
        <aside>
            <div>
                <button class="close-menu" id="close-menu"><i class='bx bx-x'></i></button>
                <h2 class="logo">👋 Hola, <?= htmlspecialchars($_SESSION['usuario']) ?></h2>

                <nav class="menu">
                    <a href="../Clases/index.php" class="boton-menu">
                        <i class='bx bx-chalkboard'></i> Mis Clases
                    </a>
                    <a href="resultados.php" class="boton-menu">
                        <i class='bx bx-trophy'></i> Mis Resultados
                    </a>
                </nav>
            </div>

            <div>
                <p class="texto-footer">Rol: Estudiante</p>
                <a href="../logout.php" class="boton-menu">
                    <i class='bx bx-log-out'></i> Cerrar sesión
                </a>
            </div>
        </aside>

        <main>
            <h1 class="titulo-principal">Bienvenido a Edutrivia</h1>
            <p>Ve a "Mis Clases" para unirte con el código de tu profesor, y desde ahí podrás jugar Jeopardy o Ahorcado con las preguntas de esa clase.</p>
        </main>
    </div>

    <script src="../js/menu2.js"></script>
</body>
</html>