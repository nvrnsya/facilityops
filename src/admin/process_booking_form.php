<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config.php");

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get facility information
    $facility_name = $_POST['facility_name'] ?? '';
    $facility_id = intval($_POST['facility_id'] ?? 0);
    
    if (empty($facility_name) || $facility_id <= 0) {
        throw new Exception('Invalid facility information');
    }
    
    // Fetch all active form fields to know what to expect
    $fieldsQuery = "SELECT * FROM booking_form_fields WHERE is_active = 1";
    $fieldsResult = $conn->query($fieldsQuery);
    
    if (!$fieldsResult) {
        throw new Exception('Failed to fetch form fields');
    }
    
    $formFields = [];
    while ($row = $fieldsResult->fetch_assoc()) {
        $formFields[$row['field_name']] = $row;
    }
    
    // Prepare booking data
    $bookingData = [];
    $bookingData['facility_name'] = $facility_name;
    $bookingData['facility_id'] = $facility_id;
    $bookingData['user_id'] = $user_id;
    $bookingData['created_at'] = date('Y-m-d H:i:s');
    $bookingData['status'] = 'pending'; // Default status
    
    // Collect all submitted field values
    $additionalData = [];
    foreach ($formFields as $fieldName => $fieldInfo) {
        if (isset($_POST[$fieldName])) {
            $value = $_POST[$fieldName];
            
            // Handle arrays (checkboxes)
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            
            $additionalData[$fieldName] = trim($value);
        }
    }
    
    // Store additional data as JSON
    $bookingData['form_data'] = json_encode($additionalData);
    
    // Insert into bookings table
    // Note: You may need to adjust table structure or create a new table
    // This assumes you have a flexible booking table
    $stmt = $conn->prepare("
        INSERT INTO bookings 
        (user_id, facility_id, facility_name, form_data, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param(
        "iissss",
        $bookingData['user_id'],
        $bookingData['facility_id'],
        $bookingData['facility_name'],
        $bookingData['form_data'],
        $bookingData['status'],
        $bookingData['created_at']
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to insert booking: ' . $stmt->error);
    }
    
    $booking_id = $stmt->insert_id;
    $stmt->close();
    
    // Send email notification (optional)
    // sendBookingNotification($booking_id, $user_id, $additionalData);
    
    echo json_encode([
        'success' => true,
        'message' => 'Booking submitted successfully',
        'booking_id' => $booking_id
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>