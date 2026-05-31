<?php

session_start();
require_once '../includes/conexion.php';

header('Content-Type: application/json; charset=utf-8');

/* ── 1. Autenticación ─────────────────────────────────────────────────── */
if (!isset($_SESSION['usuario']['id'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Debes iniciar sesión.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.']);
    exit;
}

$id_usuario = (int) $_SESSION['usuario']['id'];

/* ── 2. Recoger y validar parámetros ──────────────────────────────────── */
$id_lista = isset($_POST['id_lista']) ? (int) $_POST['id_lista'] : 0;
$id_pelicula = isset($_POST['id_pelicula']) ? (int) $_POST['id_pelicula'] : 0;
$accion = isset($_POST['accion']) ? trim($_POST['accion']) : '';

if ($id_lista <= 0 || $id_pelicula <= 0 || !in_array($accion, ['añadir', 'quitar'], true)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Parámetros no válidos.']);
    exit;
}

/* ── 3. Verificar que la lista pertenece al usuario ──────────────────── */
$stmt = $pdo->prepare("SELECT id_lista FROM listas WHERE id_lista = :id AND id_usuario = :uid");
$stmt->execute([':id' => $id_lista, ':uid' => $id_usuario]);

if (!$stmt->fetch()) {
    echo json_encode(['ok' => false, 'mensaje' => 'No tienes permiso para modificar esta lista.']);
    exit;
}

/* ── 4. Ejecutar la acción ────────────────────────────────────────────── */
try {
    if ($accion === 'quitar') {
        $stmt = $pdo->prepare(
            "DELETE FROM listas_peliculas WHERE id_lista = :id_lista AND id_pelicula = :id_pelicula"
        );
        $stmt->execute([':id_lista' => $id_lista, ':id_pelicula' => $id_pelicula]);

        if ($stmt->rowCount() === 0) {
            echo json_encode(['ok' => false, 'mensaje' => 'La película no estaba en la lista.']);
            exit;
        }

        echo json_encode(['ok' => true, 'mensaje' => 'Película eliminada de la lista.']);
    } elseif ($accion === 'añadir') {
        // Comprobar que la película existe
        $stmt = $pdo->prepare("SELECT id_pelicula FROM peliculas WHERE id_pelicula = :id");
        $stmt->execute([':id' => $id_pelicula]);
        if (!$stmt->fetch()) {
            echo json_encode(['ok' => false, 'mensaje' => 'La película no existe.']);
            exit;
        }

        // Insertar ignorando duplicados
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO listas_peliculas (id_lista, id_pelicula) VALUES (:id_lista, :id_pelicula)"
        );
        $stmt->execute([':id_lista' => $id_lista, ':id_pelicula' => $id_pelicula]);

        if ($stmt->rowCount() === 0) {
            echo json_encode(['ok' => false, 'mensaje' => 'Esa película ya está en la lista.']);
            exit;
        }

        echo json_encode(['ok' => true, 'mensaje' => 'Película añadida a la lista.']);
    }
} catch (PDOException $e) {
    error_log('gestionar-lista-pelicula.php PDOException: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'mensaje' => 'Error al procesar la solicitud. Inténtalo de nuevo.']);
}
