<?php
session_start();
// Database configuration
// Assuming 'config.php' handles database connection ($conn)
include 'config.php';

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Initialize message variables
$success_msg = '';
$error_msg = '';

// Handle exam deletion
// This block processes the form submission for deleting an exam.
if (isset($_POST['delete_exam'])) {
    $exam_id = $_POST['exam_id'];
    
    // Start a transaction for atomicity: delete questions then the exam.
    // This ensures that either both operations succeed or neither does, preventing orphaned data.
    $conn->begin_transaction();
    try {
        // Delete all questions related to the exam first to satisfy foreign key constraints.
        $delete_questions = $conn->prepare("DELETE FROM questions WHERE exam_id = ?");
        // 'i' specifies that $exam_id is an integer.
        $delete_questions->bind_param("i", $exam_id);
        $delete_questions->execute();
        
        // Then delete the exam itself, ensuring it belongs to the current user for security.
        $delete_exam = $conn->prepare("DELETE FROM exams WHERE exam_id = ? AND user_id = ?");
        // 'is' specifies that $exam_id is an integer and $user_id is a string.
        $delete_exam->bind_param("is", $exam_id, $user_id);
        
        if ($delete_exam->execute()) {
            // Commit the transaction if both deletions are successful.
            $conn->commit();
            $success_msg = "Exam deleted successfully!";
        } else {
            // Rollback if exam deletion fails.
            $conn->rollback();
            $error_msg = "Error deleting exam.";
        }
    } catch (mysqli_sql_exception $e) {
        // Catch any SQL exceptions during the transaction and rollback.
        $conn->rollback();
        $error_msg = "Database error: " . $e->getMessage();
    }
    
    // Redirect to prevent form resubmission on refresh (Post/Redirect/Get pattern).
    header("Location: dashboard.php?success=" . urlencode($success_msg) . "&error=" . urlencode($error_msg));
    exit();
}

// Handle exam details update (now includes password)
if (isset($_POST['action']) && $_POST['action'] === 'update_exam_details') {
    $exam_id = $_POST['exam_id'];
    $time_limit = filter_var($_POST['time_limit'], FILTER_VALIDATE_INT);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $new_password = $_POST['new_password'] ?? ''; // Get new password, default to empty string

    $update_fields = [];
    $bind_types = "";
    $bind_params = [];

    // Add time_limit to update fields
    if ($time_limit === false || $time_limit <= 0) {
        $error_msg = "Time limit must be a positive integer.";
    } else {
        $update_fields[] = "time_limit = ?";
        $bind_types .= "i";
        $bind_params[] = $time_limit;
    }

    // Add start_time and end_time to update fields
    if (empty($start_time) || empty($end_time)) {
        $error_msg = ($error_msg ? $error_msg . " " : "") . "Start and end times cannot be empty.";
    } elseif (strtotime($start_time) >= strtotime($end_time)) {
        $error_msg = ($error_msg ? $error_msg . " " : "") . "End time must be after start time.";
    } else {
        $update_fields[] = "start_time = ?";
        $bind_types .= "s";
        $bind_params[] = $start_time;
        
        $update_fields[] = "end_time = ?";
        $bind_types .= "s";
        $bind_params[] = $end_time;
    }

    // Add new_password to update fields if provided
    if (!empty($new_password)) {
        $update_fields[] = "exam_password = ?";
        $bind_types .= "s";
        $bind_params[] = $new_password;
    }

    if (!empty($error_msg)) {
        header("Location: dashboard.php?success=" . urlencode($success_msg) . "&error=" . urlencode($error_msg));
        exit();
    }

    if (empty($update_fields)) {
        $error_msg = "No valid fields provided for update.";
        header("Location: dashboard.php?success=" . urlencode($success_msg) . "&error=" . urlencode($error_msg));
        exit();
    }

    // Construct the SQL query dynamically
    $sql = "UPDATE exams SET " . implode(", ", $update_fields) . " WHERE exam_id = ? AND user_id = ?";
    $bind_types .= "is"; // Add types for exam_id and user_id
    $bind_params[] = $exam_id;
    $bind_params[] = $user_id;

    $update_details_stmt = $conn->prepare($sql);
    if ($update_details_stmt) {
        // Use call_user_func_array to bind parameters dynamically
        call_user_func_array([$update_details_stmt, 'bind_param'], array_merge([$bind_types], $bind_params));
        
        if ($update_details_stmt->execute()) {
            $success_msg = "Exam details updated successfully!";
        } else {
            $error_msg = "Error updating exam details: " . $conn->error;
        }
        $update_details_stmt->close();
    } else {
        $error_msg = "Failed to prepare update statement: " . $conn->error;
    }
    
    header("Location: dashboard.php?success=" . urlencode($success_msg) . "&error=" . urlencode($error_msg));
    exit();
}


