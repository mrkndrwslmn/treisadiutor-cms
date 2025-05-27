<?php
$isLoggedIn = isset($_SESSION['user']); // Adjust the session key as per your application
?>

<!-- Header -->
<header class="bg-white shadow-md fixed w-full z-50">
    <div class="container mx-auto px-4 py-4">
        <div class="flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold text-primary">
                <img src="assets/images/logo.png" alt="Treis Adiutor Logo" class="h-10 inline-block rounded-md shadow-md">
                Treis <span class="text-accent">Adiutor</span>
            </a>
            
            <!-- Mobile menu button -->
            <div class="md:hidden">
                <button id="mobile-menu-button" class="text-gray-700 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
            
            <!-- Desktop Navigation -->
            <nav class="hidden md:block">
                <ul class="flex space-x-8">
                    <?php if ($isLoggedIn): ?>
                        <li><a href="client/dashboard.php" class="text-gray-700 hover:text-primary transition-colors duration-300 font-medium">Dashboard</a></li>
                        <li><a href="profile.php" class="text-gray-700 hover:text-primary transition-colors duration-300 font-medium">Profile</a></li>
                        <li><a href="logout.php" class="text-gray-700 hover:text-primary transition-colors duration-300 font-medium">Logout</a></li>
                    <?php else: ?>
                        <li><a href="index.php" class="text-gray-700 hover:text-primary transition-colors duration-300 font-medium">Home</a></li>
                        <li><a href="index.php#services" class="text-gray-700 hover:text-primary transition-colors duration-300 font-medium">Services</a></li>
                        <li><a href="about.php" class="text-gray-700 hover:text-primary transition-colors duration-300 font-medium">About</a></li>
                        <li><a href="index.php#testimonials" class="text-gray-700 hover:text-primary transition-colors duration-300 font-medium">Testimonials</a></li>
                        <li><a href="login.php" class="text-gray-700 hover:text-primary transition-colors duration-300 font-medium">Login</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        
        <!-- Mobile Navigation -->
        <div id="mobile-menu" class="md:hidden hidden">
            <ul class="flex flex-col space-y-4 pb-4">
                <?php if ($isLoggedIn): ?>
                    <li><a href="dashboard.php" class="block text-gray-700 hover:text-primary transition-colors duration-300 font-medium">Dashboard</a></li>
                    <li><a href="profile.php" class="block text-gray-700 hover:text-primary transition-colors duration-300 font-medium">Profile</a></li>
                    <li><a href="logout.php" class="block text-gray-700 hover:text-primary transition-colors duration-300 font-medium">Logout</a></li>
                <?php else: ?>
                    <li><a href="index.php" class="block text-gray-700 hover:text-primary transition-colors duration-300 font-medium">Home</a></li>
                    <li><a href="index.php#services" class="block text-gray-700 hover:text-primary transition-colors duration-300 font-medium">Services</a></li>
                    <li><a href="about.php" class="block text-gray-700 hover:text-primary transition-colors duration-300 font-medium">About</a></li>
                    <li><a href="index.php#testimonials" class="block text-gray-700 hover:text-primary transition-colors duration-300 font-medium">Testimonials</a></li>
                    <li><a href="TA-CMS/login.php" class="block text-gray-700 hover:text-primary transition-colors duration-300 font-medium">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</header>