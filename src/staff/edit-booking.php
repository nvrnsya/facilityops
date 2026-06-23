<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!file_exists("../config.php")) {
    die("Error: config.php file not found at ../config.php");
}

include("../config.php");

if ($connect->connect_errno) {
    die("Database connection failed: " . $connect->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../logininternal.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid booking ID.");
}

$booking_id = intval($_GET['id']);

// ==========================================
// 🔹 LOAD FORM FIELDS FIRST (BEFORE POST HANDLER)
// ==========================================
$formFieldsQuery = "SELECT * FROM booking_form_fields WHERE is_active = 1 ORDER BY field_section, field_order, field_id";
$formFieldsResult = mysqli_query($connect, $formFieldsQuery);

if (!$formFieldsResult) {
    die("Error loading form fields: " . mysqli_error($connect));
}

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

// ==========================================
// 🔹 HANDLE FORM SUBMISSION
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify booking belongs to current user
    $verifyQuery = "SELECT users_id FROM ulpl WHERE ulpl_id = ?";
    $stmtVerify = $connect->prepare($verifyQuery);
    
    if (!$stmtVerify) {
        die("Prepare verification failed: " . $connect->error);
    }
    
    $stmtVerify->bind_param("i", $booking_id);
    $stmtVerify->execute();
    $verifyResult = $stmtVerify->get_result();
    $owner = $verifyResult->fetch_assoc();
    $stmtVerify->close();
    
    if (!$owner || $owner['users_id'] != $user_id) {
        die("Unauthorized: You can only edit your own bookings.");
    }
    
    // 🔹 Fields to exclude from update (shown in User Information section only)
    $fieldsToHide = ['name', 'staffid', 'phone_num', 'depart', 'department_unit', 'staff_number', 'phone_number'];
    
    // Build dynamic UPDATE query for ulpl table
    $updateFields = [];
    $updateValues = [];
    $updateTypes = '';
    
    // Static required fields
    $staticFields = ['facilityName', 'select_date', 'start_time', 'end_time'];
    foreach ($staticFields as $fieldName) {
        if (isset($_POST[$fieldName]) && !empty(trim($_POST[$fieldName]))) {
            $updateFields[] = "$fieldName = ?";
            $updateValues[] = trim($_POST[$fieldName]);
            $updateTypes .= 's';
        }
    }
    
    // Dynamic booking_details fields from booking_form_fields
    foreach ($groupedFields['booking_details'] as $field) {
        $fieldName = $field['field_name'];
        
        // 🔹 Skip hidden fields (user info fields)
        if (in_array(strtolower($fieldName), $fieldsToHide)) {
            continue;
        }
        
        if (isset($_POST[$fieldName])) {
            $updateFields[] = "$fieldName = ?";
            $updateValues[] = trim($_POST[$fieldName]);
            $updateTypes .= 's';
        }
    }
    
    if (empty($updateFields)) {
        $error = "No fields to update.";
    } else {
        // Add WHERE conditions
        $updateValues[] = $booking_id;
        $updateValues[] = $user_id;
        $updateTypes .= 'ii';
        
        $updateUlplQuery = "UPDATE ulpl SET " . implode(', ', $updateFields) . " WHERE ulpl_id = ? AND users_id = ?";
        
        $stmt = $connect->prepare($updateUlplQuery);
        if (!$stmt) {
            die("Prepare failed: " . $connect->error);
        }
        
        $stmt->bind_param($updateTypes, ...$updateValues);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // ===== UPDATE KEY HANDOVER FIELDS =====
            $keyUpdateFields = [];
            $keyUpdateValues = [];
            $keyUpdateTypes = 'i';
            $keyUpdateValues[] = $user_id;
            
            $hasKeyData = false;
            foreach ($groupedFields['key_handover'] as $field) {
                $fieldName = $field['field_name'];
                if (isset($_POST[$fieldName]) && !empty(trim($_POST[$fieldName]))) {
                    $hasKeyData = true;
                    $keyUpdateFields[] = "$fieldName = ?";
                    $keyUpdateValues[] = trim($_POST[$fieldName]);
                    $keyUpdateTypes .= 's';
                }
            }
            
            if ($hasKeyData) {
                // Check if key record exists
                $checkKeyQuery = "SELECT COUNT(*) as count FROM `keys` WHERE ulpl_id = ?";
                $stmtCheck = $connect->prepare($checkKeyQuery);
                $stmtCheck->bind_param("i", $booking_id);
                $stmtCheck->execute();
                $result = $stmtCheck->get_result();
                $keyExists = $result->fetch_assoc()['count'] > 0;
                $stmtCheck->close();
                
                if ($keyExists && !empty($keyUpdateFields)) {
                    // Update existing
                    $keyUpdateValues[] = $booking_id;
                    $keyUpdateTypes .= 'i';
                    
                    $updateKeysQuery = "UPDATE `keys` SET users_id = ?, " . 
                                      implode(', ', $keyUpdateFields) . 
                                      " WHERE ulpl_id = ?";
                    
                    $stmtKeys = $connect->prepare($updateKeysQuery);
                    if ($stmtKeys) {
                        $stmtKeys->bind_param($keyUpdateTypes, ...$keyUpdateValues);
                        $stmtKeys->execute();
                        $stmtKeys->close();
                    }
                } else if (!$keyExists && !empty($keyUpdateFields)) {
                    // Insert new
                    $keyFieldNames = ['ulpl_id', 'users_id'];
                    $keyPlaceholders = ['?', '?'];
                    $keyInsertValues = [$booking_id, $user_id];
                    $keyInsertTypes = 'ii';
                    
                    foreach ($groupedFields['key_handover'] as $field) {
                        $fieldName = $field['field_name'];
                        if (isset($_POST[$fieldName]) && !empty(trim($_POST[$fieldName]))) {
                            $keyFieldNames[] = $fieldName;
                            $keyPlaceholders[] = '?';
                            $keyInsertValues[] = trim($_POST[$fieldName]);
                            $keyInsertTypes .= 's';
                        }
                    }
                    
                    $insertKeysQuery = "INSERT INTO `keys` (" . 
                                      implode(', ', $keyFieldNames) . 
                                      ") VALUES (" . 
                                      implode(', ', $keyPlaceholders) . ")";
                    
                    $stmtKeys = $connect->prepare($insertKeysQuery);
                    if ($stmtKeys) {
                        $stmtKeys->bind_param($keyInsertTypes, ...$keyInsertValues);
                        $stmtKeys->execute();
                        $stmtKeys->close();
                    }
                }
            }
            
            header("Location: profile.php?success=1");
            exit;
        } else {
            $error = "Failed to update booking: " . $stmt->error;
        }
        $stmt->close();
    }
}

