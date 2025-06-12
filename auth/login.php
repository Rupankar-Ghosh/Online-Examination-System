<?php
session_start();

// Database configuration
include 'config.php';


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is temporarily blocked
function isUserBlocked($conn, $email) {
    $stmt = $conn->prepare("SELECT last_attempt, failed_attempts FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if ($user['failed_attempts'] >= 5) {
            $lastAttempt = strtotime($user['last_attempt']);
            $currentTime = time();
            $waitTime = 300; // 5 minutes in seconds
            
            if (($currentTime - $lastAttempt) < $waitTime) {
                $remainingTime = ceil(($waitTime - ($currentTime - $lastAttempt)) / 60);
                return [
                    'blocked' => true,
                    'remaining_time' => $remainingTime
                ];
            } else {
                // Reset attempts if wait time has passed
                $resetStmt = $conn->prepare("UPDATE users SET failed_attempts = 0 WHERE email = ?");
                $resetStmt->bind_param("s", $email);
                $resetStmt->execute();
                $resetStmt->close();
            }
        }
    }
    $stmt->close();
    return ['blocked' => false];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    // Check if user is blocked first
    $blockStatus = isUserBlocked($conn, $email);
    if ($blockStatus['blocked']) {
        header("Location: login.html?error=account_locked&time=" . $blockStatus['remaining_time']);
        exit();
    }
    
    // Prepare and bind
    $stmt = $conn->prepare("SELECT id, name, email, password, failed_attempts FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Password is correct, reset failed attempts
            $updateStmt = $conn->prepare("UPDATE users SET failed_attempts = 0 WHERE email = ?");
            $updateStmt->bind_param("s", $email);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Start session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time();
            
            header("Location: ../dashboard.php");
            exit();
        } else {
            // Increment failed attempts
            $attempts = $user['failed_attempts'] + 1;
            $remainingAttempts = 5 - $attempts;
            
            $updateStmt = $conn->prepare("UPDATE users SET failed_attempts = ?, last_attempt = NOW() WHERE email = ?");
            $updateStmt->bind_param("is", $attempts, $email);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Check if account should be locked after this attempt
            if ($attempts >= 5) {
                header("Location: login.html?error=account_locked&time=5");
            } else {
                // Show invalid credentials with remaining attempts
                header("Location: login.html?error=invalid_credentials&attempts=" . $remainingAttempts);
            }
            exit();
        }
    } else {
        // User doesn't exist, but we don't reveal that
        header("Location: login.html?error=invalid_credentials");
        exit();
    }
    
    $stmt->close();
}

// Handle session expired redirects
if (isset($_GET['session_expired'])) {
    header("Location: login.html?error=session_expired");
    exit();
}

$conn->close();
?>