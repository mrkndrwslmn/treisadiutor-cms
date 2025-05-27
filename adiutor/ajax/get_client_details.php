<?php
// Prevent any output before JSON
ini_set('display_errors', 0);
error_reporting(0);
session_start();
require_once '../../includes/db.php';

header('Content-Type: application/json');

// If user is not logged in, return JSON error
if (!isset($_SESSION['user'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Not logged in'
    ]);
    exit;
}

$client_id = $_GET['client_id'] ?? null;
$adiutor_id = $_GET['adiutor_id'] ?? null;

if (!$client_id || !$adiutor_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid parameters.'
    ]);
    exit;
}

// Check DB connection
if (!$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error.'
    ]);
    exit;
}

// Fetch client details
$client_query = "SELECT * FROM users WHERE userID = ?";
$client_stmt = $conn->prepare($client_query);
if (!$client_stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to prepare client query.'
    ]);
    exit;
}
$client_stmt->bind_param("i", $client_id);
$client_stmt->execute();
$client_result = $client_stmt->get_result();

if ($client_result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Client not found.'
    ]);
    exit;
}

$client = $client_result->fetch_assoc();

// Fetch client statistics
$rating_query = "SELECT AVG(rating) as avg_rating FROM feedbacks WHERE receiver_id = ? AND giver_id = ?";
$rating_stmt = $conn->prepare($rating_query);
if (!$rating_stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to prepare rating query.'
    ]);
    exit;
}
$rating_stmt->bind_param("ii", $adiutor_id, $client_id);
$rating_stmt->execute();
$rating_result = $rating_stmt->get_result();
$rating_data = $rating_result->fetch_assoc();

// Fetch all tasks (names) between this client and adiutor
$tasks_query = "SELECT t.title FROM tasks t 
                JOIN forms f ON t.formID = f.formID 
                WHERE f.userID = ? AND t.assignedTo = ?";
$tasks_stmt = $conn->prepare($tasks_query);
if (!$tasks_stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to prepare tasks query.'
    ]);
    exit;
}
$tasks_stmt->bind_param("ii", $client_id, $adiutor_id);
$tasks_stmt->execute();
$tasks_result = $tasks_stmt->get_result();
$task_names = [];
while ($row = $tasks_result->fetch_assoc()) {
    $task_names[] = htmlspecialchars($row['title']);
}

// Fetch most recent feedback message
$feedback_query = "SELECT comment, rating, created_at FROM feedbacks WHERE receiver_id = ? AND giver_id = ? ORDER BY created_at DESC LIMIT 1";
$feedback_stmt = $conn->prepare($feedback_query);
if (!$feedback_stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to prepare feedback query.'
    ]);
    exit;
}
$feedback_stmt->bind_param("ii", $adiutor_id, $client_id);
$feedback_stmt->execute();
$feedback_result = $feedback_stmt->get_result();
$recent_feedback = $feedback_result->fetch_assoc();

// Generate HTML for client details
$detailsHtml = '<div class="">';
$detailsHtml .= '<div class="flex items-center space-x-4 mb-4">';
$detailsHtml .= '<div class="flex-shrink-0 bg-primary text-white rounded-full h-12 w-12 flex items-center justify-center text-xl font-bold">';
$detailsHtml .= strtoupper(substr($client['fullName'], 0, 1));
$detailsHtml .= '</div>';
$detailsHtml .= '<div>';
$detailsHtml .= '<h3 class="text-xl font-semibold text-primary-700">' . htmlspecialchars($client['fullName']) . '</h3>';
$detailsHtml .= '<p class="text-gray-500 text-sm">' . htmlspecialchars($client['email']) . '</p>';
$detailsHtml .= '</div>';
$detailsHtml .= '</div>';

$detailsHtml .= '<div class="mb-2 flex items-center text-gray-700">';
$detailsHtml .= '<svg class="w-5 h-5 mr-2 text-primary-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H5a2 2 0 01-2-2V5zm0 12a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-2zm12-12a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zm0 12a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>';
$detailsHtml .= '<span>Phone: ' . (!empty($client['phone']) ? htmlspecialchars($client['phone']) : 'N/A') . '</span>';
$detailsHtml .= '</div>';

// Show all task names
$detailsHtml .= '<div class="mb-4">';
$detailsHtml .= '<div class="font-semibold text-gray-800 mb-1">Tasks</div>';
if (count($task_names) > 0) {
    $detailsHtml .= '<ul class="list-disc pl-6 text-gray-700 space-y-1">';
    foreach ($task_names as $task_name) {
        $detailsHtml .= '<li>' . $task_name . '</li>';
    }
    $detailsHtml .= '</ul>';
} else {
    $detailsHtml .= '<div class="text-gray-400">None</div>';
}
$detailsHtml .= '</div>';

// Show average rating
$detailsHtml .= '<div class="flex items-center mb-4">';
$detailsHtml .= '<span class="font-semibold text-gray-800 mr-2">Average Rating:</span>';
if ($rating_data['avg_rating']) {
    $avg = round($rating_data['avg_rating'], 1);
    $detailsHtml .= '<span class="text-yellow-500 font-bold">' . $avg . ' ';
    for ($i = 1; $i <= 5; $i++) {
        $detailsHtml .= $i <= $avg ? '★' : '☆';
    }
    $detailsHtml .= '</span>';
} else {
    $detailsHtml .= '<span class="text-gray-400">N/A</span>';
}
$detailsHtml .= '</div>';

// Show most recent feedback message
$detailsHtml .= '<div class="bg-gray-50 rounded-lg p-3">';
$detailsHtml .= '<div class="font-semibold text-gray-800 mb-1">Most Recent Feedback</div>';
if ($recent_feedback && !empty($recent_feedback['comment'])) {
    $detailsHtml .= '<div class="italic text-gray-700 mb-1">"' . htmlspecialchars($recent_feedback['comment']) . '"</div>';
    $detailsHtml .= '<div class="text-xs text-gray-500 flex items-center">';
    $detailsHtml .= '<span class="mr-2">Rating: <span class="text-yellow-500 font-bold">' . htmlspecialchars($recent_feedback['rating']) . '</span></span>';
    $detailsHtml .= '<span>' . htmlspecialchars(date('M d, Y H:i', strtotime($recent_feedback['created_at']))) . '</span>';
    $detailsHtml .= '</div>';
} else {
    $detailsHtml .= '<div class="text-gray-400">None</div>';
}
$detailsHtml .= '</div>';

$detailsHtml .= '</div>';

echo json_encode([
    'success' => true,
    'html' => $detailsHtml
]);
exit;