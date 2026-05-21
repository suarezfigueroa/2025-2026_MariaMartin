<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']['id'])) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'message' => 'usuario no autenticado'
    ]);
    exit;
}

require_once '../includes/conexion.php';

$q = trim((string) ($_GET['q'] ?? ''));

if (mb_strlen($q) < 2) {
    echo json_encode([
        'ok' => true,
        'results' => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $sql = "SELECT id_pelicula, titulo, anio, poster, sinopsis
            FROM peliculas
            WHERE titulo LIKE :q
            ORDER BY titulo ASC
            LIMIT 20";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'q' => '%' . $q . '%'
    ]);

    $results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [
            'id' => (int) $row['id_pelicula'],
            'title' => $row['titulo'],
            'year' => $row['anio'] ? (int) $row['anio'] : null,
            'poster' => $row['poster'],
            'overview' => $row['sinopsis']
        ];
    }

    echo json_encode([
        'ok' => true,
        'results' => $results
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'error buscando películas',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
