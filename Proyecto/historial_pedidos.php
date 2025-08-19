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

$filtroMecanico = isset($_GET['mecanico']) ? trim($_GET['mecanico']) : '';
$filtroFechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$filtroFechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

// Get proveedor ID for current user
$nombreUsuario = $_SESSION['usuario'];
$proveedorId = null;

// Simple mapping for existing users
if ($nombreUsuario === 'michael') {
    $proveedorId = 1; // Michael Olvera Repuestos
} else {
    // Try to find by razon_social containing the username
    $stmtProv = $conn->prepare('SELECT id FROM proveedores WHERE razon_social LIKE :nombre LIMIT 1');
    $stmtProv->execute([':nombre' => '%' . $nombreUsuario . '%']);
    $proveedor = $stmtProv->fetch(PDO::FETCH_ASSOC);
    if ($proveedor) {
        $proveedorId = $proveedor['id'];
    }
}

$whereConditions = [];
$params = [];

if ($proveedorId) {
    $whereConditions[] = 'he.proveedor_id = :proveedor_id';
    $params[':proveedor_id'] = $proveedorId;
}

if (!empty($filtroMecanico)) {
    $whereConditions[] = 'm.nombre LIKE :mecanico';
    $params[':mecanico'] = '%' . $filtroMecanico . '%';
}

if (!empty($filtroFechaInicio)) {
    $whereConditions[] = 'DATE(he.fecha_entrega) >= :fecha_inicio';
    $params[':fecha_inicio'] = $filtroFechaInicio;
}

if (!empty($filtroFechaFin)) {
    $whereConditions[] = 'DATE(he.fecha_entrega) <= :fecha_fin';
    $params[':fecha_fin'] = $filtroFechaFin;
}

$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
}

// Count total records for pagination using historial_entregas
$sqlCount = 'SELECT COUNT(*) FROM historial_entregas he 
             LEFT JOIN mecanicos m ON m.id = he.mecanico_id' . $whereClause;
$stmtCount = $conn->prepare($sqlCount);
foreach ($params as $k => $v) {
    $stmtCount->bindValue($k, $v);
}
$stmtCount->execute();
$totalRegistros = (int)$stmtCount->fetchColumn();

// Debug information - check both tables
$debugSql = 'SELECT COUNT(*) as total, estado FROM solicitudes GROUP BY estado';
$debugStmt = $conn->prepare($debugSql);
$debugStmt->execute();
$debugResults = $debugStmt->fetchAll(PDO::FETCH_ASSOC);

// Check historial_entregas table
$historialSql = 'SELECT COUNT(*) as total FROM historial_entregas';
if ($proveedorId) {
    $historialSql .= ' WHERE proveedor_id = :proveedor_id';
}
$historialStmt = $conn->prepare($historialSql);
if ($proveedorId) {
    $historialStmt->bindValue(':proveedor_id', $proveedorId);
}
$historialStmt->execute();
$historialCount = (int)$historialStmt->fetchColumn();

error_log("Debug solicitudes count by status: " . print_r($debugResults, true));
error_log("Total delivered in historial_entregas: " . $historialCount);
error_log("Proveedor ID: " . $proveedorId);

$totalPaginas = ($totalRegistros > 0) ? (int)ceil($totalRegistros / $limit) : 1;
if ($page > $totalPaginas) {
    $page = $totalPaginas;
}
$offset = ($page - 1) * $limit;

$sql = 'SELECT he.*, m.nombre AS mecanico, r.nombre AS repuesto, r.descripcion AS repuesto_descripcion, p.razon_social AS proveedor
        FROM historial_entregas he 
        LEFT JOIN mecanicos m ON m.id = he.mecanico_id
        LEFT JOIN repuestos r ON r.id = he.repuesto_id
        LEFT JOIN proveedores p ON p.id = he.proveedor_id' . $whereClause . 
        ' ORDER BY he.fecha_entrega DESC LIMIT :limit OFFSET :offset';
