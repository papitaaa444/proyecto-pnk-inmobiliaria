<?php
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

// Llama al conexion.php de la raíz, evitando el error de 'root'
require_once '../conexion.php'; 

if (!isset($_SESSION['usuario_id'])) { 
    echo json_encode(['success' => false, 'message' => 'Sesión expirada.']); exit; 
}

try {
    // ESTO CREA LA COLUMNA DE GALERÍA AUTOMÁTICAMENTE SI NO EXISTE
    try {
        $pdo->query("SELECT galeria_fotos FROM propiedades LIMIT 1");
    } catch (Exception $e) {
        $pdo->query("ALTER TABLE propiedades ADD COLUMN galeria_fotos TEXT NULL");
    }

    $usuario_id = (int)$_SESSION['usuario_id'];
    $codigo = trim($_POST['codigo'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $precio = (int)($_POST['precio'] ?? 0);
    $comuna = trim($_POST['comuna'] ?? '');
    $sector = trim($_POST['sector'] ?? '');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $superficie = (float)($_POST['superficie'] ?? 0);
    $dormitorios = (int)($_POST['dormitorios'] ?? 0);
    $banos = (int)($_POST['banos'] ?? 0);
    $descripcion = trim($_POST['descripcion'] ?? '');

    $imagenes_seleccionadas = $_POST['imagenes_seleccionadas'] ?? [];
    $indice_principal = (int)($_POST['indice_principal'] ?? 0);

    $directorio_destino = __DIR__ . '/../uploads/';
    if (!is_dir($directorio_destino)) { @mkdir($directorio_destino, 0777, true); }

    $lista_fotos_guardadas = [];
    $nombre_imagen_principal = 'default.jpg';
    $total_imagenes = count($_FILES['imagenes']['name'] ?? []);

    // GUARDA TODAS LAS FOTOS MARCADAS CON TICKET
    for ($i = 0; $i < $total_imagenes; $i++) {
        if (in_array((string)$i, $imagenes_seleccionadas) && $_FILES['imagenes']['error'][$i] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['imagenes']['name'][$i], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $nuevo_nombre = uniqid('img_') . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['imagenes']['tmp_name'][$i], $directorio_destino . $nuevo_nombre)) {
                    $lista_fotos_guardadas[] = $nuevo_nombre;
                    if ($i === $indice_principal) {
                        $nombre_imagen_principal = $nuevo_nombre;
                    }
                }
            }
        }
    }

    $galeria_json = json_encode($lista_fotos_guardadas);

    $sql = "INSERT INTO propiedades (`codigo`, `tipo`, `precio`, `comuna`, `sector`, `ubicacion`, `superficie`, `dormitorios`, `banos`, `descripcion`, `imagen_principal`, `galeria_fotos`, `estado`, `usuario_id`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'publicada', ?)";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([ $codigo, $tipo, $precio, $comuna, $sector, $ubicacion, $superficie, $dormitorios, $banos, $descripcion, $nombre_imagen_principal, $galeria_json, $usuario_id ]);

    echo json_encode(['success' => true, 'message' => '¡Propiedad guardada con todas las fotos exitosamente!']);

} catch (\PDOException $e) { 
    echo json_encode(['success' => false, 'message' => 'Fallo SQL: ' . $e->getMessage()]); 
}
?>