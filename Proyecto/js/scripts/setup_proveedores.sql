-- Script to set up the supplier system from scratch
-- Create proveedores table
CREATE TABLE IF NOT EXISTS proveedores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ruc TEXT NOT NULL,
    razon_social TEXT NOT NULL,
    telefono TEXT,
    correo TEXT,
    direccion TEXT
);

-- Insert Michael Olvera as the main supplier
INSERT OR REPLACE INTO proveedores (id, ruc, razon_social, telefono, correo, direccion) 
VALUES (1, '0999999999001', 'Michael Olvera Repuestos', '0991234567', 'michael@repuestos.com', 'Av. Principal 123');

-- Update pedidos table to ensure it has proper structure
-- Note: SQLite doesn't support adding foreign keys to existing tables easily
-- So we'll work with the current structure

-- Add some sample pedidos for testing
INSERT OR IGNORE INTO pedidos (id, proveedor_id, repuesto_id, cantidad, fecha, estado) VALUES
(1, 1, 1, 5, '2024-01-15', 'pendiente'),
(2, 1, 2, 3, '2024-01-20', 'pendiente'),
(3, 1, 3, 2, '2024-01-10', 'entregado');

-- Ensure Michael Olvera user exists with correct role
UPDATE usuarios SET rol = 'proveedor' WHERE usuario = 'michael';
