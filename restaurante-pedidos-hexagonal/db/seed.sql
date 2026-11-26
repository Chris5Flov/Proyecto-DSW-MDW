-- ============================================================================
-- Datos semilla y migración desde DatosDemo.java / mock.js
-- Contraseñas de prueba:
--   admin  -> Admin1234*
--   mesero -> Mesero1234*
--   cocina -> Cocina1234*
--   caja   -> Caja1234*
-- ============================================================================

USE restaurante_pedidos;

SET NAMES utf8mb4;

-- Roles del sistema
INSERT INTO roles (id, nombre, descripcion) VALUES
    (1, 'ADMINISTRADOR', 'Administra catálogo del menú (platos y modificadores) y usuarios del sistema'),
    (2, 'MESERO',        'Abre órdenes, agrega/edita/cancela líneas y cancela órdenes'),
    (3, 'COCINERO',      'Consulta el tablero de cocina y avanza estados de preparación'),
    (4, 'CAJERO',        'Calcula totales, procesa cobros y cierra órdenes listas')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- Permisos granulares
INSERT INTO permisos (id, clave, descripcion) VALUES
    (1, 'MENU_VER',          'Consultar platos y modificadores activos'),
    (2, 'MENU_ADMINISTRAR',  'Crear, editar y eliminar platos y modificadores'),
    (3, 'USUARIOS_GESTIONAR','Listar usuarios, cambiar rol y activar/desactivar cuentas'),
    (4, 'ORDENES_GESTIONAR', 'Abrir órdenes, gestionar líneas y cancelar órdenes'),
    (5, 'COCINA_OPERAR',     'Ver tablero de cocina y cambiar estado de líneas'),
    (6, 'CAJA_COBRAR',       'Calcular totales y cobrar órdenes listas')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- Relación rol <-> permisos
INSERT IGNORE INTO rol_permisos (rol_id, permiso_id) VALUES
    (1, 1), (1, 2), (1, 3),
    (2, 1), (2, 4),
    (3, 5),
    (4, 6);

-- Usuarios iniciales (hashes bcrypt generados con password_hash)
INSERT INTO usuarios (id, usuario, nombre, correo, password_hash, rol_id, activo) VALUES
    (1, 'admin',  'Administrador General', 'admin@restaurante.local',  '$2y$10$zJ9THNtfYKj8apbDBoSe3.IY9egkLxR5TlMzzL.xSX1bcnT78Rpl.', 1, 1),
    (2, 'mesero', 'Carlos Mesero',         'mesero@restaurante.local', '$2y$10$nkNyKUbNK.tPCI3S690cieVGQCVBmeJwP.O.er8oCqVjg0xkbsaXK', 2, 1),
    (3, 'cocina', 'Lucía Cocinera',        'cocina@restaurante.local', '$2y$10$CD6MY0wqqKNe7FdJeQcK5ep2Qjm0Gy4aTy9Spgwy1Ljaatw.7P12a', 3, 1),
    (4, 'caja',   'Sofía Cajera',          'caja@restaurante.local',   '$2y$10$YD.hDSvoXpbAGaVIzrrhkOZrj9VFjCqLKp15gPTfuf/Z18A.Mr72m', 4, 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), correo = VALUES(correo), rol_id = VALUES(rol_id);

-- Migración del menú demo (DatosDemo.java)
INSERT INTO platos (id, nombre, precio_base, tasa_impuesto, activo) VALUES
    ('P-001', 'Tacos al pastor',  85.00,  0.1600, 1),
    ('P-002', 'Pozole rojo',      120.00, 0.1600, 1),
    ('P-003', 'Quesadilla',       60.00,  0.1600, 1),
    ('P-004', 'Agua de horchata', 35.00,  0.0000, 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), precio_base = VALUES(precio_base), tasa_impuesto = VALUES(tasa_impuesto);

INSERT INTO modificadores (id, nombre, tipo, costo_extra, activo) VALUES
    ('M-001', 'Queso extra', 'EXTRA',     15.00, 1),
    ('M-002', 'Aguacate',    'EXTRA',     20.00, 1),
    ('M-003', 'Sin cebolla', 'EXCLUSION',  0.00, 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), tipo = VALUES(tipo), costo_extra = VALUES(costo_extra);

-- Migración de la orden demo (DatosDemo.java: Mesa 5 con 3 Tacos al pastor + M-001 + M-003, y 2 Aguas de horchata)
INSERT IGNORE INTO ordenes (id, tipo, referencia, estado, fecha_creacion, usuario_creador_id) VALUES
    ('11111111-1111-4111-8111-111111111111', 'SALA', 'Mesa 5', 'ABIERTA', NOW(), 2);

INSERT IGNORE INTO lineas_pedido
    (id, orden_id, plato_id, plato_nombre, cantidad, precio_unitario, tasa_impuesto_aplicada, estado_cocina)
VALUES
    ('22222222-2222-4222-8222-222222222221', '11111111-1111-4111-8111-111111111111', 'P-001', 'Tacos al pastor',  3, 85.00, 0.1600, 'PENDIENTE'),
    ('22222222-2222-4222-8222-222222222222', '11111111-1111-4111-8111-111111111111', 'P-004', 'Agua de horchata', 2, 35.00, 0.0000, 'PENDIENTE');

INSERT IGNORE INTO linea_modificadores
    (id, linea_id, modificador_id, nombre_aplicado, tipo_aplicado, costo_extra_aplicado)
VALUES
    (1, '22222222-2222-4222-8222-222222222221', 'M-001', 'Queso extra', 'EXTRA',     15.00),
    (2, '22222222-2222-4222-8222-222222222221', 'M-003', 'Sin cebolla', 'EXCLUSION',  0.00);
