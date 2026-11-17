<?php
session_start();
include("../conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'estudiante') {
    header("Location: ../login.php");
    exit();
}

$stmt = $conexion->prepare("
    SELECT c.id, c.nombre,
           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = ? AND r.clase_id = c.id AND r.juego = 'jeopardy') AS mejor_jeopardy,
           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = ? AND r.clase_id = c.id AND r.juego = 'ahorcado') AS mejor_ahorcado,
           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = ? AND r.clase_id = c.id AND r.juego = 'carrera') AS mejor_carrera,
           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = ? AND r.clase_id = c.id AND r.juego = 'memorama') AS mejor_memorama,
           (SELECT MIN(r.tiempo_segundos) FROM resultados r WHERE r.estudiante_id = ? AND r.clase_id = c.id AND r.juego = 'memorama' AND r.tiempo_segundos IS NOT NULL) AS mejor_tiempo_memorama
    FROM inscripciones i
    INNER JOIN clases c ON c.id = i.clase_id
    WHERE i.estudiante_id = ?
    ORDER BY c.nombre ASC
");
$stmt->bind_param("iiiiii", $_SESSION['usuario_id'], $_SESSION['usuario_id'], $_SESSION['usuario_id'], $_SESSION['usuario_id'], $_SESSION['usuario_id'], $_SESSION['usuario_id']);
$stmt->execute();
$clases = $stmt->get_result();
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
    <title>Mis Resultados - Edutrivia</title>
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
            max-width: 100% !important;
            margin: 0 !important;
        }

        .header-acciones-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            text-align: center;
        }

        th:first-child {
            text-align: left;
        }

        td {
            padding: 16px 20px;
            color: var(--text-main);
            border-bottom: 1px solid var(--border-light);
            font-weight: 500;
            text-align: center;
        }

        td:first-child {
            text-align: left;
            font-weight: 700;
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
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .badge-tiempo {
            background: #e0f2fe;
            color: #0284c7;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
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
                <h2 class="logo"><i class='bx bxs-graduation' style="font-size: 1.2rem;"></i> Estudiante</h2>

                <nav class="menu">
                    <a href="index.php" class="boton-menu">
                        <i class='bx bx-home-alt-2'></i> Inicio
                    </a>
                    <a href="../Clases/index.php" class="boton-menu">
                        <i class='bx bx-chalkboard'></i> Mis Clases
                    </a>
                    <a href="resultados.php" class="boton-menu active">
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
            <div class="contenedor-gestion">
                
                <div class="header-acciones-top">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <a href="index.php" style="color: var(--text-muted); font-size: 1.5rem; display: flex; align-items: center;" title="Volver">
                            <i class='bx bx-arrow-back'></i>
                        </a>
                        <h1 class="pagina-titulo"><i class='bx bx-trophy'></i> Mis Resultados Académicos</h1>
                    </div>
                </div>

                <p style="color: var(--text-muted); margin: 0; font-size: 0.95rem;">
                    Consulta tu mejor puntaje y récord en cada minijuego organizado por las clases a las que perteneces.
                </p>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Clase</th>
                                <th>Jeopardy</th>
                                <th>Ahorcado</th>
                                <th>Carrera</th>
                                <th>Memorama</th>
                                <th>Tiempo Memorama</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($clases->num_rows === 0) { ?>
                                <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px;">
                                    Todavía no estás inscrito en ninguna clase. Únete a una clase con el código de tu profesor para ver tus resultados.
                                </td></tr>
                            <?php } ?>

                            <?php while ($c = $clases->fetch_assoc()) { ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <i class='bx bx-chalkboard' style="color: var(--primary); font-size: 1.2rem;"></i>
                                            <?= htmlspecialchars($c['nombre']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($c['mejor_jeopardy'] !== null) { ?>
                                            <span class="badge-puntaje"><?= (int)$c['mejor_jeopardy'] ?> pts</span>
                                        <?php } else { ?>
                                            <span style="color: var(--text-muted);">—</span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if ($c['mejor_ahorcado'] !== null) { ?>
                                            <span class="badge-puntaje"><?= (int)$c['mejor_ahorcado'] ?> pts</span>
                                        <?php } else { ?>
                                            <span style="color: var(--text-muted);">—</span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if ($c['mejor_carrera'] !== null) { ?>
                                            <span class="badge-puntaje"><?= (int)$c['mejor_carrera'] ?> pts</span>
                                        <?php } else { ?>
                                            <span style="color: var(--text-muted);">—</span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if ($c['mejor_memorama'] !== null) { ?>
                                            <span class="badge-puntaje"><?= (int)$c['mejor_memorama'] ?> pts</span>
                                        <?php } else { ?>
                                            <span style="color: var(--text-muted);">—</span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if ($c['mejor_tiempo_memorama'] !== null) { ?>
                                            <span class="badge-tiempo">⏱️ <?= sprintf('%02d:%02d', intdiv($c['mejor_tiempo_memorama'], 60), $c['mejor_tiempo_memorama'] % 60) ?></span>
                                        <?php } else { ?>
                                            <span style="color: var(--text-muted);">—</span>
                                        <?php } ?>
                                    </td>
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