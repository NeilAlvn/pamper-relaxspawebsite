<?php
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$conn = new mysqli("localhost", "root", "", "pos_system");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    // Get form settings
    $settings_query = $conn->query("SELECT * FROM contact_form_settings LIMIT 1");
    $settings = $settings_query->fetch_assoc();

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
    
    if ($stmt->execute()) {
        // Send email notification
        $mail = new PHPMailer(true);
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'joshuapantas.devs@gmail.com';
            $mail->Password   = 'kiyl ketg anes kwrk';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // To admin
            $mail->setFrom('pamperandrelax@gmail.com', 'Pamper & Relax Spa');
            $mail->addAddress($settings['recipient_email']);
            $mail->addReplyTo($email, $name);

            $mail->isHTML(true);
            $mail->Subject = 'New Contact Form Message: ' . $subject;
            
            $mail->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #0e3f37; color: white; padding: 20px; text-align: center; }
                    .content { background-color: #f8f5ef; padding: 20px; }
                    .details { background-color: white; padding: 15px; margin: 15px 0; border-left: 4px solid #d4b26a; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>New Contact Form Message</h1>
                    </div>
                    <div class='content'>
                        <div class='details'>
                            <p><strong>From:</strong> {$name}</p>
                            <p><strong>Email:</strong> {$email}</p>
                            <p><strong>Phone:</strong> {$phone}</p>
                            <p><strong>Subject:</strong> {$subject}</p>
                        </div>
                        <div class='details'>
                            <p><strong>Message:</strong></p>
                            <p>" . nl2br(htmlspecialchars($message)) . "</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
            ";

            $mail->send();
            
            echo json_encode([
                'success' => true,
                'message' => $settings['success_message']
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => true,
                'message' => 'Message received but email notification failed.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to submit message. Please try again.'
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>