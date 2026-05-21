<?php
session_start();
require_once '../includes/conexion.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']['id'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Debes iniciar sesión.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.']);
    exit;
}

$id_usuario = (int) $_SESSION['usuario']['id'];
$id_lista = isset($_POST['id_lista']) ? (int) $_POST['id_lista'] : 0;

if ($id_lista <= 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'Lista no válida.']);
    exit;
}

// Verificar que la lista existe y que el usuario NO es el propietario
$stmt = $pdo->prepare("SELECT id_usuario FROM listas WHERE id_lista = :id");
$stmt->execute([':id' => $id_lista]);
$lista = $stmt->fetch();

if (!$lista) {
    echo json_encode(['ok' => false, 'mensaje' => 'Lista no encontrada.']);
    exit;
}

if ((int) $lista['id_usuario'] === $id_usuario) {
    echo json_encode(['ok' => false, 'mensaje' => 'No puedes dar like a tu propia lista.']);
    exit;
}

// ¿Ya existe el like?
$stmt = $pdo->prepare("SELECT 1 FROM listas_likes WHERE id_lista = :id_lista AND id_usuario = :id_usuario");
$stmt->execute([':id_lista' => $id_lista, ':id_usuario' => $id_usuario]);
$ya_tiene_like = (bool) $stmt->fetch();

try {
    if ($ya_tiene_like) {
        // Quitar like
        $stmt = $pdo->prepare("DELETE FROM listas_likes WHERE id_lista = :id_lista AND id_usuario = :id_usuario");
        $stmt->execute([':id_lista' => $id_lista, ':id_usuario' => $id_usuario]);
        $accion = 'quitado';
    } else {
        // Dar like
        $stmt = $pdo->prepare("INSERT INTO listas_likes (id_lista, id_usuario) VALUES (:id_lista, :id_usuario)");
        $stmt->execute([':id_lista' => $id_lista, ':id_usuario' => $id_usuario]);
        $accion = 'dado';
    }

    // Contar likes actuales
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM listas_likes WHERE id_lista = :id_lista");
    $stmt->execute([':id_lista' => $id_lista]);
    $total_likes = (int) $stmt->fetchColumn();

    echo json_encode([
        'ok' => true,
        'accion' => $accion,
        'liked' => !$ya_tiene_like,
        'total_likes' => $total_likes,
    ]);
} catch (PDOException $e) {
    error_log('toggle-like-lista.php: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'mensaje' => 'Error al procesar el like.']);
}
