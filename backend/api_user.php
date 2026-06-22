<?php
session_start();
require_once 'conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Sesión expirada']));
}

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

if ($action === 'get_my_props') {
    $stmt = $pdo->prepare("SELECT * FROM propiedades WHERE user_id = ?");
    $stmt->execute([$user_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

if ($action === 'save_prop') {
    $tipo = $_POST['tipo'];
    $ubicacion = $_POST['ubicacion'] . ", " . $_POST['ciudad'];
    $precio = $_POST['precio'];
    $id = $_POST['id'];

    if (!empty($id)) {
        $stmt = $pdo->prepare("UPDATE propiedades SET tipo=?, ubicacion=?, precio=? WHERE id=? AND user_id=?");
        $stmt->execute([$tipo, $ubicacion, $precio, $id, $user_id]);
        echo json_encode(['success' => true, 'message' => 'Propiedad actualizada']);
    } else {
        $codigo = "PNK-" . rand(1000, 9999);
        $stmt = $pdo->prepare("INSERT INTO propiedades (codigo, tipo, ubicacion, user_id, precio, estado) VALUES (?, ?, ?, ?, ?, 'Pendiente')");
        $stmt->execute([$codigo, $tipo, $ubicacion, $user_id, $precio]);
        echo json_encode(['success' => true, 'message' => 'Propiedad enviada a revisión']);
    }
}
?>