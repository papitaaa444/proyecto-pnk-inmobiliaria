<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "127.0.0.1";
$db   = "pnk_inmobiliaria";
$user = "root";
$pass = "admin12345";

echo "<h3>Prueba de Diagnóstico PNK</h3>";

try {
    // Intentamos conectar al servidor
    $dsn = "mysql:host=$host;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    echo "✅ Conexión al servidor MySQL (127.0.0.1) exitosa.<br>";
    
    // Intentamos seleccionar la base de datos
    $pdo->query("USE $db");
    echo "✅ Base de datos '$db' encontrada y accesible.<br>";

    // Verificamos si la tabla de usuarios existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Tabla 'usuarios' detectada correctamente.<br>";
        $count = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
        echo "ℹ️ Usuarios registrados actualmente: $count<br>";
    } else {
        echo "❌ ERROR: La tabla 'usuarios' no existe. Debes ejecutar el script SQL.<br>";
    }

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage();
}
?>