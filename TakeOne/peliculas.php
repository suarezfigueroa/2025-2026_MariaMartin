<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sección Películas</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
</head>

<body>
    <?php
    session_start();
    require_once 'includes/conexion.php';

    // ── Géneros para el desplegable ──────────────────────────────
    $generosStmt = $pdo->query("SELECT * FROM generos ORDER BY nombre ASC");
    $generos = $generosStmt->fetchAll();

    // ── Años disponibles para el desplegable ─────────────────────
    $aniosStmt = $pdo->query("SELECT DISTINCT anio FROM peliculas ORDER BY anio DESC");
    $anios = $aniosStmt->fetchAll(PDO::FETCH_COLUMN);

    // ── Países disponibles para el desplegable ───────────────────
    $paisesStmt = $pdo->query("SELECT DISTINCT pais FROM peliculas ORDER BY pais ASC");
    $paises = $paisesStmt->fetchAll(PDO::FETCH_COLUMN);

    // ── Plataformas para el desplegable ──────────────────────────
    $plataformasStmt = $pdo->query("SELECT * FROM plataformas ORDER BY nombre ASC");
    $plataformas = $plataformasStmt->fetchAll();

    // ── Filtros activos (GET) ─────────────────────────────────────
    $filtroGenero    = $_GET['genero']    ?? '';
    $filtroAnio      = $_GET['anio']      ?? '';
    $filtroPais      = $_GET['pais']      ?? '';
    $filtroPlataforma = $_GET['plataforma'] ?? '';
    $filtroBusqueda  = trim($_GET['busqueda'] ?? '');

    // ── Query películas recomendadas (excluyendo cartelera y próximos estrenos) ──
    $recomendadas = $pdo->query("
        SELECT p.* FROM recomendadas_semana r
        JOIN peliculas p ON p.id_pelicula = r.id_pelicula
        ORDER BY r.orden ASC
        LIMIT 10
    ")->fetchAll();

    // ── Query películas mejor valoradas ──────────────────────────
    $sqlValoradas = "SELECT p.* FROM peliculas p ORDER BY p.imdb DESC LIMIT 12";
    $valoradas = $pdo->query($sqlValoradas)->fetchAll();

    // ── Query con filtros aplicados ───────────────────────────────
    $sql = "SELECT DISTINCT p.* FROM peliculas p
            LEFT JOIN peliculas_generos pg ON p.id_pelicula = pg.id_pelicula
            LEFT JOIN peliculas_plataformas pp ON p.id_pelicula = pp.id_pelicula
            WHERE 1=1";
    $params = [];

    if ($filtroGenero !== '') {
        $sql .= " AND pg.id_genero = :genero";
        $params['genero'] = $filtroGenero;
    }
    if ($filtroAnio !== '') {
        $sql .= " AND p.anio = :anio";
        $params['anio'] = $filtroAnio;
    }
    if ($filtroPais !== '') {
        $sql .= " AND p.pais = :pais";
        $params['pais'] = $filtroPais;
    }
    if ($filtroPlataforma !== '') {
        $sql .= " AND pp.id_plataforma = :plataforma";
        $params['plataforma'] = $filtroPlataforma;
    }
    if ($filtroBusqueda !== '') {
        $sql .= " AND (p.titulo LIKE :busqueda OR p.titulo_original LIKE :busqueda)";
        $params['busqueda'] = '%' . $filtroBusqueda . '%';
    }

    $sql .= " ORDER BY p.anio DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $peliculasFiltradas = $stmt->fetchAll();

    $hayFiltros = $filtroGenero || $filtroAnio || $filtroPais || $filtroPlataforma || $filtroBusqueda;
    ?>

    <?php include 'includes/header.php'; ?>

    <main class="py-4">
        <div class="container" style="max-width:var(--container-max);">

            <!-- HERO -->
            <div class="card-hero card-hero-movies mb-5">
                <section class="hero-movies">
                    <h1>Explora el universo del cine</h1>
                    <p>Busca por título para encontrar tu próxima película favorita.</p>

                    <form method="GET" action="peliculas.php" id="formBusquedaPeliculas">

                        <!-- BARRA DE BÚSQUEDA -->
                        <div class="movie-search-bar">
                            <img src="img/lupa-de-busqueda.png" alt="Buscar" style="width:20px; height:20px;margin-right:15px;">
                            <input
                                type="text"
                                name="busqueda"
                                id="buscadorPeliculas"
                                placeholder="Encuentra una película"
                                value="<?= htmlspecialchars($filtroBusqueda) ?>"
                                autocomplete="off"
                                maxlength="100">
                            <button
                                type="button"
                                class="listas-search-clear"
                                id="limpiarBusquedaPeliculas"
                                aria-label="Limpiar búsqueda"
                                style="display:<?= $filtroBusqueda ? 'flex' : 'none' ?>;">
                                <span class="material-icons-outlined">close</span>
                            </button>
                        </div>

                        <!-- DESPLEGABLES -->
                        <div class="movie-filters">

                            <div class="filter-dropdown">
                                <button class="filter-btn <?= $filtroGenero ? 'active' : '' ?>" type="button" data-filter="genero">
                                    Género <?= $filtroGenero ? '✓' : '' ?>
                                </button>
                                <div class="dropdown-menu-custom">
                                    <button type="submit" class="dropdown-filter-option" name="genero" value="">Todos</button>
                                    <?php foreach ($generos as $g): ?>
                                        <button type="submit" class="dropdown-filter-option <?= $filtroGenero == $g['id_genero'] ? 'selected' : '' ?>"
                                            name="genero" value="<?= $g['id_genero'] ?>">
                                            <?= htmlspecialchars($g['nombre']) ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="filter-dropdown">
                                <button class="filter-btn <?= $filtroAnio ? 'active' : '' ?>" type="button" data-filter="anio">
                                    Año <?= $filtroAnio ? '✓' : '' ?>
                                </button>
                                <div class="dropdown-menu-custom">
                                    <button type="submit" class="dropdown-filter-option" name="anio" value="">Todos</button>
                                    <?php foreach ($anios as $a): ?>
                                        <button type="submit" class="dropdown-filter-option <?= $filtroAnio == $a ? 'selected' : '' ?>"
                                            name="anio" value="<?= $a ?>">
                                            <?= $a ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="filter-dropdown">
                                <button class="filter-btn <?= $filtroPais ? 'active' : '' ?>" type="button" data-filter="pais">
                                    País <?= $filtroPais ? '✓' : '' ?>
                                </button>
                                <div class="dropdown-menu-custom">
                                    <button type="submit" class="dropdown-filter-option" name="pais" value="">Todos</button>
                                    <?php foreach ($paises as $p): ?>
                                        <button type="submit" class="dropdown-filter-option <?= $filtroPais === $p ? 'selected' : '' ?>"
                                            name="pais" value="<?= htmlspecialchars($p) ?>">
                                            <?= htmlspecialchars($p) ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="filter-dropdown">
                                <button class="filter-btn <?= $filtroPlataforma ? 'active' : '' ?>" type="button" data-filter="plataforma">
                                    Plataforma <?= $filtroPlataforma ? '✓' : '' ?>
                                </button>
                                <div class="dropdown-menu-custom">
                                    <button type="submit" class="dropdown-filter-option" name="plataforma" value="">Todas</button>
                                    <?php foreach ($plataformas as $pl): ?>
                                        <button type="submit" class="dropdown-filter-option <?= $filtroPlataforma == $pl['id_plataforma'] ? 'selected' : '' ?>"
                                            name="plataforma" value="<?= $pl['id_plataforma'] ?>">
                                            <?= htmlspecialchars($pl['nombre']) ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <?php if ($hayFiltros): ?>
                                <a href="peliculas.php" class="filter-btn" style="text-decoration:none;">✕ Limpiar filtros</a>
                            <?php endif; ?>

                        </div>
                    </form>
                </section>
            </div>

            <!-- RESULTADOS DINÁMICOS (búsqueda en tiempo real) -->
            <div id="resultadosDinamicos" style="display:none;">
                <div class="card-hero card-hero-sliders mb-5">
                    <section>
                        <div class="section-header">
                            <h2 class="section-title" id="tituloDinamico"></h2>
                        </div>
                        <div class="results-grid" id="gridDinamico"></div>
                    </section>
                </div>
            </div>

            <!-- RESULTADOS DE BÚSQUEDA / FILTROS -->
            <?php if ($hayFiltros): ?>
                <?php
                // Construir etiqueta descriptiva del filtro activo
                $etiquetas = [];

                if ($filtroGenero) {
                    $g = array_filter($generos, fn($x) => $x['id_genero'] == $filtroGenero);
                    $g = reset($g);
                    if ($g) $etiquetas[] = 'Género: ' . $g['nombre'];
                }
                if ($filtroAnio) {
                    $etiquetas[] = 'Año: ' . $filtroAnio;
                }
                if ($filtroPais) {
                    $etiquetas[] = 'País: ' . $filtroPais;
                }
                if ($filtroPlataforma) {
                    $pl = array_filter($plataformas, fn($x) => $x['id_plataforma'] == $filtroPlataforma);
                    $pl = reset($pl);
                    if ($pl) $etiquetas[] = 'Plataforma: ' . $pl['nombre'];
                }
                if ($filtroBusqueda) {
                    $etiquetas[] = '"' . $filtroBusqueda . '"';
                }

                $textoFiltro = implode(' · ', $etiquetas);
                ?>
                <div class="card-hero card-hero-sliders mb-5" id="seccionFiltrosPHP">
                    <section>
                        <div class="section-header">
                            <h2 class="section-title">
                                <span style="color: var(--accent-light);"><?= htmlspecialchars($textoFiltro) ?></span>
                                <span style="font-size:1rem; font-weight:400; opacity:0.7;">
                                    (<?= count($peliculasFiltradas) ?> películas)
                                </span>
                            </h2>
                        </div>

                        <?php if (empty($peliculasFiltradas)): ?>
                            <p style="color: rgba(255,255,255,0.6); padding: 2rem 0;">
                                No se encontraron películas con esos filtros.
                            </p>
                        <?php else: ?>
                            <div class="results-grid">
                                <?php foreach ($peliculasFiltradas as $peli): ?>
                                    <div class="h-item">
                                        <a href="detalle-pelicula.php?id=<?= $peli['id_pelicula'] ?>">
                                            <img src="<?= htmlspecialchars($peli['poster']) ?>"
                                                alt="<?= htmlspecialchars($peli['titulo']) ?>"
                                                onerror="this.src='img/poster-placeholder.jpg'">
                                            <div class="hover-info">
                                                <h6><?= htmlspecialchars($peli['titulo']) ?></h6>
                                                <small><?= $peli['imdb'] ?> ⭐</small>
                                            </div>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            <?php endif; ?>

            <!-- SLIDERS PRINCIPALES -->
            <div class="card-hero card-hero-sliders" id="seccionSliders">

                <!-- Películas Recomendadas -->
                <section class="mb-5">
                    <div class="section-header">
                        <h2 class="section-title">Recomendadas de la semana</h2>
                    </div>

                    <div class="slider-wrapper">
                        <button class="carousel-control-custom left" id="popularLeft">
                            <span class="material-icons-outlined">chevron_left</span>
                        </button>

                        <div class="d-flex h-snap scrollbar-hide" id="popularSlider">
                            <?php foreach ($recomendadas as $peli): ?>
                                <div class="h-item">
                                    <a href="detalle-pelicula.php?id=<?= $peli['id_pelicula'] ?>">
                                        <img src="<?= htmlspecialchars($peli['poster']) ?>"
                                            alt="<?= htmlspecialchars($peli['titulo']) ?>"
                                            onerror="this.src='img/poster-placeholder.jpg'">
                                        <div class="hover-info">
                                            <h6><?= htmlspecialchars($peli['titulo']) ?></h6>
                                            <small><?= $peli['imdb'] ?> ⭐</small>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button class="carousel-control-custom right" id="popularRight">
                            <span class="material-icons-outlined">chevron_right</span>
                        </button>
                    </div>
                </section>

                <!-- Mejor Valoradas -->
                <section class="mb-5">
                    <div class="section-header">
                        <h2 class="section-title">Mejor Valoradas</h2>
                    </div>

                    <div class="slider-wrapper">
                        <button class="carousel-control-custom left" id="valoradasLeft">
                            <span class="material-icons-outlined">chevron_left</span>
                        </button>

                        <div class="d-flex h-snap scrollbar-hide" id="valoradasSlider">
                            <?php foreach ($valoradas as $peli): ?>
                                <div class="h-item">
                                    <a href="detalle-pelicula.php?id=<?= $peli['id_pelicula'] ?>">
                                        <img src="<?= htmlspecialchars($peli['poster']) ?>"
                                            alt="<?= htmlspecialchars($peli['titulo']) ?>"
                                            onerror="this.src='img/poster-placeholder.jpg'">
                                        <div class="hover-info">
                                            <h6><?= htmlspecialchars($peli['titulo']) ?></h6>
                                            <small><?= $peli['imdb'] ?> ⭐</small>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button class="carousel-control-custom right" id="valoradasRight">
                            <span class="material-icons-outlined">chevron_right</span>
                        </button>
                    </div>
                </section>

            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/peliculas.js"></script>
</body>

</html>