<?php
/**
 * AuthTest - Pruebas para el sistema de autenticación
 */

require_once __DIR__ . '/../auth_middleware.php';
require_once __DIR__ . '/../conexion.php';

class AuthTest {
    
    public function testValidUserLogin() {
        global $conn;
        
        try {
            // Buscar un usuario válido en la base de datos
            $stmt = $conn->query("SELECT usuario, contrasena FROM usuarios LIMIT 1");
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return "No hay usuarios en la base de datos para probar";
            }
            
            // Simular validación de login
            $stmt = $conn->prepare("SELECT nombre, rol FROM usuarios WHERE usuario = ? AND contrasena = ?");
            $stmt->execute([$user['usuario'], $user['contrasena']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && isset($result['nombre']) && isset($result['rol'])) {
                return true;
            } else {
                return "La validación de usuario falló";
            }
            
        } catch (Exception $e) {
            return "Error en validación de usuario: " . $e->getMessage();
        }
    }
    
    public function testInvalidUserLogin() {
        global $conn;
        
        try {
            // Probar con credenciales inválidas
            $stmt = $conn->prepare("SELECT nombre, rol FROM usuarios WHERE usuario = ? AND contrasena = ?");
            $stmt->execute(['usuario_inexistente', 'password_incorrecto']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Debe devolver false para credenciales inválidas
            if ($result === false) {
                return true;
            } else {
                return "El sistema permitió login con credenciales inválidas";
            }
            
        } catch (Exception $e) {
            return "Error en prueba de credenciales inválidas: " . $e->getMessage();
        }
    }
    
    public function testRoleValidation() {
        global $conn;
        
        try {
            // Obtener usuarios con diferentes roles
            $stmt = $conn->query("SELECT DISTINCT rol FROM usuarios");
            $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $expectedRoles = ['jefe_taller', 'mecanico', 'proveedor'];
            
            foreach ($expectedRoles as $expectedRole) {
                if (!in_array($expectedRole, $roles)) {
                    return "Falta el rol '$expectedRole' en la base de datos";
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            return "Error en validación de roles: " . $e->getMessage();
        }
    }
    
    public function testSessionSecurity() {
        // Simular inicialización de sesión segura
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar que las funciones de seguridad existen
        if (!function_exists('inicializar_sesion_segura')) {
            return "La función inicializar_sesion_segura no existe";
        }
        
        if (!function_exists('verificar_autenticacion')) {
            return "La función verificar_autenticacion no existe";
        }
        
        return true;
    }
}
?>
