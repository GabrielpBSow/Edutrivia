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
    <link rel="stylesheet" href="../css/juegos.css?v=2">
    <link rel="icon" type="img/png" href="../img/LogoEdutrivia (1).png">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Ahorcado</title>
  </head>
  <body class="pantalla-ahorcado">
    <div id="content-main">
      <h1>El juego del ahorcado</h1>
      <p class="score">Puntaje: <span id="puntaje-count" class="score-count">0</span></p>
      <p id="pista"></p>
      <div id="ahorcado"></div>
      <p id="output"></p>
      <div>
        <input type="text" id="letra" maxlength="1" placeholder="Escribir aquí" autofocus/>
        <button id="calcular">EVALUAR LETRA</button>
      </div>
      <h1 id="resultado"></h1>
    </div>

    <script>
      const CLASE_ID = <?= $clase_id ?>;
      let puntaje = 0;

      String.prototype.replaceAt = function(index, character) {
        return this.substring(0, index) + character + this.substring(index + character.length);
      };

      /* Sonidos generados con Web Audio API (sin archivos externos que subir) */
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

      function sonidoAcierto() {
        tono(660, 120);
      }

      function sonidoError() {
        tono(160, 200, 'sawtooth', 0.12);
      }

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

      function guardarYRedirigir(puntajeFinal) {
        fetch('guardar_resultado.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ clase_id: CLASE_ID, juego: 'ahorcado', puntaje: puntajeFinal })
        }).finally(() => {
          window.location.href = 'encuesta.php';
        });
      }

      const puntajeElement = document.querySelector('#puntaje-count');

      function actualizarPuntaje(cambio) {
        puntaje += cambio;
        if (puntaje < 0) puntaje = 0;
        puntajeElement.textContent = puntaje;
        puntajeElement.style.color = cambio >= 0 ? '#21C87A' : '#EF3E36';
        setTimeout(() => { puntajeElement.style.color = ''; }, 400);
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
              confirmButtonColor: '#5B2C8C'
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
              confirmButtonColor: '#5B2C8C'
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
          const ahorcadoDiv = document.getElementById('ahorcado');

          button.addEventListener('click', () => {
            let letra = document.querySelector('#letra').value.toLowerCase();

            if (!letra) {
              return;
            }

            // Si ya se intentó esta letra antes (acertada o no), ya no da
            // información nueva: cuenta como fallo, igual que una letra incorrecta.
            if (letrasIntentadas.has(letra)) {
              contadorFallos++;
              actualizarPuntaje(-25);
              document.querySelector('#letra').value = '';
              if (contadorFallos === 6) {
                sonidoPerder();
                Swal.fire({
                  title: '¡Has perdido! 😢',
                  text: `Terminaste con ${puntaje} puntos.`,
                  icon: 'error',
                  confirmButtonText: 'Continuar',
                  confirmButtonColor: '#5B2C8C'
                }).then(() => guardarYRedirigir(puntaje));
                return;
              } else {
                sonidoError();
                ahorcadoDiv.innerHTML = `<img src="../img/el-ahorcado${contadorFallos}.jpg" alt="Ahorcado" />`;
              }
              return;
            }

            letrasIntentadas.add(letra);

            let haFallado = true;
            let letraCorrecta = false;

            for (let i = 0; i < palabraCorrecta.length; i++) {
              if (letra === palabraCorrecta[i]) {
                palabraConGuiones = palabraConGuiones.replaceAt(i * 2, letra);
                haFallado = false;
                letraCorrecta = true;
              }
            }

            if (!letraCorrecta) {
              contadorFallos++;
              actualizarPuntaje(-25);
              if (contadorFallos === 6) {
                sonidoPerder();
                Swal.fire({
                  title: '¡Has perdido! 😢',
                  text: `Terminaste con ${puntaje} puntos.`,
                  icon: 'error',
                  confirmButtonText: 'Continuar',
                  confirmButtonColor: '#5B2C8C'
                }).then(() => guardarYRedirigir(puntaje));
                return;
              } else {
                sonidoError();
                ahorcadoDiv.innerHTML = `<img src="../img/el-ahorcado${contadorFallos}.jpg" alt="Ahorcado" />`;
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
                confirmButtonColor: '#5B2C8C'
              }).then(() => guardarYRedirigir(puntaje));
              return;
            }

            document.querySelector('#output').innerHTML = palabraConGuiones;
            document.querySelector('#letra').value = '';
          });
        })
        .catch(error => {
          console.error('Error al obtener palabras:', error);
        });
    </script>
  </body>
</html>