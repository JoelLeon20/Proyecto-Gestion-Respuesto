<?php
/**
 * Archivo principal para ejecutar todas las pruebas unitarias
 * 
 * Ejecuta este archivo en tu navegador para ver los resultados de las pruebas
 * URL: http://localhost/tu-proyecto/run_tests.php
 */

// Incluir todas las clases de prueba
require_once 'tests/TestRunner.php';
require_once 'tests/DatabaseTest.php';
require_once 'tests/AuthTest.php';
require_once 'tests/ValidationTest.php';
require_once 'tests/BusinessLogicTest.php';
require_once 'tests/IntegrationTest.php'; // Agregar pruebas de integración al ejecutor principal

// Crear el ejecutor de pruebas
$runner = new TestRunner();

// Agregar todas las pruebas
$runner->addTest(new DatabaseTest());
$runner->addTest(new AuthTest());
$runner->addTest(new ValidationTest());
$runner->addTest(new BusinessLogicTest());
$runner->addTest(new IntegrationTest()); // Nueva clase de pruebas de integración

// Ejecutar todas las pruebas
$runner->runAllTests();

echo "<hr>";
echo "<h3>📖 Cómo interpretar los resultados:</h3>";
echo "<ul>";
echo "<li><strong>✅ PASÓ:</strong> La funcionalidad está trabajando correctamente</li>";
echo "<li><strong>❌ FALLÓ:</strong> Hay un problema que necesita ser corregido</li>";
echo "<li><strong>Porcentaje de éxito:</strong> Indica qué tan saludable está tu aplicación</li>";
echo "</ul>";

echo "<h3>🔧 Próximos pasos:</h3>";
echo "<ul>";
echo "<li>Si hay pruebas fallidas, revisa los errores específicos</li>";
echo "<li>Ejecuta estas pruebas cada vez que hagas cambios al código</li>";
echo "<li>Agrega más pruebas cuando añadas nuevas funcionalidades</li>";
echo "</ul>";

echo "<h3>🎯 Tipos de Pruebas Incluidas:</h3>";
echo "<ul>";
echo "<li><strong>DatabaseTest:</strong> Conexión y estructura de base de datos</li>";
echo "<li><strong>AuthTest:</strong> Sistema de autenticación y roles</li>";
echo "<li><strong>ValidationTest:</strong> Validación de datos y seguridad</li>";
echo "<li><strong>BusinessLogicTest:</strong> Lógica de negocio del taller</li>";
echo "<li><strong>IntegrationTest:</strong> Integración entre módulos del sistema</li>";
echo "</ul>";
?>
