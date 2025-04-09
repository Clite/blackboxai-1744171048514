<?php
require_once '../config.php';
require_once '../classes/User.php';
require_once '../classes/Course.php';
require_once '../classes/Exam.php';
require_once '../classes/Payment.php';
require_once '../classes/ExamResult.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect(BASE_URL . '/dashboard.php');
}

$user = new User($pdo);
$course = new Course($pdo);
$exam = new Exam($pdo);
$payment = new Payment($pdo);
$examResult = new ExamResult($pdo);
$currentUser = $user->getUserById($_SESSION['user_id']);

// Get statistics
$totalUsers = $user->getTotalUsers();
$totalCourses = $course->getTotalCourses();
$totalExams = $exam->getTotalExams();
$totalPayments = $payment->getTotalPayments();
$totalRevenue = $payment->getTotalRevenue();
$recentUsers = $user->getRecentUsers(5);
$recentPayments = $payment->getRecentPayments(5);
$examStats = $examResult->getExamStatistics();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
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
                        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="bg-gray-900 text-white px-3 py-2 rounded-md text-sm font-medium">
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
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Total Users</h3>
                        <p class="text-2xl font-bold text-gray-900"><?= $totalUsers ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-book text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Total Courses</h3>
                        <p class="text-2xl font-bold text-gray-900"><?= $totalCourses ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-clipboard-list text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Total Exams</h3>
                        <p class="text-2xl font-bold text-gray-900"><?= $totalExams ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-dollar-sign text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Total Revenue</h3>
                        <p class="text-2xl font-bold text-gray-900">$<?= number_format($totalRevenue, 2) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Exam Performance Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Exam Performance</h3>
                <canvas id="examPerformanceChart" height="250"></canvas>
                <script>
                    const examCtx = document.getElementById('examPerformanceChart').getContext('2d');
                    const examChart = new Chart(examCtx, {
                        type: 'bar',
                        data: {
                            labels: <?= json_encode(array_column($examStats, 'title')) ?>,
                            datasets: [{
                                label: 'Average Score (%)',
                                data: <?= json_encode(array_column($examStats, 'average_score')) ?>,
                                backgroundColor: 'rgba(79, 70, 229, 0.5)',
                                borderColor: 'rgba(79, 70, 229, 1)',
                                borderWidth: 1
                            }, {
                                label: 'Pass Rate (%)',
                                data: <?= json_encode(array_column($examStats, 'pass_rate')) ?>,
                                backgroundColor: 'rgba(16, 185, 129, 0.5)',
                                borderColor: 'rgba(16, 185, 129, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100
                                }
                            }
                        }
                    });
                </script>
            </div>
            
            <!-- Revenue Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Monthly Revenue</h3>
                <canvas id="revenueChart" height="250"></canvas>
                <script>
                    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
                    const revenueChart = new Chart(revenueCtx, {
                        type: 'line',
                        data: {
                            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                            datasets: [{
                                label: 'Revenue ($)',
                                data: [1200, 1900, 1500, 2000, 1800, 2500, 2200, 3000, 2800, 3500, 4000, 4500],
                                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                                borderColor: 'rgba(245, 158, 11, 1)',
                                borderWidth: 2,
                                tension: 0.1,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                </script>
            </div>
        </div>

        <!-- Recent Activity Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Users -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Users</h3>
                    <div class="space-y-4">
                        <?php foreach ($recentUsers as $user): ?>
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-gray-500"></i>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?= $user['first_name'] . ' ' . $user['last_name'] ?></div>
                                    <div class="text-sm text-gray-500">Joined <?= date('M j, Y', strtotime($user['created_at'])) ?></div>
                                </div>
                                <div class="ml-auto">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?= $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' ?>">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4">
                        <a href="<?= BASE_URL ?>/admin/users.php" class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                            View all users <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Recent Payments -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Payments</h3>
                    <div class="space-y-4">
                        <?php foreach ($recentPayments as $payment): ?>
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-full flex items-center justify-center">
                                    <i class="fas fa-credit-card text-gray-500"></i>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?= $payment['user_name'] ?></div>
                                    <div class="text-sm text-gray-500"><?= $payment['course_title'] ?></div>
                                </div>
                                <div class="ml-auto text-right">
                                    <div class="text-sm font-medium text-gray-900">$<?= number_format($payment['amount'], 2) ?></div>
                                    <div class="text-sm text-gray-500">
                                        <span class="px-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?= $payment['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                               ($payment['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                            <?= ucfirst($payment['status']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4">
                        <a href="<?= BASE_URL ?>/admin/payments.php" class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                            View all payments <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
