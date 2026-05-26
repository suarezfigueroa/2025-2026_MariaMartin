<!DOCTYPE html>
<html lang="es">

<head>
  <title>Detalle Película - TakeOne</title>
  <?php require_once 'includes/head.php'; ?>
</head>

<body>
    <?php
    session_start();
    require_once 'includes/conexion.php';

    // Obtener ID de la película
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id <= 0) {
        header("Location: peliculas.php");
        exit;
    }

    // Datos de la película
    $stmt = $pdo->prepare("SELECT * FROM peliculas WHERE id_pelicula = :id");
    $stmt->execute(['id' => $id]);
    $pelicula = $stmt->fetch();

    if (!$pelicula) {
        header("Location: peliculas.php");
        exit;
    }

    // Géneros de la película
    $stmtGeneros = $pdo->prepare("
    SELECT g.nombre
    FROM generos g
    INNER JOIN peliculas_generos pg ON g.id_genero = pg.id_genero
    WHERE pg.id_pelicula = :id
");
    $stmtGeneros->execute(['id' => $id]);
    $generos = $stmtGeneros->fetchAll(PDO::FETCH_COLUMN);

    // Plataformas donde está disponible
    $stmtPlataformas = $pdo->prepare("
    SELECT pl.nombre, pl.logo
    FROM plataformas pl
    INNER JOIN peliculas_plataformas pp ON pl.id_plataforma = pp.id_plataforma
    WHERE pp.id_pelicula = :id
");
    $stmtPlataformas->execute(['id' => $id]);
    $plataformas = $stmtPlataformas->fetchAll();

    // Reparto
    $stmtReparto = $pdo->prepare("
    SELECT *
    FROM reparto
    WHERE id_pelicula = :id
    LIMIT 6
");
    $stmtReparto->execute(['id' => $id]);
    $reparto = $stmtReparto->fetchAll();

    // Comentarios
    $stmtComentarios = $pdo->prepare("
    SELECT c.*, u.username, u.avatar, up.valoracion AS valoracion_autor
    FROM comentarios_peliculas c
    INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
    LEFT JOIN usuarios_peliculas up 
        ON up.id_usuario = c.id_usuario AND up.id_pelicula = c.id_pelicula
    WHERE c.id_pelicula = :id
    ORDER BY c.fecha DESC
");
    $stmtComentarios->execute(['id' => $id]);
    $comentarios = $stmtComentarios->fetchAll();

    // ¿Es un próximo estreno?
    $stmtEstreno = $pdo->prepare("SELECT COUNT(*) FROM proximos_estrenos WHERE id_pelicula = :id");
    $stmtEstreno->execute(['id' => $id]);
    $esProximoEstreno = (bool) $stmtEstreno->fetchColumn();

    // Duración formateada
    $duracionFormateada = '';
    if (!empty($pelicula['duracion'])) {
        $horas = floor($pelicula['duracion'] / 60);
        $minutos = $pelicula['duracion'] % 60;
        $duracionFormateada = $horas . 'h ' . $minutos . 'min';
    }

    // Listas del usuario logueado para el modal "Añadir a lista"
    $listasUsuario = [];
    if (isset($_SESSION['usuario']['id'])) {
        $stmtListasUsuario = $pdo->prepare("
        SELECT id_lista, titulo
        FROM listas
        WHERE id_usuario = :id_usuario
        ORDER BY fecha_creacion DESC
    ");
        $stmtListasUsuario->execute([
            'id_usuario' => (int) $_SESSION['usuario']['id']
        ]);
        $listasUsuario = $stmtListasUsuario->fetchAll();
    }

    // ── Valoración y estado actual del usuario logueado ──────────────
    $valoracionActual = 0;
    $estadoActual = null;
    if (isset($_SESSION['usuario'])) {
        $idUsuarioSesion = $_SESSION['usuario']['id_usuario'] ?? $_SESSION['usuario']['id'] ?? null;
        if ($idUsuarioSesion) {
            $stmtVal = $pdo->prepare("
            SELECT valoracion, estado FROM usuarios_peliculas
            WHERE id_usuario = :u AND id_pelicula = :p
        ");
            $stmtVal->execute([':u' => (int) $idUsuarioSesion, ':p' => $id]);
            $filaVal = $stmtVal->fetch();
            if ($filaVal) {
                $valoracionActual = $filaVal['valoracion'] ? (int) $filaVal['valoracion'] : 0;
                $estadoActual = $filaVal['estado'] ?? null;
            }
        }
    }

    // Procesar envío de comentario
    $errorComentario = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comentario'])) {
        if (!isset($_SESSION['usuario'])) {
            $errorComentario = 'Debes iniciar sesión para comentar.';
        } else {
            $idUsuarioComentario = $_SESSION['usuario']['id_usuario'] ?? $_SESSION['usuario']['id'] ?? null;
            $texto = trim($_POST['comentario']);
            $esSpoiler = isset($_POST['es_spoiler']) ? 1 : 0;
            if ($texto !== '' && $idUsuarioComentario) {
                $stmtInsert = $pdo->prepare("
                    INSERT INTO comentarios_peliculas (id_usuario, id_pelicula, comentario, es_spoiler)
                    VALUES (:id_usuario, :id_pelicula, :comentario, :es_spoiler)
                ");
                $stmtInsert->execute([
                    'id_usuario'  => (int) $idUsuarioComentario,
                    'id_pelicula' => $id,
                    'comentario'  => $texto,
                    'es_spoiler'  => $esSpoiler
                ]);
                // Registrar actividad (sustituye al trigger trg_insert_comentario)
                $pdo->prepare("
                    INSERT INTO actividad_usuario (id_usuario, tipo, descripcion)
                    VALUES (:id_usuario, 'comentario', :descripcion)
                ")->execute([
                    ':id_usuario'  => (int) $idUsuarioComentario,
                    ':descripcion' => 'Película ID: ' . $id,
                ]);
                header("Location: detalle-pelicula.php?id=$id&comentado=1");
                exit;
            } elseif (!$idUsuarioComentario) {
                $errorComentario = 'No se pudo identificar tu usuario. Intenta cerrar sesión y volver a entrar.';
            }
        }
    }
    ?>

    <?php include 'includes/header.php'; ?>

    <!-- HERO -->
    <section class="movie-hero-new">
        <div class="hero-backdrop">
            <?php if (!empty($pelicula['backdrop'])): ?>
                <img src="<?= htmlspecialchars($pelicula['backdrop']) ?>" alt="<?= htmlspecialchars($pelicula['titulo']) ?>"
                    onerror="this.style.display='none'">
            <?php endif; ?>
        </div>

        <div class="hero-content-wrapper">
            <div class="hero-poster-float">
                <div class="poster-card">
                    <img src="<?= htmlspecialchars($pelicula['poster']) ?>"
                        alt="<?= htmlspecialchars($pelicula['titulo']) ?> poster"
                        onerror="this.src='img/poster-placeholder.jpg'">
                </div>
            </div>

            <div class="hero-info-new">
                <h1 class="movie-title-new"><?= htmlspecialchars($pelicula['titulo']) ?></h1>

                <div class="movie-meta-new">
                    <?php if ($pelicula['anio']): ?>
                        <div class="meta-item">
                            <span><?= $pelicula['anio'] ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($pelicula['duracion']): ?>
                        <div class="meta-item">
                            <span class="material-icons-outlined">schedule</span>
                            <span><?= $duracionFormateada ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($pelicula['pais']): ?>
                        <div class="meta-item">
                            <span><?= htmlspecialchars($pelicula['pais']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($pelicula['imdb']): ?>
                        <div class="meta-item">
                            <span class="material-icons-outlined" style="color: #FFD700;">star</span>
                            <span><?= $pelicula['imdb'] ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($generos)): ?>
                    <div class="genre-tags">
                        <?php foreach ($generos as $genero): ?>
                            <span class="genre-tag"><?= htmlspecialchars($genero) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="action-buttons-new">
                    <?php if (isset($_SESSION['usuario'])): ?>
                        <button class="btn-new btn-primary-new" id="abrirModalListaBtn" data-pelicula-id="<?= $id ?>">
                            <span class="material-icons-outlined">add</span>
                            <span>Añadir a lista</span>
                        </button>
                    <?php else: ?>
                        <a href="login.php" class="btn-new btn-primary-new">
                            <span class="material-icons-outlined">login</span>
                            <span>Inicia sesión para añadir</span>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($pelicula['trailer_url'])): ?>
                        <a href="<?= htmlspecialchars($pelicula['trailer_url']) ?>" target="_blank"
                            class="btn-new btn-outline-new">
                            <span class="material-icons-outlined">play_circle</span>
                            <span>Ver tráiler</span>
                        </a>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['usuario'])): ?>
                        <?php
                        $iconosEstado = [
                            'vista' => 'check_circle',
                            'favorita' => 'favorite',
                            'pendiente' => 'schedule',
                        ];
                        $labelEstado = [
                            'vista' => 'Vista',
                            'favorita' => 'Favorita',
                            'pendiente' => 'Pendiente',
                        ];
                        $iconoBoton = $estadoActual ? $iconosEstado[$estadoActual] : 'bookmark_border';
                        $textoBoton = $estadoActual ? $labelEstado[$estadoActual] : 'Marcar como';
                        ?>
                        <div class="dropdown-marcar" data-pelicula-id="<?= $id ?>"
                            data-estado="<?= htmlspecialchars($estadoActual ?? '') ?>"
                            data-titulo="<?= htmlspecialchars($pelicula['titulo']) ?>">
                            <button
                                class="btn-new btn-outline-new dropdown-toggle <?= $estadoActual ? 'estado-activo' : '' ?>">
                                <span class="material-icons-outlined"><?= $iconoBoton ?></span>
                                <span><?= $textoBoton ?></span>
                            </button>
                            <div class="dropdown-menu-marcar">
                                <a href="#"
                                    class="dropdown-item-marcar <?= $estadoActual === 'vista' ? 'activo' : '' ?>"
                                    data-status="vista">
                                    <span class="material-icons-outlined">check_circle</span>
                                    <span>Vista</span>
                                </a>
                                <a href="#"
                                    class="dropdown-item-marcar <?= $estadoActual === 'favorita' ? 'activo' : '' ?>"
                                    data-status="favorita">
                                    <span class="material-icons-outlined">favorite</span>
                                    <span>Favorita</span>
                                </a>
                                <a href="#"
                                    class="dropdown-item-marcar <?= $estadoActual === 'pendiente' ? 'activo' : '' ?>"
                                    data-status="pendiente">
                                    <span class="material-icons-outlined">schedule</span>
                                    <span>Pendiente</span>
                                </a>
                            </div>
                        </div>

                        <!-- WIDGET DE VALORACIÓN CON CORAZONES -->
                        <div class="rating-widget" data-pelicula-id="<?= $id ?>" data-valoracion="<?= $valoracionActual ?>">
                            <span class="rating-label">Tu valoración</span>
                            <div class="hearts-interactive">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="material-icons-outlined heart <?= $i <= $valoracionActual ? 'filled' : '' ?>"
                                        data-valor="<?= $i ?>">
                                        <?= $i <= $valoracionActual ? 'favorite' : 'favorite_border' ?>
                                    </span>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- CONTENIDO PRINCIPAL -->
    <div class="content-section">

        <!-- SINOPSIS -->
        <?php if (!empty($pelicula['sinopsis'])): ?>
            <div class="glass-card">
                <h2 class="section-title-new">Sinopsis</h2>
                <p class="synopsis-text"><?= htmlspecialchars($pelicula['sinopsis']) ?></p>
            </div>
        <?php endif; ?>

        <!-- GRID DOS COLUMNAS -->
        <div class="two-column-grid">

            <!-- COLUMNA IZQUIERDA -->
            <div>

                <!-- DÓNDE VER -->
                <?php if (!empty($plataformas)): ?>
                    <div class="glass-card">
                        <h2 class="section-title-new">Dónde ver</h2>
                        <div class="streaming-grid">
                            <?php foreach ($plataformas as $plataforma): ?>
                                <div class="platform-card">
                                    <?php if (!empty($plataforma['logo'])): ?>
                                        <img src="<?= htmlspecialchars($plataforma['logo']) ?>"
                                            alt="<?= htmlspecialchars($plataforma['nombre']) ?>"
                                            onerror="this.style.display='none'">
                                    <?php else: ?>
                                        <span><?= htmlspecialchars($plataforma['nombre']) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- COMENTARIOS -->
                <div class="glass-card">
                    <h2 class="section-title-new">Comentarios de la comunidad</h2>

                    <?php if ($esProximoEstreno): ?>
                        <div
                            style="display:flex; flex-direction:column; align-items:center; gap:0.75rem; padding: 1.5rem 0; text-align:center;">
                            <span class="material-icons-outlined"
                                style="font-size:2.5rem; color: var(--primary); opacity:0.8;">lock_clock</span>
                            <p style="color: rgba(255,255,255,0.6); margin:0; font-size:0.95rem;">
                                Los comentarios estarán disponibles a partir del día de su estreno.
                            </p>
                        </div>
                    <?php else: ?>
                        <?php if (!empty($comentarios)): ?>
                            <div class="comments-new">
                                <?php
                                $idUsuarioSesionComentarios = null;
                                if (isset($_SESSION['usuario'])) {
                                    $idUsuarioSesionComentarios = $_SESSION['usuario']['id_usuario'] ?? $_SESSION['usuario']['id'] ?? null;
                                }
                                ?>
                                <?php foreach ($comentarios as $comentario): ?>
                                    <?php $esPropietario = $idUsuarioSesionComentarios && (int)$comentario['id_usuario'] === (int)$idUsuarioSesionComentarios; ?>
                                    <div class="comment-card" data-comment-id="<?= (int)$comentario['id_comentario'] ?>">
                                        <div class="comment-header-new">
                                            <?php if (!empty($comentario['avatar'])): ?>
                                                <img src="<?= htmlspecialchars($comentario['avatar']) ?>" class="avatar-comentario comment-avatar-new"
                                                    alt="<?= htmlspecialchars($comentario['username']) ?>"
                                                    onerror="this.src='img/avatar-placeholder.jpg'">
                                            <?php else: ?>
                                                <div class="comment-avatar-new"
                                                    style="background: var(--primary); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1.1rem;">
                                                    <?= strtoupper(substr($comentario['username'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="comment-author-info">
                                                <div class="author-name"><?= htmlspecialchars($comentario['username']) ?></div>
                                                <div class="comment-date"><?= date('d/m/Y', strtotime($comentario['fecha'])) ?></div>
                                            </div>
                                            <?php if ($esPropietario): ?>
                                                <div class="comment-actions">
                                                    <button class="comment-action-btn btn-edit-comment"
                                                        data-id="<?= (int)$comentario['id_comentario'] ?>"
                                                        data-texto="<?= htmlspecialchars($comentario['comentario'], ENT_QUOTES) ?>"
                                                        title="Editar comentario">
                                                        <span class="material-icons-outlined">edit</span>
                                                    </button>
                                                    <button class="comment-action-btn btn-delete-comment"
                                                        data-id="<?= (int)$comentario['id_comentario'] ?>"
                                                        title="Eliminar comentario">
                                                        <span class="material-icons-outlined">delete</span>
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($comentario['valoracion_autor'])): ?>
                                            <div class="comment-rating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <span class="material-icons-outlined comment-heart <?= $i <= $comentario['valoracion_autor'] ? 'filled' : '' ?>">
                                                        <?= $i <= $comentario['valoracion_autor'] ? 'favorite' : 'favorite_border' ?>
                                                    </span>
                                                <?php endfor; ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($comentario['es_spoiler'])): ?>
                                            <div class="spoiler-wrapper">
                                                <div class="spoiler-blur" onclick="this.parentElement.classList.add('revealed')">
                                                    <p class="comment-body spoiler-body"><?= htmlspecialchars($comentario['comentario']) ?></p>
                                                    <div class="spoiler-overlay">
                                                        <button class="spoiler-btn">
                                                            <span class="material-icons-outlined">visibility_off</span>
                                                            <span>Spoiler · clic para leer</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <p class="comment-body"><?= htmlspecialchars($comentario['comentario']) ?></p>
                                        <?php endif; ?>

                                        <?php if ($esPropietario): ?>
                                            <!-- Formulario de edición inline (oculto por defecto) -->
                                            <div class="comment-edit-form" style="display:none;">
                                                <div class="comment-form-new">
                                                    <input type="text" class="comment-input-new edit-input"
                                                        value="<?= htmlspecialchars($comentario['comentario'], ENT_QUOTES) ?>"
                                                        maxlength="500">
                                                    <button type="button" class="btn-send btn-confirm-edit"
                                                        data-id="<?= (int)$comentario['id_comentario'] ?>">
                                                        <span class="material-icons-outlined">check</span>
                                                    </button>
                                                    <button type="button" class="btn-send btn-cancel-edit"
                                                        style="background: rgba(255,255,255,0.1);">
                                                        <span class="material-icons-outlined">close</span>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: rgba(255,255,255,0.5); margin-bottom: 1.5rem;">
                                Sé el primero en comentar esta película.
                            </p>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['usuario'])): ?>
                            <?php if ($errorComentario): ?>
                                <p style="color: #ef4444; margin-bottom: 0.5rem;"><?= $errorComentario ?></p>
                            <?php endif; ?>

                            <form id="formComentario" data-pelicula-id="<?= $id ?>">
                                <div class="comment-form-new">
                                    <input type="text" name="comentario" placeholder="Escribe tu comentario..."
                                        class="comment-input-new" required>
                                    <button type="submit" class="btn-send">
                                        <span class="material-icons-outlined">send</span>
                                    </button>
                                </div>
                                <label class="spoiler-check-label">
                                    <input type="checkbox" name="es_spoiler" value="1">
                                    <span class="material-icons-outlined">warning_amber</span>
                                    Contiene spoilers
                                </label>
                            </form>
                        <?php else: ?>
                            <p style="color: rgba(255,255,255,0.5); font-size:0.9rem; margin-top:1rem;">
                                <a href="login.php" style="color: var(--primary);">Inicia sesión</a> para dejar un comentario.
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- COLUMNA DERECHA -->
            <div>

                <!-- FICHA TÉCNICA -->
                <div class="glass-card info-card-gradient">
                    <h2 class="section-title-new">Ficha técnica</h2>
                    <div class="info-grid">
                        <?php if (!empty($pelicula['titulo_original'])): ?>
                            <div class="info-row">
                                <span class="info-label">Título original</span>
                                <span class="info-value"><?= htmlspecialchars($pelicula['titulo_original']) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($pelicula['director'])): ?>
                            <div class="info-row">
                                <span class="info-label">Director</span>
                                <span class="info-value"><?= htmlspecialchars($pelicula['director']) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($pelicula['guionistas'])): ?>
                            <div class="info-row">
                                <span class="info-label">Guionistas</span>
                                <span class="info-value"><?= htmlspecialchars($pelicula['guionistas']) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($pelicula['productora'])): ?>
                            <div class="info-row">
                                <span class="info-label">Productora</span>
                                <span class="info-value"><?= htmlspecialchars($pelicula['productora']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- REPARTO -->
                <?php if (!empty($reparto)): ?>
                    <div class="glass-card info-card-gradient">
                        <h2 class="section-title-new">Reparto</h2>
                        <div class="cast-grid">
                            <?php foreach ($reparto as $actor): ?>
                                <div class="cast-card-new">
                                    <div>
                                        <div class="cast-name-new"><?= htmlspecialchars($actor['nombre']) ?></div>
                                        <?php if (!empty($actor['personaje'])): ?>
                                            <div class="cast-role"><?= htmlspecialchars($actor['personaje']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- MODAL AÑADIR A LISTA -->
    <?php if (isset($_SESSION['usuario']['id'])): ?>
        <div class="modal-overlay-mis-listas" id="modalAgregarListaOverlay" style="display:none;">
            <div class="modal-content-mis-listas">
                <button class="modal-close-mis-listas" id="cerrarModalListaBtn">
                    <span class="material-icons-outlined">close</span>
                </button>

                <h2>Añadir a lista</h2>

                <div id="mensajeAgregarLista" class="alert d-none mb-3"></div>

                <?php if (!empty($listasUsuario)): ?>
                    <div class="form-group">
                        <label for="selectLista">Selecciona una de tus listas</label>
                        <select id="selectLista" class="form-select">
                            <option value="">Selecciona una lista</option>
                            <?php foreach ($listasUsuario as $lista): ?>
                                <option value="<?= $lista['id_lista'] ?>">
                                    <?= htmlspecialchars($lista['titulo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancelar" id="cancelarAgregarListaBtn">Cancelar</button>
                        <button type="button" class="btn-guardar" id="guardarEnListaBtn" data-pelicula-id="<?= $id ?>">
                            Guardar en la lista
                        </button>
                    </div>
                <?php else: ?>
                    <p style="color:#666; margin-bottom:1rem;">Todavía no tienes listas creadas.</p>
                    <div class="modal-actions">
                        <button type="button" class="btn-cancelar" id="cancelarAgregarListaBtn">Cerrar</button>
                        <a href="mis-listas.php" class="btn-guardar" style="text-decoration:none; text-align:center;">
                            Crear una lista
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- MODAL CONFIRMAR ELIMINACIÓN DE COMENTARIO -->
    <div class="modal-overlay-mis-listas" id="modalConfirmarBorrarComentario" style="display:none;">
        <div class="modal-content-mis-listas" style="max-width:420px;">
            <h2 style="margin-bottom:0.75rem;">Eliminar comentario</h2>
            <p style="color:rgba(255,255,255,0.65); margin-bottom:1.5rem;">¿Estás seguro de que quieres eliminar este comentario? Esta acción no se puede deshacer.</p>
            <div class="modal-actions">
                <button type="button" class="btn-cancelar" id="cancelarBorrarComentarioBtn">Cancelar</button>
                <button type="button" class="btn-guardar" id="confirmarBorrarComentarioBtn"
                    style="background: linear-gradient(135deg,#ef4444,#dc2626);">
                    Eliminar
                </button>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="js/detalle-pelicula.js"></script>
</body>

</html>