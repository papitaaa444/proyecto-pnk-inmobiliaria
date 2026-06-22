<?php
$host = 'localhost';
$db   = 'pnk_inmobiliaria';
$user = 'admin_pnk';
$pass = 'admin123';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (\PDOException $e) {
    // Esto evita que la pantalla se ponga blanca y rompa tu diseño
    die("<div style='color:red; text-align:center; margin-top:50px;'>Error de conexión. Verifica conexion.php</div>");
}
?>