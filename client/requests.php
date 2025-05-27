<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'client') {
    header("Location: ../login.php");
    exit;
}

include '../includes/db.php';

// Handle request update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_request'])) {
    $formID = intval($_POST['formID']);
    $project_name = trim($_POST['project_name']);
    $service_type = trim($_POST['service_type']);
    $request_description = trim($_POST['request_description']);
    $deadline = trim($_POST['deadline']);
    $expectations = trim($_POST['expectations']);
    $additional_notes = trim($_POST['additional_notes']);

    // Debugging: Log POST data
    error_log("POST Data: " . print_r($_POST, true));

    // Validate required fields
    if (empty($formID) || empty($project_name) || empty($service_type) || empty($request_description)) {
        header("Location: requests.php?status=error&message=Missing required fields");
        exit;
    }

    // Debugging: Check database connection
    if (!$conn) {
        error_log("Database connection error: " . mysqli_connect_error());
        header("Location: requests.php?status=error&message=Database connection error");
        exit;
    }

    $updateQuery = "
        UPDATE forms 
        SET project_name = ?, service_type = ?, request_description = ?, deadline = ?, expectations = ?, additional_notes = ? 
        WHERE formID = ? AND userID = ?";
    $stmt = $conn->prepare($updateQuery);
    if (!$stmt) {
        error_log("Error preparing statement: " . $conn->error);
        header("Location: requests.php?status=error&message=Error preparing statement");
        exit;
    }

    $stmt->bind_param("ssssssii", $project_name, $service_type, $request_description, $deadline, $expectations, $additional_notes, $formID, $_SESSION['user']['userID']);

    // Debugging: Log query and parameters
    error_log("Executing query: $updateQuery");
    error_log("Parameters: " . json_encode([$project_name, $service_type, $request_description, $deadline, $expectations, $additional_notes, $formID, $_SESSION['user']['userID']]));

    if (!$stmt->execute()) {
        error_log("Error executing query: " . $stmt->error);
        header("Location: requests.php?status=error&message=Error executing query");
        exit;
    }

    // Check if the query affected any rows
    if ($stmt->affected_rows === 0) {
        error_log("No rows were updated. Query: $updateQuery");
        header("Location: requests.php?status=error&message=No changes were made");
        exit;
    }

    // Redirect after successful update
    header("Location: requests.php?status=success&message=Request updated successfully");
    exit;
}

// Pagination for Requests
$requestsPerPage = 5;
$requestPage = isset($_GET['request_page']) ? max(1, intval($_GET['request_page'])) : 1;
$requestOffset = ($requestPage - 1) * $requestsPerPage;

// Fetch data for requests with pagination
$userID = $_SESSION['user']['userID'];
$totalRequestsQuery = "SELECT COUNT(*) AS count FROM forms WHERE userID = ?";
$stmt = $conn->prepare($totalRequestsQuery);
$stmt->bind_param("i", $userID);
$stmt->execute();
$totalRequests = $stmt->get_result()->fetch_assoc()['count'];
$totalRequestPages = ceil($totalRequests / $requestsPerPage);

$requestsQuery = "
    SELECT 
        formID, 
        project_name, 
        service_type, 
        submitted_at, 
        deadline, 
        status,
        userID,
        contact_method,
        contact_details,
        request_description,
        expectations,
        additional_notes
    FROM forms 
    WHERE userID = ? 
    ORDER BY submitted_at DESC 
    LIMIT ? OFFSET ?";
