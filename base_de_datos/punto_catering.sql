-- =====================================================================
-- Punto Catering - Script de creación de la base de datos
-- Trabajo final de Programación Web - UCH
-- Integrantes: Bautista Navarro y Matias Porcari
--
-- Uso: en phpMyAdmin, pestaña "Importar", seleccionar este archivo.
-- ATENCIÓN: reemplaza por completo la base "punto_catering" si ya existe.
-- =====================================================================

DROP DATABASE IF EXISTS punto_catering;
CREATE DATABASE punto_catering CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE punto_catering;

-- ---------------------------------------------------------------------
-- Tabla: usuarios
-- Empresas clientes y administradores del sitio.
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
    id_usuario       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    razon_social     VARCHAR(120) NOT NULL,
    cuit             CHAR(13) NOT NULL UNIQUE,            -- Formato: 30-12345678-9
    nombre_contacto  VARCHAR(100) NOT NULL,
    correo           VARCHAR(150) NOT NULL UNIQUE,
    telefono         VARCHAR(30) NOT NULL,
    contrasena_hash  VARCHAR(255) NOT NULL,               -- Nunca se guarda la contraseña en texto plano
    rol              ENUM('cliente', 'administrador') NOT NULL DEFAULT 'cliente',
    activo           TINYINT(1) NOT NULL DEFAULT 1,       -- 0 = no puede iniciar sesión
    fecha_alta       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabla: categorias
