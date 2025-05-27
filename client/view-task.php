<?php
session_start();
// Include your database connection
require_once '../includes/db.php'; // Adjust path as needed

// Check if taskid is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "No task ID provided.";
    exit;
}

$taskid = intval($_GET['id']);

// Prepare and execute query
$stmt = $conn->prepare("SELECT * FROM tasks WHERE taskID = ?");
$stmt->bind_param("i", $taskid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Task not found.";
    exit;
}

$task = $result->fetch_assoc();

$stmt = $conn->prepare("SELECT * FROM documents WHERE taskID = ?");
$stmt->bind_param("i", $taskid);
$stmt->execute();
$result = $stmt->get_result();

$documents = $result->fetch_assoc();

// Fetch form details if formID exists
$form = null;
if (!empty($task['formID'])) {
    $formID = intval($task['formID']);
    $form_stmt = $conn->prepare("SELECT * FROM forms WHERE formID = ?");
    $form_stmt->bind_param("i", $formID);
    $form_stmt->execute();
    $form_result = $form_stmt->get_result();
    if ($form_result->num_rows > 0) {
        $form = $form_result->fetch_assoc();
    }
}

// Check if feedback exists for this task
$feedback_exists = false;
$feedback_stmt = $conn->prepare("SELECT ID FROM feedbacks WHERE task_ID = ? LIMIT 1");
$feedback_stmt->bind_param("i", $taskid);
$feedback_stmt->execute();
$feedback_result = $feedback_stmt->get_result();
if ($feedback_result->num_rows > 0) {
    $feedback_exists = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Task</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
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
<body class="bg-gray-50 min-h-screen">
    <?php include 'components/navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8 max-w-5xl pt-24">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-primary flex items-center">
                <i class="fas fa-tasks mr-3"></i> Task Details
            </h1>
            <a href="tasks.php" class="bg-primary hover:bg-primary/90 text-white px-5 py-2.5 rounded-lg transition-all duration-300 flex items-center shadow-sm hover:shadow">
                <i class="fas fa-arrow-left mr-2"></i>Back to Tasks
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8 transition-all duration-300 hover:shadow-xl">
            <div class="p-8">
                <div class="flex flex-wrap items-center mb-6 border-b pb-4">
                    <h2 class="text-2xl font-semibold text-dark flex-grow"><?php echo htmlspecialchars($task['title']); ?></h2>
                    <span class="px-4 py-1.5 rounded-full text-sm font-medium 
                        <?php 
                        switch(strtolower($task['status'])) {
                            case 'completed': echo 'bg-green-100 text-green-800'; break;
                            case 'in progress': echo 'bg-blue-100 text-blue-800'; break;
                            case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                            default: echo 'bg-gray-100 text-gray-800';
                        }
                        ?>">
                        <?php echo ucwords(htmlspecialchars($task['status'])); ?>
                    </span>
                </div>

                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                        <i class="fas fa-align-left mr-2"></i> Description
                    </h3>
                    <div class="p-5 bg-gray-50 rounded-lg mb-4 border border-gray-100">
                        <p class="whitespace-pre-line leading-relaxed"><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-8">
                    <div>
                        <div class="mb-6">
                            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                                <i class="fas fa-info-circle mr-2"></i> Basic Information
                            </h3>
                            <div class="grid grid-cols-2 gap-5 bg-gray-50 p-5 rounded-lg border border-gray-100">
                                <div class="transform hover:translate-x-1 transition-transform duration-200">
                                    <p class="text-sm text-gray-500 mb-1">ID</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($task['taskID']); ?></p>
                                </div>
                                <div class="transform hover:translate-x-1 transition-transform duration-200">
                                    <p class="text-sm text-gray-500 mb-1">Task Type</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($task['taskType']); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                                <i class="far fa-calendar-alt mr-2"></i> Timeline
                            </h3>
                            <div class="grid grid-cols-2 gap-5 bg-gray-50 p-5 rounded-lg border border-gray-100">
                                <div class="transform hover:translate-x-1 transition-transform duration-200">
                                    <p class="text-sm text-gray-500 mb-1">Due Date</p>
                                    <p class="font-medium">
                                        <i class="far fa-calendar mr-1"></i>
                                        <?php echo htmlspecialchars($task['dueDate']); ?>
                                    </p>
                                </div>
                                <div class="transform hover:translate-x-1 transition-transform duration-200">
                                    <p class="text-sm text-gray-500 mb-1">Created At</p>
                                    <p class="font-medium">
                                        <i class="far fa-clock mr-1"></i>
                                        <?php echo htmlspecialchars($task['createdAt']); ?>
                                    </p>
                                </div>
                                <div class="col-span-2 transform hover:translate-x-1 transition-transform duration-200">
                                    <p class="text-sm text-gray-500 mb-1">Last Updated</p>
                                    <p class="font-medium">
                                        <i class="fas fa-sync-alt mr-1"></i>
                                        <?php echo htmlspecialchars($task['updatedAt']); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($task['status'] == 'completed'): ?>
            <div class="bg-green-50 border-l-4 border-green-400 text-green-700 p-4 mb-8">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <p class="font-medium">This task has been completed successfully!</p>
                </div>
                <?php if (!empty($documents['filePath'])): ?>
                    <div class="ml-4 mt-4">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">
                            <i class="fas fa-paperclip mr-1"></i> Attachments
                        </h3>
                        <ul class="list-disc pl-5">
                            <?php foreach (explode(',', $documents['filePath']) as $attachment): ?>
                                <li>
                                    <a href="<?php echo htmlspecialchars($attachment); ?>" target="_blank" class="text-blue-600 hover:underline">
                                        <?php echo htmlspecialchars(basename($attachment)); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <?php if (!$feedback_exists): ?>
                <div class="mt-6">
                    <a href="feedback.php?id=<?php echo urlencode($task['taskID']); ?>"
                       class="inline-block bg-accent hover:bg-primary text-white font-semibold px-6 py-2 rounded-lg shadow transition-all duration-200">
                        <i class="fas fa-comment-dots mr-2"></i>Give Feedback
                    </a>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

    <?php include 'components/footer.php'; ?>
</body>
</html>
