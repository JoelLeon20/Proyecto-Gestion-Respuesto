<?php
/**
 * DatabaseTest - Pruebas para la conexión y operaciones de base de datos
 */

require_once __DIR__ . '/../conexion.php';

class DatabaseTest {
    
    public function testDatabaseConnection() {
        global $conn;
        try {
            // Verificar que la conexión existe
            if (!$conn) {
                return "La conexión a la base de datos no está establecida";
            }
            // Probar una consulta simple
            $stmt = $conn->query("SELECT 1 AS uno");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && isset($result['uno']) && $result['uno'] == 1) {
                return true;
            } else {
                return "La consulta de prueba no devolvió el resultado esperado";
            }
        } catch (Exception $e) {
            return "Error de conexión: " . $e->getMessage();
        }
    }
    
    public function testUsersTableExists() {
        global $conn;
        
        try {
            $stmt = $conn->query("DESCRIBE usuarios");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $expectedColumns = ['id', 'usuario', 'contrasena', 'nombre', 'rol'];
            foreach ($expectedColumns as $column) {
                if (!in_array($column, $columns)) {
                    return "Falta la columna '$column' en la tabla usuarios";
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            return "Error al verificar tabla usuarios: " . $e->getMessage();
        }
    }
    
    public function testRepuestosTableExists() {
        global $conn;
        
        try {
            $stmt = $conn->query("DESCRIBE repuestos");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $expectedColumns = ['id', 'nombre', 'descripcion', 'stock'];
            foreach ($expectedColumns as $column) {
                if (!in_array($column, $columns)) {
                    return "Falta la columna '$column' en la tabla repuestos";
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            return "Error al verificar tabla repuestos: " . $e->getMessage();
        }
    }
    
    public function testInsertAndDeleteRepuesto() {
        global $conn;
        
        try {
            // Insertar un repuesto de prueba
            $stmt = $conn->prepare("INSERT INTO repuestos (nombre, descripcion, stock) VALUES (?, ?, ?)");
            $result = $stmt->execute(['TEST_REPUESTO', 'Repuesto de prueba', 10]);
            
            if (!$result) {
                return "No se pudo insertar el repuesto de prueba";
            }
            
            $insertId = $conn->lastInsertId();
            
            // Verificar que se insertó correctamente
            $stmt = $conn->prepare("SELECT * FROM repuestos WHERE id = ?");
            $stmt->execute([$insertId]);
            $repuesto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$repuesto || $repuesto['nombre'] !== 'TEST_REPUESTO') {
                return "El repuesto no se insertó correctamente";
            }
            
            // Eliminar el repuesto de prueba
            $stmt = $conn->prepare("DELETE FROM repuestos WHERE id = ?");
            $deleteResult = $stmt->execute([$insertId]);
            
            if (!$deleteResult) {
                return "No se pudo eliminar el repuesto de prueba";
            }
            
            return true;
            
        } catch (Exception $e) {
            return "Error en operaciones CRUD: " . $e->getMessage();
        }
    }
}
?>
