<?php
session_start();
require_once '../includes/conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];
$id_grupo   = (int)($_POST['id_grupo'] ?? 0);
$mensaje    = trim($_POST['mensaje'] ?? '');

if (!$id_grupo || $mensaje === '') {
    echo json_encode(['ok' => false, 'msg' => 'Datos inválidos']);
    exit;
}

if (mb_strlen($mensaje) > 1000) {
    echo json_encode(['ok' => false, 'msg' => 'El mensaje es demasiado largo']);
    exit;
}

// Verificar que el usuario es miembro del grupo
$check = $pdo->prepare("SELECT 1 FROM grupos_usuarios WHERE id_grupo = :g AND id_usuario = :u");
$check->execute([':g' => $id_grupo, ':u' => $id_usuario]);
if (!$check->fetch()) {
    echo json_encode(['ok' => false, 'msg' => 'No eres miembro de este grupo']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO mensajes_grupo (id_grupo, id_usuario, mensaje)
        VALUES (:id_grupo, :id_usuario, :mensaje)
    ");
    $stmt->execute([
        ':id_grupo'   => $id_grupo,
        ':id_usuario' => $id_usuario,
        ':mensaje'    => $mensaje,
    ]);

    $id_mensaje = $pdo->lastInsertId();

    // Devolver el mensaje completo para mostrarlo sin esperar al polling
    $nuevo = $pdo->prepare("
        SELECT m.id_mensaje, m.mensaje, m.fecha,
            u.username, u.avatar, u.id_usuario
        FROM mensajes_grupo m
        JOIN usuarios u ON m.id_usuario = u.id_usuario
        WHERE m.id_mensaje = :id
    ");
    $nuevo->execute([':id' => $id_mensaje]);

    echo json_encode(['ok' => true, 'mensaje' => $nuevo->fetch(PDO::FETCH_ASSOC)]);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => 'Error al enviar el mensaje']);
}
