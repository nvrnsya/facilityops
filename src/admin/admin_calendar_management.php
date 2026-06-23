<?php
// pages/admin_calendar_management.php - FIXED VERSION
session_start();
include("../config.php");

// Check if user is admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../logininternal.php");
    exit();
}

// Get pending bookings count for notification badge
$pendingCountQuery = "SELECT COUNT(*) as pending_count FROM ulpl WHERE status = 'Pending'";
$pendingResult = mysqli_query($connect, $pendingCountQuery);
$pendingCount = 0;

if ($pendingResult) {
    $pendingRow = mysqli_fetch_assoc($pendingResult);
    $pendingCount = $pendingRow['pending_count'];
}

// ✅ SIMPLIFIED: Only allow admin role
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../logininternal.php");
    exit();
}

// Initialize database table WITHOUT facility_type column
$createTableSQL = "CREATE TABLE IF NOT EXISTS calendar_dates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    status ENUM('available', 'holiday', 'unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (date)
)";
$connect->query($createTableSQL);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_date') {
        $date = $_POST['date'] ?? '';
        $status = $_POST['status'] ?? 'available';
        
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            echo json_encode(['success' => false, 'message' => 'Invalid date format']);
            exit;
        }
        
        // ✅ FIX: Remove facility_type from query
        $stmt = $connect->prepare("INSERT INTO calendar_dates (date, status) 
                                   VALUES (?, ?) 
                                   ON DUPLICATE KEY UPDATE status = ?");
        $stmt->bind_param("sss", $date, $status, $status);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Date updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
        $stmt->close();
        exit;
    }
    
    if ($action === 'bulk_update') {
        $dates = $_POST['dates'] ?? [];
        $status = $_POST['status'] ?? 'available';
        
        $updated = 0;
        foreach ($dates as $date) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                // ✅ FIX: Remove facility_type from query
                $stmt = $connect->prepare("INSERT INTO calendar_dates (date, status) 
                                           VALUES (?, ?) 
                                           ON DUPLICATE KEY UPDATE status = ?");
                $stmt->bind_param("sss", $date, $status, $status);
                if ($stmt->execute()) $updated++;
                $stmt->close();
            }
        }
        
        echo json_encode(['success' => true, 'message' => "$updated dates updated successfully"]);
        exit;
    }
}

// ✅ FIX: Fetch ALL dates (no facility_type filtering)
$stmt = $connect->prepare("SELECT date, status FROM calendar_dates ORDER BY date");
$stmt->execute();
$result = $stmt->get_result();

$existingDates = [];
while ($row = $result->fetch_assoc()) {
    $existingDates[$row['date']] = $row['status'];
}
$stmt->close();

