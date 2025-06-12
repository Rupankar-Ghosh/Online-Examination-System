<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

function sendVerificationEmail($email, $name, $otp) {
    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'rupankarghosh2012@gmail.com';
        $mail->Password   = 'zdqqrycmasqkjsak';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Disable TLS verification (for development)
        $mail->SMTPAutoTLS = false;
        $mail->isHTML(true);
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Sender and Recipient
        $mail->setFrom('rupankarghosh2012@gmail.com', 'ExamFlow');
        $mail->addAddress($email, $name);

        // Email Content
        $mail->Subject = 'ExamFlow - Email Verification';
        $mail->Body    = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
        }
        .container {
            padding: 20px;
            border: 1px solid #e5e5e5;
            border-radius: 5px;
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e5e5;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #4361ee;
        }
        .content {
            padding: 20px 0;
        }
        .otp-code {
            font-size: 32px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 5px;
            color: #4361ee;
            margin: 20px 0;
            padding: 10px;
            background-color: #f5f7ff;
            border-radius: 5px;
        }
        .footer {
            padding-top: 20px;
            border-top: 1px solid #e5e5e5;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">ExamFlow</div>
        </div>
        <div class="content">
            <h2>Verify Your Email Address</h2>
            <p>Hello ' . htmlspecialchars($name) . ',</p>
            <p>Thank you for signing up with ExamFlow. To complete your registration, please use the verification code below:</p>
            
            <div class="otp-code">' . htmlspecialchars($otp) . '</div>
            
            <p>This code will expire in 5 minutes.</p>
            <p>If you did not create an account with ExamFlow, please ignore this email.</p>
        </div>
        <div class="footer">
            <p>&copy; ' . date('Y') . ' ExamFlow. All rights reserved.</p>
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
';

        // Send Email
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>