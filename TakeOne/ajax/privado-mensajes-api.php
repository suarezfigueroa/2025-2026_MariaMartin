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

function sonAmigos(PDO $pdo, int $a, int $b): bool
{
    $stmt = $pdo->prepare("
        SELECT 1 FROM amistades
        WHERE ((id_emisor = :a AND id_receptor = :b)
            OR (id_emisor = :b2 AND id_receptor = :a2))
          AND estado = 'aceptada'
    ");
    $stmt->execute([':a' => $a, ':b' => $b, ':b2' => $b, ':a2' => $a]);
    return (bool) $stmt->fetch();
}

// ── Router ────────────────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $accion = $_GET['accion'] ?? '';

    // ── OBTENER MENSAJES ──────────────────────────────────────────────────
    if ($accion === 'mensajes') {
        $idOtro = (int) ($_GET['id_otro'] ?? 0);
        $desde  = (int) ($_GET['desde']   ?? 0);

        if (!$idOtro) fail('Parámetro inválido');
        if (!sonAmigos($pdo, $yo, $idOtro)) fail('No sois amigos', 403);

        // Marcar como leídos los mensajes del otro que aún no se han leído
        $pdo->prepare("
            UPDATE mensajes_privados
            SET leido = 1
            WHERE id_emisor = :otro AND id_receptor = :yo AND leido = 0
        ")->execute([':otro' => $idOtro, ':yo' => $yo]);

        $stmt = $pdo->prepare("
            SELECT mp.id_mensaje, mp.id_emisor, mp.mensaje, mp.fecha, mp.leido,
                   u.username, u.avatar
            FROM mensajes_privados mp
            JOIN usuarios u ON u.id_usuario = mp.id_emisor
            WHERE mp.estado = 'activo'
              AND (
                    (mp.id_emisor = :yo  AND mp.id_receptor = :otro)
                 OR (mp.id_emisor = :otro2 AND mp.id_receptor = :yo2)
              )
              AND mp.id_mensaje > :desde
            ORDER BY mp.fecha ASC, mp.id_mensaje ASC
            LIMIT 100
        ");
        $stmt->execute([
            ':yo'    => $yo,
            ':otro'  => $idOtro,
            ':otro2' => $idOtro,
            ':yo2'   => $yo,
            ':desde' => $desde,
        ]);
        $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        ok(['mensajes' => $mensajes]);
    }

    fail('Acción desconocida');
}

if ($method === 'POST') {
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? [];
    $accion = trim($data['accion'] ?? '');

    // ── ENVIAR MENSAJE ────────────────────────────────────────────────────
    if ($accion === 'enviar') {
        $idReceptor = (int) ($data['id_receptor'] ?? 0);
        $mensaje    = trim($data['mensaje'] ?? '');

        if (!$idReceptor || $idReceptor === $yo) fail('Receptor inválido');
        if ($mensaje === '')                      fail('El mensaje no puede estar vacío');
        if (mb_strlen($mensaje) > 1000)           fail('El mensaje es demasiado largo');

        if (!sonAmigos($pdo, $yo, $idReceptor)) fail('No sois amigos', 403);

        $ins = $pdo->prepare("
            INSERT INTO mensajes_privados (id_emisor, id_receptor, mensaje)
            VALUES (:emisor, :receptor, :mensaje)
        ");
        $ins->execute([
            ':emisor'   => $yo,
            ':receptor' => $idReceptor,
            ':mensaje'  => $mensaje,
        ]);

        $idNuevo = (int) $pdo->lastInsertId();

        // Devolver el mensaje completo para renderizarlo al instante
        $stmt = $pdo->prepare("
            SELECT mp.id_mensaje, mp.id_emisor, mp.mensaje, mp.fecha, mp.leido,
                   u.username, u.avatar
            FROM mensajes_privados mp
            JOIN usuarios u ON u.id_usuario = mp.id_emisor
            WHERE mp.id_mensaje = :id
        ");
        $stmt->execute([':id' => $idNuevo]);
        $nuevo = $stmt->fetch(PDO::FETCH_ASSOC);

        ok(['mensaje' => $nuevo]);
    }

    fail('Acción desconocida');
}

fail('Método no permitido', 405);