$stmt = $conn->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
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
        /* Added filter form styles */
        .filter-container {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 150px;
        }
        .filter-group label {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 14px;
        }
        .filter-group input, .filter-group select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn-filter {
            background: #000;
            color: #fff;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            height: fit-content;
        }
        .btn-clear {
            background: #6c757d;
            color: #fff;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            height: fit-content;
        }
        .btn-filter:hover, .btn-clear:hover {
            opacity: 0.9;
        }
        /* Added print preview modal styles */
        #print-preview-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.4);
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }
        #print-preview-content {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            max-width: 90%;
            max-height: 90%;
            overflow: auto;
        }
        .selected-row {
            background-color: #d0e8ff !important;
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

    <!-- Added filter form -->
    <div class="filter-container">
        <form method="GET" action="historial_pedidos.php" class="filter-form">
            <div class="filter-group">
                <label for="mecanico">Mecánico:</label>
                <input type="text" id="mecanico" name="mecanico" 
                       value="<?php echo htmlspecialchars($filtroMecanico); ?>" 
                       placeholder="Nombre del mecánico">
            </div>
            
            <div class="filter-group">
                <label for="fecha_inicio">Fecha Inicio:</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" 
                       value="<?php echo htmlspecialchars($filtroFechaInicio); ?>">
            </div>
            
            <div class="filter-group">
                <label for="fecha_fin">Fecha Fin:</label>
                <input type="date" id="fecha_fin" name="fecha_fin" 
                       value="<?php echo htmlspecialchars($filtroFechaFin); ?>">
            </div>
            
            <button type="submit" class="btn-filter">Filtrar</button>
            <a href="historial_pedidos.php" class="btn-clear">Limpiar</a>
        </form>
    </div>

    <!-- Added print button above the table -->
    <div style="display:flex; justify-content:flex-end; gap:8px; margin-bottom:10px;">
        <a href="#" class="btn-small" style="background:#6c757d; color:#fff; padding:4px 8px; font-size:12px;" onclick="return mostrarVistaPreviaImpresion();">Imprimir</a>
    </div>

    <div class="table-container">
        <?php if (count($solicitudes) > 0): ?>
        <table>
            <thead>
            <tr>
                <!-- Updated columns to match historial_entregas structure -->
                <th>ID Solicitud</th>
                <th>Mecánico</th>
                <th>Repuesto</th>
                <th>Cantidad</th>
                <th>Fecha Solicitud</th>
                <th>Fecha Entrega</th>
                <th>Entregado Por</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($solicitudes as $s): ?>
            <tr>
                <td>#<?php echo str_pad($s['solicitud_id'], 4, '0', STR_PAD_LEFT); ?></td>
                <td><?php echo htmlspecialchars($s['mecanico']); ?></td>
                <td>
                    <strong><?php echo htmlspecialchars($s['repuesto']); ?></strong>
                    <?php if ($s['repuesto_descripcion']): ?>
                        <br><small style="color:#666;"><?php echo htmlspecialchars($s['repuesto_descripcion']); ?></small>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($s['cantidad']); ?></td>
                <td><?php echo htmlspecialchars($s['fecha_solicitud']); ?></td>
                <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($s['fecha_entrega']))); ?></td>
                <td><?php echo htmlspecialchars($s['entregado_por']); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <img src="img/avatar.png" alt="Sin pedidos">
            <!-- Updated empty state message for filtered results -->
            <?php if (!empty($filtroMecanico) || !empty($filtroFechaInicio) || !empty($filtroFechaFin)): ?>
                <h3>No se encontraron pedidos con los filtros aplicados</h3>
                <p>Intenta ajustar los criterios de búsqueda o <a href="historial_pedidos.php">ver todos los pedidos</a>.</p>
            <?php else: ?>
                <h3>No hay pedidos entregados</h3>
                <p>Aún no has entregado ningún pedido. Los pedidos completados aparecerán aquí.</p>
            <?php endif; ?>
            
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: left;">
                <h4 style="margin-top: 0;">Información del sistema:</h4>
                
                <p><strong>Estados de solicitudes en la base de datos:</strong></p>
                <?php if (!empty($debugResults)): ?>
                    <ul style="text-align: left; display: inline-block;">
                        <?php foreach ($debugResults as $debug): ?>
                            <li><?php echo ucfirst($debug['estado']); ?>: <?php echo $debug['total']; ?> solicitudes</li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <p><strong>Entregas registradas en historial:</strong> <?php echo $historialCount; ?></p>
                <?php if ($proveedorId): ?>
                    <p><strong>Tu ID de proveedor:</strong> <?php echo $proveedorId; ?></p>
                <?php else: ?>
                    <p style="color: #dc3545;"><strong>⚠️ No se pudo identificar tu ID de proveedor.</strong></p>
                    <p>Usuario actual: <?php echo htmlspecialchars($nombreUsuario); ?></p>
                <?php endif; ?>
                
                <?php if ($historialCount == 0): ?>
                    <p><strong>Para que aparezcan pedidos aquí, debes:</strong></p>
                    <ol style="text-align: left; display: inline-block;">
                        <li>Ir a <a href="solicitudes.php">Solicitudes de Repuesto</a></li>
                        <li>Buscar solicitudes con estado "Aprobada"</li>
                        <li>Hacer clic en "Marcar Entregado"</li>
                    </ol>
                    
                    <p><strong>Nota:</strong> Asegúrate de haber creado la tabla historial_entregas en tu base de datos.</p>
                <?php endif; ?>
                
                <div style="margin-top: 15px;">
                    <a href="solicitudes.php" class="btn-small">
                        Ir a Solicitudes Pendientes
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Updated pagination to preserve filters -->
    <?php if ($totalPaginas > 1): ?>
    <nav class="pagination" style="margin-top: 15px; display:flex; justify-content:center; gap:6px;">
        <?php
        for ($i = 1; $i <= $totalPaginas; $i++):
            $urlParams = [
                'page' => $i,
                'mecanico' => $filtroMecanico,
                'fecha_inicio' => $filtroFechaInicio,
                'fecha_fin' => $filtroFechaFin
            ];
            $url = 'historial_pedidos.php?' . http_build_query(array_filter($urlParams));
            $isActive = ($i == $page);
        ?>
            <a href="<?php echo $url; ?>" class="page-link" style="padding:6px 10px; border-radius:4px; border:1px solid #000; background: <?php echo $isActive ? '#000' : '#fff'; ?>; color: <?php echo $isActive ? '#fff' : '#000'; ?>; text-decoration:none; font-size:14px;">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
