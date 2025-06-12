<?php
session_start();
// Database configuration
include 'config.php';

if (!isset($_SESSION['current_exam'])) {
    header("Location: exam_entry.php");
    exit();
}

$exam_id = $_SESSION['current_exam'];
$user_id = $_SESSION['user_id']; // Assuming user is logged in

// Get exam details
$exam_stmt = $conn->prepare("SELECT title, description, time_limit, end_time FROM exams WHERE exam_id = ?"); // Added end_time
$exam_stmt->bind_param("i", $exam_id);
$exam_stmt->execute();
$exam_result = $exam_stmt->get_result();
$exam = $exam_result->fetch_assoc();

if (!$exam) {
    // Handle case where exam is not found
    header("Location: dashboard.php");
    exit();
}

// Check if the exam has already ended
// If the exam has an 'end_time' set and it's in the past, redirect.
// This is a basic check; more robust checks might be needed for active exams.
if (isset($exam['end_time']) && strtotime($exam['end_time']) < time()) {
    // Optionally, set an error message before redirecting
    // $_SESSION['error_msg'] = "This exam has already ended.";
    header("Location: dashboard.php"); // Redirect if exam already ended
    exit();
}

// Get questions
$questions_stmt = $conn->prepare("SELECT question_id, question_text, option_a, option_b, option_c, option_d, points FROM questions WHERE exam_id = ? ORDER BY question_id");
$questions_stmt->bind_param("i", $exam_id);
$questions_stmt->execute();
$questions = $questions_stmt->get_result();

// Calculate total points
$total_points = 0;
// We need to fetch all questions into an array to iterate over them multiple times
$all_questions = [];
while ($question = $questions->fetch_assoc()) {
    $total_points += $question['points'];
    $all_questions[] = $question;
}
// $questions->data_seek(0); // No longer needed if we use $all_questions array