$stmt = $conn->prepare($requestsQuery);
$stmt->bind_param("iii", $userID, $requestsPerPage, $requestOffset);
$stmt->execute();
$requests = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Requests - Treis Adiutor</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
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
        <h2 class="text-xl font-bold text-primary mb-6">Your Requests</h2>
        
        <!-- Display success or error messages -->
        <?php if (isset($_GET['status']) && isset($_GET['message'])): ?>
            <?php 
                $status = htmlspecialchars($_GET['status']);
                $message = htmlspecialchars($_GET['message']);
            ?>
            <div class="alert alert-<?php echo $status; ?> px-4 py-3 rounded-lg shadow-md mb-6 <?php echo $status === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 text-left">
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Request ID</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Project Name</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Service Type</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted Date</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php while ($row = $requests->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $row['formID']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo $row['project_name']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo $row['service_type']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo $row['submitted_at']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo $row['deadline'] ?? 'N/A'; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php 
                                        $statusClass = 'bg-gray-100 text-gray-800'; 
                                        if ($row['status'] === 'pending') {
                                            $statusClass = 'bg-yellow-100 text-yellow-800';
                                        } elseif ($row['status'] === 'approved') {
                                            $statusClass = 'bg-green-100 text-green-800';
                                        } elseif ($row['status'] === 'completed') {
                                            $statusClass = 'bg-blue-100 text-blue-800';
                                        }
                                        echo $statusClass;
                                    ?>">
                                    <?php echo ucwords($row['status'] ?? 'unknown'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm flex gap-2">
                                <button 
                                    onclick='openRequestModal(<?php echo htmlspecialchars(json_encode($row)); ?>, "view")'
                                    class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-accent-700 bg-accent-100 hover:bg-accent-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent-500 transition-all"
                                >
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    View
                                </button>
                                <?php if (isset($row['status']) && $row['status'] !== 'approved'): ?>
                                <button 
                                    onclick='openRequestModal(<?php echo htmlspecialchars(json_encode($row)); ?>, "edit")'
                                    class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-primary-700 bg-primary-100 hover:bg-primary-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all"
                                >
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Edit
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
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

        <div class="mt-6 flex justify-end">
                <a href="../get-started.php" class="inline-flex items-center gap-2 bg-accent text-white px-5 py-2.5 rounded-lg shadow hover:bg-accent-dark transition-colors font-semibold text-base">
                    <i class="fa-solid fa-plus"></i> New Request
                </a>
            </div>
    </section>
    
    <!-- Simplified Modal with Alpine.js -->
    <div x-data="{ 
        isOpen: false, 
        mode: 'view',
        requestData: {},
        loading: false,
        
        init() {
            window.openRequestModal = (data, mode) => {
                this.requestData = data;
                this.mode = mode;
                this.isOpen = true;
                
                // Format deadline date
                if (this.requestData.deadline) {
                    const deadlineDate = new Date(this.requestData.deadline);
                    if (!isNaN(deadlineDate.getTime())) {
                        this.requestData.deadline = deadlineDate.toISOString().split('T')[0];
                    }
                }
            };
        },
        
        closeModal() {
            this.isOpen = false;
        },
        
        saveChanges() {
            this.loading = true;
            // Ensure the form is submitted
            if (this.mode === 'edit') {
                const form = document.getElementById('editRequestForm');
                if (form) {
                    form.submit();
                } else {
                    console.error('Edit request form not found.');
                }
            }
        }
    }" 
    x-show="isOpen" 
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform scale-95"
    x-transition:enter-end="opacity-100 transform scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-95"
    class="fixed inset-0 z-50 overflow-y-auto" 
    aria-labelledby="modal-title" 
    role="dialog" 
    aria-modal="true">

        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isOpen" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div x-show="isOpen" 
                 class="inline-block w-full max-w-2xl px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:p-6">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium leading-6 text-primary" id="modal-title">
                        <span x-text="mode === 'view' ? 'Request Details' : 'Edit Request'"></span>
                        <span class="ml-2 text-sm text-gray-500" x-text="'#' + requestData.formID"></span>
                    </h3>
                    <button @click="closeModal()" type="button" class="text-gray-400 bg-white rounded-md hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <span class="sr-only">Close</span>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <!-- Modal Content -->
                <div class="my-6 space-y-4">
                    <!-- View Mode -->
                    <div x-show="mode === 'view'" class="space-y-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Project Name</p>
                            <p class="mt-1 text-sm text-gray-900" x-text="requestData.project_name"></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Service Type</p>
                            <p class="mt-1 text-sm text-gray-900" x-text="requestData.service_type"></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Request Description</p>
                            <p class="mt-1 text-sm text-gray-900 whitespace-pre-line" x-text="requestData.request_description"></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Deadline</p>
                            <p class="mt-1 text-sm text-gray-900" x-text="requestData.deadline || 'Not specified'"></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Expectations</p>
                            <p class="mt-1 text-sm text-gray-900 whitespace-pre-line" x-text="requestData.expectations || 'None specified'"></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Additional Notes</p>
                            <p class="mt-1 text-sm text-gray-900 whitespace-pre-line" x-text="requestData.additional_notes || 'None provided'"></p>
                        </div>
                    </div>
                    
                    <!-- Edit Mode -->
                    <form id="editRequestForm" x-show="mode === 'edit'" method="post" class="space-y-4">
                        <input type="hidden" name="update_request" value="1">
                        <input type="hidden" name="formID" x-model="requestData.formID">
                        <div>
                            <label for="project_name" class="block text-sm font-medium text-gray-700">Project Name</label>
                            <input type="text" name="project_name" id="project_name" x-model="requestData.project_name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="service_type" class="block text-sm font-medium text-gray-700">Service Type</label>
                            <input type="text" name="service_type" id="service_type" x-model="requestData.service_type" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">                            
                        </div>
                        <div>
                            <label for="request_description" class="block text-sm font-medium text-gray-700">Request Description</label>
                            <textarea name="request_description" id="request_description" rows="4" x-model="requestData.request_description" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"></textarea>
                        </div>
                        <div>
                            <label for="deadline" class="block text-sm font-medium text-gray-700">Deadline</label>
                            <input type="date" name="deadline" id="deadline" x-model="requestData.deadline" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="expectations" class="block text-sm font-medium text-gray-700">Expectations</label>
                            <textarea name="expectations" id="expectations" rows="3" x-model="requestData.expectations" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"></textarea>
                        </div>
                        <div>
                            <label for="additional_notes" class="block text-sm font-medium text-gray-700">Additional Notes</label>
                            <textarea name="additional_notes" id="additional_notes" rows="3" x-model="requestData.additional_notes" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"></textarea>
                        </div>
                    </form>
                </div>
                
                <!-- Modal Footer -->
                <div class="mt-5 sm:mt-6 pt-4 border-t border-gray-200">
                    <div class="flex justify-end space-x-3">
                        <button @click="closeModal()" type="button" class="inline-flex justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            Close
                        </button>
                        <button x-show="mode === 'edit'" 
                                @click="saveChanges()" 
                                type="button" 
                                class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-primary border border-transparent rounded-md shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50">
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../components/footer.php'; ?>
</body>
</html>