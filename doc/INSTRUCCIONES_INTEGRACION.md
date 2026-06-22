# 📋 Instrucciones de Integración - PNK Inmobiliaria Tercera Entrega

## 🎯 Resumen de Cambios

Se han actualizado todos los archivos del proyecto para cumplir con los requisitos de la rúbrica de tercera evaluación. Los cambios incluyen:

- ✅ Gestión completa de fotografías (1-10 por propiedad)
- ✅ CRUD funcional en backend (no simulado)
- ✅ Integración de SweetAlert2 para mensajes
- ✅ Filtros de búsqueda en catálogo público
- ✅ Carrusel de imágenes con Bootstrap
- ✅ Nuevos campos en propiedades (comuna, sector, dormitorios, baños, superficie, descripción)

---

## 📦 Archivos Nuevos y Actualizados

### Backend (PHP)
| Archivo | Descripción |
|---------|-------------|
| `backend/api_user_updated.php` | API para propietarios (CRUD + fotos) |
| `backend/api_dashboard_updated.php` | API para admin (gestión completa) |
| `backend/api_public.php` | API pública (catálogo con filtros) |
| `backend/portal_updated.php` | Portal del propietario mejorado |
| `backend/dashboard_updated.php` | Dashboard del admin mejorado |

### Frontend (JavaScript)
| Archivo | Descripción |
|---------|-------------|
| `js/portal_updated.js` | Lógica del portal del propietario |
| `js/dashboard_updated.js` | Lógica del dashboard admin |

### Frontend (HTML)
| Archivo | Descripción |
|---------|-------------|
| `index_updated.html` | Catálogo público con filtros |

### Base de Datos
| Archivo | Descripción |
|---------|-------------|
| `doc/actualizar_bd.sql` | Script SQL para actualizar BD |

---

## 🚀 Pasos de Integración

### Paso 1: Actualizar la Base de Datos

1. Abre tu gestor de base de datos (phpMyAdmin, MySQL Workbench, etc.)
2. Selecciona la base de datos `pnk_inmobiliaria`
3. Abre el archivo `doc/actualizar_bd.sql`
4. Copia y pega el contenido en la consola SQL
5. Ejecuta el script

**Resultado esperado:** Se crearán nuevas columnas en `propiedades` y la tabla `propiedad_fotos`.

---

### Paso 2: Reemplazar Archivos del Backend

1. **Reemplaza** estos archivos en la carpeta `backend/`:
   - `api_user.php` → Renómbralo a `api_user_old.php` (respaldo)
   - Copia `api_user_updated.php` y renómbralo a `api_user.php`
   - `api_dashboard.php` → Renómbralo a `api_dashboard_old.php` (respaldo)
   - Copia `api_dashboard_updated.php` y renómbralo a `api_dashboard.php`
   - **Agrega** `api_public.php` (nuevo archivo)
   - `portal.php` → Renómbralo a `portal_old.php` (respaldo)
   - Copia `portal_updated.php` y renómbralo a `portal.php`
   - `dashboard.php` → Renómbralo a `dashboard_old.php` (respaldo)
   - Copia `dashboard_updated.php` y renómbralo a `dashboard.php`

---

### Paso 3: Reemplazar Archivos del Frontend

1. **Reemplaza** estos archivos en la carpeta `js/`:
   - `portal.js` → Renómbralo a `portal_old.js` (respaldo)
   - Copia `portal_updated.js` y renómbralo a `portal.js`
   - `dashboard.js` → Renómbralo a `dashboard_old.js` (respaldo)
   - Copia `dashboard_updated.js` y renómbralo a `dashboard.js`

2. **Reemplaza** archivo HTML:
   - `index.html` → Renómbralo a `index_old.html` (respaldo)
   - Copia `index_updated.html` y renómbralo a `index.html`

---

### Paso 4: Crear Carpeta de Uploads

1. Navega a la carpeta `img/`
2. Crea una nueva carpeta llamada `uploads`
3. Asegúrate de que tenga permisos de escritura (chmod 755 en Linux/Mac)

**Comando (Linux/Mac):**
```bash
mkdir -p img/uploads
chmod 755 img/uploads
```

---

### Paso 5: Verificar Instalación de SweetAlert2

Los archivos ya incluyen referencias a SweetAlert2 desde CDN. Verifica que en los archivos HTML tengas estas líneas:

```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
```

---

## 🧪 Pruebas Recomendadas

### 1. Prueba de Login
- Inicia sesión con un usuario propietario
- Inicia sesión con un usuario admin
- Verifica que los permisos se respeten

### 2. Prueba de Propiedades (Propietario)
- Crea una nueva propiedad con todos los campos
- Sube 3-5 fotografías
- Edita la propiedad
- Establece una foto como principal
- Elimina una foto
- Marca la propiedad como eliminada

### 3. Prueba de Catálogo Público
- Accede a `index.html` sin iniciar sesión
- Filtra por tipo de propiedad
- Filtra por comuna
- Filtra por sector
- Abre el detalle de una propiedad
- Verifica el carrusel de imágenes

