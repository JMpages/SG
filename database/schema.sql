CREATE DATABASE IF NOT EXISTS sistema_notas
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sistema_notas;

-- =========================================
-- TABLA: usuarios
-- =========================================
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  email VARCHAR(100) UNIQUE,
  password VARCHAR(255) NOT NULL,
  token_recuperacion VARCHAR(64) DEFAULT NULL,
  token_expiracion DATETIME DEFAULT NULL,
  UNIQUE KEY uk_username (username),
  KEY idx_username (username)
) ENGINE=InnoDB;

-- =========================================
-- TABLA: registro_logs (CREACIÓN DE CUENTAS)
-- =========================================
CREATE TABLE registro_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  username VARCHAR(50) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  navegador VARCHAR(100) DEFAULT NULL,
  sistema_operativo VARCHAR(100) DEFAULT NULL,
  dispositivo VARCHAR(50) DEFAULT NULL,
  ubicacion VARCHAR(150) DEFAULT NULL,
  fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_usuario (usuario_id),
  KEY idx_fecha (fecha),
  CONSTRAINT fk_registro_usuario
    FOREIGN KEY (usuario_id)
    REFERENCES usuarios(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================
-- TABLA: materias
-- =========================================
CREATE TABLE materias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  descripcion TEXT DEFAULT NULL,
  activa TINYINT(1) DEFAULT 1,
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_usuario (usuario_id),
  CONSTRAINT fk_materias_usuario
    FOREIGN KEY (usuario_id)
    REFERENCES usuarios(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================
-- TABLA: criterios_evaluacion
-- =========================================
CREATE TABLE criterios_evaluacion (
  id INT AUTO_INCREMENT PRIMARY KEY,
  materia_id INT NOT NULL,
  nombre VARCHAR(50) NOT NULL,
  porcentaje DECIMAL(5,2) NOT NULL,
  cantidad_evaluaciones INT NOT NULL DEFAULT 1,
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_materia (materia_id),
  CONSTRAINT fk_criterios_materia
    FOREIGN KEY (materia_id)
    REFERENCES materias(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================
-- TABLA: notas
-- =========================================
CREATE TABLE notas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  criterio_id INT NOT NULL,
  numero_evaluacion INT NOT NULL,
  calificacion DECIMAL(5,2) DEFAULT NULL,
  es_simulacion TINYINT(1) DEFAULT 0,
  fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TIMESTAMP NOT NULL
    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_nota (criterio_id, numero_evaluacion, es_simulacion),
  KEY idx_criterio (criterio_id),
  CONSTRAINT fk_notas_criterio
    FOREIGN KEY (criterio_id)
    REFERENCES criterios_evaluacion(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================
-- TABLA: tareas
-- =========================================
CREATE TABLE tareas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  materia_id INT NOT NULL,
  titulo VARCHAR(200) NOT NULL,
  descripcion TEXT DEFAULT NULL,
  fecha_entrega DATE NOT NULL,
  completada TINYINT(1) DEFAULT 0,
  es_calificada TINYINT(1) DEFAULT 0,
  criterio_id INT DEFAULT NULL,
  numero_evaluacion INT DEFAULT NULL,
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_materia (materia_id),
  KEY idx_fecha_entrega (fecha_entrega),
  KEY idx_criterio (criterio_id),
  CONSTRAINT fk_tareas_materia
    FOREIGN KEY (materia_id)
    REFERENCES materias(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_tareas_criterio
    FOREIGN KEY (criterio_id)
    REFERENCES criterios_evaluacion(id)
    ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================
-- TABLA: anotaciones
-- =========================================
CREATE TABLE anotaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  materia_id INT DEFAULT NULL,
  titulo VARCHAR(255) DEFAULT NULL,
  contenido LONGTEXT DEFAULT NULL, -- HTML del editor
  texto LONGTEXT DEFAULT NULL,     -- Texto plano para búsquedas
  color VARCHAR(20) DEFAULT 'white',
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_usuario (usuario_id),
  CONSTRAINT fk_anotaciones_usuario
    FOREIGN KEY (usuario_id)
    REFERENCES usuarios(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;
