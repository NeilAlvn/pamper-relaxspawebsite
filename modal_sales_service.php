<?php
$conn = new mysqli("localhost", "root", "", "pos_system");
if ($conn->connect_error) die("Connection failed");

$image = $_GET['image'] ?? '';
$service_id = $_GET['service'] ?? null;
$status = $_GET['status'] ?? ''; // Status of the service/appointment
$id = $_GET['id'] ?? null; // Service/appointment ID

// Fallback
$imagePath = (!empty($image) && file_exists($image)) 
    ? $image 
    : 'uploads/services/default.jpg';

// Get service name
$service_name = 'Service Preview';
if ($service_id) {
    $stmt = $conn->prepare("SELECT name FROM services WHERE id = ?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $service_name = $stmt->get_result()->fetch_assoc()['name'] ?? $service_name;
}
$conn->close();
?>

<div id="service-image-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60">
    <div class="bg-white rounded-xl shadow-xl max-w-3xl w-full mx-4 flex flex-col">
        
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-spaGreen">
                <?php echo htmlspecialchars($service_name); ?>
            </h3>
            <button class="closeServiceModal text-gray-500 hover:text-red-500 text-xl">✕</button>
        </div>

        <!-- Body -->
        <div class="p-6 flex justify-center bg-spaIvory">
            <img 
                src="<?php echo htmlspecialchars($imagePath); ?>" 
                class="max-h-[450px] w-auto rounded-lg border border-spaGold shadow-md object-contain"
                alt="Service Image"
            >
        </div>

        <!-- Footer Close Button -->
        <div class="px-6 py-4 border-t text-right">
            <button class="closeServiceModal px-4 py-2 bg-spaGreen text-white rounded-lg hover:bg-spaGold transition">
                Close
            </button>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.closeServiceModal').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('service-image-modal')?.remove();
    });
});

// Prevent modal from interfering with button clicks
document.getElementById('service-image-modal').addEventListener('click', function(e) {
    // Only close modal if clicking on the backdrop, not on the modal content
    if (e.target === this) {
        this.remove();
    }
});
</script>