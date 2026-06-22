<?php
require_once 'conexion.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, rut, email, password, telefono, tipo, estado) VALUES (?, ?, ?, ?, ?, 'propietario', 'Pendiente')");
        $stmt->execute([$_POST['nombre'], $_POST['rut'], $_POST['email'], $pass, $_POST['telefono']]);
        echo json_encode(['success' => true, 'message' => '¡Registro exitoso! Pendiente de aprobación.']);
    } catch (PDOException $e) {
        $errorMsg = $e->getMessage();
        if (strpos($errorMsg, 'Duplicate entry') !== false) {
            if (strpos($errorMsg, 'email') !== false) $msg = 'El correo electrónico ya está registrado.';
            else if (strpos($errorMsg, 'rut') !== false) $msg = 'El RUT ya está registrado.';
            else $msg = 'Los datos ingresados ya existen en nuestra base de datos.';
        } else {
            $msg = 'Ocurrió un error al procesar el registro.';
        }
        echo json_encode(['success' => false, 'message' => $msg]);
    }
}
?>