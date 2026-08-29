# EDUTRIVIA — Resumen del proyecto

Sistema educativo tipo Classroom + trivia, hecho por Gabriel y Alondra para la universidad. Reconstruido desde cero en `Edutrivia/` sobre el proyecto original (`Edutrivia_V3`), que tenía SQL sin preparar, contraseñas mal hasheadas y varias funciones a medias. Proyecto de portafolio/escuela, corre en local con XAMPP, no se hostea.

Colores: morado `#5B2C8C`, rojo `#EF3E36`, naranja `#F7A600`, verde `#21C87A`.

## Estructura

```
Edutrivia/
├── conexion.php, login.php, logout.php, csrf.php
├── css/  (app.css, juegos.css [?v=7+], estilos.css/normalize.css [portada de Alondra, no tocar])
├── js/   (script.js, menu2.js, confirmar.js)
├── libs/fpdf/  (fpdf.php + carpeta font/, descargados manualmente de fpdf.org)
├── img/, principal.php  (portada pública, no tocar)
│
├── Administrador/index.php
├── Profesor/index.php
├── Estudiante/  (index.php, resultados.php)
│
├── Usuarios/, Mazos/, Preguntas/  (CRUD estándar)
├── Clases/  (index, crear, guardar, unirse, ver, asignar_mazo, quitar_mazo,
│             otorgar_acceso, quitar_acceso, acceso_masivo)
│
├── Juegos/
│   ├── jeopardy.php, ahorcado.php, carrera.php, memorama.php
│   ├── obtener_preguntas.php, obtener_palabras.php,
│   │   obtener_preguntas_carrera.php, obtener_preguntas_memorama.php
│   ├── guardar_resultado.php
│   └── encuesta.php, guardar_encuesta.php, gracias.php
│
├── Reportes/  (index, ver, tabla, encuestas, lib_reportes, exportar_pdf, exportar_excel)
│
└── Backups/  (index, generar, descargar, eliminar, archivos/ [con .htaccess])
```

## Base de datos

```sql
usuarios (id, nombre, correo, contrasena[bcrypt], rol[administrador|profesor|estudiante],
          activo, intentos_fallidos, bloqueado_hasta, creado_en)

mazos (id, nombre, profesor_id, creado_en)
preguntas (id, mazo_id, categoria, pregunta, respuesta, puntaje, creado_en)

clases (id, nombre, codigo, profesor_id, creado_en)
inscripciones (id, clase_id, estudiante_id, fecha_inscripcion)
clase_mazos (id, clase_id, mazo_id, cantidad_preguntas, asignado_en)

resultados (id, estudiante_id, clase_id, juego[jeopardy|ahorcado|carrera|memorama],
            puntaje, tiempo_segundos[solo Memorama], fecha)

accesos_juego (id, clase_id, estudiante_id, juego, habilitado, otorgado_en, usado_en)
-- controla el "un solo intento": se consume automático al entrar al juego

encuestas (id, opcion[1-4], usuario_id, fecha)
```

Migraciones a correr en orden: `migracion_accesos_juego.sql` → `migracion_carrera.sql` → `migracion_memorama.sql` → `migracion_tiempo.sql`.

Credenciales de prueba (contraseña `12345678`): `admin@edutrivia.com`, `profesor@edutrivia.com`, `estudiante@edutrivia.com`.

## Permisos por rol

| Función | Admin | Profesor | Estudiante |
|---|:---:|:---:|:---:|
| Gestionar Usuarios | Sí | No | No |
| Gestionar Mazos/Preguntas | Todos | Los suyos | No |
| Gestionar Clases | Todas | Las suyas | No |
| Unirse a Clases | No | No | Sí |
| Dar/quitar acceso a juegos | Sí | Sus clases | No |
| Jugar | Sí | Sí | Con acceso otorgado |
| Reportes (ver y exportar) | Sistema completo | Sus clases | Solo su propio resultado |
| Respaldo de BD | Sí | No | No |

## Los 4 juegos

- **Jeopardy** — tablero por categoría/valor, preguntas de mazos activos.
- **Ahorcado** — adivina la `categoria`; pista = nombre del mazo; +50 acierto / -25 fallo / +100 al ganar; repetir letra cuenta como fallo.
- **Carrera de Conocimiento** — hasta 10 preguntas, 3 carriles (arranca en el centro), 5s por pregunta, 3 vidas.
- **Memorama** — hasta 10 parejas pregunta-respuesta, grid adaptable según cantidad, cronómetro, sin penalización por fallar.

Todos usan sonido con Web Audio API (sin archivos externos), modales SweetAlert2, y requieren acceso otorgado por el profesor (se consume al entrar, gane o pierda el alumno).

## Reportes (8, todos exportables a PDF y Excel)

Ranking por clase, Promedio por clase, Encuestas de satisfacción, Usuarios por Rol (admin), Actividad de Alumnos, Inventario de Mazos, Reporte General de Calificaciones, Participación en Juegos + Clases sin Actividad.

No existe reporte de "preguntas con más fallos": el sistema no guarda qué pregunta específica se falló, solo el puntaje final de la partida.

## Bugs reales corregidos

- Login atorado en Registro tras intento fallido.
- Jeopardy mandaba a la encuesta antes de la última pregunta.
- Archivos cruzados entre `Mazos/` y `Preguntas/`.
- `<label>` inline rompía formularios anchos.
- Caché de navegador con CSS/JS repetido — usar `?v=N`.
- Respaldo de BD (nunca funcionó en el original): ruta de `mysqldump` mal apuntada y verificación de éxito mal comparada. Ahora exige código de salida 0 + archivo existe + no está vacío.
- `acceso_masivo.php` no tenía 'carrera' en su lista de juegos válidos, rompiendo el habilitar/deshabilitar masivo solo para ese juego.

## Dependencias externas

- **FPDF**: descargar de fpdf.org, colocar `fpdf.php` y la carpeta `font/` en `Edutrivia/libs/fpdf/`. No viene incluido por ser código de terceros.
- Excel no necesita librería (tabla HTML con extensión .xls).
- SweetAlert2 y Boxicons se cargan por CDN.

## Detalles a recordar

- Páginas de gestión: `<body class="fondo">` + `<div class="contenedor-gestion">`.
- Botones sueltos van en `<div class="btn-wrap">`.
- Acciones destructivas usan `confirmarAccion()` de `confirmar.js`.
- El CSRF se valida contra `$_SESSION['csrf_token']` directamente.
- FPDF no soporta UTF-8 nativo, se usa `utf8_decode()` en los PDF.

## Pendientes dejados a propósito

- No se sube a hosting.
- Sin link de Reportes/Backups en el sidebar de Profesor/Administrador (se entra por URL).
- No probado a fondo en celular físico real.
- El Profesor no tiene ninguna función exclusiva fuera de Usuarios/Backups.

## Ideas futuras

- Reporte de preguntas con más fallos (requiere tabla de detalle por respuesta).
- Botón de pista con costo de puntos en Ahorcado.
- Mostrar tiempos de Memorama también en Reportes, no solo en Clases/ver.php.
