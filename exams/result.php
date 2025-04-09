<?php
require_once '../config.php';
require_once '../classes/User.php';
require_once '../classes/Course.php';
require_once '../classes/Exam.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

if (!isset($_GET['exam_id']) || !isset($_SESSION['last_exam_result'])) {
    redirect(BASE_URL . '/dashboard.php');
}

$examId = (int)$_GET['exam_id'];
$userId = $_SESSION['user_id'];
$result = $_SESSION['last_exam_result'];

$user = new User($pdo);
$currentUser = $user->getUserById($userId);

$exam = new Exam($pdo);
$examDetails = $exam->getExam($examId);

if (!$examDetails) {
    redirect(BASE_URL . '/dashboard.php');
}

$course = new Course($pdo);
$courseDetails = $course->getCourse($examDetails['course_id']);

// Check if user is enrolled in the course
if (!$course->isEnrolled($examDetails['course_id'], $userId)) {
    redirect(BASE_URL . '/courses/view.php?id=' . $examDetails['course_id']);
}

// Clear the session result
unset($_SESSION['last_exam_result']);

// Determine pass/fail status
$passed = isset($examDetails['passing_score']) 
    ? $result['percentage'] >= $examDetails['passing_score']
    : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-xl font-bold text-gray-900">Exam Results</h1>
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
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="p-6">
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2"><?= $examDetails['title'] ?></h2>
                    <p class="text-gray-600">Course: <?= $courseDetails['title'] ?></p>
                </div>

                <div class="max-w-md mx-auto">
                    <div class="<?= $passed ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700' ?> border px-4 py-3 rounded mb-6 text-center">
                        <h3 class="text-lg font-bold mb-1">
                            <?php if ($passed === true): ?>
                                <i class="fas fa-check-circle"></i> Passed!
                            <?php elseif ($passed === false): ?>
                                <i class="fas fa-times-circle"></i> Failed
                            <?php else: ?>
                                <i class="fas fa-info-circle"></i> Exam Completed
                            <?php endif; ?>
                        </h3>
                        <p class="text-sm">
                            Score: <?= $result['achieved_score'] ?> out of <?= $result['total_score'] ?>
                            (<?= $result['percentage'] ?>%)
                        </p>
                        <?php if ($passed !== null): ?>
                            <p class="text-sm mt-1">
                                Passing Score: <?= $examDetails['passing_score'] ?>%
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="text-center">
                        <a href="<?= BASE_URL ?>/courses/view.php?id=<?= $courseDetails['id'] ?>" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Back to Course
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
