-- ============================================================================
-- Esquema relacional (MySQL 8 / MariaDB 10.4+) - Sistema de Pedidos
-- Normalizado en 3FN con llaves primarias, foráneas, restricciones e índices.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS restaurante_pedidos
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE restaurante_pedidos;

SET NAMES utf8mb4;

-- ----------------------------------------------------------------------------
-- Seguridad: Roles, Permisos y Usuarios
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS roles (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(30)  NOT NULL,
    descripcion VARCHAR(180) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permisos (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    clave       VARCHAR(60)  NOT NULL,
    descripcion VARCHAR(180) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_permisos_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rol_permisos (
    rol_id     INT UNSIGNED NOT NULL,
    permiso_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (rol_id, permiso_id),
    CONSTRAINT fk_rol_permisos_rol
        FOREIGN KEY (rol_id) REFERENCES roles (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_rol_permisos_permiso
        FOREIGN KEY (permiso_id) REFERENCES permisos (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuarios (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario       VARCHAR(30)  NOT NULL,
    nombre        VARCHAR(100) NOT NULL,
    correo        VARCHAR(120) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol_id        INT UNSIGNED NOT NULL,
    activo        TINYINT(1)   NOT NULL DEFAULT 1,
    creado_en     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_usuario (usuario),
    UNIQUE KEY uq_usuarios_correo (correo),
    KEY idx_usuarios_rol (rol_id),
    KEY idx_usuarios_activo (activo),
    CONSTRAINT fk_usuarios_rol
        FOREIGN KEY (rol_id) REFERENCES roles (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Catálogo del Menú (RF1)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS platos (
    id            VARCHAR(32)   NOT NULL,
    nombre        VARCHAR(120)  NOT NULL,
    precio_base   DECIMAL(10,2) NOT NULL,
    tasa_impuesto DECIMAL(5,4)  NOT NULL DEFAULT 0.1600,
    activo        TINYINT(1)    NOT NULL DEFAULT 1,
    creado_en     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_platos_activo_nombre (activo, nombre),
    CONSTRAINT chk_platos_precio CHECK (precio_base > 0),
    CONSTRAINT chk_platos_tasa CHECK (tasa_impuesto >= 0 AND tasa_impuesto <= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS modificadores (
    id          VARCHAR(32)                NOT NULL,
    nombre      VARCHAR(120)               NOT NULL,
    tipo        ENUM('EXTRA', 'EXCLUSION') NOT NULL,
    costo_extra DECIMAL(10,2)              NOT NULL DEFAULT 0.00,
    activo      TINYINT(1)                 NOT NULL DEFAULT 1,
    creado_en   DATETIME                   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_modificadores_activo_nombre (activo, nombre),
    CONSTRAINT chk_modificadores_costo CHECK (costo_extra >= 0),
    CONSTRAINT chk_modificadores_exclusion CHECK (tipo <> 'EXCLUSION' OR costo_extra = 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Órdenes, Líneas de Pedido y Pagos (RF2, RF3, RF4, RF5)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS ordenes (
    id                 CHAR(36)                                    NOT NULL,
    tipo               ENUM('SALA', 'PARA_LLEVAR', 'DELIVERY')     NOT NULL,
    referencia         VARCHAR(80)                                 NOT NULL,
    estado             ENUM('ABIERTA', 'CERRADA', 'CANCELADA')     NOT NULL DEFAULT 'ABIERTA',
    fecha_creacion     DATETIME                                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_creador_id INT UNSIGNED                                NULL,
    subtotal_cerrado   DECIMAL(10,2)                               NULL,
    descuento_cerrado  DECIMAL(10,2)                               NULL,
    impuestos_cerrados DECIMAL(10,2)                               NULL,
    propina_cerrada    DECIMAL(10,2)                               NULL,
    total_cerrado      DECIMAL(10,2)                               NULL,
    PRIMARY KEY (id),
    KEY idx_ordenes_estado_fecha (estado, fecha_creacion),
    KEY idx_ordenes_usuario (usuario_creador_id),
    CONSTRAINT fk_ordenes_usuario
        FOREIGN KEY (usuario_creador_id) REFERENCES usuarios (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lineas_pedido (
    id                     CHAR(36)                                            NOT NULL,
    numero_linea           BIGINT UNSIGNED                                     NOT NULL AUTO_INCREMENT,
    orden_id               CHAR(36)                                            NOT NULL,
    plato_id               VARCHAR(32)                                         NOT NULL,
    plato_nombre           VARCHAR(120)                                        NOT NULL,
    cantidad               INT UNSIGNED                                        NOT NULL,
    precio_unitario        DECIMAL(10,2)                                       NOT NULL,
    tasa_impuesto_aplicada DECIMAL(5,4)                                        NOT NULL,
    estado_cocina          ENUM('PENDIENTE', 'EN_COCCION', 'LISTO', 'CANCELADA') NOT NULL DEFAULT 'PENDIENTE',
    PRIMARY KEY (id),
    UNIQUE KEY uq_lineas_numero (numero_linea),
    KEY idx_lineas_orden_estado (orden_id, estado_cocina),
    KEY idx_lineas_cocina (estado_cocina),
    KEY idx_lineas_plato (plato_id),
    CONSTRAINT chk_lineas_cantidad CHECK (cantidad > 0),
    CONSTRAINT fk_lineas_orden
        FOREIGN KEY (orden_id) REFERENCES ordenes (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_lineas_plato
        FOREIGN KEY (plato_id) REFERENCES platos (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS linea_modificadores (
    id                   BIGINT UNSIGNED            NOT NULL AUTO_INCREMENT,
    linea_id             CHAR(36)                   NOT NULL,
    modificador_id       VARCHAR(32)                NOT NULL,
    nombre_aplicado      VARCHAR(120)               NOT NULL,
    tipo_aplicado        ENUM('EXTRA', 'EXCLUSION') NOT NULL,
    costo_extra_aplicado DECIMAL(10,2)              NOT NULL,
    PRIMARY KEY (id),
    KEY idx_lmod_linea (linea_id),
    KEY idx_lmod_modificador (modificador_id),
    CONSTRAINT fk_lmod_linea
        FOREIGN KEY (linea_id) REFERENCES lineas_pedido (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_lmod_modificador
        FOREIGN KEY (modificador_id) REFERENCES modificadores (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pagos (
    id                CHAR(36)                                        NOT NULL,
    orden_id          CHAR(36)                                        NOT NULL,
    fecha             DATETIME                                        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado            ENUM('PENDIENTE', 'APROBADO', 'RECHAZADO')      NOT NULL,
    metodo            ENUM('EFECTIVO', 'TARJETA', 'PASARELA_DIGITAL') NOT NULL,
    monto             DECIMAL(10,2)                                   NOT NULL,
    referencia        VARCHAR(64)                                     NULL,
    usuario_cajero_id INT UNSIGNED                                    NULL,
    PRIMARY KEY (id),
    KEY idx_pagos_orden_estado (orden_id, estado),
    KEY idx_pagos_cajero (usuario_cajero_id),
    CONSTRAINT chk_pagos_monto CHECK (monto >= 0),
    CONSTRAINT fk_pagos_orden
        FOREIGN KEY (orden_id) REFERENCES ordenes (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_pagos_cajero
        FOREIGN KEY (usuario_cajero_id) REFERENCES usuarios (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
