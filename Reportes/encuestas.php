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
    /* El admin ve las encuestas de TODOS los alumnos del sistema */
    $stmt = $conexion->prepare("SELECT opcion, COUNT(*) AS total FROM encuestas GROUP BY opcion");
    $stmt->execute();
} else {
    /* El profesor solo ve las encuestas de alumnos inscritos en SUS clases */
    $stmt = $conexion->prepare("
        SELECT e.opcion, COUNT(*) AS total
        FROM encuestas e
        WHERE e.usuario_id IN (
            SELECT DISTINCT i.estudiante_id
            FROM inscripciones i
            INNER JOIN clases c ON c.id = i.clase_id
            WHERE c.profesor_id = ?
        )
        GROUP BY e.opcion
    ");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
}

$resultado = $stmt->get_result();

$conteo = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
while ($row = $resultado->fetch_assoc()) {
    $conteo[(int)$row['opcion']] = (int)$row['total'];
}

$total_votos = array_sum($conteo);

$opciones = [
    1 => ['emoji' => '🤩', 'texto' => 'Excelente', 'color' => '#10b981', 'bg' => '#dcfce7'],
    2 => ['emoji' => '🙂', 'texto' => 'Bueno',     'color' => '#0284c7', 'bg' => '#e0f2fe'],
    3 => ['emoji' => '😐', 'texto' => 'Regular',   'color' => '#f59e0b', 'bg' => '#fef3c7'],
    4 => ['emoji' => '😕', 'texto' => 'Malo',      'color' => '#ef4444', 'bg' => '#fee2e2'],
];
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
    <title>Encuestas de Satisfacción - Edutrivia</title>
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
            gap: 25px;
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
        }

        /* Botones de Exportar */
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

        /* Cuadrícula de Satisfacción */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 10px;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 24px;
            text-align: center;
            border: 1px solid var(--border-light);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
            display: flex;
            flex-direction: column;
            gap: 8px;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .stat-emoji {
            font-size: 2.8rem;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 800;
        }

        .stat-percentage {
            font-size: 0.85rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-block;
            margin: 0 auto;
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
                        <a href="index.php" style="color: var(--text-muted); font-size: 1.5rem; display: flex; align-items: center;" title="Volver">
                            <i class='bx bx-arrow-back'></i>
                        </a>
                        <div>
                            <h1 class="pagina-titulo"><i class='bx bx-message-rounded-dots'></i> Encuestas de Satisfacción</h1>
                            <p class="subtitulo-desc">
                                Total de respuestas: <b><?= $total_votos ?></b> 
                                <span style="color: var(--text-muted); font-size: 0.85rem;"><?= $es_admin ? '(de todo el sistema)' : '(solo de tus alumnos)' ?></span>
                            </p>
                        </div>
                    </div>

                    <div class="acciones-exportar">
                        <a href="exportar_pdf.php?tipo=encuestas" class="btn-export">
                            <i class='bx bxs-file-pdf' style="font-size: 1.1rem;"></i> PDF
                        </a>
                        <a href="exportar_excel.php?tipo=encuestas" class="btn-export excel">
                            <i class='bx bxs-file-export' style="font-size: 1.1rem;"></i> Excel
                        </a>
                    </div>
                </div>

                <!-- Cuadrícula de Estadísticas de Satisfacción -->
                <div class="stats-grid">
                    <?php foreach ($opciones as $valor => $o) {
                        $cantidad = $conteo[$valor];
                        $porcentaje = $total_votos > 0 ? round($cantidad / $total_votos * 100) : 0;
                    ?>
                    <div class="stat-card">
                        <div class="stat-emoji"><?= $o['emoji'] ?></div>
                        <div class="stat-label"><?= $o['texto'] ?></div>
                        <div class="stat-value" style="color: <?= $o['color'] ?>;"><?= $cantidad ?></div>
                        <div>
                            <span class="stat-percentage" style="background: <?= $o['bg'] ?>; color: <?= $o['color'] ?>;">
                                <?= $porcentaje ?>% del total
                            </span>
                        </div>
                    </div>
                    <?php } ?>
                </div>

                <?php if ($total_votos === 0) { ?>
                    <div style="text-align: center; color: var(--text-muted); padding: 40px; background: #f8fafc; border-radius: 16px; border: 1px dashed var(--border-light);">
                        <i class='bx bx-info-circle' style="font-size: 2rem; margin-bottom: 8px;"></i>
                        <p style="margin: 0; font-weight: 600;">Todavía no hay respuestas de encuesta registradas.</p>
                    </div>
                <?php } ?>

            </div>
        </main>
    </div>

    <script src="../js/menu2.js"></script>
</body>
</html>