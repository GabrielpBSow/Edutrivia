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

// Capturar el filtro seleccionado (solo aplica para el administrador)
$filtro_rol = $_GET['filtro_rol'] ?? 'todos';

if ($es_admin) {
    // Si el admin selecciona un rol específico, filtramos; si no, mostramos todos
    if (in_array($filtro_rol, ['administrador', 'profesor', 'estudiante'])) {
        $stmt = $conexion->prepare("SELECT id, nombre, correo, rol, activo, creado_en FROM usuarios WHERE rol = ? ORDER BY nombre ASC");
        $stmt->bind_param("s", $filtro_rol);
        $stmt->execute();
        $usuarios = $stmt->get_result();
    } else {
        $usuarios = $conexion->query("SELECT id, nombre, correo, rol, activo, creado_en FROM usuarios ORDER BY nombre ASC");
    }
} else {
    // El profesor ve ÚNICAMENTE a los estudiantes inscritos en sus propias clases
    $stmt = $conexion->prepare("
        SELECT DISTINCT u.id, u.nombre, u.correo, u.rol, u.activo, u.creado_en, c.nombre AS clase_nombre
        FROM usuarios u
        INNER JOIN inscripciones i ON i.estudiante_id = u.id
        INNER JOIN clases c ON c.id = i.clase_id
        WHERE c.profesor_id = ?
        ORDER BY u.nombre ASC
    ");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $usuarios = $stmt->get_result();
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
    <title><?= $es_admin ? 'Gestión de Usuarios' : 'Mis Estudiantes' ?> - Edutrivia</title>
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

        /* Barra de Filtros y Acciones */
        .filtros-acciones {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .select-filtro {
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid var(--border-light);
            background: #f8fafc;
            color: var(--text-main);
            font-weight: 600;
            font-size: 0.9rem;
            outline: none;
            cursor: pointer;
            transition: border-color 0.2s;
        }

        .select-filtro:focus {
            border-color: var(--primary);
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

        /* Botón superior de Nuevo Usuario */
        .btn-primary-custom {
            background: var(--primary);
            color: white;
            padding: 10px 18px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
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

        /* Badges de Estado, Roles y Clases */
        .badge-rol {
            background: #ede9fe;
            color: var(--primary);
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: capitalize;
        }

        .badge-clase {
            background: #e0f2fe;
            color: #0284c7;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .badge-activo {
            background: #dcfce7;
            color: #16a34a;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .badge-inactivo {
            background: #fee2e2;
            color: #dc2626;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
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

        .btn-editar {
            background: #e0f2fe;
            color: #0284c7;
        }

        .btn-editar:hover {
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
                    <a href="index.php" class="boton-menu active">
                        <i class='bx bx-group'></i> <?= $es_admin ? 'Ver Usuarios' : 'Mis Estudiantes' ?>
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
                        <a href="<?= $es_admin ? '../Administrador/index.php' : '../Profesor/index.php' ?>" style="color: var(--text-muted); font-size: 1.5rem; display: flex; align-items: center;" title="Volver">
                            <i class='bx bx-arrow-back'></i>
                        </a>
                        <h1 class="pagina-titulo"><i class='bx bx-group'></i> <?= $es_admin ? 'Gestión de Usuarios' : 'Mis Estudiantes Inscritos' ?></h1>
                    </div>

                    <div class="filtros-acciones">
                        <?php if ($es_admin) { ?>
                            <!-- Selector de Filtro por Rol -->
                            <form method="GET" action="index.php" style="margin: 0; display: flex; align-items: center; gap: 8px;">
                                <select name="filtro_rol" class="select-filtro" onchange="this.form.submit()">
                                    <option value="todos" <?= $filtro_rol === 'todos' ? 'selected' : '' ?>>👤 Todos los roles</option>
                                    <option value="estudiante" <?= $filtro_rol === 'estudiante' ? 'selected' : '' ?>>🎓 Solo Estudiantes</option>
                                    <option value="profesor" <?= $filtro_rol === 'profesor' ? 'selected' : '' ?>>👨‍🏫 Solo Profesores</option>
                                    <option value="administrador" <?= $filtro_rol === 'administrador' ? 'selected' : '' ?>>🛡️ Solo Administradores</option>
                                </select>
                            </form>
                            
                            <a href="crear.php" class="btn-primary-custom">
                                <i class='bx bx-user-plus' style="font-size: 1.1rem;"></i> Nuevo Usuario
                            </a>
                        <?php } ?>
                    </div>
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
                                <th>Nombre</th>
                                <th>Correo</th>
                                <?php if ($es_admin) { ?>
                                    <th>Rol</th>
                                <?php } else { ?>
                                    <th>Clase</th>
                                <?php } ?>
                                <th>Estado</th>
                                <th>Registrado</th>
                                <?php if ($es_admin) { ?><th>Acciones</th><?php } ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($usuarios->num_rows === 0) { ?>
                                <tr><td colspan="<?= $es_admin ? 6 : 5 ?>" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                    <?= $es_admin ? 'No hay usuarios registrados con este filtro.' : 'Todavía no hay estudiantes inscritos en tus clases.' ?>
                                </td></tr>
                            <?php } ?>

                            <?php while ($u = $usuarios->fetch_assoc()) { ?>
                                <tr>
                                    <td style="font-weight: 700;"><?= htmlspecialchars($u['nombre']) ?></td>
                                    <td><span style="color: var(--text-muted);"><?= htmlspecialchars($u['correo']) ?></span></td>
                                    
                                    <?php if ($es_admin) { ?>
                                        <td><span class="badge-rol"><?= htmlspecialchars(ucfirst($u['rol'])) ?></span></td>
                                    <?php } else { ?>
                                        <td><span class="badge-clase"><i class='bx bx-chalkboard'></i> <?= htmlspecialchars($u['clase_nombre'] ?? 'Sin clase') ?></span></td>
                                    <?php } ?>

                                    <td>
                                        <?php if ($u['activo']) { ?>
                                            <span class="badge-activo">Activo</span>
                                        <?php } else { ?>
                                            <span class="badge-inactivo">Inactivo</span>
                                        <?php } ?>
                                    </td>
                                    <td style="color: var(--text-muted); font-size: 0.9rem;"><?= htmlspecialchars($u['creado_en']) ?></td>
                                    
                                    <?php if ($es_admin) { ?>
                                    <td>
                                        <div class="acciones-td">
                                            <a href="editar.php?id=<?= (int)$u['id'] ?>" class="btn-accion btn-editar" title="Editar Usuario">
                                                <i class='bx bx-edit-alt'></i>
                                            </a>
                                            <a href="#" onclick="confirmarAccion('eliminar.php?id=<?= (int)$u['id'] ?>&csrf_token=<?= urlencode($token) ?>', '¿Seguro que quieres borrar este usuario?'); return false;" class="btn-accion btn-eliminar" title="Eliminar Usuario">
                                                <i class='bx bx-trash'></i>
                                            </a>
                                        </div>
                                    </td>
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
    <script src="../js/confirmar_accion.js"></script> <!-- Ajustado al nombre común del script -->
</body>
</html>