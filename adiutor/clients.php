<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Clients - Treis Adiutor</title>
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

<?php
// Include necessary files and start session
require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo "You must be logged in to give feedback.";
    exit;
}


$user = $_SESSION['user'];
$adiutor_id = $user['userID'];
$clients = [];
$client_stats = [];

// Step 1: Get all form IDs from tasks assigned to this user
$form_ids_query = "SELECT DISTINCT formID FROM tasks WHERE assignedTo = ?";
$form_stmt = $conn->prepare($form_ids_query);
$form_stmt->bind_param("i", $adiutor_id);
$form_stmt->execute();
$form_result = $form_stmt->get_result();

$form_ids = [];
while ($row = $form_result->fetch_assoc()) {
    $form_ids[] = $row['formID'];
}

// Step 2: If there are form IDs, get client IDs from those forms
if (!empty($form_ids)) {
    $placeholders = str_repeat('?,', count($form_ids) - 1) . '?';
    $client_ids_query = "SELECT DISTINCT userID FROM forms WHERE formID IN ($placeholders)";
    
    $client_stmt = $conn->prepare($client_ids_query);
    
    // Create the types string for bind_param (all integers)
    $types = str_repeat('i', count($form_ids));
    
    // Create an array with references to bind
    $bind_params = array($types);
    foreach ($form_ids as $key => $id) {
        $bind_params[] = &$form_ids[$key];
    }
    
    // Call bind_param with the array unpacked
    call_user_func_array(array($client_stmt, 'bind_param'), $bind_params);
    
    $client_stmt->execute();
    $client_result = $client_stmt->get_result();
    
    $client_ids = [];
    while ($row = $client_result->fetch_assoc()) {
        $client_ids[] = $row['userID'];
    }
    
    // Step 3: Get client data for each client ID
    if (!empty($client_ids)) {
        $client_placeholders = str_repeat('?,', count($client_ids) - 1) . '?';
        $clients_query = "SELECT * FROM users WHERE userID IN ($client_placeholders)";
        
        $clients_stmt = $conn->prepare($clients_query);
        
        // Create the types string for bind_param (all integers)
        $types = str_repeat('i', count($client_ids));
        
        // Create an array with references to bind
        $bind_params = array($types);
        foreach ($client_ids as $key => $id) {
            $bind_params[] = &$client_ids[$key];
        }
        
        // Call bind_param with the array unpacked
        call_user_func_array(array($clients_stmt, 'bind_param'), $bind_params);
        
        $clients_stmt->execute();
        $clients_result = $clients_stmt->get_result();
        
        while ($client = $clients_result->fetch_assoc()) {
            $clients[] = $client;
            
            // Step 4: Get average rating for this client
            $rating_query = "SELECT AVG(rating) as avg_rating FROM feedbacks WHERE receiver_id = ? AND giver_id = ?";
            $rating_stmt = $conn->prepare($rating_query);
            $rating_stmt->bind_param("ii", $adiutor_id, $client['userID']);
            $rating_stmt->execute();
            $rating_result = $rating_stmt->get_result();
            $rating_data = $rating_result->fetch_assoc();
            
            // Step 5: Get count of tasks for this client
            $task_query = "SELECT COUNT(*) as task_count FROM tasks t 
                          JOIN forms f ON t.formID = f.formID 
                          WHERE f.userID = ? AND t.assignedTo = ?";
            $task_stmt = $conn->prepare($task_query);
            $task_stmt->bind_param("ii", $client['userID'], $adiutor_id);
            $task_stmt->execute();
            $task_result = $task_stmt->get_result();
            $task_data = $task_result->fetch_assoc();
            
            // Store the client statistics
            $client_stats[$client['userID']] = [
                'avg_rating' => $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : 'N/A',
                'task_count' => $task_data['task_count']
            ];
        }
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-primary mb-4 md:mb-0">My Clients</h1>
        
        <div class="relative w-full md:w-64">
            <input type="text" id="clientSearch" placeholder="Search clients..." 
                class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
            <div class="absolute left-3 top-2.5 text-gray-400">
                <i class="fas fa-search"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <?php if (empty($clients)): ?>
        <div class="flex items-center bg-blue-50 p-4 rounded-lg text-primary">
            <i class="fas fa-info-circle mr-3 text-lg"></i>
            <p>You haven't served any clients yet.</p>
        </div>
        <?php else: ?>
        <div id="clientGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($clients as $client): ?>
            <div class="client-card bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow duration-300">
                <div class="bg-primary p-4 flex items-center">
                    <?php 
                    $name_parts = explode(' ', trim($client['fullName']));
                    $initials = '';
                    foreach ($name_parts as $part) {
                        if (!empty($part)) {
                            $initials .= strtoupper(substr($part, 0, 1));
                        }
                    }
                    $initials = substr($initials, 0, 2); // Limit to first two initials
                    $color = stringToColor($client['userID']); 
                    ?>
                    <div class="flex-shrink-0 mr-4">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center text-xl font-bold text-white" 
                            style="background-color: <?php echo $color; ?>">
                            <?php echo $initials; ?>
                        </div>
                    </div>
                    <div class="text-white">
                        <h3 class="text-lg font-semibold"><?php echo $client['fullName']; ?></h3>
                        <p class="text-secondary-100 text-[10px]"><?php echo $client['email']; ?></p>
                    </div>
                </div>
                
                <div class="p-4">
                    <div class="flex items-center mb-2">
                        <div class="w-5 text-gray-500">
                            <i class="fas fa-phone"></i>
                        </div>
                        <span class="ml-2"><?php echo !empty($client['phone']) ? $client['phone'] : 'N/A'; ?></span>
                    </div>
                    
                    <div class="flex items-center mb-2">
                        <div class="w-5 text-gray-500">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <span class="ml-2">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-secondary text-white">
                                <?php echo isset($client_stats[$client['userID']]) ? $client_stats[$client['userID']]['task_count'] : '0'; ?> tasks
                            </span>
                        </span>
                    </div>
                    
                    <div class="flex items-center mb-4">
                        <div class="w-5 text-gray-500">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="ml-2">
                            <?php 
                            $rating = isset($client_stats[$client['userID']]) ? $client_stats[$client['userID']]['avg_rating'] : 'N/A';
                            if ($rating != 'N/A') {
                                echo '<div class="flex items-center">';
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo '<i class="fas fa-star text-accent"></i>';
                                    } elseif ($i - 0.5 <= $rating) {
                                        echo '<i class="fas fa-star-half-alt text-accent"></i>';
                                    } else {
                                        echo '<i class="far fa-star text-accent"></i>';
                                    }
                                }
                                echo '<span class="ml-2 text-sm text-gray-600">(' . $rating . ')</span></div>';
                            } else {
                                echo 'No ratings yet';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <button type="button" class="view-client w-full py-2 bg-secondary hover:bg-primary text-white rounded-lg transition-colors duration-300 flex items-center justify-center" 
                        data-id="<?php echo $client['userID']; ?>">
                        <i class="fas fa-eye mr-2"></i> View Details
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Client Details Modal -->
<div class="fixed inset-0 flex items-center justify-center z-50 hidden" id="clientDetailsModal">
    <div class="fixed inset-0 bg-black opacity-50" id="modalOverlay"></div>
    <div class="bg-white w-11/12 md:w-3/4 lg:w-2/3 xl:w-1/2 rounded-lg shadow-lg relative z-10 max-h-90vh overflow-y-auto">
        <div class="border-b border-gray-200 p-4 flex justify-between items-center">
            <h5 class="text-xl font-semibold text-primary">Client Details</h5>
            <button type="button" class="text-gray-500 hover:text-gray-700" id="closeModal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6" id="clientDetailsContent">
            <div class="flex justify-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
            </div>
        </div>
        <div class="border-t border-gray-200 p-4 flex justify-end">
            <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg" id="closeModalBtn">Close</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Client search functionality
    document.getElementById('clientSearch').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const cards = document.querySelectorAll('.client-card');
        
        cards.forEach(card => {
            const clientName = card.querySelector('h3').textContent.toLowerCase();
            const clientEmail = card.querySelector('p').textContent.toLowerCase();
            
            if (clientName.includes(searchValue) || clientEmail.includes(searchValue)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
    
    // Modal functionality
    const modal = document.getElementById('clientDetailsModal');
    const modalOverlay = document.getElementById('modalOverlay');
    const closeModal = document.getElementById('closeModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    
    function hideModal() {
        modal.classList.add('hidden');
    }
    
    if (closeModal) closeModal.addEventListener('click', hideModal);
    if (closeModalBtn) closeModalBtn.addEventListener('click', hideModal);
    if (modalOverlay) modalOverlay.addEventListener('click', hideModal);
    
    // View client details
    document.querySelectorAll('.view-client').forEach(button => {
        button.addEventListener('click', function() {
            const clientId = this.getAttribute('data-id');
            modal.classList.remove('hidden');
            
            // Set loading state
            document.getElementById('clientDetailsContent').innerHTML = 
                '<div class="flex justify-center"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div></div>';
            
            // Load client details via AJAX
            fetch('ajax/get_client_details.php?client_id=' + clientId + '&adiutor_id=' + <?php echo $adiutor_id; ?>)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('clientDetailsContent').innerHTML = data.html;
                    } else {
                        document.getElementById('clientDetailsContent').innerHTML = 
                            '<div class="bg-red-50 text-red-700 p-4 rounded-lg"><i class="fas fa-exclamation-circle mr-2"></i>Error loading client details.</div>';
                    }
                })
                .catch(error => {
                    document.getElementById('clientDetailsContent').innerHTML = 
                        `<div class="bg-red-50 text-red-700 p-4 rounded-lg"><i class="fas fa-exclamation-circle mr-2"></i>An error occurred: ${error.message}</div>`;
                });
        });
    });
});

