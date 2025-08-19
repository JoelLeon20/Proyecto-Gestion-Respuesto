<?php
// validar_login.php
session_start();
include 'conexion.php';

// Obtenemos el usuario y la contraseña enviados desde el formulario
$usuario    = $_POST['usuario']    ?? '';
$contrasena = $_POST['contrasena'] ?? '';

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
        // Redireccionamos en función del rol.  Como los dashboards están en
        // la raíz (no en la carpeta "usuarios"), ajustamos la ruta.
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
        header("Location: login.php?error=1");
        exit;
    }
} catch (PDOException $e) {
    header("Location: login.php?error=2");
    exit;
}
?>
