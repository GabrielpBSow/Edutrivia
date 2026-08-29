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
    $stmt = $conexion->prepare("SELECT habilitado FROM accesos_juego WHERE clase_id = ? AND estudiante_id = ? AND juego = 'jeopardy'");
    $stmt->bind_param("ii", $clase_id, $_SESSION['usuario_id']);
    $stmt->execute();
    $acceso = $stmt->get_result()->fetch_assoc();

    if (!$acceso || (int)$acceso['habilitado'] !== 1) {
        $_SESSION['error'] = "Tu profesor todavía no te ha dado acceso a este juego.";
        header("Location: ../Clases/index.php");
        exit();
    }

    $stmt = $conexion->prepare("UPDATE accesos_juego SET habilitado = 0, usado_en = NOW() WHERE clase_id = ? AND estudiante_id = ? AND juego = 'jeopardy'");
    $stmt->bind_param("ii", $clase_id, $_SESSION['usuario_id']);
    $stmt->execute();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/juegos.css?v=3">
    <link rel="icon" type="img/png" href="../img/LogoEdutrivia (1).png">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Jeopardy</title>
</head>
<body class="pantalla-jeopardy">
    <div class="app">
        <header class="top-header">
            <h1>Edutrivia</h1>
            <p class="score">Score: <span class="score-count">0</span></p>
        </header>
        <div class="board">
        </div>
        <div class="card-modal">
            <div class="card-modal-inner">
                <h2 class="clue-text"></h2>
                <form autocomplete="off">
                    <input name="user-answer" type="text"/>
                    <button type="submit">PREGUNTA</button>
                </form>
                <div class="result">
                    <p class="result_success">CORRECTO</p>
                    <p class="result_fail">INCORRECTO</p>
                    <p class="result_correct-answer">
                        La respuesta correcta es: <span class="result_correct-answer-text"></span>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <script>
    const CLASE_ID = <?= $clase_id ?>;

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
        tono(880, 150, 'sine', 0.13, 100);
    }

    function sonidoError() {
        tono(200, 250, 'sawtooth', 0.12);
    }

    function sonidoFin() {
        tono(523, 130, 'sine', 0.15, 0);
        tono(659, 130, 'sine', 0.15, 130);
        tono(784, 220, 'sine', 0.15, 260);
    }

    class Edutrivia {
        constructor(element, options={}) {
            this.categories = [];
            this.clues = {};
            this.currentClue = null;
            this.score = 0;
            this.usedCards = 0;

            this.boardElement = element.querySelector(".board");
            this.scoreCountElement = element.querySelector(".score-count");
            this.formElement = element.querySelector("form");
            this.inputElement = element.querySelector("input[name=user-answer]");
            this.modalElement = element.querySelector(".card-modal");
            this.clueTextElement = element.querySelector(".clue-text");
            this.resultTextElement = element.querySelector(".result_correct-answer-text");
            this.successTextElement = element.querySelector(".result_success");
            this.failTextElement = element.querySelector(".result_fail");

            this.initGame();
        }

        initGame() {
            this.boardElement.addEventListener("click", event => {
                if (event.target.dataset.clueId) {
                    this.handleClueClick(event);
                }
            });
            this.formElement.addEventListener("submit", event => {
                this.handleFormSubmit(event);
            });

            this.updateScore(0);
            this.fetchCategories();
        }

        fetchCategories() {
            fetch(`Obtener_Preguntas.php?clase_id=${CLASE_ID}`)
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

                    if (data.length === 0) {
                        Swal.fire({
                            title: 'No hay preguntas disponibles',
                            text: 'Esta clase todavía no tiene mazos activos con preguntas.',
                            icon: 'info',
                            confirmButtonText: 'Volver',
                            confirmButtonColor: '#5B2C8C'
                        }).then(() => window.location.href = '../Clases/index.php');
                        return;
                    }

                    const categoriesMap = {};

                    data.forEach(item => {
                        const clueId = `${item.Categoria}-${item.id}-${item.Puntaje}`;

                        if (!categoriesMap[item.Categoria]) {
                            categoriesMap[item.Categoria] = {
                                title: item.Categoria,
                                clues: []
                            };
                        }

                        categoriesMap[item.Categoria].clues.push(clueId);

                        this.clues[clueId] = {
                            question: item.Pregunta,
                            answer: item.Respuesta,
                            value: item.Puntaje
                        };
                    });

                    for (const category in categoriesMap) {
                        this.categories.push(categoriesMap[category]);
                        this.renderCategory(categoriesMap[category]);
                    }
                })
                .catch(error => {
                    console.error('Error al obtener las preguntas:', error);
                });
        }

        renderCategory(category) {
            let column = document.createElement("div");
            column.classList.add("column");

            let header = document.createElement("header");
            header.textContent = category.title;
            column.appendChild(header);

            const valoresUnicos = [...new Set(category.clues.map(id => this.clues[id].value))].sort((a, b) => a - b);

            const cluesByValue = {};
            valoresUnicos.forEach(v => cluesByValue[v] = []);

            category.clues.forEach(clueId => {
                let clue = this.clues[clueId];
                cluesByValue[clue.value].push({ id: clueId, clue: clue });
            });

            valoresUnicos.forEach(value => {
                let ul = document.createElement("ul");
                cluesByValue[value].forEach(clueData => {
                    let li = document.createElement("li");
                    li.innerHTML = `<button data-clue-id="${clueData.id}" class="clue-card">${value}</button>`;
                    ul.appendChild(li);
                });
                column.appendChild(ul);
            });

            this.boardElement.appendChild(column);
        }

         updateScore(change) {
               this.score += change;
               this.scoreCountElement.textContent = this.score;
         }

         handleClueClick(event) {
            var clue = this.clues[event.target.dataset.clueId];
            event.target.classList.add("used");
            this.inputElement.value = "";
            this.currentClue = clue;
            this.clueTextElement.textContent = this.currentClue.question;
            this.resultTextElement.textContent = this.currentClue.answer;
            this.modalElement.classList.remove("showing-result");
            this.modalElement.classList.add("visible");
            this.inputElement.focus();

            this.usedCards++;
        }

        checkIfAllCardsUsed() {
            const totalQuestions = this.categories.reduce((acc, category) => acc + category.clues.length, 0);
            if (this.usedCards === totalQuestions) {
                this.finishGame();
            }
        }

        finishGame() {
            const finalScore = this.score;

            sonidoFin();
            Swal.fire({
                title: '¡Juego terminado! 🎉',
                text: `Terminaste con ${finalScore} puntos.`,
                icon: 'success',
                confirmButtonText: 'Continuar',
                confirmButtonColor: '#5B2C8C'
            }).then(() => {
                fetch('guardar_resultado.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ clase_id: CLASE_ID, juego: 'jeopardy', puntaje: finalScore })
                }).finally(() => {
                    window.location.href = 'encuesta.php';
                });
            });
        }

         handleFormSubmit(event) {
            event.preventDefault();
            var isCorrect = this.cleanseAnswer(this.inputElement.value) === this.cleanseAnswer(this.currentClue.answer);

            if (isCorrect) {
                this.updateScore(parseInt(this.currentClue.value));
                sonidoAcierto();
            } else {
                sonidoError();
            }

            this.revealAnswer(isCorrect);
        }

         cleanseAnswer(input="") {
               var friendlyAnswer = input.toLowerCase();
               friendlyAnswer = friendlyAnswer.replace("<i>", "");
               friendlyAnswer = friendlyAnswer.replace("</i>", "");
               friendlyAnswer = friendlyAnswer.replace(/ /g, "");
               friendlyAnswer = friendlyAnswer.replace(/"/g, "");
               friendlyAnswer = friendlyAnswer.replace(/^a /, "");
               friendlyAnswer = friendlyAnswer.replace(/^an /, "");
               return friendlyAnswer.trim();
         }

         revealAnswer(isCorrect) {
               this.successTextElement.style.display = isCorrect ? "block" : "none";
               this.failTextElement.style.display = !isCorrect ? "block" : "none";
               this.modalElement.classList.add("showing-result");
               setTimeout(() => {
                  this.modalElement.classList.remove("visible");
                  this.checkIfAllCardsUsed();
               }, 3000);
         }
      }

      const game = new Edutrivia(document.querySelector(".app"), {});
    </script>
</body>
</html>