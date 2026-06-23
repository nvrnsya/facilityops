<?php
include("../config.php");

// ===== SESSION CHECK =====
if (!isset($_SESSION['staffid'])) {
    header("Location: ../index.php");
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

// Initialize variables
$success_msg = '';
$error_msg = '';

// ===== ULPL STATISTICS =====
// 1. Total ULPL Bookings
$total_query = "SELECT COUNT(*) as total FROM ulpl";
$total_result = mysqli_query($connect, $total_query);
$total_bookings = mysqli_fetch_assoc($total_result)['total'];

// 2. ULPL Status Breakdown
$status_query = "
    SELECT 
        status,
        COUNT(*) as count,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM ulpl), 1) as percentage
    FROM ulpl
    GROUP BY status
";
$status_result = mysqli_query($connect, $status_query);
$status_data = [];
while ($row = mysqli_fetch_assoc($status_result)) {
    $status_data[$row['status']] = $row;
}

// ===== ADMIN MANAGEMENT SECTION =====
// Check if current user is admin
$current_staffid = $_SESSION['staffid'];
$is_admin_query = "SELECT * FROM admin_list WHERE staffid = ?";
$stmt = mysqli_prepare($connect, $is_admin_query);
mysqli_stmt_bind_param($stmt, "s", $current_staffid);
mysqli_stmt_execute($stmt);
$is_admin_result = mysqli_stmt_get_result($stmt);
$is_admin = mysqli_num_rows($is_admin_result) > 0;
mysqli_stmt_close($stmt);

// Handle success messages from redirect
if(isset($_GET['success'])) {
    if($_GET['success'] == 'admin_added') {
        $success_msg = "✅ Admin added successfully!";
    } elseif($_GET['success'] == 'admin_removed') {
        $success_msg = "✅ Admin removed successfully!";
    }
}

if(isset($_GET['error'])) {
    if($_GET['error'] == 'not_found') {
        $error_msg = "❌ Staff ID not found in system!";
    } elseif($_GET['error'] == 'already_admin') {
        $error_msg = "⚠️ This staff is already an admin!";
    } elseif($_GET['error'] == 'cannot_remove_self') {
        $error_msg = "⚠️ You cannot remove yourself as admin!";
    } elseif($_GET['error'] == 'last_admin') {
        $error_msg = "⚠️ Cannot remove the last admin!";
    } elseif($_GET['error'] == 'db_error') {
        $error_msg = "❌ Database error occurred!";
    }
}

// Handle Add Admin
if(isset($_POST['add_admin']) && $is_admin) {
    $new_staffid = mysqli_real_escape_string($connect, trim($_POST['new_staffid']));
    
    // Check if staff exists in users table
    $check_user_query = "SELECT * FROM users WHERE staffid = ?";
    $stmt = mysqli_prepare($connect, $check_user_query);
    mysqli_stmt_bind_param($stmt, "s", $new_staffid);
    mysqli_stmt_execute($stmt);
    $user_result = mysqli_stmt_get_result($stmt);
    
    if(mysqli_num_rows($user_result) > 0) {
        // Check if already admin
        $check_admin_query = "SELECT * FROM admin_list WHERE staffid = ?";
        $stmt2 = mysqli_prepare($connect, $check_admin_query);
        mysqli_stmt_bind_param($stmt2, "s", $new_staffid);
        mysqli_stmt_execute($stmt2);
        $admin_check = mysqli_stmt_get_result($stmt2);
        
        if(mysqli_num_rows($admin_check) == 0) {
            // Start transaction
            mysqli_begin_transaction($connect);
            
            try {
                // Add to admin_list
                $insert_admin_query = "INSERT INTO admin_list (staffid) VALUES (?)";
                $stmt3 = mysqli_prepare($connect, $insert_admin_query);
                mysqli_stmt_bind_param($stmt3, "s", $new_staffid);
                $insert_success = mysqli_stmt_execute($stmt3);
                
                // Update user role to admin
                $update_role_query = "UPDATE users SET role = 'admin' WHERE staffid = ?";
                $stmt4 = mysqli_prepare($connect, $update_role_query);
                mysqli_stmt_bind_param($stmt4, "s", $new_staffid);
                $update_success = mysqli_stmt_execute($stmt4);
                
                if($insert_success && $update_success) {
                    mysqli_commit($connect);
                    header("Location: dashmenu.php?success=admin_added");
                    exit();
                } else {
                    mysqli_rollback($connect);
                    header("Location: dashmenu.php?error=db_error");
                    exit();
                }
            } catch (Exception $e) {
                mysqli_rollback($connect);
                header("Location: dashmenu.php?error=db_error");
                exit();
            }
        } else {
            header("Location: dashmenu.php?error=already_admin");
            exit();
        }
        mysqli_stmt_close($stmt2);
    } else {
        header("Location: dashmenu.php?error=not_found");
        exit();
    }
    mysqli_stmt_close($stmt);
}

