<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@500&family=Montserrat+Alternates&family=Poppins:wght@300;400&family=Roboto&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/principal.css">
    <link rel="icon" type="img/png" href="img/LogoEdutrivia (1).png">
    <title>Edutrivia</title>

    <meta name="theme-color" content="#001A49">
    <!-- Primary Meta Tags -->
    <meta name="title" content="Edutrivia" />
    <meta name="description" content="Sumérgete en el aprendizaje mientras juegas, una experiencia diseñada para superar la teoría y llevar las cosas a un nivel más dinámico." />

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://metatags.io/">
    <meta property="og:title" content="Edutrivia" />
    <meta property="og:description" content="Sumérgete en el aprendizaje mientras juegas, una experiencia diseñada para superar la teoría y llevar las cosas a un nivel más dinámico." />
    <meta property="og:image" content="https://metatags.io/images/meta-tags.png" />

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image" />
    <meta property="twitter:url" content="https://metatags.io/" />
    <meta property="twitter:title" content="Edutrivia" />
    <meta property="twitter:description" content="Sumérgete en el aprendizaje mientras juegas, una experiencia diseñada para superar la teoría y llevar las cosas a un nivel más dinámico." />
    <meta property="twitter:image" content="https://metatags.io/images/meta-tags.png" />

    <!-- Meta Tags Generated with https://metatags.io -->

