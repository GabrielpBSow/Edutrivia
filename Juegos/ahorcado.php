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
    $stmt = $conexion->prepare("SELECT habilitado FROM accesos_juego WHERE clase_id = ? AND estudiante_id = ? AND juego = 'ahorcado'");
    $stmt->bind_param("ii", $clase_id, $_SESSION['usuario_id']);
    $stmt->execute();
    $acceso = $stmt->get_result()->fetch_assoc();

    if (!$acceso || (int)$acceso['habilitado'] !== 1) {
        $_SESSION['error'] = "Tu profesor todavía no te ha dado acceso a este juego.";
        header("Location: ../Clases/index.php");
        exit();
    }

    $stmt = $conexion->prepare("UPDATE accesos_juego SET habilitado = 0, usado_en = NOW() WHERE clase_id = ? AND estudiante_id = ? AND juego = 'ahorcado'");
    $stmt->bind_param("ii", $clase_id, $_SESSION['usuario_id']);
    $stmt->execute();
}
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" type="img/png" href="../img/LogoEdutrivia (1).png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Ahorcado - Edutrivia</title>
    <style>
        :root {
            --j-purple-dark: #1e1b4b;
            --j-purple: #6366f1;
            --j-purple-light: #818cf8;
            --j-red: #ef4444;
            --j-green: #10b981;
            --j-orange: #f59e0b;
            --bg-main: #f8fafc;
        }

        * {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            box-sizing: border-box;
        }

        body.pantalla-ahorcado {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top left, #312e81 0%, transparent 55%),
                        radial-gradient(circle at bottom right, #3730a3 0%, transparent 50%),
                        linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            color: #f8fafc;
            padding: 20px;
        }

        #content-main {
            width: 100%;
            max-width: 520px;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 35px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        h1 {
            font-size: 1.8rem;
            font-weight: 800;
            margin: 0;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .score-container {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.95rem;
            color: #ffb703;
        }

        .score-count {
            font-size: 1.2rem;
            font-weight: 800;
        }

        #pista {
            background: rgba(99, 102, 241, 0.15);
            color: #c7d2fe;
            border: 1px solid rgba(99, 102, 241, 0.3);
            padding: 8px 18px;
            border-radius: 14px;
            font-size: 0.95rem;
            font-weight: 600;
            margin: 0;
            text-align: center;
        }

        #ahorcado {
            width: 160px;
            height: 160px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.06);
            overflow: hidden;
            position: relative;
        }

        /* Estilos SVG integrados para las partes del ahorcado */
        .svg-parte {
            display: none;
            stroke: #c7d2fe;
            stroke-width: 4;
            stroke-linecap: round;
            fill: none;
        }

        #output {
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: 0.2em;
            color: #ffffff;
            margin: 5px 0;
            text-align: center;
            word-break: break-all;
        }

        .input-group {
            display: flex;
            gap: 10px;
            width: 100%;
            max-width: 340px;
        }

        input[type="text"] {
            flex: 1;
            height: 50px;
            background: rgba(255, 255, 255, 0.06);
            border: 2px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            text-align: center;
            font-size: 1.3rem;
            font-weight: 700;
            color: white;
            transition: all 0.2s;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: var(--j-purple);
            background: rgba(255, 255, 255, 0.1);
        }

        input[type="text"]::placeholder {
            color: rgba(255, 255, 255, 0.3);
            font-size: 0.85rem;
            font-weight: 500;
        }

        button#calcular {
            background: var(--j-purple);
            color: white;
            border: none;
            border-radius: 14px;
            padding: 0 20px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
            white-space: nowrap;
        }

        button#calcular:hover {
            background: #4f46e5;
            transform: translateY(-2px);
        }

        #resultado {
            font-size: 1.1rem;
            margin: 0;
            font-weight: 600;
        }

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
  <body class="pantalla-ahorcado">
    <div id="content-main">
      <h1><i class='bx bx-joystick' style="color: var(--j-purple);"></i> El Ahorcado</h1>
      
      <div class="score-container">
        <i class='bx bx-trophy'></i> Puntaje: <span id="puntaje-count" class="score-count">0</span>
      </div>

      <p id="pista">Cargando pista...</p>
      
      <div id="ahorcado">
        <!-- SVG interactivo del ahorcado que se va mostrando por fallos -->
        <svg width="120" height="140" viewBox="0 0 120 140">
            <!-- Base y horca (siempre visible o base) -->
            <line x1="10" y1="130" x2="70" y2="130" stroke="#818cf8" stroke-width="4" stroke-linecap="round"/>
            <line x1="40" y1="20" x2="40" y2="130" stroke="#818cf8" stroke-width="4" stroke-linecap="round"/>
            <line x1="40" y1="20" x2="90" y2="20" stroke="#818cf8" stroke-width="4" stroke-linecap="round"/>
            <line x1="90" y1="20" x2="90" y2="40" stroke="#818cf8" stroke-width="4" stroke-linecap="round"/>

            <!-- 1. Cabeza -->
            <circle cx="90" cy="55" r="12" class="svg-parte" id="parte-1"/>
            <!-- 2. Tronco -->
            <line x1="90" y1="67" x2="90" y2="100" class="svg-parte" id="parte-2"/>
            <!-- 3. Brazo izquierdo -->
            <line x1="90" y1="75" x2="75" y2="90" class="svg-parte" id="parte-3"/>
            <!-- 4. Brazo derecho -->
            <line x1="90" y1="75" x2="105" y2="90" class="svg-parte" id="parte-4"/>
            <!-- 5. Pierna izquierda -->
            <line x1="90" y1="100" x2="78" y2="120" class="svg-parte" id="parte-5"/>
            <!-- 6. Pierna derecha -->
            <line x1="90" y1="100" x2="102" y2="120" class="svg-parte" id="parte-6"/>
        </svg>
      </div>

      <p id="output">_ _ _ _</p>
      
      <div class="input-group">
        <input type="text" id="letra" maxlength="1" placeholder="Letra" autofocus autocomplete="off" />
        <button id="calcular">Evaluar</button>
      </div>
      
      <h1 id="resultado"></h1>
    </div>

    <script>
      const CLASE_ID = <?= $clase_id ?>;
      let puntaje = 0;

      String.prototype.replaceAt = function(index, character) {
        return this.substring(0, index) + character + this.substring(index + character.length);
      };

      const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

      function tono(frecuencia, duracionMs, tipo = 'sine', volumen = 0.15, retrasoMs = 0) {
        setTimeout(() => {
          if (audioCtx.state === 'suspended') {
            audioCtx.resume();
          }
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

      function sonidoAcierto() { tono(660, 120); }
      function sonidoError() { tono(160, 200, 'sawtooth', 0.12); }
      function sonidoGanar() {
        tono(523, 130, 'sine', 0.15, 0);
        tono(659, 130, 'sine', 0.15, 130);
        tono(784, 220, 'sine', 0.15, 260);
      }
      function sonidoPerder() {
        tono(300, 200, 'sawtooth', 0.12, 0);
        tono(220, 200, 'sawtooth', 0.12, 200);
        tono(140, 350, 'sawtooth', 0.12, 400);
      }

      const puntajeElement = document.querySelector('#puntaje-count');

      function actualizarPuntaje(cambio) {
        puntaje += cambio;
        if (puntaje < 0) puntaje = 0;
        puntajeElement.textContent = puntaje;
        puntajeElement.style.color = cambio >= 0 ? '#10b981' : '#ef4444';
        setTimeout(() => { puntajeElement.style.color = ''; }, 400);
      }

      function mostrarParteAhorcado(fallos) {
          if (fallos >= 1 && fallos <= 6) {
              const parte = document.getElementById(`parte-${fallos}`);
              if (parte) parte.style.display = 'block';
          }
      }

      function guardarYRedirigir(puntajeFinal) {
        fetch('guardar_resultado.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ clase_id: CLASE_ID, juego: 'ahorcado', puntaje: puntajeFinal })
        }).finally(() => {
          window.location.href = 'encuesta.php';
        });
      }

      fetch(`Obtener_Palabras.php?clase_id=${CLASE_ID}`)
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

          const categorias = data.categorias;

          if (!categorias || categorias.length === 0) {
            Swal.fire({
              title: 'No hay preguntas disponibles',
              text: 'Esta clase todavía no tiene mazos activos con preguntas.',
              icon: 'info',
              confirmButtonText: 'Volver',
              confirmButtonColor: '#6366f1'
            }).then(() => window.location.href = '../Clases/index.php');
            return;
          }

          const elegido = categorias[Math.floor(Math.random() * categorias.length)];
          const categoria = elegido.categoria;

          document.querySelector('#pista').textContent = `Tema: ${elegido.mazo}`;

          let palabraConGuiones = '';
          for (let i = 0; i < categoria.length; i++) {
            if (categoria[i] === ' ') {
              palabraConGuiones += '  ';
            } else {
              palabraConGuiones += '_ ';
            }
          }

          let contadorFallos = 0;
          const letrasIntentadas = new Set();
          document.querySelector('#output').innerHTML = palabraConGuiones;

          const palabraCorrecta = categoria.toLowerCase();
          const button = document.querySelector('#calcular');
          const inputLetra = document.querySelector('#letra');

          function procesarIntento() {
            let letra = inputLetra.value.toLowerCase();
            if (!letra) return;

            if (letrasIntentadas.has(letra)) {
              contadorFallos++;
              actualizarPuntaje(-25);
              inputLetra.value = '';
              mostrarParteAhorcado(contadorFallos);
              if (contadorFallos >= 6) {
                sonidoPerder();
                Swal.fire({
                  title: '¡Has perdido! 😢',
                  text: `Terminaste con ${puntaje} puntos.`,
                  icon: 'error',
                  confirmButtonText: 'Continuar',
                  confirmButtonColor: '#6366f1'
                }).then(() => guardarYRedirigir(puntaje));
                return;
              } else {
                sonidoError();
              }
              return;
            }

            letrasIntentadas.add(letra);

            let letraCorrecta = false;
            for (let i = 0; i < palabraCorrecta.length; i++) {
              if (letra === palabraCorrecta[i]) {
                palabraConGuiones = palabraConGuiones.replaceAt(i * 2, letra);
                letraCorrecta = true;
              }
            }

            if (!letraCorrecta) {
              contadorFallos++;
              actualizarPuntaje(-25);
              mostrarParteAhorcado(contadorFallos);
              if (contadorFallos >= 6) {
                sonidoPerder();
                Swal.fire({
                  title: '¡Has perdido! 😢',
                  text: `Terminaste con ${puntaje} puntos.`,
                  icon: 'error',
                  confirmButtonText: 'Continuar',
                  confirmButtonColor: '#6366f1'
                }).then(() => guardarYRedirigir(puntaje));
                return;
              } else {
                sonidoError();
              }
            } else {
              sonidoAcierto();
              actualizarPuntaje(50);
            }

            if (!palabraConGuiones.includes('_')) {
              actualizarPuntaje(100);
              sonidoGanar();
              Swal.fire({
                title: '¡Has ganado! 🎉',
                text: `Terminaste con ${puntaje} puntos.`,
                icon: 'success',
                confirmButtonText: 'Continuar',
                confirmButtonColor: '#6366f1'
              }).then(() => guardarYRedirigir(puntaje));
              return;
            }

            document.querySelector('#output').innerHTML = palabraConGuiones;
            inputLetra.value = '';
            inputLetra.focus();
          }

          button.addEventListener('click', procesarIntento);
          inputLetra.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
              procesarIntento();
            }
          });
        })
        .catch(error => {
          console.error('Error al obtener palabras:', error);
        });
    </script>
  </body>
</html>