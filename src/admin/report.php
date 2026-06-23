<?php
include("../config.php");

// Get pending bookings count for notification badge
$pendingCountQuery = "SELECT COUNT(*) as pending_count FROM ulpl WHERE status = 'Pending'";
$pendingResult = mysqli_query($connect, $pendingCountQuery);
$pendingCount = 0;

if ($pendingResult) {
    $pendingRow = mysqli_fetch_assoc($pendingResult);
    $pendingCount = $pendingRow['pending_count'];
}

// Function untuk map facility name - ULPL ONLY
function getFacilityDisplayName($facilityName) {
    $facilityMap = [
        'dewan-kuliah-utama' => 'Dewan Kuliah Utama',
        'bilik-makan-bauk-inn' => 'Bilik Makan Bauk Inn',
        'bilik-seminar' => 'Bilik Seminar',
        'bilik-kuliah-2' => 'Bilik Kuliah 2',
        'puspanita' => 'Puspanita'
    ];
    return isset($facilityMap[$facilityName]) ? $facilityMap[$facilityName] : $facilityName;
}

// Get filter parameters - Default to CURRENT MONTH
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-t');
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Base WHERE clause - ULPL ONLY
$where = "WHERE b.select_date BETWEEN '$date_from' AND '$date_to'";

// Add search filter - will be added to queries that have user JOIN
$search_condition = "";
if (!empty($search)) {
    $search_escaped = mysqli_real_escape_string($connect, $search);
    $search_condition = " AND (
        u.name LIKE '%$search_escaped%' OR 
        b.programe_name LIKE '%$search_escaped%' OR 
        b.depart LIKE '%$search_escaped%' OR 
        b.facilityName LIKE '%$search_escaped%'
    )";
}

// Get status filters
$status_filters = [];
if (isset($_GET['status_approved'])) {
    $status_filters[] = 'Approved';
}
if (isset($_GET['status_rejected'])) {
    $status_filters[] = 'Rejected';
}

// Default: show both if none selected
if (empty($status_filters)) {
    $status_filters = ['Approved', 'Rejected'];
}

$status_filter_string = "'" . implode("','", $status_filters) . "'";

$url_params = "date_from=$date_from&date_to=$date_to";
if (isset($_GET['status_approved'])) {
    $url_params .= '&status_approved=1';
}
if (isset($_GET['status_rejected'])) {
    $url_params .= '&status_rejected=1';
}
if (!empty($search)) {
    $url_params .= '&search=' . urlencode($search);
}

// ===== STATISTICS QUERIES (SEMUA STATUS) =====

// 1. Total Bookings (no user join needed)
$total_query = "SELECT COUNT(*) as total FROM ulpl b $where";
$total_result = mysqli_query($connect, $total_query);
$total_bookings = mysqli_fetch_assoc($total_result)['total'];

// 2. Status Breakdown (no user join needed)
$status_query = "
    SELECT 
        b.status,
        COUNT(*) AS count,
        ROUND(
            COUNT(*) * 100.0 / (
                SELECT COUNT(*) 
                FROM ulpl b2 
                $where
            ), 1
        ) AS percentage
    FROM ulpl b
    $where
    GROUP BY b.status
";

$status_result = mysqli_query($connect, $status_query);
$status_data = [];
while ($row = mysqli_fetch_assoc($status_result)) {
    $status_data[$row['status']] = $row;
}

// 3. Top 5 Department (no user join needed for this query)
$department_query = "
    SELECT 
        COALESCE(NULLIF(b.depart, ''), u.depart) AS depart,
        COUNT(*) AS booking_count
    FROM ulpl b
    JOIN users u ON b.users_id = u.users_id
    $where
    GROUP BY depart
    ORDER BY booking_count DESC
    LIMIT 5
";


$department_result = mysqli_query($connect, $department_query);
$department_data = [];
while ($row = mysqli_fetch_assoc($department_result)) {
    $department_data[] = $row;
}

// 4. Monthly Trend (no user join needed)
$trend_query = "
    SELECT 
        DATE_FORMAT(select_date, '%b %Y') AS month,
        COUNT(*) AS total
    FROM ulpl b
    $where
    GROUP BY YEAR(select_date), MONTH(select_date)
    ORDER BY YEAR(select_date), MONTH(select_date)
