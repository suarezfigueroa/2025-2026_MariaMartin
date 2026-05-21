<?php
require_once '_guard.php';
require_once '../includes/conexion.php';

/* ── ESTADÍSTICAS GENERALES ─────────────────────────────────── */

$total_usuarios   = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$total_peliculas  = $pdo->query("SELECT COUNT(*) FROM peliculas")->fetchColumn();
$total_grupos     = $pdo->query("SELECT COUNT(*) FROM grupos")->fetchColumn();
$total_noticias   = $pdo->query("SELECT COUNT(*) FROM noticias")->fetchColumn();
$total_comentarios = $pdo->query("SELECT COUNT(*) FROM comentarios_peliculas")->fetchColumn();

// Mensajes de contacto sin leer
$mensajes_sin_leer = 0;
try {
    $mensajes_sin_leer = $pdo->query("SELECT COUNT(*) FROM contacto WHERE leido = 0")->fetchColumn();
} catch (PDOException $e) {
    // El campo 'leido' aún no existe, se muestra 0
}

$usuarios_este_mes = $pdo->query("
    SELECT COUNT(*) FROM usuarios
    WHERE MONTH(fecha_registro) = MONTH(CURRENT_DATE())
    AND YEAR(fecha_registro) = YEAR(CURRENT_DATE())
")->fetchColumn();

$en_cartelera = $pdo->query("SELECT COUNT(*) FROM peliculas_en_cartelera")->fetchColumn();
$proximos_estrenos = $pdo->query("SELECT COUNT(*) FROM proximos_estrenos")->fetchColumn();

/* ── USUARIOS RECIENTES ─────────────────────────────────────── */

$stmt_usuarios = $pdo->query("
    SELECT id_usuario, username, email, rol, avatar, fecha_registro
    FROM usuarios
    ORDER BY fecha_registro DESC
    LIMIT 5
");
$usuarios_recientes = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);

/* ── PELÍCULAS RECIENTES ────────────────────────────────────── */

$stmt_peliculas = $pdo->query("
    SELECT id_pelicula, titulo, anio, director, imdb, poster
    FROM peliculas
    ORDER BY id_pelicula DESC
    LIMIT 5
");
$peliculas_recientes = $stmt_peliculas->fetchAll(PDO::FETCH_ASSOC);

/* ── COMENTARIOS RECIENTES ──────────────────────────────────── */

$stmt_comentarios = $pdo->query("
    SELECT cp.id_comentario, cp.comentario, cp.fecha,
           u.username,
           p.titulo AS titulo_pelicula, p.id_pelicula
    FROM comentarios_peliculas cp
    LEFT JOIN usuarios u ON cp.id_usuario = u.id_usuario
    LEFT JOIN peliculas p ON cp.id_pelicula = p.id_pelicula
    ORDER BY cp.fecha DESC
    LIMIT 4
");
$comentarios_recientes = $stmt_comentarios->fetchAll(PDO::FETCH_ASSOC);

/* ── MENSAJES DE CONTACTO ───────────────────────────────────── */

$stmt_contacto = $pdo->query("
    SELECT id_contacto, nombre, email, motivo, fecha, id_usuario
    FROM contacto
    ORDER BY fecha DESC
    LIMIT 4
");
$mensajes_contacto = $stmt_contacto->fetchAll(PDO::FETCH_ASSOC);

/* ── ACTIVIDAD RECIENTE ─────────────────────────────────────── */

$stmt_actividad = $pdo->query("
    SELECT a.tipo, a.descripcion, a.fecha, u.username,
           p.titulo AS titulo_pelicula, p.id_pelicula AS pid,
           up.valoracion
    FROM actividad_usuario a
    LEFT JOIN usuarios u ON a.id_usuario = u.id_usuario
    LEFT JOIN peliculas p ON p.id_pelicula = CAST(
        REGEXP_SUBSTR(a.descripcion, '[0-9]+') AS UNSIGNED
    )
    LEFT JOIN usuarios_peliculas up
        ON up.id_usuario = a.id_usuario
        AND up.id_pelicula = p.id_pelicula
    ORDER BY a.fecha DESC
    LIMIT 8
");
$actividad_reciente = $stmt_actividad->fetchAll(PDO::FETCH_ASSOC);

/* ── GRUPOS CON MÁS MIEMBROS ────────────────────────────────── */

$stmt_grupos = $pdo->query("
    SELECT g.id_grupo, g.nombre, g.tipo,
           COUNT(gu.id_usuario) AS total_miembros
    FROM grupos g
    LEFT JOIN grupos_usuarios gu ON g.id_grupo = gu.id_grupo
    GROUP BY g.id_grupo
    ORDER BY total_miembros DESC
    LIMIT 4
");
$grupos_top = $stmt_grupos->fetchAll(PDO::FETCH_ASSOC);

/* ── HELPERS ────────────────────────────────────────────────── */

function tiempo_relativo($fecha)
{
    $diff = time() - strtotime($fecha);
    if ($diff < 60)         return 'hace un momento';
    if ($diff < 3600)       return 'hace ' . floor($diff / 60) . ' min';
    if ($diff < 86400)      return 'hace ' . floor($diff / 3600) . ' h';
    if ($diff < 604800)     return 'hace ' . floor($diff / 86400) . ' días';
    return date('d/m/Y', strtotime($fecha));
}

function icono_actividad($tipo, $valoracion = null)
{
    if ($tipo === null && $valoracion !== null) return '♥';
    $iconos = [
        'favorita'   => '★',
        'vista'      => '✓',
        'pendiente'  => '◷',
        'comentario' => '✎',
        'valoracion' => '♥',
    ];
    return $iconos[$tipo] ?? '·';
}

function iniciales($nombre)
{
    $partes = explode(' ', trim($nombre));
    $ini = strtoupper(substr($partes[0], 0, 1));
    if (isset($partes[1])) $ini .= strtoupper(substr($partes[1], 0, 1));
    else $ini .= strtoupper(substr($partes[0], 1, 1)); // segunda letra si una sola palabra
    return $ini;
}

$colores_avatar = ['#dc3232', '#2e9b29', '#257fb0', '#0F6E56', '#d156aa'];

function color_avatar($id, $rol = 'usuario')
{
    global $colores_avatar;
    if ($rol === 'admin') return '#e5533d';
    return $colores_avatar[$id % count($colores_avatar)];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin · Dashboard — TakeOne</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>

<body class="admin-layout">

    <?php include '_sidebar.php'; ?>

    <div class="admin-main">

        <!-- TOPBAR -->
        <?php
        $topbar_title = 'Panel';
        $topbar_sub   = 'Resumen general · ' . date('d/m/Y');
        include '_topbar.php';
        ?>

        <div class="admin-content">

            <!-- TARJETAS DE ESTADÍSTICAS -->
            <div class="admin-stats">
                <div class="stat-card">
                    <p class="stat-card__label">Usuarios</p>
                    <p class="stat-card__value"><?= $total_usuarios ?></p>
                    <p class="stat-card__delta"><?= $usuarios_este_mes ?> registrado<?= $usuarios_este_mes != 1 ? 's' : '' ?> este mes</p>
                </div>
                <div class="stat-card">
                    <p class="stat-card__label">Películas</p>
                    <p class="stat-card__value"><?= $total_peliculas ?></p>
                    <p class="stat-card__delta stat-card__delta--neutral"><?= $en_cartelera ?> en cartelera · <?= $proximos_estrenos ?> estrenos</p>
                </div>
                <div class="stat-card">
                    <p class="stat-card__label">Comentarios</p>
                    <p class="stat-card__value"><?= $total_comentarios ?></p>
                </div>
                <div class="stat-card">
                    <p class="stat-card__label">Mensajes sin leer</p>
                    <p class="stat-card__value"><?= $mensajes_sin_leer ?></p>
                    <p class="stat-card__delta <?= $mensajes_sin_leer > 0 ? 'stat-card__delta--warn' : 'stat-card__delta--neutral' ?>">
                        <?= $mensajes_sin_leer > 0 ? 'Pendiente de revisión' : 'Todo al día' ?>
                    </p>
                </div>
            </div>

            <!-- FILA 1: USUARIOS + COMENTARIOS -->
            <div class="admin-grid-2">

                <!-- USUARIOS RECIENTES -->
                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <h2 class="admin-panel__title">Usuarios recientes</h2>
                        <a href="usuarios.php" class="admin-panel__link">Ver todos →</a>
                    </div>
                    <div class="admin-panel__body">
                        <?php foreach ($usuarios_recientes as $u): ?>
                            <div class="admin-row">
                                <div class="admin-row__avatar" style="background: <?= color_avatar($u['id_usuario'], $u['rol']) ?>">
                                    <?= iniciales($u['username']) ?>
                                </div>
                                <div class="admin-row__info">
                                    <p class="admin-row__name"><?= htmlspecialchars($u['username']) ?></p>
                                    <p class="admin-row__sub"><?= htmlspecialchars($u['email']) ?></p>
                                </div>
                                <span class="admin-badge <?= $u['rol'] === 'admin' ? 'admin-badge--purple' : 'admin-badge--gray' ?>">
                                    <?= $u['rol'] ?>
                                </span>
                                <div class="admin-row__actions">
                                    <a href="usuarios.php?ver=<?= $u['id_usuario'] ?>" class="admin-btn-xs admin-btn-xs-editar">Ver</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- COMENTARIOS RECIENTES -->
                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <h2 class="admin-panel__title">Comentarios recientes</h2>
                        <a href="comentarios.php" class="admin-panel__link">Moderar →</a>
                    </div>
                    <div class="admin-panel__body">
                        <?php foreach ($comentarios_recientes as $c): ?>
                            <div class="admin-comment">
                                <p class="admin-comment__meta">
                                    <strong><?= htmlspecialchars($c['username'] ?? 'Anónimo') ?></strong>
                                    · <?= htmlspecialchars($c['titulo_pelicula'] ?? 'Película eliminada') ?>
                                    · <?= tiempo_relativo($c['fecha']) ?>
                                </p>
                                <p class="admin-comment__text">"<?= htmlspecialchars(mb_substr($c['comentario'], 0, 90)) ?><?= mb_strlen($c['comentario']) > 90 ? '…' : '' ?>"</p>
                                <div class="admin-comment__actions">
                                    <a href="comentarios.php?eliminar=<?= $c['id_comentario'] ?>" class="admin-btn-xs admin-btn-xs--danger"
                                        onclick="return confirm('¿Eliminar este comentario?')">Eliminar</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <!-- FILA 2: PELÍCULAS + CONTACTO -->
            <div class="admin-grid-2">

                <!-- PELÍCULAS RECIENTES -->
                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <h2 class="admin-panel__title">Últimas películas añadidas</h2>
                        <a href="peliculas.php?nueva=1" class="admin-panel__link">+ Añadir</a>
                    </div>
                    <div class="admin-panel__body">
                        <?php foreach ($peliculas_recientes as $p): ?>
                            <div class="admin-row">
                                <div class="admin-poster">
                                    <?php if ($p['poster']): ?>
                                        <img src="<?= htmlspecialchars($p['poster']) ?>" alt="<?= htmlspecialchars($p['titulo']) ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="admin-row__info">
                                    <p class="admin-row__name"><?= htmlspecialchars($p['titulo']) ?></p>
                                    <p class="admin-row__sub"><?= $p['anio'] ?> · <?= htmlspecialchars($p['director']) ?> · <?= $p['imdb'] ?> IMDB</p>
                                </div>
                                <div class="admin-row__actions">
                                    <a href="peliculas.php?editar=<?= $p['id_pelicula'] ?>" class="admin-btn-xs admin-btn-xs-editar">Editar</a>
                                    <a href="peliculas.php?eliminar=<?= $p['id_pelicula'] ?>" class="admin-btn-xs admin-btn-xs--danger"
                                        onclick="return confirm('¿Eliminar <?= htmlspecialchars($p['titulo']) ?>?')">Eliminar</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- MENSAJES DE CONTACTO -->
                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <h2 class="admin-panel__title">Mensajes de contacto</h2>
                        <a href="contacto.php" class="admin-panel__link">Ver todos →</a>
                    </div>
                    <div class="admin-panel__body">
                        <?php foreach ($mensajes_contacto as $m): ?>
                            <?php
                            // Determinar si está leído
                            $leido = isset($m['leido']) ? $m['leido'] : true;
                            ?>
                            <div class="admin-contacto <?= !$leido ? 'admin-contacto--nuevo' : '' ?>">
                                <div class="admin-contacto__dot <?= $leido ? 'admin-contacto__dot--leido' : '' ?>"></div>
                                <div class="admin-row__info">
                                    <p class="admin-row__name"><?= htmlspecialchars($m['nombre']) ?></p>
                                    <p class="admin-row__sub"><?= htmlspecialchars($m['email']) ?></p>
                                    <p class="admin-contacto__motivo">"<?= htmlspecialchars(mb_substr($m['motivo'], 0, 70)) ?><?= mb_strlen($m['motivo']) > 70 ? '…' : '' ?>"</p>
                                    <p class="admin-row__sub"><?= tiempo_relativo($m['fecha']) ?></p>
                                </div>
                                <a href="contacto.php?ver=<?= $m['id_contacto'] ?>" class="admin-btn-xs admin-btn-xs-editar">Ver</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <!-- FILA 3: ACTIVIDAD + GRUPOS -->
            <div class="admin-grid-2">

                <!-- ACTIVIDAD RECIENTE -->
                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <h2 class="admin-panel__title">Actividad reciente</h2>
                    </div>
                    <div class="admin-panel__body">
                        <?php foreach ($actividad_reciente as $a):
                            $pelicula_nombre = htmlspecialchars($a['titulo_pelicula'] ?? $a['descripcion']);
                            $usuario = htmlspecialchars($a['username'] ?? 'Usuario');
                            $tipo_real = $a['tipo'] ?? ($a['valoracion'] !== null ? 'valoracion' : null);
                            if ($tipo_real === 'comentario') {
                                $texto = "<strong>{$usuario}</strong> escribió un comentario en <em>{$pelicula_nombre}</em>";
                            } elseif ($tipo_real === 'favorita') {
                                $texto = "<strong>{$usuario}</strong> marcó como <em>favorita</em> &mdash; {$pelicula_nombre}";
                            } elseif ($tipo_real === 'vista') {
                                $texto = "<strong>{$usuario}</strong> marcó como <em>vista</em> &mdash; {$pelicula_nombre}";
                            } elseif ($tipo_real === 'pendiente') {
                                $texto = "<strong>{$usuario}</strong> añadió a <em>pendientes</em> &mdash; {$pelicula_nombre}";
                            } elseif ($tipo_real === 'valoracion') {
                                $corazones = !empty($a['valoracion']) ? str_repeat('♥', (int)$a['valoracion']) : '♥';
                                $texto = "<strong>{$usuario}</strong> valoró con <em><span style='color:#e8638c'>{$corazones}</span></em> &mdash; {$pelicula_nombre}";
                            } else {
                                $texto = "<strong>{$usuario}</strong> &mdash; {$pelicula_nombre}";
                            }
                        ?>
                            <div class="admin-actividad">
                                <span class="admin-actividad__icono<?= $tipo_real ? ' badge-' . $tipo_real : '' ?>"><?= icono_actividad($tipo_real, $a['valoracion']) ?></span>
                                <div class="admin-row__info">
                                    <p class="admin-row__name"><?= $texto ?></p>
                                    <p class="admin-row__sub"><?= tiempo_relativo($a['fecha']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- GRUPOS TOP -->
                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <h2 class="admin-panel__title">Grupos más activos</h2>
                        <a href="grupos.php" class="admin-panel__link">Ver todos →</a>
                    </div>
                    <div class="admin-panel__body">
                        <?php foreach ($grupos_top as $g): ?>
                            <div class="admin-row">
                                <div class="admin-row__info">
                                    <p class="admin-row__name"><?= htmlspecialchars($g['nombre']) ?></p>
                                    <p class="admin-row__sub"><?= ucfirst($g['tipo']) ?> · <?= $g['total_miembros'] ?> miembro<?= $g['total_miembros'] != 1 ? 's' : '' ?></p>
                                </div>
                                <div class="admin-row__actions">
                                    <a href="grupos.php?ver=<?= $g['id_grupo'] ?>" class="admin-btn-xs admin-btn-xs-editar">Ver</a>
                                    <a href="grupos.php?eliminar=<?= $g['id_grupo'] ?>" class="admin-btn-xs admin-btn-xs--danger"
                                        onclick="return confirm('¿Eliminar el grupo <?= htmlspecialchars($g['nombre']) ?>?')">Eliminar</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

        </div><!-- /admin-content -->
    </div><!-- /admin-main -->

</body>

</html>