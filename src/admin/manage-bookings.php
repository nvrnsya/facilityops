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
$pendingResult = mysqli_query($conn, $pendingCountQuery);
$pendingCount = 0;

if ($pendingResult) {
    $pendingRow = mysqli_fetch_assoc($pendingResult);
    $pendingCount = $pendingRow['pending_count'];
}

/* ===========================
   HANDLE AJAX
   =========================== */
if (isset($_POST['ajax']) && $_POST['ajax'] === 'true') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_status') {
        $fieldId = intval($_POST['field_id'] ?? 0);
        $newStatus = intval($_POST['status'] ?? 0);
        
        $stmt = $conn->prepare("UPDATE booking_form_fields SET is_active=? WHERE field_id=?");
        $stmt->bind_param("ii", $newStatus, $fieldId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
        $stmt->close();
        exit();
    }
    
    if ($action === 'reorder') {
        $orders = json_decode($_POST['orders'], true);
        
        foreach ($orders as $fieldId => $order) {
            $stmt = $conn->prepare("UPDATE booking_form_fields SET field_order=? WHERE field_id=?");
            $stmt->bind_param("ii", $order, $fieldId);
            $stmt->execute();
            $stmt->close();
        }
        
        echo json_encode(['success' => true, 'message' => 'Order updated successfully']);
        exit();
    }
}

