<?php
session_start();
include("../conexion.php");
require_once("../csrf.php");

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['administrador', 'profesor'])) {
    header("Location: ../login.php");
    exit();
}

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    header("Location: ../Mazos/index.php");
    exit();
}

/* Traer la pregunta junto con el dueño del mazo, para validar permiso */
$stmt = $conexion->prepare("
    SELECT p.*, m.profesor_id
    FROM preguntas p
    INNER JOIN mazos m ON m.id = p.mazo_id
    WHERE p.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header("Location: ../Mazos/index.php");
    exit();
}

$p = $res->fetch_assoc();

if ($_SESSION['rol'] === 'profesor' && $p['profesor_id'] !== $_SESSION['usuario_id']) {
    header("Location: ../Mazos/index.php");
    exit();
}

$mazo_id = $p['mazo_id'];

/* ============================================================
   PROCESAR LA ACTUALIZACIÓN
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_validar($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Sesión de formulario inválida, intenta de nuevo";
        header("Location: editar.php?id=$id");
        exit();
    }

    $categoria = trim($_POST['categoria'] ?? '');
    $pregunta = trim($_POST['pregunta'] ?? '');
    $respuesta = trim($_POST['respuesta'] ?? '');
    $puntaje = (int)($_POST['puntaje'] ?? 0);

    if ($categoria === '' || $pregunta === '' || $respuesta === '' || $puntaje <= 0) {
        $_SESSION['error'] = "Todos los campos son obligatorios";
        header("Location: editar.php?id=$id");
        exit();
    }

    $stmt = $conexion->prepare("UPDATE preguntas SET categoria = ?, pregunta = ?, respuesta = ?, puntaje = ? WHERE id = ?");
    $stmt->bind_param("sssii", $categoria, $pregunta, $respuesta, $puntaje, $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Pregunta actualizada correctamente";
    } else {
        $_SESSION['error'] = "Error al actualizar la pregunta";
    }

    header("Location: index.php?mazo_id=$mazo_id");
    exit();
}

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
    <title>Editar Pregunta</title>
</head>
<body class="fondo">

<div class="contenedor-gestion">

    <a href="index.php?mazo_id=<?= $mazo_id ?>">
        <img class="img_before" src="../img/before_writh.png" alt="Volver" style="max-width:42px;">
    </a>

    <h1 class="pagina-titulo"><i class='bx bx-edit'></i> Editar Pregunta</h1>

    <?php if ($error) { ?>
        <p style="color:#ffb3b3; text-align:center; font-weight:bold;"><?= htmlspecialchars($error) ?></p>
    <?php } ?>

    <form method="POST" action="editar.php">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">

        <label>Categoría</label>
        <input class="name_input" type="text" name="categoria" value="<?= htmlspecialchars($p['categoria']) ?>" required>

        <label>Pregunta</label>
        <input class="name_input" type="text" name="pregunta" value="<?= htmlspecialchars($p['pregunta']) ?>" required>

        <label>Respuesta</label>
        <input class="name_input" type="text" name="respuesta" value="<?= htmlspecialchars($p['respuesta']) ?>" required>

        <label>Puntaje</label>
        <select name="puntaje" required>
            <?php foreach ([100, 200, 300, 400, 500] as $valor) { ?>
                <option value="<?= $valor ?>" <?= $p['puntaje'] == $valor ? 'selected' : '' ?>><?= $valor ?></option>
            <?php } ?>
        </select>

        <button class="btn" type="submit">Guardar Cambios</button>
    </form>

</div>

</body>
</html>