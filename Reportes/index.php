<?php
session_start();
include("../conexion.php");
require_once("../csrf.php");

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['administrador', 'profesor'])) {
    header("Location: ../login.php");
    exit();
}

$rol = $_SESSION['rol'];
$uid = $_SESSION['usuario_id'];
$es_admin = $rol === 'administrador';

if ($es_admin) {
    $stmt = $conexion->prepare("
        SELECT c.id, c.nombre, u.nombre AS profesor_nombre,
               (SELECT COUNT(*) FROM inscripciones i WHERE i.clase_id = c.id) AS total_alumnos,
               (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'jeopardy') AS promedio_jeopardy,
               (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'ahorcado') AS promedio_ahorcado,
               (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'carrera') AS promedio_carrera,
               (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'memorama') AS promedio_memorama
        FROM clases c
        INNER JOIN usuarios u ON u.id = c.profesor_id
        ORDER BY c.nombre ASC
    ");
    $stmt->execute();
} else {
    $stmt = $conexion->prepare("
        SELECT c.id, c.nombre, NULL AS profesor_nombre,
               (SELECT COUNT(*) FROM inscripciones i WHERE i.clase_id = c.id) AS total_alumnos,
               (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'jeopardy') AS promedio_jeopardy,
               (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'ahorcado') AS promedio_ahorcado,
               (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'carrera') AS promedio_carrera,
               (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'memorama') AS promedio_memorama
        FROM clases c
        WHERE c.profesor_id = ?
        ORDER BY c.nombre ASC
    ");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
}

$clases = $stmt->get_result();
$panel_regreso = $es_admin ? '../Administrador/index.php' : '../Profesor/index.php';
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
    <title>Reportes Académicos - Edutrivia</title>
    <style>
        :root {
            --sidebar-bg: #1e1b4b;
            --sidebar-hover: rgba(99, 102, 241, 0.15);
            --primary: #6366f1;
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

        .subtitulo-desc {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-top: -5px;
            margin-bottom: 10px;
        }

        /* Barra de acciones superiores (Exportar, Encuestas) */
        .acciones-reportes {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn-accion-reporte {
            background: #f1f5f9;
            color: var(--text-main);
            padding: 10px 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid var(--border-light);
            transition: all 0.2s;
        }

        .btn-accion-reporte:hover {
            background: #e2e8f0;
            border-color: #cbd5e1;
            transform: translateY(-2px);
        }

        .btn-accion-reporte.primary {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }

        .btn-accion-reporte.primary:hover {
            background: var(--primary-hover);
        }

        /* --- Tablas Modernas --- */
        .table-container {
            width: 100%;
            overflow-x: auto;
            border-radius: 16px;
            border: 1px solid var(--border-light);
            margin-bottom: 20px;
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

        .badge-puntaje {
            background: #ede9fe;
            color: var(--primary);
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .link-ranking {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: color 0.2s;
        }

        .link-ranking:hover {
            color: var(--primary-hover);
            text-decoration: underline;
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
                        <a href="<?= $panel_regreso ?>" style="color: var(--text-muted); font-size: 1.5rem; display: flex; align-items: center;">
                            <i class='bx bx-arrow-back'></i>
                        </a>
                        <div>
                            <h1 class="pagina-titulo"><i class='bx bx-bar-chart-alt-2'></i> Reportes Académicos</h1>
                            <p class="subtitulo-desc">Promedio de puntaje por clase — consulta el ranking completo de alumnos ingresando a cada clase.</p>
                        </div>
                    </div>

                    <!-- Botones de Acción Global (Exportar y Encuestas) -->
                    <div class="acciones-reportes">
                        <a href="encuestas.php" class="btn-accion-reporte">
                            <i class='bx bx-smile'></i> Ver Encuestas
                        </a>
                        <a href="exportar_pdf.php?tipo=promedio_clases" class="btn-accion-reporte primary">
                            <i class='bx bxs-file-pdf'></i> PDF
                        </a>
                        <a href="exportar_excel.php?tipo=promedio_clases" class="btn-accion-reporte primary" style="background: #059669; border-color: #059669;">
                            <i class='bx bxs-file-export'></i> Excel
                        </a>
                    </div>
                </div>

                <!-- Tabla de Rendimiento por Clase -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Clase</th>
                                <?php if ($es_admin) { ?><th>Profesor</th><?php } ?>
                                <th>Alumnos</th>
                                <th>Jeopardy</th>
                                <th>Ahorcado</th>
                                <th>Carrera</th>
                                <th>Memorama</th>
                                <th>Ranking</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($clases->num_rows === 0) { ?>
                                <tr><td colspan="<?= $es_admin ? 8 : 7 ?>" style="text-align: center; color: var(--text-muted); padding: 30px;">Todavía no hay clases con resultados registrados en el sistema.</td></tr>
                            <?php } ?>

                            <?php while ($c = $clases->fetch_assoc()) { ?>
                                <tr>
                                    <td style="font-weight: 700;"><?= htmlspecialchars($c['nombre']) ?></td>
                                    <?php if ($es_admin) { ?>
                                        <td><span style="color: var(--text-muted);"><i class='bx bx-user'></i> <?= htmlspecialchars($c['profesor_nombre']) ?></span></td>
                                    <?php } ?>
                                    <td><span style="font-weight: 700; color: var(--primary);"><?= (int)$c['total_alumnos'] ?></span></td>
                                    <td><?= $c['promedio_jeopardy'] !== null ? '<span class="badge-puntaje">'.round($c['promedio_jeopardy']).' pts</span>' : '<span style="color:var(--text-muted);">—</span>' ?></td>
                                    <td><?= $c['promedio_ahorcado'] !== null ? '<span class="badge-puntaje">'.round($c['promedio_ahorcado']).' pts</span>' : '<span style="color:var(--text-muted);">—</span>' ?></td>
                                    <td><?= $c['promedio_carrera'] !== null ? '<span class="badge-puntaje">'.round($c['promedio_carrera']).' pts</span>' : '<span style="color:var(--text-muted);">—</span>' ?></td>
                                    <td><?= $c['promedio_memorama'] !== null ? '<span class="badge-puntaje">'.round($c['promedio_memorama']).' pts</span>' : '<span style="color:var(--text-muted);">—</span>' ?></td>
                                    <td>
                                        <a href="ver.php?id=<?= (int)$c['id'] ?>" class="link-ranking">
                                            Ver ranking <i class='bx bx-right-arrow-alt'></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <!-- Sección de Otros Reportes -->
                <h2 class="pagina-titulo" style="font-size: 1.3rem !important; margin-top: 15px;"><i class='bx bx-list-ul'></i> Otros reportes del sistema</h2>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Tipo de Reporte</th>
                                <th style="text-align: right;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($es_admin) { ?>
                            <tr>
                                <td style="font-weight: 600;"><i class='bx bx-user-pin' style="color: var(--primary); margin-right: 8px;"></i> Usuarios por Rol</td>
                                <td style="text-align: right;"><a href="tabla.php?tipo=usuarios" class="link-ranking">Ver reporte <i class='bx bx-right-arrow-alt'></i></a></td>
                            </tr>
                            <?php } ?>
                            <tr>
                                <td style="font-weight: 600;"><i class='bx bx-layer' style="color: var(--primary); margin-right: 8px;"></i> Inventario de Mazos</td>
                                <td style="text-align: right;"><a href="tabla.php?tipo=mazos" class="link-ranking">Ver reporte <i class='bx bx-right-arrow-alt'></i></a></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600;"><i class='bx bx-award' style="color: var(--primary); margin-right: 8px;"></i> Reporte General de Calificaciones</td>
                                <td style="text-align: right;"><a href="tabla.php?tipo=general" class="link-ranking">Ver reporte <i class='bx bx-right-arrow-alt'></i></a></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600;"><i class='bx bx-game' style="color: var(--primary); margin-right: 8px;"></i> Participación en Juegos</td>
                                <td style="text-align: right;"><a href="tabla.php?tipo=participacion" class="link-ranking">Ver reporte <i class='bx bx-right-arrow-alt'></i></a></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600;"><i class='bx bx-time-five' style="color: var(--primary); margin-right: 8px;"></i> Clases sin Actividad</td>
                                <td style="text-align: right;"><a href="tabla.php?tipo=clases_inactivas" class="link-ranking">Ver reporte <i class='bx bx-right-arrow-alt'></i></a></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </main>
    </div>

    <script src="../js/menu2.js"></script>
</body>
</html>