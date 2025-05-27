<?php
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

// Redirect based on user role
if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'client':
            header("Location: ../client/dashboard.php");
            break;
        case 'adiutor':
            header("Location: ../adiutor/dashboard.php");
            break;
        case 'admin':
            header("Location: ../admin/dashboard.php");
            break;
        default:
            // Handle unknown roles
            header("Location: ../login.php");
            break;
    }
    exit;
}
