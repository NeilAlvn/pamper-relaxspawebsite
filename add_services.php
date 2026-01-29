<?php
    // DB CONNECTION
    $conn = new mysqli("localhost", "root", "", "pos_system");
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

    // Insert service
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $duration = $_POST['duration'];
        $image = null;

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($filetype, $allowed)) {
                $upload_dir = 'uploads/services/';
                
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $new_filename = uniqid() . '.' . $filetype;
                $destination = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    $image = $destination;
                }
            }
        }

        $stmt = $conn->prepare("INSERT INTO services (name, description, price, duration, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdis", $name, $description, $price, $duration, $image);
        $stmt->execute();
        header("Location: services.php");
        exit();
    }
?>

<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Add Service — Pamper & Relax</title>

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
            <a href="index.php" class="flex items-center space-x-2">
              <img src="assets/logo/Pamper Website Logo250x100.png" alt="Pamper & Relax Spa" class="h-8 sm:h-9 md:h-10 lg:h-12 w-auto object-contain transition-all duration-300"/>
            </a>
          </div>

          <?php 
          include 'reference.php'; 
          renderNavigation(); 
          ?>

          <div class="hidden md:block">
            <a href="services.php">
              <button class="border bg-spaGold border-spaGold text-white rounded-md font-medium hover:bg-white hover:text-spaGold hover:border-spaGold transition duration-300" style="width: 191.91px; height: 42px;">
                Add Services →
              </button>
            </a>
          </div>
        </div>
      </div>
    </nav>
  </header>

  <main class="pt-24 pb-16 min-h-screen bg-gradient-to-b from-spaIvory via-spaWhite to-spaIvory">
    <div class="max-w-6xl mx-auto w-full px-6 lg:px-12">
      
      <div class="text-center mb-12">
        <p class="text-spaGold uppercase tracking-widest text-sm font-medium mb-3">
          Service Management
        </p>
        <h1 class="font-heading text-4xl md:text-5xl text-spaGreen mb-4">
          Add New Service
        </h1>
        <p class="text-gray-600 max-w-2xl mx-auto">
          Expand your service offerings with quality spa treatments.
        </p>
      </div>

      <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-2xl shadow-lg border border-spaAccent/30 overflow-hidden">
          <div class="bg-gradient-to-r from-spaGreen to-spaGreen/90 px-8 py-6">
            <h2 class="text-white font-heading text-2xl">Service Details</h2>
          </div>
          
          <form method="POST" enctype="multipart/form-data" class="px-8 py-10 space-y-8">
            
            <div class="space-y-2">
              <label class="block text-spaGreen font-medium text-sm uppercase tracking-wide">
                Service Name
              </label>
              <input 
                type="text" 
                name="name" 
                placeholder="e.g., Swedish Massage" 
                required
                class="w-full px-4 py-3 rounded-lg border border-spaAccent bg-spaIvory/30 focus:outline-none focus:ring-2 focus:ring-spaGold focus:border-transparent transition"
              >
            </div>

            <div class="space-y-2">
              <label class="block text-spaGreen font-medium text-sm uppercase tracking-wide">
                Description
              </label>
              <textarea 
                name="description" 
                placeholder="Describe the service benefits and features..." 
                rows="4"
                required
                class="w-full px-4 py-3 rounded-lg border border-spaAccent bg-spaIvory/30 focus:outline-none focus:ring-2 focus:ring-spaGold focus:border-transparent transition resize-none"
              ></textarea>
            </div>

            <div class="space-y-2">
              <label class="block text-spaGreen font-medium text-sm uppercase tracking-wide">
                Price
              </label>
              <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-spaGreen font-medium">₱</span>
                <input 
                  type="number" 
                  step="0.01" 
                  name="price" 
                  placeholder="0.00" 
                  required
                  class="w-full pl-10 pr-4 py-3 rounded-lg border border-spaAccent bg-spaIvory/30 focus:outline-none focus:ring-2 focus:ring-spaGold focus:border-transparent transition"
                >
              </div>
            </div>

            <div class="space-y-2">
              <label class="block text-spaGreen font-medium text-sm uppercase tracking-wide">
                Duration (hours)
              </label>
              <input 
                type="number" 
                name="duration" 
                placeholder="1" 
                min="1"
                required
                class="w-full px-4 py-3 rounded-lg border border-spaAccent bg-spaIvory/30 focus:outline-none focus:ring-2 focus:ring-spaGold focus:border-transparent transition"
              >
              <p class="text-xs text-gray-500 mt-1">Enter duration in hours (e.g., 1, 2, 3)</p>
            </div>

            <div class="space-y-2">
              <label class="block text-spaGreen font-medium text-sm uppercase tracking-wide">
                Service Image
              </label>
              <input 
                type="file" 
                name="image" 
                accept="image/*" 
                required
                class="w-full px-4 py-3 rounded-lg border border-spaAccent bg-spaIvory/30 focus:outline-none focus:ring-2 focus:ring-spaGold focus:border-transparent transition file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-medium file:bg-spaGold file:text-white hover:file:bg-spaGold/90 file:cursor-pointer"
              >
              <p class="text-xs text-gray-500 mt-1">Accepted formats: JPG, JPEG, PNG, GIF</p>
            </div>

            <div class="pt-4">
              <button 
                type="submit"
                class="w-full bg-spaGold hover:bg-spaGreen text-white font-medium py-4 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-[1.02] shadow-lg hover:shadow-xl"
              >
                Add Service
              </button>
            </div>

          </form>
        </div>

        <div class="text-center mt-8">
          <a href="services.php" class="text-spaGreen hover:text-spaGold transition duration-300 inline-flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Back to Services</span>
          </a>
        </div>
      </div>

    </div>
  </main>

  <?php render_footer(); ?>
</body>
</html>