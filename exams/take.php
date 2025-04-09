<?php
require_once '../config.php';
require_once '../classes/User.php';
require_once '../classes/Course.php';
require_once '../classes/Exam.php';
require_once '../classes/Question.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

if (!isset($_GET['id'])) {
    redirect(BASE_URL . '/dashboard.php');
}

$examId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];

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

$question = new Question($pdo);
$questions = $question->getExamQuestionsWithAnswers($examId);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = $_POST['answers'] ?? [];
    $result = $exam->calculateScore($examId, $answers);
    
    // Save exam attempt (in a real app, you'd save this to database)
    $_SESSION['last_exam_result'] = $result;
    redirect(BASE_URL . '/exams/result.php?exam_id=' . $examId);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $examDetails['title'] ?> - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
        // Timer functionality
        let timeLeft = <?= $examDetails['time_limit_minutes'] ? $examDetails['time_limit_minutes'] * 60 : 0 ?>;
        
        function updateTimer() {
            if (timeLeft <= 0) {
                document.getElementById('exam-form').submit();
                return;
            }
            
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById('timer').textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            timeLeft--;
        }
        
        <?php if ($examDetails['time_limit_minutes']): ?>
            setInterval(updateTimer, 1000);
            window.addEventListener('load', updateTimer);
        <?php endif; ?>
    </script>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold text-gray-900"><?= $examDetails['title'] ?></h1>
                <p class="text-sm text-gray-600">Course: <?= $courseDetails['title'] ?></p>
            </div>
            <?php if ($examDetails['time_limit_minutes']): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded">
                    <i class="fas fa-clock"></i> Time Remaining: <span id="timer"></span>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <form id="exam-form" method="POST">
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Exam Instructions</h2>
                    <p class="text-gray-700 mb-4"><?= nl2br($examDetails['description']) ?></p>
                    <p class="text-gray-700">
                        <strong>Total Questions:</strong> <?= count($questions) ?> | 
                        <strong>Passing Score:</strong> <?= $examDetails['passing_score'] ?? 'Not specified' ?>%
                    </p>
                </div>
            </div>

            <!-- Questions -->
            <div class="space-y-6">
                <?php foreach ($questions as $index => $q): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-start mb-4">
                                <span class="bg-blue-100 text-blue-800 font-bold px-3 py-1 rounded-full mr-3">
                                    <?= $index + 1 ?>
                                </span>
                                <div>
                                    <h3 class="text-lg font-medium text-gray-800"><?= $q['question_text'] ?></h3>
                                    <p class="text-sm text-gray-600">Points: <?= $q['points'] ?></p>
                                </div>
                            </div>
                            
                            <div class="ml-10 space-y-2">
                                <?php foreach ($q['answers'] as $answer): ?>
                                    <div class="flex items-center">
                                        <input type="radio" id="answer-<?= $q['id'] ?>-<?= $answer['id'] ?>" 
                                            name="answers[<?= $q['id'] ?>]" value="<?= $answer['id'] ?>"
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                        <label for="answer-<?= $q['id'] ?>-<?= $answer['id'] ?>" class="ml-2 text-gray-700">
                                            <?= $answer['answer_text'] ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-6 text-center">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg text-lg">
                    Submit Exam
                </button>
            </div>
        </form>
    </main>
</body>
</html>
