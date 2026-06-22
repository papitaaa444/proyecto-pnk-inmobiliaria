<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (file_exists(__DIR__ . '/conexion.php')) {
    require_once __DIR__ . '/conexion.php';
} elseif (file_exists(__DIR__ . '/../conexion.php')) {
    require_once __DIR__ . '/../conexion.php';
} else {
    die("<h2 style='text-align:center; color:red; margin-top:50px;'>Error Crítico: No se encuentra conexion.php</h2>");
}

$identificador = trim($_POST['rut'] ?? $_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciando Sesión...</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>body { background-color: #f3f4f6; font-family: sans-serif; }</style>
</head>
<body>
<?php
if (empty($identificador) || empty($password)) {
    echo "<script>Swal.fire('Error', 'Debes ingresar tu RUT/Correo y contraseña.', 'error').then(() => { window.location.href='index.html'; });</script>";
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE rut = ? OR correo = ? LIMIT 1");
    $stmt->execute([$identificador, $identificador]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $pass_valida = false;
        $clave_db = $user['password'] ?? $user['clave'] ?? ''; 
        
        if (password_verify($password, $clave_db)) {
            $pass_valida = true;
        } else if ($password === $clave_db) {
            $pass_valida = true; 
        }

        if ($pass_valida) {
            $estado = strtolower($user['estado'] ?? 'activo');
            if ($estado !== 'activo' && $estado !== 'publicada') {
                echo "<script>Swal.fire('Cuenta Pendiente', 'Tu cuenta está pendiente de aprobación.', 'warning').then(() => { window.location.href='index.html'; });</script>";
                exit;
            }

            // Inyección de variables de sesión para compatibilidad total
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nombre'] = $user['nombre'] ?? 'Usuario';
            $_SESSION['usuario_nombre'] = $user['nombre'] ?? 'Usuario';
            
            $rol = strtolower(trim($user['tipo'] ?? $user['rol'] ?? 'propietario'));
            $_SESSION['pnk_role'] = $rol;
            $_SESSION['usuario_rol'] = $rol;

            // ASIGNACIÓN ESTRICTA DE PANELES SEGÚN EL ROL EXACTO
            if ($rol === 'admin' || $rol === 'administrador') {
                $destino = 'admin.php';
            } elseif ($rol === 'gestor' || $rol === 'gestor free') {
                $destino = 'gestor.php';
            } else {
                $destino = 'propietario.php';
            }

            echo "<script>
                Swal.fire({
                    title: '¡Bienvenido!',
                    text: 'Iniciaste sesión correctamente.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = '$destino';
                });
            </script>";
            exit;
        } else {
            echo "<script>Swal.fire('Datos Incorrectos', 'La contraseña ingresada no es válida.', 'error').then(() => { window.location.href='index.html'; });</script>";
        }
    } else {
        echo "<script>Swal.fire('No encontrado', 'El RUT o Correo ingresado no existe en el sistema.', 'error').then(() => { window.location.href='index.html'; });</script>";
    }
} catch (PDOException $e) {
    echo "<script>Swal.fire('Error de Base de Datos', '" . addslashes($e->getMessage()) . "', 'error').then(() => { window.location.href='index.html'; });</script>";
}
?>
</body>
</html>