<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo "You must be logged in to give feedback.";
    exit;
}

$user = $_SESSION['user'];
$giver_id = $user['userID'];

// Get task ID from GET
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "No task ID provided.";
    exit;
}
$task_id = intval($_GET['id']);

// Fetch task to get receiver_id (assumes tasks table has assignedTo or similar)
$task_stmt = $conn->prepare("SELECT * FROM tasks WHERE taskID = ?");
$task_stmt->bind_param("i", $task_id);
$task_stmt->execute();
$task_result = $task_stmt->get_result();
if ($task_result->num_rows === 0) {
    echo "Task not found.";
    exit;
}
$task = $task_result->fetch_assoc();
$receiver_id = isset($task['assignedTo']) ? $task['assignedTo'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    // Basic validation
    if ($rating < 1 || $rating > 5) {
        $error = "Rating must be between 1 and 5.";
    } elseif (empty($comment)) {
        $error = "Comment cannot be empty.";
    } elseif (!$receiver_id) {
        $error = "No receiver found for this task.";
    } else {
        // Insert feedback
        $stmt = $conn->prepare("INSERT INTO feedbacks (task_id, giver_id, receiver_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiiis", $task_id, $giver_id, $receiver_id, $rating, $comment);
        if ($stmt->execute()) {
            $success = "Feedback submitted successfully!";
        } else {
            $error = "Failed to submit feedback.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Give Feedback</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.12.0/cdn.min.js" defer></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3a5a78',
                        secondary: '#6c9ab5',
                        accent: '#f9a826',
                        dark: '#1a2a3a',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <?php include 'components/navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8 flex-grow flex items-center justify-center">
        <div class="w-full max-w-lg" x-data="{ 
            rating: '',
            comment: '',
            hoverRating: 0,
            
            setRating(val) {
                this.rating = val;
            }
        }">
            <!-- Card Container -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden transform transition-all duration-300 hover:shadow-xl">
                <!-- Header -->
                <div class="bg-gradient-to-r from-primary to-secondary p-6 text-white relative">
                    <div class="absolute top-0 right-0 -mt-2 -mr-2 bg-accent text-dark font-bold rounded-full h-12 w-12 flex items-center justify-center shadow-lg">
                        #<?php echo htmlspecialchars($task_id); ?>
                    </div>
                    <h1 class="text-3xl font-bold">Task Feedback</h1>
                    <p class="text-blue-100 mt-1">Help us improve with your valuable input</p>
                </div>
                
                <!-- Messages -->
                <?php if (!empty($error)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 m-4 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    </div>
                <?php elseif (!empty($success)): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 m-4 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm"><?php echo htmlspecialchars($success); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Form -->
                <form method="post" class="p-6">
                    <div class="mb-6">
                        <label class="block text-gray-700 font-semibold mb-3">Rating</label>
                        
                        <!-- Star Rating -->
                        <div class="flex items-center justify-center space-x-1 mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <button 
                                    type="button"
                                    @click="setRating(<?php echo $i; ?>)"
                                    @mouseover="hoverRating = <?php echo $i; ?>"
                                    @mouseleave="hoverRating = 0"
                                    class="focus:outline-none transition-transform transform hover:scale-110"
                                >
                                    <svg 
                                        class="w-10 h-10" 
                                        :class="{
                                            'text-yellow-400': hoverRating >= <?php echo $i; ?> || rating >= <?php echo $i; ?>,
                                            'text-gray-300': hoverRating < <?php echo $i; ?> && rating < <?php echo $i; ?>
                                        }"
                                        fill="currentColor" 
                                        viewBox="0 0 20 20"
                                    >
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                </button>
                            <?php endfor; ?>
                        </div>
                        
                        <!-- Current Selection Text -->
                        <div class="text-center text-sm text-gray-500 h-5 mb-2">
                            <template x-if="rating > 0">
                                <span x-text="`You selected ${rating} star${rating > 1 ? 's' : ''}`"></span>
                            </template>
                        </div>
                        
                        <!-- Hidden input for form submission -->
                        <input type="hidden" name="rating" x-model="rating" required>
                    </div>
                    
                    <div class="mb-6">
                        <label for="comment" class="block text-gray-700 font-semibold mb-2">Comment</label>
                        <div class="relative">
                            <textarea 
                                id="comment"
                                name="comment" 
                                x-model="comment"
                                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200" 
                                rows="4" 
                                placeholder="Please share your thoughts on this task..." 
                                required
                            ></textarea>
                            <div class="absolute bottom-2 right-2 text-xs text-gray-500" x-text="`${comment.length} characters`"></div>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <button 
                            type="submit" 
                            class="bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-secondary transition-all duration-200 transform hover:scale-105 flex items-center"
                            :class="{ 'opacity-50 cursor-not-allowed': !rating || !comment.trim() }"
                            :disabled="!rating || !comment.trim()"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Submit Feedback
                        </button>
                        
                        <span class="text-sm text-gray-500">
                            <template x-if="!rating && !comment.trim()">
                                <span>Please complete the form</span>
                            </template>
                            <template x-if="rating && comment.trim()">
                                <span class="text-green-600">Ready to submit!</span>
                            </template>
                        </span>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'components/footer.php'; ?>
</body>
</html>