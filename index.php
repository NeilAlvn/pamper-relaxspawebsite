 <?php 
        include 'db_connection.php'; // Your database connection
        include 'reference.php'; 
        processLogin($conn); // Process login if form submitted

        ?>
<?php
    // DB CONNECTION
    $conn = new mysqli("localhost", "root", "", "pos_system");
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

    // Fetch all services for dropdown
   $services_query = "SELECT id, name FROM services WHERE is_active = 1 ORDER BY name ASC";
    $services_result = $conn->query($services_query);

    // Insert service - only if the form fields exist
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name']) && isset($_POST['description'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $image = null;

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($filetype, $allowed)) {
                $upload_dir = 'uploads/services/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate unique filename
                $new_filename = uniqid() . '.' . $filetype;
                $destination = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    $image = $destination;
                }
            }
        }

        $stmt = $conn->prepare("INSERT INTO services (name, description, price, image) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssds", $name, $description, $price, $image);
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
  <title>Pamper & Relax — Salon and Spa</title>

  <link rel="stylesheet" href="style.css">

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

<body class="font-body text-gray-800 bg-spaIvory antialiased">
<?php login_button(); ?>
<?php logout_button(); ?>
</head>

<body class="font-body text-gray-800 bg-spaIvory antialiased">
   <header class="nav-blur text-white fixed w-full z-50 border-b border-white/10 bg-spaGreen">
<nav >
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16">

      <!-- Logo -->
      <div class="flex-shrink-0">
       <!-- LOGO -->
    <a href="#home" class="flex items-center space-x-2">
      <img 
        src="assets/logo/Pamper Website Logo250x100.png" 
        alt="Pamper & Relax Spa" 
        class="h-8 sm:h-9 md:h-10 lg:h-12 w-auto object-contain transition-all duration-300"
      />
    </a>
      </div>

      <!-- Desktop menu -->
          <?php 
          
          renderNavigation(); 
          ?>
       
      <!-- Desktop CTA -->
      <div class="hidden md:block">
            <a href="#appointment-section">
                <button
                    class="border bg-spaGold border-spaGold text-white px-5 py-2 rounded-md font-medium hover:bg-white hover:text-spaGold hover:border-spaGold transition duration-300"
                    style="width: 196.82px; height: 42.44px;">
                    Get Appointment →
                </button>
                 
            </a>
      </div>
      <!-- After logo, before navigation -->
      
       
     
    </div>
    
  </div>

  
     



    
  </div>
</nav>

</header>










  <main>
    
<!-- HERO SECTION -->
<section
  id="home"
  class="relative min-h-[85vh] flex items-center bg-gradient-to-b from-[#fff9f5] via-[#fdf8f6] to-[#fff]"
>
  <div
    class="max-w-7xl mx-auto w-full px-6 lg:px-12 grid grid-cols-1 md:grid-cols-2 gap-16 items-center"
  >
    <!-- LEFT: Text -->
    <div class="space-y-6">
      <p class="uppercase text-sm tracking-widest text-spaGold font-semibold">
        Welcome to Pamper & Relax Spa
      </p>

      <h1
        class="font-heading text-4xl sm:text-5xl lg:text-6xl text-gray-900 leading-tight"
      >
        Relaxation, Refined<br />
        Always Within Reach.
      </h1>

      <p class="text-gray-600 text-lg leading-relaxed max-w-md">
        Expanding our reach, while keeping every touch personal.
      </p>

      <div class="flex flex-wrap gap-4 pt-6">
        <!-- Secondary CTA -->
        <a href="https://mail.google.com/mail/?view=cm&fs=1&tf=1&to=pamperandrelax@gmail.com" target="_blank">
            <button
            class="border border-spaGold text-spaGold px-5 py-2 rounded-md font-medium hover:bg-spaGold hover:text-white hover:border-spaGold transition"
            >
            Contact Us
            </button>
        </a>
      </div>
    </div>

    <!-- RIGHT: Image Composition -->
    <div
      class="grid grid-cols-2 gap-4 md:gap-6 justify-center items-center md:justify-end"
    >
      <!-- LEFT column: stacked -->
      <div class="flex flex-col gap-4">
        <img
          src="assets/spadecor/spa-aroma.png"
          alt="Spa aroma decor"
          class="rounded-2xl object-cover w-full h-36 sm:h-40 md:h-48 shadow-md hover:scale-[1.02] transition-transform duration-500"
        />
        <img
          src="assets/spadecor/spa-massage.jpg"
          alt="Relaxing greenery"
          class="rounded-2xl object-cover w-full h-36 sm:h-40 md:h-48 shadow-md hover:scale-[1.02] transition-transform duration-500"
        />
      </div>

      <!-- RIGHT column: tall vertical image -->
      <img
        src="assets/spadecor/spa-room5.jpg"
        alt="Luxury chandelier"
        class="rounded-2xl object-cover w-full h-80 sm:h-96 md:h-[460px] shadow-md hover:scale-[1.02] transition-transform duration-500"
      />
    </div>
  </div>
