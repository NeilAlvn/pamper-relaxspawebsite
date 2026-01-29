<?php
session_start();

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // If using Composer
// OR manually include these files if you downloaded PHPMailer:
// require 'path/to/PHPMailer/src/Exception.php';
// require 'path/to/PHPMailer/src/PHPMailer.php';
// require 'path/to/PHPMailer/src/SMTP.php';

// Catch all errors and convert to JSON
try {
    header('Content-Type: application/json');
    
    // Database connection
    $conn = new mysqli("localhost", "root", "", "pos_system");
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Check if cart exists
    if (empty($_SESSION['cart'])) {
        throw new Exception('Your cart is empty');
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $payment_proof = null;

    // Validate required fields
    if (empty($name) || empty($email) || empty($phone) || empty($address)) {
        throw new Exception('Please fill in all required fields');
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid email address');
    }

    // Handle payment proof upload
    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Please upload payment proof');
    }

    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['payment_proof']['name'];
    $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($filetype, $allowed)) {
        throw new Exception('Invalid file type. Please upload JPG, PNG, or GIF');
    }

    if ($_FILES['payment_proof']['size'] > 5 * 1024 * 1024) {
        throw new Exception('File size must be less than 5MB');
    }
    
    $upload_dir = 'uploads/payments/';
    
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }
    
    $new_filename = uniqid() . '_' . time() . '.' . $filetype;
    $destination = $upload_dir . $new_filename;
    
    if (!move_uploaded_file($_FILES['payment_proof']['tmp_name'], $destination)) {
        throw new Exception('Failed to upload payment proof');
    }
    
    $payment_proof = $destination;

    // Calculate order total
    $order_total = 0;
    $order_items = [];
    
    foreach ($_SESSION['cart'] as $product_id => $item) {
        $order_total += $item['price'] * $item['quantity'];
        $order_items[] = [
            'product_id' => $product_id,
            'name' => $item['name'],
            'price' => $item['price'],
            'quantity' => $item['quantity']
        ];
    }

    // Start transaction
    $conn->begin_transaction();

    // Insert order
    $stmt = $conn->prepare("INSERT INTO orders (customer_name, customer_email, customer_phone, customer_address, total_amount, payment_proof, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    
    if ($stmt === false) {
        throw new Exception('Failed to prepare order statement: ' . $conn->error);
    }
    
    $stmt->bind_param("ssssds", $name, $email, $phone, $address, $order_total, $payment_proof);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to insert order: ' . $stmt->error);
    }
    
    $order_id = $stmt->insert_id;
    $stmt->close();

    $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity) VALUES (?, ?, ?, ?, ?)");
    
    
    if ($stmt_item === false) {
        throw new Exception('Failed to prepare order items statement: ' . $conn->error);
    }
    
    $stmt_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
    
    if ($stmt_stock === false) {
        throw new Exception('Failed to prepare stock update statement: ' . $conn->error);
    }
    
    foreach ($order_items as $item) {
        // Insert order item
        $stmt_item->bind_param("iisdi", $order_id, $item['product_id'], $item['name'], $item['price'], $item['quantity']);
        if (!$stmt_item->execute()) {
            throw new Exception('Failed to insert order item: ' . $stmt_item->error);
        }
        
        // Update stock
        $stmt_stock->bind_param("iii", $item['quantity'], $item['product_id'], $item['quantity']);
        if (!$stmt_stock->execute()) {
            throw new Exception('Failed to update stock for product: ' . $item['name']);
        }
        
        // Check if stock was actually updated
        if ($stmt_stock->affected_rows === 0) {
            throw new Exception('Insufficient stock for product: ' . $item['name']);
        }
    }
    
    $stmt_item->close();
    $stmt_stock->close();

    // Commit transaction
    $conn->commit();

    // Send confirmation email
    $email_result = sendOrderConfirmationEmail($order_id, $name, $email, $order_items, $order_total);

    // Clear cart after successful order
    unset($_SESSION['cart']);
    
    $message = $email_result['success'] 
        ? '✓ Order placed successfully! Confirmation email sent. Order #' . $order_id
        : '✓ Order placed successfully! Order #' . $order_id . ' (Email: ' . $email_result['error'] . ')';
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'order_id' => $order_id
    ]);

} catch (Exception $e) {
    // Rollback transaction if it was started
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

/**
 * Send order confirmation email using PHPMailer
 */
function sendOrderConfirmationEmail($order_id, $name, $email, $order_items, $total) {
    $mail = new PHPMailer(true);
    
    try {
        // SMTP Configuration - IMPORTANT: Enable debugging for troubleshooting
        $mail->SMTPDebug = 0;  // Set to 2 for detailed debug output
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
          $mail->Username   = 'joshuapantas.devs@gmail.com'; // Your Gmail address
            $mail->Password   = 'kiyl ketg anes kwrk';      // ⚠️ CHANGE THIS (16-char App Password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Sender and recipient
        $mail->setFrom('noreply@pamperrelax.com', 'Pamper & Relax Spa');
        $mail->addAddress($email, $name);
        $mail->addReplyTo('info@pamperrelax.com', 'Pamper & Relax Support');
        
        // Email content
        $order_number = str_pad($order_id, 6, '0', STR_PAD_LEFT);
        $mail->isHTML(true);
        $mail->Subject = "Order Confirmation #$order_number - Pamper & Relax Spa";
        
        // Build email body
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    margin: 0; 
                    padding: 0; 
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    background-color: #ffffff;
                }
                .header { 
                    background-color: #0e3f37; 
                    color: white; 
                    padding: 30px 20px; 
                    text-align: center; 
                }
                .header h1 { 
                    margin: 0; 
                    font-size: 28px; 
                }
                .content { 
                    background-color: #f8f5ef; 
                    padding: 30px 20px; 
                }
                .order-box { 
                    background-color: white; 
                    padding: 20px; 
                    margin: 20px 0; 
                    border-radius: 8px; 
                    border: 1px solid #e2d4c3;
                }
                .order-box h2 { 
                    color: #0e3f37; 
                    margin-top: 0; 
                    font-size: 22px;
                }
                .product-item { 
                    border-bottom: 1px solid #e2d4c3; 
                    padding: 12px 0; 
                }
                .product-item:last-child { 
                    border-bottom: none; 
                }
                .product-name { 
                    font-weight: bold; 
                    color: #0e3f37; 
                }
                .product-details { 
                    color: #666; 
                    font-size: 14px; 
                }
                .total-row { 
                    font-size: 18px; 
                    font-weight: bold; 
                    color: #0e3f37; 
                    margin-top: 15px; 
                    padding-top: 15px; 
                    border-top: 2px solid #d4b26a;
                    text-align: right;
                }
                .info-text { 
                    background-color: #fff3cd; 
                    padding: 15px; 
                    border-radius: 5px; 
                    border-left: 4px solid #d4b26a; 
                    margin: 20px 0;
                }
                .footer { 
                    text-align: center; 
                    padding: 20px; 
                    color: white; 
                    background-color: #0e3f37;
                }
                .footer p { 
                    margin: 5px 0; 
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Pamper & Relax Spa</h1>
                    <p style='margin: 10px 0 0 0; font-size: 16px;'>Thank you for your order!</p>
                </div>
                
                <div class='content'>
                    <h2 style='color: #0e3f37;'>Hello " . htmlspecialchars($name) . ",</h2>
                    <p>Your order has been received and is being processed. We will review your payment and contact you shortly.</p>
                    
                    <div class='order-box'>
                        <h2>Order #$order_number</h2>
                        <p style='color: #666;'><strong>Order Date:</strong> " . date('F j, Y, g:i A') . "</p>
                        
                        <h3 style='color: #0e3f37; margin-top: 20px;'>Order Items:</h3>";
        
        foreach ($order_items as $item) {
            $item_total = $item['price'] * $item['quantity'];
            $message .= "
                        <div class='product-item'>
                            <div class='product-name'>" . htmlspecialchars($item['name']) . "</div>
                            <div class='product-details'>
                                Qty: " . $item['quantity'] . " × ₱" . number_format($item['price'], 2) . " = 
                                <span style='color: #d4b26a; font-weight: bold;'>₱" . number_format($item_total, 2) . "</span>
                            </div>
                        </div>";
        }
        
        $message .= "
                        <div class='total-row'>
                            <span style='color: #666; font-weight: normal;'>Total:</span> 
                            <span style='color: #d4b26a;'>₱" . number_format($total, 2) . "</span>
                        </div>
                    </div>
                    
                    <div class='info-text'>
                        <strong>What's Next?</strong><br>
                        • We're reviewing your payment proof<br>
                        • You'll receive a confirmation call/text within 24 hours<br>
                        • Your order will be prepared and shipped once payment is verified
                    </div>
                    
                    <p>If you have any questions about your order, please don't hesitate to contact us.</p>
                </div>
                
                <div class='footer'>
                    <p><strong>Pamper & Relax Spa</strong></p>
                    <p>© " . date('Y') . " All rights reserved.</p>
                    <p style='font-size: 12px; margin-top: 10px;'>This is an automated message, please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message); // Plain text version
        
        $mail->send();
        return ['success' => true];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}
?>