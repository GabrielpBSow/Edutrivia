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
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
$token = csrf_token();

if ($es_admin) {
    $mazos = $conexion->query("
        SELECT m.id, m.nombre, m.creado_en, u.nombre AS profesor_nombre,
               (SELECT COUNT(*) FROM preguntas p WHERE p.mazo_id = m.id) AS total_preguntas
        FROM mazos m
        INNER JOIN usuarios u ON u.id = m.profesor_id
        ORDER BY m.creado_en DESC
    ");
} else {
    $stmt = $conexion->prepare("
        SELECT m.id, m.nombre, m.creado_en,
               (SELECT COUNT(*) FROM preguntas p WHERE p.mazo_id = m.id) AS total_preguntas
        FROM mazos m
        WHERE m.profesor_id = ?
        ORDER BY m.creado_en DESC
    ");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $mazos = $stmt->get_result();
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title><%= $es_admin ? 'Todos los Mazos' : 'Mis Mazos' ?> - Edutrivia</title>
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

        /* Contenedor tipo Tarjeta de Gestión */
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

        /* Alertas */
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            border: 1px solid #fca5a5;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            padding: 12px 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            border: 1px solid #86efac;
        }

        /* Botón superior de Nuevo Mazo */
        .btn-primary-custom {
            background: var(--primary);
            color: white;
            padding: 12px 20px;
            border-radius: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }

        .btn-primary-custom:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
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

        /* Acciones en la tabla */
        .acciones-td {
            display: flex;
            gap: 10px;
        }

        .btn-accion {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 1.1rem;
            transition: all 0.2s;
        }

        .btn-ver {
            background: #e0f2fe;
            color: #0284c7;
        }

        .btn-ver:hover {
            background: #bae6fd;
            transform: scale(1.05);
        }

        .btn-eliminar {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-eliminar:hover {
            background: #fecaca;
            transform: scale(1.05);
        }

        .badge-preguntas {
            background: #ede9fe;
            color: var(--primary);
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
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
                    <a href="index.php" class="boton-menu active">
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
                        <a href="<?= $es_admin ? '../Administrador/index.php' : '../Profesor/index.php' ?>" style="color: var(--text-muted); font-size: 1.5rem; display: flex; align-items: center;">
                            <i class='bx bx-arrow-back'></i>
                        </a>
                        <h1 class="pagina-titulo"><i class='bx bx-collection'></i> <?= $es_admin ? 'Todos los Mazos' : 'Mis Mazos' ?></h1>
                    </div>

                    <?php if (!$es_admin) { ?>
                        <a href="crear.php" class="btn-primary-custom">
                            <i class='bx bx-plus-circle' style="font-size: 1.2rem;"></i> Nuevo Mazo
                        </a>
                    <?php } ?>
                </div>

                <?php if ($error) { ?>
                    <div class="alert-error"><i class='bx bx-error-circle'></i> <?= htmlspecialchars($error) ?></div>
                <?php } ?>
                <?php if ($success) { ?>
                    <div class="alert-success"><i class='bx bx-check-circle'></i> <?= htmlspecialchars($success) ?></div>
                <?php } ?>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Mazo</th>
                                <?php if ($es_admin) { ?><th>Profesor</th><?php } ?>
                                <th>Preguntas</th>
                                <th>Creado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($mazos->num_rows === 0) { ?>
                                <tr><td colspan="<?= $es_admin ? 5 : 4 ?>" style="text-align: center; color: var(--text-muted); padding: 30px;">Todavía no hay mazos creados.</td></tr>
                            <?php } ?>

                            <?php while ($m = $mazos->fetch_assoc()) { ?>
                                <tr>
                                    <td style="font-weight: 700;"><?= htmlspecialchars($m['nombre']) ?></td>
                                    <?php if ($es_admin) { ?><td><span style="color: var(--text-muted);"><i class='bx bx-user'></i> <?= htmlspecialchars($m['profesor_nombre']) ?></span></td><?php } ?>
                                    <td><span class="badge-preguntas"><?= (int)$m['total_preguntas'] ?> preguntas</span></td>
                                    <td style="color: var(--text-muted); font-size: 0.9rem;"><?= htmlspecialchars($m['creado_en']) ?></td>
                                    <td>
                                        <div class="acciones-td">
                                            <a href="../Preguntas/index.php?mazo_id=<?= (int)$m['id'] ?>" class="btn-accion btn-ver" title="Ver Preguntas">
                                                <i class='bx bx-list-ul'></i>
                                            </a>
                                            <a href="#" onclick="confirmarAccion('eliminar.php?id=<?= (int)$m['id'] ?>&csrf_token=<?= urlencode($token) ?>', '¿Seguro que deseas eliminar este mazo? Esto borrará el mazo Y todas sus preguntas.'); return false;" class="btn-accion btn-eliminar" title="Eliminar Mazo">
                                                <i class='bx bx-trash'></i>
                                            </a>
                                        </div>
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
    <script src="../js/confirmar.js"></script>
</body>
</html>