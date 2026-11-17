<?php
session_start();
include("../conexion.php");
require_once("../csrf.php");

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['administrador', 'profesor'])) {
    header("Location: ../login.php");
    exit();
}

$rol = $_SESSION['rol'];
$es_admin = $rol === 'administrador';
$clase_id = (int)($_GET['id'] ?? 0);

if ($clase_id <= 0) {
    header("Location: index.php");
    exit();
}

$stmt = $conexion->prepare("SELECT id, nombre, profesor_id FROM clases WHERE id = ?");
$stmt->bind_param("i", $clase_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$clase = $res->fetch_assoc();

if ($_SESSION['rol'] === 'profesor' && $clase['profesor_id'] !== $_SESSION['usuario_id']) {
    $_SESSION['error'] = "No tienes permiso para ver esa clase";
    header("Location: index.php");
    exit();
}

/* Promedios de la clase */
$stmt = $conexion->prepare("SELECT AVG(puntaje) AS promedio FROM resultados WHERE clase_id = ? AND juego = 'jeopardy'");
$stmt->bind_param("i", $clase_id);
$stmt->execute();
$promedio_jeopardy = $stmt->get_result()->fetch_assoc()['promedio'];

$stmt = $conexion->prepare("SELECT AVG(puntaje) AS promedio FROM resultados WHERE clase_id = ? AND juego = 'ahorcado'");
$stmt->bind_param("i", $clase_id);
$stmt->execute();
$promedio_ahorcado = $stmt->get_result()->fetch_assoc()['promedio'];

$stmt = $conexion->prepare("SELECT AVG(puntaje) AS promedio FROM resultados WHERE clase_id = ? AND juego = 'carrera'");
$stmt->bind_param("i", $clase_id);
$stmt->execute();
$promedio_carrera = $stmt->get_result()->fetch_assoc()['promedio'];

$stmt = $conexion->prepare("SELECT AVG(puntaje) AS promedio FROM resultados WHERE clase_id = ? AND juego = 'memorama'");
$stmt->bind_param("i", $clase_id);
$stmt->execute();
$promedio_memorama = $stmt->get_result()->fetch_assoc()['promedio'];

/* Alumnos con su mejor puntaje por juego */
$stmt = $conexion->prepare("
    SELECT u.nombre, u.correo,
           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = ? AND r.juego = 'jeopardy') AS mejor_jeopardy,
           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = ? AND r.juego = 'ahorcado') AS mejor_ahorcado,
           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = ? AND r.juego = 'carrera') AS mejor_carrera,
           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = ? AND r.juego = 'memorama') AS mejor_memorama
    FROM inscripciones i
    INNER JOIN usuarios u ON u.id = i.estudiante_id
    WHERE i.clase_id = ?
");
$stmt->bind_param("iiiii", $clase_id, $clase_id, $clase_id, $clase_id, $clase_id);
$stmt->execute();
$res_alumnos = $stmt->get_result();

$alumnos = [];
while ($a = $res_alumnos->fetch_assoc()) {
    $a['total'] = (int)($a['mejor_jeopardy'] ?? 0) + (int)($a['mejor_ahorcado'] ?? 0) + (int)($a['mejor_carrera'] ?? 0) + (int)($a['mejor_memorama'] ?? 0);
    $alumnos[] = $a;
}

/* Ranking: de mayor a menor puntaje combinado */
usort($alumnos, fn($x, $y) => $y['total'] <=> $x['total']);

