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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <title>Editar Pregunta - Edutrivia</title>
    <style>
        :root {
            --j-purple-dark: #1e1b4b;
            --j-purple: #6366f1;
            --j-purple-light: #818cf8;
            --j-green: #10b981;
            --j-red: #ef4444;
            --j-orange: #f59e0b;
        }

        * {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            box-sizing: border-box;
        }

        body.fondo {
            background: radial-gradient(circle at top left, #312e81 0%, transparent 55%),
                        radial-gradient(circle at bottom right, #3730a3 0%, transparent 50%),
                        linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%) !important;
            min-height: 100vh;
            padding: 30px 20px;
            color: #f8fafc;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .contenedor-gestion {
            width: 100%;
            max-width: 650px;
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 28px;
            padding: 35px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .back-link {
            color: #94a3b8;
            font-size: 1.4rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: color 0.2s;
            width: fit-content;
        }

        .back-link:hover {
            color: white;
        }

        .pagina-titulo {
            font-size: 1.8rem !important;
            font-weight: 800 !important;
            color: #ffffff !important;
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0 !important;
        }

        .pagina-titulo i {
            color: var(--j-purple-light);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid var(--j-red);
            color: #fca5a5;
            padding: 12px 18px;
            border-radius: 14px;
            font-weight: 600;
            text-align: center;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        label {
            font-size: 0.85rem;
            font-weight: 700;
            color: #cbd5e1;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: -6px;
        }

        .name_input, select {
            width: 100%;
            height: 48px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            color: white;
            padding: 0 16px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .name_input:focus, select:focus {
            border-color: var(--j-purple-light);
        }

        select option {
            background: #1e1b4b;
            color: white;
        }

        .btn-principal {
            background: var(--j-purple);
            color: white;
            border: none;
            border-radius: 14px;
            padding: 14px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-principal:hover {
            background: #4f46e5;
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="fondo">

<div class="contenedor-gestion">

    <div>
        <a href="index.php?mazo_id=<?= $mazo_id ?>" class="back-link" title="Volver al Mazo">
            <i class='bx bx-arrow-back'></i> Volver
        </a>
    </div>

    <div>
        <h1 class="pagina-titulo"><i class='bx bx-edit'></i> Editar Pregunta</h1>
    </div>

    <?php if ($error) { ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
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
                <option value="<?= $valor ?>" <?= $p['puntaje'] == $valor ? 'selected' : '' ?>><?= $valor ?> Puntos</option>
            <?php } ?>
        </select>

        <button class="btn-principal" type="submit">
            <i class='bx bx-save' style="font-size: 1.2rem;"></i> Guardar Cambios
        </button>
    </form>

</div>

</body>
</html>