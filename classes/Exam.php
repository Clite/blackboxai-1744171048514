<?php
class Exam {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Create a new exam
    public function createExam($courseId, $title, $description, $timeLimit = null, $passingScore = null) {
        $stmt = $this->pdo->prepare("INSERT INTO exams (course_id, title, description, time_limit_minutes, passing_score) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$courseId, $title, $description, $timeLimit, $passingScore]);
        return $this->pdo->lastInsertId();
    }

    // Publish/unpublish exam
    public function setPublishStatus($examId, $isPublished) {
        $stmt = $this->pdo->prepare("UPDATE exams SET is_published = ? WHERE id = ?");
        return $stmt->execute([$isPublished, $examId]);
    }

    // Get exam by ID
    public function getExam($examId) {
        $stmt = $this->pdo->prepare("SELECT * FROM exams WHERE id = ?");
        $stmt->execute([$examId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get all exams for a course
    public function getCourseExams($courseId) {
        $stmt = $this->pdo->prepare("SELECT * FROM exams WHERE course_id = ?");
        $stmt->execute([$courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get published exams for a course
    public function getPublishedExams($courseId) {
        $stmt = $this->pdo->prepare("SELECT * FROM exams WHERE course_id = ? AND is_published = 1");
        $stmt->execute([$courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Calculate exam score
    public function calculateScore($examId, $answers) {
        $questions = $this->getExamQuestions($examId);
        $totalScore = 0;
        $achievedScore = 0;

        foreach ($questions as $question) {
            $totalScore += $question['points'];
            $correctAnswers = $this->getCorrectAnswers($question['id']);

            if ($question['question_type_id'] == 1) { // Multiple choice
                if (isset($answers[$question['id']]) && in_array($answers[$question['id']], $correctAnswers)) {
                    $achievedScore += $question['points'];
                }
            } 
            // Add other question type handling here
        }

        return [
            'total_score' => $totalScore,
            'achieved_score' => $achievedScore,
            'percentage' => ($totalScore > 0) ? round(($achievedScore / $totalScore) * 100) : 0
        ];
    }

    // Get correct answers for a question
    private function getCorrectAnswers($questionId) {
        $stmt = $this->pdo->prepare("SELECT id FROM answers WHERE question_id = ? AND is_correct = 1");
        $stmt->execute([$questionId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Get exam questions with answers
    public function getExamQuestionsWithAnswers($examId) {
        $question = new Question($this->pdo);
        $questions = $question->getExamQuestions($examId);

        foreach ($questions as &$q) {
            $q['answers'] = $question->getQuestionAnswers($q['id']);
        }

        return $questions;
    }

    // Delete exam
    public function deleteExam($examId) {
        // First remove questions from exam
        $stmt = $this->pdo->prepare("DELETE FROM exam_questions WHERE exam_id = ?");
        $stmt->execute([$examId]);

        // Then delete the exam
        $stmt = $this->pdo->prepare("DELETE FROM exams WHERE id = ?");
        return $stmt->execute([$examId]);
    }
}
