<?php
session_start();
include("../conexion.php");
require_once("../csrf.php");

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit();
}

$rol = $_SESSION['rol'];
$uid = $_SESSION['usuario_id'];
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
$token = csrf_token();

$panel_regreso = match ($rol) {
    'administrador' => '../Administrador/index.php',
    'profesor'      => '../Profesor/index.php',
    default         => '../Estudiante/index.php',
};

if ($rol === 'profesor') {
    /* El profesor ve SOLO las clases que él creó */
    $stmt = $conexion->prepare("
        SELECT c.id, c.nombre, c.codigo, c.creado_en,
               (SELECT COUNT(*) FROM inscripciones i WHERE i.clase_id = c.id) AS total_alumnos
        FROM clases c
        WHERE c.profesor_id = ?
        ORDER BY c.creado_en DESC
    ");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $clases = $stmt->get_result();

} elseif ($rol === 'estudiante') {
    /* El estudiante ve solo las clases a las que se ha unido, junto con
       su estado de acceso actual a cada juego */
    $stmt = $conexion->prepare("
        SELECT c.id, c.nombre, c.codigo, u.nombre AS profesor_nombre,
               (SELECT habilitado FROM accesos_juego a WHERE a.clase_id = c.id AND a.estudiante_id = ? AND a.juego = 'jeopardy') AS acceso_jeopardy,
               (SELECT habilitado FROM accesos_juego a WHERE a.clase_id = c.id AND a.estudiante_id = ? AND a.juego = 'ahorcado') AS acceso_ahorcado,
               (SELECT habilitado FROM accesos_juego a WHERE a.clase_id = c.id AND a.estudiante_id = ? AND a.juego = 'carrera') AS acceso_carrera,
               (SELECT habilitado FROM accesos_juego a WHERE a.clase_id = c.id AND a.estudiante_id = ? AND a.juego = 'memorama') AS acceso_memorama
        FROM inscripciones i
        INNER JOIN clases c ON c.id = i.clase_id
        INNER JOIN usuarios u ON u.id = c.profesor_id
        WHERE i.estudiante_id = ?
        ORDER BY i.fecha_inscripcion DESC
    ");
    $stmt->bind_param("iiiii", $uid, $uid, $uid, $uid, $uid);
    $stmt->execute();
    $clases = $stmt->get_result();

} else {
    /* El administrador ve TODAS las clases del sistema */
    $clases = $conexion->query("
        SELECT c.id, c.nombre, c.codigo, c.creado_en, u.nombre AS profesor_nombre,
               (SELECT COUNT(*) FROM inscripciones i WHERE i.clase_id = c.id) AS total_alumnos
        FROM clases c
        INNER JOIN usuarios u ON u.id = c.profesor_id
        ORDER BY c.creado_en DESC
    ");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" type="img/png" href="../img/LogoEdutrivia (1).png">
    <link rel="stylesheet" href="../css/app.css">
    <title>Mis Clases</title>
</head>
<body class="fondo">

<div class="contenedor-gestion">

    <a href="<?= $panel_regreso ?>">
        <img class="img_before" src="../img/before_writh.png" alt="Volver" style="max-width:42px;">
    </a>

    <h1 class="pagina-titulo"><i class='bx bx-chalkboard'></i> Mis Clases</h1>

    <?php if ($error) { ?>
        <p style="color:#ffb3b3; text-align:center; font-weight:bold;"><?= htmlspecialchars($error) ?></p>
    <?php } ?>
    <?php if ($success) { ?>
        <p style="color:#b3ffb3; text-align:center; font-weight:bold;"><?= htmlspecialchars($success) ?></p>
    <?php } ?>

    <?php if ($rol === 'estudiante') { ?>
        <!-- FORMULARIO PARA UNIRSE A UNA CLASE -->
        <form method="POST" action="unirse.php">
            <?= csrf_field() ?>
            <label>Código de la clase</label>
            <input class="name_input" type="text" name="codigo" placeholder="Ej. MAT2A7X" maxlength="10"
                   style="text-transform:uppercase;" required>
            <button class="btn" type="submit">Unirme a la clase</button>
        </form>
    <?php } ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Clase</th>
                    <?php if ($rol !== 'estudiante') { ?><th>Código</th><?php } ?>
                    <?php if ($rol === 'administrador' || $rol === 'estudiante') { ?><th>Profesor</th><?php } ?>
                    <?php if ($rol !== 'estudiante') { ?><th>Alumnos</th><?php } ?>
                    <?php if ($rol === 'profesor') { ?><th>Acciones</th><?php } ?>
                    <?php if ($rol === 'estudiante') { ?><th>Jugar</th><?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($clases->num_rows === 0) { ?>
                    <tr><td colspan="5">
                        <?= $rol === 'estudiante' ? 'Todavía no te has unido a ninguna clase.' : 'Todavía no hay clases registradas.' ?>
                    </td></tr>
                <?php } ?>

                <?php while ($c = $clases->fetch_assoc()) { ?>
                    <tr>
                        <td><?= htmlspecialchars($c['nombre']) ?></td>
                        <?php if ($rol !== 'estudiante') { ?>
                            <td><b><?= htmlspecialchars($c['codigo']) ?></b></td>
                        <?php } ?>
                        <?php if ($rol === 'administrador' || $rol === 'estudiante') { ?>
                            <td><?= htmlspecialchars($c['profesor_nombre']) ?></td>
                        <?php } ?>
                        <?php if ($rol !== 'estudiante') { ?>
                            <td><?= (int)$c['total_alumnos'] ?></td>
                        <?php } ?>
                        <?php if ($rol === 'profesor') { ?>
                            <td><a href="ver.php?id=<?= (int)$c['id'] ?>">👥 Ver alumnos</a></td>
                        <?php } ?>
                        <?php if ($rol === 'estudiante') { ?>
                            <td>
                                <?php if ((int)($c['acceso_jeopardy'] ?? 0) === 1) { ?>
                                    <a href="../Juegos/jeopardy.php?clase_id=<?= (int)$c['id'] ?>" title="Jugar Jeopardy">🎯</a>
                                <?php } else { ?>
                                    <span style="opacity:.4; cursor:not-allowed;" title="Tu profesor aún no te ha dado acceso a Jeopardy">🔒</span>
                                <?php } ?>
                                <?php if ((int)($c['acceso_ahorcado'] ?? 0) === 1) { ?>
                                    <a href="../Juegos/ahorcado.php?clase_id=<?= (int)$c['id'] ?>" title="Jugar Ahorcado">💀</a>
                                <?php } else { ?>
                                    <span style="opacity:.4; cursor:not-allowed;" title="Tu profesor aún no te ha dado acceso a Ahorcado">🔒</span>
                                <?php } ?>
                                <?php if ((int)($c['acceso_carrera'] ?? 0) === 1) { ?>
                                    <a href="../Juegos/carrera.php?clase_id=<?= (int)$c['id'] ?>" title="Jugar Carrera de Conocimiento">🏍️</a>
                                <?php } else { ?>
                                    <span style="opacity:.4; cursor:not-allowed;" title="Tu profesor aún no te ha dado acceso a Carrera">🔒</span>
                                <?php } ?>
                                <?php if ((int)($c['acceso_memorama'] ?? 0) === 1) { ?>
                                    <a href="../Juegos/memorama.php?clase_id=<?= (int)$c['id'] ?>" title="Jugar Memorama">🃏</a>
                                <?php } else { ?>
                                    <span style="opacity:.4; cursor:not-allowed;" title="Tu profesor aún no te ha dado acceso a Memorama">🔒</span>
                                <?php } ?>
                            </td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <?php if ($rol === 'profesor') { ?>
    <div class="btn-wrap">
        <a href="crear.php" class="btn">➕ Nueva Clase</a>
    </div>
    <?php } ?>

</div>

</body>
</html>