<?php
// mecanicos.php
// Página para gestionar la lista de mecánicos.  Permite visualizar,
// filtrar, añadir, editar y eliminar registros según los permisos del
// usuario (solo el jefe de taller puede añadir/editar/eliminar; los
// mecánicos solo pueden consultar su propia lista de órdenes y el
// listado de repuestos, no gestionar la lista de mecánicos).

session_start();
// Comprobar que hay un usuario logueado
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php?usuario=jefe');
    exit;
}
$rol = $_SESSION['rol'];

// Solo permiten ingresar a esta página roles jefe_taller y mecanico
if (!in_array($rol, ['jefe_taller', 'mecanico'])) {
    header('Location: index.html');
    exit;
}

include 'conexion.php';

// Procesar eliminaciones si el rol lo permite
if ($rol === 'jefe_taller' && isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $stmt = $conn->prepare('DELETE FROM mecanicos WHERE id = :id');
    $stmt->execute([':id' => $id]);
    header('Location: mecanicos.php');
    exit;
}

// Procesar inserción o actualización
if ($rol === 'jefe_taller' && isset($_POST['guardar'])) {
    $nombre       = $_POST['nombre']       ?? '';
    $telefono     = $_POST['telefono']     ?? '';
    $direccion    = $_POST['direccion']    ?? '';
    $especialidad = $_POST['especialidad'] ?? '';
    $id = $_POST['id'] ?? '';

    if ($id) {
        // actualizar
        $stmt = $conn->prepare('UPDATE mecanicos SET nombre=:nombre, telefono=:telefono, direccion=:direccion, especialidad=:especialidad WHERE id=:id');
        $stmt->execute([
            ':nombre'       => $nombre,
            ':telefono'     => $telefono,
            ':direccion'    => $direccion,
            ':especialidad' => $especialidad,
            ':id'           => $id,
        ]);
    } else {
        // insertar
        $stmt = $conn->prepare('INSERT INTO mecanicos (nombre, telefono, direccion, especialidad) VALUES (:nombre,:telefono,:direccion,:especialidad)');
        $stmt->execute([
            ':nombre'       => $nombre,
            ':telefono'     => $telefono,
            ':direccion'    => $direccion,
            ':especialidad' => $especialidad,
        ]);
    }
    header('Location: mecanicos.php');
    exit;
}

