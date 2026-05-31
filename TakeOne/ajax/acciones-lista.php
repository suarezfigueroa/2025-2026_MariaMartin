<?php
session_start();
require_once '../includes/conexion.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Debes iniciar sesión.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.']);
    exit;
}

$id_usuario = (int) ($_SESSION['usuario']['id_usuario'] ?? $_SESSION['usuario']['id'] ?? 0);
$id_lista   = (int) ($_POST['id_lista'] ?? 0);
$accion     = $_POST['accion'] ?? '';

if (!$id_usuario || !$id_lista || !in_array($accion, ['editar', 'eliminar'], true)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Datos incompletos o acción no válida.']);
    exit;
}

// Verificar que la lista pertenece al usuario
$stmt = $pdo->prepare("SELECT imagen FROM listas WHERE id_lista = :id AND id_usuario = :uid");
$stmt->execute([
    ':id'  => $id_lista,
    ':uid' => $id_usuario
]);
$lista_actual = $stmt->fetch();

if (!$lista_actual) {
    echo json_encode(['ok' => false, 'mensaje' => 'No tienes permiso para modificar esta lista.']);
    exit;
}

/* ───────────────────────── EDITAR ───────────────────────── */
if ($accion === 'editar') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $visibilidad_permitidas = ['publica', 'amigos', 'privada'];
    $visibilidad = in_array($_POST['visibilidad'] ?? '', $visibilidad_permitidas, true)
        ? $_POST['visibilidad']
        : 'publica';

    if ($titulo === '') {
        echo json_encode(['ok' => false, 'mensaje' => 'El nombre de la lista es obligatorio.']);
        exit;
    }

    if (mb_strlen($titulo) > 100) {
        echo json_encode(['ok' => false, 'mensaje' => 'El nombre no puede superar los 100 caracteres.']);
        exit;
    }

    if (mb_strlen($descripcion) > 500) {
        echo json_encode(['ok' => false, 'mensaje' => 'La descripción no puede superar los 500 caracteres.']);
        exit;
    }

    // Mantener imagen actual por defecto
    $ruta_imagen = $lista_actual['imagen'];

    // Gestión de nueva imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $archivo = $_FILES['imagen'];
        $max_size = 2 * 1024 * 1024; // 2 MB

        if ($archivo['size'] > $max_size) {
            echo json_encode(['ok' => false, 'mensaje' => 'La imagen no puede superar los 2 MB.']);
            exit;
        }

        $tipos_permitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($archivo['tmp_name']);

        if (!in_array($mime, $tipos_permitidos, true)) {
            echo json_encode(['ok' => false, 'mensaje' => 'Formato de imagen no permitido. Usa JPG, PNG, WEBP o GIF.']);
            exit;
        }

        $extensiones = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];
        $ext = $extensiones[$mime];

        $dir_upload = 'img/listas/';
        if (!is_dir($dir_upload)) {
            mkdir($dir_upload, 0755, true);
        }

        $nombre_archivo = 'lista_' . $id_lista . '_' . time() . '.' . $ext;
        $ruta_destino = $dir_upload . $nombre_archivo;

        if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
            echo json_encode(['ok' => false, 'mensaje' => 'No se pudo guardar la imagen. Inténtalo de nuevo.']);
            exit;
        }

        // Borrar imagen anterior si era local
        $imagen_vieja = $lista_actual['imagen'];
        if (
            $imagen_vieja &&
            !str_starts_with($imagen_vieja, 'http') &&
            file_exists($imagen_vieja) &&
            $imagen_vieja !== 'img/lista-default.jpg'
        ) {
            @unlink($imagen_vieja);
        }

        $ruta_imagen = $ruta_destino;
    }

    try {
        $sql = "UPDATE listas
                SET titulo = :titulo,
                    descripcion = :descripcion,
                    imagen = :imagen,
                    visibilidad = :visibilidad
                WHERE id_lista = :id_lista
                  AND id_usuario = :id_usuario";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':titulo'      => $titulo,
            ':descripcion' => $descripcion,
            ':imagen'      => $ruta_imagen,
            ':visibilidad' => $visibilidad,
            ':id_lista'    => $id_lista,
            ':id_usuario'  => $id_usuario,
        ]);

        echo json_encode(['ok' => true, 'mensaje' => 'Lista actualizada correctamente.']);
        exit;
    } catch (PDOException $e) {
        error_log('acciones-lista.php [editar] PDOException: ' . $e->getMessage());
        echo json_encode(['ok' => false, 'mensaje' => 'Error al guardar los cambios. Inténtalo de nuevo.']);
        exit;
    }
}

/* ──────────────────────── ELIMINAR ──────────────────────── */
if ($accion === 'eliminar') {
    try {
        $pdo->prepare("DELETE FROM listas_peliculas WHERE id_lista = :id")
            ->execute([':id' => $id_lista]);

        $pdo->prepare("DELETE FROM listas_likes WHERE id_lista = :id")
            ->execute([':id' => $id_lista]);

        $pdo->prepare("DELETE FROM listas WHERE id_lista = :id AND id_usuario = :uid")
            ->execute([
                ':id'  => $id_lista,
                ':uid' => $id_usuario
            ]);

        echo json_encode(['ok' => true, 'mensaje' => 'Lista eliminada.']);
        exit;
    } catch (PDOException $e) {
        error_log('acciones-lista.php [eliminar] PDOException: ' . $e->getMessage());
        echo json_encode(['ok' => false, 'mensaje' => 'No se pudo eliminar la lista.']);
        exit;
    }
}
