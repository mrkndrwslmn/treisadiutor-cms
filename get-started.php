<?php
session_start();
$isLoggedIn = isset($_SESSION['user']);
$userEmail = '';
$userFullName = '';
$userId = null;

if ($isLoggedIn) {
    $userId = is_array($_SESSION['user']) ? $_SESSION['user']['userID'] : $_SESSION['user'];

    $conn = new mysqli('localhost', 'root', '', 'ta_cms'); // Adjust DB credentials as needed
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT fullName, email FROM users WHERE userID = ?");
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $stmt->bind_result($userFullName, $userEmail);
    $stmt->fetch();
    $stmt->close();
    $conn->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contactMethod = $_POST['contact_method'];
    $contactDetails = $_POST['contact_details'];
    $serviceType = $_POST['service_type'];
    $projectName = $_POST['project_name'];
    $requestDescription = $_POST['request_description'];
    $deadline = $_POST['deadline'];
    $expectations = $_POST['expectations'];
    $additionalNotes = $_POST['additional_notes'];

    $conn = new mysqli('localhost', 'root', '', 'ta_cms'); // Adjust DB credentials as needed
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // If the user is not logged in, create a new user
    if (!$isLoggedIn) {
        $fullName = $_POST['full_name'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash the password

        $stmt = $conn->prepare("INSERT INTO users (fullName, email, password, role, status, dateCreated) VALUES (?, ?, ?, 'client', 'active', NOW())");
        $stmt->bind_param("sss", $fullName, $email, $password);
        try {
            $stmt->execute();
            $userId = $stmt->insert_id; // Get the newly created user ID
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) { // Duplicate entry error code
                // Email already exists, fetch userID
                $stmt->close();
                $lookup = $conn->prepare("SELECT userID FROM users WHERE email = ?");
                $lookup->bind_param("s", $email);
                $lookup->execute();
                $lookup->bind_result($userId);
                $lookup->fetch();
                $lookup->close();
            } else {
                $stmt->close();
                throw $e;
            }
        }
    }

    // Insert form data into the forms table (without file info)
    $stmt = $conn->prepare("INSERT INTO forms (userID, contact_method, contact_details, service_type, project_name, request_description, deadline, expectations, additional_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssss", $userId, $contactMethod, $contactDetails, $serviceType, $projectName, $requestDescription, $deadline, $expectations, $additionalNotes);
    $stmt->execute();
    $formId = $stmt->insert_id; // Get the newly created form ID
    $stmt->close();

    // Handle multiple file uploads and save to form_files table
    if (isset($_FILES['file_upload']) && is_array($_FILES['file_upload']['name'])) {
        $uploadDir = __DIR__ . '/documents/user-uploads/'; // Ensure absolute path
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
                die("Failed to create upload directory.");
            }
        }
        foreach ($_FILES['file_upload']['name'] as $idx => $name) {
            if ($_FILES['file_upload']['error'][$idx] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['file_upload']['tmp_name'][$idx];
                $baseName = basename($name);
                $uniqueName = uniqid() . '_' . $baseName;
                $targetPath = $uploadDir . $uniqueName;
                $webPath = '/ta-cms/documents/user-uploads/' . $uniqueName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    // Insert into form_files table
                    $stmt = $conn->prepare("INSERT INTO form_files (formid, filepath, date_uploaded) VALUES (?, ?, NOW())");
                    $stmt->bind_param("is", $formId, $webPath);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    die("Failed to move uploaded file: " . htmlspecialchars($name));
                }
            }
        }
    }

    $conn->close();

    // Redirect or show a success message
    header("Location: success.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Treis Adiutor - Professional Support Services</title>
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
<body class="font-sans text-gray-800">
    <!-- Header -->
    <?php include 'components/navbar.php'; ?>

    <div class="container mx-auto px-4 pt-32 pb-20">
        
        <!-- Request Form -->
        <section class="space-y-4">
            <h2 class="text-2xl font-bold">Request Form</h2>
            <p>Please fill out the form below so we can understand your needs and get in touch with the right support for you.</p>
            <form class="bg-white rounded-lg shadow-md p-6 space-y-8 max-w-3xl mx-auto" method="POST" enctype="multipart/form-data">
    <!-- Personal Information -->
    <div class="space-y-4">
        <h3 class="text-xl font-semibold text-primary pb-2 border-b border-gray-200">Personal Information</h3>
        
        <?php if (!$isLoggedIn): ?>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name:</label>
                    <input type="text" name="full_name" class="w-full px-4 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address:</label>
                    <input type="email" name="email" class="w-full px-4 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password:</label>
                    <input type="password" name="password" class="w-full px-4 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" required>
                    <p class="text-xs text-gray-500 mt-1 italic">Create a password to access your account and track request progress</p>
                </div>
            </div>
        <?php else: ?>
            <input type="hidden" name="full_name" value="<?php echo htmlspecialchars($userFullName); ?>">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($userEmail); ?>">
            <div class="bg-gray-50 p-4 rounded-md">
                <p class="mb-2"><span class="font-medium text-gray-700">Full Name:</span> <?php echo htmlspecialchars($userFullName); ?></p>
                <p><span class="font-medium text-gray-700">Email Address:</span> <?php echo htmlspecialchars($userEmail); ?></p>
            </div>
        <?php endif; ?>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Contact Method:</label>
            <input type="text" name="contact_method" class="w-full px-4 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" placeholder="Email / Messenger / WhatsApp / Others">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Details / Handle:</label>
            <input type="text" name="contact_details" class="w-full px-4 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" placeholder="e.g., username or phone number">
        </div>
    </div>

    <!-- Request Overview -->
    <div class="space-y-4">
        <h3 class="text-xl font-semibold text-primary pb-2 border-b border-gray-200">Request Overview</h3>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Service Type:</label>
            <input type="text" name="service_type" class="w-full px-4 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" placeholder="e.g., Academic Writing, Programming Help, etc.">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Project / Task Name (if any):</label>
            <input type="text" name="project_name" class="w-full px-4 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Brief Description of Your Request:</label>
            <textarea name="request_description" class="w-full px-4 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition min-h-[100px]" placeholder="What do you need help with?"></textarea>
        </div>
    </div>

    <!-- Timeline & Deliverables -->
    <div class="space-y-4">
        <h3 class="text-xl font-semibold text-primary pb-2 border-b border-gray-200">Timeline & Deliverables</h3>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Deadline or Preferred Completion Date:</label>
            <input type="date" name="deadline" class="w-full px-4 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">What are you expecting from us?</label>
            <textarea name="expectations" class="w-full px-4 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition min-h-[100px]" placeholder="Guidance, proofreading, feedback, etc."></textarea>
        </div>
    </div>

    <!-- File Uploads -->
    <div class="space-y-4">
        <h3 class="text-xl font-semibold text-primary pb-2 border-b border-gray-200">File Uploads (Optional)</h3>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Upload any related documents, screenshots, drafts, or files:</label>
            <div class="flex items-center justify-center w-full">
                <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <svg class="w-8 h-8 mb-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                        <p class="text-xs text-gray-500">Any file format (MAX. 10MB each)</p>
                    </div>
                    <input type="file" name="file_upload[]" class="hidden" multiple onchange="displaySelectedFiles(this)">
                </label>
            </div>
            <ul id="file-list" class="mt-2 text-sm text-gray-600"></ul>
            <p id="file-count" class="text-sm text-gray-600 font-semibold"></p>
        </div>
        <script>
            function displaySelectedFiles(input) {
                const fileList = document.getElementById('file-list');
                fileList.innerHTML = ''; // Clear the list
                Array.from(input.files).forEach(file => {
                    const listItem = document.createElement('li');
                    listItem.textContent = file.name;
                    fileList.appendChild(listItem);
                });
                document.getElementById('file-count').textContent =
                    `You have selected ${input.files.length} file(s).`;
            }
        </script>
    </div>

    <!-- Additional Notes -->
    <div class="space-y-4">
        <h3 class="text-xl font-semibold text-primary pb-2 border-b border-gray-200">Additional Notes</h3>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Anything else we should know?</label>
            <textarea name="additional_notes" class="w-full px-4 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition min-h-[100px]" placeholder="Specific concerns, goals, or preferences"></textarea>
        </div>
    </div>

    <!-- Submit Button -->
    <div class="flex justify-end">
        <button type="submit" class="px-6 py-3 bg-accent hover:bg-accent/90 text-white font-medium rounded-md transition-colors duration-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2">
            Submit Request
        </button>
    </div>
</form>

        </section>
    </div>

    <!-- Footer -->
    <?php include 'components/footer.php'; ?>

    <!-- JavaScript for mobile menu toggle -->
    <script>
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });
    </script>
</body>
</html>