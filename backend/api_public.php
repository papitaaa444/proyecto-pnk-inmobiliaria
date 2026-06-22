<?php
require_once 'conexion.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// ============================================================================
// 1. OBTENER PROPIEDADES ACTIVAS PARA EL CATÁLOGO PÚBLICO
// ============================================================================
if ($action === 'get_propiedades_activas') {
    $tipo = $_GET['tipo'] ?? '';
    $comuna = $_GET['comuna'] ?? '';
    $sector = $_GET['sector'] ?? '';
    
    $sql = "SELECT p.*, u.nombre as dueno,
                   (SELECT ruta FROM propiedad_fotos WHERE propiedad_id = p.id AND es_principal = TRUE LIMIT 1) as imagen_principal
            FROM propiedades p 
            INNER JOIN usuarios u ON p.user_id = u.id
            WHERE p.estado = 'Activa'";
    
    $params = [];
    
    if (!empty($tipo)) {
        $sql .= " AND p.tipo = ?";
        $params[] = $tipo;
    }
    
    if (!empty($comuna)) {
        $sql .= " AND p.comuna = ?";
        $params[] = $comuna;
    }
    
    if (!empty($sector)) {
        $sql .= " AND p.sector = ?";
        $params[] = $sector;
    }
    
    $sql .= " ORDER BY p.id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

// ============================================================================
// 2. OBTENER DETALLE DE UNA PROPIEDAD (Público)
// ============================================================================
else if ($action === 'get_propiedad_publica') {
    $prop_id = $_GET['id'] ?? null;
    
    if (!$prop_id) {
        die(json_encode(['success' => false, 'message' => 'ID requerido']));
    }
    
    // Obtener propiedad activa
    $stmt = $pdo->prepare("
        SELECT p.*, u.nombre as dueno, u.email as dueno_email, u.telefono as dueno_telefono
        FROM propiedades p 
        INNER JOIN usuarios u ON p.user_id = u.id
        WHERE p.id = ? AND p.estado = 'Activa'
    ");
    $stmt->execute([$prop_id]);
    $propiedad = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$propiedad) {
        die(json_encode(['success' => false, 'message' => 'Propiedad no encontrada']));
    }
    
    // Obtener todas las fotos
    $stmt = $pdo->prepare("SELECT id, ruta, es_principal FROM propiedad_fotos WHERE propiedad_id = ? ORDER BY es_principal DESC");
    $stmt->execute([$prop_id]);
    $fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $propiedad['fotos'] = $fotos;
    echo json_encode(['success' => true, 'data' => $propiedad]);
}

// ============================================================================
// 3. OBTENER LISTADO DE COMUNAS
// ============================================================================
else if ($action === 'get_comunas') {
    $sql = "SELECT DISTINCT comuna FROM propiedades WHERE estado = 'Activa' AND comuna IS NOT NULL ORDER BY comuna ASC";
    $comunas = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($comunas);
}

// ============================================================================
// 4. OBTENER LISTADO DE SECTORES POR COMUNA
// ============================================================================
else if ($action === 'get_sectores') {
    $comuna = $_GET['comuna'] ?? '';
    
    if (empty($comuna)) {
        die(json_encode(['success' => false, 'message' => 'Comuna requerida']));
    }
    
    $sql = "SELECT DISTINCT sector FROM propiedades WHERE estado = 'Activa' AND comuna = ? AND sector IS NOT NULL ORDER BY sector ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$comuna]);
    $sectores = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($sectores);
}

// ============================================================================
// 5. OBTENER TIPOS DE PROPIEDADES
// ============================================================================
else if ($action === 'get_tipos') {
    $sql = "SELECT DISTINCT tipo FROM propiedades WHERE estado = 'Activa' ORDER BY tipo ASC";
    $tipos = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($tipos);
}

else {
    echo json_encode(['error' => 'Acción no reconocida']);
}
?>
