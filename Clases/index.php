<?php
session_start();
include("../conexion.php");
require_once("../csrf.php");

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit();
}

$rol = $_SESSION['rol'];
$uid = $_SESSION['usuario_id'];
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
$token = csrf_token();

$panel_regreso = match ($rol) {
    'administrador' => '../Administrador/index.php',
    'profesor'      => '../Profesor/index.php',
    default         => '../Estudiante/index.php',
};

if ($rol === 'profesor') {
    /* El profesor ve SOLO las clases que él creó */
    $stmt = $conexion->prepare("
        SELECT c.id, c.nombre, c.codigo, c.creado_en,
               (SELECT COUNT(*) FROM inscripciones i WHERE i.clase_id = c.id) AS total_alumnos
        FROM clases c
        WHERE c.profesor_id = ?
        ORDER BY c.creado_en DESC
    ");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $clases = $stmt->get_result();

} elseif ($rol === 'estudiante') {
    /* El estudiante ve solo las clases a las que se ha unido, junto con su estado de acceso */
    $stmt = $conexion->prepare("
        SELECT c.id, c.nombre, c.codigo, u.nombre AS profesor_nombre,
               (SELECT habilitado FROM accesos_juego a WHERE a.clase_id = c.id AND a.estudiante_id = ? AND a.juego = 'jeopardy') AS acceso_jeopardy,
               (SELECT habilitado FROM accesos_juego a WHERE a.clase_id = c.id AND a.estudiante_id = ? AND a.juego = 'ahorcado') AS acceso_ahorcado,
               (SELECT habilitado FROM accesos_juego a WHERE a.clase_id = c.id AND a.estudiante_id = ? AND a.juego = 'carrera') AS acceso_carrera,
               (SELECT habilitado FROM accesos_juego a WHERE a.clase_id = c.id AND a.estudiante_id = ? AND a.juego = 'memorama') AS acceso_memorama,
               (SELECT habilitado FROM accesos_juego a WHERE a.clase_id = c.id AND a.estudiante_id = ? AND a.juego = 'crucigrama') AS acceso_crucigrama
        FROM inscripciones i
        INNER JOIN clases c ON c.id = i.clase_id
        INNER JOIN usuarios u ON u.id = c.profesor_id
        WHERE i.estudiante_id = ?
        ORDER BY i.fecha_inscripcion DESC
    ");
    $stmt->bind_param("iiiiii", $uid, $uid, $uid, $uid, $uid, $uid);
    $stmt->execute();
    $clases = $stmt->get_result();

} else {
    /* El administrador ve TODAS las clases del sistema */
    $clases = $conexion->query("
        SELECT c.id, c.nombre, c.codigo, c.creado_en, u.nombre AS profesor_nombre,
               (SELECT COUNT(*) FROM inscripciones i WHERE i.clase_id = c.id) AS total_alumnos
        FROM clases c
        INNER JOIN usuarios u ON u.id = c.profesor_id
        ORDER BY c.creado_en DESC
    ");
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
    <title>Mis Clases - Edutrivia</title>
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

        /* Formulario unirse a clase (Estudiante) */
        .form-unirse {
            background: #f8fafc;
            padding: 20px;
            border-radius: 16px;
            border: 1px solid var(--border-light);
            display: flex;
            gap: 15px;
            align-items: flex-end;
            margin-bottom: 10px;
        }

        .form-unirse .campo-grupo {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-unirse label {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .name_input {
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid var(--border-light);
            font-size: 1rem;
            background: white;
            outline: none;
            transition: border-color 0.2s;
        }

        .name_input:focus {
            border-color: var(--primary);
        }

        .btn-custom {
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-custom:hover {
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

        /* Botones y badges en tabla */
        .badge-codigo {
            background: #ede9fe;
            color: var(--primary);
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.05em;
        }

        .btn-ver-alumnos {
            background: #e0f2fe;
            color: #0284c7;
            padding: 8px 14px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-ver-alumnos:hover {
            background: #bae6fd;
            transform: translateY(-1px);
        }

        /* Botones de juegos para estudiantes */
        .juegos-grupo {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .juego-link {
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

        .juego-activo {
            background: #dcfce7;
            color: #16a34a;
        }

        .juego-activo:hover {
            background: #bbf7d0;
            transform: scale(1.08);
        }

        .juego-bloqueado {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
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
            .form-unirse {
                flex-direction: column;
                align-items: stretch;
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
                    <?php if ($rol === 'administrador') { ?>
                        <a href="../Administrador/index.php" class="boton-menu"><i class='bx bx-home-alt-2'></i> Inicio</a>
                    <?php } elseif ($rol === 'profesor') { ?>
                        <a href="../Profesor/index.php" class="boton-menu"><i class='bx bx-home-alt-2'></i> Inicio</a>
                        <a href="../Mazos/index.php" class="boton-menu"><i class='bx bx-layer'></i> Mis Mazos</a>
                    <?php } else { ?>
                        <a href="../Estudiante/index.php" class="boton-menu"><i class='bx bx-home-alt-2'></i> Inicio</a>
                    <?php } ?>

                    <a href="index.php" class="boton-menu active">
                        <i class='bx bx-chalkboard'></i> <?= $rol === 'estudiante' ? 'Mis Clases' : 'Gestión de Clases' ?>
                    </a>

                    <?php if ($rol === 'profesor' || $rol === 'administrador') { ?>
                        <a href="../Usuarios/index.php" class="boton-menu"><i class='bx bx-group'></i> Ver Usuarios</a>
                        <a href="../Reportes/index.php" class="boton-menu"><i class='bx bx-bar-chart-alt-2'></i> Reportes</a>
                    <?php } ?>
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
                        <h1 class="pagina-titulo"><i class='bx bx-chalkboard'></i> <?= $rol === 'estudiante' ? 'Mis Clases Inscritas' : 'Gestión de Clases' ?></h1>
                    </div>

                    <?php if ($rol === 'profesor') { ?>
                        <a href="crear.php" class="btn-custom">
                            <i class='bx bx-plus-circle' style="font-size: 1.2rem;"></i> Nueva Clase
                        </a>
                    <?php } ?>
                </div>

                <?php if ($error) { ?>
                    <div class="alert-error"><i class='bx bx-error-circle'></i> <?= htmlspecialchars($error) ?></div>
                <?php } ?>
                <?php if ($success) { ?>
                    <div class="alert-success"><i class='bx bx-check-circle'></i> <?= htmlspecialchars($success) ?></div>
                <?php } ?>

                <?php if ($rol === 'estudiante') { ?>
                    <!-- Formulario Moderno para Unirse a una Clase -->
                    <form method="POST" action="unirse.php" class="form-unirse">
                        <?= csrf_field() ?>
                        <div class="campo-grupo">
                            <label><i class='bx bx-key'></i> Código de la clase</label>
                            <input class="name_input" type="text" name="codigo" placeholder="Ej. MAT2A7X" maxlength="10" style="text-transform:uppercase;" required>
                        </div>
                        <button class="btn-custom" type="submit">
                            <i class='bx bx-log-in-circle'></i> Unirme a la clase
                        </button>
                    </form>
                <?php } ?>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Clase</th>
                                <?php if ($rol !== 'estudiante') { ?><th>Código</th><?php } ?>
                                <?php if ($rol === 'administrador' || $rol === 'estudiante') { ?><th>Profesor</th><?php } ?>
                                <?php if ($rol !== 'estudiante') { ?><th>Alumnos</th><?php } ?>
                                <?php if ($rol === 'profesor') { ?><th>Acciones</th><?php } ?>
                                <?php if ($rol === 'estudiante') { ?><th>Zona de Juegos</th><?php } ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($clases->num_rows === 0) { ?>
                                <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                    <?= $rol === 'estudiante' ? 'Todavía no te has unido a ninguna clase.' : 'Todavía no hay clases registradas.' ?>
                                </td></tr>
                            <?php } ?>

                            <?php while ($c = $clases->fetch_assoc()) { ?>
                                <tr>
                                    <td style="font-weight: 700;"><?= htmlspecialchars($c['nombre']) ?></td>
                                    
                                    <?php if ($rol !== 'estudiante') { ?>
                                        <td><span class="badge-codigo"><?= htmlspecialchars($c['codigo']) ?></span></td>
                                    <?php } ?>

                                    <?php if ($rol === 'administrador' || $rol === 'estudiante') { ?>
                                        <td><span style="color: var(--text-muted);"><i class='bx bx-user'></i> <?= htmlspecialchars($c['profesor_nombre']) ?></span></td>
                                    <?php } ?>

                                    <?php if ($rol !== 'estudiante') { ?>
                                        <td><span style="font-weight: 700; color: var(--primary);"><?= (int)$c['total_alumnos'] ?> alumnos</span></td>
                                    <?php } ?>

                                    <?php if ($rol === 'profesor') { ?>
                                        <td>
                                            <a href="ver.php?id=<?= (int)$c['id'] ?>" class="btn-ver-alumnos">
                                                <i class='bx bx-group'></i> Ver alumnos
                                            </a>
                                        </td>
                                    <?php } ?>

                                    <?php if ($rol === 'estudiante') { ?>
                                        <td>
                                            <div class="juegos-grupo">
                                                <!-- Jeopardy -->
                                                <?php if ((int)($c['acceso_jeopardy'] ?? 0) === 1) { ?>
                                                    <a href="../Juegos/jeopardy.php?clase_id=<?= (int)$c['id'] ?>" class="juego-link juego-activo" title="Jugar Jeopardy">🎯</a>
                                                <?php } else { ?>
                                                    <span class="juego-link juego-bloqueado" title="Tu profesor aún no te ha dado acceso a Jeopardy">🔒</span>
                                                <?php } ?>

                                                <!-- Ahorcado -->
                                                <?php if ((int)($c['acceso_ahorcado'] ?? 0) === 1) { ?>
                                                    <a href="../Juegos/ahorcado.php?clase_id=<?= (int)$c['id'] ?>" class="juego-link juego-activo" title="Jugar Ahorcado">💀</a>
                                                <?php } else { ?>
                                                    <span class="juego-link juego-bloqueado" title="Tu profesor aún no te ha dado acceso a Ahorcado">🔒</span>
                                                <?php } ?>

                                                <!-- Carrera -->
                                                <?php if ((int)($c['acceso_carrera'] ?? 0) === 1) { ?>
                                                    <a href="../Juegos/carrera.php?clase_id=<?= (int)$c['id'] ?>" class="juego-link juego-activo" title="Jugar Carrera de Conocimiento">🏍️</a>
                                                <?php } else { ?>
                                                    <span class="juego-link juego-bloqueado" title="Tu profesor aún no te ha dado acceso a Carrera">🔒</span>
                                                <?php } ?>

                                                <!-- Memorama -->
                                                <?php if ((int)($c['acceso_memorama'] ?? 0) === 1) { ?>
                                                    <a href="../Juegos/memorama.php?clase_id=<?= (int)$c['id'] ?>" class="juego-link juego-activo" title="Jugar Memorama">🃏</a>
                                                <?php } else { ?>
                                                    <span class="juego-link juego-bloqueado" title="Tu profesor aún no te ha dado acceso a Memorama">🔒</span>
                                                <?php } ?>

                                                <!-- Crucigrama -->
                                                <?php if ((int)($c['acceso_crucigrama'] ?? 0) === 1) { ?>
                                                    <a href="../Juegos/crucigrama.php?clase_id=<?= (int)$c['id'] ?>" class="juego-link juego-activo" title="Jugar Crucigrama Académico">🧩</a>
                                                <?php } else { ?>
                                                    <span class="juego-link juego-bloqueado" title="Tu profesor aún no te ha dado acceso a Crucigrama">🔒</span>
                                                <?php } ?>
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
</body>
</html>