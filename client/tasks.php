<?php
session_start();
include '../auth/auth.php';
include '../includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'client') {
    header("Location: /TA-CMS/login.php");
    exit;
}

$user = $_SESSION['user'];
$clientID = $user['userID'];

// Get filter values
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'dueDate';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Fetch client's forms
$formResult = $conn->query("SELECT formID FROM forms WHERE userID = $clientID");

$formIDs = [];
if ($formResult && $formResult->num_rows > 0) {
    while ($row = $formResult->fetch_assoc()) {
        $formIDs[] = $row['formID'];
    }
}

// Build query with filters
if (!empty($formIDs)) {
    $idsString = implode(',', $formIDs);
    
    $query = "SELECT * FROM tasks WHERE formID IN ($idsString)";
    
    // Add status filter
    if ($statusFilter !== 'all') {
        $statusFilter = $conn->real_escape_string($statusFilter);
        $query .= " AND status = '$statusFilter'";
    }
        
    // Add sorting
    $sortBy = $conn->real_escape_string($sortBy);
    $sortOrder = $conn->real_escape_string($sortOrder);
    $query .= " ORDER BY $sortBy $sortOrder";
    
    // Execute query
    $tasks = $conn->query($query);
} else {
    $tasks = false;
}

// Get task statistics
$completedTasks = $conn->query("SELECT COUNT(*) AS completed FROM tasks WHERE formID in (SELECT formID from forms WHERE userID = $clientID) AND status = 'completed'")->fetch_assoc()['completed'];
$inProgressTasks = $conn->query("SELECT COUNT(*) AS inprogress FROM tasks WHERE formID in (SELECT formID from forms WHERE userID = $clientID) AND status = 'in progress'")->fetch_assoc()['inprogress'];
$pendingTasks = $conn->query("SELECT COUNT(*) AS pending FROM tasks WHERE formID in (SELECT formID from forms WHERE userID = $clientID) AND status = 'pending'")->fetch_assoc()['pending'];
$totalTasks = $completedTasks + $inProgressTasks + $pendingTasks;
$progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

