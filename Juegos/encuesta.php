<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit();
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
    <title>Encuesta de Satisfacción - Edutrivia</title>
    <style>
        :root {
            --j-purple-dark: #1e1b4b;
            --j-purple: #6366f1;
            --j-purple-light: #818cf8;
            --j-green: #10b981;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-light: #e2e8f0;
        }

        * {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            box-sizing: border-box;
        }

        body.pantalla-encuesta {
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

        .encuesta-card {
            width: 100%;
            max-width: 480px;
            background: rgba(255, 255, 255, 0.96);
            border-radius: 28px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
            animation: fadeInScale 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(15px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .encuesta-icono {
            width: 70px;
            height: 70px;
            background: #ede9fe;
            color: var(--j-purple);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            margin: 0 auto 20px auto;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.2);
        }

        .encuesta-card h2 {
            color: var(--text-main);
            font-size: 1.6rem;
            font-weight: 800;
            margin: 0 0 8px 0;
        }

        .encuesta-sub {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin: 0 0 25px 0;
            line-height: 1.5;
        }

        .options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 25px;
        }

        .option {
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 16px 12px;
            border-radius: 18px;
            border: 2px solid var(--border-light);
            background: #faf8fc;
            transition: all 0.2s ease;
        }

        .option input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .option-emoji {
            font-size: 2.4rem;
            transition: transform 0.2s ease;
        }

        .option-texto {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-muted);
        }

        .option:hover {
            transform: translateY(-3px);
            border-color: #c7d2fe;
            background: #f5f3ff;
        }

        .option:hover .option-emoji {
            transform: scale(1.1);
        }

        .option-elegida {
            border-color: var(--j-purple) !important;
            background: #ede9fe !important;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.15);
        }

        .option-elegida .option-texto {
            color: var(--j-purple-dark) !important;
        }

        #submitBtn {
            width: 100%;
            background: var(--j-purple);
            color: #fff;
            border: none;
            border-radius: 16px;
            padding: 14px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.35);
        }

        #submitBtn:hover:not(:disabled) {
            background: #4f46e5;
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(99, 102, 241, 0.45);
        }

        #submitBtn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            box-shadow: none;
            background: #94a3b8;
        }
    </style>
</head>
<body class="pantalla-encuesta">

    <div class="encuesta-card">
        <div class="encuesta-icono">
            <i class='bx bx-message-rounded-dots'></i>
        </div>
        <h2>¿Qué te pareció el juego?</h2>
        <p class="encuesta-sub">Tu opinión ayuda a tu profesor a mejorar las próximas partidas y dinámicas.</p>

        <form id="encuestaForm" method="POST" action="guardar_encuesta.php">
            <div class="options">
                <label class="option" onclick="seleccionar(this)">
                    <input type="radio" name="opcion" value="1">
                    <span class="option-emoji">🤩</span>
                    <span class="option-texto">Excelente</span>
                </label>
                <label class="option" onclick="seleccionar(this)">
                    <input type="radio" name="opcion" value="2">
                    <span class="option-emoji">🙂</span>
                    <span class="option-texto">Bueno</span>
                </label>
                <label class="option" onclick="seleccionar(this)">
                    <input type="radio" name="opcion" value="3">
                    <span class="option-emoji">😐</span>
                    <span class="option-texto">Regular</span>
                </label>
                <label class="option" onclick="seleccionar(this)">
                    <input type="radio" name="opcion" value="4">
                    <span class="option-emoji">😕</span>
                    <span class="option-texto">Malo</span>
                </label>
            </div>

            <button type="submit" id="submitBtn" class="btn" disabled>Enviar mi opinión</button>
        </form>
    </div>

    <script>
        function seleccionar(labelElement) {
            document.querySelectorAll('.option').forEach(el => el.classList.remove('option-elegida'));
            labelElement.classList.add('option-elegida');
            
            // Aseguramos que el radio button interno quede marcado
            const radio = labelElement.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;

            document.getElementById('submitBtn').disabled = false;
        }
    </script>
</body>
</html>