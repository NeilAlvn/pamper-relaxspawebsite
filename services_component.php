<?php
// Fetch active services
$services_query = $conn->query("SELECT * FROM services WHERE is_active = 1 ORDER BY id DESC");
?>

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

  .price {
    margin: 0;
    font-size: 28px;
    color: #d4b26a;
    font-weight: 600;
    font-family: 'Playfair Display', serif;
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
  }
</style>

<!-- Services Section -->
<section class="py-16 px-6 lg:px-12 bg-white" id="services-section">
  <div style="max-width: 1400px; margin: 0 auto;">
    
    <!-- Section Header -->
    <div class="text-center mb-12">
      <p class="text-spaGold uppercase tracking-widest text-sm font-medium mb-3">
        Discover Relaxation
      </p>
      <h2 class="font-heading text-4xl md:text-5xl text-spaGreen mb-4">
        Our Services
      </h2>
      <p class="text-gray-600 max-w-2xl mx-auto">
        Indulge in luxury treatments designed to rejuvenate your body, mind, and soul.
      </p>
    </div>

    <!-- Services Grid -->
    <div class="services-grid">
      <?php if ($services_query && $services_query->num_rows > 0): ?>
        <?php while($row = $services_query->fetch_assoc()): ?>
          <div class="card">
            <a href="#appointment-section" onclick="selectService(<?= $row['id'] ?>)">
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
      <?php else: ?>
        <div class="col-span-full text-center py-12">
          <p class="text-gray-500">No services available at the moment.</p>
        </div>
      <?php endif; ?>
    </div>
    
    <!-- View All Button -->
    <div class="text-center mt-12">
      <a href="services.php" class="inline-block px-8 py-3 bg-spaGold hover:bg-spaGreen text-white rounded-lg transition duration-300 font-medium">
        View All Services →
      </a>
    </div>
    
  </div>
</section>

<script>
  function selectService(serviceId) {
    // Set the service in the appointment form
    setTimeout(function() {
      const serviceSelect = document.getElementById('service-select');
      if (serviceSelect) {
        serviceSelect.value = serviceId;
        serviceSelect.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }, 100);
  }
</script>