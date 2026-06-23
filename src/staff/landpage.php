<?php
include("../config.php");
include("../auth.php");

// Fetch active sections
$sectionsQuery = "SELECT * FROM landpage_sections WHERE is_active = 1 ORDER BY section_order ASC";
$sectionsResult = $conn->query($sectionsQuery);
$activeSections = [];
while ($section = $sectionsResult->fetch_assoc()) {
    $activeSections[$section['section_name']] = true;
}

// Fetch all content
$contentQuery = "SELECT * FROM landpage_content";
$contentResult = $conn->query($contentQuery);
$pageContent = [];
while ($content = $contentResult->fetch_assoc()) {
    $pageContent[$content['section_name']][$content['content_key']] = $content['content_value'];
}

// Fetch facilities from ULPL only
$sql = "SELECT facility_id, facility_name, facility_slug, description, image_path
        FROM facilities 
        WHERE is_active = 1
        ORDER BY facility_name ASC";
$result = $conn->query($sql);

// Fetch Quick Access buttons
$buttonsQuery = "SELECT * FROM quick_access_buttons WHERE is_active = 1 ORDER BY button_order ASC";
$buttonsResult = $conn->query($buttonsQuery);

// Helper function to get content with fallback
function getContent($pageContent, $section, $key, $default = '') {
    return $pageContent[$section][$key] ?? $default;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/images/favicon.png">
    <title>Home | FacilityOps</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/profile-menu.css">
    <link rel="stylesheet" href="../assets/css/landpage.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />

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
                            <li><a href="edit-profile.php" >Edit Profile</a></li>
                           <li><a href="<?php echo $link; ?>logout.php">Sign out</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </header>
    </div>       

    <!-- MARQUEE SECTION -->
    <?php if (isset($activeSections['marquee'])): ?>
    <div class="marquee-container">
        <div class="marquee-content">
            <?php
            // Fetch ACTIVE announcements (started and not expired)
            $sql_ann = "SELECT title, start_date, 'announcement' as type 
                        FROM announcements 
                        WHERE status = 'active'
                        AND announcement_type = 'ulpl'
                        AND start_date <= CURDATE()
                        AND (end_date IS NULL OR end_date >= CURDATE())
                        ORDER BY start_date DESC 
                        LIMIT 5";


            // Fetch upcoming bookings
            $sql_book = "SELECT CONCAT(facilityName, ' - ', programe_name) as title, 
                                select_date as start_date, 
                                'booking' as type 
                        FROM ulpl 
                        WHERE status = 'Approved' 
                        AND select_date >= CURDATE() 
                        ORDER BY select_date ASC 
                        LIMIT 5";

            // Combine queries
            $sql_marquee = "($sql_ann) UNION ALL ($sql_book) ORDER BY start_date DESC LIMIT 10";
            $result_marquee = $conn->query($sql_marquee);

            $marquee_items = [];
            if ($result_marquee && $result_marquee->num_rows > 0) {
                while ($event = $result_marquee->fetch_assoc()) {
                    $title = htmlspecialchars($event['title']);
                    $date = date('d M Y', strtotime($event['start_date']));
                    
                    $icon = $event['type'] == 'announcement' ? '📢' : '🏢';
                    $link = $event['type'] == 'announcement' ? '#announcements' : '#upcoming';
                    
                    $marquee_items[] = [
                        'text' => "{$icon} {$title} - {$date}",
                        'link' => $link
                    ];
                }
            }

            // Display marquee content (duplicate for continuous scroll)
            for ($i = 0; $i < 2; $i++) {
                if (!empty($marquee_items)) {
                    foreach ($marquee_items as $index => $item) {
                        echo '<a href="' . $item['link'] . '" class="marquee-item">' . $item['text'] . '</a>';
                        echo '<span class="marquee-separator">•</span>';
                    }
                } else {
                    echo '<span class="marquee-item">📅 No upcoming events at the moment</span>';
                    echo '<span class="marquee-separator">•</span>';
                }
            }
            ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- HERO / MAIN CONTENT -->
    <?php if (isset($activeSections['hero'])): ?>
    <main>
        <h2><?php echo htmlspecialchars(getContent($pageContent, 'hero', 'title', 'Welcome to FacilityOps')); ?></h2>
        <p><?php echo htmlspecialchars(getContent($pageContent, 'hero', 'subtitle', 'Your one-stop solution for managing facilities efficiently.')); ?></p>
        <p><?php echo htmlspecialchars(getContent($pageContent, 'hero', 'description', 'Explore our services and book your facilities online.')); ?></p>
    </main>
    <?php endif; ?>

    <!-- QUICK ACCESS NAVIGATION -->
    <?php if (isset($activeSections['quick_nav']) && $buttonsResult->num_rows > 0): ?>
    <div class="quick-nav">
        <h3>Quick Access</h3>
        <div class="quick-nav-grid">
            <?php while ($button = $buttonsResult->fetch_assoc()): ?>
                <a href="<?php echo htmlspecialchars($button['button_link']); ?>" class="quick-nav-item">
                    <span class="icon"><?php echo htmlspecialchars($button['button_icon']); ?></span>
                    <span><?php echo htmlspecialchars($button['button_label']); ?></span>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- OVERVIEW FACILITY  -->
    <?php if (isset($activeSections['facilities'])): ?>
    <div class="slider" id="facilities">
        <div class="slides">
            <?php 
            if ($result && $result->num_rows > 0):
                $index = 0;
                while ($row = $result->fetch_assoc()):
                    $facilityName = htmlspecialchars($row['facility_name']);
                    $description = htmlspecialchars($row['description']);
                    $imagePath = htmlspecialchars($row['image_path']);
                    ?>
                    
                    <div class="slide">
                        <img src="../assets/images/<?php echo $imagePath; ?>" alt="<?php echo $facilityName; ?>">
                        
                        <div class="bottom-center">
                            <h3 class="luxury-heading"><?php echo $facilityName; ?></h3>
                            <p class="luxury-subtitle"><?php echo $description; ?></p>
                            <div class="buttons">
                                <a href="bookingpage.php?facility=<?php echo urlencode($facilityName); ?>" class="btn primary">Explore More</a>
                                <a href="bookingdetailed.php?facility=<?php echo urlencode($row['facility_slug']); ?>" class="btn secondary">Book Now</a>
                            </div>
                        </div>
                    </div>
    
                    <?php
                    $index++;
                endwhile;
            endif; 
            ?>
        </div>
    
        <div class="indicators">
            <?php for ($i = 0; $i < $index; $i++): ?>
                <span class="dot <?php echo ($i === 0) ? 'active' : ''; ?>" onclick="showSlide(<?php echo $i; ?>)"></span>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
 
    <!-- UPCOMING EVENT -->
    <?php if (isset($activeSections['upcoming'])): ?>
    <section class="upcoming-events" id="upcoming">
        <h2>Upcoming Events / Booked Facilities</h2>
        
        <?php
        function getUpcomingFacilityColor($facilityName) {
            $colors = [
                'Dewan Kuliah Utama' => '#ef4444',
                'Bilik Makan Bauk Inn' => '#8b5cf6',
                'Bilik Seminar' => '#10b981',
                'Bilik Kuliah 2' => '#f59e0b',
                'Puspanita' => '#ec4899'
            ];
            return $colors[$facilityName] ?? '#6b7280';
        }
        
        $today = date('Y-m-d');
        $sql_upcoming = "SELECT 
                            u.facilityName,
                            u.programe_name,
                            u.select_date,
                            u.start_time
                        FROM ulpl u
                        WHERE u.status = 'Approved' 
                        AND u.select_date >= '$today'
                        ORDER BY u.select_date ASC
                        LIMIT 6";
        
        $result_upcoming = $conn->query($sql_upcoming);
        
        if ($result_upcoming && $result_upcoming->num_rows > 0) {
            echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 15px;">';
            while ($booking = $result_upcoming->fetch_assoc()) {
                $facility = htmlspecialchars($booking['facilityName']);
                $program = htmlspecialchars($booking['programe_name']);
                $date = date('d M Y', strtotime($booking['select_date']));
                $time = $booking['start_time'] ? date('H:i', strtotime($booking['start_time'])) : 'TBA';
                $dayOfWeek = date('l', strtotime($booking['select_date']));
                $color = getUpcomingFacilityColor($facility);
                
                echo "<div style='background: white; border-left: 4px solid {$color}; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                        <div style='margin-bottom: 8px;'>
                            <strong style='color: #2d3748; font-size: 16px;'>{$facility}</strong>
                        </div>
                        <div style='color: #4a5568; font-size: 14px; margin-bottom: 5px;'>
                            📅 {$dayOfWeek}, {$date} • ⏰ {$time}
                        </div>
                        <div style='color: #718096; font-size: 14px;'>
                            📋 {$program}
                        </div>
                    </div>";
            }
            echo '</div>';
        } else {
            echo '<div style="text-align: center; padding: 40px; background: #f7fafc; border-radius: 8px;">
                    <p style="color: #a0aec0; font-size: 16px; margin: 0;">📅 No upcoming events scheduled at the moment.</p>
                </div>';
        }
        ?>
        
        <a href="bookingpage.php#calendar" class="btn" style="margin-top: 20px;">View All Bookings</a>
    </section>
    <?php endif; ?>

    <!-- ANNOUNCEMENTS / NOTICES -->
    <?php if (isset($activeSections['announcements'])): ?>
    <section class="announcements" id="announcements">
        <h2>Announcements / Notices</h2>
        <?php
        $today = date('Y-m-d');
        $sql_announcements = "SELECT 
                        announcement_id,
                        title,
                        content,
                        start_date,
                        end_date
                    FROM announcements 
                    WHERE status = 'active'
                    AND announcement_type = 'ulpl'
                    AND start_date <= '$today'
                    AND (end_date IS NULL OR end_date >= '$today')
                    ORDER BY start_date DESC 
                    LIMIT 10";

        $result_announcements = $conn->query($sql_announcements);
        
        if ($result_announcements && $result_announcements->num_rows > 0) {
            while ($announcement = $result_announcements->fetch_assoc()) {
                $icon = (strpos(strtolower($announcement['content']), 'maintenance') !== false) ? '⚠️' : 'ℹ️';
                $startDate = date('d M Y', strtotime($announcement['start_date']));
                $endDate = $announcement['end_date'] ? date('d M Y', strtotime($announcement['end_date'])) : 'Ongoing';
                ?>
                 <div class="notice">
                    <p style="margin: 0 0 8px 0;">
                        <?php echo $icon; ?> <strong><?php echo htmlspecialchars($announcement['title']); ?></strong>
                    </p>
                    <p style="margin: 8px 0;">
                        <?php echo htmlspecialchars($announcement['content']); ?>
                    </p>
                    <small style="color: #718096; font-size: 13px;">
                        📅 <strong>From:</strong> <?php echo $startDate; ?> 
                        <strong>To:</strong> <?php echo $endDate; ?>
                    </small>
                </div>
                <?php
            }
        } else {
            ?>
            <div class="notice">
                <p>ℹ️ No announcements at the moment.</p>
            </div>
            <?php
        }
        ?>
    </section>
    <?php endif; ?>

    <!-- STEP-BY-STEP GUIDE -->
    <?php if (isset($activeSections['how_to_book'])): ?>
    <section class="how-it-works" id="how-to-book">
        <h2><?php echo htmlspecialchars(getContent($pageContent, 'how_to_book', 'title', 'How to Book')); ?></h2>
        <div class="steps">
            <div class="step">
                <span>1️⃣</span>
                <p><?php echo htmlspecialchars(getContent($pageContent, 'how_to_book', 'step1', 'Browse or search for facilities')); ?></p>
            </div>
            <div class="step">
                <span>2️⃣</span>
                <p><?php echo htmlspecialchars(getContent($pageContent, 'how_to_book', 'step2', 'Select your date and time')); ?></p>
            </div>
            <div class="step">
                <span>3️⃣</span>
                <p><?php echo htmlspecialchars(getContent($pageContent, 'how_to_book', 'step3', 'Fill in booking details')); ?></p>
            </div>
            <div class="step">
                <span>4️⃣</span>
                <p><?php echo htmlspecialchars(getContent($pageContent, 'how_to_book', 'step4', 'Submit & wait for approval')); ?></p>
            </div>
        </div>
        
        <div class="how-to-book-footer">
            <p><?php echo htmlspecialchars(getContent($pageContent, 'how_to_book', 'footer_text', 'Need more help? Check out our FAQ section or documentation')); ?></p>
            <a href="FAQpage.php#documentation-card" class="faq-link-btn">View Docs</a>
        </div>
    </section>
    <?php endif; ?>

    <!-- ABOUT US -->
    <?php if (isset($activeSections['about_us'])): ?>
    <section class="about-us" id="about-us">
        <h2><?php echo htmlspecialchars(getContent($pageContent, 'about_us', 'title', 'About Us')); ?></h2>
        <p><?php echo htmlspecialchars(getContent($pageContent, 'about_us', 'content', 'FacilityOps operates on a first come, first served basis, ensuring fair and transparent access to all users. Managed under the Unit Latihan Pentadbiran Lanjutan (ULPL), FacilityOps provides a convenient platform for users to easily book and manage the facilities they need. Our goal is to streamline the booking process and enhance the overall experience for both administrative staff and external users at Politeknik Sultan Mizan Zainal Abidin.')); ?></p>
    </section>
    <?php endif; ?>

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

    <!-- JAVASCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script src="../assets/js/landpage.js"></script>

</body>
</html>