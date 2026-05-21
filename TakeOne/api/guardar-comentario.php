<?php

session_start();

require_once '../includes/conexion.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'no_sesion']);
    exit;
}

$idUsuario  = $_SESSION['usuario']['id_usuario'] ?? $_SESSION['usuario']['id'] ?? null;
$idPelicula = isset($_POST['id_pelicula']) ? (int) $_POST['id_pelicula'] : 0;
$texto      = trim($_POST['comentario'] ?? '');
$esSpoiler  = isset($_POST['es_spoiler']) ? 1 : 0;

if (!$idUsuario || !$idPelicula || $texto === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO comentarios_peliculas (id_usuario, id_pelicula, comentario, es_spoiler)
    VALUES (:id_usuario, :id_pelicula, :comentario, :es_spoiler)
");
$stmt->execute([
    'id_usuario'  => (int) $idUsuario,
    'id_pelicula' => $idPelicula,
    'comentario'  => $texto,
    'es_spoiler'  => $esSpoiler,
]);

$idNuevo = $pdo->lastInsertId();

$stmtNew = $pdo->prepare("
    SELECT c.*, u.username, u.avatar, up.valoracion AS valoracion_autor
    FROM comentarios_peliculas c
    INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
    LEFT JOIN usuarios_peliculas up
        ON up.id_usuario = c.id_usuario AND up.id_pelicula = c.id_pelicula
    WHERE c.id_comentario = :id
");
$stmtNew->execute([':id' => $idNuevo]);
$comentario = $stmtNew->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'ok'         => true,
    'comentario' => [
        'id'         => (int) $comentario['id_comentario'],
        'texto'      => $comentario['comentario'],
        'es_spoiler' => (bool) $comentario['es_spoiler'],
        'username'   => $comentario['username'],
        'avatar'     => $comentario['avatar'],
        'valoracion' => $comentario['valoracion_autor'] ? (int) $comentario['valoracion_autor'] : 0,
        'id_usuario' => (int) $comentario['id_usuario'],
        'fecha'      => $comentario['fecha'],
    ]
]);
