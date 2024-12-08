<?php
// Configuración de la base de datos
$host = 'localhost';
$db = 'cubo';
$user = 'root';
$pass = '357689';

// Conexión a la base de datos usando PDO
try {
    $pdo = new PDO("mysql:host=$host;port=3308;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

?>