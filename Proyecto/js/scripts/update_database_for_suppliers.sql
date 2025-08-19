-- Add delivered status to solicitudes for supplier workflow
-- Update the solicitudes table to support the new 'entregado' status
-- This allows the complete workflow: pendiente -> aprobada -> entregado

-- No structural changes needed, just ensure the estado column can handle 'entregado' value
-- The solicitudes table already supports text values for estado

-- Sample data to test the supplier workflow
INSERT OR IGNORE INTO solicitudes (mecanico_id, repuesto_id, cantidad, fecha, estado) VALUES
(1, 1, 5, '2024-01-15', 'aprobada'),
(2, 2, 3, '2024-01-16', 'aprobada'),
(1, 3, 2, '2024-01-17', 'entregado');
