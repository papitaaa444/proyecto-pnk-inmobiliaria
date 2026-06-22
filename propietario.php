<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// BÚSQUEDA SEGURA DE LA CONEXIÓN
if (file_exists(__DIR__ . '/conexion.php')) {
    require_once __DIR__ . '/conexion.php';
} elseif (file_exists(__DIR__ . '/../conexion.php')) {
    require_once __DIR__ . '/../conexion.php';
} else {
    die("<h2 style='text-align:center; color:red; margin-top:50px;'>Error Crítico: No se encuentra conexion.php</h2>");
}

// PUENTE DE SESIÓN
if (!isset($_SESSION['usuario_id']) && isset($_SESSION['user_id'])) {
    $_SESSION['usuario_id'] = $_SESSION['user_id'];
}
if (!isset($_SESSION['usuario_nombre']) && isset($_SESSION['nombre'])) {
    $_SESSION['usuario_nombre'] = $_SESSION['nombre'];
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// AUTO-PARCHE DE BASE DE DATOS
try { $pdo->query("ALTER TABLE propiedades ADD COLUMN region VARCHAR(100) NULL AFTER tipo"); } catch(Exception $e){}
try { $pdo->query("ALTER TABLE propiedades ADD COLUMN piscina INT DEFAULT 0"); } catch(Exception $e){}
try { $pdo->query("ALTER TABLE propiedades ADD COLUMN estacionamiento INT DEFAULT 0"); } catch(Exception $e){}
try { $pdo->query("ALTER TABLE propiedades ADD COLUMN galeria_fotos TEXT NULL"); } catch(Exception $e){}

// ESCUDO PROTECTOR DE AWS (CORREGIDO EL MENSAJE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    header("Location: propietario.php?msg=error_peso");
    exit;
}

// ACCIONES DIRECTAS (ELIMINAR)
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($action === 'delete_prop' && $id > 0) {
        $stmt = $pdo->prepare("DELETE FROM propiedades WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$id, $usuario_id]);
        header("Location: propietario.php?msg=prop_deleted");
        exit;
    }
}

