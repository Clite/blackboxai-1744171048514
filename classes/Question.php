<?php
class Question {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Create a new question
    public function createQuestion($courseId, $questionTypeId, $questionText, $points = 1) {
        $stmt = $this->pdo->prepare("INSERT INTO questions (course_id, question_type_id, question_text, points) VALUES (?, ?, ?, ?)");
        $stmt->execute([$courseId, $questionTypeId, $questionText, $points]);
        return $this->pdo->lastInsertId();
    }

    // Add answer options (for multiple choice)
    public function addAnswer($questionId, $answerText, $isCorrect = false) {
        $stmt = $this->pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");
        return $stmt->execute([$questionId, $answerText, $isCorrect]);
    }

    // Get question by ID
    public function getQuestion($questionId) {
        $stmt = $this->pdo->prepare("SELECT * FROM questions WHERE id = ?");
        $stmt->execute([$questionId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get all questions for a course
    public function getCourseQuestions($courseId) {
        $stmt = $this->pdo->prepare("SELECT * FROM questions WHERE course_id = ?");
        $stmt->execute([$courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get answers for a question
    public function getQuestionAnswers($questionId) {
        $stmt = $this->pdo->prepare("SELECT * FROM answers WHERE question_id = ?");
        $stmt->execute([$questionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get all question types
    public function getQuestionTypes() {
        $stmt = $this->pdo->prepare("SELECT * FROM question_types");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add question to exam
    public function addQuestionToExam($examId, $questionId) {
        $stmt = $this->pdo->prepare("INSERT INTO exam_questions (exam_id, question_id) VALUES (?, ?)");
        return $stmt->execute([$examId, $questionId]);
    }

    // Get exam questions
    public function getExamQuestions($examId) {
        $stmt = $this->pdo->prepare("SELECT q.* FROM questions q JOIN exam_questions eq ON q.id = eq.question_id WHERE eq.exam_id = ?");
        $stmt->execute([$examId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Delete question
    public function deleteQuestion($questionId) {
        // First delete answers
        $stmt = $this->pdo->prepare("DELETE FROM answers WHERE question_id = ?");
        $stmt->execute([$questionId]);

        // Then delete from exam_questions
        $stmt = $this->pdo->prepare("DELETE FROM exam_questions WHERE question_id = ?");
        $stmt->execute([$questionId]);

        // Finally delete the question
        $stmt = $this->pdo->prepare("DELETE FROM questions WHERE id = ?");
        return $stmt->execute([$questionId]);
    }
}
