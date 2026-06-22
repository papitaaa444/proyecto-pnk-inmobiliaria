-- ============================================================================
-- SCRIPT DE ACTUALIZACIÓN DE BASE DE DATOS - PNK INMOBILIARIA
-- Tercera Entrega - Integración de Rúbrica
-- ============================================================================

-- 1. ACTUALIZAR TABLA PROPIEDADES CON NUEVOS CAMPOS
-- ============================================================================
ALTER TABLE propiedades 
ADD COLUMN IF NOT EXISTS comuna VARCHAR(100) AFTER ubicacion,
ADD COLUMN IF NOT EXISTS sector VARCHAR(100) AFTER comuna,
ADD COLUMN IF NOT EXISTS dormitorios INT DEFAULT 0 AFTER precio,
ADD COLUMN IF NOT EXISTS banos INT DEFAULT 0 AFTER dormitorios,
ADD COLUMN IF NOT EXISTS superficie INT DEFAULT 0 AFTER banos,
ADD COLUMN IF NOT EXISTS descripcion TEXT AFTER superficie;

-- 2. CREAR TABLA PARA FOTOGRAFÍAS
-- ============================================================================
CREATE TABLE IF NOT EXISTS propiedad_fotos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    propiedad_id INT NOT NULL,
    ruta VARCHAR(255) NOT NULL,
    es_principal BOOLEAN DEFAULT FALSE,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (propiedad_id) REFERENCES propiedades(id) ON DELETE CASCADE,
    INDEX idx_propiedad (propiedad_id),
    INDEX idx_principal (es_principal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. VERIFICAR INTEGRIDAD DE DATOS EXISTENTES
-- ============================================================================
-- Si hay propiedades sin comuna/sector, asignar valores por defecto
UPDATE propiedades SET comuna = 'La Serena' WHERE comuna IS NULL OR comuna = '';
UPDATE propiedades SET sector = 'Centro' WHERE sector IS NULL OR sector = '';

-- 4. CREAR ÍNDICES PARA MEJORAR RENDIMIENTO
-- ============================================================================
ALTER TABLE propiedades 
ADD INDEX IF NOT EXISTS idx_estado (estado),
ADD INDEX IF NOT EXISTS idx_user_id (user_id),
ADD INDEX IF NOT EXISTS idx_comuna (comuna),
ADD INDEX IF NOT EXISTS idx_tipo (tipo);

-- 5. VERIFICAR ESTRUCTURA FINAL
-- ============================================================================
-- Ejecuta estas consultas para verificar que todo está correcto:
-- SELECT * FROM propiedades LIMIT 1;
-- SELECT * FROM propiedad_fotos LIMIT 1;
-- SHOW COLUMNS FROM propiedades;
-- SHOW COLUMNS FROM propiedad_fotos;
