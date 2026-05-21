<?php

session_start();
require_once 'includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: seccionPrincipal.php");
    exit;
}

$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

if ($login === '' || $password === '') {
    header("Location: login.html?error=vacios");
    exit;
}

try {
    $sql = "SELECT * FROM usuarios 
                WHERE username = :login OR email = :login 
                LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['login' => $login]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($password, $usuario['password'])) {
        header("Location: login.html?error=credenciales");
        exit;
    }

    // Comprobar si la cuenta está suspendida
    if (isset($usuario['activo']) && $usuario['activo'] == 0) {
        header("Location: login.html?error=suspendido");
        exit;
    }

    // Guardar datos en sesión
    $_SESSION['usuario'] = [
        'id'       => $usuario['id_usuario'],
        'username' => $usuario['username'],
        'email'    => $usuario['email'],
        'rol'      => $usuario['rol'],
        'avatar'   => $usuario['avatar']
    ];

    // Redirigir según rol
    if ($usuario['rol'] === 'admin') {
        header("Location: admin/");
    } else {
        header("Location: seccionPrincipal.php");
    }
    exit;
} catch (PDOException $e) {
    die("❌ Error al iniciar sesión");
}
