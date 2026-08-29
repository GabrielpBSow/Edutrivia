<?php
session_start();
include("../conexion.php");

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit();
}

$clase_id = (int)($_GET['clase_id'] ?? 0);

if ($clase_id <= 0) {
    header("Location: ../Clases/index.php");
    exit();
}

/* Los estudiantes solo pueden entrar si el profesor les dio acceso.
   Se consume de inmediato: entre o no termine la partida, ya no puede
   volver a entrar hasta que se lo vuelvan a habilitar. */
if ($_SESSION['rol'] === 'estudiante') {
    $stmt = $conexion->prepare("SELECT habilitado FROM accesos_juego WHERE clase_id = ? AND estudiante_id = ? AND juego = 'carrera'");
    $stmt->bind_param("ii", $clase_id, $_SESSION['usuario_id']);
    $stmt->execute();
    $acceso = $stmt->get_result()->fetch_assoc();

    if (!$acceso || (int)$acceso['habilitado'] !== 1) {
        $_SESSION['error'] = "Tu profesor todavía no te ha dado acceso a este juego.";
        header("Location: ../Clases/index.php");
        exit();
    }

    $stmt = $conexion->prepare("UPDATE accesos_juego SET habilitado = 0, usado_en = NOW() WHERE clase_id = ? AND estudiante_id = ? AND juego = 'carrera'");
    $stmt->bind_param("ii", $clase_id, $_SESSION['usuario_id']);
    $stmt->execute();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/juegos.css?v=5">
    <link rel="icon" type="img/png" href="../img/LogoEdutrivia (1).png">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Carrera de Conocimiento</title>
</head>
<body class="pantalla-carrera">

    <div class="carrera-header">
        <span>❤️ <span id="vidas-count">3</span></span>
        <span>Puntaje: <span id="puntaje-count">0</span></span>
        <span><span id="pregunta-num">0</span>/10</span>
    </div>

    <div class="carrera-pregunta-caja">
        <p class="carrera-pregunta-texto" id="pregunta-texto">Cargando...</p>
    </div>

    <div class="carrera-timer-track">
        <div class="carrera-timer-fill" id="timer-fill"></div>
    </div>

    <div class="carrera-pista" id="pista">
        <div class="carril-izquierda carrera-carril">
            <div class="carrera-opcion" id="opcion-izquierda">-</div>
        </div>
        <div class="carril-centro carrera-carril">
            <div class="carrera-opcion carrera-opcion-centro">¿?</div>
        </div>
        <div class="carril-derecha carrera-carril">
            <div class="carrera-opcion" id="opcion-derecha">-</div>
        </div>
        <div class="carrera-vehiculo" id="vehiculo">🏍️</div>
    </div>

    <div class="carrera-controles">
        <button id="btn-izquierda">⬅️</button>
        <button id="btn-derecha">➡️</button>
    </div>

    <script>
      const CLASE_ID = <?= $clase_id ?>;
      const DURACION_PREGUNTA_MS = 5000;

      /* Sonidos generados con Web Audio API (mismo patrón que Ahorcado/Jeopardy) */
      const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

      function tono(frecuencia, duracionMs, tipo = 'sine', volumen = 0.15, retrasoMs = 0) {
        setTimeout(() => {
          const osc = audioCtx.createOscillator();
          const gain = audioCtx.createGain();
          osc.type = tipo;
          osc.frequency.value = frecuencia;
          gain.gain.value = volumen;
          osc.connect(gain);
          gain.connect(audioCtx.destination);
          osc.start();
          gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + duracionMs / 1000);
          osc.stop(audioCtx.currentTime + duracionMs / 1000);
        }, retrasoMs);
      }

      function sonidoAcierto() { tono(660, 120); tono(880, 150, 'sine', 0.13, 100); }
      function sonidoChoque() { tono(150, 300, 'sawtooth', 0.15); }
      function sonidoFin() {
        tono(523, 130, 'sine', 0.15, 0);
        tono(659, 130, 'sine', 0.15, 130);
        tono(784, 220, 'sine', 0.15, 260);
      }
      function sonidoGameOver() {
        tono(300, 200, 'sawtooth', 0.12, 0);
        tono(220, 200, 'sawtooth', 0.12, 200);
        tono(140, 350, 'sawtooth', 0.12, 400);
      }

      const pistaElement = document.getElementById('pista');
      const vehiculoElement = document.getElementById('vehiculo');
      const opcionIzqElement = document.getElementById('opcion-izquierda');
      const opcionDerElement = document.getElementById('opcion-derecha');
      const carrilIzqElement = document.querySelector('.carril-izquierda');
      const carrilCentroElement = document.querySelector('.carril-centro');
      const carrilDerElement = document.querySelector('.carril-derecha');
      const timerFillElement = document.getElementById('timer-fill');
      const preguntaTextoElement = document.getElementById('pregunta-texto');
      const preguntaNumElement = document.getElementById('pregunta-num');
      const vidasElement = document.getElementById('vidas-count');
      const puntajeElement = document.getElementById('puntaje-count');

      let preguntas = [];
      let indiceActual = 0;
      let vidas = 3;
      let puntaje = 0;
      const CARRIL_IZQUIERDA = 0;
      const CARRIL_CENTRO = 1;
      const CARRIL_DERECHA = 2;
      let carrilActual = CARRIL_CENTRO;
      let timerId = null;
      let evaluando = false;

      function posicionarVehiculo() {
        const posiciones = { [CARRIL_IZQUIERDA]: '13%', [CARRIL_CENTRO]: '46%', [CARRIL_DERECHA]: '79%' };
        vehiculoElement.style.left = posiciones[carrilActual];
      }

      function moverA(carril) {
        if (evaluando) return;
        carrilActual = carril;
        posicionarVehiculo();
      }

      document.getElementById('btn-izquierda').addEventListener('click', () => moverA(CARRIL_IZQUIERDA));
      document.getElementById('btn-derecha').addEventListener('click', () => moverA(CARRIL_DERECHA));

      document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') moverA(CARRIL_IZQUIERDA);
        if (e.key === 'ArrowRight') moverA(CARRIL_DERECHA);
      });

      /* Swipe táctil sobre la pista */
      let touchStartX = null;
      pistaElement.addEventListener('touchstart', (e) => {
        touchStartX = e.touches[0].clientX;
      });
      pistaElement.addEventListener('touchend', (e) => {
        if (touchStartX === null) return;
        const deltaX = e.changedTouches[0].clientX - touchStartX;
        if (Math.abs(deltaX) > 30) {
          moverA(deltaX > 0 ? CARRIL_DERECHA : CARRIL_IZQUIERDA);
        }
        touchStartX = null;
      });

      function actualizarPuntaje(cambio) {
        puntaje += cambio;
        if (puntaje < 0) puntaje = 0;
        puntajeElement.textContent = puntaje;
      }

      function actualizarVidas() {
        vidasElement.textContent = vidas;
      }

      function guardarYRedirigir(puntajeFinal) {
        fetch('guardar_resultado.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ clase_id: CLASE_ID, juego: 'carrera', puntaje: puntajeFinal })
        }).finally(() => {
          window.location.href = 'encuesta.php';
        });
      }

      function terminarPorVidas() {
        clearTimeout(timerId);
        sonidoGameOver();
        Swal.fire({
          title: '¡Te quedaste sin vidas! 😢',
          text: `Terminaste con ${puntaje} puntos.`,
          icon: 'error',
          confirmButtonText: 'Continuar',
          confirmButtonColor: '#5B2C8C'
        }).then(() => guardarYRedirigir(puntaje));
      }

      function terminarPorPreguntas() {
        clearTimeout(timerId);
        sonidoFin();
        Swal.fire({
          title: '¡Carrera terminada! 🏁',
          text: `Terminaste con ${puntaje} puntos.`,
          icon: 'success',
          confirmButtonText: 'Continuar',
          confirmButtonColor: '#5B2C8C'
        }).then(() => guardarYRedirigir(puntaje));
      }

      function cargarPregunta() {
        if (indiceActual >= preguntas.length) {
          terminarPorPreguntas();
          return;
        }

        evaluando = false;
        carrilActual = CARRIL_CENTRO;
        posicionarVehiculo();
        carrilIzqElement.classList.remove('correcto', 'incorrecto');
        carrilDerElement.classList.remove('correcto', 'incorrecto');

        const p = preguntas[indiceActual];
        preguntaTextoElement.textContent = p.pregunta;
        opcionIzqElement.textContent = p.opcion_izquierda;
        opcionDerElement.textContent = p.opcion_derecha;
        preguntaNumElement.textContent = indiceActual + 1;

        // Reiniciar la barra de tiempo (truco de reflow para que la transición se vea desde el 100%)
        timerFillElement.style.transition = 'none';
        timerFillElement.style.width = '100%';
        void timerFillElement.offsetWidth;
        timerFillElement.style.transition = `width ${DURACION_PREGUNTA_MS}ms linear`;
        timerFillElement.style.width = '0%';

        timerId = setTimeout(evaluarRespuesta, DURACION_PREGUNTA_MS);
      }

      function evaluarRespuesta() {
        if (evaluando) return;
        evaluando = true;

        const p = preguntas[indiceActual];
        // El backend manda 0 = izquierda, 1 = derecha; lo traducimos a los carriles reales
        const carrilCorrectoReal = p.carril_correcto === 0 ? CARRIL_IZQUIERDA : CARRIL_DERECHA;
        // Quedarse parado en el centro cuenta como no responder: siempre es fallo
        const acerto = carrilActual !== CARRIL_CENTRO && carrilActual === carrilCorrectoReal;

        if (acerto) {
          sonidoAcierto();
          actualizarPuntaje(p.puntaje);
          (carrilActual === CARRIL_IZQUIERDA ? carrilIzqElement : carrilDerElement).classList.add('correcto');
        } else {
          sonidoChoque();
          vidas--;
          actualizarVidas();
          if (carrilActual !== CARRIL_CENTRO) {
            (carrilActual === CARRIL_IZQUIERDA ? carrilIzqElement : carrilDerElement).classList.add('incorrecto');
          }
          (carrilCorrectoReal === CARRIL_IZQUIERDA ? carrilIzqElement : carrilDerElement).classList.add('correcto');
        }

        setTimeout(() => {
          if (vidas <= 0) {
            terminarPorVidas();
            return;
          }
          indiceActual++;
          cargarPregunta();
        }, 900);
      }

      fetch(`obtener_preguntas_carrera.php?clase_id=${CLASE_ID}`)
        .then(response => response.json())
        .then(data => {
          if (data.error) {
            Swal.fire({
              title: 'No se pudo cargar el juego',
              text: data.error,
              icon: 'warning',
              confirmButtonText: 'Volver',
              confirmButtonColor: '#5B2C8C'
            }).then(() => window.location.href = '../Clases/index.php');
            return;
          }

          preguntas = data;
          posicionarVehiculo();
          cargarPregunta();
        })
        .catch(error => {
          console.error('Error al obtener las preguntas:', error);
        });
    </script>
</body>
</html>