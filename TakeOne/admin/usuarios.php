<?php
require_once '_guard.php';
require_once '../includes/conexion.php';

$mi_id = $_SESSION['usuario']['id'];
$mensaje = '';
$error = '';

/* ── ACCIONES POST ──────────────────────────────────────────── */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id     = (int)($_POST['id_usuario'] ?? 0);

    // Protección: nunca actuar sobre uno mismo
    if ($id === $mi_id) {
        $error = 'No puedes modificar tu propia cuenta desde el panel.';
    } elseif ($id > 0) {

        switch ($accion) {

            case 'banear':
                $stmt = $pdo->prepare("UPDATE usuarios SET activo = 0 WHERE id_usuario = ?");
                $stmt->execute([$id]);
                $mensaje = 'Usuario suspendido correctamente.';
                break;

            case 'activar':
                $stmt = $pdo->prepare("UPDATE usuarios SET activo = 1 WHERE id_usuario = ?");
                $stmt->execute([$id]);
                $mensaje = 'Cuenta reactivada correctamente.';
                break;

            case 'cambiar_rol':
                $nuevo_rol = $_POST['nuevo_rol'] ?? '';
                if (in_array($nuevo_rol, ['admin', 'usuario'])) {
                    $stmt = $pdo->prepare("UPDATE usuarios SET rol = ? WHERE id_usuario = ?");
                    $stmt->execute([$nuevo_rol, $id]);
                    $mensaje = 'Rol actualizado correctamente.';
                }
                break;

            case 'eliminar':
                // Eliminar dependencias primero para respetar FK
                $tablas = [
                    'actividad_usuario'       => 'id_usuario',
                    'comentarios_peliculas'   => 'id_usuario',
                    'historial_sugerencias'   => 'id_usuario',
                    'listas_likes'            => 'id_usuario',
                    'usuarios_peliculas'      => 'id_usuario',
                    'usuarios_favoritas_perfil' => 'id_usuario',
                    'usuarios_generos_favoritos' => 'id_usuario',
                    'grupos_usuarios'         => 'id_usuario',
                    'mensajes_grupo'          => 'id_usuario',
                    'contacto'                => 'id_usuario',
                ];
                foreach ($tablas as $tabla => $campo) {
                    $pdo->prepare("DELETE FROM $tabla WHERE $campo = ?")->execute([$id]);
                }
                // Poner a NULL grupos creados por este usuario
                $pdo->prepare("UPDATE grupos SET id_usuario = NULL WHERE id_usuario = ?")->execute([$id]);
                // Poner a NULL listas creadas por este usuario
                $pdo->prepare("UPDATE listas SET id_usuario = NULL WHERE id_usuario = ?")->execute([$id]);
                // Eliminar usuario
                $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ?")->execute([$id]);
                $mensaje = 'Usuario eliminado correctamente.';
                break;
        }
    }
}

/* ── BÚSQUEDA Y FILTROS ─────────────────────────────────────── */

$ver_id  = (int)($_GET['ver'] ?? 0);
$buscar  = trim($_GET['buscar'] ?? '');
$filtro  = $_GET['filtro'] ?? 'todos';
$orden   = $_GET['orden'] ?? 'recientes';

$where   = ['1=1'];
$params  = [];

