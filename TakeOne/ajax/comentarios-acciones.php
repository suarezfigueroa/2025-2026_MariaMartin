<?php
session_start();
require_once '../includes/conexion.php';

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'no_sesion']);
    exit;
}

$idUsuario    = (int) ($_SESSION['usuario']['id_usuario'] ?? $_SESSION['usuario']['id'] ?? 0);
$idComentario = (int) ($_POST['id_comentario'] ?? 0);
$accion       = $_POST['accion'] ?? '';

if (!$idUsuario || !$idComentario || !in_array($accion, ['editar', 'eliminar'], true)) {
    echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos.']);
    exit;
}

// Comprobar que el comentario pertenece al usuario
$stmt = $pdo->prepare("SELECT id_usuario FROM comentarios_peliculas WHERE id_comentario = :id");
$stmt->execute([':id' => $idComentario]);
$fila = $stmt->fetch();

if (!$fila || (int) $fila['id_usuario'] !== $idUsuario) {
    echo json_encode(['success' => false, 'mensaje' => 'No tienes permiso para modificar este comentario.']);
    exit;
}

// ── Editar ────────────────────────────────────────────────────
if ($accion === 'editar') {
    $nuevoTexto = trim($_POST['comentario'] ?? '');

    if ($nuevoTexto === '') {
        echo json_encode(['success' => false, 'mensaje' => 'El comentario no puede estar vacío.']);
        exit;
    }

    if (mb_strlen($nuevoTexto) > 500) {
        echo json_encode(['success' => false, 'mensaje' => 'El comentario es demasiado largo (máx. 500 caracteres).']);
        exit;
    }

    $stmtUpdate = $pdo->prepare("
        UPDATE comentarios_peliculas
        SET comentario = :comentario
        WHERE id_comentario = :id AND id_usuario = :id_usuario
    ");
    $stmtUpdate->execute([
        ':comentario' => $nuevoTexto,
        ':id'         => $idComentario,
        ':id_usuario' => $idUsuario,
    ]);

    echo json_encode(['success' => true]);
    exit;
}

// ── Eliminar ──────────────────────────────────────────────────
if ($accion === 'eliminar') {
    $stmtDel = $pdo->prepare("
        DELETE FROM comentarios_peliculas
        WHERE id_comentario = :id AND id_usuario = :id_usuario
    ");
    $stmtDel->execute([
        ':id'         => $idComentario,
        ':id_usuario' => $idUsuario,
    ]);

    echo json_encode(['success' => true]);
    exit;
}
