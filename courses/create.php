<?php
require_once '../config.php';
require_once '../classes/User.php';
require_once '../classes/Course.php';

// Check if user is logged in and has instructor/admin role
if (!isLoggedIn() || (!hasRole('instructor') && !hasRole('admin'))) {
    redirect(BASE_URL . '/dashboard.php');
}

$errors = [];
$user = new User($pdo);
$currentUser = $user->getUserById($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $price = (float)$_POST['price'];
    $instructorId = hasRole('admin') && isset($_POST['instructor_id']) ? (int)$_POST['instructor_id'] : $_SESSION['user_id'];

    // Validate inputs
    if (empty($title)) $errors[] = 'Course title is required';
    if (empty($description)) $errors[] = 'Course description is required';
    if ($price < 0) $errors[] = 'Price must be a positive number';

    if (empty($errors)) {
        $course = new Course($pdo);
        if ($course->createCourse($title, $description, $price, $instructorId)) {
            redirect(BASE_URL . '/dashboard.php?created=1');
        } else {
            $errors[] = 'Failed to create course. Please try again.';
        }
    }
}

// Get instructors for admin to select from
$instructors = [];
if (hasRole('admin')) {
    $instructors = $user->getUsersByRole('instructor');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Course - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900"><?= SITE_NAME ?></h1>
            <div class="flex items-center space-x-4">
                <span class="text-gray-700">Welcome, <?= $currentUser['first_name'] ?></span>
                <a href="<?= BASE_URL ?>/auth/logout.php" class="text-blue-500 hover:text-blue-700">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Create New Course</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <?php foreach ($errors as $error): ?>
                            <p><?= $error ?></p>
                        <?php endforeach; ?>
                        </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                            Course Title *
                        </label>
                        <input class="w-full px-3 py-2 border rounded" type="text" id="title" name="title" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                            Course Description *
                        </label>
                        <textarea class="w-full px-3 py-2 border rounded" id="description" name="description" rows="5" required></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="price">
                            Price ($) *
                        </label>
                        <input class="w-full px-3 py-2 border rounded" type="number" id="price" name="price" step="0.01" min="0" required>
                    </div>
                    
                    <?php if (hasRole('admin') && !empty($instructors)): ?>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="instructor_id">
                                Instructor *
                            </label>
                            <select class="w-full px-3 py-2 border rounded" id="instructor_id" name="instructor_id" required>
                                <?php foreach ($instructors as $instructor): ?>
                                    <option value="<?= $instructor['id'] ?>">
                                        <?= $instructor['first_name'] . ' ' . $instructor['last_name'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <div class="flex justify-end">
                        <a href="<?= BASE_URL ?>/dashboard.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-2">
                            Cancel
                        </a>
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
