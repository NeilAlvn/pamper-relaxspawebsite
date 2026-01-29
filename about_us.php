<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli("localhost", "root", "", "pos_system");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

include 'reference.php';
processLogin($conn);

// Fetch about us content
$about_content = [];
$about_query = $conn->query("SELECT * FROM about_us ORDER BY id ASC");
while ($row = $about_query->fetch_assoc()) {
    $about_content[$row['section']] = $row;
}

// Fetch team members
$team_query = $conn->query("SELECT * FROM team_members WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
?>
<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>About Us — Pamper & Relax</title>

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
    .page-header {
      background: linear-gradient(to bottom, #f8f5ef 0%, #f8f5ef 100%);
      padding-top: 95px;
      text-align: center;
    }

    .team-card {
      transition: all 0.3s ease;
    }

    .team-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 24px rgba(0,0,0,0.15);
    }

    .team-image {
      width: 100%;
      height: 320px;
      object-fit: cover;
      transition: transform 0.3s ease;
    }

    .team-card:hover .team-image {
      transform: scale(1.05);
    }
  </style>
</head>

<body class="font-body text-gray-800 bg-spaIvory antialiased">
<?php 
login_button();
logout_button(); 
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

          <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <div class="hidden md:block">
              <button
              class="border bg-spaGold border-spaGold text-white px-5 py-2 rounded-md font-medium hover:bg-white hover:text-spaGold hover:border-spaGold transition duration-300"
                    style="width: 196.82px; height: 42.44px;">
                     Edit About Us → 
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

  <main>
    <!-- Hero Section -->
    <div class="page-header pb-16">
      <p class="text-spaGold uppercase tracking-widest text-sm font-medium mb-3">
        Get to Know Us
      </p>
      <h1 class="font-heading text-4xl md:text-5xl text-spaGreen mb-4">
        <?= isset($about_content['hero']) ? htmlspecialchars($about_content['hero']['title']) : 'About Us' ?>
      </h1>
      <p class="text-gray-600 max-w-2xl mx-auto px-4">
        <?= isset($about_content['hero']) ? nl2br(htmlspecialchars($about_content['hero']['content'])) : '' ?>
      </p>
    </div>

    <!-- Mission & Vision -->
    <section class="py-16 bg-white">
      <div class="max-w-6xl mx-auto px-6 lg:px-12">
        <div class="grid md:grid-cols-2 gap-12">
          <!-- Mission -->
          <div class="bg-spaIvory rounded-2xl p-8 border-l-4 border-spaGold">
            <div class="flex items-center mb-4">
              <svg class="w-10 h-10 text-spaGold mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <h2 class="font-heading text-3xl text-spaGreen">
                <?= isset($about_content['mission']) ? htmlspecialchars($about_content['mission']['title']) : 'Our Mission' ?>
              </h2>
            </div>
            <p class="text-gray-700 leading-relaxed">
              <?= isset($about_content['mission']) ? nl2br(htmlspecialchars($about_content['mission']['content'])) : '' ?>
            </p>
          </div>

          <!-- Vision -->
          <div class="bg-spaIvory rounded-2xl p-8 border-l-4 border-spaGreen">
            <div class="flex items-center mb-4">
              <svg class="w-10 h-10 text-spaGreen mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              <h2 class="font-heading text-3xl text-spaGreen">
                <?= isset($about_content['vision']) ? htmlspecialchars($about_content['vision']['title']) : 'Our Vision' ?>
              </h2>
            </div>
            <p class="text-gray-700 leading-relaxed">
              <?= isset($about_content['vision']) ? nl2br(htmlspecialchars($about_content['vision']['content'])) : '' ?>
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Our Story -->
    <?php if (isset($about_content['story'])): ?>
    <section class="py-16 bg-spaIvory">
      <div class="max-w-4xl mx-auto px-6 lg:px-12 text-center">
        <h2 class="font-heading text-4xl text-spaGreen mb-6">
          <?= htmlspecialchars($about_content['story']['title']) ?>
        </h2>
        <div class="prose prose-lg mx-auto text-gray-700 leading-relaxed">
          <?= nl2br(htmlspecialchars($about_content['story']['content'])) ?>
        </div>
        <?php if (!empty($about_content['story']['image']) && file_exists($about_content['story']['image'])): ?>
          <img src="<?= $about_content['story']['image'] ?>" alt="Our Story" class="mt-8 rounded-2xl shadow-xl mx-auto max-w-2xl w-full object-cover" style="max-height: 400px;">
        <?php endif; ?>
      </div>
    </section>
    <?php endif; ?>

<!-- Team Members -->
    <?php if ($team_query->num_rows > 0): ?>
    <section class="py-16 bg-white">
      <div class="max-w-7xl mx-auto px-6 lg:px-12">
        <div class="text-center mb-12">
          <p class="text-spaGold uppercase tracking-widest text-sm font-medium mb-3">
            Meet the Team
          </p>
          <h2 class="font-heading text-4xl md:text-5xl text-spaGreen mb-4">
            Our Experts
          </h2>
          <p class="text-gray-600 max-w-2xl mx-auto">
            Dedicated professionals committed to your wellness and relaxation.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <?php while($member = $team_query->fetch_assoc()): ?>
            <div class="team-card bg-white rounded-2xl overflow-hidden shadow-lg border border-spaAccent/30">
              <div class="overflow-hidden">
                <?php if (!empty($member['image']) && file_exists($member['image'])): ?>
                  <img src="<?= $member['image'] ?>" alt="<?= htmlspecialchars($member['name']) ?>" class="team-image">
                <?php else: ?>
                  <div class="team-image bg-gradient-to-br from-spaIvory to-spaAccent flex items-center justify-center">
                    <svg class="w-24 h-24 text-spaGold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                  </div>
                <?php endif; ?>
              </div>
              <div class="p-6">
                <h3 class="font-heading text-2xl text-spaGreen mb-1">
                  <?= htmlspecialchars($member['name']) ?>
                </h3>
                <p class="text-spaGold font-medium mb-3">
                  <?= htmlspecialchars($member['position']) ?>
                </p>
                <?php if (!empty($member['bio'])): ?>
                  <p class="text-gray-600 text-sm leading-relaxed">
                    <?= nl2br(htmlspecialchars($member['bio'])) ?>
                  </p>
                <?php endif; ?>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>


<!-- Locations -->
    <?php 
    $locations_query = $conn->query("SELECT * FROM locations WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
    if ($locations_query && $locations_query->num_rows > 0): 
    ?>
    <section class="py-16 bg-spaIvory">
      <div class="max-w-7xl mx-auto px-6 lg:px-12">
        <div class="text-center mb-12">
          <p class="text-spaGold uppercase tracking-widest text-sm font-medium mb-3">
            Find Us
          </p>
          <h2 class="font-heading text-4xl md:text-5xl text-spaGreen mb-4">
            Our Locations
          </h2>
          <p class="text-gray-600 max-w-2xl mx-auto">
            Visit us at any of our convenient locations.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <?php while($location = $locations_query->fetch_assoc()): ?>
            <div class="bg-white rounded-2xl overflow-hidden shadow-lg border border-spaAccent/30">
              <?php if (!empty($location['image']) && file_exists($location['image'])): ?>
                <img src="<?= $location['image'] ?>" alt="<?= htmlspecialchars($location['name']) ?>" class="w-full h-56 object-cover">
              <?php endif; ?>
              
              <div class="p-6">
                <h3 class="font-heading text-2xl text-spaGreen mb-4">
                  <?= htmlspecialchars($location['name']) ?>
                </h3>
                
                <div class="space-y-3">
                  <?php if (!empty($location['address'])): ?>
                    <div class="flex items-start space-x-3">
                      <svg class="w-5 h-5 text-spaGold mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                      </svg>
                      <div>
                        <p class="text-gray-700"><?= nl2br(htmlspecialchars($location['address'])) ?></p>
                        <?php if (!empty($location['city'])): ?>
                          <p class="text-gray-600 text-sm"><?= htmlspecialchars($location['city']) ?></p>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endif; ?>
                  
                  <?php if (!empty($location['phone'])): ?>
                    <div class="flex items-center space-x-3">
                      <svg class="w-5 h-5 text-spaGold flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                      </svg>
                      <a href="tel:<?= htmlspecialchars($location['phone']) ?>" class="text-gray-700 hover:text-spaGold transition">
                        <?= htmlspecialchars($location['phone']) ?>
                      </a>
                    </div>
                  <?php endif; ?>
                  
                  <?php if (!empty($location['email'])): ?>
                    <div class="flex items-center space-x-3">
                      <svg class="w-5 h-5 text-spaGold flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                      </svg>
                      <a href="mailto:<?= htmlspecialchars($location['email']) ?>" class="text-gray-700 hover:text-spaGold transition">
                        <?= htmlspecialchars($location['email']) ?>
                      </a>
                    </div>
                  <?php endif; ?>
                  
                  <?php if (!empty($location['hours'])): ?>
                    <div class="flex items-start space-x-3">
                      <svg class="w-5 h-5 text-spaGold mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                      </svg>
                      <p class="text-gray-700"><?= nl2br(htmlspecialchars($location['hours'])) ?></p>
                    </div>
                  <?php endif; ?>
                </div>
                
                <?php if (!empty($location['map_embed'])): ?>
                  <div class="mt-4">
                    <div class="aspect-w-16 aspect-h-9 rounded-lg overflow-hidden">
                      <?= $location['map_embed'] ?>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <!-- Contact Information -->
    <?php 
    $contacts_query = $conn->query("SELECT * FROM contact_info WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
    $form_settings_query = $conn->query("SELECT * FROM contact_form_settings LIMIT 1");
    $form_settings = $form_settings_query->fetch_assoc();
    ?>
    <section class="py-16 bg-white">
      <div class="max-w-6xl mx-auto px-6 lg:px-12">
        <div class="text-center mb-12">
          <p class="text-spaGold uppercase tracking-widest text-sm font-medium mb-3">
            <?= htmlspecialchars($form_settings['heading']) ?>
          </p>
          <h2 class="font-heading text-4xl md:text-5xl text-spaGreen mb-4">
            <?= htmlspecialchars($form_settings['subheading']) ?>
          </h2>
          <p class="text-gray-600 max-w-2xl mx-auto">
            <?= htmlspecialchars($form_settings['description']) ?>
          </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
          <!-- Contact Info Cards -->
          <?php if ($contacts_query && $contacts_query->num_rows > 0): ?>
          <div>
            <h3 class="font-heading text-2xl text-spaGreen mb-6">Contact Information</h3>
            <div class="space-y-4">
              <?php 
              while($contact = $contacts_query->fetch_assoc()): 
                $icon_map = [
                  'phone' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>',
                  'email' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
                  'location' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>',
                  'clock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>'
                ];
                $icon = isset($icon_map[$contact['icon']]) ? $icon_map[$contact['icon']] : $icon_map['phone'];
              ?>
                <div class="bg-spaIvory rounded-xl p-6 border-l-4 border-spaGold hover:shadow-md transition">
                  <div class="flex items-start space-x-4">
                    <div class="bg-white rounded-full p-3 shadow-sm">
                      <svg class="w-6 h-6 text-spaGold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <?= $icon ?>
                      </svg>
                    </div>
                    <div class="flex-1">
                      <h4 class="font-semibold text-spaGreen mb-1"><?= htmlspecialchars($contact['label']) ?></h4>
                      <?php if ($contact['type'] === 'email'): ?>
                        <a href="mailto:<?= htmlspecialchars($contact['value']) ?>" class="text-gray-700 hover:text-spaGold transition">
                          <?= htmlspecialchars($contact['value']) ?>
                        </a>
                      <?php elseif ($contact['type'] === 'phone'): ?>
                        <a href="tel:<?= htmlspecialchars($contact['value']) ?>" class="text-gray-700 hover:text-spaGold transition">
                          <?= htmlspecialchars($contact['value']) ?>
                        </a>
                      <?php else: ?>
                        <p class="text-gray-700"><?= nl2br(htmlspecialchars($contact['value'])) ?></p>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endwhile; ?>
            </div>
          </div>
          <?php endif; ?>



          <!-- Contact Form -->
          <div>
            <h3 class="font-heading text-2xl text-spaGreen mb-6">Send Us a Message</h3>
            <form id="contactForm" method="POST" action="submit_contact.php" class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-gray-700 text-sm font-bold mb-2">Your Name</label>
                  <input type="text" name="name" required
                         class="w-full px-4 py-3 border border-spaAccent rounded-lg focus:outline-none focus:ring-2 focus:ring-spaGold">
                </div>
                <div>
                  <label class="block text-gray-700 text-sm font-bold mb-2">Your Email</label>
                  <input type="email" name="email" required
                         class="w-full px-4 py-3 border border-spaAccent rounded-lg focus:outline-none focus:ring-2 focus:ring-spaGold">
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-gray-700 text-sm font-bold mb-2">Phone (Optional)</label>
                  <input type="tel" name="phone"
                         class="w-full px-4 py-3 border border-spaAccent rounded-lg focus:outline-none focus:ring-2 focus:ring-spaGold">
                </div>
                <div>
                  <label class="block text-gray-700 text-sm font-bold mb-2">Subject</label>
                  <input type="text" name="subject" required
                         class="w-full px-4 py-3 border border-spaAccent rounded-lg focus:outline-none focus:ring-2 focus:ring-spaGold">
                </div>
              </div>
              
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Message</label>
                <textarea name="message" rows="5" required
                          class="w-full px-4 py-3 border border-spaAccent rounded-lg focus:outline-none focus:ring-2 focus:ring-spaGold"></textarea>
              </div>
              
              <div id="contactAlert" class="hidden px-4 py-3 rounded-lg"></div>
              
              <button type="submit"
                      class="w-full bg-spaGold hover:bg-spaGreen text-white py-3 rounded-lg transition duration-300 font-medium">
                Send Message
              </button>
            </form>
          </div>
        </div>
      </div>
    </section>

    <script>
    document.getElementById('contactForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      const submitBtn = this.querySelector('button[type="submit"]');
      const alert = document.getElementById('contactAlert');
      
      submitBtn.disabled = true;
      submitBtn.textContent = 'Sending...';
      
      fetch('submit_contact.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert.className = 'bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg';
          alert.textContent = data.message;
          this.reset();
        } else {
          alert.className = 'bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg';
          alert.textContent = data.message;
        }
        alert.classList.remove('hidden');
        
        setTimeout(() => alert.classList.add('hidden'), 5000);
      })
      .catch(error => {
        alert.className = 'bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg';
        alert.textContent = 'An error occurred. Please try again.';
        alert.classList.remove('hidden');
      })
      .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Send Message';
      });
    });
    </script>

    

    <!-- Call to Action -->
    <section class="py-16 bg-spaGreen text-white">
      <div class="max-w-4xl mx-auto px-6 lg:px-12 text-center">
        <h2 class="font-heading text-4xl mb-6">Ready to Experience True Relaxation?</h2>
        <p class="text-lg mb-8 text-spaIvory">
          Book your appointment today and let us take care of you.
        </p>
        <a href="index.php#appointment-section">
          <button class="bg-spaGold hover:bg-white hover:text-spaGreen text-white px-8 py-4 rounded-lg transition duration-300 font-medium text-lg">
            Book an Appointment →
          </button>
        </a>
      </div>
    </section>
  </main>

  <?php render_footer(); ?>

  <?php login_modal(); ?>
</body>
</html>

<?php $conn->close(); ?>