-- Agrupa los menús del catálogo.
-- ---------------------------------------------------------------------
CREATE TABLE categorias (
    id_categoria  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(60) NOT NULL UNIQUE,
    descripcion   VARCHAR(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabla: menus
-- Oferta de catering. Una categoría con menús no se puede eliminar.
-- ---------------------------------------------------------------------
CREATE TABLE menus (
    id_menu             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_categoria        INT UNSIGNED NOT NULL,
    nombre              VARCHAR(100) NOT NULL,
    descripcion         VARCHAR(500) NOT NULL,
    precio_por_persona  DECIMAL(10, 2) NOT NULL,
    minimo_personas     INT UNSIGNED NOT NULL DEFAULT 1,
    es_vegetariano      TINYINT(1) NOT NULL DEFAULT 0,
    es_sin_tacc         TINYINT(1) NOT NULL DEFAULT 0,
    ilustracion         VARCHAR(30) NOT NULL DEFAULT 'buffet',  -- Archivo dentro de recursos/img/menus
    activo              TINYINT(1) NOT NULL DEFAULT 1,          -- 0 = oculto en el catálogo
    CONSTRAINT fk_menus_categoria FOREIGN KEY (id_categoria)
        REFERENCES categorias (id_categoria) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabla: pedidos
-- Solicitudes de presupuesto realizadas por las empresas.
-- Ciclo de vida: pendiente -> presupuestado -> aprobado -> finalizado.
-- Salidas alternativas: rechazado (por la empresa) o cancelado.
-- ---------------------------------------------------------------------
CREATE TABLE pedidos (
    id_pedido          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario         INT UNSIGNED NOT NULL,
    fecha_evento       DATE NOT NULL,
    hora_evento        TIME NOT NULL,
    direccion_evento   VARCHAR(200) NOT NULL,
    cantidad_personas  INT UNSIGNED NOT NULL,
    observaciones      VARCHAR(500) NOT NULL DEFAULT '',
    estado             ENUM('pendiente', 'presupuestado', 'aprobado', 'finalizado', 'rechazado', 'cancelado')
                       NOT NULL DEFAULT 'pendiente',
    total_estimado     DECIMAL(12, 2) NOT NULL,      -- Calculado por el sistema al crear o editar
    total_final        DECIMAL(12, 2) NULL,          -- Definido por el administrador al presupuestar
    comentario_admin   VARCHAR(500) NOT NULL DEFAULT '',
    fecha_creacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pedidos_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios (id_usuario) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_pedidos_estado (estado)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabla: detalle_pedido
-- Menús incluidos en cada pedido. Guarda el precio del momento para que
-- un cambio posterior en el catálogo no altere los pedidos ya hechos.
-- ---------------------------------------------------------------------
CREATE TABLE detalle_pedido (
    id_detalle       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pedido        INT UNSIGNED NOT NULL,
    id_menu          INT UNSIGNED NOT NULL,
    precio_unitario  DECIMAL(10, 2) NOT NULL,
    CONSTRAINT fk_detalle_pedido FOREIGN KEY (id_pedido)
        REFERENCES pedidos (id_pedido) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_detalle_menu FOREIGN KEY (id_menu)
        REFERENCES menus (id_menu) ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY uq_detalle_pedido_menu (id_pedido, id_menu)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabla: consultas
-- Mensajes enviados desde el formulario de contacto.
-- ---------------------------------------------------------------------
CREATE TABLE consultas (
    id_consulta  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL,
    empresa      VARCHAR(120) NOT NULL DEFAULT '',
    correo       VARCHAR(150) NOT NULL,
    mensaje      VARCHAR(1000) NOT NULL,
    leida        TINYINT(1) NOT NULL DEFAULT 0,
    fecha        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
-- DATOS DE DEMOSTRACIÓN (empresas y personas ficticias)
-- Contraseña del administrador: Admin123!
-- Contraseña de las empresas:   Empresa123!
-- =====================================================================

INSERT INTO usuarios (id_usuario, razon_social, cuit, nombre_contacto, correo, telefono, contrasena_hash, rol) VALUES
(1, 'Punto Catering', '30-00000000-1', 'Administración', 'admin@puntocatering.test', '11 4000-0000', '$2y$10$k.ewZLWneHoYDcpBQufS5.cc8t5MVVGP45tIQIPx02rsx//1eBZf6', 'administrador'),
(2, 'Constructora Andina S.A.', '30-11111111-2', 'Laura Méndez', 'compras@andina.test', '11 4111-1111', '$2y$10$9sSzL4n.rimNwl5bxVL4g.FpVp4TZ5Gvdzq6a1Sv91OncnAQQkjV2', 'cliente'),
(3, 'Estudio Lumen S.R.L.', '30-22222222-3', 'Tomás Ferreyra', 'oficina@lumen.test', '11 4222-2222', '$2y$10$9sSzL4n.rimNwl5bxVL4g.FpVp4TZ5Gvdzq6a1Sv91OncnAQQkjV2', 'cliente'),
(4, 'Tecnored Argentina S.A.', '30-33333333-4', 'Carla Sosa', 'rrhh@tecnored.test', '11 4333-3333', '$2y$10$9sSzL4n.rimNwl5bxVL4g.FpVp4TZ5Gvdzq6a1Sv91OncnAQQkjV2', 'cliente');

INSERT INTO categorias (id_categoria, nombre, descripcion) VALUES
(1, 'Coffee break', 'Pausas de café para reuniones, capacitaciones y jornadas.'),
(2, 'Almuerzo de trabajo', 'Viandas y platos para almorzar en la oficina.'),
(3, 'Cóctel', 'Bocados y bebidas para lanzamientos y encuentros de equipo.'),
(4, 'Evento corporativo', 'Propuestas completas para aniversarios y cierres de año.');

INSERT INTO menus (id_menu, id_categoria, nombre, descripcion, precio_por_persona, minimo_personas, es_vegetariano, es_sin_tacc, ilustracion) VALUES
(1, 1, 'Café clásico', 'Café de especialidad, té, jugo de naranja, medialunas y budines caseros.', 9500.00, 10, 1, 0, 'cafe'),
(2, 1, 'Desayuno energía', 'Café, licuados, yogur con granola, fruta de estación y tostados integrales.', 13500.00, 10, 1, 0, 'cafe'),
(3, 1, 'Pausa sin TACC', 'Café, té, jugo natural, pan de queso, cookies de almendra y fruta. Elaboración certificada sin TACC.', 14500.00, 10, 1, 1, 'cafe'),
(4, 2, 'Box ejecutivo', 'Vianda individual con plato principal del día, ensalada, postre y agua saborizada.', 18500.00, 5, 0, 0, 'vianda'),
(5, 2, 'Ensaladas de autor', 'Tres ensaladas completas para elegir, con semillas, quesos y aderezos caseros.', 16500.00, 5, 1, 1, 'ensalada'),
(6, 2, 'Parrilla en la oficina', 'Parrillero en el lugar con cortes seleccionados, guarniciones y ensaladas frescas.', 26000.00, 15, 0, 1, 'parrilla'),
(7, 3, 'Cóctel afterwork', 'Doce bocados fríos y calientes, cerveza artesanal, vino y bebidas sin alcohol.', 22000.00, 20, 0, 0, 'copa'),
(8, 3, 'Cóctel verde', 'Bocados de vegetales de estación, dips y mini tartas, con vino y bebidas sin alcohol.', 21000.00, 20, 1, 0, 'copa'),
(9, 4, 'Buffet aniversario', 'Mesa de entradas, dos platos principales, mesa dulce y bebidas. Incluye mozos.', 34000.00, 30, 0, 0, 'buffet'),
(10, 4, 'Cena de fin de año', 'Recepción, entrada, principal, postre, brindis y bebidas. Incluye vajilla y mozos.', 42000.00, 40, 0, 0, 'buffet');

INSERT INTO pedidos (id_pedido, id_usuario, fecha_evento, hora_evento, direccion_evento, cantidad_personas, observaciones, estado, total_estimado, total_final, comentario_admin) VALUES
(1, 2, '2026-12-10', '13:00:00', 'Av. Corrientes 1234, CABA', 30, 'Tres personas con dieta vegetariana.', 'pendiente', 555000.00, NULL, ''),
(2, 2, '2026-12-18', '17:30:00', 'Av. Corrientes 1234, CABA', 40, 'Cierre del proyecto Torre Norte.', 'presupuestado', 1260000.00, 1190000.00, 'Aplicamos una bonificación por combinar dos servicios el mismo día.'),
(3, 3, '2026-11-28', '12:30:00', 'Paraguay 850, CABA', 12, '', 'aprobado', 198000.00, 198000.00, 'Confirmado. La entrega se realiza media hora antes.'),
(4, 3, '2026-10-15', '09:00:00', 'Paraguay 850, CABA', 20, 'Capacitación interna.', 'finalizado', 270000.00, 270000.00, 'Servicio realizado.'),
(5, 4, '2026-11-20', '20:00:00', 'Av. del Libertador 4500, CABA', 50, '', 'rechazado', 1700000.00, 1650000.00, 'Incluye traslado y armado.'),
(6, 4, '2026-12-20', '20:30:00', 'Av. del Libertador 4500, CABA', 60, 'Fiesta de fin de año del equipo.', 'aprobado', 2520000.00, 2400000.00, 'Bonificación por cantidad de personas.'),
(7, 2, '2026-11-15', '12:00:00', 'Obra Parque Sur, Avellaneda', 20, 'Se reprogramó la visita.', 'cancelado', 520000.00, NULL, '');

INSERT INTO detalle_pedido (id_pedido, id_menu, precio_unitario) VALUES
(1, 4, 18500.00),
(2, 1, 9500.00),
(2, 7, 22000.00),
(3, 5, 16500.00),
(4, 2, 13500.00),
(5, 9, 34000.00),
(6, 10, 42000.00),
(7, 6, 26000.00);

INSERT INTO consultas (nombre, empresa, correo, mensaje, leida) VALUES
('Marina Ríos', 'Grupo Delta', 'marina@delta.test', '¿Trabajan en zona norte? Necesitamos un servicio mensual para unas 25 personas.', 0),
('Pablo Acosta', 'Acosta y Asociados', 'pablo@acosta.test', 'Quisiera saber con cuánta anticipación hay que pedir un cóctel para 100 personas.', 0),
('Inés Gallo', '', 'ines@correo.test', '¿Tienen opciones veganas para coffee break?', 1);
