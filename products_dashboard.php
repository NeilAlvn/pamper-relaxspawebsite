<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// DB CONNECTION
$conn = new mysqli("localhost", "root", "", "pos_system");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get table type (products or services)
$table_type = isset($_GET['table']) ? $_GET['table'] : 'products';

// Handle CRUD actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    if ($action === 'delete') {
        // Get image path before deleting
        $table = $table_type === 'services' ? 'services' : 'products';
        $img_query = $conn->prepare("SELECT image FROM $table WHERE id = ?");
        $img_query->bind_param("i", $id);
        $img_query->execute();
        $img_result = $img_query->get_result();
        
        if ($img_row = $img_result->fetch_assoc()) {
            // Delete image file if exists
            if (!empty($img_row['image']) && file_exists($img_row['image'])) {
                unlink($img_row['image']);
            }
        }
        
        // Delete record
        $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Build redirect URL with all parameters - FIXED VERSION
    $redirect_params = ['table' => $table_type];
    
    // Always include filter for products (even if 'all')
    if ($table_type === 'products') {
        $redirect_params['filter'] = $filter;
    }
    
    // Always include search if not empty
    if (!empty($search)) {
        $redirect_params['search'] = $search;
    }
    
    header("Location: products_dashboard.php?" . http_build_query($redirect_params));
    exit();
}

// Handle Edit/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $id = intval($_POST['id']);
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category = $_POST['category'];
    
    // Handle image upload
    $image = $_POST['existing_image']; // Keep existing image by default
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($filetype, $allowed)) {
            $upload_dir = 'uploads/products/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Delete old image if exists
            if (!empty($image) && file_exists($image)) {
                unlink($image);
            }
            
            $new_filename = uniqid() . '.' . $filetype;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                $image = $destination;
            }
        }
    }
    
    $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category = ?, image = ? WHERE id = ?");
    $stmt->bind_param("ssdissi", $name, $description, $price, $stock, $category, $image, $id);
    $stmt->execute();
    
    header("Location: products_dashboard.php?table=products");
    exit();
}

// Handle Edit/Update Services
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
    $id = intval($_POST['id']);
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $duration = intval($_POST['duration']); // Duration in hours
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $image = $_POST['existing_image'];
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($filetype, $allowed)) {
            $upload_dir = 'uploads/services/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            if (!empty($image) && file_exists($image)) {
                unlink($image);
            }
            
            $new_filename = uniqid() . '.' . $filetype;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                $image = $destination;
            }
        }
    }
    
    $stmt = $conn->prepare("UPDATE services SET name = ?, description = ?, price = ?, duration = ?, is_active = ?, image = ? WHERE id = ?");
    $stmt->bind_param("ssdisii", $name, $description, $price, $duration, $is_active, $image, $id);
    $stmt->execute();
    
    header("Location: products_dashboard.php?table=services");
    exit();
}

// Get filter and search
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query based on table type
if ($table_type === 'products') {
    $query = "SELECT * FROM products WHERE 1=1";
    
    if ($filter !== 'all') {
        $query .= " AND category = '" . $conn->real_escape_string($filter) . "'";
    }
    
    if (!empty($search)) {
        $query .= " AND (name LIKE '%" . $conn->real_escape_string($search) . "%' 
                    OR description LIKE '%" . $conn->real_escape_string($search) . "%' 
                    OR category LIKE '%" . $conn->real_escape_string($search) . "%')";
    }
    
    $query .= " ORDER BY id DESC";
    
    $result = $conn->query($query);

    // Get statistics for products WITH FILTER AND SEARCH
    $stats_query = "SELECT 
        COUNT(*) as total,
        SUM(stock) as total_stock,
        COUNT(CASE WHEN stock > 0 THEN 1 END) as in_stock,
        COUNT(CASE WHEN stock = 0 THEN 1 END) as out_of_stock
    FROM products WHERE 1=1";
    
    // Apply same filter
    if ($filter !== 'all') {
        $stats_query .= " AND category = '" . $conn->real_escape_string($filter) . "'";
    }
    
    // Apply same search
    if (!empty($search)) {
        $stats_query .= " AND (name LIKE '%" . $conn->real_escape_string($search) . "%' 
                        OR description LIKE '%" . $conn->real_escape_string($search) . "%' 
                        OR category LIKE '%" . $conn->real_escape_string($search) . "%')";
    }
    $stats_result = $conn->query($stats_query);
    $stats = $stats_result->fetch_assoc();

    // Get categories for filter
    $categories_query = "SELECT DISTINCT category FROM products ORDER BY category";
    $categories_result = $conn->query($categories_query);
} else {
    // Services query
    $query = "SELECT * FROM services WHERE 1=1";
    
    if (!empty($search)) {
        $query .= " AND (name LIKE '%" . $conn->real_escape_string($search) . "%' 
                    OR description LIKE '%" . $conn->real_escape_string($search) . "%')";
    }
    
    $query .= " ORDER BY id DESC";
    
    $result = $conn->query($query);

    // Get statistics for services
    $stats_query = "SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN is_active = 1 AND price > 0 THEN 1 END) as active_services,
        COUNT(CASE WHEN is_active = 0 THEN 1 END) as inactive_services,
        SUM(price) as total_revenue_potential
    FROM services";
    $stats_result = $conn->query($stats_query);
    $stats = $stats_result->fetch_assoc();
}
?>

