<?php

session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.html");
    exit();
}

// Database configuration
include 'config.php'; // Assumes config.php provides $conn

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to generate random exam password
function generateExamPassword($length = 8) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

$errors = [];
$success = false;
$exam_id = '';
$exam_password = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_exam'])) {
    // Validate input fields
    $required = ['title', 'description', 'time_limit', 'start_time', 'end_time'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $errors[$field] = "This field is required.";
        }
    }

    // Validate dates and times
    $start_time = strtotime($_POST['start_time']);
    $end_time = strtotime($_POST['end_time']);
    $current_time = time();
    
    if (!$start_time || !$end_time) {
        $errors['dates'] = "Invalid date/time format.";
    } elseif ($start_time >= $end_time) {
        $errors['dates'] = "End time must be after start time.";
    } elseif ($start_time < $current_time - 60) { // Allow 60 seconds grace period for past time
        $errors['dates'] = "Start time cannot be in the past.";
    }

    // Validate questions
    if (empty($_POST['questions']) || !is_array($_POST['questions'])) {
        $errors['questions'] = "Please add at least one question.";
    } else {
        foreach ($_POST['questions'] as $index => $question) {
            if (empty($question['text'])) {
                $errors["question_{$index}_text"] = "Question text is required.";
            }
            
            // Validate options
            foreach (['option_a', 'option_b', 'option_c', 'option_d'] as $option) {
                if (empty($question[$option])) {
                    $errors["question_{$index}_{$option}"] = "Option ".strtoupper(substr($option, -1))." is required.";
                }
            }
            
            // Validate correct answer
            if (empty($question['correct_answer']) || !in_array($question['correct_answer'], ['A', 'B', 'C', 'D'])) {
                $errors["question_{$index}_correct"] = "Please select a correct answer.";
            }
            
            // Validate points
            if (empty($question['points']) || !is_numeric($question['points']) || $question['points'] < 1) {
                $errors["question_{$index}_points"] = "Points must be at least 1.";
            }
        }
    }

    // If no validation errors, proceed to insert into database
    if (empty($errors)) {
        $exam_password = generateExamPassword();
        $start_time_formatted = date('Y-m-d H:i:s', $start_time);
        $end_time_formatted = date('Y-m-d H:i:s', $end_time);
        
        // Insert exam details into the 'exams' table
        $stmt = $conn->prepare("INSERT INTO exams (user_id, title, description, time_limit, exam_password, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisss", $_SESSION['user_id'], $_POST['title'], $_POST['description'], $_POST['time_limit'], $exam_password, $start_time_formatted, $end_time_formatted);
        
        if ($stmt->execute()) {
            $exam_id = $stmt->insert_id; // Get the ID of the newly created exam
            $stmt->close();
            
            // Insert questions into the 'questions' table
            $question_errors = [];
            $question_stmt = $conn->prepare("INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_answer, points) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($_POST['questions'] as $question) {
                // Ensure points is an integer and at least 1
                $points = max(1, intval($question['points']));
                
                $question_stmt->bind_param("issssssi", $exam_id, $question['text'], $question['option_a'], $question['option_b'], $question['option_c'], $question['option_d'], $question['correct_answer'], $points);
                
                if (!$question_stmt->execute()) {
                    $question_errors[] = $conn->error; // Collect any question-specific errors
                }
            }
            
            $question_stmt->close();
            
            if (empty($question_errors)) {
                $success = true; // All questions inserted successfully
            } else {
                $errors['database'] = "Exam created, but some questions failed to save.";
                // Optionally, delete the exam if questions failed to ensure data consistency
                // $conn->query("DELETE FROM exams WHERE exam_id = $exam_id"); 
            }
        } else {
            $errors['database'] = "Failed to create exam: " . $conn->error;
        }
    }
}

