<?php

function getUserById($userId) {
    // Assuming a database connection is already established
    global $db;

    // Prepare and execute the query
    $stmt = $db->prepare("SELECT email, fullName FROM users WHERE userID = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch user details
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return null; // User not found
    }
}

function notifyUserOnStatusChange($userId, $formId, $newStatus) {
    // Fetch user details
    $user = getUserById($userId);
    $email = $user['email'];
    $fullName = $user['fullName'];

    // Prepare notification message
    $subject = "Status Update for Request #$formId";
    $message = "Hello $fullName,\n\nThe status of your request (ID: $formId) has been updated to: $newStatus.\n\nThank you.";

    // Send email notification
    mail($email, $subject, $message);
}

