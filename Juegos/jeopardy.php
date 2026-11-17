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
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" type="img/png" href="../img/LogoEdutrivia (1).png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Jeopardy - Edutrivia</title>
    <style>
        :root {
            --j-purple-dark: #1e1b4b;
            --j-purple: #6366f1;
            --j-purple-light: #818cf8;
            --j-green: #10b981;
            --j-red: #ef4444;
            --j-orange: #f59e0b;
        }

        * {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            box-sizing: border-box;
        }

        body.pantalla-jeopardy {
            margin: 0;
            min-height: 100vh;
            background: radial-gradient(circle at top left, #312e81 0%, transparent 55%),
                        radial-gradient(circle at bottom right, #3730a3 0%, transparent 50%),
                        linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            color: #f8fafc;
            padding: 20px;
        }

        .app {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        /* Encabezado Principal */
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 16px 24px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }

        .top-header h1 {
            font-size: 1.5rem;
            font-weight: 800;
            margin: 0;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .score {
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
            margin: 0;
        }

        .score-count {
            font-size: 1.2rem;
            font-weight: 800;
        }

        /* Tablero */
        .board {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .column {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 20px;
            padding: 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .column header {
            text-transform: uppercase;
            letter-spacing: 0.03em;
            font-size: 0.85rem;
            font-weight: 800;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: #fff;
            text-align: center;
            min-height: 3.5em;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .column ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .column li {
            margin: 0;
        }

        .column button.clue-card {
            height: 3.6em;
            width: 100%;
            color: #ffb703;
            font-size: 1.2rem;
            font-weight: 800;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.06);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .column button.clue-card:hover {
            background: rgba(99, 102, 241, 0.2);
            border-color: var(--j-purple);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }

        .column button.clue-card.used {
            visibility: hidden;
            opacity: 0;
            pointer-events: none;
        }

        /* Modal de Pregunta */
        .card-modal {
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 1000;
        }

        .card-modal.visible {
            opacity: 1;
            pointer-events: initial;
        }

        .card-modal.showing-result .result {
            display: block;
        }

        .card-modal-inner {
            width: 100%;
            max-width: 560px;
            background: #ffffff;
            color: #0f172a;
            border-radius: 24px;
            padding: 35px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        }

        .card-modal .clue-text {
            font-size: clamp(1.1rem, 3vw, 1.4rem);
            font-weight: 700;
            margin-bottom: 25px;
            line-height: 1.5;
            color: #0f172a;
        }

        .result,
        .card-modal.showing-result form {
            display: none;
        }

        .card-modal form {
            display: flex;
            gap: 12px;
        }

        .card-modal form input[type="text"] {
            flex-grow: 1;
            height: 50px;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            background: #faf8fc;
            text-align: center;
            font-size: 1rem;
            font-weight: 600;
            padding: 0 16px;
            color: #0f172a;
            transition: border-color 0.2s;
        }

        .card-modal form input[type="text"]:focus {
            outline: none;
            border-color: var(--j-purple);
        }

        .card-modal form button[type="submit"] {
            padding: 0 24px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            color: #fff;
            background: var(--j-purple);
            border: 0;
            border-radius: 14px;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
        }

        .card-modal form button[type="submit"]:hover {
            background: #4f46e5;
            transform: translateY(-2px);
        }

        .result_success, .result_fail {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .result_success { color: var(--j-green); }
        .result_fail { color: var(--j-red); }

        .result_correct-answer {
            font-size: 0.95rem;
            color: #64748b;
        }

        .result_correct-answer-text {
            font-weight: 800;
            font-size: 1.1rem;
            display: block;
            margin-top: 4px;
            color: var(--j-purple);
        }

        /* Estilo SweetAlert acorde */
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
<body class="pantalla-jeopardy">
    <div class="app">
        <header class="top-header">
            <h1><i class='bx bx-brain' style="color: var(--j-purple);"></i> Edutrivia Jeopardy</h1>
            <p class="score"><i class='bx bx-trophy'></i> Puntuación: <span class="score-count">0</span></p>
        </header>
        <div class="board">
        </div>
        <div class="card-modal">
            <div class="card-modal-inner">
                <h2 class="clue-text"></h2>
                <form autocomplete="off">
                    <input name="user-answer" type="text" placeholder="Escribe tu respuesta..." />
                    <button type="submit">Evaluar</button>
                </form>
                <div class="result">
                    <p class="result_success">¡CORRECTO! 🎉</p>
                    <p class="result_fail">INCORRECTO ❌</p>
                    <p class="result_correct-answer">
                        La respuesta correcta era: 
                        <span class="result_correct-answer-text"></span>
                    </p>
                </div>
            </div>
        </div>
    </div>
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
                            confirmButtonColor: '#6366f1'
                        }).then(() => window.location.href = '../Clases/index.php');
                        return;
                    }

                    if (data.length === 0) {
                        Swal.fire({
                            title: 'No hay preguntas disponibles',
                            text: 'Esta clase todavía no tiene mazos activos con preguntas.',
                            icon: 'info',
                            confirmButtonText: 'Volver',
                            confirmButtonColor: '#6366f1'
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
                confirmButtonColor: '#6366f1'
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