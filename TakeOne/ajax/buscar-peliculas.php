<?php
// ── Para el funcionamiento del buscador de la sección películas ────-
session_start();
require_once '../includes/conexion.php';

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode([]);
    exit;
}

$termino = '%' . $q . '%';

$sql = "SELECT DISTINCT p.id_pelicula, p.titulo, p.titulo_original, p.poster, p.anio, p.imdb
        FROM peliculas p
        WHERE p.titulo LIKE :termino OR p.titulo_original LIKE :termino2
        ORDER BY p.anio DESC
        LIMIT 40";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':termino'  => $termino,
    ':termino2' => $termino,
]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
