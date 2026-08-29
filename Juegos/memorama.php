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
    $stmt = $conexion->prepare("SELECT habilitado FROM accesos_juego WHERE clase_id = ? AND estudiante_id = ? AND juego = 'memorama'");
    $stmt->bind_param("ii", $clase_id, $_SESSION['usuario_id']);
    $stmt->execute();
    $acceso = $stmt->get_result()->fetch_assoc();

    if (!$acceso || (int)$acceso['habilitado'] !== 1) {
        $_SESSION['error'] = "Tu profesor todavía no te ha dado acceso a este juego.";
        header("Location: ../Clases/index.php");
        exit();
    }

    $stmt = $conexion->prepare("UPDATE accesos_juego SET habilitado = 0, usado_en = NOW() WHERE clase_id = ? AND estudiante_id = ? AND juego = 'memorama'");
    $stmt->bind_param("ii", $clase_id, $_SESSION['usuario_id']);
    $stmt->execute();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/juegos.css?v=7">
    <link rel="icon" type="img/png" href="../img/LogoEdutrivia (1).png">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Memorama</title>
</head>
<body class="pantalla-memorama">

    <div class="carrera-header" style="width:min(94%, 900px);">
        <span>Puntaje: <span id="puntaje-count">0</span></span>
        <span>Intentos: <span id="intentos-count">0</span></span>
        <span>Tiempo: <span id="tiempo-count">00:00</span></span>
        <span>Parejas: <span id="parejas-count">0</span>/<span id="parejas-total">0</span></span>
    </div>

    <div class="memorama-grid" id="grid"></div>

    <script>
      const CLASE_ID = <?= $clase_id ?>;

      /* Sonidos generados con Web Audio API (mismo patrón que los otros juegos) */
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
      function sonidoError() { tono(200, 220, 'sawtooth', 0.1); }
      function sonidoFin() {
        tono(523, 130, 'sine', 0.15, 0);
        tono(659, 130, 'sine', 0.15, 130);
        tono(784, 220, 'sine', 0.15, 260);
      }

      const gridElement = document.getElementById('grid');
      const puntajeElement = document.getElementById('puntaje-count');
      const intentosElement = document.getElementById('intentos-count');
      const tiempoElement = document.getElementById('tiempo-count');
      const parejasCountElement = document.getElementById('parejas-count');
      const parejasTotalElement = document.getElementById('parejas-total');

      let cartas = [];
      let volteadas = [];
      let parejasEncontradas = 0;
      let totalParejas = 0;
      let puntaje = 0;
      let intentos = 0;
      let bloqueado = false;
      let horaInicio = null;
      let intervaloTiempo = null;

      function formatearTiempo(segundosTotales) {
        const minutos = Math.floor(segundosTotales / 60);
        const segundos = segundosTotales % 60;
        return String(minutos).padStart(2, '0') + ':' + String(segundos).padStart(2, '0');
      }

      function segundosTranscurridos() {
        return Math.floor((Date.now() - horaInicio) / 1000);
      }

      function iniciarCronometro() {
        horaInicio = Date.now();
        intervaloTiempo = setInterval(() => {
          tiempoElement.textContent = formatearTiempo(segundosTranscurridos());
        }, 1000);
      }

      function detenerCronometro() {
        clearInterval(intervaloTiempo);
      }

      /* El tablero se acopla en una cuadrícula lo más cuadrada posible según
         cuántas cartas haya (10 cartas -> 5x2, 20 cartas -> 5x4, etc.) */
      function calcularColumnas(totalCartas) {
        if (totalCartas <= 12) return Math.ceil(totalCartas / 2);
        return Math.ceil(Math.sqrt(totalCartas));
      }

      function guardarYRedirigir(puntajeFinal, tiempoFinal) {
        fetch('guardar_resultado.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ clase_id: CLASE_ID, juego: 'memorama', puntaje: puntajeFinal, tiempo: tiempoFinal })
        }).finally(() => {
          window.location.href = 'encuesta.php';
        });
      }

      function renderTablero(datos) {
        totalParejas = datos.length / 2;
        parejasTotalElement.textContent = totalParejas;
        gridElement.style.setProperty('--cols', calcularColumnas(datos.length));

        datos.forEach(carta => {
          const el = document.createElement('button');
          el.className = 'memorama-carta';
          el.dataset.id = carta.id;
          el.innerHTML = `
            <div class="memorama-carta-inner">
              <div class="memorama-cara memorama-cara-atras">🧠</div>
              <div class="memorama-cara memorama-cara-frente">${carta.texto}</div>
            </div>
          `;
          el.addEventListener('click', () => voltear(carta, el));
          gridElement.appendChild(el);
        });

        iniciarCronometro();
      }

      function voltear(carta, el) {
        if (bloqueado) return;
        if (el.classList.contains('volteada') || el.classList.contains('encontrada')) return;
        if (volteadas.length === 2) return;

        el.classList.add('volteada');
        volteadas.push({ carta, el });

        if (volteadas.length === 2) {
          intentos++;
          intentosElement.textContent = intentos;
          bloqueado = true;

          const [a, b] = volteadas;
          const esPareja = a.carta.pareja_id === b.carta.pareja_id;

          setTimeout(() => {
            if (esPareja) {
              sonidoAcierto();
              a.el.classList.add('encontrada');
              b.el.classList.add('encontrada');
              puntaje += a.carta.puntaje;
              puntajeElement.textContent = puntaje;
              parejasEncontradas++;
              parejasCountElement.textContent = parejasEncontradas;
            } else {
              sonidoError();
              a.el.classList.remove('volteada');
              b.el.classList.remove('volteada');
            }

            volteadas = [];
            bloqueado = false;

            if (parejasEncontradas === totalParejas) {
              detenerCronometro();
              const tiempoFinal = segundosTranscurridos();
              tiempoElement.textContent = formatearTiempo(tiempoFinal);
              sonidoFin();
              Swal.fire({
                title: '¡Completaste el memorama! 🎉',
                text: `Terminaste con ${puntaje} puntos en ${intentos} intentos y ${formatearTiempo(tiempoFinal)}.`,
                icon: 'success',
                confirmButtonText: 'Continuar',
                confirmButtonColor: '#5B2C8C'
              }).then(() => guardarYRedirigir(puntaje, tiempoFinal));
            }
          }, esPareja ? 500 : 900);
        }
      }

      fetch(`obtener_preguntas_memorama.php?clase_id=${CLASE_ID}`)
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

          cartas = data;
          renderTablero(cartas);
        })
        .catch(error => {
          console.error('Error al obtener las preguntas:', error);
        });
    </script>
</body>
</html>