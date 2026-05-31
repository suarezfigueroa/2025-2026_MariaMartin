<?php

if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') {
    // Configuración LOCAL
    $host = "localhost";
    $db   = "takeone";
    $user = "root";
    $pass = "";
} else {
    // Configuración SERVIDOR (InfinityFree)
    $host = "sql101.infinityfree.com";
    $db   = "if0_41982187_db_takeone";
    $user = "if0_41982187";
    $pass = "Thissempiternal";
}

$charset = "utf8mb4";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=$charset",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    $pdo->exec("SET time_zone = '+00:00'");
} catch (PDOException $e) {
    die("Error de conexión con la BD: " . $e->getMessage());
}