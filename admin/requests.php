<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../includes/db.php';

// Fetch data for overview cards
$totalUsers = $conn->query("SELECT COUNT(*) AS count FROM users")->fetch_assoc()['count'];
$totalRequests = $conn->query("SELECT COUNT(*) AS count FROM forms")->fetch_assoc()['count'];
$pendingRequests = $conn->query("SELECT COUNT(*) AS count FROM forms WHERE status = 'Pending'")->fetch_assoc()['count'];
$approvedRequests = $conn->query("SELECT COUNT(*) AS count FROM forms WHERE status = 'approved'")->fetch_assoc()['count'];

// Pagination for Recent Requests
$requestsPerPage = 5;
$requestPage = isset($_GET['request_page']) ? max(1, intval($_GET['request_page'])) : 1;
$requestOffset = ($requestPage - 1) * $requestsPerPage;

// Handle filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'submitted_at_desc';

// Build WHERE clause
$where = [];
$params = [];
if ($search !== '') {
    $searchEscaped = $conn->real_escape_string($search);
    $where[] = "(f.formID LIKE '%$searchEscaped%' OR u.fullName LIKE '%$searchEscaped%' OR f.project_name LIKE '%$searchEscaped%' OR f.service_type LIKE '%$searchEscaped%')";
}
if ($statusFilter !== '' && $statusFilter !== 'all') {
    $statusEscaped = $conn->real_escape_string($statusFilter);
    $where[] = "f.status = '$statusEscaped'";
}
$whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Sorting
$sortOptions = [
    'submitted_at_desc' => 'f.submitted_at DESC',
    'submitted_at_asc' => 'f.submitted_at ASC',
    'deadline_asc' => 'f.deadline ASC',
    'deadline_desc' => 'f.deadline DESC',
];
$orderBy = isset($sortOptions[$sort]) ? $sortOptions[$sort] : $sortOptions['submitted_at_desc'];

// Update totalRequests for filtered results
$totalRequestsQuery = "SELECT COUNT(*) AS count FROM forms f JOIN users u ON f.userID = u.userID $whereClause";
$totalRequests = $conn->query($totalRequestsQuery)->fetch_assoc()['count'];
$totalRequestPages = ceil($totalRequests / $requestsPerPage);

// Fetch data for recent requests with filters, sorting, and pagination
$recentRequests = $conn->query("
    SELECT 
        f.formID, 
        u.fullName AS user_full_name, 
        f.service_type, 
        f.project_name,
        f.request_description,
        f.expectations,
        f.additional_notes,
        f.submitted_at, 
        f.deadline, 
        f.status
    FROM forms f
    JOIN users u ON f.userID = u.userID
    $whereClause
    ORDER BY $orderBy
    LIMIT $requestsPerPage OFFSET $requestOffset
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
        <!-- Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">            
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-secondary transition-transform hover:scale-[1.02] duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm uppercase tracking-wider text-gray-500 font-medium mb-1">Total Requests</p>
                        <h2 class="text-3xl font-bold text-gray-800"><?php echo $totalRequests; ?></h2>
                    </div>
                    <div class="bg-secondary/10 p-3 rounded-full">
                        <i class="fas fa-file-alt text-2xl text-secondary"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-accent transition-transform hover:scale-[1.02] duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm uppercase tracking-wider text-gray-500 font-medium mb-1">Pending Requests</p>
                        <h2 class="text-3xl font-bold text-gray-800"><?php echo $pendingRequests; ?></h2>
                    </div>
                    <div class="bg-accent/10 p-3 rounded-full">
                        <i class="fas fa-clock text-2xl text-accent"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-green-500 transition-transform hover:scale-[1.02] duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm uppercase tracking-wider text-gray-500 font-medium mb-1">Approved Requests</p>
                        <h2 class="text-3xl font-bold text-gray-800"><?php echo $approvedRequests; ?></h2>
                    </div>
                    <div class="bg-green-500/10 p-3 rounded-full">
                        <i class="fas fa-check-circle text-2xl text-green-500"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Requests Table -->
        <div class="bg-white rounded-xl shadow-lg mb-10 overflow-hidden">
            <div class="p-6 border-b border-gray-200 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                <div>
                    <h2 class="text-xl font-bold text-primary">Recent Requests</h2>
                    <p class="text-gray-500 text-sm mt-1">Overview of the latest service requests</p>
                </div>
                <form method="get" class="flex flex-col md:flex-row gap-2 items-center">
                    <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>" class="px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    <select name="status" class="px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="all" <?php if($statusFilter === '' || $statusFilter === 'all') echo 'selected'; ?>>All Status</option>
                        <option value="pending" <?php if($statusFilter === 'pending') echo 'selected'; ?>>Pending</option>
                        <option value="approved" <?php if($statusFilter === 'approved') echo 'selected'; ?>>Approved</option>
                        <option value="rejected" <?php if($statusFilter === 'rejected') echo 'selected'; ?>>Rejected</option>
                    </select>
                    <select name="sort" class="px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="submitted_at_desc" <?php if($sort === 'submitted_at_desc') echo 'selected'; ?>>Newest Submitted</option>
                        <option value="submitted_at_asc" <?php if($sort === 'submitted_at_asc') echo 'selected'; ?>>Oldest Submitted</option>
                        <option value="deadline_asc" <?php if($sort === 'deadline_asc') echo 'selected'; ?>>Earliest Deadline</option>
                        <option value="deadline_desc" <?php if($sort === 'deadline_desc') echo 'selected'; ?>>Latest Deadline</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-secondary/10 text-secondary rounded-lg hover:bg-secondary/20 transition-colors flex items-center">
                        <i class="fas fa-filter mr-2"></i> Filter
                    </button>
                </form>
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
    </section>

    <?php include '../components/footer.php'; ?>
</body>
</html>