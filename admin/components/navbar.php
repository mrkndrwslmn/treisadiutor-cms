<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Header -->
<header class="bg-white shadow-md fixed w-full z-50">
    <div class="container mx-auto px-4 py-4">
        <div class="flex justify-between items-center">
            <a href="dashboard.php" class="text-2xl font-bold text-primary">
                <img src="../assets/images/logo.png" alt="Treis Adiutor Logo" class="h-10 inline-block rounded-md shadow-md">
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
                    <li>
                        <a href="dashboard.php"
                           class="text-gray-700 hover:text-primary transition-colors duration-300 font-medium
                           <?php if ($current_page === 'dashboard.php') echo 'border-b-2 border-primary text-primary'; ?>">
                           Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="manage_users.php"
                           class="text-gray-700 hover:text-primary transition-colors duration-300 font-medium
                           <?php if ($current_page === 'manage_users.php') echo 'border-b-2 border-primary text-primary'; ?>">
                           Users
                        </a>
                    </li>
                    <li>
                        <a href="requests.php"
                           class="text-gray-700 hover:text-primary transition-colors duration-300 font-medium
                           <?php if ($current_page === 'requests.php') echo 'border-b-2 border-primary text-primary'; ?>">
                           Requests
                        </a>
                    </li>
                    <li>
                        <a href="reports.php"
                           class="text-gray-700 hover:text-primary transition-colors duration-300 font-medium
                           <?php if ($current_page === 'reports.php') echo 'border-b-2 border-primary text-primary'; ?>">
                           Reports
                        </a>
                    </li>
                    <li class="relative" id="profile-menu-parent">
                        <a href="#" class="text-gray-700 hover:text-primary transition-colors duration-300 font-medium">Profile</a>
                        <ul id="profile-menu-dropdown" class="absolute left-0 bg-white shadow-md rounded-md py-2 hidden"
                            style="min-width: 180px;">
                            <li><a href="profile.php" class="block px-4 py-2 text-gray-700 hover:text-primary transition-colors duration-300">Manage Account</a></li>
                            <li><a href="logout.php" class="block px-4 py-2 text-gray-700 hover:text-primary transition-colors duration-300">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </div>
        
        <!-- Mobile Navigation -->
        <div id="mobile-menu" class="md:hidden hidden mt-4">
            <ul class="flex flex-col space-y-4 pb-4">
                <li>
                    <a href="dashboard.php"
                       class="block text-gray-700 hover:text-primary transition-colors duration-300 font-medium
                       <?php if ($current_page === 'dashboard.php') echo 'border-b-2 border-primary text-primary'; ?>">
                       Dashboard
                    </a>
                </li>
                <li>
                    <a href="manage_users.php"
                       class="block text-gray-700 hover:text-primary transition-colors duration-300 font-medium
                       <?php if ($current_page === 'manage_users.php') echo 'border-b-2 border-primary text-primary'; ?>">
                       Users
                    </a>
                </li>
                <li>
                    <a href="requests.php"
                       class="block text-gray-700 hover:text-primary transition-colors duration-300 font-medium
                       <?php if ($current_page === 'requests.php') echo 'border-b-2 border-primary text-primary'; ?>">
                       Requests
                    </a>
                </li>
                <li>
                    <a href="reports.php"
                       class="block text-gray-700 hover:text-primary transition-colors duration-300 font-medium
                       <?php if ($current_page === 'reports.php') echo 'border-b-2 border-primary text-primary'; ?>">
                       Reports
                    </a>
                </li>
                <li class="relative group">
                    <a href="#" class="block text-gray-700 hover:text-primary transition-colors duration-300 font-medium">Profile</a>
                    <ul class="absolute left-0 bg-white shadow-md rounded-md py-2 hidden group-hover:block"
                        style="min-width: 180px;">
                        <li><a href="profile.php" class="block text-gray-700 hover:text-primary transition-colors duration-300">Manage Account</a></li>
                        <li><a href="logout.php" class="block text-gray-700 hover:text-primary transition-colors duration-300">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</header>

<script>
    // Dropdown for Profile menu (desktop)
    const profileMenuParent = document.getElementById('profile-menu-parent');
    const profileMenuDropdown = document.getElementById('profile-menu-dropdown');
    if (profileMenuParent && profileMenuDropdown) {
        profileMenuParent.addEventListener('mouseenter', () => {
            profileMenuDropdown.classList.remove('hidden');
        });
        profileMenuParent.addEventListener('mouseleave', () => {
            profileMenuDropdown.classList.add('hidden');
        });
    }

    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }
</script>