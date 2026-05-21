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

$id_usuario_sesion = $_SESSION['usuario']['id'];
$id_grupo          = (int)($_POST['id_grupo'] ?? 0);
$id_expulsado      = (int)($_POST['id_usuario'] ?? 0);

if (!$id_grupo || !$id_expulsado) {
    echo json_encode(['ok' => false, 'msg' => 'Datos inválidos']);
    exit;
}

// Verificar que el que expulsa es el creador del grupo
$stmt = $pdo->prepare("SELECT id_usuario FROM grupos WHERE id_grupo = ?");
$stmt->execute([$id_grupo]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    echo json_encode(['ok' => false, 'msg' => 'Grupo no encontrado']);
    exit;
}

if ($grupo['id_usuario'] != $id_usuario_sesion) {
    echo json_encode(['ok' => false, 'msg' => 'Solo el creador del grupo puede expulsar miembros']);
    exit;
}

// No puede expulsarse a sí mismo
if ($id_expulsado == $id_usuario_sesion) {
    echo json_encode(['ok' => false, 'msg' => 'No puedes expulsarte a ti mismo']);
    exit;
}

// Verificar que el usuario a expulsar es miembro
$stmt = $pdo->prepare("SELECT 1 FROM grupos_usuarios WHERE id_grupo = ? AND id_usuario = ?");
$stmt->execute([$id_grupo, $id_expulsado]);
if (!$stmt->fetch()) {
    echo json_encode(['ok' => false, 'msg' => 'Ese usuario no es miembro del grupo']);
    exit;
}

// Expulsar
$pdo->prepare("DELETE FROM grupos_usuarios WHERE id_grupo = ? AND id_usuario = ?")
    ->execute([$id_grupo, $id_expulsado]);

echo json_encode(['ok' => true]);
