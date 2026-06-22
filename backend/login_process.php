<?php
session_start();
require_once '../conexion.php'; 

header('Content-Type: application/json; charset=utf-8');

// ¡CORRECCIÓN APLICADA: AHORA BUSCA POR RUT!
$rut = trim($_POST['rut'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($rut) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Por favor, ingrese su RUT y contraseña.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE rut = ? LIMIT 1");
    $stmt->execute([$rut]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        if ($user['estado'] !== 'Activo' && $user['estado'] !== 'publicada') {
            echo json_encode(['success' => false, 'message' => 'Tu cuenta se encuentra pendiente de aprobación.']);
            exit;
        }

        // SESIONES CORRECTAS
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['usuario_nombre'] = $user['nombre'];
        $_SESSION['pnk_role'] = $user['tipo'];
        $_SESSION['usuario_rol'] = $user['tipo'];

        // Te redirige a propietario.php o gestor.php según tu rol
        $redirect_url = ($user['tipo'] === 'admin' || $user['tipo'] === 'gestor') ? 'gestor.php' : 'propietario.php';

        echo json_encode(['success' => true, 'redirect' => $redirect_url]);
    } else {
        echo json_encode(['success' => false, 'message' => 'RUT o contraseña incorrectos.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $e->getMessage()]);
}
?>