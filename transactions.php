<?php
date_default_timezone_set('Asia/Manila');

// Handle user logout - must be at the very top before session_start
if (isset($_GET['logout_user'])) {
    session_start();
    // Clear all OTP and verification sessions
    $_SESSION = array(); // Clear all session variables
    
    // Destroy session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    header("Location: transactions.php");
    exit();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // If using Composer

// DB CONNECTION
$conn = new mysqli("localhost", "root", "", "pos_system");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Check if admin - redirect to admin transactions page
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: transactions_admin.php");
    exit();
}

// Handle OTP Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_otp'])) {
    $email = trim($_POST['email']);
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email exists in appointments or orders
        $check_query = "SELECT email FROM appointments WHERE email = ? 
                       UNION 
                       SELECT customer_email as email FROM orders WHERE customer_email = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ss", $email, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Generate 6-digit OTP
            $otp = rand(100000, 999999);
            $_SESSION['otp'] = $otp;
            $_SESSION['otp_email'] = $email;
            $_SESSION['otp_time'] = time();
            
            // Send OTP via PHPMailer
            $mail = new PHPMailer(true);
            
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'joshuapantas.devs@gmail.com'; // Your Gmail
                $mail->Password   = 'kiyl ketg anes kwrk'; // Your App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                
                // Disable SSL verification (for local testing)
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );

                // Recipients
                $mail->setFrom('pamperandrelax@gmail.com', 'Pamper & Relax Spa');
                $mail->addAddress($email);
                $mail->addReplyTo('pamperandrelax@gmail.com', 'Pamper & Relax Spa');

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Your Transaction Access Code - Pamper & Relax';
                $mail->Body = "
                <html>
                <body style='font-family: Arial, sans-serif;'>
                    <div style='max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f5ef;'>
                        <div style='background-color: #0e3f37; padding: 20px; text-align: center;'>
                            <h1 style='color: #d4b26a; margin: 0;'>Pamper & Relax Spa</h1>
                        </div>
                        <div style='background-color: white; padding: 30px; margin-top: 20px;'>
                            <h2 style='color: #0e3f37;'>Your Access Code</h2>
                            <p>Hello,</p>
                            <p>You requested to view your transaction history. Please use the code below:</p>
                            <div style='background-color: #f8f5ef; padding: 20px; text-align: center; margin: 20px 0;'>
                                <h1 style='color: #0e3f37; font-size: 36px; letter-spacing: 5px; margin: 0;'>{$otp}</h1>
                            </div>
                            <p style='color: #666;'><strong>This code will expire in 10 minutes.</strong></p>
                            <p>If you didn't request this code, please ignore this email.</p>
                        </div>
                        <div style='text-align: center; padding: 20px; color: #666; font-size: 12px;'>
                            <p>&copy; 2025 Pamper & Relax Spa. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                $mail->AltBody = "Your Pamper & Relax verification code is: {$otp}\n\nThis code will expire in 10 minutes.";

                $mail->send();
                $success = "OTP has been sent to your email. Please check your inbox.";
                
            } catch (Exception $e) {
                $error = "Failed to send OTP. Error: {$mail->ErrorInfo}";
                error_log("OTP Email Error: {$mail->ErrorInfo}");
            }
            
        } else {
            $error = "No transactions found for this email address.";
        }
    }
}

// Handle OTP Verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $entered_otp = trim($_POST['otp']);
    
    // Debug logging
    error_log("OTP Verification attempt - Entered: $entered_otp, Session OTP: " . ($_SESSION['otp'] ?? 'NOT SET'));
    error_log("Session OTP Email: " . ($_SESSION['otp_email'] ?? 'NOT SET'));
    
    // Check if OTP session exists
    if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_email'])) {
        $error = "Session expired. Please request a new OTP.";
        error_log("OTP Verification failed - Session not found");
    } else {
        // Check if OTP expired (10 minutes)
        if (time() - $_SESSION['otp_time'] > 600) {
            $error = "OTP has expired. Please request a new one.";
            unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_time']);
            error_log("OTP Verification failed - Expired");
        } elseif ($entered_otp == $_SESSION['otp']) {
            // OTP verified
            error_log("OTP Verification SUCCESS for: " . $_SESSION['otp_email']);
            $_SESSION['verified_email'] = $_SESSION['otp_email'];
            $_SESSION['verified_time'] = time();
            unset($_SESSION['otp'], $_SESSION['otp_time'], $_SESSION['otp_email']);
            $verified = true;
            // Redirect to avoid form resubmission
            header("Location: transactions.php");
            exit();
        } else {
            $error = "Invalid OTP. Please try again.";
            error_log("OTP Verification failed - Invalid code");
        }
    }
}

