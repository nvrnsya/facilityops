<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Correct relative path to config.php
include("../config.php");

// Check connection
if ($connect->connect_errno) {
    die("Database connection failed: " . $connect->connect_error);
}

// Check booking ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid booking ID.");
}

$booking_id = intval($_GET['id']);

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    // Start transaction for safety
    $connect->begin_transaction();
    try {
        // Delete from keys table if exists
        $stmt = $connect->prepare("DELETE FROM `keys` WHERE ulpl_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->close();

        // Delete from ulpl table
        $stmt = $connect->prepare("DELETE FROM ulpl WHERE ulpl_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->close();

        // Commit transaction
        $connect->commit();

        // Redirect back to booking history with success message
        header("Location: booking-history.php?message=Booking deleted successfully");
        exit();
    } catch (Exception $e) {
        // Rollback on error
        $connect->rollback();
        die("Error deleting booking: " . $e->getMessage());
    }
}

$query = "
    SELECT 
        'ulpl' AS source,
        a.ulpl_id AS booking_id,
        a.*, 
        u.name,
        u.staffid,
        u.phone_num,
        u.depart,
        k.*
    FROM ulpl a
    JOIN users u ON a.users_id = u.users_id
    LEFT JOIN `keys` k ON a.ulpl_id = k.ulpl_id
    WHERE a.ulpl_id = ?
";

$stmt = $connect->prepare($query);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$booking_type = 'standard';

if (!$row) {
    die("Booking not found.");
}

$formFieldsQuery = "SELECT * FROM booking_form_fields WHERE is_active = 1 ORDER BY field_section, field_order, field_id";
$formFieldsResult = mysqli_query($connect, $formFieldsQuery);
$formFields = [];
$groupedFields = [
    'booking_details' => [],
    'key_handover' => [],
    'additional_info' => []
];

while ($field = mysqli_fetch_assoc($formFieldsResult)) {
    $formFields[] = $field;
    $section = $field['field_section'] ?? 'booking_details';
    $groupedFields[$section][] = $field;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/images/favicon.png">
    <title>View Booking | FacilityOps</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/view-booking.css">
</head>
    
<body>
    <div class="container">
         <!-- ===== STANDARD BOOKING SUMMARY ===== -->
            <div class="page-2" id="summary-page-standard">
                <h2>View Details - Standard Booking</h2>
                <div class="summary-container">
                    <div class="summary-box">
                        <h3>Booking Form</h3>
                        <hr>
                        <p><strong>Facility Name:</strong> <span><?= htmlspecialchars($row['facilityName']); ?></span></p>
                        <p><strong>Program Name:</strong> <span><?= htmlspecialchars($row['programe_name']); ?></span></p>
                        <p><strong>Selected Date:</strong> <span><?= htmlspecialchars($row['select_date']); ?></span></p>
                        <p><strong>Time Slot:</strong> <span><?= htmlspecialchars($row['start_time'] ?? '-'); ?> - <?= htmlspecialchars($row['end_time'] ?? '-'); ?></span></p>
                        <?php foreach ($groupedFields['booking_details'] as $field): ?>
                            <?php 
                            $fieldValue = $row[$field['field_name']] ?? '-';
                            if (empty($fieldValue)) {
                                $fieldValue = '-';
                            }
                            ?>
                            <p>
                                <strong><?= htmlspecialchars($field['field_label']); ?>:</strong> 
                                <span><?= htmlspecialchars($fieldValue); ?></span>
                            </p>
                        <?php endforeach; ?>
                        <h3 style="margin-top: 30px;">Key's Handover</h3>
                        <hr>
                        <?php foreach ($groupedFields['key_handover'] as $field): ?>
                            <?php 
                            // Check in both ulpl table and keys table
                            $fieldValue = $row[$field['field_name']] ?? '-';
                            if (empty($fieldValue)) {
                                $fieldValue = '-';
                            }
                            ?>
                            <p>
                                <strong><?= htmlspecialchars($field['field_label']); ?>:</strong> 
                                <span><?= htmlspecialchars($fieldValue); ?></span>
                            </p>
                        <?php endforeach; ?>
                        
                        <?php if (isset($row['status']) && $row['status'] === 'Rejected' && !empty($row['rejection_notes'])): ?>
                        <div class="rejection-notes-section">
                            <h3 style="margin-top: 30px; color: #dc3545;">
                                <span style="font-size: 20px;">⚠️</span> Rejection Reason
                            </h3>
                            <hr style="border-color: #dc3545;">
                            <div class="rejection-notes-box">
                                <p><strong>Status:</strong> <span style="color: #dc3545; font-weight: bold;">REJECTED</span></p>
                                <p><strong>Reason:</strong></p>
                                <div class="rejection-notes-content">
                                    <?= nl2br(htmlspecialchars($row['rejection_notes'])); ?>
                                </div>
                                <p style="margin-top: 15px; font-size: 14px; color: #666;">
                                    <em>If you have any questions regarding this rejection, please contact the administrator.</em>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            <div class="navigation-buttons">
                <a href="profile.php" class="btn-secondary">Back to Profile</a>
                <button onclick="window.print()" class="btn-print">Print Booking</button>
            </div>
        </div>
    </div>

    <!-- BACK TO TOP BUTTON -->
    <div class="back-to-top" id="backToTop">
        ⬆
    </div>

    <script src="../assets/js/view-booking.js"></script>
    <script>
        // Format and populate print dates
        document.addEventListener('DOMContentLoaded', function() {
            const printDate = new Date().toLocaleDateString('en-MY', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            // Set all print date elements
            document.querySelectorAll('.print-date').forEach(el => {
                el.textContent = printDate;
            });
        });
    </script>
</body>
</html>