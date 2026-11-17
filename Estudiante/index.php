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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <title>Panel del Estudiante - Edutrivia</title>
    <style>
        :root {
            --sidebar-bg: #1e1b4b;
            --sidebar-hover: rgba(99, 102, 241, 0.15);
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --accent-green: #10b981;
            --bg-main: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-light: #e2e8f0;
        }

        * {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-main) !important;
            color: var(--text-main);
            margin: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* --- Header Móvil --- */
        .header-mobile {
            background: var(--sidebar-bg);
            color: white;
            padding: 15px 20px;
            display: none;
            justify-content: space-between;
            align-items: center;
        }

        /* --- Sidebar (Barra Lateral) --- */
        aside {
            width: 280px;
            background: var(--sidebar-bg) !important;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 28px 20px;
            box-shadow: 6px 0 25px rgba(0, 0, 0, 0.05);
            z-index: 100;
        }

        aside h2.logo {
            font-size: 0.95rem !important;
            color: #c7d2fe !important;
            background: rgba(255, 255, 255, 0.05);
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .menu .boton-menu {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 13px 16px;
            border-radius: 12px;
            color: #94a3b8 !important;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.25s ease;
            text-decoration: none;
            background: transparent !important;
        }

        .menu .boton-menu:hover {
            background: var(--sidebar-hover) !important;
            color: #ffffff !important;
            transform: translateX(4px);
        }

        .menu .boton-menu.active {
            background: var(--primary) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }

        .menu .boton-menu i {
            font-size: 1.3rem;
        }

        .sidebar-footer-container {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 20px;
        }

        .texto-footer {
            color: #64748b !important;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 10px;
            padding-left: 4px;
        }

        aside .sidebar-footer-container .boton-menu {
            color: #f87171 !important;
            background: rgba(248, 113, 113, 0.05) !important;
        }

        aside .sidebar-footer-container .boton-menu:hover {
            background: rgba(248, 113, 113, 0.15) !important;
        }

        /* --- Contenido Principal --- */
        main {
            flex: 1;
            padding: 40px !important;
            background-color: var(--bg-main) !important;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .welcome-card {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            border-radius: 24px;
            padding: 35px;
            color: white;
            box-shadow: 0 10px 25px -5px rgba(30, 27, 75, 0.2);
            display: flex;
            flex-direction: column;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        .welcome-card::after {
            content: '\ebc0';
            font-family: 'boxicons';
            position: absolute;
            right: -20px;
            bottom: -30px;
            font-size: 12rem;
            color: rgba(255, 255, 255, 0.04);
            pointer-events: none;
        }

        .titulo-principal {
            font-size: 2.2rem !important;
            font-weight: 800 !important;
            margin: 0 !important;
            color: white !important;
        }

        .welcome-card p {
            color: #c7d2fe;
            font-size: 1.05rem;
            margin: 0;
            max-width: 600px;
            line-height: 1.5;
        }

        /* Cuadrícula de accesos rápidos */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
        }

        .dashboard-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid var(--border-light);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
            display: flex;
            flex-direction: column;
            gap: 14px;
            text-decoration: none;
            transition: all 0.25s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 20px -3px rgba(0, 0, 0, 0.06);
            border-color: var(--primary);
        }

        .card-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: #ede9fe;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .dashboard-card h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .dashboard-card p {
            margin: 0;
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.4;
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
                overflow: auto;
            }
            aside {
                width: 100%;
                height: auto;
            }
            main {
                padding: 20px !important;
            }
            .welcome-card {
                padding: 25px;
            }
            .titulo-principal {
                font-size: 1.6rem !important;
            }
        }
    </style>
</head>
<body>

    <!-- Header Móvil -->
    <header class="header-mobile">
        <span class="logo" style="font-weight: 700; color: white;">⚡ Edutrivia</span>
        <button class="open-menu" id="open-menu" style="background:transparent; border:none; color:white; font-size:1.5rem;"><i class='bx bx-menu'></i></button>
    </header>

    <div class="wrapper" style="display: flex; width: 100%; height: 100vh;">
        <!-- Barra Lateral Consistente -->
        <aside>
            <div>
                <button class="close-menu" id="close-menu" style="display:none;"><i class='bx bx-x'></i></button>
                <h2 class="logo"><i class='bx bxs-graduation' style="font-size: 1.2rem;"></i> Estudiante</h2>

                <nav class="menu">
                    <a href="index.php" class="boton-menu active">
                        <i class='bx bx-home-alt-2'></i> Inicio
                    </a>
                    <a href="../Clases/index.php" class="boton-menu">
                        <i class='bx bx-chalkboard'></i> Mis Clases
                    </a>
                    <a href="resultados.php" class="boton-menu">
                        <i class='bx bx-trophy'></i> Mis Resultados
                    </a>
                </nav>
            </div>

            <div class="sidebar-footer-container">
                <p class="texto-footer">Rol: Estudiante</p>
                <a href="../logout.php" class="boton-menu">
                    <i class='bx bx-log-out'></i> Cerrar sesión
                </a>
            </div>
        </aside>

        <!-- Contenido Principal -->
        <main>
            <!-- Banner de Bienvenida -->
            <div class="welcome-card">
                <h1 class="titulo-principal">¡Hola, <?= htmlspecialchars($_SESSION['usuario'] ?? 'Estudiante') ?>! 👋</h1>
                <p>Bienvenido a tu panel de aprendizaje. Únete a tus clases con el código que te dio tu profesor y prepárate para jugar y aprender con trivias interactivas.</p>
            </div>

            <!-- Accesos Rápidos en Cuadrícula -->
            <div class="dashboard-grid">
                <a href="../Clases/index.php" class="dashboard-card">
                    <div class="card-icon-wrapper" style="background: #e0f2fe; color: #0284c7;">
                        <i class='bx bx-chalkboard'></i>
                    </div>
                    <h3>Mis Clases</h3>
                    <p>Inscríbete a nuevas aulas virtuales con código y accede a tus materias activas.</p>
                </a>

                <a href="resultados.php" class="dashboard-card">
                    <div class="card-icon-wrapper" style="background: #fef3c7; color: #d97706;">
                        <i class='bx bx-trophy'></i>
                    </div>
                    <h3>Mis Resultados</h3>
                    <p>Revisa tus puntajes, estadísticas y el historial de tus partidas en los minijuegos.</p>
                </a>
            </div>
        </main>
    </div>

    <script src="../js/menu2.js"></script>
</body>
</html>