// Function to generate color from string (for client avatar)
<?php
function stringToColor($str) {
    $str = (string) $str; // Ensure $str is always treated as a string
    // Simple hash function to get a consistent color for the same string
    $hash = 0;
    for ($i = 0; $i < strlen($str); $i++) {
        $hash = ord($str[$i]) + (($hash << 5) - $hash);
    }
    
    // Convert hash to RGB value
    $h = $hash % 360;
    $s = 65;
    $l = 40;
    
    // Convert HSL to RGB
    $c = (1 - abs(2 * $l / 100 - 1)) * $s / 100;
    $x = $c * (1 - abs(fmod(($h / 60), 2) - 1));
    $m = $l / 100 - $c / 2;
    
    if ($h < 60) {
        $r = $c; $g = $x; $b = 0;
    } else if ($h < 120) {
        $r = $x; $g = $c; $b = 0;
    } else if ($h < 180) {
        $r = 0; $g = $c; $b = $x;
    } else if ($h < 240) {
        $r = 0; $g = $x; $b = $c;
    } else if ($h < 300) {
        $r = $x; $g = 0; $b = $c;
    } else {
        $r = $c; $g = 0; $b = $x;
    }
    
    $r = floor(($r + $m) * 255);
    $g = floor(($g + $m) * 255);
    $b = floor(($b + $m) * 255);
    
    return "rgb($r, $g, $b)";
}
?>
</script>

<?php require_once 'components/footer.php'; ?>
