<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function renderNavigation() {
    $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    ?>
    <div class="hidden md:block">
        <div class="ml-10 flex items-baseline space-x-4">
            <?php if ($isAdmin): ?>
                <a href="appointments.php" class="hover:text-spaGold px-3 py-2 rounded-md text-sm font-medium">Appointments</a>
                <a href="products_dashboard.php" class="hover:text-spaGold px-3 py-2 rounded-md text-sm font-medium">Inventory</a>
            <?php endif; ?>
            
            <a href="index.php" class="hover:text-spaGold px-3 py-2 rounded-md text-sm font-medium">Home</a>
            <a href="services.php" class="hover:text-spaGold px-3 py-2 rounded-md text-sm font-medium">Services</a>
            <a href="products.php" class="hover:text-spaGold px-3 py-2 rounded-md text-sm font-medium">Products</a>
            <a href="transactions.php" class="hover:text-spaGold px-3 py-2 rounded-md text-sm font-medium">Transactions</a>
            
            <?php if ($isAdmin): ?>
                <a href="sales.php" class="hover:text-spaGold px-3 py-2 rounded-md text-sm font-medium">Sales</a>
            <?php endif; ?>
           <a href="about_us.php" class="hover:text-spaGold px-3 py-2 rounded-md text-sm font-medium">About Us</a>
        </div>
    </div>
    <?php
    
}
function login_button() {
    if (!isset($_SESSION['username'])) {
        ?>
        <div class="fixed top-2 right-2 z-[9999]">
            <button onclick="document.getElementById('loginModal').classList.remove('hidden')" 
                    class="flex items-center justify-center w-12 h-12 rounded-full bg-spaGold text-white hover:bg-white hover:text-spaGold border-2 border-spaGold transition duration-300 shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </button>
        </div>
        <?php
    }
}

function login_modal() {
    ?>
    <!-- Login Modal -->
    <div id="loginModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Login</h2>
                <button onclick="document.getElementById('loginModal').classList.add('hidden')" 
                        class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Username</label>
                    <input type="text" name="username" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                    <input type="password" name="password" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
                </div>
                
                <button type="submit" name="login" 
                        class="w-full bg-spaGold text-white py-2 rounded-md font-medium hover:bg-opacity-90 transition duration-300">
                    Login
                </button>
            </form>
        </div>
    </div>
    <?php
}

function processLogin($conn) {
    if (isset($_POST['login'])) {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = $_POST['password'];
        
        // Query only roles table
        $query = "SELECT role_id, username, password_hash, role_name 
                  FROM roles 
                  WHERE username = '$username' 
                  LIMIT 1";
        
        $result = mysqli_query($conn, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            
            // Direct password comparison - no hashing
            if ($password === $user['password_hash']) {
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role_name'];
                
                // Write session and redirect
                session_write_close();
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                echo "<div style='position: fixed; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: center; z-index: 99999; background: rgba(0,0,0,0.5);'>
                        <div class='bg-white p-6 rounded-lg shadow-lg text-center'>
                            <p class='text-red-600 font-semibold mb-4'>Invalid password!</p>
                            <button onclick='this.parentElement.parentElement.remove()' class='bg-spaGold text-white px-4 py-2 rounded hover:bg-opacity-90'>OK</button>
                        </div>
                      </div>";
            }
        } else {
            echo "<div style='position: fixed; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: center; z-index: 99999; background: rgba(0,0,0,0.5);'>
                    <div class='bg-white p-6 rounded-lg shadow-lg text-center'>
                        <p class='text-red-600 font-semibold mb-4'>User not found!</p>
                        <button onclick='this.parentElement.parentElement.remove()' class='bg-spaGold text-white px-4 py-2 rounded hover:bg-opacity-90'>OK</button>
                    </div>
                  </div>";
        }
    }
}

