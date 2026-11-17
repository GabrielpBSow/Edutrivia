<?php
session_start();
include("conexion.php");
require_once("csrf.php");

/* Si ya hay sesión activa, mándalo directo a su panel */
if (isset($_SESSION['usuario_id'])) {
    switch ($_SESSION['rol'] ?? '') {
        case 'administrador': header("Location: Administrador/index.php"); break;
        case 'profesor':      header("Location: Profesor/index.php");      break;
        case 'estudiante':    header("Location: Estudiante/index.php");    break;
        default: session_destroy();
    }
    if (isset($_SESSION['usuario_id'])) exit();
}

$max_intentos = 3;
$tiempo_bloqueo_minutos = 3;

$error_login = "";
$error_registro = "";

/* ============================================================
   LOGIN
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'login') {

    if (!csrf_validar($_POST['csrf_token'] ?? '')) {
        $error_login = "Sesión de formulario inválida, intenta de nuevo";
    } else {

        $correo = trim($_POST['correo'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';

        if ($correo === '' || $contrasena === '') {
            $error_login = "Faltan datos";
        } else {

            $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE correo = ? AND activo = 1");
            $stmt->bind_param("s", $correo);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows !== 1) {
                $error_login = "Correo o contraseña incorrectos";
            } else {

                $usuario = $result->fetch_assoc();

                $bloqueado = $usuario['bloqueado_hasta'] && strtotime($usuario['bloqueado_hasta']) > time();

                if ($bloqueado) {
                    $error_login = "Cuenta bloqueada temporalmente, intenta más tarde";
                } elseif (password_verify($contrasena, $usuario['contrasena'])) {

                    session_regenerate_id(true);

                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario'] = $usuario['nombre'];
                    $_SESSION['rol'] = $usuario['rol'];

                    $reset = $conexion->prepare("UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?");
                    $reset->bind_param("i", $usuario['id']);
                    $reset->execute();

                    switch ($usuario['rol']) {
                        case 'administrador': header("Location: Administrador/index.php"); break;
                        case 'profesor':      header("Location: Profesor/index.php");      break;
                        default:              header("Location: Estudiante/index.php");
                    }
                    exit();

                } else {

                    $nuevos_intentos = $usuario['intentos_fallidos'] + 1;

                    if ($nuevos_intentos >= $max_intentos) {
                        $upd = $conexion->prepare("UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?");
                        $upd->bind_param("ii", $tiempo_bloqueo_minutos, $usuario['id']);
                        $upd->execute();
                        $error_login = "Demasiados intentos. Cuenta bloqueada por $tiempo_bloqueo_minutos minutos";
                    } else {
                        $upd = $conexion->prepare("UPDATE usuarios SET intentos_fallidos = ? WHERE id = ?");
                        $upd->bind_param("ii", $nuevos_intentos, $usuario['id']);
                        $upd->execute();
                        $error_login = "Correo o contraseña incorrectos";
                    }
                }
            }
        }
    }
}

/* ============================================================
   REGISTRO
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'registro') {

    if (!csrf_validar($_POST['csrf_token'] ?? '')) {
        $error_registro = "Sesión de formulario inválida, intenta de nuevo";
    } else {

        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';

        if ($nombre === '' || $correo === '' || $contrasena === '') {
            $error_registro = "Todos los campos son obligatorios";
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error_registro = "El correo no es válido";
        } elseif (strlen($contrasena) < 8) {
            $error_registro = "La contraseña debe tener al menos 8 caracteres";
        } else {

            $check = $conexion->prepare("SELECT id FROM usuarios WHERE correo = ?");
            $check->bind_param("s", $correo);
            $check->execute();

            if ($check->get_result()->num_rows > 0) {
                $error_registro = "Ya existe una cuenta con ese correo";
            } else {

                $hash = password_hash($contrasena, PASSWORD_DEFAULT);

                $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, correo, contrasena, rol) VALUES (?, ?, ?, 'estudiante')");
                $stmt->bind_param("sss", $nombre, $correo, $hash);

                if ($stmt->execute()) {
                    session_regenerate_id(true);
                    $_SESSION['usuario_id'] = $conexion->insert_id;
                    $_SESSION['usuario'] = $nombre;
                    $_SESSION['rol'] = 'estudiante';
                    header("Location: Estudiante/index.php");
                    exit();
                } else {
                    $error_registro = "Error al crear la cuenta, intenta de nuevo";
                }
            }
        }
    }
}

$token = csrf_token();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" type="img/png" href="img/LogoEdutrivia (1).png">
    <link rel="stylesheet" href="css/app.css">
    <title>Edutrivia — Acceso</title>
    <style>
        * {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
        }
        .btn-volver-home {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            padding: 10px 16px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            z-index: 1000;
            transition: all 0.2s;
        }
        .btn-volver-home:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="pantalla-login">

    <!-- Botón para volver al inicio corregido a index.php -->
    <a href="principal.php" class="btn-volver-home">
        <i class='bx bx-arrow-back'></i> Volver al Inicio
    </a>

    <!-- Formulario de Registro (Inicia oculto con la clase 'hide') -->
    <div class="container-form register hide" id="formRegistro">
        <div class="information">
            <div class="info-childs">
                <h2>¡¡Bienvenido!!</h2>
                <p>Si ya tienes una cuenta, ¡Inicia Sesión aquí!</p>
                <input type="button" value="Iniciar Sesión" id="sign-in">
            </div>
        </div>

        <div class="form-information">
            <div class="form-information-childs">
                <h2>Crear una Cuenta</h2>
                <p>Regístrate como estudiante</p>

                <?php if ($error_registro) { ?>
                    <p style="color:#ef4444; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 8px; font-weight:600; font-size:0.9rem;"><?= htmlspecialchars($error_registro) ?></p>
                <?php } ?>

                <form class="form" method="POST" autocomplete="off">
                    <?= csrf_field() ?>
                    <input type="hidden" name="accion" value="registro">

                    <label>
                        <i class='bx bx-user'></i>
                        <input type="text" name="nombre" placeholder="Nombre Completo" required>
                    </label>
                    <label>
                        <i class='bx bx-envelope'></i>
                        <input type="email" name="correo" placeholder="Correo electrónico" required>
                    </label>
                    <label>
                        <i class='bx bxs-lock'></i>
                        <input type="password" name="contrasena" placeholder="Contraseña (mínimo 8 caracteres)" minlength="8" required>
                    </label>

                    <input type="submit" value="Registrar">
                </form>
            </div>
        </div>
    </div>

    <!-- Formulario de Login (Se muestra por defecto al entrar) -->
    <div class="container-form login" id="formLoginBox">
        <div class="information">
            <div class="info-childs">
                <h2>¡Qué Bueno Verte de Nuevo!</h2>
                <p>Si aún no tienes una cuenta, ¡Regístrate aquí!</p>
                <input type="button" value="Registrarse" id="sign-up">
            </div>
        </div>

        <div class="form-information">
            <div class="form-information-childs">
                <h2>Iniciar Sesión</h2>
                <p>Accede a tu panel de Edutrivia</p>

                <?php if ($error_login) { ?>
                    <p style="color:#ef4444; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 8px; font-weight:600; font-size:0.9rem;"><?= htmlspecialchars($error_login) ?></p>
                <?php } ?>

                <form class="form" method="POST" autocomplete="off">
                    <?= csrf_field() ?>
                    <input type="hidden" name="accion" value="login">

                    <label>
                        <i class='bx bx-envelope'></i>
                        <input type="email" name="correo" placeholder="Correo electrónico" required>
                    </label>
                    <label>
                        <i class='bx bxs-lock'></i>
                        <input type="password" name="contrasena" placeholder="Contraseña" required>
                    </label>

                    <input type="submit" value="Iniciar Sesión">
                </form>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>