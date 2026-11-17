<?php
session_start();
include("../conexion.php");
require_once("../csrf.php");

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['administrador', 'profesor'])) {
    header("Location: ../login.php");
    exit();
}

$mazo_id = (int)($_GET['mazo_id'] ?? 0);

if ($mazo_id <= 0) {
    header("Location: ../Mazos/index.php");
    exit();
}

$stmt = $conexion->prepare("SELECT id, nombre, profesor_id FROM mazos WHERE id = ?");
$stmt->bind_param("i", $mazo_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header("Location: ../Mazos/index.php");
    exit();
}

$mazo = $res->fetch_assoc();

if ($_SESSION['rol'] === 'profesor' && $mazo['profesor_id'] !== $_SESSION['usuario_id']) {
    header("Location: ../Mazos/index.php");
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
    <title>Nueva Pregunta - Edutrivia</title>
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

        .sub-mazo {
            color: #cbd5e1;
            font-size: 0.95rem;
            margin: -10px 0 5px 0;
        }

        .sub-mazo b {
            color: #ffb703;
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
        <h1 class="pagina-titulo"><i class='bx bx-plus-circle'></i> Nueva Pregunta</h1>
        <p class="sub-mazo">Agregando a mazo: <b><?= htmlspecialchars($mazo['nombre']) ?></b></p>
    </div>

    <?php if ($error) { ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php } ?>

    <form method="POST" action="guardar.php">
        <?= csrf_field() ?>
        <input type="hidden" name="mazo_id" value="<?= $mazo_id ?>">

        <label>Categoría</label>
        <input class="name_input" type="text" name="categoria" placeholder="Ej. Historia" required>

        <label>Pregunta</label>
        <input class="name_input" type="text" name="pregunta" placeholder="Escribe la pregunta o pista" required>

        <label>Respuesta</label>
        <input class="name_input" type="text" name="respuesta" placeholder="Escribe la respuesta correcta" required>

        <label>Puntaje</label>
        <select name="puntaje" required>
            <option value="100">100 Puntos</option>
            <option value="200">200 Puntos</option>
            <option value="300">300 Puntos</option>
            <option value="400">400 Puntos</option>
            <option value="500">500 Puntos</option>
        </select>

        <button class="btn-principal" type="submit">
            <i class='bx bx-save' style="font-size: 1.2rem;"></i> Guardar Pregunta
        </button>
    </form>

</div>

</body>
</html>