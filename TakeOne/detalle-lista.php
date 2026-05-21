<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Lista - TakeOne</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
</head>

<body>
    <?php
    session_start();
    require_once 'includes/conexion.php';

    // Validar id
    $id_lista = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id_lista <= 0) {
        header('Location: listas.php');
        exit;
    }

    // Datos de la lista + autor + estadísticas
    $sql = "SELECT l.id_lista, l.titulo, l.descripcion, l.fecha_creacion, l.visibilidad, l.id_usuario AS propietario_id,
               u.username, u.avatar,
               COUNT(DISTINCT lp.id_pelicula) AS num_peliculas,
               COUNT(DISTINCT ll.id_usuario) AS num_likes
            FROM listas l
            LEFT JOIN usuarios u ON l.id_usuario = u.id_usuario
            LEFT JOIN listas_peliculas lp ON l.id_lista = lp.id_lista
            LEFT JOIN listas_likes ll ON l.id_lista = ll.id_lista
            WHERE l.id_lista = :id
            GROUP BY l.id_lista";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id_lista]);
    $lista = $stmt->fetch();

    if (!$lista) {
        header('Location: listas.php');
        exit;
    }

    $id_usuario_sesion = $_SESSION['usuario']['id'] ?? null;
    $es_propietario_check = $id_usuario_sesion && ((int) $id_usuario_sesion === (int) $lista['propietario_id']);

    if (!$es_propietario_check) {
        if ($lista['visibilidad'] === 'privada') {
            header('Location: listas.php');
            exit;
        }
        // Para 'amigos' aquí puedes añadir tu lógica de amistad cuando la tengas
        // if ($lista['visibilidad'] === 'amigos' && !sonAmigos($id_usuario_sesion, $lista['propietario_id'])) { ... }
    }

    // Películas de la lista
    $sql_pelis = "SELECT p.id_pelicula, p.titulo, p.anio, p.poster
                  FROM peliculas p
                  INNER JOIN listas_peliculas lp ON p.id_pelicula = lp.id_pelicula
                  WHERE lp.id_lista = :id
                  ORDER BY p.titulo ASC";

    $stmt_pelis = $pdo->prepare($sql_pelis);
    $stmt_pelis->execute([':id' => $id_lista]);
    $peliculas = $stmt_pelis->fetchAll();

    // ¿El usuario ya dio like?
    $usuario_dio_like = false;
    $id_usuario_sesion = $_SESSION['usuario']['id'] ?? null;
    if ($id_usuario_sesion) {
        $stmt_like = $pdo->prepare("SELECT 1 FROM listas_likes WHERE id_lista = :id_lista AND id_usuario = :id_usuario");
        $stmt_like->execute([':id_lista' => $id_lista, ':id_usuario' => $id_usuario_sesion]);
        $usuario_dio_like = (bool) $stmt_like->fetch();
    }

    // ¿Es el propietario de la lista?
    $es_propietario = $id_usuario_sesion && ((int) $id_usuario_sesion === (int) $lista['propietario_id']);

    // Formatear fecha en español
    $fecha = new DateTime($lista['fecha_creacion']);
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $fecha_formateada = $fecha->format('j') . ' de ' . $meses[(int) $fecha->format('n') - 1] . ' de ' . $fecha->format('Y');
    ?>

    <?php include 'includes/header.php'; ?>

    <main class="py-4">
        <div class="container" style="max-width:var(--container-max);">
            <!-- Hero de la Lista -->
            <div class="lista-detail-hero">
                <div class="lista-detail-header">
                    <div class="lista-detail-info">
                        <h1 class="lista-detail-title"><?= htmlspecialchars($lista['titulo']) ?></h1>
                        <p class="lista-detail-description">
                            <?= htmlspecialchars($lista['descripcion']) ?>
                        </p>

                        <div class="lista-detail-meta">
                            <div class="lista-author">
                                <img src="<?= htmlspecialchars($lista['avatar']) ?>"
                                    alt="<?= htmlspecialchars($lista['username']) ?>" class="author-avatar">
                                <div class="author-info">
                                    <span class="author-name"><?= htmlspecialchars($lista['username']) ?></span>
                                    <span class="author-date">Creada el <?= $fecha_formateada ?></span>
                                </div>
                            </div>

                            <div class="lista-actions">
                                <?php if (!$es_propietario): ?>
                                    <button class="btn-lista-action btn-like <?= $usuario_dio_like ? 'liked' : '' ?>"
                                        data-id-lista="<?= $lista['id_lista'] ?>">
                                        <span class="material-icons-outlined">
                                            <?= $usuario_dio_like ? 'favorite' : 'favorite_border' ?>
                                        </span>
                                        <span><?= $usuario_dio_like ? 'Te gusta' : 'Me gusta' ?></span>
                                    </button>
                                <?php endif; ?>

                                <?php if ($es_propietario): ?>
                                    <button class="btn-lista-action btn-editar-lista" id="btnAbrirModalEditar">
                                        <span class="material-icons-outlined">edit</span>
                                        <span>Editar lista</span>
                                    </button>
                                    <button class="btn-lista-action btn-eliminar-lista-detalle" id="btnEliminarLista">
                                        <span class="material-icons-outlined">delete</span>
                                        <span>Eliminar lista</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="lista-stats">
                            <div class="stat-item">
                                <span class="material-icons-outlined">movie</span>
                                <span><strong id="contador-peliculas"><?= $lista['num_peliculas'] ?></strong>
                                    películas</span>
                            </div>
                            <div class="stat-item">
                                <span class="material-icons-outlined">favorite</span>
                                <span><strong id="contador-likes"><?= $lista['num_likes'] ?></strong> me gusta</span>
                            </div>

                            <!-- NUEVO: visibilidad -->
                            <?php
                            $vis_info = match ($lista['visibilidad']) {
                                'privada' => ['icon' => 'lock', 'label' => 'Privada'],
                                'amigos' => ['icon' => 'group', 'label' => 'Solo amigos'],
                                default => ['icon' => 'public', 'label' => 'Pública'],
                            };
                            ?>
                            <div class="stat-item">
                                <span class="material-icons-outlined"><?= $vis_info['icon'] ?></span>
                                <span><?= $vis_info['label'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grid de Películas -->
            <div class="lista-detail-content">
                <h2 class="section-title-detalle">Películas en esta lista</h2>

                <div class="movies-grid-lista" id="gridPeliculas">
                    <?php if ($peliculas): ?>
                        <?php foreach ($peliculas as $pelicula): ?>
                            <div class="movie-card-lista" id="card-pelicula-<?= $pelicula['id_pelicula'] ?>">
                                <div class="movie-poster-wrapper">
                                    <a href="detalle-pelicula.php?id=<?= $pelicula['id_pelicula'] ?>">
                                        <img src="<?= htmlspecialchars($pelicula['poster']) ?>"
                                            alt="<?= htmlspecialchars($pelicula['titulo']) ?>">
                                    </a>
                                </div>
                                <div class="movie-info-lista">
                                    <h3><?= htmlspecialchars($pelicula['titulo']) ?></h3>
                                    <p class="movie-year"><?= htmlspecialchars($pelicula['anio']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p id="msg-lista-vacia" style="color: white; max-width: 400px;">
                            Esta lista aún no tiene películas.
                            <?= $es_propietario ? 'Busca en la sección "Películas" para empezar.' : '' ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php if ($es_propietario): ?>
        <!-- Modal Editar Lista -->
        <div class="modal-overlay-mis-listas" id="modalEditarLista" style="display:none;">
            <div class="modal-content-mis-listas" style="max-width:560px;">
                <button class="modal-close-mis-listas" id="modalEditarClose">
                    <span class="material-icons-outlined">close</span>
                </button>
                <h2>Editar lista</h2>

                <div id="modal-editar-mensaje" class="alert d-none mb-3"></div>

                <!-- Pestañas -->
                <div class="editar-tabs" style="display:flex;gap:8px;margin-bottom:20px;">
                    <button class="editar-tab active" data-tab="info">
                        <span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;">info</span>
                        Información
                    </button>
                    <button class="editar-tab" data-tab="peliculas">
                        <span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;">movie</span>
                        Películas (<span id="tab-contador-peliculas"><?= $lista['num_peliculas'] ?></span>)
                    </button>
                </div>

                <!-- Panel: Información -->
                <div id="tab-info" class="editar-tab-panel">
                    <div class="form-group">
                        <label for="editarNombre">Nombre de la lista *</label>
                        <input type="text" id="editarNombre" maxlength="100"
                            value="<?= htmlspecialchars($lista['titulo']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="editarDescripcion">Descripción</label>
                        <textarea id="editarDescripcion" rows="3"
                            maxlength="500"><?= htmlspecialchars($lista['descripcion']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Visibilidad</label>
                        <div class="visibility-options">
                            <label class="visibility-option">
                                <input type="radio" name="editarVisibilidad" value="publica"
                                    <?= $lista['visibilidad'] === 'publica' ? 'checked' : '' ?>>
                                <span class="visibility-icon">
                                    <span class="material-icons-outlined">public</span>
                                    <span>Pública</span>
                                </span>
                                <small>Todos pueden ver esta lista</small>
                            </label>
                            <label class="visibility-option">
                                <input type="radio" name="editarVisibilidad" value="amigos"
                                    <?= $lista['visibilidad'] === 'amigos' ? 'checked' : '' ?>>
                                <span class="visibility-icon">
                                    <span class="material-icons-outlined">group</span>
                                    <span>Solo amigos</span>
                                </span>
                                <small>Solo tus amigos pueden verla</small>
                            </label>
                            <label class="visibility-option">
                                <input type="radio" name="editarVisibilidad" value="privada"
                                    <?= $lista['visibilidad'] === 'privada' ? 'checked' : '' ?>>
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
                        <div class="portada-upload" id="editarPortadaUpload">
                            <img id="editarPortadaPreviewImg"
                                src="<?= htmlspecialchars(!empty($lista['imagen']) ? $lista['imagen'] : 'img/logo gato sin fondo.png') ?>"
                                onerror="this.src='img/logo gato sin fondo.png'"
                                alt=""
                                style="<?= !empty($lista['imagen']) ? 'display:block' : 'display:none' ?>;width:100%;height:100%;object-fit:cover;border-radius:8px;">
                            <div id="editarPortadaPlaceholder"
                                style="<?= !empty($lista['imagen']) ? 'display:none' : 'display:flex' ?>;flex-direction:column;align-items:center;gap:8px;">
                                <span class="material-icons-outlined"
                                    style="font-size:2rem;color:#aaa;">add_photo_alternate</span>
                                <span style="color:#aaa;font-size:0.9rem;">Haz clic para seleccionar una imagen</span>
                            </div>
                        </div>
                        <input type="file" id="editarPortada" accept="image/jpeg,image/png,image/webp,image/gif"
                            style="display:none;">
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-cancelar" id="editarCancelarBtn">Cancelar</button>
                        <button type="button" class="btn-guardar" id="editarGuardarBtn">Guardar cambios</button>
                    </div>
                </div>

                <!-- Panel: Películas -->
                <div id="tab-peliculas" class="editar-tab-panel" style="display:none;">
                    <?php if ($peliculas): ?>
                        <p style="color:var(--background-dark);font-size:0.85rem;margin-bottom:12px;">
                            Haz clic en <span class="material-icons-outlined"
                                style="font-size:14px;vertical-align:middle;color:#ef4444;">close</span> para quitar una
                            película de la lista.
                        </p>
                        <div class="editar-peliculas-lista" id="editarPeliculasLista">
                            <?php foreach ($peliculas as $peli): ?>
                                <div class="editar-pelicula-item" id="editar-item-<?= $peli['id_pelicula'] ?>">
                                    <img src="<?= htmlspecialchars($peli['poster']) ?>"
                                        alt="<?= htmlspecialchars($peli['titulo']) ?>">
                                    <div class="editar-pelicula-info">
                                        <span class="editar-pelicula-titulo"><?= htmlspecialchars($peli['titulo']) ?></span>
                                        <span class="editar-pelicula-anio"><?= htmlspecialchars($peli['anio']) ?></span>
                                    </div>
                                    <button class="btn-quitar-pelicula-modal" data-id-pelicula="<?= $peli['id_pelicula'] ?>"
                                        title="Quitar de la lista">
                                        <span class="material-icons-outlined">close</span>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p id="msg-editar-vacia" style="color:red;text-align:center;padding:24px 0;">
                            Esta lista aún no tiene películas.
                        </p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>

    <script>
        const ID_LISTA = <?= $lista['id_lista'] ?>;
        const USUARIO_DIO_LIKE = <?= $usuario_dio_like ? 'true' : 'false' ?>;
        const ES_PROPIETARIO = <?= $es_propietario ? 'true' : 'false' ?>;
    </script>
    <script src="js/detalle-lista.js"></script>

</body>

</html>