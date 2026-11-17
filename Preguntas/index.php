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

/* Verificar que el mazo exista y (si eres profesor) que sea tuyo */
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
    $_SESSION['error'] = "No tienes permiso para ver ese mazo";
    header("Location: ../Mazos/index.php");
    exit();
}

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
$token = csrf_token();

$stmt = $conexion->prepare("SELECT * FROM preguntas WHERE mazo_id = ? ORDER BY categoria ASC, id DESC");
$stmt->bind_param("i", $mazo_id);
$stmt->execute();
$preguntas = $stmt->get_result();
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
    <title>Preguntas de <?= htmlspecialchars($mazo['nombre']) ?> - Edutrivia</title>
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
        }

        .contenedor-gestion {
            width: 100%;
            max-width: 1200px;
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 28px;
            padding: 35px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            gap: 25px;
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
            font-size: 2rem !important;
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

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid var(--j-green);
            color: #6ee7b7;
            padding: 12px 18px;
            border-radius: 14px;
            font-weight: 600;
            text-align: center;
        }

        .table-container {
            width: 100%;
            overflow-x: auto;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(15, 23, 42, 0.4);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.95rem;
        }

        th {
            background: rgba(255, 255, 255, 0.06);
            color: #94a3b8;
            font-weight: 700;
            padding: 14px 18px;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        td {
            padding: 16px 18px;
            color: #f1f5f9;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-weight: 500;
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.03);
        }

        .badge-categoria {
            background: rgba(99, 102, 241, 0.2);
            color: #818cf8;
            padding: 6px 12px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .badge-puntaje {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
            padding: 6px 12px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .acciones-grupo {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .acciones-grupo a {
            text-decoration: none;
            font-size: 1.1rem;
            transition: transform 0.2s;
            display: inline-flex;
        }

        .acciones-grupo a:hover {
            transform: scale(1.15);
        }

        .btn-wrap {
            display: flex;
            justify-content: flex-start;
        }

        .btn-principal {
            background: var(--j-purple);
            color: white;
            border: none;
            border-radius: 14px;
            padding: 12px 24px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-principal:hover {
            background: #4f46e5;
            transform: translateY(-2px);
        }

        .swal2-popup {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            border-radius: 24px !important;
            background: #1e1b4b !important;
            color: #fff !important;
        }
    </style>
</head>
<body class="fondo">

<div class="contenedor-gestion">

    <div>
        <a href="../Mazos/index.php" class="back-link" title="Volver a Mis Mazos">
            <i class='bx bx-arrow-back'></i> Volver
        </a>
    </div>

    <h1 class="pagina-titulo"><i class='bx bx-list-ul'></i> <?= htmlspecialchars($mazo['nombre']) ?></h1>

    <?php if ($error) { ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php } ?>
    <?php if ($success) { ?>
        <div class="alert-success"><?= htmlspecialchars($success) ?></div>
    <?php } ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th>Pregunta</th>
                    <th>Respuesta</th>
                    <th>Puntaje</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($preguntas->num_rows === 0) { ?>
                    <tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 40px;">Este mazo todavía no tiene preguntas agregadas.</td></tr>
                <?php } ?>

                <?php while ($p = $preguntas->fetch_assoc()) { ?>
                    <tr>
                        <td><span class="badge-categoria"><?= htmlspecialchars($p['categoria']) ?></span></td>
                        <td><?= htmlspecialchars($p['pregunta']) ?></td>
                        <td><strong style="color: #34d399;"><?= htmlspecialchars($p['respuesta']) ?></strong></td>
                        <td><span class="badge-puntaje"><?= (int)$p['puntaje'] ?> pts</span></td>
                        <td>
                            <div class="acciones-grupo">
                                <a href="editar.php?id=<?= (int)$p['id'] ?>" title="Editar pregunta"><i class='bx bx-edit-alt' style="color: #38bdf8;"></i></a>
                                <a href="#" onclick="confirmarAccion('eliminar.php?id=<?= (int)$p['id'] ?>&mazo_id=<?= $mazo_id ?>&csrf_token=<?= urlencode($token) ?>', '¿Seguro que quieres borrar esta pregunta?'); return false;" title="Eliminar pregunta"><i class='bx bx-trash' style="color: #f87171;"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="btn-wrap">
        <a href="crear.php?mazo_id=<?= $mazo_id ?>" class="btn-principal">
            <i class='bx bx-plus-circle' style="font-size: 1.2rem;"></i> Nueva Pregunta
        </a>
    </div>

</div>

<script src="../js/confirmar.js"></script>

</body>
</html>