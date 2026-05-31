<?php
require_once 'includes/conexion.php';
$r = $pdo->query("SELECT NOW() as hora_mysql")->fetch();
echo "MySQL: " . $r['hora_mysql'];
echo "<br>PHP: " . date('Y-m-d H:i:s');