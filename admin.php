<?php
// admin.php
session_start();
ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '50M');
ini_set('memory_limit', '128M');
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
if (!isset($_SESSION['usuario_rol']) && isset($_SESSION['pnk_role'])) {
    $_SESSION['usuario_rol'] = $_SESSION['pnk_role'];
}

// VALIDACIÓN DE SEGURIDAD
$rol_actual = strtolower($_SESSION['usuario_rol'] ?? '');
if (!isset($_SESSION['usuario_id']) || ($rol_actual !== 'administrador' && $rol_actual !== 'admin')) {
    header("Location: index.html");
    exit;
}

$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'resumen';

// AUTO-PARCHE DE BASE DE DATOS
try { $pdo->query("ALTER TABLE propiedades ADD COLUMN region VARCHAR(100) NULL AFTER tipo"); } catch(Exception $e){}
try { $pdo->query("ALTER TABLE propiedades ADD COLUMN piscina INT DEFAULT 0"); } catch(Exception $e){}
try { $pdo->query("ALTER TABLE propiedades ADD COLUMN estacionamiento INT DEFAULT 0"); } catch(Exception $e){}
try { $pdo->query("ALTER TABLE propiedades ADD COLUMN galeria_fotos TEXT NULL"); } catch(Exception $e){}

// ESCUDO AWS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    header("Location: admin.php?tab=propiedades&msg=error_peso");
    exit;
}

// ACCIONES DIRECTAS
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($action === 'approve_user' && $id > 0) {
        $stmt = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($u) {
            $nuevo_nombre = str_replace(['[Pendiente] ', '[Gestor Pendiente] '], '', $u['nombre']);
            if (str_contains($u['nombre'], '[Gestor Pendiente]')) {
                $nuevo_nombre .= ' [Gestor Free]';
            }
            $up = $pdo->prepare("UPDATE usuarios SET nombre = ? WHERE id = ?");
            $up->execute([$nuevo_nombre, $id]);
        }
        header("Location: admin.php?tab=usuarios&msg=user_approved");
        exit;
    }

    if (($action === 'deny_user' || $action === 'delete_user') && $id > 0) {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: admin.php?tab=" . ($action === 'deny_user' ? 'resumen' : 'usuarios') . "&msg=user_deleted");
        exit;
    }

    if ($action === 'delete_prop' && $id > 0) {
        $stmt = $pdo->prepare("DELETE FROM propiedades WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: admin.php?tab=propiedades&msg=prop_deleted");
        exit;
    }
}

