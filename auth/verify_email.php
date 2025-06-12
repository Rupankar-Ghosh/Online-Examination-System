<?php
session_start();

// Check if signup data exists in session
if (!isset($_SESSION['signup_data'])) {
    header("Location: signup.html");
    exit();
}

$email = $_GET['email'] ?? '';
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - ExamFlow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a6bff;
            --secondary-color: #3a5bef;
            --error-color: #ff4a4a;
            --success-color: #4caf50;
            --text-color: #2d3748;
            --light-text: #718096;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: var(--bg-color);
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 500px;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: bold;
            color: var(--primary-color);
        }

        .logo span {
            color: var(--secondary-color);
        }

        .verification-card {
            background-color: var(--card-bg);
            padding: 40px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .verification-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary-color), var(--secondary-color));
        }

        h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: var(--text-color);
        }

        .instructions {
            margin-bottom: 20px;
            color: var(--light-text);
        }

        .email-display {
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-weight: 500;
        }

        .otp-input {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .otp-input input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }

        .otp-input input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 107, 255, 0.2);
        }

        .error-message {
            color: var(--error-color);
            margin-bottom: 15px;
            text-align: center;
        }

        .success-message {
            color: var(--success-color);
            margin-bottom: 15px;
            text-align: center;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: var(--transition);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(74, 107, 255, 0.4);
        }

        .resend-link {
            text-align: center;
            margin-top: 20px;
            color: var(--light-text);
        }

        .resend-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .resend-link a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .verification-card {
                padding: 30px 20px;
            }
            
            .otp-input input {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">Exam<span>Flow</span></div>
        
        <div class="verification-card">
            <h1>Verify Your Email</h1>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <?php 
                    if ($error === 'invalid_otp') echo "Invalid OTP. Please try again.";
                    if ($error === 'expired') echo "OTP has expired. Please request a new one.";
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message">
                    Email verified successfully! Redirecting to login...
                </div>
                <script>
                    setTimeout(function() {
                        window.location.href = "login.html";
                    }, 3000);
                </script>
            <?php endif; ?>

            <?php if (isset($_GET['resent'])): ?>
            <div class="success-message">
                A new verification code has been sent to your email.
            </div>
            <?php endif; ?>

            <?php if (!isset($_GET['resent'])): ?>
            <div class="success-message">
                Wait for a few seconds
            </div>
<script>
// Wait for the page to fully load
window.addEventListener('DOMContentLoaded', function() {
    // Find the resend link
    const resendLink = document.querySelector('a[href^="resend_otp.php"]');
    
    // If the link exists, click it after a 1-second delay
    if (resendLink) {
        setTimeout(function() {
            resendLink.click();
        }, 1000); // 1000ms = 1 second delay
    }
});
</script>
     
            <?php endif; ?>
            
            <div class="instructions">
                We've sent a 6-digit verification code to your email address. Please enter it below.
            </div>
            
            <div class="email-display">
                <?php echo htmlspecialchars($email); ?>
            </div>
            
            <form id="verifyForm" action="verify_otp.php" method="POST">
                <div class="form-group">
                    <label for="otp">Verification Code</label>
                    <div class="otp-input">
                        <input type="text" name="otp1" maxlength="1" pattern="[0-9]" required>
                        <input type="text" name="otp2" maxlength="1" pattern="[0-9]" required>
                        <input type="text" name="otp3" maxlength="1" pattern="[0-9]" required>
                        <input type="text" name="otp4" maxlength="1" pattern="[0-9]" required>
                        <input type="text" name="otp5" maxlength="1" pattern="[0-9]" required>
                        <input type="text" name="otp6" maxlength="1" pattern="[0-9]" required>
                    </div>
                </div>
                
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <button type="submit" class="btn">Verify Email</button>
            </form>
            
            <div class="resend-link">
                Didn't receive a code? <a href="resend_otp.php?email=<?php echo urlencode($email); ?>">Resend Code</a>
            </div>
        </div>
    </div>

    <script>
        // Auto-focus and move between OTP inputs
        const otpInputs = document.querySelectorAll('.otp-input input');
        
        otpInputs.forEach((input, index) => {
            input.addEventListener('input', () => {
                if (input.value.length === 1 && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
            });
            
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && input.value.length === 0 && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });
        });
    </script>
</body>
</html>