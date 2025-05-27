<?php
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function notifyUser($email, $subject, $message) {
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
        $success = "Notification has been sent to your email.";
    } catch (Exception $e) {
        $error = "Failed to send the email. Please try again later.";
        error_log("Mailer Error: " . $mail->ErrorInfo);
    }

    $headers = "From: no-reply@treisadiutor.com\r\n";
    mail($email, $subject, $message, $headers);
}

function notifyTaskAssignment($conn, $assignedTo, $formID) {
    // Fetch assigned user's email
    $stmt = $conn->prepare("SELECT email FROM users WHERE userID = ?");
    $stmt->bind_param("i", $assignedTo);
    $stmt->execute();
    $assignedEmail = $stmt->get_result()->fetch_assoc()['email'] ?? '';

    // Notify assigned user
    notifyUser($assignedEmail, "New Task Assigned", "Great news! You’ve been assigned a new task. Kindly view the details over your dashboard and get started when you’re ready. If you have any questions or need clarification, feel free to reach out.\n\nLooking forward to your progress!");

    // Fetch form's contact details
    $stmt = $conn->prepare("SELECT contact_details FROM forms WHERE formID = ?");
    $stmt->bind_param("i", $formID);
    $stmt->execute();
    $contactEmail = $stmt->get_result()->fetch_assoc()['contact_details'] ?? '';

    // Notify form's contact
    notifyUser($contactEmail, "Request Approved", "Great news! \n\nYour request has been approved and is now officially in progress. Our team has started working on it, and we’ll keep you updated along the way.\n\nPlease wait awhile, we are on our way to contact you via your provided contact details the last time you filled out our form.\n\nIf you have any additional notes or concerns, feel free to reach out anytime!\n\nWarm Regards, Treis Adiutor");
}
