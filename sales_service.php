<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
    // DB CONNECTION
    $conn = new mysqli("localhost", "root", "", "pos_system");
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
include 'reference.php'; 
processLogin($conn);
    function getOrderItems($conn, $order_id) {
        $stmt = $conn->prepare("SELECT product_name FROM order_items WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row['product_name'];
        }
        return $items;
    }

    // Function to get statistics based on date range
    function getDateRangeStats($conn, $start_date = null, $end_date = null) {
        $query = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END) as pending_amount,
            SUM(CASE WHEN status = 'approved' THEN total_amount ELSE 0 END) as approved_amount,
            SUM(CASE WHEN status = 'rejected' THEN total_amount ELSE 0 END) as rejected_amount,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM orders WHERE 1=1";
        $params = [];
        $types = "";
        
        if ($start_date && $end_date) {
            $query .= " AND DATE(created_at) BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
            $types .= "ss";
        }
        
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    //Handles the get appointments stats
    function getAppointmentStats($conn) {
        $query = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected
            FROM appointments
        ";

        $result = $conn->query($query);
        return $result->fetch_assoc();
    }

    //Handles the appointment amount stats
    function getAppointmentAmountStats($conn) {
        $query = "
            SELECT
                SUM(CASE WHEN a.status = 'pending' THEN s.price ELSE 0 END) AS pending_amount,
                SUM(CASE WHEN a.status = 'approved' THEN s.price ELSE 0 END) AS approved_amount,
                SUM(CASE WHEN a.status = 'rejected' THEN s.price ELSE 0 END) AS rejected_amount
            FROM appointments a
            LEFT JOIN services s ON CAST(a.service AS UNSIGNED) = s.id
        ";

        $result = $conn->query($query);
        return $result->fetch_assoc();
    }


    // Handle AJAX request for date range stats
    if (isset($_POST['action']) && $_POST['action'] === 'get_date_range_stats') {
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        
        $stats = getDateRangeStats($conn, $start_date, $end_date);
        
        // Calculate additional metrics
        $total_revenue = $stats['approved_amount'] ?? 0;
        $approval_rate = $stats['total'] > 0 ? round(($stats['approved'] / $stats['total']) * 100, 1) : 0;
        
        // Format amounts
        $stats['pending_amount_formatted'] = '₱' . number_format($stats['pending_amount'] ?? 0, 2);
        $stats['approved_amount_formatted'] = '₱' . number_format($stats['approved_amount'] ?? 0, 2);
        $stats['rejected_amount_formatted'] = '₱' . number_format($stats['rejected_amount'] ?? 0, 2);
        $stats['total_revenue_formatted'] = '₱' . number_format($total_revenue, 2);
        $stats['approval_rate'] = $approval_rate;
        $stats['date_from'] = $start_date;
        $stats['date_to'] = $end_date;
        
        header('Content-Type: application/json');
        echo json_encode($stats);
        exit();
    }

    // Handle status update via GET (using links)
    if (isset($_GET['action']) && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $action = $_GET['action'];
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE appointments SET status = 'approved' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE appointments SET status = 'rejected' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
        
        // Redirect to maintain filter and search state
        header("Location: sales_service.php?filter=" . urlencode($filter) . "&search=" . urlencode($search));
        exit();
    }

    // Get filter
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    // Build query
    $query = "
        SELECT 
            a.*, 
            s.name AS service_name,
            s.price AS service_price
        FROM appointments a
        LEFT JOIN services s ON CAST(a.service AS UNSIGNED) = s.id
        WHERE 1=1
    ";
    
    if ($filter !== 'all') {
        $query .= " AND a.status = '" . $conn->real_escape_string($filter) . "'";
    }

    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $query .= " AND (
            a.name LIKE '%$search%' OR 
            a.email LIKE '%$search%' OR 
            s.name LIKE '%$search%'
        )";
    }

    $query .= " ORDER BY a.created_at DESC";

    
    $result = $conn->query($query);

    // Get default statistics (all time)
    $stats = getDateRangeStats($conn);
    $appointmentStats = getAppointmentStats($conn);
    $appointmentAmounts = getAppointmentAmountStats($conn);
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.getElementById('start-date');
    const endDateInput = document.getElementById('end-date');
    const generateBtn = document.getElementById('generate-results-btn');

    // Set max date to today for both inputs
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const today = `${year}-${month}-${day}`;

    startDateInput.max = today;
    endDateInput.max = today;

    // Date validation
    startDateInput.addEventListener('change', function() {
        const startDate = this.value;
        if (startDate) {
            endDateInput.setAttribute('min', startDate);
            
            // Clear end date if it's now invalid
            if (endDateInput.value && endDateInput.value < startDate) {
                endDateInput.value = '';
                showAlert('End date cleared because it was earlier than start date.', 'info');
            }
        } else {
            endDateInput.removeAttribute('min');
        }
        validateDates();
    });

    endDateInput.addEventListener('change', function() {
        validateDates();
    });

    function validateDates() {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        
        // Enable/disable button based on date selection
        if (startDate && endDate) {
            generateBtn.disabled = false;
        } else {
            generateBtn.disabled = true;
        }
        
        // Validate date order
        if (startDate && endDate && endDate < startDate) {
            showAlert('End date cannot be earlier than start date.', 'error');
            endDateInput.value = '';
            generateBtn.disabled = true;
        }
    }

    function showAlert(message, type = 'info') {
        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `fixed top-20 right-4 p-4 rounded-lg text-white z-50 ${
            type === 'error' ? 'bg-red-500' : 'bg-blue-500'
        }`;
        alertDiv.textContent = message;
        
        document.body.appendChild(alertDiv);
        
        // Remove after 3 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 3000);
    }

    function showButtonLoading(show) {
        const btnText = generateBtn.querySelector('.btn-text');
        const btnLoading = generateBtn.querySelector('.btn-loading');
        
        if (show) {
            btnText.classList.add('hidden');
            btnLoading.classList.remove('hidden');
            generateBtn.disabled = true;
        } else {
            btnText.classList.remove('hidden');
            btnLoading.classList.add('hidden');
            generateBtn.disabled = false;
        }
    }

    // Generate Results button click
    generateBtn.addEventListener('click', function() {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        // Final validation
        if (!startDate || !endDate) {
            showAlert('Please select both start and end dates.', 'error');
            return;
        }

        if (startDate > endDate) {
            showAlert('Start date cannot be later than end date.', 'error');
            return;
        }

        // Show loading state
        showButtonLoading(true);
        
        // Load the modal
        loadModal(startDate, endDate);
    });

    function loadModal(startDate, endDate) {
        // Clear any existing modal
        const modalContainer = document.getElementById('modal-container');
        modalContainer.innerHTML = '';

        // Fetch the modal from separate PHP file
        fetch(`modal_results_service.php?start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            // Inject the modal HTML into the container
            modalContainer.innerHTML = html;
            
            // Show the modal
            const modal = document.getElementById('results-modal');
            if (modal) {
                modal.classList.add('flex');
                
            }
            
            // Hide loading state
            showButtonLoading(false);
            showAlert('Results loaded successfully!', 'info');
        })
        .catch(error => {
            console.error('Error loading modal:', error);
            showButtonLoading(false);
            showAlert('Error loading results. Please try again.', 'error');
            
            // Fallback: show basic error modal
            modalContainer.innerHTML = `
                <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
                    <div class="bg-white p-6 rounded-lg shadow-lg max-w-md">
                        <h3 class="text-lg font-semibold mb-4 text-red-600">Error Loading Results</h3>
                        <p class="text-gray-700 mb-4">There was an error loading the results. Please check your connection and try again.</p>
                        <button onclick="document.getElementById('modal-container').innerHTML = ''; document.body.style.overflow = 'auto';" 
                                class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Close</button>
                    </div>
                </div>
            `;
        });
    }

    // Initialize
    validateDates();
    
    // Optional: Set default date range (last 7 days)
    function setDefaultDateRange() {
        const today = new Date();
        const lastWeek = new Date(today.getTime() - (7 * 24 * 60 * 60 * 1000));
        
        startDateInput.value = lastWeek.toISOString().split('T')[0];
        endDateInput.value = today.toISOString().split('T')[0];
        validateDates();
    }
    
    // Uncomment to set default dates
    // setDefaultDateRange();
});

// Global function to close modal (called from modal-results.php)
window.closeModal = function() {
    const modalContainer = document.getElementById('modal-container');
    modalContainer.innerHTML = '';
    document.body.style.overflow = 'auto';
};

document.querySelectorAll('.openServiceModal').forEach(img => {
    img.addEventListener('click', () => {
        const image = img.dataset.image;
        const service = img.dataset.service;

        fetch(`modal_sales_service.php?image=${encodeURIComponent(image)}&service=${service}`)
            .then(res => res.text())
            .then(html => {
                document.body.insertAdjacentHTML('beforeend', html);
            });
    });
});

</script>
<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Sales Management â€” Pamper & Relax</title>

<!-- Date and Time --> 
  <link href="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.css" rel="stylesheet" />

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

      <script>
    document.addEventListener('DOMContentLoaded', function() {
        const resultsBtn = document.getElementById('results-btn');
        const modal = document.getElementById('results-modal');
        const closeModalBtns = document.querySelectorAll('#close-modal, #close-modal-btn');
        const loadingIndicator = document.getElementById('loading-indicator');
        const modalContent = document.getElementById('modal-content');

        // Show modal
        function showModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // Hide modal
        function hideModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Close modal events
        closeModalBtns.forEach(btn => {
            btn.addEventListener('click', hideModal);
        });

        // Close modal when clicking backdrop
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                hideModal();
            }
        });

        // Results button click event
        resultsBtn.addEventListener('click', function() {
            const startDate = document.getElementById('datepicker-range-start').value;
            const endDate = document.getElementById('datepicker-range-end').value;

            if (!startDate || !endDate) {
                alert('Please select both start and end dates.');
                return;
            }

            if (startDate > endDate) {
                alert('Start date cannot be later than end date.');
                return;
            }

            showModal();
            
            // Show loading
            loadingIndicator.classList.remove('hidden');
            modalContent.classList.add('hidden');

            // Fetch data from server
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_date_range_stats&start_date=${startDate}&end_date=${endDate}`
            })
            .then(response => response.json())
            .then(data => {
                // Hide loading
                loadingIndicator.classList.add('hidden');
                modalContent.classList.remove('hidden');

                // Update modal content
                document.getElementById('date-from').textContent = data.date_from || 'N/A';
                document.getElementById('date-to').textContent = data.date_to || 'N/A';
                
                document.getElementById('total-count').textContent = data.total || '0';
                document.getElementById('pending-count').textContent = data.pending || '0';
                document.getElementById('approved-count').textContent = data.approved || '0';
                document.getElementById('rejected-count').textContent = data.rejected || '0';
                
                document.getElementById('pending-total').textContent = data.pending_amount_formatted || '₱0.00';
                document.getElementById('approved-total').textContent = data.approved_amount_formatted || '₱0.00';
                document.getElementById('rejected-total').textContent = data.rejected_amount_formatted || '₱0.00';
                
                document.getElementById('approval-rate').textContent = data.approval_rate + '%';
                document.getElementById('total-revenue').textContent = data.total_revenue_formatted || '₱0.00';
                
                // Update timestamp
                document.getElementById('last-updated').textContent = new Date().toLocaleString();
            })
            .catch(error => {
                console.error('Error:', error);
                // Hide loading and show error
                loadingIndicator.classList.add('hidden');
                modalContent.classList.remove('hidden');
                alert('Error loading data. Please try again.');
            });
        });
    });
    </script>
  
    <script>
    document.addEventListener('DOMContentLoaded', function() {
    // Initialize the date range picker
    const dateRangePicker = new DateRangePicker(document.getElementById('date-range-picker'), {
        // Options
        format: 'yyyy-mm-dd',
        autohide: true,
        orientation: 'bottom auto',
        rangeSeparator: ' to ',
        });
    });
    </script>

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

  <script>
  document.addEventListener('click', function (e) {
      const img = e.target.closest('.openServiceModal');
      if (!img) return;

      const image = img.dataset.image;
      const service = img.dataset.service;

      // Prevent opening multiple modals
      if (document.getElementById('service-image-modal')) return;

      fetch(`modal_sales_service.php?image=${encodeURIComponent(image)}&service=${service}`)
          .then(response => response.text())
          .then(html => {
              document.body.insertAdjacentHTML('beforeend', html);
          })
          .catch(err => {
              console.error('Modal load failed:', err);
          });
  });
  </script>

  <script>
  document.addEventListener('click', function (e) {

      // Close button click
      if (e.target.closest('.closeServiceModal')) {
          document.getElementById('service-image-modal')?.remove();
          return;
      }

      // Backdrop click
      if (e.target.id === 'service-image-modal') {
          document.getElementById('service-image-modal')?.remove();
      }
  });

  // ESC key support
  document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
          document.getElementById('service-image-modal')?.remove();
      }
  });
