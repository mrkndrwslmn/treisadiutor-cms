<?php
session_start();
include 'includes/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Success! - Adiutor Task Management System</title>
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

    <section class="flex-grow flex items-center justify-center p-4 lg:p-8 pt-24">
        <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-2xl text-center">
            <h2 class="text-3xl font-bold text-primary mb-4">Success!</h2>
            <p class="text-gray-700 mb-6">Your operation was successful. You will be redirected in <span id="countdown">5</span> seconds.</p>
        </div>
    </section>
    <?php include 'components/footer.php'; ?>
    <script>
        let countdown = 5;
        const intervalId = setInterval(() => {
            countdown--;
            document.getElementById('countdown').textContent = countdown;
            if(countdown <= 0) {
                clearInterval(intervalId);
                window.location.href = 'login.php';
            }
        }, 1000);
    </script>
</body>
</html>
