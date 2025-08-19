<?php
// login.php
$usuario = $_GET['usuario'] ?? 'jefe';
$error = $_GET['error'] ?? '';
$intended_page = $_GET['intended_page'] ?? '';
$titulos = [
  'jefe'      => 'Inicio de Sesión Jefe de Taller',
  'mecanico'  => 'Inicio de Sesión Mecánico',
  'proveedor' => 'Inicio de Sesión Proveedor',
];
$titulo = $titulos[$usuario] ?? $titulos['jefe'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= $titulo ?></title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body, html {
      width:100%; height:100%;
      font-family: Arial, sans-serif;
    }
    body {
      background-color: #d9d9d9;
      position: relative;
    }
    /* Botón para volver al inicio */
    /*
     * Estilo para el enlace de volver en la pantalla de inicio de sesión.
     * Se inspira en el estilo utilizado en el resto de la aplicación para el botón de volver
     * (fondo negro con texto blanco y un borde redondeado). Esto hace que el botón
     * sea consistente con el resto de la interfaz y más visible sobre el fondo gris.
     */
    .back {
      position: absolute;
      top: 20px;
      left: 20px;
      text-decoration: none;
      background: #000;
      color: #fff;
      padding: 8px 14px;
      border-radius: 6px;
      font-size: 16px;
      display: inline-block;
      z-index: 10;
      transition: background 0.3s;
    }
    .back:hover {
      background: #333;
    }

    /* Contenedor general que divide la pantalla en dos columnas */
    .login-wrapper {
      display: flex;
      height: 100vh;
    }
    /* Columna izquierda con el formulario */
    .login-left {
      width: 40%;
      min-width: 320px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #d9d9d9;
    }
    .login-box {
      background: #fff;
      padding: 40px 30px;
      width: 100%;
      max-width: 400px;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    }
    .login-box img.logo {
      display: block;
      margin: 0 auto 20px;
      width: 110px;
    }
    .login-box h1 {
      text-align: center;
      font-size: 26px;
      margin-bottom: 25px;
      letter-spacing: 1px;
    }
    .login-box label {
      display: block;
      margin-bottom: 6px;
      font-size: 14px;
      color: #333;
    }
    .login-box input[type="text"],
    .login-box input[type="password"] {
      width: 100%;
      padding: 10px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 14px;
    }
    .login-box button {
      width: 100%;
      padding: 12px;
      background: #000;
      color: #fff;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s;
    }
    .login-box button:hover {
      background: #333;
    }
    .login-box .forgot {
      display: block;
      text-align: center;
      margin-top: 15px;
      font-size: 13px;
      color: #555;
      text-decoration: none;
    }
    .login-box .forgot:hover {
      text-decoration: underline;
    }
    .error-message {
      background: #fee;
      color: #c33;
      padding: 10px;
      border: 1px solid #fcc;
      border-radius: 6px;
      margin-bottom: 20px;
      text-align: center;
      font-size: 14px;
    }
    /* Columna derecha con la imagen de fondo */
    .login-right {
      flex: 1;
      /* Fallback background in case image does not load */
      background: #f7f7f7;
      position: relative;
    }
    /* Imagen dentro de la columna derecha: ocupa todo el espacio y mantiene proporciones */
    .login-right img {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    /* Adaptar el layout en dispositivos pequeños */
    @media (max-width: 768px) {
      .login-wrapper {
        flex-direction: column;
      }
      .login-left {
        width: 100%;
        min-width: 0;
        padding: 40px 20px;
      }
      .login-right {
        width: 100%;
        height: 40vh;
      }
    }
  </style>
</head>
<body>
  <a href="index.html" class="back">← Volver</a>

  <div class="login-wrapper">
    <div class="login-left">
      <div class="login-box">
        <!-- Updated logo path -->
        <img src="img/logo_auto_motores.png" alt="Logo Auto Motores" class="logo">
        <h1>AUTO MOTORES</h1>
        
        <?php if ($error == '1'): ?>
          <!-- show inline error message for invalid credentials -->
          <div class="error-message">
            Usuario o contraseña incorrectos.
          </div>
        <?php elseif ($error == '2'): ?>
          <!-- show inline error message for database errors -->
          <div class="error-message">
            Error en el sistema. Inténtelo de nuevo.
          </div>
        <?php elseif ($error == '3'): ?>
          <!-- Added error message for unauthorized access -->
          <div class="error-message">
            No tienes permisos para acceder a esa sección. Inicia sesión con el rol correcto.
          </div>
        <?php elseif ($error == '4'): ?>
          <!-- Added error message for wrong role in specific login form -->
          <div class="error-message">
            Las credenciales ingresadas no corresponden a un usuario de este tipo. Verifica que estés usando el formulario correcto.
          </div>
        <?php elseif ($error == '5'): ?>
          <!-- Added error message for session timeout -->
          <div class="error-message">
            Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente.
          </div>
        <?php elseif ($error == '6'): ?>
          <!-- Added error message for invalid session token -->
          <div class="error-message">
            Sesión inválida detectada. Por favor, inicia sesión nuevamente.
          </div>
        <?php endif; ?>
        
        <form action="validar_login.php" method="POST">
          <!-- Added hidden field to pass intended destination -->
          <?php if ($intended_page): ?>
            <input type="hidden" name="intended_page" value="<?= htmlspecialchars($intended_page) ?>">
          <?php endif; ?>
          
          <label for="usuario">Correo</label>
          <input type="text" id="usuario" name="usuario" required>

          <label for="contrasena">Contraseña</label>
          <input type="password" id="contrasena" name="contrasena" required>

          <button type="submit">Iniciar sesión</button>
        </form>
        <a href="recuperar_contrasena.php?usuario=<?= $usuario ?>" class="forgot">¿Has olvidado tu contraseña?</a>
      </div>
    </div>
    <div class="login-right">
      <!-- Use a dedicated image for the right column instead of reutilizing the mechanic card -->
      <img src="img/login_bg.jpg" alt="Imagen mecánico bienvenida" />
    </div>
  </div>
</body>
</html>
