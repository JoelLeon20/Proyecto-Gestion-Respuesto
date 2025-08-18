<?php
// API REST Testing Suite for Auto Motores
// This file provides basic API endpoints for testing with Postman

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../conexion.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '/';

try {
    switch ($path) {
        case '/mecanicos':
            handleMecanicos($method, $conn);
            break;
        case '/ordenes':
            handleOrdenes($method, $conn);
            break;
        case '/repuestos':
            handleRepuestos($method, $conn);
            break;
        case '/reportes':
            handleReportes($method, $conn);
            break;
        case '/health':
            echo json_encode(['status' => 'OK', 'timestamp' => date('Y-m-d H:i:s')]);
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleMecanicos($method, $conn) {
    switch ($method) {
        case 'GET':
            $stmt = $conn->query('SELECT * FROM mecanicos ORDER BY nombre');
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $conn->prepare('INSERT INTO mecanicos (nombre, telefono, direccion, especialidad) VALUES (?, ?, ?, ?)');
            $stmt->execute([$data['nombre'], $data['telefono'], $data['direccion'], $data['especialidad']]);
            echo json_encode(['id' => $conn->lastInsertId(), 'message' => 'Mecánico creado']);
            break;
    }
}

function handleOrdenes($method, $conn) {
    switch ($method) {
        case 'GET':
            $stmt = $conn->query('SELECT * FROM ordenes ORDER BY fecha_inicio DESC');
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $conn->prepare('INSERT INTO ordenes (mecanico, descripcion, fecha_inicio, estado) VALUES (?, ?, ?, ?)');
            $stmt->execute([$data['mecanico'], $data['descripcion'], $data['fecha_inicio'], 'pendiente']);
            echo json_encode(['id' => $conn->lastInsertId(), 'message' => 'Orden creada']);
            break;
    }
}

function handleRepuestos($method, $conn) {
    switch ($method) {
        case 'GET':
            $stmt = $conn->query('SELECT * FROM repuestos ORDER BY nombre');
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
    }
}

function handleReportes($method, $conn) {
    switch ($method) {
        case 'GET':
            $stmt = $conn->query('SELECT * FROM reportes ORDER BY fecha DESC');
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
    }
}
?>
