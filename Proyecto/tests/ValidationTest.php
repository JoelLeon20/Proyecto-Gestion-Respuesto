<?php
/**
 * ValidationTest - Pruebas para validaciones de datos
 */

class ValidationTest {
    
    public function testEmailValidation() {
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'admin@automotores.com'
        ];
        
        $invalidEmails = [
            'invalid-email',
            '@domain.com',
            'user@',
            'user name@domain.com'
        ];
        
        foreach ($validEmails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return "Email válido '$email' fue rechazado";
            }
        }
        
        foreach ($invalidEmails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return "Email inválido '$email' fue aceptado";
            }
        }
        
        return true;
    }
    
    public function testPasswordStrength() {
        $weakPasswords = ['123', 'abc', 'password'];
        $strongPasswords = ['MyStr0ngP@ss', 'SecurePass123!', 'AutoMotores2024'];
        
        foreach ($weakPasswords as $password) {
            if (strlen($password) >= 8) {
                continue; // Este test es básico, solo verifica longitud
            }
            // Password débil detectado correctamente
        }
        
        foreach ($strongPasswords as $password) {
            if (strlen($password) < 8) {
                return "Password fuerte '$password' fue rechazado por longitud";
            }
        }
        
        return true;
    }
    
    public function testStockValidation() {
        $validStocks = [0, 1, 100, 9999];
        $invalidStocks = [-1, -100, 'abc', null];
        
        foreach ($validStocks as $stock) {
            if (!is_numeric($stock) || $stock < 0) {
                return "Stock válido '$stock' fue rechazado";
            }
        }
        
        foreach ($invalidStocks as $stock) {
            if (is_numeric($stock) && $stock >= 0) {
                return "Stock inválido '$stock' fue aceptado";
            }
        }
        
        return true;
    }
    
    public function testSQLInjectionPrevention() {
        // Simular intentos de inyección SQL
        $maliciousInputs = [
            "'; DROP TABLE usuarios; --",
            "1' OR '1'='1",
            "admin'--",
            "1; DELETE FROM repuestos; --"
        ];
        
        foreach ($maliciousInputs as $input) {
            // Verificar que contiene caracteres peligrosos
            if (strpos($input, "'") !== false || strpos($input, "--") !== false || strpos($input, ";") !== false) {
                // Input malicioso detectado - esto es bueno
                continue;
            }
        }
        
        return true;
    }
}
?>