$medallas = ['🥇', '🥈', '🥉'];
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
    <title>Reporte — <?= htmlspecialchars($clase['nombre']) ?> - Edutrivia</title>
    <style>
        :root {
            --sidebar-bg: #1e1b4b;
            --sidebar-hover: rgba(99, 102, 241, 0.15);
            --primary: #6366f1;
            --primary-hover: #4f46e5;
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
            gap: 24px;
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

        /* Botones de acción superiores */
        .acciones-barra {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-accion-top {
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

        .btn-accion-top:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        .btn-accion-top.excel {
            background: #059669;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
        }

        .btn-accion-top.excel:hover {
            background: #047857;
        }

        .btn-accion-top.secondary {
            background: #f1f5f9;
            color: var(--text-main);
            border: 1px solid var(--border-light);
            box-shadow: none;
        }

        .btn-accion-top.secondary:hover {
            background: #e2e8f0;
        }

        /* Tarjetas de Promedios de Juego */
        .promedios-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 18px;
        }

        .promedio-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 20px;
            text-align: center;
            border: 1px solid var(--border-light);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            transition: transform 0.2s;
        }

        .promedio-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .promedio-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 4px;
        }

        .promedio-label {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .promedio-valor {
            font-size: 1.9rem;
            font-weight: 800;
            color: var(--text-main);
        }

        /* --- Tabla Moderna de Ranking --- */
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

        .posicion-badge {
            font-size: 1.2rem;
            font-weight: 800;
            display: inline-block;
            min-width: 28px;
            text-align: center;
        }

        .badge-total {
            background: #ede9fe;
            color: var(--primary);
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 800;
            font-size: 0.95rem;
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
                    <a href="<?= $es_admin ? '../Administrador/index.php' : '../Profesor/index.php' ?>" class="boton-menu">
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
                        <a href="index.php" style="color: var(--text-muted); font-size: 1.5rem; display: flex; align-items: center;" title="Volver">
                            <i class='bx bx-arrow-back'></i>
                        </a>
                        <h1 class="pagina-titulo"><i class='bx bx-trophy'></i> Ranking: <?= htmlspecialchars($clase['nombre']) ?></h1>
                    </div>

                    <div class="acciones-barra">
                        <a href="tabla.php?tipo=actividad&id=<?= $clase_id ?>" class="btn-accion-top secondary">
                            <i class='bx bx-game' style="font-size: 1.1rem;"></i> Ver actividad
                        </a>
                        <a href="exportar_pdf.php?tipo=ranking_clase&id=<?= $clase_id ?>" class="btn-accion-top">
                            <i class='bx bxs-file-pdf' style="font-size: 1.1rem;"></i> PDF
                        </a>
                        <a href="exportar_excel.php?tipo=ranking_clase&id=<?= $clase_id ?>" class="btn-accion-top excel">
                            <i class='bx bxs-file-export' style="font-size: 1.1rem;"></i> Excel
                        </a>
                    </div>
                </div>

                <!-- Tarjetas de Promedios por Juego -->
                <div class="promedios-grid">
                    <div class="promedio-card">
                        <div class="promedio-icon" style="background: #ede9fe; color: #6366f1;">🎯</div>
                        <div class="promedio-label">Promedio Jeopardy</div>
                        <div class="promedio-valor"><?= $promedio_jeopardy !== null ? round($promedio_jeopardy) . ' pts' : '—' ?></div>
                    </div>

                    <div class="promedio-card">
                        <div class="promedio-icon" style="background: #fee2e2; color: #dc2626;">💀</div>
                        <div class="promedio-label">Promedio Ahorcado</div>
                        <div class="promedio-valor"><?= $promedio_ahorcado !== null ? round($promedio_ahorcado) . ' pts' : '—' ?></div>
                    </div>

                    <div class="promedio-card">
                        <div class="promedio-icon" style="background: #fef3c7; color: #d97706;">🏍️</div>
                        <div class="promedio-label">Promedio Carrera</div>
                        <div class="promedio-valor"><?= $promedio_carrera !== null ? round($promedio_carrera) . ' pts' : '—' ?></div>
                    </div>

                    <div class="promedio-card">
                        <div class="promedio-icon" style="background: #dcfce7; color: #16a34a;">🃏</div>
                        <div class="promedio-label">Promedio Memorama</div>
                        <div class="promedio-valor"><?= $promedio_memorama !== null ? round($promedio_memorama) . ' pts' : '—' ?></div>
                    </div>
                </div>

                <!-- Tabla de Ranking de Alumnos -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 60px; text-align: center;">Pos</th>
                                <th>Alumno</th>
                                <th>Correo</th>
                                <th>Jeopardy</th>
                                <th>Ahorcado</th>
                                <th>Carrera</th>
                                <th>Memorama</th>
                                <th>Puntaje Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($alumnos) === 0) { ?>
                                <tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 30px;">Todavía no hay alumnos inscritos en esta clase.</td></tr>
                            <?php } ?>

                            <?php foreach ($alumnos as $idx => $a) { ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <span class="posicion-badge"><?= $medallas[$idx] ?? ($idx + 1) ?></span>
                                    </td>
                                    <td style="font-weight: 700;"><?= htmlspecialchars($a['nombre']) ?></td>
                                    <td style="color: var(--text-muted); font-size: 0.9rem;"><?= htmlspecialchars($a['correo']) ?></td>
                                    <td><?= $a['mejor_jeopardy'] !== null ? (int)$a['mejor_jeopardy'] : '—' ?></td>
                                    <td><?= $a['mejor_ahorcado'] !== null ? (int)$a['mejor_ahorcado'] : '—' ?></td>
                                    <td><?= $a['mejor_carrera'] !== null ? (int)$a['mejor_carrera'] : '—' ?></td>
                                    <td><?= $a['mejor_memorama'] !== null ? (int)$a['mejor_memorama'] : '—' ?></td>
                                    <td><span class="badge-total"><?= $a['total'] ?> pts</span></td>
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