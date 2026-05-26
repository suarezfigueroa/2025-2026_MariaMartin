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
        p.poster,
        up.fecha
    FROM usuarios_peliculas up
    INNER JOIN peliculas p ON p.id_pelicula = up.id_pelicula
    WHERE up.id_usuario = :id_usuario
      AND up.estado = 'pendiente'
    ORDER BY up.fecha DESC
");
$stmt->execute(['id_usuario' => (int)$idUsuario]);
$pendientes = $stmt->fetchAll();

$totalPendientes = count($pendientes);

function tiempoTranscurrido($fecha)
{
  if (!$fecha) {
    return '';
  }

  $ahora = new DateTime();
  $fechaObj = new DateTime($fecha);
  $diff = $ahora->diff($fechaObj);

  if ($diff->days === 0) {
    return 'Hoy';
  }
  if ($diff->days === 1) {
    return 'Hace 1 día';
  }
  if ($diff->days < 30) {
    return 'Hace ' . $diff->days . ' días';
  }

  $meses = floor($diff->days / 30);
  if ($meses === 1) {
    return 'Hace 1 mes';
  }
  if ($meses < 12) {
    return 'Hace ' . $meses . ' meses';
  }

  $anios = floor($meses / 12);
  return $anios === 1 ? 'Hace 1 año' : 'Hace ' . $anios . ' años';
}
?>
<!doctype html>
<html lang="es" class="light">

<head>
  <title>Pendientes - TakeOne</title>
  <?php require_once 'includes/head.php'; ?>
</head>

<body>
  <?php include 'includes/header.php'; ?>

  <main class="py-4">
    <div class="container" style="max-width:var(--container-max);">

      <div class="pendientes-hero">
        <div class="pendientes-hero-content">
          <div class="pendientes-hero-text">
            <h1>Películas pendientes</h1>
            <p>Aquí están todas las películas que has marcado para ver más tarde. ¡No olvides darles una oportunidad!</p>
          </div>

          <div class="pendientes-stats">
            <div class="stat-card">
              <span class="material-icons-outlined">movie</span>
              <div class="stat-info">
                <span class="stat-number"><?= $totalPendientes ?></span>
                <span class="stat-label">Películas</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php if ($totalPendientes > 0): ?>
        <div class="pendientes-controls">
          <div class="pendientes-filters">
            <button class="filter-btn-pendientes active" data-filter="todas">Todas</button>
            <button class="filter-btn-pendientes" data-filter="agregadas-reciente">Agregadas recientemente</button>
            <button class="filter-btn-pendientes" data-filter="mas-antiguas">Más antiguas</button>
          </div>
        </div>
      <?php endif; ?>

      <div class="pendientes-content">
        <div class="pendientes-grid" <?= $totalPendientes === 0 ? 'style="display:none;"' : '' ?>>
          <?php foreach ($pendientes as $pelicula): ?>
            <div class="pendiente-card"
              data-pelicula-id="<?= (int)$pelicula['id_pelicula'] ?>"
              data-fecha="<?= htmlspecialchars(date('Y-m-d H:i:s', strtotime($pelicula['fecha']))) ?>">

              <div class="pendiente-poster">
                <a href="detalle-pelicula.php?id=<?= (int)$pelicula['id_pelicula'] ?>">
                  <img
                    src="<?= htmlspecialchars($pelicula['poster'] ?: 'img/poster-placeholder.jpg') ?>"
                    alt="<?= htmlspecialchars($pelicula['titulo']) ?>"
                    onerror="this.src='img/poster-placeholder.jpg'">
                </a>
              </div>

              <div class="pendiente-info">
                <h3><?= htmlspecialchars($pelicula['titulo']) ?></h3>
                <div class="pendiente-meta">
                  <span class="pendiente-year"><?= (int)$pelicula['anio'] ?></span>
                  <span class="pendiente-separator">•</span>
                  <span class="pendiente-date"><?= htmlspecialchars(tiempoTranscurrido($pelicula['fecha'])) ?></span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="empty-state" <?= $totalPendientes > 0 ? 'style="display:none;"' : 'style="display:flex;"' ?>>
          <span class="material-icons-outlined empty-icon">movie_filter</span>
          <h3>No tienes películas pendientes</h3>
          <p>Explora nuestro catálogo y añade películas que te gustaría ver</p>
          <a href="peliculas.php" class="btn-explore">Explorar películas</a>
        </div>
      </div>

    </div>
  </main>

  <?php include 'includes/footer.php'; ?>
  <script src="js/pendientes.js"></script>
</body>

</html>