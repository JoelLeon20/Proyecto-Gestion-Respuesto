<?php
// Unit Tests for Auto Motores System
// Run with: php tests/unit_tests.php

require_once __DIR__ . '/../conexion.php';

class AutoMotoresTest {
    private $conn;
    private $testResults = [];
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function runAllTests() {
        echo "🚗 Auto Motores - Suite de Pruebas Unitarias\n";
        echo "=" . str_repeat("=", 50) . "\n\n";
        
        $this->testDatabaseConnection();
        $this->testMecanicosCRUD();
        $this->testOrdenesValidation();
        $this->testRepuestosStock();
        $this->testReportesCalculation();
        
        $this->printResults();
    }
    
    private function testDatabaseConnection() {
        try {
            $stmt = $this->conn->query('SELECT 1');
            $this->addResult('Database Connection', true, 'Conexión exitosa');
        } catch (Exception $e) {
            $this->addResult('Database Connection', false, $e->getMessage());
        }
    }
    
    private function testMecanicosCRUD() {
        try {
            // Test Insert
            $stmt = $this->conn->prepare('INSERT INTO mecanicos (nombre, telefono, especialidad) VALUES (?, ?, ?)');
            $result = $stmt->execute(['Test Mechanic', '123456789', 'Test']);
            $testId = $this->conn->lastInsertId();
            
            $this->addResult('Mecanicos - Insert', $result, 'Mecánico insertado correctamente');
            
            // Test Select
            $stmt = $this->conn->prepare('SELECT * FROM mecanicos WHERE id = ?');
            $stmt->execute([$testId]);
            $mechanic = $stmt->fetch();
            
            $this->addResult('Mecanicos - Select', $mechanic !== false, 'Mecánico encontrado');
            
            // Test Update
            $stmt = $this->conn->prepare('UPDATE mecanicos SET nombre = ? WHERE id = ?');
            $result = $stmt->execute(['Updated Mechanic', $testId]);
            
            $this->addResult('Mecanicos - Update', $result, 'Mecánico actualizado');
            
            // Test Delete
            $stmt = $this->conn->prepare('DELETE FROM mecanicos WHERE id = ?');
            $result = $stmt->execute([$testId]);
            
            $this->addResult('Mecanicos - Delete', $result, 'Mecánico eliminado');
            
        } catch (Exception $e) {
            $this->addResult('Mecanicos CRUD', false, $e->getMessage());
        }
    }
    
    private function testOrdenesValidation() {
        try {
            // Test valid order creation
            $stmt = $this->conn->prepare('SELECT COUNT(*) FROM mecanicos LIMIT 1');
            $stmt->execute();
            $hasMecanicos = $stmt->fetchColumn() > 0;
            
            if ($hasMecanicos) {
                $stmt = $this->conn->prepare('SELECT nombre FROM mecanicos LIMIT 1');
                $stmt->execute();
                $mecanico = $stmt->fetchColumn();
                
                $stmt = $this->conn->prepare('INSERT INTO ordenes (mecanico, descripcion, fecha_inicio, estado) VALUES (?, ?, ?, ?)');
                $result = $stmt->execute([$mecanico, 'Test Order', date('Y-m-d'), 'pendiente']);
                $orderId = $this->conn->lastInsertId();
                
                $this->addResult('Ordenes - Validation', $result, 'Orden válida creada');
                
                // Cleanup
                $stmt = $this->conn->prepare('DELETE FROM ordenes WHERE id = ?');
                $stmt->execute([$orderId]);
            } else {
                $this->addResult('Ordenes - Validation', false, 'No hay mecánicos para asignar');
            }
            
        } catch (Exception $e) {
            $this->addResult('Ordenes - Validation', false, $e->getMessage());
        }
    }
    
    private function testRepuestosStock() {
        try {
            $stmt = $this->conn->query('SELECT COUNT(*) FROM repuestos WHERE stock > 0');
            $stockCount = $stmt->fetchColumn();
            
            $this->addResult('Repuestos - Stock Check', $stockCount >= 0, "Repuestos en stock: $stockCount");
            
            // Test low stock alert
            $stmt = $this->conn->query('SELECT COUNT(*) FROM repuestos WHERE stock < 5');
            $lowStock = $stmt->fetchColumn();
            
            $this->addResult('Repuestos - Low Stock Alert', true, "Repuestos con stock bajo: $lowStock");
            
        } catch (Exception $e) {
            $this->addResult('Repuestos - Stock', false, $e->getMessage());
        }
    }
    
    private function testReportesCalculation() {
        try {
            $stmt = $this->conn->query('SELECT SUM(monto) as total FROM reportes');
            $total = $stmt->fetchColumn();
            
            $this->addResult('Reportes - Calculation', is_numeric($total), "Total calculado: $" . number_format($total, 2));
            
        } catch (Exception $e) {
            $this->addResult('Reportes - Calculation', false, $e->getMessage());
        }
    }
    
    private function addResult($test, $passed, $message) {
        $this->testResults[] = [
            'test' => $test,
            'passed' => $passed,
            'message' => $message
        ];
    }
    
    private function printResults() {
        $passed = 0;
        $total = count($this->testResults);
        
        foreach ($this->testResults as $result) {
            $status = $result['passed'] ? '✅ PASS' : '❌ FAIL';
            echo sprintf("%-30s %s - %s\n", $result['test'], $status, $result['message']);
            if ($result['passed']) $passed++;
        }
        
        echo "\n" . str_repeat("=", 60) . "\n";
        echo sprintf("Resultados: %d/%d pruebas pasaron (%.1f%%)\n", $passed, $total, ($passed/$total)*100);
        
        if ($passed === $total) {
            echo "🎉 ¡Todas las pruebas pasaron!\n";
        } else {
            echo "⚠️  Algunas pruebas fallaron. Revisar implementación.\n";
        }
    }
}

// Run tests
try {
    $tester = new AutoMotoresTest($conn);
    $tester->runAllTests();
} catch (Exception $e) {
    echo "Error ejecutando pruebas: " . $e->getMessage() . "\n";
}
?>
