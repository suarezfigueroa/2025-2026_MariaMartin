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
$nombre     = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$id_genero  = !empty($_POST['id_genero']) ? (int)$_POST['id_genero'] : null;
$tipo       = $_POST['tipo'] ?? '';

$tipos_validos = ['debates', 'recomendaciones', 'reseñas', 'club-cine'];

if (!$id_grupo || $nombre === '' || !in_array($tipo, $tipos_validos)) {
    echo json_encode(['ok' => false, 'msg' => 'Datos inválidos']);
    exit;
}

// Verificar que el usuario es el creador
$check = $pdo->prepare("SELECT id_usuario, imagen FROM grupos WHERE id_grupo = :g");
$check->execute([':g' => $id_grupo]);
$grupo = $check->fetch(PDO::FETCH_ASSOC);

if (!$grupo || $grupo['id_usuario'] != $id_usuario) {
    echo json_encode(['ok' => false, 'msg' => 'No tienes permiso para editar este grupo']);
    exit;
}

// Imagen
$imagen = $grupo['imagen'];

if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $carpeta = '../uploads/grupos/';
    if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);

    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $mime = mime_content_type($_FILES['imagen']['tmp_name']);

    if (!in_array($mime, $tipos_permitidos)) {
        echo json_encode(['ok' => false, 'msg' => 'Solo se permiten imágenes JPG, PNG, WEBP o GIF.']);
        exit;
    }
    if ($_FILES['imagen']['size'] > 2 * 1024 * 1024) {
        echo json_encode(['ok' => false, 'msg' => 'La imagen no puede superar los 2 MB.']);
        exit;
    }

    $ext            = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
    $nombre_archivo = uniqid('grupo_', true) . '.' . $ext;
    $ruta           = $carpeta . $nombre_archivo;

    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta)) {
        $imagen = 'uploads/grupos/' . $nombre_archivo;
    }
}

try {
    $stmt = $pdo->prepare("
        UPDATE grupos
        SET nombre = :nombre,
            descripcion = :descripcion,
            imagen = :imagen,
            tipo = :tipo,
            id_genero = :id_genero
        WHERE id_grupo = :id_grupo
    ");
    $stmt->execute([
        ':nombre'      => $nombre,
        ':descripcion' => $descripcion,
        ':imagen'      => $imagen,
        ':tipo'        => $tipo,
        ':id_genero'   => $id_genero,
        ':id_grupo'    => $id_grupo,
    ]);

    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => 'Error al guardar los cambios']);
}
