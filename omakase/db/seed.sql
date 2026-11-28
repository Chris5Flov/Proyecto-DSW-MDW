-- ============================================================================
-- Datos semilla de OMAKASE
-- Contraseñas de prueba:
--   admin   -> Admin1234*
--   gestor  -> Gestor1234*
--   mesero  -> Mesero1234*
--   cocina  -> Cocina1234*
--   caja    -> Caja1234*
-- ============================================================================

USE restaurante_pedidos;

SET NAMES utf8mb4;

-- Roles del sistema
INSERT INTO roles (id, nombre, descripcion) VALUES
    (1, 'ADMINISTRADOR', 'Administra el catálogo del menú (platos y modificadores)'),
    (2, 'MESERO',        'Abre órdenes, agrega/edita/cancela líneas y cancela órdenes'),
    (3, 'COCINERO',      'Consulta el tablero de cocina y avanza estados de preparación'),
    (4, 'CAJERO',        'Calcula totales, procesa cobros y cierra órdenes listas'),
    (5, 'GESTOR',        'Gestiona usuarios: aprueba altas, asigna roles y activa o desactiva cuentas')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- Permisos granulares
INSERT INTO permisos (id, clave, descripcion) VALUES
    (1, 'MENU_VER',           'Consultar platos y modificadores activos'),
    (2, 'MENU_ADMINISTRAR',   'Crear, editar y eliminar platos y modificadores'),
    (3, 'USUARIOS_GESTIONAR', 'Listar usuarios, crear cuentas, cambiar rol y activar/desactivar cuentas'),
    (4, 'ORDENES_GESTIONAR',  'Abrir órdenes, gestionar líneas y cancelar órdenes'),
    (5, 'COCINA_OPERAR',      'Ver tablero de cocina y cambiar estado de líneas'),
    (6, 'CAJA_COBRAR',        'Calcular totales y cobrar órdenes listas')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- Relación rol <-> permisos (permisos estrictos: cada rol hace solo lo suyo)
INSERT IGNORE INTO rol_permisos (rol_id, permiso_id) VALUES
    (1, 1), (1, 2),   -- ADMINISTRADOR: ver y administrar menú
    (2, 1), (2, 4),   -- MESERO: ver menú y gestionar órdenes
    (3, 5),           -- COCINERO: operar cocina
    (4, 6),           -- CAJERO: cobrar
    (5, 3);           -- GESTOR: gestionar usuarios

-- Usuarios iniciales (hashes bcrypt generados con password_hash)
INSERT INTO usuarios (id, usuario, nombre, correo, password_hash, rol_id, activo) VALUES
    (1, 'admin',  'Administrador General', 'admin@omakase.local',  '$2y$10$zJ9THNtfYKj8apbDBoSe3.IY9egkLxR5TlMzzL.xSX1bcnT78Rpl.', 1, 1),
    (2, 'mesero', 'Carlos Mesero',         'mesero@omakase.local', '$2y$10$nkNyKUbNK.tPCI3S690cieVGQCVBmeJwP.O.er8oCqVjg0xkbsaXK', 2, 1),
    (3, 'cocina', 'Lucía Cocinera',        'cocina@omakase.local', '$2y$10$CD6MY0wqqKNe7FdJeQcK5ep2Qjm0Gy4aTy9Spgwy1Ljaatw.7P12a', 3, 1),
    (4, 'caja',   'Sofía Cajera',          'caja@omakase.local',   '$2y$10$YD.hDSvoXpbAGaVIzrrhkOZrj9VFjCqLKp15gPTfuf/Z18A.Mr72m', 4, 1),
    (5, 'gestor', 'Gestor de Usuarios',    'gestor@omakase.local', '$2y$10$1I54g4F216WsvXM1iO.YSOi6cE2.zRq9TYR92qtwCQkj5E03afG8K', 5, 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), correo = VALUES(correo), rol_id = VALUES(rol_id);