### 4. Prueba de Dashboard Admin
- Accede al dashboard como admin
- Visualiza el resumen
- Crea una nueva propiedad desde el admin
- Edita propiedades
- Elimina propiedades
- Gestiona usuarios (crear, editar, cambiar estado, eliminar)

### 5. Prueba de SweetAlert2
- Realiza acciones que muestren mensajes (crear, editar, eliminar)
- Verifica que aparezcan alertas bonitas en lugar de mensajes de texto

---

## 📊 Requisitos de la Rúbrica - Checklist

### 1. Autenticación, roles y control de acceso (10 pts)
- ✅ Inicio de sesión con credenciales válidas
- ✅ Diferenciación entre propietario y administrador
- ✅ Restricción de acceso: cada propietario solo ve sus propiedades

### 2. CRUD de Propiedades (20 pts)
- ✅ Crear propiedades
- ✅ Listar propiedades (propietario y admin)
- ✅ Editar propiedades
- ✅ Eliminar/cambiar estado de propiedades
- ✅ Gestión por tipo (Casa, Departamento, Terreno)
- ✅ Asociación propietario-propiedad

### 3. Validación de datos (10 pts)
- ✅ Campos obligatorios validados
- ✅ Tipos de datos validados (números, texto, etc.)
- ✅ Mensajes de error claros con SweetAlert2

### 4. Gestión de fotografías (20 pts)
- ✅ Carga de 1 a 10 fotos por propiedad
- ✅ Validación de formato (JPG, PNG, WEBP)
- ✅ Administración de imágenes (subir, eliminar)
- ✅ Selección de imagen principal

### 5. Funcionalidades del administrador (10 pts)
- ✅ Registro de propiedades para cualquier propietario
- ✅ Gestión completa de propiedades
- ✅ Gestión de fotografías
- ✅ Gestión de usuarios

### 6. Front-end y visualización pública (10 pts)
- ✅ Listado público de propiedades
- ✅ Detalle de propiedad con información completa
- ✅ Diseño responsivo (Bootstrap)
- ✅ Filtros de búsqueda (tipo, comuna, sector)

### 7. Filtros de búsqueda (10 pts)
- ✅ Filtro por tipo de propiedad
- ✅ Filtro por comuna
- ✅ Filtro por sector
- ✅ Funcionamiento combinado de filtros

### 8. Vista detalle, carrusel e iconografía (10 pts)
- ✅ Carrusel de imágenes con Bootstrap
- ✅ Características con iconos (dormitorios, baños, superficie)
- ✅ Navegación fluida entre detalle y listado

---

## 🔧 Troubleshooting

### Problema: Las fotos no se suben
**Solución:**
1. Verifica que la carpeta `img/uploads/` existe
2. Verifica permisos: `chmod 755 img/uploads/`
3. Revisa los logs del servidor PHP

### Problema: Los filtros no funcionan
**Solución:**
1. Verifica que `api_public.php` está en la carpeta `backend/`
2. Revisa la consola del navegador (F12) para errores
3. Verifica que la BD tiene datos en las columnas `comuna` y `sector`

### Problema: SweetAlert2 no aparece
**Solución:**
1. Verifica que tienes conexión a internet (CDN)
2. Revisa la consola del navegador para errores de carga
3. Verifica que las referencias a Swal están correctas en el JS

### Problema: No puedo acceder al dashboard admin
**Solución:**
1. Verifica que tu usuario tiene `tipo = 'admin'` en la BD
2. Verifica que `pnk_role` se guarda correctamente en sesión
3. Revisa `backend/login_process.php` para validar la lógica

---

## 📝 Notas Importantes

1. **Respaldos:** Todos los archivos antiguos se renombran con `_old.php` o `_old.js`. Puedes eliminarlos después de verificar que todo funciona.

2. **Base de Datos:** El script SQL usa `IF NOT EXISTS` para evitar errores si las columnas ya existen.

3. **Seguridad:** 
   - Las contraseñas se hashean con `password_hash()`
   - Las fotos se validan por tipo MIME
   - El acceso está protegido por sesiones

4. **Rendimiento:**
   - Se agregaron índices a la BD para mejorar consultas
   - Las fotos se comprimen y validan antes de subir

---

## 🎓 Evidencias Mínimas de Entrega

Según la rúbrica, debes entregar:
- ✅ Proyecto en **AWS** (o servidor en la nube)
- ✅ Repositorio en **GitHub**
- ✅ Mensajes por **Switchlet2** (SweetAlert2)

---

## 📞 Soporte

Si tienes problemas durante la integración:
1. Revisa el archivo de logs del servidor PHP
2. Abre la consola del navegador (F12) para ver errores JavaScript
3. Verifica que todos los archivos estén en las carpetas correctas
4. Comprueba que la BD se actualizó correctamente

---

**¡Listo! Tu proyecto ahora cumple con todos los requisitos de la rúbrica.** 🎉
