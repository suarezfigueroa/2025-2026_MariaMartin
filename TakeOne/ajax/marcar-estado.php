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
$estado = $_POST['estado'] ?? '';

$estadosValidos = ['pendiente', 'vista', 'favorita'];

if ($idUsuario <= 0 || $idPelicula <= 0 || !in_array($estado, $estadosValidos)) {
    echo json_encode(['success' => false, 'error' => 'datos_invalidos']);
    exit;
}

// Si el usuario pulsa el mismo estado que ya tenía → quitarlo (toggle)
$stmt = $pdo->prepare("SELECT estado FROM usuarios_peliculas WHERE id_usuario = :u AND id_pelicula = :p");
$stmt->execute([':u' => $idUsuario, ':p' => $idPelicula]);
$fila = $stmt->fetch();

if ($fila) {
    if ($fila['estado'] === $estado) {
        // Mismo estado → quitar (poner a NULL)
        $pdo->prepare("UPDATE usuarios_peliculas
                        SET estado = NULL, fecha_estado = NOW()
                        WHERE id_usuario = :u AND id_pelicula = :p")
            ->execute([':u' => $idUsuario, ':p' => $idPelicula]);
        echo json_encode(['success' => true, 'estado' => null]);
    } else {
        // Estado distinto → actualizar
        $pdo->prepare("UPDATE usuarios_peliculas
                        SET estado = :e, fecha_estado = NOW()
                        WHERE id_usuario = :u AND id_pelicula = :p")
            ->execute([':e' => $estado, ':u' => $idUsuario, ':p' => $idPelicula]);
        echo json_encode(['success' => true, 'estado' => $estado]);
    }
} else {
    // No existe registro → insertar
    $pdo->prepare("INSERT INTO usuarios_peliculas (id_usuario, id_pelicula, estado, fecha_estado)
                   VALUES (:u, :p, :e, NOW())")
        ->execute([':u' => $idUsuario, ':p' => $idPelicula, ':e' => $estado]);
    echo json_encode(['success' => true, 'estado' => $estado]);
}
