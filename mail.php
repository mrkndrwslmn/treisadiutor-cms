<?php

function notifyUser($email, $subject, $message) {
    // ...existing code for sending email...
    // Example:
    $headers = "From: no-reply@yourdomain.com\r\n";
    mail($email, $subject, $message, $headers);
}

function notifyTaskAssignment($conn, $assignedTo, $formID) {
    // Fetch assigned user's email
    $stmt = $conn->prepare("SELECT email FROM users WHERE userID = ?");
    $stmt->bind_param("i", $assignedTo);
    $stmt->execute();
    $assignedEmail = $stmt->get_result()->fetch_assoc()['email'] ?? '';

    // Notify assigned user
    notifyUser($assignedEmail, "New Task Assigned", "A new task has been assigned to you. Please open and view.");

    // Fetch form's contact details
    $stmt = $conn->prepare("SELECT contact_details FROM forms WHERE formID = ?");
    $stmt->bind_param("i", $formID);
    $stmt->execute();
    $contactEmail = $stmt->get_result()->fetch_assoc()['contact_details'] ?? '';

    // Notify form's contact
    notifyUser($contactEmail, "Request Approved", "Your request has been approved and is now in progress.");
}
