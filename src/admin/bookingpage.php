<?php
include("../config.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if user has email
$sql = "SELECT email FROM users WHERE users_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$hasEmail = !empty($user['email']);

// Fetch facilities from facilities table only
$sql = "SELECT facility_id, facility_name, facility_slug, description, image_path
        FROM facilities 
        WHERE is_active = 1
        ORDER BY facility_name ASC";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/images/favicon.png">
    <title>Booking Page | FacilityOps</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/profile-menu.css">
    <link rel="stylesheet" href="../assets/css/bookingpage.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/main.min.css" rel="stylesheet">
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
                            <li><a href="<?php echo $link; ?>logout.php">Sign out</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </header>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <h2>All Facilities</h2>
        <p>Select a facility to view details and make a booking.</p>
        <ul class="container">
            <?php 
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $facilityId = isset($row['facility_id']) ? htmlspecialchars($row['facility_id']) : '';
                    $facilityName = isset($row['facility_name']) ? htmlspecialchars($row['facility_name']) : '';
                    $facilitySlug = isset($row['facility_slug']) ? htmlspecialchars($row['facility_slug']) : '';
                    $description = isset($row['description']) ? htmlspecialchars($row['description']) : '';
                    $imagePath = isset($row['image_path']) ? htmlspecialchars($row['image_path']) : '';
                    ?>
                    <li data-id="<?php echo $facilitySlug; ?>" data-name="<?php echo $facilityName; ?>">
                        <img src="../assets/images/<?php echo $imagePath; ?>" alt="<?php echo $facilityName; ?>">
                        <div class="content">
                            <span>
                                <h2><?php echo $facilityName; ?></h2>
                                <p><?php echo $description; ?></p>
                            </span>
                        </div>
                    </li>
                    <?php
                }
            } else {
                echo '<li style="text-align: center; padding: 40px; width: 100%;">';
                echo '<p style="color: #666; font-size: 18px;">No facilities available at the moment.</p>';
                echo '</li>';
            }
            ?>
        </ul>

        <button type="button" class="btn">Confirm</button>
    </div>
    
    <!-- CALENDAR SECTION -->
    <div style="max-width: 1300px; margin: 40px auto; padding: 0 20px;">
        <div style="background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 25px;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #e2e8f0;">
                
                <div>
                    <label for="facilityFilter" style="font-weight: 600; margin-right: 10px; color: #2d3748; font-size: 15px;">Filter:</label>
                    <select id="facilityFilter" style="padding: 8px 12px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 14px;">
                        <option value="">All Facilities</option>
                    </select>
                </div>
                
                <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <span style="font-weight: 600; color: #2d3748; font-size: 14px;">Hint : </span>
                    <div id="legendContainer" style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
            </div>

            <div id="calendar"></div>
            
            <p style="margin: 15px 0 0 0; font-size: 12px; color: #718096; text-align: center; font-style: italic;">
                * Calendar shows only approved bookings
            </p>
        </div>
    </div>

    <!-- FOOTER -->
    <?php if (isset($activeSections['footer'])): ?>
    <footer class="footer-contact">
        <div class="footer-contact-content">
            <img src="../assets/images/polilogo.png" alt="Poli Logo">
            <p><?php echo htmlspecialchars(getContent($pageContent, 'footer', 'address', 'Km 08, Jalan Paka, 23000 Kuala Dungun, Terengganu')); ?></p>
            <p><a href="<?php echo htmlspecialchars(getContent($pageContent, 'footer', 'website', 'https://psmza.mypolycc.edu.my/')); ?>" target="_blank"><?php echo htmlspecialchars(getContent($pageContent, 'footer', 'website', 'https://psmza.mypolycc.edu.my/')); ?></a></p>
            <p>Tel: <?php echo htmlspecialchars(getContent($pageContent, 'footer', 'phone', '09-8400800')); ?></p>
            <p>Fax: <?php echo htmlspecialchars(getContent($pageContent, 'footer', 'fax', '09-8458781')); ?></p>
            <p class="disclaimer">Disclaimer</p>
            <p><?php echo htmlspecialchars(getContent($pageContent, 'footer', 'office_hours', '8:00 AM - 5:00 PM (Monday - Thursday)')); ?></p>
            <p class="notice"><?php echo htmlspecialchars(getContent($pageContent, 'footer', 'notice', 'For urgent matters regarding space or reservation, please contact the Unit who owns the space')); ?></p>
        </div>
        
        <div class="footer-copyright">
            <p><?php echo htmlspecialchars(getContent($pageContent, 'footer', 'copyright', '© 2025 FacilityOps | Designed by Team Toman | All rights reserved.')); ?></p>
        </div>
    </footer>
    <?php endif; ?>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
    <script src="../assets/js/bookingpage.js"></script>
    <script>
        const userHasEmail = <?php echo $hasEmail ? 'true' : 'false'; ?>;
    </script>
    <script>
    // Smooth scroll to calendar when URL has #calendar hash
    document.addEventListener('DOMContentLoaded', function() {
        // Check if URL has #calendar
        if (window.location.hash === '#calendar') {
            setTimeout(function() {
                const calendarSection = document.getElementById('calendar');
                if (calendarSection) {
                    const yOffset = -100; // Offset for header
                    const y = calendarSection.getBoundingClientRect().top + window.pageYOffset + yOffset;
                    
                    window.scrollTo({
                        top: y,
                        behavior: 'smooth'
                    });
                }
            }, 300); // Delay to ensure page fully loaded
        }
    });
    </script>
    
</body>
</html>