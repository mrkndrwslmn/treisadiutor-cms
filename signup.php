<?php
session_start();

include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = $_POST['name'];
    $email = $_POST['email'];
    $phone = preg_replace('/\s+/', '', $_POST['phone']);
    $password = $_POST['password'];

    if (!preg_match('/^\+63\d{10}$/', $phone)) {
        $alertType = 'error';
        $alertTitle = 'Error!';
        $alertMessage = 'Please enter a valid phone number starting with +63 followed by 10 digits.';
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (fullName, email, password, role, phoneNumber, status)
                VALUES (?, ?, ?, 'client', ?, 'active')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $name, $email, $hashedPassword, $phone);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $alertType = 'success';
            $alertTitle = 'Success!';
            $alertMessage = 'User created successfully.';
        } else {
            $alertType = 'error';
            $alertTitle = 'Error!';
            $alertMessage = 'Something went wrong.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Adiutor Task Management System</title>
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
<body class="bg-gray-50 min-h-screen flex flex-col">
    <?php include 'components/navbar.php'; ?>

    <section class="flex-grow flex items-center justify-center p-4 lg:p-8 mt-24">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden w-full max-w-6xl flex flex-col md:flex-row">
            
        <!-- Left side (image/features) -->
            <div class="bg-primary text-white p-8 md:w-1/2">
                <div class="h-full flex flex-col justify-center">
                    <h2 class="text-3xl font-bold mb-4">Create an Account</h2>
                    <p class="text-lg mb-8 text-gray-100">Create your account to start managing your tasks and projects.</p>
                    
                    <div class="space-y-6">
                        <div class="flex items-center space-x-4">
                            <div class="text-accent text-xl w-8">
                                <i class="fas fa-project-diagram"></i>
                            </div>
                            <span class="text-gray-100">Access your projects and tasks</span>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="text-accent text-xl w-8">
                                <i class="fas fa-users"></i>
                            </div>
                            <span class="text-gray-100">Update your project details and requirements</span>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="text-accent text-xl w-8">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <span class="text-gray-100">Track your project's progress</span>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="text-accent text-xl w-8">
                                <i class="fas fa-bell"></i>
                            </div>
                            <span class="text-gray-100">Stay updated with notifications</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right side (login form) -->
            <div class="p-8 md:w-1/2">
                <h2 class="text-3xl font-bold text-primary mb-8 text-center">Sign Up</h2>
                
                <form action="signup.php" method="POST" class="space-y-6">
                    
                    <div class="space-y-2">
                        <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            placeholder="Your Name" 
                            required
                            value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                        >

                    <div class="space-y-2">
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="example@treisadiutor.com" 
                            required
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                        >
                    </div>

                    <div class="space-y-2">
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            placeholder="+63 (917) 123-4567" 
                            required
                            oninput="formatPhoneNumber(event)"
                            value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                        >
                    </div>
                    
                    <div class="space-y-2">
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                placeholder="••••••••" 
                                required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                            >
                            <span onclick="togglePassword('password')" class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer">
                                <i id="password-toggle-icon" class="fas fa-eye text-gray-500"></i>
                            </span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                placeholder="••••••••" 
                                required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                            >
                            <span onclick="togglePassword('confirm_password')" class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer">
                                <i id="confirm_password-toggle-icon" class="fas fa-eye text-gray-500"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between">                        
                        <div>
                            <a href="forgot-password.html" class="text-sm text-secondary hover:text-primary font-medium">Forgot password?</a>
                        </div>
                    </div>
                    
                    <button 
                        type="submit" 
                        class="w-full bg-accent hover:bg-orange-500 text-white font-bold py-3 px-4 rounded-lg transition duration-300"
                    >
                        Sign Up
                    </button>
                    
                    <div class="text-center mt-6">
                        <p class="text-gray-600">
                            Already have an account? 
                            <a href="login.php" class="text-secondary hover:text-primary font-medium">Login here</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </section>
    
    <?php include 'components/footer.php'; ?>

    <?php if (isset($alertType)): ?>
    <script>
        Swal.fire({
            icon: '<?php echo $alertType; ?>',
            title: '<?php echo $alertTitle; ?>',
            text: '<?php echo $alertMessage; ?>'
        });
    </script>
    <?php endif; ?>

    <script>
    function togglePassword(fieldId) {
        var input = document.getElementById(fieldId);
        input.type = (input.type === "password") ? "text" : "password";
        var icon = document.getElementById(fieldId + '-toggle-icon');
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    }

    function formatPhoneNumber(event) {
        const input = event.target;
        let newValue = input.value.replace(/[^\d+]/g, '').replace(/\s+/g, '');
        if (!newValue.startsWith('+63')) {
            newValue = '+63' + newValue.replace('+63', '');
        }
        const raw = newValue.replace('+63', '');
        const part1 = raw.substring(0, 3);
        const part2 = raw.substring(3, 6);
        const part3 = raw.substring(6, 10);
        let formatted = '+63';
        if (part1) formatted += ' ' + part1;
        if (part2) formatted += ' ' + part2;
        if (part3) formatted += ' ' + part3;
        input.value = formatted;
    }
    </script>
</body>
</html>