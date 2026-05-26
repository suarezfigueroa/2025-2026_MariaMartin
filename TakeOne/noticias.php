<!DOCTYPE html>
<html lang="es">

<head>
  <title>La Butaca Informada - TakeOne</title>
  <?php require_once 'includes/head.php'; ?>
</head>

<body>

    <?php
    require_once 'includes/conexion.php';

    // --- Paginación ---
    $por_pagina = 6; // noticias en el grid (sin contar la destacada)
    $pagina_actual = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT) ?: 1;
    if ($pagina_actual < 1) $pagina_actual = 1;

    // Total de noticias para calcular páginas
    $total = $pdo->query("SELECT COUNT(*) FROM noticias")->fetchColumn();

    // La destacada siempre es la más reciente (independiente de la página)
    $stmt_destacada = $pdo->query("SELECT * FROM noticias ORDER BY fecha DESC LIMIT 1");
    $destacada = $stmt_destacada->fetch();

    // El resto: todas menos la destacada, paginadas
    $total_resto = max(0, $total - 1);
    $total_paginas = $total_resto > 0 ? ceil($total_resto / $por_pagina) : 1;
    if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas;
    $offset = ($pagina_actual - 1) * $por_pagina;

    $stmt_resto = $pdo->prepare(
        "SELECT * FROM noticias
         WHERE id_noticia != :id
         ORDER BY fecha DESC
         LIMIT :limite OFFSET :offset"
    );
    $stmt_resto->bindValue(':id',     $destacada ? $destacada['id_noticia'] : 0, PDO::PARAM_INT);
    $stmt_resto->bindValue(':limite', $por_pagina, PDO::PARAM_INT);
    $stmt_resto->bindValue(':offset', $offset,     PDO::PARAM_INT);
    $stmt_resto->execute();
    $resto = $stmt_resto->fetchAll();
    ?>

    <?php include 'includes/header.php'; ?>

    <main class="news-section">
        <div class="container" style="max-width:var(--container-max);">

            <!-- Encabezado -->
            <div class="news-header">
                <h1>La Butaca Informada</h1>
                <p>Entrevistas, próximos estrenos y temas de interés general sobre cine.</p>
            </div>

            <!-- Noticia Destacada -->
            <?php if ($destacada): ?>
                <div class="featured-news">
                    <div class="featured-content">
                        <h2><?= htmlspecialchars($destacada['titulo']) ?></h2>
                        <p><?= htmlspecialchars($destacada['descripcion']) ?></p>
                        <a href="detalle-noticia.php?id=<?= $destacada['id_noticia'] ?>">
                            <button class="btn-read-more">Leer más</button>
                        </a>
                    </div>
                    <div class="featured-image">
                        <img src="<?= htmlspecialchars($destacada['imagen']) ?>"
                            alt="<?= htmlspecialchars($destacada['titulo']) ?>">
                    </div>
                </div>
            <?php endif; ?>

            <!-- Título sección -->
            <h2 class="section-title-news">Todas las noticias</h2>

            <!-- Grid de Noticias -->
            <div class="news-grid">
                <?php if (empty($resto)): ?>
                    <p>No hay más noticias disponibles.</p>
                <?php else: ?>
                    <?php foreach ($resto as $noticia): ?>
                        <a href="detalle-noticia.php?id=<?= $noticia['id_noticia'] ?>" class="news-card-link">
                            <article class="news-card">
                                <img src="<?= htmlspecialchars($noticia['imagen']) ?>"
                                    alt="<?= htmlspecialchars($noticia['titulo']) ?>">
                                <div class="news-card-content">
                                    <h3><?= htmlspecialchars($noticia['titulo']) ?></h3>
                                    <p><?= htmlspecialchars($noticia['descripcion']) ?></p>
                                </div>
                            </article>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <div class="pagination">
                    <!-- Flecha anterior -->
                    <?php if ($pagina_actual > 1): ?>
                        <a href="?pagina=<?= $pagina_actual - 1 ?>" class="pagination-arrow" aria-label="Página anterior">
                            <span class="material-icons-outlined">chevron_left</span>
                        </a>
                    <?php else: ?>
                        <button class="pagination-arrow" disabled aria-label="Página anterior">
                            <span class="material-icons-outlined">chevron_left</span>
                        </button>
                    <?php endif; ?>

                    <!-- Números de página -->
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <?php if ($i === $pagina_actual): ?>
                            <button class="pagination-number active"><?= $i ?></button>
                        <?php else: ?>
                            <a href="?pagina=<?= $i ?>" class="pagination-number"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <!-- Flecha siguiente -->
                    <?php if ($pagina_actual < $total_paginas): ?>
                        <a href="?pagina=<?= $pagina_actual + 1 ?>" class="pagination-arrow" aria-label="Página siguiente">
                            <span class="material-icons-outlined">chevron_right</span>
                        </a>
                    <?php else: ?>
                        <button class="pagination-arrow" disabled aria-label="Página siguiente">
                            <span class="material-icons-outlined">chevron_right</span>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

</body>

</html>