// PROCESAR FORMULARIO (CREAR O EDITAR)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['form_action']) && $_GET['form_action'] === 'save_prop') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    $codigo = trim($_POST['codigo'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $comuna = trim($_POST['comuna'] ?? '');
    $sector = trim($_POST['sector'] ?? ''); 
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $precio = (int)($_POST['precio'] ?? 0);
    $dormitorios = (int)($_POST['dormitorios'] ?? 0);
    $banos = (int)($_POST['banos'] ?? 0);
    $superficie = (int)($_POST['superficie'] ?? 0);
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    $piscina = isset($_POST['piscina']) ? 1 : 0;
    $estacionamiento = isset($_POST['estacionamiento']) ? 1 : 0;

    // PROCESAMIENTO MÚLTIPLE DE IMÁGENES
    $fotos_subidas = [];
    if (isset($_FILES['prop_foto']) && !empty($_FILES['prop_foto']['name'][0])) {
        $total_archivos = count($_FILES['prop_foto']['name']);
        for ($i = 0; $i < $total_archivos; $i++) {
            if ($_FILES['prop_foto']['error'][$i] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['prop_foto']['name'][$i], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    if (!is_dir('uploads')) { @mkdir('uploads', 0755, true); }
                    $nombre_foto = 'img_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['prop_foto']['tmp_name'][$i], 'uploads/' . $nombre_foto)) {
                        $fotos_subidas[] = $nombre_foto;
                    }
                }
            }
        }
    }

    if ($id > 0) {
        // MODO EDICIÓN
        $stmt_curr = $pdo->prepare("SELECT galeria_fotos, imagen_principal FROM propiedades WHERE id=? AND usuario_id=?");
        $stmt_curr->execute([$id, $usuario_id]);
        $curr = $stmt_curr->fetch(PDO::FETCH_ASSOC);
        
        $galeria_actual = json_decode($curr['galeria_fotos'] ?? '[]', true);
        if (!is_array($galeria_actual)) $galeria_actual = [];
        
        $galeria_final = array_merge($galeria_actual, $fotos_subidas);
        $galeria_final = array_slice($galeria_final, 0, 10); 
        
        $imagen_principal = !empty($galeria_final) ? $galeria_final[0] : 'default.jpg';
        $galeria_json = json_encode(array_values($galeria_final));

        $stmt = $pdo->prepare("UPDATE propiedades SET codigo=?, tipo=?, region=?, comuna=?, sector=?, ubicacion=?, precio=?, dormitorios=?, banos=?, superficie=?, descripcion=?, piscina=?, estacionamiento=?, imagen_principal=?, galeria_fotos=? WHERE id=? AND usuario_id=?");
        $stmt->execute([$codigo, $tipo, $region, $comuna, $sector, $ubicacion, $precio, $dormitorios, $banos, $superficie, $descripcion, $piscina, $estacionamiento, $imagen_principal, $galeria_json, $id, $usuario_id]);

    } else {
        // MODO CREACIÓN
        $galeria_final = array_slice($fotos_subidas, 0, 10);
        $imagen_principal = !empty($galeria_final) ? $galeria_final[0] : 'default.jpg';
        $galeria_json = json_encode(array_values($galeria_final));

        $stmt = $pdo->prepare("INSERT INTO propiedades (codigo, tipo, region, comuna, sector, ubicacion, precio, dormitorios, banos, superficie, descripcion, piscina, estacionamiento, imagen_principal, galeria_fotos, estado, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'publicada', ?)");
        $stmt->execute([$codigo, $tipo, $region, $comuna, $sector, $ubicacion, $precio, $dormitorios, $banos, $superficie, $descripcion, $piscina, $estacionamiento, $imagen_principal, $galeria_json, $usuario_id]);
    }
    header("Location: propietario.php?msg=prop_success");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM propiedades WHERE usuario_id = ? ORDER BY id DESC");
    $stmt->execute([$usuario_id]);
    $mis_propiedades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mis_propiedades = []; 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Propietario - PNK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f3f4f6; font-family: system-ui, sans-serif; color: #374151; overflow-x: hidden; }
        .top-navbar { background-color: #000000; height: 70px; display: flex; justify-content: space-between; align-items: center; padding: 0 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .top-navbar .logo { font-size: 22px; font-weight: 900; color: white; text-decoration: none; margin: 0; }
        .top-navbar .logo span { color: #FF0066; }
        .user-info { color: white; font-size: 14px; display: flex; align-items: center; gap: 15px; }
        .btn-pnk { background-color: #FF0066; color: white; font-weight: bold; border-radius: 6px; padding: 8px 20px; text-decoration: none; border: none; cursor:pointer; }
        .btn-pnk:hover { background-color: #cc0052; color: white; }
        .btn-salir { background-color: transparent; border: 1px solid #e5e7eb; color: white; padding: 6px 15px; border-radius: 4px; text-decoration: none; font-size: 13px; transition: 0.3s; }
        .btn-salir:hover { background-color: #ef4444; border-color: #ef4444; color:white; }
        
        .card-tabla { background: #ffffff; border-radius: 8px; border: 1px solid #e5e7eb; margin: 30px auto; max-width: 1200px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .card-header-tabla { background: #f8fafc; border-bottom: 1px solid #e5e7eb; padding: 15px 20px; font-weight: bold; color: #4b5563; font-size: 14px; text-transform: uppercase; }
        .table th { border-bottom: 2px solid #e5e7eb; color: #000000; font-weight: 800; text-transform: uppercase; font-size: 13px; padding: 15px; }
        .table td { padding: 15px; font-weight: 600; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        .img-miniatura { width: 70px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #d1d5db; }
        
        .acciones-td { display: flex; gap: 10px; justify-content: center; align-items: center; }
        .btn-accion { padding: 8px 12px; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; text-decoration: none; border: 1px solid transparent; }
        .btn-accion-fotos { background: #f1f5f9; color: #3b82f6; border-color: #cbd5e1; }
        .btn-accion-editar { background: #fef3c7; color: #d97706; border-color: #fde68a; }
        .btn-accion-borrar { background: #fee2e2; color: #ef4444; border-color: #fecaca; }

        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: none; justify-content: center; align-items: center; z-index: 1000; padding:20px; }
        .modal-box { background: white; padding: 35px; border-radius: 16px; width: 100%; max-width: 700px; position: relative; max-height: 90vh; overflow-y: auto; }
        .modal-close-btn { position: absolute; top: 15px; right: 20px; font-size: 32px; cursor: pointer; color: #9ca3af; line-height: 1; z-index: 10; background: white; border-radius: 50%; padding: 0 5px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
        .form-group label { font-size: 13px; font-weight: 700; color:#374151; }
        .form-group input:not([type="file"]), .form-group select, .form-group textarea { padding: 11px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; }
        input[readonly], select[readonly], input:disabled, select:disabled { background-color: #f1f5f9; color: #94a3b8; cursor: not-allowed; }
        
        .caja-extras { background: #f8fafc; border: 1px dashed #cbd5e1; padding: 15px; border-radius: 8px; grid-column: 1 / -1; display: none; }
        .checkbox-container { display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; color: #0f172a; }
        .checkbox-container input { width: 18px; height: 18px; accent-color: #FF0066; cursor: pointer; }
    </style>
</head>
<body>
    <div class="top-navbar">
        <a href="#" class="logo">PNK <span>Panel Propietario</span></a>
        <div class="user-info">
            <span><i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></span>
            <button class="btn-pnk" onclick="openPropModal()"><i class="fas fa-plus me-1"></i> Subir Propiedad</button>
            <a href="index.html" class="btn-salir">Cerrar Sesión</a>
        </div>
    </div>

    <div class="card-tabla">
        <div class="card-header-tabla">MIS PROPIEDADES SUBIDAS</div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Cód.</th>
                        <th>Foto</th>
                        <th>Propiedad</th>
                        <th>Ubicación</th>
                        <th>Valor</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mis_propiedades)): ?>
                    <tr><td colspan="6" class="text-center py-5" style="color:#6b7280;">Aún no tienes propiedades publicadas. Haz clic en "Subir Propiedad" para comenzar.</td></tr>
                    <?php else: ?>
                        <?php foreach ($mis_propiedades as $p): ?>
                        <tr>
                            <td><span style="background:#000; color:#fff; padding:4px 8px; border-radius:4px; font-weight:bold; font-size:12px;"><?php echo htmlspecialchars($p['codigo']); ?></span></td>
                            <td><img src="uploads/<?php echo htmlspecialchars($p['imagen_principal'] ?? 'default.jpg'); ?>" class="img-miniatura" onerror="this.src='img/pnkpnk.png'"></td>
                            <td style="text-transform: capitalize; color:#FF0066;"><?php echo htmlspecialchars($p['tipo']); ?></td>
                            <td><?php echo htmlspecialchars($p['comuna']); ?><br><small style="color:#6b7280;"><?php echo htmlspecialchars($p['sector'] ?? ''); ?></small></td>
                            <td>$ <?php echo number_format($p['precio'] ?? 0, 0, ',', '.'); ?></td>
                            <td class="acciones-td">
                                <a href="gestionar_fotos.php?id=<?php echo $p['id']; ?>" class="btn-accion btn-accion-fotos"><i class="fa-solid fa-images"></i> Fotos</a>
                                <button class="btn-accion btn-accion-editar" onclick='editProp(<?php echo htmlspecialchars(json_encode($p), ENT_QUOTES, "UTF-8"); ?>)'><i class="fas fa-edit"></i> Editar</button>
                                <a href="propietario.php?action=delete_prop&id=<?php echo $p['id']; ?>" class="btn-accion btn-accion-borrar" onclick="return confirmarBorrado(event, this.href);"><i class="fas fa-trash"></i> Eliminar</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal-overlay" id="propModal">
        <div class="modal-box">
            <span class="modal-close-btn" onclick="closePropModal()">×</span>
            <h3 style="font-weight:900; color:#1f2937; margin-bottom:20px; font-size:20px;" id="propModalTitle">Ficha de Propiedad</h3>
            <form id="formOwnerProp" action="propietario.php?form_action=save_prop" method="POST" enctype="multipart/form-data">
                <input type="hidden" id="form-prop-id" name="id">
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="form-group"><label>Código Referencia *</label><input type="text" id="form-prop-codigo" name="codigo" placeholder="Ej: REF-100"></div>
                    <div class="form-group">
                        <label>Tipo Unidad *</label>
                        <select id="form-prop-tipo" name="tipo">
                            <option value="">Seleccione...</option>
                            <option value="casa">Casa</option>
                            <option value="departamento">Departamento</option>
                            <option value="oficina">Oficina</option>
                            <option value="terreno">Terreno</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Región *</label>
                        <select id="form-prop-region" name="region">
                            <option value="">Seleccione Región...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Comuna *</label>
                        <select id="form-prop-comuna" name="comuna" disabled>
                            <option value="">Seleccione Comuna...</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Sector / Barrio *</label>
                        <input type="text" id="form-prop-sector" name="sector" list="lista-sectores" placeholder="Primero seleccione Comuna" disabled>
                        <datalist id="lista-sectores"></datalist>
                    </div>

                    <div class="form-group"><label>Dirección Física Completa *</label><input type="text" id="form-prop-ubicacion" name="ubicacion" placeholder="Ej: Av. del Mar 1234"></div>
                    
                    <div class="form-group"><label>Valor Propiedad ($) *</label><input type="number" id="form-prop-precio" name="precio"></div>
                    <div class="form-group"><label>Superficie (m²) *</label><input type="number" id="form-prop-superficie" name="superficie"></div>
                    
                    <div class="form-group"><label>Dormitorios</label><input type="number" id="form-prop-dormitorios" name="dormitorios"></div>
                    <div class="form-group"><label>Baños</label><input type="number" id="form-prop-banos" name="banos"></div>

                    <div class="caja-extras" id="caja-extras">
                        <label style="color: #0f172a; font-weight: bold; margin-bottom: 10px; display:block;">⭐ Amenidades Adicionales</label>
                        <div style="display:flex; gap:20px;">
                            <label class="checkbox-container"><input type="checkbox" id="form-prop-piscina" name="piscina"> Piscina Privada / Condominio</label>
                            <label class="checkbox-container"><input type="checkbox" id="form-prop-estacionamiento" name="estacionamiento"> Estacionamiento</label>
                        </div>
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;"><label>Descripción *</label><textarea id="form-prop-descripcion" name="descripcion" rows="3"></textarea></div>
                    
                    <div class="form-group" style="grid-column: 1 / -1; background:#f8fafc; padding:20px; border-radius:8px; border: 1px dashed #cbd5e1; text-align: center;">
                        <label style="color: #FF0066; font-weight: bold; margin-bottom: 15px; display: block;">📸 Fotografías (Máximo 10 fotos)</label>
                        
                        <div id="image-previews" style="display:flex; flex-wrap:wrap; gap:10px; justify-content:center; margin-bottom:15px;"></div>
                        
                        <input type="file" id="prop-foto" name="prop_foto[]" class="form-control w-75 mx-auto" multiple accept="image/jpeg, image/png, image/webp" style="cursor: pointer;">
                        <small style="color:#94a3b8; display:block; margin-top:10px;">Selecciona hasta 10 archivos. El sistema las optimizará automáticamente para que el servidor no te bloquee.</small>
                    </div>
                </div>
                
                <button type="submit" class="btn-pnk" style="width:100%; margin-top:15px; padding:15px; font-size:16px;">Guardar Propiedad</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            const msg = urlParams.get('msg');
            
            if (msg === 'prop_success') { Swal.fire('¡Éxito!', 'Tu propiedad y fotografías fueron guardadas correctamente.', 'success'); }
            if (msg === 'prop_deleted') { Swal.fire('Eliminada', 'La propiedad ha sido eliminada.', 'info'); }
            
            // CORREGIDO: Este era el mensaje que te confundió. Ahora dice la verdad.
            if (msg === 'error_peso') { 
                Swal.fire('Error de Conexión', 'El servidor AWS botó la conexión porque el envío era demasiado pesado. No se guardó NADA. La compresión del navegador resolverá esto ahora.', 'error'); 
            }
            
            if(msg) { window.history.replaceState({}, document.title, window.location.pathname); }
        });

        // PREVISUALIZADOR
        document.getElementById('prop-foto').addEventListener('change', function(event) {
            const files = event.target.files;
            const previewContainer = document.getElementById('image-previews');
            
            if (files.length > 10) {
                Swal.fire('Límite excedido', 'Has superado el límite de 10 fotos permitidas por la rúbrica.', 'error');
                event.target.value = '';
                previewContainer.innerHTML = '';
                return;
            }

            previewContainer.innerHTML = '';
            Array.from(files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.width = '80px'; img.style.height = '80px'; img.style.objectFit = 'cover';
                    img.style.borderRadius = '4px'; img.style.border = '2px solid #FF0066';
                    previewContainer.appendChild(img);
                }
                reader.readAsDataURL(file);
            });
        });

        // BASE DE DATOS GEOGRÁFICA
        const chileData = {
            "Arica y Parinacota": ["Arica", "Camarones", "Putre", "General Lagos"],
            "Tarapacá": ["Iquique", "Alto Hospicio", "Pozo Almonte", "Camiña", "Colchane", "Huara", "Pica"],
            "Antofagasta": ["Antofagasta", "Mejillones", "Sierra Gorda", "Taltal", "Calama", "Ollagüe", "San Pedro de Atacama", "Tocopilla", "María Elena"],
            "Atacama": ["Copiapó", "Caldera", "Tierra Amarilla", "Chañaral", "Diego de Almagro", "Vallenar", "Alto del Carmen", "Freirina", "Huasco"],
            "Coquimbo": ["La Serena", "Coquimbo", "Andacollo", "La Higuera", "Paihuano", "Vicuña", "Illapel", "Los Vilos", "Canela", "Salamanca", "Ovalle", "Combarbalá", "Monte Patria", "Punitaqui", "Río Hurtado"],
            "Valparaíso": ["Valparaíso", "Casablanca", "Concón", "Juan Fernández", "Puchuncaví", "Quintero", "Viña del Mar", "Isla de Pascua", "Los Andes", "Cabildo", "La Calera", "La Ligua", "Papudo", "Petorca", "Zapallar", "Hijuelas", "La Cruz", "Nogales", "Quillota", "San Antonio", "San Felipe", "Quilpué", "Villa Alemana"],
            "Metropolitana de Santiago": ["Cerrillos", "Cerro Navia", "Conchalí", "El Bosque", "Estación Central", "Huechuraba", "Independencia", "La Cisterna", "La Florida", "La Granja", "La Pintana", "La Reina", "Las Condes", "Lo Barnechea", "Lo Espejo", "Lo Prado", "Macul", "Maipú", "Ñuñoa", "Pedro Aguirre Cerda", "Peñalolén", "Providencia", "Pudahuel", "Quilicura", "Quinta Normal", "Recoleta", "Renca", "Santiago", "San Joaquín", "San Miguel", "San Ramón", "Vitacura", "Puente Alto", "San Bernardo", "Colina", "Lampa", "Tiltil", "Paine", "Melipilla", "Buin"],
            "Libertador Gral. Bernardo O'Higgins": ["Rancagua", "Machalí", "Graneros", "San Fernando", "Rengo", "Pichilemu", "Santa Cruz"],
            "Maule": ["Talca", "Curicó", "Linares", "Cauquenes", "Constitución", "Molina", "San Javier"],
            "Ñuble": ["Chillán", "San Carlos", "Bulnes", "Quillón", "Coihueco"],
            "Biobío": ["Concepción", "Talcahuano", "San Pedro de la Paz", "Chiguayante", "Los Ángeles", "Coronel", "Tomé", "Penco"],
            "La Araucanía": ["Temuco", "Padre Las Casas", "Villarrica", "Pucón", "Angol", "Victoria"],
            "Los Ríos": ["Valdivia", "La Unión", "Panguipulli", "Río Bueno"],
            "Los Lagos": ["Puerto Montt", "Puerto Varas", "Osorno", "Castro", "Ancud", "Frutillar"],
            "Aysén": ["Coyhaique", "Puerto Aysén", "Chile Chico", "Cochrane"],
            "Magallanes y de la Antártica Chilena": ["Punta Arenas", "Puerto Natales", "Porvenir"]
        };

        const sectoresEspecificos = {
            'Arica': ['Centro', 'Chinchorro', 'Azapa'],
            'Iquique': ['Cavancha', 'Centro', 'Playa Brava'],
            'Antofagasta': ['Centro', 'Sector Sur', 'Sector Norte'],
            'La Serena': ['Cerro Oriente', 'Avenida del Mar', 'Centro', 'San Joaquín', 'Puertas del Mar', 'La Florida', 'Las Compañías', 'El Milagro'],
            'Coquimbo': ['San Juan', 'Herradura', 'Sindempart', 'Peñuelas', 'Centro', 'Tierras Blancas', 'Punta Mira', 'El Llano', 'La Cantera'],
            'Ovalle': ['Centro', 'Valle Limarí', 'Tuquí', 'San Julián', 'Romeral', 'Los Peñones', 'Sotaquí', 'Huamalata', 'Parte Alta'],
            'Valparaíso': ['Cerro Alegre', 'Cerro Concepción', 'Playa Ancha'],
            'Viña del Mar': ['Reñaca', 'Gómez Carreño', 'Miraflores', 'Centro'],
            'Santiago': ['Centro', 'Lastarria', 'Barrio Brasil'],
            'Concepción': ['Centro', 'Barrio Universitario', 'Collao']
        };

        const selectRegion = document.getElementById('form-prop-region');
        const selectComuna = document.getElementById('form-prop-comuna');
        const inputSector = document.getElementById('form-prop-sector');
        const dataListSectores = document.getElementById('lista-sectores');

        for (let region in chileData) {
            let option = document.createElement("option"); option.value = region; option.text = region; selectRegion.add(option);
        }

        selectRegion.addEventListener('change', function() {
            selectComuna.innerHTML = '<option value="">Seleccione Comuna...</option>';
            if (this.value !== "") {
                selectComuna.disabled = false;
                chileData[this.value].forEach(comuna => {
                    let option = document.createElement("option"); option.value = comuna; option.text = comuna; selectComuna.add(option);
                });
            } else { selectComuna.disabled = true; }
            selectComuna.dispatchEvent(new Event('change'));
        });

        selectComuna.addEventListener('change', function() {
            const comunaElegida = this.value;
            dataListSectores.innerHTML = ''; inputSector.value = ''; 

            if (comunaElegida !== "") {
                inputSector.disabled = false;
                inputSector.placeholder = `Escriba o elija sector en ${comunaElegida}...`;
                if (sectoresEspecificos[comunaElegida]) {
                    sectoresEspecificos[comunaElegida].forEach(s => {
                        let option = document.createElement('option'); option.value = s; dataListSectores.appendChild(option);
                    });
                }
            } else {
                inputSector.disabled = true; inputSector.placeholder = 'Primero seleccione Comuna';
            }
        });

        const selectTipo = document.getElementById('form-prop-tipo');
        const inputDor = document.getElementById('form-prop-dormitorios');
        const inputBan = document.getElementById('form-prop-banos');
        const cajaExtras = document.getElementById('caja-extras');

        selectTipo.addEventListener('change', function() {
            const tipo = this.value;
            inputDor.readOnly = false; inputBan.readOnly = false; cajaExtras.style.display = 'none';

            if(tipo === 'terreno') {
                inputDor.value = 0; inputDor.readOnly = true; inputBan.value = 0; inputBan.readOnly = true;
            } else if (tipo === 'oficina') {
                inputDor.value = 0; inputDor.readOnly = true; if(inputBan.value == 0) inputBan.value = '';
            } else if (tipo === 'casa' || tipo === 'departamento') {
                if(inputDor.value == 0) inputDor.value = ''; if(inputBan.value == 0) inputBan.value = ''; cajaExtras.style.display = 'block'; 
            }
        });

        function openPropModal() {
            document.getElementById('form-prop-id').value = ''; document.getElementById('form-prop-codigo').value = '';
            document.getElementById('form-prop-tipo').value = ''; selectRegion.value = '';
            selectComuna.innerHTML = '<option value="">Seleccione Comuna...</option>'; selectComuna.disabled = true;
            inputSector.value = ''; inputSector.disabled = true; inputSector.placeholder = 'Primero seleccione Comuna';
            document.getElementById('form-prop-ubicacion').value = ''; document.getElementById('form-prop-precio').value = '';
            document.getElementById('form-prop-superficie').value = ''; inputDor.value = ''; inputDor.readOnly = false;
            inputBan.value = ''; inputBan.readOnly = false; document.getElementById('form-prop-piscina').checked = false;
            document.getElementById('form-prop-estacionamiento').checked = false; cajaExtras.style.display = 'none';
            document.getElementById('form-prop-descripcion').value = '';
            document.getElementById('prop-foto').value = ''; document.getElementById('image-previews').innerHTML = '';
            document.getElementById('propModal').style.display = 'flex';
        }
        
        function closePropModal() { document.getElementById('propModal').style.display = 'none'; }
        
        function editProp(p) {
            document.getElementById('form-prop-id').value = p.id; document.getElementById('form-prop-codigo').value = p.codigo;
            const tipo = p.tipo.toLowerCase(); selectTipo.value = tipo;
            
            if (p.region) {
                selectRegion.value = p.region; selectRegion.dispatchEvent(new Event('change'));
                selectComuna.value = p.comuna; selectComuna.dispatchEvent(new Event('change'));
            }
            
            inputSector.value = p.sector; document.getElementById('form-prop-ubicacion').value = p.ubicacion; 
            document.getElementById('form-prop-precio').value = p.precio; document.getElementById('form-prop-superficie').value = p.superficie;
            
            if (tipo === 'terreno') {
                inputDor.value = 0; inputDor.readOnly = true; inputBan.value = 0; inputBan.readOnly = true; cajaExtras.style.display = 'none';
            } else if (tipo === 'oficina') {
                inputDor.value = 0; inputDor.readOnly = true; inputBan.value = p.banos; inputBan.readOnly = false; cajaExtras.style.display = 'none';
            } else {
                inputDor.value = p.dormitorios; inputDor.readOnly = false; inputBan.value = p.banos; inputBan.readOnly = false; cajaExtras.style.display = 'block';
            }

            document.getElementById('form-prop-piscina').checked = (p.piscina == 1);
            document.getElementById('form-prop-estacionamiento').checked = (p.estacionamiento == 1);
            document.getElementById('form-prop-descripcion').value = p.descripcion ? p.descripcion : '';

            const previewContainer = document.getElementById('image-previews');
            previewContainer.innerHTML = ''; document.getElementById('prop-foto').value = '';
            let galeriaActual = []; try { galeriaActual = JSON.parse(p.galeria_fotos || '[]'); } catch(e){}
            if (galeriaActual.length === 0 && p.imagen_principal && p.imagen_principal !== 'default.jpg') { galeriaActual.push(p.imagen_principal); }
            galeriaActual.forEach(foto => {
                let rutaFoto = foto.startsWith('http') ? foto : 'uploads/' + foto;
                const img = document.createElement('img'); img.src = rutaFoto;
                img.style.width = '80px'; img.style.height = '80px'; img.style.objectFit = 'cover';
                img.style.borderRadius = '4px'; img.style.border = '1px solid #cbd5e1';
                previewContainer.appendChild(img);
            });
            document.getElementById('propModal').style.display = 'flex';
        }

        function confirmarBorrado(e, url) {
            e.preventDefault();
            Swal.fire({
                title: '¿Eliminar propiedad?',
                text: "Esta acción no se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => { if (result.isConfirmed) { window.location.href = url; } });
        }

        // ==========================================
        // MOTOR DE COMPRESIÓN EN VIVO (BYPASS PARA AWS)
        // ==========================================
        
        async function compressImage(file, maxWidth, quality) {
            return new Promise((resolve) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = (event) => {
                    const img = new Image();
                    img.src = event.target.result;
                    img.onload = () => {
                        const canvas = document.createElement('canvas');
                        let width = img.width;
                        let height = img.height;
                        if (width > maxWidth) {
                            height = Math.round((height * maxWidth) / width);
                            width = maxWidth;
                        }
                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);
                        canvas.toBlob((blob) => {
                            resolve(new File([blob], file.name, { type: 'image/jpeg', lastModified: Date.now() }));
                        }, 'image/jpeg', quality);
                    };
                };
            });
        }

        const formOwnerProp = document.getElementById('formOwnerProp');
        if(formOwnerProp) {
            formOwnerProp.addEventListener('submit', async function(e) {
                e.preventDefault(); 
                
                const files = document.getElementById('prop-foto').files;
                if (files.length > 10) {
                    Swal.fire('Límite excedido', 'Has seleccionado más de 10 imágenes. Reduce la cantidad.', 'error');
                    return;
                }

                const codigo = document.getElementById('form-prop-codigo').value.trim();
                const region = document.getElementById('form-prop-region').value;
                const comuna = document.getElementById('form-prop-comuna').value;
                const sectorVal = inputSector.value.trim();
                const ubicacion = document.getElementById('form-prop-ubicacion').value.trim();
                const descripcion = document.getElementById('form-prop-descripcion').value.trim();
                const precio = parseInt(document.getElementById('form-prop-precio').value);
                const superficie = parseInt(document.getElementById('form-prop-superficie').value);
                const dor = parseInt(inputDor.value);
                const ban = parseInt(inputBan.value);
                const tipo = selectTipo.value;

                if(!tipo || !codigo || !region || !comuna || !sectorVal || !ubicacion || !descripcion) {
                    Swal.fire('Campos Incompletos', 'Por favor completa todos los campos marcados con (*).', 'warning');
                    return;
                }

                if(ubicacion.length < 8) {
                    Swal.fire('Dirección Inválida', 'La dirección física es demasiado corta. Escribe la dirección real.', 'error');
                    return;
                }

                if(isNaN(precio) || precio <= 0) {
                    Swal.fire('Valor Inválido', 'El precio debe ser un número mayor a cero.', 'error');
                    return;
                }

                // SI HAY FOTOS, LAS COMPRIMIMOS ANTES DE MANDAR A AWS
                if (files.length > 0) {
                    Swal.fire({
                        title: 'Optimizando Imágenes...',
                        text: 'Preparando tus fotos para el servidor AWS. Esto puede tomar unos segundos.',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    const dataTransfer = new DataTransfer();
                    for (let i = 0; i < files.length; i++) {
                        const file = files[i];
                        // Solo comprimimos si es imagen
                        if (file.type.startsWith('image/')) {
                            const compressedFile = await compressImage(file, 1200, 0.7); // 1200px y 70% calidad
                            dataTransfer.items.add(compressedFile);
                        } else {
                            dataTransfer.items.add(file);
                        }
                    }
                    document.getElementById('prop-foto').files = dataTransfer.files;
                } else {
                    Swal.fire({
                        title: 'Guardando...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });
                }
                
                this.submit();
            });
        }
    </script>
</body>
</html>