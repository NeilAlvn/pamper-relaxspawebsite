<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$conn = new mysqli("localhost", "root", "", "pos_system");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$result = $conn->query("SELECT * FROM services WHERE is_active = 1 ORDER BY id DESC");
include 'reference.php'; 
processLogin($conn);
?>
<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Our Services — Pamper & Relax</title>

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
    .services-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 24px;
      justify-items: center;
    }

    .card {
      background: white;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 2px 12px rgba(0,0,0,0.08);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      width: 100%;
      max-width: 380px;
    }

    .card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }

    .card-image {
      width: 100%;
      height: 280px;
      overflow: hidden;
      background: #f5f5f5;
      position: relative;
    }

    .card-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.3s ease;
    }

    .card:hover .card-image img {
      transform: scale(1.05);
    }

    .no-image {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100%;
      color: #999;
    }

    .no-image svg {
      margin-bottom: 10px;
    }

    .no-image p {
      margin: 0;
      font-size: 14px;
    }

    .card-content {
      padding: 24px;
    }

    .card h3 {
      margin: 0 0 8px 0;
      font-size: 24px;
      color: #0e3f37;
      font-weight: 600;
      font-family: 'Playfair Display', serif;
    }

    .description-wrapper {
      position: relative;
      margin: 8px 0 16px 0;
    }

    .description {
      margin: 0;
      font-size: 14px;
      color: #666;
      line-height: 1.5;
      display: -webkit-box;
      -webkit-line-clamp: 1;
      -webkit-box-orient: vertical;
      overflow: hidden;
      text-overflow: ellipsis;
      word-break: break-word;
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

    .duration-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: #f8f5ef;
      color: #0e3f37;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 13px;
      font-weight: 500;
      margin-bottom: 12px;
    }

    .duration-badge svg {
      width: 16px;
      height: 16px;
    }

    .price {
      margin: 0;
      font-size: 28px;
      color: #d4b26a;
      font-weight: 600;
      font-family: 'Playfair Display', serif;
    }

    .page-header {
      background: linear-gradient(to bottom, #f8f5ef 0%, #f8f5ef 100%);
      padding-top: 95px;
     
      text-align: center;
    }

    

    

  
    @media (max-width: 768px) {
      .services-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 20px;
      }

      .card-image {
        height: 220px;
      }

      .card h3 {
        font-size: 20px;
      }

      .price {
        font-size: 24px;
      }

      .page-header h1 {
        font-size: 2.5rem;
      }

      .page-header {
        padding: 120px 20px 60px;
      }
    }
  </style>
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


                  <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
          <div class="hidden md:block">
            <a href="add_services.php">
              <button 
              class="border bg-spaGold border-spaGold text-white px-5 py-2 rounded-md font-medium hover:bg-white hover:text-spaGold hover:border-spaGold transition duration-300"
              style="width: 196.82px; height: 42.44px;">
                    Add Services →
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

          <div class="-mr-2 flex md:hidden">
            <button id="mobile-menu-button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-200 hover:text-white hover:bg-spaGold focus:outline-none">
              <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </nav>
  </header>

  <main>
    <!-- Page Header -->
    <!-- Header -->
      <div class="text-center mb-1  page-header">
        <p class="text-spaGold uppercase tracking-widest text-sm font-medium mb-3">
          Discover Relaxation
        </p>
        <h1 class="font-heading text-4xl md:text-5xl text-spaGreen mb-4">
          Our Services
        </h1>
        <p class="text-gray-600 max-w-2xl mx-auto">
          Indulge in luxury treatments designed to rejuvenate your body, mind, and soul.
        </p>
      </div>

    <!-- Services Grid -->
    <section class="py-16 px-6 lg:px-12" style="max-width: 1400px; margin: 0 auto;">
      
      <div class="services-grid">
        <?php while($row = $result->fetch_assoc()): ?>
          <div class="card">
            <a href="index.php?service_id=<?= $row['id'] ?>#appointment-section">
              <div class="card-image">
                <?php if(!empty($row['image']) && file_exists($row['image'])): ?>
                  <img src="<?= $row['image'] ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                <?php else: ?>
                  <div class="no-image">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                      <circle cx="8.5" cy="8.5" r="1.5"></circle>
                      <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                    <p>No Image</p>
                  </div>
                <?php endif; ?>
              </div>
              <div class="card-content">
                <h3><?= htmlspecialchars($row['name']) ?></h3>
                
                <?php if(!empty($row['duration'])): ?>
                  <div class="duration-badge">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <circle cx="12" cy="12" r="10"></circle>
                      <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span><?= $row['duration'] ?> <?= $row['duration'] == 1 ? 'hour' : 'hours' ?></span>
                  </div>
                <?php endif; ?>
                
                <?php if(!empty($row['description'])): ?>
                  <div class="description-wrapper">
                    <p class="description"><?= htmlspecialchars($row['description']) ?></p>
                    <div class="description-tooltip">
                      <?= htmlspecialchars($row['description']) ?>
                    </div>
                  </div>
                <?php endif; ?>
                <p class="price">₱<?= number_format($row['price'], 2) ?></p>
              </div>
            </a>
          </div>
        <?php endwhile; ?>
      </div>
    </section>
  </main>

  <?php render_footer(); ?>

  <script>
    // Add at the top of your script section
window.addEventListener('error', function(e) {
    if (e.message.includes('Extension context invalidated') || 
        e.message.includes('message port closed')) {
        e.stopImmediatePropagation();
        return false;
    }
});
    document.addEventListener('DOMContentLoaded', () => {
      const burgerBtn = document.getElementById('mobile-menu-button');
      const mobileMenu = document.getElementById('mobile-menu');

      if (burgerBtn && mobileMenu) {
        burgerBtn.addEventListener('click', () => {
          mobileMenu.classList.toggle('hidden');
        });
      }

      // Get the service_id from URL parameters
      const urlParams = new URLSearchParams(window.location.search);
      const serviceId = urlParams.get('service_id');
      
      if (serviceId) {
        const serviceSelect = document.getElementById('service-select');
        if (serviceSelect) {
          setTimeout(function() {
            serviceSelect.value = serviceId;
            serviceSelect.dispatchEvent(new Event('change', { bubbles: true }));
          }, 100);
        }
      }
    });
  </script>
</body>
</html>

<?php $conn->close(); ?>