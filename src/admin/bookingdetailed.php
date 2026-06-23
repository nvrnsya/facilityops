<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include("../config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$facility = $_GET['facility'] ?? '';

if (empty($facility)) {
    header("Location: bookingpage.php");
    exit();
}

// Fetch facility data
$facilityData = null;
$facilitySource = null;

$stmt = $connect->prepare("SELECT facility_id, facility_name, image_path FROM facilities WHERE facility_slug = ? AND is_active = 1");
$stmt->bind_param("s", $facility);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $facilityData = $result->fetch_assoc();
    $facilitySource = 'ulpl';
}
$stmt->close();

if (!$facilityData) {
    $stmt = $connect->prepare("SELECT facility_id, facility_name, image_path FROM facilities_administration WHERE facility_slug = ? AND is_active = 1");
    $stmt->bind_param("s", $facility);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $facilityData = $result->fetch_assoc();
        $facilitySource = 'administration';
    }
    $stmt->close();
}

if (!$facilityData) {
    die("Facility not found or inactive.");
}

$facilityId = $facilityData['facility_id'];
$facilityName = $facilityData['facility_name'];
$mainImage = '../assets/images/' . $facilityData['image_path'];

// Fetch gallery images
$galleryImages = [$mainImage];
$galleryTable = ($facilitySource === 'administration') ? 'facility_gallery_administration' : 'facility_gallery';
$galleryStmt = $connect->prepare("SELECT image_path FROM {$galleryTable} WHERE facility_id = ? ORDER BY gallery_id ASC");
$galleryStmt->bind_param("i", $facilityId);
$galleryStmt->execute();
$galleryResult = $galleryStmt->get_result();
while ($row = $galleryResult->fetch_assoc()) {
    $galleryImages[] = '../assets/images/' . $row['image_path'];
}
$galleryStmt->close();

if (count($galleryImages) === 1) {
    $galleryImages[] = 'https://picsum.photos/800/400?random=2';
    $galleryImages[] = 'https://picsum.photos/800/400?random=3';
    $galleryImages[] = 'https://picsum.photos/800/400?random=4';
}

// Fetch user data
$sql = "SELECT * FROM users WHERE users_id='$user_id'";
$result = $connect->query($sql);
$user = $result->fetch_assoc();

$userData = [
    'name' => $user['name'] ?? '',
    'staffid' => $user['staffid'] ?? '',
    'phone_num' => $user['phone_num'] ?? '',
    'depart' => $user['depart'] ?? ''
];

