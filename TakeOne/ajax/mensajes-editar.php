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
$id_mensaje = (int)($_POST['id_mensaje'] ?? 0);
$mensaje    = trim($_POST['mensaje'] ?? '');

if (!$id_mensaje || $mensaje === '') {
    echo json_encode(['ok' => false, 'msg' => 'Datos inválidos']);
    exit;
}

if (mb_strlen($mensaje) > 1000) {
    echo json_encode(['ok' => false, 'msg' => 'El mensaje es demasiado largo']);
    exit;
}

// Verificar que el mensaje pertenece al usuario
$check = $pdo->prepare("SELECT id_usuario FROM mensajes_grupo WHERE id_mensaje = :id AND estado = 'activo'");
$check->execute([':id' => $id_mensaje]);
$msg = $check->fetch(PDO::FETCH_ASSOC);

if (!$msg || $msg['id_usuario'] != $id_usuario) {
    echo json_encode(['ok' => false, 'msg' => 'No tienes permiso para editar este mensaje']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE mensajes_grupo SET mensaje = :mensaje WHERE id_mensaje = :id");
    $stmt->execute([':mensaje' => $mensaje, ':id' => $id_mensaje]);

    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => 'Error al editar el mensaje']);
}
