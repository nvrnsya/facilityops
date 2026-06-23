<?php
// admin/api/get_facility_colors.php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../../config.php");

function getFacilityColor($facilityName) {
    $predefinedColors = [
        'Dewan Kuliah Utama' => '#ef4444',
        'Bilik Makan Bauk Inn' => '#8b5cf6',
        'Bilik Seminar' => '#10b981',
        'Bilik Kuliah 2' => '#f59e0b',
        'Puspanita' => '#ec4899'
    ];
    
    foreach ($predefinedColors as $name => $color) {
        if (strcasecmp($name, $facilityName) === 0) {
            return $color;
        }
    }
    
    if (isset($predefinedColors[$facilityName])) {
        return $predefinedColors[$facilityName];
    }
    
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
    $query = "
        SELECT DISTINCT facilityName 
        FROM ulpl 
        WHERE status = 'Approved'
        ORDER BY facilityName ASC
    ";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Query failed: " . $connect->error);
    }
    
    $facilities = [];
    
    while ($row = $result->fetch_assoc()) {
        $facilities[] = [
            'name' => $row['facilityName'],
            'color' => getFacilityColor($row['facilityName'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'facilities' => $facilities
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'facilities' => []
    ]);
}
?>