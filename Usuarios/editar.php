<?php
session_start();
include("../conexion.php");
require_once("../csrf.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../login.php");
    exit();
}

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit();
}

/* ============================================================
   PROCESAR LA ACTUALIZACIÓN
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_validar($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Sesión de formulario inválida, intenta de nuevo";
        header("Location: editar.php?id=$id");
        exit();
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $rol_nuevo = $_POST['rol'] ?? '';
    $activo_nuevo = ($_POST['activo'] ?? '1') === '1' ? 1 : 0;
    $roles_validos = ['administrador', 'profesor', 'estudiante'];

    if ($nombre === '' || !in_array($rol_nuevo, $roles_validos)) {
        $_SESSION['error'] = "Datos inválidos";
        header("Location: editar.php?id=$id");
        exit();
    }

    /* No puedes quitarte tu propio rol de admin ni desactivarte a ti mismo */
    if ($id === (int)$_SESSION['usuario_id'] && ($rol_nuevo !== 'administrador' || $activo_nuevo === 0)) {
        $_SESSION['error'] = "No puedes quitarte tu propio rol de administrador ni desactivar tu propia cuenta";
        header("Location: index.php");
        exit();
    }

    /* Verificar si esto dejaría al sistema sin ningún admin activo */
    $stmt = $conexion->prepare("SELECT rol FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $actual = $stmt->get_result()->fetch_assoc();

    $se_le_quita_admin = $actual['rol'] === 'administrador' && ($rol_nuevo !== 'administrador' || $activo_nuevo === 0);

    if ($se_le_quita_admin) {
        $stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'administrador' AND activo = 1 AND id != ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $otros_admins = $stmt->get_result()->fetch_assoc()['total'];

        if ($otros_admins == 0) {
            $_SESSION['error'] = "No puedes quitar el rol de administrador al único admin del sistema";
            header("Location: index.php");
            exit();
        }
    }

    $stmt = $conexion->prepare("UPDATE usuarios SET nombre = ?, rol = ?, activo = ? WHERE id = ?");
    $stmt->bind_param("ssii", $nombre, $rol_nuevo, $activo_nuevo, $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Usuario actualizado correctamente";
    } else {
        $_SESSION['error'] = "Error al actualizar el usuario";
    }

    header("Location: index.php");
    exit();
}

/* ============================================================
   MOSTRAR EL FORMULARIO PRECARGADO
   ============================================================ */
$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$u = $res->fetch_assoc();
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
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
    <title>Editar Usuario - Edutrivia</title>
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
            padding: 35px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--border-light);
            display: flex;
            flex-direction: column;
            gap: 20px;
            max-width: 700px;
            margin: 0 auto;
            width: 100%;
        }

        .header-acciones-top {
            display: flex;
            align-items: center;
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

        /* Alerta de Error */
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 14px 18px;
            border-radius: 14px;
            font-weight: 600;
            font-size: 0.95rem;
            border: 1px solid #fca5a5;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Formulario Estilizado */
        form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        form label {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .name_input, select.name_input {
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid var(--border-light);
            font-size: 1rem;
            background: #f8fafc;
            outline: none;
            transition: all 0.2s;
            color: var(--text-main);
            width: 100%;
        }

        .name_input:focus, select.name_input:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .name_input:disabled {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
        }

        .btn-submit {
            background: var(--primary);
            color: white;
            padding: 14px 24px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35);
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
            .contenedor-gestion {
                padding: 20px;
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
                    <a href="../Administrador/index.php" class="boton-menu">
                        <i class='bx bx-home-alt-2'></i> Inicio
                    </a>
                    <a href="../Mazos/index.php" class="boton-menu">
                        <i class='bx bx-layer'></i> Mis Mazos
                    </a>
                    <a href="../Clases/index.php" class="boton-menu">
                        <i class='bx bx-chalkboard'></i> Mis Clases
                    </a>
                    <a href="../Usuarios/index.php" class="boton-menu active">
                        <i class='bx bx-group'></i> Ver Usuarios
                    </a>
                    <a href="../Reportes/index.php" class="boton-menu">
                        <i class='bx bx-bar-chart-alt-2'></i> Reportes
                    </a>
                </nav>
            </div>

            <div class="sidebar-footer-container">
                <p class="texto-footer">Rol: Administrador</p>
                <a href="../logout.php" class="boton-menu">
                    <i class='bx bx-log-out'></i> Cerrar sesión
                </a>
            </div>
        </aside>

        <!-- Contenido Principal -->
        <main>
            <div class="contenedor-gestion">
                
                <div class="header-acciones-top">
                    <a href="index.php" style="color: var(--text-muted); font-size: 1.5rem; display: flex; align-items: center;" title="Volver">
                        <i class='bx bx-arrow-back'></i>
                    </a>
                    <h1 class="pagina-titulo"><i class='bx bx-edit-alt'></i> Editar Usuario</h1>
                </div>

                <?php if ($error) { ?>
                    <div class="alert-error"><i class='bx bx-error-circle'></i> <?= htmlspecialchars($error) ?></div>
                <?php } ?>

                <form method="POST" action="editar.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">

                    <div class="form-group">
                        <label><i class='bx bx-user'></i> Nombre completo</label>
                        <input class="name_input" type="text" name="nombre" value="<?= htmlspecialchars($u['nombre']) ?>" required autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label><i class='bx bx-envelope'></i> Correo electrónico (no editable)</label>
                        <input class="name_input" type="email" value="<?= htmlspecialchars($u['correo']) ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label><i class='bx bx-shield-quarter'></i> Rol del usuario</label>
                        <select name="rol" class="name_input" required>
                            <option value="estudiante" <?= $u['rol'] === 'estudiante' ? 'selected' : '' ?>>🎓 Estudiante</option>
                            <option value="profesor" <?= $u['rol'] === 'profesor' ? 'selected' : '' ?>>👨‍🏫 Profesor</option>
                            <option value="administrador" <?= $u['rol'] === 'administrador' ? 'selected' : '' ?>>🛡️ Administrador</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class='bx bx-toggle-left'></i> Estado de la cuenta</label>
                        <select name="activo" class="name_input" required>
                            <option value="1" <?= $u['activo'] == 1 ? 'selected' : '' ?>>🟢 Activo</option>
                            <option value="0" <?= $u['activo'] == 0 ? 'selected' : '' ?>>🔴 Inactivo</option>
                        </select>
                    </div>

                    <button class="btn-submit" type="submit">
                        <i class='bx bx-check-shield' style="font-size: 1.2rem;"></i> Guardar Cambios
                    </button>
                </form>

            </div>
        </main>
    </div>

    <script src="../js/menu2.js"></script>
</body>
</html>