<?php
require_once '../../config.php';
require_once '../../classes/User.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect(BASE_URL . '/dashboard.php');
}

$user = new User($pdo);
$currentUser = $user->getUserById($_SESSION['user_id']);

// Check if user ID is provided
if (!isset($_GET['id'])) {
    redirect(BASE_URL . '/admin/users.php');
}

$userId = (int)$_GET['id'];
$userToEdit = $user->getUserById($userId);

if (!$userToEdit) {
    redirect(BASE_URL . '/admin/users.php');
}

$errors = [];
$formData = [
    'first_name' => $userToEdit['first_name'],
    'last_name' => $userToEdit['last_name'],
    'email' => $userToEdit['email'],
    'role' => $userToEdit['role'],
    'is_active' => $userToEdit['is_active']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'first_name' => sanitize($_POST['first_name']),
        'last_name' => sanitize($_POST['last_name']),
        'email' => sanitize($_POST['email']),
        'role' => sanitize($_POST['role']),
        'is_active' => isset($_POST['is_active']),
        'password' => $_POST['password'],
        'password_confirm' => $_POST['password_confirm']
    ];

    // Validate inputs
    if (empty($formData['first_name'])) $errors[] = 'First name is required';
    if (empty($formData['last_name'])) $errors[] = 'Last name is required';
    if (empty($formData['email'])) $errors[] = 'Email is required';
    if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';

    // Check if email exists (excluding current user)
    if ($formData['email'] !== $userToEdit['email'] && $user->emailExists($formData['email'])) {
        $errors[] = 'Email already exists';
    }

    // Only validate passwords if they are provided
    if (!empty($formData['password']) || !empty($formData['password_confirm'])) {
        if ($formData['password'] !== $formData['password_confirm']) {
            $errors[] = 'Passwords do not match';
        }
    }

    if (empty($errors)) {
        $updateData = [
            'first_name' => $formData['first_name'],
            'last_name' => $formData['last_name'],
            'email' => $formData['email'],
            'role' => $formData['role'],
            'is_active' => $formData['is_active']
        ];

        // Only update password if provided
        if (!empty($formData['password'])) {
            $updateData['password'] = $formData['password'];
        }

        if ($user->updateUser($userId, $updateData)) {
            redirect(BASE_URL . '/admin/users.php?updated=1');
        } else {
            $errors[] = 'Failed to update user. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900">Edit User</h1>
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
                        <a href="<?= BASE_URL ?>/admin/users.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-users"></i> Users
                        </a>
                        <a href="<?= BASE_URL ?>/admin/courses.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-book"></i> Courses
                        </a>
                        <a href="<?= BASE_URL ?>/admin/exams.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-clipboard-list"></i> Exams
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <a href="<?= BASE_URL ?>/admin/users.php" class="text-blue-500 hover:text-blue-700 mb-4 inline-block">
                    <i class="fas fa-arrow-left"></i> Back to Users
                </a>
                
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Edit User: <?= $userToEdit['first_name'] . ' ' . $userToEdit['last_name'] ?></h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <?php foreach ($errors as $error): ?>
                            <p><?= $error ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="first_name">
                                First Name *
                            </label>
                            <input class="w-full px-3 py-2 border rounded" type="text" id="first_name" name="first_name" 
                                value="<?= htmlspecialchars($formData['first_name']) ?>" required>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="last_name">
                                Last Name *
                            </label>
                            <input class="w-full px-3 py-2 border rounded" type="text" id="last_name" name="last_name" 
                                value="<?= htmlspecialchars($formData['last_name']) ?>" required>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                                Email *
                            </label>
                            <input class="w-full px-3 py-2 border rounded" type="email" id="email" name="email" 
                                value="<?= htmlspecialchars($formData['email']) ?>" required>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="role">
                                Role *
                            </label>
                            <select class="w-full px-3 py-2 border rounded" id="role" name="role" required>
                                <option value="student" <?= $formData['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                                <option value="instructor" <?= $formData['role'] === 'instructor' ? 'selected' : '' ?>>Instructor</option>
                                <option value="admin" <?= $formData['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                                New Password (leave blank to keep current)
                            </label>
                            <input class="w-full px-3 py-2 border rounded" type="password" id="password" name="password">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="password_confirm">
                                Confirm New Password
                            </label>
                            <input class="w-full px-3 py-2 border rounded" type="password" id="password_confirm" name="password_confirm">
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="flex items-center">
                            <input type="checkbox" class="form-checkbox" name="is_active" <?= $formData['is_active'] ? 'checked' : '' ?>>
                            <span class="ml-2 text-gray-700">Active User</span>
                        </label>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
