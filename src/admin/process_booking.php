<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../config.php");
$connect->set_charset("utf8mb4");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$users_id = $_SESSION['user_id'];

// ✅ DEBUG: Check what POST data received
file_put_contents(__DIR__ . '/booking_debug.txt', "=== BOOKING DEBUG ===\n" . print_r($_POST, true), FILE_APPEND);

// === Basic validation ===
$facilitySlug = trim($_POST['facilityName'] ?? '');
$bookingDates = json_decode($_POST['booking_dates'] ?? '[]', true);

if (empty($bookingDates)) {
    die("❌ Error: No dates selected");
}

// === Map facility ===
$facilityMap = [
    'dewan-kuliah-utama' => 'Dewan Kuliah Utama',
    'bilik-makan-bauk-inn' => 'Bilik Makan Bauk Inn',
    'bilik-seminar' => 'Bilik Seminar',
    'bilik-kuliah-2' => 'Bilik Kuliah 2',
    'puspanita' => 'Puspanita'
];

$facilityName = $facilityMap[$facilitySlug] ?? ucwords(str_replace('-', ' ', $facilitySlug));

// === Convert date ===
function convertToMySQLDate($dateStr) {
    if (empty($dateStr)) return null;
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) return $dateStr;
    
    $parts = explode('-', $dateStr);
    if (count($parts) === 3) {
        return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }
    return null;
}

// === Get all core values ===
$programeName = $_POST['programe_name'] ?? '';
$name = $_POST['name'] ?? '';
$staffid = $_POST['staffid'] ?? '';
$phoneNum = $_POST['phone_num'] ?? '';
$departValue = $_POST['department_unit'] ?? $_POST['depart'] ?? '';
$extOffice = $_POST['ext_office'] ?? '';
$addNotes = $_POST['add_notes'] ?? '';
$recipientName = $_POST['recipient_name'] ?? '';
$departKey     = $_POST['depart_key'] ?? $departValue;
$telNum        = $_POST['tel_num'] ?? '';
$staffKey      = $_POST['staff_key'] ?? '';

// === Start transaction ===
$connect->begin_transaction();

try {
    $ulpl_ids = []; // simpan semua ID yang berjaya insert
    
    foreach ($bookingDates as $entry) {
        
        // Ambil date & time dari setiap entry
        $selectDateDB = convertToMySQLDate($entry['date']);
        $startTime = $entry['startTime'];
        $endTime = $entry['endTime'];
        
        // === Check conflict (sama macam sekarang, cuma guna variable baru) ===
        $checkQuery = "SELECT COUNT(*) as count 
                       FROM ulpl 
                       WHERE facilityName = ? 
                       AND select_date = ?
                       AND (
                           (start_time < ? AND end_time > ?) OR
                           (start_time >= ? AND start_time < ?)
                       )
                       AND status IN ('Pending', 'Approved')";
        $stmtCheck = $connect->prepare($checkQuery);
        $stmtCheck->bind_param("ssssss", $facilityName, $selectDateDB, $endTime, $startTime, $startTime, $endTime);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();
        $rowCheck = $resultCheck->fetch_assoc();
        $stmtCheck->close();
        
        if ($rowCheck['count'] > 0) {
            // Bagitahu date mana yang clash
            throw new Exception("Time slot conflict on date: {$entry['date']}");
        }
        
        // === INSERT ULPL (sama macam sekarang) ===
        $queryUlpl = "INSERT INTO ulpl (
            users_id, facilityName, programe_name, name, staffid, phone_num, 
            depart, ext_office, add_notes, select_date, start_time, end_time, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
        
        $stmtUlpl = $connect->prepare($queryUlpl);
        $stmtUlpl->bind_param(
            "isssssssssss",
            $users_id, $facilityName, $programeName, $name,
            $staffid, $phoneNum, $departValue, $extOffice,
            $addNotes, $selectDateDB, $startTime, $endTime
        );
        $stmtUlpl->execute();
        $ulpl_id = $stmtUlpl->insert_id;
        $stmtUlpl->close();
        
        // === INSERT KEYS (sama macam sekarang) ===
        $collectDateDB = convertToMySQLDate($entry['date']); // guna date sama
        $deliveryDateDB = convertToMySQLDate($entry['date']);
        
        $queryKeys = "INSERT INTO `keys` (
            ulpl_id, users_id, recipient_name, depart_key, tel_num, staff_key, key_collect, key_delivery
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmtKeys = $connect->prepare($queryKeys);
        $stmtKeys->bind_param(
            "iissssss",
            $ulpl_id, $users_id, $recipientName,
            $departKey, $telNum, $staffKey,
            $collectDateDB, $deliveryDateDB
        );
        $stmtKeys->execute();
        $stmtKeys->close();
        
        $ulpl_ids[] = $ulpl_id; // simpan ID
        
    } // akhir foreach
    
    $connect->commit();
    
    // Redirect dengan semua ID
    $firstId = $ulpl_ids[0];
    header("Location: profile.php?booking_id=$firstId#booking-history");
    exit;

} catch (Exception $e) {
    $connect->rollback();
    echo "<script>
        alert('❌ Booking Error: " . addslashes($e->getMessage()) . "');
        window.history.back();
    </script>";
    exit; 
} 
?>