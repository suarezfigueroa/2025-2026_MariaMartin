<?php

session_start();

// Si no hay sesión activa, redirigir al login
if (!isset($_SESSION['usuario'])) {
  header("Location: ../login.html");
  exit;
}

$username = $_SESSION['usuario']['username'];

// ── Conexión a la BD ──────────────────────────────────────────
require_once 'includes/conexion.php';

// ── 1. Películas en cartelera ─────────────────────────────────
$stmtCartelera = $pdo->query("
  SELECT p.id_pelicula, p.titulo, p.poster, p.imdb
  FROM peliculas p
  INNER JOIN peliculas_en_cartelera c ON p.id_pelicula = c.id_pelicula
  ORDER BY c.fecha_inicio DESC
  LIMIT 10
");
$cartelera = $stmtCartelera->fetchAll(PDO::FETCH_ASSOC);

// ── 2. Próximos estrenos ──────────────────────────────────────
$stmtEstrenos = $pdo->query("
  SELECT e.id_estreno, e.titulo, e.poster, e.fecha_estreno, e.id_pelicula
  FROM proximos_estrenos e
  WHERE e.fecha_estreno >= CURDATE()
  ORDER BY e.fecha_estreno ASC
  LIMIT 10
");
$estrenos = $stmtEstrenos->fetchAll(PDO::FETCH_ASSOC);

// ── 3. Noticias destacadas (3 más recientes) ──────────────────
$stmtNoticias = $pdo->query("
  SELECT id_noticia, titulo, descripcion, imagen, fecha
  FROM noticias
  ORDER BY fecha DESC
  LIMIT 3
");
$noticias = $stmtNoticias->fetchAll(PDO::FETCH_ASSOC);

// ── Helper: tiempo relativo ───────────────────────────────────
function tiempoRelativo(string $fechaStr): string
{
  $ahora = new DateTime();
  $fecha = new DateTime($fechaStr);
  $diff = $ahora->diff($fecha);

  if ($diff->days === 0) {
    if ($diff->h === 0)
      return "Hace {$diff->i} min";
    return "Hace {$diff->h} " . ($diff->h === 1 ? "hora" : "horas");
  }
  if ($diff->days === 1)
    return "Hace 1 día";
  if ($diff->days < 7)
    return "Hace {$diff->days} días";
  return $fecha->format('d/m/Y');
}
?>

<!doctype html>
<html lang="es" class="light">

<head>
  <title>Sección Principal - TakeOne</title>
  <?php require_once 'includes/head.php'; ?>
</head>

<body>
  <?php include 'includes/header.php'; ?>

  <main class="py-4">
    <div class="container" style="max-width:var(--container-max);">
      <div class="card-hero mb-4">
        <h2 class="bienvenido">Hola de nuevo <span class="username-highlight"><?php echo htmlspecialchars($username); ?></span></h2>

        <!-- ── CARTELERA CINES ESPAÑA ──────────────────────── -->
        <section class="mb-5">
          <div class="section-header">
            <h2 class="section-title">Cartelera Cines España</h2>
          </div>

          <div class="slider-wrapper">
            <button class="carousel-control-custom left" id="leftBtn">
              <span class="material-icons-outlined">chevron_left</span>
            </button>

            <div class="d-flex h-snap scrollbar-hide">
              <?php if (empty($cartelera)): ?>
                <p class="text-muted px-3">No hay películas en cartelera en este momento.</p>
              <?php else: ?>
                <?php foreach ($cartelera as $peli): ?>
                  <div class="h-item">
                    <a href="detalle-pelicula.php?id=<?php echo $peli['id_pelicula']; ?>">
                      <img src="<?php echo htmlspecialchars($peli['poster']); ?>"
                        alt="<?php echo htmlspecialchars($peli['titulo']); ?>" loading="lazy">
                      <div class="hover-info">
                        <h6><?php echo htmlspecialchars($peli['titulo']); ?></h6>
                        <?php if ($peli['imdb']): ?>
                          <small><?php echo number_format($peli['imdb'], 1); ?> ⭐</small>
                        <?php endif; ?>
                      </div>
                    </a>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

            <button class="carousel-control-custom right" id="rightBtn">
              <span class="material-icons-outlined">chevron_right</span>
            </button>
          </div>
        </section>

        <!-- ── PRÓXIMOS ESTRENOS ESPAÑA ───────────────────── -->
        <section class="mb-5">
          <div class="section-header">
            <h2 class="section-title">Próximos Estrenos España</h2>
          </div>

          <div class="slider-wrapper">
            <button class="carousel-control-custom left" id="leftBtn2">
              <span class="material-icons-outlined">chevron_left</span>
            </button>

            <div class="d-flex h-snap scrollbar-hide">
              <?php if (empty($estrenos)): ?>
                <p class="text-muted px-3">No hay próximos estrenos registrados.</p>
              <?php else: ?>
                <?php foreach ($estrenos as $estreno): ?>
                  <div class="h-item">
                    <?php if ($estreno['id_pelicula']): ?>
                      <a href="detalle-pelicula.php?id=<?php echo $estreno['id_pelicula']; ?>">
                      <?php endif; ?>

                      <img src="<?php echo htmlspecialchars($estreno['poster']); ?>"
                        alt="<?php echo htmlspecialchars($estreno['titulo']); ?>" loading="lazy">
                      <div class="hover-info">
                        <h6><?php echo htmlspecialchars($estreno['titulo']); ?></h6>
                        <small>
                          <?php
                          $fecha = new DateTime($estreno['fecha_estreno']);
                          echo $fecha->format('d/m/Y');
                          ?>
                        </small>
                      </div>

                      <?php if ($estreno['id_pelicula']): ?>
                      </a>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

            <button class="carousel-control-custom right" id="rightBtn2">
              <span class="material-icons-outlined">chevron_right</span>
            </button>
          </div>
        </section>

        <!-- ── NOTICIAS DESTACADAS ────────────────────────── -->
        <section class="news-section">
          <div class="section-header">
            <h2 class="section-title">Noticias Destacadas</h2>
          </div>

          <div class="news-grid">
            <?php if (empty($noticias)): ?>
              <p class="text-muted">No hay noticias disponibles.</p>
            <?php else: ?>
              <?php foreach ($noticias as $noticia): ?>
                <a href="detalle-noticia.php?id=<?php echo $noticia['id_noticia']; ?>" class="news-card">
                  <div class="news-card-img-wrapper">
                    <img src="<?php echo htmlspecialchars($noticia['imagen']); ?>"
                      alt="<?php echo htmlspecialchars($noticia['titulo']); ?>" loading="lazy">
                  </div>
                  <div class="news-content">
                    <h4 class="news-title">
                      <?php echo htmlspecialchars($noticia['titulo']); ?>
                    </h4>
                    <p class="news-excerpt">
                      <?php echo htmlspecialchars($noticia['descripcion']); ?>
                    </p>
                    <div class="news-meta">
                      <span>
                        <img src="img/calendario.png" alt="Tiempo" class="icon-meta">
                        <?php echo tiempoRelativo($noticia['fecha']); ?>
                      </span>
                    </div>
                  </div>
                </a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <a href="noticias.php" class="view-all">Ver todas →</a>
        </section>

      </div>
    </div>
  </main>

  <?php include 'includes/footer.php'; ?>

  <script src="js/scriptPrincipal.js"></script>
</body>

</html>