<?php
include("../config.php");

// Get user role
$userRole = $_SESSION['role'] ?? 'staff';

// Check permission - ULPL admin only
if ($userRole !== 'admin') {
    header("Location: landpage.php");
    exit();
}

// Get pending bookings count for notification badge
$pendingCountQuery = "SELECT COUNT(*) as pending_count FROM ulpl WHERE status = 'Pending'";
$pendingResult = mysqli_query($conn, $pendingCountQuery);
$pendingCount = 0;

if ($pendingResult) {
    $pendingRow = mysqli_fetch_assoc($pendingResult);
    $pendingCount = $pendingRow['pending_count'];
}

// ===== CHECK IF EDIT MODE =====
$editMode = false;
$editData = null;

if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $editQuery = "SELECT * FROM announcements WHERE announcement_id = ? AND announcement_type = 'ulpl'";
    $stmt = $conn->prepare($editQuery);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $editResult = $stmt->get_result();
    
    if ($editResult->num_rows > 0) {
        $editMode = true;
        $editData = $editResult->fetch_assoc();
    }
    $stmt->close();
}

/* ===========================
   HANDLE AJAX (Toggle Status)
   =========================== */
if (isset($_POST['ajax']) && $_POST['ajax'] === 'true') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';
    $announcementId = intval($_POST['announcement_id'] ?? 0);

    if ($action === 'toggle_status') {
        $newStatus = strtolower(trim($_POST['status']));
        
        $stmt = $conn->prepare("UPDATE announcements SET status=? WHERE announcement_id=? AND announcement_type='ulpl'");
        $stmt->bind_param("si", $newStatus, $announcementId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
        $stmt->close();
        exit();
    }
}

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $limit;

// Get total count - ULPL only
$countQuery = "SELECT COUNT(*) as total FROM announcements WHERE announcement_type = 'ulpl'";
$countResult = mysqli_query($conn, $countQuery);
$totalRecords = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalRecords / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/images/favicon.png">
    <title>ULPL Announcements | FacilityOps</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/edit-announcement.css?v=2">
    <link rel="stylesheet" href="../assets/css/profile-menu.css">
    <link rel="stylesheet" href="../assets/css/base.css">
