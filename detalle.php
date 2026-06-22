<?php
require_once 'backend/conexion.php';

// Obtener el ID de la propiedad que se quiere ver
$id_propiedad = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM propiedades WHERE id = ?");
$stmt->execute([$id_propiedad]);
$propiedad = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$propiedad) {
    die("<div style='text-align:center; padding: 50px; font-family: sans-serif;'><h2>La propiedad no existe o no está disponible.</h2><a href='index.html'>Volver al inicio</a></div>");
}

// Decodificar la galería de fotos desde el JSON guardado en la base de datos
$fotos_galeria = json_decode($propiedad['galeria_fotos'], true);
if (!is_array($fotos_galeria)) {
    $fotos_galeria = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Propiedad - PNK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; color: #374151; padding-bottom: 50px; }
        
        .contenedor-principal { max-width: 800px; margin: 40px auto; padding: 0 20px; background: transparent; }
        
        /* Imagen Principal */
        .img-portada { width: 100%; height: 450px; object-fit: cover; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        
        /* Galería de Fotos Reales */
        .galeria-scroll { display: flex; gap: 15px; overflow-x: auto; padding-bottom: 10px; margin-bottom: 25px; }
        .galeria-scroll img { width: 220px; height: 140px; object-fit: cover; border-radius: 8px; flex-shrink: 0; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; }
        .galeria-scroll::-webkit-scrollbar { height: 8px; }
        .galeria-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        
        /* Caja de Descripción */
        .caja-descripcion { background: #ffffff; border-left: 4px solid #FF0066; padding: 20px; border-radius: 4px; font-size: 15px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); color: #4b5563; }
        
        /* Barra Oscura de Características */
        .barra-caracteristicas { background-color: #0f172a; color: white; border-radius: 8px; padding: 20px; display: flex; justify-content: space-around; align-items: center; text-align: center; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .caract-item { display: flex; flex-direction: column; gap: 5px; }
        .caract-titulo { font-size: 11px; text-transform: uppercase; font-weight: 700; color: #94a3b8; letter-spacing: 0.5px; }
        .caract-valor { font-size: 18px; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .caract-valor i { color: #818cf8; font-size: 18px; }
        
        /* Caja de Ubicación */
        .caja-ubicacion { background: #f1f5f9; padding: 20px; border-radius: 8px; margin-bottom: 25px; font-size: 15px; color: #1e293b; }
        .caja-ubicacion i { color: #FF0066; width: 20px; text-align: center; margin-right: 5px; }
        
        /* Botón Cotizar */
        .btn-cotizar { background-color: #FF0066; color: white; width: 100%; padding: 15px; border-radius: 8px; font-size: 16px; font-weight: bold; text-transform: uppercase; border: none; letter-spacing: 1px; transition: 0.3s; box-shadow: 0 4px 6px rgba(255, 0, 102, 0.3); }
        .btn-cotizar:hover { background-color: #e6005c; transform: translateY(-2px); }
    </style>
</head>
<body>

    <div class="contenedor-principal">
        
        <img src="uploads/<?php echo htmlspecialchars($propiedad['imagen_principal'] ?? 'default.jpg'); ?>" 
             class="img-portada" 
             onerror="this.src='img/pnkpnk.png'">

        <div class="galeria-scroll">
            <?php 
            $hay_fotos_extra = false;
            foreach ($fotos_galeria as $foto) {
                // Evitamos imprimir la foto principal de nuevo en las miniaturas
                if ($foto !== $propiedad['imagen_principal']) {
                    echo '<img src="uploads/' . htmlspecialchars($foto) . '" onerror="this.style.display=\'none\'">';
                    $hay_fotos_extra = true;
                }
            }
            // Si no subiste fotos extra, mostramos un pequeño mensaje para que no quede un hueco raro
            if (!$hay_fotos_extra) {
                echo '<div style="width:100%; text-align:center; padding: 20px; background:#fff; border:1px dashed #cbd5e1; border-radius:8px; color:#94a3b8;">Sin fotografías adicionales</div>';
            }
            ?>
        </div>

        <div class="caja-descripcion">
            <?php echo nl2br(htmlspecialchars($propiedad['descripcion'])); ?>
        </div>

        <div class="barra-caracteristicas">
            <div class="caract-item">
                <span class="caract-titulo">Área Total</span>
                <span class="caract-valor"><i class="fas fa-draw-polygon" style="color: #cbd5e1;"></i> <?php echo htmlspecialchars($propiedad['superficie']); ?> m²</span>
            </div>
            <div class="caract-item">
                <span class="caract-titulo">Habitaciones</span>
                <span class="caract-valor"><i class="fas fa-bed"></i> <?php echo htmlspecialchars($propiedad['dormitorios']); ?></span>
            </div>
            <div class="caract-item">
                <span class="caract-titulo">Baños</span>
                <span class="caract-valor"><i class="fas fa-shower"></i> <?php echo htmlspecialchars($propiedad['banos']); ?></span>
            </div>
        </div>

        <div class="caja-ubicacion">
            <div style="margin-bottom: 8px;">
                <i class="fas fa-map-pin"></i> <strong>Ubicación:</strong> <?php echo htmlspecialchars($propiedad['ubicacion']); ?>
            </div>
            <div>
                <i class="fas fa-city" style="color: #64748b;"></i> <strong>Comuna:</strong> <?php echo htmlspecialchars($propiedad['comuna']); ?> <span style="color:#cbd5e1; margin:0 10px;">|</span> 
                <strong>Sector:</strong> <?php echo htmlspecialchars($propiedad['sector']); ?>
            </div>
        </div>

        <button class="btn-cotizar">Cotizar Ahora</button>

    </div>

</body>
</html>