// Set default datetime values for the form
$default_start = time() + 3600; // 1 hour from now
$default_end = $default_start + (24 * 3600); // 1 day later
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Exam</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #4A90E2; /* A modern blue */
            --secondary-color: #3B7DD1; /* Darker shade of primary */
            --accent-color: #5BC0BE; /* Teal/Turquoise for accents */
            --danger-color: #D9534F; /* Red */
            --success-color: #5CB85C; /* Green */
            --warning-color: #F0AD4E; /* Orange/Yellow */
            --light-bg-color: #F8F9FA; /* Light gray background */
            --dark-text-color: #343A40; /* Dark text */
            --border-color: #E0E0E0; /* Light gray border */
            --shadow-light: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-medium: 0 8px 20px rgba(0, 0, 0, 0.12);
            --transition-speed: 0.3s;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--dark-text-color);
            background: linear-gradient(135deg, #e0f2f7, #c1e7f0, #a2dbe9); /* Soft blue gradient */
            background-size: 400% 400%;
            animation: gradientBG 20s ease infinite;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .header {
            width: 100%;
            max-width: 900px;
            background: rgba(255, 255, 255, 0.9);
            padding: 15px 30px;
            border-radius: 10px;
            box-shadow: var(--shadow-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: fadeIn 0.6s ease-out;
        }

        .header .user-info {
            font-weight: 600;
            color: var(--dark-text-color);
            display: flex;
            align-items: center;
        }

        .header .user-info i {
            margin-right: 8px;
            color: var(--primary-color);
        }

        .header .logout-btn {
            background: none;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all var(--transition-speed) ease;
        }

        .header .logout-btn:hover {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.2);
        }
        
        .container {
            width: 90%;
            max-width: 900px;
            margin: 20px auto;
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 15px;
            box-shadow: var(--shadow-medium);
            transition: all var(--transition-speed) ease;
            animation: slideIn 0.8s ease-out;
            backdrop-filter: blur(8px); /* Stronger frosted glass */
            border: 1px solid rgba(255, 255, 255, 0.4);
            overflow: hidden; /* For smooth animations of questions */
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        h1, h2 {
            color: var(--primary-color);
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 10px;
            font-weight: 700;
            text-shadow: 1px 1px 2px rgba(0, 123, 255, 0.1);
        }
        
        h1:after, h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 70px;
            height: 4px;
            background: var(--accent-color);
            border-radius: 2px;
        }

        h2:after {
            width: 50px;
            background: var(--secondary-color);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--dark-text-color);
            display: flex;
            align-items: center;
        }

        label i {
            margin-right: 10px;
            color: var(--primary-color);
            font-size: 1.1em;
        }
        
        input[type="text"],
        input[type="number"],
        input[type="datetime-local"],
        textarea,
        select {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            transition: all var(--transition-speed) ease;
            background-color: var(--light-bg-color);
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
        }
        
        input:focus,
        textarea:focus,
        select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 4px rgba(74, 144, 226, 0.2);
            background-color: #ffffff;
        }

        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] {
            -moz-appearance: textfield;
        }
        
        .question {
            border: 2px solid var(--border-color);
            padding: 25px;
            margin-bottom: 30px;
            border-radius: 10px;
            background-color: #ffffff;
            position: relative;
            transition: all var(--transition-speed) ease;
            box-shadow: var(--shadow-light);
            border-left: 6px solid var(--accent-color);
            overflow: hidden;
        }
        
        .question:hover {
            border-color: var(--secondary-color);
            transform: translateY(-3px);
            box-shadow: var(--shadow-medium);
        }
        
        .options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .option {
            display: flex;
            align-items: center;
            background: var(--light-bg-color);
            padding: 12px;
            border-radius: 8px;
            transition: all var(--transition-speed) ease;
            border: 1px solid rgba(0,0,0,0.05);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .option:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(74, 144, 226, 0.05), rgba(91, 192, 190, 0.05));
            z-index: 0;
            opacity: 0;
            transition: opacity var(--transition-speed) ease;
            border-radius: 8px;
        }
        
        .option:hover {
            background: #eaf6fa;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transform: scale(1.01);
        }

        .option:hover:before {
            opacity: 1;
        }
        
        .option input[type="radio"] {
            margin-right: 12px;
            transform: scale(1.2);
            accent-color: var(--primary-color);
            cursor: pointer;
            flex-shrink: 0;
            z-index: 1; /* Ensure radio button is clickable */
        }

        .option input[type="text"] {
            border: none;
            background: transparent;
            padding: 0;
            box-shadow: none;
            z-index: 1; /* Ensure text input is editable */
        }

        .option input[type="text"]:focus {
            box-shadow: none;
        }
        
        .btn {
            padding: 14px 25px;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            transition: all var(--transition-speed) ease;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.3);
            min-width: 150px; /* Ensure consistent button width */
        }

        .btn:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 300%;
            height: 300%;
            background: rgba(255, 255, 255, 0.15);
            transition: all 0.75s ease-out;
            border-radius: 50%;
            transform: scale(0) translate(-50%, -50%);
            opacity: 0;
        }
        
        .btn:hover {
            background: linear-gradient(45deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
        }

        .btn:hover:before {
            transform: scale(1) translate(-50%, -50%);
            opacity: 1;
        }

        .btn:active {
            transform: translateY(0);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }
        
        .btn i {
            margin-right: 10px;
            font-size: 1.1em;
        }
        
        .btn-danger {
            background: linear-gradient(45deg, var(--danger-color), #c9302c);
            box-shadow: 0 6px 15px rgba(217, 83, 79, 0.3);
        }
        
        .btn-danger:hover {
            background: linear-gradient(45deg, #c9302c, var(--danger-color));
            box-shadow: 0 10px 25px rgba(217, 83, 79, 0.4);
        }

        .btn-success {
            background: linear-gradient(45deg, var(--success-color), #4CAF50);
            box-shadow: 0 6px 15px rgba(92, 184, 92, 0.3);
        }

        .btn-success:hover {
            background: linear-gradient(45deg, #4CAF50, var(--success-color));
            box-shadow: 0 10px 25px rgba(92, 184, 92, 0.4);
        }
        
        .error {
            color: var(--danger-color);
            font-size: 0.95em;
            margin-top: 8px;
            display: flex;
            align-items: center;
            background-color: rgba(217, 83, 79, 0.1);
            padding: 10px 15px;
            border-radius: 6px;
            border-left: 4px solid var(--danger-color);
            font-weight: 500;
        }
        
        .error i {
            margin-right: 8px;
        }
        
        .error-field {
            border-color: var(--danger-color) !important;
            box-shadow: 0 0 0 3px rgba(217, 83, 79, 0.2) !important;
        }
        
        .success {
            color: var(--success-color);
            font-weight: 600;
            margin: 20px 0;
            padding: 25px;
            background: rgba(92, 184, 92, 0.1);
            border-left: 5px solid var(--success-color);
            border-radius: 8px;
            animation: fadeInScale 0.6s ease-out;
            display: flex;
            flex-direction: column;
            gap: 15px;
            box-shadow: 0 4px 10px rgba(92, 184, 92, 0.15);
            font-size: 1.1em;
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .success .info-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 15px;
            font-size: 1em;
            word-break: break-all; /* Ensure long IDs/passwords wrap */
        }
        
        .question-number {
            font-weight: 700;
            margin-bottom: 18px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            font-size: 1.1em;
        }
        
        .question-number:before {
            content: counter(question-counter);
            counter-increment: question-counter;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            margin-right: 12px;
            font-size: 16px;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0, 123, 255, 0.2);
        }
        
        .datetime-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        .remove-question {
            margin-top: 15px;
            width: auto;
            align-self: flex-end;
            padding: 10px 18px;
            font-size: 0.95em;
        }
        
        #questions-container {
            counter-reset: question-counter;
            animation: fadeIn 0.5s ease;
        }
        
        .floating-btn {
            position: fixed;
            bottom: 40px;
            right: 40px;
            width: 65px;
            height: 65px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--accent-color), var(--primary-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 8px 20px rgba(91, 192, 190, 0.4);
            cursor: pointer;
            transition: all var(--transition-speed) ease;
            z-index: 100;
            border: 3px solid rgba(255,255,255,0.8);
            animation: bounceIn 0.8s ease-out;
        }
        
        .floating-btn:hover {
            transform: scale(1.1) translateY(-8px);
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            box-shadow: 0 12px 30px rgba(91, 192, 190, 0.5);
        }

        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); opacity: 1; }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); }
        }
        
        .copy-btn {
            background: var(--accent-color);
            padding: 8px 15px;
            font-size: 14px;
            margin-left: 15px;
            border-radius: 6px;
            cursor: pointer;
            transition: all var(--transition-speed) ease;
            position: relative;
            display: inline-flex;
            align-items: center;
            color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden; /* For pseudo-element hover */
        }
        
        .copy-btn:hover {
            background: #47A9A7; /* Darker accent on hover */
            transform: translateY(-1px);
        }

        .copy-btn i {
            margin-right: 8px;
        }
        
        .tooltip {
            position: relative;
            display: inline-block;
        }
        
        .tooltip .tooltiptext {
            visibility: hidden;
            width: 120px;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 8px;
            position: absolute;
            z-index: 1;
            bottom: 125%; /* Adjusted for better visibility above button */
            left: 50%;
            margin-left: -60px;
            opacity: 0;
            transition: opacity 0.3s, transform 0.3s;
            font-size: 0.85em;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            pointer-events: none; /* Allows clicks on elements behind it */
        }
        
        .tooltip .tooltiptext::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #555 transparent transparent transparent;
        }
        
        .tooltip:hover .tooltiptext, .tooltip.active .tooltiptext {
            visibility: visible;
            opacity: 1;
            transform: translateY(-5px); /* Lift slightly on hover/active */
        }

        /* Footer Styles */
        .footer {
            width: 100%;
            max-width: 900px;
            text-align: center;
            padding: 20px;
            margin-top: 30px;
            color: var(--dark-text-color);
            font-size: 0.9em;
            opacity: 0.8;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            .header {
                flex-direction: column;
                gap: 10px;
                padding: 15px 20px;
            }
            .container {
                padding: 25px;
                margin: 15px auto;
            }
            
            .options {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .datetime-group {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .floating-btn {
                width: 55px;
                height: 55px;
                font-size: 24px;
                bottom: 20px;
                right: 20px;
            }

            .btn {
                width: 100%;
                min-width: unset; /* Remove fixed width for responsiveness */
            }

            .copy-btn {
                margin-left: 0;
                margin-top: 10px;
                width: fit-content; /* Adjust width to content */
            }
            .success .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }
        /* Further responsive adjustments for very small screens */
        @media (max-width: 480px) {
            h1, h2 {
                font-size: 1.8em;
            }

            label {
                font-size: 0.9em;
            }

            input[type="text"],
            input[type="number"],
            input[type="datetime-local"],
            textarea,
            select {
                padding: 10px 12px;
                font-size: 14px;
            }
            
            .question {
                padding: 15px;
            }

            .btn {
                padding: 10px 15px;
                font-size: 0.9em;
            }
            .floating-btn {
                width: 50px;
                height: 50px;
                font-size: 22px;
                bottom: 15px;
                right: 15px;
            }
            .header {
                padding: 10px 15px;
            }
            .header .user-info {
                font-size: 0.9em;
            }
            .header .logout-btn {
                padding: 6px 12px;
                font-size: 0.8em;
            }
        }

        /* Keyframe for shaking element (e.g., form on validation error) */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-8px); }
            20%, 40%, 60%, 80% { transform: translateX(8px); }
        }
        /* Keyframe for pulse effect (e.g., floating button) */
        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(91, 192, 190, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 20px rgba(91, 192, 190, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(91, 192, 190, 0); }
        }
    </style>
