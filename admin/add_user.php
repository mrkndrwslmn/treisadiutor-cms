<?php
session_start();
require_once '../includes/db.php'; // adjust path as needed

// Handle form submission (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $fullName = $_POST['fullName'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $phoneNumber = $_POST['phoneNumber'];
    $password = $_POST['password'];

    if (empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Password is required.']);
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Fix: Correct SQL and bind_param
    $insertStmt = $conn->prepare("INSERT INTO users (fullName, email, password, role, phoneNumber, status) VALUES (?, ?, ?, ?, ?, ?)");
    $status = 'active';
    $insertStmt->bind_param("ssssss", $fullName, $email, $hashedPassword, $role, $phoneNumber, $status);

    if ($insertStmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error adding user.']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add User</title>
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
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <?php include 'components/navbar.php'; ?>

    <div class="flex flex-col items-center justify-center flex-1">
        <div class="bg-white shadow-lg rounded-lg p-8 w-full max-w-lg mt-10">
            <h2 class="text-2xl font-bold mb-6 text-primary flex items-center gap-2">
                <i class="fa-solid fa-user-plus"></i> Add User
            </h2>
            <form id="addUserForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="fullName" required
                        class="mt-1 block w-full rounded-md border-l-4 bg-gray-50 px-3 py-2 shadow focus:border-primary focus:ring-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" required
                        class="mt-1 block w-full rounded-md border-l-4 bg-gray-50 px-3 py-2 shadow focus:border-primary focus:ring-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required
                        class="mt-1 block w-full rounded-md border-l-4 bg-gray-50 px-3 py-2 shadow focus:border-primary focus:ring-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <input type="text" name="role" required
                        class="mt-1 block w-full rounded-md border-l-4 bg-gray-50 px-3 py-2 shadow focus:border-primary focus:ring-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input type="text" name="phoneNumber"
                        class="mt-1 block w-full rounded-md border-l-4 bg-gray-50 px-3 py-2 shadow focus:border-primary focus:ring-primary" />
                </div>
                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" onclick="window.history.back()" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded bg-primary text-white hover:bg-secondary transition font-semibold flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Add User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.getElementById('addUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Add User?',
            text: "Are you sure you want to add this user?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3a5a78',
            cancelButtonColor: '#6c9ab5',
            confirmButtonText: 'Yes, add!',
            background: '#f8fafc',
            color: '#1a2a3a'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = e.target;
                const formData = new FormData(form);
                formData.append('ajax', '1');
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Added!',
                            text: 'User added successfully.',
                            icon: 'success',
                            confirmButtonColor: '#3a5a78',
                            background: '#f8fafc',
                            color: '#1a2a3a'
                        }).then(() => {
                            window.location.replace(document.referrer);
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.error || 'Error adding user.',
                            icon: 'error',
                            confirmButtonColor: '#3a5a78',
                            background: '#f8fafc',
                            color: '#1a2a3a'
                        });
                    }
                });
            }
        });
    });
    </script>
</body>
</html>