/* ===========================
   HANDLE CRUD OPERATIONS
   =========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax'])) {
    $action = $_POST['action'] ?? '';

    /* ===== ADD NEW FIELD ===== */
    if ($action === 'add') {
        $fieldLabel = trim($_POST['field_label'] ?? '');
        $fieldName = trim($_POST['field_name'] ?? '');
        
        // Auto-generate field_name if empty
        if (empty($fieldName)) {
            $fieldName = preg_replace('/[^a-z0-9_]+/', '_', strtolower($fieldLabel));
        }
        
        $fieldType = trim($_POST['field_type'] ?? 'text');
        $isRequired = intval($_POST['is_required'] ?? 0);
        $placeholder = trim($_POST['placeholder'] ?? '');
        $helpText = trim($_POST['help_text'] ?? '');
        $fieldOptions = trim($_POST['field_options'] ?? '');
        $fieldSection = trim($_POST['field_section'] ?? 'booking_details');
        
        // ✅ STEP 1: Determine which table to alter
        $tableName = ($fieldSection === 'key_handover') ? 'keys' : 'ulpl';
        
        // ✅ STEP 2: Determine SQL column type based on field type
        $sqlType = 'VARCHAR(255)';
        switch ($fieldType) {
            case 'textarea':
                $sqlType = 'TEXT';
                break;
            case 'number':
                $sqlType = 'INT(11)';
                break;
            case 'date':
                $sqlType = 'DATE';
                break;
            case 'time':
                $sqlType = 'TIME';
                break;
            case 'email':
            case 'tel':
            case 'text':
            default:
                $sqlType = 'VARCHAR(255)';
                break;
        }
        
        // ✅ STEP 3: Set NULL or NOT NULL
        $nullClause = $isRequired ? 'NOT NULL' : 'NULL';
        
        // ✅ STEP 4: Check if column already exists
        $checkColumnQuery = "SHOW COLUMNS FROM `{$tableName}` LIKE '{$fieldName}'";
        $columnExists = mysqli_query($conn, $checkColumnQuery);
        
        if (mysqli_num_rows($columnExists) == 0) {
            // Column doesn't exist, create it
            $addColumnQuery = "ALTER TABLE `{$tableName}` ADD COLUMN `{$fieldName}` {$sqlType} {$nullClause}";
            
            if ($conn->query($addColumnQuery)) {
                error_log("✅ Created column {$fieldName} in {$tableName}");
            } else {
                $message = "Failed to create database column: " . $conn->error;
                $messageType = 'error';
            }
        } else {
            error_log("⚠️ Column {$fieldName} already exists in {$tableName}");
        }
        
        // ✅ STEP 5: Get max order
        $result = $conn->query("SELECT MAX(field_order) as max_order FROM booking_form_fields");
        $row = $result->fetch_assoc();
        $maxOrder = ($row['max_order'] ?? 0) + 1;
        
        // ✅ STEP 6: Insert into booking_form_fields
        $stmt = $conn->prepare("
            INSERT INTO booking_form_fields 
            (field_name, field_label, field_type, is_required, placeholder, help_text, field_options, field_section, field_order, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        $stmt->bind_param(
            "ssssssssi",
            $fieldName,
            $fieldLabel,
            $fieldType,
            $isRequired,
            $placeholder,
            $helpText,
            $fieldOptions,
            $fieldSection,
            $maxOrder
        );
        
        if ($stmt->execute()) {
            $message = "Form field and database column added successfully!";
            $messageType = 'success';
        } else {
            $message = "Failed to add field: " . $stmt->error;
            $messageType = 'error';
        }
        $stmt->close();
    }

    /* ===== EDIT FIELD ===== */
    elseif ($action === 'edit') {
        $fieldId = intval($_POST['field_id'] ?? 0);
        $fieldLabel = trim($_POST['field_label'] ?? '');
        $fieldName = trim($_POST['field_name'] ?? '');
        $fieldType = trim($_POST['field_type'] ?? 'text');
        $isRequired = intval($_POST['is_required'] ?? 0);
        $placeholder = trim($_POST['placeholder'] ?? '');
        $helpText = trim($_POST['help_text'] ?? '');
        $fieldOptions = trim($_POST['field_options'] ?? '');
        $fieldSection = trim($_POST['field_section'] ?? 'booking_details');
        
        $stmt = $conn->prepare("
            UPDATE booking_form_fields SET
                field_name=?,
                field_label=?,
                field_type=?,
                is_required=?,
                placeholder=?,
                help_text=?,
                field_options=?,
                field_section=?
            WHERE field_id=?
        ");
        $stmt->bind_param(
            "ssssssssi",
            $fieldName,
            $fieldLabel,
            $fieldType,
            $isRequired,
            $placeholder,
            $helpText,
            $fieldOptions,
            $fieldSection,
            $fieldId
        );
        
        if ($stmt->execute()) {
            $message = "Form field updated successfully!";
            $messageType = 'success';
            
            $_SESSION['message'] = $message;
            $_SESSION['messageType'] = $messageType;
            header("Location: manage-bookings.php");
            exit();
        } else {
            $message = "Failed to update field: " . $stmt->error;
            $messageType = 'error';
        }
        $stmt->close();
    }

    /* ===== DELETE FIELD ===== */
    elseif ($action === 'delete') {
        $fieldId = intval($_POST['field_id'] ?? 0);
        
        // ✅ STEP 1: Get field info first (need field_name and field_section)
        $getFieldStmt = $conn->prepare("SELECT field_name, field_section FROM booking_form_fields WHERE field_id=?");
        $getFieldStmt->bind_param("i", $fieldId);
        $getFieldStmt->execute();
        $fieldResult = $getFieldStmt->get_result();
        $fieldInfo = $fieldResult->fetch_assoc();
        $getFieldStmt->close();
        
        if ($fieldInfo) {
            $fieldName = $fieldInfo['field_name'];
            $fieldSection = $fieldInfo['field_section'];
            
            // ✅ STEP 2: Determine which table
            $tableName = ($fieldSection === 'key_handover') ? 'keys' : 'ulpl';
            
            // ✅ STEP 3: Check if column exists first
            $checkColumnQuery = "SHOW COLUMNS FROM `{$tableName}` LIKE '{$fieldName}'";
            $columnExists = mysqli_query($conn, $checkColumnQuery);
            
            if (mysqli_num_rows($columnExists) > 0) {
                // Column exists, safe to drop it
                $dropColumnQuery = "ALTER TABLE `{$tableName}` DROP COLUMN `{$fieldName}`";
                
                if ($conn->query($dropColumnQuery)) {
                    error_log("✅ Dropped column {$fieldName} from {$tableName}");
                } else {
                    error_log("⚠️ Failed to drop column: " . $conn->error);
                    // Don't stop execution, continue to delete from booking_form_fields
                }
            } else {
                error_log("ℹ️ Column {$fieldName} doesn't exist in {$tableName}, skipping DROP");
            }
            
            // ✅ STEP 4: Delete from booking_form_fields (always do this)
            $stmt = $conn->prepare("DELETE FROM booking_form_fields WHERE field_id=?");
            $stmt->bind_param("i", $fieldId);
            
            if ($stmt->execute()) {
                $message = "Form field deleted successfully!";
                $messageType = 'success';
            } else {
                $message = "Failed to delete field from configuration.";
                $messageType = 'error';
            }
            $stmt->close();
        } else {
            $message = "Field not found.";
            $messageType = 'error';
        }
    }
}

/* ===========================
   HANDLE EDIT MODE
   =========================== */
$editMode = false;
$editData = null;

if (isset($_GET['edit'])) {
    $editMode = true;
    $editId = intval($_GET['edit']);

    $stmt = $conn->prepare("SELECT * FROM booking_form_fields WHERE field_id=?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    $editData = $result->fetch_assoc();
    $stmt->close();
}

/* ===========================
   FETCH ALL FORM FIELDS
   =========================== */
$fields = mysqli_query($conn, "
    SELECT * FROM booking_form_fields 
    ORDER BY field_section ASC, field_order ASC, field_id ASC
");

// Check for session messages
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'];
    unset($_SESSION['message'], $_SESSION['messageType']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/images/favicon.png">
    <title>Manage Booking Form | FacilityOps</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/profile-menu.css">
    <link rel="stylesheet" href="../assets/css/manage-facilities.css">

    
    <style>
        @media (max-width: 968px) {
            .menu-toggle {
                display: block !important;
                left: auto;
                right: 16px;
                top: 80px;
            }

            .side-panel {
                left: -260px !important;
                transition: left 0.3s ease;
            }

            .side-panel.active {
                left: 0 !important;
            }

            .admin-layout {
                display: block;
            }

            main {
                margin-left: 0 !important;
                margin-right: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
                padding: 20px 16px;
                box-sizing: border-box;
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
            <h2>📝 Booking Form Builder</h2>
            <p style="color: #6b7280; margin-bottom: 20px;">Customize your booking form by adding, editing, or removing fields. Drag to reorder fields.</p>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo $messageType === 'success' ? '✓' : '✗'; ?> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- FORM FIELDS LIST -->
            <section id="fields-list">
                <h3>Current Form Fields</h3>
                <div class="table-container">
                    <table class="facilities-table" id="fieldsTable">
                        <thead>
                            <tr>
                                <th style="width: 40px;">⋮⋮</th>
                                <th>Field Label</th>
                                <th>Field Name</th>
                                <th>Type</th>
                                <th>Section</th>
                                <th style="text-align: center;">Required</th>
                                <th style="text-align: center;">Status</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="sortableFields">
                            <?php if (mysqli_num_rows($fields) > 0): ?>
                                <?php while ($field = mysqli_fetch_assoc($fields)): ?>
                                    <tr data-field-id="<?php echo $field['field_id']; ?>">
                                        <td class="drag-handle" title="Drag to reorder">⋮⋮</td>
                                        <td><strong><?php echo htmlspecialchars($field['field_label']); ?></strong></td>
                                        <td><code><?php echo htmlspecialchars($field['field_name']); ?></code></td>
                                        <td>
                                            <span class="field-type-badge type-<?php echo $field['field_type']; ?>">
                                                <?php echo strtoupper($field['field_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="section-badge">
                                                <?php echo ucwords(str_replace('_', ' ', $field['field_section'])); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ($field['is_required']): ?>
                                                <span class="required-badge" title="Required">*</span>
                                            <?php else: ?>
                                                <span style="color: #9ca3af;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <label class="toggle-switch">
                                                <input 
                                                    type="checkbox" 
                                                    class="status-toggle-ajax"
                                                    <?php echo $field['is_active'] ? 'checked' : ''; ?>
                                                    data-field-id="<?php echo $field['field_id']; ?>">
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </td>
                                        <td>
                                            <div class="actions-cell">
                                                <a href="?edit=<?php echo $field['field_id']; ?>" 
                                                   class="action-btn edit" 
                                                   title="Edit Field">
                                                    <span>&#9998;</span>
                                                </a>
                                                
                                                <form method="POST"
                                                      style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to delete this field?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="field_id" value="<?php echo $field['field_id']; ?>">
                                                    <button type="submit" class="action-btn delete" title="Delete Field">
                                                        <span>&#128465;</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 60px 20px;">
                                        <div style="color: #718096; font-size: 16px;">
                                            <span style="font-size: 48px; display: block; margin-bottom: 16px;">📋</span>
                                            <strong>No form fields found</strong>
                                            <p style="margin-top: 8px; font-size: 14px;">Add your first form field using the form below.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ADD/EDIT FORM FIELD -->
            <section id="field-form">
                <div class="form-section-title">
                    <h3>
                        <?php echo $editMode ? '✏️ Edit Form Field' : '➕ Add New Form Field'; ?>
                    </h3>
                </div>
                <div class="table-container">
                    <form action="" method="POST" id="fieldForm">
                        <input type="hidden" name="action" value="<?php echo $editMode ? 'edit' : 'add'; ?>">
                        
                        <?php if ($editMode && $editData): ?>
                            <input type="hidden" name="field_id" value="<?php echo $editData['field_id']; ?>">
                        <?php endif; ?>

                        <!-- Field Label -->
                        <div class="form-group">
                            <label for="field_label">Field Label<span>*</span></label>
                            <input 
                                type="text" 
                                id="field_label"
                                name="field_label" 
                                placeholder="e.g., Event Name, Number of Participants" 
                                value="<?php echo $editMode && $editData ? htmlspecialchars($editData['field_label']) : ''; ?>"
                                required
                            >
                            <small style="color: #666; display: block; margin-top: 5px;">
                                This is what users will see on the form
                            </small>
                        </div>

                        <!-- Field Name (for database) -->
                        <div class="form-group">
                            <label for="field_name">Field Name (Database Column)<span>*</span></label>
                            <input 
                                type="text" 
                                id="field_name"
                                name="field_name" 
                                placeholder="e.g., event_name, num_participants" 
                                value="<?php echo $editMode && $editData ? htmlspecialchars($editData['field_name']) : ''; ?>"
                                <?php echo $editMode ? 'readonly style="background-color: #f5f5f5;"' : ''; ?>
                                required
                            >
                            <small style="color: #666; display: block; margin-top: 5px;">
                                Use lowercase letters, numbers, and underscores only. <?php echo $editMode ? '(Cannot be changed)' : 'Auto-generated from label if left empty.'; ?>
                            </small>
                        </div>

                        <!-- Field Type -->
                        <div class="form-group">
                            <label for="field_type">Field Type<span>*</span></label>
                            <select id="field_type" name="field_type" required>
                                <option value="text" <?php echo ($editMode && $editData && $editData['field_type'] === 'text') ? 'selected' : ''; ?>>Text Input</option>
                                <option value="email" <?php echo ($editMode && $editData && $editData['field_type'] === 'email') ? 'selected' : ''; ?>>Email</option>
                                <option value="tel" <?php echo ($editMode && $editData && $editData['field_type'] === 'tel') ? 'selected' : ''; ?>>Phone Number</option>
                                <option value="number" <?php echo ($editMode && $editData && $editData['field_type'] === 'number') ? 'selected' : ''; ?>>Number</option>
                                <option value="date" <?php echo ($editMode && $editData && $editData['field_type'] === 'date') ? 'selected' : ''; ?>>Date</option>
                                <option value="time" <?php echo ($editMode && $editData && $editData['field_type'] === 'time') ? 'selected' : ''; ?>>Time</option>
                                <option value="textarea" <?php echo ($editMode && $editData && $editData['field_type'] === 'textarea') ? 'selected' : ''; ?>>Textarea</option>
                                <option value="select" <?php echo ($editMode && $editData && $editData['field_type'] === 'select') ? 'selected' : ''; ?>>Dropdown Select</option>
                                <option value="radio" <?php echo ($editMode && $editData && $editData['field_type'] === 'radio') ? 'selected' : ''; ?>>Radio Buttons</option>
                            </select>
                        </div>

                        <!-- Field Section -->
                        <div class="form-group">
                            <label for="field_section">Form Section<span>*</span></label>
                            <select id="field_section" name="field_section" required>
                                <option value="booking_details" <?php echo ($editMode && $editData && $editData['field_section'] === 'booking_details') ? 'selected' : ''; ?>>Booking Details</option>
                                <option value="key_handover" <?php echo ($editMode && $editData && $editData['field_section'] === 'key_handover') ? 'selected' : ''; ?>>Key Handover</option>
                            </select>
                        </div>

                        <!-- Required Checkbox -->
                        <div class="form-group">
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input 
                                    type="checkbox" 
                                    name="is_required" 
                                    value="1"
                                    <?php echo ($editMode && $editData && $editData['is_required']) ? 'checked' : ''; ?>
                                    style="margin-right: 8px; width: auto;">
                                <span>This field is required</span>
                            </label>
                        </div>

                        <!-- Help Text -->
                        <div class="form-group">
                            <label for="help_text">Help Text</label>
                            <textarea 
                                id="help_text"
                                name="help_text" 
                                rows="2"
                                placeholder="Additional instructions or information for this field..."
                            ><?php echo $editMode && $editData ? htmlspecialchars($editData['help_text']) : ''; ?></textarea>
                        </div>

                        <!-- Field Options (for select, radio, checkbox) -->
                        <div class="form-group" id="options-group" style="display: none;">
                            <label for="field_options">Options (one per line)<span>*</span></label>
                            <textarea 
                                id="field_options"
                                name="field_options" 
                                rows="5"
                                placeholder="Option 1&#10;Option 2&#10;Option 3"
                            ><?php echo $editMode && $editData ? htmlspecialchars($editData['field_options']) : ''; ?></textarea>
                            <small style="color: #666; display: block; margin-top: 5px;">
                                For dropdown, radio, or checkbox fields. Enter each option on a new line.
                            </small>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="submit" class="btn save-btn">
                                <?php echo $editMode ? 'Update Field' : 'Add Field'; ?>
                            </button>
                            <button type="reset" class="btn cancel-btn">Reset</button>
                            <?php if ($editMode): ?>
                                <a href="manage-bookings.php" class="btn cancel-btn" style="display: inline-block; text-align: center; text-decoration: none;">Cancel Edit</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>

    <!-- JAVASCRIPT -->
    <script src="../assets/js/admin.js"></script>
    
    <script>
    // Toggle options field visibility based on field type
    document.getElementById('field_type').addEventListener('change', function() {
        const optionsGroup = document.getElementById('options-group');
        const needsOptions = ['select', 'radio', 'checkbox'].includes(this.value);
        optionsGroup.style.display = needsOptions ? 'block' : 'none';
        document.getElementById('field_options').required = needsOptions;
    });

    // Auto-generate field name from label
    document.getElementById('field_label').addEventListener('input', function() {
        const fieldNameInput = document.getElementById('field_name');
        if (!fieldNameInput.readOnly) {
            const sanitized = this.value.toLowerCase()
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '');
            fieldNameInput.value = sanitized;
        }
    });

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        const fieldType = document.getElementById('field_type').value;
        const optionsGroup = document.getElementById('options-group');
        const needsOptions = ['select', 'radio', 'checkbox'].includes(fieldType);
        optionsGroup.style.display = needsOptions ? 'block' : 'none';
    });

    // Status toggle via AJAX
    document.querySelectorAll('.status-toggle-ajax').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const fieldId = this.dataset.fieldId;
            const newStatus = this.checked ? 1 : 0;
            
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `ajax=true&action=toggle_status&field_id=${fieldId}&status=${newStatus}`
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message);
                    this.checked = !this.checked;
                }
            });
        });
    });

    // Drag and drop reordering
    let draggedElement = null;

    document.querySelectorAll('#sortableFields tr').forEach(row => {
        row.draggable = true;
        
        row.addEventListener('dragstart', function() {
            draggedElement = this;
            this.classList.add('dragging');
        });
        
        row.addEventListener('dragend', function() {
            this.classList.remove('dragging');
        });
        
        row.addEventListener('dragover', function(e) {
            e.preventDefault();
            const afterElement = getDragAfterElement(this.parentElement, e.clientY);
            if (afterElement == null) {
                this.parentElement.appendChild(draggedElement);
            } else {
                this.parentElement.insertBefore(draggedElement, afterElement);
            }
        });
        
        row.addEventListener('drop', function() {
            saveOrder();
        });
    });

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('tr:not(.dragging)')];
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    function saveOrder() {
        const rows = document.querySelectorAll('#sortableFields tr');
        const orders = {};
        rows.forEach((row, index) => {
            const fieldId = row.dataset.fieldId;
            if (fieldId) {
                orders[fieldId] = index + 1;
            }
        });
        
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `ajax=true&action=reorder&orders=${JSON.stringify(orders)}`
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert('Failed to save order');
            }
        });
    }
    </script>

    <?php if ($editMode): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('field-form').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    </script>
    <?php endif; ?>
 
</body>
</html>