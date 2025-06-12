<?php
require 'mail_config.php';
session_start();

// Check if signup data exists in session
if (!isset($_SESSION['signup_data'])) {
    header("Location: signup.html");
    exit();
}

// Generate new OTP
function generateOTP() {
    return rand(100000, 999999);
}

$email = $_GET['email'] ?? '';

// Update OTP in session
$_SESSION['signup_data']['otp'] = generateOTP();
$_SESSION['signup_data']['expires'] = time() + 300; // 5 minutes from now
$_SESSION['signup_data']['attempts'] = 0; // Reset attempts

// Send new verification email
if (sendVerificationEmail($email, $_SESSION['signup_data']['name'], $_SESSION['signup_data']['otp'])) {
    header("Location: verify_email.php?email=" . urlencode($email) . "&resent=1");
} else {
    header("Location: verify_email.php?email=" . urlencode($email) . "&error=email_failed");
}
exit();
?>