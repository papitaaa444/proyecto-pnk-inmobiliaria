<?php
// backend/buscar_propiedades.php
ini_set('display_errors', 0); 
header('Content-Type: application/json; charset=utf-8');

try {
    // Busca la conexión subiendo una carpeta (hacia la raíz)
    if (file_exists(__DIR__ . '/../conexion.php')) {
        require_once __DIR__ . '/../conexion.php';
    } else {
        echo json_encode([['error' => 'No se encuentra conexion.php']]);
        exit;
    }

    $tipo = $_GET['tipo'] ?? 'todas';
    $comuna = $_GET['comuna'] ?? 'todas';
    $sector = $_GET['sector'] ?? 'todas';

    // Base: Solo muestra las propiedades publicadas
    $sql = "SELECT p.*, u.nombre as dueno_nombre FROM propiedades p LEFT JOIN usuarios u ON p.usuario_id = u.id WHERE p.estado IN ('publicada', 'Activa', 'Activo')";
    $parametros = [];

    if ($tipo !== 'todas' && $tipo !== '') {
        $sql .= " AND LOWER(p.tipo) = ?";
        $parametros[] = strtolower($tipo);
    }
    if ($comuna !== 'todas' && $comuna !== '') {
        $sql .= " AND LOWER(p.comuna) = ?";
        $parametros[] = strtolower($comuna);
    }
    if ($sector !== 'todas' && $sector !== '') {
        $sql .= " AND LOWER(p.sector) = ?";
        $parametros[] = strtolower($sector);
    }

    $sql .= " ORDER BY p.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($resultados);

} catch (Exception $e) {
    // Si la BD falla, devolvemos un JSON vacío para que JS no colapse
    echo json_encode([]);
}
?>