";

$trendResult = mysqli_query($connect, $trend_query);

$trend_months = [];
$trend_totals = [];

if ($trendResult) {
    while ($row = mysqli_fetch_assoc($trendResult)) {
        $trend_months[] = $row['month'];
        $trend_totals[] = (int)$row['total'];
    }
}

// 5. Most Active Day of Week (no user join needed)
$day_query = "
    SELECT 
        DAYNAME(select_date) as day_name,
        COUNT(*) as count
    FROM ulpl b
    $where
    GROUP BY day_name
    ORDER BY count DESC
    LIMIT 1
";
$day_result = mysqli_query($connect, $day_query);
$most_active_day = mysqli_fetch_assoc($day_result);

// Calculate approval rate
$approval_rate = isset($status_data['Approved']) && $total_bookings > 0 
    ? round(($status_data['Approved']['count'] / $total_bookings) * 100, 1) 
    : 0;

// ===== PAGINATION SETUP =====
$limit = 10; // Records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $limit;

// Get total count for pagination (WITH USER JOIN + SEARCH)
$count_bookings_query = "
    SELECT COUNT(*) as total
    FROM ulpl b
    LEFT JOIN users u ON b.users_id = u.users_id
    $where
    $search_condition
    AND b.status IN ($status_filter_string)
";
$count_bookings_result = mysqli_query($connect, $count_bookings_query);
$totalRecords = mysqli_fetch_assoc($count_bookings_result)['total'];
$totalPages = ceil($totalRecords / $limit);

// 6. Detailed Booking Records (WITH USER JOIN + SEARCH + PAGINATION)
$bookings_query = "
    SELECT 
        b.ulpl_id AS id,
        b.users_id,
        u.name,
        COALESCE(u.email, 'N/A') AS email,
        b.facilityName,
        b.programe_name AS purpose,
        COALESCE(NULLIF(b.depart, ''), u.depart, '-') AS depart,
        b.select_date,
        b.status
    FROM ulpl b
    LEFT JOIN users u ON b.users_id = u.users_id
    $where
    $search_condition
    AND b.status IN ($status_filter_string)
    ORDER BY b.select_date DESC
    LIMIT $limit OFFSET $offset
";

