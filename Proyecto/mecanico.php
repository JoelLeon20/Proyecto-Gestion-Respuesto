<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'mecanico') {
    header('Location: login.php?usuario=mecanico&intended_page=mecanico.php');
    exit;
}
$nombreUsuario = $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel Mecánico</title>
  <!-- Hoja de estilos -->
  <link rel="stylesheet" href="estilos.css">
  <style>
    /* Cabecera */
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
      border-radius: 6px;
      text-decoration: none;
      font-size: 14px;
      margin-right: 15px;
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

    /* Contenido */
    main.contenedor {
      background: #d9d9d9;
      /* Changed to match supplier page positioning with proper padding */
      padding: 40px 20px;
      min-height: calc(100vh - 75px);
      /* Added flexbox layout to match supplier page positioning */
      display: flex;
      justify-content: center;
      align-items: flex-start;
      padding-top: 60px;
    }
    .dashboard {
      /* Changed to flexbox layout and removed negative margin to match supplier page */
      display: flex;
      justify-content: center;
      gap: 20px;
      max-width: 1200px;
      margin: 0 auto;
      width: 100%;
      /* Removed flex-wrap to keep all cards in one row */
      flex-wrap: nowrap;
    }
    .card {
      background: #fff;
      border: 1px solid #ccc;
      border-radius: 12px;
      /* Reduced padding from 30px to 20px for smaller cards */
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      transition: transform 0.2s, box-shadow 0.2s;
      cursor: pointer;
      /* Reduced width from 320px to 280px to fit all 3 cards in one row */
      width: 280px;
      /* Reduced min-height from 400px to 350px for smaller cards */
      min-height: 350px;
    }
    .card h3 {
      margin-bottom: 15px;
      /* Reduced font size from 24px to 20px for smaller cards */
      font-size: 20px;
    }
    .card img {
      width: 100%;
      /* Reduced image height from 150px to 120px for smaller cards */
      height: 120px;
      object-fit: cover;
      border-radius: 8px;
      margin-bottom: 12px;
    }
    .card ul {
      list-style: none;
      padding-left: 0;
    }
    .card ul li {
      /* Reduced font size from 16px to 14px for smaller cards */
      font-size: 14px;
      margin-bottom: 8px;
      position: relative;
      padding-left: 14px;
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
      <span><?php echo htmlspecialchars($nombreUsuario); ?></span>
      <!-- Wrapped avatar and role indicator in container for vertical stacking -->
      <div class="avatar-container">
        <img src="img/avatar.png" alt="Avatar" class="avatar">
        <div class="role-indicator">Mecánico</div>
      </div>
    </div>
  </header>

  <main class="contenedor">
    <div class="dashboard">

      <div class="card" onclick="location.href='ordenes.php'">
        <h3>Órdenes de Trabajo</h3>
        <img src="img/ordenes_card.jpg" alt="Órdenes de Trabajo">
        <ul>
          <li>Detalle de órdenes de trabajo.</li>
          <li>Administrar órdenes de trabajo.</li>
        </ul>
      </div>

      <div class="card" onclick="location.href='repuestos.php'">
        <h3>Repuestos</h3>
        <img src="img/repuestos_card.jpg" alt="Repuestos">
        <ul>
          <li>Ver stock de los repuestos en bodega.</li>
        </ul>
      </div>

      <div class="card" onclick="location.href='solicitudes.php'">
        <h3>Solicitudes de Repuesto</h3>
        <img src="img/solicitudes_card.jpg" alt="Solicitudes de Repuesto">
        <ul>
          <li>Detalle de solicitudes de repuesto.</li>
          <li>Emitir solicitud de repuesto.</li>
          <li>Cancelar solicitud de repuesto.</li>
          <li>Tracking de solicitud de repuesto.</li>
        </ul>
      </div>

    </div>
  </main>

</body>
</html>