</head>
<body>
        <header class="hero" id="inicio">
            <nav class="nav container">
                <div class="nav_logo">
                    <h2 class="nav_title">JEOPARDY!</h2>
                </div>
                <ul class="nav_link nav_link-menu">
                    <li class="nav_items">
                        <a href="login.php" class="nav_links">Inicio de Sesión</a>
                    </li>
                    <li class="nav_items">
                        <a href="#acerca" class="nav_links">Acerca</a>
                    </li>
                    <li class="nav_items">
                        <a href="#nosotros" class="nav_links">Nosotros</a>
                    </li>
                    <li class="nav_items">
                        <a href="#contacto" class="nav_links">Contacto</a>
                    </li>
                    <img class="nav_close" src="img/close.png">
                </ul>
                <div class="nav_menu">
                    <img class="nav_img" src="img/menu-regular-24.png">
                </div>
            </nav>

            <section class="hero_container container">
                <h1 class="hero_title">Aprende Mientras te Diviertes!</h1>
                <p class="hero_parrafo">Juega con tus compañeros y gana puntos con nuestro tablero de aprendizaje</p>
                <a href="#acerca" class="cta">Comienza Ahora</a>
            </section>
        </header>
        <main>
            <!--SECCIÓN DE LOS BENEFICIOS AL JUGAR-->
            <section class="container about" id="acerca">
               <h2 class="subtitulo">Beneficios al jugar: </h2>
                <p class="about_parrafo">Todo lo que aprendas mientras disfrutas</p> 
                <div class="about_main">
                    <article class="about_icons">
                        <img class="about_icon"><i class='bx bxs-brain' style='color:#a101a1'  ></i>
                        <h3 class="about_title">Refuerzo de Conocimientos</h3>
                        <p class="about_parrafo">Repasa temas de clase de forma dinámica, respondiendo preguntas y retos diseñados por tu propio profesor.</p>
                    </article>
                    <article class="about_icons">
                        <img class="about_icon"><i class='bx bx-timer' style='color:#a101a1'></i>
                        <h3 class="about_title">Competitividad</h3>
                        <p class="about_parrafo">Compite por el puntaje más alto y sigue tu progreso partida tras partida.</p>
                    </article>
                    <article class="about_icons">
                        <img class="about_icon"><i class='bx bx-male-female' style='color:#a101a1'></i>
                        <h3 class="about_title">Trabajo en Equipo</h3>
                        <p class="about_parrafo">Juega junto a tus compañeros y refuercen los conocimientos entre todos.</p>
                    </article>
                </div>
            </section> 
            <!--SECCIÓN DE INFORMACIÓN ADICIONAL-->
            <section class="sect">
                <div class="sect_container container">
                    <div class="sect_texts">
                        <h2 class="subtitulo">Juega ya!</h2>
                        <p class="sect_parrafo">Edutrivia convierte el repaso de clase en un juego de preguntas y respuestas al estilo Jeopardy. Tu profesor crea las preguntas, y tú compites para poner a prueba lo que aprendiste.</p>
                        <a href="pages/registro.php" class="cta">¡Da Clic Aquí!</a>
                    </div>
                    <figure class="sect_picture">
                        <img src="img/fondo_principal_sect.jpg" class="sect_img">
                    </figure>
                </div>
            </section>
            <!--SECCIÓN DE INIVTACIÓN A UNIRSE-->
            <br><br><br><br><br><br>
            <section class="nosotros" id="nosotros">
                <div class="nosotros_container container">
                <img class="nosotros_arrow" id="before" src="img/before.png"><!--<i class='bx bx-left-arrow-alt'></i>-->
                    <section class="nosotros_body nosotros_body--show" data-id="1">
                        <div class="nosotros_texts">
                            <h2 class="subtitulo">Gabriel Pérez, 
                                <span class="nosotros_course">Administrador del Proyecto</span></h2>
                            <p class="nosotros_review">Encargado del desarrollo del sistema: base de datos, lógica de las tres vistas (administrador, profesor y estudiante), y el juego Jeopardy.</p>
                        </div>
                        <figure class="nosotros_picture">
                            <img src="img/Administrador.jpeg" class="nosotros_img">
                        </figure>
                    </section>
                    <section class="nosotros_body" data-id="2">
                        <div class="nosotros_texts">
                            <h2 class="subtitulo">Alondra Morales, 
                                <span class="nosotros_course">Gerente del Proyecto</span></h2>
                            <p class="nosotros_review">Encargada de la planificación del proyecto y el diseño de la experiencia educativa de Edutrivia.</p>
                        </div>
                        <figure class="nosotros_picture">
                            <img src="img/gerente.jpeg" class="nosotros_img">
                        </figure>
                    </section>
                    <img class="nosotros_arrow" id="next" src="img/next.png">
                </div>
            </section>
            <!--SECCIÓN DE ¿CÓMO SE JUGAR JEOPARDY?-->
            <br><br>
            <section class="questions container">
                <h2 class="subtitulo">¿CÓMO SE JUEGA?</h2>
                <p class="questions_parrafo">Jugar en Edutrivia es muy sencillo, solo sigue estos tres pasos:</p>
                
                <section class="questions_container">
                    <article class="questions_padding">
                        <div class="questions_answer">
                            <h3 class="questions_title">Inicia Sesión
                                <span class="questions_arrow"> 
                                    <img class="questions_img"><i class='bx bx-chevron-up' style="color: #ffffff;"></i>
                                </span>
                            </h3>
                            <p class="questions_show">
                                Ingresa con tu cuenta de estudiante, profesor o administrador para acceder a tu panel correspondiente.
                            </p>
                        </div>
                    </article>
                </section>
                <section class="questions_container">
                    <article class="questions_padding">
                        <div class="questions_answer">
                            <h3 class="questions_title">Selecciona una Opción
                                <span class="questions_arrow"> 
                                    <img class="questions_img"><i class='bx bx-chevron-up' style="color: #ffffff;"></i>
                                </span>
                            </h3>
                            <p class="questions_show">
                                Elige el mazo de preguntas que tu profesor haya preparado para tu grupo.
                            </p>
                        </div>
                    </article>
                </section>
                <section class="questions_container">
                    <article class="questions_padding">
                        <div class="questions_answer">
                            <h3 class="questions_title">Juega y Diviertete
                                <span class="questions_arrow"> 
                                    <img class="questions_img"><i class='bx bx-chevron-up' style="color: #ffffff;"></i>
                                </span>
                            </h3>
                            <p class="questions_show">
                                Responde las preguntas, gana puntos, y revisa tu puntuación al final de cada partida.
                            </p>
                        </div>
                    </article>
                </section>
            </section>
        </main>
        <footer class="footer">
            <section class="footer_container container" id="contacto">
                <nav class="nav nav--footer">
                    <h2 class="footer_title">Edutrivia</h2>
                    <ul class="nav_link nav_link--footer">
                        <li class="nav_items">
                            <a href="#inicio" class="nav_links">Inicio</a>
                        </li>
                        <li class="nav_items">
                            <a href="login.php" class="nav_links">Inicio de Sesión</a>
                        </li>
                        <li class="nav_items">
                        <a href="#acerca" class="nav_links">Acerca</a>
                        </li>
                        <li class="nav_items">
                            <a href="#nosotros" class="nav_links">Nosotros</a>
                        </li>
                        <li class="nav_items">
                            <a href="#contacto" class="nav_links">Contacto</a>
                        </li>
                    </ul>
                </nav>
                <form class="form_footer" action="https://formspree.io/f/maygzowd"
                method="POST">
                    <h2 class="footer_newsletter">Únete y Disfruta de la Diversión</h2>
                    <div class="footer_inputs">
                        <input type="email" placeholder="Correo:" class="footer_input" name="email">
                        <input type="submit" value="Unirse" class="footer_submit">
                    </div>
                </form>
            </section>

            <section class="footer_copy container">
                <div class="footer-social">
                    <a href="https://www.facebook.com/edutrivia.estancia?mibextid=ZbWKwL" class="footer_icon"><img class="footer_img" src="img/facebook.png" alt="Facebook"></a>
                    <a href="https://www.twitch.tv" class="footer_icon"><img class="footer_img" src="img/twitch.png" alt="Discord"></a>
                    <a href="https://www.snapchat.com/es" class="footer_icon"><img class="footer_img" src="img/snapchat.png" alt="Snapchat"></a>
                </div>
                <h3 class="footer_copyright">Derechos Reservados &copy; Alondra Morales & Gabriel Pérez</h3>
            </section>
        </footer>
        <script src="js/principal.js"></script>
        <script src="js/questions.js"></script>
        <script src="js/menu.js"></script>
</body>
</html>