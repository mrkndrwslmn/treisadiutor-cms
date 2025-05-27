<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
include '../includes/db.php';

// --- Filters ---
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;
$priority = $_GET['priority'] ?? '';
$assignedUser = $_GET['assigned_user'] ?? '';
$breakdown = $_GET['breakdown'] ?? 'assignee';

// --- Task Completion Summary ---
$where = [];
if ($startDate) $where[] = "createdAt >= '$startDate'";
if ($endDate) $where[] = "createdAt <= '$endDate'";
if ($priority) $where[] = "priority = '$priority'";
if ($assignedUser) $where[] = "assignedTo = '$assignedUser'";
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$totalTasks = $conn->query("SELECT COUNT(*) AS count FROM tasks $whereSql")->fetch_assoc()['count'];
$completedTasks = $conn->query("SELECT COUNT(*) AS count FROM tasks $whereSql" . ($whereSql ? " AND" : " WHERE") . " status = 'completed'")->fetch_assoc()['count'];
$pendingTasks = $conn->query("SELECT COUNT(*) AS count FROM tasks $whereSql" . ($whereSql ? " AND" : " WHERE") . " status = 'pending'")->fetch_assoc()['count'];
$overdueTasks = $conn->query("SELECT COUNT(*) AS count FROM tasks $whereSql" . ($whereSql ? " AND" : " WHERE") . " status != 'completed' AND dueDate < CURDATE()")->fetch_assoc()['count'];

// --- Assigned Users for Filter ---
$usersResult = $conn->query("SELECT userID, fullName FROM users WHERE role != 'client' ORDER BY fullName ASC");

// --- Task Turnaround Time ---
// Always show by assignee (assignedTo), join with users for name
$turnaroundSql = "
    SELECT u.fullName AS category, 
           AVG(TIMESTAMPDIFF(HOUR, t.createdAt, t.updatedAt)) AS avg_hours
    FROM tasks t
    LEFT JOIN users u ON t.assignedTo = u.userID
    WHERE t.status = 'completed'
    GROUP BY t.assignedTo
    ORDER BY avg_hours ASC
";
$turnaroundResult = $conn->query($turnaroundSql);

