<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/principal.css">
    <link rel="icon" type="img/png" href="img/LogoEdutrivia (1).png">
    <title>Edutrivia - Aprende Jugando</title>

    <meta name="theme-color" content="#1e1b4b">
    <!-- Primary Meta Tags -->
    <meta name="title" content="Edutrivia - Aprende Mientras te Diviertes" />
    <meta name="description" content="Sumérgete en el aprendizaje mientras juegas, una experiencia diseñada para superar la teoría y llevar el conocimiento a un nivel dinámico." />

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

        /* Animaciones fluidas y modernas */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
            100% { transform: translateY(0px); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .hero, section {
            animation: fadeIn 0.8s ease-out forwards;
        }

        .sect_picture img, .nosotros_picture img {
            animation: float 4s ease-in-out infinite;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease;
        }

        .sect_picture img:hover, .nosotros_picture img:hover {
            transform: scale(1.02);
        }

        /* Refinamiento visual general acorde al sistema */
        body {
            background-color: #0f172a;
            color: #f8fafc;
            overflow-x: hidden;
        }

        .hero {
            background: radial-gradient(circle at top left, #312e81 0%, transparent 55%),
                        radial-gradient(circle at bottom right, #3730a3 0%, transparent 50%),
                        linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%) !important;
        }

        .nav_title {
            font-weight: 800;
            letter-spacing: 0.05em;
            background: linear-gradient(135deg, #818cf8 0%, #c7d2fe 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .cta {
            background: var(--j-purple) !important;
            border-radius: 14px !important;
            font-weight: 700 !important;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4) !important;
            transition: all 0.25s ease !important;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .cta:hover {
            background: #4f46e5 !important;
            transform: translateY(-3px) !important;
            box-shadow: 0 12px 25px rgba(99, 102, 241, 0.6) !important;
        }

        .about_icons {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            transition: all 0.3s ease;
        }

        .about_icons:hover {
            transform: translateY(-5px);
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }

        .about_icon i {
            font-size: 2.5rem !important;
            background: rgba(99, 102, 241, 0.15);
            padding: 14px;
            border-radius: 16px;
        }

        .sect {
            background: rgba(255, 255, 255, 0.01);
            padding: 60px 0;
        }

        .nosotros {
            background: rgba(30, 27, 75, 0.5);
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            padding: 50px 0;
        }

        .questions_padding {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 16px;
            margin-bottom: 15px;
            transition: border-color 0.2s;
            padding: 20px;
        }

        .questions_padding:hover {
            border-color: var(--j-purple-light);
        }

        .footer {
            background: #090d16;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 50px;
        }

        /* Corrección de visibilidad para títulos principales */
        .subtitulo {
            color: #ffffff !important;
            font-weight: 800 !important;
            text-shadow: 0 2px 10px rgba(99, 102, 241, 0.3);
        }

        .about_parrafo, .questions_parrafo {
            color: #cbd5e1 !important;
        }

        /* Corrección de la sección del slider de creadores */
        .nosotros_container {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            min-height: 280px;
        }

        .nosotros_body {
            display: none !important;
            width: 100%;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
        }

        .nosotros_body.nosotros_body--show {
            display: flex !important;
            animation: fadeIn 0.5s ease-in-out;
        }

        .nosotros_texts {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .nosotros_texts .subtitulo {
            font-size: 2rem !important;
            color: #ffffff !important;
            margin: 0 !important;
            line-height: 1.2;
        }

        .nosotros_review {
            color: #cbd5e1 !important;
            font-size: 1.05rem;
            line-height: 1.6;
            margin: 0;
        }

        .nosotros_picture {
            flex-shrink: 0;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid rgba(99, 102, 241, 0.4);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
        }

        .nosotros_img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Control estricto del tamaño de las flechas del carrusel */
        .nosotros_arrow {
            width: 36px !important;
            height: 36px !important;
            max-width: 36px !important;
            object-fit: contain;
            cursor: pointer;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
            transition: transform 0.2s;
            z-index: 10;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
        }

        #before {
            left: 5px;
        }

        #next {
            right: 5px;
        }

        .nosotros_arrow:hover {
            transform: translateY(-50%) scale(1.15);
        }

        /* Corrección de visibilidad en el acordeón de preguntas/pasos */
        .questions_title {
            color: #ffffff !important;
            font-weight: 700 !important;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }

        .questions_show {
            color: #cbd5e1 !important;
            margin-top: 10px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
        <header class="hero" id="inicio">
            <nav class="nav container">
                <div class="nav_logo">
                    <h2 class="nav_title">EDUTRIVIA</h2>
                </div>
                <ul class="nav_link nav_link-menu">
                    <li class="nav_items">
                        <a href="login.php" class="nav_links" style="font-weight: 600;">Iniciar Sesión</a>
                    </li>
                    <li class="nav_items">
                        <a href="#acerca" class="nav_links">Beneficios</a>
                    </li>
                    <li class="nav_items">
                        <a href="#nosotros" class="nav_links">Creadores</a>
                    </li>
                    <li class="nav_items">
                        <a href="#contacto" class="nav_links">Contacto</a>
                    </li>
                    <img class="nav_close" src="img/close.png" alt="Cerrar menú">
                </ul>
                <div class="nav_menu">
                    <img class="nav_img" src="img/menu-regular-24.png" alt="Abrir menú">
                </div>
            </nav>

            <section class="hero_container container">
                <h1 class="hero_title">¡Aprende Mientras te Diviertes!</h1>
                <p class="hero_parrafo">Juega con tus compañeros, pon a prueba tus conocimientos y escala en el tablero de puntuaciones académicas.</p>
                <a href="login.php?modo=registro" class="cta"><i class='bx bx-rocket'></i> Comienza Ahora</a>
            </section>
        </header>

        <main>
            <!-- SECCIÓN DE LOS BENEFICIOS AL JUGAR -->
            <section class="container about" id="acerca">
               <h2 class="subtitulo">Beneficios al jugar</h2>
                <p class="about_parrafo">Descubre cómo transformamos el estudio en una experiencia emocionante y colaborativa.</p> 
                <div class="about_main">
                    <article class="about_icons">
                        <div class="about_icon"><i class='bx bxs-brain' style='color:#818cf8'></i></div>
                        <h3 class="about_title">Refuerzo de Conocimientos</h3>
                        <p class="about_parrafo">Repasa los temas clave de tus clases de forma dinámica, respondiendo retos y preguntas diseñadas a la medida por tus profesores.</p>
                    </article>
                    <article class="about_icons">
                        <div class="about_icon"><i class='bx bx-timer' style='color:#818cf8'></i></div>
                        <h3 class="about_title">Competitividad Sana</h3>
                        <p class="about_parrafo">Compite por el puntaje más alto en el tablero, mide tu velocidad y visualiza tu progreso académico partida tras partida.</p>
                    </article>
                    <article class="about_icons">
                        <div class="about_icon"><i class='bx bx-male-female' style='color:#818cf8'></i></div>
                        <h3 class="about_title">Trabajo en Equipo</h3>
                        <p class="about_parrafo">Conéctate con tu grupo de clase, comparte estrategias y refuercen los conocimientos colectivos de manera interactiva.</p>
                    </article>
                </div>
            </section> 

            <!-- SECCIÓN DE INFORMACIÓN ADICIONAL -->
            <section class="sect">
                <div class="sect_container container">
                    <div class="sect_texts">
                        <h2 class="subtitulo">¡Juega y domina tu materia!</h2>
                        <p class="sect_parrafo">Edutrivia convierte las sesiones de repaso en dinámicas competitivas inspiradas en formatos de concursos de televisión. Tus profesores configuran los mazos de preguntas y tú demuestras lo que aprendiste.</p>
                        <a href="login.php?modo=registro" class="cta"><i class='bx bx-user-plus'></i> ¡Regístrate Gratis!</a>
                    </div>
                    <figure class="sect_picture">
                        <img src="img/fondo_principal_sect.jpg" class="sect_img" alt="Estudiantes aprendiendo con Edutrivia">
                    </figure>
                </div>
            </section>

            <!-- SECCIÓN DE INVITACIÓN / CREADORES -->
            <section class="nosotros" id="nosotros">
                <div class="nosotros_container container">
                    <img class="nosotros_arrow" id="before" src="img/before.png" alt="Anterior">
                    
                    <section class="nosotros_body nosotros_body--show" data-id="1">
                        <div class="nosotros_texts">
                            <h2 class="subtitulo">Gabriel Pérez 
                                <span class="nosotros_course" style="color: var(--j-purple-light); display: block; font-size: 1.1rem; margin-top: 5px;">Administrador & Desarrollador Principal</span></h2>
                            <p class="nosotros_review">Líder técnico del sistema: responsable de la arquitectura de la base de datos relacional, el control de acceso por roles (administrador, profesor y estudiante) y la lógica de los minijuegos interactivos.</p>
                        </div>
                        <figure class="nosotros_picture">
                            <img src="img/Administrador.jpeg" class="nosotros_img" alt="Gabriel Pérez">
                        </figure>
                    </section>

                    <section class="nosotros_body" data-id="2">
                        <div class="nosotros_texts">
                            <h2 class="subtitulo">Alondra Morales 
                                <span class="nosotros_course" style="color: var(--j-purple-light); display: block; font-size: 1.1rem; margin-top: 5px;">Gerente del Proyecto & UX Designer</span></h2>
                            <p class="nosotros_review">Encargada de la planeación estratégica, gestión de requerimientos y del diseño de la experiencia de usuario para garantizar una plataforma intuitiva y pedagógicamente efectiva.</p>
                        </div>
                        <figure class="nosotros_picture">
                            <img src="img/gerente.jpeg" class="nosotros_img" alt="Alondra Morales">
                        </figure>
                    </section>

                    <img class="nosotros_arrow" id="next" src="img/next.png" alt="Siguiente">
                </div>
            </section>

            <!-- SECCIÓN DE ¿CÓMO SE JUEGA? -->
            <section class="questions container" style="padding: 60px 0;">
                <h2 class="subtitulo" style="text-align: center;">¿CÓMO SE JUEGA?</h2>
                <p class="questions_parrafo" style="text-align: center; margin-bottom: 30px;">Comenzar a aprender en Edutrivia es muy sencillo, solo sigue estos tres pasos:</p>
                
                <div class="questions_container">
                    <article class="questions_padding">
                        <div class="questions_answer">
                            <h3 class="questions_title">1. Inicia Sesión
                                <span class="questions_arrow"> 
                                    <i class='bx bx-chevron-down' style="color: #ffffff; font-size: 1.5rem;"></i>
                                </span>
                            </h3>
                            <p class="questions_show">
                                Accede con tu cuenta institucional de estudiante, profesor o administrador para ingresar a tu panel personalizado y unirte a tus clases mediante códigos de acceso.
                            </p>
                        </div>
                    </article>
                    <article class="questions_padding">
                        <div class="questions_answer">
                            <h3 class="questions_title">2. Selecciona tu Clase y Mazo
                                <span class="questions_arrow"> 
                                    <i class='bx bx-chevron-down' style="color: #ffffff; font-size: 1.5rem;"></i>
                                </span>
                            </h3>
                            <p class="questions_show">
                                Entra a tu clase activa y elige entre los diferentes minijuegos disponibles (Jeopardy, Ahorcado, Carrera o Memorama) configurados con los temas de tu profesor.
                            </p>
                        </div>
                    </article>
                    <article class="questions_padding">
                        <div class="questions_answer">
                            <h3 class="questions_title">3. Juega, Gana Puntos y Repasa
                                <span class="questions_arrow"> 
                                    <i class='bx bx-chevron-down' style="color: #ffffff; font-size: 1.5rem;"></i>
                                </span>
                            </h3>
                            <p class="questions_show">
                                Responde correctamente los retos en el menor tiempo posible, acumula puntos para el ranking de la clase y refuerza tu aprendizaje de forma divertida.
                            </p>
                        </div>
                    </article>
                </div>
            </section>
        </main>

        <footer class="footer">
            <section class="footer_container container" id="contacto">
                <nav class="nav nav--footer">
                    <h2 class="footer_title" style="color: white; font-weight: 800;">Edutrivia</h2>
                    <ul class="nav_link nav_link--footer">
                        <li class="nav_items">
                            <a href="#inicio" class="nav_links">Inicio</a>
                        </li>
                        <li class="nav_items">
                            <a href="login.php" class="nav_links">Iniciar Sesión</a>
                        </li>
                        <li class="nav_items">
                            <a href="#acerca" class="nav_links">Beneficios</a>
                        </li>
                        <li class="nav_items">
                            <a href="#nosotros" class="nav_links">Creadores</a>
                        </li>
                        <li class="nav_items">
                            <a href="#contacto" class="nav_links">Contacto</a>
                        </li>
                    </ul>
                </nav>
                <form class="form_footer" action="https://formspree.io/f/maygzowd" method="POST">
                    <h2 class="footer_newsletter">Únete y Disfruta de la Diversión</h2>
                    <div class="footer_inputs">
                        <input type="email" placeholder="Tu correo electrónico:" class="footer_input" name="email" required>
                        <input type="submit" value="Unirse" class="footer_submit">
                    </div>
                </form>
            </section>

            <section class="footer_copy container">
                <div class="footer-social">
                    <a href="https://www.facebook.com/edutrivia.estancia?mibextid=ZbWKwL" class="footer_icon" target="_blank"><img class="footer_img" src="img/facebook.png" alt="Facebook"></a>
                    <a href="https://www.twitch.tv" class="footer_icon" target="_blank"><img class="footer_img" src="img/twitch.png" alt="Twitch"></a>
                    <a href="https://www.snapchat.com/es" class="footer_icon" target="_blank"><img class="footer_img" src="img/snapchat.png" alt="Snapchat"></a>
                </div>
                <h3 class="footer_copyright">Derechos Reservados &copy; 2026 Alondra Morales & Gabriel Pérez</h3>
            </section>
        </footer>

        <script src="js/principal.js"></script>
        <script src="js/questions.js"></script>
        <script src="js/menu.js"></script>
</body>
</html>