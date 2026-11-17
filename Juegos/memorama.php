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
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" type="img/png" href="../img/LogoEdutrivia (1).png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Memorama - Edutrivia</title>
    <style>
        :root {
            --j-purple-dark: #1e1b4b;
            --j-purple: #6366f1;
            --j-purple-light: #818cf8;
            --j-green: #10b981;
            --j-orange: #f59e0b;
        }

        * {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            box-sizing: border-box;
        }

        body.pantalla-memorama {
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
        }

        /* Encabezado superior unificado */
        .memorama-header {
            width: 100%;
            max-width: 900px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 14px 22px;
            margin-bottom: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            font-weight: 700;
            font-size: 0.95rem;
            flex-wrap: wrap;
            gap: 12px;
        }

        .memorama-header span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Cuadrícula de Cartas */
        .memorama-grid {
            width: 100%;
            max-width: 900px;
            display: grid;
            grid-template-columns: repeat(var(--cols, 5), 1fr);
            gap: 12px;
        }

        @media (max-width: 768px) {
            .memorama-grid {
                grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
            }
        }

        .memorama-carta {
            aspect-ratio: 3 / 4;
            border: none;
            background: transparent;
            padding: 0;
            cursor: pointer;
            perspective: 1000px;
        }

        .memorama-carta-inner {
            position: relative;
            width: 100%;
            height: 100%;
            transition: transform 0.5s cubic-bezier(0.4, 0.2, 0.2, 1);
            transform-style: preserve-3d;
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .memorama-carta.volteada .memorama-carta-inner,
        .memorama-carta.encontrada .memorama-carta-inner {
            transform: rotateY(180deg);
        }

        .memorama-cara {
            position: absolute;
            inset: 0;
            backface-visibility: hidden;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            font-size: clamp(0.75rem, 2vw, 0.95rem);
            font-weight: 700;
            overflow: hidden;
            text-align: center;
            word-break: break-word;
        }

        .memorama-cara-atras {
            background: linear-gradient(135deg, #4f46e5 0%, #312e81 100%);
            color: #fff;
            font-size: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .memorama-cara-frente {
            background: rgba(255, 255, 255, 0.95);
            color: #0f172a;
            transform: rotateY(180deg);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .memorama-carta.encontrada .memorama-cara-frente {
            background: var(--j-green);
            color: #fff;
        }

        .memorama-carta.encontrada {
            cursor: default;
            opacity: 0.9;
        }

        /* SweetAlert Estilo */
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
<body class="pantalla-memorama">

    <div class="memorama-header">
        <span style="color: #f59e0b;"><i class='bx bx-trophy' style="font-size: 1.2rem;"></i> Puntaje: <span id="puntaje-count">0</span></span>
        <span style="color: #c7d2fe;"><i class='bx bx-rotate-left' style="font-size: 1.2rem;"></i> Intentos: <span id="intentos-count">0</span></span>
        <span style="color: #38bdf8;"><i class='bx bx-time-five' style="font-size: 1.2rem;"></i> Tiempo: <span id="tiempo-count">00:00</span></span>
        <span style="color: #10b981;"><i class='bx bx-check-shield' style="font-size: 1.2rem;"></i> Parejas: <span id="parejas-count">0</span>/<span id="parejas-total">0</span></span>
    </div>

    <div class="memorama-grid" id="grid"></div>

    <script>
      const CLASE_ID = <?= $clase_id ?>;

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
              <div class="memorama-cara memorama-cara-atras"><i class='bx bxl-brain'></i></div>
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
                confirmButtonColor: '#6366f1'
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
              confirmButtonColor: '#6366f1'
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