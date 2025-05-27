<?php
session_start();
include '../auth/auth.php';
include '../includes/db.php';
include 'functions/mail.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'adiutor') {
    header("Location: ../login.php");
    exit;
}

$user = $_SESSION['user'];
$adiutorId = $user['userID'];

// Handle task operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_task'])) {
        $id = mysqli_real_escape_string($conn, $_POST['task_id']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        
        // Check if user is trying to mark task as completed
        if ($status === 'Completed') {
            // Check if documents exist for this task
            $doc_query = "SELECT COUNT(*) as doc_count FROM documents WHERE taskID = '$id'";
            $doc_result = mysqli_query($conn, $doc_query);
            $doc_count = mysqli_fetch_assoc($doc_result)['doc_count'];
            
            if ($doc_count == 0) {
                $error_message = "Error: You must upload documents for this task before marking it as completed.";
            } else {
                // Update only the status field
                $query = "UPDATE tasks SET status='$status' WHERE taskID='$id'";
                
                if (mysqli_query($conn, $query)) {
                    $success_message = "Task status updated successfully!";

                    $clientQuery = "
                        SELECT u.email 
                        FROM users u
                        JOIN forms f ON u.userID = f.userID
                        JOIN tasks t ON f.formID = t.formID
                        WHERE t.taskID = ?
                    ";

                    $clientStmt = $conn->prepare($clientQuery);
                    $clientStmt->bind_param("i", $id);
                    $clientStmt->execute();
                    $clientResult = $clientStmt->get_result()->fetch_assoc();
                    $clientEmail = $clientResult['email'] ?? '';
                    $clientStmt->close();

                    $adminQuery = "SELECT DISTINCT email FROM users WHERE role = 'admin'";
                    $adminResult = $conn->query($adminQuery);
                    
                    if (!empty($clientEmail)) {
                        notifyUser($clientEmail, "Task Completed", "We’re pleased to inform you that your task has been successfully completed. You may now log in to treisadiutor.com to view the task attachments and review the final details. \n\nPlease take a moment to review the output at your convenience. If you encounter any issues or have any concerns, don’t hesitate to reach out — we're here to help. \n\nThank you for trusting us! \n\nWarm regards,\nTreis Adiutor");
                    }

                    while ($adminRow = $adminResult->fetch_assoc()) {
                        $adminEmail = $adminRow['email'];
                
                        if (!empty($adminEmail)) {                
                            notifyUser($adminEmail, "Task Completed", "Great news, Admin! \n\nA task assigned to one of your clients has been successfully marked as completed. \n\nPlease take a moment to review the submitted output and ensure everything is in order. If the client has not yet viewed the task or received the completion notice via email, kindly notify them through their preferred communication channel.\n\nBest regards,\nTreis Adiutor");
                        }
                    }
                } else {
                    $error_message = "Error: " . mysqli_error($conn);
                }
            }
        } else {
            // For other statuses, just update without document check
            $query = "UPDATE tasks SET status='$status' WHERE taskID='$id'";
            
            if (mysqli_query($conn, $query)) {
                $success_message = "Task status updated successfully!";
            } else {
                $error_message = "Error: " . mysqli_error($conn);
            }
        }
    }
}

// Calculate completion rate
$total_query = "SELECT COUNT(*) as total FROM tasks where assignedTo = $adiutorId";
$completed_query = "SELECT COUNT(*) as completed FROM tasks WHERE assignedTo = $adiutorId AND status='Completed'";

$total_result = mysqli_query($conn, $total_query);
$completed_result = mysqli_query($conn, $completed_query);

$total_tasks = mysqli_fetch_assoc($total_result)['total'];
$completed_tasks = mysqli_fetch_assoc($completed_result)['completed'];

$completion_rate = 0;
if ($total_tasks > 0) {
    $completion_rate = round(($completed_tasks / $total_tasks) * 100);
}

// Calculate additional task statistics
$in_progress_query = "SELECT COUNT(*) as in_progress FROM tasks WHERE assignedTo = $adiutorId && status='In Progress'";
$overdue_query = "SELECT COUNT(*) as overdue FROM tasks WHERE assignedTo = $adiutorId AND status!='Completed' AND dueDate < CURDATE()";
$high_priority_query = "SELECT COUNT(*) as tasks FROM tasks WHERE assignedTo = $adiutorId AND priority = 'High'";

