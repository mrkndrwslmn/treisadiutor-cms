<?php
session_start();
include_once '../includes/db.php'; // adjust path as needed
include '../includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'adiutor') {
    header("Location: ../login.php");
    exit;
}

$user = $_SESSION['user'];
$adiutorId = $user['userID'];

// Task Completion Rate
$onTime = $pastDue = $early = $totalCompleted = 0;
$sql = "SELECT updatedAt, dueDate, createdAt FROM tasks WHERE status = 'completed'";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $totalCompleted++;
    $completed = strtotime($row['updatedAt']);
    $due = strtotime($row['dueDate']);
    $created = strtotime($row['createdAt']);
    if ($completed < $due && $completed > $created) $onTime++;
    elseif ($completed > $due) $pastDue++;
    elseif ($completed <= $created) $early++;
}

// Task History (last 10)
$taskHistory = [];
$sql = "SELECT taskID, title, status, updatedAt FROM tasks WHERE status = 'completed' ORDER BY updatedAt DESC LIMIT 10";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $taskHistory[] = $row;
}

$adiutor_id = $user['userID'];
// Feedback Received
$feedbacks = [];
$sql = "SELECT rating, comment, created_at FROM feedbacks where receiver_id = $adiutor_id ORDER BY created_at DESC LIMIT 10";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $feedbacks[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reports / Analytics</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
    <style>
        /* Hide scrollbars for collapsible content */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include 'components/navbar.php'; ?>
    <div class="container mx-auto px-2 py-6">
        <h1 class="text-3xl font-bold text-primary mb-8 text-center">Reports / Analytics</h1>

        <div class="space-y-6">

            <!-- Task Completion Rate -->
            <div class="bg-white rounded-lg shadow p-6">
                <button onclick="toggleSection('completionRate')" class="flex items-center w-full text-left focus:outline-none">
                    <span class="text-xl font-semibold text-primary flex-1">Task Completion Rate</span>
                    <i id="icon-completionRate" class="fa fa-chevron-down transition-transform"></i>
                </button>
                <div id="completionRate" class="mt-4">
                    <ul class="divide-y divide-gray-200">
                        <li class="py-2 flex justify-between">
                            <span>On Time</span>
                            <span class="font-semibold text-green-600"><?php echo $onTime; ?> (<?php echo $totalCompleted ? round($onTime/$totalCompleted*100,1) : 0; ?>%)</span>
                        </li>
                        <li class="py-2 flex justify-between">
                            <span>Past Due</span>
                            <span class="font-semibold text-red-600"><?php echo $pastDue; ?> (<?php echo $totalCompleted ? round($pastDue/$totalCompleted*100,1) : 0; ?>%)</span>
                        </li>
                        <li class="py-2 flex justify-between">
                            <span>Early</span>
                            <span class="font-semibold text-blue-600"><?php echo $early; ?> (<?php echo $totalCompleted ? round($early/$totalCompleted*100,1) : 0; ?>%)</span>
                        </li>
                        <li class="py-2 flex justify-between">
                            <span>Total Completed</span>
                            <span class="font-semibold"><?php echo $totalCompleted; ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Task History -->
            <div class="bg-white rounded-lg shadow p-6">
                <button onclick="toggleSection('taskHistory')" class="flex items-center w-full text-left focus:outline-none">
                    <span class="text-xl font-semibold text-primary flex-1">Task History (Last 10)</span>
                    <i id="icon-taskHistory" class="fa fa-chevron-down transition-transform"></i>
                </button>
                <div id="taskHistory" class="overflow-x-auto mt-4 no-scrollbar">
                    <table class="min-w-full text-sm text-left">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="py-2 px-3">ID</th>
                                <th class="py-2 px-3">Title</th>
                                <th class="py-2 px-3">Status</th>
                                <th class="py-2 px-3">Completed At</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($taskHistory as $task): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-2 px-3"><?php echo htmlspecialchars($task['taskID']); ?></td>
                                <td class="py-2 px-3"><?php echo htmlspecialchars($task['title']); ?></td>
                                <td class="py-2 px-3">
                                    <span class="inline-block px-2 py-1 rounded text-xs font-medium
                                        <?php
                                            if ($task['status'] === 'completed') echo 'bg-green-100 text-green-700';
                                            else echo 'bg-gray-100 text-gray-700';
                                        ?>">
                                        <?php echo ucwords(htmlspecialchars($task['status'])); ?>
                                    </span>
                                </td>
                                <td class="py-2 px-3"><?php echo htmlspecialchars($task['updatedAt']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Feedback Received -->
            <div class="bg-white rounded-lg shadow p-6">
                <button onclick="toggleSection('feedbacks')" class="flex items-center w-full text-left focus:outline-none">
                    <span class="text-xl font-semibold text-primary flex-1">Feedback Received (Last 10)</span>
                    <i id="icon-feedbacks" class="fa fa-chevron-down transition-transform"></i>
                </button>
                <div id="feedbacks" class="overflow-x-auto mt-4 no-scrollbar">
                    <table class="min-w-full text-sm text-left">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="py-2 px-3">Rating</th>
                                <th class="py-2 px-3">Message</th>
                                <th class="py-2 px-3">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($feedbacks as $fb): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-2 px-3">
                                    <span class="flex items-center">
                                        <?php
                                            $rating = intval($fb['rating']);
                                            for ($i = 0; $i < $rating; $i++) {
                                                echo '<i class="fa fa-star text-yellow-400"></i>';
                                            }
                                            for ($i = $rating; $i < 5; $i++) {
                                                echo '<i class="fa fa-star text-gray-300"></i>';
                                            }
                                        ?>
                                    </span>
                                </td>
                                <td class="py-2 px-3"><?php echo htmlspecialchars($fb['comment']); ?></td>
                                <td class="py-2 px-3"><?php echo htmlspecialchars($fb['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
    <?php include 'components/footer.php'; ?>
    <script>
        // Collapsible sections
        function toggleSection(id) {
            const section = document.getElementById(id);
            const icon = document.getElementById('icon-' + id);
            if (section.style.display === 'none' || section.style.display === '') {
                section.style.display = 'block';
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                section.style.display = 'none';
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        }
        // Start with all sections open on desktop, collapsed on mobile
        function setInitialSections() {
            const isMobile = window.innerWidth < 640;
            ['completionRate', 'taskHistory', 'feedbacks'].forEach(id => {
                const section = document.getElementById(id);
                const icon = document.getElementById('icon-' + id);
                if (isMobile) {
                    section.style.display = 'none';
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                } else {
                    section.style.display = 'block';
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                }
            });
        }
        window.addEventListener('DOMContentLoaded', setInitialSections);
        window.addEventListener('resize', setInitialSections);
    </script>
</body>
</html>
