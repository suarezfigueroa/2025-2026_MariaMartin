<?php

$host    = "localhost";
$db      = "takeone";
$user    = "root";
$pass    = "";
$charset = "utf8mb4";

date_default_timezone_set('Europe/Madrid');
$offset = (new DateTime('now', new DateTimeZone('Europe/Madrid')))->format('P');

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
    $pdo->exec("SET time_zone = '$offset'");
} catch (PDOException $e) {
    die("Error de conexión con la BD: " . $e->getMessage());
}