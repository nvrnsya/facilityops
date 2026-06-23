<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config.php");

// Make sure user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$message = '';
$messageType = '';

// Get pending bookings count for notification badge
$pendingCountQuery = "SELECT COUNT(*) as pending_count FROM ulpl WHERE status = 'Pending'";
$pendingResult = mysqli_query($connect, $pendingCountQuery);
$pendingCount = 0;

if ($pendingResult) {
    $pendingRow = mysqli_fetch_assoc($pendingResult);
    $pendingCount = $pendingRow['pending_count'];
}

/* ===========================
   HANDLE AJAX REQUESTS
   =========================== */
if (isset($_POST['ajax']) && $_POST['ajax'] === 'true') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    // Toggle section status
    if ($action === 'toggle_section') {
        $sectionId = intval($_POST['section_id'] ?? 0);
        $newStatus = intval($_POST['status'] ?? 0);
        
        $stmt = $conn->prepare("UPDATE landpage_sections SET is_active=? WHERE section_id=?");
        $stmt->bind_param("ii", $newStatus, $sectionId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Section status updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
        $stmt->close();
        exit();
    }
    
    // Reorder sections
    if ($action === 'reorder') {
        $orders = json_decode($_POST['orders'], true);
        
        foreach ($orders as $sectionId => $order) {
            $stmt = $conn->prepare("UPDATE landpage_sections SET section_order=? WHERE section_id=?");
            $stmt->bind_param("ii", $order, $sectionId);
            $stmt->execute();
            $stmt->close();
        }
        
        echo json_encode(['success' => true, 'message' => 'Order updated successfully']);
        exit();
    }
}

/* ===========================
   HANDLE CONTENT UPDATES
   =========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax'])) {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_content') {
        $sectionName = $_POST['section_name'] ?? '';
        $contentData = $_POST['content'] ?? [];

        $success = true;
        foreach ($contentData as $key => $value) {
            // Check if content exists
            $checkStmt = $conn->prepare("SELECT content_id FROM landpage_content WHERE section_name=? AND content_key=?");
            $checkStmt->bind_param("ss", $sectionName, $key);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing
                $updateStmt = $conn->prepare("UPDATE landpage_content SET content_value=? WHERE section_name=? AND content_key=?");
                $updateStmt->bind_param("sss", $value, $sectionName, $key);
                if (!$updateStmt->execute()) {
                    $success = false;
                }
                $updateStmt->close();
            } else {
                // Insert new
                $insertStmt = $conn->prepare("INSERT INTO landpage_content (section_name, content_key, content_value, content_type) VALUES (?, ?, ?, 'text')");
                $insertStmt->bind_param("sss", $sectionName, $key, $value);
                if (!$insertStmt->execute()) {
                    $success = false;
                }
                $insertStmt->close();
            }
            $checkStmt->close();
        }

        if ($success) {
            $message = "Content updated successfully!";
            $messageType = 'success';
        } else {
            $message = "Failed to update some content.";
            $messageType = 'error';
        }
    }
}

/* ===========================
   FETCH SECTIONS & CONTENT
   =========================== */
$sections = mysqli_query($conn, "SELECT * FROM landpage_sections ORDER BY section_order ASC, section_id ASC");

// Fetch all content
$contentQuery = mysqli_query($conn, "SELECT * FROM landpage_content ORDER BY section_name, content_key");
$contentData = [];
while ($row = mysqli_fetch_assoc($contentQuery)) {
    $contentData[$row['section_name']][$row['content_key']] = $row['content_value'];
}

// Get edit section
$editSection = $_GET['edit_section'] ?? null;
$editSectionData = null;
if ($editSection) {
    $editSectionData = $contentData[$editSection] ?? [];
}

