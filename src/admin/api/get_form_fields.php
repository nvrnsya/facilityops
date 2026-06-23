<?php
// admin/api/get_form_fields.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    include("../../config.php");
    
    // Check database connection
    if (!isset($connect) || $connect->connect_error) {
        throw new Exception("Database connection failed");
    }
    
    $facility = $_GET['facility'] ?? '';
    $facilitySource = $_GET['source'] ?? 'ulpl';
    
    // Return all active form fields, ordered by section and field_order
    $query = "SELECT * FROM booking_form_fields 
              WHERE is_active = 1 
              ORDER BY 
                CASE field_section
                  WHEN 'booking_details' THEN 1
                  WHEN 'key_handover' THEN 2
                  WHEN 'additional_info' THEN 3
                  ELSE 4
                END,
                field_order ASC, 
                field_id ASC";
    
    $result = mysqli_query($connect, $query);
    
    if (!$result) {
        throw new Exception("Query failed: " . mysqli_error($connect));
    }
    
    $fields = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $fields[] = $row;
    }
    
    // Group fields by section
    $groupedFields = [
        'booking_details' => [],
        'key_handover' => [],
        'additional_info' => []
    ];
    
    foreach ($fields as $field) {
        $section = $field['field_section'] ?? 'booking_details';
        if (!isset($groupedFields[$section])) {
            $groupedFields[$section] = [];
        }
        $groupedFields[$section][] = $field;
    }
    
    echo json_encode([
        'success' => true,
        'fields' => $fields,
        'grouped' => $groupedFields,
        'facility' => $facility,
        'source' => $facilitySource
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>