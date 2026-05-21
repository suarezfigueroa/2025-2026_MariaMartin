<?php
require_once '_guard.php';
require_once '../includes/conexion.php';

$mensaje = '';
$error   = '';

/* ── ACCIONES POST ──────────────────────────────────────────── */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    /* ── ELIMINAR ── */
    if ($accion === 'eliminar') {
        $id = (int)$_POST['id_noticia'];
        $pdo->prepare("DELETE FROM noticias WHERE id_noticia = ?")->execute([$id]);
        $mensaje = 'Noticia eliminada correctamente.';
    }

    /* ── GUARDAR (NUEVA O EDICIÓN) ── */
    if ($accion === 'guardar') {
        $id          = (int)($_POST['id_noticia'] ?? 0);
        $titulo      = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $contenido   = trim($_POST['contenido'] ?? '');
        $imagen      = trim($_POST['imagen'] ?? '');
        $autor       = trim($_POST['autor'] ?? 'Redacción TakeOne');
        $fecha       = trim($_POST['fecha'] ?? date('Y-m-d H:i:s'));

        if ($titulo === '') {
            $error = 'El título es obligatorio.';
        } else {
            try {
                if ($id === 0) {
                    $stmt = $pdo->prepare("INSERT INTO noticias
                        (titulo, descripcion, contenido, imagen, autor, fecha)
                        VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$titulo, $descripcion, $contenido, $imagen, $autor, $fecha]);
                    $mensaje = 'Noticia creada correctamente.';
                } else {
                    $stmt = $pdo->prepare("UPDATE noticias SET
                        titulo=?, descripcion=?, contenido=?, imagen=?, autor=?, fecha=?
                        WHERE id_noticia=?");
                    $stmt->execute([$titulo, $descripcion, $contenido, $imagen, $autor, $fecha, $id]);
                    $mensaje = 'Noticia actualizada correctamente.';
                }
            } catch (PDOException $e) {
                $error = 'Error al guardar: ' . $e->getMessage();
            }
        }
    }
}

/* ── MODO EDICIÓN ───────────────────────────────────────────── */

$editando = null;
$editar_id = (int)($_GET['editar'] ?? 0);
if ($editar_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM noticias WHERE id_noticia = ?");
    $stmt->execute([$editar_id]);
    $editando = $stmt->fetch(PDO::FETCH_ASSOC);
}

$mostrar_form = isset($_GET['nueva']) || $editando;

/* ── LISTADO ────────────────────────────────────────────────── */

$buscar = trim($_GET['buscar'] ?? '');
$params = [];
$where  = '1=1';

