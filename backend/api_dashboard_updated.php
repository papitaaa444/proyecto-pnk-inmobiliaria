<?php
session_start();
require_once 'conexion.php'; 

// Seguridad básica: El usuario debe estar logueado
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'No autorizado']));
}

header('Content-Type: application/json');
$action = $_GET['action'] ?? '';

// Acciones que requieren permisos de administrador
$acciones_admin = ['get_resumen', 'get_usuarios', 'update_user_status', 'save_user', 'delete_user', 
                   'get_propiedades', 'save_prop_admin', 'delete_prop_admin', 'update_prop_status'];
if (in_array($action, $acciones_admin) && $_SESSION['pnk_role'] !== 'admin') {
    die(json_encode(['error' => 'Permisos insuficientes']));
}

// ============================================================================
// 1. RESUMEN DEL DASHBOARD
// ============================================================================
if ($action == 'get_resumen') {
    $propTotal = $pdo->query("SELECT COUNT(*) FROM propiedades")->fetchColumn();
    $propActivas = $pdo->query("SELECT COUNT(*) FROM propiedades WHERE estado = 'Activa'")->fetchColumn();
    $userTotal = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    $userPendientes = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE estado = 'Pendiente'")->fetchColumn();
    $ultimos = $pdo->query("SELECT nombre, email, tipo, estado FROM usuarios ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'stats' => ['total_prop' => $propTotal, 'activas_prop' => $propActivas, 'total_user' => $userTotal, 'pendientes_user' => $userPendientes],
        'ultimos_usuarios' => $ultimos
    ]);
}

// ============================================================================
// 2. OBTENER USUARIOS
// ============================================================================
else if ($action == 'get_usuarios') {
    echo json_encode($pdo->query("SELECT id, nombre, rut, email, telefono, tipo, estado FROM usuarios ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC));
}

// ============================================================================
// 3. OBTENER PROPIEDADES (con foto principal y nombre del dueño)
// ============================================================================
else if ($action == 'get_propiedades') {
    $sql = "SELECT p.*, u.nombre as dueno,
                   (SELECT ruta FROM propiedad_fotos WHERE propiedad_id = p.id AND es_principal = TRUE LIMIT 1) as imagen_principal
            FROM propiedades p 
            INNER JOIN usuarios u ON p.user_id = u.id
            ORDER BY p.id DESC";
    echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
}

// ============================================================================
// 4. CAMBIAR ESTADO DE USUARIO
// ============================================================================
else if ($action == 'update_user_status') {
    $id = $_POST['id'] ?? null;
    $estado = $_POST['estado'] ?? null;
    
    if (!$id || !$estado) {
        die(json_encode(['success' => false, 'message' => 'Parámetros requeridos']));
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $id]);
        echo json_encode(['success' => true, 'message' => "Usuario marcado como $estado"]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ============================================================================
// 5. GUARDAR/ACTUALIZAR USUARIO
// ============================================================================
else if ($action == 'save_user') {
    $id = $_POST['id'] ?? null;
    $nombre = $_POST['nombre'] ?? '';
    $rut = $_POST['rut'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $tipo = $_POST['tipo'] ?? 'propietario';
    $estado = $_POST['estado'] ?? 'Pendiente';
    $password = $_POST['password'] ?? null;

    if (empty($nombre) || empty($rut) || empty($email)) {
        die(json_encode(['success' => false, 'message' => 'Faltan campos obligatorios']));
    }

    try {
        if (!empty($id)) {
            // Actualizar usuario existente
            if (!empty($password)) {
                $passHash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE usuarios SET nombre=?, rut=?, email=?, telefono=?, tipo=?, estado=?, password=? WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nombre, $rut, $email, $telefono, $tipo, $estado, $passHash, $id]);
            } else {
                $sql = "UPDATE usuarios SET nombre=?, rut=?, email=?, telefono=?, tipo=?, estado=? WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nombre, $rut, $email, $telefono, $tipo, $estado, $id]);
            }
            echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente']);
        } else {
            // Crear nuevo usuario
            if (empty($password)) {
                die(json_encode(['success' => false, 'message' => 'Contraseña obligatoria para nuevos usuarios']));
            }
            $passHash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO usuarios (nombre, rut, email, password, telefono, tipo, estado) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $rut, $email, $passHash, $telefono, $tipo, $estado]);
            echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente']);
        }
    } catch (PDOException $e) {
        $errorMsg = $e->getMessage();
        if (strpos($errorMsg, 'Duplicate entry') !== false) {
            if (strpos($errorMsg, 'email') !== false) $msg = 'Ese correo electrónico ya está en uso.';
            else if (strpos($errorMsg, 'rut') !== false) $msg = 'Ese RUT ya se encuentra registrado.';
            else if (strpos($errorMsg, 'telefono') !== false) $msg = 'Ese número de teléfono ya está en uso.';
            else $msg = 'El RUT, Email o Teléfono ya están registrados.';
        } else {
            $msg = 'Error interno: ' . $e->getCode();
        }
        echo json_encode(['success' => false, 'message' => $msg]);
    }
}

