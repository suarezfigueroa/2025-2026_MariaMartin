<?php
session_start();
require_once '../includes/conexion.php';

header('Content-Type: application/json');

// Solo usuarios logueados
if (!isset($_SESSION['usuario']['id'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Debes iniciar sesión para crear una lista.']);
    exit;
}

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.']);
    exit;
}

// Recoger y validar datos de texto
$titulo      = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');

if ($titulo === '') {
    echo json_encode(['ok' => false, 'mensaje' => 'El título es obligatorio.']);
    exit;
}

if (mb_strlen($titulo) > 100) {
    echo json_encode(['ok' => false, 'mensaje' => 'El título no puede superar los 100 caracteres.']);
    exit;
}

// Validar visibilidad
$visibilidad = in_array($_POST['visibilidad'] ?? '', ['publica', 'amigos', 'privada'])
    ? $_POST['visibilidad']
    : 'publica';

// Procesar imagen
$imagen = 'img/lista-default.jpg'; // valor por defecto

if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {

    // Crear carpeta si no existe
    $carpeta = 'uploads/listas/';
    if (!is_dir($carpeta)) {
        mkdir($carpeta, 0755, true);
    }

    // Validar que sea una imagen real
    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $tipo_mime        = mime_content_type($_FILES['imagen']['tmp_name']);

    if (!in_array($tipo_mime, $tipos_permitidos)) {
        echo json_encode(['ok' => false, 'mensaje' => 'Solo se permiten imágenes JPG, PNG, WEBP o GIF.']);
        exit;
    }

    // Validar tamaño máximo (2 MB)
    if ($_FILES['imagen']['size'] > 2 * 1024 * 1024) {
        echo json_encode(['ok' => false, 'mensaje' => 'La imagen no puede superar los 2 MB.']);
        exit;
    }

    // Generar nombre único para evitar colisiones
    $extension      = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
    $nombre_archivo = uniqid('lista_', true) . '.' . strtolower($extension);
    $ruta_destino   = $carpeta . $nombre_archivo;

    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
        $imagen = $ruta_destino;
    } else {
        echo json_encode(['ok' => false, 'mensaje' => 'Error al guardar la imagen. Inténtalo de nuevo.']);
        exit;
    }
}

// Insertar en BD
$stmt = $pdo->prepare(
    "INSERT INTO listas (id_usuario, titulo, descripcion, imagen, visibilidad)
     VALUES (:id_usuario, :titulo, :descripcion, :imagen, :visibilidad)"
);

$stmt->execute([
    ':id_usuario'  => $_SESSION['usuario']['id'],
    ':titulo'      => $titulo,
    ':descripcion' => $descripcion,
    ':imagen'      => $imagen,
    ':visibilidad' => $visibilidad,
]);

$id_lista = $pdo->lastInsertId();

echo json_encode([
    'ok'       => true,
    'mensaje'  => 'Lista creada correctamente.',
    'id_lista' => (int) $id_lista,
]);
