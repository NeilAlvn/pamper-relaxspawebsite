<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

    // DB CONNECTION
    $conn = new mysqli("localhost", "root", "", "pos_system");
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

    // Handle status update via GET (using links)
    if (isset($_GET['action']) && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $action = $_GET['action'];
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        
if ($action === 'approve') {
            // Get appointment details with service name
            $details_stmt = $conn->prepare("
                SELECT a.name, a.email, a.date, a.time, a.service_fee, s.name as service_name, s.duration
                FROM appointments a
                LEFT JOIN services s ON a.service = s.id
                WHERE a.id = ?
            ");
            $details_stmt->bind_param("i", $id);
            $details_stmt->execute();
            $appointment = $details_stmt->get_result()->fetch_assoc();
            
            // Update status
            $stmt = $conn->prepare("UPDATE appointments SET status = 'approved' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // Send approval email using PHPMailer
            if ($appointment) {
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
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'joshuapantas.devs@gmail.com';
                    $mail->Password   = 'kiyl ketg anes kwrk';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    // Recipients
                    $mail->setFrom('pamperandrelax@gmail.com', 'Pamper & Relax Spa');
                    $mail->addAddress($appointment['email'], $appointment['name']);
                    $mail->addReplyTo('pamperandrelax@gmail.com', 'Pamper & Relax Spa');
                    $mail->addBCC('pamperandrelax@gmail.com');

                    // Format duration
                    $duration_text = $appointment['duration'] == 1 ? '1 hour' : $appointment['duration'] . ' hours';

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Appointment Approved - Pamper & Relax Spa';
                    
                    $mail->Body = "
                    <html>
                    <head>
                      <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #0e3f37; color: white; padding: 20px; text-align: center; }
                        .content { background-color: #f8f5ef; padding: 30px; }
                        .status-badge { background-color: #10b981; color: white; padding: 10px 20px; border-radius: 5px; display: inline-block; font-weight: bold; }
                        .details { background-color: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #d4b26a; }
                        .detail-row { padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
                        .detail-label { font-weight: bold; color: #0e3f37; }
                        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                      </style>
                    </head>
                    <body>
                      <div class='container'>
                        <div class='header'>
                          <h1>Appointment Approved!</h1>
                        </div>
                        <div class='content'>
                          <p>Dear " . htmlspecialchars($appointment['name']) . ",</p>
                          
                          <p>Great news! Your appointment has been <span class='status-badge'>APPROVED</span></p>
                          
                          <div class='details'>
                            <h3 style='color: #0e3f37; margin-top: 0;'>Appointment Details</h3>
                            <div class='detail-row'>
                              <span class='detail-label'>Service:</span> " . htmlspecialchars($appointment['service_name']) . "
                            </div>
                            <div class='detail-row'>
                              <span class='detail-label'>Duration:</span> " . $duration_text . "
                            </div>
                            <div class='detail-row'>
                              <span class='detail-label'>Date:</span> " . date('F d, Y', strtotime($appointment['date'])) . "
                            </div>
                            <div class='detail-row'>
                              <span class='detail-label'>Time:</span> " . date('g:i A', strtotime($appointment['time'])) . "
                            </div>
                            <div class='detail-row'>
                              <span class='detail-label'>Fee:</span> ₱" . number_format($appointment['service_fee'], 2) . "
                            </div>
                          </div>
                          
                          <p><strong>Please arrive 10 minutes before your scheduled time.</strong></p>
                          
                          <p>We look forward to seeing you!</p>
                          
                          <p>If you need to reschedule or have any questions, please contact us.</p>
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
                    
                    $mail->AltBody = "Dear " . $appointment['name'] . ",\n\nYour appointment has been APPROVED!\n\nService: " . $appointment['service_name'] . "\nDuration: " . $duration_text . "\nDate: " . date('F d, Y', strtotime($appointment['date'])) . "\nTime: " . date('g:i A', strtotime($appointment['time'])) . "\nFee: ₱" . number_format($appointment['service_fee'], 2) . "\n\nPlease arrive 10 minutes before your scheduled time.\n\nPamper & Relax Spa";
                    
                    $mail->send();
                } catch (Exception $e) {
                    error_log("Approval Email Error: {$mail->ErrorInfo}");
                }
            }
            
        } elseif ($action === 'reject') {
            // Get appointment details with service name
            $details_stmt = $conn->prepare("
                SELECT a.name, a.email, a.date, a.time, s.name as service_name
                FROM appointments a
                LEFT JOIN services s ON a.service = s.id
                WHERE a.id = ?
            ");
            $details_stmt->bind_param("i", $id);
            $details_stmt->execute();
            $appointment = $details_stmt->get_result()->fetch_assoc();
            
            // Update status
            $stmt = $conn->prepare("UPDATE appointments SET status = 'rejected' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // Send rejection email using PHPMailer
            if ($appointment) {
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
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'joshuapantas.devs@gmail.com';
                    $mail->Password   = 'kiyl ketg anes kwrk';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    // Recipients
                    $mail->setFrom('pamperandrelax@gmail.com', 'Pamper & Relax Spa');
                    $mail->addAddress($appointment['email'], $appointment['name']);
                    $mail->addReplyTo('pamperandrelax@gmail.com', 'Pamper & Relax Spa');
                    $mail->addBCC('pamperandrelax@gmail.com');

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Appointment Update - Pamper & Relax Spa';
                    
                    $mail->Body = "
                    <html>
                    <head>
                      <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #0e3f37; color: white; padding: 20px; text-align: center; }
                        .content { background-color: #f8f5ef; padding: 30px; }
                        .status-badge { background-color: #ef4444; color: white; padding: 10px 20px; border-radius: 5px; display: inline-block; font-weight: bold; }
                        .details { background-color: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #d4b26a; }
                        .detail-row { padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
                        .detail-label { font-weight: bold; color: #0e3f37; }
                        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                      </style>
                    </head>
                    <body>
                      <div class='container'>
                        <div class='header'>
                          <h1>Appointment Update</h1>
                        </div>
                        <div class='content'>
                          <p>Dear " . htmlspecialchars($appointment['name']) . ",</p>
                          
                          <p>We regret to inform you that your appointment request has been <span class='status-badge'>DECLINED</span></p>
                          
                          <div class='details'>
                            <h3 style='color: #0e3f37; margin-top: 0;'>Appointment Details</h3>
                            <div class='detail-row'>
                              <span class='detail-label'>Service:</span> " . htmlspecialchars($appointment['service_name']) . "
                            </div>
                            <div class='detail-row'>
                              <span class='detail-label'>Requested Date:</span> " . date('F d, Y', strtotime($appointment['date'])) . "
                            </div>
                            <div class='detail-row'>
                              <span class='detail-label'>Requested Time:</span> " . date('g:i A', strtotime($appointment['time'])) . "
                            </div>
                          </div>
                          
                          <p><strong>Reason:</strong> The requested time slot may no longer be available or there may be scheduling conflicts.</p>
                          
                          <p>We apologize for any inconvenience. We'd love to help you find an alternative time that works for you.</p>
                          
                          <p style='margin-top: 20px;'>If you have any questions, please don't hesitate to contact us at pamperandrelax@gmail.com</p>
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
                    
                    $mail->AltBody = "Dear " . $appointment['name'] . ",\n\nWe regret to inform you that your appointment request has been declined.\n\nService: " . $appointment['service_name'] . "\nRequested Date: " . date('F d, Y', strtotime($appointment['date'])) . "\nRequested Time: " . date('g:i A', strtotime($appointment['time'])) . "\n\nReason: The requested time slot may no longer be available or there may be scheduling conflicts.\n\nWe apologize for any inconvenience.\n\nPamper & Relax Spa";
                    
                    $mail->send();
                } catch (Exception $e) {
                    error_log("Rejection Email Error: {$mail->ErrorInfo}");
                }
            }
            
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
        
        // Redirect to maintain filter and search state
        header("Location: appointments.php?filter=" . urlencode($filter) . "&search=" . urlencode($search));
        exit();
    }

    // Get filter
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    // Build query - JOIN with services table to get service name
    $query = "SELECT a.*, s.name as service_name, s.duration 
              FROM appointments a 
              LEFT JOIN services s ON a.service = s.id 
              WHERE 1=1";
    
    if ($filter !== 'all') {
        $query .= " AND a.status = '" . $conn->real_escape_string($filter) . "'";
    }
    
    if (!empty($search)) {
        $query .= " AND (a.name LIKE '%" . $conn->real_escape_string($search) . "%' 
                    OR a.email LIKE '%" . $conn->real_escape_string($search) . "%' 
                    OR s.name LIKE '%" . $conn->real_escape_string($search) . "%')";
    }
    
    $query .= " ORDER BY a.created_at DESC";
    
    $result = $conn->query($query);

    // Get statistics
    $stats_query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM appointments";
    $stats_result = $conn->query($stats_query);
    $stats = $stats_result->fetch_assoc();
    
    // Get current month bookings for calendar
$view_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$calendar_query = "SELECT date, COUNT(*) as booking_count 
                   FROM appointments 
                   WHERE DATE_FORMAT(date, '%Y-%m') = '$view_month' 
                   AND status != 'rejected'
                   GROUP BY date";
$calendar_result = $conn->query($calendar_query);

$bookings_by_date = [];
while ($booking = $calendar_result->fetch_assoc()) {
    $bookings_by_date[$booking['date']] = $booking['booking_count'];
}

// Calendar helper variables
$first_day = $view_month . '-01';
$days_in_month = date('t', strtotime($first_day));
$start_day_of_week = date('w', strtotime($first_day));

// Previous and next month
$prev_month = date('Y-m', strtotime($view_month . '-01 -1 month'));
$next_month = date('Y-m', strtotime($view_month . '-01 +1 month'));
$current_month_name = date('F Y', strtotime($first_day));
?>

<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Appointments Management — Pamper & Relax</title>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  
  <!-- Tailwind -->
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
  <style>
  .calendar-cell:hover {
    transform: scale(1.05);
  }
  .timeline-slot {
    transition: all 0.2s ease;
  }
  .timeline-slot:hover {
    background-color: #f8f5ef;
  }
</style>
</head>
<body class="font-body text-gray-800 bg-spaIvory antialiased">
<?php 
include 'reference.php'; 
 
logout_button(); 

?>
<body class="font-body text-gray-800 bg-spaIvory antialiased">
  <header class="nav-blur text-white fixed w-full z-50 border-b border-white/10 bg-spaGreen">
    <nav>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex-shrink-0">
            <a href="index.php" class="flex items-center space-x-2">
              <img src="assets/logo/Pamper Website Logo250x100.png" alt="Pamper & Relax Spa" class="h-8 sm:h-9 md:h-10 lg:h-12 w-auto object-contain transition-all duration-300"/>
            </a>
          </div>

          <!-- Desktop menu -->
          <?php 
          
          renderNavigation(); 
          ?>

      <!-- Desktop CTA -->
      <div class="hidden md:block">
            <a href="index.php#appointment-section">
                <button
                    class="border bg-spaGold border-spaGold text-white px-5 py-2 rounded-md font-medium hover:bg-white hover:text-spaGold hover:border-spaGold transition duration-300"
                    style="width: 196.82px; height: 42.44px;">
                    Get Appointment →
                </button>
            </a>
      </div>

          
        </div>
      </div>
    </nav>
  </header>

  <main class="pt-24 pb-16 min-h-screen">
    <div class="max-w-7xl mx-auto px-6 lg:px-12">
      
      <!-- Header -->
      <div class="text-center mb-12">
        <p class="text-spaGold uppercase tracking-widest text-sm font-medium mb-3">
          Management Portal
        </p>
        <h1 class="font-heading text-4xl md:text-5xl text-spaGreen mb-4">
          Appointments Dashboard
        </h1>
        <p class="text-gray-600 max-w-2xl mx-auto">
          Manage and track all customer appointments with ease.
        </p>
      </div>
      <!-- Calendar Button -->
      <div class="text-center mb-10">
        <button onclick="openCalendarModal()" class="px-6 py-3 bg-spaGold hover:bg-spaGreen text-white rounded-lg transition duration-300 font-medium inline-flex items-center space-x-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
          <span>View Calendar</span>
        </button>
      </div>



      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
        <div class="bg-white rounded-xl shadow-md p-6 border border-spaAccent/30">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 uppercase tracking-wide">Total</p>
              <p class="text-3xl font-bold text-spaGreen mt-1"><?php echo $stats['total']; ?></p>
            </div>
            <div class="bg-spaIvory rounded-full p-3">
              <svg class="w-8 h-8 text-spaGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border border-spaAccent/30">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 uppercase tracking-wide">Pending</p>
              <p class="text-3xl font-bold text-yellow-600 mt-1"><?php echo $stats['pending']; ?></p>
            </div>
            <div class="bg-yellow-50 rounded-full p-3">
              <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border border-spaAccent/30">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 uppercase tracking-wide">Approved</p>
              <p class="text-3xl font-bold text-green-600 mt-1"><?php echo $stats['approved']; ?></p>
            </div>
            <div class="bg-green-50 rounded-full p-3">
              <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border border-spaAccent/30">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 uppercase tracking-wide">Rejected</p>
              <p class="text-3xl font-bold text-red-600 mt-1"><?php echo $stats['rejected']; ?></p>
            </div>
            <div class="bg-red-50 rounded-full p-3">
              <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters and Search -->
      <div class="bg-white rounded-xl shadow-md p-6 mb-8 border border-spaAccent/30">
        <form method="GET" action="appointments.php" class="flex flex-col md:flex-row gap-4">
          
          <!-- Search -->
          <div class="flex-1">
            <input 
              type="text" 
              name="search" 
              placeholder="Search by name, email, or service..." 
              value="<?php echo htmlspecialchars($search); ?>"
              class="w-full px-4 py-3 rounded-lg border border-spaAccent bg-spaIvory/30 focus:outline-none focus:ring-2 focus:ring-spaGold focus:border-transparent transition"
            >
          </div>

          <!-- Filter -->
          <div class="md:w-48">
            <select 
              name="filter" 
              class="w-full px-4 py-3 rounded-lg border border-spaAccent bg-spaIvory/30 focus:outline-none focus:ring-2 focus:ring-spaGold focus:border-transparent transition"
            >
              <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Status</option>
              <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
              <option value="approved" <?php echo $filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
              <option value="rejected" <?php echo $filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
            </select>
          </div>

          <!-- Submit -->
          <button 
            type="submit"
            class="px-6 py-3 bg-spaGold hover:bg-spaGreen text-white rounded-lg transition duration-300 font-medium"
          >
            Filter
          </button>
        </form>
      </div>

      <!-- Appointments Table -->
      <div class="bg-white rounded-xl shadow-lg border border-spaAccent/30 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-spaGreen text-white">
              <tr>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Client</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Contact</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Service</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Date & Time</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Fee</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Payment Proof</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Status</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-spaAccent/30">
              <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                  <tr class="hover:bg-spaIvory/50 transition">
                    <td class="px-6 py-4">
                      <div class="font-medium text-spaGreen"><?php echo htmlspecialchars($row['name']); ?></div>
                      <?php if (!empty($row['message'])): ?>
                        <div class="text-xs text-gray-500 mt-1" title="<?php echo htmlspecialchars($row['message']); ?>">
                          <?php echo substr(htmlspecialchars($row['message']), 0, 30) . '...'; ?>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                      <div class="text-sm text-gray-700"><?php echo htmlspecialchars($row['email']); ?></div>
                      <div class="text-sm text-gray-500"><?php echo htmlspecialchars($row['phone']); ?></div>
                    </td>
                    <td class="px-6 py-4">
                      <span class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($row['service_name']); ?></span>
                      <?php if (!empty($row['duration'])): ?>
                        <div class="text-xs text-gray-500 mt-1">
                          <?php echo $row['duration'] == 1 ? '1 hour' : $row['duration'] . ' hours'; ?>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                      <div class="text-sm text-gray-700"><?php echo date('M d, Y', strtotime($row['date'])); ?></div>
                      <div class="text-sm text-gray-500"><?php echo date('g:i A', strtotime($row['time'])); ?></div>
                    </td>
                    <td class="px-6 py-4">
                      <span class="text-sm font-semibold text-spaGreen">₱<?php echo number_format($row['service_fee'], 2); ?></span>
                    </td>
                    <td class="px-6 py-4">
                      <?php if(!empty($row['image']) && file_exists($row['image'])): ?>
                        <a href="<?php echo $row['image']; ?>" target="_blank" class="inline-block">
                          <img src="<?php echo $row['image']; ?>" alt="Payment Proof" class="w-16 h-16 object-cover rounded-lg border-2 border-spaGold hover:scale-110 transition cursor-pointer">
                        </a>
                      <?php else: ?>
                        <span class="text-xs text-gray-400 italic">No proof</span>
                      <?php endif; ?>
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
                    <td class="px-6 py-4">
                      <div class="flex items-center space-x-2">
                        <?php if ($status === 'pending'): ?>
                          <a href="appointments.php?action=approve&id=<?php echo $row['id']; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>" 
                             class="p-2 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg transition"
                             onclick="return confirm('Approve this appointment?')"
                             title="Approve">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                          </a>
                          <a href="appointments.php?action=reject&id=<?php echo $row['id']; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>" 
                             class="p-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg transition"
                             onclick="return confirm('Reject this appointment?')"
                             title="Reject">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                          </a>
                        <?php endif; ?>
                        <a href="appointments.php?action=delete&id=<?php echo $row['id']; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>" 
                           class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition"
                           onclick="return confirm('Are you sure you want to delete this appointment?')"
                           title="Delete">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                          </svg>
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-lg font-medium">No appointments found</p>
                    <p class="text-sm mt-1">Try adjusting your filters or search terms</p>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
    <!-- Calendar Modal -->
  <div id="calendarModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999] p-4">
    <div class="bg-white rounded-lg max-w-6xl w-full max-h-[90vh] overflow-hidden">
  <div class="flex justify-between items-center p-6 border-b border-spaAccent/30">
        <div class="flex items-center space-x-4">
          <button onclick="changeMonth('<?php echo $prev_month; ?>')" class="p-2 hover:bg-spaIvory rounded-lg transition">
            <svg class="w-5 h-5 text-spaGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </button>
          <h2 class="text-2xl font-heading text-spaGreen"><?php echo $current_month_name; ?></h2>
          <button onclick="changeMonth('<?php echo $next_month; ?>')" class="p-2 hover:bg-spaIvory rounded-lg transition">
            <svg class="w-5 h-5 text-spaGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>
        <button onclick="closeCalendarModal()" class="text-gray-500 hover:text-gray-700">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      
      <div class="grid md:grid-cols-2 gap-6 p-6 overflow-y-auto max-h-[calc(90vh-100px)]">
        <!-- Left: Calendar -->
        <div>
          <div class="grid grid-cols-7 gap-2">
            <!-- Day headers -->
            <?php 
            $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            foreach ($days as $day): 
            ?>
              <div class="text-center text-sm font-semibold text-gray-600 py-2">
                <?php echo $day; ?>
              </div>
            <?php endforeach; ?>
            
            <!-- Empty cells before first day -->
            <?php for ($i = 0; $i < $start_day_of_week; $i++): ?>
              <div class="aspect-square"></div>
            <?php endfor; ?>
            
           <!-- Calendar days -->
            <?php for ($day = 1; $day <= $days_in_month; $day++): 
              $current_date = $view_month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
              $booking_count = isset($bookings_by_date[$current_date]) ? $bookings_by_date[$current_date] : 0;
              $is_today = ($current_date === date('Y-m-d'));
              $is_past = ($current_date < date('Y-m-d'));
            ?>
              <button 
                onclick="loadDayTimeline('<?php echo $current_date; ?>')"
                class="calendar-cell aspect-square p-2 rounded-lg border border-spaAccent/30 hover:border-spaGold transition relative
                       <?php echo $is_today ? 'ring-2 ring-spaGold' : ''; ?>
                       <?php echo $is_past ? 'bg-gray-50' : 'bg-white hover:bg-spaIvory/50'; ?>
                       <?php echo $booking_count > 0 ? 'cursor-pointer' : ''; ?>">
                <div class="text-sm font-medium <?php echo $is_past ? 'text-gray-400' : 'text-gray-700'; ?>">
                  <?php echo $day; ?>
                </div>
                <?php if ($booking_count > 0): ?>
                  <div class="absolute bottom-1 right-1">
                    <div class="w-5 h-5 rounded-full bg-green-400 flex items-center justify-center">
                      <span class="text-xs font-bold text-white"><?php echo $booking_count; ?></span>
                    </div>
                  </div>
                <?php endif; ?>
              </button>
            <?php endfor; ?>
          </div>
          
          <div class="mt-4 text-sm text-gray-500 space-y-1">
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 rounded-full bg-green-400"></div>
              <span>Has bookings</span>
            </div>
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 rounded ring-2 ring-spaGold"></div>
              <span>Today</span>
            </div>
          </div>
        </div>
        
        <!-- Right: Timeline -->
        <div>
          <div id="timelineContainer" class="bg-spaIvory/30 rounded-lg p-4 min-h-[400px]">
            <div class="text-center text-gray-500 py-8">
              <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              <p class="font-medium">Select a date to view bookings</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    function openCalendarModal() {
      document.getElementById('calendarModal').classList.remove('hidden');
    }
    
    function closeCalendarModal() {
      document.getElementById('calendarModal').classList.add('hidden');
    }
    
    function changeMonth(month) {
      // Add month parameter and modal flag to URL and reload
      const url = new URL(window.location.href);
      url.searchParams.set('month', month);
      url.searchParams.set('calendar_open', '1');
      window.location.href = url.toString();
    }
    
    // Auto-open calendar if returning from month change
    document.addEventListener('DOMContentLoaded', function() {
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('calendar_open') === '1') {
        openCalendarModal();
        // Remove the flag from URL without reloading
        urlParams.delete('calendar_open');
        const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
        window.history.replaceState({}, '', newUrl);
      }
    });
    
    async function loadDayTimeline(date) {
      const container = document.getElementById('timelineContainer');
      
      // Show loading
      container.innerHTML = `
        <div class="text-center py-8">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-spaGold mx-auto"></div>
          <p class="text-gray-500 mt-4">Loading bookings...</p>
        </div>
      `;
      
      // Helper function to convert 24h to 12h format
      function formatTime(timeString) {
        const hour = parseInt(timeString.substring(0, 2));
        const hour12 = hour === 0 ? 12 : (hour > 12 ? hour - 12 : hour);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        return `${hour12}:00 ${ampm}`;
      }
      
      try {
        const response = await fetch(`get_day_bookings.php?date=${date}`);
        const data = await response.json();
        
        if (data.timeline && data.timeline.length > 0) {
          const dateObj = new Date(date + 'T00:00:00');
          const formattedDate = dateObj.toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
          });
          
          let html = `
            <div class="mb-4">
              <h3 class="text-lg font-heading text-spaGreen mb-2">${formattedDate}</h3>
              <p class="text-sm text-gray-600">${data.bookings.length} booking(s)</p>
            </div>
            <div class="space-y-3">
          `;
          
          // Use the timeline data
          data.timeline.forEach(slot => {
            const timeLabel = formatTime(slot.time);
            
            if (slot.booked) {
              const statusColors = {
                'pending': 'border-yellow-400 bg-yellow-50',
                'approved': 'border-green-400 bg-green-50',
                'rejected': 'border-red-400 bg-red-50'
              };
              const colorClass = statusColors[slot.status] || 'border-gray-400 bg-gray-50';
              
              // Show full details only for starting hour
              if (slot.is_start) {
                const duration = slot.duration ? (slot.duration == 1 ? '1 hr' : slot.duration + ' hrs') : '';
                html += `
                  <div class="timeline-slot border-l-4 ${colorClass} p-3 rounded">
                    <div class="flex justify-between items-start mb-2">
                      <div>
                        <div class="font-semibold text-spaGreen">${timeLabel}</div>
                        ${duration ? `<div class="text-xs text-gray-500">${duration}</div>` : ''}
                      </div>
                      <span class="text-xs px-2 py-1 rounded-full ${colorClass} capitalize">${slot.status}</span>
                    </div>
                    <div class="text-sm">
                      <div class="font-medium text-gray-800">${slot.name}</div>
                      <div class="text-gray-600 text-xs">${slot.email}</div>
                      <div class="text-gray-500 text-xs mt-1">${slot.service}</div>
                    </div>
                  </div>
                `;
              } else {
                // Continuation of previous appointment
                html += `
                  <div class="timeline-slot border-l-4 ${colorClass} p-3 rounded opacity-75">
                    <div class="font-medium text-gray-500">${timeLabel}</div>
                    <div class="text-xs text-gray-400">↳ Continuing: ${slot.service}</div>
                  </div>
                `;
              }
            } else {
              // Available slot
              html += `
                <div class="timeline-slot border-l-4 border-gray-200 bg-white p-3 rounded opacity-50">
                  <div class="font-medium text-gray-400">${timeLabel}</div>
                  <div class="text-xs text-gray-400">Available</div>
                </div>
              `;
            }
          });
          
          html += '</div>';
          container.innerHTML = html;
        } else {
          const dateObj = new Date(date + 'T00:00:00');
          container.innerHTML = `
            <div class="text-center py-8">
              <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <p class="text-gray-500 font-medium">No bookings for this date</p>
              <p class="text-sm text-gray-400 mt-2">${dateObj.toLocaleDateString('en-US', { 
                month: 'long', 
                day: 'numeric',
                year: 'numeric'
              })}</p>
            </div>
          `;
        }
      } catch (error) {
        console.error('Error loading timeline:', error);
        container.innerHTML = `
          <div class="text-center py-8 text-red-600">
            <p>Error loading bookings</p>
          </div>
        `;
      }
    }
  </script>
  </main>

  <?php render_footer(); ?>
</body>
</html>

<?php $conn->close(); ?>