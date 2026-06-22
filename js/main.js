document.addEventListener("DOMContentLoaded", function () {
    /**
     * ======================================================================
     * 1. CORE DE VALIDADORES ESTRICTOS (Nivel Profesional)
     * ======================================================================
     */
    const Validadores = {
        rut: function(rut) {
            const limpio = String(rut).replace(/[^0-9kK]/g, "").toUpperCase();
            if (limpio.length < 8) return false;
            const cuerpo = limpio.slice(0, -1);
            const dv = limpio.slice(-1);
            let suma = 0, multi = 2;
            for (let i = cuerpo.length - 1; i >= 0; i--) {
                suma += parseInt(cuerpo.charAt(i), 10) * multi;
                multi = multi === 7 ? 2 : multi + 1;
            }
            const calc = 11 - (suma % 11);
            const dvExp = calc === 11 ? "0" : calc === 10 ? "K" : String(calc);
            return dv === dvExp;
        },
        nombre: function(nombre) {
            const limpio = String(nombre).trim();
            const regex = /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]+$/;
            return regex.test(limpio) && limpio.length >= 8 && limpio.length <= 60 && limpio.split(/\s+/).length >= 2;
        },
        telefono: function(tel) {
            const v = String(tel).replace(/\D/g, "");
            return /^569\d{8}$/.test(v);
        },
        email: function(email) {
            const regex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            const limpio = String(email).trim();
            return regex.test(limpio) && !/\.\./.test(limpio) && limpio.length <= 80;
        },
        password: function(pass) {
            const p = String(pass);
            return p.length >= 8 && p.length <= 24 && /[A-Z]/.test(p) && /[a-z]/.test(p) && /\d/.test(p) && /[^A-Za-z0-9]/.test(p) && !/\s/.test(p);
        },
        codigoPropiedad: function(codigo) {
            return /^[A-Za-z]{2,4}-?\d{3,8}$/.test(String(codigo).trim());
        },
        certificadoPdf: function(inputFile) {
            if (!inputFile || !inputFile.files || !inputFile.files[0]) return false;
            const file = inputFile.files[0];
            const maxSize = 3 * 1024 * 1024;
            const isPdf = file.type === "application/pdf" || /\.pdf$/i.test(file.name);
            return isPdf && file.size <= maxSize;
        },
        edad: function(fecha) {
            if (!fecha) return false;
            const nacimiento = new Date(fecha + "T00:00:00");
            if (Number.isNaN(nacimiento.getTime())) return false;
            const year = nacimiento.getFullYear();
            // Requerido: nacido en 2006 o antes (>=18 años en 2024+)
            if (year > 2006) return false;
            const hoy = new Date();
            let edad = hoy.getFullYear() - nacimiento.getFullYear();
            const mes = hoy.getMonth() - nacimiento.getMonth();
            if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) edad--;
            return edad >= 18;
        }
    };

    /**
     * ======================================================================
     * 2. GENERADOR DINÁMICO DE FECHAS (¡ARREGLADO!)
     * ======================================================================
     */
    function poblarSelectoresFecha(prefijo) {
        const selectDia = document.getElementById(`${prefijo}-dia`);
        const selectAnio = document.getElementById(`${prefijo}-anio`);
        
        if (selectDia) {
            for (let i = 1; i <= 31; i++) {
                const opt = document.createElement("option");
                opt.value = i < 10 ? "0" + i : i;
                opt.textContent = i;
                selectDia.appendChild(opt);
            }
        }
        
        if (selectAnio) {
            for (let i = 2026; i >= 1900; i--) {
                const opt = document.createElement("option");
                opt.value = i;
                opt.textContent = i;
                selectAnio.appendChild(opt);
            }
        }
    }

    // Inicializar los días y años en ambos formularios
    poblarSelectoresFecha("prop");
    poblarSelectoresFecha("gestor");

    function actualizarFechaOculta(prefijo) {
        const dia = document.getElementById(`${prefijo}-dia`).value;
        const mes = document.getElementById(`${prefijo}-mes`).value;
        const anio = document.getElementById(`${prefijo}-anio`).value;
        const inputOculto = document.getElementById(`${prefijo}-fecha`);
        
        if (dia && mes && anio) {
            inputOculto.value = `${anio}-${mes}-${dia}`;
            validarCampo(inputOculto);
        } else {
            inputOculto.value = "";
        }
    }

    ["prop", "gestor"].forEach(prefijo => {
        ["dia", "mes", "anio"].forEach(tipo => {
            const el = document.getElementById(`${prefijo}-${tipo}`);
            if (el) el.addEventListener("change", () => actualizarFechaOculta(prefijo));
        });
    });

    /**
     * ======================================================================
     * 3. CONTROLADORES DE INTERFAZ Y ALERTAS (UI)
     * ======================================================================
     */
    function mostrarAlerta(form, mensaje, tipo = "error") {
        let alerta = form.querySelector(".form-alert");
        if (!alerta) {
            alerta = document.createElement("div");
            alerta.className = "form-alert";
            form.prepend(alerta);
        }
        alerta.textContent = mensaje;
        alerta.className = `form-alert ${tipo}`;
        setTimeout(() => alerta.remove(), 4000); 
    }

    function setError(input, mensaje) {
        if (!input) return;
        const group = input.closest(".input-group");
        if(!group) return;
        group.classList.remove("success"); group.classList.add("error");
        input.style.borderColor = "#FF0066";
        let error = group.querySelector(".error-message");
        if (!error) {
            error = document.createElement("small");
            error.className = "error-message";
            error.style.cssText = "color: #FF0066; font-size: 11px; font-weight: bold; display: block; margin-top: 5px;";
            group.appendChild(error);
        }
        error.textContent = mensaje;
    }

    function setSuccess(input) {
        if (!input) return;
        const group = input.closest(".input-group");
        if(!group) return;
        group.classList.remove("error"); group.classList.add("success");
        input.style.borderColor = "#10B981";
        const error = group.querySelector(".error-message");
        if (error) error.remove();
    }

    function limpiarEstado(input) {
        if (!input) return;
        const group = input.closest(".input-group");
        if(!group) return;
        group.classList.remove("error", "success");
        input.style.borderColor = "";
        const error = group.querySelector(".error-message");
        if (error) error.remove();
    }

    // Formateadores automáticos en vivo para inputs de texto
    document.querySelectorAll("input[type='tel']").forEach(input => {
        input.addEventListener("input", (e) => {
            let valor = e.target.value.replace(/\D/g, "");
            if (valor.startsWith("569")) valor = valor.slice(3);
            else if (valor.startsWith("9") && valor.length > 1) valor = valor.slice(1);
            valor = valor.slice(0, 8);
            e.target.value = valor.length > 0 ? "+56 9 " + valor : "+56 9 ";
            limpiarEstado(e.target);
        });
    });

    // Formateador de RUT automático Global (Login y Registro)
    const inputsRut = document.querySelectorAll("input[id*='rut'], input[name*='rut'], #login-usuario");
    inputsRut.forEach(input => {
        input.addEventListener("input", (e) => {
            const inputVal = e.target.value;

            // Diferenciar entre RUT y Correo en el campo de login (mientras se escribe)
            if (e.target.id === "login-usuario") {
                // Detectamos si tiene letras (que no sean K al final) o símbolos de email
                const esCorreo = /[^0-9kK\.\-]/.test(inputVal) || 
                                 (inputVal.toUpperCase().includes("K") && inputVal.toUpperCase().lastIndexOf("K") !== inputVal.length - 1);
                
                if (esCorreo || inputVal.includes("@")) {
                    limpiarEstado(e.target);
                    return; // Es un correo, no aplicar lógica de RUT
                }
            }

            let val = inputVal.replace(/\./g, "").replace("-", "");

            // Solo procesamos si hay al menos 2 caracteres (número + DV)
            if (val.length > 1) {
                let cuerpo = val.slice(0, -1).replace(/\D/g, ""); // Extrae solo números del cuerpo
                let dv = val.slice(-1).toUpperCase(); // El último es el DV (puede ser K)
                
                if (cuerpo.length > 0) {
                    let formatado = "";
                    while (cuerpo.length > 3) {
                        formatado = "." + cuerpo.slice(-3) + formatado;
                        cuerpo = cuerpo.slice(0, -3);
                    }
                    e.target.value = cuerpo + formatado + "-" + dv;
                }
            }
            limpiarEstado(e.target);
        });
    });

    document.querySelectorAll("input[name='nombre']").forEach(input => {
        input.addEventListener("input", (e) => {
            e.target.value = e.target.value.replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]/g, "").slice(0, 30);
            limpiarEstado(e.target);
        });
    });

    /**
     * ======================================================================
     * 4. MASTER VALIDATOR PARA CAMPOS INDIVIDUALES
     * ======================================================================
     */
    function validarCampo(input) {
        if (!input) return true;
        const name = input.name || input.id;
        const valor = input.value.trim();
        
        if (input.required) {
            if (input.type === 'file') {
                if (!input.files || input.files.length === 0) { setError(input, 'Debe subir el archivo requerido.'); return false; }
            } else if (!valor) {
                setError(input, "Este campo es obligatorio.");
                return false;
            }
        }
        if (name.includes("rut")) {
            if (Validadores.rut(valor)) { setSuccess(input); return true; }
            setError(input, "RUT inválido. Verifica los dígitos."); return false;
        }
        if (name.includes("nombre")) {
            if (Validadores.nombre(valor)) { setSuccess(input); return true; }
            setError(input, "Solo letras y espacios. Entre 10 y 30 caracteres."); return false;
        }
        if (name.includes("fecha")) {
            if (Validadores.edad(valor)) { setSuccess(input); return true; }
            setError(input, "Debes ser mayor de 18 (nacido en 2006 o antes)."); return false;
        }
        if (name.includes("telefono")) {
            if (Validadores.telefono(valor)) { setSuccess(input); return true; }
            setError(input, "Formato requerido: +56 9 seguido de 8 dígitos."); return false;
        }
        if (name.includes("email")) {
            if (Validadores.email(valor)) { setSuccess(input); return true; }
            setError(input, "Ingresa un correo electrónico válido."); return false;
        }
        if (name.includes("password")) {
            if (Validadores.password(valor)) { setSuccess(input); return true; }
            setError(input, "8-24 caracteres, sin espacios, con mayúscula, minúscula, número y símbolo."); return false;
        }
        if (name.includes("numeroPropiedad")) {
            if (Validadores.codigoPropiedad(valor)) { setSuccess(input); return true; }
            setError(input, "Formato sugerido: BR-12345 (2-4 letras y 3-8 números)."); return false;
        }
        if (name.includes("certificado")) {
            if (Validadores.certificadoPdf(input)) { setSuccess(input); return true; }
            setError(input, "Sube un PDF válido de hasta 3MB."); return false;
        }
        if (name.includes("sexo")) { 
            if (valor) { setSuccess(input); return true; } 
            setError(input, "Selecciona una opción."); return false; 
        }
        setSuccess(input);
        return true;
    }

    function validarFormulario(form) {
        let valido = true;
        form.querySelectorAll("input[required], select[required]").forEach((campo) => {
            if (!validarCampo(campo)) valido = false;
        });
        return valido;
    }

    /**
     * ======================================================================
     * 5. MANEJO DE FORMULARIOS Y LOGIN
     * ======================================================================
     */
    const formLogin = document.getElementById("form-login");
    const formsSlider = document.getElementById("forms-slider");
    const formsWrapper = document.querySelector(".forms-wrapper");
    
    if (formsWrapper) formsWrapper.style.overflow = "hidden";
    if (formsSlider) {
        formsSlider.style.display = "flex";
        formsSlider.style.width = "200%";
        formsSlider.style.transition = "transform 0.4s cubic-bezier(0.4, 0, 0.2, 1)";
        
        const panels = formsSlider.querySelectorAll(".form-panel");
        panels.forEach(panel => {
            panel.style.width = "50%";
            panel.style.flexShrink = "0";
        });
    }

    if (formLogin) {
        formLogin.addEventListener("submit", (e) => {
            e.preventDefault();
            const btnSubmit = formLogin.querySelector("button[type='submit']");
            const txtOriginal = btnSubmit.textContent;
            const usuario = document.getElementById("login-usuario").value.trim();
            const pass = document.getElementById("login-password").value;

            if(!usuario || !pass) {
                mostrarAlerta(formLogin, "Completa todos los campos", "error"); return;
            }

            btnSubmit.textContent = "VERIFICANDO...";
            btnSubmit.disabled = true;

            const formData = new FormData();
            formData.append('usuario', usuario);
            formData.append('password', pass);

            fetch('backend/login_process.php', {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (err) {
                    // Si el servidor mandó texto en vez de JSON, lo capturamos aquí
                    console.error("Respuesta no válida del servidor:", text);
                    throw new Error("El servidor respondió con un error de texto: " + text.substring(0, 50));
                }
            })
            .then(data => {
                if (data.success) {
                    // Sincronizamos el rol para lógica de UI inmediata
                    sessionStorage.setItem("pnk_role", data.role);
                    window.location.href = data.redirect;
                } else {
                    mostrarAlerta(formLogin, data.message, "error");
                    btnSubmit.textContent = txtOriginal;
                    btnSubmit.disabled = false;
                }
            })
            .catch(err => {
                console.error("Detalle:", err);
                mostrarAlerta(formLogin, "Error: " + err.message, "error");
                btnSubmit.textContent = txtOriginal;
                btnSubmit.disabled = false;
            });
        });
    }

    // Navegación entre Login y Registro
    const btnIrRegistro = document.getElementById("btn-ir-registro");
    const btnIrLogin = document.getElementById("btn-ir-login");
    if(btnIrRegistro && btnIrLogin && formsSlider) {
        btnIrRegistro.addEventListener("click", (e) => { e.preventDefault(); formsSlider.style.transform = "translateX(-50%)"; });
        btnIrLogin.addEventListener("click", (e) => { e.preventDefault(); formsSlider.style.transform = "translateX(0)"; });
    }

    // Intercambio de Pestañas del Registro (¡ARREGLADO!)
    const tabBtns = document.querySelectorAll(".tab-btn");
    tabBtns.forEach(btn => {
        btn.addEventListener("click", function() {
            tabBtns.forEach(b => b.classList.remove("active"));
            this.classList.add("active");
            
            const target = this.getAttribute("data-target");
            document.querySelectorAll(".tab-content").forEach(content => {
                content.classList.remove("active");
            });
            document.getElementById(target).classList.add("active");
        });
    });

    // Carrusel de imágenes del login
    const imgSlider = document.getElementById("img-slider");
    const totalImagenes = imgSlider ? imgSlider.children.length : 0;
    let indiceImg = 0;
    if (imgSlider && totalImagenes > 1) {
        setInterval(() => {
            indiceImg = (indiceImg + 1) % totalImagenes;
            imgSlider.style.transform = `translateX(-${indiceImg * (100 / totalImagenes)}%)`;
        }, 5000);
    }

    /**
     * ======================================================================
     *  FORMULARIOS DE REGISTRO - VALIDACIÓN EN CLIENTE
     * ======================================================================
     */
    function attachValidation(formId) {
        const form = document.getElementById(formId);
        if (!form) return;

        // Validar campos al perder foco
        form.querySelectorAll("input[required], select[required]").forEach((input) => {
            input.addEventListener('blur', () => validarCampo(input));
            input.addEventListener('input', () => limpiarEstado(input));
        });

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            if (!validarFormulario(form)) {
                mostrarAlerta(form, 'Por favor corrige los errores del formulario.', 'error');
                return;
            }

            const btn = form.querySelector("button[type='submit']");
            btn.disabled = true;
            const orig = btn.textContent;
            btn.textContent = 'ENVIANDO...';

            fetch('backend/registro_process.php', {
                method: 'POST',
                body: new FormData(form)
            })
            .then(async res => {
                const text = await res.text();
                try {
                    return JSON.parse(text);
                } catch (err) {
                    throw new Error(text || "Error en el servidor");
                }
            })
            .then(data => {
                mostrarAlerta(form, data.message, data.success ? 'success' : 'error');
                if (data.success) form.reset();
            })
            .catch(err => {
                console.error("Error capturado:", err);
                mostrarAlerta(form, err.message, "error"); // Ahora la alerta mostrará el error real
            })
            .finally(() => {
                btn.textContent = orig;
                btn.disabled = false;
            });
        });
    }

    // Adjuntar a ambos formularios de registro
    attachValidation('registro-propietario');
    attachValidation('registro-gestor');

    /**
     * ======================================================================
     * 6. PORTAL DE PROPIEDADES (Renderizado de Catálogo)
     * ======================================================================
     */
    const propiedadesAPI = [
        { codigo: "L300", tipo: "casa", comuna: "La Serena", sector: "El Milagro", direccion: "Av. Arauco 5503", precio: "UF 2.026", dormitorios: 2, banos: 1, superficie: "40", imagen: "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" },
        { codigo: "L301", tipo: "departamento", comuna: "La Serena", sector: "Puertas del Mar", direccion: "Av. Libertad 100", precio: "UF 3.150", dormitorios: 3, banos: 2, superficie: "75", imagen: "https://images.unsplash.com/photo-1512917774080-9991f1c4c750?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" },
        { codigo: "C105", tipo: "casa", comuna: "Coquimbo", sector: "Sindempart", direccion: "Los Copihues 440", precio: "UF 1.800", dormitorios: 4, banos: 2, superficie: "120", imagen: "https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" },
        { codigo: "C106", tipo: "departamento", comuna: "Coquimbo", sector: "Peñuelas", direccion: "Costanera 1500", precio: "UF 4.500", dormitorios: 2, banos: 2, superficie: "65", imagen: "https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" },
        { codigo: "O201", tipo: "casa", comuna: "Ovalle", sector: "Centro", direccion: "Calle Libertad 450", precio: "UF 1.900", dormitorios: 3, banos: 2, superficie: "110", imagen: "https://images.unsplash.com/photo-1583608205776-bfd35f0d9f83?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" },
        { codigo: "O202", tipo: "departamento", comuna: "Ovalle", sector: "Limarí", direccion: "Av. Las Torres 800", precio: "UF 2.100", dormitorios: 2, banos: 1, superficie: "55", imagen: "https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" }
    ];

    const sectoresPorComuna = {
        "La Serena": ["El Milagro", "Puertas del Mar", "San Joaquín", "Centro", "Las Compañías"],
        "Coquimbo": ["Peñuelas", "Sindempart", "La Herradura", "Centro", "Tierras Blancas"],
        "Ovalle": ["Centro", "El Portal", "Limarí", "Sotaquí"]
    };

    const contenedorPropiedades = document.getElementById("contenedor-propiedades");
    const selectComuna = document.getElementById("filtro-comuna");
    const selectSector = document.getElementById("filtro-sector");
    const btnBuscar = document.getElementById("btn-buscar-prop");
    const modal = document.getElementById("modal-detalle");
    const modalBody = document.getElementById("modal-body");
    const modalCerrar = document.getElementById("modal-cerrar");

    function abrirModalDetalle(propiedad) {
        if (!modal || !modalBody) return;
        modalBody.innerHTML = `
            <img class="modal-img" src="${propiedad.imagen}" alt="${propiedad.tipo} en ${propiedad.comuna}" style="width:100%; border-radius:8px; margin-bottom:15px; max-height: 250px; object-fit: cover;">
            <h2 id="modal-titulo" style="font-weight: 900; margin-bottom: 5px;">${propiedad.tipo.charAt(0).toUpperCase() + propiedad.tipo.slice(1)} en ${propiedad.comuna}</h2>
            <p style="color: #6B7280; font-size: 14px; margin-bottom: 5px;"><strong>CÓD. REFERENCIA:</strong> ${propiedad.codigo}</p>
            <p style="color: #6B7280; font-size: 14px; margin-bottom: 15px;"><strong>UBICACIÓN:</strong> ${propiedad.direccion}, ${propiedad.sector}</p>
            <p style="color:#FF0066; font-size:26px; font-weight:900;">${propiedad.precio}</p>
            
            <div class="modal-datos" style="display:flex; justify-content:space-between; margin: 20px 0; padding:20px 0; border-top:1px solid #E5E7EB; border-bottom:1px solid #E5E7EB; text-align: center;">
                <div style="display:flex; flex-direction:column; gap: 4px; flex:1;">
                    <span style="font-size: 20px;">🛏️</span>
                    <span style="font-size: 11px; color: #6B7280; text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px;">Habitaciones</span>
                    <span style="font-size: 22px; font-weight: 900; color: #0A0A0A;">${propiedad.dormitorios}</span>
                </div>
                <div style="display:flex; flex-direction:column; gap: 4px; border-left: 1px solid #E5E7EB; border-right: 1px solid #E5E7EB; padding: 0 25px; flex:1;">
                    <span style="font-size: 20px;">🚿</span>
                    <span style="font-size: 11px; color: #6B7280; text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px;">Baños</span>
                    <span style="font-size: 22px; font-weight: 900; color: #0A0A0A;">${propiedad.banos}</span>
                </div>
                <div style="display:flex; flex-direction:column; gap: 4px; flex:1;">
                    <span style="font-size: 20px;">📐</span>
                    <span style="font-size: 11px; color: #6B7280; text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px;">Metros Cuadrados</span>
                    <span style="font-size: 22px; font-weight: 900; color: #0A0A0A;">${propiedad.superficie} m²</span>
                </div>
            </div>
            <p style="font-size: 13px; color: #6B7280; text-align: center; margin-bottom: 15px;">¿Te interesa esta propiedad? Cotiza ahora iniciando sesión o registrándote.</p>
            <a href="#login" onclick="document.getElementById('modal-detalle').classList.remove('visible');" style="display: block; width: 100%; background-color: #FF0066; color: white; border: none; padding: 14px; border-radius: 8px; font-weight: bold; font-size: 16px; cursor: pointer; text-align: center; text-decoration: none; transition: background-color 0.3s ease, transform 0.2s ease; box-sizing: border-box; letter-spacing: 0.5px;">💰 COTIZA AHORA</a>
        `;
        modal.classList.add("visible");
    }

    function cerrarModalDetalle() { if (modal) modal.classList.remove("visible"); }
    if (modalCerrar) modalCerrar.addEventListener("click", cerrarModalDetalle);

    function renderizarPropiedades(datos) {
        if (!contenedorPropiedades) return;
        contenedorPropiedades.innerHTML = "";
        
        if (datos.length === 0) {
            contenedorPropiedades.innerHTML = "<p style='color:#6B7280; font-weight: 600; grid-column: 1 / -1; text-align:center; padding: 30px; background: white; border-radius: 8px; border: 1px solid #E5E7EB;'>No se encontraron propiedades.</p>";
            return;
        }
        
        datos.forEach((prop) => {
            const tarjeta = document.createElement("div");
            tarjeta.className = "tarjeta-inmueble"; 
            tarjeta.innerHTML = `
                <div class="tarjeta-img-container">
                    <span class="badge-codigo">REF: ${prop.codigo}</span>
                    <img src="${prop.imagen}" alt="${prop.tipo} en ${prop.comuna}" class="tarjeta-img">
                </div>
                <div class="tarjeta-info-interior">
                    <h3>${prop.tipo.charAt(0).toUpperCase() + prop.tipo.slice(1)} en ${prop.comuna}</h3>
                    <p class="tarjeta-ubicacion">${prop.direccion}, ${prop.sector}</p>
                    <p class="tarjeta-precio">${prop.precio}</p>
                    
                    <div class="tarjeta-stats" style="border-top: 1px solid #F3F4F6; padding-top: 15px; margin-top: 5px;">
                        <div class="stat-item">
                            <span style="font-size: 10px; color: #6B7280; text-transform: uppercase;">Habitaciones</span>
                            <span style="font-size: 15px; font-weight: 800;">${prop.dormitorios}</span>
                        </div>
                        <div class="stat-item" style="align-items: center;">
                            <span style="font-size: 10px; color: #6B7280; text-transform: uppercase;">Baños</span>
                            <span style="font-size: 15px; font-weight: 800;">${prop.banos}</span>
                        </div>
                        <div class="stat-item" style="align-items: flex-end;">
                            <span style="font-size: 10px; color: #6B7280; text-transform: uppercase;">Área Total</span>
                            <span style="font-size: 15px; font-weight: 800;">${prop.superficie} m²</span>
                        </div>
                    </div>
                    <div class="tarjeta-footer-btn"><button class="btn-detalles-premium" type="button">VER DETALLES</button></div>
                </div>`;
            tarjeta.querySelector(".btn-detalles-premium").addEventListener("click", () => abrirModalDetalle(prop));
            contenedorPropiedades.appendChild(tarjeta);
        });
    }

    if (contenedorPropiedades) renderizarPropiedades(propiedadesAPI);
});