// Initialize variables
$all_transactions = [];
$verified = false;

// Check if user is already verified (do this BEFORE OTP verification)
if (isset($_SESSION['verified_email']) && !isset($_POST['verify_otp']) && !isset($_POST['request_otp'])) {
    // Check if verification is still valid (30 minutes)
    if (time() - $_SESSION['verified_time'] > 1800) {
        unset($_SESSION['verified_email'], $_SESSION['verified_time']);
        $error = "Session expired. Please verify again.";
        $verified = false;
    } else {
        $verified = true;
        $user_email = $_SESSION['verified_email'];
        
        // Get user's appointments
        $appointments_query = "
            SELECT 
                a.id,
                a.name as customer_name,
                a.email,
                a.phone,
                a.date as appointment_date,
                a.time as appointment_time,
                COALESCE(s.name, 'Service') as service_name,
                s.duration,
                a.service_fee,
                a.status,
                a.created_at,
                'appointment' as log_type
            FROM appointments a
            LEFT JOIN services s ON a.service = s.id
            WHERE a.email = ?
            ORDER BY a.created_at DESC
        ";
        
        $stmt = $conn->prepare($appointments_query);
        $stmt->bind_param("s", $user_email);
        $stmt->execute();
        $appointments_result = $stmt->get_result();
        
        // Get user's orders
        $orders_query = "
            SELECT 
                o.id,
                o.customer_name,
                o.customer_email,
                o.customer_phone,
                o.customer_address,
                o.total_amount,
                o.status,
                o.created_at,
                'sale' as log_type
            FROM orders o
            WHERE o.customer_email = ?
            ORDER BY o.created_at DESC
        ";
        
        $stmt = $conn->prepare($orders_query);
        $stmt->bind_param("s", $user_email);
        $stmt->execute();
        $orders_result = $stmt->get_result();
        
        // Combine results
        $all_transactions = [];
        while($row = $appointments_result->fetch_assoc()) {
            $all_transactions[] = $row;
        }
        while($row = $orders_result->fetch_assoc()) {
            $all_transactions[] = $row;
        }
        
        // Sort by created_at
        usort($all_transactions, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        // Calculate total spent (all transactions except rejected)
        $total_spent = 0;
        foreach($all_transactions as $transaction) {
            if ($transaction['status'] !== 'rejected') {
                if ($transaction['log_type'] === 'sale') {
                    $total_spent += $transaction['total_amount'];
                } else {
                    $total_spent += $transaction['service_fee'];
                }
            }
        }
    }
}
?>

<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>My Transactions — Pamper & Relax</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            spaGreen: '#0e3f37',
            spaGold: '#d4b26a',
            spaIvory: '#f8f5ef',
            spaWhite: '#ffffff',
            spaAccent: '#e2d4c3'
          },
          fontFamily: {
            heading: ['"Playfair Display"', 'serif'],
            body: ['Inter', 'sans-serif']
          }
        }
      }
    }
  </script>
</head>

<body class="font-body text-gray-800 bg-spaIvory antialiased">
<?php 
include 'reference.php'; 
processLogin($conn);
login_button(); 
logout_button(); 
?>

<header class="nav-blur text-white fixed w-full z-50 border-b border-white/10 bg-spaGreen">
  <nav>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        <div class="flex-shrink-0">
          <a href="index.php" class="flex items-center space-x-2">
            <img src="assets/logo/Pamper Website Logo250x100.png" alt="Pamper & Relax Spa" class="h-8 sm:h-9 md:h-10 lg:h-12 w-auto object-contain transition-all duration-300"/>
          </a>
        </div>
        <?php renderNavigation(); ?>
        <div class="hidden md:block">
          <a href="index.php#appointment-section">
            <button class="border bg-spaGold border-spaGold text-white px-5 py-2 rounded-md font-medium hover:bg-white hover:text-spaGold hover:border-spaGold transition duration-300">
              Book Appointment →
            </button>
          </a>
        </div>
      </div>
    </div>
  </nav>