$in_progress_result = mysqli_query($conn, $in_progress_query);
$overdue_result = mysqli_query($conn, $overdue_query);
$high_priority_result = mysqli_query($conn, $high_priority_query);

$in_progress_tasks = mysqli_fetch_assoc($in_progress_result)['in_progress'];
$overdue_tasks = mysqli_fetch_assoc($overdue_result)['overdue'];
$high_priority_tasks = mysqli_fetch_assoc($high_priority_result)['tasks'];

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Build query with filters
$myUserID = $_SESSION['user']['userID'];
$query = "SELECT * FROM tasks WHERE assignedTo = '$myUserID'";
if (!empty($status_filter)) {
    $query .= " AND status = '$status_filter'";
}
if (!empty($priority_filter)) {
    $query .= " AND priority = '$priority_filter'";
}
if (!empty($date_filter)) {
    $query .= " AND DATE(dueDate) = '$date_filter'";
}
$query .= " ORDER BY dueDate ASC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
<body class="bg-gray-50 text-gray-800 min-h-screen">
    <?php include 'components/navbar.php'; ?>

    <div class="flex-grow container mx-auto px-4 py-8 pt-16">
  <div class="container mx-auto px-4 py-10 max-w-7xl">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-10">
      <h1 class="text-3xl font-bold text-primary mb-4 md:mb-0">
        <i class="fa-solid fa-tasks text-4xl text-accent mr-2"></i>
        Task Dashboard
      </h1>
    </div>

    <!-- Alerts -->
    <?php if (isset($success_message)): ?>
      <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg shadow-sm flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <?php echo $success_message; ?>
      </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
      <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-sm flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <?php echo $error_message; ?>
      </div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <!-- Completion Rate -->
      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-800">Completion</h3>
          <span class="text-2xl font-bold text-indigo-600"><?php echo $completion_rate; ?>%</span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-3 mb-4">
          <div class="bg-gradient-to-r from-indigo-500 to-purple-600 h-3 rounded-full" style="width: <?php echo $completion_rate; ?>%"></div>
        </div>
        <p class="text-sm text-gray-600"><?php echo $completed_tasks; ?> of <?php echo $total_tasks; ?> tasks completed</p>
      </div>
      
      <!-- Tasks In Progress -->
      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center">
          <div class="p-3 bg-blue-100 rounded-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <div class="ml-4">
            <h3 class="text-lg font-semibold text-gray-800">In Progress</h3>
            <p class="text-2xl font-bold text-blue-600"><?php echo $in_progress_tasks; ?></p>
          </div>
        </div>
      </div>
      
      <!-- Overdue Tasks -->
      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center">
          <div class="p-3 bg-red-100 rounded-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="ml-4">
            <h3 class="text-lg font-semibold text-gray-800">Overdue</h3>
            <p class="text-2xl font-bold text-red-600"><?php echo $overdue_tasks; ?></p>
          </div>
        </div>
      </div>
      
      <!-- High Priority Tasks -->
      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center">
          <div class="p-3 bg-amber-100 rounded-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <div class="ml-4">
            <h3 class="text-lg font-semibold text-gray-800">High Priority</h3>
            <p class="text-2xl font-bold text-amber-600"><?php echo $high_priority_tasks; ?></p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm mb-8 border border-gray-100">
      <div class="p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
          </svg>
          Filter Tasks
        </h2>
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" id="status" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50">
              <option value="">All Statuses</option>
              <option value="Not Started" <?php if($status_filter == 'Not Started') echo 'selected'; ?>>Not Started</option>
              <option value="In Progress" <?php if($status_filter == 'In Progress') echo 'selected'; ?>>In Progress</option>
              <option value="Completed" <?php if($status_filter == 'Completed') echo 'selected'; ?>>Completed</option>
            </select>
          </div>
          
          <div>
            <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
            <select name="priority" id="priority" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50">
              <option value="">All Priorities</option>
              <option value="High" <?php if($priority_filter == 'High') echo 'selected'; ?>>High</option>
              <option value="Medium" <?php if($priority_filter == 'Medium') echo 'selected'; ?>>Medium</option>
              <option value="Low" <?php if($priority_filter == 'Low') echo 'selected'; ?>>Low</option>
            </select>
          </div>
          
          <div>
            <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
            <input type="text" id="date" name="date" class="datepicker w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50" value="<?php echo $date_filter; ?>" placeholder="Select Date">
          </div>
          
          <div class="flex items-end space-x-2">
            <button type="submit" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              Search
            </button>
            <a href="tasks.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg transition-colors duration-200">Clear</a>
          </div>
        </form>
      </div>
    </div>
    
    <!-- Tasks Grid -->
