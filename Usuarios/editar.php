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
    <title>Editar Usuario</title>
</head>
<body class="fondo">

    <a href="index.php">
        <img class="img_before" src="../img/before_writh.png" alt="Volver" style="max-width:60px;">
    </a>

    <h1 class="pagina-titulo">✏️ Editar Usuario</h1>

    <?php if ($error) { ?>
        <p style="color:#961818; text-align:center; font-weight:bold;"><?= htmlspecialchars($error) ?></p>
    <?php } ?>

    <form method="POST" action="editar.php">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">

        <label>Nombre completo</label>
        <input class="name_input" type="text" name="nombre" value="<?= htmlspecialchars($u['nombre']) ?>" required>

        <label>Correo (no editable)</label>
        <input class="name_input" type="email" value="<?= htmlspecialchars($u['correo']) ?>" disabled>

        <label>Rol</label>
        <select name="rol" required>
            <option value="estudiante" <?= $u['rol'] === 'estudiante' ? 'selected' : '' ?>>Estudiante</option>
            <option value="profesor" <?= $u['rol'] === 'profesor' ? 'selected' : '' ?>>Profesor</option>
            <option value="administrador" <?= $u['rol'] === 'administrador' ? 'selected' : '' ?>>Administrador</option>
        </select>

        <label>Estado</label>
        <select name="activo" required>
            <option value="1" <?= $u['activo'] == 1 ? 'selected' : '' ?>>Activo</option>
            <option value="0" <?= $u['activo'] == 0 ? 'selected' : '' ?>>Inactivo</option>
        </select>

        <br><br>
        <button class="btn" type="submit">Guardar Cambios</button>
    </form>

</body>
</html>