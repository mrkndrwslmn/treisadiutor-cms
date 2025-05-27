<?php
session_start();
include 'includes/db.php';

if (isset($_SESSION['user']) && $_SESSION['user']['role']) {
    switch ($_SESSION['user']['role']) {
        case 'client':
            header("Location: /TA-CMS/client/dashboard.php");
            exit;
        case 'admin':
            header("Location: /TA-CMS/admin/dashboard.php");
            exit;
        case 'adiutor':
            header("Location: /TA-CMS/adiutor/dashboard.php");
            exit;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = $_POST['email'];
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['status'] !== 'active') {
            $alertType = 'error';
            $alertTitle = 'Error!';
            $alertMessage = 'Your account has been suspended. Contact the administrator for more details.';
        } elseif (password_verify($password, $row['password'])) {
            // Successful login
            $_SESSION['user'] = $row;
            if ($row['role'] === 'client') {
                header("Location: client/dashboard.php");
                exit;
            } elseif ($row['role'] === 'adiutor') {
                header("Location: adiutor/dashboard.php");
                exit;
            } elseif ($row['role'] === 'admin') {
                header("Location: admin/dashboard.php");
                exit;
            }
        } else {
            $alertType = 'error';
            $alertTitle = 'Error!';
            $alertMessage = 'Invalid password.';
        }
    } else {
        $alertType = 'error';
        $alertTitle = 'Error!';
        $alertMessage = 'User not found.';
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
                    <h2 class="text-3xl font-bold mb-4">Welcome Back</h2>
                    <p class="text-lg mb-8 text-gray-100">Log in to your account to see and manage your tasks and projects.</p>
                    
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
                <h2 class="text-3xl font-bold text-primary mb-8 text-center">Sign In</h2>
                
                <form action="login.php" method="POST">
                    <div class="space-y-2">
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="example@treisadiutor.com" 
                            required
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
                            <button 
                                type="button" 
                                class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500"
                                onclick="togglePassword()"
                            >
                                <i id="toggleIcon" class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between mb-4">                        
                        <div>
                            <a href="forgot-password.php" class="text-sm text-secondary hover:text-primary font-medium">Forgot password?</a>
                        </div>
                    </div>
                    
                    <button 
                        type="submit" 
                        class="w-full bg-accent hover:bg-orange-500 text-white font-bold py-3 px-4 rounded-lg transition duration-300"
                    >
                        Sign In
                    </button>
                    
                    <div class="text-center mt-6">
                        <p class="text-gray-600">
                            Don't have an account? 
                            <a href="signup.php" class="text-secondary hover:text-primary font-medium">Sign up</a>
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
    function togglePassword() {
        const pwd = document.getElementById('password');
        const icon = document.getElementById('toggleIcon');
        if (pwd.type === 'password') {
            pwd.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            pwd.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    </script>
</body>
</html>