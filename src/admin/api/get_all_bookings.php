<?php
// admin/api/get_all_bookings.php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("../../config.php");

// ✅ Auto-detect connection variable
if (!isset($conn) && isset($connect)) {
    $conn = $connect;
}

function getFacilityColor($facilityName) {
    $predefinedColors = [
        'Dewan Kuliah Utama' => '#ef4444',
        'Bilik Makan Bauk Inn' => '#8b5cf6',
        'Bilik Seminar' => '#10b981',
        'Bilik Kuliah 2' => '#f59e0b',
        'Puspanita' => '#ec4899'
    ];
    
    // ✅ Case-insensitive + trim comparison
    foreach ($predefinedColors as $name => $color) {
        if (strcasecmp(trim($name), $facilityName) === 0) {
            return $color;
        }
    }

    // Generate random color if not found
    $hash = md5($facilityName);
    $r = hexdec(substr($hash, 0, 2));
    $g = hexdec(substr($hash, 2, 2));
    $b = hexdec(substr($hash, 4, 2));
    
    $brightness = ($r + $g + $b) / 3;
    if ($brightness < 100) {
        $r = min(255, $r + 80);
        $g = min(255, $g + 80);
        $b = min(255, $b + 80);
    } elseif ($brightness > 200) {
        $r = max(0, $r - 80);
        $g = max(0, $g - 80);
        $b = max(0, $b - 80);
    }
    
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

try {
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // ✅ FIXED: Use consistent alias 'bookedBy' instead of 'full_name'
    $query = "
        SELECT 
            u.facilityName,
            u.programe_name,
            u.select_date,
            u.start_time,
            u.end_time,
            u.status,
            usr.name as bookedBy
        FROM ulpl u
        INNER JOIN users usr ON u.users_id = usr.users_id
        WHERE u.status = 'Approved'
        ORDER BY u.select_date ASC
    ";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    $bookings = [];
    
    while ($row = $result->fetch_assoc()) {
        $facilityName = $row['facilityName'];
        $color = getFacilityColor($facilityName);
        $startDate = $row['select_date'];

        $endDateForCalendar = date('Y-m-d', strtotime($startDate . ' +1 day'));
        
        $bookings[] = [
            'title' => $facilityName . ' - ' . $row['programe_name'],
            'start' => $startDate,
            'end' => $endDateForCalendar,
            'color' => $color,
            'textColor' => '#ffffff',
            'extendedProps' => [
                'status' => $row['status'],
                'facilityName' => $facilityName,
                'programName' => $row['programe_name'],
                'startDate' => $startDate,
                'startTime' => $row['start_time'], 
                'endTime' => $row['end_time'],
                'bookedBy' => $row['bookedBy']  
            ]
        ];
    }
        
    echo json_encode([
        'success' => true,
        'bookings' => $bookings,
        'total' => count($bookings)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'bookings' => []
    ]);
}
?>