// Page title and description - ULPL only
$pageTitle = 'ULPL Facilities Calendar Management';
$facilityDescription = 'All ULPL Facilities (Dewan Kuliah Utama, Bilik Makan Bauk Inn, Bilik Seminar, Bilik Kuliah 2, Puspanita)';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin</title>
    <link rel="icon" href="../assets/images/favicon.png">
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/adminpage.css">
    <link rel="stylesheet" href="../assets/css/admin_calendar_management.css">
    <link rel="stylesheet" href="../assets/css/profile-menu.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>
    <!-- HEADER -->
    <div class="header" id="header">
        <header>
            <a href="landpage.php" class="logo-container">
                <img src="../assets/images/favicon.png" alt="Logo" style="width: 32px; height: 32px; margin-right: 8px;">
                <h1>FacilityOps</h1>
            </a>
            <nav>
                <ul>
                    <li><a href="landpage.php">Home</a></li>
                    <li><a href="bookingpage.php">Booking</a></li>
                    <li><a href="FAQpage.php">FAQ</a></li>
                    <li>|</li>
                    <li class="menu">
                        <a href="profile.php">Profile</a>
                        <ul class="submenu">
                            <li><a href="dashmenu.php">Dashboard</a></li>
                            <li><a href="edit-profile.php">Edit Profile</a></li>
                            <li><a href="<?php echo $link; ?>logout.php">Sign out</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </header>
    </div>

    <!-- Mobile Menu Toggle -->
    <button class="menu-toggle" id="menuToggle">☰</button>
    
    <!-- Overlay -->
    <div class="overlay" id="overlay"></div>

    <!-- ADMIN LAYOUT -->
    <div class="admin-layout">
        <!-- SIDE PANEL -->
        <aside class="side-panel" id="sidePanel">
            <h3><a href="dashmenu.php">Admin Menu</a></h3>
            <ul class="side-menu">
                <li>
                    <a href="adminpage.php">
                        <span>Current Bookings</span>
                        <?php if ($pendingCount > 0): ?>
                            <span class="notification-badge <?php echo $pendingCount >= 10 ? 'high-count' : ''; ?>">
                                <?php echo $pendingCount > 99 ? '99+' : $pendingCount; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li><a href="booking-history.php">Booking History</a></li>
                <li><a href="edit-announcement.php">Announcement</a></li>
                <li class="has-submenu">
                    <a href="#" class="submenu-toggle">
                        Manage
                        <span class="arrow">▼</span>
                    </a>
                    <ul class="submenu">
                        <li><a href="manage-landpage.php">Home</a></li>
                        <li><a href="manage-facilities.php">Facilities</a></li>
                        <li><a href="admin_calendar_management.php">Calendar</a></li>
                        <li><a href="manage-bookings.php">Form</a></li>
                        <li><a href="manage-faq.php">FAQ</a></li>
                    </ul>
                </li>
                <li><a href="report.php">Report</a></li>
            </ul>
        </aside>

        <!-- MAIN CONTENT -->
        <main>
            <h2><?php echo $pageTitle; ?></h2>
            
            <!-- Info Banner -->
            <div style="background: #f0f9ff; border-left: 4px solid #0891b2; padding: 12px 20px; margin-bottom: 20px; border-radius: 4px;">
                <p style="margin: 0; color: #0e7490; font-weight: 500;">
                    📅 Managing calendar for: <strong><?php echo $facilityDescription; ?></strong>
                </p>
                <p style="margin: 5px 0 0 0; font-size: 0.9em; color: #64748b;">
                    Dates Loaded: <strong><?php echo count($existingDates); ?></strong>
                </p>
                <p style="margin: 5px 0 0 0; font-size: 0.85em; color: #f59e0b; font-weight: 500;">
                    ⚠️ Note: Changes apply to ALL ULPL facilities system-wide
                </p>
            </div>

            <div id="message" class="alert" style="display: none;"></div>

            <!-- CONTROL PANEL - SINGLE DATE -->
            <section class="calendar-section">
                <h3>Update Calendar Status</h3>
                
                <div class="form-group">
                    <label for="singleDate">Select a Single Date:</label>
                    <input type="date" id="singleDate" name="singleDate" class="form-control">
                </div>

                <div class="form-group">
                    <label for="singleStatus">Status:</label>
                    <select id="singleStatus" name="singleStatus" class="form-control">
                        <option value="available">Available</option>
                        <option value="holiday">Holiday / Maintenance</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn save-btn" onclick="updateSingleDate()">Update Date</button>
                </div>
            </section>

            <!-- CONTROL PANEL - BULK UPDATE -->
            <section class="calendar-section">
                <h3>Bulk Update Dates</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="rangeStart">From Date:</label>
                        <input type="date" id="rangeStart" name="rangeStart" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="rangeEnd">To Date:</label>
                        <input type="date" id="rangeEnd" name="rangeEnd" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label for="rangeStatus">Status:</label>
                    <select id="rangeStatus" name="rangeStatus" class="form-control">
                        <option value="available">Available</option>
                        <option value="holiday">Holiday / Maintenance</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn save-btn" onclick="updateDateRange()">Apply to Date Range</button>
                </div>
            </section>

            <!-- CALENDARS -->
            <div class="calendar-grid">
                <section class="calendar-section">
                    <h3>Select Date to Edit</h3>
                    <input type="text" id="adminCalendar" placeholder="Click to select date" class="form-control">
                    <div class="status-legend">
                        <div class="legend-item">
                            <div class="legend-box available"></div>
                            <span>Available - Facility can be booked</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-box holiday"></div>
                            <span>Holiday/Maintenance - Limited availability</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-box unavailable"></div>
                            <span>Unavailable - Cannot be booked</span>
                        </div>
                    </div>
                </section>

                <section class="calendar-section">
                    <h3>Calendar Preview</h3>
                    <input type="text" id="previewCalendar" placeholder="Calendar preview" class="form-control">
                </section>
            </div>

            <!-- RECENT UPDATES -->
            <section class="calendar-section">
                <h3>Recently Updated Dates</h3>
                <div id="recentList" class="recent-updates-list"></div>
                <div id="recentPagination"></div>
            </section>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Pass existing dates from PHP to JavaScript
        window.existingDatesData = <?php echo json_encode($existingDates); ?>;
        console.log('🔍 Calendar Management - ULPL Only');
        console.log('📊 Dates Loaded:', Object.keys(window.existingDatesData).length);
    </script>
    <script src="../assets/js/admin_calendar_management.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>