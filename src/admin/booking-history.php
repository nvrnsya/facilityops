<?php
include("../config.php");

if (!$connect) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Get pending bookings count for notification badge
$pendingCountQuery = "SELECT COUNT(*) as pending_count FROM ulpl WHERE status = 'Pending'";
$pendingResult = mysqli_query($connect, $pendingCountQuery);
$pendingCount = 0;

if ($pendingResult) {
    $pendingRow = mysqli_fetch_assoc($pendingResult);
    $pendingCount = $pendingRow['pending_count'];
}

// Function untuk map facility name
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

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Pagination setup
$limit = 10; // Records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Ensure page is at least 1
$offset = ($page - 1) * $limit;

// Build WHERE clause - Only show Approved/Rejected status
$whereClause = "WHERE b.status IN ('Approved', 'Rejected')";

// Add search condition
if (!empty($search)) {
    $searchEscaped = mysqli_real_escape_string($connect, $search);
    $whereClause .= " AND (
        u.name LIKE '%$searchEscaped%' OR
        b.facilityName LIKE '%$searchEscaped%' OR
        b.programe_name LIKE '%$searchEscaped%' OR
        b.depart LIKE '%$searchEscaped%' OR
        b.ulpl_id LIKE '%$searchEscaped%' OR
        b.status LIKE '%$searchEscaped%'
    )";
}

// Add date range condition
if (!empty($date_from) && !empty($date_to)) {
    $whereClause .= " AND b.select_date BETWEEN '$date_from' AND '$date_to'";
} elseif (!empty($date_from)) {
    $whereClause .= " AND b.select_date >= '$date_from'";
} elseif (!empty($date_to)) {
    $whereClause .= " AND b.select_date <= '$date_to'";
}

// Get total count for pagination
$countQuery = "
    SELECT COUNT(*) as total 
    FROM ulpl b
    JOIN users u ON b.users_id = u.users_id
    $whereClause
