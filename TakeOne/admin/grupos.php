<?php
require_once '_guard.php';
require_once '../includes/conexion.php';

$mensaje = '';
$error   = '';

/* ── CATÁLOGOS DE PENALIZACIÓN ──────────────────────────────── */

$motivos_labels = [
    'mala_conducta'        => 'Mala conducta',
    'insultos'             => 'Insultos o lenguaje ofensivo',
    'spam'                 => 'Spam',
    'contenido_inapropiado' => 'Contenido inapropiado',
    'acoso'                => 'Acoso a otros miembros',
    'otro'                 => 'Otro motivo',
];

$duraciones = [
    1   => '1 día',
    4   => '4 días',
    7   => '1 semana',
    14  => '2 semanas',
    30  => '1 mes',
    90  => '3 meses',
    0   => 'Permanente',
];

/* ── ACCIONES POST ──────────────────────────────────────────── */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // Eliminar grupo completo
    if ($accion === 'eliminar_grupo') {
        $id = (int)$_POST['id_grupo'];
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM mensajes_grupo WHERE id_grupo = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM grupos_usuarios WHERE id_grupo = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM grupos WHERE id_grupo = ?")->execute([$id]);
            $pdo->commit();
            $mensaje = 'Grupo eliminado correctamente.';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Error al eliminar el grupo: ' . $e->getMessage();
        }
    }

    // ── Expulsar miembro CON penalización ──────────────────────
    if ($accion === 'expulsar_miembro') {
        $id_grupo      = (int)$_POST['id_grupo'];
        $id_usuario    = (int)$_POST['id_usuario'];
        $motivo        = $_POST['motivo']        ?? 'mala_conducta';
        $duracion_dias = (int)($_POST['duracion_dias'] ?? 1);

        // Validar motivo
        if (!array_key_exists($motivo, $motivos_labels)) {
            $motivo = 'mala_conducta';
        }
        // Validar duración
        if (!array_key_exists($duracion_dias, $duraciones)) {
            $duracion_dias = 1;
        }

        $admin_id = $_SESSION['id_usuario'] ?? null; // ajusta al nombre de tu sesión

        try {
            $pdo->beginTransaction();

            // 1. Eliminar del grupo
            $pdo->prepare("DELETE FROM grupos_usuarios WHERE id_grupo = ? AND id_usuario = ?")
                ->execute([$id_grupo, $id_usuario]);

            // 2. Registrar penalización
            //    Si ya existe una vigente para ese grupo+usuario, la sobreescribimos
            $pdo->prepare("
                DELETE FROM penalizaciones_grupo
                WHERE id_grupo = ? AND id_usuario = ?
            ")->execute([$id_grupo, $id_usuario]);

            $pdo->prepare("
                INSERT INTO penalizaciones_grupo
                    (id_grupo, id_usuario, motivo, duracion_dias, admin_id)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([$id_grupo, $id_usuario, $motivo, $duracion_dias, $admin_id]);

            $pdo->commit();

            $label_motivo   = $motivos_labels[$motivo] ?? $motivo;
            $label_duracion = $duraciones[$duracion_dias] ?? $duracion_dias . ' días';
            $mensaje = "Miembro expulsado. Penalización: «{$label_motivo}» durante {$label_duracion}.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Error al expulsar al miembro: ' . $e->getMessage();
        }
    }

    // ── Levantar penalización manualmente ─────────────────────
    if ($accion === 'levantar_penalizacion') {
        $id_penalizacion = (int)$_POST['id_penalizacion'];
        $pdo->prepare("DELETE FROM penalizaciones_grupo WHERE id_penalizacion = ?")
            ->execute([$id_penalizacion]);
        $mensaje = 'Penalización levantada. El usuario puede volver a unirse al grupo.';
    }

    // Eliminar mensaje (marcarlo como borrado)
    if ($accion === 'eliminar_mensaje') {
        $id_mensaje = (int)$_POST['id_mensaje'];
        $pdo->prepare("UPDATE mensajes_grupo SET estado = 'borrado' WHERE id_mensaje = ?")
            ->execute([$id_mensaje]);
        $mensaje = 'Mensaje eliminado.';
    }

    // Restaurar mensaje
    if ($accion === 'restaurar_mensaje') {
        $id_mensaje = (int)$_POST['id_mensaje'];
        $pdo->prepare("UPDATE mensajes_grupo SET estado = 'activo' WHERE id_mensaje = ?")
            ->execute([$id_mensaje]);
        $mensaje = 'Mensaje restaurado.';
    }
}

/* ── VISTA DETALLE DE GRUPO ─────────────────────────────────── */

$ver_id = (int)($_GET['ver'] ?? 0);
$grupo_detalle  = null;
$miembros       = [];
$mensajes       = [];
$penalizaciones = [];

if ($ver_id > 0) {
    $stmt = $pdo->prepare("
        SELECT g.*, u.username AS creador, gen.nombre AS genero_nombre
        FROM grupos g
        LEFT JOIN usuarios u ON g.id_usuario = u.id_usuario
        LEFT JOIN generos gen ON g.id_genero = gen.id_genero
        WHERE g.id_grupo = ?
    ");
    $stmt->execute([$ver_id]);
    $grupo_detalle = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($grupo_detalle) {
        // Miembros activos
        $stmt = $pdo->prepare("
            SELECT u.id_usuario, u.username, u.avatar, u.email
            FROM grupos_usuarios gu
            JOIN usuarios u ON gu.id_usuario = u.id_usuario
            WHERE gu.id_grupo = ?
            ORDER BY u.username
        ");
        $stmt->execute([$ver_id]);
        $miembros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Penalizaciones vigentes para este grupo
        $stmt = $pdo->prepare("
            SELECT p.id_penalizacion, p.motivo, p.duracion_dias,
                   p.fecha_expulsion, p.fecha_fin,
                   u.username, u.email, u.id_usuario,
                   a.username AS admin_username
            FROM penalizaciones_grupo p
            JOIN usuarios u ON p.id_usuario = u.id_usuario
            LEFT JOIN usuarios a ON p.admin_id = a.id_usuario
            WHERE p.id_grupo = ?
              AND (p.fecha_fin IS NULL OR p.fecha_fin > NOW())
            ORDER BY p.fecha_expulsion DESC
        ");
        $stmt->execute([$ver_id]);
        $penalizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mensajes (todos, incluidos borrados)
        $stmt = $pdo->prepare("
            SELECT mg.id_mensaje, mg.mensaje, mg.fecha, mg.estado,
                   u.username, u.avatar, u.id_usuario
            FROM mensajes_grupo mg
            LEFT JOIN usuarios u ON mg.id_usuario = u.id_usuario
            WHERE mg.id_grupo = ?
            ORDER BY mg.fecha DESC
        ");
        $stmt->execute([$ver_id]);
        $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

/* ── LISTADO DE GRUPOS ──────────────────────────────────────── */

$buscar = trim($_GET['buscar'] ?? '');
$filtro_tipo = $_GET['tipo'] ?? 'todos';
$params = [];
$where  = ['1=1'];

if ($buscar !== '') {
    $where[]  = "(g.nombre LIKE ? OR g.descripcion LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}

if ($filtro_tipo !== 'todos') {
    $where[]  = "g.tipo = ?";
    $params[] = $filtro_tipo;
}

$stmt = $pdo->prepare("
    SELECT g.id_grupo, g.nombre, g.descripcion, g.tipo, g.imagen, g.fecha_creacion,
           u.username AS creador,
           COUNT(DISTINCT gu.id_usuario) AS total_miembros,
           COUNT(DISTINCT CASE WHEN mg.estado = 'activo' THEN mg.id_mensaje END) AS total_mensajes
    FROM grupos g
    LEFT JOIN usuarios u ON g.id_usuario = u.id_usuario
    LEFT JOIN grupos_usuarios gu ON g.id_grupo = gu.id_grupo
    LEFT JOIN mensajes_grupo mg ON g.id_grupo = mg.id_grupo
    WHERE " . implode(' AND ', $where) . "
    GROUP BY g.id_grupo
    ORDER BY g.fecha_creacion DESC
");
$stmt->execute($params);
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total  = count($grupos);

/* ── HELPERS ────────────────────────────────────────────────── */

function tiempo_g($fecha)
{
    $diff = time() - strtotime($fecha);
    if ($diff < 3600)   return 'hace ' . floor($diff / 60) . ' min';
    if ($diff < 86400)  return 'hace ' . floor($diff / 3600) . ' h';
    if ($diff < 604800) return 'hace ' . floor($diff / 86400) . ' días';
    return date('d/m/Y H:i', strtotime($fecha));
}

$colores = ['#534AB7', '#0F6E56', '#993C1D', '#993556', '#185FA5', '#854F0B'];
function color_g($id)
{
    global $colores;
    return $colores[$id % count($colores)];
}
function iniciales_g($n)
{
    $p = explode(' ', trim($n));
    $i = strtoupper(substr($p[0], 0, 1));
    if (isset($p[1])) $i .= strtoupper(substr($p[1], 0, 1));
    return $i;
}

$tipo_labels = [
    'debates'          => 'Debates',
    'recomendaciones'  => 'Recomendaciones',
    'reseñas'          => 'Reseñas',
    'club-cine'        => 'Club de cine',
];
$tipo_colors = [
    'debates'         => 'admin-badge--purple',
    'recomendaciones' => 'admin-badge--green',
    'reseñas'         => 'admin-badge--amber',
    'club-cine'       => 'admin-badge--blue',
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin · Grupos — TakeOne</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>

<body class="admin-layout">

    <?php include '_sidebar.php'; ?>

    <div class="admin-main">

        <?php
        $topbar_title = $grupo_detalle ? 'Grupo: ' . htmlspecialchars($grupo_detalle['nombre']) : 'Grupos';
        $topbar_sub   = $grupo_detalle
            ? count($miembros) . ' miembros · ' . count($mensajes) . ' mensajes'
            : $total . ' grupo' . ($total != 1 ? 's' : '') . ' registrados';
        include '_topbar.php';
        ?>

        <div class="admin-content">
            <?php if ($grupo_detalle): ?>
                <div style="display:flex; justify-content:flex-end; margin-bottom:8px;">
                    <a href="grupos.php" class="admin-panel__link">← Volver al listado</a>
                </div>
            <?php endif; ?>
            <?php if ($mensaje): ?>
                <div class="admin-alert admin-alert--ok"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="admin-alert admin-alert--error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($grupo_detalle): ?>
                <!-- ══════════════════════════════════════════════════════
             VISTA DETALLE DEL GRUPO
        ══════════════════════════════════════════════════════ -->

                <!-- Cabecera del grupo -->
                <div class="admin-panel">
                    <div class="grupo-header">
                        <?php if ($grupo_detalle['imagen']): ?>
                            <img src="../<?= htmlspecialchars($grupo_detalle['imagen']) ?>"
                                onerror="this.style.background='#f1efe8';this.src=''"
                                class="grupo-img" alt="">
                        <?php else: ?>
                            <div class="grupo-img" style="background:<?= color_g($grupo_detalle['id_grupo']) ?>;
                         display:flex;align-items:center;justify-content:center;
                         font-size:20px;color:#fff;font-weight:600;">
                                <?= iniciales_g($grupo_detalle['nombre']) ?>
                            </div>
                        <?php endif; ?>
                        <div style="flex:1;">
                            <p class="grupo-header__title"><?= htmlspecialchars($grupo_detalle['nombre']) ?></p>
                            <p class="grupo-header__meta">
                                <span class="admin-badge <?= $tipo_colors[$grupo_detalle['tipo']] ?? 'admin-badge--gray' ?>">
                                    <?= $tipo_labels[$grupo_detalle['tipo']] ?? $grupo_detalle['tipo'] ?>
                                </span>
                                <?php if ($grupo_detalle['genero_nombre']): ?>
                                    · <?= htmlspecialchars($grupo_detalle['genero_nombre']) ?>
                                <?php endif; ?>
                                · Creado por <strong><?= htmlspecialchars($grupo_detalle['creador'] ?? 'Desconocido') ?></strong>
                                · <?= date('d/m/Y', strtotime($grupo_detalle['fecha_creacion'])) ?>
                            </p>
                            <?php if ($grupo_detalle['descripcion']): ?>
                                <p class="grupo-header__desc"><?= htmlspecialchars($grupo_detalle['descripcion']) ?></p>
                            <?php endif; ?>
                        </div>
                        <form method="POST"
                            onsubmit="return confirm('¿Eliminar el grupo «<?= htmlspecialchars(addslashes($grupo_detalle['nombre'])) ?>» y todos sus mensajes?')">
                            <input type="hidden" name="accion" value="eliminar_grupo">
                            <input type="hidden" name="id_grupo" value="<?= $grupo_detalle['id_grupo'] ?>">
                            <button type="submit" class="admin-btn-xs admin-btn-xs--danger">Eliminar grupo</button>
                        </form>
                    </div>
                </div>

                <div class="detalle-grid">

                    <!-- MIEMBROS -->
                    <div class="admin-panel">
                        <div class="admin-panel__header">
                            <h2 class="admin-panel__title">Miembros</h2>
                            <span style="font-size:12px;color:#888780;"><?= count($miembros) ?></span>
                        </div>
                        <?php if (empty($miembros)): ?>
                            <p class="admin-empty">Sin miembros.</p>
                        <?php else: ?>
                            <div class="miembros-grid">
                                <?php foreach ($miembros as $m): ?>
                                    <div class="miembro-card">
                                        <div class="admin-row__avatar" style="background:<?= color_g($m['id_usuario']) ?>">
                                            <?= iniciales_g($m['username']) ?>
                                        </div>
                                        <div class="miembro-card__info">
                                            <p class="miembro-card__name"><?= htmlspecialchars($m['username']) ?></p>
                                            <p class="miembro-card__email"><?= htmlspecialchars($m['email']) ?></p>
                                        </div>

                                        <!-- Botón que abre el modal de expulsión -->
                                        <button type="button"
                                            class="admin-btn-xs admin-btn-xs--danger"
                                            onclick="abrirModalExpulsion(
                                    <?= $m['id_usuario'] ?>,
                                    '<?= htmlspecialchars(addslashes($m['username'])) ?>',
                                    <?= $grupo_detalle['id_grupo'] ?>
                                )">
                                            Expulsar
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- MENSAJES DEL CHAT -->
                    <div class="admin-panel">
                        <div class="admin-panel__header">
                            <h2 class="admin-panel__title">Mensajes del chat</h2>
                            <span style="font-size:12px;color:#888780;"><?= count($mensajes) ?> total</span>
                        </div>
                        <?php if (empty($mensajes)): ?>
                            <p class="admin-empty">Sin mensajes en este grupo.</p>
                        <?php else: ?>
                            <div class="chat-container">
                                <?php foreach ($mensajes as $msg): ?>
                                    <div class="chat-msg <?= $msg['estado'] === 'borrado' ? 'chat-msg--borrado' : '' ?>">
                                        <div class="chat-msg__avatar" style="background:<?= color_g($msg['id_usuario'] ?? 0) ?>">
                                            <?= iniciales_g($msg['username'] ?? '?') ?>
                                        </div>
                                        <div class="chat-msg__body">
                                            <p class="chat-msg__meta">
                                                <strong><?= htmlspecialchars($msg['username'] ?? 'Usuario eliminado') ?></strong>
                                                · <?= tiempo_g($msg['fecha']) ?>
                                                <?php if ($msg['estado'] === 'borrado'): ?>
                                                    · <span style="color:#a32d2d;">eliminado</span>
                                                <?php endif; ?>
                                            </p>
                                            <p class="chat-msg__texto">
                                                <?= $msg['estado'] === 'borrado'
                                                    ? 'Mensaje eliminado'
                                                    : htmlspecialchars($msg['mensaje']) ?>
                                            </p>
                                            <div class="chat-msg__actions">
                                                <?php if ($msg['estado'] === 'activo'): ?>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="accion" value="eliminar_mensaje">
                                                        <input type="hidden" name="id_mensaje" value="<?= $msg['id_mensaje'] ?>">
                                                        <input type="hidden" name="id_grupo" value="<?= $grupo_detalle['id_grupo'] ?>">
                                                        <button type="submit" class="admin-btn-xs admin-btn-xs--danger"
                                                            onclick="return confirm('¿Eliminar este mensaje?')">Eliminar</button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="accion" value="restaurar_mensaje">
                                                        <input type="hidden" name="id_mensaje" value="<?= $msg['id_mensaje'] ?>">
                                                        <input type="hidden" name="id_grupo" value="<?= $grupo_detalle['id_grupo'] ?>">
                                                        <button type="submit" class="admin-btn-xs">Restaurar</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div><!-- /detalle-grid -->

                <!-- ── PENALIZACIONES VIGENTES ─────────────────────────── -->
                <?php if (!empty($penalizaciones)): ?>
                    <div class="admin-panel" style="margin-top:24px;">
                        <div class="admin-panel__header">
                            <h2 class="admin-panel__title">🔒 Penalizaciones vigentes</h2>
                            <span style="font-size:12px;color:#888780;"><?= count($penalizaciones) ?> activa<?= count($penalizaciones) != 1 ? 's' : '' ?></span>
                        </div>
                        <div class="admin-table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Motivo</th>
                                        <th>Duración</th>
                                        <th>Expulsado</th>
                                        <th>Expira</th>
                                        <th>Por admin</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($penalizaciones as $p): ?>
                                        <tr>
                                            <td>
                                                <div style="display:flex;align-items:center;gap:8px;">
                                                    <div class="admin-row__avatar" style="background:<?= color_g($p['id_usuario']) ?>;width:28px;height:28px;font-size:11px;">
                                                        <?= iniciales_g($p['username']) ?>
                                                    </div>
                                                    <div>
                                                        <p class="admin-row__name" style="font-size:13px;"><?= htmlspecialchars($p['username']) ?></p>
                                                        <p class="admin-row__sub"><?= htmlspecialchars($p['email']) ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="pen-motivo-badge pen-motivo--<?= $p['motivo'] ?>">
                                                    <?= htmlspecialchars($motivos_labels[$p['motivo']] ?? $p['motivo']) ?>
                                                </span>
                                            </td>
                                            <td class="admin-table__muted">
                                                <?= $p['duracion_dias'] == 0 ? '<span style="color:#a32d2d;font-weight:600;">Permanente</span>' : htmlspecialchars($duraciones[$p['duracion_dias']] ?? $p['duracion_dias'] . ' días') ?>
                                            </td>
                                            <td class="admin-table__muted"><?= date('d/m/Y H:i', strtotime($p['fecha_expulsion'])) ?></td>
                                            <td class="admin-table__muted">
                                                <?= $p['fecha_fin']
                                                    ? date('d/m/Y H:i', strtotime($p['fecha_fin']))
                                                    : '<span style="color:#a32d2d;">—</span>' ?>
                                            </td>
                                            <td class="admin-table__muted"><?= htmlspecialchars($p['admin_username'] ?? '—') ?></td>
                                            <td>
                                                <form method="POST"
                                                    onsubmit="return confirm('¿Levantar la penalización de <?= htmlspecialchars(addslashes($p['username'])) ?>? Podrá volver a unirse al grupo.')">
                                                    <input type="hidden" name="accion" value="levantar_penalizacion">
                                                    <input type="hidden" name="id_penalizacion" value="<?= $p['id_penalizacion'] ?>">
                                                    <input type="hidden" name="id_grupo" value="<?= $grupo_detalle['id_grupo'] ?>">
                                                    <button type="submit" class="admin-btn-xs admin-btn-xs-anadir">Permitir acceso</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- ══════════════════════════════════════════════════════
             LISTADO DE GRUPOS
        ══════════════════════════════════════════════════════ -->
                <div class="admin-panel">
                    <div class="admin-panel__header">
                        <h2 class="admin-panel__title">Todos los grupos</h2>
                    </div>
                    <div class="admin-filters">
                        <form method="GET" class="admin-filters__form">
                            <input type="text" name="buscar" class="admin-filters__input"
                                placeholder="Buscar por nombre o descripción..."
                                value="<?= htmlspecialchars($buscar) ?>">
                            <select name="tipo" class="admin-filters__select" onchange="this.form.submit()">
                                <option value="todos" <?= $filtro_tipo === 'todos' ? 'selected' : '' ?>>Todos los tipos</option>
                                <?php foreach ($tipo_labels as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= $filtro_tipo === $val ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="admin-btn-xs admin-btn-xs--primary">Buscar</button>
                            <?php if ($buscar || $filtro_tipo !== 'todos'): ?>
                                <a href="grupos.php" class="admin-btn-xs">Limpiar</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <?php if (empty($grupos)): ?>
                        <p class="admin-empty">No se encontraron grupos.</p>
                    <?php else: ?>
                        <div class="admin-table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th style="width:44px;"></th>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Creador</th>
                                        <th>Miembros</th>
                                        <th>Mensajes</th>
                                        <th>Creado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($grupos as $g): ?>
                                        <tr>
                                            <td>
                                                <?php if ($g['imagen']): ?>
                                                    <img src="../<?= htmlspecialchars($g['imagen']) ?>"
                                                        onerror="this.style.background='#f1efe8';this.src=''"
                                                        class="grupo-img-sm" alt="">
                                                <?php else: ?>
                                                    <div class="grupo-img-sm"
                                                        style="background:<?= color_g($g['id_grupo']) ?>;
                                         display:flex;align-items:center;justify-content:center;
                                         font-size:12px;color:#fff;font-weight:600;">
                                                        <?= iniciales_g($g['nombre']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <p class="admin-row__name"><?= htmlspecialchars($g['nombre']) ?></p>
                                                <?php if ($g['descripcion']): ?>
                                                    <p class="admin-row__sub">
                                                        <?= htmlspecialchars(mb_substr($g['descripcion'], 0, 50)) ?>
                                                        <?= mb_strlen($g['descripcion']) > 50 ? '…' : '' ?>
                                                    </p>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="admin-badge <?= $tipo_colors[$g['tipo']] ?? 'admin-badge--gray' ?>">
                                                    <?= $tipo_labels[$g['tipo']] ?? $g['tipo'] ?>
                                                </span>
                                            </td>
                                            <td class="admin-table__muted"><?= htmlspecialchars($g['creador'] ?? '—') ?></td>
                                            <td class="admin-table__muted" style="text-align:center;"><?= $g['total_miembros'] ?></td>
                                            <td class="admin-table__muted" style="text-align:center;"><?= $g['total_mensajes'] ?></td>
                                            <td class="admin-table__muted"><?= date('d/m/Y', strtotime($g['fecha_creacion'])) ?></td>
                                            <td>
                                                <div class="admin-row__actions">
                                                    <a href="grupos.php?ver=<?= $g['id_grupo'] ?>" class="admin-btn-xs admin-btn-xs-ver">Ver</a>
                                                    <form method="POST" style="display:inline;"
                                                        onsubmit="return confirm('¿Eliminar el grupo «<?= htmlspecialchars(addslashes($g['nombre'])) ?>»?')">
                                                        <input type="hidden" name="accion" value="eliminar_grupo">
                                                        <input type="hidden" name="id_grupo" value="<?= $g['id_grupo'] ?>">
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
            <?php endif; ?>

        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
     MODAL: EXPULSIÓN CON PENALIZACIÓN
══════════════════════════════════════════════════════════════ -->
    <div id="modal-expulsion" class="pen-modal-overlay" style="display:none;" onclick="cerrarModalExpulsion(event)">
        <div class="pen-modal">
            <div class="pen-modal__header">
                <h3 class="pen-modal__title">Expulsar miembro</h3>
                <button type="button" class="pen-modal__close" onclick="cerrarModalExpulsion()">✕</button>
            </div>

            <div class="pen-modal__body">
                <p class="pen-modal__usuario" id="modal-usuario-nombre"></p>

                <form method="POST" id="form-expulsion">
                    <input type="hidden" name="accion" value="expulsar_miembro">
                    <input type="hidden" name="id_grupo" id="modal-id-grupo">
                    <input type="hidden" name="id_usuario" id="modal-id-usuario">

                    <!-- Motivo -->
                    <label class="pen-label">Motivo de la expulsión</label>
                    <select name="motivo" class="pen-select" required>
                        <?php foreach ($motivos_labels as $val => $label): ?>
                            <option value="<?= $val ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Duración -->
                    <label class="pen-label" style="margin-top:16px;">Duración de la penalización</label>
                    <div class="pen-duracion-grid">
                        <?php foreach ($duraciones as $dias => $label): ?>
                            <label class="pen-duracion-opcion">
                                <input type="radio" name="duracion_dias" value="<?= $dias ?>"
                                    <?= $dias === 1 ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($label) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- Info al admin -->
                    <div class="pen-info-box">
                        <span class="pen-info-box__icon">ℹ️</span>
                        <p>El usuario verá un mensaje de penalización si intenta volver a unirse al grupo durante el periodo sancionado.</p>
                    </div>

                    <div class="pen-modal__footer">
                        <button type="button" class="admin-btn admin-btn--ghost" onclick="cerrarModalExpulsion()">Cancelar</button>
                        <button type="submit" class="admin-btn pen-btn-expulsar"
                            onclick="return confirm('¿Confirmar expulsión y aplicar penalización?')">
                            Expulsar y sancionar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function abrirModalExpulsion(idUsuario, username, idGrupo) {
            document.getElementById('modal-id-usuario').value = idUsuario;
            document.getElementById('modal-id-grupo').value = idGrupo;
            document.getElementById('modal-usuario-nombre').innerHTML =
                'Vas a expulsar a <strong>' + username + '</strong>. Elige el motivo y la duración de la sanción.';
            document.getElementById('modal-expulsion').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function cerrarModalExpulsion(e) {
            if (e && e.target !== document.getElementById('modal-expulsion')) return;
            document.getElementById('modal-expulsion').style.display = 'none';
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') cerrarModalExpulsion();
        });
    </script>

</body>

</html>