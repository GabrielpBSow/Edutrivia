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
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
$token = csrf_token();

$usuarios = $conexion->query("SELECT id, nombre, correo, rol, activo, creado_en FROM usuarios ORDER BY nombre ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" type="img/png" href="../img/LogoEdutrivia (1).png">
    <link rel="stylesheet" href="../css/app.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Usuarios</title>
</head>
<body class="fondo">

<div class="contenedor-gestion">

    <a href="<?= $es_admin ? '../Administrador/index.php' : '../Profesor/index.php' ?>">
        <img class="img_before" src="../img/before_writh.png" alt="Volver" style="max-width:42px;">
    </a>

    <h1 class="pagina-titulo"><i class='bx bx-user'></i> <?= $es_admin ? 'Gestión de Usuarios' : 'Usuarios registrados' ?></h1>

    <?php if ($error) { ?>
        <p style="color:#ffb3b3; text-align:center; font-weight:bold;"><?= htmlspecialchars($error) ?></p>
    <?php } ?>
    <?php if ($success) { ?>
        <p style="color:#b3ffb3; text-align:center; font-weight:bold;"><?= htmlspecialchars($success) ?></p>
    <?php } ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Registrado</th>
                    <?php if ($es_admin) { ?><th>Acciones</th><?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php while ($u = $usuarios->fetch_assoc()) { ?>
                    <tr>
                        <td><?= htmlspecialchars($u['nombre']) ?></td>
                        <td><?= htmlspecialchars($u['correo']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($u['rol'])) ?></td>
                        <td><?= $u['activo'] ? 'Activo' : 'Inactivo' ?></td>
                        <td><?= htmlspecialchars($u['creado_en']) ?></td>
                        <?php if ($es_admin) { ?>
                        <td>
                            <a href="editar.php?id=<?= (int)$u['id'] ?>">✏️</a>
                            <a href="#" onclick="confirmarAccion('eliminar.php?id=<?= (int)$u['id'] ?>&csrf_token=<?= urlencode($token) ?>', '¿Seguro que quieres borrar este usuario?'); return false;">🗑️</a>
                        </td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <?php if ($es_admin) { ?>
    <div class="btn-wrap">
        <a href="crear.php" class="btn">➕ Nuevo Usuario</a>
    </div>
    <?php } ?>

</div>

<script src="../js/confirmar.js"></script>

</body>
</html>