</head>  
<body>
    <!-- HEADER -->
    <div class="header" data-role="header" id="header">
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
                
                    <!-- Profile Dropdown -->
                    <li class="menu">
                    <a href="profile.php">Profile</a>
                        <ul class="submenu">
                            <li><a href="dashmenu.php" >Dashboard</a></li>
                            <li><a href="edit-profile.php" >Edit Profile</a></li>
                            <li><a href="../logout.php">Sign out</a></li>
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
                <!-- MANAGE SUBMENU -->
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
        <h2>ULPL Announcement Dashboard</h2>
        
        <?php
        // Display success/error messages
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-error">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        ?>
        
        <!-- ADD/EDIT ANNOUNCEMENT FORM -->
        <section id="<?php echo $editMode ? 'edit' : 'add'; ?>-announcement">
            <h3><?php echo $editMode ? '✏️ Edit ULPL Announcement' : 'Add New ULPL Announcement'; ?></h3>
            
            <?php if ($editMode): ?>
                <div class="alert alert-info" style="margin-bottom: 20px;">
                    <strong>ℹ️ Edit Mode:</strong> You are currently editing announcement #<?php echo $editData['announcement_id']; ?>
                    <a href="edit-announcement.php" style="float: right; color: #2563eb; text-decoration: underline;">Cancel Edit</a>
                </div>
            <?php endif; ?>
            
            <div class="table-container">
                <form action="process_announcement.php" method="post">
                    <input type="hidden" name="action" value="<?php echo $editMode ? 'update' : 'add'; ?>">
                    <input type="hidden" name="announcement_type" value="ulpl">
                    <?php if ($editMode): ?>
                        <input type="hidden" name="id" value="<?php echo $editData['announcement_id']; ?>">
                    <?php endif; ?>
                    
                    <!-- Title -->
                    <div class="form-group">
                        <label for="title">Announcement Title</label>
                        <input type="text" id="title" name="title" 
                               placeholder="Enter announcement title" 
                               value="<?php echo $editMode ? htmlspecialchars($editData['title']) : ''; ?>" 
                               required>
                    </div>

                    <!-- Content -->
                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" rows="5" 
                                  placeholder="Write the announcement details here..." 
                                  required><?php echo $editMode ? htmlspecialchars($editData['content']) : ''; ?></textarea>
                    </div>

                    <!-- Dates -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date" 
                                   value="<?php echo $editMode ? $editData['start_date'] : ''; ?>" 
                                   required>
                        </div>
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date" 
                                   value="<?php echo $editMode ? $editData['end_date'] : ''; ?>">
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="active" <?php echo ($editMode && $editData['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($editMode && $editData['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="form-actions">
                        <button type="submit" class="btn save-btn">
                            <?php echo $editMode ? '💾 Update Announcement' : '➕ Add Announcement'; ?>
                        </button>
                        <?php if ($editMode): ?>
                            <a href="edit-announcement.php" class="btn cancel-btn">✖ Cancel</a>
                        <?php else: ?>
                            <button type="reset" class="btn cancel-btn">🔄 Reset</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </section>

        <!-- EXISTING ANNOUNCEMENTS -->
        <section id="existing-announcements">
            <h3>Existing ULPL Announcements</h3>
            
            <!-- Pagination Info -->
            <?php if ($totalRecords > 0): ?>
            <div class="pagination-info">
                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $totalRecords); ?> 
                of <?php echo $totalRecords; ?> records
            </div>
            <?php endif; ?>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Title</th>
                            <th>Content</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Query ULPL announcements only
                        $sql = "SELECT * FROM announcements WHERE announcement_type = 'ulpl' ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
                        $result = mysqli_query($conn, $sql);

                        if ($result && mysqli_num_rows($result) > 0) {
                            $rowNum = $offset + 1;
                            while ($row = mysqli_fetch_assoc($result)) {
                                // Clean status value
                                $statusValue = strtolower(trim($row['status']));
                                ?>
                                <tr>
                                    <td><?php echo $rowNum; ?></td>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($row['content'], 0, 50)) . '...'; ?></td>
                                    <td><?php echo date('d-m-y', strtotime($row['start_date'])); ?></td>
                                    <td><?php echo $row['end_date'] ? date('d-m-y', strtotime($row['end_date'])) : '-'; ?></td>
                                    <td style="text-align: center;">
                                        <div class="status-toggle-container">
                                            <label class="toggle-switch" 
                                                data-announcement-id="<?php echo $row['announcement_id']; ?>"
                                                data-status="<?php echo $statusValue == 'active' ? 'Active' : 'Inactive'; ?>">
                                                <input 
                                                    type="checkbox" 
                                                    class="status-toggle-ajax"
                                                    <?php echo $statusValue == 'active' ? 'checked' : ''; ?>
                                                    data-announcement-id="<?php echo $row['announcement_id']; ?>">
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </div>
                                    </td>
                                    <td class='actions-cell'>
                                        <a href='edit-announcement.php?edit_id=<?php echo $row['announcement_id']; ?>#<?php echo $editMode ? 'edit' : 'add'; ?>-announcement' 
                                           class='action-btn edit' 
                                           title='Edit Announcement'>
                                            <span>&#9998;</span>
                                        </a>
                                        <a href='process_announcement.php?action=delete&id=<?php echo $row['announcement_id']; ?>' 
                                           class='action-btn delete' 
                                           title='Delete Announcement'
                                           onclick="return confirm('Are you sure you want to delete this announcement?');">
                                            <span>&#128465;</span>
                                        </a>
                                    </td>
                                </tr>
                                <?php
                                $rowNum++; 
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 60px 20px;">
                                    <div style="color: #718096; font-size: 16px;">
                                        <span style="font-size: 48px; display: block; margin-bottom: 16px;">📢</span>
                                        <strong>No ULPL announcements found</strong>
                                        <p style="margin-top: 8px; font-size: 14px;">Add your first announcement using the form above.</p>
                                    </div>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Controls -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <!-- Previous Button -->
                <?php if ($page > 1): ?>
                    <a href="?page=1" title="First Page">&laquo;&laquo;</a>
                    <a href="?page=<?php echo $page - 1; ?>" title="Previous Page">&laquo;</a>
                <?php else: ?>
                    <span class="disabled">&laquo;&laquo;</span>
                    <span class="disabled">&laquo;</span>
                <?php endif; ?>
                
                <!-- Page Numbers -->
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                if ($startPage > 1) {
                    echo '<a href="?page=1">1</a>';
                    if ($startPage > 2) {
                        echo '<span>...</span>';
                    }
                }
                
                for ($i = $startPage; $i <= $endPage; $i++) {
                    if ($i == $page) {
                        echo '<span class="active">' . $i . '</span>';
                    } else {
                        echo '<a href="?page=' . $i . '">' . $i . '</a>';
                    }
                }
                
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<span>...</span>';
                    }
                    echo '<a href="?page=' . $totalPages . '">' . $totalPages . '</a>';
                }
                ?>
                
                <!-- Next Button -->
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" title="Next Page">&raquo;</a>
                    <a href="?page=<?php echo $totalPages; ?>" title="Last Page">&raquo;&raquo;</a>
                <?php else: ?>
                    <span class="disabled">&raquo;</span>
                    <span class="disabled">&raquo;&raquo;</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </section>
    </main>
    </div>

    <!-- JavaScript -->
    <script src="../assets/js/admin.js"></script>
    <script src="../assets/js/announcement.js"></script>

</body>
</html>