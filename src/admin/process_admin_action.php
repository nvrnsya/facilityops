<?php
include("../config.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

// Function to get facility display name
function getFacilityDisplayName($facilityName) {
    $facilityMap = [
        'dewan-kuliah-utama' => 'Dewan Kuliah Utama',
        'bilik-makan-bauk-inn' => 'Bilik Makan Bauk Inn',
        'bilik-seminar' => 'Bilik Seminar',
        'bilik-kuliah-2' => 'Bilik Kuliah 2',
        'puspanita' => 'Puspanita'
    ];
    
    return isset($facilityMap[$facilityName]) ? $facilityMap[$facilityName] : $facilityName;
}

// Function to send email notification
function sendBookingNotificationEmail($userEmail, $userName, $bookingData, $action) {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $gmail_smtp_username;
        $mail->Password   = $gmail_smtp_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('noreply@facilityops.com', 'FacilityOps Support');
        $mail->addAddress($userEmail, $userName);
        
        // Determine status and styling based on action
        if ($action === 'approve') {
            $status = 'APPROVED';
            $statusColor = '#28a745';
            $statusBg = '#d4edda';
            $icon = '✓';
            $message = 'Your booking has been <strong>APPROVED</strong>!';
            $subject = 'Booking Approved - FacilityOps';
        } else {
            $status = 'REJECTED';
            $statusColor = '#dc3545';
            $statusBg = '#f8d7da';
            $icon = '✕';
            $message = 'Your booking has been <strong>REJECTED</strong>.';
            $subject = 'Booking Rejected - FacilityOps';
        }
        
        // Build email content
        $facilityDisplay = getFacilityDisplayName($bookingData['facilityName']);
        $bookingDate = date('d M Y', strtotime($bookingData['select_date']));
        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = "
            <html>
            <head>
                <style>
                    * { margin: 0; padding: 0; box-sizing: border-box; }
                    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; background-color: #f5f5f5; }
                    .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
                    .header { background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%); color: white; padding: 40px 20px; text-align: center; }
                    .header h1 { font-size: 28px; margin-bottom: 10px; }
                    .header p { font-size: 14px; opacity: 0.9; }
                    .status-banner {
                        background-color: $statusBg;
                        color: $statusColor;
                        border-left: 4px solid $statusColor;
                        padding: 15px 20px;
                        margin: 20px 0;
                        border-radius: 4px;
                        text-align: center;
                        font-weight: bold;
                        font-size: 18px;
                    }
                    .content { padding: 30px 20px; }
                    .greeting { font-size: 16px; margin-bottom: 20px; }
                    .booking-details {
                        background-color: #f8f9fa;
                        border-left: 4px solid #2c5282;
                        padding: 20px;
                        margin: 20px 0;
                        border-radius: 4px;
                    }
                    .detail-row { margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0; }
                    .detail-row:last-child { border-bottom: none; }
                    .detail-label { font-weight: bold; color: #2c5282; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
                    .detail-value { color: #333; font-size: 16px; margin-top: 5px; }
                    .rejection-box {
                        background-color: #fff3cd;
                        border-left: 4px solid #ffc107;
                        padding: 20px;
                        margin: 20px 0;
                        border-radius: 4px;
                    }
                    .rejection-box h4 {
                        color: #856404;
                        margin-bottom: 10px;
                        font-size: 16px;
                    }
                    .rejection-box p {
                        color: #856404;
                        font-size: 14px;
                        line-height: 1.6;
                    }
                    .action-section { margin-top: 30px; text-align: center; }
                    .action-link {
                        display: inline-block;
                        background: linear-gradient(135deg, #2c5282 0%, #1a365d 100%);
                        color: white;
                        padding: 12px 30px;
                        text-decoration: none;
                        border-radius: 4px;
                        font-weight: bold;
                        margin: 10px 5px;
                    }
                    .footer { background-color: #f8f9fa; border-top: 1px solid #e2e8f0; padding: 20px; text-align: center; font-size: 12px; color: #666; }
                    .footer p { margin: 5px 0; }
                    .divider { border-top: 1px solid #e2e8f0; margin: 20px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>FacilityOps</h1>
                        <p>Facility Booking System</p>
                    </div>
                    
                    <div class='content'>
                        <div class='status-banner'>
                            <span style='font-size: 24px; margin-right: 10px;'>$icon</span>
                            $message
                        </div>
                        
                        <div class='greeting'>
                            <p>Dear $userName,</p>
                            <p>We are writing to inform you about the status of your facility booking.</p>
                        </div>
                        
                        <div class='booking-details'>
                            <div class='detail-row'>
                                <div class='detail-label'>Booking ID</div>
                                <div class='detail-value'>{$bookingData['ulpl_id']}</div>
                            </div>
                            
                            <div class='detail-row'>
                                <div class='detail-label'>Facility</div>
                                <div class='detail-value'>$facilityDisplay</div>
                            </div>
                            
                            <div class='detail-row'>
                                <div class='detail-label'>Programme</div>
                                <div class='detail-value'>{$bookingData['programe_name']}</div>
                            </div>
                            
                            <div class='detail-row'>
                                <div class='detail-label'>Department</div>
                                <div class='detail-value'>{$bookingData['depart']}</div>
                            </div>
                            
                            <div class='detail-row'>
                                <div class='detail-label'>Booking Date</div>
                                <div class='detail-value'>$bookingDate</div>
                            </div>
                            
                            <div class='detail-row'>
                                <div class='detail-label'>Status</div>
                                <div class='detail-value' style='color: $statusColor; font-weight: bold;'>$status</div>
                            </div>
                        </div>
                        ";
        
        if ($action === 'approve') {
            $mail->Body .= "
                        <div style='margin-top: 20px; padding: 20px; background-color: #d4edda; border-radius: 4px;'>
                            <p><strong>Next Steps:</strong></p>
                            <p>Your booking has been approved! Please note the following:</p>
                            <ul style='margin-left: 20px; margin-top: 10px;'>
                                <li>Ensure you arrive 15 minutes before your scheduled time</li>
                                <li>Bring valid identification</li>
                                <li>Contact the facility administrator if you need to reschedule</li>
                                <li>Payment (if applicable) should be made on-site</li>
                            </ul>
                        </div>
                        ";
        } else {
            // ✅ ADD REJECTION NOTES TO EMAIL
            $rejectionNotes = isset($bookingData['rejection_notes']) ? htmlspecialchars($bookingData['rejection_notes']) : 'No reason provided.';
            
            $mail->Body .= "
                        <div class='rejection-box'>
                            <h4>📋 Reason for Rejection:</h4>
                            <p>$rejectionNotes</p>
                        </div>
                        
                        <div style='margin-top: 20px; padding: 20px; background-color: #f8d7da; border-radius: 4px;'>
                            <p><strong>What Happens Next:</strong></p>
                            <p>Your booking has been rejected. If you believe this is an error or would like more information, please:</p>
                            <ul style='margin-left: 20px; margin-top: 10px;'>
                                <li>Contact the facility administrator directly</li>
                                <li>Reply to this email with your inquiry</li>
                                <li>Feel free to submit a new booking request</li>
                            </ul>
                        </div>
                        ";
        }
        
        $mail->Body .= "
                        <div class='action-section'>
                            <a href='http://facilityops.org/staff/profile.php' class='action-link'>View My Bookings</a>
                        </div>
                        
                        <div class='divider'></div>
                        
                        <div style='margin-top: 20px; color: #666; font-size: 14px;'>
                            <p>If you have any questions or need assistance, please don't hesitate to reach out to our support team.</p>
                        </div>
                    </div>
                    
                    <div class='footer'>
                        <p><strong>FacilityOps Support</strong></p>
                        <p>Km 08, Jalan Paka, 23000 Kuala Dungun, Terengganu</p>
                        <p>Tel: 09-8400800 | Email: support@facilityops.com</p>
                        <p style='margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 15px;'>&copy; 2025 FacilityOps. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        $mail->AltBody = "Booking Status: $status\n\nDear $userName,\n\nYour booking has been $status.\n\nBooking Details:\nBooking ID: {$bookingData['ulpl_id']}\nFacility: $facilityDisplay\nDate: $bookingDate\n\n" . ($action === 'reject' ? "Reason: $rejectionNotes\n\n" : "") . "For more information, visit: http://facilityops.org/staff/profile.php";
        
        return $mail->send();
        
    } catch (Exception $e) {
        error_log("Email Error for $userEmail: " . $e->getMessage());
        return false;
    }
}

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: adminpage.php");
    exit();
}

$processedCount = 0;
$emailSentCount = 0;
$emailFailedCount = 0;
$errors = [];

// Loop through POST data to find actions
foreach ($_POST as $key => $value) {
    // Check if key matches pattern: action_{booking_id}
    if (strpos($key, 'action_') === 0) {
        // Extract booking ID
        $ulpl_id = str_replace('action_', '', $key);
        $ulpl_id = mysqli_real_escape_string($connect, $ulpl_id);
        
        // Get action (approve/reject)
        $action = mysqli_real_escape_string($connect, $value);
        
        // Determine status
        if ($action === 'approve') {
            $status = 'Approved';
            $rejectionNotes = null; // No notes for approval
        } elseif ($action === 'reject') {
            $status = 'Rejected';
            
            // ✅ GET REJECTION NOTES
            $notesKey = 'rejection_notes_' . $ulpl_id;
            $rejectionNotes = isset($_POST[$notesKey]) ? trim($_POST[$notesKey]) : null;
            
            // ✅ VALIDATE: Rejection must have notes
            if (empty($rejectionNotes)) {
                $errors[] = "Rejection notes required for booking ID: $ulpl_id";
                error_log("⚠️ Rejection without notes for booking ID: $ulpl_id");
                continue; // Skip this booking
            }
            
            $rejectionNotes = mysqli_real_escape_string($connect, $rejectionNotes);
        } else {
            continue; // Skip invalid actions
        }
        
        // Fetch user email and booking details
        $fetchQuery = "
            SELECT b.ulpl_id, u.email, u.name, b.facilityName, b.programe_name, u.depart, b.select_date
            FROM ulpl b
            JOIN users u ON b.users_id = u.users_id
            WHERE b.ulpl_id = '$ulpl_id'
        ";
        
        $fetchResult = mysqli_query($connect, $fetchQuery);
        
        if ($fetchResult && mysqli_num_rows($fetchResult) > 0) {
            $bookingData = mysqli_fetch_assoc($fetchResult);
            
            // ✅ UPDATE DATABASE WITH REJECTION NOTES
            if ($action === 'reject') {
                $query = "UPDATE ulpl 
                         SET status = '$status', 
                             rejection_notes = '$rejectionNotes' 
                         WHERE ulpl_id = '$ulpl_id'";
            } else {
                $query = "UPDATE ulpl 
                         SET status = '$status', 
                             rejection_notes = NULL 
                         WHERE ulpl_id = '$ulpl_id'";
            }
            
            if (mysqli_query($connect, $query)) {
                $processedCount++;
                error_log("✅ Successfully updated booking ID: $ulpl_id to status: $status");
                
                // ✅ ADD REJECTION NOTES TO EMAIL DATA
                if ($action === 'reject') {
                    $bookingData['rejection_notes'] = $rejectionNotes;
                }
                
                // Send email notification
                $emailSent = sendBookingNotificationEmail(
                    $bookingData['email'],
                    $bookingData['name'],
                    $bookingData,
                    $action
                );
                
                if ($emailSent) {
                    $emailSentCount++;
                    error_log("✅ Email sent successfully to {$bookingData['email']} for booking ID: $ulpl_id");
                } else {
                    $emailFailedCount++;
                    error_log("⚠️ Email failed to send to {$bookingData['email']} for booking ID: $ulpl_id");
                }
                
            } else {
                $errors[] = "Failed to update booking ID: $ulpl_id - " . mysqli_error($connect);
                error_log("❌ Error updating booking ID $ulpl_id: " . mysqli_error($connect));
            }
        } else {
            $errors[] = "Booking ID not found: $ulpl_id";
            error_log("⚠️ Booking not found for ID: $ulpl_id");
        }
    }
}

// Redirect with message
if ($processedCount > 0) {
    $message = "Successfully processed $processedCount booking(s).";
    if ($emailSentCount > 0) {
        $message .= " Emails sent to $emailSentCount user(s).";
    }
    if ($emailFailedCount > 0) {
        $message .= " Warning: $emailFailedCount email(s) failed to send.";
    }
    if (!empty($errors)) {
        $message .= " " . count($errors) . " error(s) occurred.";
    }
    header("Location: adminpage.php?success=" . urlencode($message));
} else {
    $errorMsg = "No bookings were processed.";
    if (!empty($errors)) {
        $errorMsg .= " Errors: " . implode(", ", $errors);
    }
    header("Location: adminpage.php?error=" . urlencode($errorMsg));
}

exit();
?>