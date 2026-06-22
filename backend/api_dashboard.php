<?php
session_start();
require_once '../conexion.php'; 

header('Content-Type: application/json');
$action = $_GET['action'] ?? '';

// BLOQUEO EXCLUSIVO PARA ADMINISTRADOR
$acciones_admin = ['get_resumen', 'get_usuarios', 'update_user_status', 'save_user'];
if (in_array($action, $acciones_admin)) {
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['usuario_id'])) {
        die(json_encode(['error' => 'No autorizado']));
    }
}

// ESTA PARTE QUEDA LIBRE PARA QUE TU PÁGINA PÚBLICA MUESTRE LAS FOTOS
if ($action == 'get_propiedades') {
    $sql = "SELECT p.*, u.nombre as dueno 
            FROM propiedades p 
            LEFT JOIN usuarios u ON p.usuario_id = u.id 
            ORDER BY p.id DESC";
    echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// Tus demás funciones originales de Admin quedan intactas
if ($action == 'get_resumen') {
    $propTotal = $pdo->query("SELECT COUNT(*) FROM propiedades")->fetchColumn();
    $propActivas = $pdo->query("SELECT COUNT(*) FROM propiedades WHERE estado = 'Activa' OR estado = 'publicada'")->fetchColumn();
    $userTotal = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    $userPendientes = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE estado = 'Pendiente'")->fetchColumn();
    $ultimos = $pdo->query("SELECT nombre, email, tipo, estado FROM usuarios ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['stats' => ['total_prop' => $propTotal, 'activas_prop' => $propActivas, 'total_user' => $userTotal, 'pendientes_user' => $userPendientes], 'ultimos_usuarios' => $ultimos]);
}
if ($action == 'get_usuarios') {
    echo json_encode($pdo->query("SELECT id, nombre, rut, email, telefono, tipo, estado FROM usuarios")->fetchAll(PDO::FETCH_ASSOC));
}
if ($action == 'update_user_status') {
    $stmt = $pdo->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
    $stmt->execute([$_POST['estado'], $_POST['id']]);
    echo json_encode(['success' => true]);
}
?>