<?php
require_once '_guard.php';
require_once '../includes/conexion.php';
date_default_timezone_set('UTC');

$mensaje = '';
$error   = '';

/* ── ACCIONES POST ──────────────────────────────────────────── */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'eliminar') {
        $id     = (int)$_POST['id_comentario'];
        $motivo = $_POST['motivo'] ?? 'otro';
        $motivos_validos = ['spam', 'insultos', 'contenido_inapropiado', 'acoso', 'desvela_trama', 'otro'];
        if (!in_array($motivo, $motivos_validos)) $motivo = 'otro';

        // Guardar registro de moderación antes de borrar
        $row = $pdo->prepare("SELECT id_usuario, id_pelicula, comentario FROM comentarios_peliculas WHERE id_comentario = ?");
        $row->execute([$id]);
        $datos = $row->fetch(PDO::FETCH_ASSOC);
        if ($datos) {
            $pdo->prepare("INSERT INTO moderacion_comentarios (id_comentario, id_usuario, id_pelicula, comentario_texto, motivo) VALUES (?,?,?,?,?)")
                ->execute([$id, $datos['id_usuario'], $datos['id_pelicula'], $datos['comentario'], $motivo]);
        }

        $pdo->prepare("DELETE FROM comentarios_peliculas WHERE id_comentario = ?")
            ->execute([$id]);
        $mensaje = 'Comentario eliminado correctamente.';
    }

    if ($accion === 'eliminar_lote') {
        $ids = $_POST['ids'] ?? [];
        $ids = array_filter(array_map('intval', $ids));
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM comentarios_peliculas WHERE id_comentario IN ($placeholders)")
                ->execute($ids);
            $mensaje = count($ids) . ' comentario' . (count($ids) > 1 ? 's eliminados' : ' eliminado') . ' correctamente.';
        }
    }
}

/* ── VISTA DETALLE ──────────────────────────────────────────── */

$ver_id      = (int)($_GET['ver'] ?? 0);
$comentario_detalle = null;

