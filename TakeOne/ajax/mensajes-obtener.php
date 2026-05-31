<?php
session_start();
require_once '../includes/conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
    exit;
}

$id_usuario  = $_SESSION['usuario']['id'];
$id_grupo    = (int)($_GET['id_grupo'] ?? 0);
$desde_id    = (int)($_GET['desde_id'] ?? 0); // Para el polling: solo mensajes nuevos
$carga_ini   = isset($_GET['desde_id']); // false = carga inicial, true = polling

if (!$id_grupo) {
    echo json_encode(['ok' => false, 'msg' => 'Grupo inválido']);
    exit;
}

// Verificar que el usuario es miembro
$check = $pdo->prepare("SELECT 1 FROM grupos_usuarios WHERE id_grupo = :g AND id_usuario = :u");
$check->execute([':g' => $id_grupo, ':u' => $id_usuario]);
if (!$check->fetch()) {
    echo json_encode(['ok' => false, 'msg' => 'No eres miembro de este grupo']);
    exit;
}

try {
    if (!$carga_ini) {
        // Carga inicial: últimos 50 mensajes
        $stmt = $pdo->prepare("
            SELECT m.id_mensaje, m.mensaje, m.fecha,
                   u.username, u.avatar, u.id_usuario
            FROM mensajes_grupo m
            JOIN usuarios u ON m.id_usuario = u.id_usuario
            WHERE m.id_grupo = :g AND m.estado = 'activo'
            ORDER BY m.fecha DESC
            LIMIT 50
        ");
        $stmt->execute([':g' => $id_grupo]);
        $mensajes = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    } else {
        // Polling: solo mensajes posteriores al último conocido
        $stmt = $pdo->prepare("
            SELECT m.id_mensaje, m.mensaje, m.fecha,
                   u.username, u.avatar, u.id_usuario
            FROM mensajes_grupo m
            JOIN usuarios u ON m.id_usuario = u.id_usuario
            WHERE m.id_grupo = :g AND m.estado = 'activo' AND m.id_mensaje > :desde
            ORDER BY m.fecha ASC
        ");
        $stmt->execute([':g' => $id_grupo, ':desde' => $desde_id]);
        $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode(['ok' => true, 'mensajes' => $mensajes]);

} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => 'Error al obtener mensajes']);
}