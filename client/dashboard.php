<?php
session_start();
include '../auth/auth.php';
include '../includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'client') {
    header("Location: /TA-CMS/login.php");
    exit;
}

// Fetch the latest user data
$userID = $_SESSION['user']['userID'];
$userQuery = $conn->prepare("SELECT * FROM users WHERE userID = ?");
$userQuery->bind_param("i", $userID);
$userQuery->execute();
$latestUserData = $userQuery->get_result()->fetch_assoc();
$userQuery->close();

// Update session with the latest user data
if ($latestUserData) {
    $_SESSION['user'] = $latestUserData;
    $user = $latestUserData;
}

$clientID = $user['userID'];


// Fetch data for the dashboard
$formResult = $conn->query("SELECT formID FROM forms WHERE userID = $clientID ORDER BY submitted_at DESC LIMIT 5");

$formIDs = [];
if ($formResult) {
    while ($row = $formResult->fetch_assoc()) {
        $formIDs[] = $row['formID'];
    }
}

if (!empty($formIDs)) {
    $idsString = implode(',', $formIDs); // "1,3,5,7"

    // Assign tasks result to $tasks for use in HTML
    $tasks = $conn->query("SELECT * FROM tasks WHERE formID IN ($idsString) ORDER BY dueDate ASC");
} else {
    // If no forms, set $tasks to false for consistency
    $tasks = false;
    echo "No forms found.";
}

$clientID = $user['userID'];

$documentsQuery = "
    SELECT d.*
    FROM documents d
    JOIN tasks t ON d.taskID = t.taskID
    JOIN forms f ON t.formID = f.formID
    WHERE f.userID = ?
    ORDER BY d.uploadDate DESC
    LIMIT 5
";

$docStmt = $conn->prepare($documentsQuery);
$docStmt->bind_param("i", $clientID);
$docStmt->execute();
$documentsResult = $docStmt->get_result();

$documents = [];
while ($row = $documentsResult->fetch_assoc()) {
    $documents[] = $row;
}

$docStmt->close();

