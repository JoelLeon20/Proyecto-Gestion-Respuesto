-- Script to clean up all supplier-related data from database
-- Remove supplier users
DELETE FROM usuarios WHERE rol = 'proveedor';

-- Remove supplier references from pedidos table
UPDATE pedidos SET proveedor_id = NULL WHERE proveedor_id IS NOT NULL;

-- Drop proveedores table
DROP TABLE IF EXISTS proveedores;

-- Remove proveedor_id column from pedidos table if it exists
-- Note: This may require recreating the table depending on SQLite version
