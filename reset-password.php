<?php
session_start();
require_once 'includes/db.php';

$error = '';
$success = '';
$token = '';
$validToken = false;

// Get token from URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Validate token
    $stmt = $conn->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $email = $row['email'];
        $expires = strtotime($row['expires_at']);
        
        // Check if token has expired
        if (time() > $expires) {
            $error = "The password reset link has expired. Please request a new one.";
        } else {
            $validToken = true;
        }
    } else {
        $error = "Invalid password reset link. Please request a new one.";
    }
    
    $stmt->close();
} else {
    $error = "Token not provided. Please use the link sent to your email.";
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate password
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Update password in database
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // First, get the user ID using the email
        $stmt = $conn->prepare("SELECT userID FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $user_id = $user['userID'];
            
            // Update the password
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE userID = ?");
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($update_stmt->execute()) {
                // Delete the password reset token
                $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
                $delete_stmt->bind_param("s", $email);
                $delete_stmt->execute();
                $delete_stmt->close();
                
                $success = "Your password has been reset successfully. You can now log in with your new password.";
                $validToken = false; // Hide the form
            } else {
                $error = "Failed to update password. Please try again.";
            }
            
            $update_stmt->close();
        } else {
            $error = "User not found.";
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - GCard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
<body class="flex flex-col min-h-screen bg-gray-100 font-sans">

    <?php include 'components/navbar.php'; ?>
    
    <div class="flex-grow flex items-center justify-center px-4 py-6">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-center mb-6">Reset Password</h2>
                
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div>
                    <div class="text-center mt-4">
                        <a href="forgot-password.php" class="text-blue-500 hover:text-blue-700">Request a new reset link</a>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $success; ?></div>
                    <div class="text-center mt-4">
                        <a href="login.php" class="bg-accent hover:bg-orange-500 text-white font-medium py-2 px-4 rounded-md transition duration-200">Go to Login</a>
                    </div>
                <?php endif; ?>
                
                <?php if ($validToken): ?>
                    <p class="mb-6 text-gray-600">Please enter your new password below.</p>
                    
                    <form method="POST" action="reset-password.php?token=<?php echo htmlspecialchars($token); ?>">
                        <div class="mb-4 relative">
                            <label for="password" class="block text-gray-700 font-medium mb-2">New Password</label>
                            <input type="password" id="password" name="password" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-sm text-gray-500 mt-1">Password must be at least 8 characters long.</p>
                            <button type="button" class="absolute right-3 top-9 text-gray-500"
                                    onclick="togglePassword('password', this)">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        
                        <div class="mb-6 relative">
                            <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="button" class="absolute right-3 top-9 text-gray-500"
                                    onclick="togglePassword('confirm_password', this)">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        
                        <div class="mb-4">
                            <button type="submit" 
                                    class="w-full bg-accent hover:bg-orange-500 text-white font-medium py-2 px-4 rounded-md transition duration-200">
                                Reset Password
                            </button>
                        </div>
                    </form>
                    <script>
                    function togglePassword(fieldId, btn) {
                        const field = document.getElementById(fieldId);
                        const icon = btn.querySelector('i');
                        if (field.type === 'password') {
                            field.type = 'text';
                            icon.classList.remove('fa-solid', 'fa-eye');
                            icon.classList.add('fa-solid', 'fa-eye-slash');
                        } else {
                            field.type = 'password';
                            icon.classList.remove('fa-solid', 'fa-eye-slash');
                            icon.classList.add('fa-solid', 'fa-eye');
                        }
                    }
                    </script>
                <?php endif; ?>
                
                <?php if (!$validToken && !$success && !$error): ?>
                    <div class="text-center">
                        <p class="mb-4 text-gray-600">Invalid or missing reset token.</p>
                        <a href="forgot-password.php" class="text-blue-500 hover:text-blue-700">Request a password reset</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'components/footer.php'; ?>
</body>
</html>