<div>
  <h2 class="text-xl font-semibold text-gray-800 mb-6 flex items-center">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
    </svg>
    Your Tasks
  </h2>
  
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php while ($row = mysqli_fetch_assoc($result)): 
      $task_id = $row['taskID'];
      $doc_query = "SELECT COUNT(*) as doc_count FROM documents WHERE taskID = '$task_id'";
      $doc_result = mysqli_query($conn, $doc_query);
      $doc_count = mysqli_fetch_assoc($doc_result)['doc_count'];
      
      $priorityClass = '';
      $priorityBadge = '';
      if ($row['priority'] == 'High') {
        $priorityClass = 'border-l-4 border-red-500';
        $priorityBadge = 'bg-red-100 text-red-800';
      } else if ($row['priority'] == 'Medium') {
        $priorityClass = 'border-l-4 border-yellow-500';
        $priorityBadge = 'bg-yellow-100 text-yellow-800';
      } else {
        $priorityClass = 'border-l-4 border-green-500';
        $priorityBadge = 'bg-green-100 text-green-800';
      }
      
      $statusBadge = '';
      if ($row['status'] == 'Not Started') $statusBadge = 'bg-gray-100 text-gray-800';
      else if ($row['status'] == 'In Progress') $statusBadge = 'bg-blue-100 text-blue-800';
      else $statusBadge = 'bg-green-100 text-green-800';
      
      // Calculate days left
      $due_date = new DateTime($row['dueDate']);
      $today = new DateTime();
      $days_left = $today->diff($due_date)->days;
      $is_overdue = $today > $due_date && $row['status'] != 'Completed';
    ?>
    
    <div class="bg-white rounded-xl shadow-sm overflow-hidden <?php echo $priorityClass; ?>">
      <div class="p-6">
        <div class="flex justify-between items-start mb-4">
          <h3 class="text-lg font-semibold text-gray-800 line-clamp-1"><?php echo htmlspecialchars($row['title']); ?></h3>
          <div class="flex space-x-2">
            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $priorityBadge; ?>">
              <?php echo ucfirst(htmlspecialchars($row['priority'])); ?>
            </span>
            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $statusBadge; ?>">
              <?php echo ucwords(htmlspecialchars($row['status'])); ?>
            </span>
          </div>
        </div>
        
        <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?php echo htmlspecialchars($row['description']); ?></p>
        
        <div class="flex items-center text-sm text-gray-500 mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 <?php echo $is_overdue ? 'text-red-500' : 'text-gray-400'; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <span class="<?php echo $is_overdue ? 'text-red-500 font-medium' : ''; ?>">
            <?php echo date('M d, Y', strtotime($row['dueDate'])); ?>
            <?php if ($is_overdue): ?>
              (Overdue)
            <?php elseif ($days_left == 0 && $row['status'] != 'Completed'): ?>
              (Due today)
            <?php elseif ($days_left > 0 && $row['status'] != 'Completed'): ?>
              (<?php echo $days_left; ?> days left)
            <?php endif; ?>
          </span>
        </div>
        
        <div class="flex items-center text-sm text-indigo-600 mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <a href="documents.php?task_id=<?php echo $task_id; ?>" class="hover:underline">
            Documents (<?php echo $doc_count; ?>)
          </a>
        </div>
        
        <div class="flex justify-between pt-4 border-t border-gray-100">
          <?php if ($row['status'] !== 'completed'): ?>
            <button class="bg-primary hover:bg-primary/90 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors duration-200 flex items-center update-status-btn"
                  data-id="<?php echo $task_id; ?>"
                  data-title="<?php echo htmlspecialchars($row['title']); ?>"
                  data-status="<?php echo htmlspecialchars($row['status']); ?>"
                  data-doccount="<?php echo $doc_count; ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              Update Status
            </button>
          <?php endif; ?>
          <a href="view_task.php?id=<?php echo $task_id; ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors duration-200 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m0 0l3-3m-3 3l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            View Task
          </a>
        </div>
      </div>
    </div> 
    <?php endwhile; ?>
    
    <!-- Empty state if no tasks -->
    <?php if (mysqli_num_rows($result) == 0): ?>
    <div class="col-span-1 md:grid-cols-2 lg:col-span-3 bg-white rounded-xl shadow-sm p-8 text-center">
      <div class="max-w-md mx-auto">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        <h3 class="text-lg font-medium text-gray-800 mb-2">No tasks found</h3>
        <p class="text-gray-500 mb-6">Please try a different filter.</p>
        <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition-colors duration-200 inline-flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
          Create New Task
        </button>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div> 


