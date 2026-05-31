<?php
session_start();
require_once '../includes/conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode([]);
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];
$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT g.id_grupo, g.nombre, g.descripcion, g.imagen, g.tipo,
           ge.nombre AS nombre_genero,
           COUNT(DISTINCT gu.id_usuario) AS num_miembros,
           MAX(CASE WHEN gu2.id_usuario = :uid THEN 1 ELSE 0 END) AS unido
    FROM grupos g
    LEFT JOIN generos ge ON g.id_genero = ge.id_genero
    LEFT JOIN grupos_usuarios gu ON g.id_grupo = gu.id_grupo
    LEFT JOIN grupos_usuarios gu2 ON g.id_grupo = gu2.id_grupo AND gu2.id_usuario = :uid2
    WHERE g.nombre LIKE :q OR g.descripcion LIKE :q2
    GROUP BY g.id_grupo
    ORDER BY num_miembros DESC
");

$stmt->execute([
    ':uid'  => $id_usuario,
    ':uid2' => $id_usuario,
    ':q'    => "%$q%",
    ':q2'   => "%$q%",
]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