</section>






    <!-- ICON STRIP -->
     <!-- ICON STRIP -->
<div class="bg-spaGreen text-white">
  <div class="max-w-6xl mx-auto px-4 sm:px-8 md:px-12 py-6 sm:py-8">
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 sm:gap-8 text-center">
      <!-- Expert Therapists -->
      <div class="flex flex-col items-center">
        <div class="mb-2">
          <img src="assets/icons/spa mis icon 64x64/expertTherapists.png" 
               alt="Expert Therapists" 
               class="w-12 h-12 sm:w-16 sm:h-16 object-contain">
        </div>
        <div class="text-xs sm:text-sm tracking-wide font-medium">Expert Therapists</div>
      </div>

      <!-- Serene Ambience -->
      <div class="flex flex-col items-center">
        <div class="mb-2">
          <img src="assets/icons/spa mis icon 64x64/sereneAmbience.png" 
               alt="Serene Ambience" 
               class="w-12 h-12 sm:w-16 sm:h-16 object-contain">
        </div>
        <div class="text-xs sm:text-sm tracking-wide font-medium">Serene Ambience</div>
      </div>

      <!-- Premium Products -->
      <div class="flex flex-col items-center">
        <div class="mb-2">
          <img src="assets/icons/spa mis icon 64x64/premiumProducts.png" 
               alt="Premium Products" 
               class="w-12 h-12 sm:w-16 sm:h-16 object-contain">
        </div>
        <div class="text-xs sm:text-sm tracking-wide font-medium">Premium Products</div>
      </div>

      <!-- Natural Touch -->
      <div class="flex flex-col items-center">
        <div class="mb-2">
          <img src="assets/icons/spa mis icon 64x64/naturalTouch.png" 
               alt="Natural Touch" 
               class="w-12 h-12 sm:w-16 sm:h-16 object-contain">
        </div>
        <div class="text-xs sm:text-sm tracking-wide font-medium">Natural Touch</div>
      </div>
    </div>
  </div>
