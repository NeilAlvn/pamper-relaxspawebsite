<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "pos_system");
if ($conn->connect_error) {
    echo json_encode(['booked_times' => [], 'error' => 'Connection failed']);
    exit();
}

$date = $_GET['date'] ?? '';

if (empty($date)) {
    echo json_encode(['booked_times' => [], 'error' => 'No date provided']);
    exit();
}

// FIXED: Use service column (the one with actual data)
$stmt = $conn->prepare("
    SELECT a.time, s.duration, s.name as service_name, a.name as customer_name
    FROM appointments a
    JOIN services s ON a.service = s.id
    WHERE a.date = ? AND a.status != 'rejected'
");
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

$booked_times = [];
$debug_info = []; // For debugging

while ($row = $result->fetch_assoc()) {
    $start_time = $row['time']; // e.g., "08:00:00"
    $duration = (int)($row['duration'] ?? 1); // Duration in hours, default 1
    
    // Extract the starting hour
    $start_hour = (int)substr($start_time, 0, 2);
    
    // Store debug info
    $blocked_hours = [];
    
    // Block all time slots for the duration of this appointment
    for ($i = 0; $i < $duration; $i++) {
        $blocked_hour = $start_hour + $i;
        
        // Only block times within business hours (8 AM to 5 PM)
        if ($blocked_hour >= 8 && $blocked_hour <= 17) {
            $blocked_time = sprintf("%02d:00:00", $blocked_hour);
            $booked_times[] = $blocked_time;
            $blocked_hours[] = $blocked_time;
        }
    }
    
    $debug_info[] = [
        'customer' => $row['customer_name'],
        'service' => $row['service_name'],
        'start' => $start_time,
        'duration' => $duration . ' hours',
        'blocks' => $blocked_hours
    ];
}

// Remove duplicates (in case of overlapping appointments)
$booked_times = array_unique($booked_times);

// Re-index array to ensure proper JSON encoding
$booked_times = array_values($booked_times);

// Sort times
sort($booked_times);

$response = [
    'booked_times' => $booked_times,
    'date' => $date,
    'total_appointments' => count($debug_info),
    'debug' => $debug_info
];

echo json_encode($response);

$conn->close();
?>