if ($buscar !== '') {
    $where[]  = "(username LIKE ? OR email LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}

if ($filtro === 'activos') {
    $where[] = "activo = 1";
}
if ($filtro === 'baneados') {
    $where[] = "activo = 0";
}
if ($filtro === 'usuarios') {
    $where[] = "rol = 'usuario'";
}
if ($filtro === 'admins') {
    $where[] = "rol = 'admin'";
}

$order_sql = match ($orden) {
    'nombre'    => 'username ASC',
    'antiguos'  => 'fecha_registro ASC',
    default     => 'fecha_registro DESC',
};

$sql = "SELECT id_usuario, username, email, rol, activo, avatar, localidad, fecha_registro
        FROM usuarios
        WHERE " . implode(' AND ', $where) . "
        ORDER BY $order_sql";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ── ESTADÍSTICAS RÁPIDAS ───────────────────────────────────── */

$total      = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$activos    = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE activo = 1")->fetchColumn();
$baneados   = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE activo = 0")->fetchColumn();
$admins     = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'")->fetchColumn();

/* ── HELPERS ────────────────────────────────────────────────── */

function tiempo_relativo_u($fecha)
{
    $diff = time() - strtotime($fecha);
    if ($diff < 86400)   return 'hoy';
    if ($diff < 604800)  return 'hace ' . floor($diff / 86400) . ' días';
    return date('d/m/Y', strtotime($fecha));
}

$colores = ['#dc3232', '#2e9b29', '#257fb0', '#0F6E56', '#d156aa'];
function color_u($id, $rol = 'usuario')
{
    global $colores;
    if ($rol === 'admin') return '#e5533d';
    return $colores[$id % count($colores)];
}

function iniciales_u($nombre)
{
    $p = explode(' ', trim($nombre));
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
    <title>Admin · Usuarios — TakeOne</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>

<body class="admin-layout">

    <?php include '_sidebar.php'; ?>

    <div class="admin-main">

        <!-- TOPBAR -->
        <?php
        $topbar_title = 'Usuarios';
        $topbar_sub   = "$total usuario" . ($total != 1 ? 's' : '') . " registrados";
        include '_topbar.php';
        ?>

        <div class="admin-content">

            <?php if ($mensaje): ?>
                <div class="admin-alert admin-alert--ok"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="admin-alert admin-alert--error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- ESTADÍSTICAS -->
            <div class="admin-stats">
                <div class="stat-card">
                    <p class="stat-card__label">Total</p>
                    <p class="stat-card__value"><?= $total ?></p>
                </div>
                <div class="stat-card">
                    <p class="stat-card__label">Activos</p>
                    <p class="stat-card__value"><?= $activos ?></p>
                    <p class="stat-card__delta">cuentas en activo</p>
                </div>
                <div class="stat-card">
                    <p class="stat-card__label">Suspendidos</p>
                    <p class="stat-card__value"><?= $baneados ?></p>
                    <p class="stat-card__delta <?= $baneados > 0 ? 'stat-card__delta--warn' : 'stat-card__delta--neutral' ?>">
                        <?= $baneados > 0 ? 'con acceso bloqueado' : 'ninguno suspendido' ?>
                    </p>
                </div>
                <div class="stat-card">
                    <p class="stat-card__label">Administradores</p>
                    <p class="stat-card__value"><?= $admins ?></p>
                    <p class="stat-card__delta stat-card__delta--neutral">con acceso al panel</p>
                </div>
            </div>

            <!-- FILTROS Y BÚSQUEDA -->
            <div class="admin-panel">
                <div class="admin-panel__header">
                    <h2 class="admin-panel__title">Listado de usuarios</h2>
                </div>
                <div class="admin-filters">
                    <form method="GET" class="admin-filters__form">
                        <input type="text" name="buscar" placeholder="Buscar por nombre o email..."
                            value="<?= htmlspecialchars($buscar) ?>" class="admin-filters__input">

                        <select name="filtro" class="admin-filters__select" onchange="this.form.submit()">
                            <option value="todos" <?= $filtro === 'todos'    ? 'selected' : '' ?>>Todos</option>
                            <option value="activos" <?= $filtro === 'activos'  ? 'selected' : '' ?>>Activos</option>
                            <option value="baneados" <?= $filtro === 'baneados' ? 'selected' : '' ?>>Suspendidos</option>
                            <option value="usuarios" <?= $filtro === 'usuarios' ? 'selected' : '' ?>>Usuarios</option>
                            <option value="admins" <?= $filtro === 'admins'   ? 'selected' : '' ?>>Administradores</option>
                        </select>

                        <select name="orden" class="admin-filters__select" onchange="this.form.submit()">
                            <option value="recientes" <?= $orden === 'recientes' ? 'selected' : '' ?>>Más recientes</option>
                            <option value="antiguos" <?= $orden === 'antiguos'  ? 'selected' : '' ?>>Más antiguos</option>
                        </select>

                        <button type="submit" class="admin-btn-xs admin-btn-xs--primary">Buscar</button>
                        <?php if ($buscar || $filtro !== 'todos' || $orden !== 'recientes'): ?>
                            <a href="usuarios.php" class="admin-btn-xs">Limpiar</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- TABLA -->
                <?php if (empty($usuarios)): ?>
                    <p class="admin-empty">No se encontraron usuarios con esos criterios.</p>
                <?php else: ?>
                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Email</th>
                                    <th>Localidad</th>
                                    <th>Registro</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $u): ?>
                                    <tr id="usuario-<?= $u['id_usuario'] ?>" class="<?= !$u['activo'] ? 'admin-table__row--baneado' : '' ?> <?= $ver_id === $u['id_usuario'] ? 'admin-table__row--highlight' : '' ?>">
                                        <td>
                                            <div class="admin-row" style="padding:0;border:none;gap:8px;">
                                                <div class="admin-row__avatar" style="background:<?= color_u($u['id_usuario'], $u['rol']) ?>">
                                                    <?= iniciales_u($u['username']) ?>
                                                </div>
                                                <div>
                                                    <p class="admin-row__name"><?= htmlspecialchars($u['username']) ?></p>
                                                    <?php if ($u['id_usuario'] == $mi_id): ?>
                                                        <span style="font-size:10px;color:#534AB7;">(tú)</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="admin-table__muted"><?= htmlspecialchars($u['email']) ?></td>
                                        <td class="admin-table__muted"><?= $u['localidad'] ? htmlspecialchars($u['localidad']) : '—' ?></td>
                                        <td class="admin-table__muted"><?= tiempo_relativo_u($u['fecha_registro']) ?></td>
                                        <td>
                                            <?php if ($u['id_usuario'] != $mi_id): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="accion" value="cambiar_rol">
                                                    <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                                                    <select name="nuevo_rol" class="admin-filters__select admin-filters__select--sm"
                                                        onchange="this.form.submit()"
                                                        title="Cambiar rol">
                                                        <option value="usuario" <?= $u['rol'] === 'usuario' ? 'selected' : '' ?>>usuario</option>
                                                        <option value="admin" <?= $u['rol'] === 'admin'   ? 'selected' : '' ?>>admin</option>
                                                    </select>
                                                </form>
                                            <?php else: ?>
                                                <span class="admin-badge admin-badge--purple">admin</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($u['activo']): ?>
                                                <span class="admin-badge admin-badge--green">activo</span>
                                            <?php else: ?>
                                                <span class="admin-badge admin-badge--red">suspendido</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($u['id_usuario'] != $mi_id): ?>
                                                <div class="admin-row__actions">
                                                    <?php if ($u['activo']): ?>
                                                        <form method="POST" style="display:inline;"
                                                            onsubmit="return confirm('¿Suspender la cuenta de <?= htmlspecialchars($u['username']) ?>? No podrá iniciar sesión.')">
                                                            <input type="hidden" name="accion" value="banear">
                                                            <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                                                            <button type="submit" class="admin-btn-xs admin-btn-xs--suspender">Banear</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="accion" value="activar">
                                                            <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                                                            <button type="submit" class="admin-btn-xs admin-btn-xs--primary">Reactivar</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form method="POST" style="display:inline;"
                                                        onsubmit="return confirm('¿Eliminar definitivamente a <?= htmlspecialchars($u['username']) ?>? Esta acción no se puede deshacer.')">
                                                        <input type="hidden" name="accion" value="eliminar">
                                                        <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                                                        <button type="submit" class="admin-btn-xs admin-btn-xs--danger">Eliminar</button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <span class="admin-table__muted" style="font-size:11px;">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script>
        // Scroll automático al usuario si viene del dashboard
        document.addEventListener('DOMContentLoaded', () => {
            const ver = <?= $ver_id ?>;
            if (ver) {
                const fila = document.getElementById('usuario-' + ver);
                if (fila) {
                    fila.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }
        });
    </script>
</body>

</html>