</div>
<?php include 'services_component.php'; ?>
<!-- Products component -->
<?php include 'products_component.php'; ?>
    <!-- BOOKING FORM -->
    <section class="py-24 bg-spaIvory" id="appointment-section">
      <div class="max-w-6xl mx-auto px-8 md:px-12 grid md:grid-cols-2 gap-12 items-center">
        <div>
          <h2 class="font-heading text-4xl text-spaGreen mb-4">Schedule a Session</h2>
          <p class="text-gray-600 mb-6 max-w-md">Book your preferred slot — we'll confirm via email. Please provide accurate details for a smooth experience.</p>

          <!-- Replace your booking form section with this updated version -->

          <form id="bookingForm" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow-md space-y-4">
            <div class="grid sm:grid-cols-2 gap-4">
              <input name="name" required placeholder="Name" class="border border-spaAccent rounded px-3 py-2 w-full">
              <input type="email" name="email" required placeholder="Email address" class="border border-spaAccent rounded px-3 py-2 w-full">
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
              <input name="phone" required placeholder="Phone" class="border border-spaAccent rounded px-3 py-2 w-full">
              <input type="date" name="date" required min="<?php echo date('Y-m-d'); ?>" class="border border-spaAccent rounded px-3 py-2 w-full">
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
              <select name="time" id="time-select" required class="border border-spaAccent rounded px-3 py-2 w-full">
                <option value="">Select Time</option>
                <option value="08:00:00">08:00 AM</option>
                <option value="09:00:00">09:00 AM</option>
                <option value="10:00:00">10:00 AM</option>
                <option value="11:00:00">11:00 AM</option>
                <option value="12:00:00">12:00 PM</option>
                <option value="13:00:00">01:00 PM</option>
                <option value="14:00:00">02:00 PM</option>
                <option value="15:00:00">03:00 PM</option>
                <option value="16:00:00">04:00 PM</option>
                <option value="17:00:00">05:00 PM</option>
              </select>
              
              <select name="service" id="service-select" required class="border border-spaAccent rounded px-3 py-2 w-full">
                <option value="">Select Service</option>
                <?php 
                // Get the service_id from URL
                $selected_service_id = isset($_GET['service_id']) ? $_GET['service_id'] : null;
                
                if ($services_result && $services_result->num_rows > 0) {
                    while($service = $services_result->fetch_assoc()): 
                ?>
                    <option value="<?= $service['id'] ?>" 
                        <?= ($selected_service_id == $service['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($service['name']) ?>
                    </option>
                <?php 
                    endwhile;
                } else {
                    echo '<option value="" disabled>No services available</option>';
                }
                ?>
              </select>
            </div>

            <textarea name="message" rows="3" placeholder="Message (optional)" class="border border-spaAccent rounded px-3 py-2 w-full"></textarea>
            
            <!-- QR Code and Image Upload Buttons -->
            <div class="grid sm:grid-cols-2 gap-4">
              <button type="button" onclick="showQRCode()" class="border border-spaAccent rounded px-3 py-2 w-full hover:bg-spaAccent transition">
                View Payment QR
              </button>
              <label class="border border-spaAccent rounded px-3 py-2 w-full text-center cursor-pointer hover:bg-spaAccent transition">
                Upload Payment Proof
                <input type="file" name="attachment" accept="image/*" class="hidden" onchange="handleImageUpload(event)">
              </label>
            </div>
            
            <!-- QR Code Modal (hidden by default) -->
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
            
            <!-- Image Preview -->
            <div id="imagePreview" class="hidden bg-green-50 border border-green-200 rounded px-3 py-2">
              <p class="text-sm text-green-700">✓ Selected: <span id="imageName" class="font-medium"></span></p>
            </div>
            
            <button type="submit" class="btn-lux bg-spaGreen text-white w-full py-3 rounded-md hover:bg-opacity-90 transition font-medium">
              Book Appointment
            </button>
            
            <div id="bookingAlert" class="hidden px-4 py-3 text-sm rounded"></div>
          </form>

          <script>
          function handleBookingSubmit(e) {
            e.preventDefault();
            
            const alertBox = document.getElementById('bookingAlert');
            const submitButton = e.target.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            
            // Disable submit button and show loading state
            submitButton.disabled = true;
            submitButton.textContent = 'Submitting...';
            submitButton.classList.add('opacity-50', 'cursor-not-allowed');
            
            // Create FormData object to handle file upload
            const formData = new FormData(e.target);
            
            // Send AJAX request
            fetch('process_appointment.php', {
              method: 'POST',
              body: formData
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                alertBox.textContent = data.message;
                alertBox.classList.remove('hidden', 'bg-red-50', 'text-red-600');
                alertBox.classList.add('bg-emerald-50', 'text-emerald-600', 'border', 'border-emerald-200');
                
                // Reset form and image preview
                e.target.reset();
                document.getElementById('imagePreview').classList.add('hidden');
                
                // Scroll to alert
                alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Auto-hide after 8 seconds
                setTimeout(() => alertBox.classList.add('hidden'), 8000);
              } else {
                alertBox.textContent = data.message;
                alertBox.classList.remove('hidden', 'bg-emerald-50', 'text-emerald-600');
                alertBox.classList.add('bg-red-50', 'text-red-600', 'border', 'border-red-200');
                
                // Scroll to alert
                alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
              }
            })
            .catch(error => {
              console.error('Error:', error);
              alertBox.textContent = 'An error occurred. Please check your connection and try again.';
              alertBox.classList.remove('hidden', 'bg-emerald-50', 'text-emerald-600');
              alertBox.classList.add('bg-red-50', 'text-red-600', 'border', 'border-red-200');
            })
            .finally(() => {
              // Re-enable submit button
              submitButton.disabled = false;
              submitButton.textContent = originalText;
              submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
            });
            
            return false;
          }

          // Attach the event handler
          document.addEventListener('DOMContentLoaded', function() {
            const bookingForm = document.getElementById('bookingForm');
            if (bookingForm) {
              bookingForm.onsubmit = handleBookingSubmit;
            }
          });

          function showQRCode() {
            document.getElementById('qrModal').classList.remove('hidden');
          }

          function hideQRCode() {
            document.getElementById('qrModal').classList.add('hidden');
          }

          function handleImageUpload(event) {
            const file = event.target.files[0];
            if (file) {
              // Validate file size (max 5MB)
              if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                event.target.value = '';
                return;
              }
              
              // Validate file type
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
                    // Replace the entire checkBookingAvailability section with this:
                    let bookedTimes = [];

                    async function loadBookedTimes() {
                      const dateInput = document.querySelector('input[name="date"]');
                      const timeSelect = document.getElementById('time-select');
                      
                      const date = dateInput.value;
                      
                      if (!date) return;
                      
                      try {
                        const response = await fetch(`check_booking.php?date=${date}`);
                        const data = await response.json();
                        bookedTimes = data.booked_times || [];
                        
                        // Disable booked time options
                        const timeOptions = timeSelect.querySelectorAll('option');
                        timeOptions.forEach(option => {
                          if (option.value && bookedTimes.includes(option.value)) {
                            option.disabled = true;
                            option.textContent = option.textContent.replace(' (Booked)', '') + ' (Booked)';
                          } else if (option.value) {
                            option.disabled = false;
                            option.textContent = option.textContent.replace(' (Booked)', '');
                          }
                        });
                        
                        // Reset time selection if currently selected time is now booked
                        if (timeSelect.value && bookedTimes.includes(timeSelect.value)) {
                          timeSelect.value = '';
                        }
                        
                      } catch (error) {
                        console.error('Error loading booked times:', error);
                      }
                    }

                    // Attach listeners
                    document.addEventListener('DOMContentLoaded', function() {
                      const dateInput = document.querySelector('input[name="date"]');
                      const timeSelect = document.getElementById('time-select');
                      
                      if (dateInput && timeSelect) {
                        dateInput.addEventListener('change', loadBookedTimes);
                      }
                    });

          </script>
        </div>

        <img src="assets/spadecor/spa-room3.jpg" 
             class="rounded-lg shadow-xl object-cover max-h-[540px]" alt="spa interior">
      </div>
    </section>
   



    








    <!-- FOOTER -->
    
      <?php render_footer(); ?>
   
  </main>
  
<!-- Alpine.js -->
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>        
  <script>
// Wait for DOM to be ready
  document.addEventListener('DOMContentLoaded', () => {

    /* ---------- Desktop mega-menu ---------- */
    const desktopTabs   = document.querySelectorAll('.tab-item');
    const desktopPanels = document.querySelectorAll('.tab-panel');

    desktopTabs.forEach(tab => {
      tab.addEventListener('click', () => {
        // reset all
        desktopTabs.forEach(t => {
          t.classList.remove('border-b-2', 'border-gold-600', 'text-spaGold');
          t.classList.add('text-gray-700');
        });
        desktopPanels.forEach(p => p.classList.add('hidden'));

        // activate clicked
        tab.classList.add('border-b-2', 'border-gold-600', 'text-gold-600');
        tab.classList.remove('text-gray-700');
        const target = document.getElementById(tab.dataset.tab);
        if (target) target.classList.remove('hidden');
      });
    });

    /* ---------- Mobile menu ---------- */
    const burgerBtn      = document.getElementById('mobile-menu-button');
    const mobileMenu     = document.getElementById('mobile-menu');
    const servicesToggle = document.getElementById('services-mobile-toggle');
    const servicesSub    = document.getElementById('services-mobile-submenu');

    burgerBtn.addEventListener('click', () => {
      mobileMenu.classList.toggle('hidden');
    });

    servicesToggle.addEventListener('click', () => {
      servicesSub.classList.toggle('hidden');
    });

    /* ---------- Mobile tab switching ---------- */
    const mobileTabs   = document.querySelectorAll('.tab-mobile');
    const mobilePanels = document.querySelectorAll('.mobile-panel');

    mobileTabs.forEach(tab => {
      tab.addEventListener('click', () => {
        mobileTabs.forEach(t => t.classList.remove('font-bold', 'text-spaGold'));
        mobilePanels.forEach(p => p.classList.add('hidden'));

        tab.classList.add('font-bold', 'text-spaGold');
        const target = document.getElementById(tab.dataset.tabMobile + '-mobile');
        if (target) target.classList.remove('hidden');
      });
    });
  });


document.getElementById('bookTopBtn').onclick = () =>
      document.getElementById('bookingForm').scrollIntoView({ behavior: 'smooth' });
    document.getElementById('heroBook').onclick = () =>
      document.getElementById('bookingForm').scrollIntoView({ behavior: 'smooth' });

    function handleBookingSubmit(e) {
      e.preventDefault();
      
      const alertBox = document.getElementById('bookingAlert');
      const submitButton = e.target.querySelector('button[type="submit"]');
      
      // Disable submit button and show loading state
      submitButton.disabled = true;
      submitButton.textContent = 'Submitting...';
      
      // Create FormData object to handle file upload
      const formData = new FormData(e.target);
      
      // Send AJAX request
      fetch('process_appointment.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alertBox.textContent = data.message;
          alertBox.classList.remove('hidden', 'bg-red-50', 'text-red-600');
          alertBox.classList.add('bg-emerald-50', 'text-emerald-600');
          
          // Reset form and image preview
          e.target.reset();
          document.getElementById('imagePreview').classList.add('hidden');
          
          // Auto-hide after 6 seconds
          setTimeout(() => alertBox.classList.add('hidden'), 6000);
        } else {
          alertBox.textContent = data.message;
          alertBox.classList.remove('hidden', 'bg-emerald-50', 'text-emerald-600');
          alertBox.classList.add('bg-red-50', 'text-red-600');
        }
      })
      .catch(error => {
        alertBox.textContent = 'An error occurred. Please try again.';
        alertBox.classList.remove('hidden', 'bg-emerald-50', 'text-emerald-600');
        alertBox.classList.add('bg-red-50', 'text-red-600');
      })
      .finally(() => {
        // Re-enable submit button
        submitButton.disabled = false;
        submitButton.textContent = 'Book Appointment';
      });
    }

    function showQRCode() {
      document.getElementById('qrModal').classList.remove('hidden');
    }

    function hideQRCode() {
      document.getElementById('qrModal').classList.add('hidden');
    }

    function handleImageUpload(event) {
      const file = event.target.files[0];
      if (file) {
        document.getElementById('imagePreview').classList.remove('hidden');
        document.getElementById('imageName').textContent = file.name;
      }
    }
  </script>
  
  <?php 
  
  login_modal(); ?>
</body>
</html>
