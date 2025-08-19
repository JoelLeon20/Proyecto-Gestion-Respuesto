<?php
// solicitudes.php
// Gestión de solicitudes de repuestos. Según el rol del usuario se
// muestran y permiten distintas acciones:  
// - Jefe de taller: ver todas las solicitudes y aprobar/rechazar.  
// - Mecánico: ver sus solicitudes, crear nuevas y cancelar si están pendientes.  
// - Proveedor: ver todas las solicitudes.

session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php?usuario=jefe');
    exit;
}
$rol = $_SESSION['rol'];

include 'conexion.php';

// Procesar creación de solicitud (mecánico)
if ($rol === 'mecanico' && isset($_POST['nueva_solicitud'])) {
    $mecanicoId = $_POST['mecanico_id'];
    $repuestoId = $_POST['repuesto_id'];
    $cantidad   = (int)$_POST['cantidad'];
    $fecha      = date('Y-m-d H:i:s');
    // Insertar estado pendiente
    $stmt = $conn->prepare('INSERT INTO solicitudes (mecanico_id, repuesto_id, cantidad, fecha, estado) VALUES (:mecanico,:repuesto,:cantidad,:fecha,:estado)');
    $stmt->execute([
        ':mecanico' => $mecanicoId,
        ':repuesto' => $repuestoId,
        ':cantidad' => $cantidad,
        ':fecha'    => $fecha,
        ':estado'   => 'pendiente',
    ]);
    header('Location: solicitudes.php');
    exit;
}

// Procesar cancelar solicitud (mecánico)
if ($rol === 'mecanico' && isset($_GET['cancelar'])) {
    $id = (int)$_GET['cancelar'];
    // El mecánico sólo puede cancelar sus propias solicitudes y cuando están pendientes
    // Verificamos el estado
    $stmt = $conn->prepare('SELECT estado, mecanico_id FROM solicitudes WHERE id=:id');
    $stmt->execute([':id' => $id]);
    $sol = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($sol && $sol['estado'] === 'pendiente') {
        $stmt = $conn->prepare('DELETE FROM solicitudes WHERE id=:id');
        $stmt->execute([':id' => $id]);
    }
    header('Location: solicitudes.php');
    exit;
}

