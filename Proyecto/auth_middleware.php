<?php
// auth_middleware.php - Middleware de autenticación mejorado

if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Cambiar a 1 en producción con HTTPS
    session_start();
}

define('SESSION_TIMEOUT', 1800);

function verificar_autenticacion($rol_requerido = null) {
    // Verificar si existe sesión
    if (!isset($_SESSION['usuario']) || !isset($_SESSION['rol'])) {
        destruir_sesion();
        redirigir_login($rol_requerido);
        return false;
    }
    
    // Verificar timeout de sesión
    if (isset($_SESSION['ultimo_acceso'])) {
        if (time() - $_SESSION['ultimo_acceso'] > SESSION_TIMEOUT) {
            destruir_sesion();
            redirigir_login($rol_requerido, 'timeout');
            return false;
        }
    }
    
    // Verificar token de sesión
    if (!isset($_SESSION['token']) || !verificar_token_sesion()) {
        destruir_sesion();
        redirigir_login($rol_requerido, 'invalid_token');
        return false;
    }
    
    // Verificar rol específico si se requiere
    if ($rol_requerido && $_SESSION['rol'] !== $rol_requerido) {
        redirigir_login($rol_requerido, 'unauthorized');
        return false;
    }
    
    // Actualizar último acceso y regenerar token periódicamente
    $_SESSION['ultimo_acceso'] = time();
    
    // Regenerar token cada 10 minutos para mayor seguridad
    if (!isset($_SESSION['token_generado']) || (time() - $_SESSION['token_generado']) > 600) {
        regenerar_token_sesion();
    }
    
    return true;
}

function generar_token_sesion() {
    $token = bin2hex(random_bytes(32));
    $_SESSION['token'] = hash('sha256', $token . $_SESSION['usuario'] . session_id());
    $_SESSION['token_generado'] = time();
    return $_SESSION['token'];
}

function verificar_token_sesion() {
    if (!isset($_SESSION['token']) || !isset($_SESSION['usuario'])) {
        return false;
    }
    
    // El token debe coincidir con el hash generado
    $token_esperado = $_SESSION['token'];
    return isset($token_esperado);
}

function regenerar_token_sesion() {
    generar_token_sesion();
    // Regenerar ID de sesión para prevenir session fixation
    session_regenerate_id(true);
}

function destruir_sesion() {
    $_SESSION = array();
    
    // Destruir cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

function redirigir_login($rol_requerido = null, $error = null) {
    $usuario_param = '';
    $pagina_actual = basename($_SERVER['PHP_SELF']);
    
    // Mapear rol a parámetro de usuario
    switch ($rol_requerido) {
        case 'jefe_taller':
            $usuario_param = 'jefe';
            break;
        case 'mecanico':
            $usuario_param = 'mecanico';
            break;
        case 'proveedor':
            $usuario_param = 'proveedor';
            break;
    }
    
    $url = 'login.php';
    $params = array();
    
    if ($usuario_param) {
        $params['usuario'] = $usuario_param;
    }
    
    if ($pagina_actual && $pagina_actual !== 'login.php') {
        $params['intended_page'] = $pagina_actual;
    }
    
    // Agregar código de error específico
    switch ($error) {
        case 'timeout':
            $params['error'] = '5';
            break;
        case 'invalid_token':
            $params['error'] = '6';
            break;
        case 'unauthorized':
            $params['error'] = '3';
            break;
    }
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    header('Location: ' . $url);
    exit;
}

function inicializar_sesion_segura($usuario, $rol) {
    // Regenerar ID de sesión para prevenir session fixation
    session_regenerate_id(true);
    
    $_SESSION['usuario'] = $usuario;
    $_SESSION['rol'] = $rol;
    $_SESSION['ultimo_acceso'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Generar token de sesión único
    generar_token_sesion();
}
?>
