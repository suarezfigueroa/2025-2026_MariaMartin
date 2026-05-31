<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once '../includes/conexion.php';

// ── Autenticación ─────────────────────────────────────────────────────────────
$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
    exit;
}
$yo = (int) $usuario['id'];

// ── Leer payload ──────────────────────────────────────────────────────────────
$raw    = file_get_contents('php://input');
$data   = json_decode($raw, true) ?? [];
$accion = trim($data['accion'] ?? $_GET['accion'] ?? '');

// ── Helpers ───────────────────────────────────────────────────────────────────
function ok(array $payload = []): void
{
    echo json_encode(array_merge(['ok' => true], $payload));
    exit;
}

function fail(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'msg' => $msg]);
    exit;
}

/**
 * Devuelve avatar HTML (inicial o img) para un usuario dado.
 */
function avatarData(array $u): array
{
    return [
        'id'       => (int)  $u['id_usuario'],
        'username' => $u['username'],
        'avatar'   => $u['avatar'] ?? null,
        'inicial'  => strtoupper(substr($u['username'], 0, 1)),
    ];
}

// ── Router ────────────────────────────────────────────────────────────────────
switch ($accion) {

    // ── BUSCAR USUARIOS ───────────────────────────────────────────────────────
    case 'buscar': {
            $q = trim($data['q'] ?? '');
            if (strlen($q) < 2) fail('Escribe al menos 2 caracteres');

            $stmt = $pdo->prepare("
            SELECT u.id_usuario, u.username, u.avatar,
                   a.estado AS estado_amistad,
                   a.id_emisor
            FROM usuarios u
            LEFT JOIN amistades a
                   ON (a.id_emisor = :yo  AND a.id_receptor = u.id_usuario)
                   OR (a.id_receptor = :yo2 AND a.id_emisor = u.id_usuario)
            WHERE u.id_usuario <> :yo3
              AND u.rol <> 'admin'
              AND u.activo = 1
              AND u.username LIKE :q
            LIMIT 20
        ");
            $stmt->execute([':yo' => $yo, ':yo2' => $yo, ':yo3' => $yo, ':q' => "%$q%"]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $result = array_map(function ($u) use ($yo) {
                $d = avatarData($u);
                $d['estado']    = $u['estado_amistad'];   // null | pendiente | aceptada | rechazada
                $d['soy_emisor'] = ($u['id_emisor'] == $yo);
                return $d;
            }, $rows);

            ok(['usuarios' => $result]);
        }

        // ── ENVIAR SOLICITUD ──────────────────────────────────────────────────────
    case 'enviar': {
            $idDestino = (int)($data['id_usuario'] ?? 0);
            if (!$idDestino || $idDestino === $yo) fail('Usuario no válido');

            // Comprobar que el usuario destino existe y no es admin
            $check = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = ? AND activo = 1 AND rol <> 'admin'");
            $check->execute([$idDestino]);
            if (!$check->fetch()) fail('Usuario no encontrado');

            // Comprobar si ya hay relación en cualquier dirección
            $exist = $pdo->prepare("
            SELECT id_amistad, estado FROM amistades
            WHERE (id_emisor = :a AND id_receptor = :b)
               OR (id_emisor = :b2 AND id_receptor = :a2)
        ");
            $exist->execute([':a' => $yo, ':b' => $idDestino, ':b2' => $idDestino, ':a2' => $yo]);
            $rel = $exist->fetch(PDO::FETCH_ASSOC);

            if ($rel) {
                if ($rel['estado'] === 'aceptada') fail('Ya sois amigos');
                if ($rel['estado'] === 'pendiente') fail('Ya existe una solicitud pendiente');
                // Si fue rechazada, permitir reenvío actualizando
                $upd = $pdo->prepare("
                UPDATE amistades SET estado='pendiente', id_emisor=:e, id_receptor=:r, fecha=NOW()
                WHERE id_amistad = :id
            ");
                $upd->execute([':e' => $yo, ':r' => $idDestino, ':id' => $rel['id_amistad']]);
                ok(['msg' => 'Solicitud enviada']);
            }

            $ins = $pdo->prepare("INSERT INTO amistades (id_emisor, id_receptor) VALUES (?, ?)");
            $ins->execute([$yo, $idDestino]);
            ok(['msg' => 'Solicitud enviada']);
        }

        // ── ACEPTAR SOLICITUD ─────────────────────────────────────────────────────
    case 'aceptar': {
            $idEmisor = (int)($data['id_usuario'] ?? 0);
            if (!$idEmisor) fail('Parámetro inválido');

            $upd = $pdo->prepare("
            UPDATE amistades SET estado='aceptada'
            WHERE id_emisor = :e AND id_receptor = :r AND estado = 'pendiente'
        ");
            $upd->execute([':e' => $idEmisor, ':r' => $yo]);
            if ($upd->rowCount() === 0) fail('Solicitud no encontrada');
            ok(['msg' => 'Amistad aceptada']);
        }

        // ── RECHAZAR SOLICITUD ────────────────────────────────────────────────────
    case 'rechazar': {
            $idEmisor = (int)($data['id_usuario'] ?? 0);
            if (!$idEmisor) fail('Parámetro inválido');

            $upd = $pdo->prepare("
            UPDATE amistades SET estado='rechazada'
            WHERE id_emisor = :e AND id_receptor = :r AND estado = 'pendiente'
        ");
            $upd->execute([':e' => $idEmisor, ':r' => $yo]);
            if ($upd->rowCount() === 0) fail('Solicitud no encontrada');
            ok(['msg' => 'Solicitud rechazada']);
        }

        // ── ELIMINAR AMISTAD ──────────────────────────────────────────────────────
    case 'eliminar': {
            $idAmigo = (int)($data['id_usuario'] ?? 0);
            if (!$idAmigo) fail('Parámetro inválido');

            $del = $pdo->prepare("
            DELETE FROM amistades
            WHERE ((id_emisor = :a AND id_receptor = :b)
                OR (id_emisor = :b2 AND id_receptor = :a2))
              AND estado = 'aceptada'
        ");
            $del->execute([':a' => $yo, ':b' => $idAmigo, ':b2' => $idAmigo, ':a2' => $yo]);
            if ($del->rowCount() === 0) fail('Amistad no encontrada');
            ok(['msg' => 'Amigo eliminado']);
        }

        // ── MIS AMIGOS ────────────────────────────────────────────────────────────
    case 'mis_amigos': {
            $q = trim($data['q'] ?? '');

            $sql = "
            SELECT u.id_usuario, u.username, u.avatar
            FROM amistades a
            JOIN usuarios u ON u.id_usuario = IF(a.id_emisor = :yo, a.id_receptor, a.id_emisor)
            WHERE a.estado = 'aceptada'
              AND (a.id_emisor = :yo2 OR a.id_receptor = :yo3)
              AND u.activo = 1
        ";
            $params = [':yo' => $yo, ':yo2' => $yo, ':yo3' => $yo];

            if ($q !== '') {
                $sql .= " AND u.username LIKE :q";
                $params[':q'] = "%$q%";
            }
            $sql .= " ORDER BY u.username ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            ok(['amigos' => array_map('avatarData', $rows)]);
        }

        // ── SOLICITUDES PENDIENTES ────────────────────────────────────────────────
    case 'pendientes': {
            $stmt = $pdo->prepare("
            SELECT u.id_usuario, u.username, u.avatar, a.fecha
            FROM amistades a
            JOIN usuarios u ON u.id_usuario = a.id_emisor
            WHERE a.id_receptor = :yo AND a.estado = 'pendiente'
            ORDER BY a.fecha DESC
        ");
            $stmt->execute([':yo' => $yo]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $result = array_map(function ($u) {
                $d = avatarData($u);
                $d['fecha'] = $u['fecha'];
                return $d;
            }, $rows);

            ok(['pendientes' => $result, 'total' => count($result)]);
        }

        // ── ESTADO DE RELACIÓN CON UN USUARIO ────────────────────────────────────
    case 'estado': {
            $idOtro = (int)($data['id_usuario'] ?? $_GET['id_usuario'] ?? 0);
            if (!$idOtro) fail('Parámetro inválido');

            $stmt = $pdo->prepare("
            SELECT estado, id_emisor FROM amistades
            WHERE (id_emisor = :a AND id_receptor = :b)
               OR (id_emisor = :b2 AND id_receptor = :a2)
        ");
            $stmt->execute([':a' => $yo, ':b' => $idOtro, ':b2' => $idOtro, ':a2' => $yo]);
            $rel = $stmt->fetch(PDO::FETCH_ASSOC);

            ok([
                'estado'     => $rel ? $rel['estado'] : null,
                'soy_emisor' => $rel ? ((int)$rel['id_emisor'] === $yo) : null,
            ]);
        }

    default:
        fail('Acción desconocida: ' . htmlspecialchars($accion));
}
