<?php

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

function responderError(string $message, int $status = 400): void
{
    http_response_code($status);
    echo json_encode([
        'ok' => false,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $idUsuario = (int) $_SESSION['usuario']['id'];

    $username = trim((string) ($_POST['username'] ?? ''));
    $bio = trim((string) ($_POST['bio'] ?? ''));
    $location = trim((string) ($_POST['location'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $currentPassword = (string) ($_POST['currentPassword'] ?? '');
    $newPassword = (string) ($_POST['newPassword'] ?? '');

    $favoriteMoviesJson = (string) ($_POST['favoriteMovies'] ?? '[]');
    $selectedGenresJson = (string) ($_POST['selectedGenres'] ?? '[]');

    $favoriteMovies = json_decode($favoriteMoviesJson, true);
    $selectedGenres = json_decode($selectedGenresJson, true);

    if ($username === '' || mb_strlen($username) < 3) {
        responderError('el nombre de usuario debe tener al menos 3 caracteres.');
    }

    if ($bio !== '' && mb_strlen($bio) > 200) {
        responderError('la biografía no puede superar los 200 caracteres.');
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        responderError('introduce un correo electrónico válido.');
    }

    if (!is_array($favoriteMovies)) {
        $favoriteMovies = [];
    }

    if (!is_array($selectedGenres)) {
        $selectedGenres = [];
    }

    if (count($favoriteMovies) > 5) {
        responderError('solo puedes guardar hasta 5 películas favoritas.');
    }

    $idsPeliculas = [];
    foreach ($favoriteMovies as $movie) {
        if (!isset($movie['id'])) {
            responderError('formato inválido en películas favoritas.');
        }

        $idPelicula = (int) $movie['id'];
        if ($idPelicula <= 0) {
            responderError('hay una película favorita inválida.');
        }

        if (in_array($idPelicula, $idsPeliculas, true)) {
            responderError('no puedes repetir películas favoritas.');
        }

        $idsPeliculas[] = $idPelicula;
    }

    $idsGeneros = [];
    foreach ($selectedGenres as $idGenero) {
        $idGenero = (int) $idGenero;
        if ($idGenero > 0 && !in_array($idGenero, $idsGeneros, true)) {
            $idsGeneros[] = $idGenero;
        }
    }

    $stmtUsuarioActual = $pdo->prepare("SELECT password, avatar FROM usuarios WHERE id_usuario = :id_usuario LIMIT 1");
    $stmtUsuarioActual->execute(['id_usuario' => $idUsuario]);
    $usuarioActual = $stmtUsuarioActual->fetch(PDO::FETCH_ASSOC);

    if (!$usuarioActual) {
        responderError('usuario no encontrado.', 404);
    }

    $avatarPath = $usuarioActual['avatar'];

    if (!empty($_FILES['avatar']['name'])) {
        if (!isset($_FILES['avatar']['tmp_name']) || !is_uploaded_file($_FILES['avatar']['tmp_name'])) {
            responderError('error al subir la imagen.');
        }

        if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
            responderError('la imagen no puede superar los 5mb.');
        }

        $mime = mime_content_type($_FILES['avatar']['tmp_name']);
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        if (!isset($allowed[$mime])) {
            responderError('formato de imagen no válido. usa jpg, png o webp.');
        }

        $uploadDir = __DIR__ . '/uploads/avatars';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $extension = $allowed[$mime];
        $fileName = 'avatar_' . $idUsuario . '_' . time() . '.' . $extension;
        $destino = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $destino)) {
            responderError('no se pudo guardar la imagen. Dir: ' . $uploadDir . ' | Writable: ' . (is_writable($uploadDir) ? 'si' : 'no') . ' | File error: ' . $_FILES['avatar']['error']);
        }

        $avatarPath = 'uploads/avatars/' . $fileName;
    }

    // Comprobar username único
    $stmtCheckUsername = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE username = :username AND id_usuario != :id_usuario LIMIT 1");
    $stmtCheckUsername->execute(['username' => $username, 'id_usuario' => $idUsuario]);
    if ($stmtCheckUsername->fetchColumn()) {
        responderError('ese nombre de usuario ya está en uso.');
    }

    // Comprobar email único
    $stmtCheckEmail = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = :email AND id_usuario != :id_usuario LIMIT 1");
    $stmtCheckEmail->execute(['email' => $email, 'id_usuario' => $idUsuario]);
    if ($stmtCheckEmail->fetchColumn()) {
        responderError('ese correo electrónico ya está registrado.');
    }

    $pdo->beginTransaction();

    $sqlUpdate = "UPDATE usuarios
                  SET username = :username,
                      email = :email,
                      avatar = :avatar,
                      biografia = :biografia,
                      localidad = :localidad
                  WHERE id_usuario = :id_usuario";

    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute([
        'username' => $username,
        'email' => $email,
        'avatar' => $avatarPath,
        'biografia' => $bio !== '' ? $bio : null,
        'localidad' => $location !== '' ? $location : null,
        'id_usuario' => $idUsuario
    ]);

    if ($currentPassword !== '' || $newPassword !== '') {
        if ($currentPassword === '' || $newPassword === '') {
            throw new RuntimeException('para cambiar la contraseña debes completar ambos campos.');
        }

        if (!password_verify($currentPassword, $usuarioActual['password'])) {
            throw new RuntimeException('la contraseña actual no es correcta.');
        }

        if (
            strlen($newPassword) < 8 ||
            !preg_match('/[A-Z]/', $newPassword) ||
            !preg_match('/[a-z]/', $newPassword) ||
            !preg_match('/[0-9]/', $newPassword)
        ) {
            throw new RuntimeException('la nueva contraseña no cumple los requisitos mínimos.');
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmtPass = $pdo->prepare("UPDATE usuarios SET password = :password WHERE id_usuario = :id_usuario");
        $stmtPass->execute([
            'password' => $hash,
            'id_usuario' => $idUsuario
        ]);
    }

    $stmtDeleteGeneros = $pdo->prepare("DELETE FROM usuarios_generos_favoritos WHERE id_usuario = :id_usuario");
    $stmtDeleteGeneros->execute(['id_usuario' => $idUsuario]);

    if (!empty($idsGeneros)) {
        $stmtInsertGenero = $pdo->prepare(
            "INSERT INTO usuarios_generos_favoritos (id_usuario, id_genero) VALUES (:id_usuario, :id_genero)"
        );

        foreach ($idsGeneros as $idGenero) {
            $stmtInsertGenero->execute([
                'id_usuario' => $idUsuario,
                'id_genero' => $idGenero
            ]);
        }
    }

    $stmtDeleteFavoritas = $pdo->prepare("DELETE FROM usuarios_favoritas_perfil WHERE id_usuario = :id_usuario");
    $stmtDeleteFavoritas->execute(['id_usuario' => $idUsuario]);

    if (!empty($idsPeliculas)) {
        $stmtCheckMovie = $pdo->prepare("SELECT id_pelicula FROM peliculas WHERE id_pelicula = :id_pelicula LIMIT 1");
        $stmtInsertFavorita = $pdo->prepare(
            "INSERT INTO usuarios_favoritas_perfil (id_usuario, id_pelicula, orden)
             VALUES (:id_usuario, :id_pelicula, :orden)"
        );

        foreach ($idsPeliculas as $index => $idPelicula) {
            $stmtCheckMovie->execute(['id_pelicula' => $idPelicula]);
            if (!$stmtCheckMovie->fetchColumn()) {
                throw new RuntimeException('una de las películas favoritas no existe.');
            }

            $stmtInsertFavorita->execute([
                'id_usuario' => $idUsuario,
                'id_pelicula' => $idPelicula,
                'orden' => $index + 1
            ]);
        }
    }

    $pdo->commit();

    $_SESSION['usuario']['username'] = $username;
    $_SESSION['usuario']['email'] = $email;
    $_SESSION['usuario']['avatar'] = $avatarPath;

    echo json_encode([
        'ok' => true,
        'message' => 'perfil actualizado correctamente'
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}