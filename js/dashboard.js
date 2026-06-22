document.addEventListener("DOMContentLoaded", function () {
    // 1. ELIMINAMOS DATOS SIMULADOS
    let bdPropiedades = [];
    let bdUsuarios = [];

    // 2. REFERENCIAS DEL DOM
    const contentArea = document.getElementById("dashboard-content-area");
    const sectionTitle = document.getElementById("section-title");
    const sectionDesc = document.getElementById("section-desc");
    const btnAddMain = document.getElementById("btn-add-main");
    const navItems = document.querySelectorAll(".nav-item");
    const toastContainer = document.getElementById("toast-container");

    // Modal de Propiedades
    const modalProp = document.getElementById("modal-propiedad");
    const formProp = document.getElementById("form-propiedad-admin");
    const btnCerrarModalProp = document.getElementById("btn-cerrar-modal-prop");

    // Modal de Usuarios
    const modalUsr = document.getElementById("modal-usuario");
    const formUsr = document.getElementById("form-usuario-admin");
    const btnCerrarModalUsr = document.getElementById("btn-cerrar-modal-usr");

    // 3. FUNCIONES DE UTILIDAD (Notificaciones)
    function showToast(mensaje, tipo = "ok") {
        const toast = document.createElement("div");
        toast.className = `toast ${tipo === "error" ? "error" : ""}`;
        toast.textContent = mensaje;
        toastContainer.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    // Formateadores Automáticos (RUT y Teléfono)
    const inputRut = document.getElementById("admin-usr-rut");
    if (inputRut) {
        inputRut.addEventListener("input", (e) => {
            let val = e.target.value.replace(/\./g, "").replace("-", "");
            if (val.length > 1) {
                let cuerpo = val.slice(0, -1).replace(/\D/g, "");
                let dv = val.slice(-1).toUpperCase();
                if (cuerpo.length > 0) {
                    let formatado = "";
                    while (cuerpo.length > 3) {
                        formatado = "." + cuerpo.slice(-3) + formatado;
                        cuerpo = cuerpo.slice(0, -3);
                    }
                    e.target.value = cuerpo + formatado + "-" + dv;
                }
            }
        });
    }

    const inputTel = document.getElementById("admin-usr-telefono");
    if (inputTel) {
        inputTel.addEventListener("input", (e) => {
            let valor = e.target.value.replace(/\D/g, "");
            if (valor.startsWith("569")) valor = valor.slice(3);
            valor = valor.slice(0, 8);
            e.target.value = valor.length > 0 ? "+56 9 " + valor : "";
        });
    }

    function validarEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function validarRut(rut) {
        // Simplificado para la UI, el servidor hará la verificación final
        return /^[0-9]{1,2}(\.[0-9]{3}){2}-[0-9Kk]{1}$/.test(rut);
    }

    // Cerrar Sesión
    document.getElementById("btn-cerrar-sesion").addEventListener("click", () => {
        window.location.href = "../backend/logout.php"; 
    });

    // Cambiar de pestaña (útil para cuando el Admin quiere ver propiedades de un usuario)
    function activarTab(sectionName) {
        navItems.forEach(b => b.classList.remove("active"));
        const target = document.querySelector(`[data-section="${sectionName}"]`);
        if(target) target.classList.add("active");
    }

    // 4. VISTAS DEL DASHBOARD

    // A) VISTA RESUMEN
    function renderResumen() {
        activarTab("resumen");
        sectionTitle.innerHTML = "Consola de <span>Resumen</span>";
        sectionDesc.textContent = "Estado operativo global de la plataforma.";
        btnAddMain.style.display = "none";

        fetch('api_dashboard.php?action=get_resumen')
            .then(res => res.json())
            .then(data => {
                contentArea.innerHTML = `
                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-label">Inmuebles en Catálogo</span>
                        <span class="stat-value">${data.stats.total_prop}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Publicaciones Vigentes</span>
                        <span class="stat-value" style="color: var(--pnk-pink);">${data.stats.activas_prop}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Usuarios Verificados</span>
                        <span class="stat-value">${data.stats.total_user}</span>
                    </div>
                </div>
                <div class="content-card" style="margin-top: 32px; border-top: 1px solid #e2e8f0; padding-top: 24px;">
                    <div class="card-header"><h3>Actividad Reciente</h3></div>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <tbody>${data.ultimos_usuarios.map(u => `<tr><td>${u.nombre}</td><td>${u.tipo}</td><td>${u.estado}</td></tr>`).join('')}</tbody>
                        </table>
                    </div>
                </div>`;
            });
    }

    // B) VISTA PROPIEDADES
    window.editarPropiedad = function(id) {
        const prop = bdPropiedades.find(p => p.id === id);
        if(!prop) return;
        
        document.getElementById("modal-prop-title").textContent = "Editar Propiedad";
        document.getElementById("prop-id").value = prop.id;
        document.getElementById("admin-prop-codigo").value = prop.codigo;
        document.getElementById("admin-prop-tipo").value = prop.tipo;
        document.getElementById("admin-prop-ubicacion").value = prop.ubicacion;
        document.getElementById("admin-prop-dueno").value = prop.dueno;
        document.getElementById("admin-prop-precio").value = prop.precio;
        document.getElementById("admin-prop-estado").value = prop.estado;
        
        modalProp.classList.add("visible");
    };

    window.eliminarPropiedad = function(id) {
        if(confirm("¿Estás seguro de eliminar esta propiedad permanentemente?")) {
            bdPropiedades = bdPropiedades.filter(p => p.id !== id);
            renderPropiedades();
            showToast("Propiedad eliminada", "error");
        }
    };

    function renderPropiedades(filtroDueno = null) {
        activarTab("propiedades");
        sectionTitle.innerHTML = filtroDueno ? `Filtrado por: <span>${filtroDueno}</span>` : "Gestión de <span>Inventario</span>";
        sectionDesc.textContent = filtroDueno ? "Resultados específicos por titular de cuenta." : "Control y supervisión de activos inmobiliarios registrados.";
        btnAddMain.style.display = "block";
        btnAddMain.textContent = "+ Nueva Propiedad";
        
        // Asignar función al botón principal
        btnAddMain.onclick = () => {
            formProp.reset();
            document.getElementById("prop-id").value = "";
            document.getElementById("modal-prop-title").textContent = "Nueva Propiedad";
            modalProp.classList.add("visible");
        };

        fetch('api_dashboard.php?action=get_propiedades')
            .then(res => res.json())
            .then(data => {
                bdPropiedades = data;
                let propsAMostrar = bdPropiedades;
                
                if(filtroDueno) {
                    propsAMostrar = bdPropiedades.filter(p => p.dueno === filtroDueno);
                }

                contentArea.innerHTML = `
            ${filtroDueno ? `<button class="btn-action" style="margin-bottom: 20px;" onclick="renderPropiedades()">Volver al Listado General</button>` : ''}
            <div class="content-card">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr><th>Código</th><th>Tipo / Ubicación</th><th>Propietario</th><th>Precio (UF)</th><th>Estado</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            ${propsAMostrar.length === 0 ? '<tr><td colspan="6" style="text-align:center;">No hay propiedades registradas</td></tr>' : ''}
                            ${propsAMostrar.map(p => `
                                <tr>
                                    <td><strong>${p.codigo}</strong></td>
                                    <td>${p.tipo}<br><small style="color:#666;">${p.ubicacion}</small></td>
                                    <td>${p.dueno}</td>
                                    <td><strong>${p.precio}</strong></td>
                                    <td><span class="badge-status ${p.estado.toLowerCase()}">${p.estado}</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action" onclick="editarPropiedad(${p.id})">Editar</button>
                                            <button class="btn-action delete" onclick="eliminarPropiedad(${p.id})">Eliminar</button>
                                        </div>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
            });
    }

    // C) VISTA USUARIOS (CONTROL TOTAL)
    window.cambiarEstadoUsuario = function(id, nuevoEstado) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('estado', nuevoEstado);

        fetch('api_dashboard.php?action=update_user_status', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderUsuarios(); // Recargamos la lista desde la BD
                showToast(`Usuario marcado como ${nuevoEstado}`);
            } else {
                showToast("No se pudo actualizar el estado", "error");
            }
        })
        .catch(err => {
            console.error(err);
            showToast("Error de conexión", "error");
        });
    };

    window.verPropiedadesUsuario = function(nombreDueno) {
        renderPropiedades(nombreDueno);
    };

    window.editarUsuario = function(id) {
        const usr = bdUsuarios.find(u => u.id === id);
        if(!usr) return;
        
        document.getElementById("modal-usr-title").textContent = "Editar Usuario";
        document.getElementById("usr-id").value = usr.id;
        document.getElementById("admin-usr-nombre").value = usr.nombre;
        document.getElementById("admin-usr-rut").value = usr.rut;
        document.getElementById("admin-usr-email").value = usr.email;
        document.getElementById("admin-usr-telefono").value = usr.telefono;
        document.getElementById("admin-usr-pass").value = "";
        document.getElementById("admin-usr-tipo").value = usr.tipo;
        document.getElementById("admin-usr-estado").value = usr.estado;
        
        modalUsr.classList.add("visible");
    };

    window.eliminarUsuario = function(id) {
        if(confirm("¡ATENCIÓN! ¿Eliminar este usuario de forma permanente?")) {
            bdUsuarios = bdUsuarios.filter(u => u.id !== id);
            renderUsuarios();
            showToast("Usuario eliminado correctamente", "error");
        }
    };

    function renderUsuarios() {
        activarTab("usuarios");
        sectionTitle.innerHTML = "Directorio de <span>Usuarios</span>";
        sectionDesc.textContent = "Administración de perfiles, permisos y verificación de estados de cuenta.";
        btnAddMain.style.display = "block";
        btnAddMain.textContent = "+ Crear Usuario";

        btnAddMain.onclick = () => {
            formUsr.reset();
            document.getElementById("usr-id").value = "";
            document.getElementById("modal-usr-title").textContent = "Nuevo Usuario";
            document.getElementById("admin-usr-pass").required = true;
            modalUsr.classList.add("visible");
        };

        fetch('api_dashboard.php?action=get_usuarios')
            .then(res => res.json())
            .then(data => {
                bdUsuarios = data;
                contentArea.innerHTML = `
                <div class="content-card">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr><th>Datos Personales</th><th>Contacto</th><th>Tipo</th><th>Estado</th><th style="min-width: 160px;">Acciones Admin</th></tr>
                            </thead>
                            <tbody>
                                ${bdUsuarios.length === 0 ? '<tr><td colspan="5" style="text-align:center;">No hay usuarios registrados</td></tr>' : ''}
                                ${bdUsuarios.map(u => `
                                    <tr>
                                        <td><strong>${u.nombre}</strong><br><small style="color:#666;">RUT: ${u.rut}</small></td>
                                        <td><small>✉️ ${u.email}</small><br><small>📞 ${u.telefono}</small></td>
                                        <td><strong style="text-transform:capitalize;">${u.tipo}</strong></td>
                                        <td><span class="badge-status ${u.estado.toLowerCase()}">${u.estado}</span></td>
                                        <td>
                                            <div class="action-buttons">
                                                ${u.estado === 'Pendiente' ? `
                                                    <button class="btn-action approve" onclick="cambiarEstadoUsuario(${u.id}, 'Aprobado')">Autorizar</button>
                                                ` : ''}
                                                <button class="btn-action" onclick="verPropiedadesUsuario('${u.nombre}')">Activos</button>
                                                <button class="btn-action" onclick="editarUsuario(${u.id})">Ficha</button>
                                                <button class="btn-action delete" onclick="eliminarUsuario(${u.id})">Baja</button>
                                            </div>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>`;
            });
    }

    // D) VISTAS ADICIONALES PROFESIONALES
    function renderReportes() {
        activarTab("reportes");
        sectionTitle.innerHTML = "Auditoría y <span>Reportes</span>";
        sectionDesc.textContent = "Seguimiento de actividad del sistema y exportación de datos operativos.";
        btnAddMain.style.display = "none";
        contentArea.innerHTML = `
            <div class="content-card" style="padding: 40px; text-align: center;">
                <p style="color: #6B7280; font-weight: 600;">Módulo de generación de reportes en formato PDF/Excel y logs de acceso.</p>
                <button class="btn-primary" style="max-width: 250px; margin-top: 20px;">Generar Reporte Mensual</button>
            </div>`;
    }

    function renderConfig() {
        activarTab("config");
        sectionTitle.innerHTML = "Configuración del <span>Sistema</span>";
        sectionDesc.textContent = "Parámetros globales, gestión de roles y mantenimiento técnico.";
        btnAddMain.style.display = "none";
        contentArea.innerHTML = `<div class="content-card" style="padding: 40px;"><p>Ajustes de servidor, límites de subida y variables de entorno.</p></div>`;
    }

    // 5. MANEJO DE EVENTOS DE FORMULARIOS Y MENÚ

    // Navegación Sidebar
    navItems.forEach(btn => {
        btn.addEventListener("click", function() {
            const section = this.getAttribute("data-section");
            if(section === "resumen") renderResumen();
            if(section === "propiedades") renderPropiedades();
            if(section === "usuarios") renderUsuarios();
            if(section === "reportes") renderReportes();
            if(section === "config") renderConfig();
        });
    });

    // Guardar Propiedad
    formProp.addEventListener("submit", (e) => {
        e.preventDefault();
        const id = document.getElementById("prop-id").value;
        const nuevaData = {
            codigo: document.getElementById("admin-prop-codigo").value,
            tipo: document.getElementById("admin-prop-tipo").value,
            ubicacion: document.getElementById("admin-prop-ubicacion").value,
            dueno: document.getElementById("admin-prop-dueno").value,
            precio: document.getElementById("admin-prop-precio").value,
            estado: document.getElementById("admin-prop-estado").value
        };

        if(id) { 
            const index = bdPropiedades.findIndex(p => p.id == id);
            if(index !== -1) bdPropiedades[index] = { ...bdPropiedades[index], ...nuevaData };
            showToast("Propiedad actualizada");
        } else { 
            nuevaData.id = Date.now();
            bdPropiedades.push(nuevaData);
            showToast("Propiedad agregada exitosamente");
        }
        modalProp.classList.remove("visible");
        renderPropiedades();
    });

    // Guardar Usuario
    formUsr.addEventListener("submit", (e) => {
        e.preventDefault();
        
        const rut = document.getElementById("admin-usr-rut").value;
        const email = document.getElementById("admin-usr-email").value;

        if (!validarRut(rut)) { showToast("RUT Inválido", "error"); return; }
        if (!validarEmail(email)) { showToast("Email Inválido", "error"); return; }

        const formData = new FormData();
        formData.append('id', document.getElementById("usr-id").value);
        formData.append('nombre', document.getElementById("admin-usr-nombre").value);
        formData.append('rut', rut);
        formData.append('email', email);
        formData.append('telefono', document.getElementById("admin-usr-telefono").value);
        formData.append('password', document.getElementById("admin-usr-pass").value);
        formData.append('tipo', document.getElementById("admin-usr-tipo").value);
        formData.append('estado', document.getElementById("admin-usr-estado").value);

        const isEdit = document.getElementById("usr-id").value !== "";

        if (!isEdit && !document.getElementById("admin-usr-pass").value) {
            showToast("La contraseña es obligatoria para nuevos usuarios", "error");
            return;
        }

        fetch('api_dashboard.php?action=save_user', {
            method: 'POST',
            body: formData
        })
        .then(res => {
            if (!res.ok) throw new Error("Error en el servidor");
            return res.json();
        })
        .then(data => {
            if(data.success) {
                showToast(data.message);
                modalUsr.classList.remove("visible");
                renderUsuarios();
            } else {
                showToast(data.message, "error");
            }
        })
        .catch(err => {
            console.error(err);
            showToast("No se pudo conectar con el servidor o hubo un error inesperado.", "error");
        });
    });

    // Cerrar Modales
    btnCerrarModalProp.addEventListener("click", () => modalProp.classList.remove("visible"));
    btnCerrarModalUsr.addEventListener("click", () => modalUsr.classList.remove("visible"));
    
    [modalProp, modalUsr].forEach(modal => {
        modal.addEventListener("click", (e) => { if(e.target === modal) modal.classList.remove("visible"); });
    });

    // 6. INICIAR DASHBOARD
    renderResumen();
});