<?php
session_start();
// Para este ejemplo asumimos que tras el login guardas:
// $_SESSION['usuario'] = 'joel';  // o el nombre real
// $_SESSION['rol'] = 'jefe_taller';
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'jefe_taller') {
    header('Location: login.php?usuario=jefe');
    exit;
}
$nombreUsuario = $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel Jefe de Taller</title>
  <link rel="stylesheet" href="css/estilos.css">
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
    .header-right img.avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
    }

    main.contenedor {
      background: #f2f2f2;
      padding: 40px 20px;
      min-height: calc(100vh - 75px);
    }
    .dashboard {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      gap: 20px;
      max-width: 1200px;
      margin: 0 auto;
    }
    .card {
      background: #fff;
      border: 1px solid #ccc;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      transition: transform 0.2s, box-shadow 0.2s;
      cursor: pointer;
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
      <img src="../img/logo_auto_motores.png" alt="Logo Auto Motores">
      <h1>AUTO MOTORES</h1>
    </div>
    <div class="header-right">
      <!-- Aquí se ha añadido sólo la llamada a cerrar sesión -->
      <a href="../cerrar_sesion.php" class="btn-logout">Cerrar sesión</a>
      <span><?php echo ucfirst($nombreUsuario); ?></span>
      <img src="img/avatar.png" alt="Avatar" class="avatar">
    </div>
  </header>

  <main class="contenedor">
    <div class="dashboard">

      <div class="card" onclick="location.href='usuarios/jefe.php'">
        <h3>Mecánicos</h3>
        <img src="img/mecanico_card.jpg" alt="Mecánicos">
        <ul>
          <li>Detalle de mecánicos contratados.</li>
          <li>Agregar mecánicos nuevos al sistema.</li>
          <li>Eliminar mecánicos del sistema.</li>
          <li>Actualizar información de mecánicos.</li>
        </ul>
      </div>

      <div class="card" onclick="location.href='solicitudes.php'">
        <h3>Solicitudes de Repuesto</h3>
        <img src="img/solicitudes_card.jpg" alt="Solicitudes de Repuesto">
        <ul>
          <li>Detalle de solicitudes de repuesto.</li>
          <li>Aprobar/Rechazar solicitud de repuesto.</li>
          <li>Administrar solicitudes de repuesto.</li>
          <li>Tracking de solicitud de repuesto.</li>
        </ul>
      </div>

      <div class="card" onclick="location.href='repuestos.php'">
        <h3>Repuestos</h3>
        <img src="img/repuestos_card.jpg" alt="Repuestos">
        <ul>
          <li>Ver stock de repuestos.</li>
          <li>Agregar/Eliminar repuestos.</li>
          <li>Registrar entrada de repuesto solicitado.</li>
        </ul>
      </div>

      <div class="card" onclick="location.href='ordenes.php'">
        <h3>Órdenes de Trabajo</h3>
        <img src="img/ordenes_card.jpg" alt="Órdenes de Trabajo">
        <ul>
          <li>Detalle de órdenes de trabajo.</li>
          <li>Administrar órdenes de trabajo.</li>
        </ul>
      </div>

      <div class="card" onclick="location.href='reportes.php'">
        <h3>Reportes</h3>
        <img src="img/reportes_card.jpg" alt="Reportes">
        <ul>
          <li>Lectura de reportes financieros.</li>
          <li>Administrar reportes.</li>
        </ul>
      </div>

    </div>
  </main>

</body>
</html>
