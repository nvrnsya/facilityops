<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config.php");

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$message = '';
$messageType = '';
$editMode = false;
$editData = null;

// Get pending bookings count for notification badge
$pendingCountQuery = "SELECT COUNT(*) as pending_count FROM ulpl WHERE status = 'Pending'";
$pendingResult = mysqli_query($connect, $pendingCountQuery);
$pendingCount = 0;

if ($pendingResult) {
    $pendingRow = mysqli_fetch_assoc($pendingResult);
    $pendingCount = $pendingRow['pending_count'];
}

/* ===========================
   HANDLE AJAX (Toggle Status & Delete Gallery Image)
   =========================== */
if (isset($_POST['ajax']) && $_POST['ajax'] === 'true') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';
    $facilityId = intval($_POST['facility_id'] ?? 0);

    if ($action === 'toggle_status') {
        $newStatus = intval($_POST['status'] ?? 0);
        $stmt = $conn->prepare("UPDATE facilities SET is_active=? WHERE facility_id=?");
        $stmt->bind_param("ii", $newStatus, $facilityId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
        $stmt->close();
        exit();
    }
    
    // Delete single gallery image
    if ($action === 'delete_gallery_image') {
        $galleryId = intval($_POST['gallery_id'] ?? 0);
        
        // Get image path first
        $stmt = $conn->prepare("SELECT image_path FROM facility_gallery WHERE gallery_id=?");
        $stmt->bind_param("i", $galleryId);
        $stmt->execute();
        $result = $stmt->get_result();
        $gallery = $result->fetch_assoc();
        $stmt->close();
        
        if ($gallery) {
            // Delete from database
            $stmt = $conn->prepare("DELETE FROM facility_gallery WHERE gallery_id=?");
            $stmt->bind_param("i", $galleryId);
            
            if ($stmt->execute()) {
                // Delete image file
                $imagePath = '../assets/images/' . $gallery['image_path'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
                echo json_encode(['success' => true, 'message' => 'Gallery image deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete gallery image']);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Gallery image not found']);
        }
        exit();
    }
}

function checkDuplicateFacility($conn, $facilityName, $excludeId = 0) {
    $stmt = $conn->prepare("SELECT facility_id FROM facilities WHERE facility_name=? AND facility_id != ?");
    $stmt->bind_param("si", $facilityName, $excludeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

// FIXED: Better image validation function
function validateImageFile($tmpName, $fileSize, $fileName) {
    // Check file size (5MB max)
    if ($fileSize > 5242880) {
        return "Image file size must be less than 5MB: " . $fileName;
    }
    
    // Get file extension
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($fileExt, $allowedExts)) {
        return "Only image files (JPG, JPEG, PNG, GIF) are allowed: " . $fileName;
    }
    
    // Additional MIME type check
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $tmpName);
    finfo_close($finfo);
    
    $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    
    if (!in_array($mimeType, $allowedMimes)) {
        return "Invalid image format detected: " . $fileName;
    }
    
    return true; // Valid image
}

// FIXED: Clean filename but keep original name
function sanitizeFilename($filename) {
    // Get the file extension
    $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Get base name without extension
    $baseName = pathinfo($filename, PATHINFO_FILENAME);
    
    // Remove dangerous characters but keep most characters including spaces
    // Only remove: / \ : * ? " < > |
    $cleanName = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $baseName);
    
    // Return clean filename with extension
    return $cleanName . '.' . $fileExt;
}

// Check if file already exists and create unique name if needed
function getUniqueFilename($uploadDir, $filename) {
    $cleanFilename = sanitizeFilename($filename);
    $targetPath = $uploadDir . $cleanFilename;
    
    // If file doesn't exist, return original clean name
    if (!file_exists($targetPath)) {
        return $cleanFilename;
    }
    
    // If file exists, add number suffix
    $fileExt = pathinfo($cleanFilename, PATHINFO_EXTENSION);
    $baseName = pathinfo($cleanFilename, PATHINFO_FILENAME);
    
    $counter = 1;
    while (file_exists($uploadDir . $baseName . '_' . $counter . '.' . $fileExt)) {
        $counter++;
    }
    
    return $baseName . '_' . $counter . '.' . $fileExt;
}

/* ===========================
   HANDLE CRUD OPERATIONS
   =========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax'])) {
    $action = $_POST['action'] ?? '';

    /* ===== CREATE: Add New Facility ===== */
    if ($action === 'add') {
        $facilityName = trim($_POST['facility_name'] ?? '');
        $facilityDesc = trim($_POST['facility_desc'] ?? '');
        $facilitySlug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($facilityName))), '-');
        $facilitySlug = trim($facilitySlug, '-'); 

        if (checkDuplicateFacility($conn, $facilityName)) {
            $message = "Facility with this name already exists!";
            $messageType = 'error';
        } 
        elseif (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === 0) {
            $uploadDir = '../assets/images/';
            
            // Validate main image
            $validation = validateImageFile(
                $_FILES['main_image']['tmp_name'],
                $_FILES['main_image']['size'],
                $_FILES['main_image']['name']
            );
            
            if ($validation !== true) {
                $message = $validation;
                $messageType = 'error';
            } else {
                // FIXED: Keep original filename with sanitization
                $mainFileName = getUniqueFilename($uploadDir, $_FILES['main_image']['name']);
                $mainTargetPath = $uploadDir . $mainFileName;
                
                if (move_uploaded_file($_FILES['main_image']['tmp_name'], $mainTargetPath)) {
                    // Insert facility with main image
                    $stmt = $conn->prepare("INSERT INTO facilities (facility_name, facility_slug, description, image_path, is_active) VALUES (?, ?, ?, ?, 1)");
                    $stmt->bind_param("ssss", $facilityName, $facilitySlug, $facilityDesc, $mainFileName);
                    
                    if ($stmt->execute()) {
                        $newFacilityId = $stmt->insert_id;
                        
                        // Upload gallery images if any
                        if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['tmp_name'][0])) {
                            foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmpName) {
                                if ($_FILES['gallery_images']['error'][$key] === 0) {
                                    $validation = validateImageFile(
                                        $tmpName,
                                        $_FILES['gallery_images']['size'][$key],
                                        $_FILES['gallery_images']['name'][$key]
                                    );
                                    
                                    if ($validation === true) {
                                        // FIXED: Keep original filename with sanitization
                                        $galFileName = getUniqueFilename($uploadDir, $_FILES['gallery_images']['name'][$key]);
                                        $galTargetPath = $uploadDir . $galFileName;
                                        
                                        if (move_uploaded_file($tmpName, $galTargetPath)) {
                                            $galStmt = $conn->prepare("INSERT INTO facility_gallery (facility_id, image_path) VALUES (?, ?)");
                                            $galStmt->bind_param("is", $newFacilityId, $galFileName);
                                            $galStmt->execute();
                                            $galStmt->close();
                                        }
                                    }
                                }
                            }
                        }
                        
                        $message = "Facility added successfully!";
                        $messageType = 'success';
                    } else {
                        $message = "Database error: " . $stmt->error;
                        $messageType = 'error';
                    }
                    $stmt->close();
                } else {
                    $message = "Failed to upload main image.";
                    $messageType = 'error';
                }
            }
        } else {
            $message = "Please select a main image to upload.";
            $messageType = 'error';
        }
    }

   /* ===== UPDATE: Edit Existing Facility ===== */
    elseif ($action === 'edit') {
        $facilityId = intval($_POST['facility_id'] ?? 0);
        $facilityName = trim($_POST['facility_name'] ?? '');
        $facilityDesc = trim($_POST['facility_desc'] ?? '');
        
        // FIXED: Sama dengan ADD operation
        $facilitySlug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($facilityName))), '-');
        
        if (checkDuplicateFacility($conn, $facilityName, $facilityId)) {
            $message = "Facility with this name already exists!";
            $messageType = 'error';
        }
        else {
            $uploadDir = '../assets/images/';
            $updateMainImage = false;
            $newMainImage = '';
            
            // Check if new main image is uploaded
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === 0) {
                $validation = validateImageFile(
                    $_FILES['main_image']['tmp_name'],
                    $_FILES['main_image']['size'],
                    $_FILES['main_image']['name']
                );
                
                if ($validation !== true) {
                    $message = $validation;
                    $messageType = 'error';
                } else {
                    $newMainImage = getUniqueFilename($uploadDir, $_FILES['main_image']['name']);
                    $mainTargetPath = $uploadDir . $newMainImage;
                    
                    if (move_uploaded_file($_FILES['main_image']['tmp_name'], $mainTargetPath)) {
                        // Delete old main image
                        $oldImage = $_POST['old_image'] ?? '';
                        if ($oldImage && file_exists($uploadDir . $oldImage)) {
                            unlink($uploadDir . $oldImage);
                        }
                        $updateMainImage = true;
                    }
                }
            }
            
            // Update database
            if ($messageType !== 'error') {
                // FIXED: Prepare statement dengan betul
                if ($updateMainImage) {
                    $stmt = $conn->prepare("UPDATE facilities SET facility_name=?, facility_slug=?, description=?, image_path=? WHERE facility_id=?");
                    $stmt->bind_param("ssssi", $facilityName, $facilitySlug, $facilityDesc, $newMainImage, $facilityId);
                } else {
                    $stmt = $conn->prepare("UPDATE facilities SET facility_name=?, facility_slug=?, description=? WHERE facility_id=?");
                    $stmt->bind_param("sssi", $facilityName, $facilitySlug, $facilityDesc, $facilityId);
                }
                
                if ($stmt->execute()) {
                    // Handle gallery images
                    if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['tmp_name'][0])) {
                        foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmpName) {
                            if ($_FILES['gallery_images']['error'][$key] === 0) {
                                $validation = validateImageFile(
                                    $tmpName,
                                    $_FILES['gallery_images']['size'][$key],
                                    $_FILES['gallery_images']['name'][$key]
                                );
                                
                                if ($validation === true) {
                                    $galFileName = getUniqueFilename($uploadDir, $_FILES['gallery_images']['name'][$key]);
                                    $galTargetPath = $uploadDir . $galFileName;
                                    
                                    if (move_uploaded_file($tmpName, $galTargetPath)) {
                                        $galStmt = $conn->prepare("INSERT INTO facility_gallery (facility_id, image_path) VALUES (?, ?)");
                                        $galStmt->bind_param("is", $facilityId, $galFileName);
                                        $galStmt->execute();
                                        $galStmt->close();
                                    }
                                }
                            }
                        }
                    }
                    
                    $message = "Facility updated successfully!";
                    $messageType = 'success';
                    
                    // FIXED: Redirect AFTER semua process
                    $_SESSION['message'] = $message;
                    $_SESSION['messageType'] = $messageType;
                    header("Location: manage-facilities.php");
                    exit();
                } else {
                    $message = "Database error: " . $stmt->error;
                    $messageType = 'error';
                }
                $stmt->close();
            }
        }
    }
    

    /* ===== Permanently Delete Facility ===== */
    elseif ($action === 'hard_delete') {
        $facilityId = intval($_POST['facility_id'] ?? 0);

        // Get image path first
        $stmt = $conn->prepare("SELECT image_path FROM facilities WHERE facility_id=?");
        $stmt->bind_param("i", $facilityId);
        $stmt->execute();
        $result = $stmt->get_result();
        $facility = $result->fetch_assoc();
        $stmt->close();

        if ($facility) {
            // Delete from database
            $stmt = $conn->prepare("DELETE FROM facilities WHERE facility_id=?");
            $stmt->bind_param("i", $facilityId);

            if ($stmt->execute()) {
                // Delete image file
                $imagePath = '../assets/images/' . $facility['image_path'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }

                // Delete gallery images
                $galleryStmt = $conn->prepare("SELECT gallery_id, image_path FROM facility_gallery WHERE facility_id=?");
                $galleryStmt->bind_param("i", $facilityId);
                $galleryStmt->execute();
                $galleryResult = $galleryStmt->get_result();
                while ($galImg = $galleryResult->fetch_assoc()) {
                    $galPath = '../assets/images/' . $galImg['image_path'];
                    if (file_exists($galPath)) {
                        unlink($galPath);
                    }
                }
                $galleryStmt->close();
                $conn->query("DELETE FROM facility_gallery WHERE facility_id=$facilityId");

                $message = "Facility deleted permanently!";
                $messageType = 'success';
            } else {
                $message = "Failed to delete facility.";
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
}

/* ===========================
   HANDLE EDIT MODE
   =========================== */
if (isset($_GET['edit'])) {
    $editMode = true;
    $editId = intval($_GET['edit']);

    $stmt = $conn->prepare("SELECT * FROM facilities WHERE facility_id=?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    $editData = $result->fetch_assoc();
    $stmt->close();
}

/* ===========================
   FETCH ALL FACILITIES
   =========================== */
$facilities = mysqli_query($conn, "SELECT facility_id, facility_name, description, image_path, is_active, created_at FROM facilities ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/images/favicon.png">
    <title>Manage Facilities | FacilityOps</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/profile-menu.css">
    <link rel="stylesheet" href="../assets/css/manage-facilities.css">
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
            <h2>Facility Management</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo $messageType === 'success' ? '✓' : '✗'; ?> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- FACILITIES LIST -->
            <section id="facilities-list">
                <h3>All Facilities</h3>
                <div class="table-container">
                    <table class="facilities-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Facility Name</th>
                                <th>Description</th>
                                <th style="text-align: center;">Status</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($facilities) > 0): ?>
                                <?php while ($facility = mysqli_fetch_assoc($facilities)): ?>
                                    <tr>
                                        <td>
                                            <img src="../assets/images/<?php echo htmlspecialchars($facility['image_path']); ?>" 
                                                alt="<?php echo htmlspecialchars($facility['facility_name']); ?>"
                                                class="facility-image"
                                                onerror="this.src='../assets/images/placeholder.png'">
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($facility['facility_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars(substr($facility['description'], 0, 100)) . '...'; ?></td>
                                        <td style="text-align: center;">
                                            <div class="status-toggle-container">
                                                <label class="toggle-switch" 
                                                    data-facility-id="<?php echo $facility['facility_id']; ?>"
                                                    data-status="<?php echo $facility['is_active'] ? 'Active' : 'Inactive'; ?>">
                                                    <input 
                                                        type="checkbox" 
                                                        class="status-toggle-ajax"
                                                        <?php echo $facility['is_active'] ? 'checked' : ''; ?>
                                                        data-facility-id="<?php echo $facility['facility_id']; ?>">
                                                    <span class="toggle-slider"></span>
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="actions-cell">                                                
                                                <!-- Edit Button -->
                                                <a href="?edit=<?php echo $facility['facility_id']; ?>" 
                                                class="action-btn edit" 
                                                title="Edit Facility">
                                                    <span>&#9998;</span>
                                                </a>
                                                
                                                <!-- Delete Button -->
                                                <form method="POST"
                                                      style="display: inline;" 
                                                      class="delete-form" 
                                                    onsubmit="return confirm('Are you sure you want to permanently delete this facility? This action cannot be undone!');">
                                                    <input type="hidden" name="action" value="hard_delete">
                                                    <input type="hidden" name="facility_id" value="<?php echo $facility['facility_id']; ?>">
                                                    <button type="submit" class="action-btn delete" title="Delete Permanently">
                                                        <span>&#128465;</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 60px 20px;">
                                    <div style="color: #718096; font-size: 16px;">
                                        <span style="font-size: 48px; display: block; margin-bottom: 16px;">🏢</span>
                                        <strong>No facilities found</strong>
                                        <p style="margin-top: 8px; font-size: 14px;">Add your first facility using the form below.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ADD/EDIT FACILITY FORM -->
            <section id="facility-form">
                <div class="form-section-title">
                    <h3>
                        <?php echo $editMode ? '✏️ Edit Facility' : '➕ Add New Facility'; ?>
                    </h3>
                </div>
                <div class="table-container">
                    <form action="" method="POST" enctype="multipart/form-data" id="facilityForm">
                        <input type="hidden" name="action" value="<?php echo $editMode ? 'edit' : 'add'; ?>">
                        
                        <?php if ($editMode && $editData): ?>
                            <input type="hidden" name="facility_id" value="<?php echo $editData['facility_id']; ?>">
                            <input type="hidden" name="old_image" value="<?php echo $editData['image_path']; ?>">
                        <?php endif; ?>

                        <!-- Facility Name -->
                        <div class="form-group">
                            <label for="facility_name">Facility Name<span>*</span></label>
                            <input 
                                type="text" 
                                id="facility_name"
                                name="facility_name" 
                                placeholder="e.g., DEWAN KULIAH UTAMA" 
                                value="<?php echo $editMode && $editData ? htmlspecialchars($editData['facility_name']) : ''; ?>"
                                required
                            >
                        </div>

                        <!-- Facility Slug (Auto-generated, read-only) -->
                        <div class="form-group">
                            <label for="facility_slug">Facility Slug (Auto-generated)</label>
                            <input 
                                type="text" 
                                id="facility_slug"
                                name="facility_slug_display" 
                                placeholder="Will be generated from facility name..." 
                                value="<?php echo $editMode && $editData ? htmlspecialchars($editData['facility_slug']) : ''; ?>"
                                readonly
                                style="background-color: #f5f5f5; cursor: not-allowed;"
                            >
                            <small style="color: #666; display: block; margin-top: 5px;">
                                This slug is used in URLs and will be automatically generated from the facility name.
                            </small>
                        </div>

                        <!-- Description -->
                        <div class="form-group">
                            <label for="facility_desc">Description<span>*</span></label>
                            <textarea 
                                id="facility_desc"
                                name="facility_desc" 
                                rows="5" 
                                placeholder="Enter facility description..."
                                required
                            ><?php echo $editMode && $editData ? htmlspecialchars($editData['description']) : ''; ?></textarea>
                        </div>

                        <!-- SEPARATED: Main Image -->
                        <div class="form-group">
                            <label for="main_image">Main Facility Image<span><?php echo $editMode ? '' : '*'; ?></span></label>
                            <?php if ($editMode && $editData): ?>
                                <div style="margin-bottom: 10px;">
                                    <p style="color: #666; font-size: 14px;">Current main image: <strong><?php echo htmlspecialchars($editData['image_path']); ?></strong></p>
                                    <img src="../assets/images/<?php echo htmlspecialchars($editData['image_path']); ?>" alt="Current Main" style="max-width: 200px; border-radius: 4px; border: 2px solid #ddd;">
                                </div>
                            <?php endif; ?>
                            <div class="file-input-wrapper">
                                <span class="file-placeholder">Choose main image...</span>
                                <input type="file" id="main_image" name="main_image" accept="image/*" <?php echo $editMode ? '' : 'required'; ?>>
                            </div>
                            <small style="color: #666; display: block; margin-top: 5px;">
                                <?php echo $editMode ? 'Leave empty to keep current main image. ' : ''; ?>Accepted formats: JPG, JPEG, PNG, GIF. Max size: 5MB.
                            </small>
                        </div>

                        <!-- SEPARATED: Gallery Images -->
                        <div class="form-group">
                            <label for="gallery_images">Gallery Images (Optional)</label>
                            
                            <?php if ($editMode && $editData): ?>
                                <?php
                                $galleryStmt = $conn->prepare("SELECT gallery_id, image_path FROM facility_gallery WHERE facility_id=?");
                                $galleryStmt->bind_param("i", $editData['facility_id']);
                                $galleryStmt->execute();
                                $galleryResult = $galleryStmt->get_result();
                                if ($galleryResult->num_rows > 0):
                                ?>
                                <div style="margin-bottom: 10px;">
                                    <p style="color: #666; font-size: 14px;">Current gallery images:</p>
                                    <div class="gallery-grid">
                                        <?php while ($img = $galleryResult->fetch_assoc()): ?>
                                            <div class="gallery-item" data-gallery-id="<?php echo $img['gallery_id']; ?>">
                                                <img src="../assets/images/<?php echo htmlspecialchars($img['image_path']); ?>" alt="Gallery" title="<?php echo htmlspecialchars($img['image_path']); ?>">
                                                <button type="button" class="delete-gallery-btn" 
                                                        data-gallery-id="<?php echo $img['gallery_id']; ?>"
                                                        title="Delete this image">
                                                    ✕
                                                </button>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                                <?php
                                endif;
                                $galleryStmt->close();
                                ?>
                            <?php endif; ?>
                            
                            <div class="file-input-wrapper">
                                <span class="file-placeholder">Choose gallery images...</span>
                                <input type="file" id="gallery_images" name="gallery_images[]" accept="image/*" multiple>
                            </div>
                            <small style="color: #666; display: block; margin-top: 5px;">
                                You can select multiple images at once. Accepted formats: JPG, JPEG, PNG, GIF. Max size: 5MB each.
                            </small>
                        </div>

                        <!-- Buttons -->
                        <div class="form-actions">
                            <button type="submit" class="btn save-btn" id="submitBtn">
                                <?php echo $editMode ? 'Update Facility' : 'Add Facility'; ?>
                            </button>
                            <button type="reset" class="btn cancel-btn">Reset</button>
                            <?php if ($editMode): ?>
                                <a href="manage-facilities.php" class="btn cancel-btn" style="display: inline-block; text-align: center; text-decoration: none;">Cancel Edit</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>

    <!-- JAVASCRIPT -->
    <script src="../assets/js/admin.js"></script>
    <script src="../assets/js/manage-facilities.js"></script>

    <?php if ($editMode): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('facility-form').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    </script>
    <?php endif; ?>
 
</body>
</html>