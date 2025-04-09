<?php
require_once '../../config.php';
require_once '../../classes/User.php';
require_once '../../classes/Course.php';
require_once '../../classes/Exam.php';
require_once '../../classes/ExamResult.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect(BASE_URL . '/dashboard.php');
}

$user = new User($pdo);
$course = new Course($pdo);
$exam = new Exam($pdo);
$examResult = new ExamResult($pdo);
$currentUser = $user->getUserById($_SESSION['user_id']);

// Check if user ID is provided
if (!isset($_GET['id'])) {
    redirect(BASE_URL . '/admin/users.php');
}

$userId = (int)$_GET['id'];
$student = $user->getUserById($userId);

if (!$student) {
    redirect(BASE_URL . '/admin/users.php');
}

// Get user progress data
$enrolledCourses = $course->getCoursesByUserId($userId);
$completedExams = $examResult->getCompletedExamsByUser($userId);
$examAttempts = $examResult->getExamAttemptsByUser($userId);
$courseProgress = [];

foreach ($enrolledCourses as $course) {
    $courseExams = $exam->getExamsByCourseId($course['id']);
    $completedCourseExams = 0;
    $courseScore = 0;
    
    foreach ($courseExams as $exam) {
        $result = $examResult->getUserExamResult($userId, $exam['id']);
        if ($result) {
            $completedCourseExams++;
            $courseScore += $result['score'];
        }
    }
    
    $courseProgress[] = [
        'course' => $course,
        'total_exams' => count($courseExams),
        'completed_exams' => $completedCourseExams,
        'average_score' => $completedCourseExams > 0 ? round($courseScore / $completedCourseExams, 2) : 0,
        'progress' => count($courseExams) > 0 ? round(($completedCourseExams / count($courseExams)) * 100) : 0
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Progress - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900">User Progress Tracking</h1>
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
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">Progress for: <?= $student['first_name'] . ' ' . $student['last_name'] ?></h2>
                        <p class="text-gray-600">Email: <?= $student['email'] ?></p>
                    </div>
                    <a href="<?= BASE_URL ?>/admin/users.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        <i class="fas fa-arrow-left"></i> Back to Users
                    </a>
                </div>
                
                <!-- User Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="border rounded-lg p-4">
                        <h3 class="text-sm font-medium text-gray-500">Enrolled Courses</h3>
                        <p class="text-2xl font-bold text-gray-900"><?= count($enrolledCourses) ?></p>
                    </div>
                    <div class="border rounded-lg p-4">
                        <h3 class="text-sm font-medium text-gray-500">Completed Exams</h3>
                        <p class="text-2xl font-bold text-gray-900"><?= count($completedExams) ?></p>
                    </div>
                    <div class="border rounded-lg p-4">
                        <h3 class="text-sm font-medium text-gray-500">Total Attempts</h3>
                        <p class="text-2xl font-bold text-gray-900"><?= $examAttempts ?></p>
                    </div>
                </div>

                <!-- Course Progress -->
                <h3 class="text-lg font-medium text-gray-900 mb-4">Course Progress</h3>
                <div class="space-y-6">
                    <?php foreach ($courseProgress as $progress): ?>
                        <div class="border rounded-lg p-4">
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="font-medium text-gray-800"><?= $progress['course']['title'] ?></h4>
                                <span class="text-sm font-medium <?= $progress['progress'] >= 100 ? 'text-green-600' : 'text-blue-600' ?>">
                                    <?= $progress['progress'] ?>% Complete
                                </span>
                            </div>
                            
                            <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
                                <div class="bg-blue-600 h-2.5 rounded-full" 
                                    style="width: <?= $progress['progress'] ?>%"></div>
                            </div>
                            
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Exams Taken:</span>
                                    <span class="font-medium"><?= $progress['completed_exams'] ?>/<?= $progress['total_exams'] ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Average Score:</span>
                                    <span class="font-medium"><?= $progress['average_score'] ?>%</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Enrolled:</span>
                                    <span class="font-medium"><?= date('M j, Y', strtotime($progress['course']['enrolled_at'])) ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Last Activity:</span>
                                    <span class="font-medium"><?= date('M j, Y', strtotime($progress['course']['last_accessed'])) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Exam Results -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Exam Results</h3>
                
                <?php if (count($completedExams) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exam</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Taken</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($completedExams as $result): ?>
                                    <?php $examData = $exam->getExamById($result['exam_id']); ?>
                                    <?php $courseData = $course->getCourseById($examData['course_id']); ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= $examData['title'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= $courseData['title'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('M j, Y', strtotime($result['completed_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= $result['score'] ?>%
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?= $result['score'] >= $examData['pass_mark'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                <?= $result['score'] >= $examData['pass_mark'] ? 'Passed' : 'Failed' ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="<?= BASE_URL ?>/admin/exams/results/details.php?id=<?= $result['id'] ?>" class="text-blue-500 hover:text-blue-700">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500">No exam results found for this user.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
