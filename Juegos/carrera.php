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
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" type="img/png" href="../img/LogoEdutrivia (1).png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Carrera de Conocimiento - Edutrivia</title>
    <style>
        :root {
            --j-purple-dark: #1e1b4b;
            --j-purple: #6366f1;
            --j-red: #ef4444;
            --j-green: #10b981;
            --j-orange: #f59e0b;
        }

        * {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            box-sizing: border-box;
        }

        body.pantalla-carrera {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top left, #312e81 0%, transparent 55%),
                        radial-gradient(circle at bottom right, #3730a3 0%, transparent 50%),
                        linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            color: #f8fafc;
            padding: 20px;
            overflow-x: hidden;
        }

        /* Encabezado del juego */
        .carrera-header {
            width: 100%;
            max-width: 640px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 14px 22px;
            margin-bottom: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            font-weight: 700;
            font-size: 1.05rem;
        }

        .carrera-vidas {
            color: var(--j-red);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .carrera-puntaje {
            color: #f59e0b;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .carrera-progreso {
            color: #c7d2fe;
            background: rgba(99, 102, 241, 0.15);
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.9rem;
        }

        /* Caja de pregunta */
        .carrera-pregunta-caja {
            width: 100%;
            max-width: 640px;
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 12px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .carrera-pregunta-texto {
            font-size: clamp(1.1rem, 2.5vw, 1.35rem);
            font-weight: 700;
            margin: 0;
            color: #ffffff;
            line-height: 1.4;
        }

        /* Barra de tiempo */
        .carrera-timer-track {
            width: 100%;
            max-width: 640px;
            height: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 999px;
            overflow: hidden;
            margin-bottom: 15px;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
        }

        .carrera-timer-fill {
            height: 100%;
            width: 100%;
            background: linear-gradient(90deg, #6366f1, #f59e0b);
            border-radius: 999px;
            transition: width linear;
        }

        /* Pista de carreras */
        .carrera-pista {
            position: relative;
            width: 100%;
            max-width: 640px;
            height: 380px;
            background: linear-gradient(180deg, #181824 0%, #111118 100%);
            border: 2px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            overflow: hidden;
            display: flex;
            box-shadow: inset 0 0 50px rgba(0,0,0,0.6), 0 20px 40px rgba(0,0,0,0.4);
        }

        .carrera-carril {
            flex: 1;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            border-right: 2px dashed rgba(255, 255, 255, 0.1);
            padding: 15px 5px;
        }

        .carrera-carril:last-child {
            border-right: none;
        }

        .carrera-opcion {
            margin-top: 10px;
            background: rgba(255, 255, 255, 0.95);
            color: #0f172a;
            font-weight: 700;
            font-size: clamp(0.8rem, 2vw, 0.95rem);
            border-radius: 14px;
            padding: 12px 10px;
            width: 90%;
            text-align: center;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            transition: all 0.25s ease;
            word-break: break-word;
        }

        .carrera-opcion-centro {
            background: transparent;
            color: rgba(255, 255, 255, 0.2);
            box-shadow: none;
            font-size: 1.5rem;
            border: none;
        }

        .carrera-carril.correcto .carrera-opcion {
            background: var(--j-green) !important;
            color: #fff !important;
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.6);
        }

        .carrera-carril.incorrecto .carrera-opcion {
            background: var(--j-red) !important;
            color: #fff !important;
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.6);
        }

        .carrera-vehiculo {
            position: absolute;
            bottom: 6%;
            font-size: 3rem;
            transition: left 0.18s cubic-bezier(0.4, 0, 0.2, 1);
            filter: drop-shadow(0 8px 12px rgba(0,0,0,0.6));
            z-index: 10;
        }

        /* Controles móviles / botones */
        .carrera-controles {
            display: flex;
            gap: 15px;
            margin-top: 15px;
            width: 100%;
            max-width: 640px;
        }

        .carrera-controles button {
            flex: 1;
            padding: 14px;
            font-size: 1.5rem;
            border: none;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .carrera-controles button:hover {
            background: rgba(99, 102, 241, 0.3);
            border-color: var(--j-purple);
            transform: translateY(-2px);
        }

        .carrera-controles button:active {
            transform: translateY(0);
        }

        /* Estilo SweetAlert personalizado */
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
<body class="pantalla-carrera">

    <div class="carrera-header">
        <div class="carrera-vidas">
            <i class='bx bxs-heart' style="font-size: 1.3rem;"></i> <span id="vidas-count">3</span>
        </div>
        <div class="carrera-puntaje">
            <i class='bx bx-trophy' style="font-size: 1.3rem;"></i> <span id="puntaje-count">0</span> pts
        </div>
        <div class="carrera-progreso">
            Pregunta <span id="pregunta-num">0</span>/10
        </div>
    </div>

    <div class="carrera-pregunta-caja">
        <p class="carrera-pregunta-texto" id="pregunta-texto">Cargando carrera...</p>
    </div>

    <div class="carrera-timer-track">
        <div class="carrera-timer-fill" id="timer-fill"></div>
    </div>

    <div class="carrera-pista" id="pista">
        <div class="carril-izquierda carrera-carril">
            <div class="carrera-opcion" id="opcion-izquierda">-</div>
        </div>
        <div class="carril-centro carrera-carril">
            <div class="carrera-opcion carrera-opcion-centro"><i class='bx bx-chevrons-up'></i></div>
        </div>
        <div class="carril-derecha carrera-carril">
            <div class="carrera-opcion" id="opcion-derecha">-</div>
        </div>
        <div class="carrera-vehiculo" id="vehiculo">🏎️</div>
    </div>

    <div class="carrera-controles">
        <button id="btn-izquierda" title="Mover a la izquierda"><i class='bx bx-left-arrow-alt'></i></button>
        <button id="btn-derecha" title="Mover a la derecha"><i class='bx bx-right-arrow-alt'></i></button>
    </div>

    <script>
      const CLASE_ID = <?= $clase_id ?>;
      const DURACION_PREGUNTA_MS = 6000;

      const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

      function tono(frecuencia, duracionMs, tipo = 'sine', volumen = 0.15, retrasoMs = 0) {
        setTimeout(() => {
          if (audioCtx.state === 'suspended') { audioCtx.resume(); }
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
        const posiciones = { [CARRIL_IZQUIERDA]: '12%', [CARRIL_CENTRO]: '45%', [CARRIL_DERECHA]: '78%' };
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
          confirmButtonColor: '#6366f1'
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
          confirmButtonColor: '#6366f1'
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
        const carrilCorrectoReal = p.carril_correcto === 0 ? CARRIL_IZQUIERDA : CARRIL_DERECHA;
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
              confirmButtonColor: '#6366f1'
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