if (isset($_GET['export_all'])) {
    $all_query = "
        SELECT 
            b.ulpl_id AS id,
            u.name,
            b.facilityName,
            b.programe_name AS purpose,
            COALESCE(NULLIF(b.depart, ''), u.depart, '-') AS depart,
            b.select_date,
            b.status
        FROM ulpl b
        LEFT JOIN users u ON b.users_id = u.users_id
        $where
        $search_condition
        AND b.status IN ($status_filter_string)
        ORDER BY b.select_date DESC
    ";
    $all_result = mysqli_query($connect, $all_query);
    $all_data = [];
    while ($row = mysqli_fetch_assoc($all_result)) {
        $all_data[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($all_data);
    exit;
}

$bookings_result = mysqli_query($connect, $bookings_query);

if (!$bookings_result) {
    echo "Query Error: " . mysqli_error($connect);
    die();
}

$bookings_data = [];
while ($row = mysqli_fetch_assoc($bookings_result)) {
    $bookings_data[] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/images/favicon.png">
    <title>Analytics Report | FacilityOps</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/profile-menu.css">
    <link rel="stylesheet" href="../assets/css/report.css">
    
    <!-- Chart.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js"></script>
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

        <!-- CONTENT -->
        <main>
            <div class="dashboard-header">
                <h2>Analytics Dashboard</h2>
                <p class="dashboard-subtitle">Comprehensive booking statistics and insights</p>
            </div>
            
            <div class="container">
                <!-- Header with Date Filter -->
                <div class="report-header no-print">
                    <h1>Booking Analytics Report</h1>
                    <div class="header-actions">
                        <button class="btn btn-secondary" onclick="showPrintOptions()">🖨 Print</button>
                        <button class="btn btn-secondary" onclick="saveAsPDF()">📄 Save as PDF</button>
                        <button class="btn btn-primary" onclick="exportToCSV()">📥 Export CSV</button>
                    </div>
                </div>

                <!-- Date Range Filter with Search -->
                <form class="date-filter" method="GET" action="">
                    <div class="date-inputs">
                        <div class="search-box">
                            <label for="search">Search</label>
                            <input type="text" id="search" name="search" 
                                   placeholder="Search name, facility, programme, department..." 
                                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        </div>
                        <div>
                            <label for="date_from">From</label>
                            <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                        </div>
                        <div>
                            <label for="date_to">To</label>
                            <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                        </div>
                        
                        <!-- PRESERVE STATUS FILTERS -->
                        <?php if (isset($_GET['status_approved'])): ?>
                        <input type="hidden" name="status_approved" value="1">
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['status_rejected'])): ?>
                        <input type="hidden" name="status_rejected" value="1">
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-primary">Apply</button>
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='report.php'">Reset</button>
                    </div>
                    <div class="date-range-info">
                        <?php
                        // Check if showing current month
                        $current_month_start = date('Y-m-01');
                        $current_month_end = date('Y-m-t');
                        $is_current_month = ($date_from == $current_month_start && $date_to == $current_month_end);
                        
                        if ($is_current_month && !isset($_GET['date_from'])) {
                            echo 'Showing data for <strong>' . date('F Y') . '</strong> (' . 
                                 date('d M Y', strtotime($date_from)) . ' to ' . 
                                 date('d M Y', strtotime($date_to)) . ')';
                        } else {
                            echo 'Showing data from <strong>' . 
                                 date('d M Y', strtotime($date_from)) . 
                                 '</strong> to <strong>' . 
                                 date('d M Y', strtotime($date_to)) . '</strong>';
                        }
                        ?>
                    </div>
                </form>

                <?php if (!empty($search)): ?>
                <div style="margin-bottom: 20px; padding: 12px 18px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 18px;">🔍</span>
                        <span style="color: #856404; font-weight: 500;">
                            Active Search: <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
                        </span>
                    </div>
                    <a href="?date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?><?php echo isset($_GET['status_approved']) ? '&status_approved=1' : ''; ?><?php echo isset($_GET['status_rejected']) ? '&status_rejected=1' : ''; ?>" 
                       style="color: #856404; text-decoration: none; font-weight: 600; padding: 6px 12px; background: rgba(255,255,255,0.7); border-radius: 6px; transition: all 0.2s; display: flex; align-items: center; gap: 6px;"
                       onmouseover="this.style.background='rgba(255,255,255,1)'"
                       onmouseout="this.style.background='rgba(255,255,255,0.7)'">
                        Clear Search ✕
                    </a>
                </div>
                <?php endif; ?>

                <!-- Summary Cards -->
                <div class="stats-grid">
                    <div class="stat-card stat-primary">
                        <div class="stat-icon">📅</div>
                        <div class="stat-content">
                            <h3>Total Bookings</h3>
                            <p class="stat-value"><?php echo $total_bookings; ?></p>
                            <span class="stat-label">In selected period</span>
                        </div>
                    </div>

                    <div class="stat-card stat-success">
                        <div class="stat-icon">✅</div>
                        <div class="stat-content">
                            <h3>Approved</h3>
                            <p class="stat-value"><?php echo $status_data['Approved']['count'] ?? 0; ?></p>
                            <span class="stat-label"><?php echo $status_data['Approved']['percentage'] ?? 0; ?>% approval rate</span>
                        </div>
                    </div>

                    <div class="stat-card stat-warning">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-content">
                            <h3>Pending</h3>
                            <p class="stat-value"><?php echo $status_data['Pending']['count'] ?? 0; ?></p>
                            <span class="stat-label"><?php echo $status_data['Pending']['percentage'] ?? 0; ?>% awaiting review</span>
                        </div>
                    </div>

                    <div class="stat-card stat-danger">
                        <div class="stat-icon">❌</div>
                        <div class="stat-content">
                            <h3>Rejected</h3>
                            <p class="stat-value"><?php echo $status_data['Rejected']['count'] ?? 0; ?></p>
                            <span class="stat-label"><?php echo $status_data['Rejected']['percentage'] ?? 0; ?>% declined</span>
                        </div>
                    </div>
                </div>

                <div class="detailed-booking-container">
                    <!-- ADD THIS LINE -->
                    <div class="print-only-date-range" style="display: none;">
                        Date Range: <?php echo date('d M Y', strtotime($date_from)); ?> to <?php echo date('d M Y', strtotime($date_to)); ?>
                    </div>

                <!-- Detailed Booking Records -->
                <div class="table-container detailed-booking-container">
                    <div class="table-header-section">
                        <h3>Detailed Booking Records</h3>
                        <p class="table-subtitle">Complete list of all bookings in selected period</p>
                    </div>

                    <!-- Status Filter Checkboxes -->
                    <div class="status-filter-box">
                        <form method="GET" action="" id="statusFilterForm">
                            <input type="hidden" name="date_from" value="<?php echo $date_from; ?>">
                            <input type="hidden" name="date_to" value="<?php echo $date_to; ?>">
                            
                            <!-- PRESERVE SEARCH -->
                            <?php if (!empty($search)): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <?php endif; ?>
                            
                            <input type="hidden" name="page" value="1">
                            
                            <label style="font-weight: 600; margin-right: 20px;">Filter Status:</label>
                            
                            <label style="margin-right: 15px; cursor: pointer;">
                                <input type="checkbox" name="status_approved" value="1" 
                                       <?php echo isset($_GET['status_approved']) || (!isset($_GET['status_approved']) && !isset($_GET['status_rejected'])) ? 'checked' : ''; ?>
                                       onchange="this.form.submit()">
                                <span style="color: #28a745; font-weight: 500;">✓ Approved</span>
                            </label>
                            
                            <label style="cursor: pointer;">
                                <input type="checkbox" name="status_rejected" value="1" 
                                       <?php echo isset($_GET['status_rejected']) || (!isset($_GET['status_approved']) && !isset($_GET['status_rejected'])) ? 'checked' : ''; ?>
                                       onchange="this.form.submit()">
                                <span style="color: #dc3545; font-weight: 500;">✗ Rejected</span>
                            </label>
                        </form>
                    </div>

                    <!-- Pagination Info -->
                    <?php if ($totalRecords > 0): ?>
                    <div class="pagination-info">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $totalRecords); ?> 
                        of <?php echo $totalRecords; ?> records
                    </div>
                    <?php endif; ?>
                    
                    <?php if (count($bookings_data) > 0): ?>
                    <div class="table-responsive">
                        <table class="booking-table" id="booking-records-table">
                            <thead>
                                <tr>
                                    <th>View</th>
                                    <th>No</th>
                                    <th>Name</th>
                                    <th>Facility</th>
                                    <th>Programme</th>
                                    <th>Department</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (count($bookings_data) > 0) {
                                    $rowNum = $offset + 1; 
                                    foreach ($bookings_data as $booking): 
                                ?>
                                <tr>
                                    <td class='actions-cell'>
                                        <a href='read-booking-report.php?id=<?php echo $booking['id']; ?>' class='action-btn view' title='View Record'>
                                            <span>&#128065;</span>
                                        </a>
                                    </td>
                                    <td class='row-number'><?php echo $rowNum; ?></td> 
                                    <td><strong><?php echo htmlspecialchars($booking['name']); ?></strong></td>
                                    <td class="facility-name">
                                        <?php echo getFacilityDisplayName($booking['facilityName']); ?>
                                    </td>
                                    <td class="programe-name"><?php echo htmlspecialchars($booking['purpose']); ?></td>
                                    <td class="depart"><?php echo htmlspecialchars($booking['depart']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($booking['select_date'])); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        switch ($booking['status']) {
                                            case 'Approved':
                                                $statusClass = 'status-approved';
                                                break;
                                            case 'Pending':
                                                $statusClass = 'status-pending';
                                                break;
                                            case 'Rejected':
                                                $statusClass = 'status-rejected';
                                                break;
                                        }
                                        ?>
                                        <span class="<?php echo $statusClass; ?>"></span>
                                    </td>
                                </tr>
                                <?php 
                                $rowNum++;
                                endforeach;
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
                            <a href="?page=1&<?php echo $url_params; ?>" title="First Page">&laquo;&laquo;</a>
                            <a href="?page=<?php echo $page - 1; ?>&<?php echo $url_params; ?>" title="Previous Page">&laquo;</a>
                        <?php else: ?>
                            <span class="disabled">&laquo;&laquo;</span>
                            <span class="disabled">&laquo;</span>
                        <?php endif; ?>
                        
                        <!-- Page Numbers -->
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        if ($startPage > 1) {
                            echo '<a href="?page=1&'.$url_params.'">1</a>';
                            if ($startPage > 2) {
                                echo '<span>...</span>';
                            }
                        }
                        
                        for ($i = $startPage; $i <= $endPage; $i++) {
                            if ($i == $page) {
                                echo '<span class="active">' . $i . '</span>';
                            } else {
                                echo '<a href="?page=' . $i . '&'.$url_params.'">' . $i . '</a>';
                            }
                        }
                        
                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<span>...</span>';
                            }
                            echo '<a href="?page=' . $totalPages . '&'.$url_params.'">' . $totalPages . '</a>';
                        }
                        ?>
                        
                        <!-- Next Button -->
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&<?php echo $url_params; ?>" title="Next Page">&raquo;</a>
                            <a href="?page=<?php echo $totalPages; ?>&<?php echo $url_params; ?>" title="Last Page">&raquo;&raquo;</a>
                        <?php else: ?>
                            <span class="disabled">&raquo;</span>
                            <span class="disabled">&raquo;&raquo;</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <div class="no-data-message">
                        <p style="font-size: 48px; margin-bottom: 10px;">📭</p>
                        <p style="font-size: 16px; font-weight: 600; color: #2d3748; margin-bottom: 8px;">
                            No booking records found
                        </p>
                        <?php if (!empty($search)): ?>
                        <p style="font-size: 14px; color: #718096;">
                            No results for "<strong><?php echo htmlspecialchars($search); ?></strong>" in the selected period.
                        </p>
                        <a href="?date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?><?php echo isset($_GET['status_approved']) ? '&status_approved=1' : ''; ?><?php echo isset($_GET['status_rejected']) ? '&status_rejected=1' : ''; ?>" 
                           style="display: inline-block; margin-top: 12px; padding: 8px 16px; background: #4299e1; color: white; text-decoration: none; border-radius: 6px; font-weight: 500; transition: all 0.3s;"
                           onmouseover="this.style.background='#3182ce'"
                           onmouseout="this.style.background='#4299e1'">
                            Clear Search Filter
                        </a>
                        <?php else: ?>
                        <p style="font-size: 14px; color: #718096;">
                            Try adjusting your date range or filters.
                        </p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Charts Section -->
                <div class="charts-section">
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>📈 Booking Status Distribution</h3>
                        </div>
                        <canvas id="statusChart"></canvas>
                    </div>

                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>🏢 Top 5 Active Departments</h3>
                        </div>
                        <canvas id="facilityChart"></canvas>
                    </div>
                </div>

                <!-- Trend Chart (Full Width) -->
                <div class="chart-container chart-full">
                    <div class="chart-header">
                        <h3>📊 Booking Trend (Last 6 Months)</h3>
                    </div>
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </main>
    </div>
    
    <!-- JAVASCRIPT - Data Preparation -->
    <script>
        // Prepare data for charts (to be used by report.js)
        const statusData = {
            labels: ['Approved', 'Pending', 'Rejected'],
            values: [
                <?php echo $status_data['Approved']['count'] ?? 0; ?>,
                <?php echo $status_data['Pending']['count'] ?? 0; ?>,
                <?php echo $status_data['Rejected']['count'] ?? 0; ?>
            ]
        };

        const departmentData = {
            labels: [
                <?php 
                foreach ($department_data as $d) {
                    echo "'" . addslashes(htmlspecialchars($d['depart'])) . "',";
                }
                ?>
            ],
            values: [
                <?php 
                foreach ($department_data as $d) {
                    echo $d['booking_count'] . ",";
                }
                ?>
            ]
        };
        
        const trendData = {
            labels: <?php echo json_encode($trend_months); ?>,
            values: <?php echo json_encode($trend_totals); ?>
        };

    </script>
    <script src="../assets/js/report.js"></script>
</body>
</html>