// Handle Remove Admin
if(isset($_POST['remove_admin']) && $is_admin) {
    $remove_staffid = mysqli_real_escape_string($connect, trim($_POST['remove_staffid']));
    
    // Prevent removing yourself
    if($remove_staffid == $current_staffid) {
        header("Location: dashmenu.php?error=cannot_remove_self");
        exit();
    }
    
    // Check if this is the last admin
    $count_admins_query = "SELECT COUNT(*) as total FROM admin_list";
    $count_result = mysqli_query($connect, $count_admins_query);
    $admin_count = mysqli_fetch_assoc($count_result)['total'];
    
    if($admin_count <= 1) {
        header("Location: dashmenu.php?error=last_admin");
        exit();
    }
    
    // Start transaction
    mysqli_begin_transaction($connect);
    
    try {
        // Remove from admin_list
        $delete_admin_query = "DELETE FROM admin_list WHERE staffid = ?";
        $stmt = mysqli_prepare($connect, $delete_admin_query);
        mysqli_stmt_bind_param($stmt, "s", $remove_staffid);
        $delete_success = mysqli_stmt_execute($stmt);
        
        // Update user role to staff
        $update_role_query = "UPDATE users SET role = 'staff' WHERE staffid = ?";
        $stmt2 = mysqli_prepare($connect, $update_role_query);
        mysqli_stmt_bind_param($stmt2, "s", $remove_staffid);
        $update_success = mysqli_stmt_execute($stmt2);
        
        if($delete_success && $update_success) {
            mysqli_commit($connect);
            header("Location: dashmenu.php?success=admin_removed");
            exit();
        } else {
            mysqli_rollback($connect);
            header("Location: dashmenu.php?error=db_error");
            exit();
        }
    } catch (Exception $e) {
        mysqli_rollback($connect);
        header("Location: dashmenu.php?error=db_error");
        exit();
    }
}

// Get list of all admins with user details
$admin_list_query = "
    SELECT a.staffid, u.name, u.email 
    FROM admin_list a
    LEFT JOIN users u ON a.staffid = u.staffid
    ORDER BY a.staffid
