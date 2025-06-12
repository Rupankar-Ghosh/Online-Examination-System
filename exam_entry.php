<?php
session_start();
// Database configuration
include 'config.php';

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.html");
    exit();
}

$error = ''; // Initialize error variable

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exam_id = $_POST['exam_id'];
    $password = $_POST['password'];
    $user_id = $_SESSION['user_id']; // Assuming user is logged in

    // Prepare and execute the statement to check exam access
    // This query verifies if the exam exists, matches the password, is active,
    // and falls within its scheduled start and end times.
    $stmt = $conn->prepare("SELECT * FROM exams WHERE exam_id = ? AND exam_password = ? AND is_active = TRUE AND NOW() BETWEEN start_time AND end_time");
    // 'is' specifies that exam_id is an integer and password is a string.
    $stmt->bind_param("is", $exam_id, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // If exam credentials are valid, attempt to grant access.
        // This query inserts a record into the exam_access table.
        // The UNIQUE KEY (exam_id, participant_id) in the database
        // handles cases where a user might try to insert access multiple times.
        $access_stmt = $conn->prepare("INSERT INTO exam_access (exam_id, participant_id) VALUES (?, ?)");
        // 'is' specifies that exam_id is an integer and user_id is a string.
        $access_stmt->bind_param("is", $exam_id, $user_id);
        
        if ($access_stmt->execute()) {
            // Access successfully granted (first time for this user for this exam).
            $_SESSION['current_exam'] = $exam_id;
            header("Location: take_exam.php");
            exit();
        } else {
            // This 'else' block will likely be hit if the UNIQUE KEY constraint is violated,
            // meaning the user already has an entry in exam_access for this exam.
            // In this case, simply proceed to the exam.
            $_SESSION['current_exam'] = $exam_id;
            header("Location: take_exam.php");
            exit();
        }
    } else {
        // If the initial exam credentials check fails, set an error message.
        $error = "Invalid exam ID, password, or exam is not currently available.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamFlow - Enter Exam</title>
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
<body class="flex flex-col min-h-screen items-center justify-between text-gray-800">
    <!-- Header Section -->
    <header class="w-full bg-white shadow-lg py-5">
        <div class="container mx-auto px-4 flex justify-center items-center">
            <a href="dashboard.php" class="text-3xl font-extrabold text-blue-600 tracking-tight hover:text-blue-700 transition duration-300">ExamFlow</a>
        </div>
    </header>

    <!-- Main Content - Exam Entry Form -->
    <main class="flex-grow flex items-center justify-center p-4 w-full">
        <div class="bg-white rounded-2xl shadow-xl p-8 sm:p-10 w-full max-w-md text-center border border-gray-200">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Enter Exam Details</h1>
            
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6 shadow-md" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-6">
                <div class="text-left">
                    <label for="exam_id" class="block text-gray-700 text-sm font-semibold mb-2">Exam ID</label>
                    <input type="number" id="exam_id" name="exam_id" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out text-lg">
                </div>
                
                <div class="text-left">
                    <label for="password" class="block text-gray-700 text-sm font-semibold mb-2">Exam Password</label>
                    <input type="password" id="password" name="password" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out text-lg">
                </div>
                
                <button type="submit" 
                        class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-bold text-lg hover:bg-blue-700 transition duration-300 shadow-lg flex items-center justify-center">
                    <i class="fas fa-arrow-right mr-3"></i>Start Exam
                </button>
            </form>

            <div class="mt-8 text-center text-gray-600 text-sm">
                <p>Don't have an exam ID? Check your dashboard to <a href="dashboard.php" class="text-blue-600 hover:underline font-medium">Create One</a> or find existing exams.</p>
            </div>
        </div>
    </main>

    <!-- Footer Section -->
    <footer class="w-full bg-gray-800 text-gray-300 py-4 text-center text-sm shadow-inner">
        <div class="container mx-auto px-4">
            &copy; <?php echo date("Y"); ?> ExamFlow. All rights reserved.
        </div>
    </footer>
</body>
</html>
