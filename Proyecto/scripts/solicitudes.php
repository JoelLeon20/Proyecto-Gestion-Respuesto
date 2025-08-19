<?php
// solicitudes.php
// Gestión de solicitudes de repuestos.  Según el rol del usuario se
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
    $fecha      = date('Y-m-d');
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
        $nuevoEstado = ($accion === 'aprobar') ? 'aprobada' : 'rechazada';
        // Actualizar estado
        $stmt = $conn->prepare('UPDATE solicitudes SET estado=:estado WHERE id=:id');
        $stmt->execute([':estado' => $nuevoEstado, ':id' => $id]);
        // Si se aprueba, restar del stock?  Opcional.  Para simplificar, no modificamos el stock aquí.
    }
    header('Location: solicitudes.php');
    exit;
}

// Procesar marcar como entregado (proveedor)
if ($rol === 'proveedor' && isset($_GET['entregar']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // El proveedor puede marcar como entregado solo las solicitudes aprobadas
    $stmt = $conn->prepare('SELECT estado FROM solicitudes WHERE id=:id');
    $stmt->execute([':id' => $id]);
    $sol = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($sol && $sol['estado'] === 'aprobada') {
        $stmt = $conn->prepare('UPDATE solicitudes SET estado=:estado WHERE id=:id');
        $stmt->execute([':estado' => 'entregado', ':id' => $id]);
        header('Location: solicitudes.php?entregado_exitoso=1');
        exit;
    }
    header('Location: solicitudes.php');
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
    $stmtTmp = $conn->prepare('SELECT id FROM mecanicos WHERE nombre = :nombre LIMIT 1');
    $stmtTmp->execute([':nombre' => $nombreUsuario]);
    $me = $stmtTmp->fetch(PDO::FETCH_ASSOC);
    if ($me) {
        $conditions[] = 's.mecanico_id = :mid';
        $params[':mid'] = $me['id'];
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
        ' ORDER BY s.fecha DESC LIMIT :limit OFFSET :offset';
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
    
    <div class="actions">
        <form method="get" style="display:flex; align-items:center; gap:8px;">
            <!-- Permite filtrar por estado -->
            <select name="estado" style="padding:8px 12px; border:1px solid #ccc; border-radius:6px; font-size:14px;">
                <option value="">-- Estado --</option>
                <option value="pendiente" <?php echo (($_GET['estado'] ?? '') === 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                <option value="aprobada" <?php echo (($_GET['estado'] ?? '') === 'aprobada') ? 'selected' : ''; ?>>Aprobada</option>
                <option value="rechazada" <?php echo (($_GET['estado'] ?? '') === 'rechazada') ? 'selected' : ''; ?>>Rechazada</option>
                <option value="entregado" <?php echo (($_GET['estado'] ?? '') === 'entregado') ? 'selected' : ''; ?>>Entregado</option>
            </select>
            <button type="submit" class="btn-small" style="background:#000;">Filtrar</button>
        </form>
        <!-- Contenedor de botones alineado a la derecha -->
        <div style="display:flex; gap:8px; margin-left:auto;">
            <?php if ($rol === 'proveedor'): ?>
                <!-- Added quick link to history for suppliers -->
                <a href="historial_pedidos.php" class="btn-small" style="background:#28a745; color:#fff;">Ver Historial</a>
            <?php endif; ?>
            <?php if ($rol === 'jefe_taller'): ?>
                <!-- Imprimir: gris con texto blanco -->
                <a href="#" class="btn-small" style="background:#6c757d; color:#fff;" onclick="return mostrarVistaPreviaImpresion();">Imprimir</a>
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
            <?php foreach ($solicitudes as $s): ?>
                <tr>
                    <td><?php echo htmlspecialchars($s['mecanico']); ?></td>
                    <td><?php echo htmlspecialchars($s['repuesto']); ?></td>
                    <td><?php echo htmlspecialchars($s['cantidad']); ?></td>
                    <td><?php echo htmlspecialchars($s['fecha']); ?></td>
                    <td><?php echo htmlspecialchars($s['estado']); ?></td>
                    <td>
                        <?php if ($rol === 'jefe_taller' && $s['estado'] === 'pendiente'): ?>
                            <a href="solicitudes.php?accion=aprobar&id=<?php echo $s['id']; ?>" class="btn-small" style="background:#28a745;">Aprobar</a>
                            <a href="solicitudes.php?accion=rechazar&id=<?php echo $s['id']; ?>" class="btn-small" style="background:#dc3545;">Rechazar</a>
                        <?php elseif ($rol === 'mecanico' && $s['estado'] === 'pendiente'): ?>
                            <a href="solicitudes.php?cancelar=<?php echo $s['id']; ?>" class="btn-small" style="background:#dc3545;" onclick="return confirm('¿Desea cancelar la solicitud?');">Cancelar</a>
                        <?php elseif ($rol === 'proveedor' && $s['estado'] === 'aprobada'): ?>
                            <a href="solicitudes.php?entregar=1&id=<?php echo $s['id']; ?>" class="btn-small" style="background:#28a745;" onclick="return confirm('¿Marcar como entregado?');">Marcar Entregado</a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
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
    var html = '';
    if (selected) {
        var cells = selected.querySelectorAll('td');
        var headers = document.querySelectorAll('table thead th');
        html += '<h3 style="margin-top:0; margin-bottom:10px;">Vista previa de impresión</h3>';
        html += '<table style="width:100%; border-collapse:collapse;">';
        html += '<thead><tr>';
        for (var i = 0; i < cells.length - 1; i++) {
            html += '<th style="text-align:left; padding:6px 8px; border-bottom:1px solid #ccc;">' + headers[i].textContent + '</th>';
        }
        html += '</tr></thead>';
        html += '<tbody><tr>';
        for (var i = 0; i < cells.length - 1; i++) {
            html += '<td style="padding:6px 8px; border-bottom:1px solid #eee;">' + cells[i].textContent + '</td>';
        }
        html += '</tr></tbody></table>';
    } else {
        var table = document.querySelector('table');
        if (table) {
            html += '<h3 style="margin-top:0; margin-bottom:10px;">Vista previa de impresión</h3>';
            html += '<table style="width:100%; border-collapse:collapse;">';
            var ths = table.querySelectorAll('thead th');
            html += '<thead><tr>';
            for (var i = 0; i < ths.length - 1; i++) {
                html += '<th style="text-align:left; padding:6px 8px; border-bottom:1px solid #ccc;">' + ths[i].textContent + '</th>';
            }
            html += '</tr></thead>';
            html += '<tbody>';
            var rows = table.querySelectorAll('tbody tr');
            rows.forEach(function(r) {
                var tds = r.querySelectorAll('td');
                html += '<tr>';
                for (var i = 0; i < tds.length - 1; i++) {
                    html += '<td style="padding:6px 8px; border-bottom:1px solid #eee;">' + tds[i].textContent + '</td>';
                }
                html += '</tr>';
            });
            html += '</tbody></table>';
        }
    }
    html += '<div style="text-align:center; margin-top:20px;"><button onclick="cerrarVistaPrevia()" class="btn-small" style="background:#6c757d;">Cerrar</button></div>';
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
