<?php
// historial_pedidos.php
// Historial de pedidos entregados para proveedores

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'proveedor') {
    header('Location: login.php?usuario=proveedor');
    exit;
}
include 'conexion.php';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;

$whereClause = ' WHERE s.estado = "entregado"';

// Count total records for pagination
$sqlCount = 'SELECT COUNT(*) FROM solicitudes s' . $whereClause;
$stmtCount = $conn->prepare($sqlCount);
$stmtCount->execute();
$totalRegistros = (int)$stmtCount->fetchColumn();

$debugSql = 'SELECT COUNT(*) as total, estado FROM solicitudes GROUP BY estado';
$debugStmt = $conn->prepare($debugSql);
$debugStmt->execute();
$debugResults = $debugStmt->fetchAll(PDO::FETCH_ASSOC);

// Also check recent deliveries
$recentSql = 'SELECT s.*, m.nombre AS mecanico, r.nombre AS repuesto 
              FROM solicitudes s 
              LEFT JOIN mecanicos m ON m.id = s.mecanico_id
              LEFT JOIN repuestos r ON r.id = s.repuesto_id
              WHERE s.estado = "entregado" 
              ORDER BY s.fecha DESC LIMIT 5';
$recentStmt = $conn->prepare($recentSql);
$recentStmt->execute();
$recentDeliveries = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

error_log("Debug solicitudes count by status: " . print_r($debugResults, true));
error_log("Total delivered solicitudes found: " . $totalRegistros);

$totalPaginas = ($totalRegistros > 0) ? (int)ceil($totalRegistros / $limit) : 1;
if ($page > $totalPaginas) {
    $page = $totalPaginas;
}
$offset = ($page - 1) * $limit;

$sql = 'SELECT s.*, m.nombre AS mecanico, r.nombre AS repuesto, r.descripcion AS repuesto_descripcion 
        FROM solicitudes s 
        LEFT JOIN mecanicos m ON m.id = s.mecanico_id
        LEFT JOIN repuestos r ON r.id = s.repuesto_id' . $whereClause . 
        ' ORDER BY s.fecha DESC LIMIT :limit OFFSET :offset';
