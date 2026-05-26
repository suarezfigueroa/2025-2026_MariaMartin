<!doctype html>
<html lang="es">

<head>
  <title>TakeOne - Tu portal de cine</title>
  <?php require_once 'includes/head.php'; ?>
</head>

<body class="landing-page">
  <!-- Header -->
  <header class="landing-header" id="landingHeader">
    <nav class="landing-nav">
      <a href="#" class="landing-logo">
        <img src="img/logo gato sin fondo.png" alt="TakeOne Logo" />
        <span class="landing-logo-text">TakeOne</span>
      </a>

      <div class="landing-cta-buttons">
        <a href="login.html" class="btn-landing btn-secondary-landing">
          <span>Iniciar sesión</span>
        </a>
        <a href="register.html" class="btn-landing btn-primary-landing">
          <span>Registrarse</span>
          <span class="material-icons-outlined">arrow_forward</span>
        </a>
      </div>
    </nav>
  </header>

  <!-- Hero Section -->
  <section class="hero-landing">
    <div class="hero-background"></div>
    <div class="hero-content">
      <span class="hero-badge">✨ Bienvenido al universo del cine</span>
      <h1 class="hero-title">
        Un espacio para<br />quienes aman las películas
      </h1>
      <p class="hero-subtitle">
        Descubre, comparte y conecta con otros cinéfilos. Crea listas
        personalizadas, únete a comunidades y sumérgete en el mundo del
        séptimo arte.
      </p>

      <div class="hero-buttons">
        <a href="#features" class="btn-hero btn-hero-secondary">
          <span>Descubre más</span>
          <span class="material-icons-outlined">expand_more</span>
        </a>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section class="features-section" id="features">
    <div class="features-container">
      <div class="section-header">
        <h2 class="features-title">Todo lo que necesitas en un solo lugar</h2>
      </div>

      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon">🎬</div>
          <h3 class="feature-title">Explora películas</h3>
          <p class="feature-description">
            Descubre miles de películas, filtra por género, año, país y
            plataforma. Encuentra tu próxima película favorita con facilidad.
          </p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">📝</div>
          <h3 class="feature-title">Crea tus listas</h3>
          <p class="feature-description">
            Organiza tus películas favoritas en listas personalizadas.
            Compártelas con la comunidad y descubre las listas de otros
            usuarios.
          </p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">💬</div>
          <h3 class="feature-title">Únete a la comunidad</h3>
          <p class="feature-description">
            Haz amigos, únete a grupos temáticos y participa
            en debates sobre tus películas favoritas.
          </p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">🎲</div>
          <h3 class="feature-title">Sugerencias personalizadas</h3>
          <p class="feature-description">
            ¿No sabes qué ver? Descubre películas recomendadas según
            tus gustos o déjate sorprender por una selección aleatoria pensada para ti.
          </p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">📰</div>
          <h3 class="feature-title">Mantente informado</h3>
          <p class="feature-description">
            Lee las últimas noticias del mundo del cine, entrevistas
            exclusivas y análisis de películas.
          </p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">❤️</div>
          <h3 class="feature-title">Valora y comenta</h3>
          <p class="feature-description">
            Comparte tu opinión, lee reseñas de la comunidad y descubre nuevas
            perspectivas.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="index-section">
    <div class="index-container">
      <h2 class="index-title">Únete a la comunidad</h2>
      <p class="index-description">
        Descubre películas y compártelas con una comunidad que valora las
        buenas historias.
      </p>

      <div class="index-buttons">
        <a href="register.html" class="btn-index btn-crear-cuenta">
          <span>Crear cuenta gratis</span>
          <span class="material-icons-outlined">person_add</span>
        </a>
        <a href="login.html" class="btn-index btn-ya-tengo">
          <span>Ya tengo cuenta</span>
          <span class="material-icons-outlined">login</span>
        </a>
      </div>
    </div>
  </section>

  <?php include 'includes/footer.php'; ?>

  <script src="js/index.js"></script>
</body>

</html>