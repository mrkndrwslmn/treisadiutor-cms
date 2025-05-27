<?php
session_start();
include '../includes/db.php';
include_once 'functions/mail.php';

$formID = $_GET['id'];

$query = "
    SELECT 
        f.formID, f.contact_method, f.contact_details, f.project_name, f.service_type, f.request_description, 
        f.expectations, f.submitted_at, f.deadline, f.status, f.additional_notes,
        u.fullName, u.email, u.phoneNumber, u.userID,
        t.title AS projectName
    FROM forms f
    LEFT JOIN users u ON f.userID = u.userID
    LEFT JOIN tasks t ON f.formID = t.formID
    WHERE f.formID = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $formID);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();

// Handle reject request action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject'])) {
    $feedback = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';
    $updateQuery = "UPDATE forms SET status = 'rejected' WHERE formID = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("i", $formID);
    $stmt->execute();

    // Fetch user email based on userID from forms table
    $userQuery = "SELECT u.email FROM forms f JOIN users u ON f.userID = u.userID WHERE f.formID = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("i", $formID);
    $stmt->execute();
    $stmt->bind_result($assignedToEmail);
    $stmt->fetch();
    $stmt->close();

    $projectName = $request['project_name'] ?? 'the project';
    $message = "We appreciate your submission and the time you’ve taken to complete your request. After careful review, we regret to inform you that your request has been rejected due to the following reason(s): \n\n$feedback \n\nWe understand that this may be disappointing, and we want to assure you that our decision was made after a thorough evaluation based on our guidelines and standards. If you believe there has been a misunderstanding or if you would like to make the necessary adjustments, you are welcome to revise your submission and resubmit it for review. \n\nShould you need further clarification regarding the feedback or if you require assistance in addressing the issues raised, please don’t hesitate to reach out to our support team. \n\nRegards, \nTreis Adiutor";
    // Call notifyUser with feedback as message
    if (!empty($assignedToEmail)) {
        notifyUser($assignedToEmail, "Request for $projectName has been Denied", $message);
    }

    // Refresh to show updated status
    header("Location: view_requests.php?id=" . $formID);
    exit;
}

// Get all form file attachments
$query = "SELECT filepath, date_uploaded FROM form_files WHERE formID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $formID);
$stmt->execute();
$attachments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch all users with the role "adiutor"
$adiutorQuery = "SELECT userID, fullName FROM users WHERE role = 'adiutor'";
$adiutorResults = $conn->query($adiutorQuery);

