<?php
require_once '../config.php';
require_once '../classes/User.php';
require_once '../classes/Course.php';
require_once '../classes/Payment.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['course_id'])) {
    redirect(BASE_URL . '/dashboard.php');
}

$courseId = (int)$_POST['course_id'];
$userId = $_SESSION['user_id'];

$user = new User($pdo);
$currentUser = $user->getUserById($userId);

$course = new Course($pdo);
$courseDetails = $course->getCourse($courseId);

if (!$courseDetails) {
    redirect(BASE_URL . '/dashboard.php');
}

// Check if already enrolled
if ($course->isEnrolled($courseId, $userId)) {
    redirect(BASE_URL . '/courses/view.php?id=' . $courseId);
}

// Process payment (in a real app, this would integrate with payment gateway)
$payment = new Payment($pdo);
$paymentSuccess = $payment->processPayment(
    $userId,
    $courseId,
    $courseDetails['price'],
    'manual', // In a real app, this would be 'credit_card', 'paypal', etc.
    'PAY-' . uniqid() // Generate a fake transaction ID for demo
);

if ($paymentSuccess) {
    // Update payment status to completed
    $paymentId = $pdo->lastInsertId();
    $payment->updatePaymentStatus($paymentId, 'completed');
    
    // Enroll student
    if ($course->enrollStudent($courseId, $userId)) {
        redirect(BASE_URL . '/courses/view.php?id=' . $courseId . '&enrolled=1');
    }
}

// If we get here, something went wrong
redirect(BASE_URL . '/courses/view.php?id=' . $courseId . '&error=1');