// ==========================================
// 🔹 FETCH BOOKING DATA FOR DISPLAY
// ==========================================
$query = "
    SELECT 
        a.*,
        u.name AS user_name,
        u.staffid,
        u.phone_num,
        u.depart AS user_depart,
        k.*
    FROM ulpl a
    INNER JOIN users u ON a.users_id = u.users_id
    LEFT JOIN `keys` k ON a.ulpl_id = k.ulpl_id
    WHERE a.ulpl_id = ? AND a.users_id = ?
";

$stmt = $connect->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $connect->error);
}
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    die("Booking not found or you don't have permission to edit this booking.");
}

if (strtolower($row['status']) !== 'pending') {
    die("Only pending bookings can be edited. This booking status is: " . htmlspecialchars($row['status']));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/images/favicon.png">
    <title>Edit Booking | FacilityOps</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/edit-booking.css">
</head>
    
<body>
    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <strong>Error:</strong> <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="edit-page">
            <h2>Edit Booking - Standard</h2>
            
            <form method="POST" class="edit-form">
                <div class="form-section">
                    <h3>Booking Details</h3>
                    <hr>
                    
                    <!-- MESSAGE BOX -->
                    <div class="info-message">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <p><strong>Note:</strong> Facility name, date, and time cannot be modified. If you need to change these details, please delete this booking and create a new one.</p>
                    </div>
                    <!-- Static Fields -->
                    <div class="input-group">
                        <input type="text" name="facilityName" id="facilityName" 
                                value="<?= htmlspecialchars($row['facilityName']); ?>" 
                                placeholder=" " required readonly>
                        <label for="facilityName">Facility Name</label>
                    </div>
                
                    <div class="input-group">
                        <input type="date" name="select_date" id="select_date" 
                                value="<?= htmlspecialchars($row['select_date']); ?>" 
                                required readonly>
                        <label for="select_date">Selected Date</label>
                    </div>
                
                    <div class="input-row">
                        <div class="input-group">
                            <input type="text" name="start_time" id="start_time" 
                                    value="<?= htmlspecialchars($row['start_time'] ?? ''); ?>" 
                                    placeholder=" " required readonly>
                            <label for="start_time">Start Time</label>
                        </div>
                
                        <div class="input-group">
                            <input type="text" name="end_time" id="end_time" 
                                    value="<?= htmlspecialchars($row['end_time'] ?? ''); ?>" 
                                    placeholder=" " required readonly>
                            <label for="end_time">End Time</label>
                        </div>
                    </div>
                    
                    <!-- Dynamic Fields from booking_form_fields -->
                    <?php 
                    // Fields to hide (already shown in User Information section)
                    $fieldsToHide = ['name', 'staffid', 'phone_num', 'depart'];
                    
                    foreach ($groupedFields['booking_details'] as $field): 
                        // Skip fields that are shown in User Information section
                        if (in_array(strtolower($field['field_name']), $fieldsToHide)) {
                            continue;
                        }
                        
                        $fieldValue = $row[$field['field_name']] ?? '';
                        $isRequired = ($field['is_required'] == 1) ? 'required' : '';
                        $isReadonly = ($field['is_readonly'] == 1) ? 'readonly' : '';
                    ?>
                        
                        <div class="input-group">
                            <?php if ($field['field_type'] === 'textarea'): ?>
                                <textarea name="<?= $field['field_name']; ?>" 
                                          id="<?= $field['field_name']; ?>" 
                                          rows="4" 
                                          placeholder=" " 
                                          <?= $isRequired; ?> 
                                          <?= $isReadonly; ?>><?= htmlspecialchars($fieldValue); ?></textarea>
                            
                            <?php elseif ($field['field_type'] === 'select'): ?>
                                <?php $options = array_filter(array_map('trim', explode("\n", $field['field_options']))); ?>
                                <select name="<?= $field['field_name']; ?>" 
                                        id="<?= $field['field_name']; ?>" 
                                        <?= $isRequired; ?> 
                                        <?= $isReadonly ? 'disabled' : ''; ?>>
                                    <option value="">Select <?= htmlspecialchars($field['field_label']); ?></option>
                                    <?php foreach ($options as $option): ?>
                                        <option value="<?= htmlspecialchars($option); ?>" 
                                                <?= ($fieldValue === $option) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($option); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($isReadonly): ?>
                                    <input type="hidden" name="<?= $field['field_name']; ?>" value="<?= htmlspecialchars($fieldValue); ?>">
                                <?php endif; ?>
                            
                            <?php else: ?>
                                <input type="<?= $field['field_type']; ?>" 
                                       name="<?= $field['field_name']; ?>" 
                                       id="<?= $field['field_name']; ?>" 
                                       value="<?= htmlspecialchars($fieldValue); ?>" 
                                       placeholder=" " 
                                       <?= $isRequired; ?> 
                                       <?= $isReadonly; ?>>
                            <?php endif; ?>
                            
                            <label for="<?= $field['field_name']; ?>">
                                <?= htmlspecialchars($field['field_label']); ?>
                                <?= ($field['is_required'] == 1) ? '<span style="color: red;">*</span>' : ''; ?>
                            </label>
                            
                            <?php if (!empty($field['help_text'])): ?>
                                <small style="color: #666; display: block; margin-top: 5px;">
                                    <?= htmlspecialchars($field['help_text']); ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="form-section">
                    <h3>Key's Handover</h3>
                    <hr>
                    
                    <?php 
                    $keyFields = $groupedFields['key_handover'];
                    $totalKeyFields = count($keyFields);
                    
                    for ($i = 0; $i < $totalKeyFields; $i++): 
                        $field = $keyFields[$i];
                        $fieldValue = $row[$field['field_name']] ?? '';
                        $isRequired = ($field['is_required'] == 1) ? 'required' : '';
                        $isReadonly = ($field['is_readonly'] == 1) ? 'readonly' : '';
                        
                        // Check if next field exists for pairing
                        $nextField = ($i + 1 < $totalKeyFields) ? $keyFields[$i + 1] : null;
                        
                        // Pair fields if both exist and we're at an even index
                        if ($i % 2 == 0 && $nextField): 
                            // Start a row with two fields
                    ?>
                            <div class="input-row">
                                <!-- First Field -->
                                <div class="input-group">
                                    <?php if ($field['field_type'] === 'textarea'): ?>
                                        <textarea name="<?= $field['field_name']; ?>" 
                                                  id="<?= $field['field_name']; ?>" 
                                                  rows="4" 
                                                  placeholder=" " 
                                                  <?= $isRequired; ?> 
                                                  <?= $isReadonly; ?>><?= htmlspecialchars($fieldValue); ?></textarea>
                                    
                                    <?php elseif ($field['field_type'] === 'select'): ?>
                                        <?php $options = array_filter(array_map('trim', explode("\n", $field['field_options']))); ?>
                                        <select name="<?= $field['field_name']; ?>" 
                                                id="<?= $field['field_name']; ?>" 
                                                <?= $isRequired; ?> 
                                                <?= $isReadonly ? 'disabled' : ''; ?>>
                                            <option value="">Select <?= htmlspecialchars($field['field_label']); ?></option>
                                            <?php foreach ($options as $option): ?>
                                                <option value="<?= htmlspecialchars($option); ?>" 
                                                        <?= ($fieldValue === $option) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($option); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if ($isReadonly): ?>
                                            <input type="hidden" name="<?= $field['field_name']; ?>" value="<?= htmlspecialchars($fieldValue); ?>">
                                        <?php endif; ?>
                                    
                                    <?php else: ?>
                                        <input type="<?= $field['field_type']; ?>" 
                                               name="<?= $field['field_name']; ?>" 
                                               id="<?= $field['field_name']; ?>" 
                                               value="<?= htmlspecialchars($fieldValue); ?>" 
                                               placeholder=" " 
                                               <?= $isRequired; ?> 
                                               <?= $isReadonly; ?>>
                                    <?php endif; ?>
                                    
                                    <label for="<?= $field['field_name']; ?>">
                                        <?= htmlspecialchars($field['field_label']); ?>
                                        <?= ($field['is_required'] == 1) ? '<span style="color: red;">*</span>' : ''; ?>
                                    </label>
                                    
                                    <?php if (!empty($field['help_text'])): ?>
                                        <small style="color: #666; display: block; margin-top: 5px;">
                                            <?= htmlspecialchars($field['help_text']); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Second Field -->
                                <?php 
                                $nextFieldValue = $row[$nextField['field_name']] ?? '';
                                $nextIsRequired = ($nextField['is_required'] == 1) ? 'required' : '';
                                $nextIsReadonly = ($nextField['is_readonly'] == 1) ? 'readonly' : '';
                                ?>
                                <div class="input-group">
                                    <?php if ($nextField['field_type'] === 'textarea'): ?>
                                        <textarea name="<?= $nextField['field_name']; ?>" 
                                                  id="<?= $nextField['field_name']; ?>" 
                                                  rows="4" 
                                                  placeholder=" " 
                                                  <?= $nextIsRequired; ?> 
                                                  <?= $nextIsReadonly; ?>><?= htmlspecialchars($nextFieldValue); ?></textarea>
                                    
                                    <?php elseif ($nextField['field_type'] === 'select'): ?>
                                        <?php $nextOptions = array_filter(array_map('trim', explode("\n", $nextField['field_options']))); ?>
                                        <select name="<?= $nextField['field_name']; ?>" 
                                                id="<?= $nextField['field_name']; ?>" 
                                                <?= $nextIsRequired; ?> 
                                                <?= $nextIsReadonly ? 'disabled' : ''; ?>>
                                            <option value="">Select <?= htmlspecialchars($nextField['field_label']); ?></option>
                                            <?php foreach ($nextOptions as $option): ?>
                                                <option value="<?= htmlspecialchars($option); ?>" 
                                                        <?= ($nextFieldValue === $option) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($option); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if ($nextIsReadonly): ?>
                                            <input type="hidden" name="<?= $nextField['field_name']; ?>" value="<?= htmlspecialchars($nextFieldValue); ?>">
                                        <?php endif; ?>
                                    
                                    <?php else: ?>
                                        <input type="<?= $nextField['field_type']; ?>" 
                                               name="<?= $nextField['field_name']; ?>" 
                                               id="<?= $nextField['field_name']; ?>" 
                                               value="<?= htmlspecialchars($nextFieldValue); ?>" 
                                               placeholder=" " 
                                               <?= $nextIsRequired; ?> 
                                               <?= $nextIsReadonly; ?>>
                                    <?php endif; ?>
                                    
                                    <label for="<?= $nextField['field_name']; ?>">
                                        <?= htmlspecialchars($nextField['field_label']); ?>
                                        <?= ($nextField['is_required'] == 1) ? '<span style="color: red;">*</span>' : ''; ?>
                                    </label>
                                    
                                    <?php if (!empty($nextField['help_text'])): ?>
                                        <small style="color: #666; display: block; margin-top: 5px;">
                                            <?= htmlspecialchars($nextField['help_text']); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php 
                            $i++; // Skip next field since we already processed it
                        elseif ($i % 2 == 0): 
                            // Odd number of fields, render last one alone
                            ?>
                            <div class="input-group">
                                <?php if ($field['field_type'] === 'textarea'): ?>
                                    <textarea name="<?= $field['field_name']; ?>" 
                                              id="<?= $field['field_name']; ?>" 
                                              rows="4" 
                                              placeholder=" " 
                                              <?= $isRequired; ?> 
                                              <?= $isReadonly; ?>><?= htmlspecialchars($fieldValue); ?></textarea>
                                
                                <?php elseif ($field['field_type'] === 'select'): ?>
                                    <?php $options = array_filter(array_map('trim', explode("\n", $field['field_options']))); ?>
                                    <select name="<?= $field['field_name']; ?>" 
                                            id="<?= $field['field_name']; ?>" 
                                            <?= $isRequired; ?> 
                                            <?= $isReadonly ? 'disabled' : ''; ?>>
                                        <option value="">Select <?= htmlspecialchars($field['field_label']); ?></option>
                                        <?php foreach ($options as $option): ?>
                                            <option value="<?= htmlspecialchars($option); ?>" 
                                                    <?= ($fieldValue === $option) ? 'selected' : ''; ?>>
                                                <?= htmlspecialchars($option); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($isReadonly): ?>
                                        <input type="hidden" name="<?= $field['field_name']; ?>" value="<?= htmlspecialchars($fieldValue); ?>">
                                    <?php endif; ?>
                                
                                <?php else: ?>
                                    <input type="<?= $field['field_type']; ?>" 
                                           name="<?= $field['field_name']; ?>" 
                                           id="<?= $field['field_name']; ?>" 
                                           value="<?= htmlspecialchars($fieldValue); ?>" 
                                           placeholder=" " 
                                           <?= $isRequired; ?> 
                                           <?= $isReadonly; ?>>
                                <?php endif; ?>
                                
                                <label for="<?= $field['field_name']; ?>">
                                    <?= htmlspecialchars($field['field_label']); ?>
                                    <?= ($field['is_required'] == 1) ? '<span style="color: red;">*</span>' : ''; ?>
                                </label>
                                
                                <?php if (!empty($field['help_text'])): ?>
                                    <small style="color: #666; display: block; margin-top: 5px;">
                                        <?= htmlspecialchars($field['help_text']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>

                <div class="form-section readonly-section">
                    <h3>User Information (Read-Only)</h3>
                    <hr>
                    <div class="readonly-grid">
                        <div class="readonly-item">
                            <strong>Name:</strong>
                            <span><?= htmlspecialchars($row['user_name']); ?></span>
                        </div>
                        <div class="readonly-item">
                            <strong>Staff ID:</strong>
                            <span><?= htmlspecialchars($row['staffid']); ?></span>
                        </div>
                        <div class="readonly-item">
                            <strong>Phone Number:</strong>
                            <span><?= htmlspecialchars($row['phone_num']); ?></span>
                        </div>
                        <div class="readonly-item">
                            <strong>Department:</strong>
                            <span><?= htmlspecialchars($row['user_depart']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-submit">Save Changes</button>
                    <a href="profile.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/edit-booking.js"></script>
</body>
</html>