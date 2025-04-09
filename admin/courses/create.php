<?php
require_once '../../config.php';
require_once '../../classes/Course.php';
require_once '../../classes/User.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect(BASE_URL . '/dashboard.php');
}

$course = new Course($pdo);
$user = new User($pdo);
$currentUser = $user->getUserById($_SESSION['user_id']);
$instructors = $user->getUsersByRole('instructor');

$errors = [];
$formData = [
    'title' => '',
    'description' => '',
    'price' => '0.00',
    'instructor_id' => '',
    'is_active' => true
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'title' => sanitize($_POST['title']),
        'description' => sanitize($_POST['description']),
        'price' => sanitize($_POST['price']),
        'instructor_id' => sanitize($_POST['instructor_id']),
        'is_active' => isset($_POST['is_active'])
    ];

    // Validate inputs
    if (empty($formData['title'])) $errors[] = 'Title is required';
    if (empty($formData['description'])) $errors[] = 'Description is required';
    if (!is_numeric($formData['price']) || $formData['price'] < 0) $errors[] = 'Invalid price';
    if (empty($formData['instructor_id'])) $errors[] = 'Instructor is required';

    if (empty($errors)) {
        if ($course->createCourse(
            $formData['title'],
            $formData['description'],
            $formData['price'],
            $formData['instructor_id'],
            $formData['is_active']
        )) {
            redirect(BASE_URL . '/admin/courses.php?created=1');
        } else {
            $errors[] = 'Failed to create course. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Course - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900">Create Course</h1>
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
                <a href="<?= BASE_URL ?>/admin/courses.php" class="text-blue-500 hover:text-blue-700 mb-4 inline-block">
                    <i class="fas fa-arrow-left"></i> Back to Courses
                </a>
                
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Create New Course</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <?php foreach ($errors as $error): ?>
                            <p><?= $error ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="grid grid-cols-1 gap-6 mb-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                                Title *
                            </label>
                            <input class="w-full px-3 py-2 border rounded" type="text" id="title" name="title" 
                                value="<?= htmlspecialchars($formData['title']) ?>" required>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                                Description *
                            </label>
                            <textarea class="w-full px-3 py-2 border rounded" id="description" name="description" rows="4" required><?= htmlspecialchars($formData['description']) ?></textarea>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="price">
                                    Price *
                                </label>
                                <input class="w-full px-3 py-2 border rounded" type="number" step="0.01" min="0" id="price" name="price" 
                                    value="<?= htmlspecialchars($formData['price']) ?>" required>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="instructor_id">
                                    Instructor *
                                </label>
                                <select class="w-full px-3 py-2 border rounded" id="instructor_id" name="instructor_id" required>
                                    <option value="">Select Instructor</option>
                                    <?php foreach ($instructors as $instructor): ?>
                                        <option value="<?= $instructor['id'] ?>" <?= $formData['instructor_id'] == $instructor['id'] ? 'selected' : '' ?>>
                                            <?= $instructor['first_name'] . ' ' . $instructor['last_name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="flex items-center">
                            <input type="checkbox" class="form-checkbox" name="is_active" <?= $formData['is_active'] ? 'checked' : '' ?>>
                            <span class="ml-2 text-gray-700">Active Course</span>
                        </label>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Create Course
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
