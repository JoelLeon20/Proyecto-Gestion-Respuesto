<?php
// reportes.php
// Módulo de reportes financieros.  Solo el jefe de taller puede acceder y
// gestionar reportes.  Puede crear, editar y eliminar reportes.
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'jefe_taller') {
    header('Location: index.html');
    exit;
}
include 'conexion.php';

// Procesar guardar (nuevo o editar)
if (isset($_POST['guardar'])) {
    $id          = $_POST['id'] ?? '';
    $titulo      = $_POST['titulo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $fecha       = $_POST['fecha'] ?? '';
    $monto       = (float)($_POST['monto'] ?? 0);
    if ($id) {
        $stmt = $conn->prepare('UPDATE reportes SET titulo=:titulo, descripcion=:descripcion, fecha=:fecha, monto=:monto WHERE id=:id');
        $stmt->execute([
            ':titulo'      => $titulo,
            ':descripcion' => $descripcion,
            ':fecha'       => $fecha,
            ':monto'       => $monto,
            ':id'          => $id,
        ]);
    } else {
        $stmt = $conn->prepare('INSERT INTO reportes (titulo, descripcion, fecha, monto) VALUES (:titulo,:descripcion,:fecha,:monto)');
        $stmt->execute([
            ':titulo'      => $titulo,
            ':descripcion' => $descripcion,
            ':fecha'       => $fecha,
            ':monto'       => $monto,
        ]);
    }
    header('Location: reportes.php');
    exit;
}

// Eliminar reporte
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $stmt = $conn->prepare('DELETE FROM reportes WHERE id=:id');
    $stmt->execute([':id' => $id]);
    header('Location: reportes.php');
    exit;
}

// Obtener para edición
$editar = null;
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $stmt = $conn->prepare('SELECT * FROM reportes WHERE id=:id');
    $stmt->execute([':id' => $id]);
    $editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Obtener reportes con paginación
$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;

$totalRegistros = (int)$conn->query('SELECT COUNT(*) FROM reportes')->fetchColumn();
$totalPaginas   = ($totalRegistros > 0) ? (int)ceil($totalRegistros / $limit) : 1;
if ($page > $totalPaginas) {
    $page = $totalPaginas;
}
$offset = ($page - 1) * $limit;