";
$admin_list_result = mysqli_query($connect, $admin_list_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/images/favicon.png">
    <title>Admin Dashboard | FacilityOps</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/profile-menu.css">
    <link rel="stylesheet" href="../assets/css/dashmenu.css">
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
                    <li class="menu">
                        <a href="profile.php">Profile</a>
                        <ul class="submenu">
                            <li><a href="dashmenu.php">Dashboard</a></li>
                            <li><a href="edit-profile.php">Edit Profile</a></li>
                            <li><a href="../logout.php">Sign out</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </header>
    </div>       

    <!-- Mobile Menu Toggle -->
    <button class="menu-toggle" id="menuToggle" aria-label="Toggle Menu">☰</button>
    
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
        <div class="dashboard-header">
            <h2>Admin Dashboard</h2>
            <p class="dashboard-subtitle">Overview of facility booking statistics</p>
        </div>
        
        <div class="stats-grid">
            <!-- TOTAL BOOKINGS CARD -->
            <div class="stat-card total">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <h6 class="stat-label">Total Bookings</h6>
                    <h3 class="stat-value"><?php echo $total_bookings; ?></h3>
                    <p class="stat-description">All time facility bookings</p>
                </div>
            </div>

            <!-- APPROVED CARD -->
            <div class="stat-card approved">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <div class="stat-content">
                    <h6 class="stat-label">Approved</h6>
                    <h3 class="stat-value"><?php echo $status_data['Approved']['count'] ?? 0; ?></h3>
                    <p class="stat-description"><?php echo $status_data['Approved']['percentage'] ?? 0; ?>% approval rate</p>
                </div>
            </div>

            <!-- PENDING CARD -->
            <div class="stat-card pending">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <div class="stat-content">
                    <h6 class="stat-label">Pending</h6>
                    <h3 class="stat-value"><?php echo $status_data['Pending']['count'] ?? 0; ?></h3>
                    <p class="stat-description"><?php echo $status_data['Pending']['percentage'] ?? 0; ?>% awaiting review</p>
                </div>
            </div>

            <!-- REJECTED CARD -->
            <div class="stat-card rejected">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </div>
                <div class="stat-content">
                    <h6 class="stat-label">Rejected</h6>
                    <h3 class="stat-value"><?php echo $status_data['Rejected']['count'] ?? 0; ?></h3>
                    <p class="stat-description"><?php echo $status_data['Rejected']['percentage'] ?? 0; ?>% declined</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="quick-actions">
            <h3>Quick Actions</h3>
            <div class="action-buttons">
                <a href="adminpage.php" class="action-btn primary">
                    <span class="btn-icon">📋</span>
                    <span>Current Bookings</span>
                </a>
                <a href="booking-history.php" class="action-btn secondary">
                    <span class="btn-icon">📚</span>
                    <span>History</span>
                </a>
                <a href="edit-announcement.php" class="action-btn tertiary">
                    <span class="btn-icon">📢</span>
                    <span>Announcement</span>
                </a>
                </a>
                <a href="report.php" class="action-btn quaternary">
                    <span class="btn-icon">📊</span>
                    <span>Report</span>
                </a>
            </div>
        </div>

        <!-- ADMIN MANAGEMENT SECTION -->
        <?php if($is_admin): ?>
        <div class="admin-section">
            <h3>
                <span>👥</span>
                Manage Administrators
            </h3>
            
            <?php if(!empty($success_msg)): ?>
                <div class="alert alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($error_msg)): ?>
                <div class="alert alert-error"><?php echo $error_msg; ?></div>
            <?php endif; ?>
            
            <!-- Add Admin Form -->
            <form method="POST" class="admin-form" onsubmit="return confirmAddAdmin(this);">
                <input type="text" 
                       name="new_staffid" 
                       placeholder="Enter Staff ID to add as admin" 
                       required
                       pattern="[0-9]+"
                       title="Please enter numbers only">
                <button type="submit" name="add_admin">➕ Add Admin</button>
            </form>
            
            <!-- Admin List Table -->
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Staff ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $admin_count = mysqli_num_rows($admin_list_result);
                    if($admin_count > 0): 
                        while($admin = mysqli_fetch_assoc($admin_list_result)): 
                            $is_current_user = ($admin['staffid'] == $current_staffid);
                    ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($admin['staffid']); ?>
                                <?php if($is_current_user): ?>
                                    <span class="badge badge-you">You</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($admin['name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($admin['email'] ?? 'N/A'); ?></td>
                            <td>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('⚠️ Are you sure you want to remove this admin?\n\nStaff ID: <?php echo htmlspecialchars($admin['staffid']); ?>\nName: <?php echo htmlspecialchars($admin['name'] ?? 'N/A'); ?>');">
                                    <input type="hidden" name="remove_staffid" value="<?php echo htmlspecialchars($admin['staffid']); ?>">
                                    <button type="submit" 
                                        name="remove_admin" 
                                        class="remove-btn"
                                        title="<?php echo $is_current_user ? 'Cannot remove yourself' : ($admin_count <= 1 ? 'Cannot remove last admin' : 'Remove admin'); ?>"
                                        <?php echo ($is_current_user || $admin_count <= 1) ? 'disabled' : ''; ?>>
                                    🗑️
                                </button>
                                </form>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                        <tr>
                            <td colspan="4" class="no-admins">No administrators found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        </main>
    </div>

    <!-- JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Admin management script loaded');
        
        const adminForms = document.querySelectorAll('.admin-form, .admin-section form');
        adminForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                console.log('Admin form submitting...');
            });
        });
    });

    function confirmAddAdmin(form) {
        const staffId = form.querySelector('input[name="new_staffid"]').value.trim();
        if (!staffId) {
            alert('Please enter a Staff ID');
            return false;
        }
        return confirm(`Add Staff ID "${staffId}" as admin?`);
    }
    </script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>