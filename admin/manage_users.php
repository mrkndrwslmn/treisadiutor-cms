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


// --- Advanced Filters ---
$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'dateCreated_desc';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination
$usersPerPage = 10;
$userPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$userOffset = ($userPage - 1) * $usersPerPage;

// --- Build WHERE clause ---
$where = [];
if ($roleFilter && in_array($roleFilter, ['admin', 'client', 'adiutor'])) {
    $where[] = "role = '" . $conn->real_escape_string($roleFilter) . "'";
}
if ($statusFilter && in_array($statusFilter, ['active', 'inactive'])) {
    $where[] = "status = '" . $conn->real_escape_string($statusFilter) . "'";
}
if ($search) {
    $searchEsc = $conn->real_escape_string($search);
    $where[] = "(fullName LIKE '%$searchEsc%' OR email LIKE '%$searchEsc%' OR userID LIKE '%$searchEsc%')";
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// --- Sorting ---
$sortSql = "ORDER BY dateCreated DESC";
if ($sort === 'name_asc') $sortSql = "ORDER BY fullName ASC";
elseif ($sort === 'name_desc') $sortSql = "ORDER BY fullName DESC";
elseif ($sort === 'dateCreated_asc') $sortSql = "ORDER BY dateCreated ASC";
elseif ($sort === 'dateCreated_desc') $sortSql = "ORDER BY dateCreated DESC";

// --- Count total filtered users ---
$totalUsersRes = $conn->query("SELECT COUNT(*) AS count FROM users $whereSql");
$totalUsers = $totalUsersRes->fetch_assoc()['count'];
$totalUserPages = max(1, ceil($totalUsers / $usersPerPage));

// --- Fetch users with filters, sort, and pagination ---
$users = $conn->query("
    SELECT 
        userID, 
        fullName, 
        email, 
        role, 
        status, 
        dateCreated, 
        (SELECT COUNT(*) FROM forms WHERE forms.userID = users.userID) AS totalRequests 
    FROM users
    $whereSql
    $sortSql
    LIMIT $usersPerPage OFFSET $userOffset
");

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
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <?php include 'components/navbar.php'; ?>

    <section class="container mx-auto p-6 md:px-10 flex-grow pt-24">
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

        <!-- User Management -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                <div>
                    <h2 class="text-xl font-bold text-primary">Manage Users</h2>
                    <p class="text-gray-500 text-sm mt-1">View and manage all system users</p>
                </div>
                <form method="get" class="flex flex-wrap gap-2 items-center">
                    <div class="relative">
                        <input id="searchInput" name="search" type="text" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>" class="px-4 py-2 pl-10 bg-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <select name="role" class="px-3 py-2 rounded-lg bg-gray-100 text-sm">
                        <option value="">All Roles</option>
                        <option value="admin" <?php if($roleFilter=='admin') echo 'selected'; ?>>Admin</option>
                        <option value="adiutor" <?php if($roleFilter=='adiutor') echo 'selected'; ?>>Adiutor</option>
                        <option value="client" <?php if($roleFilter=='client') echo 'selected'; ?>>Client</option>
                    </select>
                    <select name="status" class="px-3 py-2 rounded-lg bg-gray-100 text-sm">
                        <option value="">All Status</option>
                        <option value="active" <?php if($statusFilter=='active') echo 'selected'; ?>>Active</option>
                        <option value="inactive" <?php if($statusFilter=='inactive') echo 'selected'; ?>>Inactive</option>
                    </select>
                    <select name="sort" class="px-3 py-2 rounded-lg bg-gray-100 text-sm">
                        <option value="dateCreated_desc" <?php if($sort=='dateCreated_desc') echo 'selected'; ?>>Newest</option>
                        <option value="dateCreated_asc" <?php if($sort=='dateCreated_asc') echo 'selected'; ?>>Oldest</option>
                        <option value="name_asc" <?php if($sort=='name_asc') echo 'selected'; ?>>Name A-Z</option>
                        <option value="name_desc" <?php if($sort=='name_desc') echo 'selected'; ?>>Name Z-A</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 text-sm">Filter</button>
                    <a href="manage_users.php" class="px-3 py-2 bg-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-300">Reset</a>
                    <button type="button" class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors flex items-center ml-2"
                        onclick="window.location.href='add_user.php'">
                        <i class="fas fa-plus mr-2"></i> Add User
                    </button>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table id="userTable" class="w-full">
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
                        <?php while ($user = $users->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $user['userID']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full bg-primary/20 flex items-center justify-center text-primary font-medium">
                                        <?php echo strtoupper(substr($user['fullName'], 0, 2)); ?>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['fullName']); ?></div>
                                        <div class="text-xs text-gray-500">Member since <?php echo date('Y', strtotime($user['dateCreated'])); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo date('Y-m-d', strtotime($user['dateCreated'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo $user['totalRequests']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
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
                                    <a href="edit_user.php?userID=<?php echo $user['userID']; ?>" class="text-secondary hover:text-primary transition-colors" title="Edit User">
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
                        if ($totalUsers == 0) echo "0 users";
                        else echo "$start-$end of $totalUsers users";
                    ?>
                </p>
                <div class="flex items-center space-x-2">
                    <?php
                    // Build base query string for filters
                    $baseQS = $_GET;
                    unset($baseQS['page']);
                    $baseQS = http_build_query($baseQS);
                    ?>
                    <a href="?<?php echo $baseQS . ($baseQS ? '&' : '') . 'page=' . max(1, $userPage-1); ?>"
                        class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50<?php if($userPage==1) echo ' opacity-50 pointer-events-none'; ?>">Previous</a>
                    <?php for($i=1; $i<=$totalUserPages; $i++): ?>
                        <a href="?<?php echo $baseQS . ($baseQS ? '&' : '') . 'page=' . $i; ?>"
                           class="px-3 py-1 <?php echo $i==$userPage ? 'bg-primary text-white border border-primary' : 'bg-white border border-gray-300 text-gray-700'; ?> rounded-md text-sm hover:bg-primary/90 <?php if($i==$userPage) echo 'pointer-events-none'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    <a href="?<?php echo $baseQS . ($baseQS ? '&' : '') . 'page=' . min($totalUserPages, $userPage+1); ?>"
                        class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50<?php if($userPage==$totalUserPages) echo ' opacity-50 pointer-events-none'; ?>">Next</a>
                </div>
            </div>
        </div>
    </section>

    <?php include '../components/footer.php'; ?>

    <script>
        // SweetAlert ban/unban logic
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

        // Optional: Keep instant search working (client-side)
        document.getElementById('searchInput').addEventListener('input', function () {
            // Optionally, submit the form for server-side search
            // this.form.submit();
        });
    </script>
</body>
</html>