# Guía Completa de Testing - Sistema Auto Motores

## Índice
1. [Configuración Inicial](#configuración-inicial)
2. [Pruebas de API REST](#pruebas-de-api-rest)
3. [Pruebas con Postman](#pruebas-con-postman)
4. [Pruebas Unitarias PHP](#pruebas-unitarias-php)
5. [Pruebas de Usabilidad](#pruebas-de-usabilidad)
6. [Pruebas de Rendimiento](#pruebas-de-rendimiento)
7. [Análisis de Seguridad](#análisis-de-seguridad)
8. [Interpretación de Resultados](#interpretación-de-resultados)

## Configuración Inicial

### Requisitos Previos
- XAMPP/WAMP/LAMP instalado
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Postman instalado
- Apache Bench (ab) instalado
- OWASP ZAP instalado

### Configuración del Entorno
\`\`\`bash
# 1. Iniciar servicios
# En XAMPP: Iniciar Apache y MySQL

# 2. Verificar que el sistema esté funcionando
# Abrir: http://localhost/auto-motores/

# 3. Verificar conexión a base de datos
# Revisar que conexion.php funcione correctamente
\`\`\`

## Pruebas de API REST

### Paso 1: Activar la API
\`\`\`bash
# Copiar el archivo api_test.php a la raíz del proyecto
# Acceder a: http://localhost/auto-motores/tests/api_test.php
\`\`\`

### Paso 2: Probar Endpoints
\`\`\`bash
# Mecánicos
GET http://localhost/auto-motores/tests/api_test.php?endpoint=mecanicos
POST http://localhost/auto-motores/tests/api_test.php?endpoint=mecanicos&action=create

# Órdenes de Trabajo
GET http://localhost/auto-motores/tests/api_test.php?endpoint=ordenes
POST http://localhost/auto-motores/tests/api_test.php?endpoint=ordenes&action=create

# Repuestos
GET http://localhost/auto-motores/tests/api_test.php?endpoint=repuestos
POST http://localhost/auto-motores/tests/api_test.php?endpoint=repuestos&action=create
\`\`\`

### Resultados Esperados
- **200 OK**: Operación exitosa
- **201 Created**: Recurso creado correctamente
- **400 Bad Request**: Datos inválidos
- **500 Internal Server Error**: Error del servidor

## Pruebas con Postman

### Paso 1: Importar Colección
1. Abrir Postman
2. Click en "Import"
3. Seleccionar `tests/postman_collection.json`
4. La colección "Auto Motores API" aparecerá

### Paso 2: Configurar Variables
\`\`\`json
{
  "base_url": "http://localhost/auto-motores",
  "api_url": "http://localhost/auto-motores/tests/api_test.php"
}
\`\`\`

### Paso 3: Ejecutar Pruebas
1. **Pruebas Funcionales**:
   - Ejecutar cada request individualmente
   - Verificar códigos de respuesta
   - Validar estructura JSON

2. **Pruebas de Carga Ligera**:
   - Usar "Collection Runner"
   - Configurar 10-50 iteraciones
   - Monitorear tiempos de respuesta

### Métricas a Documentar
- Tiempo de respuesta promedio
- Tasa de éxito (%)
- Errores encontrados
- Throughput (requests/segundo)

## Pruebas Unitarias PHP

### Paso 1: Ejecutar Pruebas
\`\`\`bash
# Desde el navegador - NUEVA IMPLEMENTACIÓN
http://localhost/auto-motores/run_tests.php

# O desde línea de comandos
php tests/unit_tests.php
\`\`\`

### Paso 2: Interpretar Resultados
\`\`\`
✓ PASS: testDatabaseConnection - Conexión a BD exitosa
✓ PASS: testUserAuthentication - Autenticación funcional
✗ FAIL: testDataValidation - Validación de datos falló
\`\`\`

### Cobertura de Pruebas - ACTUALIZADA
- **Conexión a Base de Datos**: Verifica conectividad y estructura de tablas
- **Autenticación**: Valida login/logout y roles de usuario
- **Validación de Datos**: Comprueba sanitización y prevención de SQL injection
- **Operaciones CRUD**: Testa crear/leer/actualizar/eliminar repuestos
- **Lógica de Negocio**: Verifica flujos de trabajo y gestión de stock
- **Integridad de Datos**: Comprueba consistencia de la base de datos

### Nuevas Clases de Prueba Implementadas

#### DatabaseTest
- `testDatabaseConnection()`: Verifica conexión a MySQL
- `testUsersTableExists()`: Confirma estructura de tabla usuarios
- `testRepuestosTableExists()`: Confirma estructura de tabla repuestos
- `testInsertAndDeleteRepuesto()`: Prueba operaciones CRUD básicas

#### AuthTest
- `testValidUserLogin()`: Valida autenticación con credenciales correctas
- `testInvalidUserLogin()`: Verifica rechazo de credenciales incorrectas
- `testRoleValidation()`: Confirma existencia de roles del sistema
- `testSessionSecurity()`: Verifica funciones de seguridad de sesión

#### ValidationTest
- `testEmailValidation()`: Valida formato de emails
- `testPasswordStrength()`: Verifica fortaleza de contraseñas
- `testStockValidation()`: Valida números de stock
- `testSQLInjectionPrevention()`: Detecta intentos de inyección SQL

#### BusinessLogicTest
- `testRepuestoStockManagement()`: Verifica gestión de inventario
- `testUserRolePermissions()`: Confirma permisos por rol
- `testWorkflowLogic()`: Valida flujos de trabajo del taller
- `testDataIntegrity()`: Verifica integridad de datos del sistema

### Ejecución Automatizada
\`\`\`bash
# Ejecutar desde la raíz del proyecto
php run_tests.php
\`\`\`

El nuevo sistema de pruebas proporciona:
- **Reporte visual HTML** con colores y estadísticas
- **Detección automática** de métodos de prueba
- **Manejo de excepciones** robusto
- **Estadísticas detalladas** de éxito/fallo
- **Guía de interpretación** integrada

## Pruebas de Usabilidad

### Paso 1: Preparar Evaluación
\`\`\`bash
# Abrir el archivo de pruebas
http://localhost/auto-motores/tests/usability_test.html
\`\`\`

### Paso 2: Ejecutar Escenarios
1. **Registro de Mecánico**:
   - Tiempo: < 2 minutos
   - Clicks: < 10
   - Errores: 0

2. **Creación de Orden de Trabajo**:
   - Tiempo: < 3 minutos
   - Campos obligatorios claros
   - Validación inmediata

3. **Búsqueda de Repuestos**:
   - Resultados en < 2 segundos
   - Filtros funcionales
   - Paginación clara

### Métricas de Usabilidad
- **Eficiencia**: Tiempo para completar tareas
- **Efectividad**: Tasa de éxito en tareas
- **Satisfacción**: Escala 1-5 de facilidad de uso

## Pruebas de Rendimiento

### Paso 1: Instalar Apache Bench
\`\`\`bash
# Ubuntu/Debian
sudo apt-get install apache2-utils

# Windows (incluido en XAMPP)
# Usar: C:\xampp\apache\bin\ab.exe

# macOS
brew install httpd
\`\`\`

### Paso 2: Ejecutar Pruebas
\`\`\`bash
# Hacer ejecutable el script
chmod +x tests/performance_test.sh

# Ejecutar pruebas
./tests/performance_test.sh
\`\`\`

### Paso 3: Interpretar Resultados
\`\`\`
Requests per second: 150.23 [#/sec] (mean)
Time per request: 6.656 [ms] (mean)
Transfer rate: 45.67 [Kbytes/sec] received
\`\`\`

### Benchmarks Objetivo
- **Requests/segundo**: > 100
- **Tiempo de respuesta**: < 200ms
- **Concurrencia**: Soportar 50 usuarios simultáneos

## Análisis de Seguridad

### Paso 1: Instalar OWASP ZAP
1. Descargar desde: https://www.zaproxy.org/download/
2. Instalar y ejecutar
3. Configurar proxy en 127.0.0.1:8080

### Paso 2: Configurar Escaneo
1. **URL Objetivo**: `http://localhost/auto-motores/`
2. **Tipo de Escaneo**: Automated Scan
3. **Autenticación**: Configurar login si es necesario

### Paso 3: Ejecutar Análisis
\`\`\`
1. Quick Start → Automated Scan
2. Ingresar URL del sistema
3. Esperar completar escaneo (15-30 min)
4. Revisar alertas generadas
\`\`\`

### Vulnerabilidades Comunes a Verificar
- **SQL Injection**: En formularios de búsqueda
- **XSS**: En campos de texto
- **CSRF**: En formularios críticos
- **Autenticación**: Bypass de login
- **Autorización**: Acceso no autorizado

### Paso 4: Remediar Vulnerabilidades
Seguir checklist en `tests/security_checklist.md`

## Interpretación de Resultados

### Criterios de Aceptación

#### Funcionalidad
- ✅ Todas las pruebas unitarias pasan
- ✅ API responde correctamente
- ✅ CRUD operations funcionan

#### Rendimiento
- ✅ Tiempo de respuesta < 200ms
- ✅ Soporta 50+ usuarios concurrentes
- ✅ Sin memory leaks

#### Usabilidad
- ✅ Tareas completadas en tiempo objetivo
- ✅ Tasa de error < 5%
- ✅ Satisfacción > 4/5

#### Seguridad
- ✅ Sin vulnerabilidades críticas
- ✅ Sin vulnerabilidades altas
- ✅ Vulnerabilidades medias < 3

### Documentación de Resultados

#### Formato de Reporte
\`\`\`markdown
## Reporte de Testing - Auto Motores
**Fecha**: [Fecha de ejecución]
**Versión**: [Versión del sistema]
**Tester**: [Nombre del evaluador]

### Resumen Ejecutivo
- Pruebas ejecutadas: X/Y
- Tasa de éxito: XX%
- Vulnerabilidades encontradas: X
- Recomendaciones: X

### Resultados Detallados
[Incluir métricas específicas de cada tipo de prueba]

### Recomendaciones
[Lista de mejoras sugeridas]
\`\`\`

## Automatización de Pruebas

### Script de Ejecución Completa
\`\`\`bash
#!/bin/bash
echo "Iniciando suite completo de testing..."

# 1. Pruebas unitarias
echo "Ejecutando pruebas unitarias..."
php tests/unit_tests.php > results/unit_test_results.txt

# 2. Pruebas de rendimiento
echo "Ejecutando pruebas de rendimiento..."
./tests/performance_test.sh > results/performance_results.txt

# 3. Generar reporte
echo "Generando reporte final..."
php tests/generate_report.php

echo "Testing completado. Revisar carpeta results/"
\`\`\`

### Integración Continua
Para proyectos más avanzados, considerar:
- GitHub Actions
- Jenkins
- GitLab CI/CD

## Conclusión

Este conjunto de pruebas proporciona una evaluación completa del sistema Auto Motores, cubriendo aspectos funcionales, de rendimiento, usabilidad y seguridad. La documentación de resultados permitirá identificar áreas de mejora y garantizar la calidad del software.
