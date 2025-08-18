<?php
$host     = '127.0.0.1';
$dbname   = 'sistema_taller';
$username = 'root';
$password = '';
$port     = 3307; // 3306 si no cambiaste

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
try {
  $conn = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (PDOException $e) {
  die('Error de conexión MySQL: ' . $e->getMessage());
}
