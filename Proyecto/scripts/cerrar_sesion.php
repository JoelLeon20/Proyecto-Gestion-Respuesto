<?php
// cerrar_sesion.php
// Este script destruye la sesión actual y redirige al inicio.
session_start();
// Vaciar todas las variables de sesión
$_SESSION = [];
// Destruir la sesión
session_destroy();
// Redirigir al índice
header('Location: index.html');
exit;
