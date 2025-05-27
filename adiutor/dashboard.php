<?php
session_start();
include '../auth/auth.php';
include '../includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'adiutor') {
    header("Location: ../login.php");
    exit;
}

// Fetch the latest user data
$userID = $_SESSION['user']['userID'];
$userQuery = $conn->prepare("SELECT * FROM users WHERE userID = ?");
$userQuery->bind_param("i", $userID);
$userQuery->execute();
$latestUserData = $userQuery->get_result()->fetch_assoc();
$userQuery->close();

$user = $_SESSION['user'];
$adiutorId = $user['userID'];


// Get stats for quick overview
$totalAssignedTasks = getCountFromDB("SELECT COUNT(*) FROM tasks WHERE assignedTo = $adiutorId");
$completedTasks = getCountFromDB("SELECT COUNT(*) FROM tasks WHERE assignedTo = $adiutorId AND status = 'completed'");
$pendingTasks = getCountFromDB("SELECT COUNT(*) FROM tasks WHERE assignedTo = $adiutorId AND status = 'pending'");
$totalClients = getCountFromDB("SELECT COUNT(DISTINCT forms.userID) AS totalClients
    FROM forms
    JOIN tasks ON forms.formID = tasks.formID
    WHERE tasks.assignedTo = $adiutorId");

// Get upcoming deadlines
$upcomingDeadlines = getUpcomingDeadlines($adiutorId, 5); // Get 5 upcoming deadlines

// Get task completion rate
$completionRate = ($totalAssignedTasks > 0) ? round(($completedTasks / $totalAssignedTasks) * 100) : 0;

// Get average feedback rating
$avgRating = getAverageRating($adiutorId);


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adiutor Dashboard - Treis Adiutor</title>
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
<body class="bg-gray-100 min-h-screen flex flex-col">
<?php include 'components/navbar.php'; ?>

<!-- Dashboard Content -->
<main class="flex-grow container mx-auto px-4 py-8 pt-24">
    <h1 class="text-3xl font-bold text-primary mb-2">Adiutor Dashboard</h1>
    <p class="text-gray-600 mb-8">Welcome to your dashboard, <?php echo htmlspecialchars($latestUserData['fullName']); ?>!</p>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Assigned Tasks Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden border-l-4 border-blue-500 hover:shadow-lg transition-shadow">
            <div class="p-5 flex justify-between items-center">
                <div>
                    <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider mb-1">
                        Assigned Tasks
                    </p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $totalAssignedTasks; ?></p>
                </div>
                <div class="text-blue-500 bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-tasks"></i>
                </div>
            </div>
        </div>

        <!-- Tasks Completed Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden border-l-4 border-green-500 hover:shadow-lg transition-shadow">
            <div class="p-5 flex justify-between items-center">
                <div>
                    <p class="text-xs font-semibold text-green-600 uppercase tracking-wider mb-1">
                        Completed Tasks
                    </p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $completedTasks; ?></p>
                </div>
                <div class="text-green-500 bg-green-100 p-3 rounded-full">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        <!-- Pending Tasks Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden border-l-4 border-yellow-500 hover:shadow-lg transition-shadow">
            <div class="p-5 flex justify-between items-center">
                <div>
                    <p class="text-xs font-semibold text-yellow-600 uppercase tracking-wider mb-1">
                        Pending Tasks
                    </p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $pendingTasks; ?></p>
                </div>
                <div class="text-yellow-500 bg-yellow-100 p-3 rounded-full">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>

        <!-- Students Managed Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden border-l-4 border-indigo-500 hover:shadow-lg transition-shadow">
            <div class="p-5 flex justify-between items-center">
                <div>
                    <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wider mb-1">
                        Client
                    </p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $totalClients; ?></p>
                </div>
                <div class="text-indigo-500 bg-indigo-100 p-3 rounded-full">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Mid Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Upcoming Deadlines Column -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="border-b px-6 py-4">
                <h2 class="font-bold text-lg text-primary">Upcoming Deadlines</h2>
            </div>
            <div class="p-6">
                <?php if (count($upcomingDeadlines) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Task</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($upcomingDeadlines as $task): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $task['title']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo formatDate($task['dueDate']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php 
                                        $statusClass = '';
                                        switch($task['status']) {
                                            case 'completed':
                                                $statusClass = 'bg-green-100 text-green-800';
                                                break;
                                            case 'pending':
                                                $statusClass = 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'overdue':
                                                $statusClass = 'bg-red-100 text-red-800';
                                                break;
                                            default:
                                                $statusClass = 'bg-gray-100 text-gray-800';
                                                break;
                                        }
                                        ?>
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                            <?php echo ucfirst($task['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        <a href="tasks.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            View All Tasks
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-calendar-check text-4xl mb-3"></i>
                        <p>No upcoming deadlines.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions Column -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="border-b px-6 py-4">
                <h2 class="font-bold text-lg text-primary">Quick Actions</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 gap-4">
                    <a href="tasks.php" class="flex items-center justify-center p-4 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors">
                        <i class="fas fa-checklist mr-2"></i> Manage Tasks
                    </a>
                    <a href="clients.php" class="flex items-center justify-center p-4 bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition-colors">
                        <i class="fas fa-user-graduate mr-2"></i> View Clients
                    </a>
                    <a href="reports.php" class="flex items-center justify-center p-4 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition-colors">
                        <i class="fas fa-chart-line mr-2"></i> View Reports
                    </a>
                    <a href="feedback.php" class="flex items-center justify-center p-4 bg-yellow-50 text-yellow-700 rounded-lg hover:bg-yellow-100 transition-colors">
                        <i class="fas fa-comment mr-2"></i> View Feedback
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Task Completion Rate Chart -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="border-b px-6 py-4">
                <h2 class="font-bold text-lg text-primary">Task Completion Rate</h2>
            </div>
            <div class="p-6">
                <div class="w-full bg-gray-200 rounded-full h-4 mb-6">
                    <div class="bg-gradient-to-r from-blue-500 to-green-500 h-4 rounded-full" style="width: <?php echo $completionRate; ?>%"></div>
                </div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-gray-700">Progress</span>
                    <span class="text-sm font-semibold text-primary"><?php echo $completionRate; ?>%</span>
                </div>
                <p class="text-sm text-gray-600">You have completed <?php echo $completedTasks; ?> out of <?php echo $totalAssignedTasks; ?> assigned tasks.</p>
            </div>
        </div>

        <!-- Feedback Rating Summary -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="border-b px-6 py-4">
                <h2 class="font-bold text-lg text-primary">Feedback Rating Summary</h2>
            </div>
            <div class="p-6 text-center">
                <h1 class="text-4xl font-bold text-gray-800 mb-2"><?php echo number_format($avgRating, 1); ?>/5.0</h1>
                <div class="text-2xl mb-4 text-yellow-400">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?php if ($i <= round($avgRating)): ?>
                            <i class="fas fa-star"></i>
                        <?php elseif ($i - 0.5 <= $avgRating): ?>
                            <i class="fas fa-star-half-alt"></i>
                        <?php else: ?>
                            <i class="far fa-star"></i>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
                <p class="text-gray-600 mb-4">Based on recent client feedbacks</p>
                <a href="feedback.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    View All Feedback
                </a>
            </div>
        </div>
    </div>

</main>

<?php include 'components/footer.php'; ?>

</body>
</html>

<?php
// Helper functions for dashboard
function getCountFromDB($query) {
    global $conn;
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_row();
        return $row[0];
    }
    return 0;
}

function getUpcomingDeadlines($adiutorId, $limit) {
    global $conn;
    $query = "SELECT taskID, title, dueDate, status FROM tasks 
              WHERE assignedTo = $adiutorId 
              AND dueDate >= CURDATE() 
              ORDER BY dueDate ASC 
              LIMIT $limit";
    $result = $conn->query($query);
    $deadlines = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $deadlines[] = $row;
        }
    }
    return $deadlines;
}

function getAverageRating($adiutorId) {
    global $conn;
    $query = "SELECT AVG(rating) as avg_rating FROM feedbacks WHERE receiver_id = $adiutorId";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        return $row['avg_rating'] ? $row['avg_rating'] : 0;
    }
    return 0;
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'completed':
            return 'bg-success text-white';
        case 'pending':
            return 'bg-warning text-dark';
        case 'overdue':
            return 'bg-danger text-white';
        default:
            return 'bg-secondary text-white';
    }
}

function getRecentActivities($adiutorId, $limit) {
    global $conn;
    // In a real application, you would have an activities table
    // For demonstration, we'll return dummy data
    $activities = [];
    
    // Example activities - in a real app, fetch this from a database
    $activities[] = [
        'icon' => 'fas fa-check-circle text-success',
        'title' => 'Completed task: Weekly Student Progress Review',
        'time' => '2 hours ago'
    ];
    $activities[] = [
        'icon' => 'fas fa-file-alt text-primary',
        'title' => 'Created a new task: Math Assignment Review',
        'time' => 'Yesterday at 3:45 PM'
    ];
    $activities[] = [
        'icon' => 'fas fa-comment text-warning',
        'title' => 'Received feedback from student John Doe',
        'time' => 'Yesterday at 1:30 PM'
    ];
    $activities[] = [
        'icon' => 'fas fa-user-plus text-info',
        'title' => 'New student assigned: Jane Smith',
        'time' => '3 days ago'
    ];
    
    return array_slice($activities, 0, $limit);
}
?>


