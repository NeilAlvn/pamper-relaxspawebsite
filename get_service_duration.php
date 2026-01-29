<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "pos_system");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'duration' => 1]);
    exit();
}

$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;

if ($service_id <= 0) {
    echo json_encode(['success' => false, 'duration' => 1]);
    exit();
}

$stmt = $conn->prepare("SELECT duration FROM services WHERE id = ?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'success' => true, 
        'duration' => (int)$row['duration']
    ]);
} else {
    echo json_encode(['success' => false, 'duration' => 1]);
}

$stmt->close();
$conn->close();
?>