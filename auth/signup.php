<?php
// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set headers to prevent caching
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

session_start();

// Database configuration
include 'config.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to generate random OTP
function generateOTP() {
    return rand(100000, 999999);
}

// Function to generate user ID
function generateUserID($name) {
    $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 3));
    $random = rand(1000, 9999);
    return $prefix . $random;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $conn->real_escape_string($_POST['password']);
    
    // Check if email already exists
    $checkEmail = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();
    
    if ($checkEmail->num_rows > 0) {
        // Email already exists
        header("Location: signup.html?error=email_exists");
        exit();
    }
    
    // Generate OTP and user ID
    $otp = generateOTP();
    $user_id = generateUserID($name);
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Store data in session for verification
    session_start();
    $_SESSION['signup_data'] = [
        'name' => $name,
        'email' => $email,
        'password' => $hashed_password,
        'otp' => $otp,
        'user_id' => $user_id,
        'attempts' => 0,
        'expires' => time() + 300 // OTP expires in 5 minutes
    ];
    
    // In a real application, you would send the OTP via email
    // For this example, we'll just display it (remove this in production)
    echo "<h2>Development Mode - OTP: $otp</h2>";
    
    // Redirect to OTP verification page
    header("Location: verify_email.php?email=".urlencode($email));
    exit();
   
}

$conn->close();
?>