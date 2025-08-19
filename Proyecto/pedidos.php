<?php
// pedidos.php
// Gestión de pedidos de repuestos - Solo para jefe de taller

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'jefe_taller') {
    header('Location: login.php?usuario=jefe');
    exit;
}
$rol = $_SESSION['rol'];
include 'conexion.php';

$listaRepuestos = $conn->query('SELECT id, nombre FROM repuestos ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);

// Crear o editar pedido (solo jefe)
if (isset($_POST['guardar'])) {
    $id          = $_POST['id'] ?? '';
    $repuestoId  = $_POST['repuesto_id'] ?? '';
    $cantidad    = (int)($_POST['cantidad'] ?? 0);
    $fecha       = $_POST['fecha'] ?? '';
    $estado      = $_POST['estado'] ?? 'pendiente';
    if ($id) {
        $stmt = $conn->prepare('UPDATE pedidos SET repuesto_id=:rep, cantidad=:cant, fecha=:fecha, estado=:estado WHERE id=:id');
        $stmt->execute([
            ':rep'   => $repuestoId,
            ':cant'  => $cantidad,
            ':fecha' => $fecha,
            ':estado'=> $estado,
            ':id'    => $id,
        ]);
    } else {
        $stmt = $conn->prepare('INSERT INTO pedidos (repuesto_id, cantidad, fecha, estado) VALUES (:rep,:cant,:fecha,:estado)');
        $stmt->execute([
            ':rep'   => $repuestoId,
            ':cant'  => $cantidad,
            ':fecha' => $fecha,
            ':estado'=> $estado,
        ]);
    }
    header('Location: pedidos.php');
    exit;
}

// Eliminar pedido (solo jefe)
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $stmt = $conn->prepare('DELETE FROM pedidos WHERE id=:id');
    $stmt->execute([':id' => $id]);
    header('Location: pedidos.php');
    exit;
}

// Obtener pedido a editar (solo jefe)
$editar = null;
if (isset($_GET['editar'])) {
    $idEdit = (int)$_GET['editar'];
    $stmt = $conn->prepare('SELECT * FROM pedidos WHERE id=:id');
    $stmt->execute([':id' => $idEdit]);
    $editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Construir consulta para listar pedidos con paginación
$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;

// Consulta para contar total de registros (para paginación)
$sqlCount = 'SELECT COUNT(*) FROM pedidos p LEFT JOIN repuestos r ON r.id = p.repuesto_id';
$stmtCount = $conn->prepare($sqlCount);
$stmtCount->execute();
$totalRegistros = (int)$stmtCount->fetchColumn();
$totalPaginas   = ($totalRegistros > 0) ? (int)ceil($totalRegistros / $limit) : 1;
if ($page > $totalPaginas) {
    $page = $totalPaginas;
}
$offset = ($page - 1) * $limit;

// Consulta principal con paginación
$sql = 'SELECT p.*, r.nombre AS repuesto FROM pedidos p LEFT JOIN repuestos r ON r.id = p.repuesto_id ORDER BY p.fecha DESC LIMIT :limit OFFSET :offset';
$stmt = $conn->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedidos</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        /* ... existing styles ... */
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
        .form-container {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .form-container h2 {
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 20px;
        }
        .form-container label {
            display: block;
            margin-top: 12px;
            font-size: 14px;
        }
        .form-container input[type="text"],
        .form-container input[type="date"],
        .form-container input[type="number"],
        .form-container select {
            width: 100%;
            padding: 8px;
            margin-top: 4px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }
        .form-container button,
        .form-container a.btn {
            margin-top: 16px;
            padding: 8px 14px;
            font-size: 14px;
            border-radius: 6px;
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
        <a href="jefe_taller.php" class="btn-back">← Volver</a>
    </div>
    <div class="header-right">
        <a href="cerrar_sesion.php" class="btn-logout">Cerrar sesión</a>
        <span class="user-name"><?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
        <img src="img/avatar.png" alt="Avatar" class="user-logo">
    </div>
</header>

<div class="container">
    <h2 class="page-title">Pedidos</h2>
    <?php if (isset($_GET['nuevo']) || $editar): ?>
    <div class="form-container">
        <h2><?php echo $editar ? 'Editar pedido' : 'Nuevo pedido'; ?></h2>
        <form method="post" action="pedidos.php">
            <input type="hidden" name="id" value="<?php echo $editar['id'] ?? ''; ?>">
            <label>Repuesto
                <select name="repuesto_id" required>
                    <?php foreach ($listaRepuestos as $r): ?>
                        <option value="<?php echo $r['id']; ?>" <?php echo (isset($editar['repuesto_id']) && $editar['repuesto_id'] == $r['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($r['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Cantidad
                <input type="number" name="cantidad" min="1" value="<?php echo htmlspecialchars($editar['cantidad'] ?? 1); ?>" required>
            </label>
            <label>Fecha
                <input type="date" name="fecha" value="<?php echo htmlspecialchars($editar['fecha'] ?? date('Y-m-d')); ?>" required>
            </label>
            <label>Estado
                <select name="estado">
                    <option value="pendiente" <?php echo (isset($editar['estado']) && $editar['estado'] == 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="entregado" <?php echo (isset($editar['estado']) && $editar['estado'] == 'entregado') ? 'selected' : ''; ?>>Entregado</option>
                </select>
            </label>
            <button type="submit" name="guardar" class="btn">Guardar</button>
            <a href="pedidos.php" class="btn" style="background:#6c757d;">Cancelar</a>
        </form>
    </div>
    <?php endif; ?>

    <div style="display:flex; justify-content:flex-end; margin-bottom:10px;">
        <a href="pedidos.php?nuevo=1" class="btn-small" style="background:#000; color:#fff;">Nuevo pedido</a>
    </div>

    <div class="table-container">
        <table>
            <thead>
            <tr>
                <!-- Removed Proveedor column -->
                <th>Repuesto</th>
                <th>Cantidad</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($pedidos as $p): ?>
            <tr>
                <!-- Removed proveedor data -->
                <td><?php echo htmlspecialchars($p['repuesto']); ?></td>
                <td><?php echo htmlspecialchars($p['cantidad']); ?></td>
                <td><?php echo htmlspecialchars($p['fecha']); ?></td>
                <td><?php echo htmlspecialchars($p['estado']); ?></td>
                <td>
                    <a href="pedidos.php?editar=<?php echo $p['id']; ?>" class="btn-small" style="background:#007bff;">Editar</a>
                    <a href="pedidos.php?eliminar=<?php echo $p['id']; ?>" class="btn-small" style="background:#dc3545;" onclick="return confirm('¿Desea eliminar este pedido?');">Eliminar</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPaginas > 1): ?>
    <nav class="pagination" style="margin-top: 15px; display:flex; justify-content:center; gap:6px;">
        <?php
        $baseParams = $_GET;
        unset($baseParams['page'], $baseParams['nuevo'], $baseParams['editar'], $baseParams['eliminar']);
        for ($i = 1; $i <= $totalPaginas; $i++):
            $baseParams['page'] = $i;
            $url = 'pedidos.php?' . http_build_query($baseParams);
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
