<?php
session_start();
require_once '../includes/conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Debes iniciar sesión.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.']);
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];
$nombre     = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$id_genero  = !empty($_POST['id_genero']) ? (int)$_POST['id_genero'] : null;
$tipo       = $_POST['tipo'] ?? '';

$tipos_validos = ['debates', 'recomendaciones', 'reseñas', 'club-cine'];

if ($nombre === '') {
    echo json_encode(['ok' => false, 'mensaje' => 'El nombre es obligatorio.']);
    exit;
}
if (!in_array($tipo, $tipos_validos)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Tipo de actividad no válido.']);
    exit;
}

// Imagen
$imagen = 'img/logo gato sin fondo.png';

if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $carpeta = '../uploads/grupos/';
    if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);

    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $mime = mime_content_type($_FILES['imagen']['tmp_name']);

    if (!in_array($mime, $tipos_permitidos)) {
        echo json_encode(['ok' => false, 'mensaje' => 'Solo se permiten imágenes JPG, PNG, WEBP o GIF.']);
        exit;
    }
    if ($_FILES['imagen']['size'] > 2 * 1024 * 1024) {
        echo json_encode(['ok' => false, 'mensaje' => 'La imagen no puede superar los 2 MB.']);
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
        INSERT INTO grupos (nombre, descripcion, imagen, tipo, id_genero, id_usuario)
        VALUES (:nombre, :descripcion, :imagen, :tipo, :id_genero, :id_usuario)
    ");
    $stmt->execute([
        ':nombre'      => $nombre,
        ':descripcion' => $descripcion,
        ':imagen'      => $imagen,
        ':tipo'        => $tipo,
        ':id_genero'   => $id_genero,
        ':id_usuario'  => $id_usuario,
    ]);

    $id_grupo = $pdo->lastInsertId();

    // Cuando el usuario crea un grupo automáticamente se une
    $pdo->prepare("INSERT INTO grupos_usuarios (id_grupo, id_usuario) VALUES (:g, :u)")
        ->execute([':g' => $id_grupo, ':u' => $id_usuario]);

    echo json_encode(['ok' => true, 'id_grupo' => (int)$id_grupo]);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'mensaje' => 'Error al crear el grupo.']);
}
