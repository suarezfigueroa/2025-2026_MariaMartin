<?php

session_start();

if (!isset($_SESSION['usuario']['id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/conexion.php';

$idUsuario = (int) $_SESSION['usuario']['id'];
$yo        = $idUsuario; // necesario para _seccion_amigos_perfil.php

$stmtUsuario = $pdo->prepare("
    SELECT id_usuario, username, email, avatar, biografia, localidad
    FROM usuarios
    WHERE id_usuario = :id_usuario
    LIMIT 1
");
$stmtUsuario->execute(['id_usuario' => $idUsuario]);
$usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    http_response_code(404);
    exit('usuario no encontrado');
}

$stmtFavoritas = $pdo->prepare("
    SELECT ufp.orden, p.id_pelicula, p.titulo, p.poster
    FROM usuarios_favoritas_perfil ufp
    INNER JOIN peliculas p ON p.id_pelicula = ufp.id_pelicula
    WHERE ufp.id_usuario = :id_usuario
    ORDER BY ufp.orden ASC
");
$stmtFavoritas->execute(['id_usuario' => $idUsuario]);
$peliculasFavoritas = $stmtFavoritas->fetchAll(PDO::FETCH_ASSOC);

$stmtGeneros = $pdo->prepare("
    SELECT g.nombre
    FROM usuarios_generos_favoritos ugf
    INNER JOIN generos g ON g.id_genero = ugf.id_genero
    WHERE ugf.id_usuario = :id_usuario
    ORDER BY g.nombre ASC
");
$stmtGeneros->execute(['id_usuario' => $idUsuario]);
$generosFavoritos = $stmtGeneros->fetchAll(PDO::FETCH_ASSOC);

$avatar   = !empty($usuario['avatar'])    ? $usuario['avatar']    : 'img/default-avatar.png';
$bio      = !empty($usuario['biografia']) ? $usuario['biografia'] : 'Todavía no has escrito tu biografía.';
$localidad = !empty($usuario['localidad']) ? $usuario['localidad'] : '';

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>perfil - takeone</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <main class="py-4">
        <div class="container" style="max-width:var(--container-max);">
            <div class="perfil-hero">
                <div class="perfil-header">
                    <div class="perfil-avatar-wrapper">
                        <img src="<?= htmlspecialchars($avatar) ?>"
                            alt="<?= htmlspecialchars($usuario['username']) ?>"
                            class="perfil-avatar-large">
                    </div>

                    <div class="perfil-info">
                        <h1 class="perfil-username"><?= htmlspecialchars($usuario['username']) ?></h1>
                        <p class="perfil-tagline"><?= htmlspecialchars($bio) ?></p>

                        <?php if (!empty($localidad)): ?>
                            <p class="perfil-location">
                                <span class="material-icons-outlined">location_on</span>
                                <?= htmlspecialchars($localidad) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <a href="editar-perfil.php" class="btn-editar-perfil">
                        <span class="material-icons-outlined">edit</span>
                        Editar perfil
                    </a>
                </div>

                <section class="perfil-section">
                    <h2 class="perfil-section-title">Películas favoritas</h2>
                    <div class="perfil-movies-grid">
                        <?php if (!empty($peliculasFavoritas)): ?>
                            <?php foreach ($peliculasFavoritas as $pelicula): ?>
                                <a href="detalle-pelicula.php?id=<?= (int) $pelicula['id_pelicula'] ?>"
                                    class="perfil-movie-card">
                                    <img
                                        src="<?= htmlspecialchars($pelicula['poster'] ?: 'img/placeholder-movie.jpg') ?>"
                                        alt="<?= htmlspecialchars($pelicula['titulo']) ?>"
                                        onerror="this.src='img/placeholder-movie.jpg'">
                                </a>
                            <?php endforeach; ?>

                            <?php for ($i = count($peliculasFavoritas); $i < 5; $i++): ?>
                                <div class="perfil-movie-card perfil-movie-card-empty">
                                    <span class="material-icons-outlined">add</span>
                                </div>
                            <?php endfor; ?>
                        <?php else: ?>
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <div class="perfil-movie-card perfil-movie-card-empty">
                                    <span class="material-icons-outlined">add</span>
                                </div>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="perfil-section">
                    <h2 class="perfil-section-title">Géneros favoritos</h2>
                    <div class="perfil-genres">
                        <?php if (!empty($generosFavoritos)): ?>
                            <?php foreach ($generosFavoritos as $genero): ?>
                                <span class="perfil-genre-tag"><?= htmlspecialchars($genero['nombre']) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="perfil-empty-text">Todavía no has elegido géneros favoritos.</p>
                        <?php endif; ?>
                    </div>
                </section>

                <?php
                // ── Amigos del usuario ────────────────────────────────────
                $stmtAmigos = $pdo->prepare("
                    SELECT u.id_usuario, u.username, u.avatar
                    FROM amistades a
                    INNER JOIN usuarios u ON u.id_usuario = CASE
                        WHEN a.id_emisor  = :yo1 THEN a.id_receptor
                        ELSE a.id_emisor
                    END
                    WHERE (a.id_emisor = :yo2 OR a.id_receptor = :yo3)
                      AND a.estado = 'aceptada'
                    ORDER BY u.username ASC
                ");
                $stmtAmigos->execute([':yo1' => $idUsuario, ':yo2' => $idUsuario, ':yo3' => $idUsuario]);
                $amigos = $stmtAmigos->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <?php if (!empty($amigos)): ?>
                    <section class="perfil-section">
                        <h2 class="perfil-section-title">Amigos</h2>
                        <div class="perfil-amigos-grid">
                            <?php foreach ($amigos as $amigo):
                                $avatarAmigo = !empty($amigo['avatar']) ? $amigo['avatar'] : null;
                                $inicialAmigo = strtoupper(substr($amigo['username'], 0, 1));
                            ?>
                                <a href="perfil-amigo.php?id=<?= (int) $amigo['id_usuario'] ?>" class="perfil-amigo-card">
                                    <div class="perfil-amigo-avatar">
                                        <?php if ($avatarAmigo): ?>
                                            <img src="<?= htmlspecialchars($avatarAmigo) ?>"
                                                alt="<?= htmlspecialchars($amigo['username']) ?>">
                                        <?php else: ?>
                                            <div class="perfil-amigo-inicial"><?= $inicialAmigo ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="perfil-amigo-username"><?= htmlspecialchars($amigo['username']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>

</html>