// Obtenemos datos del registro que se desea editar (si corresponde)
$edicion = null;
if ($rol === 'jefe_taller' && isset($_GET['editar'])) {
    $idEdit = (int)$_GET['editar'];
    $stmt = $conn->prepare('SELECT * FROM mecanicos WHERE id = :id');
    $stmt->execute([':id' => $idEdit]);
    $edicion = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Parámetro de búsqueda y paginación
$busqueda = $_GET['buscar'] ?? '';
$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit    = 10;
$conditions = [];
$params     = [];

// Construir condiciones de búsqueda
if ($busqueda) {
    $conditions[] = '(nombre LIKE :buscar OR especialidad LIKE :buscar)';
    $params[':buscar'] = '%' . $busqueda . '%';
}

// Consulta para contar registros
$sqlCount = 'SELECT COUNT(*) FROM mecanicos';
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
// Ajustar página actual si es mayor que el total
if ($page > $totalPaginas) {
    $page = $totalPaginas;
}
$offset = ($page - 1) * $limit;

// Consulta principal con paginación
$sql = 'SELECT * FROM mecanicos';
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
$mecanicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mecánicos</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        /* Cabecera y botones */
        header {
            position: relative;
            display: flex;
            justify-content: space-between;
            /* Center header items vertically */
            align-items: center;
            background: #fff;
            padding: 10px 20px;
            border-bottom: 5px solid #000;
        }
        /* Horizontal layout for logo and title */
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
            /* Remove top margin to align with the username */
            margin-top: 0;
            margin-left: 10px;
        }

        /* Contenedor principal para las páginas de listado */
        .container {
            padding: 20px;
            max-width: 1200px;
            margin: 40px auto 0 auto;
        }
        /* Área de acciones: barra de búsqueda y botones */
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
            font-size: 26px; /* más grande */
            font-weight: bold;
            text-align: center;
            /* centrado horizontal y espacio inferior */
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
        /* Contenedor de tabla con fondo blanco y sombra */
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
        /* Formulario para alta/edición en modal */
        /* La superposición oscura detrás del modal */
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
        /* Contenedor del formulario dentro del modal */
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
    <h2 class="page-title">Mecánicos</h2>
    <!-- Barra de acciones: búsqueda y botón de añadir -->
    <div class="actions">
        <form method="get">
            <input type="text" name="buscar" placeholder="Buscar por nombre o especialidad" value="<?php echo htmlspecialchars($busqueda); ?>">
            <button type="submit" class="btn-small">Filtrar</button>
        </form>
        <?php if ($rol === 'jefe_taller'): ?>
            <!-- Mover botón a la derecha y cambiar a color oscuro con texto blanco -->
            <a href="mecanicos.php?nuevo=1" class="btn-small" style="background:#000; color:#fff; margin-left:auto;">Añadir</a>
        <?php endif; ?>
    </div>

    <?php
    // Mostrar formulario de edición o de alta si corresponde
    if ($rol === 'jefe_taller' && (isset($_GET['nuevo']) || $edicion)): ?>
    <div class="modal-overlay">
        <div class="modal-container">
            <h2><?php echo $edicion ? 'Editar mecánico' : 'Nuevo mecánico'; ?></h2>
            <form method="post" action="mecanicos.php">
                <input type="hidden" name="id" value="<?php echo $edicion['id'] ?? ''; ?>">
                <label>Nombre
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($edicion['nombre'] ?? ''); ?>" required>
                </label>
                <label>Teléfono
                    <input type="text" name="telefono" value="<?php echo htmlspecialchars($edicion['telefono'] ?? ''); ?>">
                </label>
                <label>Dirección
                    <input type="text" name="direccion" value="<?php echo htmlspecialchars($edicion['direccion'] ?? ''); ?>">
                </label>
                <label>Especialidad
                    <input type="text" name="especialidad" value="<?php echo htmlspecialchars($edicion['especialidad'] ?? ''); ?>">
                </label>
                <button type="submit" name="guardar" class="btn">Guardar</button>
                <a href="mecanicos.php" class="btn" style="background:#6c757d;">Cancelar</a>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabla de registros -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th>Especialidad</th>
                    <?php if ($rol === 'jefe_taller'): ?><th>Acciones</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($mecanicos as $m): ?>
                <tr>
                    <td><?php echo htmlspecialchars($m['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($m['telefono']); ?></td>
                    <td><?php echo htmlspecialchars($m['direccion']); ?></td>
                    <td><?php echo htmlspecialchars($m['especialidad']); ?></td>
                    <?php if ($rol === 'jefe_taller'): ?>
                    <td>
                        <a href="mecanicos.php?editar=<?php echo $m['id']; ?>" class="btn-small" style="background:#007bff;">Editar</a>
                        <a href="mecanicos.php?eliminar=<?php echo $m['id']; ?>" class="btn-small" style="background:#dc3545;" onclick="return confirm('¿Seguro que desea eliminar este mecánico?');">Eliminar</a>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- Navegación de páginas para la lista de mecánicos -->
    <?php if ($totalPaginas > 1): ?>
    <nav class="pagination" style="margin-top: 15px; display: flex; justify-content: center; gap:6px;">
        <?php
        // Construir los parámetros conservando la búsqueda
        $baseParams = $_GET;
        unset($baseParams['page'], $baseParams['nuevo'], $baseParams['editar'], $baseParams['eliminar']);
        for ($i = 1; $i <= $totalPaginas; $i++):
            $baseParams['page'] = $i;
            $url = 'mecanicos.php?' . http_build_query($baseParams);
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