// PROCESAR FORMULARIOS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['form_action'])) {
    $form_action = $_GET['form_action'];

    if ($form_action === 'save_user') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $nombre = $_POST['nombre'];
        $rut = $_POST['rut'];
        $correo = $_POST['correo'];
        $clave = $_POST['clave'];
        $rol_form = $_POST['rol']; 

        if ($rol_form === 'gestor' && !str_contains($nombre, '[Gestor Free]')) {
            $nombre = $nombre . ' [Gestor Free]';
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, rut = ?, correo = ?, clave = ?, rol = ? WHERE id = ?");
            $stmt->execute([$nombre, $rut, $correo, $clave, $rol_form, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, rut, correo, clave, rol) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $rut, $correo, $clave, $rol_form]);
        }
        header("Location: admin.php?tab=usuarios&msg=user_success");
        exit;
    }

    if ($form_action === 'save_prop') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $usuario_dueño_id = (int)$_POST['usuario_id'];
        
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
            $stmt_curr = $pdo->prepare("SELECT galeria_fotos, imagen_principal FROM propiedades WHERE id=?");
            $stmt_curr->execute([$id]);
            $curr = $stmt_curr->fetch(PDO::FETCH_ASSOC);
            
            $galeria_actual = json_decode($curr['galeria_fotos'] ?? '[]', true);
            if (!is_array($galeria_actual)) $galeria_actual = [];
            
            $galeria_final = array_merge($galeria_actual, $fotos_subidas);
            $galeria_final = array_slice($galeria_final, 0, 10); 
            
            $imagen_principal = !empty($galeria_final) ? $galeria_final[0] : 'default.jpg';
            $galeria_json = json_encode(array_values($galeria_final));

            $stmt = $pdo->prepare("UPDATE propiedades SET codigo=?, tipo=?, region=?, comuna=?, sector=?, ubicacion=?, precio=?, dormitorios=?, banos=?, superficie=?, descripcion=?, piscina=?, estacionamiento=?, imagen_principal=?, galeria_fotos=?, usuario_id=? WHERE id=?");
            $stmt->execute([$codigo, $tipo, $region, $comuna, $sector, $ubicacion, $precio, $dormitorios, $banos, $superficie, $descripcion, $piscina, $estacionamiento, $imagen_principal, $galeria_json, $usuario_dueño_id, $id]);
        } else {
            $galeria_final = array_slice($fotos_subidas, 0, 10);
            $imagen_principal = !empty($galeria_final) ? $galeria_final[0] : 'default.jpg';
            $galeria_json = json_encode(array_values($galeria_final));

            $stmt = $pdo->prepare("INSERT INTO propiedades (codigo, tipo, region, comuna, sector, ubicacion, precio, dormitorios, banos, superficie, descripcion, piscina, estacionamiento, imagen_principal, galeria_fotos, estado, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'publicada', ?)");
            $stmt->execute([$codigo, $tipo, $region, $comuna, $sector, $ubicacion, $precio, $dormitorios, $banos, $superficie, $descripcion, $piscina, $estacionamiento, $imagen_principal, $galeria_json, $usuario_dueño_id]);
        }
        header("Location: admin.php?tab=propiedades&msg=prop_success");
        exit;
    }
}

