<?php
session_start();
include("../conexion.php");
require_once("../csrf.php");

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['administrador', 'profesor'])) {
    header("Location: ../login.php");
    exit();
}

$clase_id = (int)($_GET['id'] ?? 0);

if ($clase_id <= 0) {
    header("Location: index.php");
    exit();
}

/* Traer la clase y validar que sea del profesor (o que sea admin) */
$stmt = $conexion->prepare("
    SELECT c.id, c.nombre, c.codigo, c.profesor_id, u.nombre AS profesor_nombre
    FROM clases c
    INNER JOIN usuarios u ON u.id = c.profesor_id
    WHERE c.id = ?
");
$stmt->bind_param("i", $clase_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$clase = $res->fetch_assoc();

if ($_SESSION['rol'] === 'profesor' && $clase['profesor_id'] !== $_SESSION['usuario_id']) {
    $_SESSION['error'] = "No tienes permiso para ver esa clase";
    header("Location: index.php");
    exit();
}

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
$token = csrf_token();

/* Mazos ya asignados a esta clase */
$stmt = $conexion->prepare("
    SELECT cm.id AS asignacion_id, m.id AS mazo_id, m.nombre, cm.cantidad_preguntas,
           (SELECT COUNT(*) FROM preguntas p WHERE p.mazo_id = m.id) AS total_preguntas
    FROM clase_mazos cm
    INNER JOIN mazos m ON m.id = cm.mazo_id
    WHERE cm.clase_id = ?
    ORDER BY cm.asignado_en DESC
");
$stmt->bind_param("i", $clase_id);
$stmt->execute();
$mazos_asignados = $stmt->get_result();

/* Mazos del profesor que TODAVÍA no están asignados a esta clase */
$stmt = $conexion->prepare("
    SELECT m.id, m.nombre, (SELECT COUNT(*) FROM preguntas p WHERE p.mazo_id = m.id) AS total_preguntas
    FROM mazos m
    WHERE m.profesor_id = ?
      AND m.id NOT IN (SELECT mazo_id FROM clase_mazos WHERE clase_id = ?)
    ORDER BY m.nombre ASC
");
$stmt->bind_param("ii", $clase['profesor_id'], $clase_id);
$stmt->execute();
$mazos_disponibles = $stmt->get_result();

/* Alumnos inscritos, con su mejor puntaje por juego y su estado de acceso actual */
$stmt = $conexion->prepare("
    SELECT u.id, u.nombre, u.correo,
           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = ? AND r.juego = 'jeopardy') AS mejor_jeopardy,
           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = ? AND r.juego = 'ahorcado') AS mejor_ahorcado,
           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = ? AND r.juego = 'carrera') AS mejor_carrera,
           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = ? AND r.juego = 'memorama') AS mejor_memorama,
           (SELECT MIN(r.tiempo_segundos) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = ? AND r.juego = 'memorama' AND r.tiempo_segundos IS NOT NULL) AS mejor_tiempo_memorama,
           (SELECT habilitado FROM accesos_juego a WHERE a.clase_id = ? AND a.estudiante_id = u.id AND a.juego = 'jeopardy') AS acceso_jeopardy,
           (SELECT habilitado FROM accesos_juego a WHERE a.clase_id = ? AND a.estudiante_id = u.id AND a.juego = 'ahorcado') AS acceso_ahorcado,
           (SELECT habilitado FROM accesos_juego a WHERE a.clase_id = ? AND a.estudiante_id = u.id AND a.juego = 'carrera') AS acceso_carrera,
           (SELECT habilitado FROM accesos_juego a WHERE a.clase_id = ? AND a.estudiante_id = u.id AND a.juego = 'memorama') AS acceso_memorama
    FROM inscripciones i
    INNER JOIN usuarios u ON u.id = i.estudiante_id
    WHERE i.clase_id = ?
    ORDER BY u.nombre ASC
");
$stmt->bind_param("iiiiiiiiii", $clase_id, $clase_id, $clase_id, $clase_id, $clase_id, $clase_id, $clase_id, $clase_id, $clase_id, $clase_id);
$stmt->execute();
$alumnos = $stmt->get_result();
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
    <title><?= htmlspecialchars($clase['nombre']) ?> - Edutrivia</title>
    <style>
        :root {
            --j-purple-dark: #1e1b4b;
            --j-purple: #6366f1;
            --j-purple-light: #818cf8;
            --j-green: #10b981;
            --j-red: #ef4444;
            --j-orange: #f59e0b;
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
            max-width: 1250px;
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

        .sub-info {
            color: #cbd5e1;
            font-size: 1rem;
            margin: -10px 0 10px 0;
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .sub-info b {
            color: #ffb703;
        }

        /* Alertas */
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

        /* Tarjeta de sección unificada */
        .seccion-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 20px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* Contenedor de controles masivos modernizado */
        .control-masivo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 14px;
        }

        .juego-control-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: center;
            text-align: center;
        }

        .juego-control-card h4 {
            margin: 0;
            font-size: 1.05rem;
            color: #ffffff;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-grupo-mini {
            display: flex;
            gap: 8px;
            width: 100%;
        }

        .btn-accion-mini {
            flex: 1;
            padding: 8px 10px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-habilitar {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.4);
        }

        .btn-habilitar:hover {
            background: var(--j-green);
            color: #fff;
        }

        .btn-deshabilitar {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.4);
        }

        .btn-deshabilitar:hover {
            background: var(--j-red);
            color: #fff;
        }

        /* Tabla moderna */
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
            font-size: 0.9rem;
            white-space: nowrap;
        }

        th {
            background: rgba(255, 255, 255, 0.06);
            color: #94a3b8;
            font-weight: 700;
            padding: 14px 16px;
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.05em;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            text-align: center;
        }

        th:first-child, th:nth-child(2) {
            text-align: left;
        }

        td {
            padding: 14px 16px;
            color: #f1f5f9;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-weight: 500;
            text-align: center;
        }

        td:first-child, td:nth-child(2) {
            text-align: left;
            font-weight: 600;
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.03);
        }

        .badge-puntaje {
            background: rgba(99, 102, 241, 0.15);
            color: #818cf8;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .badge-tiempo {
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .estado-acceso {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            font-size: 0.82rem;
        }

        .estado-acceso a {
            color: #f43f5e;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.78rem;
        }

        .estado-acceso a:hover {
            text-decoration: underline;
        }

        .estado-acceso .dar-acceso {
            color: #34d399;
        }

        /* Formularios y botones de profesor */
        form.asignar-form {
            display: flex;
            gap: 12px;
            align-items: flex-end;
            flex-wrap: wrap;
            background: rgba(255, 255, 255, 0.03);
            padding: 18px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        form.asignar-form label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #cbd5e1;
            display: block;
            margin-bottom: 6px;
        }

        form.asignar-form select, 
        form.asignar-form input[type="number"] {
            height: 44px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            color: white;
            padding: 0 14px;
            font-size: 0.95rem;
            outline: none;
        }

        form.asignar-form select:focus, 
        form.asignar-form input[type="number"]:focus {
            border-color: var(--j-purple-light);
        }

        .btn-principal {
            background: var(--j-purple);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 0 20px;
            height: 44px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }

        .btn-principal:hover {
            background: #4f46e5;
            transform: translateY(-2px);
        }

        .back-link {
            color: #94a3b8;
            font-size: 1.4rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: white;
        }

        /* Estilo SweetAlert acorde */
        .swal2-popup {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            border-radius: 24px !important;
            background: #1e1b4b !important;
            color: #fff !important;
        }
        .swal2-title {
            color: #fff !important;
        }
        .swal2-html-container {
            color: #c7d2fe !important;
        }
    </style>
</head>
<body class="fondo">

<div class="contenedor-gestion">

    <div>
        <a href="index.php" class="back-link" title="Volver a Clases">
            <i class='bx bx-arrow-back'></i> Volver
        </a>
    </div>

    <div>
        <h1 class="pagina-titulo"><i class='bx bx-chalkboard'></i> <?= htmlspecialchars($clase['nombre']) ?></h1>
        <div class="sub-info" style="margin-top: 8px;">
            <span>Código de acceso: <b><?= htmlspecialchars($clase['codigo']) ?></b></span>
            <?php if ($_SESSION['rol'] === 'administrador') { ?> 
                <span>· Profesor: <?= htmlspecialchars($clase['profesor_nombre']) ?></span> 
            <?php } ?>
        </div>
    </div>

    <?php if ($error) { ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php } ?>
    <?php if ($success) { ?>
        <div class="alert-success"><?= htmlspecialchars($success) ?></div>
    <?php } ?>

    <!-- ================= MAZOS ASIGNADOS ================= -->
    <div class="seccion-card">
        <h2 class="pagina-titulo" style="font-size: 1.3rem !important;"><i class='bx bx-collection'></i> Mazos activos en esta clase</h2>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Mazo</th>
                        <th>Preguntas usadas</th>
                        <?php if ($_SESSION['rol'] === 'profesor') { ?><th style="text-align: right;">Acciones</th><?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($mazos_asignados->num_rows === 0) { ?>
                        <tr><td colspan="3" style="text-align: center; color: var(--text-muted); padding: 30px;">Todavía no has activado ningún mazo en esta clase.</td></tr>
                    <?php } ?>

                    <?php while ($ma = $mazos_asignados->fetch_assoc()) { ?>
                        <tr>
                            <td><?= htmlspecialchars($ma['nombre']) ?></td>
                            <td><?= (int)$ma['cantidad_preguntas'] ?> de <?= (int)$ma['total_preguntas'] ?></td>
                            <?php if ($_SESSION['rol'] === 'profesor') { ?>
                            <td style="text-align: right;">
                                <a href="#" onclick="confirmarAccion('quitar_mazo.php?id=<?= (int)$ma['asignacion_id'] ?>&clase_id=<?= $clase_id ?>&csrf_token=<?= urlencode($token) ?>', '¿Quitar este mazo de la clase?'); return false;" style="color: #f87171; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class='bx bx-trash'></i> Quitar
                                </a>
                            </td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <?php if ($_SESSION['rol'] === 'profesor' && $mazos_disponibles->num_rows > 0) { ?>
        <form class="asignar-form" method="POST" action="asignar_mazo.php">
            <?= csrf_field() ?>
            <input type="hidden" name="clase_id" value="<?= $clase_id ?>">

            <div>
                <label>Activar un mazo nuevo</label>
                <select name="mazo_id" id="selectMazo" required>
                    <?php while ($m = $mazos_disponibles->fetch_assoc()) { ?>
                        <option value="<?= (int)$m['id'] ?>" data-total="<?= (int)$m['total_preguntas'] ?>">
                            <?= htmlspecialchars($m['nombre']) ?> (<?= (int)$m['total_preguntas'] ?> preguntas)
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div>
                <label>Preguntas a usar</label>
                <input type="number" name="cantidad_preguntas" id="inputCantidad" min="1" value="10" required style="width: 120px;">
            </div>

            <button class="btn-principal" type="submit"><i class='bx bx-plus'></i> Activar Mazo</button>
        </form>
        <?php } elseif ($_SESSION['rol'] === 'profesor') { ?>
            <p style="color:#94a3b8; font-size: 0.9rem; margin: 0;">✨ Ya activaste todos tus mazos disponibles en esta clase.</p>
        <?php } ?>
    </div>

    <!-- ================= ALUMNOS Y CONTROL MASIVO ================= -->
    <div class="seccion-card">
        <h2 class="pagina-titulo" style="font-size: 1.3rem !important;"><i class='bx bx-group'></i> Alumnos inscritos y Control de Acceso</h2>

        <?php if ($mazos_asignados->num_rows === 0) { ?>
            <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid var(--j-red); color: #fca5a5; border-radius: 14px; padding: 15px; font-weight: 600; text-align: center;">
                ⚠️ Esta clase todavía no tiene ningún mazo activado. Activa uno arriba antes de otorgar acceso a los juegos.
            </div>
        <?php } else { ?>
            <!-- Barra de control masivo profesional organizada por tarjetas de juegos -->
            <div class="control-masivo-grid">
                <!-- Jeopardy -->
                <div class="juego-control-card">
                    <h4><i class='bx bx-brain' style="color: #818cf8;"></i> Jeopardy</h4>
                    <div class="btn-grupo-mini">
                        <a href="acceso_masivo.php?clase_id=<?= $clase_id ?>&juego=jeopardy&accion=habilitar&csrf_token=<?= urlencode($token) ?>" class="btn-accion-mini btn-habilitar" title="Habilitar a todos"><i class='bx bx-lock-open-alt'></i> Todos</a>
                        <a href="#" onclick="confirmarAccion('acceso_masivo.php?clase_id=<?= $clase_id ?>&juego=jeopardy&accion=deshabilitar&csrf_token=<?= urlencode($token) ?>', '¿Quitar acceso a Jeopardy a TODOS los alumnos?'); return false;" class="btn-accion-mini btn-deshabilitar" title="Bloquear a todos"><i class='bx bx-lock-alt'></i> Bloquear</a>
                    </div>
                </div>

                <!-- Ahorcado -->
                <div class="juego-control-card">
                    <h4><i class='bx bx-joystick' style="color: #f59e0b;"></i> Ahorcado</h4>
                    <div class="btn-grupo-mini">
                        <a href="acceso_masivo.php?clase_id=<?= $clase_id ?>&juego=ahorcado&accion=habilitar&csrf_token=<?= urlencode($token) ?>" class="btn-accion-mini btn-habilitar" title="Habilitar a todos"><i class='bx bx-lock-open-alt'></i> Todos</a>
                        <a href="#" onclick="confirmarAccion('acceso_masivo.php?clase_id=<?= $clase_id ?>&juego=ahorcado&accion=deshabilitar&csrf_token=<?= urlencode($token) ?>', '¿Quitar acceso a Ahorcado a TODOS los alumnos?'); return false;" class="btn-accion-mini btn-deshabilitar" title="Bloquear a todos"><i class='bx bx-lock-alt'></i> Bloquear</a>
                    </div>
                </div>

                <!-- Carrera -->
                <div class="juego-control-card">
                    <h4><i class='bx bx-car' style="color: #38bdf8;"></i> Carrera</h4>
                    <div class="btn-grupo-mini">
                        <a href="acceso_masivo.php?clase_id=<?= $clase_id ?>&juego=carrera&accion=habilitar&csrf_token=<?= urlencode($token) ?>" class="btn-accion-mini btn-habilitar" title="Habilitar a todos"><i class='bx bx-lock-open-alt'></i> Todos</a>
                        <a href="#" onclick="confirmarAccion('acceso_masivo.php?clase_id=<?= $clase_id ?>&juego=carrera&accion=deshabilitar&csrf_token=<?= urlencode($token) ?>', '¿Quitar acceso a Carrera a TODOS los alumnos?'); return false;" class="btn-accion-mini btn-deshabilitar" title="Bloquear a todos"><i class='bx bx-lock-alt'></i> Bloquear</a>
                    </div>
                </div>

                <!-- Memorama -->
                <div class="juego-control-card">
                    <h4><i class='bx bxl-brain' style="color: #10b981;"></i> Memorama</h4>
                    <div class="btn-grupo-mini">
                        <a href="acceso_masivo.php?clase_id=<?= $clase_id ?>&juego=memorama&accion=habilitar&csrf_token=<?= urlencode($token) ?>" class="btn-accion-mini btn-habilitar" title="Habilitar a todos"><i class='bx bx-lock-open-alt'></i> Todos</a>
                        <a href="#" onclick="confirmarAccion('acceso_masivo.php?clase_id=<?= $clase_id ?>&juego=memorama&accion=deshabilitar&csrf_token=<?= urlencode($token) ?>', '¿Quitar acceso a Memorama a TODOS los alumnos?'); return false;" class="btn-accion-mini btn-deshabilitar" title="Bloquear a todos"><i class='bx bx-lock-alt'></i> Bloquear</a>
                    </div>
                </div>
            </div>
        <?php } ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nombre del Alumno</th>
                        <th>Correo</th>
                        <th>Jeopardy</th>
                        <th>Ahorcado</th>
                        <th>Carrera</th>
                        <th>Memorama</th>
                        <th>Tiempo Memorama</th>
                        <?php if ($_SESSION['rol'] === 'profesor' || $_SESSION['rol'] === 'administrador') { ?>
                        <th>Acceso Jeopardy</th>
                        <th>Acceso Ahorcado</th>
                        <th>Acceso Carrera</th>
                        <th>Acceso Memorama</th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($alumnos->num_rows === 0) { ?>
                        <tr><td colspan="11" style="text-align: center; color: var(--text-muted); padding: 40px;">Todavía no hay alumnos inscritos en esta clase.</td></tr>
                    <?php } ?>

                    <?php while ($a = $alumnos->fetch_assoc()) { ?>
                        <tr>
                            <td><?= htmlspecialchars($a['nombre']) ?></td>
                            <td><?= htmlspecialchars($a['correo']) ?></td>
                            <td>
                                <?php if ($a['mejor_jeopardy'] !== null) { ?>
                                    <span class="badge-puntaje"><?= (int)$a['mejor_jeopardy'] ?> pts</span>
                                <?php } else { ?> <span style="color: #64748b;">—</span> <?php } ?>
                            </td>
                            <td>
                                <?php if ($a['mejor_ahorcado'] !== null) { ?>
                                    <span class="badge-puntaje"><?= (int)$a['mejor_ahorcado'] ?> pts</span>
                                <?php } else { ?> <span style="color: #64748b;">—</span> <?php } ?>
                            </td>
                            <td>
                                <?php if ($a['mejor_carrera'] !== null) { ?>
                                    <span class="badge-puntaje"><?= (int)$a['mejor_carrera'] ?> pts</span>
                                <?php } else { ?> <span style="color: #64748b;">—</span> <?php } ?>
                            </td>
                            <td>
                                <?php if ($a['mejor_memorama'] !== null) { ?>
                                    <span class="badge-puntaje"><?= (int)$a['mejor_memorama'] ?> pts</span>
                                <?php } else { ?> <span style="color: #64748b;">—</span> <?php } ?>
                            </td>
                            <td>
                                <?php if ($a['mejor_tiempo_memorama'] !== null) { ?>
                                    <span class="badge-tiempo">⏱️ <?= sprintf('%02d:%02d', intdiv($a['mejor_tiempo_memorama'], 60), $a['mejor_tiempo_memorama'] % 60) ?></span>
                                <?php } else { ?> <span style="color: #64748b;">—</span> <?php } ?>
                            </td>

                            <!-- Accesos Individuales -->
                            <td>
                                <div class="estado-acceso">
                                    <?php if ((int)($a['acceso_jeopardy'] ?? 0) === 1) { ?>
                                        <span style="color: #34d399; font-weight: 700;">✅ Habilitado</span>
                                        <a href="#" onclick="confirmarAccion('quitar_acceso.php?clase_id=<?= $clase_id ?>&estudiante_id=<?= (int)$a['id'] ?>&juego=jeopardy&csrf_token=<?= urlencode($token) ?>', '¿Quitar acceso a Jeopardy de <?= htmlspecialchars(addslashes($a['nombre'])) ?>?'); return false;">❌ Quitar</a>
                                    <?php } else { ?>
                                        <span style="color: #94a3b8;">🔒 Bloqueado</span>
                                        <?php if ($mazos_asignados->num_rows > 0) { ?>
                                            <a href="otorgar_acceso.php?clase_id=<?= $clase_id ?>&estudiante_id=<?= (int)$a['id'] ?>&juego=jeopardy&csrf_token=<?= urlencode($token) ?>" class="dar-acceso">🔓 Permitir</a>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                            </td>
                            <td>
                                <div class="estado-acceso">
                                    <?php if ((int)($a['acceso_ahorcado'] ?? 0) === 1) { ?>
                                        <span style="color: #34d399; font-weight: 700;">✅ Habilitado</span>
                                        <a href="#" onclick="confirmarAccion('quitar_acceso.php?clase_id=<?= $clase_id ?>&estudiante_id=<?= (int)$a['id'] ?>&juego=ahorcado&csrf_token=<?= urlencode($token) ?>', '¿Quitar acceso a Ahorcado de <?= htmlspecialchars(addslashes($a['nombre'])) ?>?'); return false;">❌ Quitar</a>
                                    <?php } else { ?>
                                        <span style="color: #94a3b8;">🔒 Bloqueado</span>
                                        <?php if ($mazos_asignados->num_rows > 0) { ?>
                                            <a href="otorgar_acceso.php?clase_id=<?= $clase_id ?>&estudiante_id=<?= (int)$a['id'] ?>&juego=ahorcado&csrf_token=<?= urlencode($token) ?>" class="dar-acceso">🔓 Permitir</a>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                            </td>
                            <td>
                                <div class="estado-acceso">
                                    <?php if ((int)($a['acceso_carrera'] ?? 0) === 1) { ?>
                                        <span style="color: #34d399; font-weight: 700;">✅ Habilitado</span>
                                        <a href="#" onclick="confirmarAccion('quitar_acceso.php?clase_id=<?= $clase_id ?>&estudiante_id=<?= (int)$a['id'] ?>&juego=carrera&csrf_token=<?= urlencode($token) ?>', '¿Quitar acceso a Carrera de <?= htmlspecialchars(addslashes($a['nombre'])) ?>?'); return false;">❌ Quitar</a>
                                    <?php } else { ?>
                                        <span style="color: #94a3b8;">🔒 Bloqueado</span>
                                        <?php if ($mazos_asignados->num_rows > 0) { ?>
                                            <a href="otorgar_acceso.php?clase_id=<?= $clase_id ?>&estudiante_id=<?= (int)$a['id'] ?>&juego=carrera&csrf_token=<?= urlencode($token) ?>" class="dar-acceso">🔓 Permitir</a>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                            </td>
                            <td>
                                <div class="estado-acceso">
                                    <?php if ((int)($a['acceso_memorama'] ?? 0) === 1) { ?>
                                        <span style="color: #34d399; font-weight: 700;">✅ Habilitado</span>
                                        <a href="#" onclick="confirmarAccion('quitar_acceso.php?clase_id=<?= $clase_id ?>&estudiante_id=<?= (int)$a['id'] ?>&juego=memorama&csrf_token=<?= urlencode($token) ?>', '¿Quitar acceso a Memorama de <?= htmlspecialchars(addslashes($a['nombre'])) ?>?'); return false;">❌ Quitar</a>
                                    <?php } else { ?>
                                        <span style="color: #94a3b8;">🔒 Bloqueado</span>
                                        <?php if ($mazos_asignados->num_rows > 0) { ?>
                                            <a href="otorgar_acceso.php?clase_id=<?= $clase_id ?>&estudiante_id=<?= (int)$a['id'] ?>&juego=memorama&csrf_token=<?= urlencode($token) ?>" class="dar-acceso">🔓 Permitir</a>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
const selectMazo = document.getElementById('selectMazo');
const inputCantidad = document.getElementById('inputCantidad');

function actualizarMaximo() {
    if (!selectMazo) return;
    const opcion = selectMazo.options[selectMazo.selectedIndex];
    const total = parseInt(opcion.dataset.total, 10);
    inputCantidad.max = total;
    if (parseInt(inputCantidad.value, 10) > total) {
        inputCantidad.value = total;
    }
}

if (selectMazo) {
    selectMazo.addEventListener('change', actualizarMaximo);
    actualizarMaximo();
}
</script>

<script src="../js/confirmar.js"></script>

</body>
</html>