// Fetch form fields from database
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
    <title><?php echo htmlspecialchars($facilityName); ?> | Booking Detail</title>
    <link rel="icon" href="../assets/images/favicon.png">
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/profile-menu.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="../assets/css/bookingdetailed.css">
</head>
<body>
    <script>
        window.FACILITY_SOURCE = '<?php echo $facilitySource; ?>';
    </script>

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
                            <li><a href="../logout.php">Sign out</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </header>
    </div>

    <section class="hero" id="hero-section">
        <h2 class="facility-title"><?php echo htmlspecialchars($facilityName); ?></h2>
        <div class="slider">
            <div class="slides">
                <?php foreach ($galleryImages as $index => $img): ?>
                    <div class="slide">
                        <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($facilityName); ?> - Image <?php echo $index+1; ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="dots">
            <?php foreach ($galleryImages as $index => $img): ?>
                <span class="dot<?php echo $index === 0 ? ' active' : ''; ?>" data-index="<?php echo $index; ?>"></span>
            <?php endforeach; ?>
        </div>
        <button class="hero-btn" onclick="document.getElementById('booking-form').scrollIntoView({behavior:'smooth'})">
            Start Booking
        </button>
    </section>

    <div class="step-indicator">
        <div class="step active" data-step="1">
            <div class="step-number">1</div>
            <div class="step-label">Booking Details</div>
        </div>
        <div class="step-line"></div>
        <div class="step" data-step="2">
            <div class="step-number">2</div>
            <div class="step-label">Review & Submit</div>
        </div>
    </div>

    <section class="booking-section" id="booking-form">

        <!-- DYNAMIC STANDARD FORM -->
        <div class="page-1" id="page1-standard">
            <h2>Book Your Facility</h2>
            <div class="booking-grid-3">
                <!-- Column 1: Booking Details -->
                <div class="column1">
                    <div class="form-container">
                        <h3>Booking Form</h3>
                        <input type="hidden" id="facilityName" value="<?php echo htmlspecialchars($facility); ?>">
                        
                        <!-- Dynamic fields will be injected here by JavaScript -->
                        <div id="booking-details-container"></div>
                
                        <div class="input-group">
                            <p>* Every Applicant/Organizer/program Secretary is OBLIGATED to maintain the cleanliness of the place and area and practice related savings.</p>
                        </div>
                    </div>
                </div>

                <!-- Column 2: Calendar -->
                <div class="column2">
                    <div class="calendar-container">
                        <h3>Select Date</h3>
                        <input type="text" id="big-calendar" name="select_date" placeholder="Choose a date" required>
                        
                         <div class="selected-dates-panel">
                            <h4>Selected Dates</h4>
                            <div id="selected-dates-list">
                                <p class="no-dates">No dates selected yet.</p>
                            </div>
                        </div>
                        
                        <div class="calendar-legend">
                            <div><span class="legend-box available"></span> Available</div>
                            <div>
                                <span class="legend-box partially-booked"></span>
                                <span>Partially Booked (some slots left)</span>
                            </div>
                            <div><span class="legend-box limited"></span> Holiday or Maintenance</div>
                            <div><span class="legend-box unavailable"></span> Unavailable</div>
                        </div>
                    </div>
                </div>

                <!-- Column 3: Key Handover -->
                <div class="column3">
                    <div class="handover-box">
                        <h3>Key's Handover</h3>
                        
                        <!-- Dynamic fields will be injected here by JavaScript -->
                        <div id="key-handover-container"></div>
                
                        <div class="input-group">
                            <p>* I hereby assume responsibility for all facilities used such as cleanliness, safety and savings throughout the period this key is lent to me.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="navigation-buttons">
                <button type="button" class="btn btn-secondary" onclick="window.history.back()">Back</button>
                <button type="button" class="btn btn-next" id="nextBtn">Next: Review Booking</button>
            </div>
        </div>

        <!-- Summary Page -->
        <div class="page-2" id="summary-page-standard" style="display: none;">
            <h2>Review Your Booking</h2>
            <div class="summary-container">
                <div class="summary-box">
                    <h3>Booking Details</h3>
                    <hr>
                    
                    <p><strong>Facility Name:</strong> <span id="summary-facility-std"><?php echo htmlspecialchars($facilityName ?? '—'); ?></span></p>
                    <p style="align-items: flex-start;">
                        <strong>Selected Dates & Time:</strong>
                        <span id="summary-dates-list" style="display:flex; flex-direction:column; gap:6px; background:transparent; border:none; padding:0;">—</span>
                    </p>
                    
                    <!-- Dynamic Booking Details Fields -->
                    <?php foreach ($groupedFields['booking_details'] as $field): ?>
                        <p>
                            <strong><?php echo htmlspecialchars($field['field_label']); ?>:</strong> 
                            <span id="summary-<?php echo htmlspecialchars($field['field_name']); ?>" class="summary-value">—</span>
                        </p>
                    <?php endforeach; ?>
        
                    <h3 style="margin-top: 30px;">Key's Handover</h3>
                    <hr>
                    
                    <!-- Dynamic Key Handover Fields -->
                    <?php foreach ($groupedFields['key_handover'] as $field): ?>
                        <p>
                            <strong><?php echo htmlspecialchars($field['field_label']); ?>:</strong> 
                            <span id="summary-<?php echo htmlspecialchars($field['field_name']); ?>" class="summary-value">—</span>
                        </p>
                    <?php endforeach; ?>
                </div>
            </div>
        
            <div class="navigation-buttons">
                <button type="button" class="btn btn-secondary" id="backBtn">Back to Edit</button>
                <button type="submit" class="btn btn-submit" id="submitBtn">Submit Booking</button>
            </div>
        </div>

        <!-- Hidden Form for Submission -->
        <form id="hiddenForm" action="process_booking.php" method="POST" style="display: none;">
            <input type="hidden" name="facilityName" id="form_facilityName" value="<?php echo htmlspecialchars($facilityName); ?>">
            <input type="hidden" name="programe_name" id="form_programeName">
            <input type="hidden" name="booking_dates" id="form_booking_dates">
            <input type="hidden" name="name" id="form_name">
            <input type="hidden" name="staffid" id="form_staffid">
            <input type="hidden" name="phone_num" id="form_phone_num">
            <input type="hidden" name="depart" id="form_depart">
            <input type="hidden" name="ext_office" id="form_office">
            <input type="hidden" name="add_notes" id="form_notes">
            <input type="hidden" name="recipient_name" id="form_recipientName">
            <input type="hidden" name="depart_key" id="form_DepartKey">
            <input type="hidden" name="tel_num" id="form_noTel">
            <input type="hidden" name="staff_key" id="form_noStaffKey">
            <input type="hidden" name="collect_date" id="form_collectDate">
            <input type="hidden" name="delivery_date" id="form_deliveryDate">
            
            <?php foreach ($formFields as $field): ?>
                <!--  ALWAYS without [] - JavaScript will send as comma-separated string -->
                <input type="hidden" name="<?php echo $field['field_name']; ?>" id="form_<?php echo $field['field_name']; ?>">
            <?php endforeach; ?>
        </form>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        window.userData = <?php echo json_encode($userData); ?>;
        console.log('👤 User data loaded:', window.userData);
    </script>
    <script src="../assets/js/bookingdetailed.js"></script>
</body>
</html>