</script>


</head>
<body class="font-body text-gray-800 bg-spaIvory antialiased">
<?php 


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

      <?php 
         
          renderNavigation(); 
          ?>

      <!-- Desktop CTA -->
      <div class="hidden md:block">
            <a href="sales.php">
                 <button class="border bg-spaGold border-spaGold text-white rounded-md font-medium hover:bg-white hover:text-spaGold hover:border-spaGold transition duration-300" style="width: 191.91px; height: 42px;">
                
                    Get Product →
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
          Service Sales Dashboard
        </h1>
        <p class="text-gray-600 max-w-2xl mx-auto">
          Manage and track all customer service sales with ease.
        </p>
      </div>

        <!-- Updated Date Range Picker with Modal Caller -->
        <div class="w-full flex justify-center pb-8">
            <div class="flex justify-center items-center gap-6">  
                <div class="flex items-center gap-4">
                    <!-- Start Date -->
                    <div class="relative">
                        <label for="start-date" class="block text-xs text-gray-600 mb-1">From Date</label>
                        <input 
                            id="start-date" 
                            name="start_date" 
                            type="date" 
                            class="block w-40 px-3 py-2.5 bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>
                    
                    <span class="text-gray-500 mt-5 text-sm font-medium">to</span>
                    
                    <!-- End Date -->
                    <div class="relative">
                        <label for="end-date" class="block text-xs text-gray-600 mb-1">To Date</label>
                        <input 
                            id="end-date" 
                            name="end_date" 
                            type="date" 
                            class="block w-40 px-3 py-2.5 bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>
                </div>
                
                <!-- Results Button -->
                <button 
                    type="button"
                    id="generate-results-btn" 
                    class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-300 transition-colors duration-200 mt-5 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span class="btn-text">Generate Results</span>
                    <span class="btn-loading hidden">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Loading...
                    </span>
                </button>
            </div>
            <?php include 'modal_print_service.php'; ?>
        </div>

        <!-- Modal Container (this is where the modal will be injected) -->
        <div id="modal-container"></div>

        


      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
        <div class="bg-white rounded-xl shadow-md p-6 border border-spaAccent/30">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 uppercase tracking-wide">Total</p>
              <p class="text-3xl font-bold text-spaGreen mt-1"><?php echo $appointmentStats['total']; ?></p>
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
              <p class="text-3xl font-bold text-yellow-600 mt-1"><?php echo $appointmentStats['pending']; ?></p>
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
              <p class="text-3xl font-bold text-green-600 mt-1"><?php echo $appointmentStats['approved']; ?></p>
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
              <p class="text-3xl font-bold text-red-600 mt-1"><?php echo $appointmentStats['rejected']; ?></p>
            </div>
            <div class="bg-red-50 rounded-full p-3">
              <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
          </div>
        </div>
      </div>

    <!-- Pending Totals -->

    <div class="flex justify-center items-center gap-6 w-full pb-10">
      <div class="bg-white rounded-xl shadow-md p-6 border border-spaAccent/30">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 uppercase tracking-wide">Pending Total ₱</p>
              <p class="text-3xl font-bold text-yellow-600 mt-1"><?php echo $appointmentAmounts['pending_amount']; ?></p>
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
              <p class="text-sm text-gray-600 uppercase tracking-wide">Approved Total ₱</p>
              <p class="text-3xl font-bold text-green-600 mt-1"><?php echo $appointmentAmounts['approved_amount']; ?></p>
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
              <p class="text-sm text-gray-600 uppercase tracking-wide">Rejected Total ₱</p>
              <p class="text-3xl font-bold text-red-600 mt-1"><?php echo $appointmentAmounts['rejected_amount']; ?></p>
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
        <form method="GET" action="sales_service.php" class="flex flex-col md:flex-row gap-4">
          
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
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Date & Time</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Service</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Service Fee</th>
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
                    </td>
                    <td class="px-6 py-4">
                      <div class="text-sm text-gray-700"><?php echo htmlspecialchars($row['email']); ?></div>
                      <div class="text-sm text-gray-500"><?php echo htmlspecialchars($row['phone']); ?></div>
                    </td>
                    <td class="px-6 py-4">
                      <div class="text-sm font-medium text-gray-800">
                        <?php echo date('M d, Y', strtotime($row['date'])); ?>
                        <br>
                        <?php echo date('h:i A', strtotime($row['time'])); ?>
                      </div>
                    </td>
                    <td class="px-6 py-4">
                      <div class="text-sm text-gray-700">
                        <?php echo htmlspecialchars($row['service_name']); ?>
                      </div>
                    </td>
                    <td class="px-6 py-4">
                      <span class="text-sm font-semibold text-spaGreen">₱<?php echo number_format($row['service_fee'], 2); ?></span>
                    </td>
                    <td class="px-6 py-4">
                      <?php if (!empty($row['image']) && file_exists($row['image'])): ?>
                        <img 
                          src="<?php echo $row['image']; ?>" 
                          data-image="<?php echo $row['image']; ?>"
                          data-service="<?php echo $row['service']; ?>"
                          class="w-16 h-16 object-cover rounded-lg border-2 border-spaGold hover:scale-110 transition cursor-pointer openServiceModal"
                          alt="Service Image"
                        >
                      <?php else: ?>
                        <span class="text-xs text-gray-400 italic">No image</span>
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
                          <a href="sales_service.php?action=approve&id=<?php echo $row['id']; ?>" 
                             class="p-2 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg transition"
                             onclick="return confirm('Approve this appointment?')"
                             title="Approve">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                          </a>
                          <a href="sales_service.php?action=reject&id=<?php echo $row['id']; ?>" 
                             class="p-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg transition"
                             onclick="return confirm('Reject this appointment?')"
                             title="Reject">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                          </a>
                        <?php endif; ?>
                        <a href="sales_service.php?action=delete&id=<?php echo $row['id']; ?>" 
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
                  <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-lg font-medium">No Products found</p>
                    <p class="text-sm mt-1">Try adjusting your filters or search terms</p>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>

  <footer class="bg-spaGreen text-white">
    <div class="max-w-6xl mx-auto px-8 md:px-12 py-16 grid md:grid-cols-3 gap-8">
      <img src="assets/logo/Pamper Website Logo250x100.png" alt="Pamper & Relax Logo" class="h-14">
      <div>
        <h4 class="font-semibold mb-3">About</h4>
        <ul class="space-y-1 text-sm opacity-80">
          <li>About Us</li><li>Contacts</li><li>Locations</li>
        </ul>
      </div>
    </div>
  </footer>
