<?php
include("../config.php");

// Check if user is admin (you can add your own admin check here)
// session_start();
// if (!isset($_SESSION['admin'])) {
//     die(json_encode(['success' => false, 'message' => 'Unauthorized']));
// }

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

switch($action) {
    case 'add':
        addFAQ();
        break;
    case 'edit':
        editFAQ();
        break;
    case 'delete':
        deleteFAQ();
        break;
    case 'toggle':
        toggleFAQ();
        break;
    case 'reorder':
        reorderFAQ();
        break;
    case 'update_phone':
        updatePhoneNumber();
        break;
    case 'update_documentation':
        updateDocumentation();
        break;
    case 'update_email':
        updateSupportEmail();
        break;
    case 'update_smtp':
        updateSMTPSettings();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

// ADD FAQ
function addFAQ() {
    global $connect;
    
    $question = mysqli_real_escape_string($connect, $_POST['question']);
    $answer = mysqli_real_escape_string($connect, $_POST['answer']);
    
    // Get the highest display_order and add 1
    $order_query = "SELECT MAX(display_order) as max_order FROM faqs";
    $order_result = mysqli_query($connect, $order_query);
    $max_order = mysqli_fetch_assoc($order_result)['max_order'] ?? 0;
    $new_order = $max_order + 1;
    
    $query = "INSERT INTO faqs (question, answer, display_order) VALUES ('$question', '$answer', $new_order)";
    
    if (mysqli_query($connect, $query)) {
        echo json_encode(['success' => true, 'message' => 'FAQ added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($connect)]);
    }
}

// EDIT FAQ
function editFAQ() {
    global $connect;
    
    $id = intval($_POST['id']);
    $question = mysqli_real_escape_string($connect, $_POST['question']);
    $answer = mysqli_real_escape_string($connect, $_POST['answer']);
    
    $query = "UPDATE faqs SET question = '$question', answer = '$answer' WHERE id = $id";
    
    if (mysqli_query($connect, $query)) {
        echo json_encode(['success' => true, 'message' => 'FAQ updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($connect)]);
    }
}

// DELETE FAQ
function deleteFAQ() {
    global $connect;
    
    $id = intval($_POST['id']);
    
    $query = "DELETE FROM faqs WHERE id = $id";
    
    if (mysqli_query($connect, $query)) {
        echo json_encode(['success' => true, 'message' => 'FAQ deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($connect)]);
    }
}

// TOGGLE ACTIVE/INACTIVE
function toggleFAQ() {
    global $connect;
    
    $id = intval($_POST['id']);
    
    $query = "UPDATE faqs SET is_active = NOT is_active WHERE id = $id";
    
    if (mysqli_query($connect, $query)) {
        echo json_encode(['success' => true, 'message' => 'FAQ status updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($connect)]);
    }
}

// REORDER FAQ (Move Up/Down)
function reorderFAQ() {
    global $connect;
    
    $id = intval($_POST['id']);
    $direction = $_POST['direction']; // 'up' or 'down'
    
    // Get current order
    $current_query = "SELECT display_order FROM faqs WHERE id = $id";
    $current_result = mysqli_query($connect, $current_query);
    $current_order = mysqli_fetch_assoc($current_result)['display_order'];
    
    if ($direction == 'up') {
        // Swap with previous item
        $swap_query = "SELECT id, display_order FROM faqs WHERE display_order < $current_order ORDER BY display_order DESC LIMIT 1";
    } else {
        // Swap with next item
        $swap_query = "SELECT id, display_order FROM faqs WHERE display_order > $current_order ORDER BY display_order ASC LIMIT 1";
    }
    
    $swap_result = mysqli_query($connect, $swap_query);
    
    if (mysqli_num_rows($swap_result) > 0) {
        $swap_item = mysqli_fetch_assoc($swap_result);
        $swap_id = $swap_item['id'];
        $swap_order = $swap_item['display_order'];
        
        // Perform the swap
        mysqli_query($connect, "UPDATE faqs SET display_order = $swap_order WHERE id = $id");
        mysqli_query($connect, "UPDATE faqs SET display_order = $current_order WHERE id = $swap_id");
        
        echo json_encode(['success' => true, 'message' => 'Order updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cannot move further']);
    }
}

// UPDATE PHONE NUMBER
function updatePhoneNumber() {
    global $connect;
    
    $phone = mysqli_real_escape_string($connect, trim($_POST['phone']));
    
    // Validate phone number format (basic validation)
    if (empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'Phone number cannot be empty']);
        return;
    }
    
    // Check if setting exists
    $check_query = "SELECT setting_id FROM site_settings WHERE setting_key = 'support_phone'";
    $check_result = mysqli_query($connect, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Update existing
        $query = "UPDATE site_settings SET setting_value = '$phone' WHERE setting_key = 'support_phone'";
    } else {
        // Insert new
        $query = "INSERT INTO site_settings (setting_key, setting_value, setting_label, setting_description) 
                  VALUES ('support_phone', '$phone', 'Support Phone Number', 'Phone number displayed on FAQ and contact sections')";
    }
    
    if (mysqli_query($connect, $query)) {
        echo json_encode(['success' => true, 'message' => 'Phone number updated successfully', 'phone' => $phone]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($connect)]);
    }
}

// UPDATE DOCUMENTATION
function updateDocumentation() {
    global $connect;
    
    // Check if file was uploaded
    if (!isset($_FILES['documentation']) || $_FILES['documentation']['error'] === UPLOAD_ERR_NO_FILE) {
        echo json_encode(['success' => false, 'message' => 'No file selected']);
        return;
    }
    
    $file = $_FILES['documentation'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload error: ' . $file['error']]);
        return;
    }
    
    // Validate file size (10MB max)
    $maxSize = 10 * 1024 * 1024; // 10MB in bytes
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'File size exceeds 10MB limit']);
        return;
    }
    
    // Validate file type
    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF, DOC, and DOCX are allowed']);
        return;
    }
    
    // Get file extension
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Create unique filename
    $newFilename = 'user_manual_' . date('Y-m-d_His') . '.' . $fileExtension;
    
    // Define upload directory
    $uploadDir = '../assets/documents/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploadPath = $uploadDir . $newFilename;
    
    // Get old file path to delete it later
    $old_file_query = "SELECT setting_value FROM site_settings WHERE setting_key = 'user_documentation'";
    $old_file_result = mysqli_query($connect, $old_file_query);
    $old_file_path = null;
    if ($old_file_result && mysqli_num_rows($old_file_result) > 0) {
        $old_file_row = mysqli_fetch_assoc($old_file_result);
        $old_file_path = $old_file_row['setting_value'];
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Delete old file if it exists and is not the default
        if ($old_file_path && file_exists($old_file_path) && $old_file_path !== '../assets/documents/FACILTYOPS USER MANUAL.pdf') {
            unlink($old_file_path);
        }
        
        // Update database
        $check_query = "SELECT setting_id FROM site_settings WHERE setting_key = 'user_documentation'";
        $check_result = mysqli_query($connect, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Update existing
            $query = "UPDATE site_settings SET setting_value = '$uploadPath' WHERE setting_key = 'user_documentation'";
        } else {
            // Insert new
            $query = "INSERT INTO site_settings (setting_key, setting_value, setting_label, setting_description) 
                      VALUES ('user_documentation', '$uploadPath', 'User Documentation', 'User manual or documentation file')";
        }
        
        if (mysqli_query($connect, $query)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Documentation uploaded successfully',
                'filename' => $newFilename,
                'filepath' => $uploadPath
            ]);
        } else {
            // If database update fails, delete the uploaded file
            unlink($uploadPath);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($connect)]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
    }
}

