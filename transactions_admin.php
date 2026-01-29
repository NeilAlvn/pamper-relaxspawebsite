<?php

date_default_timezone_set('Asia/Manila');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
//if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//    header("Location: index.php");
//    exit();
//}

// DB CONNECTION
$conn = new mysqli("localhost", "root", "", "pos_system");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get filter parameters
$log_type = isset($_GET['type']) ? $_GET['type'] : 'all'; // all, appointments, products
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Get appointments (service transactions)
$appointments_query = "
    SELECT 
        a.id,
        a.name as customer_name,
        a.email,
        a.phone,
        a.date as appointment_date,
        a.time as appointment_time,
        a.service as service_id,
        COALESCE(s.name, 'Service') as service_name,
        s.duration,
        a.service_fee,
        a.status,
        a.created_at,
        'appointment' as log_type
    FROM appointments a
    LEFT JOIN services s ON a.service = s.id
    WHERE 1=1
";

if (!empty($date_from)) {
    $appointments_query .= " AND DATE(a.created_at) >= '" . $conn->real_escape_string($date_from) . "'";
}
if (!empty($date_to)) {
    $appointments_query .= " AND DATE(a.created_at) <= '" . $conn->real_escape_string($date_to) . "'";
}
if (!empty($search)) {
    $appointments_query .= " AND (a.name LIKE '%" . $conn->real_escape_string($search) . "%' 
                            OR a.email LIKE '%" . $conn->real_escape_string($search) . "%'
                            OR s.name LIKE '%" . $conn->real_escape_string($search) . "%')";
}

// Only include appointments if type is 'all' or 'appointments'
if ($log_type === 'all' || $log_type === 'appointments') {
    $appointments_result = $conn->query($appointments_query . " ORDER BY a.created_at DESC");
    if (!$appointments_result) {
        $appointments_error = $conn->error;
    }
} else {
    $appointments_result = null;
}

// Get product sales from orders table
$sales_query = "
    SELECT 
        o.id,
        o.customer_name,
        o.customer_email,
        o.customer_phone,
        o.customer_address,
        o.total_amount,
        o.payment_proof,
        o.status,
        o.created_at,
        'sale' as log_type
    FROM orders o
    WHERE 1=1
";

if (!empty($date_from)) {
    $sales_query .= " AND DATE(o.created_at) >= '" . $conn->real_escape_string($date_from) . "'";
}
if (!empty($date_to)) {
    $sales_query .= " AND DATE(o.created_at) <= '" . $conn->real_escape_string($date_to) . "'";
}
if (!empty($search)) {
    $sales_query .= " AND (o.customer_name LIKE '%" . $conn->real_escape_string($search) . "%' 
                        OR o.customer_email LIKE '%" . $conn->real_escape_string($search) . "%')";
}

// Only include sales if type is 'all' or 'products'
if ($log_type === 'all' || $log_type === 'products') {
    $sales_result = $conn->query($sales_query . " ORDER BY o.created_at DESC");
} else {
    $sales_result = null;
}

// Combine both result sets into a single array
$all_transactions = [];

// Add appointments to array
if ($appointments_result && $appointments_result->num_rows > 0) {
    while($row = $appointments_result->fetch_assoc()) {
        $all_transactions[] = $row;
    }
}

// Add sales to array
if ($sales_result && $sales_result->num_rows > 0) {
    while($row = $sales_result->fetch_assoc()) {
        $all_transactions[] = $row;
    }
}