<script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.js"></script>
<script>

document.addEventListener('click', function(e) {
    if (e.target && e.target.id === 'printModalServicePdf') {
        const modal = document.getElementById('results-modal');
        if (!modal) return;

        // Get only modal body content (so header/footer/buttons are excluded)
        const modalBody = modal.querySelector('.p-6.bg-spaIvory'); // adjust selector if needed

        const printContents = modalBody.innerHTML;

        const printWindow = window.open('', '', 'height=800,width=1000');
        printWindow.document.write('<html><head><title>Sales Report</title></head><body>');
        printWindow.document.write('<div style="text-align:center;font-family:sans-serif;color:black;">');
        printWindow.document.write(printContents);
        printWindow.document.write('</div></body></html>');
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    }
});
  
//Toast JavaScript
function showToast() {
    const toast = document.getElementById('toast-simple');
    toast.classList.remove('hidden');

    // auto-hide after 3 seconds
    setTimeout(() => {
        hideToast();
    }, 3000);
}

function hideToast() {
    const toast = document.getElementById('toast-simple');
    toast.classList.add('hidden');
}
</script>


<!-- Simple Toast IDK HOW IT WORKS -->
    <div
        id="toast-simple"
        class="fixed bottom-5 right-5 hidden flex items-center w-full max-w-sm p-4
         !bg-spaWhite !opacity-100
         rounded-lg shadow-xl border border-spaAccent z-50"
        role="alert"
    >
        <svg class="w-5 h-5 text-fg-brand" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="m12 18-7 3 7-18 7 18-7-3Zm0 0v-5"/>
          <div class="ms-2.5 text-sm border-s border-default ps-3.5 text-black">
            Reports downloaded successfully!
          </div>
        </svg>
        <button type="button" class="ms-auto h-8 w-8" onclick="hideToast()">✕</button>
    </div>

</body>
</html>

<?php $conn->close(); ?>