$stmt = $conn->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Pedidos - Proveedor</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        header {
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 10px 20px;
            border-bottom: 5px solid #000;
        }
        .header-left {
            display: flex;
            align-items: center;
        }
        .logo-title {
            display: flex;
            align-items: center;
        }
        .logo-title img {
            height: 40px;
            margin-right: 10px;
        }
        .logo-title h1 {
            font-family: 'Impact', sans-serif;
            font-size: 28px;
            margin: 0;
        }
        .btn-back {
            position: absolute;
            bottom: -40px;
            left: 20px;
            background: #000;
            color: #fff;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
        }
        .header-right {
            display: flex;
            align-items: center;
        }
        .btn-logout {
            background: #000;
            color: #fff;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            margin-top: 0;
            margin-left: 10px;
        }
        .container {
            padding: 20px;
            max-width: 1200px;
            margin: 40px auto 0 auto;
        }
        .page-title {
            font-size: 26px;
            font-weight: bold;
            text-align: center;
            margin: 0 0 20px 0;
        }
        .user-logo {
            height: 32px;
            width: 32px;
            border-radius: 50%;
            margin-left: 8px;
        }
        .user-name {
            margin-left: 10px;
            font-weight: bold;
        }
        .table-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow-x: auto;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }
        thead th {
            background: #000;
            color: #fff;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            border-right: 1px solid #fff;
        }
        tbody td {
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        tbody tr:hover {
            background: #f1f1f1;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            background: #d4edda;
            color: #155724;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        .empty-state img {
            width: 64px;
            height: 64px;
            opacity: 0.5;
            margin-bottom: 16px;
        }
        .btn-small {
            background: #007bff;
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
<header>
    <div class="header-left">
        <div class="logo-title">
            <img src="img/logo_auto_motores.png" alt="Logo">
            <h1>AUTO MOTORES</h1>
        </div>
        <!-- Back button goes to supplier dashboard -->
        <a href="proveedor.php" class="btn-back">← Volver</a>
    </div>
    <div class="header-right">
        <a href="cerrar_sesion.php" class="btn-logout">Cerrar sesión</a>
        <span class="user-name"><?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
        <img src="img/avatar.png" alt="Avatar" class="user-logo">
    </div>
</header>

<div class="container">
    <!-- Title for order history -->
    <h2 class="page-title">Historial de Pedidos Entregados</h2>

    <div class="table-container">
        <?php if (count($solicitudes) > 0): ?>
        <table>
            <thead>
            <tr>
                <!-- Updated columns to match solicitudes structure -->
                <th>ID Solicitud</th>
                <th>Mecánico</th>
                <th>Repuesto</th>
                <th>Cantidad</th>
                <th>Fecha Solicitud</th>
                <th>Estado Final</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($solicitudes as $s): ?>
            <tr>
                <td>#<?php echo str_pad($s['id'], 4, '0', STR_PAD_LEFT); ?></td>
                <td><?php echo htmlspecialchars($s['mecanico']); ?></td>
                <td>
                    <strong><?php echo htmlspecialchars($s['repuesto']); ?></strong>
                    <?php if ($s['repuesto_descripcion']): ?>
                        <br><small style="color:#666;"><?php echo htmlspecialchars($s['repuesto_descripcion']); ?></small>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($s['cantidad']); ?></td>
                <td><?php echo htmlspecialchars($s['fecha']); ?></td>
                <td>
                    <span class="status-badge">
                        Entregado
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <img src="img/avatar.png" alt="Sin pedidos">
            <h3>No hay pedidos entregados</h3>
            <p>Aún no has entregado ningún pedido. Los pedidos completados aparecerán aquí.</p>
            
            <!-- Added helpful debug information and quick action -->
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: left;">
                <h4 style="margin-top: 0;">Información del sistema:</h4>
                <?php if (!empty($debugResults)): ?>
                    <p><strong>Estados de solicitudes en la base de datos:</strong></p>
                    <ul style="text-align: left; display: inline-block;">
                        <?php foreach ($debugResults as $debug): ?>
                            <li><?php echo ucfirst($debug['estado']); ?>: <?php echo $debug['total']; ?> solicitudes</li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <?php if (!empty($recentDeliveries)): ?>
                    <p><strong>Entregas recientes encontradas:</strong></p>
                    <ul style="text-align: left; display: inline-block;">
                        <?php foreach ($recentDeliveries as $recent): ?>
                            <li>ID #<?php echo $recent['id']; ?> - <?php echo htmlspecialchars($recent['repuesto']); ?> (<?php echo $recent['fecha']; ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p><strong>No se encontraron entregas en la base de datos.</strong></p>
                    <p>Para que aparezcan pedidos aquí, debes:</p>
                    <ol style="text-align: left; display: inline-block;">
                        <li>Ir a <a href="solicitudes.php">Solicitudes de Repuesto</a></li>
                        <li>Buscar solicitudes con estado "Aprobada"</li>
                        <li>Hacer clic en "Marcar Entregado"</li>
                    </ol>
                <?php endif; ?>
                
                <div style="margin-top: 15px;">
                    <a href="solicitudes.php" class="btn-small">
                        Ir a Solicitudes Pendientes
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination for history -->
    <?php if ($totalPaginas > 1): ?>
    <nav class="pagination" style="margin-top: 15px; display:flex; justify-content:center; gap:6px;">
        <?php
        for ($i = 1; $i <= $totalPaginas; $i++):
            $url = 'historial_pedidos.php?page=' . $i;
            $isActive = ($i == $page);
        ?>
            <a href="<?php echo $url; ?>" class="page-link" style="padding:6px 10px; border-radius:4px; border:1px solid #000; background: <?php echo $isActive ? '#000' : '#fff'; ?>; color: <?php echo $isActive ? '#fff' : '#000'; ?>; text-decoration:none; font-size:14px;">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
</div>
</body>
</html>
