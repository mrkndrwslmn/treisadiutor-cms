<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function notifyUser($to, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'treisadiutor@gmail.com'; // replace with your Gmail
        $mail->Password   = 'lpnw vhys eeit ebmw'; // use an App Password, not your Gmail password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom('treisadiutor@gmail.com', 'Treis Adiutor'); // replace as needed
        $mail->addAddress($to);

        //Content
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Optionally log error: $mail->ErrorInfo
        return false;
    }
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
