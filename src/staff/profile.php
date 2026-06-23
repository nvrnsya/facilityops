<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include("../config.php");

// Function untuk map facility name - ULPL ONLY
function getFacilityDisplayName($facilityName) {
    $facilityMap = [
        'dewan-kuliah-utama' => 'Dewan Kuliah Utama',
        'bilik-makan-bauk-inn' => 'Bilik Makan Bauk Inn',
        'bilik-seminar' => 'Bilik Seminar',
        'bilik-kuliah-2' => 'Bilik Kuliah 2',
        'puspanita' => 'Puspanita'
    ];
    
    return isset($facilityMap[$facilityName]) ? $facilityMap[$facilityName] : ucwords(str_replace('-', ' ', $facilityName));
}

// Pastikan user dah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch data user dari table users
$sql = "SELECT * FROM users WHERE users_id = ?";
$stmt_user = $connect->prepare($sql);

if (!$stmt_user) {
    die("Prepare failed: " . $connect->error);
}

$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows === 0) {
    die("User not found");
}

$user = $result_user->fetch_assoc();
$stmt_user->close();

// Check if email is empty
$hasEmail = !empty($user['email']);

// Pagination setup
$limit = 5; // Records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Ensure page is at least 1
$offset = ($page - 1) * $limit;

// Get total count for pagination
$countQuery = "
    SELECT COUNT(*) as total 
    FROM ulpl 
    WHERE users_id = ?
";
$countStmt = $connect->prepare($countQuery);
$countStmt->bind_param("i", $user_id);
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);
$countStmt->close();

// Fetch booking history - ULPL ONLY (no Dewan Dagang)
$query = "
    SELECT 
        ulpl_id as booking_id,
        'ulpl' as booking_type,
        facilityName as display_name,
        programe_name as event_name,
        select_date as booking_date,
        status
    FROM ulpl 
    WHERE users_id = ?
    ORDER BY booking_date DESC, booking_id DESC
    LIMIT ? OFFSET ?
";

$stmt = $connect->prepare($query);

if (!$stmt) {
    die("Prepare failed: " . $connect->error);
}

$stmt->bind_param("iii", $user_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/images/favicon.png">
    <title>Profile | FacilityOps</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/profile-menu.css">
    <link rel="stylesheet" href="../assets/css/profile.css"> 
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
                    <!-- Profile Dropdown -->
                    <li class="menu">
                        <a href="profile.php">Profile</a>
                        <ul class="submenu">
                            <li><a href="edit-profile.php">Edit Profile</a></li>
                            <li><a href="../logout.php">Sign out</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </header>
    </div>

    <!-- MAIN CONTENT -->
    <h2>Profile</h2>
    <div class="container">
        <div class="card">
            <h3>USER INFORMATION</h3>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" disabled readonly>
            </div>
            
            <div class="form-group">
                <label>NRIC Number</label>
                <input type="text" name="icnum" value="<?php echo htmlspecialchars($user['icnum']); ?>" disabled readonly>
            </div>

            <div class="form-group">
                <label>Staff ID<span>*</span></label>
                <input type="text" name="staffid" value="<?php echo htmlspecialchars($user['staffid']); ?>" disabled readonly>
            </div>

            <div class="form-group">
                <label>Email Address<span>*</span></label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled readonly>
            </div>

            <div class="form-group">
                <label>Phone Number<span>*</span></label>
                <input type="text" name="phone_num" value="<?php echo htmlspecialchars($user['phone_num']); ?>" disabled readonly>
            </div>

            <div class="form-group">
                <label>Department<span>*</span></label>
                <input type="text" name="depart" value="<?php echo htmlspecialchars($user['depart']); ?>" disabled readonly>
            </div>

            <div class="form-group">
                <label>Role<span>*</span></label>
                <input type="text" name="role" value="<?php echo htmlspecialchars($user['role']); ?>" disabled readonly>
            </div>
        </div>

        <div class="card">
            <h3>BOOKING HISTORY</h3>
            <section id="booking-history">
                <table class="booking-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Facility</th>
                            <th>Programme</th>
                            <th>Date</th>
                            <th>Action</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            if ($result->num_rows > 0) {
                                $rowNum = $offset + 1; 
                                while ($row = $result->fetch_assoc()): 
                        ?>
                            <tr>
                                <td><?php echo $rowNum; ?></td>
                                <td class="facility-name">
                                    <?php echo htmlspecialchars(getFacilityDisplayName($row['display_name'])); ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['event_name']); ?></td>
                                <td class="booking-date"><?php echo htmlspecialchars($row['booking_date']); ?></td>
                                <td class='actions-cell'>
                                    <a href='read-booking.php?id=<?php echo urlencode($row['booking_id']); ?>&type=<?php echo urlencode($row['booking_type']); ?>' class='action-btn view' title='View Record'>
                                        <span>&#128065;</span>
                                    </a>
                                    
                                    <?php if (strtolower($row['status']) === 'pending'): ?>
                                        <a href='edit-booking.php?id=<?php echo urlencode($row['booking_id']); ?>&type=<?php echo urlencode($row['booking_type']); ?>' class='action-btn edit' title='Update Record'>
                                            <span>&#9998;</span>
                                        </a>
                                        <a href='delete-booking.php?id=<?php echo urlencode($row['booking_id']); ?>&type=<?php echo urlencode($row['booking_type']); ?>' class='action-btn delete' title='Delete Record' onclick="return confirm('Are you sure you want to delete this record?');">
                                            <span>&#128465;</span>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $statusClass = strtolower(htmlspecialchars($row['status']));
                                        echo '<span class="status-' . $statusClass . '" title="' . htmlspecialchars($row['status']) . '"></span>';
                                    ?>
                                </td>
                            </tr>
                        <?php 
                                $rowNum++;
                                endwhile;
                            } else {
                                echo "<tr><td colspan='6' style='text-align: center;'>No booking history found</td></tr>";
                            }
                        ?>
                    </tbody>
                </table>
                
                <!-- Pagination Info -->
                <?php if ($totalRecords > 0): ?>
                <div class="pagination-info">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $totalRecords); ?> 
                    of <?php echo $totalRecords; ?> records
                </div>
                <?php endif; ?>
                
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
        </div>
    </div>

    <script>
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
<?php
$stmt->close();
$connect->close();
?>