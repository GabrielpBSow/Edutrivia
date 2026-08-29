<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'administrador') {
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
    <title>Panel del Administrador</title>
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
                    <a href="../Usuarios/crear.php" class="boton-menu">
                        <i class='bx bx-user-plus'></i> Gestionar Usuarios
                    </a>
                    <a href="../Usuarios/index.php" class="boton-menu">
                        <i class='bx bx-group'></i> Ver Usuarios
                    </a>
                    <a href="../Mazos/index.php" class="boton-menu">
                        <i class='bx bx-list-ul'></i> Todos los Mazos
                    </a>
                    <a href="../Clases/index.php" class="boton-menu">
                        <i class='bx bx-chalkboard'></i> Todas las Clases
                    </a>
                    <a href="../Reportes/index.php" class="boton-menu">
                        <i class='bx bx-bar-chart-alt-2'></i> Reportes
                    </a>
                    <a href="../Backups/index.php" class="boton-menu">
                        <i class='bx bx-data'></i> Respaldo de BD
                    </a>
                </nav>
            </div>

            <div>
                <p class="texto-footer">Rol: Administrador</p>
                <a href="../logout.php" class="boton-menu">
                    <i class='bx bx-log-out'></i> Cerrar sesión
                </a>
            </div>
        </aside>

        <main>
            <h1 class="titulo-principal">Panel del Administrador</h1>
            <p>Control total del sistema: usuarios, banco de preguntas, reportes, y respaldos de la base de datos.</p>
        </main>
    </div>

    <script src="../js/menu2.js"></script>
</body>
</html>