</head>
<body>

   
    <div class="container">
        <h1><i class="fas fa-file-alt"></i> Create New Exam</h1>
        
        <?php if (isset($success) && $success): ?>
            <!-- Success message with Exam ID and Password -->
            <div class="success">
                <i class="fas fa-check-circle"></i> Exam created successfully!<br>
                <div class="info-row">
                    <strong>Exam ID:</strong> <span><?php echo htmlspecialchars($exam_id); ?></span>
                </div>
                <div class="info-row">
                    <strong>Exam Password:</strong> <span><?php echo htmlspecialchars($exam_password); ?></span> 
                </div>
            </div>
            <a href="dashboard.php" class="btn btn-success"><i class="fas fa-tachometer-alt"></i> Go to Dashboard</a>
        <?php else: ?>
            <!-- Display general database errors if any -->
            <?php if (!empty($errors['database'])): ?>
                <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['database']); ?></div>
            <?php endif; ?>
            
            <!-- Exam creation form -->
            <form id="exam-form" method="post">
                <div class="form-group">
                    <label for="title"><i class="fas fa-heading"></i> Exam Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required
                           class="<?php echo !empty($errors['title']) ? 'error-field' : ''; ?>">
                    <?php if (!empty($errors['title'])): ?>
                        <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['title']); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="description"><i class="fas fa-align-left"></i> Description</label>
                    <textarea id="description" name="description" rows="3" required
                              class="<?php echo !empty($errors['description']) ? 'error-field' : ''; ?>"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    <?php if (!empty($errors['description'])): ?>
                        <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['description']); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="time_limit"><i class="fas fa-clock"></i> Time Limit (minutes)</label>
                    <input type="number" id="time_limit" name="time_limit" min="1" value="<?php echo htmlspecialchars($_POST['time_limit'] ?? '30'); ?>" required
                           class="<?php echo !empty($errors['time_limit']) ? 'error-field' : ''; ?>">
                    <?php if (!empty($errors['time_limit'])): ?>
                        <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['time_limit']); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group datetime-group">
                    <div>
                        <label for="start_time"><i class="fas fa-calendar-plus"></i> Start Time</label>
                        <input type="datetime-local" id="start_time" name="start_time" value="<?php echo date('Y-m-d\TH:i', $default_start); ?>" required
                               class="<?php echo !empty($errors['dates']) ? 'error-field' : ''; ?>">
                    </div>
                    <div>
                        <label for="end_time"><i class="fas fa-calendar-times"></i> End Time</label>
                        <input type="datetime-local" id="end_time" name="end_time" value="<?php echo date('Y-m-d\TH:i', $default_end); ?>" required
                               class="<?php echo !empty($errors['dates']) ? 'error-field' : ''; ?>">
                    </div>
                    <?php if (!empty($errors['dates'])): ?>
                        <div class="error" style="grid-column: 1 / -1;"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['dates']); ?></div>
                    <?php endif; ?>
                </div>
                
                <h2><i class="fas fa-question-circle"></i> Questions</h2>
                <?php if (!empty($errors['questions'])): ?>
                    <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['questions']); ?></div>
                <?php endif; ?>
                
                <div id="questions-container">
                    <!-- Default first question - dynamically added/removed by JavaScript -->
                    <div class="question" data-index="0">
                        <div class="question-number">Question 1</div>
                        <div class="form-group">
                            <label><i class="fas fa-question"></i> Question Text</label>
                            <textarea name="questions[0][text]" rows="2" required
                                      class="<?php echo !empty($errors['question_0_text']) ? 'error-field' : ''; ?>"><?php echo htmlspecialchars($_POST['questions'][0]['text'] ?? ''); ?></textarea>
                            <?php if (!empty($errors['question_0_text'])): ?>
                                <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['question_0_text']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="options <?php echo !empty($errors['question_0_correct']) ? 'error-field' : ''; ?>">
                            <div class="option">
                                <input type="radio" name="questions[0][correct_answer]" value="A" required <?php echo (isset($_POST['questions'][0]['correct_answer']) && $_POST['questions'][0]['correct_answer'] == 'A') ? 'checked' : ''; ?>>
                                <input type="text" name="questions[0][option_a]" placeholder="Option A" required
                                       value="<?php echo htmlspecialchars($_POST['questions'][0]['option_a'] ?? ''); ?>"
                                       class="<?php echo !empty($errors['question_0_option_a']) ? 'error-field' : ''; ?>">
                            </div>
                            
                            <div class="option">
                                <input type="radio" name="questions[0][correct_answer]" value="B" <?php echo (isset($_POST['questions'][0]['correct_answer']) && $_POST['questions'][0]['correct_answer'] == 'B') ? 'checked' : ''; ?>>
                                <input type="text" name="questions[0][option_b]" placeholder="Option B" required
                                       value="<?php echo htmlspecialchars($_POST['questions'][0]['option_b'] ?? ''); ?>"
                                       class="<?php echo !empty($errors['question_0_option_b']) ? 'error-field' : ''; ?>">
                            </div>
                            
                            <div class="option">
                                <input type="radio" name="questions[0][correct_answer]" value="C" <?php echo (isset($_POST['questions'][0]['correct_answer']) && $_POST['questions'][0]['correct_answer'] == 'C') ? 'checked' : ''; ?>>
                                <input type="text" name="questions[0][option_c]" placeholder="Option C" required
                                       value="<?php echo htmlspecialchars($_POST['questions'][0]['option_c'] ?? ''); ?>"
                                       class="<?php echo !empty($errors['question_0_option_c']) ? 'error-field' : ''; ?>">
                            </div>
                            
                            <div class="option">
                                <input type="radio" name="questions[0][correct_answer]" value="D" <?php echo (isset($_POST['questions'][0]['correct_answer']) && $_POST['questions'][0]['correct_answer'] == 'D') ? 'checked' : ''; ?>>
                                <input type="text" name="questions[0][option_d]" placeholder="Option D" required
                                       value="<?php echo htmlspecialchars($_POST['questions'][0]['option_d'] ?? ''); ?>"
                                       class="<?php echo !empty($errors['question_0_option_d']) ? 'error-field' : ''; ?>">
                            </div>
                        </div>
                        <?php if (!empty($errors['question_0_correct'])): ?>
                            <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['question_0_correct']); ?></div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label><i class="fas fa-star"></i> Points</label>
                            <input type="number" name="questions[0][points]" min="1" value="<?php echo htmlspecialchars($_POST['questions'][0]['points'] ?? '1'); ?>" required
                                   class="<?php echo !empty($errors['question_0_points']) ? 'error-field' : ''; ?>">
                            <?php if (!empty($errors['question_0_points'])): ?>
                                <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['question_0_points']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="button" class="btn btn-danger remove-question" style="display: none;"><i class="fas fa-trash"></i> Remove Question</button>
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px; margin-top: 30px; flex-wrap: wrap; justify-content: center;">
                    <button type="button" id="add-question" class="btn"><i class="fas fa-plus"></i> Add Question</button>
                    <button type="submit" name="create_exam" class="btn btn-success"><i class="fas fa-save"></i> Create Exam</button>
                </div>
            </form>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const questionsContainer = document.getElementById('questions-container');
                    const addQuestionBtn = document.getElementById('add-question');
                    let questionCount = document.querySelectorAll('.question').length -1; // Initialize with 0 if one question exists, otherwise correct count
                    
                    // Update question numbers and icons
                    function updateQuestionNumbers() {
                        document.querySelectorAll('.question').forEach((q, index) => {
                            q.querySelector('.question-number').innerHTML = `<i class="fas fa-question-circle"></i> Question ${index + 1}`;
                            
                            // Re-index input names if questions are reordered or removed
                            const inputs = q.querySelectorAll('[name^="questions["]');
                            inputs.forEach(input => {
                                // Regex to replace the index part of the name
                                const newName = input.name.replace(/questions\[\d+\]/, `questions[${index}]`);
                                input.name = newName;
                            });
                        });
                    }
                    
                    // Add question functionality
                    addQuestionBtn.addEventListener('click', function() {
                        questionCount++; // Increment for the new question's index
                        const newQuestion = document.createElement('div');
                        newQuestion.className = 'question';
                        newQuestion.dataset.index = questionCount; // Store data-index for easier manipulation
                        
                        newQuestion.innerHTML = `
                            <div class="question-number">Question ${questionCount + 1}</div>
                            <div class="form-group">
                                <label><i class="fas fa-question"></i> Question Text</label>
                                <textarea name="questions[${questionCount}][text]" rows="2" required></textarea>
                            </div>
                            
                            <div class="options">
                                <div class="option">
                                    <input type="radio" name="questions[${questionCount}][correct_answer]" value="A" required>
                                    <input type="text" name="questions[${questionCount}][option_a]" placeholder="Option A" required>
                                </div>
                                
                                <div class="option">
                                    <input type="radio" name="questions[${questionCount}][correct_answer]" value="B">
                                    <input type="text" name="questions[${questionCount}][option_b]" placeholder="Option B" required>
                                </div>
                                
                                <div class="option">
                                    <input type="radio" name="questions[${questionCount}][correct_answer]" value="C">
                                    <input type="text" name="questions[${questionCount}][option_c]" placeholder="Option C" required>
                                </div>
                                
                                <div class="option">
                                    <input type="radio" name="questions[${questionCount}][correct_answer]" value="D">
                                    <input type="text" name="questions[${questionCount}][option_d]" placeholder="Option D" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-star"></i> Points</label>
                                <input type="number" name="questions[${questionCount}][points]" min="1" value="1" required>
                            </div>
                            
                            <button type="button" class="btn btn-danger remove-question"><i class="fas fa-trash"></i> Remove Question</button>
                        `;
                        
                        questionsContainer.appendChild(newQuestion);
                        updateQuestionNumbers(); // Re-index all questions
                        
                        // Animate the new question
                        newQuestion.style.opacity = '0';
                        newQuestion.style.transform = 'translateY(20px)';
                        setTimeout(() => {
                            newQuestion.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                            newQuestion.style.opacity = '1';
                            newQuestion.style.transform = 'translateY(0)';
                        }, 10);
                        
                        // Show remove buttons if there are multiple questions
                        if (document.querySelectorAll('.question').length > 1) {
                            document.querySelectorAll('.remove-question').forEach(btn => {
                                btn.style.display = 'inline-flex';
                            });
                        }
                    });
                    
                    // Remove question functionality
                    questionsContainer.addEventListener('click', function(e) {
                        if (e.target.classList.contains('remove-question') || e.target.closest('.remove-question')) {
                            const removeBtn = e.target.classList.contains('remove-question') ? e.target : e.target.closest('.remove-question');
                            const question = removeBtn.closest('.question');
                            const questions = document.querySelectorAll('.question');
                            
                            if (questions.length > 1) { // Only allow removal if more than one question exists
                                // Animate removal
                                question.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                                question.style.opacity = '0';
                                question.style.transform = 'translateY(-20px)';
                                
                                setTimeout(() => {
                                    question.remove();
                                    questionCount = document.querySelectorAll('.question').length - 1; // Recalculate based on actual elements
                                    updateQuestionNumbers(); // Re-index remaining questions
                                    
                                    // Hide remove buttons if only one question left
                                    if (document.querySelectorAll('.question').length === 1) {
                                        document.querySelector('.remove-question').style.display = 'none';
                                    }
                                }, 300);
                            } else {
                                // Shake animation to indicate cannot remove the last question
                                question.style.animation = 'shake 0.5s';
                                setTimeout(() => {
                                    question.style.animation = '';
                                }, 500);
                            }
                        }
                    });
                    
                    // Client-side form validation
                    document.getElementById('exam-form').addEventListener('submit', function(e) {
                        let isValid = true;
                        
                        // Clear previous error fields (visual feedback)
                        document.querySelectorAll('.error-field').forEach(el => {
                            el.classList.remove('error-field');
                        });
                        
                        // Basic form fields validation
                        const titleField = document.getElementById('title');
                        const descriptionField = document.getElementById('description');
                        const timeLimitField = document.getElementById('time_limit');
                        const startTimeField = document.getElementById('start_time');
                        const endTimeField = document.getElementById('end_time');

                        if (!titleField.value.trim()) { titleField.classList.add('error-field'); isValid = false; }
                        if (!descriptionField.value.trim()) { descriptionField.classList.add('error-field'); isValid = false; }
                        if (!timeLimitField.value || parseInt(timeLimitField.value) < 1) { timeLimitField.classList.add('error-field'); isValid = false; }

                        const startDate = new Date(startTimeField.value);
                        const endDate = new Date(endTimeField.value);
                        const now = new Date();

                        if (isNaN(startDate.getTime())) { startTimeField.classList.add('error-field'); isValid = false; }
                        if (isNaN(endDate.getTime())) { endTimeField.classList.add('error-field'); isValid = false; }
                        
                        if (isValid) { // Only check date logic if dates are validly parsed
                            if (startDate.getTime() >= endDate.getTime()) {
                                startTimeField.classList.add('error-field');
                                endTimeField.classList.add('error-field');
                                isValid = false;
                            }
                            // Allow for a small grace period for start time to account for submission delay
                            if (startDate.getTime() < now.getTime() - (60 * 1000) ) { 
                                startTimeField.classList.add('error-field');
                                isValid = false;
                            }
                        }

                        // Validate questions
                        const questions = document.querySelectorAll('.question');
                        if (questions.length === 0) {
                            // This scenario should be prevented by the initial question, but as a safeguard
                            // A dedicated error message might be needed if form starts with no questions
                            isValid = false; 
                        } else {
                            questions.forEach((q, index) => {
                                const textarea = q.querySelector('textarea[name$="[text]"]');
                                if (!textarea.value.trim()) {
                                    textarea.classList.add('error-field');
                                    isValid = false;
                                }
                                
                                const options = q.querySelectorAll('.option input[type="text"]');
                                options.forEach(opt => {
                                    if (!opt.value.trim()) {
                                        opt.classList.add('error-field');
                                        isValid = false;
                                    }
                                });
                                
                                if (!q.querySelector(`input[name="questions[${index}][correct_answer]"]:checked`)) {
                                    q.querySelector('.options').classList.add('error-field');
                                    isValid = false;
                                }
                                
                                const points = q.querySelector('input[name$="[points]"]');
                                if (!points.value || parseInt(points.value) < 1) {
                                    points.classList.add('error-field');
                                    isValid = false;
                                }
                            });
                        }
                        
                        if (!isValid) {
                            e.preventDefault(); // Prevent form submission
                            // Shake the form to indicate errors
                            this.style.animation = 'shake 0.5s';
                            setTimeout(() => {
                                this.style.animation = '';
                            }, 500);
                            
                            // Scroll to the first error field
                            const firstError = document.querySelector('.error-field');
                            if (firstError) {
                                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                firstError.focus();
                            }
                        }
                    });
                    
                    // Initial setup: update question numbers and hide remove button if only one question
                    updateQuestionNumbers();
                    if (document.querySelectorAll('.question').length === 1) {
                        document.querySelector('.remove-question').style.display = 'none';
                    }
                });
                
                // Function to copy text to clipboard with tooltip feedback
                function copyToClipboard(text, event) {
                    navigator.clipboard.writeText(text).then(() => {
                        const tooltip = event.target.querySelector('.tooltiptext') || event.target.closest('.tooltip').querySelector('.tooltiptext');
                        const originalText = tooltip.textContent;
                        tooltip.textContent = 'Copied!';
                        tooltip.classList.add('active'); // Add active class to show tooltip

                        setTimeout(() => {
                            tooltip.textContent = originalText;
                            tooltip.classList.remove('active'); // Remove active class to hide tooltip
                        }, 2000);
                    }).catch(err => {
                        console.error('Failed to copy text using Clipboard API: ', err);
                        // Fallback for older browsers or if Clipboard API fails (e.g., in some iframe contexts)
                        const tempInput = document.createElement('textarea');
                        tempInput.value = text;
                        document.body.appendChild(tempInput);
                        tempInput.select();
                        try {
                            document.execCommand('copy');
                            const tooltip = event.target.querySelector('.tooltiptext') || event.target.closest('.tooltip').querySelector('.tooltiptext');
                            const originalText = tooltip.textContent;
                            tooltip.textContent = 'Copied!';
                            tooltip.classList.add('active'); // Add active class

                            setTimeout(() => {
                                tooltip.textContent = originalText;
                                tooltip.classList.remove('active'); // Remove active class
                            }, 2000);
                        } catch (fallbackErr) {
                            console.error('Fallback copy failed: ', fallbackErr);
                        } finally {
                            document.body.removeChild(tempInput);
                        }
                    });
                }
            </script>
        <?php endif; ?>
    </div>
    
    <!-- Floating "Add Question" button -->
    <?php if (!isset($success) || !$success): ?>
        <div class="floating-btn" id="floating-add-btn" title="Add Question">
            <i class="fas fa-plus"></i>
        </div>
        
        <script>
            // Floating button functionality to add new questions
            document.getElementById('floating-add-btn').addEventListener('click', function() {
                document.getElementById('add-question').click(); // Programmatically click the hidden "Add Question" button
                
                // Add pulse animation for visual feedback
                this.style.animation = 'pulse 0.5s';
                setTimeout(() => {
                    this.style.animation = ''; // Reset animation
                }, 500);
                
                // Scroll to the newly added question after a short delay
                setTimeout(() => {
                    const questions = document.querySelectorAll('.question');
                    const lastQuestion = questions[questions.length - 1];
                    if (lastQuestion) { // Ensure a question was actually added
                        lastQuestion.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 350);
            });
        </script>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        &copy; <?php echo date("Y"); ?> Online Exam System. All rights reserved.
    </footer>
</body>
</html>
