<?php
session_start();
// Database configuration
include 'config.php';

if (!isset($_SESSION['current_exam']) || !isset($_SESSION['user_id'])) {
    header("Location: exam_entry.php");
    exit();
}

$exam_id = $_SESSION['current_exam'];
$user_id = $_SESSION['user_id'];

// Calculate score
$score = 0;
$total_points = 0;

// Get all questions for this exam
$questions_stmt = $conn->prepare("SELECT question_id, correct_answer, points FROM questions WHERE exam_id = ?");
$questions_stmt->bind_param("i", $exam_id);
$questions_stmt->execute();
$questions = $questions_stmt->get_result();

while ($question = $questions->fetch_assoc()) {
    $total_points += $question['points'];
    
    $answer_key = 'q' . $question['question_id'];
    if (isset($_POST[$answer_key]) && $_POST[$answer_key] === $question['correct_answer']) {
        $score += $question['points'];
    }
}

// Save result
$result_stmt = $conn->prepare("INSERT INTO exam_results (exam_id, participant_id, score) VALUES (?, ?, ?)");
$result_stmt->bind_param("isi", $exam_id, $user_id, $score);
$result_stmt->execute();

// Clear session
unset($_SESSION['current_exam']);

// Determine message based on score (optional, but good for design)
$pass_threshold = 0.6; // 60% to pass
$percentage_score = ($total_points > 0) ? ($score / $total_points) : 0;
$result_message = "Thank you for completing the exam. Your results have been recorded.";
$score_color_class = "text-blue-600"; // Default color

if ($percentage_score >= $pass_threshold) {
    $result_message = "Congratulations! You passed the exam!";
    $score_color_class = "text-green-600";
} elseif ($total_points > 0) {
    $result_message = "You completed the exam. Keep practicing to improve!";
    $score_color_class = "text-red-600";
}

// Display results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Submitted - ExamFlow</title>
    <!-- Google Fonts - Inter for a modern look -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom CSS to define the Inter font and ensure smooth scrolling */
        body {
            font-family: 'Inter', sans-serif;
            scroll-behavior: smooth;
            /* Background animation styles */
            background: linear-gradient(-45deg, #e0f7fa, #b2ebf2, #80deea, #4dd0e1); /* More vibrant subtle gradient */
            background-size: 400% 400%; /* Make background larger than viewport to allow animation */
            animation: backgroundAnimation 25s ease infinite alternate; /* Slower, smoother animation */
            display: flex;
            flex-direction: column; /* Allows header and content to stack */
            min-height: 100vh; /* Full viewport height */
        }

        /* Keyframes for the background animation */
        @keyframes backgroundAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Base styles for buttons for consistency with other pages */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 9999px; /* Rounded-full */
            font-weight: 600;
            transition: all 0.2s ease-in-out;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background-color: #3b82f6; /* Tailwind blue-500 */
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .btn-primary:hover {
            background-color: #2563eb; /* Tailwind blue-700 */
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
    </style>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="text-gray-800 antialiased">
    <!-- Header Section (Consistent with Dashboard) -->
    <header class="bg-white shadow-md py-5 mb-8">
        <div class="container mx-auto px-4 flex flex-col sm:flex-row justify-between items-center">
            <!-- Logo -->
            <a href="dashboard.php" class="text-3xl font-extrabold text-blue-600 tracking-tight hover:text-blue-700 transition duration-300 mb-4 sm:mb-0">ExamFlow</a>
            
            <!-- Optional: Back to Dashboard Button -->
            <a href="dashboard.php" class="inline-flex items-center justify-center bg-gray-200 text-gray-800 py-2 px-4 rounded-full font-semibold text-sm hover:bg-gray-300 transition duration-300 shadow-md">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </header>

    <main class="flex-1 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl p-8 max-w-lg w-full text-center border border-gray-100 transform transition-transform duration-300 hover:scale-[1.01]">
            <div class="mb-6">
                <?php if ($percentage_score >= $pass_threshold): ?>
                    <i class="fas fa-award text-7xl text-green-500 mb-4 animate-bounce"></i>
                    <h1 class="text-4xl font-bold text-green-700 mb-2">Excellent!</h1>
                <?php else: ?>
                    <i class="fas fa-clipboard-check text-7xl text-blue-500 mb-4 animate-pulse"></i>
                    <h1 class="text-4xl font-bold text-blue-700 mb-2">Exam Completed</h1>
                <?php endif; ?>
            </div>
            
            <p class="text-xl text-gray-700 mb-4 font-medium"><?php echo htmlspecialchars($result_message); ?></p>

            <div class="score mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <p class="text-lg text-gray-600 font-semibold mb-2">Your Score:</p>
                <div class="flex items-center justify-center gap-4">
                    <span class="score-value text-6xl font-extrabold <?php echo $score_color_class; ?> leading-none">
                        <?php echo $score; ?>
                    </span>
                    <span class="text-4xl font-bold text-gray-400">/</span>
                    <span class="total-points text-4xl font-bold text-gray-600">
                        <?php echo $total_points; ?>
                    </span>
                </div>
                 <?php if ($total_points > 0): ?>
                    <p class="text-sm text-gray-500 mt-2">(<?php echo round($percentage_score * 100); ?>%)</p>
                <?php endif; ?>
            </div>
            
            <a href="dashboard.php" class="btn btn-primary text-lg px-8 py-3 mt-6 shadow-lg hover:shadow-xl">
                <i class="fas fa-tachometer-alt mr-2"></i>Go to Dashboard
            </a>
        </div>
    </main>
</body>
</html>
