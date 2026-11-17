<?php
session_start();
include("../conexion.php");
require_once("../csrf.php");
require_once(__DIR__ . '/lib_reportes.php');

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['administrador', 'profesor'])) {
    header("Location: ../login.php");
    exit();
}

$rol = $_SESSION['rol'];
$tipo = $_GET['tipo'] ?? '';
$id = (int)($_GET['id'] ?? 0);

$datos = obtener_datos_reporte($conexion, $tipo, $id);

if (isset($datos['error'])) {
    $_SESSION['error'] = $datos['error'];
    header("Location: index.php");
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
    <title><= htmlspecialchars($datos['titulo']) ?> - Edutrivia</title>
    <style>
        :root {
            --sidebar-bg: #1e1b4b;
            --sidebar-hover: rgba(99, 102, 241, 0.15);
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --accent-green: #10b981;
            --accent-red: #ef4444;
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

        .contenedor-gestion {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--border-light);
            display: flex;
            flex-direction: column;
            gap: 20px;
            flex: 1;
        }

        .header-acciones-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .pagina-titulo {
            font-size: 1.8rem !important;
            font-weight: 800 !important;
            color: var(--text-main) !important;
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0 !important;
        }

        .pagina-titulo i {
            color: var(--primary);
        }

        .acciones-exportar {
            display: flex;
            gap: 10px;
        }

        .btn-export {
            background: var(--primary);
            color: white;
            padding: 10px 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }

        .btn-export:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        .btn-export.excel {
            background: #059669;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
        }

        .btn-export.excel:hover {
            background: #047857;
        }

        /* --- Tabla Moderna --- */
        .table-container {
            width: 100%;
            overflow-x: auto;
            border-radius: 16px;
            border: 1px solid var(--border-light);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            background: white;
            font-size: 0.95rem;
        }

        th {
            background: #f1f5f9;
            color: var(--text-muted);
            font-weight: 700;
            padding: 16px 20px;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border-light);
        }

        td {
            padding: 16px 20px;
            color: var(--text-main);
            border-bottom: 1px solid var(--border-light);
            font-weight: 500;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #f8fafc;
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
        }
    </style>
</head>
<body>

    <!-- Header Móvil -->
    <header class="header-mobile">
        <span class="logo" style="font-weight: 700;">⚡ Edutrivia</span>
        <button class="open-menu" id="open-menu" style="background:transparent; border:none; color:white; font-size:1.5rem;"><i class='bx bx-menu'></i></button>
    </header>

    <div class="wrapper" style="display: flex; width: 100%; height: 100vh;">
        <!-- Barra Lateral Consistente -->
        <aside>
            <div>
                <button class="close-menu" id="close-menu" style="display:none;"><i class='bx bx-x'></i></button>
                <h2 class="logo"><i class='bx bxs-graduation' style="font-size: 1.2rem;"></i> Edutrivia</h2>

                <nav class="menu">
                    <a href="../Profesor/index.php" class="boton-menu">
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
                    <a href="index.php" class="boton-menu active">
                        <i class='bx bx-bar-chart-alt-2'></i> Reportes
                    </a>
                </nav>
            </div>

            <div class="sidebar-footer-container">
                <p class="texto-footer">Rol: <?= ucfirst($rol) ?></p>
                <a href="../logout.php" class="boton-menu">
                    <i class='bx bx-log-out'></i> Cerrar sesión
                </a>
            </div>
        </aside>

        <!-- Contenido Principal -->
        <main>
            <div class="contenedor-gestion">
                
                <div class="header-acciones-top">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <a href="<?= $id > 0 ? '../Clases/ver.php?id=' . $id : 'index.php' ?>" style="color: var(--text-muted); font-size: 1.5rem; display: flex; align-items: center;">
                            <i class='bx bx-arrow-back'></i>
                        </a>
                        <h1 class="pagina-titulo"><i class='bx bx-bar-chart-alt-2'></i> <?= htmlspecialchars($datos['titulo']) ?></h1>
                    </div>

                    <div class="acciones-exportar">
                        <a href="exportar_pdf.php?tipo=<?= urlencode($tipo) ?>&id=<?= $id ?>" class="btn-export">
                            <i class='bx bxs-file-pdf' style="font-size: 1.1rem;"></i> PDF
                        </a>
                        <a href="exportar_excel.php?tipo=<?= urlencode($tipo) ?>&id=<?= $id ?>" class="btn-export excel">
                            <i class='bx bxs-file-export' style="font-size: 1.1rem;"></i> Excel
                        </a>
                    </div>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <?php foreach ($datos['columnas'] as $c) { ?>
                                    <th><?= htmlspecialchars($c) ?></th>
                                <?php } ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($datos['filas']) === 0) { ?>
                                <tr>
                                    <td colspan="<?= count($datos['columnas']) ?>" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                        No hay datos para este reporte todavía.
                                    </td>
                                </tr>
                            <?php } ?>
                            
                            <?php foreach ($datos['filas'] as $fila) { ?>
                                <tr>
                                    <?php foreach ($fila as $v) { ?>
                                        <td><?= htmlspecialchars((string)$v) ?></td>
                                    <?php } ?>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </main>
    </div>

    <script src="../js/menu2.js"></script>
</body>
</html>