<?php
// Script temporal de diagnóstico - ELIMINAR DESPUÉS
require_once 'conexion.php';
header('Content-Type: application/json');

$columnas = $pdo->query("DESCRIBE propiedades")->fetchAll(PDO::FETCH_ASSOC);
$registros = $pdo->query("SELECT id, codigo, imagen_principal, estado FROM propiedades LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'columnas' => array_column($columnas, 'Field'),
    'muestra_registros' => $registros
], JSON_PRETTY_PRINT);
?>
