<?php
class Course {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Create a new course
    public function createCourse($title, $description, $price, $instructorId) {
        $stmt = $this->pdo->prepare("INSERT INTO courses (title, description, price, instructor_id) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$title, $description, $price, $instructorId]);
    }

    // Update course details
    public function updateCourse($courseId, $title, $description, $price) {
        $stmt = $this->pdo->prepare("UPDATE courses SET title = ?, description = ?, price = ? WHERE id = ?");
        return $stmt->execute([$title, $description, $price, $courseId]);
    }

    // Delete a course
    public function deleteCourse($courseId) {
        $stmt = $this->pdo->prepare("DELETE FROM courses WHERE id = ?");
        return $stmt->execute([$courseId]);
    }

    // Get course by ID
    public function getCourse($courseId) {
        $stmt = $this->pdo->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([$courseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get all courses
    public function getAllCourses() {
        $stmt = $this->pdo->prepare("SELECT * FROM courses");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get courses by instructor
    public function getInstructorCourses($instructorId) {
        $stmt = $this->pdo->prepare("SELECT * FROM courses WHERE instructor_id = ?");
        $stmt->execute([$instructorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Enroll student in course
    public function enrollStudent($courseId, $studentId) {
        $stmt = $this->pdo->prepare("INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)");
        return $stmt->execute([$studentId, $courseId]);
    }

    // Get enrolled students for a course
    public function getEnrolledStudents($courseId) {
        $stmt = $this->pdo->prepare("SELECT u.* FROM users u JOIN enrollments e ON u.id = e.user_id WHERE e.course_id = ?");
        $stmt->execute([$courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Check if user is enrolled in course
    public function isEnrolled($courseId, $studentId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$studentId, $courseId]);
        return $stmt->fetchColumn() > 0;
    }
}