<!-- Status Update Modal  -->
<div id="status-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Update Task Status</h3>
    <p class="text-gray-600 mb-4">Change status for <span id="modal-task-title" class="font-medium"></span></p>
    
    <form method="POST" action="" class="space-y-4">
      <input type="hidden" id="modal-task-id" name="task_id" value="">
      
      <div>
        <label for="new_status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
        <select id="new_status" name="new_status" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50">
            <option value="pending">Pending</option>
            <option value="Not Started">Not Started</option>          
          <option value="In Progress">In Progress</option>
          <option value="Completed">Completed</option>
        </select>
      </div>
      
      <div class="flex justify-end space-x-3 pt-4">
        <button type="button" id="close-modal" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-lg transition-colors duration-200">
          Cancel
        </button>
        <button type="submit" name="update_status" class="bg-accent hover:bg-primary text-white px-4 py-2 rounded-lg transition-colors duration-200">
          Update Status
        </button>
      </div>
    </form>
  </div>
</div>

<!-- JavaScript to handle modal functionality-->
<script>
  // This would be expanded in a real implementation
  document.addEventListener('DOMContentLoaded', function() {
    const statusBtns = document.querySelectorAll('.update-status-btn');
    const modal = document.getElementById('status-modal');
    const closeModal = document.getElementById('close-modal');
    const modalTaskTitle = document.getElementById('modal-task-title');
    const modalTaskId = document.getElementById('modal-task-id');
    const newStatus = document.getElementById('new_status');
    
    // Open modal
    statusBtns.forEach(btn => {
      btn.addEventListener('click', function() {
        const taskId = this.getAttribute('data-id');
        const taskTitle = this.getAttribute('data-title');
        const taskStatus = this.getAttribute('data-status');
        
        modalTaskTitle.textContent = taskTitle;
        modalTaskId.value = taskId;
        newStatus.value = taskStatus;
        
        modal.classList.remove('hidden');
      });
    });
    
    // Close modal
    closeModal.addEventListener('click', function() {
      modal.classList.add('hidden');
    });
  });
