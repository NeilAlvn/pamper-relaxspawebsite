<?php
$conn = new mysqli("localhost", "root", "", "pos_system");
if ($conn->connect_error) die("Connection failed");

$order_id = $_GET['order'] ?? null;
$imagePath = 'uploads/products/default.jpg';
$title = "Payment Proof Preview"; // static title

if ($order_id) {
    $stmt = $conn->prepare("SELECT payment_proof FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result) {
        $imagePath = file_exists($result['payment_proof']) ? $result['payment_proof'] : $imagePath;
    }
}

$conn->close();
?>

<div id="product-image-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60">
    <div class="bg-white rounded-xl shadow-xl max-w-3xl w-full mx-4 flex flex-col">
        
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-spaGreen">
                <?php echo $title; ?>
            </h3>
            <button class="closeProductModal text-gray-500 hover:text-red-500 text-xl">✕</button>
        </div>

        <!-- Body -->
        <div class="p-6 flex justify-center bg-spaIvory">
            <img 
                src="<?php echo htmlspecialchars($imagePath); ?>" 
                class="max-h-[450px] w-auto rounded-lg border border-spaGold shadow-md object-contain"
                alt="Payment Proof"
            >
        </div>

        <!-- Footer Close Button -->
        <div class="px-6 py-4 border-t text-right">
            <button class="closeProductModal px-4 py-2 bg-spaGreen text-white rounded-lg hover:bg-spaGold transition">
                Close
            </button>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.closeProductModal').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('product-image-modal')?.remove();
    });
});

// Close modal when clicking on backdrop
document.getElementById('product-image-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.remove();
    }
});

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.getElementById('product-image-modal')?.remove();
    }
});
</script>
