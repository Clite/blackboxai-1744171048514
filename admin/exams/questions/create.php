<?php
require_once '../../../config.php';
require_once '../../../classes/Exam.php';
require_once '../../../classes/Question.php';
require_once '../../../classes/Course.php';
require_once '../../../classes/User.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect(BASE_URL . '/dashboard.php');
}

$exam = new Exam($pdo);
$question = new Question($pdo);
$user = new User($pdo);
$currentUser = $user->getUserById($_SESSION['user_id']);

// Check if exam ID is provided
if (!isset($_GET['exam_id'])) {
    redirect(BASE_URL . '/admin/exams.php');
}

$examId = (int)$_GET['exam_id'];
$examData = $exam->getExamById($examId);

if (!$examData) {
    redirect(BASE_URL . '/admin/exams.php');
}

$errors = [];
$formData = [
    'question_text' => '',
    'question_type' => 'multiple_choice',
    'points' => '1',
    'options' => [
        ['text' => '', 'is_correct' => false],
        ['text' => '', 'is_correct' => false],
        ['text' => '', 'is_correct' => false],
        ['text' => '', 'is_correct' => false]
    ],
    'correct_answer' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'question_text' => sanitize($_POST['question_text']),
        'question_type' => sanitize($_POST['question_type']),
        'points' => sanitize($_POST['points']),
        'options' => isset($_POST['options']) ? $_POST['options'] : [],
        'correct_answer' => sanitize($_POST['correct_answer'])
    ];

    // Validate inputs
    if (empty($formData['question_text'])) $errors[] = 'Question text is required';
    if (!is_numeric($formData['points']) || $formData['points'] <= 0) $errors[] = 'Invalid points value';
    
    if ($formData['question_type'] === 'multiple_choice') {
        $hasCorrectOption = false;
        foreach ($formData['options'] as $option) {
            if (!empty($option['text']) && $option['is_correct']) {
                $hasCorrectOption = true;
                break;
            }
        }
        if (!$hasCorrectOption) $errors[] = 'At least one correct option is required for multiple choice questions';
    } else {
        if (empty($formData['correct_answer'])) $errors[] = 'Correct answer is required for this question type';
    }

    if (empty($errors)) {
        if ($question->createQuestion(
            $examId,
            $formData['question_text'],
            $formData['question_type'],
            $formData['points'],
            $formData['question_type'] === 'multiple_choice' ? $formData['options'] : null,
            $formData['question_type'] !== 'multiple_choice' ? $formData['correct_answer'] : null
        )) {
            redirect(BASE_URL . "/admin/exams/questions.php?id=$examId&created=1");
        } else {
            $errors[] = 'Failed to create question. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Question - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
        function toggleQuestionType() {
            const questionType = document.getElementById('question_type').value;
            document.getElementById('multiple-choice-section').style.display = questionType === 'multiple_choice' ? 'block' : 'none';
            document.getElementById('text-answer-section').style.display = questionType !== 'multiple_choice' ? 'block' : 'none';
        }
    </script>
</head>
<body class="bg-gray-100" onload="toggleQuestionType()">
    <!-- Header -->
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900">Create Question</h1>
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
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <a href="<?= BASE_URL ?>/admin/exams/questions.php?id=<?= $examId ?>" class="text-blue-500 hover:text-blue-700 mb-4 inline-block">
                    <i class="fas fa-arrow-left"></i> Back to Questions
                </a>
                
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Create New Question for: <?= $examData['title'] ?></h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <?php foreach ($errors as $error): ?>
                            <p><?= $error ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="grid grid-cols-1 gap-6 mb-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="question_text">
                                Question Text *
                            </label>
                            <textarea class="w-full px-3 py-2 border rounded" id="question_text" name="question_text" rows="3" required><?= htmlspecialchars($formData['question_text']) ?></textarea>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="question_type">
                                    Question Type *
                                </label>
                                <select class="w-full px-3 py-2 border rounded" id="question_type" name="question_type" onchange="toggleQuestionType()" required>
                                    <option value="multiple_choice" <?= $formData['question_type'] === 'multiple_choice' ? 'selected' : '' ?>>Multiple Choice</option>
                                    <option value="short_answer" <?= $formData['question_type'] === 'short_answer' ? 'selected' : '' ?>>Short Answer</option>
                                    <option value="true_false" <?= $formData['question_type'] === 'true_false' ? 'selected' : '' ?>>True/False</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="points">
                                    Points *
                                </label>
                                <input class="w-full px-3 py-2 border rounded" type="number" min="1" id="points" name="points" 
                                    value="<?= htmlspecialchars($formData['points']) ?>" required>
                            </div>
                        </div>
                        
                        <!-- Multiple Choice Options -->
                        <div id="multiple-choice-section" style="display: none;">
                            <h3 class="text-lg font-medium text-gray-800 mb-3">Multiple Choice Options</h3>
                            <div class="space-y-4">
                                <?php foreach ($formData['options'] as $index => $option): ?>
                                    <div class="flex items-center">
                                        <input type="checkbox" name="options[<?= $index ?>][is_correct]" 
                                            <?= $option['is_correct'] ? 'checked' : '' ?> 
                                            class="form-checkbox h-4 w-4 text-blue-600 transition duration-150 ease-in-out">
                                        <input type="text" name="options[<?= $index ?>][text]" 
                                            value="<?= htmlspecialchars($option['text']) ?>" 
                                            class="ml-2 flex-1 px-3 py-2 border rounded" placeholder="Option <?= $index + 1 ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Text Answer Section -->
                        <div id="text-answer-section" style="display: none;">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="correct_answer">
                                    Correct Answer *
                                </label>
                                <input class="w-full px-3 py-2 border rounded" type="text" id="correct_answer" name="correct_answer" 
                                    value="<?= htmlspecialchars($formData['correct_answer']) ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Create Question
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