</script>

        <!-- Calendar View -->
        <div class="bg-white rounded-lg shadow-md mb-8 overflow-hidden max-w-7xl mx-auto mt-4">
            <div class="bg-primary text-white px-6 py-4">
                <h2 class="text-xl font-semibold">Calendar View</h2>
            </div>
            <div class="p-6">
                <div id="calendar" class="h-[600px]"></div>
            </div>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div id="updateStatusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="flex justify-between items-center border-b px-6 py-4">
                <h2 class="text-xl font-bold text-gray-800">Update Task Status</h2>
                <button class="close text-gray-500 hover:text-gray-800 focus:outline-none">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form method="POST" action="" class="px-6 py-4">
                <input type="hidden" id="edit_task_id" name="task_id">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="task_title">Task:</label>
                    <p id="task_title" class="text-gray-800 px-3 py-2 bg-gray-100 rounded"></p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_status">Status</label>
                    <select id="edit_status" name="status" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring focus:ring-primary focus:ring-opacity-50">
                        <option value="Pending">Pending</option>
                        <option value="Not Started">Not Started</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
                <div id="document_warning" class="mb-4 bg-yellow-100 text-yellow-800 p-4 rounded hidden">
                    <div class="flex">
                        <svg class="h-5 w-5 text-yellow-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            You must attach documents, photos, or links for this task before marking it as completed.
                            <a href="#" id="upload_documents_link" class="font-medium text-yellow-800 underline">Upload Documents</a>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" name="update_task" class="bg-primary hover:bg-secondary/90 text-white font-medium py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>

    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr(".datepicker", {
                dateFormat: "Y-m-d",
                allowInput: true,
            });
            
            const modal = document.getElementById('updateStatusModal');
            const updateBtns = document.querySelectorAll('.update-status-btn');
            const closeBtn = document.querySelector('.close');
            const statusSelect = document.getElementById('edit_status');
            const docWarning = document.getElementById('document_warning');
            
            updateBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const title = this.getAttribute('data-title');
                    const status = this.getAttribute('data-status');
                    const docCount = parseInt(this.getAttribute('data-doccount'));
                    
                    document.getElementById('edit_task_id').value = id;
                    document.getElementById('task_title').textContent = title;
                    document.getElementById('edit_status').value = status;
                    
                    document.getElementById('upload_documents_link').href = 'upload_documents.php?id=' + id;
                    
                    checkDocumentsForStatus(docCount);
                    
                    modal.classList.remove('hidden');
                });
            });
            
            statusSelect.addEventListener('change', function() {
                const docCount = parseInt(document.querySelector('.update-status-btn[data-id="' + document.getElementById('edit_task_id').value + '"]').getAttribute('data-doccount'));
                checkDocumentsForStatus(docCount);
            });
            
            function checkDocumentsForStatus(docCount) {
                if (statusSelect.value === 'Completed' && docCount === 0) {
                    docWarning.classList.remove('hidden');
                    document.querySelector('button[name="update_task"]').disabled = true;
                } else {
                    docWarning.classList.add('hidden');
                    document.querySelector('button[name="update_task"]').disabled = false;
                }
            }
            
            closeBtn.addEventListener('click', function() {
                modal.classList.add('hidden');
            });
            
            window.addEventListener('click', function(event) {
                if (event.target == modal) {
                    modal.classList.add('hidden');
                }
            });
            
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: [
                    <?php 
                    // Only show tasks with status 'In Progress' or 'Not Started'
                    mysqli_data_seek($result, 0);
                    while ($row = mysqli_fetch_assoc($result)): 
                        if ($row['status'] !== 'in progress' && $row['status'] !== 'pending') continue;
                        $color = '';
                        if ($row['priority'] == 'High') $color = '#f87171';
                        else if ($row['priority'] == 'Medium') $color = '#fbbf24';
                        else $color = '#34d399';
                        
                        $task_id = $row['taskID'];
                        $doc_query = "SELECT COUNT(*) as doc_count FROM documents WHERE taskID = '$task_id'";
                        $doc_result = mysqli_query($conn, $doc_query);
                        $doc_count = mysqli_fetch_assoc($doc_result)['doc_count'];
                    ?>
                    {
                        title: '<?php echo addslashes($row['title']); ?>',
                        start: '<?php echo date('Y-m-d', strtotime($row['dueDate'])); ?>',
                        backgroundColor: '<?php echo $color; ?>',
                        borderColor: '<?php echo $color; ?>',
                        extendedProps: {
                            id: <?php echo $task_id; ?>,
                            status: '<?php echo $row['status']; ?>',
                            docCount: <?php echo $doc_count; ?>
                        }
                    },
                    <?php endwhile; ?>
                ],
                eventClick: function(info) {
                    const id = info.event.extendedProps.id;
                    const title = info.event.title;
                    const status = info.event.extendedProps.status;
                    const docCount = info.event.extendedProps.docCount;
                    
                    document.getElementById('edit_task_id').value = id;
                    document.getElementById('task_title').textContent = title;
                    document.getElementById('edit_status').value = status;
                    document.getElementById('upload_documents_link').href = 'documents.php?task_id=' + id;
                    
                    checkDocumentsForStatus(docCount);
                    
                    modal.classList.remove('hidden');
                }
            });
            calendar.render();
        });
    </script>
    <?php include 'components/footer.php'; ?>
</body>
</html>
