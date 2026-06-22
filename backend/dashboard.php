<?php
session_start();
// SEGURIDAD DE NIVEL SERVIDOR
if (!isset($_SESSION['pnk_role']) || $_SESSION['pnk_role'] !== 'admin') {
    echo '
    <div style="height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; font-family: sans-serif; background: #F4F4F9; text-align: center; margin: 0;">
        <h1 style="color: #FF0066; font-size: 80px; margin: 0;">🚫</h1>
        <h2 style="color: #000; font-weight: 900; margin-top: 20px;">PERMISOS INSUFICIENTES</h2>
        <p style="color: #666; font-weight: 600; text-transform: uppercase; max-width: 500px; line-height: 1.6;">
            PERMISOS INSUFICIENTES PARA INGRESAR A PNK INMOBILIARIA.<br>COMUNÍCATE CON UN ADMINISTRADOR.
        </p>
        <script>setTimeout(() => { window.location.href = "../index.html"; }, 3000);</script>
    </div>';
    exit(); // Bloqueo total de renderizado
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Console v2.0 | PNK Inmobiliaria</title>
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        :root {
            --pnk-black: #000000;
            --pnk-pink: #FF0066;
            --pnk-gray-bg: #F4F4F9;
            --pnk-white: #ffffff;
            --pnk-border: #E5E7EB;
        }

        body { 
            display: block; 
            background-color: var(--pnk-gray-bg);
            color: var(--pnk-black);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            margin: 0;
            padding-top: 70px; /* Espacio para la nav fija */
        }

        /* Top Navigation Profesional */
        .top-navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 70px;
            background-color: var(--pnk-black);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            box-sizing: border-box;
            z-index: 1000;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-text {
            color: var(--pnk-white);
            font-weight: 900;
            font-size: 1.2rem;
            letter-spacing: -0.5px;
        }

        .brand-text span { color: var(--pnk-pink); }

        .nav-links {
            display: flex;
            gap: 5px;
            height: 100%;
        }

        .nav-item {
            background: transparent;
            border: none;
            color: #9ca3af;
            padding: 0 20px;
            height: 100%;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }

        .nav-item:hover { color: var(--pnk-white); }

        .nav-item.active {
            color: var(--pnk-white);
            border-bottom: 3px solid var(--pnk-pink);
            background: rgba(255, 0, 102, 0.05);
        }

        .nav-user-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-logout-top {
            background: transparent;
            border: 1px solid #374151;
            color: var(--pnk-white);
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-logout-top:hover {
            background: var(--pnk-pink);
            border-color: var(--pnk-pink);
        }

        /* Ajustes de contenido */
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .section-header-admin {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <nav class="top-navbar">
        <div class="nav-brand">
            <div class="brand-text">PNK<span>Inmobiliaria</span></div>
        </div>
        <div class="nav-links">
            <button class="nav-item active" data-section="resumen">Resumen General</button>
            <button class="nav-item" data-section="usuarios">Control de Usuarios</button>
            <button class="nav-item" data-section="propiedades">Control de Propiedades</button>
            <button class="nav-item" data-section="reportes">Auditoría y Reportes</button>
            <button class="nav-item" data-section="config">Configuración</button>
        </div>
        <div class="nav-user-actions">
            <button id="btn-cerrar-sesion" class="btn-logout-top">Salir del Sistema</button>
        </div>
    </nav>

    <main class="main-content">
        <header class="section-header-admin">
            <div>
                <h1 id="section-title">Consola de <span>Resumen</span></h1>
                <p id="section-desc">Estado operativo global de la plataforma.</p>
            </div>
            <button id="btn-add-main" class="btn-primary" style="display: none; max-width: 220px; height: 45px;">+ Crear Registro</button>
        </header>

        <div id="dashboard-content-area">
            <!-- Renderizado dinámico -->
        </div>
    </main>

    <!-- Modal Propiedades -->
    <div id="modal-propiedad" class="modal-overlay">
        <div class="modal-card">
            <h2 id="modal-prop-title">Nueva Propiedad</h2>
            <form id="form-propiedad-admin">
                <input type="hidden" id="prop-id">
                <div class="form-grid">
                    <div class="input-group">
                        <label>Código</label>
                        <input type="text" id="admin-prop-codigo" required>
                    </div>
                    <div class="input-group">
                        <label>Tipo</label>
                        <select id="admin-prop-tipo" class="select-estilizado">
                            <option value="Casa">Casa</option>
                            <option value="Departamento">Departamento</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Ubicación</label>
                        <input type="text" id="admin-prop-ubicacion" required>
                    </div>
                    <div class="input-group">
                        <label>Dueño</label>
                        <input type="text" id="admin-prop-dueno" required>
                    </div>
                    <div class="input-group">
                        <label>Precio (UF)</label>
                        <input type="number" id="admin-prop-precio" required>
                    </div>
                    <div class="input-group">
                        <label>Estado</label>
                        <select id="admin-prop-estado" class="select-estilizado">
                            <option value="Activa">Activa</option>
                            <option value="Pendiente">Pendiente</option>
                            <option value="Vendida">Vendida</option>
                        </select>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" id="btn-cerrar-modal-prop" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Usuarios -->
    <div id="modal-usuario" class="modal-overlay">
        <div class="modal-card">
            <h2 id="modal-usr-title">Editar Usuario</h2>
            <form id="form-usuario-admin">
                <input type="hidden" id="usr-id">
                <div class="form-grid">
                    <div class="input-group"><label>Nombre Completo</label><input type="text" id="admin-usr-nombre" required></div>
                    <div class="input-group"><label>RUT (Formato automático)</label><input type="text" id="admin-usr-rut" required></div>
                    <div class="input-group"><label>Email Institucional</label><input type="email" id="admin-usr-email" required></div>
                    <div class="input-group"><label>Teléfono (+56 9 ...)</label><input type="text" id="admin-usr-telefono" required></div>
                    <div class="input-group"><label>Contraseña</label><input type="password" id="admin-usr-pass" placeholder="Dejar vacío para no cambiar"></div>
                    <div class="input-group"><label>Rol / Permisos</label><select id="admin-usr-tipo" class="select-estilizado"><option value="propietario">Propietario (Free)</option><option value="gestor">Gestor Inmobiliario</option><option value="admin">Administrador</option></select></div>
                    <div class="input-group"><label>Estado</label><select id="admin-usr-estado" class="select-estilizado"><option value="Aprobado">Aprobado</option><option value="Pendiente">Pendiente</option></select></div>
                </div>
                <div class="modal-actions">
                    <button type="button" id="btn-cerrar-modal-usr" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar Usuario</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notificaciones (Toast) -->
    <div id="toast-container" class="toast-container"></div>

    <script src="../js/dashboard.js"></script>
</body>
</html>