if ($buscar !== '') {
    $where    = "(titulo LIKE ? OR autor LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}

$stmt = $pdo->prepare("SELECT * FROM noticias WHERE $where ORDER BY fecha DESC");
$stmt->execute($params);
$noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($noticias);

/* ── HELPER: parsear [IMG:ruta] para previsualización ───────── */
function parsear_contenido_preview($texto, $max = 200)
{
    // Eliminar marcadores [IMG:...] para el preview de texto
    $texto = preg_replace('/\[IMG:[^\]]+\]/', '[imagen]', $texto);
    return mb_substr($texto, 0, $max) . (mb_strlen($texto) > $max ? '…' : '');
}

function tiempo_n($fecha)
{
    $diff = time() - strtotime($fecha);
    if ($diff < 3600)   return 'hace ' . floor($diff / 60) . ' min';
    if ($diff < 86400)  return 'hace ' . floor($diff / 3600) . ' h';
    if ($diff < 604800) return 'hace ' . floor($diff / 86400) . ' días';
    return date('d/m/Y', strtotime($fecha));
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin · Noticias — TakeOne</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>

<body class="admin-layout">

    <?php include '_sidebar.php'; ?>

    <div class="admin-main">

        <!-- TOPBAR -->
        <?php
        $topbar_title = $editando ? 'Editando noticia' : (isset($_GET['nueva']) ? 'Nueva noticia' : 'Noticias');
        $topbar_sub   = "$total noticia" . ($total != 1 ? 's' : '') . " publicadas";
        include '_topbar.php';
        ?>

        <div class="admin-content">
            <div style="display:flex; justify-content:flex-end; margin-bottom:8px;">
                <?php if (!$mostrar_form): ?>
                    <a href="noticias.php?nueva=1" class="admin-btn admin-btn--primary">+ Nueva noticia</a>
                <?php else: ?>
                    <a href="noticias.php" class="admin-panel__link">← Volver al listado</a>
                <?php endif; ?>
            </div>
            <?php if ($mensaje): ?>
                <div class="admin-alert admin-alert--ok"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="admin-alert admin-alert--error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($mostrar_form): ?>
                <!-- ══════════════════════════════════════════════════════
             FORMULARIO NUEVA / EDITAR
        ══════════════════════════════════════════════════════ -->
                <div class="admin-panel">
                    <form method="POST" id="formNoticia">
                        <input type="hidden" name="accion" value="guardar">
                        <input type="hidden" name="id_noticia" value="<?= $editando ? $editando['id_noticia'] : 0 ?>">

                        <!-- DATOS PRINCIPALES -->
                        <div class="form-section">
                            <p class="form-section-title">Datos principales</p>

                            <div class="form-group">
                                <label class="form-label">Título *</label>
                                <input type="text" name="titulo" class="form-input" required
                                    value="<?= htmlspecialchars($editando['titulo'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Descripción breve</label>
                                <textarea name="descripcion" class="form-textarea form-textarea--lg"><?= htmlspecialchars($editando['descripcion'] ?? '') ?></textarea>
                                <p class="hint">Aparece en la card de la noticia y como subtítulo en la cabecera de noticias.</p>
                            </div>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Autor</label>
                                    <input type="text" name="autor" class="form-input"
                                        value="<?= htmlspecialchars($editando['autor'] ?? 'Redacción TakeOne') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Fecha de publicación</label>
                                    <input type="datetime-local" name="fecha" class="form-input"
                                        value="<?= $editando ? date('Y-m-d\TH:i', strtotime($editando['fecha'])) : date('Y-m-d\TH:i') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- IMAGEN DE PORTADA -->
                        <div class="form-section">
                            <p class="form-section-title">Imagen de portada</p>
                            <div class="form-group">
                                <label class="form-label">Ruta de la imagen</label>
                                <input type="text" name="imagen" id="inputImagen" class="form-input"
                                    placeholder="img/noticias/nombre-imagen.jpg"
                                    value="<?= htmlspecialchars($editando['imagen'] ?? '') ?>"
                                    oninput="previewPortada()">
                                <p class="hint">Sube la imagen a <code>img/noticias/</code> e introduce aquí la ruta relativa desde la raíz del proyecto. Ej: <code>img/noticias/batman.jpg</code></p>
                                <img id="prevPortada"
                                    src="<?= $editando ? '../' . htmlspecialchars($editando['imagen']) : '' ?>"
                                    onerror="this.style.display='none'" class="img-preview"
                                    style="<?= empty($editando['imagen']) ? 'display:none' : '' ?>">
                            </div>
                        </div>

                        <!-- CONTENIDO -->
                        <div class="form-section">
                            <p class="form-section-title">Contenido del artículo</p>
                            <div class="form-group">
                                <label class="form-label">Texto completo</label>
                                <textarea name="contenido" id="inputContenido" class="form-textarea form-textarea--contenido"
                                    oninput="contarMarcadores()"><?= htmlspecialchars($editando['contenido'] ?? '') ?></textarea>
                                <p class="hint">
                                    Para insertar una imagen dentro del artículo usa el marcador <code>[IMG:img/noticias/nombre.jpg]</code>
                                    con la ruta relativa desde la raíz del proyecto. Escríbelo en una línea propia.
                                </p>
                                <p class="img-markers" id="contadorMarcadores"></p>
                            </div>
                        </div>

                        <!-- BOTONES -->
                        <div class="form-actions">
                            <button type="submit" class="admin-btn admin-btn--guardar">
                                <?= $editando ? 'Guardar cambios' : 'Publicar noticia' ?>
                            </button>
                            <a href="noticias.php" class="admin-btn admin-btn--ghost">Cancelar</a>
                        </div>
                    </form>
                </div>

            <?php else: ?>
                <!-- ══════════════════════════════════════════════════════
             LISTADO DE NOTICIAS
        ══════════════════════════════════════════════════════ -->
                <div class="admin-panel">
                    <div class="admin-panel__header">
                        <h2 class="admin-panel__title">Todas las noticias</h2>
                    </div>
                    <div class="admin-filters">
                        <form method="GET" class="admin-filters__form">
                            <input type="text" name="buscar" class="admin-filters__input"
                                placeholder="Buscar por título o palabra..."
                                value="<?= htmlspecialchars($buscar) ?>">
                            <button type="submit" class="admin-btn-xs admin-btn-xs--primary">Buscar</button>
                            <?php if ($buscar): ?>
                                <a href="noticias.php" class="admin-btn-xs">Limpiar</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <?php if (empty($noticias)): ?>
                        <p class="admin-empty">No se encontraron noticias.</p>
                    <?php else: ?>
                        <div class="noticias-grid">
                            <?php foreach ($noticias as $n): ?>
                                <div class="noticia-card">
                                    <?php if ($n['imagen']): ?>
                                        <img src="../<?= htmlspecialchars($n['imagen']) ?>"
                                            onerror="this.style.background='#f1efe8';this.src=''"
                                            class="noticia-card__img" alt="">
                                    <?php else: ?>
                                        <div class="noticia-card__img"></div>
                                    <?php endif; ?>
                                    <div class="noticia-card__body">
                                        <p class="noticia-card__titulo"><?= htmlspecialchars($n['titulo']) ?></p>
                                        <p class="noticia-card__meta">
                                            <?= htmlspecialchars($n['autor']) ?> · <?= tiempo_n($n['fecha']) ?>
                                        </p>
                                        <p class="noticia-card__desc"><?= htmlspecialchars(parsear_contenido_preview($n['descripcion'])) ?></p>
                                        <div class="noticia-card__actions">
                                            <a href="noticias.php?editar=<?= $n['id_noticia'] ?>" class="admin-btn-xs admin-btn-xs-editar">Editar</a>
                                            <form method="POST" style="display:inline;"
                                                onsubmit="return confirm('¿Eliminar «<?= htmlspecialchars(addslashes($n['titulo'])) ?>»?')">
                                                <input type="hidden" name="accion" value="eliminar">
                                                <input type="hidden" name="id_noticia" value="<?= $n['id_noticia'] ?>">
                                                <button type="submit" class="admin-btn-xs admin-btn-xs--danger">Eliminar</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <script>
        function previewPortada() {
            const ruta = document.getElementById('inputImagen').value;
            const img = document.getElementById('prevPortada');
            if (ruta) {
                img.src = '../' + ruta;
                img.style.display = 'block';
                img.onerror = () => {
                    img.style.display = 'none';
                };
            } else {
                img.style.display = 'none';
            }
        }

        function contarMarcadores() {
            const texto = document.getElementById('inputContenido').value;
            const matches = texto.match(/\[IMG:[^\]]+\]/g);
            const contador = document.getElementById('contadorMarcadores');
            if (matches && matches.length > 0) {
                contador.textContent = matches.length + ' imagen' + (matches.length > 1 ? 'es' : '') + ' insertada' + (matches.length > 1 ? 's' : '') + ' en el contenido';
            } else {
                contador.textContent = '';
            }
        }

        // Ejecutar al cargar si estamos editando
        document.addEventListener('DOMContentLoaded', contarMarcadores);
    </script>

</body>

</html>