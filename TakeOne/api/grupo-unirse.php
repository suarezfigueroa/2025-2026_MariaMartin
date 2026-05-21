<?php
session_start();
require_once '../includes/conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];
$id_grupo   = (int)($_POST['id_grupo'] ?? 0);
$accion     = $_POST['accion'] ?? ''; // 'unirse' o 'salir'

if (!$id_grupo || !in_array($accion, ['unirse', 'salir'])) {
    echo json_encode(['ok' => false, 'msg' => 'Datos inválidos']);
    exit;
}

try {
    if ($accion === 'unirse') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO grupos_usuarios (id_grupo, id_usuario) VALUES (:g, :u)");
    } else {
        $stmt = $pdo->prepare("DELETE FROM grupos_usuarios WHERE id_grupo = :g AND id_usuario = :u");
    }
    $stmt->execute(['g' => $id_grupo, 'u' => $id_usuario]);

    // Contar miembros actualizados
    $count = $pdo->prepare("SELECT COUNT(*) FROM grupos_usuarios WHERE id_grupo = :g");
    $count->execute(['g' => $id_grupo]);

    echo json_encode(['ok' => true, 'miembros' => (int)$count->fetchColumn()]);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => 'Error de BD']);
}
