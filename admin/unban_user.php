<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_POST['userID'])) {
    echo json_encode(['success' => false, 'message' => 'Missing userID']);
    exit;
}

include '../includes/db.php';

$userID = intval($_POST['userID']);
$stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE userID = ?");
$stmt->bind_param('i', $userID);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
$stmt->close();
$conn->close();
