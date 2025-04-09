<?php
require_once '../config.php';
require_once '../classes/User.php';
require_once '../classes/Course.php';
require_once '../classes/Exam.php';
require_once '../classes/Payment.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

if (!isset($_GET['id'])) {
    redirect(BASE_URL . '/dashboard.php');
}

$courseId = (int)$_GET['id'];
$user = new User($pdo);
$currentUser = $user->getUserById($_SESSION['user_id']);

$course = new Course($pdo);
$courseDetails = $course->getCourse($courseId);

if (!$courseDetails) {
    redirect(BASE_URL . '/dashboard.php');
}

$isEnrolled = $course->isEnrolled($courseId, $_SESSION['user_id']);
$exams = [];
$payment = new Payment($pdo);

if ($isEnrolled) {
    $exam = new Exam($pdo);
    $exams = $exam->getPublishedExams($courseId);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $courseDetails['title'] ?> - <?= SITE_NAME ?></title>
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
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800"><?= $courseDetails['title'] ?></h2>
                        <p class="text-gray-600">Instructor: <?= $user->getUserById($courseDetails['instructor_id'])['first_name'] . ' ' . $user->getUserById($courseDetails['instructor_id'])['last_name'] ?></p>
                    </div>
                    <span class="text-xl font-bold text-gray-800">$<?= number_format($courseDetails['price'], 2) ?></span>
                </div>
                
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Course Description</h3>
                    <p class="text-gray-700"><?= nl2br($courseDetails['description']) ?></p>
                </div>

                <?php if (!$isEnrolled && hasRole('student')): ?>
                    <form action="<?= BASE_URL ?>/courses/enroll.php" method="POST">
                        <input type="hidden" name="course_id" value="<?= $courseId ?>">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            <i class="fas fa-shopping-cart"></i> Enroll Now ($<?= number_format($courseDetails['price'], 2) ?>)
                        </button>
                    </form>
                <?php elseif ($isEnrolled): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        You are enrolled in this course
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isEnrolled && !empty($exams)): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Available Exams</h3>
                    <div class="space-y-4">
                        <?php foreach ($exams as $exam): ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h4 class="text-lg font-medium text-gray-800"><?= $exam['title'] ?></h4>
                                        <p class="text-gray-600">Time Limit: <?= $exam['time_limit_minutes'] ? $exam['time_limit_minutes'] . ' minutes' : 'No time limit' ?></p>
                                    </div>
                                    <a href="<?= BASE_URL ?>/exams/take.php?id=<?= $exam['id'] ?>" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        Take Exam
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
