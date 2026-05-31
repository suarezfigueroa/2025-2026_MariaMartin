<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/conexion.php';

if (!isset($_SESSION['usuario']['id'])) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Debes iniciar sesión para añadir películas a una lista.'
    ]);
    exit;
}

$id_usuario = (int) $_SESSION['usuario']['id'];
$id_lista = isset($_POST['id_lista']) ? (int) $_POST['id_lista'] : 0;
$id_pelicula = isset($_POST['id_pelicula']) ? (int) $_POST['id_pelicula'] : 0;

if ($id_lista <= 0 || $id_pelicula <= 0) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Faltan datos para guardar la película.'
    ]);
    exit;
}

try {
    $stmtLista = $pdo->prepare("
        SELECT id_lista
        FROM listas
        WHERE id_lista = :id_lista
          AND id_usuario = :id_usuario
        LIMIT 1
    ");
    $stmtLista->execute([
        'id_lista' => $id_lista,
        'id_usuario' => $id_usuario
    ]);

    if (!$stmtLista->fetch()) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'No puedes añadir películas a esta lista.'
        ]);
        exit;
    }

    $stmtPelicula = $pdo->prepare("
        SELECT id_pelicula
        FROM peliculas
        WHERE id_pelicula = :id_pelicula
        LIMIT 1
    ");
    $stmtPelicula->execute([
        'id_pelicula' => $id_pelicula
    ]);

    if (!$stmtPelicula->fetch()) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'La película no existe.'
        ]);
        exit;
    }

    $stmtExiste = $pdo->prepare("
        SELECT 1
        FROM listas_peliculas
        WHERE id_lista = :id_lista
          AND id_pelicula = :id_pelicula
        LIMIT 1
    ");
    $stmtExiste->execute([
        'id_lista' => $id_lista,
        'id_pelicula' => $id_pelicula
    ]);

    if ($stmtExiste->fetch()) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Esta película ya está en esa lista.'
        ]);
        exit;
    }

    $stmtInsert = $pdo->prepare("
        INSERT INTO listas_peliculas (id_lista, id_pelicula)
        VALUES (:id_lista, :id_pelicula)
    ");
    $stmtInsert->execute([
        'id_lista' => $id_lista,
        'id_pelicula' => $id_pelicula
    ]);

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Película añadida correctamente a la lista.'
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Ha ocurrido un error al guardar la película en la lista.'
    ]);
}
