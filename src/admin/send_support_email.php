<?php
// send_support_email.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
require '../config.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Sanitize input
    $name = strip_tags(trim($_POST["name"]));
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $subject = strip_tags(trim($_POST["subject"]));
    $message = strip_tags(trim($_POST["message"]));
    
    // Validate
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please fill in all required fields.'
        ]);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please enter a valid email address.'
        ]);
        exit;
    }
    
    // Fetch support email from database
    $email_query = "SELECT setting_value FROM site_settings WHERE setting_key = 'support_email'";
    $email_result = mysqli_query($connect, $email_query);
    $support_email = $gmail_smtp_username; // Default fallback from credentials.php
    
    if ($email_result && mysqli_num_rows($email_result) > 0) {
        $email_row = mysqli_fetch_assoc($email_result);
        $support_email = $email_row['setting_value'];
    }
    
    // Fetch SMTP settings from database
    $smtp_user_query = "SELECT setting_value FROM site_settings WHERE setting_key = 'smtp_username'";
    $smtp_user_result = mysqli_query($connect, $smtp_user_query);
    $smtp_username = $gmail_smtp_username; // Default fallback from credentials.php
    
    if ($smtp_user_result && mysqli_num_rows($smtp_user_result) > 0) {
        $smtp_row = mysqli_fetch_assoc($smtp_user_result);
        $smtp_username = $smtp_row['setting_value'];
    }
    
    $smtp_pass_query = "SELECT setting_value FROM site_settings WHERE setting_key = 'smtp_password'";
    $smtp_pass_result = mysqli_query($connect, $smtp_pass_query);
    $smtp_password = $gmail_smtp_password; // Default fallback from credentials.php
    
    if ($smtp_pass_result && mysqli_num_rows($smtp_pass_result) > 0) {
        $smtp_pass_row = mysqli_fetch_assoc($smtp_pass_result);
        $smtp_password = $smtp_pass_row['setting_value'];
    }
    
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_username; // Use SMTP username from database
        $mail->Password   = $smtp_password; // Use SMTP password from database
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom($smtp_username, 'FacilityOps Support'); // Send FROM the SMTP account
        $mail->addAddress($support_email); // Send TO the support email
        $mail->addReplyTo($email, $name); // User can reply to the customer
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Support Request: " . $subject;
        $mail->Body    = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #1a365d; color: white; padding: 20px; text-align: center; }
                    .content { background: #f7fafc; padding: 30px; }
                    .field { margin-bottom: 15px; }
                    .label { font-weight: bold; color: #2c5282; }
                    .message-box { background: white; padding: 15px; border-left: 4px solid #2c5282; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>New Support Request</h2>
                    </div>
                    <div class='content'>
                        <div class='field'>
                            <span class='label'>From:</span> $name
                        </div>
                        <div class='field'>
                            <span class='label'>Email:</span> $email
                        </div>
                        <div class='field'>
                            <span class='label'>Subject:</span> $subject
                        </div>
                        <div class='message-box'>
                            <div class='label'>Message:</div>
                            <p>" . nl2br(htmlspecialchars($message)) . "</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
        ";
        $mail->AltBody = "Name: $name\nEmail: $email\nSubject: $subject\n\nMessage:\n$message";
        
        $mail->send();
        
        echo json_encode([
            'success' => true,
            'message' => 'Thank you! Your message has been sent successfully. We will respond within 24 hours.'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Message could not be sent. Error: ' . $mail->ErrorInfo
        ]);
    }
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}
?>