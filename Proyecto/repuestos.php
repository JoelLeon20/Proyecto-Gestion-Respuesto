<?php
// repuestos.php
// Gestión de repuestos del taller.  Permite ver el inventario y, si el
// usuario es jefe de taller, añadir, editar y eliminar repuestos.
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php?usuario=jefe');
    exit;
}
$rol = $_SESSION['rol'];

// Cualquier rol puede consultar la lista de repuestos; solo el jefe
// puede modificarlos.
include 'conexion.php';

// Eliminar repuesto
if ($rol === 'jefe_taller' && isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $stmt = $conn->prepare('DELETE FROM repuestos WHERE id=:id');
    $stmt->execute([':id' => $id]);
    header('Location: repuestos.php');
    exit;
}

// Insertar o actualizar repuesto
if ($rol === 'jefe_taller' && isset($_POST['guardar'])) {
    $nombre      = $_POST['nombre']      ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $stock       = (int)($_POST['stock'] ?? 0);
    $id          = $_POST['id'] ?? '';
    if ($id) {
        $stmt = $conn->prepare('UPDATE repuestos SET nombre=:nombre, descripcion=:descripcion, stock=:stock WHERE id=:id');
        $stmt->execute([
            ':nombre'      => $nombre,
            ':descripcion' => $descripcion,
            ':stock'       => $stock,
            ':id'          => $id,
        ]);
    } else {
        $stmt = $conn->prepare('INSERT INTO repuestos (nombre, descripcion, stock) VALUES (:nombre,:descripcion,:stock)');
        $stmt->execute([
            ':nombre'      => $nombre,
            ':descripcion' => $descripcion,
            ':stock'       => $stock,
        ]);
    }
    header('Location: repuestos.php');
    exit;
}

// Obtener repuesto a editar
$editar = null;
if ($rol === 'jefe_taller' && isset($_GET['editar'])) {
    $idEdit = (int)$_GET['editar'];
    $stmt = $conn->prepare('SELECT * FROM repuestos WHERE id=:id');
    $stmt->execute([':id' => $idEdit]);
    $editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Filtro de búsqueda y paginación para la lista de repuestos
$busqueda = $_GET['buscar'] ?? '';
$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit    = 10;
$conditions = [];
$params     = [];

// Construir condiciones de búsqueda si corresponde
if ($busqueda) {
    $conditions[] = '(nombre LIKE :buscar OR descripcion LIKE :buscar)';
    $params[':buscar'] = '%' . $busqueda . '%';
}

// Consulta para contar registros (paginación)
$sqlCount = 'SELECT COUNT(*) FROM repuestos';
if ($conditions) {
    $sqlCount .= ' WHERE ' . implode(' AND ', $conditions);
}
$stmtCount = $conn->prepare($sqlCount);
if ($busqueda) {
    $stmtCount->bindValue(':buscar', '%' . $busqueda . '%');
}
$stmtCount->execute();
$totalRegistros = (int)$stmtCount->fetchColumn();
$totalPaginas   = ($totalRegistros > 0) ? (int)ceil($totalRegistros / $limit) : 1;
if ($page > $totalPaginas) {
    $page = $totalPaginas;
}
$offset = ($page - 1) * $limit;

// Consulta principal con filtros y paginación
$sql = 'SELECT * FROM repuestos';
if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY nombre ASC LIMIT :limit OFFSET :offset';
$stmt = $conn->prepare($sql);
if ($busqueda) {
    $stmt->bindValue(':buscar', '%' . $busqueda . '%');
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$repuestos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Repuestos</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        /* Cabecera y botones */
        header {
            position: relative;
            display: flex;
            justify-content: space-between;
            /* Center items vertically so the logout button aligns with the title */
            align-items: center;
            background: #fff;
            padding: 10px 20px;
            border-bottom: 5px solid #000;
        }
        /* Lay the logo and title out in a row and center them vertically */
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
            /* Remove top margin so the button aligns with the username */
            margin-top: 0;
            /* Add some left spacing from the username */
            margin-left: 10px;
        }

        .container {
            padding: 20px;
            max-width: 1200px;
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
            min-width: 600px;
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

        /* Modal overlay and container for add/edit forms */
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
    <!-- Cabecera rediseñada: logo y título arriba con botón de volver debajo -->
    <div class="header-left">
        <div class="logo-title">
            <img src="img/logo_auto_motores.png" alt="Logo">
            <h1>AUTO MOTORES</h1>
        </div>
        <a href="<?php echo ($rol === 'jefe_taller') ? 'jefe_taller.php' : 'mecanico.php'; ?>" class="btn-back">← Volver</a>
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
    <h2 class="page-title">Repuestos</h2>
    <div class="actions">
        <form method="get">
            <input type="text" name="buscar" placeholder="Buscar repuesto" value="<?php echo htmlspecialchars($busqueda); ?>">
            <button type="submit" class="btn-small">Filtrar</button>
        </form>
        <?php if ($rol === 'jefe_taller'): ?>
            <!-- Mover botón a la derecha y cambiar a color oscuro con texto blanco -->
            <a href="repuestos.php?nuevo=1" class="btn-small" style="background:#000; color:#fff; margin-left:auto;">Añadir</a>
        <?php endif; ?>
    </div>

    <?php if ($rol === 'jefe_taller' && (isset($_GET['nuevo']) || $editar)): ?>
    <div class="modal-overlay">
        <div class="modal-container">
            <h2><?php echo $editar ? 'Editar repuesto' : 'Nuevo repuesto'; ?></h2>
            <form method="post" action="repuestos.php">
                <input type="hidden" name="id" value="<?php echo $editar['id'] ?? ''; ?>">
                <label>Nombre
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($editar['nombre'] ?? ''); ?>" required>
                </label>
                <label>Descripción
                    <textarea name="descripcion"><?php echo htmlspecialchars($editar['descripcion'] ?? ''); ?></textarea>
                </label>
                <label>Stock
                    <input type="number" name="stock" min="0" value="<?php echo htmlspecialchars($editar['stock'] ?? 0); ?>">
                </label>
                <button type="submit" name="guardar" class="btn">Guardar</button>
                <a href="repuestos.php" class="btn" style="background:#6c757d;">Cancelar</a>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Stock</th>
                    <?php if ($rol === 'jefe_taller'): ?><th>Acciones</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($repuestos as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($r['descripcion']); ?></td>
                    <td><?php echo htmlspecialchars($r['stock']); ?></td>
                    <?php if ($rol === 'jefe_taller'): ?>
                    <td>
                        <a href="repuestos.php?editar=<?php echo $r['id']; ?>" class="btn-small" style="background:#007bff;">Editar</a>
                        <a href="repuestos.php?eliminar=<?php echo $r['id']; ?>" class="btn-small" style="background:#dc3545;" onclick="return confirm('¿Seguro que desea eliminar este repuesto?');">Eliminar</a>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- Navegación de páginas para la lista de repuestos -->
    <?php if ($totalPaginas > 1): ?>
    <nav class="pagination" style="margin-top: 15px; display: flex; justify-content: center; gap:6px;">
        <?php
        $baseParams = $_GET;
        unset($baseParams['page'], $baseParams['nuevo'], $baseParams['editar'], $baseParams['eliminar']);
        for ($i = 1; $i <= $totalPaginas; $i++):
            $baseParams['page'] = $i;
            $url = 'repuestos.php?' . http_build_query($baseParams);
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
