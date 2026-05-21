<?php
session_start();
require_once 'includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.html");
    exit;
}

$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email']    ?? '');
$password = $_POST['password']      ?? '';

if ($username === '' || $email === '' || $password === '') {
    echo json_encode(['ok' => false, 'mensaje' => 'Faltan datos']);
    exit;
}

try {
    // Comprobar username
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE username = :u");
    $stmt->execute(['u' => $username]);
    if ($stmt->fetch()) {
        echo json_encode(['ok' => false, 'mensaje' => 'Ese nombre de usuario ya está en uso']);
        exit;
    }

    // Comprobar email
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = :e");
    $stmt->execute(['e' => $email]);
    if ($stmt->fetch()) {
        echo json_encode(['ok' => false, 'mensaje' => 'Ese correo electrónico ya está registrado']);
        exit;
    }

    // Hashear contraseña e insertar
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO usuarios (username, email, `password`) VALUES (:u, :e, :p)");
    $stmt->execute(['u' => $username, 'e' => $email, 'p' => $hash]);

    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    // Captura también el error de unique constraint de la BD como red de seguridad
    if ($e->getCode() === '23000') {
        echo json_encode(['ok' => false, 'mensaje' => 'El usuario o email ya están en uso']);
    } else {
        echo json_encode(['ok' => false, 'mensaje' => 'Error al registrar']);
    }
}
