<?php
session_start();
include '../auth/auth.php';
include '../includes/db.php';

// Check if user is logged in and is an adiutor
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$user = $_SESSION['user'];

// Set up pagination
$recordsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

// Set up filtering options
$filterRating = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$filterDate = isset($_GET['date']) ? $_GET['date'] : '';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';

// Build the query without adiutorId filter
$query = "SELECT f.*, s.fullName as sender_name, t.title as task_title,
          (SELECT COUNT(*) FROM featuredfeedback ff WHERE ff.feedbackID = f.id AND ff.isActive = 1) as is_featured
          FROM feedbacks f
          LEFT JOIN users s ON f.giver_id = s.userID
          LEFT JOIN tasks t ON f.task_id = t.taskID";

// Add filtering conditions
if ($filterRating > 0) {
    $query .= " WHERE f.rating = $filterRating";
}

if ($filterDate) {
    $query .= ($filterRating > 0 ? " AND" : " WHERE") . " DATE(f.created_at) = '$filterDate'";
}

// Add sorting
switch ($sortBy) {
    case 'rating_asc':
        $query .= " ORDER BY f.rating ASC";
        break;
    case 'rating_desc':
        $query .= " ORDER BY f.rating DESC";
        break;
    case 'date_asc':
        $query .= " ORDER BY f.created_at ASC";
        break;
    case 'date_desc':
    default:
        $query .= " ORDER BY f.created_at DESC";
        break;
}

// Get total records for pagination
$countQuery = str_replace("SELECT f.*, s.fullName as sender_name, t.title as task_title, (SELECT COUNT(*) FROM featuredfeedback ff WHERE ff.feedbackID = f.id AND ff.isActive = 1) as is_featured", "SELECT COUNT(*)", $query);
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_row()[0];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Add pagination limit
$query .= " LIMIT $offset, $recordsPerPage";

// Execute the query
$result = $conn->query($query);
$feedbacks = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $feedbacks[] = $row;
    }
}

// Calculate average rating
$avgRatingQuery = "SELECT AVG(rating) as avg_rating FROM feedbacks";
$avgRatingResult = $conn->query($avgRatingQuery);
$avgRating = 0;
if ($avgRatingResult && $row = $avgRatingResult->fetch_assoc()) {
    $avgRating = $row['avg_rating'] ? round($row['avg_rating'], 1) : 0;
}

// Get rating distribution
$ratingDistribution = [];
$ratingQuery = "SELECT rating, COUNT(*) as count FROM feedbacks GROUP BY rating ORDER BY rating DESC";
$ratingResult = $conn->query($ratingQuery);
if ($ratingResult) {
    while ($row = $ratingResult->fetch_assoc()) {
        $ratingDistribution[$row['rating']] = $row['count'];
    }
}

// Fill in missing ratings with 0
for ($i = 5; $i >= 1; $i--) {
    if (!isset($ratingDistribution[$i])) {
        $ratingDistribution[$i] = 0;
    }
}

// Function to display star rating
function displayStarRating($rating) {
    $output = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $output .= '<i class="fas fa-star text-yellow-400"></i>';
        } elseif ($i - 0.5 <= $rating) {
            $output .= '<i class="fas fa-star-half-alt text-yellow-400"></i>';
        } else {
            $output .= '<i class="far fa-star text-yellow-400"></i>';
        }
    }
    return $output;
}

// Function to format date
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Treis Adiutor</title>
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

        function starFeedback(feedbackId) {
            Swal.fire({
                title: 'Feature this feedback?',
                text: "This feedback will be shown on the main page",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3a5a78',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, feature it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Check current featured count
                    fetch('feature_feedback.php?check=true')
                    .then(response => response.json())
                    .then(data => {
                        if (data.count >= 3) {
                            return Swal.fire({
                                title: 'Maximum Featured Reached',
                                text: 'There are already 3 featured feedbacks. Do you want to remove the oldest and add this one?',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#3a5a78',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'Yes, replace oldest'
                            });
                        }
                        return { isConfirmed: true };
                    })
                    .then(result => {
                        if (result.isConfirmed) {
                            return fetch(`feature_feedback.php?id=${feedbackId}`);
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire(
                                'Featured!',
                                'The feedback has been featured.',
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        }
                    });
                }
            });
        }

        function unstarFeedback(feedbackId) {
            Swal.fire({
                title: 'Remove from Featured?',
                text: "This feedback will be removed from the main page",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3a5a78',
                confirmButtonText: 'Yes, remove it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`feature_feedback.php?remove=${feedbackId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire(
                                'Removed!',
                                'The feedback has been removed from featured.',
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        }
                    });
                }
            });
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
<?php include 'components/navbar.php'; ?>

