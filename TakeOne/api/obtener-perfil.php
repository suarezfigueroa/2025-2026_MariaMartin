<?php

// Para editar perfil
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

try {
    $idUsuario = (int) $_SESSION['usuario']['id'];

    $sqlUsuario = "SELECT id_usuario, username, email, avatar, biografia, localidad
                   FROM usuarios
                   WHERE id_usuario = :id_usuario
                   LIMIT 1";
    $stmtUsuario = $pdo->prepare($sqlUsuario);
    $stmtUsuario->execute(['id_usuario' => $idUsuario]);
    $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'message' => 'usuario no encontrado'
        ]);
        exit;
    }

    $sqlGeneros = "SELECT g.id_genero, g.nombre
                   FROM usuarios_generos_favoritos ugf
                   INNER JOIN generos g ON g.id_genero = ugf.id_genero
                   WHERE ugf.id_usuario = :id_usuario
                   ORDER BY g.nombre ASC";
    $stmtGeneros = $pdo->prepare($sqlGeneros);
    $stmtGeneros->execute(['id_usuario' => $idUsuario]);
    $generos = $stmtGeneros->fetchAll(PDO::FETCH_ASSOC);

    $sqlTodosGeneros = "SELECT id_genero, nombre
                        FROM generos
                        ORDER BY nombre ASC";
    $stmtTodosGeneros = $pdo->query($sqlTodosGeneros);
    $todosGeneros = $stmtTodosGeneros->fetchAll(PDO::FETCH_ASSOC);

    $sqlFavoritas = "SELECT ufp.orden, p.id_pelicula, p.titulo, p.poster
                     FROM usuarios_favoritas_perfil ufp
                     INNER JOIN peliculas p ON p.id_pelicula = ufp.id_pelicula
                     WHERE ufp.id_usuario = :id_usuario
                     ORDER BY ufp.orden ASC";
    $stmtFavoritas = $pdo->prepare($sqlFavoritas);
    $stmtFavoritas->execute(['id_usuario' => $idUsuario]);
    $favoritasRaw = $stmtFavoritas->fetchAll(PDO::FETCH_ASSOC);

    $favoritas = [];
    for ($i = 1; $i <= 5; $i++) {
        $favoritas[$i] = null;
    }

    foreach ($favoritasRaw as $fila) {
        $orden = (int) $fila['orden'];
        if ($orden >= 1 && $orden <= 5) {
            $favoritas[$orden] = [
                'orden' => $orden,
                'id_pelicula' => (int) $fila['id_pelicula'],
                'titulo' => $fila['titulo'],
                'poster' => $fila['poster']
            ];
        }
    }

    echo json_encode([
        'ok' => true,
        'perfil' => [
            'id_usuario' => (int) $usuario['id_usuario'],
            'username' => $usuario['username'],
            'email' => $usuario['email'],
            'avatar' => $usuario['avatar'],
            'biografia' => $usuario['biografia'],
            'localidad' => $usuario['localidad'],
            'generos_favoritos' => $generos,
            'todos_generos' => $todosGeneros,
            'peliculas_favoritas' => array_values($favoritas)
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'error interno al cargar el perfil',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