";
$countResult = mysqli_query($connect, $countQuery);
$totalRecords = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalRecords / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/images/favicon.png">
    <title>Booking History | FacilityOps</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/profile-menu.css">
    <link rel="stylesheet" href="../assets/css/booking-history.css">  <!-- load LAST -->
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
            <h2>Booking Dashboard</h2>
            <section id="booking-history">
                <h3>Booking History</h3>
                
                <!-- Search and Date Filter -->
                <form class="date-filter" method="GET" action="">
                    <div class="date-inputs">
                        <div class="search-box">
                            <label for="search">Search</label>
                            <input type="text" id="search" name="search" placeholder="Search ID, name, facility, programme, status..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div>
                            <label for="date_from">From</label>
                            <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div>
                            <label for="date_to">To</label>
                            <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Apply</button>
                        <?php if (!empty($search) || !empty($date_from) || !empty($date_to)): ?>
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='booking-history.php'">Reset</button>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($date_from) || !empty($date_to)): ?>
                    <div class="date-range-info">
                        <?php if (!empty($date_from) && !empty($date_to)): ?>
                            Showing data from <strong><?php echo date('d-m-Y', strtotime($date_from)); ?></strong> to <strong><?php echo date('d-m-Y', strtotime($date_to)); ?></strong>
                        <?php elseif (!empty($date_from)): ?>
                            Showing data from <strong><?php echo date('d-m-Y', strtotime($date_from)); ?></strong> onwards
                        <?php elseif (!empty($date_to)): ?>
                            Showing data up to <strong><?php echo date('d-m-Y', strtotime($date_to)); ?></strong>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </form>
                
                <!-- Search Results Info -->
                <?php if (!empty($search)): ?>
                <div class="pagination-info" style="margin-bottom: 10px;">
                    Search results for "<strong><?php echo htmlspecialchars($search); ?></strong>" - Found <?php echo $totalRecords; ?> record(s)
                </div>
                <?php endif; ?>
                
                <!-- Pagination Info -->
                <?php if ($totalRecords > 0): ?>
                <div class="pagination-info">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $totalRecords); ?> 
                    of <?php echo $totalRecords; ?> records
                </div>
                <?php endif; ?>
                
                <div class="table-container">
                    <table class="booking-table" id="booking-history-table">
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
                        <tbody id="booking-history-tbody">
                            <?php
                            $query = "
                                SELECT 
                                    b.ulpl_id, 
                                    u.name, 
                                    b.facilityName, 
                                    b.programe_name, 
                                    COALESCE(NULLIF(b.depart, ''), u.depart, '-') as depart,
                                    b.select_date,
                                    b.status
                                FROM ulpl b
                                JOIN users u ON b.users_id = u.users_id
                                $whereClause
                                ORDER BY b.select_date DESC
                                LIMIT $limit OFFSET $offset
                            ";
                            $result = mysqli_query($connect, $query);

                            if (mysqli_num_rows($result) > 0) {
                                $rowNum = $offset + 1; 
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $statusClass = match(strtolower($row['status'])) {
                                        'approved' => 'status-approved',
                                        'rejected' => 'status-rejected',
                                        default => 'status-pending',
                                    };

                                    echo "
                                    <tr>
                                        <td class='actions-cell'>
                                            <a href='read-booking-history.php?id={$row['ulpl_id']}' class='action-btn view' title='View Record'>
                                                <span>&#128065;</span>
                                            </a>
                                        </td>
                                        <td class='row-number'>{$rowNum}</td>
                                        <td>{$row['name']}</td>
                                        <td class='facility-name'>" . getFacilityDisplayName($row['facilityName']) . "</td>
                                        <td class='programe-name'>{$row['programe_name']}</td>
                                        <td class='depart'>{$row['depart']}</td>
                                        <td>{$row['select_date']}</td>
                                        <td><span class='{$statusClass}' title='{$row['status']}'></span></td>
                                    </tr>";
                                    $rowNum++; 
                                }
                            } else {
                                $noDataMessage = (!empty($search) || !empty($date_from) || !empty($date_to)) ? "No records found matching your criteria." : "No booking history found";
                                echo "<tr><td colspan='8'>$noDataMessage</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination Controls -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php
                    $params = [];
                    if (!empty($search)) $params[] = "search=" . urlencode($search);
                    if (!empty($date_from)) $params[] = "date_from=" . urlencode($date_from);
                    if (!empty($date_to)) $params[] = "date_to=" . urlencode($date_to);
                    $queryString = !empty($params) ? "&" . implode("&", $params) : "";
                    ?>
                    <!-- Previous Button -->
                    <?php if ($page > 1): ?>
                        <a href="?page=1<?php echo $queryString; ?>" title="First Page">&laquo;&laquo;</a>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $queryString; ?>" title="Previous Page">&laquo;</a>
                    <?php else: ?>
                        <span class="disabled">&laquo;&laquo;</span>
                        <span class="disabled">&laquo;</span>
                    <?php endif; ?>
                    
                    <!-- Page Numbers -->
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    if ($startPage > 1) {
                        echo '<a href="?page=1' . $queryString . '">1</a>';
                        if ($startPage > 2) {
                            echo '<span>...</span>';
                        }
                    }
                    
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        if ($i == $page) {
                            echo '<span class="active">' . $i . '</span>';
                        } else {
                            echo '<a href="?page=' . $i . $queryString . '">' . $i . '</a>';
                        }
                    }
                    
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<span>...</span>';
                        }
                        echo '<a href="?page=' . $totalPages . $queryString . '">' . $totalPages . '</a>';
                    }
                    ?>
                    
                    <!-- Next Button -->
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $queryString; ?>" title="Next Page">&raquo;</a>
                        <a href="?page=<?php echo $totalPages; ?><?php echo $queryString; ?>" title="Last Page">&raquo;&raquo;</a>
                    <?php else: ?>
                        <span class="disabled">&raquo;</span>
                        <span class="disabled">&raquo;&raquo;</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- JAVASCRIPT -->
    <script src="../assets/js/admin.js"></script>
</body>
</html>