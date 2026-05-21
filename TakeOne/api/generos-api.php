<?php

require_once '../includes/conexion.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id_genero, nombre FROM generos ORDER BY nombre ASC");
    $generos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['generos' => $generos]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al cargar géneros']);
}
