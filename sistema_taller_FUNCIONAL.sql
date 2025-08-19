-- =====================================================
-- SISTEMA DE GESTIÓN DE TALLER AUTOMOTRIZ - AUTO MOTORES
-- Base de datos completa con todas las tablas y datos
-- =====================================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS sistema_taller;
USE sistema_taller;

-- Configurar charset
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- TABLA: usuarios
-- =====================================================
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    rol ENUM('jefe_taller', 'mecanico', 'proveedor') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: mecanicos
-- =====================================================
DROP TABLE IF EXISTS mecanicos;
CREATE TABLE mecanicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(15),
    direccion TEXT,
    especialidad VARCHAR(100),
    usuario_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- =====================================================
-- TABLA: proveedores
-- =====================================================
DROP TABLE IF EXISTS proveedores;
CREATE TABLE proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ruc VARCHAR(20) UNIQUE NOT NULL,
    razon_social VARCHAR(150) NOT NULL,
    telefono VARCHAR(15),
    correo VARCHAR(100),
    direccion TEXT,
    usuario_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- =====================================================
-- TABLA: repuestos
-- =====================================================
DROP TABLE IF EXISTS repuestos;
CREATE TABLE repuestos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    stock INT DEFAULT 0,
    precio_unitario DECIMAL(10,2) DEFAULT 0.00,
    stock_minimo INT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: ordenes
-- =====================================================
DROP TABLE IF EXISTS ordenes;
CREATE TABLE ordenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mecanico_id INT NOT NULL,
    descripcion TEXT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE,
    estado ENUM('pendiente', 'en_proceso', 'completada', 'cancelada') DEFAULT 'pendiente',
    cliente_nombre VARCHAR(100),
    vehiculo_placa VARCHAR(10),
    vehiculo_modelo VARCHAR(50),
    costo_total DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mecanico_id) REFERENCES mecanicos(id) ON DELETE CASCADE
);

-- =====================================================
-- TABLA: solicitudes
-- =====================================================
DROP TABLE IF EXISTS solicitudes;
CREATE TABLE solicitudes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mecanico_id INT NOT NULL,
    repuesto_id INT NOT NULL,
    cantidad INT NOT NULL,
    fecha DATE NOT NULL,
    estado ENUM('pendiente', 'aprobada', 'rechazada', 'entregada') DEFAULT 'pendiente',
    orden_id INT,
    observaciones TEXT,
    aprobado_por INT,
    fecha_aprobacion TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mecanico_id) REFERENCES mecanicos(id) ON DELETE CASCADE,
    FOREIGN KEY (repuesto_id) REFERENCES repuestos(id) ON DELETE CASCADE,
    FOREIGN KEY (orden_id) REFERENCES ordenes(id) ON DELETE SET NULL,
    FOREIGN KEY (aprobado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- =====================================================
-- TABLA: pedidos
-- =====================================================
DROP TABLE IF EXISTS pedidos;
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT NOT NULL,
    repuesto_id INT NOT NULL,
    cantidad INT NOT NULL,
    fecha DATE NOT NULL,
    estado ENUM('pendiente', 'confirmado', 'enviado', 'entregado', 'cancelado') DEFAULT 'pendiente',
    precio_unitario DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) DEFAULT 0.00,
    fecha_entrega_estimada DATE,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE CASCADE,
    FOREIGN KEY (repuesto_id) REFERENCES repuestos(id) ON DELETE CASCADE
);

-- =====================================================
-- TABLA: historial_entregas
-- =====================================================
DROP TABLE IF EXISTS historial_entregas;
CREATE TABLE historial_entregas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitud_id INT NOT NULL,
    mecanico_id INT NOT NULL,
    repuesto_id INT NOT NULL,
    proveedor_id INT,
    cantidad INT NOT NULL,
    fecha_solicitud DATE NOT NULL,
    fecha_entrega DATE,
    estado_anterior ENUM('pendiente', 'aprobada', 'rechazada') NOT NULL,
    notas TEXT,
    entregado_por VARCHAR(100),
    recibido_por VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (solicitud_id) REFERENCES solicitudes(id) ON DELETE CASCADE,
    FOREIGN KEY (mecanico_id) REFERENCES mecanicos(id) ON DELETE CASCADE,
    FOREIGN KEY (repuesto_id) REFERENCES repuestos(id) ON DELETE CASCADE,
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL
);

