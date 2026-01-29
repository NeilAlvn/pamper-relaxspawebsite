<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "pos_system");
if ($conn->connect_error) {
    echo json_encode(['bookings' => []]);
    exit();
}

$date = $_GET['date'] ?? '';

if (empty($date)) {
    echo json_encode(['bookings' => []]);
    exit();
}

// Get all appointments for this date with service details (excluding rejected)
$stmt = $conn->prepare("
    SELECT a.name, a.email, a.time, a.status, s.name as service, s.duration
    FROM appointments a
    LEFT JOIN services s ON a.service = s.id
    WHERE a.date = ? AND a.status != 'rejected'
    ORDER BY a.time ASC
");
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
$blocked_hours = []; // Track all blocked hours

while ($row = $result->fetch_assoc()) {
    $appointment = [
        'name' => $row['name'],
        'email' => $row['email'],
        'time' => $row['time'],
        'status' => $row['status'],
        'service' => $row['service'],
        'duration' => (int)$row['duration'],
        'is_start' => true // Mark this as the starting hour
    ];
    
    $bookings[] = $appointment;
    
    // Calculate all hours this appointment blocks
    $start_hour = (int)substr($row['time'], 0, 2);
    $duration = (int)$row['duration'];
    
    for ($i = 0; $i < $duration; $i++) {
        $hour = $start_hour + $i;
        if ($hour >= 8 && $hour <= 17) {
            $blocked_hours[$hour] = $appointment;
        }
    }
}

// Now create a complete timeline showing all 10 hours (8 AM to 5 PM)
$timeline = [];
for ($hour = 8; $hour <= 17; $hour++) {
    $time_string = sprintf("%02d:00:00", $hour);
    
    if (isset($blocked_hours[$hour])) {
        $booking = $blocked_hours[$hour];
        $timeline[] = [
            'time' => $time_string,
            'name' => $booking['name'],
            'email' => $booking['email'],
            'service' => $booking['service'],
            'status' => $booking['status'],
            'duration' => $booking['duration'],
            'is_start' => ($time_string === $booking['time']), // Only true for starting hour
            'booked' => true
        ];
    } else {
        $timeline[] = [
            'time' => $time_string,
            'booked' => false
        ];
    }
}

echo json_encode([
    'bookings' => $bookings, // Original appointments list
    'timeline' => $timeline  // Complete hour-by-hour timeline
]);

$conn->close();
?>