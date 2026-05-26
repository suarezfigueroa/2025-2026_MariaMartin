<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listas - TakeOne</title>
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

    $id_usuario_sesion = $_SESSION['usuario']['id'] ?? null;

    $sql = "SELECT l.id_lista, l.titulo, l.imagen,
                COUNT(DISTINCT lp.id_pelicula) AS num_peliculas,
                COUNT(DISTINCT ll.id_usuario) AS num_likes
            FROM listas l
            LEFT JOIN listas_peliculas lp ON l.id_lista = lp.id_lista
            LEFT JOIN listas_likes ll ON l.id_lista = ll.id_lista
            WHERE l.visibilidad = 'publica'
            OR (l.visibilidad = 'privada' AND l.id_usuario = :id_usuario)
            OR (l.visibilidad = 'amigos'  AND l.id_usuario = :id_usuario)
            GROUP BY l.id_lista
            ORDER BY l.fecha_creacion DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_usuario' => $id_usuario_sesion]);
    $listas = $stmt->fetchAll();

    $usuario_logueado = isset($_SESSION['usuario']);
    ?>

    <?php include 'includes/header.php'; ?>

    <main class="py-4">
        <div class="container" style="max-width:var(--container-max);">
            <!-- Hero Section -->
            <div class="listas-hero">
                <div class="listas-hero-content">
                    <div class="listas-hero-text">
                        <h1>Descubre listas de películas</h1>
                        <p>Nuestras listas están hechas por y para amantes del cine. Comparte, comenta y descubre qué
                            ver según tu estado de ánimo.</p>
                    </div>

                    <!-- Buscador -->
                    <div class="listas-search-gradient-wrap">
                        <div class="listas-search-wrap">
                            <span class="material-icons-outlined listas-search-icon">search</span>
                            <input
                                type="text"
                                id="buscadorListas"
                                class="listas-search-input"
                                placeholder="Busca por nombre o palabra..."
                                autocomplete="off"
                                maxlength="100">
                            <button class="listas-search-clear" id="limpiarBusqueda" aria-label="Limpiar búsqueda" style="display:none;">
                                <span class="material-icons-outlined">close</span>
                            </button>
                        </div>
                    </div>

                    <button class="crear-lista-btn" data-logueado="<?= $usuario_logueado ? 'true' : 'false' ?>">
                        <span>Crear lista</span>
                        <span style="font-size: 1.5rem; font-weight: bold;">+</span>
                    </button>
                </div>
            </div>

            <!-- Grid de Listas -->
            <div class="listas-container">
                <!-- Aviso de resultados (oculto por defecto) -->
                <p class="listas-search-info" id="resultadosBusqueda" style="display:none;"></p>

                <div class="listas-grid" id="listasGrid">
                    <?php if ($listas): ?>
                        <?php foreach ($listas as $lista): ?>
                            <div class="lista-card">
                                <a href="detalle-lista.php?id=<?= $lista['id_lista'] ?>">
                                    <img src="<?= htmlspecialchars($lista['imagen'] ?: 'img/logo gato sin fondo.png') ?>"
                                        onerror="this.src='img/logo gato sin fondo.png'"
                                        alt="<?= htmlspecialchars($lista['titulo']) ?>">
                                    <div class="lista-overlay">
                                        <span class="lista-badge"><?= htmlspecialchars($lista['titulo']) ?></span>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center text-muted">No hay listas disponibles todavía.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Modal Crear Lista -->
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
                    <div class="portada-upload" id="portadaUpload">
                        <img id="portadaPreviewImg" src="" alt=""
                            style="display:none; width:100%; height:100%; object-fit:cover; border-radius:8px;">
                        <div id="portadaPlaceholder">
                            <span class="material-icons-outlined"
                                style="font-size:2rem; color:#aaa;">add_photo_alternate</span>
                            <span style="color:#aaa; font-size:0.9rem;">Haz clic para seleccionar una imagen</span>
                        </div>
                    </div>
                    <input type="file" id="listaPortada" accept="image/jpeg,image/png,image/webp,image/gif"
                        style="display:none;">
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancelar" id="cancelarBtn">Cancelar</button>
                    <button type="button" class="btn-guardar" id="guardarListaBtn">Crear lista</button>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/listas.js"></script>
</body>

</html>