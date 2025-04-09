<?php
require_once '../../config.php';
require_once '../../classes/Payment.php';
require_once '../../classes/User.php';
require_once '../../classes/Course.php';
require_once '../../classes/Email.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect(BASE_URL . '/dashboard.php');
}

$payment = new Payment($pdo);
$user = new User($pdo);
$course = new Course($pdo);
$email = new Email();
$currentUser = $user->getUserById($_SESSION['user_id']);

// Get pending payments
$pendingPayments = $payment->getPaymentsByStatus('pending');

// Handle sending notifications
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_notification'])) {
        $paymentId = (int)$_POST['payment_id'];
        $paymentData = $payment->getPaymentById($paymentId);
        
        if ($paymentData) {
            $student = $user->getUserById($paymentData['user_id']);
            $courseData = $course->getCourseById($paymentData['course_id']);
            
            // Send email notification
            $subject = "Payment Reminder: {$courseData['title']}";
            $message = "Dear {$student['first_name']},<br><br>"
                     . "This is a reminder that your payment for the course <strong>{$courseData['title']}</strong> "
                     . "is still pending. The amount due is <strong>\${$paymentData['amount']}</strong>.<br><br>"
                     . "Please complete your payment at your earliest convenience to avoid any disruption to your course access.<br><br>"
                     . "Thank you,<br>"
                     . SITE_NAME;
            
            if ($email->send($student['email'], $subject, $message)) {
                redirect(BASE_URL . '/admin/payments/notifications.php?sent=1');
            } else {
                redirect(BASE_URL . '/admin/payments/notifications.php?error=send_failed');
            }
        }
    }
    
    if (isset($_POST['send_bulk_notifications'])) {
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($pendingPayments as $payment) {
            $student = $user->getUserById($payment['user_id']);
            $courseData = $course->getCourseById($payment['course_id']);
            
            $subject = "Payment Reminder: {$courseData['title']}";
            $message = "Dear {$student['first_name']},<br><br>"
                     . "This is a reminder that your payment for the course <strong>{$courseData['title']}</strong> "
                     . "is still pending. The amount due is <strong>\${$payment['amount']}</strong>.<br><br>"
                     . "Please complete your payment at your earliest convenience to avoid any disruption to your course access.<br><br>"
                     . "Thank you,<br>"
                     . SITE_NAME;
            
            if ($email->send($student['email'], $subject, $message)) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }
        
        redirect(BASE_URL . "/admin/payments/notifications.php?bulk_sent=1&success={$successCount}&errors={$errorCount}");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Notifications - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900">Payment Notifications</h1>
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
                        <a href="<?= BASE_URL ?>/admin/payments.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-credit-card"></i> Payments
                        </a>
                        <a href="<?= BASE_URL ?>/admin/payments/notifications.php" class="bg-gray-900 text-white px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-envelope"></i> Notifications
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <?php if (isset($_GET['sent'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                Payment reminder sent successfully.
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                Failed to send payment reminder. Please try again.
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['bulk_sent'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                Bulk notifications sent successfully. <?= $_GET['success'] ?> succeeded, <?= $_GET['errors'] ?> failed.
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Pending Payment Reminders</h2>
                    
                    <?php if (count($pendingPayments) > 0): ?>
                        <form method="POST">
                            <button type="submit" name="send_bulk_notifications" 
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                <i class="fas fa-paper-plane mr-2"></i> Send All Reminders
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                
                <?php if (count($pendingPayments) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($pendingPayments as $payment): ?>
                                    <?php $student = $user->getUserById($payment['user_id']); ?>
                                    <?php $courseData = $course->getCourseById($payment['course_id']); ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-user text-gray-500"></i>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?= $student['first_name'] . ' ' . $student['last_name'] ?></div>
                                                    <div class="text-sm text-gray-500"><?= $student['email'] ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= $courseData['title'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            $<?= number_format($payment['amount'], 2) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('M j, Y', strtotime($payment['created_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                                                <button type="submit" name="send_notification" 
                                                    class="text-blue-500 hover:text-blue-700">
                                                    <i class="fas fa-paper-plane mr-1"></i> Send Reminder
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500">No pending payments found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Email Template Configuration -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Email Template Configuration</h2>
                
                <form method="POST">
                    <div class="grid grid-cols-1 gap-6 mb-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="subject">
                                Email Subject
                            </label>
                            <input class="w-full px-3 py-2 border rounded" type="text" id="subject" name="subject" 
                                value="Payment Reminder: {course_title}">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="template">
                                Email Template
                            </label>
                            <textarea class="w-full px-3 py-2 border rounded" id="template" name="template" rows="10">
Dear {student_name},

This is a reminder that your payment for the course "{course_title}" is still pending. 
The amount due is ${amount}.

Please complete your payment at your earliest convenience to avoid any disruption to your course access.

Thank you,
{site_name}
                            </textarea>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Available Variables
                            </label>
                            <div class="bg-gray-50 p-4 rounded">
                                <ul class="list-disc pl-5 space-y-1">
                                    <li><code>{student_name}</code> - Student's full name</li>
                                    <li><code>{course_title}</code> - Course title</li>
                                    <li><code>{amount}</code> - Payment amount</li>
                                    <li><code>{due_date}</code> - Payment due date</li>
                                    <li><code>{site_name}</code> - Your site name</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" name="save_template" 
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Save Template
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