$stmt = $conn->prepare('SELECT * FROM reportes ORDER BY fecha DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        /* Cabecera y botones */
        header {
            position: relative;
            display: flex;
            justify-content: space-between;
            /* Align header items vertically */
            align-items: center;
            background: #fff;
            padding: 10px 20px;
            border-bottom: 5px solid #000;
        }
        /* Lay the logo and title on a single row, center aligned */
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
            /* Remove top margin so the button aligns with the username */
            margin-top: 0;
            /* Add a little space to the left so it doesn’t touch the username */
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
        .form-container input[type="number"],
        .form-container textarea {
            width: 100%;
            padding: 8px;
            margin-top: 4px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }
        .form-container textarea {
            height: 80px;
            resize: vertical;
        }
        .form-container button,
        .form-container a.btn {
            margin-top: 16px;
            padding: 8px 14px;
            font-size: 14px;
            border-radius: 6px;
        }

        /* Modal overlay and container for reports */
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
        .modal-container input[type="number"],
        .modal-container textarea {
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
    <!-- Cabecera rediseñada para reportes: botón volver debajo del título -->
    <div class="header-left">
        <div class="logo-title">
            <img src="img/logo_auto_motores.png" alt="Logo">
            <h1>AUTO MOTORES</h1>
        </div>
        <a href="jefe_taller.php" class="btn-back">← Volver</a>
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
    <h2 class="page-title">Reportes</h2>
    <?php if (isset($_GET['nuevo']) || $editar): ?>
    <div class="modal-overlay">
        <div class="modal-container">
            <h2><?php echo $editar ? 'Editar reporte' : 'Nuevo reporte'; ?></h2>
            <form method="post" action="reportes.php">
                <input type="hidden" name="id" value="<?php echo $editar['id'] ?? ''; ?>">
                <label>Título
                    <input type="text" name="titulo" value="<?php echo htmlspecialchars($editar['titulo'] ?? ''); ?>" required>
                </label>
                <label>Descripción
                    <textarea name="descripcion" required><?php echo htmlspecialchars($editar['descripcion'] ?? ''); ?></textarea>
                </label>
                <label>Fecha
                    <input type="date" name="fecha" value="<?php echo htmlspecialchars($editar['fecha'] ?? date('Y-m-d')); ?>" required>
                </label>
                <label>Monto
                    <input type="number" step="0.01" name="monto" value="<?php echo htmlspecialchars($editar['monto'] ?? 0); ?>" required>
                </label>
                <button type="submit" name="guardar" class="btn">Guardar</button>
                <a href="reportes.php" class="btn" style="background:#6c757d;">Cancelar</a>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Contenedor de botones alineado a la derecha. El orden se muestra como: Imprimir (izq), Nuevo reporte (der) -->
    <div style="display:flex; justify-content:flex-end; gap:8px; margin-bottom:10px;">
        <a href="#" class="btn-small" style="background:#6c757d; color:#fff;" onclick="return mostrarVistaPreviaImpresion();">Imprimir</a>
        <a href="reportes.php?nuevo=1" class="btn-small" style="background:#000; color:#fff;">Nuevo reporte</a>
    </div>

    <div class="table-container">
        <table>
            <thead>
            <tr>
                <th>Título</th>
                <th>Descripción</th>
                <th>Fecha</th>
                <th>Monto</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($reportes as $r): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['titulo']); ?></td>
                <td><?php echo htmlspecialchars($r['descripcion']); ?></td>
                <td><?php echo htmlspecialchars($r['fecha']); ?></td>
                <td><?php echo htmlspecialchars(number_format($r['monto'],2)); ?></td>
                <td>
                    <a href="reportes.php?editar=<?php echo $r['id']; ?>" class="btn-small" style="background:#007bff;">Editar</a>
                    <a href="reportes.php?eliminar=<?php echo $r['id']; ?>" class="btn-small" style="background:#dc3545;" onclick="return confirm('¿Desea eliminar este reporte?');">Eliminar</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- Navegación de páginas para reportes -->
    <?php if ($totalPaginas > 1): ?>
    <nav class="pagination" style="margin-top: 15px; display:flex; justify-content:center; gap:6px;">
        <?php
        $baseParams = $_GET;
        unset($baseParams['page'], $baseParams['nuevo'], $baseParams['editar'], $baseParams['eliminar']);
        for ($i = 1; $i <= $totalPaginas; $i++):
            $baseParams['page'] = $i;
            $url = 'reportes.php?' . http_build_query($baseParams);
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
<div id="print-preview-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:2000;">
    <div id="print-preview-content" style="background:#fff; padding:20px; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.3); width:90%; max-width:800px; max-height:90%; overflow:auto; position:relative;"></div>
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
    
    var html = `
        <div style="max-width: 800px; margin: 0 auto; font-family: Arial, sans-serif;">
            <div style="text-align: center; margin-bottom: 30px; padding: 20px; border-bottom: 3px solid #000;">
                <h1 style="margin: 0; font-size: 32px; font-weight: bold; color: #000;">AUTO MOTORES</h1>
                <p style="margin: 5px 0 0 0; font-size: 16px; color: #666;">Sistema de Gestión Automotriz</p>
            </div>
            
            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="margin: 0; font-size: 24px; color: #000;">REPORTE FINANCIERO</h2>
                <p style="margin: 10px 0 0 0; font-size: 14px; color: #666;">Fecha de generación: ${new Date().toLocaleDateString('es-ES')}</p>
            </div>`;
    
    if (selected) {
        // Single report selected
        var cells = selected.querySelectorAll('td');
        html += `
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h3 style="margin: 0 0 15px 0; color: #000; font-size: 18px;">Detalle del Reporte</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div><strong>Título:</strong> ${cells[0].textContent}</div>
                    <div><strong>Fecha:</strong> ${cells[2].textContent}</div>
                    <div style="grid-column: 1 / -1;"><strong>Descripción:</strong> ${cells[1].textContent}</div>
                    <div style="grid-column: 1 / -1; font-size: 18px; color: #000;"><strong>Monto: $${cells[3].textContent}</strong></div>
                </div>
            </div>`;
    } else {
        // All reports
        var rows = document.querySelectorAll('table tbody tr');
        var totalMonto = 0;
        
        html += `
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h3 style="margin: 0 0 15px 0; color: #000; font-size: 18px;">Resumen de Reportes</h3>
                <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                    <thead>
                        <tr style="background: #000; color: #fff;">
                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Título</th>
                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Descripción</th>
                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Fecha</th>
                            <th style="padding: 12px; text-align: right; border: 1px solid #ddd;">Monto</th>
                        </tr>
                    </thead>
                    <tbody>`;
        
        rows.forEach(function(row) {
            var cells = row.querySelectorAll('td');
            if (cells.length >= 4) {
                var monto = parseFloat(cells[3].textContent.replace(/[,$]/g, '')) || 0;
                totalMonto += monto;
                html += `
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;">${cells[0].textContent}</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">${cells[1].textContent}</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">${cells[2].textContent}</td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: right;">$${cells[3].textContent}</td>
                    </tr>`;
            }
        });
        
        html += `
                    </tbody>
                    <tfoot>
                        <tr style="background: #f8f9fa; font-weight: bold;">
                            <td colspan="3" style="padding: 12px; border: 1px solid #ddd; text-align: right;">TOTAL:</td>
                            <td style="padding: 12px; border: 1px solid #ddd; text-align: right; font-size: 16px;">$${totalMonto.toFixed(2)}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>`;
    }
    
    html += `
            <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666; font-size: 12px;">
                <p style="margin: 0;">Auto Motores - Sistema de Gestión Automotriz</p>
                <p style="margin: 5px 0 0 0;">Reporte generado el ${new Date().toLocaleString('es-ES')}</p>
            </div>
            
            <div style="text-align: center; margin-top: 30px; gap: 10px; display: flex; justify-content: center;">
                <button onclick="imprimirReporte()" style="background: #000; color: #fff; padding: 12px 20px; border: none; border-radius: 6px; font-size: 14px; cursor: pointer;">Imprimir</button>
                <button onclick="cerrarVistaPrevia()" style="background: #6c757d; color: #fff; padding: 12px 20px; border: none; border-radius: 6px; font-size: 14px; cursor: pointer;">Cerrar</button>
            </div>
        </div>`;
    
    contentDiv.innerHTML = html;
    modal.style.display = 'flex';
    return false;
}

function imprimirReporte() {
    window.print();
}

function cerrarVistaPrevia() {
    var modal = document.getElementById('print-preview-modal');
    if (modal) modal.style.display = 'none';
}
</script>
</body>
</html>