// Check for success/error messages passed via URL parameters after redirection
if (isset($_GET['success'])) {
    $success_msg = htmlspecialchars($_GET['success']);
}
if (isset($_GET['error'])) {
    $error_msg = htmlspecialchars($_GET['error']);
}

// Get user's created exams with all relevant details
$created_exams_stmt = $conn->prepare("
    SELECT 
        e.exam_id, 
        e.title, 
        e.description,
        e.exam_password,
        e.time_limit,
        e.start_time,
        e.end_time,
        COUNT(DISTINCT q.question_id) as question_count,
        COUNT(DISTINCT er.result_id) as attempts_count,
        IFNULL(AVG(er.score), 0) as avg_score,
        IFNULL(MAX(er.score), 0) as high_score,
        (SELECT SUM(points) FROM questions WHERE exam_id = e.exam_id) as total_points
    FROM exams e
    LEFT JOIN questions q ON e.exam_id = q.exam_id
    LEFT JOIN exam_results er ON e.exam_id = er.exam_id
    WHERE e.user_id = ?
    GROUP BY e.exam_id
    ORDER BY e.created_at DESC
");
$created_exams_stmt->bind_param("s", $user_id);
$created_exams_stmt->execute();
$created_exams = $created_exams_stmt->get_result();

// Get user's exam results with more details
$exam_results_stmt = $conn->prepare("
    SELECT 
        er.result_id, 
        e.title, 
        er.score, 
        er.completed_at, 
        (SELECT SUM(points) FROM questions WHERE exam_id = e.exam_id) as total_points,
        (SELECT COUNT(*) FROM exam_results er2 WHERE er2.exam_id = e.exam_id) as total_attempts,
        (SELECT COUNT(*) FROM exam_results er3 WHERE er3.exam_id = e.exam_id AND er3.score >= er.score) as rank
    FROM exam_results er
    JOIN exams e ON er.exam_id = e.exam_id
    WHERE er.participant_id = ?
    ORDER BY er.completed_at DESC
");
$exam_results_stmt->bind_param("s", $user_id);
$exam_results_stmt->execute();
$exam_results = $exam_results_stmt->get_result();

// Get user info
$user_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$user_stmt->bind_param("s", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ExamFlow</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

        /* Basic styling for the modal overlay and content */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
        }

        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 0.75rem; /* rounded-xl */
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1), 0 4px 6px rgba(0, 0, 0, 0.05); /* shadow-xl */
            width: 90%;
            max-width: 400px;
            transform: translateY(-20px);
            opacity: 0;
            transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
        }

        .modal-overlay.show .modal-content {
            transform: translateY(0);
            opacity: 1;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="min-h-screen text-gray-800">
    <header class="bg-white shadow-xl py-5 mb-8">
        <div class="container mx-auto px-4 flex flex-col sm:flex-row justify-between items-center">
            <a href="index.php" class="text-3xl font-extrabold text-blue-600 tracking-tight hover:text-blue-700 transition duration-300 mb-4 sm:mb-0">ExamFlow</a>
            
            <nav class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto mb-4 sm:mb-0">
                <a href="create_exam.php" class="inline-flex items-center justify-center w-full sm:w-auto bg-green-500 text-white py-2 px-4 rounded-full font-semibold text-sm hover:bg-green-600 transition duration-300 shadow-md">
                    <i class="fas fa-plus-circle mr-2"></i>Create Exam
                </a>
                <a href="exam_entry.php" class="inline-flex items-center justify-center w-full sm:w-auto bg-blue-500 text-white py-2 px-4 rounded-full font-semibold text-sm hover:bg-blue-600 transition duration-300 shadow-md">
                    <i class="fas fa-play-circle mr-2"></i>Join Exam
                </a>
            </nav>

            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold text-lg uppercase shadow-md">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
                <div class="text-right">
                    <div class="font-semibold text-lg"><?php echo htmlspecialchars($user['name']); ?></div>
                    <form action="logout.php" method="POST" class="inline">
                        <button type="submit" class="text-gray-500 hover:text-red-500 text-sm font-medium transition duration-300">
                            <i class="fas fa-sign-out-alt mr-1"></i>Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>
    
    <div class="container mx-auto px-4">
        <?php if (!empty($success_msg)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6 shadow-md" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline"><?php echo $success_msg; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_msg)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6 shadow-md" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?php echo $error_msg; ?></span>
            </div>
        <?php endif; ?>
        
        <h1 class="text-4xl font-bold text-gray-800 mb-8 text-center sm:text-left">Welcome back, <span class="text-blue-600"><?php echo htmlspecialchars(explode(' ', $user['name'])[0]); ?>!</span></h1>
        
        <h2 class="text-3xl font-semibold text-gray-700 border-b border-gray-300 pb-3 mb-6">Your Exams</h2>
        <?php if ($created_exams->num_rows > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                <?php while ($exam = $created_exams->fetch_assoc()): 
                    $percentage_avg = $exam['total_points'] > 0 ? round(($exam['avg_score'] / $exam['total_points']) * 100) : 0;
                    $percentage_high = $exam['total_points'] > 0 ? round(($exam['high_score'] / $exam['total_points']) * 100) : 0;
                ?>
                    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 flex flex-col justify-between border border-gray-200">
                        <div>
                            <h3 class="text-xl font-bold text-blue-600 mb-2"><?php echo htmlspecialchars($exam['title']); ?></h3>
                            <p class="text-gray-600 text-sm mb-4 line-clamp-3"><?php echo htmlspecialchars($exam['description']); ?></p>
                            <div class="flex justify-between text-gray-500 text-xs mb-3">
                                <span><i class="fas fa-question-circle mr-1"></i><?php echo $exam['question_count']; ?> Questions</span>
                                <span><i class="fas fa-users mr-1"></i><?php echo $exam['attempts_count']; ?> Attempts</span>
                            </div>

                            <div class="mt-4 border-t border-gray-200 pt-4">
                                <label class="block text-gray-700 text-sm font-semibold mb-2">Exam ID:</label>
                                <div class="flex items-center bg-gray-100 p-2 rounded-md border border-gray-200">
                                    <span class="exam-id flex-grow font-mono text-gray-800 mr-2" data-id="<?php echo htmlspecialchars($exam['exam_id']); ?>"><?php echo htmlspecialchars($exam['exam_id']); ?></span>
                                    <button type="button" class="copy-exam-id text-gray-500 hover:text-blue-600 transition duration-200 p-1 rounded" data-id-target=".exam-id">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mt-4 border-t border-gray-200 pt-4">
                                <label class="block text-gray-700 text-sm font-semibold mb-2">Exam Password:</label>
                                <div class="flex items-center bg-gray-100 p-2 rounded-md border border-gray-200">
                                    <span class="exam-password flex-grow font-mono text-gray-800 mr-2" data-password="<?php echo htmlspecialchars($exam['exam_password']); ?>">********</span>
                                    <button type="button" class="toggle-password-visibility text-gray-500 hover:text-blue-600 transition duration-200 p-1 rounded">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="copy-password text-gray-500 hover:text-blue-600 transition duration-200 ml-2 p-1 rounded" data-password-target=".exam-password">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mt-4 border-t border-gray-200 pt-4 text-sm text-gray-700">
                                <p><span class="font-semibold">Time Limit:</span> <?php echo htmlspecialchars($exam['time_limit']); ?> minutes</p>
                                <p><span class="font-semibold">Starts:</span> <?php echo date('M j, Y g:i A', strtotime($exam['start_time'])); ?></p>
                                <p><span class="font-semibold">Ends:</span> <?php echo date('M j, Y g:i A', strtotime($exam['end_time'])); ?></p>
                            </div>

                        </div>
                        
                        <?php if ($exam['attempts_count'] > 0): ?>
                        <div class="bg-blue-50 p-3 rounded-md mt-4 border border-blue-100">
                            <div class="flex justify-between items-center text-sm mb-1">
                                <span class="font-medium text-gray-700">Avg. Score:</span>
                                <span class="font-bold text-blue-700"><?php echo $exam['avg_score']; ?>/<?php echo $exam['total_points']; ?> (<?php echo $percentage_avg; ?>%)</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="font-medium text-gray-700">High Score:</span>
                                <span class="font-bold text-green-700"><?php echo $exam['high_score']; ?>/<?php echo $exam['total_points']; ?> (<?php echo $percentage_high; ?>%)</span>
                            </div>
                        </div>
                        <?php else: ?>
                            <div class="bg-gray-50 p-3 rounded-md mt-4 text-center text-gray-500 text-sm">
                                No attempts recorded yet.
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex flex-col sm:flex-row gap-3 mt-6">
                            <a href="exam_results.php?exam_id=<?php echo $exam['exam_id']; ?>" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg font-semibold text-center hover:bg-blue-700 transition duration-300 shadow-md">
                                <i class="fas fa-chart-bar mr-2"></i>View Results
                            </a>
                            <button type="button" 
                                    class="flex-1 bg-indigo-500 text-white py-2 px-4 rounded-lg font-semibold text-center hover:bg-indigo-600 transition duration-300 shadow-md open-edit-details-modal"
                                    data-exam-id="<?php echo $exam['exam_id']; ?>"
                                    data-exam-title="<?php echo htmlspecialchars($exam['title']); ?>"
                                    data-time-limit="<?php echo htmlspecialchars($exam['time_limit']); ?>"
                                    data-start-time="<?php echo date('Y-m-d\TH:i', strtotime($exam['start_time'])); ?>"
                                    data-end-time="<?php echo date('Y-m-d\TH:i', strtotime($exam['end_time'])); ?>"
                                    data-current-password="<?php echo htmlspecialchars($exam['exam_password']); ?>">
                                <i class="fas fa-edit mr-2"></i>Edit Details
                            </button>
                            <button type="button" 
                                    class="flex-1 bg-red-500 text-white py-2 px-4 rounded-lg font-semibold text-center hover:bg-red-600 transition duration-300 shadow-md open-delete-modal"
                                    data-exam-id="<?php echo $exam['exam_id']; ?>">
                                <i class="fas fa-trash-alt mr-2"></i>Delete Exam
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow-lg p-10 text-center border border-gray-200">
                <i class="fas fa-feather-alt text-6xl text-gray-400 mb-6"></i>
                <p class="text-gray-600 text-lg mb-6">You haven't created any exams yet. Let's get started!</p>
                <a href="create_exam.php" class="inline-flex items-center justify-center w-full sm:w-auto bg-green-500 text-white py-3 px-8 rounded-full font-bold text-lg hover:bg-green-600 transition duration-300 shadow-lg hover:shadow-xl">
                    <i class="fas fa-plus-circle mr-3"></i>Create Your First Exam
                </a>
            </div>
        <?php endif; ?>
        
        <h2 class="text-3xl font-semibold text-gray-700 border-b border-gray-300 pb-3 mb-6 mt-12">Your Exam History</h2>
        <?php if ($exam_results->num_rows > 0): ?>
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 mb-10">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-blue-600 text-white">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-medium uppercase tracking-wider rounded-tl-xl">Exam</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-medium uppercase tracking-wider">Score</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-medium uppercase tracking-wider">Rank</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-medium uppercase tracking-wider rounded-tr-xl">Date Completed</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($result = $exam_results->fetch_assoc()): 
                                $total_points = $result['total_points'] ?: 1; // Avoid division by zero
                                $percentage = ($result['score'] / $total_points) * 100;
                                $passed = $percentage >= 60; // Assuming 60% is passing
                                
                                $total_attempts = $result['total_attempts'] ?: 1; // Avoid division by zero
                                $rank_percentage = ($result['rank'] / $total_attempts) * 100;
                            ?>
                                <tr class="hover:bg-gray-50 transition duration-150 ease-in-out">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($result['title']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold <?php echo $passed ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo $result['score']; ?>/<?php echo $result['total_points']; ?>
                                        <span class="text-xs text-gray-500 ml-1">(<?php echo round($percentage); ?>%)</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?php if ($result['total_attempts'] > 1): ?>
                                            Top <?php echo round($rank_percentage); ?>%
                                            <span class="text-xs text-gray-500 ml-1">(<?php echo $result['rank']; ?>/<?php echo $result['total_attempts']; ?>)</span>
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
                <i class="fas fa-book-open text-6xl text-gray-400 mb-6"></i>
                <p class="text-gray-600 text-lg mb-6">You haven't taken any exams yet. Find one and test your knowledge!</p>
                <a href="exam_entry.php" class="inline-flex items-center justify-center w-full sm:w-auto bg-blue-500 text-white py-3 px-8 rounded-full font-bold text-lg hover:bg-blue-600 transition duration-300 shadow-lg hover:shadow-xl">
                    <i class="fas fa-play-circle mr-3"></i>Take an Exam
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div id="deleteModal" class="modal-overlay hidden">
        <div class="modal-content">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Confirm Deletion</h3>
            <p class="text-gray-700 mb-6">Are you sure you want to delete this exam? This action cannot be undone.</p>
            <div class="flex justify-end gap-3">
                <button id="cancelDelete" class="px-5 py-2 border border-gray-300 rounded-md text-gray-700 font-medium hover:bg-gray-100 transition duration-300">
                    Cancel
                </button>
                <form id="deleteForm" method="POST" class="inline-block">
                    <input type="hidden" name="exam_id" id="modalDeleteExamId">
                    <button type="submit" name="delete_exam" class="px-5 py-2 bg-red-600 text-white rounded-md font-medium hover:bg-red-700 transition duration-300">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div id="editDetailsModal" class="modal-overlay hidden">
        <div class="modal-content">
            <h3 class="text-xl font-bold text-gray-900 mb-4" id="editDetailsModalTitle">Edit Exam Details</h3>
            <form id="editDetailsForm" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="update_exam_details">
                <input type="hidden" name="exam_id" id="modalEditDetailsExamId">
                
                <div>
                    <label for="edit_time_limit" class="block text-sm font-medium text-gray-700 text-left mb-1">Time Limit (minutes)</label>
                    <input type="number" id="edit_time_limit" name="time_limit" required min="1"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                
                <div>
                    <label for="edit_start_time" class="block text-sm font-medium text-gray-700 text-left mb-1">Start Time</label>
                    <input type="datetime-local" id="edit_start_time" name="start_time" required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                
                <div>
                    <label for="edit_end_time" class="block text-sm font-medium text-gray-700 text-left mb-1">End Time</label>
                    <input type="datetime-local" id="edit_end_time" name="end_time" required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <label for="edit_new_password" class="block text-sm font-medium text-gray-700 text-left mb-1">New Password (leave blank to keep current)</label>
                    <input type="text" id="edit_new_password" name="new_password"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" id="cancelEditDetails" class="px-5 py-2 border border-gray-300 rounded-md text-gray-700 font-medium hover:bg-gray-100 transition duration-300">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700 transition duration-300">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Common Modal Logic (for Delete and Edit Details Modals) ---
            function setupModal(modalId, openButtonsSelector, idDataAttribute, titleDataAttribute, modalTitleElementId, hiddenIdInputId, additionalDataAttributes = {}) {
                const modal = document.getElementById(modalId);
                const openButtons = document.querySelectorAll(openButtonsSelector);
                const cancelButton = modal.querySelector('button[id^="cancel"]'); // Finds button with ID starting with 'cancel'
                const hiddenIdInput = document.getElementById(hiddenIdInputId);
                const modalTitleElement = modalTitleElementId ? document.getElementById(modalTitleElementId) : null;

                function showModal(examId, examTitle = '', additionalData = {}) {
                    hiddenIdInput.value = examId;
                    if (modalTitleElement) {
                        modalTitleElement.textContent = `Edit Details for "${examTitle}"`;
                    }
                    // Populate additional fields for the specific modal
                    for (const key in additionalData) {
                        const inputElement = document.getElementById(`edit_${key}`);
                        if (inputElement) {
                            inputElement.value = additionalData[key];
                        }
                    }
                    modal.classList.remove('hidden');
                    setTimeout(() => { modal.classList.add('show'); }, 10);
                }

                function hideModal() {
                    modal.classList.remove('show');
                    setTimeout(() => { modal.classList.add('hidden'); }, 300);
                }

                openButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const examId = this.dataset[idDataAttribute];
                        const examTitle = this.dataset[titleDataAttribute];
                        const additionalData = {};
                        for (const key in additionalDataAttributes) {
                            // Convert data attribute name from kebab-case (HTML) to camelCase (JS)
                            const dataAttrKey = additionalDataAttributes[key]; // Use the value directly as it's already camelCase
                            additionalData[key] = this.dataset[dataAttrKey];
                        }
                        showModal(examId, examTitle, additionalData);
                    });
                });

                cancelButton.addEventListener('click', hideModal);
                modal.addEventListener('click', function(event) {
                    if (event.target === modal) {
                        hideModal();
                    }
                });
            }

            // Setup for Delete Modal
            setupModal('deleteModal', '.open-delete-modal', 'examId', '', '', 'modalDeleteExamId');

            // Setup for Edit Exam Details Modal (now includes password field)
            setupModal('editDetailsModal', '.open-edit-details-modal', 'examId', 'examTitle', 'editDetailsModalTitle', 'modalEditDetailsExamId', {
                'time_limit': 'timeLimit',
                'start_time': 'startTime',
                'end_time': 'endTime',
                'new_password': 'currentPassword' // Map data-current-password to edit_new_password input
            });


            // --- Password Visibility Toggle ---
            document.querySelectorAll('.toggle-password-visibility').forEach(button => {
                button.addEventListener('click', function() {
                    const passwordSpan = this.closest('.bg-gray-100').querySelector('.exam-password');
                    const icon = this.querySelector('i');
                    if (passwordSpan.textContent === '********') {
                        // Reveal password
                        passwordSpan.textContent = passwordSpan.dataset.password;
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        // Mask password
                        passwordSpan.textContent = '********';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });

            // --- Copy to Clipboard Functionality for Passwords ---
            document.querySelectorAll('.copy-password').forEach(button => {
                button.addEventListener('click', function() {
                    const targetSelector = this.dataset.passwordTarget;
                    const passwordSpan = this.closest('.bg-gray-100').querySelector(targetSelector);
                    
                    // Create a temporary textarea to copy content
                    const tempInput = document.createElement('textarea');
                    // Use the original password from data-password for copying
                    tempInput.value = passwordSpan.dataset.password; 
                    document.body.appendChild(tempInput);
                    tempInput.select();
                    document.execCommand('copy');
                    document.body.removeChild(tempInput);

                    // Optional: Provide visual feedback to the user
                    const originalIconClass = button.querySelector('i').className;
                    const originalColor = button.style.color;
                    button.querySelector('i').classList.replace('fa-copy', 'fa-check');
                    button.style.color = '#28a745'; // Green color for success
                    setTimeout(() => {
                        button.querySelector('i').className = originalIconClass; // Revert icon
                        button.style.color = originalColor; // Revert color
                    }, 1500); // Revert after 1.5 seconds
                });
            });

            // --- Copy to Clipboard Functionality for Exam IDs ---
            document.querySelectorAll('.copy-exam-id').forEach(button => {
                button.addEventListener('click', function() {
                    const targetSelector = this.dataset.idTarget;
                    const idSpan = this.closest('.bg-gray-100').querySelector(targetSelector);
                    
                    const tempInput = document.createElement('textarea');
                    tempInput.value = idSpan.dataset.id; // Get the ID from the data-id attribute
                    document.body.appendChild(tempInput);
                    tempInput.select();
                    document.execCommand('copy');
                    document.body.removeChild(tempInput);

                    const originalIconClass = button.querySelector('i').className;
                    const originalColor = button.style.color;
                    button.querySelector('i').classList.replace('fa-copy', 'fa-check');
                    button.style.color = '#28a745'; // Green color for success
                    setTimeout(() => {
                        button.querySelector('i').className = originalIconClass; // Revert icon
                        button.style.color = originalColor; // Revert color
                    }, 1500); // Revert after 1.5 seconds
                });
            });
        });
    </script>
</body>
</html>