// --- Overdue Task Report ---
// Ensure overdue tasks are shown with assigned user name (even if null)
$overdueResult = $conn->query("
    SELECT t.title, u.fullName, t.dueDate, DATEDIFF(CURDATE(), t.dueDate) AS days_overdue
    FROM tasks t
    LEFT JOIN users u ON t.assignedTo = u.userID
    WHERE t.status != 'completed' AND t.dueDate < CURDATE()
    ORDER BY t.dueDate ASC
");

// --- User Performance Reports ---
// Filter users by role = 'adiutor'
$userPerfResult = $conn->query("
    SELECT u.userID, u.fullName,
        COUNT(t.taskID) AS assigned,
        SUM(t.status = 'completed') AS completed,
        ROUND(100 * SUM(t.status = 'completed' AND t.updatedAt <= t.dueDate)/NULLIF(SUM(t.status = 'completed'),0),1) AS on_time_rate,
        ROUND(AVG(f.rating),2) AS avg_rating
    FROM users u
    LEFT JOIN tasks t ON t.assignedTo = u.userID
    LEFT JOIN feedbacks f ON t.taskID = f.task_ID
    WHERE u.role = 'adiutor'
    GROUP BY u.userID
");

// Top Performers
$topPerfResult = $conn->query("
    SELECT u.fullName,
        ROUND(100 * SUM(t.status = 'completed' AND t.updatedAt <= t.dueDate)/NULLIF(SUM(t.status = 'completed'),0),1) AS on_time_rate,
        SUM(t.status = 'completed') AS completed
    FROM users u
    LEFT JOIN tasks t ON t.assignedTo = u.userID
    WHERE u.role = 'adiutor'
    GROUP BY u.userID
    ORDER BY on_time_rate DESC, completed DESC
    LIMIT 5
");

// Idle Users
$idleUsersResult = $conn->query("
    SELECT u.fullName,
        MAX(t.updatedAt) AS last_completed,
        DATEDIFF(CURDATE(), MAX(t.updatedAt)) AS days_idle
    FROM users u
    LEFT JOIN tasks t ON t.assignedTo = u.userID AND t.status = 'completed'
    WHERE u.role = 'adiutor'
    GROUP BY u.userID
    HAVING days_idle >= 30 OR last_completed IS NULL
");

// --- Client Engagement Reports ---
$clientOverviewResult = $conn->query("
    SELECT u.fullName AS client,
        COUNT(f.formID) AS submitted,
        SUM(f.status = 'Approved') AS approved,
        SUM(f.status = 'Assigned') AS assigned,
        SUM(f.status = 'Completed') AS completed
    FROM users u
    LEFT JOIN forms f ON f.userID = u.userID
    WHERE u.role = 'client'
    GROUP BY u.userID
");

// Client Feedback
$clientFeedbackResult = $conn->query("
    SELECT u.fullName AS client,
        ROUND(AVG(fb.rating),2) AS avg_rating,
        GROUP_CONCAT(fb.comment SEPARATOR '; ') AS feedbacks
    FROM users u
    LEFT JOIN forms f ON f.userID = u.userID
    LEFT JOIN tasks t ON t.formID = f.formID
    LEFT JOIN feedbacks fb ON fb.task_ID = t.taskID AND fb.rating IS NOT NULL
    WHERE u.role = 'client'
    GROUP BY u.userID
    HAVING avg_rating IS NOT NULL
");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - Treis Adiutor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
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

        document.addEventListener('DOMContentLoaded', function() {
            // Form filter auto-submit
            const filterForm = document.querySelector('#filterForm');
            const filterInputs = filterForm.querySelectorAll('input, select');
            filterInputs.forEach(input => {
                input.addEventListener('change', () => filterForm.submit());
            });
        });
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <?php include 'components/navbar.php'; ?>

    <div class="bg-gradient-to-r from-primary/10 to-secondary/10 py-8 px-6 md:px-10 pt-24">
        <div class="container mx-auto">
            <h1 class="text-3xl md:text-4xl font-bold text-primary mb-2 transition-all hover:text-secondary">Reports Dashboard</h1>
            <p class="text-gray-600 hover:text-primary transition-colors">Real-time analytics and performance insights</p>
        </div>
    </div>

    <section class="container mx-auto p-6 md:px-10 flex-grow space-y-10">

        <!-- Task Completion Summary -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-xl">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center bg-gradient-to-r from-primary/5 to-secondary/5">
                <div>
                    <h2 class="text-xl font-bold text-primary">Task Completion Summary</h2>
                    <p class="text-gray-500 text-sm mt-1">Overview of all tasks</p>
                </div>
            </div>
            <form id="filterForm" class="flex flex-wrap gap-4 p-6 bg-gray-50 border-b border-gray-200">
                <div class="group">
                    <label class="block text-xs text-gray-500 mb-1 group-hover:text-primary transition-colors">Date Range</label>
                    <div class="flex items-center space-x-2">
                        <input type="date" name="start_date" value="<?php echo $_GET['start_date'] ?? ''; ?>"
                               class="px-3 py-2 border rounded-lg bg-white hover:border-primary focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        <span class="text-gray-400">to</span>
                        <input type="date" name="end_date" value="<?php echo $_GET['end_date'] ?? ''; ?>"
                               class="px-3 py-2 border rounded-lg bg-white hover:border-primary focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                </div>
                <div class="group">
                    <label class="block text-xs text-gray-500 mb-1 group-hover:text-primary transition-colors">Priority</label>
                    <select name="priority" 
                            class="px-3 py-2 border rounded-lg bg-white hover:border-primary focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        <option value="">All</option>
                        <option value="high" <?php echo ($_GET['priority'] ?? '') === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="normal" <?php echo ($_GET['priority'] ?? '') === 'normal' ? 'selected' : ''; ?>>Normal</option>
                        <option value="low" <?php echo ($_GET['priority'] ?? '') === 'low' ? 'selected' : ''; ?>>Low</option>
                    </select>
                </div>
                <div class="group">
                    <label class="block text-xs text-gray-500 mb-1 group-hover:text-primary transition-colors">Assigned User</label>
                    <select name="assigned_user" 
                            class="px-3 py-2 border rounded-lg bg-white hover:border-primary focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        <option value="">All</option>
                        <?php while($user = $usersResult->fetch_assoc()): ?>
                            <option value="<?php echo $user['userID']; ?>" 
                                    <?php echo ($_GET['assigned_user'] ?? '') == $user['userID'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['fullName']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 text-left">
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Total Assigned</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Completed</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Pending</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Overdue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="bg-white">
                            <td class="px-6 py-4"><?php echo $totalTasks; ?></td>
                            <td class="px-6 py-4"><?php echo $completedTasks; ?></td>
                            <td class="px-6 py-4"><?php echo $pendingTasks; ?></td>
                            <td class="px-6 py-4"><?php echo $overdueTasks; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Task Turnaround Time -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-primary">Task Turnaround Time</h2>
                <p class="text-gray-500 text-sm mt-1">Average time from assignment to completion (by Adiutor)</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 text-left">
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Adiutor</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Avg. Turnaround Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($turnaroundResult && $turnaroundResult->num_rows > 0): ?>
                            <?php while($row = $turnaroundResult->fetch_assoc()): ?>
                                <tr class="bg-white">
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($row['category'] ?? '--'); ?></td>
                                    <td class="px-6 py-4"><?php echo is_null($row['avg_hours']) ? '--' : round($row['avg_hours'], 2) . ' hrs'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr class="bg-white"><td class="px-6 py-4" colspan="2">--</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Overdue Task Report -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-primary">Overdue Task Report</h2>
                <p class="text-gray-500 text-sm mt-1">Tasks past their due dates</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 text-left">
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Task</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Assigned Adiutor</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Due Date</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Days Overdue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($overdueResult && $overdueResult->num_rows > 0): ?>
                            <?php while($row = $overdueResult->fetch_assoc()): ?>
                                <tr class="bg-white">
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($row['fullName'] ?? '--'); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($row['dueDate']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($row['days_overdue']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr class="bg-white">
                                <td class="px-6 py-4 text-center text-gray-500" colspan="4">No overdue tasks found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Adiutor Performance Reports -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-primary">Adiutor Performance Reports</h2>
            </div>
            <div class="p-6">
                <h3 class="text-lg font-semibold text-secondary mb-2">Individual Adiutor Report</h3>
                <div class="overflow-x-auto mb-6">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 text-left">
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Adiutor</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Assigned</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Completed</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">On-time %</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Avg. Task Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($userPerfResult && $userPerfResult->num_rows > 0): ?>
                                <?php while($row = $userPerfResult->fetch_assoc()): ?>
                                    <tr class="bg-white">
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($row['fullName']); ?></td>
                                        <td class="px-6 py-4"><?php echo $row['assigned']; ?></td>
                                        <td class="px-6 py-4"><?php echo $row['completed']; ?></td>
                                        <td class="px-6 py-4"><?php echo is_null($row['on_time_rate']) ? '--' : $row['on_time_rate'].'%'; ?></td>
                                        <td class="px-6 py-4"><?php echo is_null($row['avg_rating']) ? '--' : $row['avg_rating']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr class="bg-white"><td class="px-6 py-4" colspan="5">--</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <h3 class="text-lg font-semibold text-secondary mb-2">Top Performers</h3>
                <div class="overflow-x-auto mb-6">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 text-left">
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Adiutor</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">On-time %</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Tasks Completed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($topPerfResult && $topPerfResult->num_rows > 0): ?>
                                <?php while($row = $topPerfResult->fetch_assoc()): ?>
                                    <tr class="bg-white">
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($row['fullName']); ?></td>
                                        <td class="px-6 py-4"><?php echo is_null($row['on_time_rate']) ? '--' : $row['on_time_rate'].'%'; ?></td>
                                        <td class="px-6 py-4"><?php echo $row['completed']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr class="bg-white"><td class="px-6 py-4" colspan="3">--</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <h3 class="text-lg font-semibold text-secondary mb-2">Idle Adiutors Report</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 text-left">
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Adiutor</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Last Task Completed</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Days Idle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($idleUsersResult && $idleUsersResult->num_rows > 0): ?>
                                <?php while($row = $idleUsersResult->fetch_assoc()): ?>
                                    <tr class="bg-white">
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($row['fullName']); ?></td>
                                        <td class="px-6 py-4"><?php echo $row['last_completed'] ? htmlspecialchars($row['last_completed']) : '--'; ?></td>
                                        <td class="px-6 py-4"><?php echo $row['days_idle']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr class="bg-white"><td class="px-6 py-4" colspan="3">--</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Client Engagement Reports -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-primary">Client Engagement Reports</h2>
            </div>
            <div class="p-6">
                <h3 class="text-lg font-semibold text-secondary mb-2">Client Task Overview</h3>
                <div class="overflow-x-auto mb-6">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 text-left">
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Client</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Tasks Submitted</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Approved</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Assigned</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Completed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($clientOverviewResult && $clientOverviewResult->num_rows > 0): ?>
                                <?php while($row = $clientOverviewResult->fetch_assoc()): ?>
                                    <tr class="bg-white">
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($row['client']); ?></td>
                                        <td class="px-6 py-4"><?php echo $row['submitted']; ?></td>
                                        <td class="px-6 py-4"><?php echo $row['approved']; ?></td>
                                        <td class="px-6 py-4"><?php echo $row['assigned']; ?></td>
                                        <td class="px-6 py-4"><?php echo $row['completed']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr class="bg-white"><td class="px-6 py-4" colspan="5">--</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <h3 class="text-lg font-semibold text-secondary mb-2">Client Feedback Summary</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 text-left">
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Client</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Avg. Rating</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($clientFeedbackResult && $clientFeedbackResult->num_rows > 0): ?>
                                <?php while($row = $clientFeedbackResult->fetch_assoc()): ?>
                                    <tr class="bg-white">
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($row['client']); ?></td>
                                        <td class="px-6 py-4"><?php echo is_null($row['avg_rating']) ? '--' : $row['avg_rating']; ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($row['feedbacks'] ?? '--'); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr class="bg-white"><td class="px-6 py-4" colspan="3">--</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </section>

    <?php include '../components/footer.php'; ?>
</body>
</html>