$stmt_dueños = $pdo->query("SELECT id, nombre, rol FROM usuarios WHERE nombre NOT LIKE '[Pendiente]%' AND nombre NOT LIKE '[Gestor Pendiente]%' ORDER BY nombre ASC");
$lista_dueños = $stmt_dueños->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PNK Inmobiliaria - Consola Maestro</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --pnk-pink: #FF0066; --pnk-black: #0a0a0c; --bg-light: #f4f5f7; --border-color: #e4e7eb; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, sans-serif; }
        body { background-color: var(--bg-light); display: flex; min-height: 100vh; color: #1f2937; }
        .sidebar { width: 280px; background-color: var(--pnk-black); padding: 30px 20px; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; }
        .sidebar-header { display: flex; align-items: center; gap: 12px; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 1px solid #1f2937; }
        .sidebar-title { color: white; font-size: 20px; font-weight: 900; text-transform: uppercase; }
        .sidebar-title span { color: var(--pnk-pink); }
        .sidebar-menu { display: flex; flex-direction: column; gap: 10px; flex-grow: 1; }
        .menu-link { display: flex; align-items: center; gap: 12px; color: #9ca3af; text-decoration: none; padding: 14px 18px; border-radius: 8px; font-size: 15px; font-weight: 700; transition: all 0.3s; }
        .menu-link:hover, .menu-link.active { color: white; background-color: var(--pnk-pink); }
        .main-content { margin-left: 280px; flex-grow: 1; padding: 40px 50px; }
        .main-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; }
        .header-title h1 { font-size: 32px; font-weight: 900; color: var(--pnk-black); }
        .btn-action-main { background-color: var(--pnk-black); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; text-decoration: none; }
        .btn-action-main:hover { background-color: var(--pnk-pink); }
        .data-panel { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .panel-title { font-size: 20px; font-weight: 800; margin-bottom: 20px; color: var(--pnk-black); }
        .table-responsive { width: 100%; overflow-x: auto; }
        .premium-table { width: 100%; border-collapse: collapse; text-align: left; }
        .premium-table th { background-color: #fafafa; color: var(--pnk-black); padding: 16px; font-size: 13px; font-weight: 800; text-transform: uppercase; border-bottom: 2px solid var(--border-color); }
        .premium-table td { padding: 16px; border-bottom: 1px solid var(--border-color); font-size: 14px; }
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; text-transform: uppercase; }
        .badge.pendiente { background-color: #fffbeb; color: #f59e0b; }
        .badge.gestor { background-color: #fee2e2; color: #FF0066; }
        .actions-cell { display: flex; gap: 8px; }
        .btn-table { padding: 8px 12px; border: 1px solid #d1d5db; background: white; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; text-decoration: none; color: #374151; }
        .btn-table.btn-approve { background-color: #e6f9f0; color: #10b981; border-color: #a7f3d0; }
        .btn-table.btn-deny { background-color: #fee2e2; color: #ef4444; border-color: #fecaca; }
        
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: none; justify-content: center; align-items: center; z-index: 1000; padding: 20px; }
        .modal-box { background: white; padding: 40px; border-radius: 16px; width: 100%; max-width: 700px; position: relative; max-height: 90vh; overflow-y: auto; }
        .modal-close-btn { position: absolute; top: 15px; right: 20px; font-size: 32px; cursor: pointer; color: #9ca3af; line-height: 1; z-index: 10; background: white; border-radius: 50%; padding: 0 5px; }
        
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
        .form-group label { font-size: 13px; font-weight: 700; }
        .form-group input:not([type="file"]), .form-group select, .form-group textarea { padding: 11px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; }
        input[readonly], select[readonly], input:disabled, select:disabled { background-color: #f1f5f9; color: #94a3b8; cursor: not-allowed; }
        
        .caja-extras { background: #f8fafc; border: 1px dashed #cbd5e1; padding: 15px; border-radius: 8px; grid-column: 1 / -1; display: none; }
        .checkbox-container { display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; color: #0f172a; }
        .checkbox-container input { width: 18px; height: 18px; accent-color: #FF0066; cursor: pointer; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header"><div class="sidebar-title">PNK <span>Inmobiliaria</span></div></div>
        <nav class="sidebar-menu">
            <a href="admin.php?tab=resumen" class="menu-link <?php echo $current_tab === 'resumen' ? 'active' : ''; ?>">📊 Resumen General</a>
            <a href="admin.php?tab=usuarios" class="menu-link <?php echo $current_tab === 'usuarios' ? 'active' : ''; ?>">👥 Gestionar Usuarios</a>
            <a href="admin.php?tab=propiedades" class="menu-link <?php echo $current_tab === 'propiedades' ? 'active' : ''; ?>">🏠 Inventario Propiedades</a>
        </nav>
        <div class="sidebar-footer"><a href="index.html" class="btn-logout" style="color:#9ca3af; text-decoration:none; padding:14px 18px; display:block; font-weight:700;">Cerrar Sesión</a></div>
    </aside>

    <main class="main-content">
        <header class="main-header">
            <div class="header-title"><h1>Control Maestro</h1></div>
            <div>
                <?php if($current_tab === 'usuarios'): ?><button class="btn-action-main" onclick="openUserModal()">+ Crear Nuevo Usuario</button><?php endif; ?>
                <?php if($current_tab === 'propiedades'): ?><button class="btn-action-main" onclick="openPropModal()" style="background-color: var(--pnk-pink);">+ Añadir Nueva Propiedad</button><?php endif; ?>
            </div>
        </header>

        <?php if($current_tab === 'resumen'): ?>
            <div class="data-panel">
                <div class="panel-title">⚠️ Solicitudes Pendientes de Aceptación</div>
                <div class="table-responsive">
                    <table class="premium-table">
                        <thead>
                            <tr><th>Nombre</th><th>RUT</th><th>Email</th><th>Rol Solicitado</th><th>Decisión de Acceso</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM usuarios WHERE nombre LIKE '[Pendiente]%' OR nombre LIKE '[Gestor Pendiente]%' ORDER BY id DESC");
                            $pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            if(count($pendientes) === 0):
                                echo '<tr><td colspan="5" style="text-align:center; color:#666;">No hay solicitudes pendientes.</td></tr>';
                            else:
                                foreach($pendientes as $u):
                                    $es_gestor = str_contains($u['nombre'], '[Gestor Pendiente]');
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($u['nombre']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($u['rut']); ?></td>
                                    <td><?php echo htmlspecialchars($u['correo']); ?></td>
                                    <td><span class="badge pendiente"><?php echo $es_gestor ? 'Gestor Free' : 'Propietario'; ?></span></td>
                                    <td class="actions-cell">
                                        <a href="admin.php?action=approve_user&id=<?php echo $u['id']; ?>" class="btn-table btn-approve">Aceptar e Incorporar</a>
                                        <a href="admin.php?action=deny_user&id=<?php echo $u['id']; ?>" class="btn-table btn-deny" onclick="return confirm('¿Denegar?')">Denegar Acceso</a>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if($current_tab === 'usuarios'): ?>
            <div class="data-panel">
                <div class="panel-title">Lista General de Cuentas Activas</div>
                <div class="table-responsive">
                    <table class="premium-table">
                        <thead>
                            <tr><th>Nombre</th><th>RUT</th><th>Email</th><th>Rol Asignado</th><th>Acciones de Control</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM usuarios WHERE nombre NOT LIKE '[Pendiente]%' AND nombre NOT LIKE '[Gestor Pendiente]%' ORDER BY id DESC");
                            while($u = $stmt->fetch(PDO::FETCH_ASSOC)):
                                $es_gestor = str_contains($u['nombre'], '[Gestor Free]');
                                $nombre_limpio = str_replace(' [Gestor Free]', '', $u['nombre']);
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($nombre_limpio); ?></strong></td>
                                    <td><?php echo htmlspecialchars($u['rut']); ?></td>
                                    <td><?php echo htmlspecialchars($u['correo']); ?></td>
                                    <td><?php echo $es_gestor ? '<span class="badge gestor">Gestor Free</span>' : '<b>'.ucfirst($u['rol'] ?? 'Propietario').'</b>'; ?></td>
                                    <td class="actions-cell">
                                        <button class="btn-table" onclick='editUser(<?php echo htmlspecialchars(json_encode(array_merge($u, ['nombre' => $nombre_limpio, 'display_rol' => $es_gestor ? 'gestor' : ($u['rol'] ?? 'propietario')])), ENT_QUOTES, "UTF-8"); ?>)'>Editar</button>
                                        <a href="admin.php?action=delete_user&id=<?php echo $u['id']; ?>" class="btn-table btn-deny" onclick="return confirm('¿Borrar definitivamente a este usuario?')">Borrar</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if($current_tab === 'propiedades'): ?>
            <div class="data-panel">
                <div class="panel-title">Portafolio de Bienes Raíces PNK</div>
                <div class="table-responsive">
                    <table class="premium-table">
                        <thead>
                            <tr><th>Código</th><th>Tipo</th><th>Ubicación</th><th>Características</th><th>Valor</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT p.*, u.nombre as dueno_nombre FROM propiedades p LEFT JOIN usuarios u ON p.usuario_id = u.id ORDER BY p.id DESC");
                            while($p = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                                <tr>
                                    <td><span style="background:#000; color:#fff; padding:4px 8px; border-radius:4px; font-weight:bold; font-size:12px;"><?php echo htmlspecialchars($p['codigo']); ?></span></td>
                                    <td><strong style="text-transform:uppercase; color:var(--pnk-pink);"><?php echo htmlspecialchars($p['tipo']); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($p['ubicacion']); ?><br>
                                        <small style="color:#666;"><?php echo htmlspecialchars($p['comuna'] . ' - ' . ($p['region'] ?? 'N/A')); ?></small><br>
                                        <small style="color:var(--pnk-pink); font-weight:bold;">👤 Dueño: <?php echo htmlspecialchars($p['dueno_nombre'] ?? 'Sin Asignar'); ?></small>
                                    </td>
                                    <td><small>📐 <?php echo $p['superficie']; ?>m² | 🛏️ <?php echo $p['dormitorios']; ?>D | 🚿 <?php echo $p['banos']; ?>B</small></td>
                                    <td><strong>$ <?php echo number_format($p['precio'], 0, ',', '.'); ?></strong></td>
                                    <td class="actions-cell">
                                        <button class="btn-table" onclick='editProp(<?php echo htmlspecialchars(json_encode($p), ENT_QUOTES, "UTF-8"); ?>)'>Editar</button>
                                        <a href="admin.php?action=delete_prop&id=<?php echo $p['id']; ?>" class="btn-table btn-deny" onclick="return confirm('¿Remover propiedad?')">Eliminar</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <div class="modal-overlay" id="userModal">
        <div class="modal-box" style="max-width:500px;">
            <span class="modal-close-btn" onclick="closeUserModal()">×</span>
            <h2 class="panel-title" id="userModalTitle">Perfil de Usuario</h2>
            <form action="admin.php?form_action=save_user" method="POST" class="modal-form">
                <input type="hidden" id="form-user-id" name="id">
                <div class="form-group"><label>Nombre Completo</label><input type="text" id="form-user-nombre" name="nombre" required></div>
                <div class="form-group"><label>RUT</label><input type="text" id="form-user-rut" name="rut" required></div>
                <div class="form-group"><label>Correo Electrónico</label><input type="email" id="form-user-email" name="correo" required></div>
                <div class="form-group"><label>Contraseña / Token</label><input type="text" id="form-user-clave" name="clave" required></div>
                <div class="form-group">
                    <label>Rol de Plataforma</label>
                    <select id="form-user-rol" name="rol">
                        <option value="propietario">Propietario</option>
                        <option value="gestor">Gestor Free</option>
                        <option value="administrador">Administrador</option>
                    </select>
                </div>
                <button type="submit" class="btn-action-main" style="width:100%;">Guardar Parámetros de Cuenta</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="propModal">
        <div class="modal-box">
            <span class="modal-close-btn" onclick="closePropModal()">×</span>
            <h2 class="panel-title" id="propModalTitle">Ficha de Propiedad</h2>
            <form id="formAdminProp" action="admin.php?form_action=save_prop" method="POST" class="modal-form" enctype="multipart/form-data">
                <input type="hidden" id="form-prop-id" name="id">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    
                    <div class="form-group" style="grid-column: 1 / -1; background:#f8fafc; padding:10px; border-radius:8px; border:1px solid #cbd5e1;">
                        <label style="color:var(--pnk-black);">👤 Asignar a Propietario / Gestor *</label>
                        <select id="form-prop-usuario_id" name="usuario_id" style="width:100%; border:1px solid #cbd5e1; padding:8px; border-radius:4px; margin-top:5px;">
                            <option value="">Seleccione al dueño de esta propiedad...</option>
                            <?php foreach($lista_dueños as $dueño): ?>
                                <option value="<?php echo $dueño['id']; ?>">
                                    <?php echo htmlspecialchars($dueño['nombre'] . ' (' . ucfirst($dueño['rol'] ?? 'Propietario') . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group"><label>Código Corretaje *</label><input type="text" id="form-prop-codigo" name="codigo"></div>
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
                    <div class="form-group"><label>Valor ($) *</label><input type="number" id="form-prop-precio" name="precio"></div>
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
                        <label style="color: var(--pnk-pink); font-weight: bold; margin-bottom: 15px; display: block;">📸 Fotografías (Máximo 10 fotos)</label>
                        
                        <div id="image-previews" style="display:flex; flex-wrap:wrap; gap:10px; justify-content:center; margin-bottom:15px;"></div>
                        
                        <input type="file" id="prop-foto" name="prop_foto[]" class="form-control w-75 mx-auto" multiple accept="image/jpeg, image/png, image/webp" style="cursor: pointer; padding: 6px;">
                        <small style="color:#94a3b8; display:block; margin-top:10px;">Selecciona hasta 10 archivos. El sistema las optimizará automáticamente.</small>
                    </div>
                </div>
                <button type="submit" class="btn-action-main" style="width:100%; margin-top:15px; background-color:var(--pnk-pink); font-size:16px; padding:15px;">Guardar Ficha Inmueble</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            const msg = urlParams.get('msg');
            
            if (msg === 'prop_success') { Swal.fire('¡Éxito!', 'La propiedad fue guardada correctamente en el sistema.', 'success'); }
            if (msg === 'user_success') { Swal.fire('¡Éxito!', 'El usuario ha sido guardado exitosamente.', 'success'); }
            if (msg === 'prop_deleted') { Swal.fire('Eliminada', 'La propiedad fue borrada del inventario.', 'info'); }
            if (msg === 'user_deleted') { Swal.fire('Eliminado', 'El usuario ha sido eliminado.', 'info'); }
            if (msg === 'user_approved') { Swal.fire('Aprobado', 'El usuario ahora tiene acceso a la plataforma.', 'success'); }
            if (msg === 'error_peso') { Swal.fire('Error de Conexión', 'El servidor AWS botó la conexión porque el envío era demasiado pesado.', 'error'); }
            
            if(msg) { window.history.replaceState({}, document.title, window.location.pathname + "?tab=" + urlParams.get('tab')); }
        });

        function openUserModal() {
            document.getElementById('form-user-id').value = ''; document.getElementById('form-user-nombre').value = '';
            document.getElementById('form-user-rut').value = ''; document.getElementById('form-user-email').value = '';
            document.getElementById('form-user-clave').value = ''; document.getElementById('form-user-rol').value = 'propietario';
            document.getElementById('userModal').style.display = 'flex';
        }
        function closeUserModal() { document.getElementById('userModal').style.display = 'none'; }
        function editUser(u) {
            document.getElementById('form-user-id').value = u.id; document.getElementById('form-user-nombre').value = u.nombre;
            document.getElementById('form-user-rut').value = u.rut; document.getElementById('form-user-email').value = u.correo;
            document.getElementById('form-user-clave').value = u.clave; document.getElementById('form-user-rol').value = u.display_rol; 
            document.getElementById('userModal').style.display = 'flex';
        }

        // PREVISUALIZADOR
        document.getElementById('prop-foto').addEventListener('change', function(event) {
            const files = event.target.files;
            const previewContainer = document.getElementById('image-previews');
            
            if (files.length > 10) {
                Swal.fire('Límite excedido', 'Has superado el límite de 10 fotos permitidas.', 'error');
                event.target.value = ''; previewContainer.innerHTML = '';
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

        // GEOGRAFÍA NACIONAL Y PREDICTIVO
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
            'Arica': ['Centro', 'Chinchorro', 'Azapa'], 'Iquique': ['Cavancha', 'Centro', 'Playa Brava'], 'Antofagasta': ['Centro', 'Sector Sur', 'Sector Norte'],
            'La Serena': ['Cerro Oriente', 'Avenida del Mar', 'Centro', 'San Joaquín', 'Puertas del Mar', 'La Florida', 'Las Compañías', 'El Milagro'],
            'Coquimbo': ['San Juan', 'Herradura', 'Sindempart', 'Peñuelas', 'Centro', 'Tierras Blancas', 'Punta Mira', 'El Llano', 'La Cantera'],
            'Ovalle': ['Centro', 'Valle Limarí', 'Tuquí', 'San Julián', 'Romeral', 'Los Peñones', 'Sotaquí', 'Huamalata', 'Parte Alta'],
            'Valparaíso': ['Cerro Alegre', 'Cerro Concepción', 'Playa Ancha'], 'Viña del Mar': ['Reñaca', 'Gómez Carreño', 'Miraflores', 'Centro'],
            'Santiago': ['Centro', 'Lastarria', 'Barrio Brasil'], 'Concepción': ['Centro', 'Barrio Universitario', 'Collao']
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

        // VALIDACIÓN SEGÚN TIPO
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
            document.getElementById('form-prop-id').value = ''; document.getElementById('form-prop-usuario_id').value = '';
            document.getElementById('form-prop-codigo').value = ''; document.getElementById('form-prop-tipo').value = '';
            selectRegion.value = ''; selectComuna.innerHTML = '<option value="">Seleccione Comuna...</option>'; selectComuna.disabled = true;
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
            document.getElementById('form-prop-id').value = p.id;
            document.getElementById('form-prop-usuario_id').value = p.usuario_id || '';
            document.getElementById('form-prop-codigo').value = p.codigo;
            
            const tipo = p.tipo.toLowerCase(); selectTipo.value = tipo;
            
            if (p.region) {
                selectRegion.value = p.region; selectRegion.dispatchEvent(new Event('change'));
                selectComuna.value = p.comuna; selectComuna.dispatchEvent(new Event('change'));
            }
            
            inputSector.value = p.sector;
            document.getElementById('form-prop-ubicacion').value = p.ubicacion; document.getElementById('form-prop-precio').value = p.precio;
            document.getElementById('form-prop-superficie').value = p.superficie;
            
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

            // CARGAR GALERÍA ACTUAL AL EDITAR
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

        // MOTOR COMPRESIÓN
        async function compressImage(file, maxWidth, quality) {
            return new Promise((resolve) => {
                const reader = new FileReader(); reader.readAsDataURL(file);
                reader.onload = (event) => {
                    const img = new Image(); img.src = event.target.result;
                    img.onload = () => {
                        const canvas = document.createElement('canvas');
                        let width = img.width; let height = img.height;
                        if (width > maxWidth) { height = Math.round((height * maxWidth) / width); width = maxWidth; }
                        canvas.width = width; canvas.height = height;
                        const ctx = canvas.getContext('2d'); ctx.drawImage(img, 0, 0, width, height);
                        canvas.toBlob((blob) => { resolve(new File([blob], file.name, { type: 'image/jpeg', lastModified: Date.now() })); }, 'image/jpeg', quality);
                    };
                };
            });
        }

        const formPropAdmin = document.getElementById('formAdminProp');
        if(formPropAdmin) {
            formPropAdmin.addEventListener('submit', async function(e) {
                e.preventDefault(); 
                
                const files = document.getElementById('prop-foto').files;
                if (files.length > 10) {
                    Swal.fire('Límite excedido', 'Has seleccionado más de 10 imágenes. Reduce la cantidad.', 'error');
                    return;
                }

                const usuario = document.getElementById('form-prop-usuario_id').value;
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

                if(!usuario || !tipo || !codigo || !region || !comuna || !sectorVal || !ubicacion || !descripcion) {
                    Swal.fire('Campos Incompletos', 'Por favor completa todos los campos requeridos y selecciona un propietario.', 'warning');
                    return;
                }

                if(ubicacion.length < 8) {
                    Swal.fire('Dirección Inválida', 'La dirección física es demasiado corta.', 'error');
                    return;
                }

                if(isNaN(precio) || precio <= 0 || isNaN(superficie) || superficie <= 0) {
                    Swal.fire('Error Numérico', 'Precio y superficie deben ser mayores a cero.', 'error');
                    return;
                }

                if(tipo === 'oficina' && (isNaN(ban) || ban < 1)) {
                    Swal.fire('Faltan Baños', 'Una oficina debe tener al menos 1 baño registrado.', 'error'); return;
                } else if((tipo === 'casa' || tipo === 'departamento') && (isNaN(dor) || dor < 1 || isNaN(ban) || ban < 1)) {
                    Swal.fire('Faltan Datos', 'Una casa o departamento debe tener al menos 1 dormitorio y 1 baño.', 'error'); return;
                }

                // COMPRESIÓN EN VIVO ANTES DE MANDAR
                if (files.length > 0) {
                    Swal.fire({ title: 'Optimizando Imágenes...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                    const dataTransfer = new DataTransfer();
                    for (let i = 0; i < files.length; i++) {
                        const file = files[i];
                        if (file.type.startsWith('image/')) {
                            const compressedFile = await compressImage(file, 1200, 0.7);
                            dataTransfer.items.add(compressedFile);
                        } else { dataTransfer.items.add(file); }
                    }
                    document.getElementById('prop-foto').files = dataTransfer.files;
                } else {
                    Swal.fire({ title: 'Guardando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                }
                
                this.submit();
            });
        }
    </script>
</body>
</html>