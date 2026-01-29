<?php
session_start();
$conn = new mysqli("localhost", "root", "", "pos_system");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
include 'reference.php'; 
processLogin($conn);
// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Add to Cart
if (isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    // Get product details
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        // Calculate total quantity (existing + new)
        $existing_quantity = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id]['quantity'] : 0;
        $total_quantity = $existing_quantity + $quantity;
        
        // Check if total quantity exceeds stock
        if ($total_quantity > $product['stock']) {
            $_SESSION['error_message'] = "Cannot add {$quantity} items. Only " . ($product['stock'] - $existing_quantity) . " more available in stock.";
        } else {
            // Check if product already in cart
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] = $total_quantity;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'quantity' => $quantity,
                    'image' => $product['image']
                ];
            }
            $_SESSION['success_message'] = "Product added to cart successfully!";
        }
    }
    header("Location: products.php");
    exit();
}

// Handle Remove from Cart
if (isset($_GET['remove'])) {
    $product_id = intval($_GET['remove']);
    unset($_SESSION['cart'][$product_id]);
    $_SESSION['success_message'] = "Product removed from cart.";
    header("Location: products.php");
    exit();
}

// Handle Update Cart
if (isset($_POST['update_cart'])) {
    $errors = [];
    
    foreach ($_POST['quantities'] as $product_id => $quantity) {
        $quantity = intval($quantity);
        
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
        } else {
            // Check stock availability
            $stmt = $conn->prepare("SELECT stock, name FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $product = $result->fetch_assoc();
                
                if ($quantity > $product['stock']) {
                    $errors[] = "{$product['name']}: Only {$product['stock']} available in stock.";
                    $_SESSION['cart'][$product_id]['quantity'] = $product['stock'];
                } else {
                    $_SESSION['cart'][$product_id]['quantity'] = $quantity;
                }
            }
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['error_message'] = implode(" ", $errors);
    } else {
        $_SESSION['success_message'] = "Cart updated successfully!";
    }
    
    header("Location: products.php");
    exit();
}

$result = $conn->query("SELECT * FROM products WHERE stock > 0 ORDER BY id DESC");