// Check for session messages
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'] ?? 'success';
    unset($_SESSION['message'], $_SESSION['messageType']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/images/favicon.png">
    <title>Manage Home Page | FacilityOps</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/profile-menu.css">
    <link rel="stylesheet" href="../assets/css/manage-facilities.css">

    <style>
        .section-preview {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            border-left: 4px solid #3b82f6;
        }
        .section-preview h4 {
            margin: 0 0 10px 0;
            color: #1e293b;
            font-size: 14px;
        }
        .section-preview p {
            margin: 5px 0;
            color: #64748b;
            font-size: 13px;
        }
        .edit-content-form {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .content-field {
            margin-bottom: 20px;
        }
        .content-field label {
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .content-field input[type="text"],
        .content-field textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }
        .content-field textarea {
            min-height: 100px;
            resize: vertical;
        }
        .steps-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        @media (max-width: 768px) {
            .steps-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
            <h2>🎨 Home Page Settings</h2>
            <p style="color: #6b7280; margin-bottom: 20px;">Customize your landing page sections and content. Toggle sections on/off, edit content, or reorder sections by dragging.</p>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo $messageType === 'success' ? '✓' : '✗'; ?> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- SECTIONS LIST -->
            <section id="sections-list">
                <h3>Home Page Sections</h3>
                <div class="table-container">
                    <table class="facilities-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">⋮⋮</th>
                                <th>Section Name</th>
                                <th>Description</th>
                                <th style="text-align: center;">Status</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="sortableSections">
                            <?php if (mysqli_num_rows($sections) > 0): ?>
                                <?php while ($section = mysqli_fetch_assoc($sections)): ?>
                                    <tr data-section-id="<?php echo $section['section_id']; ?>">
                                        <td class="drag-handle" title="Drag to reorder">⋮⋮</td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($section['section_label']); ?></strong>
                                            <br>
                                            <small style="color: #94a3b8;"><?php echo htmlspecialchars($section['section_name']); ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                            $descriptions = [
                                                'marquee' => 'Scrolling announcements and upcoming events banner',
                                                'hero' => 'Main welcome message and introduction',
                                                'facilities' => 'Carousel showcasing available facilities',
                                                'upcoming' => 'List of upcoming events and bookings',
                                                'announcements' => 'Important notices and announcements',
                                                'how_to_book' => 'Step-by-step booking guide',
                                                'about_us' => 'Information about FacilityOps',
                                                'footer' => 'Contact information and footer details'
                                            ];
                                            echo '<span style="color: #64748b; font-size: 14px;">' . ($descriptions[$section['section_name']] ?? 'Section content') . '</span>';
                                            ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <label class="toggle-switch">
                                                <input 
                                                    type="checkbox" 
                                                    class="status-toggle-ajax"
                                                    <?php echo $section['is_active'] ? 'checked' : ''; ?>
                                                    data-section-id="<?php echo $section['section_id']; ?>">
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </td>
                                        <td>
                                            <div class="actions-cell">
                                                <?php if (in_array($section['section_name'], ['hero', 'about_us', 'how_to_book', 'footer'])): ?>
                                                    <a href="?edit_section=<?php echo $section['section_name']; ?>" 
                                                       class="action-btn edit" 
                                                       title="Edit Content">
                                                        <span>&#9998;</span>
                                                    </a>
                                                <?php else: ?>
                                                    <span style="color: #cbd5e1; font-size: 12px;">Auto-generated</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 60px 20px;">
                                        <div style="color: #718096; font-size: 16px;">
                                            <span style="font-size: 48px; display: block; margin-bottom: 16px;">📄</span>
                                            <strong>No sections found</strong>
                                            <p style="margin-top: 8px; font-size: 14px;">Please run the SQL setup first.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- EDIT CONTENT FORM -->
            <?php if ($editSection): ?>
                <section id="edit-content">
                    <div class="form-section-title">
                        <h3>✏️ Edit <?php echo ucwords(str_replace('_', ' ', $editSection)); ?> Content</h3>
                    </div>
                    
                    <div class="edit-content-form">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_content">
                            <input type="hidden" name="section_name" value="<?php echo htmlspecialchars($editSection); ?>">

                            <?php if ($editSection === 'hero'): ?>
                                <div class="content-field">
                                    <label>Title</label>
                                    <input type="text" name="content[title]" value="<?php echo htmlspecialchars($editSectionData['title'] ?? 'Welcome to FacilityOps'); ?>" required>
                                </div>
                                <div class="content-field">
                                    <label>Subtitle</label>
                                    <input type="text" name="content[subtitle]" value="<?php echo htmlspecialchars($editSectionData['subtitle'] ?? 'Your one-stop solution for managing facilities efficiently.'); ?>" required>
                                </div>
                                <div class="content-field">
                                    <label>Description</label>
                                    <input type="text" name="content[description]" value="<?php echo htmlspecialchars($editSectionData['description'] ?? 'Explore our services and book your facilities online.'); ?>" required>
                                </div>

                            <?php elseif ($editSection === 'about_us'): ?>
                                <div class="content-field">
                                    <label>Section Title</label>
                                    <input type="text" name="content[title]" value="<?php echo htmlspecialchars($editSectionData['title'] ?? 'About Us'); ?>" required>
                                </div>
                                <div class="content-field">
                                    <label>Content</label>
                                    <textarea name="content[content]" required><?php echo htmlspecialchars($editSectionData['content'] ?? ''); ?></textarea>
                                </div>

                            <?php elseif ($editSection === 'how_to_book'): ?>
                                <div class="content-field">
                                    <label>Section Title</label>
                                    <input type="text" name="content[title]" value="<?php echo htmlspecialchars($editSectionData['title'] ?? 'How to Book'); ?>" required>
                                </div>
                                <div class="steps-group">
                                    <div class="content-field">
                                        <label>Step 1</label>
                                        <input type="text" name="content[step1]" value="<?php echo htmlspecialchars($editSectionData['step1'] ?? 'Browse or search for facilities'); ?>" required>
                                    </div>
                                    <div class="content-field">
                                        <label>Step 2</label>
                                        <input type="text" name="content[step2]" value="<?php echo htmlspecialchars($editSectionData['step2'] ?? 'Select your date and time'); ?>" required>
                                    </div>
                                    <div class="content-field">
                                        <label>Step 3</label>
                                        <input type="text" name="content[step3]" value="<?php echo htmlspecialchars($editSectionData['step3'] ?? 'Fill in booking details'); ?>" required>
                                    </div>
                                    <div class="content-field">
                                        <label>Step 4</label>
                                        <input type="text" name="content[step4]" value="<?php echo htmlspecialchars($editSectionData['step4'] ?? 'Submit & wait for approval'); ?>" required>
                                    </div>
                                </div>
                                <div class="content-field">
                                    <label>Footer Text</label>
                                    <input type="text" name="content[footer_text]" value="<?php echo htmlspecialchars($editSectionData['footer_text'] ?? 'Need more help? Check out our FAQ section or documentation'); ?>" required>
                                </div>

                            <?php elseif ($editSection === 'footer'): ?>
                                <div class="content-field">
                                    <label>Address</label>
                                    <input type="text" name="content[address]" value="<?php echo htmlspecialchars($editSectionData['address'] ?? ''); ?>" required>
                                </div>
                                <div class="content-field">
                                    <label>Website URL</label>
                                    <input type="text" name="content[website]" value="<?php echo htmlspecialchars($editSectionData['website'] ?? ''); ?>" required>
                                </div>
                                <div class="content-field">
                                    <label>Phone</label>
                                    <input type="text" name="content[phone]" value="<?php echo htmlspecialchars($editSectionData['phone'] ?? ''); ?>" required>
                                </div>
                                <div class="content-field">
                                    <label>Fax</label>
                                    <input type="text" name="content[fax]" value="<?php echo htmlspecialchars($editSectionData['fax'] ?? ''); ?>" required>
                                </div>
                                <div class="content-field">
                                    <label>Office Hours</label>
                                    <input type="text" name="content[office_hours]" value="<?php echo htmlspecialchars($editSectionData['office_hours'] ?? ''); ?>" required>
                                </div>
                                <div class="content-field">
                                    <label>Notice Text</label>
                                    <textarea name="content[notice]" required><?php echo htmlspecialchars($editSectionData['notice'] ?? ''); ?></textarea>
                                </div>
                            <?php endif; ?>

                            <div class="form-actions">
                                <button type="submit" class="btn save-btn">Save Changes</button>
                                <a href="manage-landpage.php" class="btn cancel-btn" style="display: inline-block; text-align: center; text-decoration: none;">Cancel</a>
                            </div>
                        </form>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>

    <!-- JAVASCRIPT -->
    <script src="../assets/js/admin.js"></script>
</body>
</html>