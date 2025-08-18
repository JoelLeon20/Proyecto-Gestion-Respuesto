#!/bin/bash
# Performance Testing Script for Auto Motores
# Requires Apache Bench (ab) - install with: sudo apt-get install apache2-utils

echo "🚗 Auto Motores - Pruebas de Rendimiento"
echo "========================================"

BASE_URL="http://localhost/auto-motores"
RESULTS_DIR="performance_results"
mkdir -p $RESULTS_DIR

echo "📊 Iniciando pruebas de carga..."

# Test 1: Login page load test
echo "1. Probando carga de página de login..."
ab -n 100 -c 10 -g "$RESULTS_DIR/login_load.dat" "$BASE_URL/login.php" > "$RESULTS_DIR/login_results.txt"

# Test 2: Dashboard load test (requires session - simplified)
echo "2. Probando carga de dashboard..."
ab -n 50 -c 5 -g "$RESULTS_DIR/dashboard_load.dat" "$BASE_URL/jefe_taller.php" > "$RESULTS_DIR/dashboard_results.txt"

# Test 3: API endpoints test
echo "3. Probando endpoints de API..."
ab -n 200 -c 20 -g "$RESULTS_DIR/api_load.dat" "$BASE_URL/tests/api_test.php/health" > "$RESULTS_DIR/api_results.txt"

# Test 4: Database stress test
echo "4. Probando consultas de base de datos..."
ab -n 100 -c 10 -g "$RESULTS_DIR/db_load.dat" "$BASE_URL/tests/api_test.php/mecanicos" > "$RESULTS_DIR/db_results.txt"

echo "✅ Pruebas completadas. Resultados guardados en: $RESULTS_DIR/"

# Generate summary report
echo "📋 Generando reporte resumen..."
cat > "$RESULTS_DIR/summary.html" << EOF
<!DOCTYPE html>
<html>
<head>
    <title>Auto Motores - Reporte de Rendimiento</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-result { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 8px; }
        .metric { display: inline-block; margin: 5px 15px 5px 0; }
        .good { color: #2e7d32; }
        .warning { color: #ef6c00; }
        .error { color: #c62828; }
    </style>
</head>
<body>
    <h1>🚗 Auto Motores - Reporte de Rendimiento</h1>
    <p>Fecha: $(date)</p>
    
    <div class="test-result">
        <h3>Resumen de Pruebas</h3>
        <p>Las pruebas de rendimiento evalúan:</p>
        <ul>
            <li>Tiempo de respuesta bajo carga</li>
            <li>Capacidad de usuarios concurrentes</li>
            <li>Estabilidad del sistema</li>
            <li>Rendimiento de la base de datos</li>
        </ul>
    </div>
    
    <div class="test-result">
        <h3>Métricas Objetivo</h3>
        <div class="metric good">✅ Tiempo de respuesta < 500ms</div>
        <div class="metric good">✅ 95% de requests exitosos</div>
        <div class="metric good">✅ Soporte para 20+ usuarios concurrentes</div>
    </div>
    
    <div class="test-result">
        <h3>Archivos de Resultados</h3>
        <ul>
            <li><a href="login_results.txt">Resultados Login</a></li>
            <li><a href="dashboard_results.txt">Resultados Dashboard</a></li>
            <li><a href="api_results.txt">Resultados API</a></li>
            <li><a href="db_results.txt">Resultados Base de Datos</a></li>
        </ul>
    </div>
</body>
</html>
EOF

echo "🎉 Reporte completo generado: $RESULTS_DIR/summary.html"