$completedTasks = $conn->query("SELECT COUNT(*) AS completed FROM tasks WHERE assignedTo = $clientID AND status = 'completed'")->fetch_assoc()['completed'];
$totalTasks = $conn->query("SELECT COUNT(*) AS total FROM tasks WHERE assignedTo = $clientID")->fetch_assoc()['total'];
$progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Client</title>
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
<body class="bg-gray-50 min-h-screen flex flex-col">
    <?php include 'components/navbar.php'; ?>

    <section class="container mt-12 mx-auto p-6 pt-24">
        <!-- Welcome Section -->
        <div class="mb-8 mt-4">
            <h1 class="text-3xl font-bold text-primary">Hi, <?php echo htmlspecialchars($user['fullName']); ?>!</h1>        
        </div>

        <!-- My Tasks -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4 max-w-2xl">
                <h2 class="text-2xl font-bold text-primary">My Tasks</h2>
                
                <a href="../get-started.php" class="inline-flex items-center gap-2 bg-accent text-white px-5 py-2.5 rounded-lg shadow hover:bg-accent-dark transition-colors font-semibold text-base">
                    <i class="fa-solid fa-plus"></i> New Request
                </a>
            </div>

            <?php if ($tasks && $tasks->num_rows > 0): ?>
            <div class="grid gap-4 md:grid-cols-2">
                <?php while ($task = $tasks->fetch_assoc()): ?>
                <div class="bg-white rounded-xl shadow-md p-6 flex flex-col md:flex-row md:items-center justify-between hover:shadow-lg transition-shadow border border-gray-100">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-lg font-semibold text-primary"><?php echo htmlspecialchars($task['title']); ?></span>
                            <?php
                                $statusColor = match($task['status']) {
                                    'completed' => 'bg-green-100 text-green-700',
                                    'in progress' => 'bg-yellow-100 text-yellow-700',
                                    'pending' => 'bg-gray-100 text-gray-700',
                                    default => 'bg-gray-100 text-gray-700'
                                };
                            ?>
                            <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium <?php echo $statusColor; ?>">
                                <?php echo htmlspecialchars(ucfirst($task['status'])); ?>
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                            <div>
                                <i class="fa-regular fa-calendar mr-1 text-accent"></i>
                                <span class="font-medium">Due:</span>
                                <?php echo htmlspecialchars($task['dueDate']); ?>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 md:mt-0 md:ml-6 flex-shrink-0">
                        <a href="view-task.php?id=<?php echo $task['taskID']; ?>"
                           class="inline-flex items-center gap-2 bg-accent text-white px-4 py-2 rounded-lg shadow hover:bg-accent-dark transition-colors font-semibold text-sm">
                            <i class="fa-regular fa-eye"></i> View
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="bg-white rounded-xl shadow-md p-8 flex flex-col items-center justify-center">
                <p class="text-gray-600 text-lg mb-4 flex items-center gap-2">
                    <i class="fa-regular fa-face-smile text-accent text-2xl"></i>
                    No tasks available. Get started by filling out your first task!
                </p>
                <a href="../get-started.php" class="inline-flex items-center gap-2 bg-accent text-white px-5 py-2.5 rounded-lg shadow hover:bg-accent-dark transition-colors font-semibold text-base">
                    <i class="fa-solid fa-rocket"></i> Get Started
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- My Documents -->
        <?php
        // Group documents by taskID for card display
        $documentsByTask = [];
        foreach ($documents as $doc) {
            $taskID = $doc['taskID'];
            if (!isset($documentsByTask[$taskID])) {
                $documentsByTask[$taskID] = [];
            }
            $documentsByTask[$taskID][] = $doc;
        }

        // Fetch task titles and descriptions for each taskID
        $taskInfo = [];
        if (!empty($documentsByTask)) {
            $taskIDs = implode(',', array_map('intval', array_keys($documentsByTask)));
            $taskQuery = $conn->query("SELECT taskID, title, description FROM tasks WHERE taskID IN ($taskIDs)");
            while ($row = $taskQuery->fetch_assoc()) {
                $taskInfo[$row['taskID']] = [
                    'title' => $row['title'],
                    'description' => $row['description']
                ];
            }
        }
        ?>

        <?php if (count($documents) > 0): ?>
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-primary mb-4">My Documents</h2>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($documentsByTask as $taskID => $docs): ?>
                <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col border border-gray-100 hover:shadow-2xl transition-shadow">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-primary flex items-center gap-2">
                            <i class="fa-regular fa-folder-open text-accent"></i>
                            <?php echo htmlspecialchars($taskInfo[$taskID]['title'] ?? 'Untitled Task'); ?>
                        </h3>
                        <p class="text-gray-500 text-sm mt-1">
                            <?php echo htmlspecialchars($taskInfo[$taskID]['description'] ?? 'No description.'); ?>
                        </p>
                    </div>
                    <div class="flex-1">
                        <ul class="space-y-4">
                            <?php foreach ($docs as $doc): ?>
                            <li class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2 border border-gray-200">
                                <div>
                                    <div class="font-medium text-gray-800 flex items-center gap-2">
                                        <i class="fa-regular fa-file-lines text-accent"></i>
                                        <?php echo htmlspecialchars($doc['fileName']); ?>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <?php echo strtoupper(pathinfo($doc['fileName'], PATHINFO_EXTENSION)); ?> &middot;
                                        Uploaded: <?php echo htmlspecialchars(date('M d, Y', strtotime($doc['uploadDate']))); ?>
                                    </div>
                                </div>
                                <div>
                                    <a href="<?php echo htmlspecialchars($doc['filePath']); ?>"
                                       class="inline-flex items-center gap-1 bg-accent text-white px-3 py-1 rounded-md shadow hover:bg-accent-dark transition-colors text-xs font-semibold"
                                       download>
                                        <i class="fa-solid fa-download"></i> Download
                                    </a>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Progress Summary -->
        <?php if ($totalTasks > 0): ?>
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-primary mb-4">Progress Summary</h2>
            <div class="bg-white p-4 rounded-lg shadow-md">
                <p class="text-gray-600">Tasks Completed: <?php echo $completedTasks; ?> / <?php echo $totalTasks; ?></p>
                <div class="w-full bg-gray-200 rounded-full h-4 mt-2">
                    <div class="bg-accent h-4 rounded-full" style="width: <?php echo $progress; ?>%;"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </section>

    <?php include '../components/footer.php'; ?>

    <script>
        function openModal() {
            document.getElementById('getStartedModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('getStartedModal').classList.add('hidden');
        }
    </script>
</body>
</html>