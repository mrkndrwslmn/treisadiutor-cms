<?php
session_start();
include '../includes/db.php';
include 'functions/mail.php';

$formID     = $_POST['formID'];
$taskTitle  = $_POST['taskTitle'];
$description= $_POST['description'];
$assignedTo = $_POST['assignedTo'];
$assignedBy = $_POST['assignedBy'];
$dueDate    = $_POST['dueDate'];
$dateAssigned = $_POST['dateAssigned'];
$priority   = $_POST['priority'];
$taskType   = $_POST['taskType'];

// Insert task into database
$query = "INSERT INTO tasks (formID, title, description, assignedTo, assignedBy, dueDate, dateAssigned, priority, taskType) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("issiissss", $formID, $taskTitle, $description, $assignedTo, $assignedBy, $dueDate, $dateAssigned, $priority, $taskType);
$stmt->execute();

// Fetch user email from users table
$fetchEmail = "SELECT email FROM users WHERE userID = ?";
$userStmt = $conn->prepare($fetchEmail);
$userStmt->bind_param("i", $assignedTo);
$userStmt->execute();
$emailResult = $userStmt->get_result()->fetch_assoc();
$assignedToEmail = $emailResult['email'] ?? '';

// Approve the request 
$approveQuery = "UPDATE forms SET status = 'approved' WHERE formID = ?";
$approveStmt = $conn->prepare($approveQuery);
$approveStmt->bind_param("i", $formID);
$approveStmt->execute();
$approveStmt->close();


// Fetch contact details from forms table
$fetchContact = "SELECT email 
                FROM users
                JOIN forms ON users.userID = forms.userID
                WHERE forms.formID = ?;
";

$formStmt = $conn->prepare($fetchContact);
$formStmt->bind_param("i", $formID);
$formStmt->execute();
$contactResult = $formStmt->get_result()->fetch_assoc();
$clientEmail = $contactResult['email'] ?? '';

// Send email
notifyUser($assignedToEmail, "New Task Assigned", "Great news, Adiutor!\n\nYou’ve been assigned a new task. Kindly view the details over your dashboard and get started whenever you’re ready.\n\nIf you have any questions or need clarification, feel free to reach out.\n\nLooking forward to your progress!");
notifyUser($clientEmail, "Request Approved", "Great news! \n\nYour request has been approved and is now officially in progress. Our team has started working on it, and we’ll keep you updated along the way.\n\nPlease standby and keep posted as we are on our way to contact you via your provided contact details the last time you filled out our form.\n\nIf you have any additional notes or concerns, feel free to reach out anytime!\n\nRegards, \nTreis Adiutor");

// Redirect or handle success
header("Location: view_requests.php?id=$formID");
exit;