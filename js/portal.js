document.addEventListener("DOMContentLoaded", function() {
    const contentArea = document.getElementById("portal-content-area");
    const navItems = document.querySelectorAll(".nav-item");
    const modal = document.getElementById("modal-propiedad-user");
    const form = document.getElementById("form-propiedad-user");
    const btnCerrar = document.getElementById("btn-cerrar-modal");

    function formatoPeso(monto) {
        return new Intl.NumberFormat('es-CL').format(monto || 0);
    }

    function cargarSeccion(seccion) {
        contentArea.innerHTML = "<div class='user-welcome'><h3>Cargando información...</h3></div>";
        
        if (seccion === "catalogo") {
            fetch('backend/api_dashboard.php?action=get_propiedades')
                .then(res => res.ok ? res.json() : Promise.reject())
                .then(data => {
                    // ELIMINA MODALES FALSOS DEL HTML SI EXISTEN
                    let viejoContenedor = document.getElementById('modales-dinamicos-pnk');
                    if(viejoContenedor) viejoContenedor.remove();

                    let contenedorModales = document.createElement('div');
                    contenedorModales.id = 'modales-dinamicos-pnk';
                    document.body.appendChild(contenedorModales);

                    let cartasHTML = '';
                    let modalesHTML = '';

                    data.forEach(p => {
                        let galeria = [];
                        try { galeria = JSON.parse(p.galeria_fotos || '[]'); } catch(e){}
                        let imgPrincipal = p.imagen_principal || 'default.jpg';

                        // CREA LA TARJETA
                        cartasHTML += `
                        <div class="stat-card" style="padding:0; overflow:hidden; border-radius:12px; border:1px solid #e5e7eb; box-shadow:0 4px 6px rgba(0,0,0,0.05);">
                            <img src="uploads/${imgPrincipal}" style="width:100%; height:200px; object-fit:cover;" onerror="this.src='img/pnkpnk.png'">
                            <div style="padding:20px;">
                                <span style="background:#fff1f2; color:#FF0066; padding:5px 10px; border-radius:4px; font-size:12px; font-weight:bold;">${p.tipo}</span>
                                <h4 style="color:#FF0066; font-weight:bold; margin:12px 0;">$ ${formatoPeso(p.precio)}</h4>
                                <p style="color:#6b7280; font-size:14px;"><i class="fas fa-map-marker-alt" style="color:#FF0066;"></i> ${p.comuna}, ${p.sector}</p>
                                <button onclick="abrirModalPNK(${p.id})" style="width:100%; padding:12px; background:#000; color:#fff; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Ver Detalles</button>
                            </div>
                        </div>`;

                        // FILTRA FOTOS PARA LA GALERÍA
                        let fotosExtra = galeria.filter(f => f !== imgPrincipal);
                        let htmlFotosExtra = fotosExtra.map(f => `<img src="uploads/${f}" style="width:200px; height:130px; object-fit:cover; border-radius:8px; border:1px solid #e5e7eb;" onerror="this.style.display='none'">`).join('');
                        if(htmlFotosExtra === '') htmlFotosExtra = '<div style="width:100%; text-align:center; padding:10px; background:#fff; border:1px dashed #cbd5e1; border-radius:8px; color:#9ca3af;">Sin fotos adicionales</div>';

                        // CREA EL MODAL DINÁMICO (CERO FOTOS FALSAS)
                        modalesHTML += `
                        <div id="modal-pnk-${p.id}" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:99999; justify-content:center; align-items:center; padding:20px;">
                            <div style="background:#f8fafc; width:100%; max-width:800px; max-height:90vh; overflow-y:auto; border-radius:16px; padding:25px; position:relative;">
                                <button onclick="cerrarModalPNK(${p.id})" style="position:absolute; top:15px; right:15px; background:#FF0066; color:#fff; border:none; width:40px; height:40px; border-radius:50%; font-size:22px; cursor:pointer; font-weight:bold;">&times;</button>
                                
                                <img src="uploads/${imgPrincipal}" style="width:100%; height:380px; object-fit:cover; border-radius:12px; margin-bottom:20px;" onerror="this.src='img/pnkpnk.png'">
                                
                                <div style="display:flex; gap:15px; overflow-x:auto; padding-bottom:15px; margin-bottom:20px;">
                                    ${htmlFotosExtra}
                                </div>
                                
                                <div style="background:#fff; padding:20px; border-left:4px solid #FF0066; margin-bottom:20px; border-radius:4px; box-shadow:0 2px 4px rgba(0,0,0,0.02);">
                                    <p style="margin:0; color:#4b5563; white-space:pre-line; font-size:15px;">${p.descripcion}</p>
                                </div>
                                
                                <div style="display:flex; justify-content:space-around; background:#0f172a; color:#fff; padding:20px; border-radius:8px; margin-bottom:20px; text-align:center;">
                                    <div><small style="color:#94a3b8; font-size:11px; text-transform:uppercase; letter-spacing:1px;">Área</small><br><strong style="font-size:18px;">${p.superficie} m²</strong></div>
                                    <div><small style="color:#94a3b8; font-size:11px; text-transform:uppercase; letter-spacing:1px;">Habitaciones</small><br><strong style="font-size:18px;">${p.dormitorios}</strong></div>
                                    <div><small style="color:#94a3b8; font-size:11px; text-transform:uppercase; letter-spacing:1px;">Baños</small><br><strong style="font-size:18px;">${p.banos}</strong></div>
                                </div>
                                
                                <div style="background:#f1f5f9; padding:20px; border-radius:8px; margin-bottom:25px; color:#1e293b; font-size:15px;">
                                    <strong><i class="fas fa-map-pin" style="color:#FF0066; margin-right:8px;"></i>Ubicación:</strong> ${p.ubicacion} <br>
                                    <div style="margin-top:8px; color:#64748b;"><i class="fas fa-city" style="margin-right:8px;"></i>${p.comuna} | ${p.sector}</div>
                                </div>
                                
                                <button onclick="cerrarModalPNK(${p.id})" style="width:100%; padding:15px; background:#FF0066; color:#fff; border:none; border-radius:8px; font-weight:bold; font-size:16px; cursor:pointer;">CERRAR</button>
                            </div>
                        </div>`;
                    });

                    contentArea.innerHTML = `
                        <div class="user-welcome"><h3>Catálogo Inmobiliario</h3></div>
                        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:25px;">
                            ${cartasHTML}
                        </div>`;
                    
                    contenedorModales.innerHTML = modalesHTML;

                    // Exponer funciones al entorno global
                    window.abrirModalPNK = function(id) { document.getElementById('modal-pnk-'+id).style.display = 'flex'; };
                    window.cerrarModalPNK = function(id) { document.getElementById('modal-pnk-'+id).style.display = 'none'; };
                })
                .catch(() => contentArea.innerHTML = "<p>Error al cargar el catálogo de propiedades.</p>");
        } 
        else if (seccion === "mis-publicaciones") {
            fetch('backend/api_user.php?action=get_my_props')
                .then(res => res.ok ? res.json() : Promise.reject())
                .then(data => {
                    contentArea.innerHTML = `
                        <div class="content-card">
                            <div class="card-header"><h3>Mis Propiedades</h3></div>
                            <div class="table-responsive">
                                <table class="admin-table">
                                    <thead><tr><th>Tipo</th><th>Ubicación</th><th>Precio</th><th>Estado</th></tr></thead>
                                    <tbody>
                                        ${data.length === 0 ? '<tr><td colspan="4">No tienes publicaciones aún.</td></tr>' : 
                                          data.map(p => `<tr><td>${p.tipo}</td><td>${p.ubicacion}</td><td>$ ${formatoPeso(p.precio)}</td><td><span class="badge-status activo">Activo</span></td></tr>`).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>`;
                })
                .catch(() => contentArea.innerHTML = "<p>Error al cargar tus propiedades.</p>");
        }
    }

    navItems.forEach(item => {
        item.addEventListener("click", () => {
            navItems.forEach(i => i.classList.remove("active"));
            item.classList.add("active");
            cargarSeccion(item.getAttribute("data-section"));
        });
    });

    cargarSeccion("catalogo");
});