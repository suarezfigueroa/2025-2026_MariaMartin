<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php
            require_once 'includes/conexion.php';

            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                header('Location: noticias.php');
                exit;
            }

            $stmt = $pdo->prepare("SELECT * FROM noticias WHERE id_noticia = ?");
            $stmt->execute([$id]);
            $noticia = $stmt->fetch();

            if (!$noticia) {
                header('Location: noticias.php');
                exit;
            }

            // Otras noticias para el sidebar
            $stmt_otras = $pdo->prepare(
                "SELECT id_noticia, titulo, imagen, fecha FROM noticias
             WHERE id_noticia != ?
             ORDER BY fecha DESC
             LIMIT 3"
            );
            $stmt_otras->execute([$id]);
            $otras = $stmt_otras->fetchAll();

            echo htmlspecialchars($noticia['titulo']) . ' - TakeOne';
            ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <main class="detalle-noticia-main">
        <div class="container" style="max-width:var(--container-max);">

            <!-- Breadcrumb -->
            <nav class="breadcrumb-detalle">
                <a href="seccionPrincipal.php">Inicio</a>
                <span class="material-icons-outlined">chevron_right</span>
                <a href="noticias.php">Noticias</a>
                <span class="material-icons-outlined">chevron_right</span>
                <span><?= htmlspecialchars($noticia['titulo']) ?></span>
            </nav>

            <!-- Artículo -->
            <article class="article-container">
                <div class="article-header">
                    <h1 class="article-title"><?= htmlspecialchars($noticia['titulo']) ?></h1>
                    <p class="article-subtitle"><?= htmlspecialchars($noticia['descripcion']) ?></p>

                    <div class="article-meta">
                        <div class="meta-item">
                            <span class="material-icons-outlined">schedule</span>
                            <?php
                            $meses_full = [
                                '',
                                'Ene',
                                'Feb',
                                'Mar',
                                'Abr',
                                'May',
                                'Jun',
                                'Jul',
                                'Ago',
                                'Sep',
                                'Oct',
                                'Nov',
                                'Dic'
                            ];
                            $ts_n = strtotime($noticia['fecha']);
                            echo '<span>' . date('d', $ts_n) . ' ' . $meses_full[(int)date('n', $ts_n)] . ', ' . date('Y', $ts_n) . '</span>';
                            ?>
                        </div>
                        <span class="meta-separator">•</span>
                        <div class="meta-item">
                            <span class="material-icons-outlined">person</span>
                            <span>Por <strong><?= htmlspecialchars($noticia['autor']) ?></strong></span>
                        </div>
                    </div>
                </div>

                <!-- Imagen principal -->
                <div class="article-image-hero">
                    <img src="<?= htmlspecialchars($noticia['imagen']) ?>"
                        alt="<?= htmlspecialchars($noticia['titulo']) ?>">
                </div>

                <!-- Contenido + Sidebar -->
                <div class="article-content-wrapper">

                    <!-- Contenido principal -->
                    <div class="article-main-content">
                        <?php
                        $lineas = explode("\n", $noticia['contenido']);
                        $parrafo_actual = [];

                        foreach ($lineas as $linea) {
                            $linea = trim($linea);

                            // Marcador de imagen: [IMG:ruta/imagen.jpg]
                            if (preg_match('/^\[IMG:(.+?)\]$/', $linea, $matches)) {
                                // Volcar párrafo acumulado antes de la imagen
                                if (!empty($parrafo_actual)) {
                                    echo '<p>' . implode(' ', $parrafo_actual) . '</p>';
                                    $parrafo_actual = [];
                                }
                                $src = htmlspecialchars(trim($matches[1]));
                                echo '<div class="article-inline-image">';
                                echo '<img src="' . $src . '" alt="">';
                                echo '</div>';

                                // Título de sección (línea corta sin punto final, no vacía)
                            } elseif (!empty($linea) && strlen($linea) < 80 && substr($linea, -1) !== '.') {
                                // Volcar párrafo anterior
                                if (!empty($parrafo_actual)) {
                                    echo '<p>' . implode(' ', $parrafo_actual) . '</p>';
                                    $parrafo_actual = [];
                                }
                                echo '<h2 class="article-section-title">' . htmlspecialchars($linea) . '</h2>';

                                // Línea vacía → cierra párrafo actual
                            } elseif (empty($linea)) {
                                if (!empty($parrafo_actual)) {
                                    echo '<p>' . implode(' ', $parrafo_actual) . '</p>';
                                    $parrafo_actual = [];
                                }

                                // Línea de texto normal → acumular en párrafo
                            } else {
                                $parrafo_actual[] = htmlspecialchars($linea);
                            }
                        }

                        // Volcar último párrafo si queda texto
                        if (!empty($parrafo_actual)) {
                            echo '<p>' . implode(' ', $parrafo_actual) . '</p>';
                        }
                        ?>
                    </div>

                    <!-- Sidebar -->
                    <aside class="article-sidebar">
                        <div class="sidebar-card">
                            <h3 class="sidebar-title">Más Noticias</h3>
                            <div class="sidebar-news-list">
                                <?php if (empty($otras)): ?>
                                    <p>No hay más noticias.</p>
                                <?php else: ?>
                                    <?php foreach ($otras as $otra): ?>
                                        <a href="detalle-noticia.php?id=<?= $otra['id_noticia'] ?>" class="sidebar-news-item">
                                            <img src="<?= htmlspecialchars($otra['imagen']) ?>"
                                                alt="<?= htmlspecialchars($otra['titulo']) ?>">
                                            <div class="sidebar-news-info">
                                                <h4><?= htmlspecialchars($otra['titulo']) ?></h4>
                                                <span class="sidebar-news-time">
                                                    <?php
                                                    $meses = [
                                                        '',
                                                        'Ene',
                                                        'Feb',
                                                        'Mar',
                                                        'Abr',
                                                        'May',
                                                        'Jun',
                                                        'Jul',
                                                        'Ago',
                                                        'Sep',
                                                        'Oct',
                                                        'Nov',
                                                        'Dic'
                                                    ];
                                                    $ts = strtotime($otra['fecha']);
                                                    echo date('d', $ts) . ' ' . $meses[(int)date('n', $ts)];
                                                    ?>
                                                </span>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </aside>

                </div>
            </article>

        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

</body>

</html>