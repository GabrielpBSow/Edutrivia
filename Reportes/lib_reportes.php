<?php
/*
 * Centraliza las consultas de TODOS los reportes en un solo lugar.
 * Devuelve siempre ['titulo' => ..., 'columnas' => [...], 'filas' => [[...], [...]]]
 * o ['error' => '...'] si algo no es válido (permiso, clase inexistente, etc).
 *
 * La usan: Reportes/tabla.php (vista en pantalla), Reportes/exportar_pdf.php
 * y Reportes/exportar_excel.php — así la consulta se escribe una sola vez.
 */
function obtener_datos_reporte($conexion, $tipo, $id) {
    $rol = $_SESSION['rol'];
    $uid = $_SESSION['usuario_id'];

    switch ($tipo) {

        case 'ranking_clase': {
            $clase_id = $id;
            $stmt = $conexion->prepare("SELECT id, nombre, profesor_id FROM clases WHERE id = ?");
            $stmt->bind_param("i", $clase_id);
            $stmt->execute();
            $clase = $stmt->get_result()->fetch_assoc();

            if (!$clase) return ['error' => 'Clase no encontrada.'];
            if ($rol === 'profesor' && $clase['profesor_id'] !== $uid) return ['error' => 'No tienes permiso para esta clase.'];

            $stmt = $conexion->prepare("
                SELECT u.nombre, u.correo,
                       (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = ? AND r.juego = 'jeopardy') AS mejor_jeopardy,
                       (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = ? AND r.juego = 'ahorcado') AS mejor_ahorcado,
                       (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = ? AND r.juego = 'carrera') AS mejor_carrera,
                       (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = ? AND r.juego = 'memorama') AS mejor_memorama
                FROM inscripciones i
                INNER JOIN usuarios u ON u.id = i.estudiante_id
                WHERE i.clase_id = ?
            ");
            $stmt->bind_param("iiiii", $clase_id, $clase_id, $clase_id, $clase_id, $clase_id);
            $stmt->execute();
            $res = $stmt->get_result();

            $alumnos = [];
            while ($a = $res->fetch_assoc()) {
                $a['total'] = (int)($a['mejor_jeopardy'] ?? 0) + (int)($a['mejor_ahorcado'] ?? 0) + (int)($a['mejor_carrera'] ?? 0) + (int)($a['mejor_memorama'] ?? 0);
                $alumnos[] = $a;
            }
            usort($alumnos, fn($x, $y) => $y['total'] <=> $x['total']);

            $filas = [];
            foreach ($alumnos as $idx => $a) {
                $filas[] = [
                    $idx + 1,
                    $a['nombre'],
                    $a['correo'],
                    $a['mejor_jeopardy'] ?? '-',
                    $a['mejor_ahorcado'] ?? '-',
                    $a['mejor_carrera'] ?? '-',
                    $a['mejor_memorama'] ?? '-',
                    $a['total'],
                ];
            }

            return [
                'titulo' => 'Ranking - ' . $clase['nombre'],
                'columnas' => ['#', 'Nombre', 'Correo', 'Jeopardy', 'Ahorcado', 'Carrera', 'Memorama', 'Total'],
                'filas' => $filas,
            ];
        }

        case 'promedio_clases': {
            if ($rol === 'administrador') {
                $stmt = $conexion->prepare("
                    SELECT c.nombre, u.nombre AS profesor_nombre,
                           (SELECT COUNT(*) FROM inscripciones i WHERE i.clase_id = c.id) AS total_alumnos,
                           (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'jeopardy') AS promedio_jeopardy,
                           (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'ahorcado') AS promedio_ahorcado,
                           (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'carrera') AS promedio_carrera,
                           (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'memorama') AS promedio_memorama
                    FROM clases c
                    INNER JOIN usuarios u ON u.id = c.profesor_id
                    ORDER BY c.nombre
                ");
                $stmt->execute();
            } else {
                $stmt = $conexion->prepare("
                    SELECT c.nombre, NULL AS profesor_nombre,
                           (SELECT COUNT(*) FROM inscripciones i WHERE i.clase_id = c.id) AS total_alumnos,
                           (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'jeopardy') AS promedio_jeopardy,
                           (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'ahorcado') AS promedio_ahorcado,
                           (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'carrera') AS promedio_carrera,
                           (SELECT AVG(r.puntaje) FROM resultados r WHERE r.clase_id = c.id AND r.juego = 'memorama') AS promedio_memorama
                    FROM clases c
                    WHERE c.profesor_id = ?
                    ORDER BY c.nombre
                ");
                $stmt->bind_param("i", $uid);
                $stmt->execute();
            }
            $res = $stmt->get_result();

            $columnas = $rol === 'administrador'
                ? ['Clase', 'Profesor', 'Alumnos', 'Prom. Jeopardy', 'Prom. Ahorcado', 'Prom. Carrera', 'Prom. Memorama']
                : ['Clase', 'Alumnos', 'Prom. Jeopardy', 'Prom. Ahorcado', 'Prom. Carrera', 'Prom. Memorama'];

            $filas = [];
            while ($c = $res->fetch_assoc()) {
                $fila = [$c['nombre']];
                if ($rol === 'administrador') $fila[] = $c['profesor_nombre'];
                $fila[] = (int)$c['total_alumnos'];
                $fila[] = $c['promedio_jeopardy'] !== null ? round($c['promedio_jeopardy']) : '-';
                $fila[] = $c['promedio_ahorcado'] !== null ? round($c['promedio_ahorcado']) : '-';
                $fila[] = $c['promedio_carrera'] !== null ? round($c['promedio_carrera']) : '-';
                $fila[] = $c['promedio_memorama'] !== null ? round($c['promedio_memorama']) : '-';
                $filas[] = $fila;
            }

            return ['titulo' => 'Promedio de Puntaje por Clase', 'columnas' => $columnas, 'filas' => $filas];
        }

        case 'encuestas': {
            if ($rol === 'administrador') {
                $stmt = $conexion->prepare("SELECT opcion, COUNT(*) AS total FROM encuestas GROUP BY opcion");
                $stmt->execute();
            } else {
                $stmt = $conexion->prepare("
                    SELECT e.opcion, COUNT(*) AS total
                    FROM encuestas e
                    WHERE e.usuario_id IN (
                        SELECT DISTINCT i.estudiante_id FROM inscripciones i
                        INNER JOIN clases c ON c.id = i.clase_id
                        WHERE c.profesor_id = ?
                    )
                    GROUP BY e.opcion
                ");
                $stmt->bind_param("i", $uid);
                $stmt->execute();
            }
            $res = $stmt->get_result();

            $conteo = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
            while ($row = $res->fetch_assoc()) $conteo[(int)$row['opcion']] = (int)$row['total'];

            $etiquetas = [1 => 'Excelente', 2 => 'Bueno', 3 => 'Regular', 4 => 'Malo'];
            $total = array_sum($conteo);

            $filas = [];
            foreach ($etiquetas as $valor => $texto) {
                $porcentaje = $total > 0 ? round($conteo[$valor] / $total * 100) : 0;
                $filas[] = [$texto, $conteo[$valor], $porcentaje . '%'];
            }

            return ['titulo' => 'Encuestas de Satisfaccion', 'columnas' => ['Opcion', 'Cantidad', 'Porcentaje'], 'filas' => $filas];
        }

        case 'usuarios': {
            if ($rol !== 'administrador') return ['error' => 'Solo el administrador puede ver este reporte.'];

            $stmt = $conexion->prepare("SELECT rol, COUNT(*) AS total FROM usuarios GROUP BY rol");
            $stmt->execute();
            $res = $stmt->get_result();

            $filas = [];
            while ($row = $res->fetch_assoc()) {
                $filas[] = [ucfirst($row['rol']), (int)$row['total']];
            }

            return ['titulo' => 'Usuarios por Rol', 'columnas' => ['Rol', 'Total'], 'filas' => $filas];
        }

        case 'actividad': {
            $clase_id = $id;
            $stmt = $conexion->prepare("SELECT id, nombre, profesor_id FROM clases WHERE id = ?");
            $stmt->bind_param("i", $clase_id);
            $stmt->execute();
            $clase = $stmt->get_result()->fetch_assoc();

            if (!$clase) return ['error' => 'Clase no encontrada.'];
            if ($rol === 'profesor' && $clase['profesor_id'] !== $uid) return ['error' => 'No tienes permiso para esta clase.'];

            $stmt = $conexion->prepare("
                SELECT u.nombre, u.correo,
                       (SELECT COUNT(*) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = ? AND r.juego = 'jeopardy') AS veces_jeopardy,
                       (SELECT COUNT(*) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = ? AND r.juego = 'ahorcado') AS veces_ahorcado,
                       (SELECT COUNT(*) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = ? AND r.juego = 'carrera') AS veces_carrera,
                       (SELECT COUNT(*) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = ? AND r.juego = 'memorama') AS veces_memorama
                FROM inscripciones i
                INNER JOIN usuarios u ON u.id = i.estudiante_id
                WHERE i.clase_id = ?
            ");
            $stmt->bind_param("iiiii", $clase_id, $clase_id, $clase_id, $clase_id, $clase_id);
            $stmt->execute();
            $res = $stmt->get_result();

            $filas = [];
            while ($a = $res->fetch_assoc()) {
                $total_partidas = (int)$a['veces_jeopardy'] + (int)$a['veces_ahorcado'] + (int)$a['veces_carrera'] + (int)$a['veces_memorama'];
                $filas[] = [
                    $a['nombre'],
                    $a['correo'],
                    (int)$a['veces_jeopardy'],
                    (int)$a['veces_ahorcado'],
                    (int)$a['veces_carrera'],
                    (int)$a['veces_memorama'],
                    $total_partidas > 0 ? 'Si' : 'Nunca ha jugado',
                ];
            }

            return [
                'titulo' => 'Actividad de Alumnos - ' . $clase['nombre'],
                'columnas' => ['Nombre', 'Correo', 'Partidas Jeopardy', 'Partidas Ahorcado', 'Partidas Carrera', 'Partidas Memorama', 'Ha jugado'],
                'filas' => $filas,
            ];
        }

        case 'mazos': {
            if ($rol === 'administrador') {
                $stmt = $conexion->prepare("
                    SELECT m.nombre, u.nombre AS profesor_nombre,
                           (SELECT COUNT(*) FROM preguntas p WHERE p.mazo_id = m.id) AS total_preguntas,
                           (SELECT COUNT(*) FROM clase_mazos cm WHERE cm.mazo_id = m.id) AS clases_activas
                    FROM mazos m
                    INNER JOIN usuarios u ON u.id = m.profesor_id
                    ORDER BY m.nombre
                ");
                $stmt->execute();
            } else {
                $stmt = $conexion->prepare("
                    SELECT m.nombre, NULL AS profesor_nombre,
                           (SELECT COUNT(*) FROM preguntas p WHERE p.mazo_id = m.id) AS total_preguntas,
                           (SELECT COUNT(*) FROM clase_mazos cm WHERE cm.mazo_id = m.id) AS clases_activas
                    FROM mazos m
                    WHERE m.profesor_id = ?
                    ORDER BY m.nombre
                ");
                $stmt->bind_param("i", $uid);
                $stmt->execute();
            }
            $res = $stmt->get_result();

            $columnas = $rol === 'administrador'
                ? ['Mazo', 'Profesor', 'Preguntas', 'Clases donde esta activo']
                : ['Mazo', 'Preguntas', 'Clases donde esta activo'];

            $filas = [];
            while ($m = $res->fetch_assoc()) {
                $fila = [$m['nombre']];
                if ($rol === 'administrador') $fila[] = $m['profesor_nombre'];
                $fila[] = (int)$m['total_preguntas'];
                $fila[] = (int)$m['clases_activas'];
                $filas[] = $fila;
            }

            return ['titulo' => 'Inventario de Mazos', 'columnas' => $columnas, 'filas' => $filas];
        }

        case 'general': {
            if ($rol === 'administrador') {
                $stmt = $conexion->prepare("
                    SELECT c.nombre AS clase_nombre, u.nombre, u.correo,
                           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = c.id AND r.juego = 'jeopardy') AS mejor_jeopardy,
                           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = c.id AND r.juego = 'ahorcado') AS mejor_ahorcado,
                           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = c.id AND r.juego = 'carrera') AS mejor_carrera,
                           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = c.id AND r.juego = 'memorama') AS mejor_memorama
                    FROM inscripciones i
                    INNER JOIN clases c ON c.id = i.clase_id
                    INNER JOIN usuarios u ON u.id = i.estudiante_id
                    ORDER BY c.nombre, u.nombre
                ");
                $stmt->execute();
            } else {
                $stmt = $conexion->prepare("
                    SELECT c.nombre AS clase_nombre, u.nombre, u.correo,
                           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = c.id AND r.juego = 'jeopardy') AS mejor_jeopardy,
                           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = c.id AND r.juego = 'ahorcado') AS mejor_ahorcado,
                           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = c.id AND r.juego = 'carrera') AS mejor_carrera,
                           (SELECT MAX(r.puntaje) FROM resultados r WHERE r.estudiante_id = u.id AND r.clase_id = c.id AND r.juego = 'memorama') AS mejor_memorama
                    FROM inscripciones i
                    INNER JOIN clases c ON c.id = i.clase_id
                    INNER JOIN usuarios u ON u.id = i.estudiante_id
                    WHERE c.profesor_id = ?
                    ORDER BY c.nombre, u.nombre
                ");
                $stmt->bind_param("i", $uid);
                $stmt->execute();
            }
            $res = $stmt->get_result();

            $filas = [];
            while ($r = $res->fetch_assoc()) {
                $filas[] = [
                    $r['clase_nombre'],
                    $r['nombre'],
                    $r['correo'],
                    $r['mejor_jeopardy'] ?? '-',
                    $r['mejor_ahorcado'] ?? '-',
                    $r['mejor_carrera'] ?? '-',
                    $r['mejor_memorama'] ?? '-',
                ];
            }

            return [
                'titulo' => 'Reporte General de Calificaciones',
                'columnas' => ['Clase', 'Nombre', 'Correo', 'Jeopardy', 'Ahorcado', 'Carrera', 'Memorama'],
                'filas' => $filas,
            ];
        }

        case 'participacion': {
            $stmt = $conexion->prepare($rol === 'administrador'
                ? "SELECT COUNT(DISTINCT i.estudiante_id) AS total FROM inscripciones i INNER JOIN clases c ON c.id = i.clase_id"
                : "SELECT COUNT(DISTINCT i.estudiante_id) AS total FROM inscripciones i INNER JOIN clases c ON c.id = i.clase_id WHERE c.profesor_id = ?"
            );
            if ($rol !== 'administrador') $stmt->bind_param("i", $uid);
            $stmt->execute();
            $total_alumnos = (int)$stmt->get_result()->fetch_assoc()['total'];

            $stmt = $conexion->prepare($rol === 'administrador'
                ? "SELECT r.juego, COUNT(*) AS total_partidas, COUNT(DISTINCT r.estudiante_id) AS alumnos_distintos FROM resultados r INNER JOIN clases c ON c.id = r.clase_id GROUP BY r.juego"
                : "SELECT r.juego, COUNT(*) AS total_partidas, COUNT(DISTINCT r.estudiante_id) AS alumnos_distintos FROM resultados r INNER JOIN clases c ON c.id = r.clase_id WHERE c.profesor_id = ? GROUP BY r.juego"
            );
            if ($rol !== 'administrador') $stmt->bind_param("i", $uid);
            $stmt->execute();
            $res = $stmt->get_result();

            $datos = [
                'jeopardy' => ['partidas' => 0, 'alumnos' => 0],
                'ahorcado' => ['partidas' => 0, 'alumnos' => 0],
                'carrera'  => ['partidas' => 0, 'alumnos' => 0],
                'memorama' => ['partidas' => 0, 'alumnos' => 0],
            ];
            while ($row = $res->fetch_assoc()) {
                $datos[$row['juego']] = ['partidas' => (int)$row['total_partidas'], 'alumnos' => (int)$row['alumnos_distintos']];
            }

            $filas = [];
            foreach ($datos as $juego => $d) {
                $porcentaje = $total_alumnos > 0 ? round($d['alumnos'] / $total_alumnos * 100) : 0;
                $filas[] = [ucfirst($juego), $d['partidas'], $d['alumnos'] . ' de ' . $total_alumnos, $porcentaje . '%'];
            }

            return [
                'titulo' => 'Participacion en Juegos',
                'columnas' => ['Juego', 'Partidas Jugadas', 'Alumnos que jugaron', '% Participacion'],
                'filas' => $filas,
            ];
        }

        case 'clases_inactivas': {
            if ($rol === 'administrador') {
                $stmt = $conexion->prepare("
                    SELECT c.nombre, c.creado_en,
                           (SELECT COUNT(*) FROM inscripciones i WHERE i.clase_id = c.id) AS total_alumnos
                    FROM clases c
                    WHERE NOT EXISTS (SELECT 1 FROM resultados r WHERE r.clase_id = c.id)
                    ORDER BY c.creado_en DESC
                ");
                $stmt->execute();
            } else {
                $stmt = $conexion->prepare("
                    SELECT c.nombre, c.creado_en,
                           (SELECT COUNT(*) FROM inscripciones i WHERE i.clase_id = c.id) AS total_alumnos
                    FROM clases c
                    WHERE c.profesor_id = ?
                      AND NOT EXISTS (SELECT 1 FROM resultados r WHERE r.clase_id = c.id)
                    ORDER BY c.creado_en DESC
                ");
                $stmt->bind_param("i", $uid);
                $stmt->execute();
            }
            $res = $stmt->get_result();

            $filas = [];
            while ($c = $res->fetch_assoc()) {
                $filas[] = [$c['nombre'], date('d/m/Y', strtotime($c['creado_en'])), (int)$c['total_alumnos']];
            }

            return ['titulo' => 'Clases sin Actividad', 'columnas' => ['Clase', 'Creada el', 'Alumnos inscritos'], 'filas' => $filas];
        }

        default:
            return ['error' => 'Tipo de reporte no reconocido.'];
    }
}