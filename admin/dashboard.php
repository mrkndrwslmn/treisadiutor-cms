<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../includes/db.php';

// Fetch data for cards
$totalUsers = $conn->query("SELECT COUNT(*) AS count FROM users")->fetch_assoc()['count'];
$totalClients = $conn->query("SELECT COUNT(*) AS count FROM users WHERE role = 'client'")->fetch_assoc()['count'];
$totalActiveUsers = $conn->query("SELECT COUNT(*) AS count FROM users WHERE status = 'active'")->fetch_assoc()['count'];

$totalRequests = $conn->query("SELECT COUNT(*) AS count FROM forms")->fetch_assoc()['count'];
$pendingRequests = $conn->query("SELECT COUNT(*) AS count FROM forms WHERE status = 'Pending'")->fetch_assoc()['count'];
$approvedRequests = $conn->query("SELECT COUNT(*) AS count FROM forms WHERE status = 'Approved'")->fetch_assoc()['count'];

$totalTasks = $conn->query("SELECT COUNT(*) AS count FROM tasks")->fetch_assoc()['count'];
$pendingTasks = $conn->query("SELECT COUNT(*) AS count FROM tasks WHERE status = 'Pending'")->fetch_assoc()['count'];
$completedTasks = $conn->query("SELECT COUNT(*) AS count FROM tasks WHERE status = 'Completed'")->fetch_assoc()['count'];

// Pagination for Recent Requests
$requestsPerPage = 5;
$requestPage = isset($_GET['request_page']) ? max(1, intval($_GET['request_page'])) : 1;
$requestOffset = ($requestPage - 1) * $requestsPerPage;
$totalRequestPages = ceil($totalRequests / $requestsPerPage);

