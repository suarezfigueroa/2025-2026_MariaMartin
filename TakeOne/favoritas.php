<?php
session_start();
require_once 'includes/conexion.php';

if (!isset($_SESSION['usuario'])) {
  header('Location: login.php');
  exit;
}

$idUsuario = $_SESSION['usuario']['id_usuario'] ?? $_SESSION['usuario']['id'] ?? null;

if (!$idUsuario) {
  die('No se pudo identificar al usuario logueado.');
}

$stmt = $pdo->prepare("
    SELECT 
        p.id_pelicula,
        p.titulo,
        p.anio,
        p.poster
    FROM usuarios_peliculas up
    INNER JOIN peliculas p ON p.id_pelicula = up.id_pelicula
    WHERE up.id_usuario = :id_usuario
      AND up.estado = 'favorita'
    ORDER BY up.fecha DESC
");
$stmt->execute([
  'id_usuario' => (int)$idUsuario
]);
$favoritas = $stmt->fetchAll();

$totalFavoritas = count($favoritas);
?>
<!doctype html>
<html lang="es" class="light">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Favoritas - TakeOne</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap"
    rel="stylesheet">
  <link href="css/styles.css" rel="stylesheet">
</head>

<body>
  <?php include 'includes/header.php'; ?>

  <main class="py-4">
    <div class="container" style="max-width:var(--container-max);">

      <div class="favoritas-hero">
        <div class="favoritas-hero-content">
          <div class="favoritas-hero-text">
            <h1>Películas favoritas</h1>
            <p>Aquí están todas las películas que has marcado como favoritas. ¡Tu colección personal de tesoros cinematográficos!</p>
          </div>

          <div class="favoritas-stats">
            <div class="stat-card-fav">
              <span class="material-icons-outlined">favorite</span>
              <div class="stat-info">
                <span class="stat-number"><?= $totalFavoritas ?></span>
                <span class="stat-label">Favoritas</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="favoritas-content">
        <div class="favoritas-grid" <?= $totalFavoritas === 0 ? 'style="display:none;"' : '' ?>>
          <?php foreach ($favoritas as $pelicula): ?>
            <a href="detalle-pelicula.php?id=<?= (int)$pelicula['id_pelicula'] ?>" class="favorita-card-link">
              <div class="favorita-card" data-pelicula-id="<?= (int)$pelicula['id_pelicula'] ?>">
                <div class="favorita-poster">
                  <img
                    src="<?= htmlspecialchars($pelicula['poster'] ?: 'img/poster-placeholder.jpg') ?>"
                    alt="<?= htmlspecialchars($pelicula['titulo']) ?>"
                    onerror="this.src='img/poster-placeholder.jpg'">
                </div>

                <div class="favorita-info">
                  <h3><?= htmlspecialchars($pelicula['titulo']) ?></h3>
                  <div class="favorita-meta">
                    <span class="favorita-year"><?= (int)$pelicula['anio'] ?></span>
                  </div>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>

        <div class="empty-state" <?= $totalFavoritas > 0 ? 'style="display:none;"' : 'style="display:flex;"' ?>>
          <span class="material-icons-outlined empty-icon">favorite_border</span>
          <h3>No tienes películas favoritas</h3>
          <p>Explora nuestro catálogo y marca las películas que más te gusten como favoritas</p>
          <a href="peliculas.php" class="btn-explore-fav">
            Explorar películas
          </a>
        </div>
      </div>

    </div>
  </main>

  <?php include 'includes/footer.php'; ?>

  <script src="js/favoritas.js"></script>
</body>

</html>