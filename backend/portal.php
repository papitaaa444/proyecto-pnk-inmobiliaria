<?php
// backend/portal.php
require_once '../conexion.php'; 

header('Content-Type: text/html; charset=utf-8');

// RECIBIR LA BÚSQUEDA DEL USUARIO (Por si busca por comuna en el index)
$busqueda_comuna = $_POST['comuna'] ?? $_GET['comuna'] ?? '';

try {
    // Si buscó una comuna, filtramos. Si no, mostramos todo.
    $sql = "SELECT * FROM propiedades WHERE estado = 'publicada'";
    $parametros = [];
    
    if (!empty($busqueda_comuna)) {
        $sql .= " AND comuna LIKE ?";
        $parametros[] = "%" . $busqueda_comuna . "%";
    }
    
    $sql .= " ORDER BY id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    $propiedades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<p class='text-danger'>Error de Base de Datos: " . $e->getMessage() . "</p>";
    exit;
}

if (empty($propiedades)) {
    echo "<div class='text-center py-5 text-muted fw-bold w-100'>No se encontraron propiedades para esta búsqueda.</div>";
    exit;
}

foreach ($propiedades as $p) {
    $id = $p['id'];
    
    // FORMATO PESO CHILENO ESTRICTO ($ 150.000.000)
    $precio_formateado = '$ ' . number_format($p['precio'] ?? 0, 0, ',', '.');
    $descripcion = htmlspecialchars($p['descripcion'] ?? '');
    
    // Obtener la galería de fotos REALES subidas
    $galeria = json_decode($p['galeria_fotos'] ?? '[]', true);
    if (!is_array($galeria)) { $galeria = []; }
    
    // TARJETA DE PROPIEDAD
    echo "
    <div class='col-md-4 mb-4'>
        <div class='card card-propiedad border-0 shadow-sm' style='border-radius:12px; overflow:hidden; background:#fff;'>
            <img src='uploads/" . htmlspecialchars($p['imagen_principal'] ?? 'default.jpg') . "' class='card-img-top' style='height:230px; object-fit:cover;' onerror=\"this.src='img/pnkpnk.png'\">
            <div class='card-body p-4'>
                <span class='badge mb-2' style='background:#fff1f2; color:#FF0066; font-weight:700; padding:6px 10px;'>" . htmlspecialchars($p['tipo']) . "</span>
                <h5 class='card-title fw-bold' style='color:#FF0066; font-size:22px;'>" . $precio_formateado . "</h5>
                <p class='text-muted small mb-3'><i class='fas fa-map-marker-alt me-1' style='color:#FF0066;'></i> " . htmlspecialchars($p['comuna'] . ', ' . $p['sector']) . "</p>
                <button class='btn btn-dark w-100 fw-bold text-white' style='background:#000000; border:none; border-radius:6px; padding:10px;' data-bs-toggle='modal' data-bs-target='#modalPropiedad" . $id . "'>Ver Detalles</button>
            </div>
        </div>
    </div>

    <div class='modal fade' id='modalPropiedad" . $id . "' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-lg modal-dialog-centered'>
            <div class='modal-content border-0' style='border-radius:16px; overflow:hidden;'>
                <div class='modal-body p-4' style='background:#f8fafc;'>
                    
                    <img src='uploads/" . htmlspecialchars($p['imagen_principal'] ?? 'default.jpg') . "' class='w-100 shadow-sm' style='height:380px; object-fit:cover; border-radius:12px; margin-bottom:20px;' onerror=\"this.src='img/pnkpnk.png'\">
                    
                    <div class='d-flex gap-3 mb-4 overflow-auto pb-2' style='white-space: nowrap;'>";
                    
                    $cont = 0;
                    foreach ($galeria as $foto_extra) {
                        // Saltamos la principal para no repetirla
                        if ($foto_extra !== $p['imagen_principal']) {
                            echo "<img src='uploads/" . htmlspecialchars($foto_extra) . "' style='width:200px; height:130px; object-fit:cover; border-radius:8px; flex-shrink:0; box-shadow: 0 2px 5px rgba(0,0,0,0.1);' onerror=\"this.style.display='none'\">";
                            $cont++;
                        }
                    }
                    if ($cont === 0) {
                        echo "<div class='w-100 text-center py-3 rounded' style='background:#fff; border:1px dashed #cbd5e1;'><span class='text-muted small'>No hay fotos adicionales para esta propiedad.</span></div>";
                    }

    echo "          </div>

                    <div class='p-3 mb-4 bg-white shadow-sm' style='border-left:4px solid #FF0066; border-radius:4px; color:#4b5563; font-size:15px;'>
                        <p class='m-0' style='white-space: pre-line;'>" . $descripcion . "</p>
                    </div>

                    <div class='d-flex justify-content-around text-center py-4 mb-4' style='background:#0f172a; color:#fff; border-radius:8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
                        <div>
                            <span class='d-block small' style='color:#94a3b8; font-weight:700; letter-spacing:1px; font-size:11px; text-transform:uppercase;'>Área Total</span>
                            <span class='fw-bold' style='font-size:18px;'><i class='fas fa-ruler-combined me-2' style='color:#cbd5e1;'></i>" . htmlspecialchars($p['superficie']) . " m²</span>
                        </div>
                        <div>
                            <span class='d-block small' style='color:#94a3b8; font-weight:700; letter-spacing:1px; font-size:11px; text-transform:uppercase;'>Habitaciones</span>
                            <span class='fw-bold' style='font-size:18px;'><i class='fas fa-bed me-2' style='color:#818cf8;'></i>" . htmlspecialchars($p['dormitorios']) . "</span>
                        </div>
                        <div>
                            <span class='d-block small' style='color:#94a3b8; font-weight:700; letter-spacing:1px; font-size:11px; text-transform:uppercase;'>Baños</span>
                            <span class='fw-bold' style='font-size:18px;'><i class='fas fa-shower me-2' style='color:#818cf8;'></i>" . htmlspecialchars($p['banos']) . "</span>
                        </div>
                    </div>

                    <div class='p-3 mb-4 shadow-sm' style='background:#f1f5f9; border-radius:8px; color:#1e293b; font-size:15px;'>
                        <div class='mb-2'><i class='fas fa-map-pin me-2' style='color:#FF0066;'></i><strong>Ubicación:</strong> " . htmlspecialchars($p['ubicacion']) . "</div>
                        <div><i class='fas fa-building me-2' style='color:#94a3b8;'></i><strong>Comuna:</strong> " . htmlspecialchars($p['comuna']) . " <span class='mx-2' style='color:#cbd5e1;'>|</span> <strong>Sector:</strong> " . htmlspecialchars($p['sector']) . "</div>
                    </div>

                    <button class='btn w-100 fw-bold shadow-sm' style='background:#FF0066; color:white; padding:15px; border-radius:8px; text-transform:uppercase; font-size:16px; letter-spacing:1px;' data-bs-dismiss='modal'>Cotizar Ahora</button>
                </div>
            </div>
        </div>
    </div>";
}
?>