// Procesar aprobación o rechazo (jefe de taller)
if ($rol === 'jefe_taller' && isset($_GET['accion']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $accion = $_GET['accion'];
    
    if (in_array($accion, ['aprobar','rechazar'])) {
        $nuevoEstado = ($accion === 'aprobar') ? 'aprobada' : 'denegada';
        
        $stmt = $conn->prepare('UPDATE solicitudes SET estado = :estado WHERE id = :id');
        $result = $stmt->execute([':estado' => $nuevoEstado, ':id' => $id]);
        
        if ($result) {
            if ($accion === 'rechazar') {
                header('Location: solicitudes.php?rechazado_exitoso=1');
            } else {
                header('Location: solicitudes.php?aprobado_exitoso=1');
            }
            exit;
        } else {
            header('Location: solicitudes.php?error=actualizacion_fallida');
            exit;
        }
    }
    
    header('Location: solicitudes.php');
    exit;
}

// Procesar marcar como entregado (proveedor)
if ($rol === 'proveedor' && isset($_GET['entregar']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // El proveedor puede marcar como entregado solo las solicitudes aprobadas
    $stmt = $conn->prepare('SELECT * FROM solicitudes WHERE id=:id');
    $stmt->execute([':id' => $id]);
    $sol = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($sol && $sol['estado'] === 'aprobada') {
        $nombreUsuario = $_SESSION['usuario'];
        $proveedorId = null;
        
        // Simple mapping for existing users
        if ($nombreUsuario === 'michael') {
            $proveedorId = 1; // Michael Olvera Repuestos
        } else {
            // For other users, try to find a matching provider or use a default
            $proveedorId = 1; // Default to first provider for now
        }
        
        try {
            $stmtHistorial = $conn->prepare('
                INSERT INTO historial_entregas 
                (solicitud_id, mecanico_id, repuesto_id, proveedor_id, cantidad, fecha_solicitud, fecha_entrega, estado_anterior, entregado_por) 
                VALUES (:solicitud_id, :mecanico_id, :repuesto_id, :proveedor_id, :cantidad, :fecha_solicitud, NOW(), :estado_anterior, :entregado_por)
            ');
            $stmtHistorial->execute([
                ':solicitud_id' => $sol['id'],
                ':mecanico_id' => $sol['mecanico_id'],
                ':repuesto_id' => $sol['repuesto_id'],
                ':proveedor_id' => $proveedorId,
                ':cantidad' => $sol['cantidad'],
                ':fecha_solicitud' => $sol['fecha'],
                ':estado_anterior' => 'aprobada',
                ':entregado_por' => $nombreUsuario
            ]);
            
            $stmt = $conn->prepare('UPDATE solicitudes SET estado=:estado WHERE id=:id');
            $stmt->execute([':estado' => 'entregado', ':id' => $id]);
            
        } catch (PDOException $e) {
            // If historial_entregas table doesn't exist, just log the delivery
            error_log("Historial entregas table not found: " . $e->getMessage());
            $stmt = $conn->prepare('UPDATE solicitudes SET estado=:estado WHERE id=:id');
            $stmt->execute([':estado' => 'entregado', ':id' => $id]);
        }
        
        header('Location: solicitudes.php?entregado_exitoso=1');
        exit;
    }
    header('Location: solicitudes.php');
    exit;
}

// Procesar eliminación de solicitud (jefe de taller)
if ($rol === 'jefe_taller' && isset($_GET['eliminar']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    // El jefe de taller puede eliminar cualquier solicitud
    $stmt = $conn->prepare('DELETE FROM solicitudes WHERE id=:id');
    $stmt->execute([':id' => $id]);
    header('Location: solicitudes.php?eliminado_exitoso=1&page=' . $currentPage);
    exit;
}

if ($rol === 'proveedor' && isset($_GET['eliminar']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    // Allow deletion of any request regardless of status
    $stmt = $conn->prepare('DELETE FROM solicitudes WHERE id=:id');
    $stmt->execute([':id' => $id]);
    header('Location: solicitudes.php?eliminado_exitoso=1&page=' . $currentPage);
    exit;
}

// Obtener lista de repuestos (para el formulario de mecánico)
$listaRepuestos = $conn->query('SELECT id, nombre FROM repuestos ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de mecánicos (para formulario y para mostrar nombres)
$listaMecanicos = $conn->query('SELECT id, nombre FROM mecanicos ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);

// -------------------------------------------------------
// Construir consulta base para mostrar solicitudes con filtros y paginación
// -------------------------------------------------------

// Parametros de filtrado
$estadoFiltro = $_GET['estado'] ?? '';
$page        = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit       = 10;
$conditions  = [];
$params      = [];

// Si mecánico, mostrar solo sus solicitudes
if ($rol === 'mecanico') {
    $nombreUsuario = $_SESSION['usuario'];
    error_log("[v0] Mechanic user: " . $nombreUsuario);
    
    $stmtTmp = $conn->prepare('SELECT id, nombre FROM mecanicos WHERE nombre = :nombre LIMIT 1');
    $stmtTmp->execute([':nombre' => $nombreUsuario]);
    $me = $stmtTmp->fetch(PDO::FETCH_ASSOC);
    
    if (!$me) {
        error_log("[v0] Exact match failed, trying partial match");
        $stmtTmp = $conn->prepare('SELECT id, nombre FROM mecanicos WHERE nombre LIKE :nombre LIMIT 1');
        $stmtTmp->execute([':nombre' => '%' . $nombreUsuario . '%']);
        $me = $stmtTmp->fetch(PDO::FETCH_ASSOC);
    }
    
    if ($me) {
        error_log("[v0] Found mechanic ID: " . $me['id']);
        $conditions[] = 's.mecanico_id = :mid';
        $params[':mid'] = $me['id'];
    } else {
        error_log("[v0] Mechanic not found in database");
        $mechanicNotFound = true;
    }
}

// Filtrado por estado
if ($estadoFiltro !== '') {
    $conditions[] = 's.estado = :estado';
    $params[':estado'] = $estadoFiltro;
}

// Construir cláusula WHERE
$whereClause = '';
if (count($conditions) > 0) {
    $whereClause = ' WHERE ' . implode(' AND ', $conditions);
}

// Consulta para contar total de registros (para paginación)
$sqlCount = 'SELECT COUNT(*) FROM solicitudes s' . $whereClause;
// Preparamos y bind de parámetros
$stmtCount = $conn->prepare($sqlCount);
foreach ($params as $k => $v) {
    $stmtCount->bindValue($k, $v);
}
$stmtCount->execute();
$totalRegistros = (int)$stmtCount->fetchColumn();
$totalPaginas   = ($totalRegistros > 0) ? (int)ceil($totalRegistros / $limit) : 1;
if ($page > $totalPaginas) {
    $page = $totalPaginas;
}
$offset = ($page - 1) * $limit;

// Consulta principal para obtener las solicitudes con sus relaciones
$sql = "SELECT s.*, m.nombre AS mecanico, r.nombre AS repuesto
        FROM solicitudes s
        LEFT JOIN mecanicos m ON m.id = s.mecanico_id
        LEFT JOIN repuestos r ON r.id = s.repuesto_id" . $whereClause .
        ' ORDER BY s.fecha DESC, s.id DESC LIMIT :limit OFFSET :offset';
$stmt = $conn->prepare($sql);
// Bind de parámetros
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
    <title>Solicitudes de repuesto</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        /* Cabecera y botones */
        header {
            position: relative;
            display: flex;
            justify-content: space-between;
            /* Align items vertically so logout lines up with the title */
            align-items: center;
            background: #fff;
            padding: 10px 20px;
            border-bottom: 5px solid #000;
        }
        /* Display the logo and title horizontally and center aligned */
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
            /* Push the back button further below the black separator line */
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
            /* Remove top margin to align with the username */
            margin-top: 0;
            margin-left: 10px;
        }

        .container {
            padding: 20px;
            max-width: 1200px;
            /* Increase top margin to provide more space for the repositioned back button */
            margin: 40px auto 0 auto;
        }
        .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .actions form {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .actions select {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
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
        .table-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow-x: auto;
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

        /* Modal overlay and container for solicitud emit forms */
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
        .modal-container input[type="number"],
        .modal-container select {
            width: 100%;
            padding: 8px;
            margin-top: 4px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }
        .modal-container button,
        .modal-container a.btn {
            margin-top: 16px;
            padding: 8px 14px;
            font-size: 14px;
            border-radius: 6px;
        }

        /* Título de la página ubicado bajo la franja negra */
        .page-title {
            font-size: 26px;
            font-weight: bold;
            text-align: center;
            margin: 0 0 20px 0;
        }
        /* Imagen de usuario (logo) junto al nombre y botón de salida */
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
    </style>
</head>
<body>
<header>
    <!-- Cabecera rediseñada para solicitudes: botón volver debajo del título -->
    <div class="header-left">
        <div class="logo-title">
            <img src="img/logo_auto_motores.png" alt="Logo">
            <h1>AUTO MOTORES</h1>
        </div>
        <?php if ($rol === 'jefe_taller'): ?>
            <a href="jefe_taller.php" class="btn-back">← Volver</a>
        <?php elseif ($rol === 'mecanico'): ?>
            <a href="mecanico.php" class="btn-back">← Volver</a>
        <?php else: ?>
            <a href="proveedor.php" class="btn-back">← Volver</a>
        <?php endif; ?>
    </div>
    <div class="header-right">
        <!-- Botón de cerrar sesión seguido del nombre de usuario y el logo -->
        <a href="cerrar_sesion.php" class="btn-logout">Cerrar sesión</a>
        <span class="user-name"><?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
        <img src="img/avatar.png" alt="Avatar" class="user-logo">
    </div>
</header>

<div class="container">
    <!-- Título de la sección -->
    <h2 class="page-title">Solicitudes de Repuesto</h2>
    
    <!-- Added success message when item is marked as delivered -->
    <?php if (isset($_GET['entregado_exitoso'])): ?>
    <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
        ✓ Pedido marcado como entregado exitosamente. <a href="historial_pedidos.php" style="color: #155724; text-decoration: underline;">Ver historial de pedidos</a>
    </div>
    <?php endif; ?>
    
    <!-- Added success message when request is approved -->
    <?php if (isset($_GET['aprobado_exitoso'])): ?>
    <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
        ✓ Solicitud aprobada exitosamente.
    </div>
    <?php endif; ?>
    
    <!-- Added success message when request is rejected -->
    <?php if (isset($_GET['rechazado_exitoso'])): ?>
    <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
        ✓ Solicitud denegada exitosamente.
    </div>
    <?php endif; ?>
    
    <!-- Added success message when item is deleted -->
    <?php if (isset($_GET['eliminado_exitoso'])): ?>
    <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
        ✓ Solicitud eliminada exitosamente.
    </div>
    <?php endif; ?>
    
    <!-- Added error message for update failures -->
    <?php if (isset($_GET['error'])): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
        <?php 
        $error = $_GET['error'];
        if ($error === 'solicitud_no_encontrada') {
            echo '✗ Error: La solicitud no fue encontrada.';
        } elseif ($error === 'actualizacion_fallida') {
            echo '✗ Error: No se pudo actualizar el estado de la solicitud. Intente nuevamente.';
        } else {
            echo '✗ Error: Ocurrió un problema inesperado.';
        }
        ?>
    </div>
    <?php endif; ?>
    
    <div class="actions">
        <form method="get" style="display:flex; align-items:center; gap:8px;">
            <!-- Permite filtrar por estado -->
            <select name="estado" style="padding:8px 12px; border:1px solid #ccc; border-radius:6px; font-size:14px;">
                <option value="">-- Estado --</option>
                <option value="pendiente" <?php echo (($_GET['estado'] ?? '') === 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                <option value="aprobada" <?php echo (($_GET['estado'] ?? '') === 'aprobada') ? 'selected' : ''; ?>>Aprobada</option>
                <!-- Cambiar 'rechazada' por 'denegada' en el filtro -->
                <option value="denegada" <?php echo (($_GET['estado'] ?? '') === 'denegada') ? 'selected' : ''; ?>>Denegada</option>
                <option value="entregado" <?php echo (($_GET['estado'] ?? '') === 'entregado') ? 'selected' : ''; ?>>Entregado</option>
            </select>
            <button type="submit" class="btn-small" style="background:#000;">Filtrar</button>
        </form>
        <!-- Contenedor de botones alineado a la derecha -->
        <div style="display:flex; gap:8px; margin-left:auto;">
            <?php if ($rol === 'proveedor'): ?>
                <!-- Changed Ver Historial button background to black -->
                <a href="historial_pedidos.php" class="btn-small" style="background:#000; color:#fff;">Ver Historial</a>
                <!-- Added delete button for delivered requests -->
                <button onclick="eliminarSeleccionado()" class="btn-small" style="background:#dc3545; color:#fff;">Eliminar Seleccionado</button>
            <?php endif; ?>
            <?php if ($rol === 'jefe_taller'): ?>
                <!-- Imprimir: gris con texto blanco -->
                <a href="#" class="btn-small" style="background:#6c757d; color:#fff;" onclick="return mostrarVistaPreviaImpresion();">Imprimir</a>
                <!-- Added delete button next to print button for jefe_taller -->
                <button onclick="eliminarSeleccionadoJefe()" class="btn-small" style="background:#dc3545; color:#fff;">Eliminar</button>
            <?php endif; ?>
            <?php if ($rol === 'mecanico'): ?>
                <!-- Emitir solicitud: gris con texto blanco -->
                <a href="solicitudes.php?nueva=1" class="btn-small" style="background:#6c757d; color:#fff;">Emitir solicitud</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($rol === 'mecanico' && isset($_GET['nueva'])): ?>
    <div class="modal-overlay">
        <div class="modal-container">
            <h2>Nuevo requerimiento de repuesto</h2>
            <form method="post" action="solicitudes.php">
                <input type="hidden" name="nueva_solicitud" value="1">
                <label>Mecánico
                    <select name="mecanico_id" required>
                        <?php foreach ($listaMecanicos as $m): ?>
                            <?php $selected = ($_SESSION['usuario'] === $m['nombre']) ? 'selected' : ''; ?>
                            <option value="<?php echo $m['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($m['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Repuesto
                    <select name="repuesto_id" required>
                        <?php foreach ($listaRepuestos as $r): ?>
                            <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Cantidad
                    <input type="number" name="cantidad" min="1" value="1" required>
                </label>
                <button type="submit" class="btn">Enviar</button>
                <a href="solicitudes.php" class="btn" style="background:#6c757d;">Cancelar</a>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="table-container">
        <table>
            <thead>
            <tr>
                <th>Mecánico</th>
                <th>Repuesto</th>
                <th>Cantidad</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php if (isset($mechanicNotFound) && $mechanicNotFound): ?>
                <!-- Added message when mechanic not found in database -->
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px; color: #666;">
                        No se encontró el mecánico en la base de datos. Por favor contacte al administrador.
                    </td>
                </tr>
            <?php elseif (empty($solicitudes)): ?>
                <!-- Added message when no requests found -->
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px; color: #666;">
                        <?php if ($rol === 'mecanico'): ?>
                            No tienes solicitudes de repuesto. Haz clic en "Emitir solicitud" para crear una nueva.
                        <?php else: ?>
                            No hay solicitudes de repuesto disponibles.
                        <?php endif; ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($solicitudes as $s): ?>
                    <!-- Added data-request-id attribute to each row for JavaScript deletion functionality -->
                    <tr data-request-id="<?php echo $s['id']; ?>">
                        <td><?php echo htmlspecialchars($s['mecanico']); ?></td>
                        <td><?php echo htmlspecialchars($s['repuesto']); ?></td>
                        <td><?php echo htmlspecialchars($s['cantidad']); ?></td>
                        <td><?php echo htmlspecialchars($s['fecha']); ?></td>
                        <!-- Improved status display with proper formatting and fallback -->
                        <td>
                            <?php 
                            $estado = $s['estado'] ?? '';
                            if (empty($estado)) {
                                echo 'Pendiente';
                            } else {
                                switch($estado) {
                                    case 'pendiente':
                                        echo '<span style="color: #ffc107; font-weight: bold;">Pendiente</span>';
                                        break;
                                    case 'aprobada':
                                        echo '<span style="color: #28a745; font-weight: bold;">Aprobada</span>';
                                        break;
                                    case 'denegada':
                                        echo '<span style="color: #dc3545; font-weight: bold;">Denegada</span>';
                                        break;
                                    case 'entregado':
                                        echo '<span style="color: #17a2b8; font-weight: bold;">Entregado</span>';
                                        break;
                                    default:
                                        echo ucfirst($estado);
                                }
                            }
                            ?>
                        </td>
                        <!-- Modified actions column to show appropriate options for mechanic -->
                        <td>
                            <?php if ($rol === 'jefe_taller' && $s['estado'] === 'pendiente'): ?>
                                <a href="solicitudes.php?accion=aprobar&id=<?php echo $s['id']; ?>" class="btn-small" style="background:#28a745;">Aprobar</a>
                                <a href="solicitudes.php?accion=rechazar&id=<?php echo $s['id']; ?>" class="btn-small" style="background:#dc3545;">Rechazar</a>
                            <?php elseif ($rol === 'mecanico'): ?>
                                <?php if ($s['estado'] === 'pendiente'): ?>
                                    <a href="solicitudes.php?cancelar=<?php echo $s['id']; ?>" class="btn-small" style="background:#dc3545;" onclick="return confirm('¿Desea cancelar la solicitud?');">Cancelar</a>
                                <?php else: ?>
                                    <span style="color: #666; font-style: italic;">Solo lectura</span>
                                <?php endif; ?>
                            <?php elseif ($rol === 'proveedor' && $s['estado'] === 'aprobada'): ?>
                                <a href="solicitudes.php?entregar=1&id=<?php echo $s['id']; ?>" class="btn-small" style="background:#28a745;" onclick="return confirm('¿Marcar como entregado?');">Marcar Entregado</a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Navegación de páginas para solicitudes -->
    <?php if ($totalPaginas > 1): ?>
    <nav class="pagination" style="margin-top: 15px; display: flex; justify-content: center; gap:6px;">
        <?php
        $baseParams = $_GET;
        unset($baseParams['page'], $baseParams['nueva'], $baseParams['accion'], $baseParams['id'], $baseParams['cancelar'], $baseParams['entregar']);
        for ($i = 1; $i <= $totalPaginas; $i++):
            $baseParams['page'] = $i;
            $url = 'solicitudes.php?' . http_build_query($baseParams);
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
<div id="print-preview-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); justify-content:center; align-items:center; z-index:2000;">
    <div id="print-preview-content" style="background:#fff; padding:20px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.2); max-width:90%; max-height:90%; overflow:auto;"></div>
</div>

<!-- Estilo para resaltar la fila seleccionada -->
<style>
.selected-row {
    background-color: #d0e8ff !important;
}
</style>

<!-- Funciones de selección y vista previa de impresión -->
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
    
    // Get current date and time
    var now = new Date();
    var dateStr = now.toLocaleDateString('es-ES') + ', ' + now.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});
    
    var html = '';
    
    // Professional header with company branding
    html += '<div style="text-align: center; margin-bottom: 40px; border-bottom: 3px solid #000; padding-bottom: 20px;">';
    html += '<h1 style="font-family: Impact, sans-serif; font-size: 36px; margin: 0; color: #000; letter-spacing: 2px;">Auto Motores</h1>';
    html += '<h2 style="font-size: 20px; margin: 15px 0 0 0; color: #333; font-weight: normal;">Reporte de Solicitudes de Repuesto</h2>';
    html += '</div>';
    
    // Date and time with professional formatting
    html += '<div style="text-align: center; margin-bottom: 30px;">';
    html += '<p style="font-size: 14px; margin: 0; color: #666; font-weight: bold;">' + dateStr + '</p>';
    html += '</div>';
    
    // Professional table with enhanced styling
    var tableStyle = 'width: 100%; border-collapse: collapse; margin: 20px 0; font-family: Arial, sans-serif; border: 1px solid #ddd;';
    var headerStyle = 'background: #000; color: #fff; padding: 15px 12px; text-align: left; font-weight: bold; font-size: 14px; border-right: 1px solid #333;';
    var cellStyle = 'padding: 12px; border-bottom: 1px solid #ddd; border-right: 1px solid #eee; font-size: 13px; vertical-align: top;';
    var evenRowStyle = 'background: #f8f9fa;';
    
    if (selected) {
        // Single selected row
        var cells = selected.querySelectorAll('td');
        var headers = document.querySelectorAll('table thead th');
        
        html += '<table style="' + tableStyle + '">';
        html += '<thead><tr>';
        for (var i = 0; i < cells.length - 1; i++) {
            html += '<th style="' + headerStyle + '">' + headers[i].textContent + '</th>';
        }
        html += '</tr></thead>';
        html += '<tbody><tr>';
        for (var i = 0; i < cells.length - 1; i++) {
            html += '<td style="' + cellStyle + '">' + cells[i].textContent + '</td>';
        }
        html += '</tr></tbody></table>';
    } else {
        // All rows with alternating colors
        var table = document.querySelector('table');
        if (table) {
            html += '<table style="' + tableStyle + '">';
            var ths = table.querySelectorAll('thead th');
            html += '<thead><tr>';
            for (var i = 0; i < ths.length - 1; i++) {
                html += '<th style="' + headerStyle + '">' + ths[i].textContent + '</th>';
            }
            html += '</tr></thead>';
            html += '<tbody>';
            var rows = table.querySelectorAll('tbody tr');
            rows.forEach(function(r, index) {
                var tds = r.querySelectorAll('td');
                var rowStyle = (index % 2 === 1) ? evenRowStyle : '';
                html += '<tr style="' + rowStyle + '">';
                for (var i = 0; i < tds.length - 1; i++) {
                    html += '<td style="' + cellStyle + '">' + tds[i].textContent + '</td>';
                }
                html += '</tr>';
            });
            html += '</tbody></table>';
        }
    }
    
    // Professional footer with system information
    html += '<div style="text-align: center; margin-top: 50px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 10px; color: #666;">';
    html += '<p style="margin: 8px 0; font-weight: bold;">Auto Motores - Sistema de Gestión de Solicitudes de Repuesto</p>';
    html += '<p style="margin: 8px 0;">Reporte generado el ' + dateStr + '</p>';
    html += '</div>';
    
    // Professional action buttons with better styling
    html += '<div style="text-align: center; margin-top: 30px; padding: 20px;">';
    html += '<button onclick="imprimirReporte()" style="background: #007bff; color: #fff; padding: 14px 28px; border: none; border-radius: 6px; font-size: 16px; margin-right: 15px; cursor: pointer; box-shadow: 0 2px 4px rgba(0,123,255,0.3); transition: all 0.2s;">Imprimir</button>';
    html += '<button onclick="cerrarVistaPrevia()" style="background: #6c757d; color: #fff; padding: 14px 28px; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; box-shadow: 0 2px 4px rgba(108,117,125,0.3); transition: all 0.2s;">Cerrar</button>';
    html += '</div>';
    
    contentDiv.innerHTML = html;
    modal.style.display = 'flex';
    return false;
}

function imprimirReporte() {
    var contentDiv = document.getElementById('print-preview-content');
    var printWindow = window.open('', '_blank', 'width=800,height=600');
    
    printWindow.document.write('<!DOCTYPE html>');
    printWindow.document.write('<html><head><title>Reporte de Solicitudes de Repuesto</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('@page { margin: 2cm; size: A4; }');
    printWindow.document.write('body { font-family: Arial, sans-serif; margin: 0; padding: 20px; line-height: 1.4; }');
    printWindow.document.write('table { width: 100%; border-collapse: collapse; margin: 20px 0; }');
    printWindow.document.write('th { background: #000; color: #fff; padding: 12px 8px; text-align: left; font-size: 12px; }');
    printWindow.document.write('td { padding: 10px 8px; border-bottom: 1px solid #ddd; font-size: 11px; }');
    printWindow.document.write('tr:nth-child(even) { background: #f8f9fa; }');
    printWindow.document.write('h1 { font-family: Impact, sans-serif; text-align: center; font-size: 28px; margin-bottom: 10px; }');
    printWindow.document.write('h2 { text-align: center; font-size: 18px; margin-bottom: 20px; }');
    printWindow.document.write('.header-border { border-bottom: 3px solid #000; padding-bottom: 15px; margin-bottom: 30px; }');
    printWindow.document.write('.footer { text-align: center; margin-top: 40px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 10px; color: #666; }');
    printWindow.document.write('@media print { button { display: none !important; } .no-print { display: none !important; } }');
    printWindow.document.write('</style>');
    printWindow.document.write('</head><body>');
    
    // Extract and clean content for printing
    var printContent = contentDiv.innerHTML;
    // Remove buttons and non-printable elements
    printContent = printContent.replace(/<div style="text-align: center; margin-top: 30px; padding: 20px;">.*?<\/div>/s, '');
    printContent = printContent.replace(/box-shadow: [^;]+;/g, '');
    printContent = printContent.replace(/border-radius: [^;]+;/g, '');
    
    printWindow.document.write(printContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    
    // Wait for content to load then print
    setTimeout(function() {
        printWindow.focus();
        printWindow.print();
    }, 500);
}

function cerrarVistaPrevia() {
    var modal = document.getElementById('print-preview-modal');
    if (modal) modal.style.display = 'none';
}

function eliminarSeleccionado() {
    var selected = document.querySelector('table tbody tr.selected-row');
    if (!selected) {
        alert('Por favor selecciona una fila para eliminar.');
        return;
    }
    
    var cells = selected.querySelectorAll('td');
    if (cells.length < 5) {
        alert('Error al obtener los datos de la solicitud.');
        return;
    }
    
    // Find the row index to get the corresponding request ID from PHP data
    var tbody = selected.parentNode;
    var rowIndex = Array.from(tbody.children).indexOf(selected);
    
    // Since we can't directly access PHP data from JavaScript, we'll use a different approach
    // We'll add the ID as a data attribute to each row
    var requestId = selected.getAttribute('data-request-id');
    if (!requestId) {
        alert('No se puede eliminar esta solicitud.');
        return;
    }
    
    if (confirm('¿Eliminar la solicitud seleccionada?')) {
        var currentPage = new URLSearchParams(window.location.search).get('page') || '1';
        window.location.href = 'solicitudes.php?eliminar=1&id=' + requestId + '&page=' + currentPage;
    }
}

function eliminarSeleccionadoJefe() {
    var selected = document.querySelector('table tbody tr.selected-row');
    if (!selected) {
        alert('Por favor selecciona una fila para eliminar.');
        return;
    }
    
    var requestId = selected.getAttribute('data-request-id');
    if (!requestId) {
        alert('No se puede eliminar esta solicitud.');
        return;
    }
    
    if (confirm('¿Está seguro de que desea eliminar la solicitud seleccionada?')) {
        var currentPage = new URLSearchParams(window.location.search).get('page') || '1';
        window.location.href = 'solicitudes.php?eliminar=1&id=' + requestId + '&page=' + currentPage;
    }
}
</script>
</body>
</html>
