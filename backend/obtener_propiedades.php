<?php
require_once 'conexion.php';
header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : 'todas';
$comuna = isset($_GET['comuna']) ? trim($_GET['comuna']) : 'todas';
$sector = isset($_GET['sector']) ? trim($_GET['sector']) : 'todas';

if ($id > 0) {
    // Si se pide una propiedad específica por su ID
    $stmt = $pdo->prepare("SELECT * FROM propiedades WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
} else {
    // Busca solo las propiedades que estén con estado "publicada" (en minúscula como en tu BD)
    $sql = "SELECT * FROM propiedades WHERE estado = 'publicada'";
    $params = [];

    // Filtros dinámicos
    if ($tipo !== 'todas') {
        $sql .= " AND LOWER(tipo) = LOWER(?)";
        $params[] = $tipo;
    }
    if ($comuna !== 'todas') {
        $sql .= " AND LOWER(comuna) = LOWER(?)";
        $params[] = $comuna;
    }
    if ($sector !== 'todas') {
        $sql .= " AND LOWER(sector) = LOWER(?)";
        $params[] = $sector;
    }

    $sql .= " ORDER BY id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
?>