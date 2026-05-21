<?php
session_start();
require_once '../includes/conexion.php';

header('Content-Type: application/json');

$id_usuario_sesion = $_SESSION['usuario']['id'] ?? null;
$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode([]);
    exit;
}

$termino = '%' . $q . '%';

$sql = "SELECT l.id_lista, l.titulo, l.imagen,
            COUNT(DISTINCT lp.id_pelicula) AS num_peliculas,
            COUNT(DISTINCT ll.id_usuario)  AS num_likes
        FROM listas l
        LEFT JOIN listas_peliculas lp ON l.id_lista = lp.id_lista
        LEFT JOIN listas_likes     ll ON l.id_lista = ll.id_lista
        WHERE (
            l.visibilidad = 'publica'
            OR (l.visibilidad IN ('privada','amigos') AND l.id_usuario = :id_usuario)
        )
        AND (l.titulo LIKE :termino OR l.descripcion LIKE :termino2)
        GROUP BY l.id_lista
        ORDER BY l.fecha_creacion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':id_usuario' => $id_usuario_sesion,
    ':termino'    => $termino,
    ':termino2'   => $termino,
]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
