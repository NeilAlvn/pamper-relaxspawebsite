<?php
// Fetch products with stock
$products_query = $conn->query("SELECT * FROM products WHERE stock > 0 ORDER BY id DESC LIMIT 8");
?>

<style>
  .products-component .product-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.2s ease;
    border: 1px solid rgba(228, 212, 195, 0.3);
  }

  .products-component .product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  }

  .products-component .product-image-wrapper {
    position: relative;
    width: 100%;
    height: 280px;
    overflow: hidden;
    background: linear-gradient(135deg, #f8f5ef 0%, #e2d4c3 100%);
  }

  .products-component .product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
  }

  .products-component .product-card:hover .product-image {
    transform: scale(1.05);
  }

  .products-component .description-wrapper {
    position: relative;
  }

  .products-component .description {
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

  .products-component .description-tooltip {
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

  .products-component .description-tooltip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 8px solid transparent;
    border-top-color: #d4b26a;
  }

  .products-component .description-wrapper:hover .description-tooltip {
    opacity: 1;
    visibility: visible;
  }
</style>

<!-- Products Section -->
<section class="products-component py-16 px-6 lg:px-12 bg-spaIvory" id="products-section">
  <div style="max-width: 1400px; margin: 0 auto;">
    
    <!-- Section Header -->
    <div class="text-center mb-12">
      <p class="text-spaGold uppercase tracking-widest text-sm font-medium mb-3">
        Spa Essentials
      </p>
      <h2 class="font-heading text-4xl md:text-5xl text-spaGreen mb-4">
        Our Products
      </h2>
      <p class="text-gray-600 max-w-2xl mx-auto">
        Premium spa and wellness products for your daily self-care routine.
      </p>
    </div>

    <!-- Products Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <?php if ($products_query && $products_query->num_rows > 0): ?>
        <?php while($product = $products_query->fetch_assoc()): ?>
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
              
              <div class="flex items-center justify-between mt-4">
                <span class="font-heading text-2xl text-spaGold">₱<?= number_format($product['price'], 2) ?></span>
                <span class="text-sm text-gray-500"><?= $product['stock'] ?> in stock</span>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="col-span-full text-center py-12">
          <p class="text-gray-500">No products available at the moment.</p>
        </div>
      <?php endif; ?>
    </div>
    
    <!-- View All Button -->
    <div class="text-center mt-12">
      <a href="products.php" class="inline-block px-8 py-3 bg-spaGold hover:bg-spaGreen text-white rounded-lg transition duration-300 font-medium">
        View All Products →
      </a>
    </div>
    
  </div>
</section>