<!-- Main Content -->
<main class="flex-grow container mx-auto px-4 py-8 pt-24">
    <h1 class="text-3xl font-bold text-primary mb-2">Feedback Overview</h1>
    <p class="text-gray-600 mb-8">View all feedback received from your clients</p>

    <!-- Rating Summary -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Overall Rating -->
            <div class="text-center">
                <h2 class="text-lg font-semibold text-gray-700 mb-2">Overall Rating</h2>
                <div class="text-5xl font-bold text-gray-800"><?php echo number_format($avgRating, 1); ?></div>
                <div class="text-2xl my-2">
                    <?php echo displayStarRating($avgRating); ?>
                </div>
                <p class="text-gray-500">Based on <?php echo $totalRecords; ?> reviews</p>
            </div>

            <!-- Rating Distribution -->
            <div class="col-span-2">
                <h2 class="text-lg font-semibold text-gray-700 mb-2">Rating Distribution</h2>
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <?php 
                    $count = $ratingDistribution[$i];
                    $percentage = $totalRecords > 0 ? ($count / $totalRecords) * 100 : 0;
                    ?>
                    <div class="flex items-center mb-2">
                        <div class="w-12 text-sm text-gray-600 font-medium">
                            <?php echo $i; ?> stars
                        </div>
                        <div class="mx-4 flex-1">
                            <div class="bg-gray-200 h-2.5 rounded-full overflow-hidden">
                                <div class="bg-yellow-400 h-2.5" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                        <div class="w-12 text-sm text-gray-600 font-medium text-right">
                            <?php echo $count; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Filter Feedback</h2>
        <form action="" method="get" class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Rating Filter -->
            <div>
                <label for="rating" class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                <select name="rating" id="rating" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                    <option value="0" <?php if ($filterRating == 0) echo 'selected'; ?>>All Ratings</option>
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <option value="<?php echo $i; ?>" <?php if ($filterRating == $i) echo 'selected'; ?>>
                            <?php echo $i; ?> Stars
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Date Filter -->
            <div>
                <label for="date" class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                <input type="date" name="date" id="date" value="<?php echo $filterDate; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
            </div>

            <!-- Sort By -->
            <div>
                <label for="sort" class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                <select name="sort" id="sort" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                    <option value="date_desc" <?php if ($sortBy == 'date_desc') echo 'selected'; ?>>Newest First</option>
                    <option value="date_asc" <?php if ($sortBy == 'date_asc') echo 'selected'; ?>>Oldest First</option>
                    <option value="rating_desc" <?php if ($sortBy == 'rating_desc') echo 'selected'; ?>>Highest Rating</option>
                    <option value="rating_asc" <?php if ($sortBy == 'rating_asc') echo 'selected'; ?>>Lowest Rating</option>
                </select>
            </div>

            <div class="md:col-span-3 flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary text-white font-medium rounded-md hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    <i class="fas fa-filter mr-2"></i> Apply Filters
                </button>
                <a href="feedbacks.php" class="ml-2 inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    <i class="fas fa-times mr-2"></i> Clear Filters
                </a>
            </div>
        </form>
    </div>

    <!-- Feedback List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
        <div class="border-b px-6 py-4">
            <h2 class="font-bold text-lg text-primary">Feedback History</h2>
        </div>
        <?php if (count($feedbacks) > 0): ?>
            <div class="divide-y divide-gray-200">
                <?php foreach ($feedbacks as $feedback): ?>
                    <div class="p-6 hover:bg-gray-50">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <div class="text-lg font-medium text-gray-800 mr-2">
                                    <?php echo htmlspecialchars($feedback['sender_name']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    • <?php echo formatDate($feedback['created_at']); ?>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <?php if (!$feedback['is_featured']): ?>
                                    <button onclick="starFeedback(<?php echo $feedback['id']; ?>)" 
                                            class="text-gray-400 hover:text-yellow-400 transition-colors">
                                        <i class="far fa-star"></i>
                                    </button>
                                <?php else: ?>
                                    <span onclick="unstarFeedback(<?php echo $feedback['id']; ?>)" 
                                          class="text-yellow-400 cursor-pointer hover:text-yellow-300">
                                        <i class="fas fa-star"></i>
                                    </span>
                                <?php endif; ?>
                                <div>
                                    <?php echo displayStarRating($feedback['rating']); ?>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($feedback['task_title'])): ?>
                        <div class="text-sm text-gray-600 mb-2">
                            <span class="font-medium">Task:</span> <?php echo htmlspecialchars($feedback['task_title']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="text-gray-700">
                            <?php echo nl2br(htmlspecialchars($feedback['comment'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $recordsPerPage, $totalRecords); ?> of <?php echo $totalRecords; ?> feedbacks
                        </div>
                        <div class="flex space-x-1">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&rating=<?php echo $filterRating; ?>&date=<?php echo $filterDate; ?>&sort=<?php echo $sortBy; ?>" class="px-3 py-1 text-gray-600 rounded hover:bg-gray-100">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            if ($startPage > 1) {
                                echo '<a href="?page=1&rating=' . $filterRating . '&date=' . $filterDate . '&sort=' . $sortBy . '" class="px-3 py-1 text-gray-600 rounded hover:bg-gray-100">1</a>';
                                if ($startPage > 2) {
                                    echo '<span class="px-3 py-1">...</span>';
                                }
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++) {
                                if ($i == $page) {
                                    echo '<span class="px-3 py-1 bg-primary text-white rounded">' . $i . '</span>';
                                } else {
                                    echo '<a href="?page=' . $i . '&rating=' . $filterRating . '&date=' . $filterDate . '&sort=' . $sortBy . '" class="px-3 py-1 text-gray-600 rounded hover:bg-gray-100">' . $i . '</a>';
                                }
                            }
                            
                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) {
                                    echo '<span class="px-3 py-1">...</span>';
                                }
                                echo '<a href="?page=' . $totalPages . '&rating=' . $filterRating . '&date=' . $filterDate . '&sort=' . $sortBy . '" class="px-3 py-1 text-gray-600 rounded hover:bg-gray-100">' . $totalPages . '</a>';
                            }
                            ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&rating=<?php echo $filterRating; ?>&date=<?php echo $filterDate; ?>&sort=<?php echo $sortBy; ?>" class="px-3 py-1 text-gray-600 rounded hover:bg-gray-100">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="p-6 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 text-gray-400 mb-4">
                    <i class="fas fa-comment-slash text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-1">No feedback found</h3>
                <p class="text-gray-500">
                    <?php if ($filterRating || $filterDate): ?>
                        No feedback matches your current filters. Try adjusting your filters to see more results.
                    <?php else: ?>
                        You haven't received any feedback yet.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php include 'components/footer.php'; ?>
</body>
</html>
