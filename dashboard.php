<?php
require_once 'config.php';
require_once 'classes/User.php';
require_once 'classes/Course.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

$user = new User($pdo);
$currentUser = $user->getUserById($_SESSION['user_id']);

$course = new Course($pdo);
$courses = [];

if (hasRole('student')) {
    $courses = $course->getAllCourses();
} elseif (hasRole('instructor')) {
    $courses = $course->getInstructorCourses($_SESSION['user_id']);
} elseif (hasRole('admin')) {
    $courses = $course->getAllCourses();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= SITE_NAME ?></title>
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
        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">
                <?= hasRole('student') ? 'Available Courses' : 'Your Courses' ?>
            </h2>
            <?php if (hasRole('instructor') || hasRole('admin')): ?>
                <a href="<?= BASE_URL ?>/courses/create.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-plus"></i> Create New Course
                </a>
            <?php endif; ?>
        </div>

        <!-- Courses Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($courses as $course): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2"><?= $course['title'] ?></h3>
                        <p class="text-gray-600 mb-4"><?= substr($course['description'], 0, 100) ?>...</p>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-700 font-bold">$<?= number_format($course['price'], 2) ?></span>
                            <a href="<?= BASE_URL ?>/courses/view.php?id=<?= $course['id'] ?>" class="text-blue-500 hover:text-blue-700">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>
