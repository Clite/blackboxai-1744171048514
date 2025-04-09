<?php
class Payment {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Process a new payment
    public function processPayment($userId, $courseId, $amount, $paymentMethod, $transactionId = null) {
        $stmt = $this->pdo->prepare("INSERT INTO payments (user_id, course_id, amount, payment_method, transaction_id) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$userId, $courseId, $amount, $paymentMethod, $transactionId]);
    }

    // Update payment status
    public function updatePaymentStatus($paymentId, $status) {
        $stmt = $this->pdo->prepare("UPDATE payments SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $paymentId]);
    }

    // Get payment by ID
    public function getPayment($paymentId) {
        $stmt = $this->pdo->prepare("SELECT * FROM payments WHERE id = ?");
        $stmt->execute([$paymentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get all payments for a user
    public function getUserPayments($userId) {
        $stmt = $this->pdo->prepare("SELECT p.*, c.title as course_title FROM payments p JOIN courses c ON p.course_id = c.id WHERE p.user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get all payments for a course
    public function getCoursePayments($courseId) {
        $stmt = $this->pdo->prepare("SELECT p.*, u.username, u.email FROM payments p JOIN users u ON p.user_id = u.id WHERE p.course_id = ?");
        $stmt->execute([$courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Verify payment and enroll student
    public function verifyAndEnroll($paymentId) {
        $payment = $this->getPayment($paymentId);
        
        if ($payment && $payment['status'] === 'completed') {
            $course = new Course($this->pdo);
            return $course->enrollStudent($payment['course_id'], $payment['user_id']);
        }
        
        return false;
    }

    // Generate payment receipt
    public function generateReceipt($paymentId) {
        $stmt = $this->pdo->prepare("SELECT p.*, u.username, u.email, c.title as course_title FROM payments p JOIN users u ON p.user_id = u.id JOIN courses c ON p.course_id = c.id WHERE p.id = ?");
        $stmt->execute([$paymentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get total revenue
    public function getTotalRevenue() {
        $stmt = $this->pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}
