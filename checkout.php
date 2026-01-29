<?php
session_start();
$conn = new mysqli("localhost", "root", "", "pos_system");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    header("Location: products.php");
    exit();
}

// Calculate cart total
$cart_total = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_total += $item['price'] * $item['quantity'];
}
?>
<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Checkout — Pamper & Relax</title>

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
  <header class="nav-blur text-white fixed w-full z-50 border-b border-white/10 bg-spaGreen">
    <nav>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex-shrink-0">
            <a href="index.php">
              <img src="assets/logo/Pamper Website Logo250x100.png" alt="Pamper &amp; Relax Spa" class="h-12 w-auto object-contain"/>
            </a>
          </div>

          <?php 
          include 'reference.php'; 
          renderNavigation(); 
          ?>

          <div class="hidden md:block">
            <a href="add_products.php">
              <button class="border bg-spaGold border-spaGold text-white rounded-md font-medium hover:bg-white hover:text-spaGold hover:border-spaGold transition duration-300" style="width: 191.91px; height: 42px;">
                Add Products →
              </button>
            </a>
          </div>
        </div>
      </div>
    </nav>
  </header>

  <main class="pt-24 pb-16 min-h-screen">
    <div class="max-w-6xl mx-auto px-6 lg:px-12">
      
      <div class="text-center mb-12">
        <p class="text-spaGold uppercase tracking-widest text-sm font-medium mb-3">Complete Your Order</p>
        <h1 class="font-heading text-4xl md:text-5xl text-spaGreen mb-4">Checkout</h1>
      </div>

      <div class="grid lg:grid-cols-2 gap-8">
        <!-- Order Form -->
        <div class="bg-white rounded-2xl shadow-lg border border-spaAccent/30 p-8">
          <h2 class="font-heading text-2xl text-spaGreen mb-6">Customer Information</h2>
          
          <form id="checkoutForm" enctype="multipart/form-data" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
              <input type="text" name="name" required class="w-full px-4 py-3 rounded-lg border border-spaAccent bg-spaIvory/30 focus:outline-none focus:ring-2 focus:ring-spaGold">
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
              <input type="email" name="email" required class="w-full px-4 py-3 rounded-lg border border-spaAccent bg-spaIvory/30 focus:outline-none focus:ring-2 focus:ring-spaGold">
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
              <input type="tel" name="phone" required class="w-full px-4 py-3 rounded-lg border border-spaAccent bg-spaIvory/30 focus:outline-none focus:ring-2 focus:ring-spaGold">
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Address</label>
              <textarea name="address" rows="3" required class="w-full px-4 py-3 rounded-lg border border-spaAccent bg-spaIvory/30 focus:outline-none focus:ring-2 focus:ring-spaGold resize-none"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4 pt-4">
              <button type="button" onclick="showQRCode()" class="border border-spaAccent rounded-lg px-4 py-3 hover:bg-spaAccent transition">
                View Payment QR
              </button>
              <label class="border border-spaAccent rounded-lg px-4 py-3 text-center cursor-pointer hover:bg-spaAccent transition flex items-center justify-center">
                <span class="text-sm">Upload Payment Proof</span>
                <input type="file" name="payment_proof" accept="image/*" required class="hidden" onchange="handleImageUpload(event)">
              </label>
            </div>

            <div id="imagePreview" class="hidden bg-green-50 border border-green-200 rounded-lg px-4 py-3">
              <p class="text-sm text-green-700">✓ Selected: <span id="imageName" class="font-medium"></span></p>
            </div>

            <div id="qrModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" onclick="hideQRCode()">
              <div class="bg-white p-6 rounded-lg shadow-xl max-w-sm" onclick="event.stopPropagation()">
                <div class="flex justify-between items-center mb-4">
                  <h3 class="text-lg font-semibold">Scan to Pay</h3>
                  <button type="button" onclick="hideQRCode()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
                </div>
                <img src="assets/qr/qr.png" alt="QR Code" class="w-full">
                <p class="text-sm text-gray-600 mt-3 text-center">Scan to make payment</p>
              </div>
            </div>

            <button type="submit" class="w-full bg-spaGold hover:bg-spaGreen text-white py-4 rounded-lg transition duration-300 font-medium text-lg mt-6">
              Place Order
            </button>

            <div id="orderAlert" class="hidden px-4 py-3 text-sm rounded mt-4"></div>
          </form>
        </div>

        <!-- Order Summary -->
        <div>
          <div class="bg-white rounded-2xl shadow-lg border border-spaAccent/30 p-8 top-24">
            <h2 class="font-heading text-2xl text-spaGreen mb-6">Order Summary</h2>
            
            <div class="space-y-4 mb-6 max-h-96 overflow-y-auto pr-2" style="scrollbar-width: thin; scrollbar-color: #d4b26a #f8f5ef;">
              <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                <div class="flex gap-4 pb-4 border-b border-spaAccent">
                  <img src="<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-20 h-20 object-cover rounded-lg">
                  <div class="flex-1">
                    <h3 class="font-medium text-spaGreen"><?= htmlspecialchars($item['name']) ?></h3>
                    <p class="text-sm text-gray-500">Qty: <?= $item['quantity'] ?></p>
                    <p class="text-spaGold font-semibold mt-1">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></p>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <div class="space-y-3 pt-4 border-t border-spaAccent">
              <div class="flex justify-between text-lg">
                <span class="font-medium">Subtotal:</span>
                <span>₱<?= number_format($cart_total, 2) ?></span>
              </div>
              <div class="flex justify-between text-lg">
                <span class="font-medium">Shipping:</span>
                <span class="text-green-600">FREE</span>
              </div>
              <div class="flex justify-between text-2xl font-heading text-spaGreen pt-3 border-t border-spaAccent">
                <span>Total:</span>
                <span class="text-spaGold">₱<?= number_format($cart_total, 2) ?></span>
              </div>
            </div>
          </div>

          <div class="mt-4 text-center">
            <a href="products.php" class="text-spaGreen hover:text-spaGold transition duration-300 inline-flex items-center space-x-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
              </svg>
              <span>Continue Shopping</span>
            </a>
          </div>
        </div>
      </div>

    </div>
  </main>

  <?php render_footer(); ?>

  <script>
    function showQRCode() {
      document.getElementById('qrModal').classList.remove('hidden');
    }

    function hideQRCode() {
      document.getElementById('qrModal').classList.add('hidden');
    }

    function handleImageUpload(event) {
      const file = event.target.files[0];
      if (file) {
        if (file.size > 5 * 1024 * 1024) {
          alert('File size must be less than 5MB');
          event.target.value = '';
          return;
        }
        
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
          alert('Please upload a valid image file (JPG, PNG, or GIF)');
          event.target.value = '';
          return;
        }
        
        document.getElementById('imagePreview').classList.remove('hidden');
        document.getElementById('imageName').textContent = file.name;
      }
    }

    document.getElementById('checkoutForm').onsubmit = function(e) {
      e.preventDefault();
      
      const alertBox = document.getElementById('orderAlert');
      const submitButton = e.target.querySelector('button[type="submit"]');
      const originalText = submitButton.textContent;
      
      submitButton.disabled = true;
      submitButton.textContent = 'Processing...';
      submitButton.classList.add('opacity-50', 'cursor-not-allowed');
      
      const formData = new FormData(e.target);
      
      fetch('process_order.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alertBox.textContent = data.message;
          alertBox.classList.remove('hidden', 'bg-red-50', 'text-red-600');
          alertBox.classList.add('bg-emerald-50', 'text-emerald-600', 'border', 'border-emerald-200');
          
          setTimeout(() => {
            window.location.href = 'products.php';
          }, 2000);
        } else {
          alertBox.textContent = data.message;
          alertBox.classList.remove('hidden', 'bg-emerald-50', 'text-emerald-600');
          alertBox.classList.add('bg-red-50', 'text-red-600', 'border', 'border-red-200');
          
          submitButton.disabled = false;
          submitButton.textContent = originalText;
          submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alertBox.textContent = 'An error occurred. Please try again.';
        alertBox.classList.remove('hidden', 'bg-emerald-50', 'text-emerald-600');
        alertBox.classList.add('bg-red-50', 'text-red-600', 'border', 'border-red-200');
        
        submitButton.disabled = false;
        submitButton.textContent = originalText;
        submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
      });
      
      return false;
    };
  </script>
</body>
</html>