<?php
session_start();
require_once '../includes/db.php'; // adjust path as needed

// Get userID to edit
if (!isset($_GET['userID'])) {
    echo "No user selected.";
    exit;
}
$editUserID = intval($_GET['userID']);

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE userID = ?");
$stmt->bind_param("i", $editUserID);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    echo "User not found.";
    exit;
}
$user = $result->fetch_assoc();

// Handle form submission (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $fullName = $_POST['fullName'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $phoneNumber = $_POST['phoneNumber'];
    $status = $_POST['status'];
    $password = $_POST['password'];

    // Only update password if a new one is entered
    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("UPDATE users SET fullName=?, email=?, password=?, role=?, phoneNumber=?, status=? WHERE userID=?");
        $updateStmt->bind_param("ssssssi", $fullName, $email, $hashedPassword, $role, $phoneNumber, $status, $editUserID);
    } else {
        $updateStmt = $conn->prepare("UPDATE users SET fullName=?, email=?, role=?, phoneNumber=?, status=? WHERE userID=?");
        $updateStmt->bind_param("sssssi", $fullName, $email, $role, $phoneNumber, $status, $editUserID);
    }

    if ($updateStmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error updating user.']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
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
                <i class="fa-solid fa-user-pen"></i> Edit User
            </h2>
            <form id="editUserForm" class="space-y-4">
                <input type="hidden" name="userID" value="<?php echo htmlspecialchars($user['userID']); ?>" />
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="fullName" value="<?php echo htmlspecialchars($user['fullName']); ?>" required
                        class="mt-1 block w-full rounded-md border-l-4 bg-gray-50 px-3 py-2 shadow focus:border-primary focus:ring-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required
                        class="mt-1 block w-full rounded-md border-l-4 bg-gray-50 px-3 py-2 shadow focus:border-primary focus:ring-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-xs text-gray-400">(leave blank to keep current)</span></label>
                    <input type="password" name="password"
                        class="mt-1 block w-full rounded-md border-l-4 bg-gray-50 px-3 py-2 shadow focus:border-primary focus:ring-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <input type="text" name="role" value="<?php echo ucwords(htmlspecialchars($user['role'])); ?>" required
                        class="mt-1 block w-full rounded-md border-l-4 bg-gray-50 px-3 py-2 shadow focus:border-primary focus:ring-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input type="text" name="phoneNumber" value="<?php echo htmlspecialchars($user['phoneNumber']); ?>"
                        class="mt-1 block w-full rounded-md border-l-4 bg-gray-50 px-3 py-2 shadow focus:border-primary focus:ring-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status"
                        class="mt-1 block w-full rounded-md border-l-4 bg-gray-50 px-3 py-2 shadow focus:border-primary focus:ring-primary">
                        <option value="active" <?php if($user['status']=='active') echo 'selected'; ?>>Active</option>
                        <option value="inactive" <?php if($user['status']=='inactive') echo 'selected'; ?>>Inactive</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" onclick="window.history.back()" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded bg-primary text-white hover:bg-secondary transition font-semibold flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.getElementById('editUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Update User?',
            text: "Are you sure you want to update this user?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3a5a78',
            cancelButtonColor: '#6c9ab5',
            confirmButtonText: 'Yes, update!',
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
                            title: 'Updated!',
                            text: 'User updated successfully.',
                            icon: 'success',
                            confirmButtonColor: '#3a5a78',
                            background: '#f8fafc',
                            color: '#1a2a3a'
                        }).then(() => {
                            // Go back and reload the previous page
                            window.location.replace(document.referrer);
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.error || 'Error updating user.',
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