// Sort by created_at (most recent first)
if (count($all_transactions) > 0) {
    usort($all_transactions, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
}

// Get statistics based on filters
$stats = [
    'total_appointments' => 0,
    'approved_appointments' => 0,
    'rejected_appointments' => 0,
    'pending_appointments' => 0,
    'appointments_revenue' => 0,
    'total_sales' => 0,
    'product_revenue' => 0,
    'total_revenue' => 0
];

// Build filtered statistics query for appointments
if ($log_type === 'all' || $log_type === 'appointments') {
    $appointments_stats_query = "
        SELECT 
            COUNT(*) as total_appointments,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_appointments,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_appointments,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_appointments,
            SUM(CASE WHEN status = 'approved' THEN service_fee ELSE 0 END) as appointments_revenue
        FROM appointments a
        LEFT JOIN services s ON a.service = s.id
        WHERE 1=1
    ";
    
    if (!empty($date_from)) {
        $appointments_stats_query .= " AND DATE(a.created_at) >= '" . $conn->real_escape_string($date_from) . "'";
    }
    if (!empty($date_to)) {
        $appointments_stats_query .= " AND DATE(a.created_at) <= '" . $conn->real_escape_string($date_to) . "'";
    }
    if (!empty($search)) {
        $appointments_stats_query .= " AND (a.name LIKE '%" . $conn->real_escape_string($search) . "%' 
                                OR a.email LIKE '%" . $conn->real_escape_string($search) . "%'
                                OR s.name LIKE '%" . $conn->real_escape_string($search) . "%')";
    }
    
    $appointments_stats_result = $conn->query($appointments_stats_query);
    if ($appointments_stats_result) {
        $appointments_stats = $appointments_stats_result->fetch_assoc();
        $stats['total_appointments'] = $appointments_stats['total_appointments'] ?? 0;
        $stats['approved_appointments'] = $appointments_stats['approved_appointments'] ?? 0;
        $stats['rejected_appointments'] = $appointments_stats['rejected_appointments'] ?? 0;
        $stats['pending_appointments'] = $appointments_stats['pending_appointments'] ?? 0;
        $stats['appointments_revenue'] = $appointments_stats['appointments_revenue'] ?? 0;
    }
}

// Build filtered statistics query for product sales
if ($log_type === 'all' || $log_type === 'products') {
    $sales_stats_query = "
        SELECT 
            COUNT(*) as total_sales,
            SUM(CASE WHEN status = 'approved' THEN total_amount ELSE 0 END) as product_revenue
        FROM orders o
        WHERE 1=1
    ";
    
    if (!empty($date_from)) {
        $sales_stats_query .= " AND DATE(o.created_at) >= '" . $conn->real_escape_string($date_from) . "'";
    }
    if (!empty($date_to)) {
        $sales_stats_query .= " AND DATE(o.created_at) <= '" . $conn->real_escape_string($date_to) . "'";
    }
    if (!empty($search)) {
        $sales_stats_query .= " AND (o.customer_name LIKE '%" . $conn->real_escape_string($search) . "%' 
                            OR o.customer_email LIKE '%" . $conn->real_escape_string($search) . "%')";
    }
    
    $sales_stats_result = $conn->query($sales_stats_query);
    if ($sales_stats_result) {
        $sales_stats = $sales_stats_result->fetch_assoc();
        $stats['total_sales'] = $sales_stats['total_sales'] ?? 0;
        $stats['product_revenue'] = $sales_stats['product_revenue'] ?? 0;
    }
}

// Calculate total revenue
$stats['total_revenue'] = ($stats['appointments_revenue'] ?? 0) + ($stats['product_revenue'] ?? 0);

// Helper functions for building URLs with preserved parameters
function buildFilterUrl($type, $search, $date_from, $date_to) {
    $params = ['type' => $type];
    if (!empty($search)) $params['search'] = $search;
    if (!empty($date_from)) $params['date_from'] = $date_from;
    if (!empty($date_to)) $params['date_to'] = $date_to;
    return 'transactions_admin.php?' . http_build_query($params);
}

function buildDateFilterUrl($new_date_from, $new_date_to, $log_type, $search) {
    $params = ['date_from' => $new_date_from, 'date_to' => $new_date_to];
    if ($log_type !== 'all') $params['type'] = $log_type;
    if (!empty($search)) $params['search'] = $search;
    return 'transactions_admin.php?' . http_build_query($params);
}
?>

