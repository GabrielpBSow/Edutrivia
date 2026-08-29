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

/* Mazos del profesor que TODAVÍA no están asignados a esta clase
   (para el <select> de "asignar mazo nuevo") */
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title><?= htmlspecialchars($clase['nombre']) ?></title>
</head>
<body class="fondo">

<div class="contenedor-gestion">

    <a href="index.php">
        <img class="img_before" src="../img/before_writh.png" alt="Volver" style="max-width:42px;">
    </a>

    <h1 class="pagina-titulo"><i class='bx bx-chalkboard'></i> <?= htmlspecialchars($clase['nombre']) ?></h1>
    <p style="color:#dcd0ea; text-align:center; margin-top:-1.5rem; margin-bottom:2rem;">
        Código: <b><?= htmlspecialchars($clase['codigo']) ?></b>
        <?php if ($_SESSION['rol'] === 'administrador') { ?> · Profesor: <?= htmlspecialchars($clase['profesor_nombre']) ?><?php } ?>
    </p>

    <?php if ($error) { ?>
        <p style="color:#ffb3b3; text-align:center; font-weight:bold;"><?= htmlspecialchars($error) ?></p>
    <?php } ?>
    <?php if ($success) { ?>
        <p style="color:#b3ffb3; text-align:center; font-weight:bold;"><?= htmlspecialchars($success) ?></p>
    <?php } ?>

    <!-- ================= MAZOS ASIGNADOS ================= -->
    <h2 class="pagina-titulo" style="font-size:1.3rem;"><i class='bx bx-collection'></i> Mazos activos en esta clase</h2>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Mazo</th>
                    <th>Preguntas usadas</th>
                    <?php if ($_SESSION['rol'] === 'profesor') { ?><th>Acciones</th><?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($mazos_asignados->num_rows === 0) { ?>
                    <tr><td colspan="3">Todavía no has activado ningún mazo en esta clase.</td></tr>
                <?php } ?>

                <?php while ($ma = $mazos_asignados->fetch_assoc()) { ?>
                    <tr>
                        <td><?= htmlspecialchars($ma['nombre']) ?></td>
                        <td><?= (int)$ma['cantidad_preguntas'] ?> de <?= (int)$ma['total_preguntas'] ?></td>
                        <?php if ($_SESSION['rol'] === 'profesor') { ?>
                        <td>
                            <a href="#" onclick="confirmarAccion('quitar_mazo.php?id=<?= (int)$ma['asignacion_id'] ?>&clase_id=<?= $clase_id ?>&csrf_token=<?= urlencode($token) ?>', '¿Quitar este mazo de la clase?'); return false;">🗑️</a>
                        </td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <?php if ($_SESSION['rol'] === 'profesor' && $mazos_disponibles->num_rows > 0) { ?>
    <form method="POST" action="asignar_mazo.php">
        <?= csrf_field() ?>
        <input type="hidden" name="clase_id" value="<?= $clase_id ?>">

        <label>Activar un mazo nuevo en esta clase</label>
        <select name="mazo_id" id="selectMazo" required>
            <?php while ($m = $mazos_disponibles->fetch_assoc()) { ?>
                <option value="<?= (int)$m['id'] ?>" data-total="<?= (int)$m['total_preguntas'] ?>">
                    <?= htmlspecialchars($m['nombre']) ?> (<?= (int)$m['total_preguntas'] ?> preguntas)
                </option>
            <?php } ?>
        </select>

        <label>¿Cuántas preguntas de ese mazo se usan aquí?</label>
        <input class="name_input" type="number" name="cantidad_preguntas" id="inputCantidad" min="1" value="10" required>

        <button class="btn" type="submit">Activar Mazo</button>
    </form>
    <?php } elseif ($_SESSION['rol'] === 'profesor') { ?>
        <p style="color:#dcd0ea; text-align:center;">Ya activaste todos tus mazos disponibles en esta clase.</p>
    <?php } ?>

    <!-- ================= ALUMNOS ================= -->
    <h2 class="pagina-titulo" style="font-size:1.3rem; margin-top:3rem;"><i class='bx bx-group'></i> Alumnos inscritos</h2>

    <?php if ($mazos_asignados->num_rows === 0) { ?>
        <p style="background:rgba(239,62,54,.15); border:1px solid #EF3E36; color:#ffdcda; text-align:center; font-weight:bold; border-radius:1rem; padding:.8rem 1rem; margin-bottom:1.2rem;">
            ⚠️ Esta clase todavía no tiene ningún mazo activado. Activa uno arriba antes de dar acceso a los juegos.
        </p>
    <?php } else { ?>
    <div class="btn-wrap" style="display:flex; gap:.6rem; flex-wrap:wrap; justify-content:center; margin-bottom:1.2rem;">
        <a class="btn" href="acceso_masivo.php?clase_id=<?= $clase_id ?>&juego=jeopardy&accion=habilitar&csrf_token=<?= urlencode($token) ?>">🔓 Habilitar Jeopardy a todos</a>
        <a href="#" onclick="confirmarAccion('acceso_masivo.php?clase_id=<?= $clase_id ?>&juego=jeopardy&accion=deshabilitar&csrf_token=<?= urlencode($token) ?>', '¿Quitar el acceso a Jeopardy a TODOS los alumnos de esta clase?'); return false;" class="btn">🔒 Deshabilitar Jeopardy a todos</a>
        <a class="btn" href="acceso_masivo.php?clase_id=<?= $clase_id ?>&juego=ahorcado&accion=habilitar&csrf_token=<?= urlencode($token) ?>">🔓 Habilitar Ahorcado a todos</a>
        <a href="#" onclick="confirmarAccion('acceso_masivo.php?clase_id=<?= $clase_id ?>&juego=ahorcado&accion=deshabilitar&csrf_token=<?= urlencode($token) ?>', '¿Quitar el acceso a Ahorcado a TODOS los alumnos de esta clase?'); return false;" class="btn">🔒 Deshabilitar Ahorcado a todos</a>
        <a class="btn" href="acceso_masivo.php?clase_id=<?= $clase_id ?>&juego=carrera&accion=habilitar&csrf_token=<?= urlencode($token) ?>">🔓 Habilitar Carrera a todos</a>
        <a href="#" onclick="confirmarAccion('acceso_masivo.php?clase_id=<?= $clase_id ?>&juego=carrera&accion=deshabilitar&csrf_token=<?= urlencode($token) ?>', '¿Quitar el acceso a Carrera a TODOS los alumnos de esta clase?'); return false;" class="btn">🔒 Deshabilitar Carrera a todos</a>
        <a class="btn" href="acceso_masivo.php?clase_id=<?= $clase_id ?>&juego=memorama&accion=habilitar&csrf_token=<?= urlencode($token) ?>">🔓 Habilitar Memorama a todos</a>
        <a href="#" onclick="confirmarAccion('acceso_masivo.php?clase_id=<?= $clase_id ?>&juego=memorama&accion=deshabilitar&csrf_token=<?= urlencode($token) ?>', '¿Quitar el acceso a Memorama a TODOS los alumnos de esta clase?'); return false;" class="btn">🔒 Deshabilitar Memorama a todos</a>
    </div>
    <?php } ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Mejor puntaje Jeopardy</th>
                    <th>Mejor puntaje Ahorcado</th>
                    <th>Mejor puntaje Carrera</th>
                    <th>Mejor puntaje Memorama</th>
                    <th>Mejor tiempo Memorama</th>
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
                    <tr><td colspan="11">Todavía no hay alumnos inscritos en esta clase.</td></tr>
                <?php } ?>

                <?php while ($a = $alumnos->fetch_assoc()) { ?>
                    <tr>
                        <td><?= htmlspecialchars($a['nombre']) ?></td>
                        <td><?= htmlspecialchars($a['correo']) ?></td>
                        <td><?= $a['mejor_jeopardy'] !== null ? (int)$a['mejor_jeopardy'] : '—' ?></td>
                        <td><?= $a['mejor_ahorcado'] !== null ? (int)$a['mejor_ahorcado'] : '—' ?></td>
                        <td><?= $a['mejor_carrera'] !== null ? (int)$a['mejor_carrera'] : '—' ?></td>
                        <td><?= $a['mejor_memorama'] !== null ? (int)$a['mejor_memorama'] : '—' ?></td>
                        <td><?= $a['mejor_tiempo_memorama'] !== null ? sprintf('%02d:%02d', intdiv($a['mejor_tiempo_memorama'], 60), $a['mejor_tiempo_memorama'] % 60) : '—' ?></td>
                        <td>
                            <?php if ((int)($a['acceso_jeopardy'] ?? 0) === 1) { ?>
                                <span style="color:#21C87A; font-weight:700;">✅ Habilitado</span><br>
                                <a href="#" onclick="confirmarAccion('quitar_acceso.php?clase_id=<?= $clase_id ?>&estudiante_id=<?= (int)$a['id'] ?>&juego=jeopardy&csrf_token=<?= urlencode($token) ?>', '¿Quitar el acceso a Jeopardy de <?= htmlspecialchars(addslashes($a['nombre'])) ?>?'); return false;">❌ Quitar acceso</a>
                            <?php } else { ?>
                                <span style="color:#dcd0ea;">🔒 Sin acceso</span><br>
                                <?php if ($mazos_asignados->num_rows > 0) { ?>
                                <a href="otorgar_acceso.php?clase_id=<?= $clase_id ?>&estudiante_id=<?= (int)$a['id'] ?>&juego=jeopardy&csrf_token=<?= urlencode($token) ?>">🔓 Dar acceso</a>
                                <?php } else { ?>
                                <span style="color:#8f7ba3; font-size:.8rem;">Falta activar un mazo</span>
                                <?php } ?>
                            <?php } ?>
                        </td>
                        <td>
                            <?php if ((int)($a['acceso_ahorcado'] ?? 0) === 1) { ?>
                                <span style="color:#21C87A; font-weight:700;">✅ Habilitado</span><br>
                                <a href="#" onclick="confirmarAccion('quitar_acceso.php?clase_id=<?= $clase_id ?>&estudiante_id=<?= (int)$a['id'] ?>&juego=ahorcado&csrf_token=<?= urlencode($token) ?>', '¿Quitar el acceso a Ahorcado de <?= htmlspecialchars(addslashes($a['nombre'])) ?>?'); return false;">❌ Quitar acceso</a>
                            <?php } else { ?>
                                <span style="color:#dcd0ea;">🔒 Sin acceso</span><br>
                                <?php if ($mazos_asignados->num_rows > 0) { ?>
                                <a href="otorgar_acceso.php?clase_id=<?= $clase_id ?>&estudiante_id=<?= (int)$a['id'] ?>&juego=ahorcado&csrf_token=<?= urlencode($token) ?>">🔓 Dar acceso</a>
                                <?php } else { ?>
                                <span style="color:#8f7ba3; font-size:.8rem;">Falta activar un mazo</span>
                                <?php } ?>
                            <?php } ?>
                        </td>
                        <td>
                            <?php if ((int)($a['acceso_carrera'] ?? 0) === 1) { ?>
                                <span style="color:#21C87A; font-weight:700;">✅ Habilitado</span><br>
                                <a href="#" onclick="confirmarAccion('quitar_acceso.php?clase_id=<?= $clase_id ?>&estudiante_id=<?= (int)$a['id'] ?>&juego=carrera&csrf_token=<?= urlencode($token) ?>', '¿Quitar el acceso a Carrera de <?= htmlspecialchars(addslashes($a['nombre'])) ?>?'); return false;">❌ Quitar acceso</a>
                            <?php } else { ?>
                                <span style="color:#dcd0ea;">🔒 Sin acceso</span><br>
                                <?php if ($mazos_asignados->num_rows > 0) { ?>
                                <a href="otorgar_acceso.php?clase_id=<?= $clase_id ?>&estudiante_id=<?= (int)$a['id'] ?>&juego=carrera&csrf_token=<?= urlencode($token) ?>">🔓 Dar acceso</a>
                                <?php } else { ?>
                                <span style="color:#8f7ba3; font-size:.8rem;">Falta activar un mazo</span>
                                <?php } ?>
                            <?php } ?>
                        </td>
                        <td>
                            <?php if ((int)($a['acceso_memorama'] ?? 0) === 1) { ?>
                                <span style="color:#21C87A; font-weight:700;">✅ Habilitado</span><br>
                                <a href="#" onclick="confirmarAccion('quitar_acceso.php?clase_id=<?= $clase_id ?>&estudiante_id=<?= (int)$a['id'] ?>&juego=memorama&csrf_token=<?= urlencode($token) ?>', '¿Quitar el acceso a Memorama de <?= htmlspecialchars(addslashes($a['nombre'])) ?>?'); return false;">❌ Quitar acceso</a>
                            <?php } else { ?>
                                <span style="color:#dcd0ea;">🔒 Sin acceso</span><br>
                                <?php if ($mazos_asignados->num_rows > 0) { ?>
                                <a href="otorgar_acceso.php?clase_id=<?= $clase_id ?>&estudiante_id=<?= (int)$a['id'] ?>&juego=memorama&csrf_token=<?= urlencode($token) ?>">🔓 Dar acceso</a>
                                <?php } else { ?>
                                <span style="color:#8f7ba3; font-size:.8rem;">Falta activar un mazo</span>
                                <?php } ?>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

</div>

<script>
/* Ajusta el máximo de "cuántas preguntas usar" según el mazo elegido,
   para no permitir pedir más preguntas de las que el mazo realmente tiene */
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