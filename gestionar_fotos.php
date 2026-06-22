<?php
session_start();
ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '50M');
ini_set('memory_limit', '128M');
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['usuario_id'])) { header("Location: index.html#login"); exit; }

require_once 'conexion.php';

// PARCHE DE AUTO-REPARACIÓN DE LA BASE DE DATOS
try {
    $pdo->query("SELECT galeria_fotos FROM propiedades LIMIT 1");
} catch (Exception $e) {
    try {
        $pdo->query("ALTER TABLE propiedades ADD COLUMN galeria_fotos TEXT NULL");
    } catch (Exception $ex) {}
}

$propiedad_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id_oculto']) ? (int)$_POST['id_oculto'] : 0);

$stmt = $pdo->prepare("SELECT * FROM propiedades WHERE id = ?");
$stmt->execute([$propiedad_id]);
$propiedad = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$propiedad) { die("Propiedad no encontrada en la base de datos."); }
$titulo_propiedad = htmlspecialchars($propiedad['tipo'] . ' en ' . $propiedad['comuna']);

$fotos_galeria = json_decode($propiedad['galeria_fotos'] ?? '[]', true);
if (!is_array($fotos_galeria) || empty($fotos_galeria)) {
    $fotos_galeria = !empty($propiedad['imagen_principal']) ? [$propiedad['imagen_principal']] : [];
}

