<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sección Sugerir</title>
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
    <?php include 'includes/header.php'; ?>

    <main class="py-4">
        <div class="container" style="max-width:var(--container-max);">
            <div class="sugerir-container">
                <div class="sugerir-content">
                    <h1>¿No sabes qué ver?</h1>
                    <p>Elige una opción y te ayudaremos a encontrar tu próxima película favorita.</p>

                    <div class="opciones-grid">
                        <!-- Opción 1: Filtrada por géneros -->
                        <div class="opcion-card" data-tipo="generos">
                            <div class="opcion-icon">
                                <img src="img/palomitas-de-maiz.png" alt="Géneros">
                            </div>
                            <h3>Filtrada por géneros</h3>
                            <p>Elige tus géneros preferidos.</p>
                        </div>

                        <!-- Opción 2: Basada en las películas favoritas -->
                        <div class="opcion-card" data-tipo="gustos">
                            <div class="opcion-icon">
                                <img src="img/sugerir-pelicula-favoritas.png" alt="Basada en mis gustos">
                            </div>
                            <h3>Basada en mis gustos</h3>
                            <p>Analizamos tus favoritas para darte una recomendación personal.</p>
                        </div>

                        <!-- Opción 3: Totalmente aleatoria -->
                        <div class="opcion-card" data-tipo="aleatoria">
                            <div class="opcion-icon">
                                <img src="img/pelicula-aleatoria.png" alt="Totalmente aleatoria">
                            </div>
                            <h3>Totalmente aleatoria</h3>
                            <p>Una película al azar de todo nuestro catálogo. ¡Sorpréndete!</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resultado de la sugerencia -->
            <div class="resultado-container" id="resultadoContainer" style="display: none;">
                <h2>Tu película sugerida</h2>
                <div class="slot-machine">
                    <div class="slot-item" id="slotItem">
                        <img src="" alt="Película sugerida" id="peliculaPoster">
                    </div>
                </div>
                <div class="pelicula-info" id="peliculaInfo">
                    <h3 id="peliculaTitulo"></h3>
                    <p id="peliculaDescripcion"></p>
                    <div class="pelicula-meta">
                        <span id="peliculaAnio"></span>
                        <span class="separator">•</span>
                        <span id="peliculaGenero"></span>
                    </div>
                    <button class="btn-ver-mas" id="btnVerMas">Ver más detalles</button>
                    <button class="btn-otra" id="btnOtra">Otra sugerencia</button>
                </div>
            </div>

            <!-- Historial de sugerencias -->
            <div class="historial-container" id="historialContainer" style="display: none;">
                <h2>Tus últimas sugerencias</h2>
                <div class="historial-grid" id="historialGrid"></div>
            </div>
        </div>
    </main>

    <!-- Modal para selección de géneros -->
    <div class="modal-overlay" id="modalGeneros" style="display: none;">
        <div class="modal-content">
            <button class="modal-close" id="closeModal">&times;</button>
            <h2>Selecciona tus géneros favoritos</h2>
            <p>Elige uno o varios géneros para tu recomendación</p>
            <div class="generos-grid">
                <!-- Cargado dinámicamente por sugerir.js → cargarGenerosModal() -->
                <p style="color:#999; grid-column:1/-1; text-align:center;">Cargando géneros…</p>
            </div>
            <button class="btn-confirmar" id="btnConfirmarGeneros">¡Sorpréndeme!</button>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Exponer el ID de usuario al JS -->
    <script>
        const USUARIO_ID = <?= isset($_SESSION['usuario']['id'])
                                ? (int)$_SESSION['usuario']['id']
                                : 'null' ?>;
    </script>

    <script src="js/sugerir.js"></script>

</body>

</html>