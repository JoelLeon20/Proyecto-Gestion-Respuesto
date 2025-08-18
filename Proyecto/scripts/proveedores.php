<?php
// proveedores.php
// Gestión de proveedores.  Solo el jefe de taller puede acceder y
// modificar la información de los proveedores.  La interfaz permite
// listar, filtrar, añadir, editar y eliminar registros.
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'jefe_taller') {
    // Solo el jefe de taller puede gestionar proveedores
    header('Location: index.html');
    exit;
}
$rol = $_SESSION['rol'];
include 'conexion.php';

// Eliminar proveedor
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $stmt = $conn->prepare('DELETE FROM proveedores WHERE id=:id');
    $stmt->execute([':id' => $id]);
    header('Location: proveedores.php');
    exit;
}

// Insertar o actualizar proveedor
if (isset($_POST['guardar'])) {
    $id          = $_POST['id'] ?? '';
    $ruc         = $_POST['ruc'] ?? '';
    $razon       = $_POST['razon_social'] ?? '';
    $telefono    = $_POST['telefono'] ?? '';
    $correo      = $_POST['correo'] ?? '';
    $direccion   = $_POST['direccion'] ?? '';
    if ($id) {
        $stmt = $conn->prepare('UPDATE proveedores SET ruc=:ruc, razon_social=:razon, telefono=:telefono, correo=:correo, direccion=:direccion WHERE id=:id');
        $stmt->execute([
            ':ruc'       => $ruc,
            ':razon'     => $razon,
            ':telefono'  => $telefono,
            ':correo'    => $correo,
            ':direccion' => $direccion,
            ':id'        => $id,
        ]);
    } else {
        $stmt = $conn->prepare('INSERT INTO proveedores (ruc, razon_social, telefono, correo, direccion) VALUES (:ruc,:razon,:telefono,:correo,:direccion)');
        $stmt->execute([
            ':ruc'       => $ruc,
            ':razon'     => $razon,
            ':telefono'  => $telefono,
            ':correo'    => $correo,
            ':direccion' => $direccion,
        ]);
    }
    header('Location: proveedores.php');
    exit;
}

// Datos para edición
$editar = null;
if (isset($_GET['editar'])) {
    $idEdit = (int)$_GET['editar'];
    $stmt = $conn->prepare('SELECT * FROM proveedores WHERE id=:id');
    $stmt->execute([':id' => $idEdit]);
    $editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Filtro de búsqueda y paginación
$busqueda = $_GET['buscar'] ?? '';
$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit    = 10;
$conditions = [];
$params     = [];

if ($busqueda) {
    $conditions[] = '(ruc LIKE :b OR razon_social LIKE :b)';
    $params[':b'] = '%' . $busqueda . '%';
}

// Consulta para contar total
$sqlCount = 'SELECT COUNT(*) FROM proveedores';
if ($conditions) {
    $sqlCount .= ' WHERE ' . implode(' AND ', $conditions);
}
$stmtCount = $conn->prepare($sqlCount);
if ($busqueda) {
    $stmtCount->bindValue(':b', '%' . $busqueda . '%');
}
$stmtCount->execute();
$totalRegistros = (int)$stmtCount->fetchColumn();
$totalPaginas   = ($totalRegistros > 0) ? (int)ceil($totalRegistros / $limit) : 1;
if ($page > $totalPaginas) {
    $page = $totalPaginas;
}
$offset = ($page - 1) * $limit;

// Consulta principal con paginación
$sql = 'SELECT * FROM proveedores';
if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY razon_social ASC LIMIT :limit OFFSET :offset';
$stmt = $conn->prepare($sql);
if ($busqueda) {
    $stmt->bindValue(':b', '%' . $busqueda . '%');
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Proveedores</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        /* Cabecera y botones */
        header {
            position: relative;
            display: flex;
            justify-content: space-between;
            /* Align header content vertically */
            align-items: center;
            background: #fff;
            padding: 10px 20px;
            border-bottom: 5px solid #000;
        }
        /* Place logo and title on one row and center vertically */
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
        .actions input[type="text"] {
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
        .form-container input[type="text"] {
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

        /* Modal overlay and container for provider forms */
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
        .modal-container input[type="text"] {
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
    <!-- Cabecera rediseñada para gestión de proveedores -->
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
    <h2 class="page-title">Proveedores</h2>
    <div class="actions">
        <form method="get">
            <input type="text" name="buscar" placeholder="Buscar por RUC o Razón Social" value="<?php echo htmlspecialchars($busqueda); ?>">
            <button type="submit" class="btn-small">Filtrar</button>
        </form>
        <!-- Mover botón a la derecha y cambiar a color oscuro con texto blanco -->
        <a href="proveedores.php?nuevo=1" class="btn-small" style="background:#000; color:#fff; margin-left:auto;">Añadir</a>
    </div>

    <?php if (isset($_GET['nuevo']) || $editar): ?>
    <div class="modal-overlay">
        <div class="modal-container">
            <h2><?php echo $editar ? 'Editar proveedor' : 'Nuevo proveedor'; ?></h2>
            <form method="post" action="proveedores.php">
                <input type="hidden" name="id" value="<?php echo $editar['id'] ?? ''; ?>">
                <label>RUC
                    <input type="text" name="ruc" value="<?php echo htmlspecialchars($editar['ruc'] ?? ''); ?>" required>
                </label>
                <label>Razón Social
                    <input type="text" name="razon_social" value="<?php echo htmlspecialchars($editar['razon_social'] ?? ''); ?>" required>
                </label>
                <label>Teléfono
                    <input type="text" name="telefono" value="<?php echo htmlspecialchars($editar['telefono'] ?? ''); ?>">
                </label>
                <label>Correo
                    <input type="text" name="correo" value="<?php echo htmlspecialchars($editar['correo'] ?? ''); ?>">
                </label>
                <label>Dirección
                    <input type="text" name="direccion" value="<?php echo htmlspecialchars($editar['direccion'] ?? ''); ?>">
                </label>
                <button type="submit" name="guardar" class="btn">Guardar</button>
                <a href="proveedores.php" class="btn" style="background:#6c757d;">Cancelar</a>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="table-container">
        <table>
            <thead>
            <tr>
                <th>RUC</th>
                <th>Razón Social</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>Dirección</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($proveedores as $p): ?>
            <tr>
                <td><?php echo htmlspecialchars($p['ruc']); ?></td>
                <td><?php echo htmlspecialchars($p['razon_social']); ?></td>
                <td><?php echo htmlspecialchars($p['telefono']); ?></td>
                <td><?php echo htmlspecialchars($p['correo']); ?></td>
                <td><?php echo htmlspecialchars($p['direccion']); ?></td>
                <td>
                    <a href="proveedores.php?editar=<?php echo $p['id']; ?>" class="btn-small" style="background:#007bff;">Editar</a>
                    <a href="proveedores.php?eliminar=<?php echo $p['id']; ?>" class="btn-small" style="background:#dc3545;" onclick="return confirm('¿Desea eliminar este proveedor?');">Eliminar</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- Navegación de páginas para proveedores -->
    <?php if ($totalPaginas > 1): ?>
    <nav class="pagination" style="margin-top: 15px; display: flex; justify-content: center; gap:6px;">
        <?php
        $baseParams = $_GET;
        unset($baseParams['page'], $baseParams['nuevo'], $baseParams['editar'], $baseParams['eliminar']);
        for ($i = 1; $i <= $totalPaginas; $i++):
            $baseParams['page'] = $i;
            $url = 'proveedores.php?' . http_build_query($baseParams);
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
