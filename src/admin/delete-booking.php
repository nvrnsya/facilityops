<?php
// Make sure no output before headers
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include("../config.php");

// Check connection
if ($connect->connect_errno) {
    die("Database connection failed: " . $connect->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../logininternal.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Check booking ID
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || $_GET['id'] <= 0) {
    header("Location: profile.php?error=" . urlencode("Invalid booking ID."));
    exit();
}

$booking_id = intval($_GET['id']);

// ==========================================
// 🔹 VERIFY BOOKING EXISTS & USER PERMISSION
// ==========================================
$checkQuery = "SELECT ulpl_id, users_id FROM ulpl WHERE ulpl_id = ?";
$checkStmt = $connect->prepare($checkQuery);
$checkStmt->bind_param("i", $booking_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    error_log("No booking found for ID: $booking_id");
    header("Location: profile.php?error=" . urlencode("Booking not found."));
    exit();
}

$bookingData = $checkResult->fetch_assoc();
$checkStmt->close();

// Check if user owns this booking or is admin
if (!$is_admin && $bookingData['users_id'] != $user_id) {
    header("Location: profile.php?error=" . urlencode("You don't have permission to delete this booking."));
    exit();
}

// ==========================================
// 🔹 HANDLE DELETE CONFIRMATION
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $connect->begin_transaction();
    try {
        // 1. Delete from keys table
        $deleteKeysQuery = "DELETE FROM `keys` WHERE ulpl_id = ?";
        $stmtKeys = $connect->prepare($deleteKeysQuery);
        $stmtKeys->bind_param("i", $booking_id);
        $stmtKeys->execute();
        $stmtKeys->close();

        // 2. Delete from ulpl table
        $deleteUlplQuery = "DELETE FROM ulpl WHERE ulpl_id = ?";
        $stmtUlpl = $connect->prepare($deleteUlplQuery);
        $stmtUlpl->bind_param("i", $booking_id);
        $stmtUlpl->execute();
        $stmtUlpl->close();

        // Commit transaction
        $connect->commit();

        // Redirect with success message
        ob_end_clean();
        header("Location: profile.php?deleted=1");
        exit();
        
    } catch (Exception $e) {
        // Rollback on error
        $connect->rollback();
        $error = "Failed to delete booking: " . $e->getMessage();
        error_log($error);
    }
}

// ==========================================
// 🔹 FETCH BOOKING DATA FOR CONFIRMATION
// ==========================================
$query = "
    SELECT
        u.ulpl_id AS booking_id,
        u.facilityName,
        u.programe_name,
        u.select_date,
        u.start_time,
        u.end_time,
        u.status,
        COALESCE(usr.name, 'Unknown User') AS user_name,
        COALESCE(usr.staffid, 'N/A') AS staffid
    FROM ulpl u
    LEFT JOIN users usr ON u.users_id = usr.users_id
    WHERE u.ulpl_id = ?
";

$stmt = $connect->prepare($query);
$stmt->bind_param("i", $booking_id);
$stmt->execute();

if ($stmt->errno) {
    error_log("SQL Error: " . $stmt->error);
    die("An error occurred while fetching the booking: " . $stmt->error);
}

$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    error_log("Booking not found for ID: $booking_id");
    header("Location: profile.php?error=" . urlencode("Booking not found."));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/images/favicon.png">
    <title>Delete Booking | FacilityOps</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/delete-booking.css">
</head>
<body>
    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <strong>✕ Error:</strong> <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="delete-page">
            <div class="warning-icon">⚠️</div>
            <h2>Delete Booking Confirmation</h2>
            <p class="warning-text">Are you sure you want to delete this booking? This action cannot be undone.</p>
            
            <div class="booking-details">
                <h3>Booking Information</h3>
                <hr>
                
                <div class="detail-row">
                    <span class="label">Facility Name:</span>
                    <span class="value"><?= htmlspecialchars($row['facilityName']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Program Name:</span>
                    <span class="value"><?= htmlspecialchars($row['programe_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Selected Date:</span>
                    <span class="value"><?= htmlspecialchars($row['select_date']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Time Slot:</span>
                    <span class="value">
                        <?= htmlspecialchars($row['start_time'] ?? '-'); ?> - 
                        <?= htmlspecialchars($row['end_time'] ?? '-'); ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">Status:</span>
                    <span class="value status-badge status-<?= strtolower($row['status']); ?>">
                        <?= htmlspecialchars($row['status']); ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">Booked By:</span>
                    <span class="value"><?= htmlspecialchars($row['user_name']); ?> (<?= htmlspecialchars($row['staffid']); ?>)</span>
                </div>
                <div class="detail-row">
                    <span class="label">Booking ID:</span>
                    <span class="value">#<?= htmlspecialchars($row['booking_id']); ?></span>
                </div>
            </div>

            <div class="deletion-notice">
                <h4>⚠️ What will be deleted:</h4>
                <ul>
                    <li>Main booking record</li>
                    <li>Key handover information (if applicable)</li>
                    <li>All associated data</li>
                </ul>
            </div>

            <form method="POST" id="deleteForm">
                <div class="checkbox-confirm">
                    <input type="checkbox" id="confirmCheck" name="confirm_check" required>
                    <label for="confirmCheck">I understand this action cannot be undone</label>
                </div>
                <div class="button-group">
                    <button type="submit" name="confirm_delete" class="btn btn-delete" id="deleteBtn" disabled>
                        Delete Booking
                    </button>
                    <a href="profile.php" class="btn btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Enable delete button only when checkbox is checked
        const confirmCheck = document.getElementById('confirmCheck');
        const deleteBtn = document.getElementById('deleteBtn');
        
        confirmCheck.addEventListener('change', function() {
            deleteBtn.disabled = !this.checked;
        });

        // Additional confirmation on submit
        document.getElementById('deleteForm').addEventListener('submit', function(e) {
            if (!confirm('Final confirmation: Are you absolutely sure you want to delete this booking?')) {
                e.preventDefault();
            }
        });

        // Prevent accidental form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Back to Top Button
        const backToTopBtn = document.createElement('button');
        backToTopBtn.id = 'backToTop';
        backToTopBtn.innerHTML = '↑';
        backToTopBtn.title = 'Back to Top';
        document.body.appendChild(backToTopBtn);

        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        });

        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html>