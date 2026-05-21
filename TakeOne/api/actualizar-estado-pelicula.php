<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/conexion.php';

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'message' => 'Debes iniciar sesión.'
    ]);
    exit;
}

$idUsuario = $_SESSION['usuario']['id_usuario'] ?? $_SESSION['usuario']['id'] ?? null;
$idPelicula = isset($_POST['id_pelicula']) ? (int)$_POST['id_pelicula'] : 0;
$estado = isset($_POST['estado']) ? trim($_POST['estado']) : null;

$estadosValidos = ['pendiente', 'vista', 'favorita', ''];

if (!$idUsuario || $idPelicula <= 0 || !in_array($estado, $estadosValidos, true)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'message' => 'Datos no válidos.'
    ]);
    exit;
}

try {
    $stmtExiste = $pdo->prepare("
        SELECT id_usuario, id_pelicula, valoracion
        FROM usuarios_peliculas
        WHERE id_usuario = :id_usuario
          AND id_pelicula = :id_pelicula
        LIMIT 1
    ");
    $stmtExiste->execute([
        'id_usuario' => (int)$idUsuario,
        'id_pelicula' => $idPelicula
    ]);
    $registro = $stmtExiste->fetch(PDO::FETCH_ASSOC);

    if ($registro) {
        if ($estado === '') {
            $stmtUpdate = $pdo->prepare("
                UPDATE usuarios_peliculas
                SET estado = NULL, fecha = NOW()
                WHERE id_usuario = :id_usuario
                  AND id_pelicula = :id_pelicula
            ");
            $stmtUpdate->execute([
                'id_usuario' => (int)$idUsuario,
                'id_pelicula' => $idPelicula
            ]);
        } else {
            $stmtUpdate = $pdo->prepare("
                UPDATE usuarios_peliculas
                SET estado = :estado, fecha = NOW()
                WHERE id_usuario = :id_usuario
                  AND id_pelicula = :id_pelicula
            ");
            $stmtUpdate->execute([
                'estado' => $estado,
                'id_usuario' => (int)$idUsuario,
                'id_pelicula' => $idPelicula
            ]);
        }
    } else {
        if ($estado === '') {
            echo json_encode([
                'ok' => true,
                'estado' => null
            ]);
            exit;
        }

        $stmtInsert = $pdo->prepare("
            INSERT INTO usuarios_peliculas (id_usuario, id_pelicula, estado, fecha)
            VALUES (:id_usuario, :id_pelicula, :estado, NOW())
        ");
        $stmtInsert->execute([
            'id_usuario' => (int)$idUsuario,
            'id_pelicula' => $idPelicula,
            'estado' => $estado
        ]);
    }

    echo json_encode([
        'ok' => true,
        'estado' => $estado !== '' ? $estado : null
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Error al actualizar el estado.'
    ]);
}