</header>

<main class="pt-24 pb-16 min-h-screen">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <?php if (!isset($verified) || !$verified): ?>
      <!-- OTP Request/Verification Form -->
      <div class="text-center mb-8">
        <p class="text-spaGold uppercase tracking-widest text-sm font-medium mb-3">Secure Access</p>
        <h1 class="font-heading text-4xl md:text-5xl text-spaGreen mb-4">View Your Transactions</h1>
        <p class="text-gray-600 max-w-xl mx-auto">
          Enter your email to receive a one-time code and view your transaction history.
        </p>
      </div>

      <?php if (isset($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 max-w-md mx-auto">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <?php if (isset($success)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 max-w-md mx-auto">
          <?php echo htmlspecialchars($success); ?>
        </div>
      <?php endif; ?>

      <div class="bg-white rounded-xl shadow-lg p-8 max-w-md mx-auto border border-spaAccent/30">
        <?php 
        // Determine which form to show
        $should_show_otp = isset($_SESSION['otp']) && isset($_SESSION['otp_email']);
        
        if (!$should_show_otp): 
        ?>
          <!-- Email Form -->
          <form method="POST" class="space-y-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
              <input 
                type="email" 
                name="email" 
                required 
                placeholder="your@email.com"
                value="<?php echo isset($_SESSION['otp_email']) ? htmlspecialchars($_SESSION['otp_email']) : ''; ?>"
                class="w-full px-4 py-3 rounded-lg border border-spaAccent bg-spaIvory/30 focus:outline-none focus:ring-2 focus:ring-spaGold focus:border-transparent transition"
              >
              <p class="text-xs text-gray-500 mt-2">Enter the email you used for bookings</p>
            </div>
            
            <button 
              type="submit" 
              name="request_otp"
              class="w-full px-6 py-3 bg-spaGold hover:bg-spaGreen text-white rounded-lg transition duration-300 font-medium"
            >
              Send Verification Code
            </button>
          </form>
        <?php else: ?>
          <!-- OTP Verification Form -->
          <!-- OTP Verification Form -->
          <form method="POST" class="space-y-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Enter Verification Code</label>
              <input 
                type="text" 
                name="otp" 
                required 
                maxlength="6"
                pattern="[0-9]{6}"
                placeholder="000000"
                class="w-full px-4 py-3 rounded-lg border border-spaAccent bg-spaIvory/30 focus:outline-none focus:ring-2 focus:ring-spaGold focus:border-transparent transition text-center text-2xl tracking-widest font-mono"
              >
              <p class="text-xs text-gray-500 mt-2">Check your email for the 6-digit code</p>
            </div>
            
            <button 
              type="submit" 
              name="verify_otp"
              class="w-full px-6 py-3 bg-spaGold hover:bg-spaGreen text-white rounded-lg transition duration-300 font-medium"
            >
              Verify & View Transactions
            </button>
            
            <div class="text-center">
              <a href="transactions.php" class="text-sm text-spaGold hover:text-spaGreen transition">
                ← Use different email
              </a>
            </div>
          </form>
        <?php endif; ?>
        
       
        
       
      </div>

    <?php else: ?>
      <!-- Verified - Show Transactions -->
      <div class="text-center mb-8">
        <p class="text-spaGold uppercase tracking-widest text-sm font-medium mb-3">Welcome Back</p>
        <h1 class="font-heading text-4xl md:text-5xl text-spaGreen mb-4">My Transactions</h1>
        <p class="text-gray-600">
          Viewing transactions for: <strong><?php echo htmlspecialchars($user_email); ?></strong>
        </p>
        <a href="?logout_user=1" class="inline-block mt-2 text-sm text-red-600 hover:text-red-700 transition">
          Logout & Clear Session
        </a>
      </div>

      <!-- Statistics -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-6 border border-spaAccent/30">
          <div class="flex items-center space-x-4">
            <div class="bg-yellow-50 rounded-full p-4 flex-shrink-0">
              <svg class="w-10 h-10 text-spaGold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Total Spent</p>
              <p class="text-2xl font-bold text-spaGold truncate">₱<?php echo number_format($total_spent ?? 0, 2); ?></p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border border-spaAccent/30">
          <div class="flex items-center space-x-4">
            <div class="bg-spaIvory rounded-full p-4 flex-shrink-0">
              <svg class="w-10 h-10 text-spaGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Total Bookings</p>
              <p class="text-2xl font-bold text-spaGreen"><?php echo count($all_transactions); ?></p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border border-spaAccent/30">
          <div class="flex items-center space-x-4">
            <div class="bg-blue-50 rounded-full p-4 flex-shrink-0">
              <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Appointments</p>
              <p class="text-2xl font-bold text-blue-600">
                <?php echo count(array_filter($all_transactions, fn($t) => $t['log_type'] === 'appointment')); ?>
              </p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border border-spaAccent/30">
          <div class="flex items-center space-x-4">
            <div class="bg-purple-50 rounded-full p-4 flex-shrink-0">
              <svg class="w-10 h-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Orders</p>
              <p class="text-2xl font-bold text-purple-600">
                <?php echo count(array_filter($all_transactions, fn($t) => $t['log_type'] === 'sale')); ?>
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Transactions List -->
      <div class="bg-white rounded-xl shadow-lg border border-spaAccent/30 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-spaGreen text-white">
              <tr>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">ID</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Type</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Details</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Date</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Amount</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-spaAccent/30">
              <?php if (count($all_transactions) > 0): ?>
                <?php foreach($all_transactions as $row): 
                  $is_sale = ($row['log_type'] === 'sale');
                ?>
                  <tr class="hover:bg-spaIvory/50 transition">
                    <td class="px-6 py-4">
                      <span class="text-sm font-mono text-gray-600">#<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></span>
                    </td>
                    <td class="px-6 py-4">
                      <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold <?php echo $is_sale ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                        <?php echo $is_sale ? 'Product Order' : 'Appointment'; ?>
                      </span>
                    </td>
                    <td class="px-6 py-4">
                      <div class="font-medium text-gray-800">
                        <?php echo $is_sale ? 'Order #' . str_pad($row['id'], 4, '0', STR_PAD_LEFT) : htmlspecialchars($row['service_name']); ?>
                      </div>
                      <?php if (!$is_sale && !empty($row['duration'])): ?>
                        <div class="text-xs text-gray-500">Duration: <?php echo $row['duration'] == 1 ? '1 hour' : $row['duration'] . ' hours'; ?></div>
                      <?php endif; ?>
                      <?php if (!$is_sale): ?>
                        <div class="text-xs text-gray-500">Scheduled: <?php echo date('M d, Y g:i A', strtotime($row['appointment_date'] . ' ' . $row['appointment_time'])); ?></div>
                      <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                      <div class="text-sm text-gray-700"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></div>
                      <div class="text-xs text-gray-500"><?php echo date('g:i A', strtotime($row['created_at'])); ?></div>
                    </td>
                    <td class="px-6 py-4">
                      <span class="text-sm font-semibold text-spaGreen">
                        ₱<?php echo number_format($is_sale ? $row['total_amount'] : $row['service_fee'], 2); ?>
                      </span>
                    </td>
                    <td class="px-6 py-4">
                      <?php
                        $status = $row['status'];
                        $status_colors = [
                          'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                          'approved' => 'bg-green-100 text-green-800 border-green-300',
                          'rejected' => 'bg-red-100 text-red-800 border-red-300'
                        ];
                        $color_class = $status_colors[$status] ?? 'bg-gray-100 text-gray-800 border-gray-300';
                      ?>
                      <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold border <?php echo $color_class; ?>">
                        <?php echo ucfirst($status); ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="text-lg font-medium">No transactions found</p>
                    <p class="text-sm mt-1">You haven't made any bookings yet</p>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

  </div>
</main>

<?php render_footer(); ?>

<?php login_modal(); ?>
</body>
</html>

<?php $conn->close(); ?>