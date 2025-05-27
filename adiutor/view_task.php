<?php
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

// Fetch attached documents if formID exists
$documents = [];
if (!empty($task['formID'])) {
    $formID = intval($task['formID']);
    $doc_stmt = $conn->prepare("SELECT filepath FROM form_files WHERE formID = ?");
    $doc_stmt->bind_param("i", $formID);
    $doc_stmt->execute();
    $doc_result = $doc_stmt->get_result();
    while ($row = $doc_result->fetch_assoc()) {
        $documents[] = $row['filepath'];
    }
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
                                <div class="transform hover:translate-x-1 transition-transform duration-200">
                                    <p class="text-sm text-gray-500 mb-1">Priority</p>
                                    <p class="font-medium flex items-center">
                                        <?php 
                                        $priority = strtolower($task['priority']);
                                        if ($priority == 'high') {
                                            echo '<span class="text-red-500 mr-2"><i class="fas fa-exclamation-circle"></i></span>';
                                        } elseif ($priority == 'medium') {
                                            echo '<span class="text-yellow-500 mr-2"><i class="fas fa-exclamation"></i></span>';
                                        } else {
                                            echo '<span class="text-blue-500 mr-2"><i class="fas fa-info-circle"></i></span>';
                                        }
                                        echo ucwords(htmlspecialchars($task['priority'])); 
                                        ?>
                                    </p>
                                </div>
                                <div class="transform hover:translate-x-1 transition-transform duration-200">
                                    <p class="text-sm text-gray-500 mb-1">Adiutor</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($task['assignedTo']); ?></p>
                                </div>
                            </div>
                        </div>

                        <?php
                        // Check if documents exist for this task
                        $doc_query = "SELECT COUNT(*) as doc_count FROM documents WHERE taskID = ?";
                        $doc_stmt = $conn->prepare($doc_query);
                        $doc_stmt->bind_param("i", $taskid);
                        $doc_stmt->execute();
                        $doc_result = $doc_stmt->get_result();
                        $doc_count = $doc_result->fetch_assoc()['doc_count'];
                        ?>

                        <?php if (strtolower($task['status']) === 'in progress' || strtolower($task['status']) === 'pending'): ?>
                            <div class="mb-6">
                                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                                    <i class="fas fa-sync-alt mr-2"></i> Update Status
                                </h3>
                                <form method="POST" action="tasks.php">
                                    <input type="hidden" name="task_id" value="<?php echo $task['taskID']; ?>">
                                    <select name="status" class="w-full p-3 rounded-lg border-2 border-primary/50 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 mb-4">
                                        <option value="In Progress" <?php if ($task['status'] === 'In Progress') echo 'selected'; ?>>In Progress</option>
                                        <option value="Pending" <?php if ($task['status'] === 'Pending') echo 'selected'; ?>>Pending</option>
                                        <option value="Completed" <?php if ($task['status'] === 'Completed') echo 'selected'; ?>>Completed</option>
                                    </select>
                                    <?php if ($doc_count == 0): ?>
                                        <div class="text-sm text-red-600 mb-4">
                                            <i class="fas fa-exclamation-circle mr-1"></i> You must attach documents, photos, or links for this task before marking it as completed.
                                            <a href="upload_documents.php?id=<?php echo $task['taskID']; ?>" class="font-medium text-red-800 underline">Upload Documents</a>
                                        </div>
                                    <?php endif; ?>
                                    <button type="submit" name="update_task" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg transition-colors duration-200"
                                        <?php if ($doc_count == 0 && strtolower($task['status']) === 'completed') echo 'disabled'; ?>>
                                        Update Status
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>

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

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-primary">
                    <i class="fas fa-clipboard-list mr-2"></i>Additional Details
                </h2>
            </div>
            <div class="p-6">
                <?php if ($form): ?>
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">
                            <i class="fas fa-bullseye mr-1"></i> Expectations
                        </h3>
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <p class="whitespace-pre-line"><?php echo nl2br(htmlspecialchars($form['expectations'])); ?></p>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">
                            <i class="fas fa-sticky-note mr-1"></i> Additional Notes
                        </h3>
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <p class="whitespace-pre-line"><?php echo nl2br(htmlspecialchars($form['additional_notes'])); ?></p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-folder-open text-4xl mb-3"></i>
                        <p>No form details found for this task.</p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($documents)): ?>
                    <div class="mt-6">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                            <i class="fas fa-paperclip mr-1"></i> Attached Documents
                        </h3>
                        <ul class="list-disc list-inside bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <?php foreach ($documents as $filepath): ?>
                                <li>
                                    <a href="<?php echo htmlspecialchars($filepath); ?>" target="_blank" class="text-primary hover:underline">
                                        <?php echo basename($filepath); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-folder-open text-4xl mb-3"></i>
                        <p>No attached documents found for this task.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>
</body>
</html>