<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?php echo ucfirst($table_type); ?> Management — Pamper & Relax</title>

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
</head>

<body class="font-body text-gray-800 bg-spaIvory antialiased">
<?php 
include 'reference.php'; 
login_button(); 
logout_button(); 
processLogin($conn);
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
            <a href="add_products.php">
              <button class="border bg-spaGold border-spaGold text-white px-5 py-2 rounded-md font-medium hover:bg-white hover:text-spaGold hover:border-spaGold transition duration-300"
                style="width: 196.82px; height: 42.46px;">
                Add New Product →
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
          <?php echo ucfirst($table_type); ?> Dashboard
        </h1>
        <p class="text-gray-600 max-w-2xl mx-auto">
          Manage your <?php echo $table_type; ?> inventory with ease.
        </p>
      </div>

      <!-- Statistics Cards -->
      <?php if ($table_type === 'products'): ?>
      <!-- Products Statistics -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
        <div class="bg-white rounded-xl shadow-md p-6 border border-spaAccent/30">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 uppercase tracking-wide">Total Products</p>
              <p class="text-3xl font-bold text-spaGreen mt-1"><?php echo $stats['total']; ?></p>
            </div>
            <div class="bg-spaIvory rounded-full p-3">
              <svg class="w-8 h-8 text-spaGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border border-spaAccent/30">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 uppercase tracking-wide">Total Stock</p>
              <p class="text-3xl font-bold text-blue-600 mt-1"><?php echo $stats['total_stock']; ?></p>
            </div>
            <div class="bg-blue-50 rounded-full p-3">
              <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border border-spaAccent/30">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 uppercase tracking-wide">In Stock</p>
              <p class="text-3xl font-bold text-green-600 mt-1"><?php echo $stats['in_stock']; ?></p>
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
              <p class="text-sm text-gray-600 uppercase tracking-wide">Out of Stock</p>
              <p class="text-3xl font-bold text-red-600 mt-1"><?php echo $stats['out_of_stock']; ?></p>
            </div>
            <div class="bg-red-50 rounded-full p-3">
              <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
          </div>
        </div>
      </div>
      <?php else: ?>
      <!-- Services Statistics -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
        <div class="bg-white rounded-xl shadow-md p-6 border border-spaAccent/30">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 uppercase tracking-wide">Total Services</p>
              <p class="text-3xl font-bold text-spaGreen mt-1"><?php echo $stats['total']; ?></p>
            </div>
            <div class="bg-spaIvory rounded-full p-3">
              <svg class="w-8 h-8 text-spaGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border border-spaAccent/30">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 uppercase tracking-wide">Active Services</p>
              <p class="text-3xl font-bold text-green-600 mt-1"><?php echo $stats['active_services']; ?></p>
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
              <p class="text-sm text-gray-600 uppercase tracking-wide">Inactive Services</p>
              <p class="text-3xl font-bold text-red-600 mt-1"><?php echo $stats['inactive_services']; ?></p>
            </div>
            <div class="bg-red-50 rounded-full p-3">
              <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border border-spaAccent/30">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 uppercase tracking-wide">Revenue Potential</p>
              <p class="text-3xl font-bold text-blue-600 mt-1">₱<?php echo number_format($stats['total_revenue_potential'], 2); ?></p>
            </div>
            <div class="bg-blue-50 rounded-full p-3">
              <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Filters and Search -->
      <div class="bg-white rounded-xl shadow-md p-6 mb-8 border border-spaAccent/30">
        <form method="GET" action="products_dashboard.php" class="flex flex-col md:flex-row gap-4">
          <input type="hidden" name="table" value="<?php echo $table_type; ?>">
          
          <!-- Search -->
          <div class="flex-1">
            <input 
              type="text" 
              name="search" 
              placeholder="Search by name<?php echo $table_type === 'products' ? ', description, or category' : ' or description'; ?>..." 
              value="<?php echo htmlspecialchars($search); ?>"
              class="w-full px-4 py-3 rounded-lg border border-spaAccent bg-spaIvory/30 focus:outline-none focus:ring-2 focus:ring-spaGold focus:border-transparent transition"
            >
          </div>

          <!-- Filter (only for products) -->
          <?php if ($table_type === 'products'): ?>
          <div class="md:w-48">
            <select 
              name="filter" 
              class="w-full px-4 py-3 rounded-lg border border-spaAccent bg-spaIvory/30 focus:outline-none focus:ring-2 focus:ring-spaGold focus:border-transparent transition"
            >
              <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Categories</option>
              <?php 
              $categories_result->data_seek(0);
              while($cat = $categories_result->fetch_assoc()): 
              ?>
                <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $filter === $cat['category'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($cat['category']); ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <?php endif; ?>

          <!-- Submit -->
          <?php if ($table_type === 'products'): ?>
            <button 
              type="submit"
              class="px-6 py-3 bg-spaGold hover:bg-spaGreen text-white rounded-lg transition duration-300 font-medium"
            >
              Filter
            </button>
          <?php endif; ?>
          
          <!-- Toggle Button -->
          <a href="products_dashboard.php?table=<?php echo $table_type === 'products' ? 'services' : 'products'; ?>" 
             class="px-6 py-3 bg-spaGreen hover:bg-spaGold text-white rounded-lg transition duration-300 font-medium text-center whitespace-nowrap">
            Switch to <?php echo $table_type === 'products' ? 'Services' : 'Products'; ?>
          </a>
        </form>
      </div>

      <!-- Products Table -->
      <div class="bg-white rounded-xl shadow-lg border border-spaAccent/30 overflow-hidden">
        <div class="overflow-x-auto">
          
          <?php if ($table_type === 'products'): ?>
          <!-- PRODUCTS TABLE -->
          <table class="w-full">
            <thead class="bg-spaGreen text-white">
              <tr>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Image</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Product</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Category</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Price</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Stock</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-spaAccent/30">
              <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                  <tr class="hover:bg-spaIvory/50 transition">
                    <td class="px-6 py-4">
                      <?php if(!empty($row['image']) && file_exists($row['image'])): ?>
                        <img src="<?php echo $row['image']; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="w-16 h-16 object-cover rounded-lg">
                      <?php else: ?>
                        <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                          <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                          </svg>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                      <div class="font-medium text-spaGreen"><?php echo htmlspecialchars($row['name']); ?></div>
                      <?php if (!empty($row['description'])): ?>
                        <div class="text-xs text-gray-500 mt-1" title="<?php echo htmlspecialchars($row['description']); ?>">
                          <?php echo substr(htmlspecialchars($row['description']), 0, 50) . '...'; ?>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                      <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-spaIvory text-spaGreen">
                        <?php echo htmlspecialchars($row['category']); ?>
                      </span>
                    </td>
                    <td class="px-6 py-4">
                      <span class="text-sm font-semibold text-spaGreen">₱<?php echo number_format($row['price'], 2); ?></span>
                    </td>
                    <td class="px-6 py-4">
                      <?php
                        $stock = $row['stock'];
                        if ($stock > 10) {
                          $stock_class = 'bg-green-100 text-green-800 border-green-300';
                        } elseif ($stock > 0) {
                          $stock_class = 'bg-yellow-100 text-yellow-800 border-yellow-300';
                        } else {
                          $stock_class = 'bg-red-100 text-red-800 border-red-300';
                        }
                      ?>
                      <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold border <?php echo $stock_class; ?>">
                        <?php echo $stock; ?> units
                      </span>
                    </td>
                    <td class="px-6 py-4">
                      <div class="flex items-center space-x-2">
                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)" 
                           class="p-2 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition"
                           title="Edit">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                          </svg>
                        </button>
                        
                        <!-- Form-based delete for reliability -->
                        <form method="GET" action="products_dashboard.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                          <input type="hidden" name="table" value="products">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                          <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                          <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                          <button type="submit" class="p-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg transition" title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <p class="text-lg font-medium">No products found</p>
                    <p class="text-sm mt-1">Try adjusting your filters or search terms</p>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>

          <?php else: ?>
          <!-- SERVICES TABLE -->
          <table class="w-full">
            <thead class="bg-spaGreen text-white">
              <tr>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Image</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Service</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Duration</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Price</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Status</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Created</th>
                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-spaAccent/30">
              <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                  <tr class="hover:bg-spaIvory/50 transition">
                    <td class="px-6 py-4">
                      <?php if(!empty($row['image']) && file_exists($row['image'])): ?>
                        <img src="<?php echo $row['image']; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="w-16 h-16 object-cover rounded-lg">
                      <?php else: ?>
                        <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                          <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                          </svg>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                      <div class="font-medium text-spaGreen"><?php echo htmlspecialchars($row['name']); ?></div>
                      <?php if (!empty($row['description'])): ?>
                        <div class="text-xs text-gray-500 mt-1" title="<?php echo htmlspecialchars($row['description']); ?>">
                          <?php echo substr(htmlspecialchars($row['description']), 0, 80) . '...'; ?>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                      <span class="text-sm text-gray-600">
                        <?php 
                          $duration = isset($row['duration']) ? $row['duration'] : 1;
                          echo $duration == 1 ? '1 hour' : $duration . ' hours'; 
                        ?>
                      </span>
                    </td>
                    <td class="px-6 py-4">
                      <span class="text-sm font-semibold text-spaGreen">₱<?php echo number_format($row['price'], 2); ?></span>
                    </td>
                    <td class="px-6 py-4">
                      <?php
                        $is_active = isset($row['is_active']) ? $row['is_active'] : 1;
                        if ($is_active == 1) {
                          $status_class = 'bg-green-100 text-green-800 border-green-300';
                          $status_text = 'Active';
                        } else {
                          $status_class = 'bg-red-100 text-red-800 border-red-300';
                          $status_text = 'Inactive';
                        }
                      ?>
                      <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold border <?php echo $status_class; ?>">
                        <?php echo $status_text; ?>
                      </span>
                    </td>
                    <td class="px-6 py-4">
                      <span class="text-sm text-gray-600"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></span>
                    </td>
                    <td class="px-6 py-4">
                      <div class="flex items-center space-x-2">
                        <button onclick="openEditServiceModal(<?php echo htmlspecialchars(json_encode($row)); ?>)" 
                           class="p-2 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition"
                           title="Edit">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                          </svg>
                        </button>
                        
                        <!-- Form-based delete for reliability -->
                        <form method="GET" action="products_dashboard.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this service?');">
                          <input type="hidden" name="table" value="services">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                          <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                          <button type="submit" class="p-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg transition" title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <p class="text-lg font-medium">No services found</p>
                    <p class="text-sm mt-1">Try adjusting your search terms</p>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
          <?php endif; ?>

        </div>
      </div>

    </div>
  </main>

  <!-- Edit Modal -->
  <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999] overflow-y-auto">
    <div class="bg-white rounded-lg p-8 max-w-2xl w-full mx-4 my-8">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Edit Product</h2>
        <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      
      <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="update_product" value="1">
        <input type="hidden" name="id" id="edit_id">
        <input type="hidden" name="existing_image" id="edit_existing_image">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">Product Name</label>
            <input type="text" name="name" id="edit_name" required 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
          </div>
          
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">Category</label>
            <input type="text" name="category" id="edit_category" required 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
          </div>
          
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">Price (₱)</label>
            <input type="number" name="price" id="edit_price" step="0.01" required 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
          </div>
          
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">Stock</label>
            <input type="number" name="stock" id="edit_stock" required 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
          </div>
        </div>
        
        <div class="mt-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
          <textarea name="description" id="edit_description" rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold"></textarea>
        </div>
        
        <div class="mt-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Product Image</label>
          <div id="current_image_preview" class="mb-2"></div>
          <input type="file" name="image" accept="image/*"
                 class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
          <p class="text-xs text-gray-500 mt-1">Leave empty to keep current image</p>
        </div>
        
        <div class="flex justify-end space-x-3 mt-6">
          <button type="button" onclick="closeEditModal()"
                  class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-100 transition">
            Cancel
          </button>
          <button type="submit"
                  class="px-4 py-2 bg-spaGold text-white rounded-md hover:bg-spaGreen transition">
            Update Product
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Service Modal -->
  <div id="editServiceModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999] overflow-y-auto">
    <div class="bg-white rounded-lg p-8 max-w-2xl w-full mx-4 my-8">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Edit Service</h2>
        <button onclick="closeEditServiceModal()" class="text-gray-500 hover:text-gray-700">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      
      <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="update_service" value="1">
        <input type="hidden" name="id" id="edit_service_id">
        <input type="hidden" name="existing_image" id="edit_service_existing_image">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">Service Name</label>
            <input type="text" name="name" id="edit_service_name" required 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
          </div>
          
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">Price (₱)</label>
            <input type="number" name="price" id="edit_service_price" step="0.01" required 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
          </div>
          
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">Duration (hours)</label>
            <input type="number" name="duration" id="edit_service_duration" min="1" value="1" required 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
            <p class="text-xs text-gray-500 mt-1">Duration in hours (e.g., 1, 2, 3)</p>
          </div>
          
          <div class="flex items-center">
            <label class="flex items-center cursor-pointer">
              <input type="checkbox" name="is_active" id="edit_service_is_active" class="mr-2 w-5 h-5 text-spaGold focus:ring-spaGold border-gray-300 rounded">
              <span class="text-gray-700 text-sm font-bold">Active (Available for booking)</span>
            </label>
          </div>
        </div>
        
        <div class="mt-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
          <textarea name="description" id="edit_service_description" rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold"></textarea>
        </div>
        
        <div class="mt-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Service Image</label>
          <div id="current_service_image_preview" class="mb-2"></div>
          <input type="file" name="image" accept="image/*"
                 class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
          <p class="text-xs text-gray-500 mt-1">Leave empty to keep current image</p>
        </div>
        
        <div class="flex justify-end space-x-3 mt-6">
          <button type="button" onclick="closeEditServiceModal()"
                  class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-100 transition">
            Cancel
          </button>
          <button type="submit"
                  class="px-4 py-2 bg-spaGold text-white rounded-md hover:bg-spaGreen transition">
            Update Service
          </button>
        </div>
      </form>
    </div>
  </div>

  <?php render_footer(); ?>

  <?php login_modal(); ?>

  <script>
    function openEditModal(product) {
      document.getElementById('edit_id').value = product.id;
      document.getElementById('edit_name').value = product.name;
      document.getElementById('edit_description').value = product.description || '';
      document.getElementById('edit_price').value = product.price;
      document.getElementById('edit_stock').value = product.stock;
      document.getElementById('edit_category').value = product.category;
      document.getElementById('edit_existing_image').value = product.image || '';
      
      // Show current image if exists
      const previewDiv = document.getElementById('current_image_preview');
      if (product.image) {
        previewDiv.innerHTML = `<img src="${product.image}" alt="Current" class="w-32 h-32 object-cover rounded-lg border">`;
      } else {
        previewDiv.innerHTML = '';
      }
      
      document.getElementById('editModal').classList.remove('hidden');
    }
    
    function closeEditModal() {
      document.getElementById('editModal').classList.add('hidden');
    }
    
    function openEditServiceModal(service) {
      document.getElementById('edit_service_id').value = service.id;
      document.getElementById('edit_service_name').value = service.name;
      document.getElementById('edit_service_description').value = service.description || '';
      document.getElementById('edit_service_price').value = service.price;
      document.getElementById('edit_service_duration').value = service.duration || 1;
      document.getElementById('edit_service_existing_image').value = service.image || '';
      
      // Set active checkbox
      const isActive = service.is_active !== undefined ? service.is_active : 1;
      document.getElementById('edit_service_is_active').checked = isActive == 1;
      
      // Show current image if exists
      const previewDiv = document.getElementById('current_service_image_preview');
      if (service.image) {
        previewDiv.innerHTML = `<img src="${service.image}" alt="Current" class="w-32 h-32 object-cover rounded-lg border">`;
      } else {
        previewDiv.innerHTML = '';
      }
      
      document.getElementById('editServiceModal').classList.remove('hidden');
    }
    
    function closeEditServiceModal() {
      document.getElementById('editServiceModal').classList.add('hidden');
    }
  </script>
</body>
</html>

<?php $conn->close(); ?>