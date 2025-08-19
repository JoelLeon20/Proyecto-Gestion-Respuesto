<?php
// ordenes.php
// Gestión de órdenes de trabajo.  
// - Jefe de taller: puede ver todas, crear nuevas, editar (cambiar descripción, asignar mecánico, fechas) y eliminar.  
// - Mecánico: solo puede ver órdenes asignadas a él y marcar como finalizadas.

session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php?usuario=jefe');
    exit;
}
$rol = $_SESSION['rol'];
include 'conexion.php';

// Obtener lista de mecánicos para formularios
$listaMecanicos = $conn->query('SELECT id, nombre FROM mecanicos ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);

// Procesar crear/editar orden (jefe)
if ($rol === 'jefe_taller' && isset($_POST['guardar'])) {
    $id           = $_POST['id'] ?? '';
    $mecanicoId   = $_POST['mecanico_id'] ?? '';
    $descripcion  = $_POST['descripcion'] ?? '';
    $fechaInicio  = $_POST['fecha_inicio'] ?? '';
    $fechaFin     = $_POST['fecha_fin'] ?? '';
    $estado       = $_POST['estado'] ?? 'abierta';
    if ($id) {
        $stmt = $conn->prepare('UPDATE ordenes SET mecanico_id=:mecanico, descripcion=:descripcion, fecha_inicio=:fecha_inicio, fecha_fin=:fecha_fin, estado=:estado WHERE id=:id');
        $stmt->execute([
            ':mecanico'     => $mecanicoId,
            ':descripcion'  => $descripcion,
            ':fecha_inicio' => $fechaInicio,
            ':fecha_fin'    => $fechaFin,
            ':estado'       => $estado,
            ':id'           => $id,
        ]);
    } else {
        $stmt = $conn->prepare('INSERT INTO ordenes (mecanico_id, descripcion, fecha_inicio, fecha_fin, estado) VALUES (:mecanico,:descripcion,:fecha_inicio,:fecha_fin,:estado)');
        $stmt->execute([
            ':mecanico'     => $mecanicoId,
            ':descripcion'  => $descripcion,
            ':fecha_inicio' => $fechaInicio,
            ':fecha_fin'    => $fechaFin,
            ':estado'       => $estado,
        ]);
    }
    header('Location: ordenes.php');
    exit;
}

// Procesar eliminar orden (jefe)
if ($rol === 'jefe_taller' && isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $stmt = $conn->prepare('DELETE FROM ordenes WHERE id=:id');
    $stmt->execute([':id' => $id]);
    header('Location: ordenes.php');
    exit;
}

// Procesar finalizar orden (mecánico)
if ($rol === 'mecanico' && isset($_GET['finalizar'])) {
    $id = (int)$_GET['finalizar'];
    // Asegurar que la orden pertenece al mecanico actual
    $nombreUsuario = $_SESSION['usuario'];
    $stmt = $conn->prepare('SELECT o.id FROM ordenes o JOIN mecanicos m ON m.id = o.mecanico_id WHERE o.id=:id AND m.nombre=:nombre');
    $stmt->execute([':id' => $id, ':nombre' => $nombreUsuario]);
    if ($stmt->fetch()) {
        $stmt = $conn->prepare('UPDATE ordenes SET estado="finalizada", fecha_fin=:fecha_fin WHERE id=:id');
        $stmt->execute([':fecha_fin' => date('Y-m-d'), ':id' => $id]);
    }
    header('Location: ordenes.php');
    exit;
}

// Obtener orden a editar
$editar = null;
if ($rol === 'jefe_taller' && isset($_GET['editar'])) {
    $idEdit = (int)$_GET['editar'];
    $stmt = $conn->prepare('SELECT * FROM ordenes WHERE id=:id');
    $stmt->execute([':id' => $idEdit]);
    $editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// -------------------------------------------------------
//  Construir consulta para listar órdenes con filtrado y paginación
// -------------------------------------------------------

// Parámetros de filtro (por GET)
$inicio = $_GET['inicio'] ?? '';
$fin    = $_GET['fin'] ?? '';
$buscar = $_GET['buscar'] ?? '';
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
// Número de registros por página
$limit  = 10;
// Condiciones dinámicas y parámetros
$conditions = [];
$queryParams = [];

// Filtrar por rol (solo mostrar órdenes del mecánico si no es jefe)
if ($rol === 'mecanico') {
    $nombreUsuario = $_SESSION['usuario'];
    $conditions[] = 'm.nombre = :nombre';
    $queryParams[':nombre'] = $nombreUsuario;
}
// Filtrar por fecha de inicio (desde)
if (!empty($inicio)) {
    $conditions[] = 'date(o.fecha_inicio) >= :inicio';
    $queryParams[':inicio'] = $inicio;
}
// Filtrar por fecha de inicio (hasta)
if (!empty($fin)) {
    $conditions[] = 'date(o.fecha_inicio) <= :fin';
    $queryParams[':fin'] = $fin;
}
// Filtrar por búsqueda en descripción o nombre de mecánico
if (!empty($buscar)) {
    $conditions[] = '(o.descripcion LIKE :buscar OR m.nombre LIKE :buscar)';
    $queryParams[':buscar'] = '%' . $buscar . '%';
}

// Armado de la cláusula WHERE
$whereClause = '';
if (count($conditions) > 0) {
    $whereClause = ' WHERE ' . implode(' AND ', $conditions);
}

// Consulta para contar cuántas órdenes cumplen los filtros (para paginación)
$sqlCount = 'SELECT COUNT(*) FROM ordenes o LEFT JOIN mecanicos m ON m.id = o.mecanico_id' . $whereClause;
$stmtCount = $conn->prepare($sqlCount);
foreach ($queryParams as $key => $val) {
    $stmtCount->bindValue($key, $val);
}
$stmtCount->execute();
$totalRegistros = (int)$stmtCount->fetchColumn();
// Calcular número total de páginas
$totalPaginas = ($totalRegistros > 0) ? (int)ceil($totalRegistros / $limit) : 1;
// Asegurar que la página actual no exceda el total
if ($page > $totalPaginas) {
    $page = $totalPaginas;
}
// Calcular offset según la página actual
$offset = ($page - 1) * $limit;

// Consulta principal para listar órdenes con filtros y paginación
$sql = 'SELECT o.*, m.nombre AS mecanico FROM ordenes o LEFT JOIN mecanicos m ON m.id = o.mecanico_id' . $whereClause . ' ORDER BY o.fecha_inicio DESC LIMIT :limit OFFSET :offset';
$stmt = $conn->prepare($sql);
// Bind de parámetros de filtros
foreach ($queryParams as $key => $val) {
    $stmt->bindValue($key, $val);
}
// Bind de parámetros de paginación (enteros)
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Órdenes de trabajo</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        /* Cabecera y botones */
        header {
            position: relative;
            display: flex;
            justify-content: space-between;
            /* Center the header items vertically */
            align-items: center;
            background: #fff;
            padding: 10px 20px;
            border-bottom: 5px solid #000;
        }
        /* Lay out logo and title in a row and center them */
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

        /* Modal overlay and container for order forms */
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
        .modal-container input[type="text"],
        .modal-container input[type="date"],
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
    </style>
</head>
<body>
<header>
    <!-- Cabecera rediseñada para órdenes: logo y título arriba con botón de volver debajo -->
    <div class="header-left">
        <div class="logo-title">
            <img src="img/logo_auto_motores.png" alt="Logo">
            <h1>AUTO MOTORES</h1>
        </div>
        <?php if ($rol === 'jefe_taller'): ?>
            <a href="jefe_taller.php" class="btn-back">← Volver</a>
        <?php else: ?>
            <a href="mecanico.php" class="btn-back">← Volver</a>
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
    <h2 class="page-title">Órdenes de Trabajo</h2>
    <?php if ($rol === 'jefe_taller' && (isset($_GET['nuevo']) || $editar)): ?>
    <div class="modal-overlay">
        <div class="modal-container">
            <h2><?php echo $editar ? 'Editar orden' : 'Nueva orden'; ?></h2>
            <form method="post" action="ordenes.php">
                <input type="hidden" name="id" value="<?php echo $editar['id'] ?? ''; ?>">
                <label>Mecánico
                    <select name="mecanico_id" required>
                        <?php foreach ($listaMecanicos as $m): ?>
                        <option value="<?php echo $m['id']; ?>" <?php echo (isset($editar['mecanico_id']) && $editar['mecanico_id'] == $m['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($m['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Descripción
                    <input type="text" name="descripcion" value="<?php echo htmlspecialchars($editar['descripcion'] ?? ''); ?>" required>
                </label>
                <label>Fecha de inicio
                    <input type="date" name="fecha_inicio" value="<?php echo htmlspecialchars($editar['fecha_inicio'] ?? date('Y-m-d')); ?>" required>
                </label>
                <label>Fecha de fin
                    <input type="date" name="fecha_fin" value="<?php echo htmlspecialchars($editar['fecha_fin'] ?? ''); ?>">
                </label>
                <label>Estado
                    <select name="estado">
                        <option value="abierta" <?php echo (isset($editar['estado']) && $editar['estado'] == 'abierta') ? 'selected' : ''; ?>>Abierta</option>
                        <option value="finalizada" <?php echo (isset($editar['estado']) && $editar['estado'] == 'finalizada') ? 'selected' : ''; ?>>Finalizada</option>
                    </select>
                </label>
                <button type="submit" name="guardar" class="btn">Guardar</button>
                <a href="ordenes.php" class="btn" style="background:#6c757d;">Cancelar</a>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Barra de acciones: filtrado por fechas y botones de imprimir y añadir -->
    <div class="actions" style="display:flex; justify-content: space-between; align-items:center; flex-wrap:wrap; margin-bottom: 10px; gap:10px;">
        <form method="get" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            <input type="date" name="inicio" value="<?php echo htmlspecialchars($inicio); ?>" class="input-date" style="padding:6px 8px; border:1px solid #ccc; border-radius:6px; font-size:14px;">
            <input type="date" name="fin" value="<?php echo htmlspecialchars($fin); ?>" class="input-date" style="padding:6px 8px; border:1px solid #ccc; border-radius:6px; font-size:14px;">
            <!-- Campo de búsqueda opcional -->
            <input type="text" name="buscar" placeholder="Buscar descripción o mecánico" value="<?php echo htmlspecialchars($buscar); ?>" style="padding:6px 8px; border:1px solid #ccc; border-radius:6px; font-size:14px;" />
            <button type="submit" class="btn-small" style="background:#000;">Filtrar</button>
        </form>
        <?php if ($rol === 'jefe_taller'): ?>
            <!-- Contenedor de botones alineado a la derecha -->
            <div style="display:flex; gap:8px; margin-left:auto;">
                <!-- Botón de imprimir: gris con texto blanco -->
                <a href="#" class="btn-small" style="background:#6c757d; color:#fff;" onclick="return mostrarVistaPreviaImpresion();">Imprimir</a>
                <!-- Botón de añadir nueva orden: negro con texto blanco -->
                <a href="ordenes.php?nuevo=1" class="btn-small" style="background:#000; color:#fff;">Añadir</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="table-container">
        <table>
            <thead>
            <tr>
                <th>Mecánico</th>
                <th>Descripción</th>
                <th>Fecha inicio</th>
                <th>Fecha fin</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($ordenes as $o): ?>
                <tr>
                    <td><?php echo htmlspecialchars($o['mecanico']); ?></td>
                    <td><?php echo htmlspecialchars($o['descripcion']); ?></td>
                    <td><?php echo htmlspecialchars($o['fecha_inicio']); ?></td>
                    <td><?php echo htmlspecialchars($o['fecha_fin']); ?></td>
                    <td><?php echo htmlspecialchars($o['estado']); ?></td>
                    <td>
                        <?php if ($rol === 'jefe_taller'): ?>
                            <a href="ordenes.php?editar=<?php echo $o['id']; ?>" class="btn-small" style="background:#007bff;">Editar</a>
                            <a href="ordenes.php?eliminar=<?php echo $o['id']; ?>" class="btn-small" style="background:#dc3545;" onclick="return confirm('¿Desea eliminar esta orden?');">Eliminar</a>
                        <?php elseif ($rol === 'mecanico' && $o['estado'] === 'abierta'): ?>
                            <a href="ordenes.php?finalizar=<?php echo $o['id']; ?>" class="btn-small" style="background:#28a745;" onclick="return confirm('¿Marcar esta orden como finalizada?');">Finalizar</a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- Navegación de páginas -->
    <?php if ($totalPaginas > 1): ?>
    <nav class="pagination" style="margin-top: 15px; display: flex; justify-content: center; gap:6px;">
        <?php
        // Conservar parámetros de filtro en los enlaces de paginación
        $baseParams = $_GET;
        unset($baseParams['page'], $baseParams['nuevo'], $baseParams['editar'], $baseParams['eliminar'], $baseParams['finalizar']);
        for ($i = 1; $i <= $totalPaginas; $i++):
            $baseParams['page'] = $i;
            $url = 'ordenes.php?' . http_build_query($baseParams);
            $active = ($i == $page) ? 'active' : '';
        ?>
            <a href="<?php echo $url; ?>" class="page-link <?php echo $active; ?>" style="padding:6px 10px; border-radius:4px; border:1px solid #000; background: <?php echo ($i == $page) ? '#000' : '#fff'; ?>; color: <?php echo ($i == $page) ? '#fff' : '#000'; ?>; text-decoration:none; font-size:14px;">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
</div>
<!-- Modal para simulación de impresión -->
<!-- Modal para vista previa de impresión -->
<div id="print-preview-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); justify-content:center; align-items:center; z-index:2000;">
    <div id="print-preview-content" style="background:#fff; padding:20px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.2); max-width:90%; max-height:90%; overflow:auto;">
        <!-- El contenido se inyecta mediante JavaScript -->
    </div>
</div>

<!-- Estilo para resaltar la fila seleccionada -->
<style>
.selected-row {
    background-color: #d0e8ff !important;
}
</style>

<!-- Funciones de selección y vista previa de impresión -->
<script>
// Destacar fila seleccionada
document.addEventListener('DOMContentLoaded', function() {
    var tbody = document.querySelector('table tbody');
    if (tbody) {
        tbody.addEventListener('click', function(e) {
            var row = e.target.closest('tr');
            if (!row) return;
            // Remover selección previa
            tbody.querySelectorAll('tr').forEach(function(r) { r.classList.remove('selected-row'); });
            // Seleccionar la nueva fila
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
        // Get data from selected row
        var cells = selected.querySelectorAll('td');
        var mecanico = cells[0].textContent;
        var descripcion = cells[1].textContent;
        var fechaInicio = cells[2].textContent;
        var fechaFin = cells[3].textContent;
        var estado = cells[4].textContent;
        
        // Generate work order number (simple random for demo)
        var ordenNum = Math.floor(Math.random() * 1000) + 1;
        var currentDate = new Date().toLocaleDateString('es-ES');
        var currentTime = new Date().toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});
        
        html = `
        <div style="max-width: 800px; margin: 0 auto; font-family: Arial, sans-serif; background: white;">
            <!-- Header -->
            <div style="text-align: center; padding: 20px 0; border-bottom: 2px solid #000; margin-bottom: 20px;">
                <h1 style="font-size: 32px; font-weight: bold; margin: 0; color: #000;">Auto Motores</h1>
                <h2 style="font-size: 24px; margin: 10px 0 0 0; color: #333;">Orden de Trabajo #${ordenNum}</h2>
            </div>
            
            <!-- Date and basic info -->
            <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px;">
                <div><strong>${currentDate}, ${currentTime}</strong></div>
                <div><strong>Imprimir Orden #${ordenNum}</strong></div>
            </div>
            
            <!-- Work order details -->
            <div style="display: flex; justify-content: space-between; margin-bottom: 30px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <div>
                    <strong>Mecánico:</strong> ${mecanico}
                </div>
                <div>
                    <strong>Fecha:</strong> ${fechaInicio}
                </div>
            </div>
            
            <!-- Service details section -->
            <div style="margin-bottom: 30px;">
                <h3 style="font-size: 20px; margin-bottom: 15px; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Detalle del Servicio</h3>
                <div style="padding: 15px; background: #f8f9fa; border-radius: 8px; line-height: 1.6;">
                    ${descripcion}
                </div>
            </div>
            
            <!-- Status and dates -->
            <div style="margin-bottom: 30px;">
                <h3 style="font-size: 20px; margin-bottom: 15px; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Estado del Trabajo</h3>
                <div style="display: flex; justify-content: space-between; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <div><strong>Estado:</strong> <span style="color: ${estado === 'finalizada' ? '#28a745' : '#ffc107'};">${estado.charAt(0).toUpperCase() + estado.slice(1)}</span></div>
                    <div><strong>Fecha inicio:</strong> ${fechaInicio}</div>
                    <div><strong>Fecha fin:</strong> ${fechaFin || 'Pendiente'}</div>
                </div>
            </div>
            
            <!-- Footer -->
            <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666; font-size: 12px;">
                <p>Auto Motores - Sistema de Gestión de Órdenes de Trabajo</p>
                <p>Documento generado el ${currentDate} a las ${currentTime}</p>
            </div>
        </div>`;
    } else {
        // If no selection, show summary of all orders
        var table = document.querySelector('table');
        if (table) {
            var currentDate = new Date().toLocaleDateString('es-ES');
            var currentTime = new Date().toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});
            
            html = `
            <div style="max-width: 800px; margin: 0 auto; font-family: Arial, sans-serif; background: white;">
                <!-- Header -->
                <div style="text-align: center; padding: 20px 0; border-bottom: 2px solid #000; margin-bottom: 20px;">
                    <h1 style="font-size: 32px; font-weight: bold; margin: 0; color: #000;">Auto Motores</h1>
                    <h2 style="font-size: 24px; margin: 10px 0 0 0; color: #333;">Reporte de Órdenes de Trabajo</h2>
                </div>
                
                <!-- Date -->
                <div style="text-align: center; margin-bottom: 20px; font-size: 14px;">
                    <strong>${currentDate}, ${currentTime}</strong>
                </div>
                
                <!-- Table -->
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
                    <thead>
                        <tr style="background: #000; color: white;">`;
            
            var ths = table.querySelectorAll('thead th');
            for (var i = 0; i < ths.length - 1; i++) {
                html += `<th style="padding: 12px 8px; text-align: left; border-right: 1px solid #333;">${ths[i].textContent}</th>`;
            }
            
            html += `</tr></thead><tbody>`;
            
            var rows = table.querySelectorAll('tbody tr');
            rows.forEach(function(r, index) {
                var tds = r.querySelectorAll('td');
                var bgColor = index % 2 === 0 ? '#f8f9fa' : 'white';
                html += `<tr style="background: ${bgColor};">`;
                for (var i = 0; i < tds.length - 1; i++) {
                    html += `<td style="padding: 10px 8px; border-bottom: 1px solid #ddd;">${tds[i].textContent}</td>`;
                }
                html += '</tr>';
            });
            
            html += `</tbody></table>
                
                <!-- Footer -->
                <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666; font-size: 12px;">
                    <p>Auto Motores - Sistema de Gestión de Órdenes de Trabajo</p>
                    <p>Reporte generado el ${currentDate} a las ${currentTime}</p>
                </div>
            </div>`;
        }
    }
    
    // Add print and close buttons
    html += `
    <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
        <button onclick="window.print()" style="background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 6px; font-size: 16px; margin-right: 10px; cursor: pointer;">Imprimir</button>
        <button onclick="cerrarVistaPrevia()" style="background: #6c757d; color: white; padding: 12px 24px; border: none; border-radius: 6px; font-size: 16px; cursor: pointer;">Cerrar</button>
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
