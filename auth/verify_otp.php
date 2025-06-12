<?php
session_start();

// Check if signup data exists in session
if (!isset($_SESSION['signup_data'])) {
    header("Location: signup.html");
    exit();
}

// Database configuration
include 'config.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $otp = $_POST['otp1'] . $_POST['otp2'] . $_POST['otp3'] . $_POST['otp4'] . $_POST['otp5'] . $_POST['otp6'];
    
    $signup_data = $_SESSION['signup_data'];
    
    // Check if OTP has expired
    if (time() > $signup_data['expires']) {
        header("Location: verify_email.php?email=" . urlencode($email) . "&error=expired");
        exit();
    }
    
    // Check OTP attempts
    if ($signup_data['attempts'] >= 3) {
        session_unset();
        session_destroy();
        header("Location: signup.html?error=max_attempts");
        exit();
    }
    
    // Verify OTP
    if ($otp == $signup_data['otp']) {
        // OTP is correct - create user account
        
        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO users (id, name, email, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $signup_data['user_id'], $signup_data['name'], $signup_data['email'], $signup_data['password']);
        
        if ($stmt->execute()) {
            // Account created successfully
            session_unset();
            session_destroy();
            header("Location: login.php");
            header("Location: verify_email.php?email=" . urlencode($email) . "&success=1");
            exit();
        } else {
            // Database error
            header("Location: verify_email.php?email=" . urlencode($email) . "&error=database_error");
            exit();
        }
    } else {
        // Invalid OTP
        $_SESSION['signup_data']['attempts']++;
        header("Location: verify_email.php?email=" . urlencode($email) . "&error=invalid_otp");
        exit();
    }
}

$conn->close();
?>