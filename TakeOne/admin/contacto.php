<?php
require_once '_guard.php';
require_once '../includes/conexion.php';

$mensaje = '';
$error   = '';

/* ── ACCIONES POST ──────────────────────────────────────────── */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // Eliminar mensaje
    if ($accion === 'eliminar') {
        $id = (int)$_POST['id_contacto'];
        $pdo->prepare("DELETE FROM contacto WHERE id_contacto = ?")->execute([$id]);
        $mensaje = 'Mensaje eliminado correctamente.';
    }

    // Marcar como leído / no leído
    if ($accion === 'marcar_leido') {
        $id    = (int)$_POST['id_contacto'];
        $valor = (int)$_POST['leido'];
        $pdo->prepare("UPDATE contacto SET leido = ? WHERE id_contacto = ?")->execute([$valor, $id]);
    }

    // Responder por email (abre mailto en el cliente, el envío lo hace el gestor de correo del admin)
    // La respuesta se gestiona en el frontend con JS — no necesita acción PHP
}

/* ── VISTA DETALLE ──────────────────────────────────────────── */

$ver_id  = (int)($_GET['ver'] ?? 0);
$detalle = null;

if ($ver_id > 0) {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, u.avatar, u.rol
        FROM contacto c
        LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
        WHERE c.id_contacto = ?
    ");
    $stmt->execute([$ver_id]);
    $detalle = $stmt->fetch(PDO::FETCH_ASSOC);

    // Marcar como leído automáticamente al abrir
    if ($detalle && !$detalle['leido']) {
        $pdo->prepare("UPDATE contacto SET leido = 1 WHERE id_contacto = ?")
            ->execute([$ver_id]);
        $detalle['leido'] = 1;
    }

    // Otros mensajes del mismo email
    if ($detalle) {
        $stmt = $pdo->prepare("
            SELECT id_contacto, motivo, fecha, leido
            FROM contacto
            WHERE email = ? AND id_contacto != ?
            ORDER BY fecha DESC
            LIMIT 5
        ");
        $stmt->execute([$detalle['email'], $ver_id]);
        $otros_mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $otros_mensajes = [];
    }
}

/* ── FILTROS Y LISTADO ──────────────────────────────────────── */

$filtro = $_GET['filtro'] ?? 'todos'; // todos | sin_leer | leidos
$buscar = trim($_GET['buscar'] ?? '');
$where  = ['1=1'];
$params = [];

if ($filtro === 'sin_leer') {
    $where[] = 'leido = 0';
}
if ($filtro === 'leidos') {
    $where[] = 'leido = 1';
}

if ($buscar !== '') {
    $where[]  = "(nombre LIKE ? OR email LIKE ? OR motivo LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}

$stmt = $pdo->prepare("
    SELECT c.*, u.username
    FROM contacto c
    LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
    WHERE " . implode(' AND ', $where) . "
    ORDER BY c.leido ASC, c.fecha DESC
");
$stmt->execute($params);
$mensajes_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$total_mensajes = $pdo->query("SELECT COUNT(*) FROM contacto")->fetchColumn();
$sin_leer       = $pdo->query("SELECT COUNT(*) FROM contacto WHERE leido = 0")->fetchColumn();
$leidos         = $pdo->query("SELECT COUNT(*) FROM contacto WHERE leido = 1")->fetchColumn();
$este_mes       = $pdo->query("SELECT COUNT(*) FROM contacto
    WHERE MONTH(fecha) = MONTH(NOW()) AND YEAR(fecha) = YEAR(NOW())")->fetchColumn();

/* ── HELPERS ────────────────────────────────────────────────── */

function tiempo_ct($fecha)
{
    $diff = time() - strtotime($fecha);
    if ($diff < 3600)   return 'hace ' . floor($diff / 60) . ' min';
    if ($diff < 86400)  return 'hace ' . floor($diff / 3600) . ' h';
    if ($diff < 604800) return 'hace ' . floor($diff / 86400) . ' días';
    return date('d/m/Y H:i', strtotime($fecha));
}

$colores = ['#534AB7', '#0F6E56', '#993C1D', '#993556', '#185FA5', '#854F0B'];
function color_ct($str)
{
    global $colores;
    return $colores[abs(crc32($str ?? '')) % count($colores)];
}
function iniciales_ct($n)
{
    $p = explode(' ', trim($n ?? '?'));
    $i = strtoupper(substr($p[0], 0, 1));
    if (isset($p[1])) $i .= strtoupper(substr($p[1], 0, 1));
    return $i;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin · Contacto — TakeOne</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>

<body class="admin-layout">

    <?php include '_sidebar.php'; ?>

    <div class="admin-main">

        <?php
        $topbar_title = $detalle ? 'Mensaje de ' . htmlspecialchars($detalle['nombre']) : 'Contacto';
        $topbar_sub   = $detalle
            ? date('d/m/Y H:i', strtotime($detalle['fecha']))
            : $total_mensajes . ' mensajes · ' . $sin_leer . ' sin leer';
        include '_topbar.php';
        ?>

        <div class="admin-content">
            <?php if ($detalle): ?>
                <div style="display:flex; justify-content:flex-end; margin-bottom:8px;">
                    <a href="contacto.php" class="admin-panel__link">← Volver</a>
                </div>
            <?php endif; ?>
            <?php if ($mensaje): ?>
                <div class="admin-alert admin-alert--ok"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>

            <?php if ($detalle): ?>
                <!-- ══════════════════════════════════════════════════════
             VISTA DETALLE
        ══════════════════════════════════════════════════════ -->
                <div class="detalle-wrap">

                    <!-- Mensaje principal -->
                    <div class="admin-panel">
                        <div class="mensaje-box">
                            <p class="mensaje-nombre"><?= htmlspecialchars($detalle['nombre']) ?></p>
                            <p class="mensaje-email"><?= htmlspecialchars($detalle['email']) ?></p>
                            <p class="mensaje-fecha">
                                Recibido el <?= date('d/m/Y', strtotime($detalle['fecha'])) ?>
                                a las <?= date('H:i', strtotime($detalle['fecha'])) ?>
                                · <?= $detalle['leido']
                                        ? '<span style="color:#3B6D11;">✓ Leído</span>'
                                        : '<span style="color:#534AB7;">● Sin leer</span>' ?>
                            </p>

                            <p class="mensaje-cuerpo"><?= nl2br(htmlspecialchars($detalle['motivo'])) ?></p>

                            <!-- Área de respuesta -->
                            <div class="respuesta-box">
                                <p class="respuesta-label">Responder por email</p>
                                <textarea class="respuesta-textarea" id="textoRespuesta"
                                    placeholder="Escribe tu respuesta aquí..."></textarea>
                                <p class="respuesta-hint">
                                    Al pulsar "Enviar respuesta" se abrirá tu cliente de correo con el mensaje preparado
                                    para enviarlo a <strong><?= htmlspecialchars($detalle['email']) ?></strong>.
                                </p>
                                <div style="margin-top:10px;">
                                    <button type="button" class="admin-btn admin-btn--primary"
                                        onclick="enviarRespuesta('<?= htmlspecialchars(addslashes($detalle['email'])) ?>', '<?= htmlspecialchars(addslashes($detalle['nombre'])) ?>')">
                                        Enviar respuesta
                                    </button>
                                </div>
                            </div>

                            <!-- Acciones -->
                            <div class="mensaje-acciones">
                                <?php if ($detalle['leido']): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="accion" value="marcar_leido">
                                        <input type="hidden" name="id_contacto" value="<?= $detalle['id_contacto'] ?>">
                                        <input type="hidden" name="leido" value="0">
                                        <button type="submit" class="admin-btn-xs">Marcar como no leído</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="accion" value="marcar_leido">
                                        <input type="hidden" name="id_contacto" value="<?= $detalle['id_contacto'] ?>">
                                        <input type="hidden" name="leido" value="1">
                                        <button type="submit" class="admin-btn-xs">Marcar como leído</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" style="display:inline;"
                                    onsubmit="return confirm('¿Eliminar este mensaje?')">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_contacto" value="<?= $detalle['id_contacto'] ?>">
                                    <button type="submit" class="admin-btn-xs admin-btn-xs--danger">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Panel lateral -->
                    <div class="lateral-panel">

                        <!-- Datos del remitente -->
                        <div class="admin-panel">
                            <div class="admin-panel__header">
                                <h2 class="admin-panel__title">Remitente</h2>
                            </div>
                            <div class="usuario-card">
                                <div class="admin-row__avatar"
                                    style="width:40px;height:40px;font-size:14px;background:<?= color_ct($detalle['email']) ?>">
                                    <?= iniciales_ct($detalle['nombre']) ?>
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <p class="admin-row__name"><?= htmlspecialchars($detalle['nombre']) ?></p>
                                    <p class="admin-row__sub"><?= htmlspecialchars($detalle['email']) ?></p>
                                    <?php if ($detalle['username']): ?>
                                        <p style="font-size:11px;color:#534AB7;margin-top:2px;">
                                            Usuario registrado: <strong><?= htmlspecialchars($detalle['username']) ?></strong>
                                            <?= $detalle['rol'] === 'admin' ? '· <span style="color:#534AB7;">admin</span>' : '' ?>
                                        </p>
                                    <?php else: ?>
                                        <p style="font-size:11px;color:#888780;margin-top:2px;">Sin cuenta registrada</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Historial de mensajes del mismo email -->
                        <?php if (!empty($otros_mensajes)): ?>
                            <div class="admin-panel">
                                <div class="admin-panel__header">
                                    <h2 class="admin-panel__title">Mensajes anteriores</h2>
                                    <span style="font-size:12px;color:#888780;"><?= count($otros_mensajes) ?></span>
                                </div>
                                <?php foreach ($otros_mensajes as $om): ?>
                                    <div class="historial-item">
                                        <p class="historial-texto"><?= htmlspecialchars($om['motivo']) ?></p>
                                        <p class="historial-meta">
                                            <?= tiempo_ct($om['fecha']) ?>
                                            · <?= $om['leido'] ? '<span style="color:#3B6D11;">leído</span>' : '<span style="color:#534AB7;">sin leer</span>' ?>
                                            · <a href="contacto.php?ver=<?= $om['id_contacto'] ?>"
                                                style="color:#534AB7;font-size:11px;">Ver</a>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            <?php else: ?>
                <!-- ══════════════════════════════════════════════════════
             LISTADO
        ══════════════════════════════════════════════════════ -->

                <!-- Stats -->
                <div class="admin-stats">
                    <div class="stat-card">
                        <p class="stat-card__label">Total mensajes</p>
                        <p class="stat-card__value"><?= $total_mensajes ?></p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-card__label">Sin leer</p>
                        <p class="stat-card__value"><?= $sin_leer ?></p>
                        <p class="stat-card__delta <?= $sin_leer > 0 ? 'stat-card__delta--warn' : 'stat-card__delta--neutral' ?>">
                            <?= $sin_leer > 0 ? 'pendientes de revisión' : 'todo al día' ?>
                        </p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-card__label">Leídos</p>
                        <p class="stat-card__value"><?= $leidos ?></p>
                        <p class="stat-card__delta stat-card__delta--neutral">gestionados</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-card__label">Este mes</p>
                        <p class="stat-card__value"><?= $este_mes ?></p>
                        <p class="stat-card__delta stat-card__delta--neutral">recibidos en <?= date('F') ?></p>
                    </div>
                </div>

                <div class="admin-panel">
                    <div class="admin-panel__header">
                        <h2 class="admin-panel__title">Todos los mensajes</h2>
                    </div>

                    <!-- Filtros -->
                    <div class="admin-filters">
                        <form method="GET" class="admin-filters__form">
                            <input type="text" name="buscar" class="admin-filters__input"
                                placeholder="Buscar por nombre, email o contenido..."
                                value="<?= htmlspecialchars($buscar) ?>">
                            <select name="filtro" class="admin-filters__select" onchange="this.form.submit()">
                                <option value="todos" <?= $filtro === 'todos'    ? 'selected' : '' ?>>Todos</option>
                                <option value="sin_leer" <?= $filtro === 'sin_leer' ? 'selected' : '' ?>>Sin leer</option>
                                <option value="leidos" <?= $filtro === 'leidos'   ? 'selected' : '' ?>>Leídos</option>
                            </select>
                            <button type="submit" class="admin-btn-xs admin-btn-xs--primary">Buscar</button>
                            <?php if ($buscar || $filtro !== 'todos'): ?>
                                <a href="contacto.php" class="admin-btn-xs">Limpiar</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <?php if (empty($mensajes_lista)): ?>
                        <p class="admin-empty">No hay mensajes con esos criterios.</p>
                    <?php else: ?>
                        <?php foreach ($mensajes_lista as $m): ?>
                            <a href="contacto.php?ver=<?= $m['id_contacto'] ?>"
                                class="contacto-item <?= !$m['leido'] ? 'contacto-item--sin-leer' : '' ?>"
                                style="text-decoration:none;">
                                <div class="contacto-dot <?= $m['leido'] ? 'contacto-dot--leido' : '' ?>"></div>
                                <div class="admin-row__avatar"
                                    style="width:34px;height:34px;font-size:11px;flex-shrink:0;
                         background:<?= color_ct($m['email']) ?>">
                                    <?= iniciales_ct($m['nombre']) ?>
                                </div>
                                <div class="contacto-item__body">
                                    <p class="contacto-item__nombre <?= $m['leido'] ? 'contacto-item__nombre--leido' : '' ?>">
                                        <?= htmlspecialchars($m['nombre']) ?>
                                        <?php if ($m['username']): ?>
                                            <span style="font-size:11px;color:#534AB7;font-weight:400;">
                                                · <?= htmlspecialchars($m['username']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </p>
                                    <p class="contacto-item__email"><?= htmlspecialchars($m['email']) ?></p>
                                    <p class="contacto-item__motivo"><?= htmlspecialchars($m['motivo']) ?></p>
                                </div>
                                <p class="contacto-item__fecha"><?= tiempo_ct($m['fecha']) ?></p>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <script>
        function enviarRespuesta(email, nombre) {
            const texto = document.getElementById('textoRespuesta').value.trim();

            if (!texto) {
                alert('Escribe tu respuesta antes de enviar.');
                document.getElementById('textoRespuesta').focus();
                return;
            }

            const asunto = encodeURIComponent('Re: Tu mensaje en TakeOne');
            const cuerpo = encodeURIComponent(
                'Hola ' + nombre + ',\n\n' + texto + '\n\nSaludos,\nEl equipo de TakeOne'
            );

            window.location.href = `mailto:${email}?subject=${asunto}&body=${cuerpo}`;
        }
    </script>

</body>

</html>