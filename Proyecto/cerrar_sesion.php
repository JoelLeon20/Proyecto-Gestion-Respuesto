<?php
// cerrar_sesion.php
require_once 'auth_middleware.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Destruir sesión de forma segura
destruir_sesion();

// Redirigir al índice
header('Location: index.html');
exit;
?>
