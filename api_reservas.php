<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de Laragon
$host = "127.0.0.1";
$user = "root";
$pass = ""; 
$db   = "digital_twin_db";

// Crear conexión
$conn = new mysqli($host, $user, $pass);

// Verificar conexión
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Conexión fallida: " . $conn->connect_error]));
}

// Crear Base de Datos si no existe
$conn->query("CREATE DATABASE IF NOT EXISTS $db");
$conn->select_db($db);

// Crear Tabla si no existe
$sqlTable = "CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zone VARCHAR(50) NOT NULL,
    reservation_date DATETIME NOT NULL,
    guests INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sqlTable);

// Recibir Datos
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data) {
    $zone = $conn->real_escape_string($data['zone']);
    $datetime = $conn->real_escape_string($data['datetime']);
    $guests = intval($data['guests']);

    $stmt = $conn->prepare("INSERT INTO reservations (zone, reservation_date, guests) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $zone, $datetime, $guests);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Reserva guardada en MySQL"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error al guardar: " . $stmt->error]);
    }
    $stmt->close();
} else {
    // Si es un GET, devolver las últimas 5 reservas
    $result = $conn->query("SELECT * FROM reservations ORDER BY created_at DESC LIMIT 5");
    $list = [];
    while($row = $result->fetch_assoc()) {
        $list[] = $row;
    }
    echo json_encode($list);
}

$conn->close();
?>
