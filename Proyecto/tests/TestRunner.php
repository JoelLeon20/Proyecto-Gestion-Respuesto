<?php
/**
 * TestRunner - Ejecutor de pruebas unitarias para Auto Motores
 * 
 * Este archivo ejecuta todas las pruebas unitarias del sistema y muestra
 * un reporte detallado de los resultados.
 */

class TestRunner {
    private $tests = [];
    private $passed = 0;
    private $failed = 0;
    private $errors = [];

    public function addTest($testClass) {
        $this->tests[] = $testClass;
    }

    public function runAllTests() {
        echo "<h1>🧪 Ejecutando Pruebas Unitarias - Auto Motores</h1>\n";
        echo "<style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .test-pass { color: #28a745; font-weight: bold; }
            .test-fail { color: #dc3545; font-weight: bold; }
            .test-error { background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px; }
            .summary { background: #e9ecef; padding: 15px; border-radius: 8px; margin: 20px 0; }
            .test-section { border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px; }
        </style>";

        foreach ($this->tests as $testClass) {
            $this->runTestClass($testClass);
        }

        $this->showSummary();
    }

    private function runTestClass($testClass) {
        echo "<div class='test-section'>";
        echo "<h2>📋 Ejecutando: " . get_class($testClass) . "</h2>";
        
        $methods = get_class_methods($testClass);
        foreach ($methods as $method) {
            if (strpos($method, 'test') === 0) {
                $this->runSingleTest($testClass, $method);
            }
        }
        echo "</div>";
    }

    private function runSingleTest($testClass, $method) {
        try {
            $result = $testClass->$method();
            if ($result === true) {
                echo "<div class='test-pass'>✅ $method - PASÓ</div>";
                $this->passed++;
            } else {
                echo "<div class='test-fail'>❌ $method - FALLÓ</div>";
                if (is_string($result)) {
                    echo "<div class='test-error'>Error: $result</div>";
                    $this->errors[] = "$method: $result";
                }
                $this->failed++;
            }
        } catch (Exception $e) {
            echo "<div class='test-fail'>❌ $method - ERROR</div>";
            echo "<div class='test-error'>Excepción: " . $e->getMessage() . "</div>";
            $this->errors[] = "$method: " . $e->getMessage();
            $this->failed++;
        }
    }

    private function showSummary() {
        $total = $this->passed + $this->failed;
        $percentage = $total > 0 ? round(($this->passed / $total) * 100, 2) : 0;
        
        echo "<div class='summary'>";
        echo "<h2>📊 Resumen de Pruebas</h2>";
        echo "<p><strong>Total de pruebas:</strong> $total</p>";
        echo "<p><strong>Pasaron:</strong> <span class='test-pass'>{$this->passed}</span></p>";
        echo "<p><strong>Fallaron:</strong> <span class='test-fail'>{$this->failed}</span></p>";
        echo "<p><strong>Porcentaje de éxito:</strong> $percentage%</p>";
        
        if (!empty($this->errors)) {
            echo "<h3>🚨 Errores encontrados:</h3>";
            foreach ($this->errors as $error) {
                echo "<div class='test-error'>$error</div>";
            }
        }
        echo "</div>";
    }
}
?>
