<?php
session_start();
include '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check current featured count
if (isset($_GET['check'])) {
    $query = "SELECT COUNT(*) as count FROM featuredfeedback WHERE isActive = 1";
    $result = $conn->query($query);
    $count = $result->fetch_assoc()['count'];
    echo json_encode(['count' => $count]);
    exit;
}

// Check if feedback is featured
if (isset($_GET['check_featured'])) {
    $feedbackId = (int)$_GET['check_featured'];
    $query = "SELECT id FROM featuredfeedback WHERE feedbackID = ? AND isActive = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $feedbackId);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode(['isFeatured' => $result->num_rows > 0]);
    exit;
}

// Remove from featured
if (isset($_GET['remove'])) {
    $feedbackId = (int)$_GET['remove'];
    
    try {
        $query = "UPDATE featuredfeedback SET isActive = 0 WHERE feedbackID = ? AND isActive = 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $feedbackId);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Feature new feedback
if (isset($_GET['id'])) {
    $feedbackId = (int)$_GET['id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check current count
        $query = "SELECT COUNT(*) as count FROM featuredfeedback WHERE isActive = 1";
        $result = $conn->query($query);
        $count = $result->fetch_assoc()['count'];
        
        // If 3 or more, deactivate oldest
        if ($count >= 3) {
            $query = "UPDATE featuredfeedback 
                     SET isActive = 0 
                     WHERE id = (
                         SELECT id 
                         FROM (
                             SELECT id 
                             FROM featuredfeedback 
                             WHERE isActive = 1 
                             ORDER BY dateFeatured ASC 
                             LIMIT 1
                         ) as sub
                     )";
            $conn->query($query);
        }
        
        // Insert new featured feedback
        $query = "INSERT INTO featuredfeedback (feedbackID, dateFeatured, isActive) 
                 VALUES (?, NOW(), 1)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $feedbackId);
        $stmt->execute();
        
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
