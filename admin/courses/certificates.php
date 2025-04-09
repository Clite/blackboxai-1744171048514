<?php
require_once '../../config.php';
require_once '../../classes/User.php';
require_once '../../classes/Course.php';
require_once '../../classes/Exam.php';
require_once '../../classes/ExamResult.php';
require_once '../../classes/Certificate.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect(BASE_URL . '/dashboard.php');
}

$user = new User($pdo);
$course = new Course($pdo);
$exam = new Exam($pdo);
$examResult = new ExamResult($pdo);
$certificate = new Certificate($pdo);
$currentUser = $user->getUserById($_SESSION['user_id']);

// Check if course ID is provided
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : null;
$courseData = $courseId ? $course->getCourseById($courseId) : null;

// Get all courses for dropdown
$allCourses = $course->getAllCourses();

// Get students who completed the course
$completedStudents = [];
if ($courseId) {
    $completedStudents = $certificate->getStudentsCompletedCourse($courseId);
}

// Handle certificate generation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_certificate'])) {
        $userId = (int)$_POST['user_id'];
        $student = $user->getUserById($userId);
        
        if ($student && $courseData) {
            // Generate certificate PDF
            $certificateId = $certificate->generateCertificate($userId, $courseId, $currentUser['id']);
            
            if ($certificateId) {
                redirect(BASE_URL . "/admin/courses/certificates.php?course_id={$courseId}&generated=1");
            } else {
                redirect(BASE_URL . "/admin/courses/certificates.php?course_id={$courseId}&error=generate_failed");
            }
        }
    }
    
    if (isset($_POST['generate_all_certificates'])) {
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($completedStudents as $student) {
            if (!$certificate->certificateExists($student['id'], $courseId)) {
                if ($certificate->generateCertificate($student['id'], $courseId, $currentUser['id'])) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            }
        }
        
        redirect(BASE_URL . "/admin/courses/certificates.php?course_id={$courseId}&bulk_generated=1&success={$successCount}&errors={$errorCount}");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Certificates - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900">Course Certificates</h1>
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
                        <a href="<?= BASE_URL ?>/admin/courses.php" class="bg-gray-900 text-white px-3 py-2 rounded-md text-sm font-medium">
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
        <?php if (isset($_GET['generated'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                Certificate generated successfully.
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                Failed to generate certificate. Please try again.
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['bulk_generated'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                Bulk certificate generation completed. <?= $_GET['success'] ?> succeeded, <?= $_GET['errors'] ?> failed.
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Certificate Management</h2>
                
                <!-- Course Selection -->
                <form method="GET" class="mb-6">
                    <div class="flex items-end space-x-4">
                        <div class="flex-1">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="course_id">
                                Select Course
                            </label>
                            <select class="w-full px-3 py-2 border rounded" id="course_id" name="course_id" onchange="this.form.submit()" required>
                                <option value="">-- Select a Course --</option>
                                <?php foreach ($allCourses as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= $courseId === $c['id'] ? 'selected' : '' ?>>
                                        <?= $c['title'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($courseId): ?>
                            <button type="button" onclick="window.open('<?= BASE_URL ?>/certificate/preview.php?course_id=<?= $courseId ?>', '_blank')" 
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                <i class="fas fa-eye mr-2"></i> Preview Template
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
                
                <?php if ($courseId): ?>
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-800">Students Completed: <?= $courseData['title'] ?></h3>
                        
                        <?php if (count($completedStudents) > 0): ?>
                            <form method="POST">
                                <button type="submit" name="generate_all_certificates" 
                                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    <i class="fas fa-certificate mr-2"></i> Generate All Certificates
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (count($completedStudents) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed On</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Average Score</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Certificate</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($completedStudents as $student): ?>
                                        <?php $certificateData = $certificate->getCertificate($student['id'], $courseId); ?>
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
                                                <?= date('M j, Y', strtotime($student['completed_at'])) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= $student['average_score'] ?>%
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php if ($certificateData): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        Generated
                                                    </span>
                                                    <div class="text-xs text-gray-500 mt-1">
                                                        <?= date('M j, Y', strtotime($certificateData['created_at'])) ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                        Pending
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <?php if ($certificateData): ?>
                                                    <a href="<?= BASE_URL ?>/certificate/download.php?id=<?= $certificateData['id'] ?>" 
                                                        class="text-blue-500 hover:text-blue-700 mr-3">
                                                        <i class="fas fa-download mr-1"></i> Download
                                                    </a>
                                                <?php else: ?>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="user_id" value="<?= $student['id'] ?>">
                                                        <button type="submit" name="generate_certificate" 
                                                            class="text-green-500 hover:text-green-700">
                                                            <i class="fas fa-certificate mr-1"></i> Generate
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500">No students have completed this course yet.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-gray-500">Please select a course to view certificate information.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Certificate Template Configuration -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Certificate Template Configuration</h2>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 gap-6 mb-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="template_file">
                                Certificate Template (PDF)
                            </label>
                            <input class="w-full px-3 py-2 border rounded" type="file" id="template_file" name="template_file" accept=".pdf">
                            <p class="text-xs text-gray-500 mt-1">Upload a PDF template with placeholders for dynamic fields</p>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Available Placeholders
                            </label>
                            <div class="bg-gray-50 p-4 rounded">
                                <ul class="list-disc pl-5 space-y-1">
                                    <li><code>{student_name}</code> - Student's full name</li>
                                    <li><code>{course_title}</code> - Course title</li>
                                    <li><code>{completion_date}</code> - Course completion date</li>
                                    <li><code>{average_score}</code> - Average exam score</li>
                                    <li><code>{certificate_id}</code> - Unique certificate ID</li>
                                    <li><code>{issued_date}</code> - Certificate issue date</li>
                                    <li><code>{issuer_name}</code> - Admin who issued the certificate</li>
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
