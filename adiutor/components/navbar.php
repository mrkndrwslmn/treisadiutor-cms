<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user']);

// Determine current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Header -->
<header class="bg-white shadow-lg fixed w-full z-50 border-b border-gray-100">
    <div class="container mx-auto px-6 py-3">
        <div class="flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold text-primary flex items-center">
                <img src="../assets/images/logo.png" alt="Treis Adiutor Logo" class="h-12 mr-3 inline-block rounded-md shadow-sm">
                <span>Treis <span class="text-accent font-extrabold">Adiutor</span></span>
            </a>
            
            <!-- Mobile menu button -->
            <div class="md:hidden">
                <button id="mobile-menu-button" class="text-gray-700 hover:text-primary focus:outline-none p-2 rounded-md transition-colors duration-300">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
            
            <!-- Desktop Navigation -->
            <nav class="hidden md:block">
                <ul class="flex space-x-8 items-center">
                    <?php if ($isLoggedIn): ?>
                        <li>
                            <a href="dashboard.php" class="text-gray-700 hover:text-primary transition-colors duration-300 font-medium <?= $current_page === 'dashboard.php' ? 'border-b-2 border-primary text-primary' : '' ?>">
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="tasks.php" class="text-gray-700 hover:text-primary transition-colors duration-300 font-medium <?= $current_page === 'tasks.php' ? 'border-b-2 border-primary text-primary' : '' ?>">
                                Tasks
                            </a>
                        </li>
                        <li>
                            <a href="feedback.php" class="text-gray-700 hover:text-primary transition-colors duration-300 font-medium <?= $current_page === 'feedbacks.php' ? 'border-b-2 border-primary text-primary' : '' ?>">
                                Feedback
                            </a>
                        </li>
                        <li class="relative group">
                            <a href="#" class="text-gray-700 hover:text-primary transition-colors duration-300 font-medium">
                                Account
                            </a>
                            <ul class="absolute left-0 bg-white shadow-md rounded-md py-2 hidden group-hover:block" style="min-width: 180px;">
                                <li><a href="profile.php" class="block px-4 py-2 text-gray-700 hover:text-primary transition-colors duration-300 <?= $current_page === 'profile.php' ? 'text-primary' : '' ?>">Profile</a></li>
                                <li><a href="logout.php" class="block px-4 py-2 text-gray-700 hover:text-primary transition-colors duration-300">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li><a href="index.php" class="py-2 px-1 text-gray-700 hover:text-primary transition-colors duration-300 font-medium border-b-2 <?= $current_page === 'index.php' ? 'border-primary text-primary' : 'border-transparent' ?>">Home</a></li>
                        <li><a href="index.php#services" class="py-2 px-1 text-gray-700 hover:text-primary transition-colors duration-300 font-medium border-b-2 border-transparent">Services</a></li>
                        <li><a href="about.php" class="py-2 px-1 text-gray-700 hover:text-primary transition-colors duration-300 font-medium border-b-2 <?= $current_page === 'about.php' ? 'border-primary text-primary' : 'border-transparent' ?>">About</a></li>
                        <li><a href="index.php#testimonials" class="py-2 px-1 text-gray-700 hover:text-primary transition-colors duration-300 font-medium border-b-2 border-transparent">Testimonials</a></li>
                        <li><a href="login.php" class="ml-2 py-2 px-6 bg-primary text-white hover:bg-primary-dark rounded-md transition-colors duration-300 font-medium shadow-sm">Login</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        
        <!-- Mobile Navigation -->
        <div id="mobile-menu" class="md:hidden hidden mt-4">
            <ul class="flex flex-col space-y-4 pb-4">
                <?php if ($isLoggedIn): ?>
                    <li>
                        <a href="dashboard.php" class="block text-gray-700 hover:text-primary transition-colors duration-300 font-medium <?= $current_page === 'dashboard.php' ? 'border-b-2 border-primary text-primary' : '' ?>">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="tasks.php" class="block text-gray-700 hover:text-primary transition-colors duration-300 font-medium <?= $current_page === 'tasks.php' ? 'border-b-2 border-primary text-primary' : '' ?>">
                            Tasks
                        </a>
                    </li>
                    <li>
                        <a href="feedback.php" class="block text-gray-700 hover:text-primary transition-colors duration-300 font-medium <?= $current_page === 'feedbacks.php' ? 'border-b-2 border-primary text-primary' : '' ?>">
                            Feedback
                        </a>
                    </li>
                    <li class="relative group">
                        <a href="#" class="block text-gray-700 hover:text-primary transition-colors duration-300 font-medium">Account</a>
                        <ul class="absolute left-0 bg-white shadow-md rounded-md py-2 hidden group-hover:block" style="min-width: 180px;">
                            <li><a href="profile.php" class="block text-gray-700 hover:text-primary transition-colors duration-300 <?= $current_page === 'profile.php' ? 'text-primary' : '' ?>">Profile</a></li>
                            <li><a href="logout.php" class="block text-gray-700 hover:text-primary transition-colors duration-300">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li><a href="index.php" class="block py-2 px-3 rounded <?= $current_page === 'index.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-200' ?> transition-colors duration-300 font-medium">
                        <i class="fas fa-home mr-2"></i> Home
                    </a></li>
                    <li><a href="index.php#services" class="block py-2 px-3 rounded text-gray-700 hover:bg-gray-200 transition-colors duration-300 font-medium">
                        <i class="fas fa-cogs mr-2"></i> Services
                    </a></li>
                    <li><a href="about.php" class="block py-2 px-3 rounded <?= $current_page === 'about.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-200' ?> transition-colors duration-300 font-medium">
                        <i class="fas fa-info-circle mr-2"></i> About
                    </a></li>
                    <li><a href="index.php#testimonials" class="block py-2 px-3 rounded text-gray-700 hover:bg-gray-200 transition-colors duration-300 font-medium">
                        <i class="fas fa-comment mr-2"></i> Testimonials
                    </a></li>
                    <li><a href="login.php" class="block py-2 px-3 mt-2 bg-primary text-white hover:bg-primary-dark rounded transition-colors duration-300 font-medium">
                        <i class="fas fa-sign-in-alt mr-2"></i> Login
                    </a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</header>

<!-- Add JavaScript for mobile menu toggle -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile menu toggle
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        
        if (mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', function() {
                // Toggle the mobile menu
                if (mobileMenu.classList.contains('hidden')) {
                    mobileMenu.classList.remove('hidden');
                    mobileMenuButton.innerHTML = '<i class="fas fa-times text-xl"></i>';
                } else {
                    mobileMenu.classList.add('hidden');
                    mobileMenuButton.innerHTML = '<i class="fas fa-bars text-xl"></i>';
                }
            });
        }
    });
</script>