function logout_button() {
    if (isset($_SESSION['username'])) {
        ?>
        <div class="fixed top-2 right-2 z-[9999]">
            <!-- Logout Button -->
            <a href="?logout=1" title="Logout">
                <button class="flex items-center justify-center w-12 h-12 rounded-full bg-red-500 text-white hover:bg-white hover:text-red-500 border-2 border-red-500 transition duration-300 shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            </a>
        </div>
        <?php
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// NEW FOOTER FUNCTION
function render_footer() {
    ?>
    <footer class="bg-spaGreen text-spaIvory relative overflow-hidden">
        <!-- Decorative background pattern -->
        <div class="absolute inset-0 opacity-5">
            <div class="absolute top-0 right-0 w-96 h-96 bg-spaGold rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-spaGold rounded-full blur-3xl"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-8 md:px-12 py-16">
            <!-- Main Footer Content -->
            <div class="grid md:grid-cols-3 gap-12 mb-12">
                <!-- Brand Column -->
                <div class="md:col-span-1">
                    <img src="assets/logo/Pamper Website Logo250x100.png" 
                         alt="Pamper & Relax Logo" 
                         class="h-16 mb-6 brightness-0 invert opacity-90 hover:opacity-100 transition-opacity duration-300">
                    <p class="text-sm opacity-80 leading-relaxed font-body">
                        Experience tranquility and rejuvenation at our premier wellness sanctuary.
                    </p>
                    
                    <!-- Social Media - Horizontal Layout -->
                    <div class="mt-6">
                        <h4 class="font-heading text-spaGold text-base mb-3 tracking-wide">Follow Us</h4>
                        <div class="flex space-x-4">
                            <a href="#" class="w-10 h-10 rounded-full bg-spaGold bg-opacity-10 flex items-center justify-center hover:bg-spaGold hover:text-spaGreen transition-all duration-300 group">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </a>
                            <a href="#" class="w-10 h-10 rounded-full bg-spaGold bg-opacity-10 flex items-center justify-center hover:bg-spaGold hover:text-spaGreen transition-all duration-300 group">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                </svg>
                            </a>
                            <a href="#" class="w-10 h-10 rounded-full bg-spaGold bg-opacity-10 flex items-center justify-center hover:bg-spaGold hover:text-spaGreen transition-all duration-300 group">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="font-heading text-spaGold text-lg mb-4 tracking-wide">Quick Links</h4>
                    <ul class="space-y-2.5 font-body">
                        <li>
                            <a href="index.php" class="text-sm opacity-80 hover:opacity-100 hover:text-spaGold transition-all duration-300 inline-flex items-center group">
                                <span class="w-0 group-hover:w-3 h-px bg-spaGold transition-all duration-300 mr-0 group-hover:mr-2"></span>
                                Home
                            </a>
                        </li>
                        <li>
                            <a href="services.php" class="text-sm opacity-80 hover:opacity-100 hover:text-spaGold transition-all duration-300 inline-flex items-center group">
                                <span class="w-0 group-hover:w-3 h-px bg-spaGold transition-all duration-300 mr-0 group-hover:mr-2"></span>
                                Services
                            </a>
                        </li>
                        <li>
                            <a href="products.php" class="text-sm opacity-80 hover:opacity-100 hover:text-spaGold transition-all duration-300 inline-flex items-center group">
                                <span class="w-0 group-hover:w-3 h-px bg-spaGold transition-all duration-300 mr-0 group-hover:mr-2"></span>
                                Products
                            </a>
                        </li>
                        <li>
                            <a href="transactions.php" class="text-sm opacity-80 hover:opacity-100 hover:text-spaGold transition-all duration-300 inline-flex items-center group">
                                <span class="w-0 group-hover:w-3 h-px bg-spaGold transition-all duration-300 mr-0 group-hover:mr-2"></span>
                                Transactions
                            </a>
                        </li>
                        <li>
                            <a href="about_us.php" class="text-sm opacity-80 hover:opacity-100 hover:text-spaGold transition-all duration-300 inline-flex items-center group">
                                <span class="w-0 group-hover:w-3 h-px bg-spaGold transition-all duration-300 mr-0 group-hover:mr-2"></span>
                                About Us
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h4 class="font-heading text-spaGold text-lg mb-4 tracking-wide">Contact</h4>
                    <ul class="space-y-3 font-body text-sm opacity-80">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 mr-3 mt-0.5 text-spaGold flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="leading-relaxed">123 Wellness Avenue<br>Makati City, Metro Manila</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 mr-3 text-spaGold flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <span>(02) 1234-5678</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 mr-3 text-spaGold flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span>info@pamperrelax.com</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Divider -->
            <div class="border-t border-spaGold border-opacity-20 pt-8">
                <!-- Bottom Bar -->
                <div class="flex flex-col md:flex-row justify-center items-center">
                    <p class="text-sm opacity-70 font-body">
                        &copy; <?php echo date('Y'); ?> Pamper & Relax Spa. All rights reserved.
                    </p>
                </div>
            </div>
        </div>

        <!-- Scroll to top button -->
        <button onclick="window.scrollTo({top: 0, behavior: 'smooth'})" 
                class="fixed bottom-8 right-8 w-12 h-12 bg-spaGold text-spaGreen rounded-full shadow-lg hover:shadow-xl transform hover:scale-110 transition-all duration-300 flex items-center justify-center group z-50"
                id="scrollToTop"
                style="display: none;">
            <svg class="w-6 h-6 group-hover:-translate-y-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
            </svg>
        </button>

        <script>
            // Show/hide scroll to top button
            window.addEventListener('scroll', function() {
                const scrollBtn = document.getElementById('scrollToTop');
                if (scrollBtn) {
                    if (window.pageYOffset > 300) {
                        scrollBtn.style.display = 'flex';
                    } else {
                        scrollBtn.style.display = 'none';
                    }
                }
            });
        </script>
    </footer>
    <?php
}
?>