$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva_principal = $_POST['nueva_principal'] ?? '';
    $fotos_activas = $_POST['fotos_activas'] ?? []; // Las que mantuvieron el ticket marcado
    $nuevas_subidas = [];

    // Procesar las imágenes nuevas optimizadas que vienen del motor de compresión
    if (isset($_FILES['nuevas_fotos']) && !empty($_FILES['nuevas_fotos']['name'][0])) {
        $total_nuevas = count($_FILES['nuevas_fotos']['name']);
        for ($i = 0; $i < $total_nuevas; $i++) {
            if ($_FILES['nuevas_fotos']['error'][$i] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['nuevas_fotos']['name'][$i], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    if (!is_dir('uploads')) { @mkdir('uploads', 0755, true); }
                    $nuevo_nombre = 'img_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['nuevas_fotos']['tmp_name'][$i], 'uploads/' . $nuevo_nombre)) {
                        $nuevas_subidas[] = $nuevo_nombre;
                    }
                }
            }
        }
    }

    // Unificar fotos que se quedaron + fotos nuevas
    $galeria_final = array_merge($fotos_activas, $nuevas_subidas);

    if (count($galeria_final) < 1) {
        $error_msg = "Debes mantener al menos 1 fotografía de la propiedad. No puedes borrarlas todas.";
    } elseif (count($galeria_final) > 10) {
        $error_msg = "El límite máximo es de 10 fotografías. Estás intentando guardar " . count($galeria_final) . " fotos en total.";
    } else {
        if (empty($nueva_principal) || !in_array($nueva_principal, $galeria_final)) {
            $nueva_principal = $galeria_final[0];
        }

        try {
            $galeria_json = json_encode(array_values($galeria_final));
            $stmtUpdate = $pdo->prepare("UPDATE propiedades SET imagen_principal = ?, galeria_fotos = ? WHERE id = ?");
            $resultado = $stmtUpdate->execute([$nueva_principal, $galeria_json, $propiedad_id]);
            
            if ($resultado) {
                header("Location: gestionar_fotos.php?id=$propiedad_id&msg=success");
                exit;
            } else {
                $error_msg = "Error interno al guardar los cambios.";
            }
        } catch (PDOException $e) {
            $error_msg = "Error SQL: " . $e->getMessage();
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'success') {
    $success_msg = "La galería y la portada fueron actualizadas correctamente.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Galería - PNK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f3f4f6; font-family: system-ui; }
        .top-navbar { background-color: #000000; height: 70px; display: flex; align-items: center; justify-content: space-between; padding: 0 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .top-navbar .logo { font-size: 22px; font-weight: 900; color: white; text-decoration: none; }
        .top-navbar .logo span { color: #FF0066; }
        .btn-volver { border: 1px solid #cbd5e1; color: white; padding: 7px 15px; border-radius: 4px; text-decoration: none; font-weight: bold; }
        .btn-volver:hover { background: #FF0066; border-color: #FF0066; color: white; }
        
        .foto-row { background: white; border: 1px solid #cbd5e1; margin-bottom: 20px; border-radius: 8px; display: flex; align-items: center; padding: 15px; gap: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .foto-img-container img { width: 150px; height: 100px; object-fit: cover; border-radius: 6px; }
        .foto-info { flex-grow: 1; font-weight: bold; font-size: 15px; color: #1e293b; }
        
        .foto-controles { display: flex; align-items: center; gap: 20px; padding-right: 15px; }
        .check-grande { width: 22px; height: 22px; accent-color: #FF0066; cursor: pointer; }
        .icon-star-btn { font-size: 26px; color: #cbd5e1; cursor: pointer; transition: 0.3s; }
        .icon-star-btn.activa { color: #f59e0b; transform: scale(1.2); }
        
        .caja-subida { background: white; border: 2px dashed #cbd5e1; padding: 25px; border-radius: 8px; margin-bottom: 30px; text-align: center; }
    </style>
</head>
<body>
    <div class="top-navbar">
        <a href="#" class="logo">PNK <span>Galería de Fotos</span></a>
        <?php 
            $rol = strtolower($_SESSION['usuario_rol'] ?? 'propietario');
            $link_volver = ($rol === 'admin' || $rol === 'administrador' || $rol === 'gestor' || $rol === 'gestor free') ? 'gestor.php' : 'propietario.php';
            if ($rol === 'admin' || $rol === 'administrador') $link_volver = 'admin.php?tab=propiedades';
        ?>
        <a href="<?php echo $link_volver; ?>" class="btn-volver">Volver al Panel</a>
    </div>

    <div class="container mt-5" style="max-width: 900px;">
        
        <div class="alert shadow-sm" style="background:#0f172a; color:#fff; border-left:4px solid #FF0066; border-radius:4px;">
            <i class="fas fa-info-circle me-2" style="color:#FF0066;"></i> 
            <strong>Instrucciones:</strong> Quita el ticket cuadrado para eliminar una foto. Usa la estrella dorada para elegir la portada principal. Puedes subir imágenes nuevas abajo y se sumarán al guardar.
        </div>

        <form id="formGallery" action="gestionar_fotos.php?id=<?php echo $propiedad_id; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_oculto" value="<?php echo $propiedad_id; ?>">

            <div class="caja-subida">
                <h5 style="color: #FF0066; font-weight: 800; margin-bottom: 15px;"><i class="fas fa-cloud-upload-alt me-2"></i>Añadir Fotografías Nuevas</h5>
                
                <div id="new-previews" style="display:flex; flex-wrap:wrap; gap:10px; justify-content:center; margin-bottom:15px;"></div>
                
                <input type="file" id="nuevas-fotos" name="nuevas_fotos[]" multiple accept="image/jpeg, image/png, image/webp" class="form-control w-75 mx-auto" style="border:1px solid #cbd5e1; cursor:pointer;">
                <p class="text-muted small mt-2 mb-0">Formatos permitidos: JPG, PNG, WEBP. (Máximo 10 fotos totales en la propiedad).</p>
            </div>

            <h4 class="mb-4 fw-bold" style="color:#1e293b;">Fotos de la Propiedad</h4>
            
            <?php foreach ($fotos_galeria as $index => $nombre_foto): 
                $isPrincipal = ($nombre_foto === $propiedad['imagen_principal']);
            ?>
            <div class="foto-row" id="card_<?php echo $index; ?>">
                <div class="foto-img-container">
                    <img src="uploads/<?php echo htmlspecialchars($nombre_foto); ?>" onerror="this.src='img/pnkpnk.png'">
                </div>
                <div class="foto-info">
                    <?php echo $titulo_propiedad; ?><br>
                    <span class="badge bg-light text-dark border mt-1"><i class="fas fa-image me-1 text-secondary"></i><?php echo htmlspecialchars($nombre_foto); ?></span>
                </div>
                <div class="foto-controles">
                    <div class="text-center">
                        <label class="d-block small text-muted fw-bold mb-1">Mantener</label>
                        <input class="check-grande" type="checkbox" name="fotos_activas[]" value="<?php echo htmlspecialchars($nombre_foto); ?>" checked id="check_<?php echo $index; ?>" onchange="forzarCheck(<?php echo $index; ?>)">
                    </div>
                    
                    <div class="text-center ms-3">
                        <label class="d-block small text-muted fw-bold mb-1">Portada</label>
                        <label for="radio_<?php echo $index; ?>" style="margin:0; cursor:pointer;">
                            <input type="radio" name="nueva_principal" value="<?php echo htmlspecialchars($nombre_foto); ?>" <?php echo $isPrincipal ? 'checked' : ''; ?> id="radio_<?php echo $index; ?>" style="display:none;" onchange="actualizarEstrellas(<?php echo $index; ?>)">
                            <i id="star_<?php echo $index; ?>" class="fas fa-star icon-star-btn <?php echo $isPrincipal ? 'activa' : ''; ?>"></i>
                        </label>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="text-end mt-4 mb-5">
                <button type="submit" class="btn fw-bold shadow-sm" style="background:#FF0066; color:white; border:none; padding:15px 40px; font-size:16px;">
                    <i class="fas fa-save me-2"></i>Guardar Cambios de Galería
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            <?php if ($error_msg): ?>
                Swal.fire('Atención', '<?php echo addslashes($error_msg); ?>', 'warning');
            <?php endif; ?>
            
            <?php if ($success_msg): ?>
                Swal.fire('¡Éxito!', '<?php echo addslashes($success_msg); ?>', 'success').then(() => {
                    window.history.replaceState({}, document.title, window.location.pathname + "?id=<?php echo $propiedad_id; ?>");
                });
            <?php endif; ?>
        });

        // PREVISUALIZADOR EN VIVO PARA FOTOS NUEVAS EN LA GALERÍA
        document.getElementById('nuevas-fotos').addEventListener('change', function(event) {
            const files = event.target.files;
            const previewContainer = document.getElementById('new-previews');
            const fotosExistentes = document.querySelectorAll('.foto-row').length;
            
            if (files.length + fotosExistentes > 10) {
                Swal.fire('Límite excedido', `Esta propiedad ya tiene ${fotosExistentes} fotos. No puedes añadir ${files.length} más (Máximo 10 en total).`, 'error');
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
                    img.style.width = '70px'; img.style.height = '50px'; img.style.objectFit = 'cover';
                    img.style.borderRadius = '4px'; img.style.border = '2px solid #FF0066';
                    previewContainer.appendChild(img);
                }
                reader.readAsDataURL(file);
            });
        });

        // CONTROL SINCRO DE ESTRELLAS CON IDENTIFICADORES CORREGIDOS
        function actualizarEstrellas(selectedIndex) {
            const total = document.querySelectorAll('.foto-row').length;
            for(let i = 0; i < total; i++) {
                let star = document.getElementById(`star_${i}`);
                let check = document.getElementById(`check_${i}`);
                
                if(star && check) {
                    if(i === selectedIndex) {
                        star.classList.add('activa');
                        check.checked = true; 
                    } else {
                        star.classList.remove('activa');
                    }
                }
            }
        }

        function forzarCheck(index) {
            let radio = document.getElementById(`radio_${index}`);
            let check = document.getElementById(`check_${index}`);
            if (radio && radio.checked && !check.checked) {
                check.checked = true; 
                Swal.fire('Operación no permitida', 'La foto designada como portada principal no puede ser eliminada. Selecciona otra estrella primero.', 'info');
            }
        }

        // MOTOR DE COMPRESIÓN DEL NAVEGADOR ANTES DE ENVIAR A AWS
        async function compressImage(file, maxWidth, quality) {
            return new Promise((resolve) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = (event) => {
                    const img = new Image();
                    img.src = event.target.result;
                    img.onload = () => {
                        const canvas = document.createElement('canvas');
                        let width = img.width; let height = img.height;
                        if (width > maxWidth) {
                            height = Math.round((height * maxWidth) / width); width = maxWidth;
                        }
                        canvas.width = width; canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);
                        canvas.toBlob((blob) => {
                            resolve(new File([blob], file.name, { type: 'image/jpeg', lastModified: Date.now() }));
                        }, 'image/jpeg', quality);
                    };
                };
            });
        }

        const formGallery = document.getElementById('formGallery');
        if(formGallery) {
            formGallery.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const files = document.getElementById('nuevas-fotos').files;
                const checksMarcados = document.querySelectorAll('input[name="fotos_activas[]"]:checked').length;

                if (files.length + checksMarcados < 1) {
                    Swal.fire('Galería vacía', 'Debes mantener al menos 1 foto activa en la propiedad.', 'warning');
                    return;
                }

                if (files.length > 0) {
                    Swal.fire({
                        title: 'Optimizando e Incorporando...',
                        text: 'Procesando las nuevas imágenes para el servidor. Espera un momento.',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    const dataTransfer = new DataTransfer();
                    for (let i = 0; i < files.length; i++) {
                        const file = files[i];
                        if (file.type.startsWith('image/')) {
                            const compressedFile = await compressImage(file, 1200, 0.7);
                            dataTransfer.items.add(compressedFile);
                        } else {
                            dataTransfer.items.add(file);
                        }
                    }
                    document.getElementById('nuevas-fotos').files = dataTransfer.files;
                } else {
                    Swal.fire({ title: 'Guardando cambios...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                }
                
                this.submit();
            });
        }
    </script>
</body>
</html>