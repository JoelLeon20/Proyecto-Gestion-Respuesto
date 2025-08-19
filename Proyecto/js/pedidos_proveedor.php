<?php
// pedidos_proveedor.php
// Gestión de pedidos para proveedores - Solo para proveedor

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'proveedor') {
    header('Location: login.php?usuario=proveedor');
    exit;
}
$rol = $_SESSION['rol'];
include 'conexion.php';

if (isset($_POST['marcar_entregado'])) {
    $id = (int)$_POST['pedido_id'];
    $observaciones = $_POST['observaciones'] ?? '';
    
    // Update order status to delivered
    $stmt = $conn->prepare('UPDATE pedidos SET estado = "entregado", observaciones = :obs WHERE id = :id AND proveedor_id = 1');
    $stmt->execute([
        ':obs' => $observaciones,
        ':id' => $id
    ]);
    
    header('Location: pedidos_proveedor.php?success=1');
    exit;
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;

$estadoFiltro = $_GET['estado'] ?? '';
$conditions = ['p.proveedor_id = 1']; // Only orders for this supplier
$params = [];

if ($estadoFiltro !== '') {
    $conditions[] = 'p.estado = :estado';
    $params[':estado'] = $estadoFiltro;
}

$whereClause = ' WHERE ' . implode(' AND ', $conditions);

// Count total records for pagination
$sqlCount = 'SELECT COUNT(*) FROM pedidos p' . $whereClause;
$stmtCount = $conn->prepare($sqlCount);
foreach ($params as $k => $v) {
    $stmtCount->bindValue($k, $v);
}
$stmtCount->execute();
$totalRegistros = (int)$stmtCount->fetchColumn();
$totalPaginas = ($totalRegistros > 0) ? (int)ceil($totalRegistros / $limit) : 1;
if ($page > $totalPaginas) {
    $page = $totalPaginas;
}
$offset = ($page - 1) * $limit;

$sql = 'SELECT p.*, r.nombre AS repuesto, r.descripcion AS repuesto_descripcion 
        FROM pedidos p 
        LEFT JOIN repuestos r ON r.id = p.repuesto_id' . $whereClause . 
        ' ORDER BY p.fecha DESC LIMIT :limit OFFSET :offset';
$stmt = $conn->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedidos - Proveedor</title>
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
        .btn {
            background: #000;
            color: #fff;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            border: none;
        }
        .btn-small {
            background: #000;
            color: #fff;
            padding: 6px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            cursor: pointer;
            border: none;
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
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.4);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-container {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 500px;
        }
        .modal-container h2 {
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 20px;
        }
        .modal-container label {
            display: block;
            margin-top: 12px;
            font-size: 14px;
        }
        .modal-container textarea {
            width: 100%;
            padding: 8px;
            margin-top: 4px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            resize: vertical;
            min-height: 80px;
        }
        .modal-container button,
        .modal-container a.btn {
            margin-top: 16px;
            padding: 8px 14px;
            font-size: 14px;
            border-radius: 6px;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pendiente {
            background: #fff3cd;
            color: #856404;
        }
        .status-entregado {
            background: #d4edda;
            color: #155724;
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
    <!-- Updated title for supplier view -->
    <h2 class="page-title">Gestión de Pedidos</h2>
    
    <?php if (isset($_GET['success'])): ?>
    <div class="success-message">
        ✓ Pedido marcado como entregado exitosamente.
    </div>
    <?php endif; ?>

    <!-- Filter controls for supplier -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
        <form method="get" style="display:flex; align-items:center; gap:8px;">
            <select name="estado" style="padding:8px 12px; border:1px solid #ccc; border-radius:6px; font-size:14px;">
                <option value="">-- Todos los estados --</option>
                <option value="pendiente" <?php echo ($estadoFiltro === 'pendiente') ? 'selected' : ''; ?>>Pendientes</option>
                <option value="entregado" <?php echo ($estadoFiltro === 'entregado') ? 'selected' : ''; ?>>Entregados</option>
            </select>
            <button type="submit" class="btn-small">Filtrar</button>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
            <tr>
                <!-- Supplier-specific columns -->
                <th>ID Pedido</th>
                <th>Repuesto</th>
                <th>Cantidad</th>
                <th>Fecha Solicitud</th>
                <th>Estado</th>
                <th>Observaciones</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($pedidos as $p): ?>
            <tr>
                <td>#<?php echo str_pad($p['id'], 4, '0', STR_PAD_LEFT); ?></td>
                <td>
                    <strong><?php echo htmlspecialchars($p['repuesto']); ?></strong>
                    <?php if ($p['repuesto_descripcion']): ?>
                        <br><small style="color:#666;"><?php echo htmlspecialchars($p['repuesto_descripcion']); ?></small>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($p['cantidad']); ?></td>
                <td><?php echo htmlspecialchars($p['fecha']); ?></td>
                <td>
                    <span class="status-badge status-<?php echo $p['estado']; ?>">
                        <?php echo ucfirst($p['estado']); ?>
                    </span>
                </td>
                <td><?php echo htmlspecialchars($p['observaciones'] ?? '—'); ?></td>
                <td>
                    <?php if ($p['estado'] === 'pendiente'): ?>
                        <a href="#" class="btn-small" style="background:#28a745;" onclick="mostrarModalEntrega(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['repuesto']); ?>')">Marcar Entregado</a>
                    <?php else: ?>
                        <span style="color:#666; font-size:12px;">Completado</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination for supplier orders -->
    <?php if ($totalPaginas > 1): ?>
    <nav class="pagination" style="margin-top: 15px; display:flex; justify-content:center; gap:6px;">
        <?php
        $baseParams = $_GET;
        unset($baseParams['page'], $baseParams['success']);
        for ($i = 1; $i <= $totalPaginas; $i++):
            $baseParams['page'] = $i;
            $url = 'pedidos_proveedor.php?' . http_build_query($baseParams);
            $isActive = ($i == $page);
        ?>
            <a href="<?php echo $url; ?>" class="page-link" style="padding:6px 10px; border-radius:4px; border:1px solid #000; background: <?php echo $isActive ? '#000' : '#fff'; ?>; color: <?php echo $isActive ? '#fff' : '#000'; ?>; text-decoration:none; font-size:14px;">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
</div>

<!-- Modal for marking order as delivered -->
<div id="modal-entrega" class="modal-overlay" style="display:none;">
    <div class="modal-container">
        <h2>Marcar Pedido como Entregado</h2>
        <form method="post" action="pedidos_proveedor.php">
            <input type="hidden" name="pedido_id" id="pedido-id">
            <p>¿Confirma que ha entregado el pedido <strong id="pedido-info"></strong>?</p>
            <label>Observaciones (opcional)
                <textarea name="observaciones" placeholder="Ingrese cualquier observación sobre la entrega..."></textarea>
            </label>
            <button type="submit" name="marcar_entregado" class="btn" style="background:#28a745;">Confirmar Entrega</button>
            <a href="#" class="btn" style="background:#6c757d;" onclick="cerrarModal()">Cancelar</a>
        </form>
    </div>
</div>

<script>
function mostrarModalEntrega(pedidoId, repuesto) {
    document.getElementById('pedido-id').value = pedidoId;
    document.getElementById('pedido-info').textContent = '#' + String(pedidoId).padStart(4, '0') + ' - ' + repuesto;
    document.getElementById('modal-entrega').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modal-entrega').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('modal-entrega').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModal();
    }
});
</script>
</body>
</html>
