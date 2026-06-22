<?php
session_start();
require_once 'conexion.php';
header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';
$input = json_decode(file_get_contents('php://input'), true);

if ($action === 'login') {
    $usuario = isset($input['correo']) ? trim($input['correo']) : '';
    $clave = isset($input['clave']) ? trim($input['clave']) : '';

    if (empty($usuario) || empty($clave)) {
        echo json_encode(["success" => false, "message" => "Por favor, complete todos los campos."]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ? OR rut = ?");
        $stmt->execute([$usuario, $usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // LLAVE MAESTRA: Si usas admin@pnk.cl y 123, entra directo como administrador (Bypass de emergencia)
        if ($usuario === 'admin@pnk.cl' && $clave === '123') {
            $_SESSION['usuario_id'] = 999;
            $_SESSION['usuario_rol'] = 'administrador';
            $_SESSION['usuario_nombre'] = 'Admin Principal';
            echo json_encode(["success" => true, "message" => "Bienvenido (Acceso Maestro)", "rol" => "administrador", "nombre" => "Admin Principal"]);
            exit;
        }

        // Validación normal para el resto de usuarios o gestores
        if ($user && (password_verify($clave, $user['clave']) || $clave === $user['clave'])) {
            
            $esPendiente = (strpos($user['nombre'], '[Pendiente]') !== false || strpos($user['nombre'], '[Gestor Pendiente]') !== false);
            
            if ($esPendiente && $user['rol'] !== 'administrador') {
                echo json_encode(["success" => false, "message" => "Tu cuenta aún está en revisión por un administrador."]);
                exit;
            }

            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_rol'] = $user['rol'];
            
            $nombreLimpio = str_replace(['[Pendiente] ', '[Gestor Pendiente] '], '', $user['nombre']);
            $_SESSION['usuario_nombre'] = $nombreLimpio;

            echo json_encode(["success" => true, "message" => "Bienvenido al portal", "rol" => $user['rol'], "nombre" => $nombreLimpio]);
        } else {
            echo json_encode(["success" => false, "message" => "Credenciales incorrectas. Verifica tu contraseña."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Error de conexión con la base de datos."]);
    }
} 
elseif ($action === 'registro') {
    $rut = isset($input['rut']) ? trim($input['rut']) : '';
    $nombre = isset($input['nombre']) ? trim($input['nombre']) : '';
    $correo = isset($input['correo']) ? trim($input['correo']) : '';
    $clave = isset($input['clave']) ? trim($input['clave']) : '';
    $rol = isset($input['rol']) && $input['rol'] === 'gestor' ? 'gestor' : 'propietario';

    if (empty($rut) || empty($nombre) || empty($correo) || empty($clave)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ? OR rut = ?");
        $stmt->execute([$correo, $rut]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ese RUT o Correo ya se encuentra registrado.']);
            exit;
        }

        $claveHash = password_hash($clave, PASSWORD_DEFAULT);
        $etiqueta = ($rol === 'gestor') ? '[Gestor Pendiente] ' : '[Pendiente] ';
        
        $stmt = $pdo->prepare("INSERT INTO usuarios (rut, nombre, correo, clave, rol) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$rut, $etiqueta . $nombre, $correo, $claveHash, $rol])) {
            echo json_encode(['success' => true, 'message' => 'Registro exitoso. Tu cuenta pasará a revisión.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo completar el registro.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error de base de datos en el registro.']);
    }
}
?>