<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['exam_id'])) {
    header("Location: dashboard.php");
    exit();
}

$exam_id = $_GET['exam_id'];
$user_id = $_SESSION['user_id'];

// Verify the user owns this exam or has taken it
$exam_check = $conn->prepare("
    SELECT e.exam_id, e.title, e.description 
    FROM exams e
    LEFT JOIN exam_results er ON e.exam_id = er.exam_id
    WHERE e.exam_id = ? AND (e.user_id = ? OR er.participant_id = ?)
    GROUP BY e.exam_id
");
$exam_check->bind_param("iss", $exam_id, $user_id, $user_id);
$exam_check->execute();
$exam = $exam_check->get_result()->fetch_assoc();

if (!$exam) {
    header("Location: dashboard.php");
    exit();
}

// Get all results for this exam
$results_stmt = $conn->prepare("
    SELECT 
        er.result_id,
        u.name as participant_name,
        er.score,
        (SELECT SUM(points) FROM questions WHERE exam_id = e.exam_id) as total_points,
        er.completed_at,
        (SELECT COUNT(*) FROM exam_results WHERE exam_id = e.exam_id) as total_attempts,
        (SELECT COUNT(*) FROM exam_results er2 
         WHERE er2.exam_id = e.exam_id AND er2.score >= er.score) as rank
    FROM exam_results er
    JOIN exams e ON er.exam_id = e.exam_id
    JOIN users u ON er.participant_id = u.id
    WHERE er.exam_id = ?
    ORDER BY er.score DESC, er.completed_at ASC
");
$results_stmt->bind_param("i", $exam_id);
$results_stmt->execute();
$results = $results_stmt->get_result();

// Get question count and total points
$questions_stmt = $conn->prepare("
    SELECT COUNT(*) as question_count, SUM(points) as total_points 
    FROM questions 
    WHERE exam_id = ?
");
$questions_stmt->bind_param("i", $exam_id);
$questions_stmt->execute();
$questions_info = $questions_stmt->get_result()->fetch_assoc();
$total_points = $questions_info['total_points'] ?? 0;
$question_count = $questions_info['question_count'] ?? 0;

// Calculate statistics
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as attempt_count,
        AVG(score) as avg_score,
        MIN(score) as min_score,
        MAX(score) as max_score
    FROM exam_results
    WHERE exam_id = ?
");
$stats_stmt->bind_param("i", $exam_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Calculate percentages
if ($stats['attempt_count'] > 0 && $total_points > 0) {
    $avg_percentage = round(($stats['avg_score'] / $total_points) * 100);
    $min_percentage = round(($stats['min_score'] / $total_points) * 100);
    $max_percentage = round(($stats['max_score'] / $total_points) * 100);
} else {
    $avg_percentage = $min_percentage = $max_percentage = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results - ExamFlow</title>
    <!-- Google Fonts - Inter for a modern look -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom CSS for background animation */
        body {
            font-family: 'Inter', sans-serif;
            scroll-behavior: smooth;
            background: linear-gradient(135deg, #e0f2fe 0%, #c1d9f0 100%); /* Light blue to slightly darker blue gradient */
            background-size: 400% 400%; /* Make background larger than viewport to allow animation */
            animation: backgroundAnimation 20s ease infinite alternate; /* Slower animation for subtle effect */
        }

        /* Keyframes for the background animation */
        @keyframes backgroundAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    </style>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="min-h-screen text-gray-800 antialiased">
    <!-- Header Section -->
    <header class="bg-white shadow-xl py-5 mb-8">
        <div class="container mx-auto px-4 flex flex-col sm:flex-row justify-between items-center">
            <!-- Logo -->
            <a href="dashboard.php" class="text-3xl font-extrabold text-blue-600 tracking-tight hover:text-blue-700 transition duration-300 mb-4 sm:mb-0">ExamFlow</a>
            
            <!-- Back to Dashboard Button -->
            <a href="dashboard.php" class="inline-flex items-center justify-center bg-gray-200 text-gray-800 py-2 px-4 rounded-full font-semibold text-sm hover:bg-gray-300 transition duration-300 shadow-md">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </header>
    
    <!-- Main Content Container -->
    <div class="container mx-auto px-4">
        <!-- Exam Header Section -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8 border border-gray-200">
            <h1 class="text-3xl sm:text-4xl font-bold text-blue-700 mb-3"><?php echo htmlspecialchars($exam['title']); ?></h1>
            <p class="text-gray-600 text-base sm:text-lg mb-4"><?php echo htmlspecialchars($exam['description']); ?></p>
            <div class="flex flex-wrap gap-x-6 gap-y-2 text-gray-500 text-sm">
                <span><i class="fas fa-clipboard-question mr-1 text-blue-500"></i><?php echo $question_count; ?> Questions</span>
                <span><i class="fas fa-coins mr-1 text-yellow-500"></i><?php echo $total_points; ?> Total Points</span>
                <span><i class="fas fa-users-rays mr-1 text-green-500"></i><?php echo $stats['attempt_count']; ?> Attempts</span>
            </div>
        </div>
        
        <h2 class="text-2xl font-semibold text-gray-700 border-b border-gray-300 pb-3 mb-6">Exam Statistics</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
            <!-- Average Score Card -->
            <div class="bg-white rounded-xl shadow-md p-6 border border-blue-100 flex flex-col items-start transition-all duration-300 hover:shadow-lg">
                <div class="text-sm font-medium text-gray-500 mb-2">Average Score</div>
                <div class="text-3xl font-bold text-blue-600 mb-1"><?php echo round($stats['avg_score'], 1); ?><span class="text-xl text-gray-400">/<?php echo $total_points; ?></span></div>
                <div class="text-base text-gray-700 font-semibold"><?php echo $avg_percentage; ?>%</div>
            </div>
            
            <!-- Highest Score Card -->
            <div class="bg-white rounded-xl shadow-md p-6 border border-green-100 flex flex-col items-start transition-all duration-300 hover:shadow-lg">
                <div class="text-sm font-medium text-gray-500 mb-2">Highest Score</div>
                <div class="text-3xl font-bold text-green-600 mb-1"><?php echo $stats['max_score']; ?><span class="text-xl text-gray-400">/<?php echo $total_points; ?></span></div>
                <div class="text-base text-gray-700 font-semibold"><?php echo $max_percentage; ?>%</div>
            </div>
            
            <!-- Lowest Score Card -->
            <div class="bg-white rounded-xl shadow-md p-6 border border-red-100 flex flex-col items-start transition-all duration-300 hover:shadow-lg">
                <div class="text-sm font-medium text-gray-500 mb-2">Lowest Score</div>
                <div class="text-3xl font-bold text-red-600 mb-1"><?php echo $stats['min_score']; ?><span class="text-xl text-gray-400">/<?php echo $total_points; ?></span></div>
                <div class="text-base text-gray-700 font-semibold"><?php echo $min_percentage; ?>%</div>
            </div>
        </div>
        
        <h2 class="text-2xl font-semibold text-gray-700 border-b border-gray-300 pb-3 mb-6">All Attempts</h2>
        <?php if ($results->num_rows > 0): ?>
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 mb-10">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-blue-600 text-white">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-medium uppercase tracking-wider rounded-tl-xl">Participant</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-medium uppercase tracking-wider">Score</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-medium uppercase tracking-wider">Rank</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-medium uppercase tracking-wider rounded-tr-xl">Completed At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php while ($result = $results->fetch_assoc()): 
                                $percentage = ($result['score'] / $result['total_points']) * 100;
                                $passed = $percentage >= 60; // Assuming 60% is passing
                                $rank_percentage = ($result['rank'] / $result['total_attempts']) * 100;
                            ?>
                                <tr class="hover:bg-blue-50 transition duration-150 ease-in-out">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($result['participant_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold <?php echo $passed ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo $result['score']; ?>/<?php echo $result['total_points']; ?>
                                        <span class="text-xs text-gray-500 ml-1">(<?php echo round($percentage); ?>%)</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?php if ($result['total_attempts'] > 1): ?>
                                            #<?php echo $result['rank']; ?> of <?php echo $result['total_attempts']; ?>
                                            <span class="text-xs text-gray-500 ml-1">(Top <?php echo round($rank_percentage); ?>%)</span>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y g:i A', strtotime($result['completed_at'])); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow-lg p-10 text-center border border-gray-200 mb-10">
                <i class="fas fa-exclamation-circle text-6xl text-gray-400 mb-6"></i>
                <p class="text-gray-600 text-lg">No attempts have been made on this exam yet.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