$recentRequests = $conn->query("
    SELECT 
        f.formID, 
        u.fullName AS user_full_name, 
        f.service_type, 
        f.project_name,
        f.submitted_at, 
        f.deadline, 
        f.status
    FROM forms f
    JOIN users u ON f.userID = u.userID
    ORDER BY f.submitted_at DESC
    LIMIT $requestsPerPage OFFSET $requestOffset
");

// Pagination for Tasks Table Section
$tasksPerPage = 5;
$taskPage = isset($_GET['task_page']) ? max(1, intval($_GET['task_page'])) : 1;
$taskOffset = ($taskPage - 1) * $tasksPerPage;
$totalTaskPages = ceil($totalTasks / $tasksPerPage);

$recentTasks = $conn->query("
    SELECT taskID, title, dueDate, status
    FROM tasks
    ORDER BY createdAt DESC
    LIMIT $tasksPerPage OFFSET $taskOffset
");

// Pagination for Manage Users
$usersPerPage = 5;
$userPage = isset($_GET['user_page']) ? max(1, intval($_GET['user_page'])) : 1;
$userOffset = ($userPage - 1) * $usersPerPage;
$totalUserPages = ceil($totalUsers / $usersPerPage);

$usersResult = $conn->query("SELECT userID, fullName, email, dateCreated, role, status FROM users ORDER BY dateCreated DESC LIMIT $usersPerPage OFFSET $userOffset");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Treis Adiutor</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        };

        function banUser(userID, userName) {
            Swal.fire({
                title: 'Are you sure?',
                text: `Do you want to deactivate ${userName}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: tailwind.config.theme.extend.colors.accent,
                cancelButtonColor: tailwind.config.theme.extend.colors.primary,
                confirmButtonText: 'Yes, deactivate',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('ban_user.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'userID=' + encodeURIComponent(userID)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Deactivated!',
                                text: 'User has been set to inactive.',
                                icon: 'success',
                                confirmButtonColor: tailwind.config.theme.extend.colors.primary
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: data.message || 'Failed to deactivate user.',
                                icon: 'error',
                                confirmButtonColor: tailwind.config.theme.extend.colors.primary
                            });
                        }
                    });
                }
            });
        }

        function unbanUser(userID, userName) {
            Swal.fire({
                title: 'Reactivate User?',
                text: `Do you want to reactivate ${userName}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: tailwind.config.theme.extend.colors.accent,
                cancelButtonColor: tailwind.config.theme.extend.colors.primary,
                confirmButtonText: 'Yes, reactivate',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('unban_user.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'userID=' + encodeURIComponent(userID)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Reactivated!',
                                text: 'User has been set to active.',
                                icon: 'success',
                                confirmButtonColor: tailwind.config.theme.extend.colors.primary
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: data.message || 'Failed to reactivate user.',
                                icon: 'error',
                                confirmButtonColor: tailwind.config.theme.extend.colors.primary
                            });
                        }
                    });
                }
            });
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <?php include 'components/navbar.php'; ?>

    <div class="bg-gradient-to-r from-primary/10 to-secondary/10 py-8 px-6 md:px-10 pt-24">
        <div class="container mx-auto">
            <h1 class="text-3xl md:text-4xl font-bold text-primary mb-2">Admin Dashboard</h1>
            <p class="text-gray-600">Welcome back! Here's your overview.</p>
        </div>
    </div>

    <section class="container mx-auto p-6 md:px-10 flex-grow space-y-10">

        <!-- Overview Cards: Users -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-primary">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm uppercase text-gray-500 mb-1">Total Users</p>
                        <h2 class="text-3xl font-bold text-gray-800"><?php echo $totalUsers; ?></h2>
                    </div>
                    <div class="bg-primary/10 p-3 rounded-full">
                        <i class="fas fa-users text-2xl text-primary"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-secondary">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm uppercase text-gray-500 mb-1">Total Clients</p>
                        <h2 class="text-3xl font-bold text-gray-800"><?php echo $totalClients; ?></h2>
                    </div>
                    <div class="bg-secondary/10 p-3 rounded-full">
                        <i class="fas fa-user-tie text-2xl text-secondary"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-accent">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm uppercase text-gray-500 mb-1">Total Active Users</p>
                        <h2 class="text-3xl font-bold text-gray-800"><?php echo $totalActiveUsers; ?></h2>
                    </div>
                    <div class="bg-accent/10 p-3 rounded-full">
                        <i class="fas fa-user-check text-2xl text-accent"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manage Users Section -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-primary">Users</h2>
                    <p class="text-gray-500 text-sm mt-1">View and manage all system users</p>
                </div>
                <div class="flex space-x-3">
                    
                    <button type="button" class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors flex items-center ml-2"
                        onclick="window.location.href='add_user.php'">
                        <i class="fas fa-plus mr-2"></i> Add User
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 text-left">
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">User ID</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Registration Date</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Total Requests</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php while ($user = $usersResult->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $user['userID']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full bg-primary/20 flex items-center justify-center text-primary font-medium">
                                        <?php
                                            $names = explode(' ', $user['fullName']);
                                            $initials = strtoupper(substr($names[0],0,1) . (isset($names[1]) ? substr($names[1],0,1) : ''));
                                            echo $initials;
                                        ?>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['fullName']); ?></div>
                                        <div class="text-xs text-gray-500">Member since <?php echo date('Y', strtotime($user['dateCreated'])); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($user['dateCreated']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <?php
                                    // Count total requests for this user
                                    $uid = $user['userID'];
                                    $reqCount = $conn->query("SELECT COUNT(*) AS cnt FROM forms WHERE userID = $uid")->fetch_assoc()['cnt'];
                                    echo $reqCount;
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                    <?php echo $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="flex space-x-3">
                                    <a href="edit_user.php?userID=<?php echo $user['userID']; ?>" class="text-secondary hover:text-primary transition-colors">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user['status'] === 'active'): ?>
                                    <button class="text-red-500 hover:text-red-700 transition-colors"
                                        onclick="banUser('<?php echo $user['userID']; ?>', '<?php echo htmlspecialchars(addslashes($user['fullName'])); ?>')"
                                        title="Deactivate User">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                    <?php else: ?>
                                    <button class="text-green-600 hover:text-green-800 transition-colors"
                                        onclick="unbanUser('<?php echo $user['userID']; ?>', '<?php echo htmlspecialchars(addslashes($user['fullName'])); ?>')"
                                        title="Reactivate User">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
                <p class="text-sm text-gray-500">
                    Showing <?php
                        $start = $userOffset + 1;
                        $end = min($userOffset + $usersPerPage, $totalUsers);
                        echo "$start-$end of $totalUsers users";
                    ?>
                </p>
                <div class="flex items-center space-x-2">
                    <a href="?user_page=<?php echo max(1, $userPage-1); ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50<?php if($userPage==1) echo ' opacity-50 pointer-events-none'; ?>">Previous</a>
                    <?php for($i=1; $i<=$totalUserPages; $i++): ?>
                        <a href="?user_page=<?php echo $i; ?>"
                           class="px-3 py-1 <?php echo $i==$userPage ? 'bg-primary text-white border border-primary' : 'bg-white border border-gray-300 text-gray-700'; ?> rounded-md text-sm hover:bg-primary/90 <?php if($i==$userPage) echo 'pointer-events-none'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    <a href="?user_page=<?php echo min($totalUserPages, $userPage+1); ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50<?php if($userPage==$totalUserPages) echo ' opacity-50 pointer-events-none'; ?>">Next</a>
                </div>
            </div>
        </div>

        <!-- Overview Cards: Requests -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-primary">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm uppercase text-gray-500 mb-1">Total Requests</p>
                        <h2 class="text-3xl font-bold text-gray-800"><?php echo $totalRequests; ?></h2>
                    </div>
                    <div class="bg-primary/10 p-3 rounded-full">
                        <i class="fas fa-file-alt text-2xl text-primary"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-accent">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm uppercase text-gray-500 mb-1">Pending Requests</p>
                        <h2 class="text-3xl font-bold text-gray-800"><?php echo $pendingRequests; ?></h2>
                    </div>
                    <div class="bg-accent/10 p-3 rounded-full">
                        <i class="fas fa-clock text-2xl text-accent"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm uppercase text-gray-500 mb-1">Approved Requests</p>
                        <h2 class="text-3xl font-bold text-gray-800"><?php echo $approvedRequests; ?></h2>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-check-circle text-2xl text-green-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Requests Section -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-primary">Recent Requests</h2>
                    <p class="text-gray-500 text-sm mt-1">Overview of the latest service requests</p>
                </div>
                <div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 text-left">
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Request ID</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">User Full Name</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Project Name</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Service Type</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted Date</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php while ($row = $recentRequests->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $row['formID']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo $row['user_full_name']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo $row['project_name']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo $row['service_type']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo $row['submitted_at']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo $row['deadline']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $row['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($row['status'] === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'); ?>">
                                    <?php echo ucwords($row['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="view_requests.php?id=<?php echo $row['formID']; ?>" class="text-accent hover:text-primary transition-colors font-medium">View Details</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
                <p class="text-sm text-gray-500">
                    Showing <?php
                        $start = $requestOffset + 1;
                        $end = min($requestOffset + $requestsPerPage, $totalRequests);
                        echo "$start-$end of $totalRequests entries";
                    ?>
                </p>
                <div class="flex items-center space-x-2">
                    <a href="?request_page=<?php echo max(1, $requestPage-1); ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50<?php if($requestPage==1) echo ' opacity-50 pointer-events-none'; ?>">Previous</a>
                    <?php for($i=1; $i<=$totalRequestPages; $i++): ?>
                        <a href="?request_page=<?php echo $i; ?>"
                           class="px-3 py-1 <?php echo $i==$requestPage ? 'bg-primary text-white border border-primary' : 'bg-white border border-gray-300 text-gray-700'; ?> rounded-md text-sm hover:bg-primary/90 <?php if($i==$requestPage) echo 'pointer-events-none'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    <a href="?request_page=<?php echo min($totalRequestPages, $requestPage+1); ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50<?php if($requestPage==$totalRequestPages) echo ' opacity-50 pointer-events-none'; ?>">Next</a>
                </div>
            </div>
        </div>

        <!-- Overview Cards: Tasks -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-primary">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm uppercase text-gray-500 mb-1">Total Tasks</p>
                        <h2 class="text-3xl font-bold text-gray-800"><?php echo $totalTasks; ?></h2>
                    </div>
                    <div class="bg-primary/10 p-3 rounded-full">
                        <i class="fas fa-tasks text-2xl text-primary"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-accent">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm uppercase text-gray-500 mb-1">Pending Tasks</p>
                        <h2 class="text-3xl font-bold text-gray-800"><?php echo $pendingTasks; ?></h2>
                    </div>
                    <div class="bg-accent/10 p-3 rounded-full">
                        <i class="fas fa-hourglass-half text-2xl text-accent"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm uppercase text-gray-500 mb-1">Completed Tasks</p>
                        <h2 class="text-3xl font-bold text-gray-800"><?php echo $completedTasks; ?></h2>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-check text-2xl text-green-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tasks Table Section -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-primary">Tasks</h2>
                    <p class="text-gray-500 text-sm mt-1">Most recent tasks</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 text-left">
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Task Title</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php while ($task = $recentTasks->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($task['title']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($task['dueDate']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                    <?php
                                        if ($task['status'] === 'pending') echo 'bg-yellow-100 text-yellow-800';
                                        elseif ($task['status'] === 'completed') echo 'bg-green-100 text-green-800';
                                        else echo 'bg-gray-100 text-gray-800';
                                    ?>">
                                    <?php echo ucwords(htmlspecialchars($task['status'])); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
                <p class="text-sm text-gray-500">
                    Showing <?php
                        $start = $taskOffset + 1;
                        $end = min($taskOffset + $tasksPerPage, $totalTasks);
                        echo "$start-$end of $totalTasks tasks";
                    ?>
                </p>
                <div class="flex items-center space-x-2">
                    <a href="?task_page=<?php echo max(1, $taskPage-1); ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50<?php if($taskPage==1) echo ' opacity-50 pointer-events-none'; ?>">Previous</a>
                    <?php for($i=1; $i<=$totalTaskPages; $i++): ?>
                        <a href="?task_page=<?php echo $i; ?>"
                           class="px-3 py-1 <?php echo $i==$taskPage ? 'bg-primary text-white border border-primary' : 'bg-white border border-gray-300 text-gray-700'; ?> rounded-md text-sm hover:bg-primary/90 <?php if($i==$taskPage) echo 'pointer-events-none'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    <a href="?task_page=<?php echo min($totalTaskPages, $taskPage+1); ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50<?php if($taskPage==$totalTaskPages) echo ' opacity-50 pointer-events-none'; ?>">Next</a>
                </div>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="bg-white rounded-xl shadow-lg p-6 flex flex-wrap gap-4 justify-between items-center">
            <h2 class="text-xl font-bold text-primary mb-4 w-full">Quick Actions</h2>
            <a href="reports.php" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors flex items-center">
                <i class="fas fa-chart-line mr-2"></i> View Reports
            </a>
            <a href="requests.php" class="px-6 py-3 bg-secondary text-white rounded-lg hover:bg-secondary/90 transition-colors flex items-center">
                <i class="fas fa-tasks mr-2"></i> Manage Requests
            </a>
            <a href="manage_clients.php" class="px-6 py-3 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors flex items-center">
                <i class="fas fa-user-tie mr-2"></i> Manage Clients
            </a>
            <a href="feedbacks.php" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors flex items-center">
                <i class="fas fa-comments mr-2"></i> View Feedbacks
            </a>
        </div>

    </section>

    <?php include '../components/footer.php'; ?>
</body>
</html>