</div>

<!-- Modal para vista previa de impresión -->
<div id="print-preview-modal">
    <div id="print-preview-content"></div>
</div>

<!-- Added print functionality JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var tbody = document.querySelector('table tbody');
    if (tbody) {
        tbody.addEventListener('click', function(e) {
            var row = e.target.closest('tr');
            if (!row) return;
            tbody.querySelectorAll('tr').forEach(function(r) { r.classList.remove('selected-row'); });
            row.classList.add('selected-row');
        });
    }
});

function mostrarVistaPreviaImpresion() {
    var modal = document.getElementById('print-preview-modal');
    var contentDiv = document.getElementById('print-preview-content');
    var selected = document.querySelector('table tbody tr.selected-row');
    var html = '';
    
    var currentDate = new Date().toLocaleDateString('es-ES');
    var currentTime = new Date().toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});
    
    html = `
    <div style="max-width: 800px; margin: 0 auto; font-family: Arial, sans-serif; background: white;">
        <!-- Header -->
        <div style="text-align: center; padding: 20px 0; border-bottom: 2px solid #000; margin-bottom: 20px;">
            <h1 style="font-size: 32px; font-weight: bold; margin: 0; color: #000;">Auto Motores</h1>
            <h2 style="font-size: 24px; margin: 10px 0 0 0; color: #333;">Historial de Pedidos Entregados</h2>
        </div>
        
        <!-- Date -->
        <div style="text-align: center; margin-bottom: 20px; font-size: 14px;">
            <strong>${currentDate}, ${currentTime}</strong>
        </div>`;
    
    if (selected) {
        // Single delivery selected
        var cells = selected.querySelectorAll('td');
        html += `
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="margin: 0 0 15px 0; color: #000; font-size: 18px;">Detalle de Entrega</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div><strong>ID Solicitud:</strong> ${cells[0].textContent}</div>
                <div><strong>Mecánico:</strong> ${cells[1].textContent}</div>
                <div><strong>Repuesto:</strong> ${cells[2].textContent.split('\\n')[0]}</div>
                <div><strong>Cantidad:</strong> ${cells[3].textContent}</div>
                <div><strong>Fecha Solicitud:</strong> ${cells[4].textContent}</div>
                <div><strong>Fecha Entrega:</strong> ${cells[5].textContent}</div>
                <div style="grid-column: 1 / -1;"><strong>Entregado Por:</strong> ${cells[6].textContent}</div>
            </div>
        </div>`;
    } else {
        // All deliveries
        var table = document.querySelector('table');
        if (table) {
            html += `
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h3 style="margin: 0 0 15px 0; color: #000; font-size: 18px;">Resumen de Entregas</h3>
                <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                    <thead>
                        <tr style="background: #000; color: #fff;">`;
            
            var ths = table.querySelectorAll('thead th');
            for (var i = 0; i < ths.length; i++) {
                html += `<th style="padding: 12px 8px; text-align: left; border: 1px solid #333;">${ths[i].textContent}</th>`;
            }
            
            html += `</tr></thead><tbody>`;
            
            var rows = table.querySelectorAll('tbody tr');
            rows.forEach(function(r, index) {
                var tds = r.querySelectorAll('td');
                var bgColor = index % 2 === 0 ? '#f8f9fa' : 'white';
                html += `<tr style="background: ${bgColor};">`;
                for (var i = 0; i < tds.length; i++) {
                    html += `<td style="padding: 10px 8px; border-bottom: 1px solid #ddd; font-size: 12px;">${tds[i].textContent}</td>`;
                }
                html += '</tr>';
            });
            
            html += `</tbody></table>
            </div>`;
        }
    }
    
    html += `
        <!-- Footer -->
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666; font-size: 12px;">
            <p>Auto Motores - Sistema de Gestión de Entregas</p>
            <p>Reporte generado el ${currentDate} a las ${currentTime}</p>
        </div>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <button onclick="window.print()" style="background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 6px; font-size: 16px; margin-right: 10px; cursor: pointer;">Imprimir</button>
            <button onclick="cerrarVistaPrevia()" style="background: #6c757d; color: white; padding: 12px 24px; border: none; border-radius: 6px; font-size: 16px; cursor: pointer;">Cerrar</button>
        </div>
    </div>`;
    
    contentDiv.innerHTML = html;
    modal.style.display = 'flex';
    return false;
}

function cerrarVistaPrevia() {
    var modal = document.getElementById('print-preview-modal');
    if (modal) modal.style.display = 'none';
}
</script>
</body>
</html>