if ($ver_id > 0) {
    $stmt = $pdo->prepare("
        SELECT cp.*, u.username, u.email, u.avatar,
               p.titulo AS titulo_pelicula, p.poster, p.anio, p.id_pelicula
        FROM comentarios_peliculas cp
        LEFT JOIN usuarios u ON cp.id_usuario = u.id_usuario
        LEFT JOIN peliculas p ON cp.id_pelicula = p.id_pelicula
        WHERE cp.id_comentario = ?
    ");
    $stmt->execute([$ver_id]);
    $comentario_detalle = $stmt->fetch(PDO::FETCH_ASSOC);

    // Otros comentarios del mismo usuario en la misma película
    if ($comentario_detalle && $comentario_detalle['id_usuario']) {
        $stmt = $pdo->prepare("
            SELECT cp.id_comentario, cp.comentario, cp.fecha, p.titulo AS titulo_pelicula
            FROM comentarios_peliculas cp
            LEFT JOIN peliculas p ON cp.id_pelicula = p.id_pelicula
            WHERE cp.id_usuario = ? AND cp.id_comentario != ?
            ORDER BY cp.fecha DESC
            LIMIT 5
        ");
        $stmt->execute([$comentario_detalle['id_usuario'], $ver_id]);
        $otros_comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $otros_comentarios = [];
    }
}

/* ── FILTROS Y LISTADO ──────────────────────────────────────── */

$buscar_usuario  = trim($_GET['usuario'] ?? '');
$buscar_pelicula = trim($_GET['pelicula'] ?? '');
$fecha_desde     = trim($_GET['desde'] ?? '');
$fecha_hasta     = trim($_GET['hasta'] ?? '');
$orden           = $_GET['orden'] ?? 'recientes';

$where  = ['1=1'];
$params = [];

if ($buscar_usuario !== '') {
    $where[]  = "u.username LIKE ?";
    $params[] = "%$buscar_usuario%";
}
if ($buscar_pelicula !== '') {
    $where[]  = "p.titulo LIKE ?";
    $params[] = "%$buscar_pelicula%";
}
if ($fecha_desde !== '') {
    $where[]  = "cp.fecha >= ?";
    $params[] = $fecha_desde . ' 00:00:00';
}
if ($fecha_hasta !== '') {
    $where[]  = "cp.fecha <= ?";
    $params[] = $fecha_hasta . ' 23:59:59';
}

$order_sql = match ($orden) {
    'antiguos' => 'cp.fecha ASC',
    'pelicula' => 'p.titulo ASC',
    'usuario'  => 'u.username ASC',
    default    => 'cp.fecha DESC',
};

$stmt = $pdo->prepare("
    SELECT cp.id_comentario, cp.comentario, cp.fecha,
           u.id_usuario, u.username, u.email, u.avatar,
           p.id_pelicula, p.titulo AS titulo_pelicula, p.poster, p.anio
    FROM comentarios_peliculas cp
    LEFT JOIN usuarios u ON cp.id_usuario = u.id_usuario
    LEFT JOIN peliculas p ON cp.id_pelicula = p.id_pelicula
    WHERE " . implode(' AND ', $where) . "
    ORDER BY $order_sql
");
$stmt->execute($params);
$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($comentarios);

/* ── ESTADÍSTICAS ───────────────────────────────────────────── */

$total_global  = $pdo->query("SELECT COUNT(*) FROM comentarios_peliculas")->fetchColumn();
$este_mes      = $pdo->query("SELECT COUNT(*) FROM comentarios_peliculas
    WHERE MONTH(fecha) = MONTH(NOW()) AND YEAR(fecha) = YEAR(NOW())")->fetchColumn();
$top_pelicula  = $pdo->query("
    SELECT p.titulo, COUNT(*) AS total
    FROM comentarios_peliculas cp
    JOIN peliculas p ON cp.id_pelicula = p.id_pelicula
    GROUP BY cp.id_pelicula
    ORDER BY total DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

/* ── HELPERS ────────────────────────────────────────────────── */

function tiempo_c($fecha)
{
    $diff = time() - strtotime($fecha);
    if ($diff < 3600)   return 'hace ' . floor($diff / 60) . ' min';
    if ($diff < 86400)  return 'hace ' . floor($diff / 3600) . ' h';
    if ($diff < 604800) return 'hace ' . floor($diff / 86400) . ' días';
    return date('d/m/Y H:i', strtotime($fecha));
}

$colores = ['#534AB7', '#0F6E56', '#993C1D', '#993556', '#185FA5', '#854F0B'];
function color_c($id)
{
    global $colores;
    return $colores[($id ?? 0) % count($colores)];
}
function iniciales_c($n)
{
    $p = explode(' ', trim($n ?? '?'));
    $i = strtoupper(substr($p[0], 0, 1));
    if (isset($p[1])) $i .= strtoupper(substr($p[1], 0, 1));
    return $i;
}

$hay_filtros = $buscar_usuario || $buscar_pelicula || $fecha_desde || $fecha_hasta || $orden !== 'recientes';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin · Comentarios — TakeOne</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>

<body class="admin-layout">

    <?php include '_sidebar.php'; ?>

    <div class="admin-main">

        <?php
        $topbar_title = $comentario_detalle ? 'Detalle del comentario' : 'Comentarios';
        $topbar_sub   = $comentario_detalle
            ? 'por ' . htmlspecialchars($comentario_detalle['username'] ?? 'Anónimo')
            : $total_global . ' comentarios en total · ' . $este_mes . ' este mes';
        include '_topbar.php';
        ?>

        <div class="admin-content">
            <?php if ($comentario_detalle): ?>
                <div style="display:flex; justify-content:flex-end; margin-bottom:8px;">
                    <a href="comentarios.php" class="admin-btn admin-btn--primary">← Volver al listado</a>
                </div>
            <?php endif; ?>
            <?php if ($mensaje): ?>
                <div class="admin-alert admin-alert--ok"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="admin-alert admin-alert--error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($comentario_detalle): ?>
                <!-- ══════════════════════════════════════════════════════
             VISTA DETALLE
        ══════════════════════════════════════════════════════ -->

                <div class="detalle-wrap">

                    <!-- Columna izquierda: comentario + película -->
                    <div style="display:flex;flex-direction:column;gap:16px;">

                        <div class="admin-panel">
                            <div class="admin-panel__header">
                                <h2 class="admin-panel__title">Comentario</h2>
                                <span style="font-size:11px;color:#888780;">#<?= $comentario_detalle['id_comentario'] ?></span>
                            </div>
                            <div class="comentario-box">
                                <blockquote class="comentario-texto">
                                    <?= htmlspecialchars($comentario_detalle['comentario']) ?>
                                </blockquote>
                                <p class="comentario-fecha">
                                    Publicado el <?= date('d/m/Y', strtotime($comentario_detalle['fecha'])) ?>
                                    a las <?= date('H:i', strtotime($comentario_detalle['fecha'])) ?>
                                </p>
                                <div style="margin-top:14px;">
                                    <button type="button" class="admin-btn-xs admin-btn-xs--danger"
                                        onclick="abrirModalEliminar(<?= $comentario_detalle['id_comentario'] ?>)">
                                        Moderar y eliminar comentario
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="admin-panel">
                            <div class="admin-panel__header">
                                <h2 class="admin-panel__title">Película</h2>
                            </div>
                            <div class="detalle-pelicula">
                                <?php if ($comentario_detalle['poster']): ?>
                                    <img src="<?= htmlspecialchars($comentario_detalle['poster']) ?>"
                                        class="detalle-poster"
                                        onerror="this.style.background='#f1efe8';this.src=''" alt="">
                                <?php else: ?>
                                    <div class="detalle-poster"></div>
                                <?php endif; ?>
                                <div>
                                    <p class="admin-row__name"><?= htmlspecialchars($comentario_detalle['titulo_pelicula'] ?? 'Película eliminada') ?></p>
                                    <?php if ($comentario_detalle['anio']): ?>
                                        <p class="admin-row__sub"><?= $comentario_detalle['anio'] ?></p>
                                    <?php endif; ?>
                                    <?php if ($comentario_detalle['id_pelicula']): ?>
                                        <a href="peliculas.php?editar=<?= $comentario_detalle['id_pelicula'] ?>"
                                            class="admin-btn-xs admin-btn-xs-editar" style="margin-top:8px;display:inline-flex;">
                                            Ver en catálogo
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Columna derecha: usuario + historial -->
                    <div style="display:flex;flex-direction:column;gap:16px;">

                        <div class="admin-panel">
                            <div class="admin-panel__header">
                                <h2 class="admin-panel__title">Usuario</h2>
                            </div>
                            <?php if ($comentario_detalle['id_usuario']): ?>
                                <div class="detalle-usuario">
                                    <div class="admin-row__avatar" style="width:40px;height:40px;font-size:14px;
                             background:<?= color_c($comentario_detalle['id_usuario']) ?>">
                                        <?= iniciales_c($comentario_detalle['username']) ?>
                                    </div>
                                    <div>
                                        <p class="admin-row__name"><?= htmlspecialchars($comentario_detalle['username']) ?></p>
                                        <p class="admin-row__sub"><?= htmlspecialchars($comentario_detalle['email']) ?></p>
                                    </div>
                                    <a href="usuarios.php" class="admin-btn-xs admin-btn-xs-editar" style="margin-left:auto;">
                                        Ver usuario
                                    </a>
                                </div>
                            <?php else: ?>
                                <p style="padding:14px 16px;font-size:13px;color:#888780;">
                                    Comentario anónimo (usuario eliminado o no registrado)
                                </p>
                            <?php endif; ?>
                        </div>

                        <?php if ($comentario_detalle['id_usuario']): ?>
                            <div class="admin-panel">
                                <div class="admin-panel__header">
                                    <h2 class="admin-panel__title">Otros comentarios de este usuario</h2>
                                    <span style="font-size:12px;color:#888780;"><?= count($otros_comentarios) ?></span>
                                </div>
                                <?php if (empty($otros_comentarios)): ?>
                                    <p class="admin-empty">No tiene más comentarios.</p>
                                <?php else: ?>
                                    <?php foreach ($otros_comentarios as $oc): ?>
                                        <div class="historial-item">
                                            <p class="historial-texto">"<?= htmlspecialchars(mb_substr($oc['comentario'], 0, 80)) ?><?= mb_strlen($oc['comentario']) > 80 ? '…' : '' ?>"</p>
                                            <p class="historial-meta">
                                                <?= htmlspecialchars($oc['titulo_pelicula'] ?? 'Película eliminada') ?>
                                                · <?= tiempo_c($oc['fecha']) ?>
                                                · <a href="comentarios.php?ver=<?= $oc['id_comentario'] ?>"
                                                    style="color:#534AB7;font-size:11px;">Ver</a>
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            <?php else: ?>
                <!-- ══════════════════════════════════════════════════════
             LISTADO CON FILTROS
        ══════════════════════════════════════════════════════ -->

                <!-- Stats rápidas -->
                <div class="admin-stats">
                    <div class="stat-card">
                        <p class="stat-card__label">Total comentarios</p>
                        <p class="stat-card__value"><?= $total_global ?></p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-card__label">Este mes</p>
                        <p class="stat-card__value"><?= $este_mes ?></p>
                        <p class="stat-card__delta">publicados en <?= date('F') ?></p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-card__label">Película más comentada</p>
                        <p class="stat-card__value"><?= $top_pelicula ? $top_pelicula['total'] : '—' ?></p>
                        <p class="stat-card__delta stat-card__delta--neutral"><?= $top_pelicula ? htmlspecialchars(mb_strimwidth($top_pelicula['titulo'], 0, 28, '…')) : 'Sin datos' ?></p>
                    </div>
                </div>

                <div class="admin-panel">
                    <div class="admin-panel__header">
                        <h2 class="admin-panel__title">Todos los comentarios</h2>
                    </div>

                    <!-- Filtros -->
                    <div class="admin-filters">
                        <form method="GET" class="admin-filters__form" style="flex-wrap:wrap;gap:8px;">
                            <input type="text" name="usuario" class="admin-filters__input"
                                style="min-width:140px;flex:1;"
                                placeholder="Filtrar por usuario..."
                                value="<?= htmlspecialchars($buscar_usuario) ?>">
                            <input type="text" name="pelicula" class="admin-filters__input"
                                style="min-width:140px;flex:1;"
                                placeholder="Filtrar por película..."
                                value="<?= htmlspecialchars($buscar_pelicula) ?>">
                            <div class="form-group-sm">
                                <label class="form-label-sm" style="font-size:10px;font-weight:600;color:#888780;text-transform:uppercase;letter-spacing:0.04em;">Desde</label>
                                <input type="date" name="desde" class="admin-filters__input"
                                    style="min-width:130px;flex:0;height:32px;"
                                    value="<?= htmlspecialchars($fecha_desde) ?>">
                            </div>
                            <div class="form-group-sm">
                                <label class="form-label-sm" style="font-size:10px;font-weight:600;color:#888780;text-transform:uppercase;letter-spacing:0.04em;">Hasta</label>
                                <input type="date" name="hasta" class="admin-filters__input"
                                    style="min-width:130px;flex:0;height:32px;"
                                    value="<?= htmlspecialchars($fecha_hasta) ?>">
                            </div>
                            <select name="orden" class="admin-filters__select" onchange="this.form.submit()">
                                <option value="recientes" <?= $orden === 'recientes' ? 'selected' : '' ?>>Más recientes</option>
                                <option value="antiguos" <?= $orden === 'antiguos'  ? 'selected' : '' ?>>Más antiguos</option>
                                <option value="usuario" <?= $orden === 'usuario'   ? 'selected' : '' ?>>Por usuario</option>
                            </select>
                            <button type="submit" class="admin-btn-xs admin-btn-xs--primary">Filtrar</button>
                            <?php if ($hay_filtros): ?>
                                <a href="comentarios.php" class="admin-btn-xs">Limpiar</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- Barra de acciones en lote -->
                    <form method="POST" id="formLote">
                        <input type="hidden" name="accion" value="eliminar_lote">

                        <div class="lote-bar" id="loteBar">
                            <span class="lote-count" id="loteCount">0 seleccionados</span>
                            <button type="submit" class="admin-btn-xs admin-btn-xs--danger"
                                onclick="return confirm('¿Eliminar los comentarios seleccionados?')">
                                Eliminar seleccionados
                            </button>
                            <button type="button" class="admin-btn-xs" onclick="deseleccionarTodos()">
                                Cancelar
                            </button>
                        </div>

                        <?php if (empty($comentarios)): ?>
                            <p class="admin-empty">No se encontraron comentarios con esos filtros.</p>
                        <?php else: ?>
                            <div class="admin-table-wrap">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th class="check-col">
                                                <input type="checkbox" id="checkTodos" onchange="toggleTodos(this)">
                                            </th>
                                            <th>Usuario</th>
                                            <th>Comentario</th>
                                            <th>Película</th>
                                            <th>Fecha</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($comentarios as $c): ?>
                                            <tr>
                                                <td class="check-col">
                                                    <input type="checkbox" name="ids[]"
                                                        value="<?= $c['id_comentario'] ?>"
                                                        class="check-item" onchange="actualizarLote()">
                                                </td>
                                                <td>
                                                    <div style="display:flex;align-items:center;gap:7px;">
                                                        <div class="admin-row__avatar"
                                                            style="background:<?= color_c($c['id_usuario'] ?? 0) ?>">
                                                            <?= iniciales_c($c['username'] ?? 'Anónimo') ?>
                                                        </div>
                                                        <div>
                                                            <p class="admin-row__name">
                                                                <?= htmlspecialchars($c['username'] ?? 'Anónimo') ?>
                                                            </p>
                                                            <?php if ($c['email']): ?>
                                                                <p class="admin-row__sub"><?= htmlspecialchars($c['email']) ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <p class="comentario-preview">
                                                        "<?= htmlspecialchars($c['comentario']) ?>"
                                                    </p>
                                                </td>
                                                <td>
                                                    <div style="display:flex;align-items:center;gap:7px;">
                                                        <?php if ($c['poster']): ?>
                                                            <img src="<?= htmlspecialchars($c['poster']) ?>"
                                                                style="width:22px;height:32px;object-fit:cover;border-radius:2px;flex-shrink:0;"
                                                                onerror="this.style.display='none'" alt="">
                                                        <?php endif; ?>
                                                        <p class="admin-row__name" style="font-size:12px;">
                                                            <?= htmlspecialchars($c['titulo_pelicula'] ?? '—') ?>
                                                            <?php if ($c['anio']): ?>
                                                                <span style="font-weight:400;color:#888780;">(<?= $c['anio'] ?>)</span>
                                                            <?php endif; ?>
                                                        </p>
                                                    </div>
                                                </td>
                                                <td class="admin-table__muted"><?= tiempo_c($c['fecha']) ?></td>
                                                <td>
                                                    <div class="admin-row__actions">
                                                        <a href="comentarios.php?ver=<?= $c['id_comentario'] ?>"
                                                            class="admin-btn-xs admin-btn-xs-editar">Ver</a>
                                                        <button type="button" class="admin-btn-xs admin-btn-xs--danger"
                                                            onclick="eliminarUno(<?= $c['id_comentario'] ?>)">
                                                            Eliminar
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- ── MODAL ELIMINAR COMENTARIO ─────────────────────────────── -->
    <div id="modal-eliminar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9000;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:14px;width:420px;max-width:95vw;box-shadow:0 20px 60px rgba(0,0,0,.25);animation:pen-fadein .18s ease;">
            <div class="pen-modal__header" style="padding:20px 24px 0;">
                <h3 class="pen-modal__title">Moderar y eliminar comentario</h3>
                <button class="pen-modal__close" onclick="cerrarModalEliminar()">✕</button>
            </div>
            <div class="pen-modal__body">
                <p style="font-size:13px;color:#5f5e5a;margin-bottom:16px;">
                    Selecciona el motivo de la eliminación. Quedará registrado en el historial de moderación.
                </p>
                <label class="pen-label">Motivo</label>
                <select id="modal-motivo" class="pen-select">
                    <option value="spam">Spam o publicidad</option>
                    <option value="insultos">Insultos o lenguaje ofensivo</option>
                    <option value="contenido_inapropiado">Contenido inapropiado</option>
                    <option value="acoso">Acoso a otros usuarios</option>
                    <option value="desvela_trama">Desvela la trama sin aviso</option>
                    <option value="otro">Otro motivo</option>
                </select>
                <input type="hidden" id="modal-id-comentario" value="">
                <div class="pen-modal__footer">
                    <button type="button" class="admin-btn admin-btn--ghost" onclick="cerrarModalEliminar()">Cancelar</button>
                    <button type="button" class="admin-btn admin-btn--danger" onclick="confirmarEliminar()">Eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Selección en lote
        function actualizarLote() {
            const checks = document.querySelectorAll('.check-item:checked');
            const loteBar = document.getElementById('loteBar');
            const count = document.getElementById('loteCount');
            const n = checks.length;
            count.textContent = n + ' seleccionado' + (n !== 1 ? 's' : '');
            loteBar.classList.toggle('visible', n > 0);
        }

        function toggleTodos(master) {
            document.querySelectorAll('.check-item').forEach(c => c.checked = master.checked);
            actualizarLote();
        }

        function deseleccionarTodos() {
            document.querySelectorAll('.check-item, #checkTodos').forEach(c => c.checked = false);
            actualizarLote();
        }

        // Modal de eliminación
        function eliminarUno(id) {
            abrirModalEliminar(id);
        }

        function abrirModalEliminar(id) {
            document.getElementById('modal-id-comentario').value = id;
            document.getElementById('modal-motivo').value = 'spam';
            document.getElementById('modal-eliminar').style.display = 'flex';
        }

        function cerrarModalEliminar() {
            document.getElementById('modal-eliminar').style.display = 'none';
        }

        function confirmarEliminar() {
            const id = document.getElementById('modal-id-comentario').value;
            const motivo = document.getElementById('modal-motivo').value;

            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
        <input type="hidden" name="accion" value="eliminar">
        <input type="hidden" name="id_comentario" value="${id}">
        <input type="hidden" name="motivo" value="${motivo}">
    `;
            document.body.appendChild(form);
            form.submit();
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('modal-eliminar').addEventListener('click', function(e) {
            if (e.target === this) cerrarModalEliminar();
        });
    </script>

</body>

</html>