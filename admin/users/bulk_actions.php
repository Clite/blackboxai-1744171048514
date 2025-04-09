<?php
require_once '../../config.php';
require_once '../../classes/User.php';
require_once '../../classes/Email.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect(BASE_URL . '/dashboard.php');
}

$user = new User($pdo);
$email = new Email();
$currentUser = $user->getUserById($_SESSION['user_id']);

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['user_ids']) && isset($_POST['bulk_action'])) {
        $userIds = $_POST['user_ids'];
        $action = $_POST['bulk_action'];
        $successCount = 0;
        $errorCount = 0;

        foreach ($userIds as $userId) {
            $userId = (int)$userId;
            $student = $user->getUserById($userId);

            if ($student) {
                try {
                    switch ($action) {
                        case 'activate':
                            if ($user->updateUserStatus($userId, 'active')) {
                                $successCount++;
                            } else {
                                $errorCount++;
                            }
                            break;
                            
                        case 'deactivate':
                            if ($user->updateUserStatus($userId, 'inactive')) {
                                $successCount++;
                            } else {
                                $errorCount++;
                            }
                            break;
                            
                        case 'send_welcome':
                            $subject = "Welcome to " . SITE_NAME;
                            $message = "Dear {$student['first_name']},<br><br>"
                                     . "Welcome to " . SITE_NAME . "! Your account has been successfully created.<br><br>"
                                     . "You can now login using your credentials:<br>"
                                     . "Email: {$student['email']}<br><br>"
                                     . "Thank you for joining us!<br><br>"
                                     . "Best regards,<br>"
                                     . "The " . SITE_NAME . " Team";
                            
                            if ($email->send($student['email'], $subject, $message)) {
                                $successCount++;
                            } else {
                                $errorCount++;
                            }
                            break;
                            
                        case 'delete':
                            if ($user->deleteUser($userId)) {
                                $successCount++;
                            } else {
                                $errorCount++;
                            }
                            break;
                    }
                } catch (Exception $e) {
                    $errorCount++;
                }
            } else {
                $errorCount++;
            }
        }

        $redirectUrl = BASE_URL . '/admin/users/bulk_actions.php?action=' . urlencode($action) . 
                      '&success=' . $successCount . '&errors=' . $errorCount;
        redirect($redirectUrl);
    }
}

// Get all users for selection
$allUsers = $user->getAllUsers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk User Actions - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900">Bulk User Actions</h1>
            <div class="flex items-center space-x-4">
                <span class="text-gray-700">Welcome, <?= $currentUser['first_name'] ?></span>
                <a href="<?= BASE_URL ?>/auth/logout.php" class="text-blue-500 hover:text-blue-700">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="bg-gray-800 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <div class="flex space-x-4">
                        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a href="<?= BASE_URL ?>/admin/users.php" class="bg-gray-900 text-white px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-users"></i> Users
                        </a>
                        <a href="<?= BASE_URL ?>/admin/courses.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-book"></i> Courses
                        </a>
                        <a href="<?= BASE_URL ?>/admin/exams.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-clipboard-list"></i> Exams
                        </a>
                        <a href="<?= BASE_URL ?>/admin/payments.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-credit-card"></i> Payments
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <?php if (isset($_GET['action'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                Bulk action "<?= htmlspecialchars($_GET['action']) ?>" completed. 
                <?= $_GET['success'] ?> succeeded, <?= $_GET['errors'] ?> failed.
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Perform Bulk Actions</h2>
                
                <form method="POST">
                    <div class="grid grid-cols-1 gap-6 mb-6">
                        <!-- User Selection -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Select Users
                            </label>
                            <div class="overflow-y-auto max-h-96 border rounded">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <input type="checkbox" id="select-all" class="rounded">
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($allUsers as $user): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <input type="checkbox" name="user_ids[]" value="<?= $user['id'] ?>" class="user-checkbox rounded">
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-full flex items-center justify-center">
                                                            <i class="fas fa-user text-gray-500"></i>
                                                        </div>
                                                        <div class="ml-4">
                                                            <div class="text-sm font-medium text-gray-900"><?= $user['first_name'] . ' ' . $user['last_name'] ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?= $user['email'] ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                        <?= $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                        <?= ucfirst($user['status']) ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?= ucfirst($user['role']) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Action Selection -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="bulk_action">
                                Select Action
                            </label>
                            <select class="w-full px-3 py-2 border rounded" id="bulk_action" name="bulk_action" required>
                                <option value="">-- Select an Action --</option>
                                <option value="activate">Activate Selected Users</option>
                                <option value="deactivate">Deactivate Selected Users</option>
                                <option value="send_welcome">Send Welcome Email</option>
                                <option value="delete">Delete Selected Users</option>
                            </select>
                        </div>
                        
                        <!-- Confirmation for Delete -->
                        <div id="delete-confirm" class="hidden bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700">
                                        Warning: This will permanently delete the selected users and all their data. 
                                        This action cannot be undone.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" 
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            <i class="fas fa-play mr-2"></i> Execute Action
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Select all checkbox functionality
        document.getElementById('select-all').addEventListener('change', function(e) {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = e.target.checked;
            });
        });

        // Show/hide delete confirmation
        document.getElementById('bulk_action').addEventListener('change', function(e) {
            const deleteConfirm = document.getElementById('delete-confirm');
            if (e.target.value === 'delete') {
                deleteConfirm.classList.remove('hidden');
            } else {
                deleteConfirm.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
