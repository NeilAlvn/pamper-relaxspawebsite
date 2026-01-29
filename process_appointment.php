<?php
header('Content-Type: application/json');

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // If using Composer
// OR
// require 'path/to/PHPMailer/src/Exception.php';
// require 'path/to/PHPMailer/src/PHPMailer.php';
// require 'path/to/PHPMailer/src/SMTP.php';

$conn = new mysqli("localhost", "root", "", "pos_system");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $service_id = intval($_POST['service']);
    $message = trim($_POST['message']);
    $image = null;

    // Validate required fields
    if (empty($name) || empty($email) || empty($phone) || empty($date) || empty($time) || empty($service_id)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit();
    }

    // Fetch service details including duration
    $service_query = $conn->prepare("SELECT name, price, duration FROM services WHERE id = ?");
    $service_query->bind_param("i", $service_id);
    $service_query->execute();
    $service_result = $service_query->get_result();
    $service_fee = 0;
    $service_name = '';
    $selected_duration = 1; // Default 1 hour
    
    if ($service_result->num_rows > 0) {
        $service_data = $service_result->fetch_assoc();
        $service_fee = $service_data['price'];
        $service_name = $service_data['name'];
        $selected_duration = (int)$service_data['duration'];
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid service selected']);
        exit();
    }

    // CHECK AVAILABILITY - Consider duration-based conflicts
    $selected_hour = (int)substr($time, 0, 2);
    $selected_end_hour = $selected_hour + $selected_duration;
    
    // Get all appointments for the selected date (excluding rejected)
    $check_stmt = $conn->prepare("
        SELECT a.time, s.duration 
        FROM appointments a
        JOIN services s ON a.service = s.id
        WHERE a.date = ? AND a.status != 'rejected'
    ");
    $check_stmt->bind_param("s", $date);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    $is_available = true;
    while ($row = $check_result->fetch_assoc()) {
        $booked_start_hour = (int)substr($row['time'], 0, 2);
        $booked_duration = (int)$row['duration'];
        $booked_end_hour = $booked_start_hour + $booked_duration;
        
        // Check for time overlap
        // Overlap occurs if:
        // 1. Selected start time is within an existing appointment
        // 2. Selected end time is within an existing appointment
        // 3. Selected appointment completely covers an existing appointment
        if (($selected_hour >= $booked_start_hour && $selected_hour < $booked_end_hour) ||
            ($selected_end_hour > $booked_start_hour && $selected_end_hour <= $booked_end_hour) ||
            ($selected_hour <= $booked_start_hour && $selected_end_hour >= $booked_end_hour)) {
            $is_available = false;
            break;
        }
    }
    $check_stmt->close();
    
    if (!$is_available) {
        echo json_encode([
            'success' => false, 
            'message' => 'This time slot is not available for a ' . $selected_duration . '-hour service. Please select another time.'
        ]);
        exit();
    }

    // Handle image upload
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['attachment']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($filetype, $allowed)) {
            $upload_dir = 'uploads/payments/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = uniqid() . '.' . $filetype;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $destination)) {
                $image = $destination;
            }
        }
    }

    // Insert appointment
    $stmt = $conn->prepare("INSERT INTO appointments (name, email, phone, date, time, service, service_fee, message, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("sssssiiss", $name, $email, $phone, $date, $time, $service_id, $service_fee, $message, $image);
    
    if ($stmt->execute()) {
        // Send email confirmation
        $mail = new PHPMailer(true);
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // Gmail SMTP server
            $mail->SMTPAuth   = true;
            $mail->Username   = 'joshuapantas.devs@gmail.com'; // Your Gmail address
            $mail->Password   = 'kiyl ketg anes kwrk'; // Your Gmail App Password (NOT regular password)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('pamperandrelax@gmail.com', 'Pamper & Relax Spa');
            $mail->addAddress($email, $name); // Customer email
            $mail->addReplyTo('pamperandrelax@gmail.com', 'Pamper & Relax Spa');
            
            // Optional: Send copy to spa
            $mail->addBCC('pamperandrelax@gmail.com');

            // Attach payment proof if uploaded
            if ($image) {
                $mail->addAttachment($image);
            }

            // Format duration display
            $duration_text = $selected_duration == 1 ? '1 hour' : $selected_duration . ' hours';

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Appointment Confirmation - Pamper & Relax Spa';
            
            $emailBody = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #0e3f37; color: white; padding: 20px; text-align: center; }
                    .content { background-color: #f8f5ef; padding: 20px; }
                    .details { background-color: white; padding: 15px; margin: 15px 0; border-left: 4px solid #d4b26a; }
                    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                    .button { background-color: #d4b26a; color: white; padding: 10px 20px; text-decoration: none; display: inline-block; margin: 10px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Appointment Confirmation</h1>
                    </div>
                    <div class='content'>
                        <p>Dear {$name},</p>
                        <p>Thank you for booking an appointment with <strong>Pamper & Relax Spa</strong>. We're excited to pamper you!</p>
                        
                        <div class='details'>
                            <h3>Appointment Details:</h3>
                            <p><strong>Service:</strong> {$service_name}</p>
                            <p><strong>Duration:</strong> {$duration_text}</p>
                            <p><strong>Date:</strong> {$date}</p>
                            <p><strong>Time:</strong> {$time}</p>
                            <p><strong>Service Fee:</strong> ₱{$service_fee}</p>
                            <p><strong>Phone:</strong> {$phone}</p>
                            " . ($message ? "<p><strong>Message:</strong> {$message}</p>" : "") . "
                        </div>
                        
                        <p>Your appointment is currently <strong>pending confirmation</strong>. We will review your booking and send you a confirmation email shortly.</p>
                        
                        <p>If you have any questions, please don't hesitate to contact us.</p>
                        
                        <p>We look forward to seeing you!</p>
                    </div>
                    <div class='footer'>
                        <p>Pamper & Relax Spa<br>
                        Email: pamperandrelax@gmail.com<br>
                        This is an automated message, please do not reply directly to this email.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $mail->Body = $emailBody;
            $mail->AltBody = "Dear {$name},\n\nThank you for booking with Pamper & Relax Spa.\n\nAppointment Details:\nService: {$service_name}\nDuration: {$duration_text}\nDate: {$date}\nTime: {$time}\nFee: ₱{$service_fee}\n\nWe will confirm your appointment shortly.";

            $mail->send();
            
            echo json_encode([
                'success' => true, 
                'message' => '✓ Appointment booked successfully! Confirmation email sent to ' . $email
            ]);
            
        } catch (Exception $e) {
            // Appointment was saved but email failed
            echo json_encode([
                'success' => true, 
                'message' => '✓ Appointment booked successfully! However, email notification could not be sent. We will contact you shortly.'
            ]);
            
            // Log the error
            error_log("Email Error: {$mail->ErrorInfo}");
        }
        
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to book appointment: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>