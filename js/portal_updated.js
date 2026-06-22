document.addEventListener("DOMContentLoaded", function() {
    const contentArea = document.getElementById("portal-content-area");
    const navItems = document.querySelectorAll(".nav-item");
    const modal = document.getElementById("modal-propiedad-user");
    const form = document.getElementById("form-propiedad-user");
    const btnCerrar = document.getElementById("btn-cerrar-modal");
    
    // Modal de fotos
    const modalFotos = document.getElementById("modal-fotos");
    const uploadArea = document.getElementById("upload-area");
    const fileInput = document.getElementById("file-input");
    const fotosPreview = document.getElementById("fotos-preview");
    const btnCerrarFotos = document.getElementById("btn-cerrar-fotos");
    const btnSubirFotos = document.getElementById("btn-subir-fotos");
    
    let propiedadActual = null;
    let archivosSeleccionados = [];

    // ========== FUNCIONES SWEETALERT2 ==========
    function mostrarExito(mensaje) {
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: mensaje,
            confirmButtonColor: '#FF0066'
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

    // ========== FUNCIONES DE CARGA DE FOTOS ==========
    uploadArea.addEventListener("click", () => fileInput.click());

    uploadArea.addEventListener("dragover", (e) => {
        e.preventDefault();
        uploadArea.classList.add("dragover");
    });

    uploadArea.addEventListener("dragleave", () => {
        uploadArea.classList.remove("dragover");
    });

    uploadArea.addEventListener("drop", (e) => {
        e.preventDefault();
        uploadArea.classList.remove("dragover");
        const files = e.dataTransfer.files;
        manejarArchivos(files);
    });

    fileInput.addEventListener("change", (e) => {
        manejarArchivos(e.target.files);
    });

    function manejarArchivos(files) {
        archivosSeleccionados = Array.from(files);
        fotosPreview.innerHTML = "";
        
        archivosSeleccionados.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement("div");
                div.style.cssText = "position: relative; display: inline-block; margin: 10px; border-radius: 8px; overflow: hidden;";
                div.innerHTML = `
                    <img src="${e.target.result}" style="width: 100px; height: 100px; object-fit: cover;">
                    <button type="button" style="position: absolute; top: 5px; right: 5px; background: red; color: white; border: none; border-radius: 50%; width: 25px; height: 25px; cursor: pointer; font-weight: bold;" onclick="this.parentElement.remove()">×</button>
                `;
                fotosPreview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }

    btnSubirFotos.addEventListener("click", () => {
        if (archivosSeleccionados.length === 0) {
            mostrarError("Selecciona al menos una foto");
            return;
        }

        if (!propiedadActual) {
            mostrarError("Debes guardar la propiedad primero");
            return;
        }

        const formData = new FormData();
        formData.append("prop_id", propiedadActual);
        archivosSeleccionados.forEach(file => {
            formData.append("fotos[]", file);
        });

        btnSubirFotos.disabled = true;
        btnSubirFotos.textContent = "Subiendo...";

        fetch("api_user_updated.php?action=upload_fotos", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btnSubirFotos.disabled = false;
            btnSubirFotos.textContent = "Subir Fotos";

            if (data.success) {
                mostrarExito(`${data.fotos_subidas} foto(s) subida(s) correctamente`);
                modalFotos.classList.remove("visible");
                archivosSeleccionados = [];
                fileInput.value = "";
                fotosPreview.innerHTML = "";
                cargarSeccion("mis-publicaciones");
            } else {
                let mensaje = "Error al subir fotos";
                if (data.errores) {
                    mensaje += ": " + data.errores.join(", ");
                }
                mostrarError(mensaje);
            }
        })
        .catch(err => {
            btnSubirFotos.disabled = false;
            btnSubirFotos.textContent = "Subir Fotos";
            mostrarError("Error de conexión al servidor");
        });
    });

    btnCerrarFotos.addEventListener("click", () => {
        modalFotos.classList.remove("visible");
        archivosSeleccionados = [];
        fileInput.value = "";
        fotosPreview.innerHTML = "";
    });

    // ========== CARGAR SECCIONES ==========
    function cargarSeccion(seccion) {
        contentArea.innerHTML = "<div class='user-welcome'><h3>Cargando información...</h3></div>";
        
        if (seccion === "catalogo") {
            cargarCatalogo();

        } else if (seccion === "mis-publicaciones") {
            cargarMisPublicaciones();

        } else if (seccion === "publicar") {
            form.reset();
            document.getElementById("user-prop-id").value = "";
            document.getElementById("modal-title").textContent = "Publicar Propiedad";
            propiedadActual = null;
            modal.classList.add("visible");
            contentArea.innerHTML = "<div class='user-welcome'><h3>Utiliza el formulario emergente para publicar.</h3></div>";

        } else if (seccion === "perfil") {
            contentArea.innerHTML = `
                <div class="perfil-card">
                    <h2>Mi Perfil</h2>
                    <hr style="margin: 15px 0; border: 0; border-top: 1px solid #eee;">
                    <p><strong>Nombre:</strong> ${USER_NAME}</p>
                    <p><strong>Rol de Cuenta:</strong> <span style="text-transform: capitalize;">${USER_ROLE}</span></p>
                    <p style="margin-top: 20px; font-size: 0.9rem; color: #666;">Para cambiar tus datos de contacto, solicita soporte al administrador.</p>
                </div>`;
        }
    }

    function cargarCatalogo() {
        fetch('api_public.php?action=get_propiedades_activas')
            .then(res => res.ok ? res.json() : Promise.reject())
            .then(data => {
                contentArea.innerHTML = `
                    <div class="user-welcome"><h3>Propiedades Disponibles en la IV Región</h3></div>
                    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                        ${data.map(p => `
                            <div class="stat-card" style="cursor: pointer;" onclick="verDetallePublico(${p.id})">
                                <div style="width:100%; position: relative; height: 150px; background: #f0f0f0; border-radius: 8px; overflow: hidden; margin-bottom: 10px;">
                                    <img src="${p.imagen_principal || 'img/placeholder.jpg'}" style="width: 100%; height: 100%; object-fit: cover;" alt="${p.tipo}">
                                </div>
                                <strong>${p.tipo} en ${p.comuna}</strong><br>
                                <span style="color:var(--color-rosado); font-weight: bold;">UF ${p.precio}</span>
                                <br><small>${p.ubicacion}</small>
                                <br><small style="color: #999;">Vendedor: ${p.dueno}</small>
                            </div>
                        `).join('')}
                    </div>`;
            })
            .catch(() => contentArea.innerHTML = "<p>Error al cargar el catálogo.</p>");
    }

    function cargarMisPublicaciones() {
        fetch('api_user_updated.php?action=get_my_props')
            .then(res => res.ok ? res.json() : Promise.reject())
            .then(data => {
                let html = `
                    <div class="content-card">
                        <div class="card-header"><h3>Mis Propiedades</h3></div>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead><tr><th>Tipo</th><th>Ubicación</th><th>Precio</th><th>Estado</th><th>Acciones</th></tr></thead>
                                <tbody>`;
                
                if (data.length === 0) {
                    html += '<tr><td colspan="5">No tienes publicaciones aún.</td></tr>';
                } else {
                    data.forEach(p => {
                        html += `
                            <tr>
                                <td>${p.tipo}</td>
                                <td>${p.ubicacion}</td>
                                <td>UF ${p.precio}</td>
                                <td><span class="badge-status ${p.estado.toLowerCase()}">${p.estado}</span></td>
                                <td>
                                    <button class="btn-action" onclick="editarPropiedad(${p.id})">Editar</button>
                                    <button class="btn-action" onclick="abrirModalFotos(${p.id})">Fotos</button>
                                    <button class="btn-action delete" onclick="eliminarPropiedad(${p.id})">Eliminar</button>
                                </td>
                            </tr>`;
                    });
                }
                html += '</tbody></table></div></div>';
                contentArea.innerHTML = html;
            })
            .catch(() => contentArea.innerHTML = "<p>Error al cargar tus propiedades.</p>");
    }

    // ========== FUNCIONES GLOBALES ==========
    window.editarPropiedad = function(id) {
        fetch(`api_user_updated.php?action=get_prop_detail&id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const p = data.data;
                    document.getElementById("user-prop-id").value = p.id;
                    document.getElementById("user-prop-tipo").value = p.tipo;
                    document.getElementById("user-prop-comuna").value = p.comuna;
                    document.getElementById("user-prop-sector").value = p.sector;
                    document.getElementById("user-prop-ubicacion").value = p.ubicacion;
                    document.getElementById("user-prop-precio").value = p.precio;
                    document.getElementById("user-prop-dormitorios").value = p.dormitorios;
                    document.getElementById("user-prop-banos").value = p.banos;
                    document.getElementById("user-prop-superficie").value = p.superficie;
                    document.getElementById("user-prop-descripcion").value = p.descripcion;
                    document.getElementById("modal-title").textContent = "Editar Propiedad";
                    propiedadActual = p.id;
                    modal.classList.add("visible");
                }
            });
    };

    window.abrirModalFotos = function(id) {
        propiedadActual = id;
        modalFotos.classList.add("visible");
    };

    window.eliminarPropiedad = function(id) {
        mostrarConfirmacion(
            "¿Eliminar propiedad?",
            "Esta acción no se puede deshacer",
            () => {
                const formData = new FormData();
                formData.append("id", id);
                formData.append("estado", "Eliminada");

                fetch("api_user_updated.php?action=delete_prop", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        mostrarExito("Propiedad eliminada");
                        cargarSeccion("mis-publicaciones");
                    } else {
                        mostrarError(data.message);
                    }
                });
            }
        );
    };

    window.verDetallePublico = function(id) {
        fetch(`api_public.php?action=get_propiedad_publica&id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const p = data.data;
                    let fotosHtml = '';
                    if (p.fotos && p.fotos.length > 0) {
                        fotosHtml = `
                            <div id="carouselPropiedad" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-inner">
                                    ${p.fotos.map((foto, idx) => `
                                        <div class="carousel-item ${idx === 0 ? 'active' : ''}">
                                            <img src="${foto.ruta}" class="d-block w-100" alt="Foto ${idx + 1}" style="height: 400px; object-fit: cover;">
                                        </div>
                                    `).join('')}
                                </div>
                                ${p.fotos.length > 1 ? `
                                    <button class="carousel-control-prev" type="button" data-bs-target="#carouselPropiedad" data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon"></span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#carouselPropiedad" data-bs-slide="next">
                                        <span class="carousel-control-next-icon"></span>
                                    </button>
                                ` : ''}
                            </div>`;
                    }

                    Swal.fire({
                        title: `${p.tipo} en ${p.comuna}`,
                        html: `
                            ${fotosHtml}
                            <div style="text-align: left; margin-top: 20px;">
                                <p><strong>Precio:</strong> UF ${p.precio}</p>
                                <p><strong>Ubicación:</strong> ${p.ubicacion}, ${p.sector}</p>
                                <p><strong>Dormitorios:</strong> ${p.dormitorios} | <strong>Baños:</strong> ${p.banos}</p>
                                <p><strong>Superficie:</strong> ${p.superficie} m²</p>
                                <p><strong>Descripción:</strong> ${p.descripcion}</p>
                                <p><strong>Contacto:</strong> ${p.dueno_email} | ${p.dueno_telefono}</p>
                            </div>
                        `,
                        confirmButtonColor: '#FF0066',
                        width: '90%',
                        maxWidth: '600px'
                    });
                }
            });
    };

    // ========== MANEJO DEL FORMULARIO ==========
    if (form) {
        form.addEventListener("submit", function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            
            fetch('api_user_updated.php?action=save_prop', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    mostrarExito(data.message);
                    propiedadActual = data.prop_id;
                    modal.classList.remove("visible");
                    form.reset();
                    // Preguntar si desea agregar fotos
                    Swal.fire({
                        title: '¿Agregar fotos?',
                        text: 'Puedes subir fotos ahora o hacerlo después',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#FF0066',
                        confirmButtonText: 'Subir fotos ahora',
                        cancelButtonText: 'Después'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            abrirModalFotos(propiedadActual);
                        } else {
                            cargarSeccion("mis-publicaciones");
                        }
                    });
                } else {
                    mostrarError(data.message);
                }
            })
            .catch(() => mostrarError("Error al conectar con el servidor"));
        });
    }

    if (btnCerrar) {
        btnCerrar.addEventListener("click", () => modal.classList.remove("visible"));
    }

    navItems.forEach(item => {
        item.addEventListener("click", () => {
            navItems.forEach(i => i.classList.remove("active"));
            item.classList.add("active");
            cargarSeccion(item.getAttribute("data-section"));
        });
    });

    // Cargar catálogo por defecto
    cargarSeccion("catalogo");
});