// UPDATE SUPPORT EMAIL
function updateSupportEmail() {
    global $connect;
    
    $email = mysqli_real_escape_string($connect, trim($_POST['email']));
    
    // Validate email format
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email address cannot be empty']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        return;
    }
    
    // Check if setting exists
    $check_query = "SELECT setting_id FROM site_settings WHERE setting_key = 'support_email'";
    $check_result = mysqli_query($connect, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Update existing
        $query = "UPDATE site_settings SET setting_value = '$email' WHERE setting_key = 'support_email'";
    } else {
        // Insert new
        $query = "INSERT INTO site_settings (setting_key, setting_value, setting_label, setting_description) 
                  VALUES ('support_email', '$email', 'Support Email Address', 'Email address that receives support requests from contact form')";
    }
    
    if (mysqli_query($connect, $query)) {
        echo json_encode(['success' => true, 'message' => 'Support email updated successfully', 'email' => $email]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($connect)]);
    }
}

// UPDATE SMTP SETTINGS
function updateSMTPSettings() {
    global $connect;
    
    $smtp_username = mysqli_real_escape_string($connect, trim($_POST['smtp_username']));
    $smtp_password = mysqli_real_escape_string($connect, trim($_POST['smtp_password']));
    
    // Validate
    if (empty($smtp_username) || empty($smtp_password)) {
        echo json_encode(['success' => false, 'message' => 'SMTP username and password cannot be empty']);
        return;
    }
    
    if (!filter_var($smtp_username, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid Gmail address']);
        return;
    }
    
    // Update or insert SMTP username
    $check_user_query = "SELECT setting_id FROM site_settings WHERE setting_key = 'smtp_username'";
    $check_user_result = mysqli_query($connect, $check_user_query);
    
    if (mysqli_num_rows($check_user_result) > 0) {
        $query1 = "UPDATE site_settings SET setting_value = '$smtp_username' WHERE setting_key = 'smtp_username'";
    } else {
        $query1 = "INSERT INTO site_settings (setting_key, setting_value, setting_label, setting_description) 
                   VALUES ('smtp_username', '$smtp_username', 'SMTP Username', 'Gmail account used for sending emails')";
    }
    
    // Update or insert SMTP password
    $check_pass_query = "SELECT setting_id FROM site_settings WHERE setting_key = 'smtp_password'";
    $check_pass_result = mysqli_query($connect, $check_pass_query);
    
    if (mysqli_num_rows($check_pass_result) > 0) {
        $query2 = "UPDATE site_settings SET setting_value = '$smtp_password' WHERE setting_key = 'smtp_password'";
    } else {
        $query2 = "INSERT INTO site_settings (setting_key, setting_value, setting_label, setting_description) 
                   VALUES ('smtp_password', '$smtp_password', 'SMTP Password', 'Gmail app password for SMTP')";
    }
    
    if (mysqli_query($connect, $query1) && mysqli_query($connect, $query2)) {
        echo json_encode(['success' => true, 'message' => 'SMTP settings updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($connect)]);
    }
}
?>