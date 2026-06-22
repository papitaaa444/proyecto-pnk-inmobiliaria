<?php
ob_start(); // Previene que cualquier espacio en blanco o warning rompa el JSON
// Desactivamos la salida de errores en texto plano para que no rompan el JSON
error_reporting(0);
ini_set('display_errors', 0);

// IMPORTANTE: En AWS, PHP debe usar 127.0.0.1 para conectar a su propio MySQL.
// Usar la IP pública (3.213.38.42) desde adentro del servidor suele fallar por el Firewall de AWS.
$host = "127.0.0.1"; 
$db   = "pnk_inmobiliaria";
$user = "admin_pnk";
$pass = "admin123";

try {
    // Cambiamos a utf8mb4 para compatibilidad total con AWS
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    // Limpiamos cualquier mensaje de error de texto previo
    ob_clean();
    echo json_encode(['success' => false, 'message' => "Error DB Interno: " . $e->getMessage()]);
    exit;
}