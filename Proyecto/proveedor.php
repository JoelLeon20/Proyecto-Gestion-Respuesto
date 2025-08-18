<?php
session_start();
// Verificamos que el usuario tenga la sesión iniciada y que sea proveedor.
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'proveedor') {
    header('Location: login.php?usuario=proveedor&intended_page=proveedor.php');
    exit;
}
$nombreUsuario = $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel Proveedor</title>
  <!-- La hoja de estilos global -->
  <link rel="stylesheet" href="estilos.css">
  <style>
    /* Adaptaciones para el dashboard */
    header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #fff;
      padding: 10px 20px;
      border-bottom: 5px solid #000;
    }
    .header-left { display: flex; align-items: center; }
    .header-left img { height: 50px; margin-right: 15px; }
    .header-left h1 { font-family: 'Impact', sans-serif; font-size: 28px; }

    .header-right {
      display: flex;
      align-items: center;
      /* Changed back to row layout to keep original horizontal arrangement */
    }
    .header-right .btn-logout {
      background: #000;
      color: #fff;
      padding: 8px 12px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      margin-right: 15px;
      font-size: 14px;
      text-decoration: none;
    }
    .header-right span {
      margin-right: 10px;
      font-weight: bold;
    }
    /* Added avatar container to position role indicator below avatar specifically */
    .header-right .avatar-container {
      position: relative;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .header-right img.avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
    }
    .header-right .role-indicator {
      /* Made role indicator smaller and positioned below avatar */
      background: #007bff;
      color: white;
      padding: 2px 8px;
      border-radius: 3px;
      font-size: 10px;
      font-weight: bold;
      text-align: center;
      margin-top: 2px;
      min-width: 60px;
    }

    main.contenedor {
      background: #d9d9d9;
      padding: 40px 20px;
      min-height: calc(100vh - 75px);
      /* Better centering and positioning higher up with flexbox */
      display: flex;
      justify-content: center;
      align-items: flex-start;
      padding-top: 60px;
    }
    .dashboard {
      /* Making cards larger and perfectly centered */
      display: flex;
      justify-content: center;
      gap: 40px;
      max-width: 1000px;
      margin: 0 auto;
      width: 100%;
      flex-wrap: wrap;
    }
    .card {
      background: #fff;
      border: 1px solid #ccc;
      border-radius: 12px;
      /* Increased padding and size for larger cards */
      padding: 30px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      transition: transform 0.2s, box-shadow 0.2s;
      cursor: pointer;
      /* Fixed width for consistent larger size */
      width: 320px;
      min-height: 400px;
    }
    .card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    .card h3 {
      margin-bottom: 15px;
      font-size: 20px;
    }
    .card img {
      width: 100%;
      /* Increased image height for larger cards */
      height: 160px;
      object-fit: cover;
      border-radius: 8px;
      margin-bottom: 15px;
    }
    .card ul {
      list-style: none;
      padding-left: 0;
    }
    .card ul li {
      font-size: 14px;
      margin-bottom: 6px;
      position: relative;
      padding-left: 14px;
    }
    .card ul li:before {
      content: '•';
      position: absolute;
      left: 0;
      color: #333;
    }
  </style>
</head>
<body>

  <header>
    <div class="header-left">
      <img src="img/logo_auto_motores.png" alt="Logo Auto Motores">
      <h1>AUTO MOTORES</h1>
    </div>
    <div class="header-right">
      <!-- Kept original horizontal layout for logout button and username -->
      <a href="cerrar_sesion.php" class="btn-logout">Cerrar sesión</a>
      <span><?php echo ucfirst($nombreUsuario); ?></span>
      <!-- Wrapped avatar and role indicator in container for vertical stacking -->
      <div class="avatar-container">
        <img src="img/avatar.png" alt="Avatar" class="avatar">
        <div class="role-indicator">Proveedor</div>
      </div>
    </div>
  </header>

  <main class="contenedor">
    <div class="dashboard">

      <!-- Updated supplier-specific cards for order management -->
      <div class="card" onclick="location.href='solicitudes.php'">
        <h3>Pedidos Pendientes</h3>
        <!-- Updated image to use the planner/calendar image -->
        <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/image-70gq6IR4hhrf4sCO5Zqr3amoxovYCc.png" alt="Pedidos Pendientes">
        <ul>
          <li>Ver pedidos de repuestos solicitados.</li>
          <li>Revisar cantidad y fecha de solicitud.</li>
          <li>Verificar estado de pedidos.</li>
          <li>Consultar observaciones del pedido.</li>
        </ul>
      </div>

      <div class="card" onclick="location.href='historial_pedidos.php'">
        <h3>Historial de Pedidos</h3>
        <!-- Updated image to use the hands writing image -->
        <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/image-ALn2ZwEpxbyJUqPiGk6j0DlfVTvZ2U.png" alt="Historial de Pedidos">
        <ul>
          <li>Ver pedidos entregados anteriormente.</li>
          <li>Consultar fechas de entrega.</li>
          <li>Revisar repuestos entregados.</li>
          <li>Estado final de pedidos completados.</li>
        </ul>
      </div>

    </div>
  </main>

</body>
</html>