// Get past due tasks
$today = date('Y-m-d');
$pastDueTasks = $conn->query("SELECT COUNT(*) AS pastdue FROM tasks 
                             WHERE assignedTo = $clientID 
                             AND status != 'completed' 
                             AND dueDate < '$today'")->fetch_assoc()['pastdue'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks - Client Portal</title>
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
        <!-- Page Header -->
        <div class="mb-8 mt-4 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-primary">My Tasks</h1>
                <p class="text-gray-600">View all of your approved tasks</p>
            </div>
            <!-- Changed from "New Task" to "New Request" -->
            <a href="../get-started.php" class="inline-flex items-center gap-2 bg-accent text-white px-5 py-2.5 rounded-lg shadow hover:bg-accent-dark transition-colors font-semibold">
                <i class="fa-solid fa-plus"></i> New Request
            </a>
        </div>

        <!-- Task Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-md p-4 border-l-4 border-blue-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500">Total Tasks</p>
                        <h3 class="text-2xl font-bold"><?php echo $totalTasks; ?></h3>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fa-solid fa-list-check text-blue-500 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-md p-4 border-l-4 border-yellow-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500">In Progress</p>
                        <h3 class="text-2xl font-bold"><?php echo $inProgressTasks; ?></h3>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <i class="fa-solid fa-spinner text-yellow-500 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-md p-4 border-l-4 border-green-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500">Completed</p>
                        <h3 class="text-2xl font-bold"><?php echo $completedTasks; ?></h3>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fa-solid fa-check text-green-500 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-md p-4 border-l-4 border-red-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500">Past Due</p>
                        <h3 class="text-2xl font-bold"><?php echo $pastDueTasks; ?></h3>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <i class="fa-solid fa-clock text-red-500 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mb-8 bg-white p-4 rounded-lg shadow-md">
            <div class="flex justify-between mb-2">
                <span class="font-medium">Overall Progress</span>
                <span class="font-medium"><?php echo $progress; ?>%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4">
                <div class="bg-accent h-4 rounded-full" style="width: <?php echo $progress; ?>%;"></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 bg-white p-4 rounded-lg shadow-md">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary" onchange="this.form.submit()">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in progress" <?php echo $statusFilter === 'in progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>               
                
                <div>
                    <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                    <select name="sort" id="sort" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary" onchange="this.form.submit()">
                        <option value="dueDate" <?php echo $sortBy === 'dueDate' ? 'selected' : ''; ?>>Due Date</option>
                        <option value="title" <?php echo $sortBy === 'title' ? 'selected' : ''; ?>>Title</option>
                        <option value="status" <?php echo $sortBy === 'status' ? 'selected' : ''; ?>>Status</option>
                    </select>
                </div>
                
                <div>
                    <label for="order" class="block text-sm font-medium text-gray-700 mb-1">Order</label>
                    <select name="order" id="order" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary" onchange="this.form.submit()">
                        <option value="ASC" <?php echo $sortOrder === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                        <option value="DESC" <?php echo $sortOrder === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- Tasks List -->
        <?php if ($tasks && $tasks->num_rows > 0): ?>
            <div class="grid gap-4">
                <?php while ($task = $tasks->fetch_assoc()): 
                    $isDue = ($task['status'] !== 'completed' && strtotime($task['dueDate']) < strtotime(date('Y-m-d')));
                    $dueBadge = $isDue ? '<span class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">OVERDUE</span>' : '';
                    
                    $statusColors = [
                        'completed' => 'bg-green-100 text-green-700',
                        'in progress' => 'bg-yellow-100 text-yellow-700',
                        'pending' => 'bg-gray-100 text-gray-700',
                    ];
                    
                    $statusColor = $statusColors[$task['status']] ?? 'bg-gray-100 text-gray-700';
                ?>
                <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow border border-gray-100">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xl font-semibold text-primary"><?php echo htmlspecialchars($task['title']); ?></span>
                                <?php echo $dueBadge; ?>
                            </div>
                            <p class="text-gray-600 mb-3"><?php echo htmlspecialchars($task['description']); ?></p>
                            <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                                <div>
                                    <i class="fa-regular fa-calendar mr-1 text-accent"></i>
                                    <span class="font-medium">Due:</span>
                                    <?php echo htmlspecialchars(date('M d, Y', strtotime($task['dueDate']))); ?>
                                </div>
                                <div>
                                    <i class="fa-solid fa-circle-check mr-1 text-accent"></i>
                                    <span class="font-medium">Status:</span>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium <?php echo $statusColor; ?>">
                                        <?php echo strtoupper(htmlspecialchars($task['status'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <!-- Removed the task update form and only kept the view button -->
                            <a href="view-task.php?id=<?php echo $task['taskID']; ?>"
                               class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-lg shadow hover:bg-primary/90 transition-colors font-semibold text-sm">
                                <i class="fa-regular fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow-md p-8 flex flex-col items-center justify-center">
                <svg class="w-48 h-48 mb-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15" stroke="#3a5a78" stroke-width="2"/>
                    <path d="M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5V7H9V5Z" stroke="#3a5a78" stroke-width="2"/>
                    <path d="M12 12L12 16" stroke="#6c9ab5" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="12" cy="9" r="1" fill="#f9a826"/>
                </svg></svg>
                <p class="text-gray-600 text-lg mb-4 flex items-center gap-2">
                    <i class="fa-regular fa-face-smile text-accent text-2xl"></i>
                    No tasks available. Create a new request to get started!
                </p>
                <!-- Changed from "Get Started" to "New Request" -->
                <a href="../get-started.php" class="inline-flex items-center gap-2 bg-accent text-white px-5 py-2.5 rounded-lg shadow hover:bg-accent-dark transition-colors font-semibold text-base">
                    <i class="fa-solid fa-paper-plane"></i> Create New Request
                </a>
            </div>
        <?php endif; ?>
    </section>

    <?php include '../components/footer.php'; ?>
</body>
</html>
