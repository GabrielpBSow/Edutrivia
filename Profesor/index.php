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
    <title>Panel del Profesor - Edutrivia</title>
    <!-- Google Fonts para una tipografía ultra profesional -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #1e1b4b; /* Azul medianoche / morado muy profundo */
            --sidebar-hover: rgba(99, 102, 241, 0.15);
            --primary: #6366f1; /* Índigo moderno */
            --primary-hover: #4f46e5;
            --accent-green: #10b981;
            --accent-orange: #f59e0b;
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

        /* --- Sidebar (Barra Lateral Moderna) --- */
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
            transition: transform 0.3s ease;
        }

        aside h2.logo {
            font-size: 1rem !important;
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

        /* Footer del Sidebar */
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
            gap: 30px;
        }

        /* Banner de Bienvenida Estilo Tarjeta Dinámica */
        .welcome-banner {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 35px;
            border-radius: 24px;
            box-shadow: 0 15px 30px -10px rgba(79, 70, 229, 0.3);
            position: relative;
            overflow: hidden;
        }

        .welcome-banner::after {
            content: '';
            position: absolute;
            right: -30px;
            bottom: -30px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            pointer-events: none;
        }

        .titulo-principal {
            font-size: 2.2rem !important;
            font-weight: 800 !important;
            color: #ffffff !important;
            margin-bottom: 10px !important;
            letter-spacing: -0.02em;
        }

        .welcome-banner p {
            font-size: 1.05rem;
            line-height: 1.6;
            opacity: 0.92;
            max-width: 750px;
            margin: 0;
            font-weight: 400;
        }

        /* Cuadrícula de Accesos Rápidos (Dashboard Cards) */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 22px;
        }

        .action-card {
            background: var(--card-bg);
            padding: 26px;
            border-radius: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -2px rgba(0, 0, 0, 0.02);
            border: 1px solid var(--border-light);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            gap: 14px;
            position: relative;
        }

        .action-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.06), 0 8px 10px -6px rgba(0, 0, 0, 0.06);
            border-color: #cbd5e1;
        }

        .card-icon {
            width: 54px;
            height: 54px;
            background: #ede9fe;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            font-size: 1.6rem;
            transition: transform 0.3s ease;
        }

        .action-card:hover .card-icon {
            transform: scale(1.1);
        }

        .action-card h3 {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-main);
            margin: 0;
        }

        .action-card p {
            font-size: 0.92rem;
            color: var(--text-muted);
            line-height: 1.5;
            margin: 0;
        }

        /* Variaciones de color para los iconos de las tarjetas */
        .card-icon.clases { background: #e0f2fe; color: #0284c7; }
        .card-icon.reportes { background: #fef3c7; color: #d97706; }
        .card-icon.usuarios { background: #dcfce7; color: #16a34a; }

        /* Responsive */
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
        }
    </style>
</head>
<body>

    <header class="header-mobile">
        <span class="logo" style="font-weight: 700;">⚡ Edutrivia</span>
        <button class="open-menu" id="open-menu" style="background:transparent; border:none; color:white; font-size:1.5rem;"><i class='bx bx-menu'></i></button>
    </header>

    <div class="wrapper" style="display: flex; width: 100%; height: 100vh;">
        <aside>
            <div>
                <button class="close-menu" id="close-menu" style="display:none;"><i class='bx bx-x'></i></button>
                <h2 class="logo"><i class='bx bxs-graduation' style="font-size: 1.2rem;"></i> Edutrivia</h2>

                <nav class="menu">
                    <a href="index.php" class="boton-menu active">
                        <i class='bx bx-home-alt-2'></i> Inicio
                    </a>
                    <a href="../Mazos/index.php" class="boton-menu">
                        <i class='bx bx-layer'></i> Mis Mazos
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

            <div class="sidebar-footer-container">
                <p class="texto-footer">Rol: Profesor</p>
                <a href="../logout.php" class="boton-menu">
                    <i class='bx bx-log-out'></i> Cerrar sesión
                </a>
            </div>
        </aside>

        <main>
            <!-- Banner de Bienvenida -->
            <div class="welcome-banner">
                <h1 class="titulo-principal">¡Hola, <?= htmlspecialchars($_SESSION['usuario']) ?>! 👋</h1>
                <p>Bienvenido a tu panel de control. Desde aquí puedes armar tu banco de preguntas, gestionar tus clases, revisar reportes de estudiantes y poner a prueba tus trivias antes de compartirlas.</p>
            </div>

            <!-- Tarjetas de Acceso Rápido -->
            <div class="dashboard-grid">
                <a href="../Mazos/index.php" class="action-card">
                    <div class="card-icon"><i class='bx bx-layer'></i></div>
                    <h3>Mis Mazos</h3>
                    <p>Crea, edita y organiza tus bancos de preguntas y trivias por temas o materias.</p>
                </a>

                <a href="../Clases/index.php" class="action-card">
                    <div class="card-icon clases"><i class='bx bx-chalkboard'></i></div>
                    <h3>Mis Clases</h3>
                    <p>Administra tus grupos de alumnos, asigna actividades y haz seguimiento.</p>
                </a>

                <a href="../Reportes/index.php" class="action-card">
                    <div class="card-icon reportes"><i class='bx bx-line-chart'></i></div>
                    <h3>Reportes</h3>
                    <p>Analiza el rendimiento, aciertos y calificaciones generales de tus estudiantes.</p>
                </a>

                <a href="../Usuarios/index.php" class="action-card">
                    <div class="card-icon usuarios"><i class='bx bx-user-check'></i></div>
                    <h3>Usuarios</h3>
                    <p>Consulta el listado general de usuarios registrados dentro de la plataforma.</p>
                </a>
            </div>
        </main>
    </div>

    <script src="../js/menu2.js"></script>
</body>
</html>