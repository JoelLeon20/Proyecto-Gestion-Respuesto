<?php
// validar_login.php
session_start();
include 'conexion.php';

// Obtenemos el usuario y la contraseña enviados desde el formulario
$usuario    = $_POST['usuario']    ?? '';
$contrasena = $_POST['contrasena'] ?? '';
$intended_page = $_POST['intended_page'] ?? '';

$page_roles = [
    'jefe_taller.php' => 'jefe_taller',
    'mecanico.php' => 'mecanico', 
    'proveedor.php' => 'proveedor'
];

// Validamos usuario y contraseña utilizando PDO
try {
    $stmt = $conn->prepare("SELECT nombre, rol FROM usuarios WHERE usuario = :usuario AND contrasena = :contrasena");
    $stmt->execute([
        ':usuario'    => $usuario,
        ':contrasena' => $contrasena,
    ]);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($fila) {
        // Usuario encontrado
        $_SESSION['usuario'] = $fila['nombre'];
        $_SESSION['rol']     = $fila['rol'];
        
        if ($intended_page && isset($page_roles[$intended_page])) {
            $required_role = $page_roles[$intended_page];
            if ($fila['rol'] === $required_role) {
                // User has correct role for intended page
                header("Location: " . $intended_page);
                exit;
            } else {
                // User doesn't have permission for intended page
                $usuario_param = '';
                switch ($required_role) {
                    case 'jefe_taller': $usuario_param = 'jefe'; break;
                    case 'mecanico': $usuario_param = 'mecanico'; break;
                    case 'proveedor': $usuario_param = 'proveedor'; break;
                }
                header("Location: login.php?usuario=" . $usuario_param . "&intended_page=" . urlencode($intended_page) . "&error=3");
                exit;
            }
        }
        
        switch ($fila['rol']) {
            case 'jefe_taller':
                header("Location: jefe_taller.php");
                exit;
            case 'mecanico':
                header("Location: mecanico.php");
                exit;
            case 'proveedor':
                header("Location: proveedor.php");
                exit;
        }
    } else {
        $redirect_url = "login.php?error=1";
        if ($intended_page) {
            $redirect_url .= "&intended_page=" . urlencode($intended_page);
        }
        header("Location: " . $redirect_url);
        exit;
    }
} catch (PDOException $e) {
    $redirect_url = "login.php?error=2";
    if ($intended_page) {
        $redirect_url .= "&intended_page=" . urlencode($intended_page);
    }
    header("Location: " . $redirect_url);
    exit;
}
?>
