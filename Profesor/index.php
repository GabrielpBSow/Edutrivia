<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'profesor') {
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
    <title>Panel del Profesor</title>
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
                    <a href="../Mazos/index.php" class="boton-menu">
                        <i class='bx bx-list-ul'></i> Mis Mazos
                    </a>
                    <a href="../Clases/index.php" class="boton-menu">
                        <i class='bx bx-chalkboard'></i> Mis Clases
                    </a>
                    <a href="../Usuarios/index.php" class="boton-menu">
                        <i class='bx bx-group'></i> Ver Usuarios
                    </a>
                    <a href="../Reportes/index.php" class="boton-menu">
                        <i class='bx bx-bar-chart-alt-2'></i> Reportes
                    </a>
                </nav>
            </div>

            <div>
                <p class="texto-footer">Rol: Profesor</p>
                <a href="../logout.php" class="boton-menu">
                    <i class='bx bx-log-out'></i> Cerrar sesión
                </a>
            </div>
        </aside>

        <main>
            <h1 class="titulo-principal">Panel del Profesor</h1>
            <p>Desde aquí puedes armar tu banco de preguntas, revisar el listado de usuarios, ver reportes de tus estudiantes, y jugar para probar tus preguntas antes de compartirlas.</p>
        </main>
    </div>

    <script src="../js/menu2.js"></script>
</body>
</html>