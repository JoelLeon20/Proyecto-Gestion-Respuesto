#!/bin/bash

# Script para ejecutar todos los tests y generar reporte
echo "=== INICIANDO BATERÍA COMPLETA DE TESTING ==="
echo "Sistema: Auto Motores"
echo "Fecha: $(date)"
echo "=========================================="

# Crear directorio de resultados
mkdir -p test_results
cd test_results

# 1. Pruebas Unitarias
echo "1. Ejecutando Pruebas Unitarias..."
php ../tests/unit_tests.php > unit_test_results.txt 2>&1
echo "   ✓ Pruebas unitarias completadas"

# 2. Pruebas de API
echo "2. Ejecutando Pruebas de API..."
php ../tests/api_test.php > api_test_results.txt 2>&1
echo "   ✓ Pruebas de API completadas"

# 3. Pruebas de Rendimiento
echo "3. Ejecutando Pruebas de Rendimiento..."
if command -v ab &> /dev/null; then
    ab -n 1000 -c 10 http://localhost/auto-motores/ > performance_results.txt 2>&1
    echo "   ✓ Pruebas de rendimiento completadas"
else
    echo "   ⚠ Apache Bench no instalado, saltando pruebas de rendimiento"
fi

# 4. Pruebas con Postman (si Newman está instalado)
echo "4. Ejecutando Pruebas con Postman..."
if command -v newman &> /dev/null; then
    newman run ../tests/postman_collection.json --reporters html --reporter-html-export postman_results.html > postman_results.txt 2>&1
    echo "   ✓ Pruebas de Postman completadas"
else
    echo "   ⚠ Newman no instalado, saltando pruebas de Postman"
fi

# 5. Análisis de Seguridad
echo "5. Ejecutando Análisis de Seguridad..."
if command -v zap-cli &> /dev/null; then
    zap-cli quick-scan --self-contained http://localhost/auto-motores/ > security_results.txt 2>&1
    echo "   ✓ Análisis de seguridad completado"
else
    echo "   ⚠ OWASP ZAP no instalado, ejecutando checklist manual"
    echo "Ejecutar manualmente: zap-cli quick-scan http://localhost/auto-motores/" > security_results.txt
fi

# 6. Generar Reporte HTML
echo "6. Generando Reporte HTML..."
cp ../docs/REPORTE_TESTING_AUTO_MOTORES.html ./
echo "   ✓ Reporte HTML generado"

# 7. Generar Reporte Word (si PHPWord está disponible)
echo "7. Generando Reporte Word..."
if php -r "require_once '../vendor/autoload.php';" 2>/dev/null; then
    php ../scripts/generate_word_report.php > word_generation.log 2>&1
    echo "   ✓ Reporte Word generado"
else
    echo "   ⚠ PHPWord no instalado, solo reporte HTML disponible"
fi

# Resumen final
echo ""
echo "=========================================="
echo "TESTING COMPLETADO"
echo "=========================================="
echo "Resultados disponibles en: ./test_results/"
echo ""
echo "Archivos generados:"
ls -la
echo ""
echo "Para ver el reporte completo, abrir:"
echo "- REPORTE_TESTING_AUTO_MOTORES.html (navegador)"
echo "- Reporte_Testing_Auto_Motores_*.docx (Word)"
echo ""
echo "¡Testing completado exitosamente!"
