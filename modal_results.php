<?php
// modal_results.php - Separate modal file for your sales dashboard

// DB CONNECTION
$conn = new mysqli("localhost", "root", "", "pos_system");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

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

// Get data based on request
$start_date = $_GET['start_date'] ?? $_POST['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? $_POST['end_date'] ?? null;

$stats = getDateRangeStats($conn, $start_date, $end_date);

// Calculate additional metrics
$total_revenue = $stats['approved_amount'] ?? 0;
$approval_rate = $stats['total'] > 0 ? round(($stats['approved'] / $stats['total']) * 100, 1) : 0;

// Format amounts
$pending_amount_formatted = '₱' . number_format($stats['pending_amount'] ?? 0, 2);
$approved_amount_formatted = '₱' . number_format($stats['approved_amount'] ?? 0, 2);
$rejected_amount_formatted = '₱' . number_format($stats['rejected_amount'] ?? 0, 2);
$total_revenue_formatted = '₱' . number_format($total_revenue, 2);

$conn->close();
?>

<!-- Results Modal - Matching your spa design theme -->
<div id="results-modal" tabindex="-1" aria-hidden="true" class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-black bg-opacity-50">
    <div class="relative p-4 w-full max-w-6xl h-auto">
        <!-- Modal content -->
        <div class="relative bg-white border border-spaAccent/30 rounded-xl shadow-lg">
            <!-- Modal header -->
            <div class="flex items-center justify-between border-b border-spaAccent/30 p-6 bg-spaGreen rounded-t-xl">
                <h3 class="text-xl font-heading text-white">
                    Sales Results Summary
                </h3>
                <button type="button" class="text-white hover:text-spaGold rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center transition" onclick="closeModal()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6m0 0L6 6m12 12"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            
            <!-- Modal body -->
            <div class="p-6 bg-spaIvory modal-body">
                <!-- Top Row: Date Range + Appointment Statistics -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    
                    <!-- Date Range Container -->
                    <div class="bg-white rounded-xl shadow-md p-4 border border-spaAccent/30">
                        <h4 class="text-sm font-semibold text-spaGreen mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-spaGold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Date Range
                        </h4>
                        <div class="space-y-3">
                            <div class="bg-spaIvory/50 rounded-lg p-3 text-center border border-spaAccent/20">
                                <div class="text-xs text-gray-600 uppercase tracking-wide mb-1">From:</div>
                                <div class="text-sm font-semibold text-spaGreen"><?php echo $start_date ?: 'All time'; ?></div>
                            </div>
                            <div class="bg-spaIvory/50 rounded-lg p-3 text-center border border-spaAccent/20">
                                <div class="text-xs text-gray-600 uppercase tracking-wide mb-1">To:</div>
                                <div class="text-sm font-semibold text-spaGreen"><?php echo $end_date ?: 'All time'; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Appointment Statistics -->
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-md p-4 border border-spaAccent/30">
                        <h4 class="text-sm font-semibold text-spaGreen mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-spaGold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Appointment Statistics
                        </h4>
                        <div class="grid grid-cols-4 gap-4">
                            <div class="bg-spaIvory/50 rounded-lg p-4 text-center border border-spaAccent/20">
                                <div class="text-2xl font-bold text-spaGreen mb-1"><?php echo $stats['total'] ?? '0'; ?></div>
                                <div class="text-xs text-gray-600 uppercase tracking-wide">Total</div>
                            </div>
                            <div class="bg-yellow-50 rounded-lg p-4 text-center border border-yellow-200">
                                <div class="text-2xl font-bold text-yellow-600 mb-1"><?php echo $stats['pending'] ?? '0'; ?></div>
                                <div class="text-xs text-gray-600 uppercase tracking-wide">Pending</div>
                            </div>
                            <div class="bg-green-50 rounded-lg p-4 text-center border border-green-200">
                                <div class="text-2xl font-bold text-green-600 mb-1"><?php echo $stats['approved'] ?? '0'; ?></div>
                                <div class="text-xs text-gray-600 uppercase tracking-wide">Approved</div>
                            </div>
                            <div class="bg-red-50 rounded-lg p-4 text-center border border-red-200">
                                <div class="text-2xl font-bold text-red-600 mb-1"><?php echo $stats['rejected'] ?? '0'; ?></div>
                                <div class="text-xs text-gray-600 uppercase tracking-wide">Rejected</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Row: Financial Summary + Quick Overview -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    
                    <!-- Financial Summary -->
                    <div class="bg-white rounded-xl shadow-md p-4 border border-spaAccent/30">
                        <h4 class="text-sm font-semibold text-spaGreen mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-spaGold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                            </svg>
                            Financial Summary (₱)
                        </h4>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                                <span class="text-sm text-gray-600">Pending Total</span>
                                <span class="text-lg font-bold text-yellow-600"><?php echo $pending_amount_formatted; ?></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg border border-green-200">
                                <span class="text-sm text-gray-600">Approved Total</span>
                                <span class="text-lg font-bold text-green-600"><?php echo $approved_amount_formatted; ?></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg border border-red-200">
                                <span class="text-sm text-gray-600">Rejected Total</span>
                                <span class="text-lg font-bold text-red-600"><?php echo $rejected_amount_formatted; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Overview -->
                    <div class="bg-white rounded-xl shadow-md p-4 border border-spaAccent/30">
                        <h4 class="text-sm font-semibold text-spaGreen mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-spaGold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Performance Metrics
                        </h4>
                        <div class="space-y-4">
                            <div class="bg-spaIvory/50 rounded-lg p-4 border border-spaAccent/20">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm text-gray-600">Approval Rate</span>
                                    <span class="text-xs px-2 py-1 rounded-full <?php echo $approval_rate >= 70 ? 'text-green-700 bg-green-100' : ($approval_rate >= 50 ? 'text-yellow-700 bg-yellow-100' : 'text-red-700 bg-red-100'); ?>">
                                        <?php echo $approval_rate >= 70 ? 'Excellent' : ($approval_rate >= 50 ? 'Good' : 'Needs Improvement'); ?>
                                    </span>
                                </div>
                                <div class="text-2xl font-bold text-spaGreen"><?php echo $approval_rate; ?>%</div>
                            </div>
                            <div class="bg-spaIvory/50 rounded-lg p-4 border border-spaAccent/20">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm text-gray-600">Total Revenue</span>
                                    <span class="text-xs text-green-700 bg-green-100 px-2 py-1 rounded-full">Confirmed</span>
                                </div>
                                <div class="text-2xl font-bold text-spaGreen"><?php echo $total_revenue_formatted; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Modal footer -->
            <div class="flex items-center justify-between border-t border-spaAccent/30 p-6 bg-white rounded-b-xl">
                <div class="text-xs text-gray-500">
                    Last updated: <span><?php echo date('M j, Y \a\t g:i A'); ?></span>
                </div>
                    <button 
                        type="button"
                        class="px-4 py-2 text-white bg-blue-600 hover:bg-blue-700 transition font-medium rounded-lg text-sm"
                        onclick="printModalPdf()">
                        Print
                    </button>
                    <button onclick="closeModal()" type="button" class="px-4 py-2 text-white bg-spaGreen hover:bg-spaGold transition font-medium rounded-lg text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

<?php include 'modal_print_product.php'; ?>

</div>

<script>
function closeModal() {
    document.getElementById('results-modal').remove();
    
}

// Close on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// Close on backdrop click
document.getElementById('results-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>