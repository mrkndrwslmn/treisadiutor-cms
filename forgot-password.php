<?php
session_start();
require_once 'includes/db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email exists in database
        $stmt = $conn->prepare("SELECT userID FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Generate unique token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Save token in database
            $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?) 
                                    ON DUPLICATE KEY UPDATE token = ?, expires_at = ?");
            $stmt->bind_param("sssss", $email, $token, $expires, $token, $expires);
            
            if ($stmt->execute()) {
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/TA-CMS/reset-password.php?token=" . $token;
                
                $to = $email;
                $subject = "Password Reset Request";
                $message = "Hello,\n\n";
                $message .= "You have requested to reset your password. Please click the link below to reset your password:\n\n";
                $message .= $reset_link . "\n\n";
                $message .= "This link will expire in 1 hour.\n\n";
                $message .= "If you did not request a password reset, please ignore this email.\n\n";
                $message .= "Regards,\nTreis Adiutor";
                
                $mail = new PHPMailer(true);
                
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'treisadiutor@gmail.com';
                    $mail->Password = 'lpnw vhys eeit ebmw';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;
                    
                    $mail->setFrom('noreply@treisadiutor.com', 'Treis Adiutor');
                    $mail->addAddress($email);
                    
                    $mail->Subject = $subject;
                    $mail->Body    = $message;
                    
                    $mail->send();
                    $success = "Password reset instructions have been sent to your email.";
                } catch (Exception $e) {
                    $error = "Failed to send the email. Please try again later.";
                    error_log("Mailer Error: " . $mail->ErrorInfo);
                }
            } else {
                $error = "An error occurred. Please try again later.";
            }
        } else {
            // Don't reveal that the email doesn't exist for security reasons
            $success = "If your email is registered, you will receive password reset instructions.";
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
    <title>Forgot Password - GCard</title>
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
<body class="flex flex-col min-h-screen bg-gray-100 font-sans">

    <?php include 'components/navbar.php'; ?>
    
    <div class="flex-grow flex items-center justify-center px-4 py-6">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-center mb-6">Forgot Password</h2>
                
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $success; ?></div>
                <?php else: ?>
                    <p class="mb-6 text-gray-600">Enter your email address below and we'll send you instructions to reset your password.</p>
                    
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="email" class="block text-gray-700 font-medium mb-2">Email Address</label>
                            <input type="email" id="email" name="email" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div class="mb-4">
                            <button type="submit" 
                                    class="w-full bg-accent hover:bg-orange-500 text-white font-medium py-2 px-4 rounded-md transition duration-200">
                                Send reset instructions
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <a href="login.php" class="text-secondary hover:text-primary">Back to Login</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'components/footer.php'; ?>
</body>
</html>

