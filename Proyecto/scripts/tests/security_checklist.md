# 🔒 Auto Motores - Lista de Verificación de Seguridad

## Análisis con OWASP ZAP

### Configuración Inicial
1. **Instalar OWASP ZAP**: https://www.zaproxy.org/download/
2. **Configurar proxy**: Puerto 8080
3. **URL objetivo**: http://localhost/auto-motores

### Pruebas Automatizadas

#### 1. Spider/Crawling
- [ ] Ejecutar spider automático en toda la aplicación
- [ ] Verificar que todas las páginas son descubiertas
- [ ] Revisar formularios encontrados

#### 2. Escaneo de Vulnerabilidades Activo
- [ ] SQL Injection en formularios de login
- [ ] XSS en campos de entrada de datos
- [ ] CSRF en formularios de modificación
- [ ] Directory Traversal en parámetros de archivo

#### 3. Escaneo Pasivo
- [ ] Headers de seguridad faltantes
- [ ] Información sensible en respuestas
- [ ] Cookies sin flags de seguridad

### Verificaciones Manuales

#### Autenticación y Autorización
- [ ] **Bypass de login**: Intentar acceso directo a páginas protegidas
- [ ] **Escalación de privilegios**: Mecánico accediendo a funciones de jefe
- [ ] **Session fixation**: Verificar regeneración de sesiones
- [ ] **Logout seguro**: Invalidación completa de sesión

#### Validación de Entrada
- [ ] **SQL Injection**: Probar en todos los formularios
  \`\`\`sql
  ' OR '1'='1' --
  '; DROP TABLE mecanicos; --
  \`\`\`
- [ ] **XSS**: Probar scripts maliciosos
  \`\`\`html
  <script>alert('XSS')</script>
  <img src=x onerror=alert('XSS')>
  \`\`\`
- [ ] **File Upload**: Si existe, probar archivos maliciosos

#### Configuración del Servidor
- [ ] **Información del servidor**: Headers que revelan versiones
- [ ] **Archivos sensibles**: .env, config.php, backups
- [ ] **Directorios listables**: Verificar que no se listen directorios

### Comandos OWASP ZAP CLI

\`\`\`bash
# Escaneo básico
zap-cli quick-scan --self-contained http://localhost/auto-motores

# Escaneo completo con spider
zap-cli start
zap-cli spider http://localhost/auto-motores
zap-cli active-scan http://localhost/auto-motores
zap-cli report -o security_report.html -f html
\`\`\`

### Vulnerabilidades Comunes en PHP

#### 1. SQL Injection
**Ubicaciones a revisar:**
- `validar_login.php` - Consultas de autenticación
- Todos los archivos con `$_GET` y `$_POST`
- Formularios de búsqueda y filtros

#### 2. XSS (Cross-Site Scripting)
**Ubicaciones a revisar:**
- Campos de entrada de datos
- Parámetros URL mostrados en pantalla
- Mensajes de error

#### 3. CSRF (Cross-Site Request Forgery)
**Ubicaciones a revisar:**
- Formularios de modificación de datos
- Acciones de eliminación
- Cambios de configuración

### Recomendaciones de Seguridad

#### Inmediatas (Alta Prioridad)
- [ ] Implementar tokens CSRF en todos los formularios
- [ ] Validar y sanitizar todas las entradas
- [ ] Usar prepared statements en todas las consultas SQL
- [ ] Implementar headers de seguridad

#### Mediano Plazo (Media Prioridad)
- [ ] Implementar rate limiting en login
- [ ] Agregar logging de seguridad
- [ ] Implementar 2FA para administradores
- [ ] Cifrar datos sensibles en base de datos

#### Largo Plazo (Baja Prioridad)
- [ ] Implementar WAF (Web Application Firewall)
- [ ] Auditorías de seguridad regulares
- [ ] Penetration testing profesional
- [ ] Certificación SSL/TLS

### Herramientas Adicionales

1. **Nikto**: Escáner de vulnerabilidades web
   \`\`\`bash
   nikto -h http://localhost/auto-motores
   \`\`\`

2. **SQLMap**: Detección automática de SQL injection
   \`\`\`bash
   sqlmap -u "http://localhost/auto-motores/login.php" --forms
   \`\`\`

3. **Burp Suite Community**: Proxy de interceptación
   - Configurar proxy en navegador
   - Interceptar y modificar requests
   - Analizar respuestas

### Reporte de Resultados

Crear un documento con:
- [ ] Vulnerabilidades encontradas (criticidad)
- [ ] Evidencia (screenshots, logs)
- [ ] Impacto potencial
- [ ] Recomendaciones de corrección
- [ ] Timeline de implementación