// Calculate cart total
$cart_total = 0;
$cart_count = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_total += $item['price'] * $item['quantity'];
    $cart_count += $item['quantity'];
}
?>
<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Products — Pamper & Relax</title>
    <style>
        .description-wrapper {
        position: relative;
        }

        .description {
        margin: 0;
        font-size: 14px;
        color: #666;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        word-break: break-word;
        min-height: 42px;
        }

        .description-tooltip {
        position: absolute;
        bottom: calc(100% + 10px);
        left: 50%;
        transform: translateX(-50%);
        background: #d4b26a;
        color: white;
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 13px;
        line-height: 1.6;
        white-space: normal;
        width: 280px;
        max-width: 90vw;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.2s ease, visibility 0.2s ease;
        pointer-events: none;
        z-index: 1000;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        word-break: break-word;
        }

        .description-tooltip::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 8px solid transparent;
        border-top-color: #d4b26a;
        }

        .description-wrapper:hover .description-tooltip {
        opacity: 1;
        visibility: visible;
        }
    </style>
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

  <style>
    .product-card {
      background: white;
      border-radius: 16px;
      overflow: hidden;
      transition: all 0.2s ease;
      border: 1px solid rgba(228, 212, 195, 0.3);
    }

    .product-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    }

    .product-image-wrapper {
      position: relative;
      width: 100%;
      height: 280px;
      overflow: hidden;
      background: linear-gradient(135deg, #f8f5ef 0%, #e2d4c3 100%);
    }

    .product-image {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.3s ease;
    }

    .product-card:hover .product-image {
      transform: scale(1.05);
    }

    .cart-badge {
      position: absolute;
      top: -6px;
      right: -6px;
      background: #d4b26a;
      color: white;
      border-radius: 50%;
      min-width: 20px;
      height: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      font-weight: 600;
      padding: 0 4px;
      pointer-events: none;
    }

    .cart-button-container {
      position: relative;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .header-right-section {
      display: flex;
      align-items: center;
      gap: 1rem;
      min-width: fit-content;
    }
    
    .add-products-btn {
      width: 180px;
      height: 42px;
      flex-shrink: 0;
    }
    
    .page-header {
      background: linear-gradient(to bottom, #f8f5ef 0%, #f8f5ef 100%);
      padding-top: 95px;
      text-align: center;
    }

    .alert {
      position: fixed;
      top: 80px;
      right: 20px;
      z-index: 1000;
      max-width: 400px;
      padding: 16px 20px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      animation: slideIn 0.3s ease-out;
    }

    .alert-success {
      background: #d4edda;
      border: 1px solid #c3e6cb;
      color: #155724;
    }

    .alert-error {
      background: #f8d7da;
      border: 1px solid #f5c6cb;
      color: #721c24;
    }

    @keyframes slideIn {
      from {
        transform: translateX(400px);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
  </style>
</head>
<body class="font-body text-gray-800 bg-spaIvory antialiased">
<?php 


logout_button(); 

?>
<body class="font-body text-gray-800 bg-spaIvory antialiased">
  <!-- Alert Messages -->
  <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success" id="alertMessage">
      <?= htmlspecialchars($_SESSION['success_message']) ?>
    </div>
    <?php unset($_SESSION['success_message']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-error" id="alertMessage">
      <?= htmlspecialchars($_SESSION['error_message']) ?>
    </div>
    <?php unset($_SESSION['error_message']); ?>
  <?php endif; ?>

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
         
          renderNavigation(); 
          ?>

                  <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
          <div class="hidden md:block">
            <a href="add_products.php">
              <button class="border bg-spaGold border-spaGold text-white px-5 py-2 rounded-md font-medium hover:bg-white hover:text-spaGold hover:border-spaGold transition duration-300"
                    style="width: 196.82px; height: 42.44px;">
                    Add Products →
              </button>
            </a>
          </div>
        <?php else: ?>
          <div class="hidden md:block">
            <a href="index.php#appointment-section">
              <button class="border bg-spaGold border-spaGold text-white px-5 py-2 rounded-md font-medium hover:bg-white hover:text-spaGold hover:border-spaGold transition duration-300">
                Get Appointment →
              </button>
            </a>
          </div>
        <?php endif; ?>




        </div>
      </div>
    </nav>
  </header>

  <!-- Floating Cart Button -->
  <button onclick="toggleCart()" class="fixed bottom-6 right-6 z-40 bg-spaGold hover:bg-spaGreen text-white p-4 rounded-full shadow-2xl transition-all duration-300 hover:scale-110">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
    </svg>
    <?php if ($cart_count > 0): ?>
      <span class="cart-badge"><?= $cart_count ?></span>
    <?php endif; ?>
  </button>

  <!-- Replace the entire Cart Sidebar section with this improved design: -->

<!-- Cart Sidebar -->
<div id="cartSidebar" class="fixed right-0 top-0 h-full w-96 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 z-50 flex flex-col">
  <!-- Header -->
  <div class="bg-spaGreen text-white p-5 flex justify-between items-center border-b border-spaGold/20">
    <div>
      <h2 class="font-body text-lg font-semibold tracking-wide">Shopping Cart</h2>
      <?php if ($cart_count > 0): ?>
        <p class="text-spaGold text-xs mt-0.5"><?= $cart_count ?> <?= $cart_count === 1 ? 'item' : 'items' ?></p>
      <?php endif; ?>
    </div>
    <button onclick="toggleCart()" class="text-white hover:text-spaGold transition-colors duration-200 p-1 hover:bg-white/10 rounded">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
  </div>

  <!-- Cart Items -->
  <div class="flex-1 overflow-y-auto p-5">
    <?php if (empty($_SESSION['cart'])): ?>
      <div class="text-center py-16">
        <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        <p class="text-gray-500 font-medium">Your cart is empty</p>
        <p class="text-gray-400 text-sm mt-1">Add products to get started</p>
      </div>
    <?php else: ?>
      <form method="POST" class="space-y-4">
        <?php 
        foreach ($_SESSION['cart'] as $product_id => $item): 
          // Get current stock for this product
          $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
          $stmt->bind_param("i", $product_id);
          $stmt->execute();
          $stock_result = $stmt->get_result();
          $current_stock = $stock_result->num_rows > 0 ? $stock_result->fetch_assoc()['stock'] : 0;
        ?>
          <div class="flex gap-3 pb-4 border-b border-gray-200">
            <img src="<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-20 h-20 object-cover rounded-lg border border-gray-200">
            <div class="flex-1 min-w-0">
              <h3 class="font-medium text-spaGreen text-sm truncate" title="<?= htmlspecialchars($item['name']) ?>">
                <?= htmlspecialchars($item['name']) ?>
              </h3>
              <p class="text-spaGold font-semibold text-base mt-1">₱<?= number_format($item['price'], 2) ?></p>
              <p class="text-xs text-gray-500 mt-1">Stock: <?= $current_stock ?></p>
              <div class="flex items-center gap-3 mt-2">
                <div class="flex items-center border border-gray-300 rounded overflow-hidden">
                  <input 
                    type="number" 
                    name="quantities[<?= $product_id ?>]" 
                    value="<?= $item['quantity'] ?>" 
                    min="1" 
                    max="<?= $current_stock ?>"
                    class="w-12 px-2 py-1 text-center text-sm focus:outline-none"
                    onchange="this.form.querySelector('[name=update_cart]').classList.remove('bg-spaGreen'); this.form.querySelector('[name=update_cart]').classList.add('bg-spaGold'); this.form.querySelector('[name=update_cart]').textContent='Save Changes'"
                  >
                </div>
                <a href="?remove=<?= $product_id ?>" class="text-red-500 hover:text-red-700 text-xs font-medium transition-colors">
                  Remove
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
        
        <button 
          type="submit" 
          name="update_cart" 
          class="w-full text-sm text-white bg-spaGreen hover:bg-spaGold px-4 py-2.5 rounded-lg transition-colors duration-200 font-medium"
        >
          Update Cart
        </button>
      </form>
    <?php endif; ?>
  </div>

  <!-- Cart Footer -->
  <?php if (!empty($_SESSION['cart'])): ?>
    <div class="border-t border-gray-200 p-5 bg-gray-50">
      <div class="flex justify-between items-center mb-4">
        <span class="font-body text-base font-semibold text-gray-700">Subtotal:</span>
        <span class="font-body text-2xl font-bold text-spaGold">₱<?= number_format($cart_total, 2) ?></span>
      </div>
      <a href="checkout.php">
        <button class="w-full bg-spaGold hover:bg-spaGreen text-white py-3 rounded-lg transition-all duration-300 font-medium text-base shadow-sm hover:shadow-md">
          Proceed to Checkout →
        </button>
      </a>
      <button 
        onclick="toggleCart()" 
        class="w-full mt-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 py-2.5 rounded-lg transition-all duration-200 font-medium text-sm"
      >
        Continue Shopping
      </button>
    </div>
  <?php endif; ?>
</div>
  <!-- Cart Overlay -->
  <div id="cartOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden" onclick="toggleCart()"></div>

  <main>
    <div class="text-center mb-1 page-header">
      <p class="text-spaGold uppercase tracking-widest text-sm font-medium mb-3">
        Spa Essentials
      </p>
      <h1 class="font-heading text-4xl md:text-5xl text-spaGreen mb-4">
        Our Products
      </h1>
      <p class="text-gray-600 max-w-2xl mx-auto">
        Premium spa and wellness products for your daily self-care routine.
      </p>
    </div>

    <section class="py-16 px-6 lg:px-12" style="max-width: 1400px; margin: 0 auto;">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php while($product = $result->fetch_assoc()): 
          // Check if product is already in cart
          $in_cart_quantity = isset($_SESSION['cart'][$product['id']]) ? $_SESSION['cart'][$product['id']]['quantity'] : 0;
          $available_to_add = $product['stock'] - $in_cart_quantity;
        ?>
          <div class="product-card">
            <div class="product-image-wrapper">
              <?php if(!empty($product['image']) && file_exists($product['image'])): ?>
                <img src="<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
              <?php else: ?>
                <div class="flex items-center justify-center h-full text-gray-400">
                  <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                  </svg>
                </div>
              <?php endif; ?>
              <?php if ($product['stock'] < 10): ?>
                <span class="absolute top-3 right-3 bg-red-500 text-white text-xs px-2 py-1 rounded">
                  Only <?= $product['stock'] ?> left
                </span>
              <?php endif; ?>
              <?php if ($in_cart_quantity > 0): ?>
                <span class="absolute top-3 left-3 bg-spaGold text-white text-xs px-2 py-1 rounded">
                  <?= $in_cart_quantity ?> in cart
                </span>
              <?php endif; ?>
            </div>

            <div class="p-5">
                <span class="text-xs text-gray-500 uppercase tracking-wide"><?= htmlspecialchars($product['category']) ?></span>
                <h3 class="font-heading text-xl text-spaGreen mt-1 mb-2 truncate"><?= htmlspecialchars($product['name']) ?></h3>
                
                <?php if(!empty($product['description'])): ?>
                    <div class="description-wrapper">
                    <p class="description"><?= htmlspecialchars($product['description']) ?></p>
                    <div class="description-tooltip">
                        <?= htmlspecialchars($product['description']) ?>
                    </div>
                    </div>
                <?php else: ?>
                    <div style="min-height: 42px;"></div>
                <?php endif; ?>
                
                <div class="flex items-center justify-between mt-4 gap-3">
                    <span class="font-heading text-2xl text-spaGold truncate flex-shrink min-w-0" style="max-width: 140px;" title="₱<?= number_format($product['price'], 2) ?>">
                    ₱<?= number_format($product['price'], 2) ?>
                    </span>
                    
                    <?php if ($available_to_add > 0): ?>
                    <form method="POST" class="flex items-center gap-2 flex-shrink-0">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <input 
                        type="number" 
                        name="quantity" 
                        value="1" 
                        min="1" 
                        max="<?= $available_to_add ?>" 
                        class="w-14 px-2 py-1 border border-spaAccent rounded text-center text-sm"
                        >
                        <button type="submit" name="add_to_cart" class="bg-spaGreen hover:bg-spaGold text-white px-3 py-1 rounded-lg transition text-sm font-medium whitespace-nowrap">
                        Add
                        </button>
                    </form>
                    <?php else: ?>
                    <span class="text-sm text-red-500 font-medium flex-shrink-0">Max in cart</span>
                    <?php endif; ?>
                </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>

      <?php if ($result->num_rows === 0): ?>
        <div class="text-center py-20">
          <svg class="w-24 h-24 mx-auto mb-6 text-spaAccent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
          </svg>
          <h3 class="font-heading text-2xl text-spaGreen mb-3">No Products Available</h3>
          
              <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <p class="text-gray-600 mb-6">Start adding products to showcase your spa essentials.</p>
      <a href="add_products.php">
        <button class="bg-spaGold text-white px-8 py-3 rounded-lg hover:bg-spaGreen transition duration-300">
          Add Your First Product
        </button>
      </a>
    <?php endif; ?>


        </div>
      <?php endif; ?>
    </section>
  </main>

  <?php render_footer(); ?>

  <script>
    function toggleCart() {
      const sidebar = document.getElementById('cartSidebar');
      const overlay = document.getElementById('cartOverlay');
      
      sidebar.classList.toggle('translate-x-full');
      overlay.classList.toggle('hidden');
    }

    // Auto-hide alert messages after 5 seconds
    const alertMessage = document.getElementById('alertMessage');
    if (alertMessage) {
      setTimeout(() => {
        alertMessage.style.animation = 'slideIn 0.3s ease-out reverse';
        setTimeout(() => {
          alertMessage.remove();
        }, 300);
      }, 5000);
    }
  </script>
</body>
</html>

<?php $conn->close(); ?>