-- =====================================================
-- TABLA: reportes
-- =====================================================
DROP TABLE IF EXISTS reportes;
CREATE TABLE reportes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT,
    fecha DATE NOT NULL,
    monto DECIMAL(10,2) DEFAULT 0.00,
    tipo ENUM('ingresos', 'gastos', 'inventario', 'productividad') DEFAULT 'ingresos',
    generado_por INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (generado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- =====================================================
-- INSERTAR DATOS DE EJEMPLO
-- =====================================================

-- Usuarios (coinciden con los de tu imagen)
INSERT INTO usuarios (nombre, usuario, contrasena, rol) VALUES
('Joel León', 'joel', 'l123', 'jefe_taller'),
('Joseph Montesdeoca', 'joseph', 'm123', 'mecanico'),
('Michael Olvera', 'michael', 'o123', 'proveedor'),
('Sebastián Allauca', 'sebastian', 'a123', 'mecanico');

-- Mecánicos
INSERT INTO mecanicos (nombre, telefono, direccion, especialidad, usuario_id) VALUES
('Joseph Montesdeoca', '0987654321', 'Av. Principal 123', 'Motor y Transmisión', 2),
('Sebastián Allauca', '0976543210', 'Calle Secundaria 456', 'Frenos y Suspensión', 4);

-- Proveedores
INSERT INTO proveedores (ruc, razon_social, telefono, correo, direccion, usuario_id) VALUES
('1234567890001', 'Repuestos Olvera S.A.', '022345678', 'ventas@olvera.com', 'Zona Industrial Norte', 3),
('0987654321001', 'AutoPartes del Ecuador', '023456789', 'info@autopartes.ec', 'Av. Comercial 789', NULL);

-- Repuestos
INSERT INTO repuestos (nombre, descripcion, stock, precio_unitario, stock_minimo) VALUES
('Filtro de Aceite', 'Filtro de aceite para motor 1.6L', 25, 12.50, 5),
('Pastillas de Freno', 'Pastillas de freno delanteras', 15, 35.00, 3),
('Bujías', 'Bujías de encendido NGK', 40, 8.75, 10),
('Aceite Motor 5W30', 'Aceite sintético para motor', 30, 28.00, 8),
('Filtro de Aire', 'Filtro de aire del motor', 20, 15.25, 5),
('Correa de Distribución', 'Correa de distribución Gates', 8, 85.00, 2),
('Amortiguadores', 'Amortiguadores traseros KYB', 6, 120.00, 2),
('Radiador', 'Radiador de aluminio', 4, 180.00, 1);

-- Órdenes de trabajo
INSERT INTO ordenes (mecanico_id, descripcion, fecha_inicio, fecha_fin, estado, cliente_nombre, vehiculo_placa, vehiculo_modelo, costo_total) VALUES
(1, 'Cambio de aceite y filtros', '2024-01-15', '2024-01-15', 'completada', 'Carlos Mendoza', 'ABC-1234', 'Toyota Corolla 2018', 45.50),
(2, 'Reparación sistema de frenos', '2024-01-16', NULL, 'en_proceso', 'María González', 'XYZ-5678', 'Chevrolet Aveo 2020', 85.00),
(1, 'Mantenimiento preventivo', '2024-01-17', NULL, 'pendiente', 'Luis Rodríguez', 'DEF-9012', 'Nissan Sentra 2019', 120.00);

-- Solicitudes de repuestos
INSERT INTO solicitudes (mecanico_id, repuesto_id, cantidad, fecha, estado, orden_id, observaciones) VALUES
(1, 1, 2, '2024-01-15', 'entregada', 1, 'Para cambio de aceite orden #1'),
(1, 5, 1, '2024-01-15', 'entregada', 1, 'Filtro de aire orden #1'),
(2, 2, 1, '2024-01-16', 'aprobada', 2, 'Pastillas delanteras orden #2'),
(1, 3, 4, '2024-01-17', 'pendiente', 3, 'Bujías para mantenimiento'),
(2, 7, 2, '2024-01-18', 'pendiente', NULL, 'Amortiguadores para stock');

-- Pedidos a proveedores
INSERT INTO pedidos (proveedor_id, repuesto_id, cantidad, fecha, estado, precio_unitario, total, fecha_entrega_estimada) VALUES
(1, 1, 50, '2024-01-10', 'entregado', 11.00, 550.00, '2024-01-15'),
(1, 2, 20, '2024-01-12', 'enviado', 32.00, 640.00, '2024-01-20'),
(2, 6, 10, '2024-01-14', 'confirmado', 80.00, 800.00, '2024-01-25'),
(1, 8, 5, '2024-01-16', 'pendiente', 170.00, 850.00, '2024-01-30');

-- Historial de entregas
INSERT INTO historial_entregas (solicitud_id, mecanico_id, repuesto_id, proveedor_id, cantidad, fecha_solicitud, fecha_entrega, estado_anterior, notas, entregado_por, recibido_por) VALUES
(1, 1, 1, 1, 2, '2024-01-15', '2024-01-15', 'aprobada', 'Entrega completa sin observaciones', 'Joel León', 'Joseph Montesdeoca'),
(2, 1, 5, 1, 1, '2024-01-15', '2024-01-15', 'aprobada', 'Filtro en perfectas condiciones', 'Joel León', 'Joseph Montesdeoca');

-- Reportes
INSERT INTO reportes (titulo, descripcion, fecha, monto, tipo, generado_por) VALUES
('Ingresos Enero 2024', 'Reporte de ingresos del mes de enero', '2024-01-31', 2500.75, 'ingresos', 1),
('Gastos en Repuestos', 'Compra de repuestos a proveedores', '2024-01-31', 1890.00, 'gastos', 1),
('Inventario Actual', 'Estado actual del inventario de repuestos', '2024-01-31', 0.00, 'inventario', 1);

-- Restaurar verificación de claves foráneas
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- ÍNDICES PARA OPTIMIZACIÓN
-- =====================================================
CREATE INDEX idx_usuarios_usuario ON usuarios(usuario);
CREATE INDEX idx_usuarios_rol ON usuarios(rol);
CREATE INDEX idx_solicitudes_estado ON solicitudes(estado);
CREATE INDEX idx_ordenes_estado ON ordenes(estado);
CREATE INDEX idx_pedidos_estado ON pedidos(estado);
CREATE INDEX idx_repuestos_stock ON repuestos(stock);

-- =====================================================
-- SCRIPT COMPLETADO EXITOSAMENTE
-- =====================================================
SELECT 'Base de datos sistema_taller creada exitosamente con todos los datos de ejemplo' as mensaje;
