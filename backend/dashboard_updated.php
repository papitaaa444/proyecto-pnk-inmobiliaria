<?php
session_start();
// SEGURIDAD: Verificar que sea admin
if (!isset($_SESSION['user_id']) || $_SESSION['pnk_role'] !== 'admin') {
    echo '
    <div style="height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; font-family: sans-serif; background: #F4F4F9; text-align: center; margin: 0;">
        <h1 style="color: #FF0066; font-size: 80px; margin: 0;">🚫</h1>
        <h2 style="color: #000; font-weight: 900; margin-top: 20px;">ACCESO DENEGADO</h2>
        <p style="color: #666; font-weight: 600; text-transform: uppercase;">
            SOLO ADMINISTRADORES PUEDEN ACCEDER A ESTA SECCIÓN.
        </p>
        <script>setTimeout(() => { window.location.href = "../index.html"; }, 3000);</script>
    </div>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PNK Inmobiliaria - Panel Administrativo</title>
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: Arial; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo-text">PNK<span class="inmobiliaria">Admin</span></div>
            </div>
            <nav class="sidebar-nav">
                <button class="nav-item active" data-section="resumen">📊 Resumen</button>
                <button class="nav-item" data-section="propiedades">🏠 Propiedades</button>
                <button class="nav-item" data-section="usuarios">👥 Usuarios</button>
            </nav>
            <div class="sidebar-footer">
                <button id="btn-cerrar-sesion" class="btn-logout">Cerrar Sesión</button>
            </div>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <div>
                    <h1 id="section-title">Consola de <span>Resumen</span></h1>
                    <p id="section-desc">Estado operativo global de la plataforma.</p>
                </div>
                <button id="btn-add-main" class="btn-primary" style="display: none;">+ Agregar</button>
            </header>

            <div id="dashboard-content-area">
                <!-- Contenido dinámico -->
            </div>
        </main>
    </div>

    <!-- Modal para Propiedades -->
    <div id="modal-propiedad" class="modal-overlay">
        <div class="modal-card">
            <h2 id="modal-prop-title">Nueva Propiedad</h2>
            <form id="form-propiedad-admin">
                <input type="hidden" id="prop-id" name="id">
                <div class="form-grid">
                    <div class="input-group"><label>Código</label>
                        <input type="text" id="admin-prop-codigo" name="codigo" readonly>
                    </div>
                    <div class="input-group"><label>Tipo *</label>
                        <select id="admin-prop-tipo" name="tipo" required>
                            <option value="">Selecciona...</option>
                            <option value="Casa">Casa</option>
                            <option value="Departamento">Departamento</option>
                            <option value="Terreno">Terreno</option>
                        </select>
                    </div>
                    <div class="input-group"><label>Comuna *</label>
                        <select id="admin-prop-comuna" name="comuna" required>
                            <option value="">Selecciona...</option>
                            <option value="La Serena">La Serena</option>
                            <option value="Coquimbo">Coquimbo</option>
                            <option value="Ovalle">Ovalle</option>
                        </select>
                    </div>
                    <div class="input-group"><label>Sector *</label>
                        <input type="text" id="admin-prop-sector" name="sector" required>
                    </div>
                    <div class="input-group"><label>Dirección *</label>
                        <input type="text" id="admin-prop-ubicacion" name="ubicacion" required>
                    </div>
                    <div class="input-group"><label>Precio (UF) *</label>
                        <input type="number" id="admin-prop-precio" name="precio" required min="0" step="0.1">
                    </div>
                    <div class="input-group"><label>Dormitorios</label>
                        <input type="number" id="admin-prop-dormitorios" name="dormitorios" min="0" value="0">
                    </div>
                    <div class="input-group"><label>Baños</label>
                        <input type="number" id="admin-prop-banos" name="banos" min="0" value="0">
                    </div>
                    <div class="input-group"><label>Superficie (m²)</label>
                        <input type="number" id="admin-prop-superficie" name="superficie" min="0" value="0">
                    </div>
                    <div class="input-group"><label>Estado</label>
                        <select id="admin-prop-estado" name="estado">
                            <option value="Pendiente">Pendiente</option>
                            <option value="Activa">Activa</option>
                            <option value="Vendida">Vendida</option>
                            <option value="Eliminada">Eliminada</option>
                        </select>
                    </div>
                    <div class="input-group"><label>Propietario *</label>
                        <input type="number" id="admin-prop-user-id" name="user_id" required>
                    </div>
                </div>
                <div class="input-group"><label>Descripción</label>
                    <textarea id="admin-prop-descripcion" name="descripcion" rows="3"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" id="btn-cerrar-modal-prop" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Usuarios -->
    <div id="modal-usuario" class="modal-overlay">
        <div class="modal-card">
            <h2 id="modal-usr-title">Nuevo Usuario</h2>
            <form id="form-usuario-admin">
                <input type="hidden" id="usr-id" name="id">
                <div class="form-grid">
                    <div class="input-group"><label>Nombre *</label>
                        <input type="text" id="admin-usr-nombre" name="nombre" required>
                    </div>
                    <div class="input-group"><label>RUT *</label>
                        <input type="text" id="admin-usr-rut" name="rut" required>
                    </div>
                    <div class="input-group"><label>Email *</label>
                        <input type="email" id="admin-usr-email" name="email" required>
                    </div>
                    <div class="input-group"><label>Teléfono *</label>
                        <input type="tel" id="admin-usr-telefono" name="telefono" required>
                    </div>
                    <div class="input-group"><label>Contraseña</label>
                        <input type="password" id="admin-usr-pass" name="password">
                    </div>
                    <div class="input-group"><label>Tipo</label>
                        <select id="admin-usr-tipo" name="tipo">
                            <option value="propietario">Propietario</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    <div class="input-group"><label>Estado</label>
                        <select id="admin-usr-estado" name="estado">
                            <option value="Pendiente">Pendiente</option>
                            <option value="Aprobado">Aprobado</option>
                            <option value="Rechazado">Rechazado</option>
                        </select>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" id="btn-cerrar-modal-usr" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/dashboard_updated.js"></script>
</body>
</html>