<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Transactions & Logs — Pamper & Relax</title>

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
logout_button();
?>

  <header class="nav-blur text-white fixed w-full z-50 border-b border-white/10 bg-spaGreen">
    <nav>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <!-- Logo -->
          <div class="flex-shrink-0">
            <a href="index.php" class="flex items-center space-x-2">
              <img src="assets/logo/Pamper Website Logo250x100.png" alt="Pamper & Relax Spa" class="h-8 sm:h-9 md:h-10 lg:h-12 w-auto object-contain transition-all duration-300"/>
            </a>
          </div>

          <!-- Desktop menu -->
          <?php renderNavigation(); ?>

          <!-- Desktop CTA -->
          <div class="hidden md:block">
            <a href="appointments.php">
              <button class="border bg-spaGold border-spaGold text-white rounded-md font-medium hover:bg-white hover:text-spaGold hover:border-spaGold transition duration-300 flex items-center justify-center" style="width: 196.82px; height: 42.46px;">
                View Appointments →
              </button>
            </a>
          </div>
        </div>
      </div>
    </nav>
  </header>

  <main class="pt-24 pb-16 min-h-screen">
    <!-- Print Header (only visible when printing) -->
    <div class="print-header">
      <img src="assets/logo/Pamper Website Logo250x100.png" alt="Pamper & Relax Spa Logo">
      <h1>PAMPER & RELAX SPA</h1>
      <p>TRANSACTIONS & ACTIVITY LOGS REPORT</p>
      <div class="report-info">
        <p>Report Generated: <?php echo date('F d, Y g:i A'); ?></p>
        <?php if (!empty($date_from) || !empty($date_to)): ?>
          <p>Period: <?php echo !empty($date_from) ? date('M d, Y', strtotime($date_from)) : 'Beginning'; ?> to <?php echo !empty($date_to) ? date('M d, Y', strtotime($date_to)) : 'Present'; ?></p>
        <?php endif; ?>
        <?php if ($log_type !== 'all'): ?>
          <p>Filter: <?php echo ucfirst($log_type); ?> Only</p>
        <?php endif; ?>
        <?php if (!empty($search)): ?>
          <p>Search Term: <?php echo htmlspecialchars($search); ?></p>
        <?php endif; ?>
      </div>
    </div>
    
    <div class="max-w-[1600px] mx-auto px-8 sm:px-12 lg:px-16">
      
      <!-- Header -->
      <div class="text-center mb-12">
        <p class="text-spaGold uppercase tracking-widest text-sm font-medium mb-3">
          Management Portal
        </p>
        <h1 class="font-heading text-4xl md:text-5xl text-spaGreen mb-4">
          Transactions & Activity Logs
        </h1>
        <p class="text-gray-600 max-w-2xl mx-auto">
          Complete audit trail of all appointments, bookings, and business activities.
        </p>
      </div>

      <!-- Statistics Cards - Modified to 5 columns on large screens -->
      <div style="margin-bottom: 15px; display: none;" class="print-summary-title">
        <h2 style="font-size: 12pt; font-weight: bold; color: #000; margin: 0;">SUMMARY STATISTICS</h2>
      </div>
      
      <!-- Print-only simple summary -->
      <div class="print-summary-simple" style="display: none; margin-bottom: 15px; font-size: 10pt;">
        <strong>Total:</strong> <?php echo $stats['total_appointments']; ?> | 
        <strong>Approved:</strong> <?php echo $stats['approved_appointments']; ?> | 
        <strong>Pending:</strong> <?php echo $stats['pending_appointments']; ?> | 
        <strong>Rejected:</strong> <?php echo $stats['rejected_appointments']; ?> | 
        <strong>Revenue:</strong> ₱<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?> | 
        <strong>Potential Revenue:</strong> ₱<?php 
          // Calculate potential revenue (sum of ALL transactions regardless of status)
          $potential_revenue = 0;
          foreach($all_transactions as $transaction) {
            if ($transaction['log_type'] === 'sale') {
              $potential_revenue += $transaction['total_amount'];
            } else {
              $potential_revenue += $transaction['service_fee'];
            }
          }
          echo number_format($potential_revenue, 2); 
        ?>
      </div>
      
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-8 mb-10 stats-cards">
        <div class="bg-white rounded-xl shadow-md p-8 border border-spaAccent/30">
          <div class="flex items-center space-x-4">
            <div class="bg-spaIvory rounded-full p-4 flex-shrink-0">
              <svg class="w-10 h-10 text-spaGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Total</p>
              <p class="text-2xl font-bold text-spaGreen"><?php echo $stats['total_appointments']; ?></p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-8 border border-spaAccent/30">
          <div class="flex items-center space-x-4">
            <div class="bg-green-50 rounded-full p-4 flex-shrink-0">
              <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Approved</p>
              <p class="text-2xl font-bold text-green-600"><?php echo $stats['approved_appointments']; ?></p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-8 border border-spaAccent/30">
          <div class="flex items-center space-x-4">
            <div class="bg-yellow-50 rounded-full p-4 flex-shrink-0">
              <svg class="w-10 h-10 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Pending</p>
              <p class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending_appointments']; ?></p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-8 border border-spaAccent/30">
          <div class="flex items-center space-x-4">
            <div class="bg-red-50 rounded-full p-4 flex-shrink-0">
              <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Rejected</p>
              <p class="text-2xl font-bold text-red-600"><?php echo $stats['rejected_appointments']; ?></p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-8 border border-spaAccent/30">
          <div class="flex items-center space-x-4">
            <div class="bg-blue-50 rounded-full p-4 flex-shrink-0">
              <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Revenue</p>
              <p class="text-xl font-bold text-blue-600">₱<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl shadow-md p-6 mb-8 border border-spaAccent/30">
        <form method="GET" action="transactions_admin.php" class="grid grid-cols-1 md:grid-cols-5 gap-4">
          
          <!-- Hidden field to preserve type filter -->
          <?php if ($log_type !== 'all'): ?>
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($log_type); ?>">
          <?php endif; ?>
          
          <!-- Search -->
          <div class="md:col-span-2">
            <input 
              type="text" 
              name="search" 
              placeholder="Search by customer, email, or service..." 
              value="<?php echo htmlspecialchars($search); ?>"
              class="w-full px-4 py-3 rounded-lg border border-spaAccent bg-spaIvory/30 focus:outline-none focus:ring-2 focus:ring-spaGold focus:border-transparent transition"
            >
          </div>

          <!-- Date From -->
          <div>
            <input 
              type="date" 
              name="date_from" 
              value="<?php echo htmlspecialchars($date_from); ?>"
              class="w-full px-4 py-3 rounded-lg border border-spaAccent bg-spaIvory/30 focus:outline-none focus:ring-2 focus:ring-spaGold focus:border-transparent transition"
              placeholder="From Date"
            >
          </div>

          <!-- Date To -->
          <div>
            <input 
              type="date" 
              name="date_to" 
              value="<?php echo htmlspecialchars($date_to); ?>"
              class="w-full px-4 py-3 rounded-lg border border-spaAccent bg-spaIvory/30 focus:outline-none focus:ring-2 focus:ring-spaGold focus:border-transparent transition"
              placeholder="To Date"
            >
          </div>

          <!-- Submit -->
          <button 
            type="submit"
            class="px-6 py-3 bg-spaGold hover:bg-spaGreen text-white rounded-lg transition duration-300 font-medium"
          >
            Filter
          </button>
        </form>

        <!-- Quick Filters -->
        <div class="flex flex-wrap gap-2 mt-4">
          <a href="<?php echo buildFilterUrl('all', $search, $date_from, $date_to); ?>" 
             class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $log_type === 'all' ? 'bg-spaGreen text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> transition">
            All Transactions
          </a>
          <a href="<?php echo buildFilterUrl('appointments', $search, $date_from, $date_to); ?>" 
             class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $log_type === 'appointments' ? 'bg-spaGreen text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> transition">
            Appointments Only
          </a>
          <a href="<?php echo buildFilterUrl('products', $search, $date_from, $date_to); ?>" 
             class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $log_type === 'products' ? 'bg-spaGreen text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> transition">
            Product Sales Only
          </a>
          <a href="<?php echo buildDateFilterUrl(date('Y-m-d'), date('Y-m-d'), $log_type, $search); ?>" 
             class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
            Today
          </a>
          <a href="<?php echo buildDateFilterUrl(date('Y-m-d', strtotime('-7 days')), date('Y-m-d'), $log_type, $search); ?>" 
             class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
            Last 7 Days
          </a>
          <a href="<?php echo buildDateFilterUrl(date('Y-m-01'), date('Y-m-t'), $log_type, $search); ?>" 
             class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
            This Month
          </a>
        </div>
      </div>

      <!-- Transactions Table -->
      <div style="margin: 20px 0 10px; display: none;" class="print-table-title">
        <h2 style="font-size: 12pt; font-weight: bold; color: #000; margin: 0;">TRANSACTION DETAILS</h2>
      </div>
      <div class="bg-white rounded-xl shadow-lg border border-spaAccent/30 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-spaGreen text-white">
              <tr>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">ID</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Customer</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Service/Product</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Appointment Date</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Amount</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Status</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Created</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Type</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-spaAccent/30">
              <?php 
              if (count($all_transactions) > 0): 
                foreach($all_transactions as $row): 
                  $is_sale = ($row['log_type'] === 'sale');
              ?>
                <tr class="hover:bg-spaIvory/50 transition">
                  <td class="px-6 py-4">
                    <span class="text-sm font-mono text-gray-600">#<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="font-medium text-spaGreen"><?php echo htmlspecialchars($row['customer_name']); ?></div>
                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($is_sale ? $row['customer_email'] : $row['email']); ?></div>
                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($is_sale ? $row['customer_phone'] : $row['phone']); ?></div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="font-medium text-gray-800">
                      <?php 
                      if ($is_sale) {
                          echo "Order #" . str_pad($row['id'], 4, '0', STR_PAD_LEFT);
                      } else {
                          echo htmlspecialchars($row['service_name']);
                      }
                      ?>
                    </div>
                    <?php if ($is_sale): ?>
                      <div class="text-xs text-gray-500">Total: ₱<?php echo number_format($row['total_amount'], 2); ?></div>
                      <?php if (!empty($row['customer_address'])): ?>
                        <div class="text-xs text-gray-500">Address: <?php echo htmlspecialchars($row['customer_address']); ?></div>
                      <?php endif; ?>
                    <?php elseif (!empty($row['duration'])): ?>
                      <div class="text-xs text-gray-500">Duration: <?php echo $row['duration'] == 1 ? '1 hour' : $row['duration'] . ' hours'; ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="px-6 py-4">
                    <?php if ($is_sale): ?>
                      <span class="text-xs text-gray-400 italic">N/A</span>
                    <?php else: ?>
                      <div class="text-sm text-gray-700"><?php echo date('M d, Y', strtotime($row['appointment_date'])); ?></div>
                      <div class="text-xs text-gray-500"><?php echo date('g:i A', strtotime($row['appointment_time'])); ?></div>
                    <?php endif; ?>
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
                  <td class="px-6 py-4">
                    <div class="text-sm text-gray-700"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></div>
                    <div class="text-xs text-gray-500"><?php echo date('g:i A', strtotime($row['created_at'])); ?></div>
                  </td>
                  <td class="px-6 py-4">
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold <?php echo $is_sale ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                      <?php echo $is_sale ? 'Product Order' : 'Appointment'; ?>
                    </span>
                  </td>
                </tr>
              <?php 
                endforeach;
              else: 
              ?>
                <tr>
                  <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="text-lg font-medium">No transactions found</p>
                    <p class="text-sm mt-1">Try adjusting your filters or search terms</p>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Export Options -->
      <div class="mt-8 flex justify-end space-x-4 no-print">
        <button onclick="window.print()" class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition duration-300 font-medium inline-flex items-center space-x-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
          </svg>
          <span>Print Report</span>
        </button>
      </div>

    </div>
  </main>

  <?php render_footer(); ?>

  <style>
    @media print {
      /* Hide screen-only elements */
      header, footer, button, .no-print, 
      .bg-white.rounded-xl.shadow-md.p-6.mb-8,
      .text-center.mb-12 { 
        display: none !important; 
      }
      
      body { 
        background: white;
        color: black;
        font-size: 10pt;
        font-family: Arial, sans-serif;
      }
      
      /* Simple print header - no borders or heavy styling */
      .print-header {
        display: block !important;
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border: none;
      }
      
      .print-header img {
        height: 40px;
        margin: 0 auto 15px;
      }
      
      .print-header h1 {
        color: #000;
        font-size: 16pt;
        font-weight: bold;
        margin: 0 0 5px 0;
        letter-spacing: 1px;
      }
      
      .print-header > p {
        color: #000;
        font-size: 11pt;
        margin: 0 0 15px 0;
        font-weight: normal;
      }
      
      .print-header .report-info {
        text-align: left;
        max-width: 600px;
        margin: 15px auto 0;
        font-size: 9pt;
      }
      
      .print-header .report-info p {
        margin: 3px 0;
        color: #000;
      }
      
      /* Show print-only section titles - simplified */
      .print-summary-title,
      .print-table-title {
        display: block !important;
        page-break-after: avoid;
      }
      
      .print-summary-title h2,
      .print-table-title h2 {
        font-size: 11pt !important;
        font-weight: bold !important;
        margin: 20px 0 10px 0 !important;
        padding: 0 !important;
        border-bottom: none !important;
      }
      
      /* Show simple text summary */
      .print-summary-simple {
        display: block !important;
        margin-bottom: 20px !important;
        font-size: 10pt !important;
        line-height: 1.6 !important;
      }
      
      /* Hide the entire statistics cards grid in print */
      .stats-cards {
        display: none !important;
      }
      
      /* Hide Export Options / Print Report button section */
      .mt-8.flex.justify-end,
      .mt-8 {
        display: none !important;
      }
      
      main {
        padding-top: 0 !important;
        padding-bottom: 0 !important;
      }
      
      /* Simple table styling */
      table { 
        page-break-inside: auto;
        width: 100%;
        border-collapse: collapse;
        margin-top: 5px;
      }
      
      tr { 
        page-break-inside: avoid; 
        page-break-after: auto; 
      }
      
      thead th {
        background: #f0f0f0 !important;
        color: #000 !important;
        padding: 8px 5px !important;
        font-size: 8pt !important;
        border: 1px solid #666 !important;
        font-weight: bold !important;
        text-align: left !important;
      }
      
      tbody td {
        padding: 6px 5px !important;
        font-size: 8pt !important;
        border: 1px solid #999 !important;
        color: #000 !important;
        vertical-align: top !important;
      }
      
      tbody tr:nth-child(even) {
        background-color: #fafafa !important;
      }
      
      /* Simplify status badges */
      .inline-flex {
        display: inline !important;
        background: transparent !important;
        border: none !important;
        padding: 0 !important;
        font-size: 8pt !important;
        color: #000 !important;
        font-weight: normal !important;
      }
      
      .bg-yellow-100, .bg-green-100, .bg-red-100,
      .bg-purple-100, .bg-blue-100 {
        background: transparent !important;
        border: none !important;
      }
      
      /* Remove all color classes */
      .text-spaGreen, .text-gray-700, .text-gray-500, 
      .text-gray-600, .text-gray-800,
      .text-yellow-800, .text-green-800, .text-red-800,
      .text-purple-800, .text-blue-800,
      .text-green-600, .text-yellow-600, .text-red-600, .text-blue-600 {
        color: #000 !important;
      }
      
      .font-medium, .font-semibold {
        font-weight: normal !important;
      }
      
      .font-bold {
        font-weight: bold !important;
      }
      
      /* Clean container */
      .max-w-\\[1600px\\] {
        max-width: 100% !important;
        padding: 0 15px !important;
      }
      
      /* Remove all decorative elements */
      .rounded-xl, .rounded-lg, .rounded-full, .rounded-md {
        border-radius: 0 !important;
      }
      
      .shadow-lg, .shadow-md {
        box-shadow: none !important;
      }
      
      .bg-white {
        background: white !important;
      }
      
      /* Ensure table wrapper has no styling */
      .bg-white.rounded-xl.shadow-lg {
        background: transparent !important;
        border: none !important;
        page-break-inside: auto;
      }
      
      .overflow-hidden {
        overflow: visible !important;
      }
      
      .overflow-x-auto {
        overflow-x: visible !important;
      }
    }
    
    /* Hide print elements on screen */
    .print-header,
    .print-summary-title,
    .print-table-title {
      display: none;
    }
  </style>
</body>
</html>

<?php $conn->close(); ?>