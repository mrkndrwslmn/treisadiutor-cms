<?php
$servername = "127.0.0.1";  
$username = "root"; 
$password = ""; 
$database = "ta_cms"; 

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    $conn = mysqli_connect($servername, $username, $password, $database);
    
    if (!$conn) {
        $servername = "localhost";
        $conn = mysqli_connect($servername, $username, $password, $database);
    }
    
    mysqli_set_charset($conn, 'utf8mb4');

    if (!$conn) {  
        throw new Exception(mysqli_connect_error());
    }

} catch (Exception $e) {
    $error_message = $e->getMessage();
    error_log("Database connection error: " . $error_message);

    if (!headers_sent()) {
        header("Location: /INFOSYSTEM/error.php?message=" . urlencode($error_message));
    } else {
        echo "Database connection failed: " . htmlspecialchars($error_message);
    }
    exit();
}