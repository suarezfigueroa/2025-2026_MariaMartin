<!doctype html>
<html lang="es" class="light">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Actividad - TakeOne</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet" />
  <link href="css/styles.css" rel="stylesheet" />
</head>

<body>
  <?php
  session_start();
  require_once 'includes/conexion.php';

  if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
  }

  $idUsuario = (int) ($_SESSION['usuario']['id_usuario'] ?? $_SESSION['usuario']['id'] ?? 0);

  // ── 1. Estados: películas marcadas como vista / pendiente / favorita ──────────
  $stmtEstados = $pdo->prepare("
    SELECT
        up.id_pelicula,
        up.estado,
        up.valoracion,
        COALESCE(up.fecha_estado, up.fecha) AS fecha_accion,
        p.titulo,
        p.anio,
        p.poster,
        GROUP_CONCAT(g.nombre ORDER BY g.nombre SEPARATOR ', ') AS generos
    FROM usuarios_peliculas up
    INNER JOIN peliculas p ON p.id_pelicula = up.id_pelicula
    LEFT JOIN peliculas_generos pg ON pg.id_pelicula = p.id_pelicula
    LEFT JOIN generos g ON g.id_genero = pg.id_genero
    WHERE up.id_usuario = :u
      AND up.estado IS NOT NULL
    GROUP BY up.id_pelicula, up.estado, up.valoracion, up.fecha, up.fecha_estado, p.titulo, p.anio, p.poster
    ORDER BY COALESCE(up.fecha_estado, up.fecha) DESC
");
  $stmtEstados->execute([':u' => $idUsuario]);
  $filasEstados = $stmtEstados->fetchAll();

  // ── 2. Valoraciones (independientes de estado) ────────────────────────────────
  $stmtValoraciones = $pdo->prepare("
    SELECT
        up.id_pelicula,
        up.valoracion,
        COALESCE(up.fecha_estado, up.fecha) AS fecha_accion,
        p.titulo,
        p.anio,
        p.poster,
        GROUP_CONCAT(g.nombre ORDER BY g.nombre SEPARATOR ', ') AS generos
    FROM usuarios_peliculas up
    INNER JOIN peliculas p ON p.id_pelicula = up.id_pelicula
    LEFT JOIN peliculas_generos pg ON pg.id_pelicula = p.id_pelicula
    LEFT JOIN generos g ON g.id_genero = pg.id_genero
    WHERE up.id_usuario = :u
      AND up.valoracion IS NOT NULL
    GROUP BY up.id_pelicula, up.valoracion, up.fecha, up.fecha_estado, p.titulo, p.anio, p.poster
    ORDER BY COALESCE(up.fecha_estado, up.fecha) DESC
");
  $stmtValoraciones->execute([':u' => $idUsuario]);
  $filasValoraciones = $stmtValoraciones->fetchAll();

  // ── 3. Comentarios ────────────────────────────────────────────────────────────
  $stmtComentarios = $pdo->prepare("
    SELECT
        cp.id_comentario,
        cp.comentario,
        cp.fecha,
        p.id_pelicula,
        p.titulo,
        p.anio,
        p.poster,
        GROUP_CONCAT(g.nombre ORDER BY g.nombre SEPARATOR ', ') AS generos
    FROM comentarios_peliculas cp
    INNER JOIN peliculas p ON p.id_pelicula = cp.id_pelicula
    LEFT JOIN peliculas_generos pg ON pg.id_pelicula = p.id_pelicula
    LEFT JOIN generos g ON g.id_genero = pg.id_genero
    WHERE cp.id_usuario = :u
    GROUP BY cp.id_comentario, cp.comentario, cp.fecha, p.id_pelicula, p.titulo, p.anio, p.poster
    ORDER BY cp.fecha DESC
");
  $stmtComentarios->execute([':u' => $idUsuario]);
  $filasComentarios = $stmtComentarios->fetchAll();

  // ── Unificar y ordenar todo por fecha desc ────────────────────────────────────
  $items = [];

  foreach ($filasEstados as $f) {
    $items[] = [
      'tipo'       => $f['estado'],            // 'vista' | 'pendiente' | 'favorita'
      'fecha'      => $f['fecha_accion'],
      'pelicula'   => [
        'id'      => $f['id_pelicula'],
        'titulo'  => $f['titulo'],
        'anio'    => $f['anio'],
        'poster'  => $f['poster'],
        'generos' => $f['generos'],
      ],
      'valoracion' => $f['valoracion'],
      'comentario' => null,
    ];
  }

  foreach ($filasValoraciones as $f) {
    // No crear entrada 'valoracion' si ya hay una entrada de estado para esa película
    $yaIncluida = false;
    foreach ($items as $item) {
      if (
        $item['tipo'] !== 'comentario' &&
        (int)$item['pelicula']['id'] === (int)$f['id_pelicula']
      ) {
        $yaIncluida = true;
        break;
      }
    }
    if (!$yaIncluida) {
      $items[] = [
        'tipo'       => 'valoracion',
        'fecha'      => $f['fecha_accion'],
        'pelicula'   => [
          'id'      => $f['id_pelicula'],
          'titulo'  => $f['titulo'],
          'anio'    => $f['anio'],
          'poster'  => $f['poster'],
          'generos' => $f['generos'],
        ],
        'valoracion' => $f['valoracion'],
        'comentario' => null,
      ];
    }
  }

  foreach ($filasComentarios as $f) {
    $items[] = [
      'tipo'       => 'comentario',
      'fecha'      => $f['fecha'],
      'pelicula'   => [
        'id'      => $f['id_pelicula'],
        'titulo'  => $f['titulo'],
        'anio'    => $f['anio'],
        'poster'  => $f['poster'],
        'generos' => $f['generos'],
      ],
      'valoracion' => null,
      'comentario' => $f['comentario'],
    ];
  }

  // Ordenar por fecha descendente
  usort($items, fn($a, $b) => strtotime($b['fecha']) - strtotime($a['fecha']));

  // ── Helper: tiempo relativo ───────────────────────────────────────────────────
  function tiempoRelativo(string $fecha): string
  {
    $diff = time() - strtotime($fecha);
    if ($diff < 60)           return 'Hace un momento';
    if ($diff < 3600)         return 'Hace ' . floor($diff / 60) . ' min';
    if ($diff < 86400)        return 'Hace ' . floor($diff / 3600) . ' h';
    if ($diff < 604800)       return 'Hace ' . floor($diff / 86400) . ' días';
    if ($diff < 2592000)      return 'Hace ' . floor($diff / 604800) . ' semanas';
    return 'Hace ' . floor($diff / 2592000) . ' meses';
  }

  // ── Helper: corazones ─────────────────────────────────────────────────────────
  function renderCorazones(int $val): string
  {
    $html = '<div class="actividad-valoracion">';
    for ($i = 1; $i <= 5; $i++) {
      $icon = $i <= $val ? 'favorite' : 'favorite_border';
      $cls  = $i <= $val ? 'heart-filled' : 'heart-empty';
      $html .= '<span class="material-icons-outlined ' . $cls . '">' . $icon . '</span>';
    }
    $html .= '</div>';
    return $html;
  }
  ?>

  <?php include 'includes/header.php'; ?>

  <main class="py-4">
    <div class="container" style="max-width: var(--container-max)">
      <div class="actividad-hero">
        <div class="actividad-hero-content">
          <div class="actividad-hero-text">
            <h1>Mi actividad</h1>
            <p>
              Todo lo que haces en TakeOne, organizado: pendientes, vistas, favoritas y tus comentarios en cada película.
            </p>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="actividad-filtros mb-4" id="filtrosActividad">
        <button class="filtro-btn active" data-filtro="todos">Todos</button>
        <button class="filtro-btn" data-filtro="vista">Vistas</button>
        <button class="filtro-btn" data-filtro="pendiente">Pendientes</button>
        <button class="filtro-btn" data-filtro="favorita">Favoritas</button>
        <button class="filtro-btn" data-filtro="valoracion">Valoraciones</button>
        <button class="filtro-btn" data-filtro="comentario">Comentarios</button>
      </div>

      <!-- Timeline de Actividad -->
      <div class="actividad-content">
        <?php if (!empty($items)): ?>
          <div class="actividad-timeline" id="actividadTimeline">

            <?php foreach ($items as $item):
              $tipo    = $item['tipo'];
              $pel     = $item['pelicula'];
              $tiempo  = tiempoRelativo($item['fecha']);
              $poster  = htmlspecialchars($pel['poster'] ?? '');
              $titulo  = htmlspecialchars($pel['titulo'] ?? '');
              $meta    = ($pel['anio'] ?? '') . ($pel['generos'] ? ' • ' . htmlspecialchars($pel['generos']) : '');
              $enlace  = 'detalle-pelicula.php?id=' . (int)$pel['id'];

              // Config por tipo
              $config = [
                'vista'      => ['icon' => 'visibility',  'badge_cls' => 'badge-vista',      'label' => 'Película vista',        'icon_cls' => 'icon-vista'],
                'pendiente'  => ['icon' => 'schedule',    'badge_cls' => 'badge-pendiente',   'label' => 'Añadida a pendientes',  'icon_cls' => 'icon-pendiente'],
                'favorita'   => ['icon' => 'star',        'badge_cls' => 'badge-favorita',    'label' => 'Añadida a favoritas',   'icon_cls' => 'icon-favorita'],
                'valoracion' => ['icon' => 'favorite',    'badge_cls' => 'badge-valoracion',  'label' => 'Película valorada',     'icon_cls' => 'icon-valoracion'],
                'comentario' => ['icon' => 'comment',     'badge_cls' => 'badge-comentario',  'label' => 'Nuevo comentario',      'icon_cls' => 'icon-comentario'],
              ];
              $c = $config[$tipo] ?? $config['vista'];
            ?>

              <?php
              $tipos = $tipo;
              if (in_array($tipo, ['vista', 'favorita', 'pendiente']) && $item['valoracion']) {
                $tipos = $tipo . ' valoracion';
              }
              ?>
              <div class="actividad-item" data-tipo="<?= htmlspecialchars($tipos) ?>">
                <div class="actividad-icon <?= $c['icon_cls'] ?>">
                  <span class="material-icons-outlined"><?= $c['icon'] ?></span>
                </div>
                <div class="actividad-card">
                  <div class="actividad-header">
                    <div class="actividad-badge <?= $c['badge_cls'] ?>"><?= $c['label'] ?></div>
                    <span class="actividad-tiempo"><?= $tiempo ?></span>
                  </div>
                  <div class="actividad-body">
                    <div class="actividad-pelicula">
                      <?php if ($poster): ?>
                        <a href="<?= $enlace ?>">
                          <img src="<?= $poster ?>" alt="<?= $titulo ?>" class="actividad-poster"
                            onerror="this.src='img/poster-placeholder.jpg'">
                        </a>
                      <?php endif; ?>
                      <div class="actividad-info">
                        <h3><a href="<?= $enlace ?>" style="color:inherit;text-decoration:none;"><?= $titulo ?></a></h3>
                        <p class="actividad-meta"><?= $meta ?></p>

                        <!-- para que apareza el tipo y la valoración al mismo tiempo -->
                        <?php if (in_array($tipo, ['vista', 'favorita']) && $item['valoracion']): ?>
                          <?= renderCorazones((int)$item['valoracion']) ?>
                        <?php endif; ?>

                        <?php if ($tipo === 'valoracion'): ?>
                          <?= renderCorazones((int)$item['valoracion']) ?>
                        <?php endif; ?>

                        <?php if ($tipo === 'comentario' && $item['comentario']): ?>
                          <div class="actividad-comentario">
                            <p>"<?= htmlspecialchars($item['comentario']) ?>"</p>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

            <?php endforeach; ?>

          </div>
        <?php else: ?>
          <!-- Sin actividad -->
          <div class="empty-state-actividad">
            <span class="material-icons-outlined empty-icon">timeline</span>
            <h3>Sin actividad aún</h3>
            <p>Empieza a explorar películas y tu actividad aparecerá aquí</p>
            <a href="peliculas.php" class="btn-explore-actividad">Explorar películas</a>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </main>

  <?php include 'includes/footer.php'; ?>

  <script src="js/actividad.js"></script>
</body>

</html>