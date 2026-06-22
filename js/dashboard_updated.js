document.addEventListener("DOMContentLoaded", function () {
    // 1. REFERENCIAS DEL DOM
    const contentArea = document.getElementById("dashboard-content-area");
    const sectionTitle = document.getElementById("section-title");
    const sectionDesc = document.getElementById("section-desc");
    const btnAddMain = document.getElementById("btn-add-main");
    const navItems = document.querySelectorAll(".nav-item");

    // Modal de Propiedades
    const modalProp = document.getElementById("modal-propiedad");
    const formProp = document.getElementById("form-propiedad-admin");
    const btnCerrarModalProp = document.getElementById("btn-cerrar-modal-prop");

    // Modal de Usuarios
    const modalUsr = document.getElementById("modal-usuario");
    const formUsr = document.getElementById("form-usuario-admin");
    const btnCerrarModalUsr = document.getElementById("btn-cerrar-modal-usr");

    // 2. FUNCIONES DE SWEETALERT2
    function mostrarExito(mensaje) {
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: mensaje,
            confirmButtonColor: '#FF0066',
            timer: 2000
        });
    }

    function mostrarError(mensaje) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: mensaje,
            confirmButtonColor: '#FF0066'
        });
    }

    function mostrarConfirmacion(titulo, mensaje, callback) {
        Swal.fire({
            title: titulo,
            text: mensaje,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#FF0066',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                callback();
            }
        });
    }

    // 3. FORMATEADORES AUTOMÁTICOS
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

    // 4. CERRAR SESIÓN
    const btnCerrarSesion = document.getElementById("btn-cerrar-sesion");
    if (btnCerrarSesion) {
        btnCerrarSesion.addEventListener("click", () => {
            mostrarConfirmacion("¿Cerrar sesión?", "Se cerrará tu sesión en el sistema", () => {
                window.location.href = "../backend/logout.php";
            });
        });
    }

    // 5. CAMBIAR DE PESTAÑA
    function activarTab(sectionName) {
        navItems.forEach(b => b.classList.remove("active"));
        const target = document.querySelector(`[data-section="${sectionName}"]`);
        if(target) target.classList.add("active");
    }

    // ========== VISTA RESUMEN ==========
    function renderResumen() {
        activarTab("resumen");
        sectionTitle.innerHTML = "Consola de <span>Resumen</span>";
        sectionDesc.textContent = "Estado operativo global de la plataforma.";
        btnAddMain.style.display = "none";

        fetch('api_dashboard_updated.php?action=get_resumen')
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
            })
            .catch(() => mostrarError("Error al cargar el resumen"));
    }

    // ========== VISTA PROPIEDADES ==========
    window.editarPropiedad = function(id) {
        fetch(`api_dashboard_updated.php?action=get_propiedades`)
            .then(res => res.json())
            .then(data => {
                const prop = data.find(p => p.id === id);
                if(!prop) return;
                
                document.getElementById("modal-prop-title").textContent = "Editar Propiedad";
                document.getElementById("prop-id").value = prop.id;
                document.getElementById("admin-prop-codigo").value = prop.codigo;
                document.getElementById("admin-prop-tipo").value = prop.tipo;
                document.getElementById("admin-prop-ubicacion").value = prop.ubicacion;
                document.getElementById("admin-prop-comuna").value = prop.comuna;
                document.getElementById("admin-prop-sector").value = prop.sector;
                document.getElementById("admin-prop-precio").value = prop.precio;
                document.getElementById("admin-prop-dormitorios").value = prop.dormitorios;
                document.getElementById("admin-prop-banos").value = prop.banos;
                document.getElementById("admin-prop-superficie").value = prop.superficie;
                document.getElementById("admin-prop-descripcion").value = prop.descripcion;
                document.getElementById("admin-prop-estado").value = prop.estado;
                
                modalProp.classList.add("visible");
            });
    };

    window.eliminarPropiedad = function(id) {
        mostrarConfirmacion(
            "¿Eliminar propiedad?",
            "Esta acción no se puede deshacer",
            () => {
                const formData = new FormData();
                formData.append("id", id);

                fetch("api_dashboard_updated.php?action=delete_prop_admin", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        mostrarExito("Propiedad eliminada");
                        renderPropiedades();
                    } else {
                        mostrarError(data.message);
                    }
                });
            }
        );
    };

    function renderPropiedades(filtroDueno = null) {
        activarTab("propiedades");
        sectionTitle.innerHTML = filtroDueno ? `Filtrado por: <span>${filtroDueno}</span>` : "Gestión de <span>Inventario</span>";
        sectionDesc.textContent = filtroDueno ? "Resultados específicos por titular de cuenta." : "Control y supervisión de activos inmobiliarios registrados.";
        btnAddMain.style.display = "block";
        btnAddMain.textContent = "+ Nueva Propiedad";
        
        btnAddMain.onclick = () => {
            formProp.reset();
            document.getElementById("prop-id").value = "";
            document.getElementById("modal-prop-title").textContent = "Nueva Propiedad";
            modalProp.classList.add("visible");
        };

        fetch('api_dashboard_updated.php?action=get_propiedades')
            .then(res => res.json())
            .then(data => {
                let propsAMostrar = data;
                
                if(filtroDueno) {
                    propsAMostrar = data.filter(p => p.dueno === filtroDueno);
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
            })
            .catch(() => mostrarError("Error al cargar propiedades"));
    }

    // ========== VISTA USUARIOS ==========
    window.cambiarEstadoUsuario = function(id, nuevoEstado) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('estado', nuevoEstado);

        fetch('api_dashboard_updated.php?action=update_user_status', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                mostrarExito(data.message);
                renderUsuarios();
            } else {
                mostrarError("No se pudo actualizar el estado");
            }
        })
        .catch(err => mostrarError("Error de conexión"));
    };

    window.verPropiedadesUsuario = function(nombreDueno) {
        renderPropiedades(nombreDueno);
    };

    window.editarUsuario = function(id) {
        fetch(`api_dashboard_updated.php?action=get_usuarios`)
            .then(res => res.json())
            .then(data => {
                const usr = data.find(u => u.id === id);
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
            });
    };

    window.eliminarUsuario = function(id) {
        mostrarConfirmacion(
            "¿Eliminar usuario?",
            "Se eliminarán también todas sus propiedades",
            () => {
                const formData = new FormData();
                formData.append("id", id);

                fetch("api_dashboard_updated.php?action=delete_user", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        mostrarExito("Usuario eliminado");
                        renderUsuarios();
                    } else {
                        mostrarError(data.message);
                    }
                });
            }
        );
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

        fetch('api_dashboard_updated.php?action=get_usuarios')
            .then(res => res.json())
            .then(data => {
                contentArea.innerHTML = `
                <div class="content-card">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr><th>Datos Personales</th><th>Contacto</th><th>Tipo</th><th>Estado</th><th style="min-width: 160px;">Acciones Admin</th></tr>
                            </thead>
                            <tbody>
                                ${data.length === 0 ? '<tr><td colspan="5" style="text-align:center;">No hay usuarios registrados</td></tr>' : ''}
                                ${data.map(u => `
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
            })
            .catch(() => mostrarError("Error al cargar usuarios"));
    }

    // ========== MANEJO DE FORMULARIOS ==========
    if (formProp) {
        formProp.addEventListener("submit", (e) => {
            e.preventDefault();
            const formData = new FormData(formProp);
            
            fetch('api_dashboard_updated.php?action=save_prop_admin', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    mostrarExito(data.message);
                    modalProp.classList.remove("visible");
                    formProp.reset();
                    renderPropiedades();
                } else {
                    mostrarError(data.message);
                }
            })
            .catch(() => mostrarError("Error al guardar"));
        });
    }

    if (formUsr) {
        formUsr.addEventListener("submit", (e) => {
            e.preventDefault();
            const formData = new FormData(formUsr);
            
            fetch('api_dashboard_updated.php?action=save_user', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    mostrarExito(data.message);
                    modalUsr.classList.remove("visible");
                    formUsr.reset();
                    renderUsuarios();
                } else {
                    mostrarError(data.message);
                }
            })
            .catch(() => mostrarError("Error al guardar"));
        });
    }

    if (btnCerrarModalProp) {
        btnCerrarModalProp.addEventListener("click", () => modalProp.classList.remove("visible"));
    }

    if (btnCerrarModalUsr) {
        btnCerrarModalUsr.addEventListener("click", () => modalUsr.classList.remove("visible"));
    }

    // ========== NAVEGACIÓN PRINCIPAL ==========
    navItems.forEach(item => {
        item.addEventListener("click", () => {
            navItems.forEach(i => i.classList.remove("active"));
            item.classList.add("active");
            const section = item.getAttribute("data-section");
            
            if (section === "resumen") renderResumen();
            else if (section === "propiedades") renderPropiedades();
            else if (section === "usuarios") renderUsuarios();
        });
    });

    // Cargar resumen por defecto
    renderResumen();
});
