<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['userID'];

// Fetch current user data
$stmt = $conn->prepare("SELECT fullName, email, password FROM users WHERE userID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fullName, $email, $hashed_current_password);
$stmt->fetch();
$stmt->close();

$errors = [];

if (isset($_POST['update_profile'])) {
    $new_name = trim($_POST['fullName']);
    $new_email = trim($_POST['email']);
    $new_password = trim($_POST['new_password']);
    $old_password = trim($_POST['old_password']);

    try {
        // Update with password
        if (!empty($new_password)) {
            if (empty($old_password)) {
                $errors[] = "Old password is required to change your password.";
            } elseif (!password_verify($old_password, $hashed_current_password)) {
                $errors[] = "Old password does not match.";
            } else {
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET fullName = ?, email = ?, password = ? WHERE userId = ?");
                $stmt->bind_param("sssi", $new_name, $new_email, $new_hashed_password, $user_id);
                $stmt->execute();
                $stmt->close();
                header("Location: profile.php?success=1");
                exit;
            }
        }

        // Update without password
        if (empty($new_password) && empty($errors)) {
            $stmt = $conn->prepare("UPDATE users SET fullName = ?, email = ? WHERE userID = ?");
            $stmt->bind_param("ssi", $new_name, $new_email, $user_id);
            $stmt->execute();
            $stmt->close();
            header("Location: profile.php?success=1");
            exit;
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() === 1062) { // Error code for duplicate entry
            $errors[] = "The email address is already in use. Please use a different email.";
        } else {
            $errors[] = "An unexpected error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3a5a78',
                        secondary: '#6c9ab5',
                        accent: '#f9a826',
                        dark: '#1a2a3a',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-sans">

<?php include 'components/navbar.php'; ?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg overflow-hidden fade-in" data-aos="fade-up">
        <div class="bg-accent py-6 px-4">
            <h2 class="text-2xl font-bold text-white text-center">Edit Your Profile</h2>
        </div>
        
        <div class="p-6 sm:p-8">
            <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded mb-6 animate__animated animate__fadeIn">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm">Profile updated successfully!</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-500"></i>
                        </div>
                        <div class="ml-3">
                            <ul class="list-disc ml-5 text-sm">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-dark mb-1">Full Name</label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-primary"></i>
                        </div>
                        <input id="name" type="text" name="fullName" value="<?= htmlspecialchars($fullName) ?>" 
                               class="form-input block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition duration-300 ease-in-out sm:text-sm" 
                               required>
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-dark mb-1">Email Address</label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-primary"></i>
                        </div>
                        <input id="email" type="email" name="email" value="<?= htmlspecialchars($email) ?>" 
                               class="form-input block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition duration-300 ease-in-out sm:text-sm" 
                               required>
                    </div>
                </div>

                <!-- New Password -->
                <div>
                    <label for="new_password" class="block text-sm font-medium text-dark mb-1">
                        New Password <span class="text-gray-500 text-xs">(Leave blank to keep current)</span>
                    </label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-primary"></i>
                        </div>
                        <input id="new_password" type="password" name="new_password" 
                               class="form-input block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition duration-300 ease-in-out sm:text-sm">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <button type="button" id="toggleNewPassword" class="text-secondary hover:text-accent focus:outline-none transition duration-300">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Old Password -->
                <div>
                    <label for="old_password" class="block text-sm font-medium text-gray-700 mb-1">
                        Current Password <span class="text-gray-500 text-xs">(Required to change password)</span>
                    </label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-key text-gray-400"></i>
                        </div>
                        <input id="old_password" type="password" name="old_password" 
                               class="form-input block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out sm:text-sm">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <button type="button" id="toggleOldPassword" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Submit button -->
                <div class="pt-4">
                    <button type="submit" name="update_profile" 
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-save"></i>
                        </span>
                        Save Changes
                    </button>
                </div>
            </form>
            
            <div class="mt-6 text-center">
                <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../components/footer.php'; ?>

<script>
    // Password visibility toggle
    document.getElementById('toggleNewPassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('new_password');
        const icon = this.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
    
    document.getElementById('toggleOldPassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('old_password');
        const icon = this.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });

    // Initialize AOS animation library
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
    }
</script>
</body>
</html>