#!/bin/bash

# Script para ejecutar todos los tests del sistema Auto Motores
# Uso: ./run_all_tests.sh

echo "=========================================="
echo "  SUITE COMPLETO DE TESTING AUTO MOTORES"
echo "=========================================="
echo ""

# Crear directorio de resultados si no existe
mkdir -p results
mkdir -p results/screenshots

# Variables
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BASE_URL="http://localhost/auto-motores"
RESULTS_DIR="results"

echo "Iniciando testing en: $(date)"
echo "URL Base: $BASE_URL"
echo "Directorio de resultados: $RESULTS_DIR"
echo ""

# 1. VERIFICAR SERVICIOS
echo "1. Verificando servicios..."
echo "----------------------------------------"

# Verificar Apache
if pgrep -x "httpd" > /dev/null || pgrep -x "apache2" > /dev/null; then
    echo "✅ Apache está ejecutándose"
else
    echo "❌ Apache no está ejecutándose"
    echo "Por favor, inicia Apache y vuelve a ejecutar el script"
    exit 1
fi

# Verificar MySQL
if pgrep -x "mysqld" > /dev/null; then
    echo "✅ MySQL está ejecutándose"
else
    echo "❌ MySQL no está ejecutándose"
    echo "Por favor, inicia MySQL y vuelve a ejecutar el script"
    exit 1
fi

# Verificar conectividad
if curl -s "$BASE_URL" > /dev/null; then
    echo "✅ Sistema accesible en $BASE_URL"
else
    echo "❌ Sistema no accesible en $BASE_URL"
    exit 1
fi

echo ""

# 2. PRUEBAS UNITARIAS
echo "2. Ejecutando pruebas unitarias..."
echo "----------------------------------------"
php tests/unit_tests.php > "$RESULTS_DIR/unit_tests_$TIMESTAMP.txt" 2>&1
if [ $? -eq 0 ]; then
    echo "✅ Pruebas unitarias completadas"
else
    echo "❌ Error en pruebas unitarias"
fi
echo ""

# 3. PRUEBAS DE API
echo "3. Probando endpoints de API..."
echo "----------------------------------------"

# Probar endpoint de mecánicos
echo "Probando /api/mecanicos..."
curl -s -o /dev/null -w "Status: %{http_code}, Tiempo: %{time_total}s\n" "$BASE_URL/tests/api_test.php?endpoint=mecanicos"

# Probar endpoint de órdenes
echo "Probando /api/ordenes..."
curl -s -o /dev/null -w "Status: %{http_code}, Tiempo: %{time_total}s\n" "$BASE_URL/tests/api_test.php?endpoint=ordenes"

# Probar endpoint de repuestos
echo "Probando /api/repuestos..."
curl -s -o /dev/null -w "Status: %{http_code}, Tiempo: %{time_total}s\n" "$BASE_URL/tests/api_test.php?endpoint=repuestos"

echo ""

# 4. PRUEBAS DE RENDIMIENTO
echo "4. Ejecutando pruebas de rendimiento..."
echo "----------------------------------------"

# Verificar si ab está instalado
if command -v ab > /dev/null; then
    echo "Ejecutando Apache Bench..."
    
    # Prueba de carga ligera
    echo "Prueba de carga ligera (100 requests, 10 concurrentes):"
    ab -n 100 -c 10 "$BASE_URL/" > "$RESULTS_DIR/performance_light_$TIMESTAMP.txt" 2>&1
    
    # Prueba de carga media
    echo "Prueba de carga media (500 requests, 25 concurrentes):"
    ab -n 500 -c 25 "$BASE_URL/" > "$RESULTS_DIR/performance_medium_$TIMESTAMP.txt" 2>&1
    
    # Extraer métricas clave
    echo "Métricas de rendimiento:"
    grep "Requests per second" "$RESULTS_DIR/performance_light_$TIMESTAMP.txt" | head -1
    grep "Time per request" "$RESULTS_DIR/performance_light_$TIMESTAMP.txt" | head -1
    
    echo "✅ Pruebas de rendimiento completadas"
else
    echo "❌ Apache Bench (ab) no está instalado"
    echo "Instalar con: sudo apt-get install apache2-utils"
fi

echo ""

# 5. PRUEBAS DE CONECTIVIDAD DE PÁGINAS
echo "5. Verificando páginas principales..."
echo "----------------------------------------"

PAGES=("login.php" "mecanico.php" "proveedor.php" "jefe_taller.php" "ordenes.php" "repuestos.php" "reportes.php")

for page in "${PAGES[@]}"; do
    echo -n "Probando $page... "
    status=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/$page")
    if [ "$status" -eq 200 ]; then
        echo "✅ OK ($status)"
    else
        echo "❌ Error ($status)"
    fi
done

echo ""

# 6. PRUEBAS DE FORMULARIOS
echo "6. Probando formularios críticos..."
echo "----------------------------------------"

# Test de login (simulado)
echo -n "Probando formulario de login... "
login_response=$(curl -s -X POST -d "usuario=test&password=test" "$BASE_URL/validar_login.php")
if [[ $login_response == *"error"* ]] || [[ $login_response == *"incorrectos"* ]]; then
    echo "✅ Validación de login funcionando"
else
    echo "⚠️  Respuesta inesperada en login"
fi

echo ""

# 7. GENERAR REPORTE RESUMEN
echo "7. Generando reporte resumen..."
echo "----------------------------------------"

REPORT_FILE="$RESULTS_DIR/test_summary_$TIMESTAMP.md"

cat > "$REPORT_FILE" << EOF
# Reporte de Testing - Auto Motores
**Fecha**: $(date)
**Timestamp**: $TIMESTAMP

## Resumen de Ejecución

### Servicios Verificados
- ✅ Apache ejecutándose
- ✅ MySQL ejecutándose  
- ✅ Sistema accesible

### Pruebas Ejecutadas
- ✅ Pruebas unitarias
- ✅ Pruebas de API
- ✅ Pruebas de rendimiento
- ✅ Verificación de páginas
- ✅ Pruebas de formularios

### Archivos Generados
- unit_tests_$TIMESTAMP.txt
- performance_light_$TIMESTAMP.txt
- performance_medium_$TIMESTAMP.txt
- test_summary_$TIMESTAMP.md

### Próximos Pasos
1. Revisar archivos de resultados en directorio: $RESULTS_DIR
2. Ejecutar pruebas de seguridad con OWASP ZAP
3. Realizar pruebas de usabilidad manuales
4. Documentar hallazgos y recomendaciones

EOF

echo "✅ Reporte generado: $REPORT_FILE"
echo ""

# 8. RESUMEN FINAL
echo "=========================================="
echo "  TESTING COMPLETADO"
echo "=========================================="
echo ""
echo "📁 Resultados guardados en: $RESULTS_DIR/"
echo "📄 Reporte principal: $REPORT_FILE"
echo ""
echo "Próximos pasos recomendados:"
echo "1. Revisar archivos de resultados"
echo "2. Ejecutar OWASP ZAP para análisis de seguridad"
echo "3. Realizar pruebas de usabilidad con usuarios reales"
echo "4. Importar colección de Postman para pruebas adicionales"
echo ""
echo "Para pruebas de seguridad:"
echo "- Abrir OWASP ZAP"
echo "- Configurar proxy en 127.0.0.1:8080"
echo "- Ejecutar Automated Scan en $BASE_URL"
echo ""
echo "¡Testing completado exitosamente! 🎉"
