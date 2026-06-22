<?php
session_start();
// SEGURIDAD: Verificar que al menos esté logueado
if (!isset($_SESSION['user_id'])) {
    echo '
    <div style="height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; font-family: sans-serif; background: #F4F4F9; text-align: center; margin: 0;">
        <h1 style="color: #FF0066; font-size: 80px; margin: 0;">🚫</h1>
        <h2 style="color: #000; font-weight: 900; margin-top: 20px;">ACCESO NO AUTORIZADO</h2>
        <p style="color: #666; font-weight: 600; text-transform: uppercase;">
            DEBES INICIAR SESIÓN PARA INGRESAR AL PORTAL DE PNK INMOBILIARIA.
        </p>
        <script>setTimeout(() => { window.location.href = "../index.html"; }, 3000);</script>
    </div>';
    exit();
}
$rol = $_SESSION['pnk_role'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PNK Inmobiliaria - Mi Portal</title>
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .user-welcome { padding: 20px; background: white; border-radius: 10px; margin-bottom: 20px; border-left: 5px solid var(--color-rosado); }
        .perfil-card { background: white; padding: 30px; border-radius: 12px; box-shadow: var(--shadow-soft); max-width: 500px; }
        
        /* Estilos para galería de fotos */
        .fotos-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; margin-top: 20px; }
        .foto-item { position: relative; border-radius: 8px; overflow: hidden; background: #f0f0f0; }
        .foto-item img { width: 100%; height: 150px; object-fit: cover; }
        .foto-badge { position: absolute; top: 5px; right: 5px; background: var(--color-rosado); color: white; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .foto-actions { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.7); padding: 8px; display: flex; gap: 5px; justify-content: center; }
        .foto-actions button { background: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: bold; }
        .foto-actions button:hover { background: var(--color-rosado); color: white; }
        
        /* Modal para subir fotos */
        .modal-fotos { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal-fotos.visible { display: flex; }
        .modal-fotos-content { background: white; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%; }
        .upload-area { border: 2px dashed var(--color-rosado); border-radius: 8px; padding: 30px; text-align: center; cursor: pointer; transition: all 0.3s; }
        .upload-area:hover { background: #fff0f5; }
        .upload-area.dragover { background: #ffe0ed; border-color: #ff0066; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo-text">PNK<span class="inmobiliaria">Portal</span></div>
            </div>
            <nav class="sidebar-nav">
                <button class="nav-item active" data-section="catalogo">🔍 Buscar Propiedades</button>
                <button class="nav-item" data-section="publicar">➕ Publicar Propiedad</button>
                <button class="nav-item" data-section="mis-publicaciones">🏠 Mis Publicaciones</button>
                <button class="nav-item" data-section="perfil">👤 Mi Perfil</button>
            </nav>
            <div class="sidebar-footer">
                <button onclick="location.href='logout.php'" class="btn-logout">Cerrar Sesión</button>
            </div>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <div>
                    <h1 id="section-title">Bienvenido, <span><?php echo $_SESSION['user_name']; ?></span></h1>
                    <p id="section-desc">Gestiona tus búsquedas y publicaciones desde aquí.</p>
                </div>
            </header>

            <div id="portal-content-area">
                <!-- Contenido dinámico cargado por portal.js -->
            </div>
        </main>
    </div>

    <!-- Modal para Publicar/Editar Propiedad -->
    <div id="modal-propiedad-user" class="modal-overlay">
        <div class="modal-card">
            <h2 id="modal-title">Publicar Propiedad</h2>
            <form id="form-propiedad-user">
                <input type="hidden" id="user-prop-id" name="id">
                <div class="form-grid">
                    <div class="input-group"><label>Tipo *</label>
                        <select name="tipo" id="user-prop-tipo" required>
                            <option value="">Selecciona...</option>
                            <option value="Casa">Casa</option>
                            <option value="Departamento">Departamento</option>
                            <option value="Terreno">Terreno</option>
                        </select>
                    </div>
                    <div class="input-group"><label>Comuna *</label>
                        <select name="comuna" id="user-prop-comuna" required>
                            <option value="">Selecciona...</option>
                            <option value="La Serena">La Serena</option>
                            <option value="Coquimbo">Coquimbo</option>
                            <option value="Ovalle">Ovalle</option>
                        </select>
                    </div>
                    <div class="input-group"><label>Sector *</label>
                        <input type="text" name="sector" id="user-prop-sector" required placeholder="Ej: Centro, Playa, etc.">
                    </div>
                    <div class="input-group"><label>Dirección *</label>
                        <input type="text" name="ubicacion" id="user-prop-ubicacion" required placeholder="Calle y número">
                    </div>
                    <div class="input-group"><label>Precio (UF) *</label>
                        <input type="number" name="precio" id="user-prop-precio" required min="0" step="0.1">
                    </div>
                    <div class="input-group"><label>Dormitorios</label>
                        <input type="number" name="dormitorios" id="user-prop-dormitorios" min="0" value="0">
                    </div>
                    <div class="input-group"><label>Baños</label>
                        <input type="number" name="banos" id="user-prop-banos" min="0" value="0">
                    </div>
                    <div class="input-group"><label>Superficie (m²)</label>
                        <input type="number" name="superficie" id="user-prop-superficie" min="0" value="0">
                    </div>
                </div>
                <div class="input-group"><label>Descripción</label>
                    <textarea name="descripcion" id="user-prop-descripcion" rows="4" placeholder="Describe la propiedad..."></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" id="btn-cerrar-modal" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar Publicación</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para subir fotos -->
    <div id="modal-fotos" class="modal-fotos">
        <div class="modal-fotos-content">
            <h3>Subir Fotografías</h3>
            <p style="color: #666; font-size: 14px;">Máximo 10 fotos por propiedad (JPG, PNG, WEBP). Máx 5MB cada una.</p>
            <div class="upload-area" id="upload-area">
                <p style="margin: 0; color: #666;">📁 Arrastra fotos aquí o haz clic para seleccionar</p>
                <input type="file" id="file-input" multiple accept="image/*" style="display: none;">
            </div>
            <div id="fotos-preview" style="margin-top: 20px;"></div>
            <div class="modal-actions">
                <button type="button" id="btn-cerrar-fotos" class="btn-secondary">Cancelar</button>
                <button type="button" id="btn-subir-fotos" class="btn-primary">Subir Fotos</button>
            </div>
        </div>
    </div>

    <div id="toast-container" class="toast-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const USER_ROLE = "<?php echo $rol; ?>";
        const USER_NAME = "<?php echo $_SESSION['user_name']; ?>";
    </script>
    <script src="../js/portal_updated.js"></script>
</body>
</html>
