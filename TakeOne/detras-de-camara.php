<?php
session_start();
$usuario = $_SESSION['usuario'] ?? null;
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <title>Detrás de cámara · TakeOne</title>
  <?php require_once 'includes/head.php'; ?>
    
  <style>
    .sobre-hero {
      min-height: 60vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #0f1419;
      padding: 7rem 2rem 4rem;
      text-align: center;
    }

    .sobre-hero-inner {
      max-width: 760px;
      margin: 0 auto;
    }

    .sobre-hero-badge {
      display: inline-block;
      border: 2px solid #14b8a6;
      color: #14b8a6;
      font-size: 0.85rem;
      font-weight: 600;
      letter-spacing: 2px;
      text-transform: uppercase;
      padding: 0.4rem 1.2rem;
      border-radius: 2rem;
      margin-bottom: 1.5rem;
    }

    .sobre-hero h1 {
      font-family: 'Playfair Display', serif;
      font-size: clamp(2.8rem, 6vw, 4.5rem);
      font-weight: 900;
      color: #fff;
      line-height: 1.1;
      margin-bottom: 1.5rem;
    }

    .sobre-hero h1 span {
      color: #ff6347;
    }

    .sobre-hero p {
      font-size: 1.4rem;
      color: white;
      line-height: 1.7;
    }

    /* ── Sección misión ── */
    .sobre-section {
      max-width: 1100px;
      margin: 0 auto;
      padding: 5rem 2rem;
      margin-bottom: 1rem;
    }

    .sobre-mision-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 4rem;
      align-items: center;
      margin-bottom: 2rem;
    }

    .sobre-mision-text .label {
      color: #14b8a6;
      font-weight: 600;
      font-size: 0.95rem;
      text-transform: uppercase;
      letter-spacing: 2px;
      margin-bottom: 1rem;
    }

    .sobre-mision-text h2 {
      font-family: 'Playfair Display', serif;
      font-size: 2.2rem;
      font-weight: 700;
      color: #fff;
      line-height: 1.3;
      margin-bottom: 1.25rem;
    }

    .sobre-mision-text p {
      color: white;
      line-height: 1.8;
      font-size: 1.2rem;
      margin-bottom: 1rem;
    }

    .sobre-mision-visual {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      height: 450px;
      border-radius: 1.5rem;
      overflow: hidden;
      padding: 0;
      text-align: center;
    }

    /* ── Valores ── */
    .sobre-valores {
      background: #0f1419;
      padding: 5rem 2rem;
    }

    .sobre-valores-inner {
      max-width: 1100px;
      margin: 0 auto;
    }

    .sobre-valores-inner .section-label {
      text-align: center;
      color: #14b8a6;
      font-weight: 600;
      font-size: 0.95rem;
      text-transform: uppercase;
      letter-spacing: 2px;
      margin-bottom: 0.85rem;
    }

    .sobre-valores-inner h2 {
      font-family: 'Playfair Display', serif;
      font-size: 2.2rem;
      font-weight: 700;
      color: #fff;
      text-align: center;
      margin-bottom: 3.5rem;
    }

    .valores-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 2rem;
    }

    .valor-card {
      background: rgba(255, 255, 255, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 1.25rem;
      padding: 2.5rem 1.75rem;
      transition: transform 0.3s ease, border-color 0.3s ease;
    }

    .valor-card:hover {
      transform: translateY(-6px);
      border-color: rgba(255, 99, 71, 0.3);
    }

    .valor-icon {
      width: 60px;
      height: 60px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.6rem;
      margin-bottom: 1.25rem;
      margin: 0 auto 1.25rem auto;
    }

    .valor-icon img {
      width: 36px;
      height: 36px;
      object-fit: contain;
    }

    .valor-icon.red {
      background: rgba(255, 99, 71, 0.15);
    }

    .valor-icon.teal {
      background: rgba(138, 20, 184, 0.15);
    }

    .valor-icon.blue {
      background: rgba(59, 130, 246, 0.15);
    }

    .valor-icon.amber {
      background: rgba(55, 236, 230, 0.27);
    }

    .valor-icon.pink {
      background: rgba(236, 173, 72, 0.15);
    }

    .valor-icon.green {
      background: rgba(34, 197, 94, 0.15);
    }

    .valor-card h3 {
      font-family: 'Playfair Display', serif;
      font-size: 1.3rem;
      font-weight: 700;
      color: #fff;
      margin-bottom: 0.75rem;
      text-align: center;
    }

    .valor-card p {
      color: white;
      font-size: 1rem;
      line-height: 1.7;
      text-align: center;
    }

    /* ── Equipo ── */
    .sobre-equipo {
      padding: 5rem 2rem;
      max-width: 1100px;
      margin: 0 auto;
    }

    .sobre-equipo .section-label {
      color: #14b8a6;
      font-weight: 600;
      font-size: 0.95rem;
      text-transform: uppercase;
      letter-spacing: 2px;
      margin-bottom: 0.75rem;
    }

    .sobre-equipo h2 {
      font-family: 'Playfair Display', serif;
      font-size: 2.2rem;
      font-weight: 700;
      color: #fff;
      margin-bottom: 0.75rem;
    }

    .sobre-equipo>p {
      color: white;
      margin-bottom: 3rem;
      font-size: 1.2rem;
      line-height: 1.7;
      max-width: 600px;
    }

    .equipo-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 2rem;
    }

    .equipo-card {
      background: rgba(255, 255, 255, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 1.25rem;
      margin-top: 1.6rem;
      padding: 2rem 1.5rem;
      text-align: center;
      transition: transform 0.3s ease;
    }

    .equipo-card:hover {
      transform: translateY(-5px);
    }

    .equipo-img {
      width: 120px;
      height: 120px;
      object-fit: cover;
      border-radius: 50%;
      margin: 0 auto 20px;
      display: block;
      border: 4px solid rgba(255, 255, 255, 0.1);
    }

    .equipo-card h4 {
      color: #fff;
      font-weight: 600;
      font-size: 1.2rem;
      margin-bottom: 0.25rem;
    }

    .equipo-card .role {
      color: #14b8a6;
      font-size: 0.95rem;
      font-weight: 500;
      margin-bottom: 0.75rem;
    }

    .equipo-card p {
      color: white;
      font-size: 1rem;
      line-height: 1.6;
    }

    .role span {
      color: white;
    }

    /* ── CTA final ── */
    .sobre-cta {
      background: #0f1419;
      border-top: 1px solid rgba(255, 255, 255, 0.07);
      padding: 5rem 2rem;
      text-align: center;
    }

    .sobre-cta h3 {
      font-family: 'Playfair Display', serif;
      font-size: 2rem;
      font-weight: 900;
      color: #fff;
      margin-bottom: 1rem;
    }

    .sobre-cta p {
      color: white;
      margin-bottom: 2rem;
      margin-top: 2rem;
      font-size: 1.2rem;
    }

    .sobre-cta a {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: linear-gradient(135deg, #ff6347, #e5533d);
      color: #fff;
      font-weight: 600;
      font-size: 1rem;
      padding: 0.9rem 2.2rem;
      border-radius: 12px;
      text-decoration: none;
      transition: all 0.3s ease;
    }

    .sobre-cta a:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(255, 99, 71, 0.45);
    }

    /* ── Responsive ── */
    @media (max-width: 768px) {
      .sobre-mision-grid {
        grid-template-columns: 1fr;
      }
        
      .sobre-mision-visual {
    	height: auto;
    	aspect-ratio: 16/9;
  	  }

      .valores-grid {
        grid-template-columns: 1fr;
      }

      .equipo-grid {
        grid-template-columns: 1fr 1fr;
      }
    }

    @media (max-width: 480px) {
      .equipo-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body style="background:#0f1419; color:#e5e7eb; font-family:'Inter',sans-serif; margin:0;">

  <?php include 'includes/header.php'; ?>

  <!-- Hero -->
  <section class="sobre-hero">
    <div class="sobre-hero-inner">
      <span class="sobre-hero-badge">✨ Nuestra historia</span>
      <h1>El cine es mejor<br><span>en compañía</span></h1>
      <p>Entre películas, código y muchas noches de ideas, nació TakeOne: una plataforma hecha desde la experiencia real de una apasionada del cine que buscaba una forma más completa de vivirlo.</p>
    </div>
  </section>

  <!-- Misión -->
  <section class="sobre-section">
    <div class="sobre-mision-grid">
      <div class="sobre-mision-text">
        <div class="label">✦ Una misión</div>
        <h2>Una forma más personal de vivir el cine</h2>
        <p>TakeOne nació de una idea muy simple: sentir que faltaba un lugar donde vivir el cine de una forma más cercana, personal y auténtica. No solo marcar películas vistas, sino recordar lo que te hicieron sentir, descubrir nuevas historias y conectar con personas que viven el cine con la misma pasión.</p>
        <p>Desde los grandes estrenos que llenan salas hasta esas joyas ocultas que encuentras por casualidad a las dos de la mañana. Aquí cada historia encuentra su espacio.</p>
      </div>
      <div class="sobre-mision-visual">
        <img src="img/escena-cine.png" alt="Cine" style="width:100%; height:100%; object-fit:cover; display:block;">
      </div>
    </div>
  </section>

  <!-- Valores -->
  <section class="sobre-valores">
    <div class="sobre-valores-inner">
      <div class="section-label">✦ Lo que hace especial a esta comunidad</div>
      <h2>La esencia de TakeOne</h2>
      <div class="valores-grid">
        <div class="valor-card">
          <div class="valor-icon red">
            <img src="img/generos/drama.png" alt="Diversidad de géneros">
          </div>
          <h3>Diversidad de géneros</h3>
          <p>No hay géneros mejores ni peores. Celebramos el terror, la comedia, el drama de autor y la ciencia ficción con el mismo entusiasmo.</p>
        </div>
        <div class="valor-card">
          <div class="valor-icon teal">
            <img src="img/spoiler.jpg" alt="Comunidad sin spoilers">
          </div>
          <h3>Comunidad sin spoilers</h3>
          <p>Respetamos la experiencia de cada usuario. Discutimos sin arruinar la sorpresa, porque la primera vez que ves algo es irrepetible.</p>
        </div>
        <div class="valor-card">
          <div class="valor-icon blue">
            <img src="img/opinion.png" alt="Crítica honesta">
          </div>
          <h3>Crítica honesta</h3>
          <p>Fomentamos las reseñas sinceras frente a las modas. Tu opinión tiene valor aunque vaya a contracorriente.</p>
        </div>
        <div class="valor-card">
          <div class="valor-icon amber">
            <img src="img/lupa-de-busqueda.png" alt="Descubrimiento constante">
          </div>
          <h3>Descubrimiento constante</h3>
          <p>El mejor cine a veces está en los márgenes. Ayudamos a encontrar joyas ocultas más allá de los estrenos de temporada.</p>
        </div>
        <div class="valor-card">
          <div class="valor-icon pink">
            <img src="img/apreton-de-manos.png" alt="Respeto entre usuarios">
          </div>
          <h3>Respeto entre usuarios</h3>
          <p>Debates encendidos, sí. Pero siempre desde el respeto. TakeOne es un espacio seguro para todos los aficionados al cine.</p>
        </div>
        <div class="valor-card">
          <div class="valor-icon green">
            <img src="img/planeta-tierra.png" alt="Cine sin fronteras">
          </div>
          <h3>Cine sin fronteras</h3>
          <p>De Bollywood a la nouvelle vague, del neorrealismo italiano al nuevo cine coreano. El idioma no es barrera para una gran historia.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Equipo -->
  <section class="sobre-equipo">
    <div class="section-label">✦ Quién hay detrás</div>

    <h2>La persona detrás de TakeOne</h2>

    <div class="equipo-grid" style="grid-template-columns: 1fr; max-width: 360px; margin: 0 auto;">

      <div class="equipo-card">

        <!-- IMAGEN -->
        <img
          src="img/juanito.png"
          alt="Gato de María - Fundadora de TakeOne"
          class="equipo-img">

        <h4>María</h4>

        <div class="role">Creadora <span>|</span> Desarrollo</div>

        <p>
          Viendo más pelis de las que debería (y disfrutándolo).
        </p>

      </div>

    </div>
  </section>

  <!-- CTA -->
  <?php if (!$usuario): ?>
    <section class="sobre-cta">
      <h2>¿Todavía no eres parte de la comunidad?</h2>
      <p>Únete a miles de cinéfilos que ya comparten, descubren y debaten sobre cine en TakeOne.</p>
      <a href="register.php">Empieza gratis</a>
    </section>
  <?php else: ?>
    <section class="sobre-cta">
      <h3>“Buenos días… y por si no volvemos a vernos: buenos días, buenas tardes y buenas noches.”</h3>
      <p>Sigue explorando, descubriendo y compartiendo tu pasión por el cine con la comunidad.</p>
      <a href="seccionPrincipal.php"><span class="material-icons-outlined" style="font-size:1.1rem;">home</span> Volver al inicio</a>
    </section>
  <?php endif; ?>

  <?php include 'includes/footer.php'; ?>

</body>

</html>