<!doctype html>
<html lang="es" class="light">

<head>
  <title>Mis Listas - TakeOne</title>
  <?php require_once 'includes/head.php'; ?>
</head>

<body>
  <?php
  session_start();
  require_once 'includes/conexion.php';

  if (!isset($_SESSION['usuario']['id'])) {
    header('Location: login.php');
    exit;
  }

  $id_usuario = (int) $_SESSION['usuario']['id'];

  $sql = "SELECT l.id_lista, l.titulo, l.descripcion, l.imagen, l.fecha_creacion, l.visibilidad,
                 COUNT(DISTINCT lp.id_pelicula) AS num_peliculas,
                 COUNT(DISTINCT ll.id_usuario)  AS num_likes
          FROM listas l
          LEFT JOIN listas_peliculas lp ON l.id_lista = lp.id_lista
          LEFT JOIN listas_likes     ll ON l.id_lista = ll.id_lista
          WHERE l.id_usuario = :id_usuario
          GROUP BY l.id_lista
          ORDER BY l.fecha_creacion DESC";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([':id_usuario' => $id_usuario]);
  $mis_listas = $stmt->fetchAll();

  $total_listas = count($mis_listas);

  function formatearFecha(string $fecha): string
  {
    $dt = new DateTime($fecha);
    return $dt->format('d/m/Y');
  }

  function visibilidadInfo(string $v): array
  {
    return match ($v) {
      'privada' => ['icon' => 'lock', 'label' => 'Privada'],
      'amigos' => ['icon' => 'group', 'label' => 'Solo amigos'],
      default => ['icon' => 'public', 'label' => 'Pública'],
    };
  }
  ?>

  <?php include 'includes/header.php'; ?>

  <main class="py-4">
    <div class="container" style="max-width:var(--container-max);">

      <div class="mis-listas-hero">
        <div class="mis-listas-hero-content">
          <div class="mis-listas-hero-text">
            <h1>Mis listas</h1>
            <p>Gestiona todas las listas que has creado. Organiza tus películas en colecciones personalizadas y
              compártelas con la comunidad.</p>
          </div>
          <div class="mis-listas-stats">
            <div class="stat-card-mis-listas">
              <span class="material-icons-outlined">list_alt</span>
              <div class="stat-info">
                <span class="stat-number"><?= $total_listas ?></span>
                <span class="stat-label">Listas</span>
              </div>
            </div>
          </div>
        </div>

        <div class="mis-listas-actions">
          <button class="btn-crear-lista" id="crearListaBtn">
            <span class="material-icons-outlined">add</span>
            Crear nueva lista
          </button>
        </div>
      </div>

      <div class="mis-listas-content">

        <?php if ($mis_listas): ?>
          <div class="mis-listas-grid">
            <?php foreach ($mis_listas as $lista): ?>
              <div class="mis-lista-card" data-id="<?= $lista['id_lista'] ?>">
                <div class="mis-lista-cover">
                  <a href="detalle-lista.php?id=<?= $lista['id_lista'] ?>">
                    <img src="<?= htmlspecialchars($lista['imagen'] ?: 'img/logo gato sin fondo.png') ?>"
                      onerror="this.src='img/logo gato sin fondo.png'"
                      alt="<?= htmlspecialchars($lista['titulo']) ?>">
                  </a>
                </div>
                <div class="mis-lista-info">
                  <h3>
                    <a href="detalle-lista.php?id=<?= $lista['id_lista'] ?>" style="text-decoration:none;color:inherit;">
                      <?= htmlspecialchars($lista['titulo']) ?>
                    </a>
                  </h3>
                  <div class="mis-lista-meta">
                    <span class="mis-lista-count">
                      <?= $lista['num_peliculas'] ?> películas
                    </span>
                    <span class="mis-lista-separator">•</span>
                    <span class="mis-lista-date">Creada:
                      <?= formatearFecha($lista['fecha_creacion']) ?>
                    </span>
                  </div>
                  <p class="mis-lista-desc">
                    <?= htmlspecialchars($lista['descripcion']) ?>
                  </p>
                  <div class="mis-lista-visibility">
                    <?php $vis = visibilidadInfo($lista['visibilidad']); ?>
                    <span class="mis-lista-vis-badge">
                      <span class="material-icons-outlined"><?= $vis['icon'] ?></span>
                      <span><?= $vis['label'] ?></span>
                    </span>
                    <span class="mis-lista-separator">•</span>
                    <span class="material-icons-outlined">favorite_border</span>
                    <span><?= $lista['num_likes'] ?> me gusta</span>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

        <?php else: ?>
          <div class="empty-state-mis-listas">
            <span class="material-icons-outlined empty-icon">list_alt</span>
            <h3>No tienes listas creadas</h3>
            <p>Crea tu primera lista para organizar tus películas favoritas en colecciones personalizadas</p>
            <button class="btn-crear-primera-lista" id="crearPrimeraListaBtn">
              Crear mi primera lista
            </button>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </main>

  <!-- Modal para crear lista -->
  <div class="modal-overlay-mis-listas" id="modalOverlay" style="display: none;">
    <div class="modal-content-mis-listas">
      <button class="modal-close-mis-listas" id="modalClose">
        <span class="material-icons-outlined">close</span>
      </button>
      <h2 id="modalTitle">Crear nueva lista</h2>

      <div id="modal-mis-listas-mensaje" class="alert d-none mb-3"></div>

      <div class="form-group">
        <label for="listaNombre">Nombre de la lista *</label>
        <input type="text" id="listaNombre" required placeholder="Ej: Mis películas favoritas de los 90s"
          maxlength="100">
      </div>

      <div class="form-group">
        <label for="listaDescripcion">Descripción</label>
        <textarea id="listaDescripcion" rows="3" placeholder="Describe el tema o propósito de tu lista..."
          maxlength="500"></textarea>
      </div>

      <div class="form-group">
        <label>Visibilidad</label>
        <div class="visibility-options">
          <label class="visibility-option">
            <input type="radio" name="visibilidad" value="publica" checked>
            <span class="visibility-icon">
              <span class="material-icons-outlined">public</span>
              <span>Pública</span>
            </span>
            <small>Todos pueden ver esta lista</small>
          </label>
          <label class="visibility-option">
            <input type="radio" name="visibilidad" value="amigos">
            <span class="visibility-icon">
              <span class="material-icons-outlined">group</span>
              <span>Solo amigos</span>
            </span>
            <small>Solo tus amigos pueden verla</small>
          </label>
          <label class="visibility-option">
            <input type="radio" name="visibilidad" value="privada">
            <span class="visibility-icon">
              <span class="material-icons-outlined">lock</span>
              <span>Privada</span>
            </span>
            <small>Solo tú puedes ver esta lista</small>
          </label>
        </div>
      </div>

      <div class="form-group">
        <label>Imagen de portada <small style="color:#6c757d;">(opcional, máx. 2 MB)</small></label>
        <!-- Zona de previsualización clicable -->
        <div class="portada-upload" id="portadaUpload">
          <img id="portadaPreviewImg" src="" alt=""
            style="display:none; width:100%; height:100%; object-fit:cover; border-radius:8px;">
          <div id="portadaPlaceholder">
            <span class="material-icons-outlined" style="font-size:2rem; color:#aaa;">add_photo_alternate</span>
            <span style="color:#aaa; font-size:0.9rem;">Haz clic para seleccionar una imagen</span>
          </div>
        </div>
        <input type="file" id="listaPortada" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;">
      </div>

      <div class="modal-actions">
        <button type="button" class="btn-cancelar" id="cancelarBtn">Cancelar</button>
        <button type="button" class="btn-guardar" id="guardarListaBtn">Crear lista</button>
      </div>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>

  <script src="js/mis-listas.js"></script>
</body>

</html>