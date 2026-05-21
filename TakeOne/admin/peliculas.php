<?php
require_once '_guard.php';
require_once '../includes/conexion.php';

$mensaje = '';
$error   = '';

/* ── ACCIONES POST ──────────────────────────────────────────── */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    /* ── GUARDAR PELÍCULA (nueva o edición) ── */
    if ($accion === 'guardar') {
        $id           = (int)($_POST['id_pelicula'] ?? 0);
        $titulo       = trim($_POST['titulo'] ?? '');
        $titulo_orig  = trim($_POST['titulo_original'] ?? '');
        $anio         = (int)($_POST['anio'] ?? 0);
        $duracion     = (int)($_POST['duracion'] ?? 0);
        $pais         = trim($_POST['pais'] ?? '');
        $sinopsis     = trim($_POST['sinopsis'] ?? '');
        $imdb = ($_POST['imdb'] ?? '') !== '' ? (float)$_POST['imdb'] : null;
        $poster       = trim($_POST['poster'] ?? '');
        $backdrop     = trim($_POST['backdrop'] ?? '');
        $trailer_url  = trim($_POST['trailer_url'] ?? '');
        $director     = trim($_POST['director'] ?? '');
        $guionistas   = trim($_POST['guionistas'] ?? '');
        $productora   = trim($_POST['productora'] ?? '');
        $generos_sel  = $_POST['generos'] ?? [];
        $plataformas_sel = $_POST['plataformas'] ?? [];

        if ($titulo === '') {
            $error = 'El título es obligatorio.';
        } else {
            if ($id > 0) {
                // EDITAR
                $stmt = $pdo->prepare("
                    UPDATE peliculas SET
                        titulo=?, titulo_original=?, anio=?, duracion=?, pais=?,
                        sinopsis=?, imdb=?, poster=?, backdrop=?, trailer_url=?,
                        director=?, guionistas=?, productora=?
                    WHERE id_pelicula=?
                ");
                $stmt->execute([
                    $titulo,
                    $titulo_orig,
                    $anio ?: null,
                    $duracion ?: null,
                    $pais,
                    $sinopsis,
                    $imdb,
                    $poster,
                    $backdrop,
                    $trailer_url,
                    $director,
                    $guionistas,
                    $productora,
                    $id
                ]);
                $mensaje = "Película «{$titulo}» actualizada correctamente.";
            } else {
                // CREAR
                $stmt = $pdo->prepare("
                    INSERT INTO peliculas
                        (titulo, titulo_original, anio, duracion, pais, sinopsis, imdb,
                         poster, backdrop, trailer_url, director, guionistas, productora)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
                ");
                $stmt->execute([
                    $titulo,
                    $titulo_orig,
                    $anio ?: null,
                    $duracion ?: null,
                    $pais,
                    $sinopsis,
                    $imdb,
                    $poster,
                    $backdrop,
                    $trailer_url,
                    $director,
                    $guionistas,
                    $productora
                ]);
                $id = (int)$pdo->lastInsertId();
                $mensaje = "Película «{$titulo}» añadida correctamente.";

                // Reparto enviado junto con el formulario de creación
                $reparto_nombres    = $_POST['reparto_nombre']    ?? [];
                $reparto_personajes = $_POST['reparto_personaje'] ?? [];
                foreach ($reparto_nombres as $i => $nom) {
                    $nom = trim($nom);
                    if ($nom !== '') {
                        $per = trim($reparto_personajes[$i] ?? '');
                        $pdo->prepare("INSERT INTO reparto (id_pelicula, nombre, personaje) VALUES (?,?,?)")
                            ->execute([$id, $nom, $per]);
                    }
                }
            }

            // Sync géneros
            $pdo->prepare("DELETE FROM peliculas_generos WHERE id_pelicula=?")->execute([$id]);
            foreach ($generos_sel as $gid) {
                $pdo->prepare("INSERT INTO peliculas_generos (id_pelicula, id_genero) VALUES (?,?)")->execute([$id, (int)$gid]);
            }

            // Sync plataformas
            $pdo->prepare("DELETE FROM peliculas_plataformas WHERE id_pelicula=?")->execute([$id]);
            foreach ($plataformas_sel as $pid) {
                $pdo->prepare("INSERT INTO peliculas_plataformas (id_pelicula, id_plataforma) VALUES (?,?)")->execute([$id, (int)$pid]);
            }
        }
    }

    /* ── ELIMINAR PELÍCULA ── */ elseif ($accion === 'eliminar') {
        $id = (int)($_POST['id_pelicula'] ?? 0);
        if ($id > 0) {
            $titulo_el = $pdo->prepare("SELECT titulo FROM peliculas WHERE id_pelicula=?");
            $titulo_el->execute([$id]);
            $nombre_el = $titulo_el->fetchColumn() ?: 'Película';

            // Eliminar dependencias (FK)
            foreach (
                [
                    'peliculas_generos',
                    'peliculas_plataformas',
                    'reparto',
                    'comentarios_peliculas',
                    'usuarios_peliculas',
                    'historial_sugerencias',
                    'listas_peliculas',
                    'usuarios_favoritas_perfil'
                ] as $tabla
            ) {
                $pdo->prepare("DELETE FROM $tabla WHERE id_pelicula=?")->execute([$id]);
            }
            $pdo->prepare("UPDATE proximos_estrenos SET id_pelicula=NULL WHERE id_pelicula=?")->execute([$id]);
            $pdo->prepare("DELETE FROM peliculas_en_cartelera WHERE id_pelicula=?")->execute([$id]);
            $pdo->prepare("DELETE FROM peliculas WHERE id_pelicula=?")->execute([$id]);
            $mensaje = "«{$nombre_el}» eliminada correctamente.";
        }
    }

    /* ── REPARTO: añadir actor ── */ elseif ($accion === 'reparto_add') {
        $id_pel    = (int)($_POST['id_pelicula'] ?? 0);
        $nombre    = trim($_POST['nombre'] ?? '');
        $personaje = trim($_POST['personaje'] ?? '');
        if ($id_pel > 0 && $nombre !== '') {
            $pdo->prepare("INSERT INTO reparto (id_pelicula, nombre, personaje) VALUES (?,?,?)")
                ->execute([$id_pel, $nombre, $personaje]);
            $mensaje = 'Actor añadido.';
        }
    }

    /* ── REPARTO: eliminar actor ── */ elseif ($accion === 'reparto_del') {
        $id_rep = (int)($_POST['id_reparto'] ?? 0);
        if ($id_rep > 0) {
            $pdo->prepare("DELETE FROM reparto WHERE id_reparto=?")->execute([$id_rep]);
            $mensaje = 'Actor eliminado.';
        }
    }

    /* ── REPARTO: editar actor ── */ elseif ($accion === 'reparto_edit') {
        $id_rep    = (int)($_POST['id_reparto'] ?? 0);
        $nombre    = trim($_POST['nombre'] ?? '');
        $personaje = trim($_POST['personaje'] ?? '');
        if ($id_rep > 0 && $nombre !== '') {
            $pdo->prepare("UPDATE reparto SET nombre=?, personaje=? WHERE id_reparto=?")
                ->execute([$nombre, $personaje, $id_rep]);
            $mensaje = 'Actor actualizado.';
        }
    }

    // Redirigir para evitar reenvío de formulario, preservando modo
    $redir = 'peliculas.php';
    $qs    = [];
    if ($mensaje) $qs['ok']    = urlencode($mensaje);
    if ($error)   $qs['err']   = urlencode($error);

    // Si estamos en modo edición, volver al formulario de esa película
    $back_id = (int)($_POST['back_id'] ?? 0);
    if ($back_id > 0) $qs['editar'] = $back_id;

    header('Location: peliculas.php' . ($qs ? '?' . http_build_query($qs) : ''));
    exit;
}

// Recuperar mensajes de redirección
if (!$mensaje && isset($_GET['ok']))  $mensaje = urldecode($_GET['ok']);
if (!$error   && isset($_GET['err'])) $error   = urldecode($_GET['err']);

/* ── MODO: lista / nueva / editar ──────────────────────────── */

$modo     = 'lista'; // lista | form
$pelicula = null;
$reparto  = [];

if (isset($_GET['nueva'])) {
    $modo = 'form';
} elseif (isset($_GET['editar'])) {
    $id_editar = (int)$_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM peliculas WHERE id_pelicula=?");
    $stmt->execute([$id_editar]);
    $pelicula = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($pelicula) {
        $modo = 'form';
        // Géneros y plataformas actuales
        $pelicula['generos']    = $pdo->prepare("SELECT id_genero FROM peliculas_generos WHERE id_pelicula=?");
        $pelicula['generos']->execute([$id_editar]);
        $pelicula['generos']    = array_column($pelicula['generos']->fetchAll(PDO::FETCH_ASSOC), 'id_genero');

        $pelicula['plataformas'] = $pdo->prepare("SELECT id_plataforma FROM peliculas_plataformas WHERE id_pelicula=?");
        $pelicula['plataformas']->execute([$id_editar]);
        $pelicula['plataformas'] = array_column($pelicula['plataformas']->fetchAll(PDO::FETCH_ASSOC), 'id_plataforma');

        // Reparto
        $stmt_rep = $pdo->prepare("SELECT * FROM reparto WHERE id_pelicula=? ORDER BY id_reparto");
        $stmt_rep->execute([$id_editar]);
        $reparto = $stmt_rep->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $error = 'Película no encontrada.';
    }
}

/* ── DATOS PARA FORMULARIO ──────────────────────────────────── */

$todos_generos    = $pdo->query("SELECT * FROM generos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$todas_plataformas = $pdo->query("SELECT * FROM plataformas ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

/* ── LISTA DE PELÍCULAS ─────────────────────────────────────── */

if ($modo === 'lista') {
    $buscar = trim($_GET['buscar'] ?? '');
    $orden  = $_GET['orden'] ?? 'recientes';

    $where  = ['1=1'];
    $params = [];

    if ($buscar !== '') {
        $where[]  = "(titulo LIKE ? OR director LIKE ? OR titulo_original LIKE ?)";
        $params[] = "%$buscar%";
        $params[] = "%$buscar%";
        $params[] = "%$buscar%";
    }

    $order_sql = match ($orden) {
        'titulo'   => 'titulo ASC',
        'anio_asc' => 'anio ASC',
        'anio_desc' => 'anio DESC',
        'imdb'     => 'imdb DESC',
        default    => 'id_pelicula DESC',
    };

    $sql = "SELECT id_pelicula, titulo, titulo_original, anio, director, imdb, poster, duracion
            FROM peliculas
            WHERE " . implode(' AND ', $where) . "
            ORDER BY $order_sql";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $peliculas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total     = count($peliculas);
}

/* ── HELPERS ────────────────────────────────────────────────── */

$colores_avatar = ['#e5533d', '#0F6E56', '#993C1D', '#993556', '#185FA5'];
function color_p($id)
{
    global $colores_avatar;
    return $colores_avatar[$id % count($colores_avatar)];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin · Películas — TakeOne</title>
    <link rel="stylesheet" href="../css/admin.css">

</head>

<body class="admin-layout">

    <?php include '_sidebar.php'; ?>

    <div class="admin-main">

        <!-- TOPBAR -->
        <?php
        if ($modo === 'lista') {
            $topbar_title = 'Películas';
            $topbar_sub   = "$total película" . ($total != 1 ? 's' : '') . " en la base de datos";
        } elseif ($pelicula) {
            $topbar_title = 'Editar película';
            $topbar_sub   = $pelicula['titulo'];
        } else {
            $topbar_title = 'Nueva película';
            $topbar_sub   = 'Añadir a la base de datos';
        }
        include '_topbar.php';
        ?>

        <div class="admin-content">

            <?php if ($mensaje): ?>
                <div class="admin-alert admin-alert--ok"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="admin-alert admin-alert--error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- ═══════════════════════════════════════════════════════ -->
            <?php if ($modo === 'lista'): ?>
                <!-- MODO LISTA ──────────────────────────────────────────── -->

                <div class="admin-panel">
                    <div class="admin-panel__header">
                        <h2 class="admin-panel__title">Listado de películas</h2>
                        <a href="peliculas.php?nueva=1" class="admin-btn admin-btn--primary">+ Nueva película</a>
                    </div>

                    <!-- Filtros -->
                    <div class="admin-filters">
                        <form method="GET" class="admin-filters__form">
                            <input type="text" name="buscar" placeholder="Buscar por título, director..."
                                value="<?= htmlspecialchars($buscar ?? '') ?>" class="admin-filters__input">

                            <select name="orden" class="admin-filters__select" onchange="this.form.submit()">
                                <option value="recientes" <?= ($orden ?? '') === 'recientes' ? 'selected' : '' ?>>Más recientes</option>
                                <option value="titulo" <?= ($orden ?? '') === 'titulo'    ? 'selected' : '' ?>>Título A–Z</option>
                                <option value="anio_desc" <?= ($orden ?? '') === 'anio_desc' ? 'selected' : '' ?>>Año (↓)</option>
                                <option value="anio_asc" <?= ($orden ?? '') === 'anio_asc'  ? 'selected' : '' ?>>Año (↑)</option>
                                <option value="imdb" <?= ($orden ?? '') === 'imdb'      ? 'selected' : '' ?>>IMDB (↓)</option>
                            </select>

                            <button type="submit" class="admin-btn-xs admin-btn-xs--primary">Buscar</button>
                            <?php if (!empty($buscar) || ($orden ?? 'recientes') !== 'recientes'): ?>
                                <a href="peliculas.php" class="admin-btn-xs admin-btn-xs--limpiar">Limpiar</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <?php if (empty($peliculas)): ?>
                        <p class="admin-empty">No se encontraron películas con esos criterios.</p>
                    <?php else: ?>
                        <div class="admin-table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Película</th>
                                        <th>Año</th>
                                        <th>Director</th>
                                        <th>Duración</th>
                                        <th>IMDB</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($peliculas as $p): ?>
                                        <tr>
                                            <td>
                                                <div class="peli-poster-col">
                                                    <div class="peli-poster-sm">
                                                        <?php if ($p['poster']): ?>
                                                            <img src="<?= htmlspecialchars($p['poster']) ?>" alt="">
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <p class="admin-row__name"><?= htmlspecialchars($p['titulo']) ?></p>
                                                        <?php if ($p['titulo_original'] && $p['titulo_original'] !== $p['titulo']): ?>
                                                            <p class="admin-row__sub"><?= htmlspecialchars($p['titulo_original']) ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="admin-table__muted"><?= $p['anio'] ?: '—' ?></td>
                                            <td class="admin-table__muted"><?= htmlspecialchars($p['director'] ?: '—') ?></td>
                                            <td class="admin-table__muted"><?= $p['duracion'] ? $p['duracion'] . ' min' : '—' ?></td>
                                            <td>
                                                <?php if ($p['imdb']): ?>
                                                    <span class="admin-imdb-rating">
                                                        <span class="admin-imdb-rating__star">★</span>
                                                        <span class="admin-imdb-rating__number"><?= number_format($p['imdb'], 1) ?></span>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="admin-table__muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="admin-row__actions">
                                                    <a href="peliculas.php?editar=<?= $p['id_pelicula'] ?>" class="admin-btn-xs admin-btn-xs-editar">Editar</a>
                                                    <form method="POST" style="display:inline;"
                                                        onsubmit="return confirm('¿Eliminar «<?= htmlspecialchars(addslashes($p['titulo'])) ?>»? Esta acción no se puede deshacer.')">
                                                        <input type="hidden" name="accion" value="eliminar">
                                                        <input type="hidden" name="id_pelicula" value="<?= $p['id_pelicula'] ?>">
                                                        <button type="submit" class="admin-btn-xs admin-btn-xs--danger">Eliminar</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <!-- MODO FORMULARIO ─────────────────────────────────────── -->

                <?php
                $f = $pelicula ?? [];
                $gen_act  = $f['generos']    ?? [];
                $plt_act  = $f['plataformas'] ?? [];
                $is_edit  = !empty($f['id_pelicula']);
                ?>

                <div class="admin-panel">
                    <div class="admin-panel__header">
                        <h2 class="admin-panel__title"><?= $is_edit ? 'Editar datos de la película' : 'Añadir nueva película' ?></h2>
                        <a href="peliculas.php" class="admin-panel__link">← Volver al listado</a>
                    </div>

                    <form method="POST" id="form-guardar">
                        <input type="hidden" name="accion" value="guardar">
                        <?php if ($is_edit): ?>
                            <input type="hidden" name="id_pelicula" value="<?= $f['id_pelicula'] ?>">
                        <?php endif; ?>

                        <div class="peli-form">

                            <!-- Título -->
                            <div class="peli-form__group peli-form__group--full">
                                <label for="titulo">Título <span style="color:#a32d2d">*</span></label>
                                <input type="text" id="titulo" name="titulo" required
                                    value="<?= htmlspecialchars($f['titulo'] ?? '') ?>"
                                    placeholder="Título en español">
                            </div>

                            <!-- Título original -->
                            <div class="peli-form__group">
                                <label for="titulo_original">Título original</label>
                                <input type="text" id="titulo_original" name="titulo_original"
                                    value="<?= htmlspecialchars($f['titulo_original'] ?? '') ?>">
                            </div>

                            <!-- Director -->
                            <div class="peli-form__group">
                                <label for="director">Director/a</label>
                                <input type="text" id="director" name="director"
                                    value="<?= htmlspecialchars($f['director'] ?? '') ?>">
                            </div>

                            <!-- Año -->
                            <div class="peli-form__group">
                                <label for="anio">Año</label>
                                <input type="number" id="anio" name="anio" min="1888" max="2099"
                                    value="<?= $f['anio'] ?? '' ?>">
                            </div>

                            <!-- Duración -->
                            <div class="peli-form__group">
                                <label for="duracion">Duración (min)</label>
                                <input type="number" id="duracion" name="duracion" min="1"
                                    value="<?= $f['duracion'] ?? '' ?>">
                            </div>

                            <!-- País -->
                            <div class="peli-form__group">
                                <label for="pais">País</label>
                                <input type="text" id="pais" name="pais"
                                    value="<?= htmlspecialchars($f['pais'] ?? '') ?>">
                            </div>

                            <!-- IMDB -->
                            <div class="peli-form__group">
                                <label for="imdb">Nota IMDB</label>
                                <input type="number" id="imdb" name="imdb" step="0.1" min="0" max="10"
                                    value="<?= $f['imdb'] ?? '' ?>">
                            </div>

                            <!-- Guionistas -->
                            <div class="peli-form__group peli-form__group--full">
                                <label for="guionistas">Guionistas</label>
                                <input type="text" id="guionistas" name="guionistas"
                                    value="<?= htmlspecialchars($f['guionistas'] ?? '') ?>">
                            </div>

                            <!-- Productora -->
                            <div class="peli-form__group peli-form__group--full">
                                <label for="productora">Productora</label>
                                <input type="text" id="productora" name="productora"
                                    value="<?= htmlspecialchars($f['productora'] ?? '') ?>">
                            </div>

                            <!-- Sinopsis -->
                            <div class="peli-form__group peli-form__group--full">
                                <label for="sinopsis">Sinopsis</label>
                                <textarea id="sinopsis" name="sinopsis"><?= htmlspecialchars($f['sinopsis'] ?? '') ?></textarea>
                            </div>

                            <!-- Póster URL + preview -->
                            <div class="peli-form__group peli-form__group--full">
                                <label for="poster">URL del póster (TMDB o externa)</label>
                                <input type="url" id="poster" name="poster"
                                    value="<?= htmlspecialchars($f['poster'] ?? '') ?>"
                                    placeholder="https://image.tmdb.org/t/p/w500/..."
                                    oninput="prevPoster(this.value)">
                                <div class="peli-preview" id="preview-wrap" style="<?= empty($f['poster']) ? 'display:none' : '' ?>">
                                    <div class="peli-preview__img">
                                        <img id="prev-poster" src="<?= htmlspecialchars($f['poster'] ?? '') ?>" alt="Póster">
                                    </div>
                                </div>
                            </div>

                            <!-- Backdrop URL + preview -->
                            <div class="peli-form__group peli-form__group--full">
                                <label for="backdrop">URL del backdrop (imagen de fondo)</label>
                                <input type="url" id="backdrop" name="backdrop"
                                    value="<?= htmlspecialchars($f['backdrop'] ?? '') ?>"
                                    placeholder="https://image.tmdb.org/t/p/original/..."
                                    oninput="prevBackdrop(this.value)">
                                <div class="peli-preview" id="backdrop-wrap" style="<?= empty($f['backdrop']) ? 'display:none' : '' ?>">
                                    <div class="peli-preview__back">
                                        <img id="prev-backdrop" src="<?= htmlspecialchars($f['backdrop'] ?? '') ?>" alt="Backdrop">
                                    </div>
                                </div>
                            </div>

                            <!-- Trailer URL -->
                            <div class="peli-form__group peli-form__group--full">
                                <label for="trailer_url">URL del tráiler (YouTube)</label>
                                <input type="url" id="trailer_url" name="trailer_url"
                                    value="<?= htmlspecialchars($f['trailer_url'] ?? '') ?>">
                            </div>

                            <!-- Géneros -->
                            <div class="peli-form__group peli-form__group--full">
                                <label>Géneros</label>
                                <div class="peli-checks">
                                    <?php foreach ($todos_generos as $g): ?>
                                        <label class="peli-check">
                                            <input type="checkbox" name="generos[]" value="<?= $g['id_genero'] ?>"
                                                <?= in_array($g['id_genero'], $gen_act) ? 'checked' : '' ?>>
                                            <?= htmlspecialchars($g['nombre']) ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Plataformas -->
                            <div class="peli-form__group peli-form__group--full">
                                <label>Plataformas de streaming</label>
                                <div class="peli-checks">
                                    <?php foreach ($todas_plataformas as $pl): ?>
                                        <label class="peli-check">
                                            <input type="checkbox" name="plataformas[]" value="<?= $pl['id_plataforma'] ?>"
                                                <?= in_array($pl['id_plataforma'], $plt_act) ? 'checked' : '' ?>>
                                            <?= htmlspecialchars($pl['nombre']) ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                        </div><!-- /peli-form -->

                    </form>

                    <?php if ($is_edit): ?>
                        <form id="form-eliminar" method="POST"
                            onsubmit="return confirm('¿Eliminar esta película definitivamente?')">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id_pelicula" value="<?= $f['id_pelicula'] ?>">
                        </form>
                    <?php endif; ?>

                    <!-- ── SECCIÓN REPARTO ──────────────────────────── -->
                    <div class="reparto-section">
                        <h3>Reparto</h3>

                        <?php if (!$is_edit): ?>
                            <div id="reparto-nuevo"></div>
                            <button type="button" class="admin-btn-xs admin-btn-xs-anadir" onclick="addReparto()">+ Añadir</button>
                        <?php else: ?>

                            <?php if (empty($reparto)): ?>
                                <p style="font-size:12px; color:#888780; margin-bottom:12px;">Aún no hay actores añadidos.</p>
                            <?php else: ?>
                                <div class="reparto-list" id="reparto-list">
                                    <?php foreach ($reparto as $r): ?>
                                        <div class="reparto-item" id="rep-<?= $r['id_reparto'] ?>">

                                            <!-- Vista normal -->
                                            <div class="reparto-item__info">
                                                <p class="reparto-item__nombre"><?= htmlspecialchars($r['nombre']) ?></p>
                                                <p class="reparto-item__personaje"><?= htmlspecialchars($r['personaje'] ?: '—') ?></p>
                                            </div>

                                            <!-- Vista edición inline -->
                                            <form method="POST" class="reparto-item__edit" id="edit-form-<?= $r['id_reparto'] ?>">
                                                <input type="hidden" name="accion" value="reparto_edit">
                                                <input type="hidden" name="id_reparto" value="<?= $r['id_reparto'] ?>">
                                                <input type="hidden" name="back_id" value="<?= $f['id_pelicula'] ?>">
                                                <input type="text" name="nombre" value="<?= htmlspecialchars($r['nombre']) ?>" placeholder="Actor/actriz" required>
                                                <input type="text" name="personaje" value="<?= htmlspecialchars($r['personaje'] ?? '') ?>" placeholder="Personaje">
                                                <button type="submit" class="admin-btn-xs admin-btn-xs--primary">✓</button>
                                                <button type="button" class="admin-btn-xs" onclick="cancelEdit(<?= $r['id_reparto'] ?>)">✕</button>
                                            </form>

                                            <div class="admin-row__actions">
                                                <button type="button" class="admin-btn-xs"
                                                    onclick="startEdit(<?= $r['id_reparto'] ?>)">Editar</button>
                                                <form method="POST" style="display:inline;"
                                                    onsubmit="return confirm('¿Eliminar a <?= htmlspecialchars(addslashes($r['nombre'])) ?>?')">
                                                    <input type="hidden" name="accion" value="reparto_del">
                                                    <input type="hidden" name="id_reparto" value="<?= $r['id_reparto'] ?>">
                                                    <input type="hidden" name="back_id" value="<?= $f['id_pelicula'] ?>">
                                                    <button type="submit" class="admin-btn-xs admin-btn-xs--danger">✕</button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Añadir nuevo actor -->
                            <form method="POST" class="reparto-add">
                                <input type="hidden" name="accion" value="reparto_add">
                                <input type="hidden" name="id_pelicula" value="<?= $f['id_pelicula'] ?>">
                                <input type="hidden" name="back_id" value="<?= $f['id_pelicula'] ?>">
                                <input type="text" name="nombre" placeholder="Nombre del actor/actriz" required>
                                <input type="text" name="personaje" placeholder="Personaje (opcional)">
                                <button type="submit" class="admin-btn-xs admin-btn-xs-anadir">+ Añadir</button>
                            </form>

                        <?php endif; ?>
                    </div>

                    <!-- ── BOTONES DE ACCIÓN ────────────────────────── -->
                    <div class="peli-actions">
                        <button type="submit" form="form-guardar" class="admin-btn admin-btn--guardar">
                            <?= $is_edit ? 'Guardar cambios' : 'Añadir película' ?>
                        </button>
                        <a href="peliculas.php" class="admin-btn admin-btn--ghost">Cancelar</a>
                        <?php if ($is_edit): ?>
                            <span style="flex:1"></span>
                            <button type="button" class="admin-btn admin-btn--danger admin-btn--delete-movie"
                                onclick="document.getElementById('form-eliminar').submit()">
                                Eliminar película
                            </button>
                        <?php endif; ?>
                    </div>

                </div><!-- /admin-panel -->

            <?php endif; ?>

        </div><!-- /admin-content -->
    </div><!-- /admin-main -->

    <script>
        // ── Preview de imágenes ────────────────────────────────────
        function prevPoster(url) {
            const wrap = document.getElementById('preview-wrap');
            const img = document.getElementById('prev-poster');
            if (url) {
                img.src = url;
                wrap.style.display = 'flex';
            } else {
                wrap.style.display = 'none';
            }
        }

        function prevBackdrop(url) {
            const wrap = document.getElementById('backdrop-wrap');
            const img = document.getElementById('prev-backdrop');
            if (url) {
                img.src = url;
                wrap.style.display = 'flex';
            } else {
                wrap.style.display = 'none';
            }
        }

        // ── Edición inline de reparto ──────────────────────────────
        function startEdit(id) {
            document.getElementById('rep-' + id).classList.add('editing');
            document.querySelector('#edit-form-' + id + ' input[name="nombre"]').focus();
        }

        function cancelEdit(id) {
            document.getElementById('rep-' + id).classList.remove('editing');
        }

        // ── Reparto en formulario de nueva película ─────────────────
        function addReparto() {
            const wrap = document.getElementById('reparto-nuevo');
            const div = document.createElement('div');
            div.className = 'reparto-add';
            div.innerHTML =
                '<input type="text" form="form-guardar" name="reparto_nombre[]" placeholder="Actor/actriz" required>' +
                '<input type="text" form="form-guardar" name="reparto_personaje[]" placeholder="Personaje (opcional)">' +
                '<button type="button" class="admin-btn-xs admin-btn-xs--danger" onclick="this.parentNode.remove()">✕</button>';
            wrap.appendChild(div);
            div.querySelector('input').focus();
        }
    </script>

</body>

</html>