// Display request details
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Request - Treis Adiutor</title>
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
<body class="bg-gray-50/50 min-h-screen flex flex-col">
    <?php include 'components/navbar.php'; ?>

    <!-- Header Section -->
    <div class="bg-gradient-to-r from-primary/10 via-secondary/10 to-primary/5 py-8 pt-24">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <!-- Back Navigation -->
                    <button onclick="history.back()" class="group mb-6 inline-flex items-center space-x-2 text-sm font-medium text-gray-600 hover:text-primary transition-colors">
                        <svg class="w-5 h-5 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span>Back to requests</span>
                    </button>
                    <h1 class="text-3xl font-bold text-primary tracking-tight">Request Details</h1>
                    <p class="mt-2 text-gray-600">Request #<?= $request['formID'] ?></p>
                </div>
                <div class="mt-4 md:mt-0">
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium
                        <?= $request['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                            ($request['status'] === 'approved' ? 'bg-green-100 text-green-800' : 
                            'bg-red-100 text-red-800'); ?>">
                        <span class="h-2 w-2 rounded-full 
                            <?= $request['status'] === 'pending' ? 'bg-yellow-400' : 
                                ($request['status'] === 'approved' ? 'bg-green-400' : 
                                'bg-red-400'); ?> mr-2"></span>
                        <?= ucfirst($request['status']) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Info Card -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Information -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Request Information</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Project Name</label>
                            <p class="mt-1 text-gray-800"><?= $request['project_name'] ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Service Type</label>
                            <p class="mt-1 text-gray-800"><?= $request['service_type'] ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Submitted Date</label>
                            <p class="mt-1 text-gray-800"><?= $request['submitted_at'] ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Deadline</label>
                            <p class="mt-1 text-gray-800"><?= $request['deadline'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Description Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Request Details</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Description</label>
                            <p class="mt-1 text-gray-800"><?= $request['request_description'] ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Expectations</label>
                            <p class="mt-1 text-gray-800"><?= $request['expectations'] ?></p>
                        </div>
                        <?php if ($request['additional_notes']): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Additional Notes</label>
                            <p class="mt-1 text-gray-800"><?= $request['additional_notes'] ?></p>
                        </div>
                        <?php endif; ?>
                    </div>

                    
                    <?php if (!empty($attachments)): ?>
                    <div class="mt-4 md:mt-0">
                        <h2 class="text-lg font-semibold text-gray-800">Attachments</h2>
                        <div class="flex space-x-2 mt-2">
                            <?php foreach ($attachments as $attachment): ?>
                                <a href="<?= $attachment['filepath'] ?>" class="text-sm text-blue-600 hover:underline">
                                    <?= basename($attachment['filepath']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Side Info -->
            <div class="space-y-6">
                <!-- Client Info Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Client Information</h2>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-800"><?= $request['fullName'] ?></p>
                                <p class="text-sm text-gray-500"><?= $request['email'] ?></p>
                            </div>
                        </div>
                        <?php if (!empty($request['phoneNumber'])): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Phone Number</label>
                                <p class="mt-1 text-gray-800"><?= htmlspecialchars($request['phoneNumber']) ?></p>
                            </div>
                        <?php endif; ?>


                        <div>
                            <label class="block text-sm font-medium text-gray-500">Preferred Contact</label>
                            <p class="mt-1 text-gray-800 mb-2"><?= $request['contact_method'] ?></p>

                            <label class="block text-sm font-medium text-gray-500">Contact Details</label>
                            <p class="mt-1 text-gray-800"><?= $request['contact_details'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Actions Card -->
                <?php if ($request['status'] === 'pending'): ?>
                <form method="POST" action="#">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Actions</h2>
                        <div class="space-y-3">
                            <button type="submit" name="approve" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <i class="fas fa-check mr-2"></i>
                                Approve Request
                            </button>
                            <button type="button" id="rejectBtn" name="reject" class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-xl shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                <i class="fas fa-times mr-2"></i>
                                Reject Request
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="feedback" id="feedbackInput">
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Task Modal -->
    <div id="taskModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
            
            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="bg-primary text-white px-4 py-3 rounded-t-lg flex justify-between items-center">
                    <h3 class="text-lg font-medium">Insert Task</h3>
                    <button type="button" class="text-white hover:text-gray-200" onclick="document.getElementById('taskModal').classList.add('hidden')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form method="POST" action="insert_task.php" class="p-6">
                    <input type="hidden" name="formID" value="<?php echo $formID; ?>">
                    
                    <div class="mb-4">
                        <label for="taskTitle" class="block text-sm font-medium text-gray-700 mb-1">Task Title</label>
                        <input type="text" id="taskTitle" name="taskTitle" value="<?php echo $request['project_name']; ?>" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="description" name="description" class="w-full px-3 py-2 border rounded-md" required><?= $request['request_description'] ?></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="assignedTo" class="block text-sm font-medium text-gray-700 mb-1">Assigned To</label>
                        <select id="assignedTo" name="assignedTo" class="w-full px-3 py-2 border rounded-md" required>
                            <?php while($row = $adiutorResults->fetch_assoc()): ?>
                                <option value="<?= $row['userID'] ?>"><?= $row['fullName'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <input type="hidden" name="assignedBy" value="<?= $_SESSION['user']['userID'] ?>">
                    <input type="hidden" name="dueDate" value="<?= $request['deadline'] ?>">
                    <input type="hidden" name="dateAssigned" value="<?= date('Y-m-d H:i:s') ?>">
                    
                    <div class="mb-4">
                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                        <select id="priority" name="priority" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                    
                    <div class="mb-6">
                        <label for="taskType" class="block text-sm font-medium text-gray-700 mb-1">Task Type</label>
                        <input type="text" id="taskType" name="taskType" value="<?= $request['service_type'] ?>" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50" required>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="button" class="mr-3 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300"
                            onclick="document.getElementById('taskModal').classList.add('hidden')">
                            Cancel
                        </button>
                        <button id="saveTaskBtn" type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary/90 flex items-center justify-center" style="min-width: 100px;">
                            <svg id="saveTaskSpinner" class="animate-spin h-4 w-4 mr-2 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            <span id="saveTaskBtnText">Save Task</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../components/footer.php'; ?>
    <script>
        // Script to show modal when approve button is clicked
        document.addEventListener('DOMContentLoaded', function() {
            const approveButton = document.querySelector('button[name="approve"]');
            if (approveButton) {
                approveButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Submit the form and then show the modal
                    const form = this.closest('form');
                    const formData = new FormData(form);
                    
                    fetch(form.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        document.getElementById('taskModal').classList.remove('hidden');
                    })
                    .catch(error => console.error('Error:', error));
                });
            }
        });

        // Loading indicator for Save Task button
        document.addEventListener('DOMContentLoaded', function() {
            const taskForm = document.querySelector('#taskModal form');
            if (taskForm) {
                taskForm.addEventListener('submit', function() {
                    const btn = document.getElementById('saveTaskBtn');
                    const spinner = document.getElementById('saveTaskSpinner');
                    const btnText = document.getElementById('saveTaskBtnText');
                    if (btn && spinner && btnText) {
                        btn.setAttribute('disabled', 'disabled');
                        spinner.classList.remove('hidden');
                        btnText.textContent = 'Saving...';
                    }
                });
            }
        });

        // SweetAlert for reject confirmation with feedback
        document.addEventListener('DOMContentLoaded', function() {
            const rejectBtn = document.getElementById('rejectBtn');
            if (rejectBtn) {
                rejectBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Reject Request',
                        input: 'textarea',
                        inputLabel: 'Please provide feedback for rejection',
                        inputPlaceholder: 'Type your feedback here...',
                        inputAttributes: {
                            'aria-label': 'Feedback'
                        },
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Reject',
                        reverseButtons: true,
                        inputValidator: (value) => {
                            if (!value) {
                                return 'Feedback is required!';
                            }
                        }
                    }).then((result) => {
                        if (result.isConfirmed && result.value) {
                            // Set feedback and submit form
                            const form = rejectBtn.closest('form');
                            let feedbackInput = document.getElementById('feedbackInput');
                            if (!feedbackInput) {
                                feedbackInput = document.createElement('input');
                                feedbackInput.type = 'hidden';
                                feedbackInput.name = 'feedback';
                                feedbackInput.id = 'feedbackInput';
                                form.appendChild(feedbackInput);
                            }
                            feedbackInput.value = result.value;

                            // Add hidden reject input if not present
                            let input = form.querySelector('input[name="reject"]');
                            if (!input) {
                                input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'reject';
                                input.value = '1';
                                form.appendChild(input);
                            }
                            form.submit();
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>