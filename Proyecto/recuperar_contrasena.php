<?php
// recuperar_contrasena.php
$usuario = $_GET['usuario'] ?? 'jefe';
$titulos = [
  'jefe'      => 'Jefe de Taller',
  'mecanico'  => 'Mecánico',
  'proveedor' => 'Proveedor',
];
$tituloRol = $titulos[$usuario] ?? $titulos['jefe'];

$mostrarConfirmacion = false;
$correoEnmascarado = '';
$error = '';

if ($_POST && isset($_POST['correo'])) {
    $correoIngresado = trim($_POST['correo']);
    
    // Basic email validation
    if (empty($correoIngresado)) {
        $error = 'Por favor ingrese su correo electrónico.';
    } elseif (!filter_var($correoIngresado, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor ingrese un correo electrónico válido.';
    } else {
        // Here you would normally check if the email exists in the database
        // For now, we'll just show the confirmation
        $mostrarConfirmacion = true;
        
        // Mask the email for security
        $mailParts = explode('@', $correoIngresado);
        $correoEnmascarado = substr($mailParts[0],0,1) . '*****@' . $mailParts[1];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Recuperar Contraseña - <?= $tituloRol ?></title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    html, body {
      width:100%; height:100%;
      font-family: Arial, sans-serif;
    }
    body {
      background-color: #d9d9d9;
      position: relative;
    }
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
      transition: background 0.3s;
    }
    .back:hover {
      background: #333;
    }

    .card-box {
      position: absolute;
      top: 50%; left: 10%;
      transform: translateY(-50%);
      background:#fff;
      padding: 40px 30px;
      width: 360px;
      border-radius:12px;
      box-shadow:0 8px 24px rgba(0,0,0,0.2);
    }
    .card-box img.logo {
      display:block;
      margin:0 auto 20px;
      width:100px;
    }
    .card-box h1 {
      text-align:center;
      font-size:24px;
      margin-bottom:20px;
      letter-spacing:1px;
    }
    
    /* Added styles for the form */
    .form-container {
      margin-bottom: 20px;
    }
    .form-container h2 {
      font-size: 18px;
      margin-bottom: 15px;
      color: #333;
    }
    .form-container label {
      display: block;
      margin-bottom: 6px;
      font-size: 14px;
      color: #333;
    }
    .form-container input[type="email"] {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 14px;
    }
    .form-container button {
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
    .form-container button:hover {
      background: #333;
    }
    .error-message {
      background: #fee;
      color: #c33;
      padding: 10px;
      border: 1px solid #fcc;
      border-radius: 6px;
      margin-bottom: 15px;
      text-align: center;
      font-size: 14px;
    }
    
    .rec-card {
      border:1px solid #ccc;
      border-radius:8px;
      padding:15px;
      font-size:14px;
      color:#333;
    }
    .rec-card strong {
      display:block;
      margin-bottom:8px;
    }
    .rec-card code {
      display:block;
      margin:10px 0;
      background:#f7f7f7;
      padding:6px;
      border-radius:4px;
      text-align:center;
    }
    .btn-regresar {
      display:block;
      margin:20px auto 0;
      padding:8px 16px;
      background:#000;
      color:#fff;
      text-decoration:none;
      border-radius:6px;
      text-align:center;
      width:120px;
      transition:background 0.3s;
    }
    .btn-regresar:hover {
      background:#333;
    }

    .recuperar-right {
      position: absolute;
      top: 0;
      right: 0;
      width: 60%;
      height: 100%;
      z-index: 0;
    }
    .recuperar-right img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .card-box {
      z-index: 1;
    }
  </style>
</head>
<body>
  <a href="login.php?usuario=<?= $usuario ?>" class="back">← Volver</a>

  <div class="recuperar-right">
    <img src="img/recuperar_bg.jpg" alt="Imagen recuperación">
  </div>

  <div class="card-box">
    <img src="img/logo_auto_motores.png" alt="Logo Auto Motores" class="logo">
    <h1>AUTO MOTORES</h1>
    
    <?php if ($mostrarConfirmacion): ?>
      <!-- Show confirmation message after successful form submission -->
      <div class="rec-card">
        <strong>Recuperar Contraseña</strong>
        Se ha enviado un correo electrónico a la dirección<br>
        <code><?= $correoEnmascarado ?></code>
        para el proceso de recuperación.
      </div>
    <?php else: ?>
      <!-- Show form to input email address -->
      <div class="form-container">
        <h2>Recuperar Contraseña</h2>
        <p style="font-size: 14px; color: #666; margin-bottom: 15px;">
          Ingrese su correo electrónico para recibir las instrucciones de recuperación.
        </p>
        
        <?php if ($error): ?>
          <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
          <label for="correo">Correo electrónico</label>
          <input type="email" id="correo" name="correo" required 
                 value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>"
                 placeholder="ejemplo@correo.com">
          <!-- Changed button text from "Enviar instrucciones" to just "Enviar" -->
          <button type="submit">Enviar</button>
        </form>
      </div>
    <?php endif; ?>
    
    <!-- Removed the "Regresar" button since there's already a back arrow at the top -->
  </div>
</body>
</html>
