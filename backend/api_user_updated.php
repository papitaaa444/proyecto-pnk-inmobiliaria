<?php
session_start();
require_once 'conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Sesión expirada']));
}

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

// ============================================================================
// 1. OBTENER MIS PROPIEDADES (con foto principal)
// ============================================================================
if ($action === 'get_my_props') {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               (SELECT ruta FROM propiedad_fotos WHERE propiedad_id = p.id AND es_principal = TRUE LIMIT 1) as imagen_principal
        FROM propiedades p 
        WHERE p.user_id = ? 
        ORDER BY p.id DESC
    ");
    $stmt->execute([$user_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

// ============================================================================
// 2. OBTENER DETALLE DE UNA PROPIEDAD (con todas sus fotos)
// ============================================================================
else if ($action === 'get_prop_detail') {
    $prop_id = $_POST['id'] ?? $_GET['id'] ?? null;
    
    if (!$prop_id) {
        die(json_encode(['success' => false, 'message' => 'ID de propiedad requerido']));
    }
    
    // Verificar que la propiedad pertenezca al usuario
    $stmt = $pdo->prepare("SELECT * FROM propiedades WHERE id = ? AND user_id = ?");
    $stmt->execute([$prop_id, $user_id]);
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
// 3. GUARDAR/ACTUALIZAR PROPIEDAD (sin fotos, solo datos básicos)
// ============================================================================
else if ($action === 'save_prop') {
    $id = $_POST['id'] ?? null;
    $tipo = $_POST['tipo'] ?? '';
    $ubicacion = $_POST['ubicacion'] ?? '';
    $comuna = $_POST['comuna'] ?? '';
    $sector = $_POST['sector'] ?? '';
    $precio = $_POST['precio'] ?? 0;
    $dormitorios = $_POST['dormitorios'] ?? 0;
    $banos = $_POST['banos'] ?? 0;
    $superficie = $_POST['superficie'] ?? 0;
    $descripcion = $_POST['descripcion'] ?? '';

    // Validaciones básicas
    if (empty($tipo) || empty($ubicacion) || empty($comuna) || empty($precio)) {
        die(json_encode(['success' => false, 'message' => 'Faltan campos obligatorios']));
    }

    try {
        if (!empty($id)) {
            // ACTUALIZAR propiedad existente
            $stmt = $pdo->prepare("
                UPDATE propiedades 
                SET tipo=?, ubicacion=?, comuna=?, sector=?, precio=?, 
                    dormitorios=?, banos=?, superficie=?, descripcion=?
                WHERE id=? AND user_id=?
            ");
            $stmt->execute([$tipo, $ubicacion, $comuna, $sector, $precio, 
                           $dormitorios, $banos, $superficie, $descripcion, $id, $user_id]);
            echo json_encode(['success' => true, 'message' => 'Propiedad actualizada correctamente', 'prop_id' => $id]);
        } else {
            // CREAR nueva propiedad
            $codigo = "PNK-" . rand(100000, 999999);
            $stmt = $pdo->prepare("
                INSERT INTO propiedades 
                (codigo, tipo, ubicacion, comuna, sector, user_id, precio, dormitorios, banos, superficie, descripcion, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente')
            ");
            $stmt->execute([$codigo, $tipo, $ubicacion, $comuna, $sector, $user_id, $precio, 
                           $dormitorios, $banos, $superficie, $descripcion]);
            $prop_id = $pdo->lastInsertId();
            echo json_encode(['success' => true, 'message' => 'Propiedad enviada a revisión', 'prop_id' => $prop_id]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()]);
    }
}

// ============================================================================
// 4. CARGAR FOTOS (máximo 10 por propiedad)
// ============================================================================
else if ($action === 'upload_fotos') {
    $prop_id = $_POST['prop_id'] ?? null;
    
    if (!$prop_id) {
        die(json_encode(['success' => false, 'message' => 'ID de propiedad requerido']));
    }
    
    // Verificar que la propiedad pertenezca al usuario
    $stmt = $pdo->prepare("SELECT id FROM propiedades WHERE id = ? AND user_id = ?");
    $stmt->execute([$prop_id, $user_id]);
    if (!$stmt->fetch()) {
        die(json_encode(['success' => false, 'message' => 'Propiedad no autorizada']));
    }
    
    // Contar fotos existentes
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM propiedad_fotos WHERE propiedad_id = ?");
    $stmt->execute([$prop_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $fotos_actuales = $result['total'];
    
    if (!isset($_FILES['fotos'])) {
        die(json_encode(['success' => false, 'message' => 'No se enviaron archivos']));
    }
    
    $archivos = $_FILES['fotos'];
    $fotos_subidas = [];
    $errores = [];
    
    // Crear carpeta si no existe
    $upload_dir = '../img/uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Procesar cada archivo
    for ($i = 0; $i < count($archivos['name']); $i++) {
        // Validar que no se exceda el límite de 10
        if ($fotos_actuales + count($fotos_subidas) >= 10) {
            $errores[] = "Máximo 10 fotos por propiedad";
            break;
        }
        
        $file = $archivos['tmp_name'][$i];
        $nombre = $archivos['name'][$i];
        $tipo_archivo = mime_content_type($file);
        
        // Validar formato
        $formatos_validos = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($tipo_archivo, $formatos_validos)) {
            $errores[] = "$nombre: Formato no permitido (JPG, PNG, WEBP)";
            continue;
        }
        
        // Validar tamaño (máx 5MB por foto)
        if (filesize($file) > 5 * 1024 * 1024) {
            $errores[] = "$nombre: Archivo muy grande (máx 5MB)";
            continue;
        }
        
        // Generar nombre único
        $extension = pathinfo($nombre, PATHINFO_EXTENSION);
        $nombre_unico = "prop_" . $prop_id . "_" . time() . "_" . rand(1000, 9999) . "." . $extension;
        $ruta_completa = $upload_dir . $nombre_unico;
        
        // Guardar archivo
        if (move_uploaded_file($file, $ruta_completa)) {
            // Insertar en BD
            $es_principal = (count($fotos_subidas) === 0 && $fotos_actuales === 0) ? TRUE : FALSE;
            $stmt = $pdo->prepare("INSERT INTO propiedad_fotos (propiedad_id, ruta, es_principal) VALUES (?, ?, ?)");
            $stmt->execute([$prop_id, 'img/uploads/' . $nombre_unico, $es_principal]);
            $fotos_subidas[] = ['nombre' => $nombre, 'ruta' => 'img/uploads/' . $nombre_unico];
        } else {
            $errores[] = "$nombre: Error al guardar el archivo";
        }
    }
    
    $respuesta = [
        'success' => count($errores) === 0,
        'fotos_subidas' => count($fotos_subidas),
        'fotos' => $fotos_subidas
    ];
    
    if (!empty($errores)) {
        $respuesta['errores'] = $errores;
    }
    
    echo json_encode($respuesta);
}

// ============================================================================
// 5. ESTABLECER IMAGEN PRINCIPAL
// ============================================================================
else if ($action === 'set_main_foto') {
    $foto_id = $_POST['foto_id'] ?? null;
    $prop_id = $_POST['prop_id'] ?? null;
    
    if (!$foto_id || !$prop_id) {
        die(json_encode(['success' => false, 'message' => 'Parámetros requeridos']));
    }
    
    // Verificar que la propiedad pertenezca al usuario
    $stmt = $pdo->prepare("SELECT id FROM propiedades WHERE id = ? AND user_id = ?");
    $stmt->execute([$prop_id, $user_id]);
    if (!$stmt->fetch()) {
        die(json_encode(['success' => false, 'message' => 'No autorizado']));
    }
    
    try {
        // Desmarcar todas
        $stmt = $pdo->prepare("UPDATE propiedad_fotos SET es_principal = FALSE WHERE propiedad_id = ?");
        $stmt->execute([$prop_id]);
        
        // Marcar la nueva
        $stmt = $pdo->prepare("UPDATE propiedad_fotos SET es_principal = TRUE WHERE id = ? AND propiedad_id = ?");
        $stmt->execute([$foto_id, $prop_id]);
        
        echo json_encode(['success' => true, 'message' => 'Imagen principal actualizada']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ============================================================================
// 6. ELIMINAR FOTO
// ============================================================================
else if ($action === 'delete_foto') {
    $foto_id = $_POST['foto_id'] ?? null;
    $prop_id = $_POST['prop_id'] ?? null;
    
    if (!$foto_id || !$prop_id) {
        die(json_encode(['success' => false, 'message' => 'Parámetros requeridos']));
    }
    
    // Verificar que la propiedad pertenezca al usuario
    $stmt = $pdo->prepare("SELECT id FROM propiedades WHERE id = ? AND user_id = ?");
    $stmt->execute([$prop_id, $user_id]);
    if (!$stmt->fetch()) {
        die(json_encode(['success' => false, 'message' => 'No autorizado']));
    }
    
    try {
        // Obtener ruta de la foto
        $stmt = $pdo->prepare("SELECT ruta FROM propiedad_fotos WHERE id = ? AND propiedad_id = ?");
        $stmt->execute([$foto_id, $prop_id]);
        $foto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($foto) {
            // Eliminar archivo físico
            $ruta_archivo = '../' . $foto['ruta'];
            if (file_exists($ruta_archivo)) {
                unlink($ruta_archivo);
            }
            
            // Eliminar de BD
            $stmt = $pdo->prepare("DELETE FROM propiedad_fotos WHERE id = ?");
            $stmt->execute([$foto_id]);
            
            echo json_encode(['success' => true, 'message' => 'Foto eliminada']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Foto no encontrada']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ============================================================================
// 7. ELIMINAR/CAMBIAR ESTADO DE PROPIEDAD
// ============================================================================
else if ($action === 'delete_prop') {
    $prop_id = $_POST['id'] ?? null;
    $nuevo_estado = $_POST['estado'] ?? 'Eliminada';
    
    if (!$prop_id) {
        die(json_encode(['success' => false, 'message' => 'ID requerido']));
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE propiedades SET estado = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$nuevo_estado, $prop_id, $user_id]);
        echo json_encode(['success' => true, 'message' => 'Propiedad actualizada']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
}
?>