// Calculate initial time left based on time_limit
$initial_time_limit_seconds = $exam['time_limit'] * 60;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamFlow - <?php echo htmlspecialchars($exam['title']); ?></title>
    <!-- Google Fonts - Inter for a modern look -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Exam Page specific styles */
        body {
            height: 100vh;
            height: calc(var(--vh, 1vh) * 100); /* Mobile viewport height fix */
            display: flex;
            flex-direction: column;
            overflow: hidden;
            font-family: 'Inter', sans-serif;
            scroll-behavior: smooth;
            /* Enhanced Background animation styles */
            background: linear-gradient(-45deg, #e0f7fa, #b2ebf2, #80deea, #4dd0e1); /* More vibrant subtle gradient */
            background-size: 400% 400%; /* Make background larger than viewport to allow animation */
            animation: backgroundAnimation 25s ease infinite alternate; /* Slower, smoother animation */
        }

        /* Keyframes for the background animation */
        @keyframes backgroundAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Define color variables for consistency, though Tailwind handles most */
        :root {
            --primary: #3b82f6; /* Tailwind blue-500 */
            --primary-dark: #2563eb; /* Tailwind blue-700 */
            --secondary: #10b981; /* Tailwind emerald-500 */
            --danger: #ef4444; /* Tailwind red-500 */
            --light: #f3f4f6; /* Tailwind gray-100 */
            --dark: #1f2937; /* Tailwind gray-900 */
            --gray: #6b7280; /* Tailwind gray-500 */
            --light-gray: #e5e7eb; /* Tailwind gray-200 */
            --medium-gray: #d1d5db; /* Tailwind gray-300 */
            --background: #f9fafb; /* Tailwind gray-50 */
            --white: #ffffff;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); /* Adjusted from default */
            --radius-sm: 0.25rem; /* 4px */
            --radius-md: 0.5rem; /* 8px */
            --radius-lg: 0.75rem; /* 12px */
            --space-xs: 0.5rem; /* 8px */
            --space-sm: 0.75rem; /* 12px */
            --space-md: 1rem; /* 16px */
            --space-lg: 1.5rem; /* 24px */
        }

        /* Exam Header */
        .exam-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background-color: var(--white);
            box-shadow: var(--shadow-md); /* Slightly more prominent shadow */
            padding: var(--space-sm) 0;
        }
        
        .exam-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .exam-title-text { /* Renamed to avoid conflict with actual element */
            font-size: 1.25rem;
            font-weight: 600; /* Bolder font */
            color: var(--dark);
            margin: 0;
            max-width: 60%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .exam-timer {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }
        
        .exam-timer-count {
            font-weight: 700; /* Extra bold */
            font-size: 1.25rem; /* Slightly larger */
            color: var(--primary);
            transition: color 0.3s ease-in-out; /* Smooth color transition */
        }
        
        .exam-timer-text {
            font-size: 0.75rem;
            color: var(--gray);
        }
        
        /* Main Content */
        .exam-content {
            flex: 1;
            display: flex;
            overflow: hidden;
            position: relative;
        }
        
        /* Sidebar Navigation */
        .question-nav {
            width: 280px; /* Slightly wider sidebar */
            background-color: var(--white);
            border-right: 1px solid var(--medium-gray);
            padding: var(--space-md);
            overflow-y: auto;
            transition: transform 0.3s ease;
            flex-shrink: 0; /* Prevent shrinking on larger screens */
            box-shadow: var(--shadow-lg); /* More prominent shadow for sidebar */
        }
        
        .nav-section {
            margin-bottom: var(--space-md);
        }
        
        .nav-section-title {
            font-size: 0.95rem; /* Slightly larger */
            color: var(--gray);
            margin-bottom: var(--space-sm);
            font-weight: 600; /* Bolder title */
            letter-spacing: 0.05em; /* Slight letter spacing */
            text-transform: uppercase;
        }
        
        .questions-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: var(--space-xs);
            margin-bottom: var(--space-md);
        }
        
        .question-marker {
            width: 100%;
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--light-gray);
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-weight: 600; /* Bolder number */
            color: var(--dark);
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid transparent;
            position: relative;
        }
        
        .question-marker:hover {
            background-color: var(--primary);
            color: var(--white);
            transform: scale(1.05); /* Subtle scale effect */
        }
        
        .question-marker.current {
            border-color: var(--primary-dark); /* Darker blue border */
            background-color: var(--white);
            color: var(--primary);
            box-shadow: var(--shadow-sm);
            transform: scale(1.05); /* Keep scaled for current */
        }
        
        .question-marker.answered {
            background-color: var(--primary);
            color: var(--white);
            border-color: var(--primary-dark);
        }
        
        .question-marker.flagged::after {
            content: '';
            position: absolute;
            top: -4px;
            right: -4px;
            width: 10px;
            height: 10px;
            background-color: #f59e0b; /* Tailwind amber-500 */
            border-radius: 50%;
            border: 2px solid var(--white); /* White border for better visibility */
        }
        
        .nav-actions {
            display: flex;
            flex-direction: column;
            gap: var(--space-sm);
        }

        .summary-stats .stat-item {
            border-bottom: 1px dashed var(--light-gray); /* Dashed separator */
        }
        .summary-stats .stat-item:last-child {
            border-bottom: none;
        }

        /* Question Area */
        .question-area {
            flex: 1;
            padding: var(--space-lg);
            overflow-y: auto;
            background-color: var(--background);
        }
        
        .question-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .question-card {
            background-color: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md); /* More prominent shadow */
            padding: var(--space-lg);
            margin-bottom: var(--space-lg);
            border: 1px solid var(--light-gray);
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-md);
        }
        
        .question-number {
            font-size: 0.95rem;
            color: var(--gray);
            font-weight: 500;
        }
        
        .question-points {
            font-size: 0.95rem;
            color: var(--primary);
            font-weight: 600;
        }
        
        .question-text {
            font-size: 1.15rem; /* Slightly larger text */
            margin-bottom: var(--space-lg);
            line-height: 1.6; /* Improved line height */
            color: var(--dark);
        }
        
        .question-options {
            display: flex;
            flex-direction: column;
            gap: var(--space-md);
        }
        
        .option-item {
            position: relative;
            display: flex;
            align-items: flex-start;
            padding: var(--space-md);
            border: 2px solid var(--medium-gray); /* Thicker border */
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-sm); /* Subtle shadow on options */
        }
        
        .option-item:hover {
            border-color: var(--primary);
            background-color: rgba(59, 130, 246, 0.08); /* blue-500 at 8% opacity */
            transform: translateY(-3px); /* More noticeable lift */
            box-shadow: var(--shadow-md); /* Stronger shadow on hover */
        }
        
        .option-item.selected {
            border-color: var(--primary-dark); /* Darker blue border */
            background-color: rgba(59, 130, 246, 0.15); /* blue-500 at 15% opacity */
            box-shadow: var(--shadow-md); /* Stronger shadow when selected */
            transform: translateY(0); /* Reset lift */
            animation: popIn 0.2s ease-out; /* Pop animation */
        }
        
        @keyframes popIn {
            0% { transform: scale(0.98); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }

        .option-marker {
            margin-right: var(--space-md);
            width: 28px; /* Larger marker */
            height: 28px; /* Larger marker */
            border: 2px solid var(--gray); /* Neutral border for default */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.2s ease;
            flex-shrink: 0;
            color: var(--dark);
            background-color: var(--light);
        }
        
        .option-item:hover .option-marker {
            border-color: var(--primary);
        }
        
        .option-item.selected .option-marker {
            border-color: var(--primary-dark);
            background-color: var(--primary-dark);
            color: var(--white);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3); /* Ring shadow for selected */
        }
        
        .option-text {
            flex: 1;
            line-height: 1.6;
            color: var(--dark);
        }
        
        /* For text/essay questions - keeping original styles for now if they are used */
        .answer-textarea {
            width: 100%;
            min-height: 200px;
            padding: var(--space-md);
            border: 1px solid var(--medium-gray);
            border-radius: var(--radius-md);
            font-size: 1rem;
            line-height: 1.5;
            transition: all 0.2s ease;
            resize: vertical;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05); /* Inset shadow */
        }
        
        .answer-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3), inset 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        /* File upload for certain question types */
        .file-upload {
            display: flex;
            flex-direction: column;
            gap: var(--space-md);
        }
        
        .upload-area {
            border: 2px dashed var(--medium-gray);
            border-radius: var(--radius-md);
            padding: var(--space-lg);
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background-color: var(--light);
        }
        
        .upload-area:hover {
            border-color: var(--primary);
            background-color: rgba(59, 130, 246, 0.05);
        }
        
        .upload-icon {
            font-size: 2.5rem; /* Larger icon */
            color: var(--primary);
            margin-bottom: var(--space-sm);
        }
        
        .upload-text {
            font-size: 0.95rem; /* Slightly larger text */
            color: var(--gray);
            font-weight: 500;
        }
        
        .upload-file-input {
            display: none;
        }
        
        /* Question flag */
        .question-actions {
            display: flex;
            justify-content: flex-end; /* Align flag button to the right */
            margin-top: var(--space-lg);
        }
        
        .question-flag {
            display: flex;
            align-items: center;
            gap: var(--space-xs);
            border: 1px solid var(--medium-gray); /* Subtle border */
            background-color: var(--light);
            color: var(--gray);
            font-size: 0.875rem;
            cursor: pointer;
            padding: var(--space-xs) var(--space-sm);
            border-radius: var(--radius-sm);
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .question-flag:hover {
            background-color: var(--medium-gray);
            color: var(--dark);
        }
        
        .question-flag.flagged {
            color: #f59e0b; /* Tailwind amber-500 */
            background-color: #fffbeb; /* Tailwind amber-50 light background */
            border-color: #fcd34d; /* Tailwind amber-300 */
            box-shadow: var(--shadow-sm);
        }
        
        .question-flag.flagged:hover {
             background-color: #fef3c7; /* slightly darker amber on hover */
        }

        /* Navigation buttons */
        .question-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: var(--space-lg);
            padding: var(--space-md); /* Add some padding around nav buttons */
            background-color: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--light-gray);
        }

        /* General Button Styles */
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

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
            box-shadow: var(--shadow-md);
        }
        .btn-primary:hover:not(:disabled) {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        .btn-primary:active:not(:disabled) {
            transform: translateY(0);
            box-shadow: var(--shadow-sm);
        }

        .btn-outline {
            background-color: transparent;
            color: var(--gray);
            border: 2px solid var(--medium-gray);
            box-shadow: var(--shadow-sm);
        }
        .btn-outline:hover:not(:disabled) {
            border-color: var(--primary);
            color: var(--primary);
            background-color: rgba(59, 130, 246, 0.05);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .btn-outline:active:not(:disabled) {
            transform: translateY(0);
            box-shadow: var(--shadow-sm);
        }

        /* Mobile sidebar toggle */
        .nav-toggle {
            display: none;
            align-items: center;
            justify-content: center;
            width: 48px; /* Larger touch target */
            height: 48px; /* Larger touch target */
            border-radius: 50%;
            background-color: var(--primary);
            color: var(--white);
            box-shadow: var(--shadow-lg); /* More prominent shadow */
            position: fixed;
            bottom: var(--space-lg);
            right: var(--space-lg);
            z-index: 50;
            cursor: pointer;
            border: none;
            font-size: 1.5rem; /* Larger icon */
            transition: all 0.2s ease-in-out;
        }
        .nav-toggle:hover {
            transform: scale(1.1);
            background-color: var(--primary-dark);
        }
        .nav-toggle:active {
            transform: scale(1);
        }
        
        /* Message Box Styling */
        #messageBox {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #4CAF50; /* Green background */
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            display: none; /* Hidden by default */
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
            font-weight: 600;
        }
        #messageBox.show {
            display: block;
            opacity: 1;
        }
    </style>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- Message Box -->
    <div id="messageBox"></div>

    <header class="exam-header">
        <div class="container mx-auto px-4">
            <h1 class="exam-title-text"><?php echo htmlspecialchars($exam['title']); ?></h1>
            <div class="exam-timer">
                <span class="exam-timer-count" id="timer">
                    <?php echo floor($initial_time_limit_seconds / 3600); ?>:<?php echo str_pad(floor(($initial_time_limit_seconds % 3600) / 60), 2, '0', STR_PAD_LEFT); ?>:<?php echo str_pad($initial_time_limit_seconds % 60, 2, '0', STR_PAD_LEFT); ?>
                </span>
                <span class="exam-timer-text">remaining</span>
            </div>
        </div>
    </header>
    
    <div class="exam-content">
        <!-- Question Navigation Sidebar -->
        <aside class="question-nav" id="questionNav">
            <button class="mobile-nav-close" id="mobileNavClose">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="nav-section">
                <h3 class="nav-section-title">Questions</h3>
                <div class="questions-grid">
                    <?php for ($i = 0; $i < count($all_questions); $i++): ?>
                        <button type="button" class="question-marker <?php echo ($i === 0) ? 'current' : ''; ?>" data-question="<?php echo $i + 1; ?>"><?php echo $i + 1; ?></button>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="nav-section">
                <h3 class="nav-section-title">Summary</h3>
                <div class="summary-stats space-y-2">
                    <div class="stat-item flex justify-between items-center py-1">
                        <span class="stat-label text-gray-700 flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i> Answered:</span>
                        <span class="stat-value font-semibold text-blue-600">0/<?php echo count($all_questions); ?></span>
                    </div>
            
                </div>
            </div>
            
            <div class="nav-actions">
                <button type="button" class="btn btn-primary w-full" id="submitExamBtn">
                    <i class="fas fa-paper-plane mr-2"></i>Submit Exam
                </button>
            </div>
        </aside>
        
        <!-- Main Question Area -->
        <main class="question-area">
            <div class="question-container">
                <!-- Mobile question info (only shows on mobile) -->
                <div class="mobile-question-info bg-gray-100 rounded-t-lg px-6 py-3 mb-4 md:hidden flex justify-between items-center border-b border-gray-200">
                    <span class="text-sm font-semibold text-gray-700" id="mobileQuestionCounter">Question 1 of <?php echo count($all_questions); ?></span>
                    <span class="text-sm font-semibold text-gray-700 flex items-center" id="mobileFlagStatus">
                        <i class="far fa-flag mr-1"></i> Flag for review
                    </span>
                </div>
                
                <form id="examForm" action="submit_exam.php" method="POST">
                    <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">

                    <?php 
                    $question_num = 1;
                    foreach ($all_questions as $question): 
                    ?>
                        <div class="question-card" id="question-<?php echo $question_num; ?>" style="display: <?php echo $question_num === 1 ? 'block' : 'none'; ?>;">
                            <div class="question-header">
                                <span class="question-number">Question <?php echo $question_num; ?></span>
                                <span class="question-points"><?php echo $question['points']; ?> point<?php echo $question['points'] > 1 ? 's' : ''; ?></span>
                            </div>
                            
                            <div class="question-text">
                                <p><?php echo htmlspecialchars($question['question_text']); ?></p>
                            </div>
                            
                            <div class="question-options">
                                <label class="option-item">
                                    <span class="option-marker">A</span>
                                    <span class="option-text"><?php echo htmlspecialchars($question['option_a']); ?></span>
                                    <input type="radio" id="q<?php echo $question['question_id']; ?>_a" name="q<?php echo $question['question_id']; ?>" value="A" hidden>
                                </label>
                                
                                <label class="option-item">
                                    <span class="option-marker">B</span>
                                    <span class="option-text"><?php echo htmlspecialchars($question['option_b']); ?></span>
                                    <input type="radio" id="q<?php echo $question['question_id']; ?>_b" name="q<?php echo $question['question_id']; ?>" value="B" hidden>
                                </label>
                                
                                <label class="option-item">
                                    <span class="option-marker">C</span>
                                    <span class="option-text"><?php echo htmlspecialchars($question['option_c']); ?></span>
                                    <input type="radio" id="q<?php echo $question['question_id']; ?>_c" name="q<?php echo $question['question_id']; ?>" value="C" hidden>
                                </label>
                                
                                <label class="option-item">
                                    <span class="option-marker">D</span>
                                    <span class="option-text"><?php echo htmlspecialchars($question['option_d']); ?></span>
                                    <input type="radio" id="q<?php echo $question['question_id']; ?>_d" name="q<?php echo $question['question_id']; ?>" value="D" hidden>
                                </label>
                            </div>
                            
                            <div class="question-actions">
                                <button type="button" class="question-flag" data-question-id="<?php echo $question['question_id']; ?>">
                                    <i class="far fa-flag"></i>
                                    <span>Flag for review</span>
                                </button>
                            </div>
                        </div>
                    <?php 
                    $question_num++;
                    endforeach; 
                    ?>
                </form>
                
                <!-- Navigation buttons -->
                <div class="question-navigation">
                    <button type="button" class="btn btn-outline" id="prevQuestion" disabled>
                        <i class="fas fa-arrow-left mr-2"></i>Previous
                    </button>
                    <button type="button" class="btn btn-primary" id="nextQuestion">
                        Next <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Mobile navigation toggle -->
    <button class="nav-toggle" id="navToggle">
        <i class="fas fa-list-ul"></i>
    </button>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile viewport height fix
            function setVHVariable() {
                let vh = window.innerHeight * 0.01;
                document.documentElement.style.setProperty('--vh', `${vh}px`);
            }
            
            setVHVariable();
            window.addEventListener('resize', setVHVariable);
            
            // Message Box functionality (reusable)
            function showMessageBox(message, type = 'success') {
                const messageBox = document.getElementById('messageBox');
                messageBox.textContent = message;
                messageBox.className = 'message-box show'; // Reset classes and add 'show'
                if (type === 'error') {
                    messageBox.style.backgroundColor = '#ef4444'; // Tailwind red-500 for error
                } else {
                    messageBox.style.backgroundColor = '#10b981'; // Tailwind emerald-500 for success (default)
                }
                setTimeout(() => {
                    messageBox.classList.remove('show');
                }, 3000); // Hide after 3 seconds
            }

            // Timer setup
            let timeLeft = <?php echo $initial_time_limit_seconds; ?>;
            let countdownInterval; 
            const timerElement = document.getElementById('timer');
            const examForm = document.getElementById('examForm');
            
            const startCountdown = () => {
                countdownInterval = setInterval(() => {
                    timeLeft--;
                    
                    if (timeLeft < 0) { 
                        timeLeft = 0;
                        clearInterval(countdownInterval); 
                        showMessageBox('Time is up! Submitting your exam automatically.', 'error');
                        examForm.submit(); // Automatically submit the form
                        return;
                    }
                    
                    const hours = Math.floor(timeLeft / 3600);
                    const minutes = Math.floor((timeLeft % 3600) / 60);
                    const seconds = timeLeft % 60;
                    
                    timerElement.textContent = `${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    
                    if (timeLeft <= 300 && timeLeft > 0) { // 5 minutes left, but not expired
                        timerElement.classList.add('text-red-500'); // Tailwind red-500
                        timerElement.classList.remove('text-blue-600'); 
                    } else {
                        timerElement.classList.remove('text-red-500');
                        timerElement.classList.add('text-blue-600'); // Tailwind blue-600
                    }
                }, 1000);
            };

            startCountdown(); // Start the countdown when the page loads
            
            // Mobile navigation toggle
            const navToggle = document.getElementById('navToggle');
            const mobileNavClose = document.getElementById('mobileNavClose');
            const questionNav = document.getElementById('questionNav');
            
            navToggle.addEventListener('click', function() {
                questionNav.classList.add('active');
            });
            
            mobileNavClose.addEventListener('click', function() {
                questionNav.classList.remove('active');
            });
            
            // Close mobile nav when clicking outside
            document.addEventListener('click', function(e) {
                const isNavButton = e.target === navToggle || navToggle.contains(e.target);
                const isInsideNav = questionNav.contains(e.target);
                
                if (!isNavButton && !isInsideNav && questionNav.classList.contains('active')) {
                    questionNav.classList.remove('active');
                }
            });
            
            // Question navigation
            const allQuestions = Array.from(document.querySelectorAll('.question-card'));
            const totalQuestions = allQuestions.length;
            let currentQuestionIndex = 0; // 0-indexed
            
            const prevBtn = document.getElementById('prevQuestion');
            const nextBtn = document.getElementById('nextQuestion');
            const questionMarkers = document.querySelectorAll('.question-marker');
            const mobileQuestionCounter = document.getElementById('mobileQuestionCounter');
            const mobileFlagStatus = document.getElementById('mobileFlagStatus'); 
            const submitContainer = document.getElementById('submitContainer');

            function showQuestion(index) {
                allQuestions.forEach((q, i) => {
                    q.style.display = (i === index) ? 'block' : 'none';
                });
                
                // Update question markers in the navigation
                questionMarkers.forEach((marker, i) => {
                    marker.classList.toggle('current', i === index);
                });
                
                // Update the navigation buttons
                prevBtn.disabled = (index === 0);
                nextBtn.disabled = (index === totalQuestions - 1);
                
                // Update mobile question info
                mobileQuestionCounter.textContent = `Question ${index + 1} of ${totalQuestions}`;
                
                // Update mobile flag status
                const currentQuestionFlagBtn = allQuestions[index].querySelector('.question-flag');
                if (currentQuestionFlagBtn && currentQuestionFlagBtn.classList.contains('flagged')) {
                    mobileFlagStatus.innerHTML = '<i class="fas fa-flag mr-1"></i> Flagged';
                    mobileFlagStatus.classList.add('text-amber-500');
                    mobileFlagStatus.classList.remove('text-gray-700');
                } else {
                    mobileFlagStatus.innerHTML = '<i class="far fa-flag mr-1"></i> Flag for review';
                    mobileFlagStatus.classList.remove('text-amber-500');
                    mobileFlagStatus.classList.add('text-gray-700');
                }

                currentQuestionIndex = index;

                // Show submit button only on the last question
                if (currentQuestionIndex === totalQuestions - 1) {
                    submitContainer.style.display = 'block';
                } else {
                    submitContainer.style.display = 'none';
                }
            }
            
            // Navigate between questions with buttons
            nextBtn.addEventListener('click', function() {
                if (currentQuestionIndex < totalQuestions - 1) {
                    showQuestion(currentQuestionIndex + 1);
                }
            });
            
            prevBtn.addEventListener('click', function() {
                if (currentQuestionIndex > 0) {
                    showQuestion(currentQuestionIndex - 1);
                }
            });
            
            // Click on question markers to navigate
            questionMarkers.forEach((marker, index) => {
                marker.addEventListener('click', function() {
                    showQuestion(index);
                    // Close mobile nav after selecting a question
                    if (window.innerWidth <= 768) {
                        questionNav.classList.remove('active');
                    }
                });
            });
            
            // Multiple choice selection
            document.querySelectorAll('.question-options').forEach(optionsContainer => {
                optionsContainer.addEventListener('click', function(e) {
                    const optionItem = e.target.closest('.option-item');
                    if (!optionItem) return;

                    const questionCard = this.closest('.question-card');
                    const questionIdInput = optionItem.querySelector('input[type="radio"]');
                    
                    // Deselect all options in the same question
                    optionsContainer.querySelectorAll('.option-item').forEach(opt => {
                        opt.classList.remove('selected');
                        const radio = opt.querySelector('input[type="radio"]');
                        if (radio) radio.checked = false; // Ensure radio is unchecked
                    });
                    
                    // Select clicked option
                    optionItem.classList.add('selected');
                    
                    // Check the hidden radio button
                    if (questionIdInput) {
                        questionIdInput.checked = true;
                    }
                    
                    // Mark the question as answered in the navigation
                    const questionNum = parseInt(questionCard.id.replace('question-', ''));
                    document.querySelector(`.question-marker[data-question="${questionNum}"]`).classList.add('answered');
                    
                    // Update summary statistics
                    updateSummaryStats();
                    showMessageBox('Answer saved!', 'success');
                });
            });

            // Flag questions for review
            document.querySelectorAll('.question-flag').forEach(flagBtn => {
                flagBtn.addEventListener('click', function() {
                    this.classList.toggle('flagged');
                    
                    // Update the flag icon
                    const icon = this.querySelector('i');
                    const spanText = this.querySelector('span');
                    const questionCard = this.closest('.question-card');
                    const questionNum = parseInt(questionCard.id.replace('question-', ''));
                    const marker = document.querySelector(`.question-marker[data-question="${questionNum}"]`);

                    if (this.classList.contains('flagged')) {
                        icon.className = 'fas fa-flag';
                        spanText.textContent = 'Flagged for review';
                        marker.classList.add('flagged');
                        mobileFlagStatus.innerHTML = '<i class="fas fa-flag mr-1"></i> Flagged';
                        mobileFlagStatus.classList.add('text-amber-500');
                        mobileFlagStatus.classList.remove('text-gray-700');
                    } else {
                        icon.className = 'far fa-flag';
                        spanText.textContent = 'Flag for review';
                        marker.classList.remove('flagged');
                        mobileFlagStatus.innerHTML = '<i class="far fa-flag mr-1"></i> Flag for review';
                        mobileFlagStatus.classList.remove('text-amber-500');
                        mobileFlagStatus.classList.add('text-gray-700');
                    }
                    
                    // Update summary statistics
                    updateSummaryStats();
                });
            });
            
            // Update summary statistics
            function updateSummaryStats() {
                const answeredQuestions = document.querySelectorAll('.question-marker.answered').length;
                const flaggedQuestions = document.querySelectorAll('.question-marker.flagged').length;
                const notAnsweredQuestions = totalQuestions - answeredQuestions;
                
                // Update the summary in the navigation
                document.querySelector('.nav-section .summary-stats .stat-value:nth-child(2)').textContent = `${answeredQuestions}/${totalQuestions}`; // Answered
                document.querySelector('.nav-section .summary-stats .stat-value:nth-child(4)').textContent = flaggedQuestions; // Flagged
                document.querySelector('.nav-section .summary-stats .stat-value:nth-child(6)').textContent = notAnsweredQuestions; // Not Answered
            }
            
            // --- MODIFIED SUBMIT FUNCTIONALITY ---
            const submitExamAction = () => {
                clearInterval(countdownInterval); // Stop the timer
                timeLeft = 0; // Set time to zero
                timerElement.textContent = "0:00:00"; // Update timer display
                timerElement.classList.add('text-red-500'); // Visually indicate exam ended
                
                examForm.submit(); // Directly submit the form
                showMessageBox('Exam submitted successfully! Redirecting to results page...', 'success');
            };

            // Event listener for sidebar submit button
            document.getElementById('submitExamBtn').addEventListener('click', function() {
                submitExamAction();
            });

            // Event listener for the main submit button (on last question)
            if (submitContainer.querySelector('.btn.btn-primary')) {
                 submitContainer.querySelector('.btn.btn-primary').addEventListener('click', function(e) { 
                    e.preventDefault(); // Prevent default form submission if any
                    submitExamAction();
                });
            }
           
            // Remove modal related event listeners as the modal is gone
            // No longer need cancelSubmit, confirmSubmit listeners
            
            // Add keyboard navigation
            document.addEventListener('keydown', function(e) {
                // Don't trigger if user is typing in a text field
                if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT') {
                    return;
                }
                
                if (e.key === 'ArrowRight' || e.key === 'j') {
                    // Next question
                    if (currentQuestionIndex < totalQuestions - 1) {
                        showQuestion(currentQuestionIndex + 1);
                    }
                } else if (e.key === 'ArrowLeft' || e.key === 'k') {
                    // Previous question
                    if (currentQuestionIndex > 0) {
                        showQuestion(currentQuestionIndex - 1);
                    }
                } else if (e.key === 'f') {
                    // Flag current question
                    const flagBtn = allQuestions[currentQuestionIndex].querySelector('.question-flag');
                    if (flagBtn) {
                        flagBtn.click();
                    }
                } else if (e.key === 's' && (e.ctrlKey || e.metaKey)) { // Ctrl+S or Cmd+S for submit
                    e.preventDefault(); // Prevent browser save dialog
                    submitExamAction(); // Directly submit on shortcut
                }
            });
            
            // Initialize the first question
            showQuestion(0);
            
            // Initialize summary stats
            updateSummaryStats();
        });
    </script>
</body>
</html>