-- Menú de OMAKASE (precio base; IVA del 16 % en todos los platos)
INSERT INTO platos (id, nombre, categoria, imagen, precio_base, tasa_impuesto, activo) VALUES
    ('P-001', 'Coctel de camarón',                  'Del mar', 'img/platos/P-001.png',        150.00, 0.1600, 1),
    ('P-002', 'Sushi',                              'Del mar',  'img/platos/P-002.png',      135.00, 0.1600, 1),
    ('P-003', 'Filete de pescado empanizado',       'Del mar',  'img/platos/P-003.png',      130.00, 0.1600, 1),
    ('P-004', 'Empanada de camarón',                'Del mar',   'img/platos/P-004.png',      60.00, 0.1600, 1),
    ('P-005', 'Bistec encebollado',                 'Platos fuertes', 'img/platos/P-005.png', 155.00, 0.1600, 1),
    ('P-006', 'Enchiladas suizas',                  'Platos fuertes', 'img/platos/P-006.png', 110.00, 0.1600, 1),
    ('P-007', 'Pozole',                             'Platos fuertes', 'img/platos/P-007.png', 90.00, 0.1600, 1),
    ('P-008', 'Tacos (orden de 4)',                 'Antojitos',      'img/platos/P-008.png', 60.00, 0.1600, 1),
    ('P-009', 'Chalupas (orden de 5)',              'Antojitos',       'img/platos/P-009.png',55.00, 0.1600, 1),
    ('P-010', 'Gordita/memela',                     'Antojitos',      'img/platos/P-010.png', 23.00, 0.1600, 1),
    ('P-011', 'Fresas con crema',                   'Postres',         'img/platos/P-011.png',60.00, 0.1600, 1),
    ('P-012', 'Cerveza',                            'Bebidas',        'img/platos/P-012.png', 45.00, 0.1600, 1),
    ('P-013', 'Refresco',                           'Bebidas',        'img/platos/P-013.png', 30.00, 0.1600, 1),
    ('P-014', 'Agua de jamaica/horchata (vaso)',    'Bebidas',        'img/platos/P-014.png', 32.00, 0.1600, 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), categoria = VALUES(categoria),
    imagen=VALUES(imagen), precio_base = VALUES(precio_base), tasa_impuesto = VALUES(tasa_impuesto);

INSERT INTO modificadores (id, nombre, tipo, costo_extra, activo) VALUES
    ('M-001', 'Queso extra',    'EXTRA',     15.00, 1),
    ('M-002', 'Aguacate extra', 'EXTRA',     20.00, 1),
    ('M-003', 'Sin cebolla',    'EXCLUSION',  0.00, 1),
    ('M-004', 'Sin picante',    'EXCLUSION',  0.00, 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), tipo = VALUES(tipo), costo_extra = VALUES(costo_extra);

-- Orden de ejemplo: Mesa 5 con 2 órdenes de tacos (queso extra, sin cebolla) y 2 aguas
INSERT IGNORE INTO ordenes (id, tipo, referencia, estado, fecha_creacion, usuario_creador_id) VALUES
    ('11111111-1111-4111-8111-111111111111', 'SALA', 'Mesa 5', 'ABIERTA', NOW(), 2);

INSERT IGNORE INTO lineas_pedido
    (id, orden_id, plato_id, plato_nombre, cantidad, precio_unitario, tasa_impuesto_aplicada, estado_cocina)
VALUES
    ('22222222-2222-4222-8222-222222222221', '11111111-1111-4111-8111-111111111111', 'P-008', 'Tacos (orden de 4)',              2, 60.00, 0.1600, 'PENDIENTE'),
    ('22222222-2222-4222-8222-222222222222', '11111111-1111-4111-8111-111111111111', 'P-014', 'Agua de jamaica/horchata (vaso)', 2, 32.00, 0.1600, 'PENDIENTE');

INSERT IGNORE INTO linea_modificadores
    (id, linea_id, modificador_id, nombre_aplicado, tipo_aplicado, costo_extra_aplicado)
VALUES
    (1, '22222222-2222-4222-8222-222222222221', 'M-001', 'Queso extra', 'EXTRA',     15.00),
    (2, '22222222-2222-4222-8222-222222222221', 'M-003', 'Sin cebolla', 'EXCLUSION',  0.00);