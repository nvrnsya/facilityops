<?php
// admin/api/get_calendar_dates.php - CLEAN VERSION
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    include(__DIR__ . "/../../config.php");
    
    if (!isset($connect) || $connect->connect_error) {
        throw new Exception("Database connection failed");
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Configuration error: ' . $e->getMessage()
    ]);
    exit;
}

// Helper function to check if date is fully booked
function isDateFullyBooked($timeSlots) {
    if (empty($timeSlots)) return false;
    
    $operatingStart = strtotime('08:00:00');
    $operatingEnd = strtotime('22:00:00');
    
    usort($timeSlots, function($a, $b) {
        return strcmp($a['start'], $b['start']);
    });
    
    $currentTime = $operatingStart;
    
    foreach ($timeSlots as $slot) {
        $slotStart = strtotime($slot['start']);
        $slotEnd = strtotime($slot['end']);
        
        if ($slotStart > $currentTime) {
            return false;
        }
        
        $currentTime = max($currentTime, $slotEnd);
    }
    
    return $currentTime >= $operatingEnd;
}

try {
    $requestAll = isset($_GET['all']) && $_GET['all'] === 'true';
    $facility = $_GET['facility'] ?? '';
    
    // Create calendar_dates table if not exists
    $createTableSQL = "CREATE TABLE IF NOT EXISTS calendar_dates (
        id INT PRIMARY KEY AUTO_INCREMENT,
        date DATE NOT NULL,
        status ENUM('available', 'holiday', 'unavailable') DEFAULT 'available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_date (date)
    )";
    $connect->query($createTableSQL);
    
    // Initialize date arrays
    $dates = [
        'available' => [],
        'holiday' => [],
        'unavailable' => []
    ];
    
    // Get dates from calendar_dates table
    $stmt = $connect->prepare("SELECT date, status FROM calendar_dates ORDER BY date");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $connect->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $dates[$row['status']][] = $row['date'];
    }
    $stmt->close();
    
    // ===== HANDLE ALL FACILITIES REQUEST =====
    if ($requestAll) {
        $ulplBookings = $connect->query("SELECT select_date, start_time, end_time FROM ulpl WHERE status IN ('Pending', 'Approved')");
        
        if ($ulplBookings) {
            $allDateTimeBookings = [];
            
            while ($row = $ulplBookings->fetch_assoc()) {
                if ($row['select_date']) {
                    $eventDate = $row['select_date'];
                    
                    if (!isset($allDateTimeBookings[$eventDate])) {
                        $allDateTimeBookings[$eventDate] = [];
                    }
                    
                    if ($row['start_time'] && $row['end_time']) {
                        $allDateTimeBookings[$eventDate][] = [
                            'start' => substr($row['start_time'], 0, 5),
                            'end' => substr($row['end_time'], 0, 5)      
                        ];
                    }
                }
            }
            
            foreach ($allDateTimeBookings as $date => $timeSlots) {
                if (isDateFullyBooked($timeSlots)) {
                    $key = array_search($date, $dates['available']);
                    if ($key !== false) {
                        unset($dates['available'][$key]);
                    }
                    if (!in_array($date, $dates['unavailable']) && !in_array($date, $dates['holiday'])) {
                        $dates['unavailable'][] = $date;
                    }
                }
            }
        }
        
        $dates['available'] = array_values($dates['available']);
        $dates['unavailable'] = array_values($dates['unavailable']);
        $dates['holiday'] = array_values($dates['holiday']);
        
        echo json_encode([
            'success' => true,
            'dates' => $dates,
            'mode' => 'all_facilities'
        ]);
        exit;
    }
    
    // ===== HANDLE SPECIFIC FACILITY REQUEST =====
    $dateTimeBookings = [];
    $debugInfo = [
        'facility_param' => $facility,
        'ulpl_bookings' => []
    ];
    
    if (!empty($facility)) {
        $facilityMap = [
            'dewan-kuliah-utama' => 'Dewan Kuliah Utama',
            'bilik-makan-bauk-inn' => 'Bilik Makan Bauk Inn',
            'bilik-seminar' => 'Bilik Seminar',
            'bilik-kuliah-2' => 'Bilik Kuliah 2',
            'puspanita' => 'Puspanita'
        ];
        
        $facilityNameMapped = $facilityMap[$facility] ?? ucwords(str_replace('-', ' ', $facility));
        
        $ulplQuery = "SELECT ulpl_id, select_date, start_time, end_time, status, facilityName
                      FROM ulpl 
                      WHERE facilityName = ? 
                      AND status IN ('Pending', 'Approved')
                      ORDER BY select_date, start_time";
        
        $stmt = $connect->prepare($ulplQuery);
        if (!$stmt) {
            throw new Exception("Prepare ULPL query failed: " . $connect->error);
        }
        
        $stmt->bind_param("s", $facilityNameMapped);
        $stmt->execute();
        $ulplResult = $stmt->get_result();
        
        while ($row = $ulplResult->fetch_assoc()) {
            $debugInfo['ulpl_bookings'][] = [
                'id' => $row['ulpl_id'],
                'date' => $row['select_date'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time']
            ];
            
            $eventDate = $row['select_date'];
            
            if (!isset($dateTimeBookings[$eventDate])) {
                $dateTimeBookings[$eventDate] = [];
            }
            
            if ($row['start_time'] && $row['end_time']) {
                $dateTimeBookings[$eventDate][] = [
                    'start' => substr($row['start_time'], 0, 5),  
                    'end' => substr($row['end_time'], 0, 5)      
                ];
            }
        }
        $stmt->close();
        
        foreach ($dateTimeBookings as $date => $timeSlots) {
            if (isDateFullyBooked($timeSlots)) {
                $key = array_search($date, $dates['available']);
                if ($key !== false) {
                    unset($dates['available'][$key]);
                }
                if (!in_array($date, $dates['unavailable']) && !in_array($date, $dates['holiday'])) {
                    $dates['unavailable'][] = $date;
                }
            }
        }
        
        $dates['available'] = array_values($dates['available']);
    }

    echo json_encode([
        'success' => true,
        'dates' => $dates,
        'facility' => $facility,
        'time_bookings' => $dateTimeBookings,
        'debug' => $debugInfo
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>