<?php
session_start();
require_once '../includes/conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'no_sesion']);
    exit;
}

$idUsuario = (int) ($_SESSION['usuario']['id_usuario'] ?? $_SESSION['usuario']['id'] ?? 0);
$idPelicula = (int) ($_POST['id_pelicula'] ?? 0);
$valoracion = isset($_POST['valoracion']) ? (int) $_POST['valoracion'] : null;

if ($idUsuario <= 0 || $idPelicula <= 0) {
    echo json_encode(['success' => false, 'error' => 'datos_invalidos']);
    exit;
}

// Sin valoración o 0 → borrar
if ($valoracion === null || $valoracion === 0) {
    $stmt = $pdo->prepare("SELECT estado FROM usuarios_peliculas WHERE id_usuario = :u AND id_pelicula = :p");
    $stmt->execute([':u' => $idUsuario, ':p' => $idPelicula]);
    $fila = $stmt->fetch();

    if ($fila) {
        if (!empty($fila['estado'])) {
            // Tiene estado → solo poner valoracion a NULL, actualizar fecha_estado
            $pdo->prepare("UPDATE usuarios_peliculas
                            SET valoracion = NULL, fecha_estado = NOW()
                            WHERE id_usuario = :u AND id_pelicula = :p")
                ->execute([':u' => $idUsuario, ':p' => $idPelicula]);
        } else {
            // Solo tenía valoración → borrar fila entera
            $pdo->prepare("DELETE FROM usuarios_peliculas WHERE id_usuario = :u AND id_pelicula = :p")
                ->execute([':u' => $idUsuario, ':p' => $idPelicula]);
        }
    }

    echo json_encode(['success' => true, 'valoracion' => 0]);
    exit;
}

// Con valoración válida (1-5) → guardar
if ($valoracion < 1 || $valoracion > 5) {
    echo json_encode(['success' => false, 'error' => 'datos_invalidos']);
    exit;
}

$pdo->prepare("
    INSERT INTO usuarios_peliculas (id_usuario, id_pelicula, valoracion, fecha_estado)
    VALUES (:u, :p, :v, NOW())
    ON DUPLICATE KEY UPDATE valoracion = :v, fecha_estado = NOW()
")->execute([':u' => $idUsuario, ':p' => $idPelicula, ':v' => $valoracion]);

echo json_encode(['success' => true, 'valoracion' => $valoracion]);
