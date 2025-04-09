<?php
require_once '../../config.php';
require_once '../../classes/Exam.php';
require_once '../../classes/Course.php';
require_once '../../classes/User.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect(BASE_URL . '/dashboard.php');
}

$exam = new Exam($pdo);
$course = new Course($pdo);
$user = new User($pdo);
$currentUser = $user->getUserById($_SESSION['user_id']);
$allCourses = $course->getAllActiveCourses();

// Check if exam ID is provided
if (!isset($_GET['id'])) {
    redirect(BASE_URL . '/admin/exams.php');
}

$examId = (int)$_GET['id'];
$examToEdit = $exam->getExamById($examId);

if (!$examToEdit) {
    redirect(BASE_URL . '/admin/exams.php');
}

$errors = [];
$formData = [
    'title' => $examToEdit['title'],
    'description' => $examToEdit['description'],
    'course_id' => $examToEdit['course_id'],
    'duration' => $examToEdit['duration'],
    'pass_mark' => $examToEdit['pass_mark'],
    'is_active' => $examToEdit['is_active']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'title' => sanitize($_POST['title']),
        'description' => sanitize($_POST['description']),
        'course_id' => sanitize($_POST['course_id']),
        'duration' => sanitize($_POST['duration']),
        'pass_mark' => sanitize($_POST['pass_mark']),
        'is_active' => isset($_POST['is_active'])
    ];

    // Validate inputs
    if (empty($formData['title'])) $errors[] = 'Title is required';
    if (empty($formData['description'])) $errors[] = 'Description is required';
    if (empty($formData['course_id'])) $errors[] = 'Course is required';
    if (!is_numeric($formData['duration']) || $formData['duration'] <= 0) $errors[] = 'Invalid duration';
    if (!is_numeric($formData['pass_mark']) || $formData['pass_mark'] < 0 || $formData['pass_mark'] > 100) $errors[] = 'Invalid pass mark';

    if (empty($errors)) {
        if ($exam->updateExam(
            $examId,
            $formData['title'],
            $formData['description'],
            $formData['course_id'],
            $formData['duration'],
            $formData['pass_mark'],
            $formData['is_active']
        )) {
            redirect(BASE_URL . '/admin/exams.php?updated=1');
        } else {
            $errors[] = 'Failed to update exam. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Exam - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900">Edit Exam</h1>
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
                <a href="<?= BASE_URL ?>/admin/exams.php" class="text-blue-500 hover:text-blue-700 mb-4 inline-block">
                    <i class="fas fa-arrow-left"></i> Back to Exams
                </a>
                
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Edit Exam: <?= $examToEdit['title'] ?></h2>
                
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
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="course_id">
                                    Course *
                                </label>
                                <select class="w-full px-3 py-2 border rounded" id="course_id" name="course_id" required>
                                    <option value="">Select Course</option>
                                    <?php foreach ($allCourses as $course): ?>
                                        <option value="<?= $course['id'] ?>" <?= $formData['course_id'] == $course['id'] ? 'selected' : '' ?>>
                                            <?= $course['title'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="duration">
                                    Duration (minutes) *
                                </label>
                                <input class="w-full px-3 py-2 border rounded" type="number" min="1" id="duration" name="duration" 
                                    value="<?= htmlspecialchars($formData['duration']) ?>" required>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="pass_mark">
                                    Pass Mark (%) *
                                </label>
                                <input class="w-full px-3 py-2 border rounded" type="number" min="0" max="100" id="pass_mark" name="pass_mark" 
                                    value="<?= htmlspecialchars($formData['pass_mark']) ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="flex items-center">
                            <input type="checkbox" class="form-checkbox" name="is_active" <?= $formData['is_active'] ? 'checked' : '' ?>>
                            <span class="ml-2 text-gray-700">Active Exam</span>
                        </label>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Update Exam
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
