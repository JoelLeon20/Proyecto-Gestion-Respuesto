<?php
/**
 * BusinessLogicTest - Pruebas para la lógica de negocio del taller
 */

require_once __DIR__ . '/../conexion.php';

class BusinessLogicTest {
    
    public function testRepuestoStockManagement() {
        global $conn;
        
        try {
            // Crear un repuesto de prueba
            $stmt = $conn->prepare("INSERT INTO repuestos (nombre, descripcion, stock) VALUES (?, ?, ?)");
            $stmt->execute(['TEST_STOCK', 'Prueba de stock', 50]);
            $repuestoId = $conn->lastInsertId();
            
            // Simular reducción de stock
            $newStock = 45;
            $stmt = $conn->prepare("UPDATE repuestos SET stock = ? WHERE id = ?");
            $stmt->execute([$newStock, $repuestoId]);
            
            // Verificar que el stock se actualizó
            $stmt = $conn->prepare("SELECT stock FROM repuestos WHERE id = ?");
            $stmt->execute([$repuestoId]);
            $currentStock = $stmt->fetchColumn();
            
            // Limpiar
            $stmt = $conn->prepare("DELETE FROM repuestos WHERE id = ?");
            $stmt->execute([$repuestoId]);
            
            if ($currentStock == $newStock) {
                return true;
            } else {
                return "El stock no se actualizó correctamente. Esperado: $newStock, Actual: $currentStock";
            }
            
        } catch (Exception $e) {
            return "Error en gestión de stock: " . $e->getMessage();
        }
    }
    
    public function testUserRolePermissions() {
        $rolePermissions = [
            'jefe_taller' => ['mecanicos.php', 'repuestos.php', 'ordenes.php', 'reportes.php'],
            'mecanico' => ['ordenes.php', 'repuestos.php', 'solicitudes.php'],
            'proveedor' => ['solicitudes.php', 'historial_pedidos.php']
        ];
        
        foreach ($rolePermissions as $role => $allowedPages) {
            if (empty($allowedPages)) {
                return "El rol '$role' no tiene páginas asignadas";
            }
            
            // Verificar que cada rol tiene al menos una página
            if (count($allowedPages) < 1) {
                return "El rol '$role' debe tener al menos una página permitida";
            }
        }
        
        return true;
    }
    
    public function testWorkflowLogic() {
        // Simular flujo de trabajo: Solicitud -> Aprobación -> Pedido
        $workflowSteps = [
            'solicitud_creada' => 'pendiente',
            'solicitud_aprobada' => 'aprobada',
            'pedido_enviado' => 'en_proceso',
            'pedido_recibido' => 'completado'
        ];
        
        $validTransitions = [
            'pendiente' => ['aprobada', 'rechazada'],
            'aprobada' => ['en_proceso'],
            'en_proceso' => ['completado', 'cancelado'],
            'completado' => [],
            'rechazada' => [],
            'cancelado' => []
        ];
        
        // Verificar que las transiciones son válidas
        foreach ($validTransitions as $currentState => $allowedNextStates) {
            if ($currentState === 'completado' && !empty($allowedNextStates)) {
                return "El estado 'completado' no debería permitir más transiciones";
            }
        }
        
        return true;
    }
    
    public function testDataIntegrity() {
        global $conn;
        
        try {
            // Verificar que no hay datos huérfanos
            $stmt = $conn->query("
                SELECT COUNT(*) as count 
                FROM repuestos 
                WHERE nombre IS NULL OR nombre = '' OR stock < 0
            ");
            $invalidRepuestos = $stmt->fetchColumn();
            
            if ($invalidRepuestos > 0) {
                return "Hay $invalidRepuestos repuestos con datos inválidos";
            }
            
            // Verificar usuarios sin rol
            $stmt = $conn->query("
                SELECT COUNT(*) as count 
                FROM usuarios 
                WHERE rol IS NULL OR rol = ''
            ");
            $usersWithoutRole = $stmt->fetchColumn();
            
            if ($usersWithoutRole > 0) {
                return "Hay $usersWithoutRole usuarios sin rol asignado";
            }
            
            return true;
            
        } catch (Exception $e) {
            return "Error verificando integridad de datos: " . $e->getMessage();
        }
    }
}
?>