// ============================================================================
// 6. ELIMINAR USUARIO
// ============================================================================
else if ($action == 'delete_user') {
    $id = $_POST['id'] ?? null;
    
    if (!$id) {
        die(json_encode(['success' => false, 'message' => 'ID requerido']));
    }
    
    try {
        // Primero, eliminar las propiedades del usuario (y sus fotos en cascada)
        $stmt = $pdo->prepare("DELETE FROM propiedades WHERE user_id = ?");
        $stmt->execute([$id]);
        
        // Luego, eliminar el usuario
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Usuario eliminado correctamente']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ============================================================================
// 7. GUARDAR/ACTUALIZAR PROPIEDAD (Admin)
// ============================================================================
else if ($action == 'save_prop_admin') {
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
    $estado = $_POST['estado'] ?? 'Pendiente';
    $user_id = $_POST['user_id'] ?? null;

    if (empty($tipo) || empty($ubicacion) || empty($precio) || !$user_id) {
        die(json_encode(['success' => false, 'message' => 'Faltan campos obligatorios']));
    }

    try {
        if (!empty($id)) {
            // Actualizar
            $stmt = $pdo->prepare("
                UPDATE propiedades 
                SET tipo=?, ubicacion=?, comuna=?, sector=?, precio=?, 
                    dormitorios=?, banos=?, superficie=?, descripcion=?, estado=?
                WHERE id=?
            ");
            $stmt->execute([$tipo, $ubicacion, $comuna, $sector, $precio, 
                           $dormitorios, $banos, $superficie, $descripcion, $estado, $id]);
            echo json_encode(['success' => true, 'message' => 'Propiedad actualizada']);
        } else {
            // Crear
            $codigo = "PNK-" . rand(100000, 999999);
            $stmt = $pdo->prepare("
                INSERT INTO propiedades 
                (codigo, tipo, ubicacion, comuna, sector, user_id, precio, dormitorios, banos, superficie, descripcion, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$codigo, $tipo, $ubicacion, $comuna, $sector, $user_id, $precio, 
                           $dormitorios, $banos, $superficie, $descripcion, $estado]);
            echo json_encode(['success' => true, 'message' => 'Propiedad creada']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ============================================================================
// 8. CAMBIAR ESTADO DE PROPIEDAD
// ============================================================================
else if ($action == 'update_prop_status') {
    $id = $_POST['id'] ?? null;
    $estado = $_POST['estado'] ?? null;
    
    if (!$id || !$estado) {
        die(json_encode(['success' => false, 'message' => 'Parámetros requeridos']));
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE propiedades SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $id]);
        echo json_encode(['success' => true, 'message' => "Propiedad marcada como $estado"]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ============================================================================
// 9. ELIMINAR PROPIEDAD
// ============================================================================
else if ($action == 'delete_prop_admin') {
    $id = $_POST['id'] ?? null;
    
    if (!$id) {
        die(json_encode(['success' => false, 'message' => 'ID requerido']));
    }
    
    try {
        // Las fotos se eliminarán en cascada por la FK
        $stmt = $pdo->prepare("DELETE FROM propiedades WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Propiedad eliminada']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

else {
    echo json_encode(['error' => 'Acción no reconocida']);
}
?>
