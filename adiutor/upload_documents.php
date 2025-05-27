<?php
session_start();
include '../auth/auth.php';
include '../includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'adiutor') {
    header("Location: ../login.php");
    exit;
}

// Confirm taskID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "No task ID provided.";
    exit;
}

$taskID = intval($_GET['id']);

// Prepare and execute query
$stmt = $conn->prepare("SELECT * FROM tasks WHERE taskID = ?");
$stmt->bind_param("i", $taskID); // FIXED typo here
$stmt->execute();
$result = $stmt->get_result();

$task = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle file upload
    if (isset($_FILES['docs']) && is_array($_FILES['docs']['name'])) { // use 'docs' not 'file_upload'
        $uploadDir = 'documents/user-uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        foreach ($_FILES['docs']['name'] as $idx => $name) {
            if ($_FILES['docs']['error'][$idx] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['docs']['tmp_name'][$idx];
                $baseName = basename($name);
                $uniqueName = uniqid() . '_' . $baseName;
                $targetPath = '/ta-cms/adiutor/' . $uploadDir . $uniqueName; // absolute path for DB
                $serverPath = $uploadDir . $uniqueName; // relative path for move_uploaded_file
                move_uploaded_file($tmpName, $serverPath);

                // Insert into documents table
                $fileName = $targetPath; // store absolute path in DB
                $stmt = $conn->prepare("INSERT INTO documents (taskID, fileName, filePath) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $taskID, $baseName, $fileName);
                $stmt->execute();
                $stmt->close();
            }
        }
        $success_message = "Documents uploaded successfully.";
    } else {
        $error_message = "No files selected.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Upload Documents</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
<body class="bg-gray-50 text-gray-800 min-h-screen">
    <?php include 'components/navbar.php'; ?>

    <div class="container mx-auto px-4 py-10 max-w-5xl pt-24">
        <h1 class="text-3xl font-bold text-primary mb-6">Upload Documents for Task #<?php echo htmlspecialchars($taskID); ?></h1>

        <!-- Alerts -->
        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg shadow-sm flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <?php echo $success_message; ?>
            </div>
            <div class="flex flex-col items-center mt-2 text-sm text-gray-600">
                <p>Redirecting in <span id="countdown">5</span> seconds...</p>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                    <div id="progressBar" class="bg-green-500 h-2 rounded-full" style="width: 0%;"></div>
                </div>
            </div>
            <script>
            let timeLeft = 5;
            let countdownElement = document.getElementById('countdown');
            let progressBarElement = document.getElementById('progressBar');
            let intervalId = setInterval(() => {
                timeLeft--;
                countdownElement.textContent = timeLeft;
                progressBarElement.style.width = ((5 - timeLeft) / 5 * 100) + '%';
                if (timeLeft <= 0) {
                    clearInterval(intervalId);
                    window.location.href = "view_task.php?id=<?php echo $taskID; ?>";
                }
            }, 1000);
            </script>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-sm flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Upload Form -->
        <form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow-md p-6 border border-gray-100">
            
    <!-- File Uploads -->
    <div class="space-y-4">
        <h3 class="text-xl font-semibold text-primary pb-2 border-b border-gray-200">File Uploads (Optional)</h3>        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Upload any related documents, screenshots, drafts, or files that are relevant to the task.</label>
            <div class="flex items-center justify-center w-full">
                <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <svg class="w-8 h-8 mb-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                        <p class="text-xs text-gray-500">Any file format (MAX. 10MB each)</p>
                    </div>
                    <input type="file" name="docs[]" class="hidden" multiple id="docsInput">
                </label>
            </div>
            <!-- Add this div to display selected file names -->
            <div id="selectedFiles" class="mt-2 text-sm text-gray-600"></div>
        </div>
    </div>

            <button type="submit" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg transition-colors duration-200 mt-4">
                Upload
            </button>
        </form>
    </div>

    <?php include 'components/footer.php'; ?>
    <!-- Add this script before closing body tag -->
    <script>
    document.getElementById('docsInput').addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        const fileList = files.map(f => `<li>${f.name}</li>`).join('');
        document.getElementById('selectedFiles').innerHTML = files.length
            ? `<ul class="list-disc pl-5">${fileList}</ul>`
            : '';
    });
    </script>
</body>
</html>