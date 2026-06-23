<?php
include("../config.php");

// Fetch all FAQs
$query = "SELECT * FROM faqs ORDER BY display_order ASC";
$result = mysqli_query($connect, $query);

// Fetch current phone number
$phone_query = "SELECT setting_value FROM site_settings WHERE setting_key = 'support_phone'";
$phone_result = mysqli_query($connect, $phone_query);
$current_phone = '+60 11-11577404'; // Default fallback
if ($phone_result && mysqli_num_rows($phone_result) > 0) {
    $phone_row = mysqli_fetch_assoc($phone_result);
    $current_phone = $phone_row['setting_value'];
}

// Fetch current documentation file
$doc_query = "SELECT setting_value FROM site_settings WHERE setting_key = 'user_documentation'";
$doc_result = mysqli_query($connect, $doc_query);
$current_doc = '../assets/documents/FACILTYOPS USER MANUAL.pdf'; // Default fallback
if ($doc_result && mysqli_num_rows($doc_result) > 0) {
    $doc_row = mysqli_fetch_assoc($doc_result);
    $current_doc = $doc_row['setting_value'];
}

// Fetch current support email
$email_query = "SELECT setting_value FROM site_settings WHERE setting_key = 'support_email'";
$email_result = mysqli_query($connect, $email_query);
$current_email = $gmail_smtp_username; // Default fallback from credentials.php
if ($email_result && mysqli_num_rows($email_result) > 0) {
    $email_row = mysqli_fetch_assoc($email_result);
    $current_email = $email_row['setting_value'];
}

// Fetch current SMTP settings
$smtp_user_query = "SELECT setting_value FROM site_settings WHERE setting_key = 'smtp_username'";
$smtp_user_result = mysqli_query($connect, $smtp_user_query);
$current_smtp_user = $gmail_smtp_username; // Default fallback from credentials.php
if ($smtp_user_result && mysqli_num_rows($smtp_user_result) > 0) {
    $smtp_user_row = mysqli_fetch_assoc($smtp_user_result);
    $current_smtp_user = $smtp_user_row['setting_value'];
}

$smtp_pass_query = "SELECT setting_value FROM site_settings WHERE setting_key = 'smtp_password'";
$smtp_pass_result = mysqli_query($connect, $smtp_pass_query);
$current_smtp_pass = $gmail_smtp_password; // Default fallback from credentials.php
if ($smtp_pass_result && mysqli_num_rows($smtp_pass_result) > 0) {
    $smtp_pass_row = mysqli_fetch_assoc($smtp_pass_result);
    $current_smtp_pass = $smtp_pass_row['setting_value'];
}

// Get pending bookings count for notification badge
$pendingCountQuery = "SELECT COUNT(*) as pending_count FROM ulpl WHERE status = 'Pending'";
$pendingResult = mysqli_query($connect, $pendingCountQuery);
$pendingCount = 0;

if ($pendingResult) {
    $pendingRow = mysqli_fetch_assoc($pendingResult);
    $pendingCount = $pendingRow['pending_count'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/images/favicon.png">
    <title>Manage FAQ | FacilityOps</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/profile-menu.css">
    <link rel="stylesheet" href="../assets/css/dashmenu.css">
    <link rel="stylesheet" href="../assets/css/manage-faq.css">
    
    <style>
        /* Settings Section Styles */
        .settings-section {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            margin-top: 32px;
            margin-bottom: 32px;
            box-shadow: 0 2px 8px rgba(26, 54, 93, 0.08);
        }
        
        .settings-section h3 {
            font-size: 18px;
            color: #1a365d;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Form Layout - Vertical */
        .settings-form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .settings-form .form-group {
            width: 100%;
        }
        
        .settings-form label {
            display: block;
            margin-bottom: 8px;
            color: #2d3748;
            font-weight: 500;
            font-size: 14px;
        }
        
        .settings-form input[type="text"],
        .settings-form input[type="tel"],
        .settings-form input[type="email"] {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            color: #2d3748;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .settings-form input:focus {
            outline: none;
            border-color: #2c5282;
            box-shadow: 0 0 0 3px rgba(44, 82, 130, 0.1);
        }
        
        /* Centered Update Button */
        .btn-update {
            padding: 12px 32px;
            background: linear-gradient(135deg, #2c5282 0%, #1a365d 100%);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 24px auto 0;
            display: block;
            min-width: 200px;
        }
        
        .btn-update:hover {
            background: linear-gradient(135deg, #1a365d 0%, #0f2847 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 54, 93, 0.3);
        }
        
        .btn-update:active {
            transform: translateY(0);
        }

        .phone-preview, .doc-preview, .email-preview {
            margin-top: 12px;
            padding: 12px;
            background: #f7fafc;
            border-radius: 6px;
            border-left: 3px solid #2c5282;
        }

        .phone-preview p, .doc-preview p, .email-preview p {
            margin: 0;
            font-size: 13px;
            color: #4a5568;
        }

        .phone-preview strong, .doc-preview strong, .email-preview strong {
            color: #2d3748;
            font-weight: 600;
        }

        /* Enhanced File Input Styling */
        .file-input-wrapper {
            position: relative;
            width: 100%;
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
            z-index: 2;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            padding: 20px;
            border: 2px dashed #cbd5e0;
            border-radius: 8px;
            background: #f7fafc;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .file-input-label:hover {
            border-color: #2c5282;
            background: #edf2f7;
            transform: translateY(-1px);
        }

        .file-input-label.drag-over {
            border-color: #2c5282;
            background: #e6f2ff;
            border-style: solid;
        }

        .file-input-label.has-file {
            border-color: #48bb78;
            background: #f0fff4;
            border-style: solid;
        }

        .file-input-icon {
            width: 40px;
            height: 40px;
            color: #718096;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .file-input-label:hover .file-input-icon {
            color: #2c5282;
            transform: scale(1.1);
        }

        .file-input-label.has-file .file-input-icon {
            color: #48bb78;
        }

        .file-input-text {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
        }

        .file-input-text strong {
            font-size: 14px;
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .file-input-text span {
            font-size: 12px;
            color: #718096;
        }

        .file-input-label.has-file .file-input-text strong {
            color: #2f855a;
        }

        .selected-file-name {
            display: none;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            padding: 12px 16px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 13px;
            color: #2d3748;
        }

        .selected-file-name.show {
            display: flex;
        }

        .selected-file-name svg {
            width: 20px;
            height: 20px;
            color: #48bb78;
            flex-shrink: 0;
        }

        .selected-file-name .file-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .selected-file-name .file-name {
            font-weight: 600;
            color: #2d3748;
        }

        .selected-file-name .file-size {
            font-size: 11px;
            color: #718096;
        }

        .btn-remove-file {
            padding: 4px 8px;
            background: #fed7d7;
            color: #c53030;
            border: none;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-remove-file:hover {
            background: #fc8181;
            color: #ffffff;
        }

        .current-file {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-top: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }

        .current-file:hover {
            border-color: #cbd5e0;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        .current-file svg {
            width: 24px;
            height: 24px;
            color: #e53e3e;
            flex-shrink: 0;
        }

        .current-file span {
            font-size: 14px;
            color: #2d3748;
            font-weight: 500;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .btn-view-doc {
            padding: 8px 16px;
            background: #edf2f7;
            color: #2c5282;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-view-doc:hover {
            background: #e2e8f0;
            color: #1a365d;
            border-color: #a0aec0;
            transform: translateY(-1px);
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #718096;
            margin-top: 8px;
            padding: 8px 12px;
            background: #edf2f7;
            border-radius: 6px;
        }

        .file-info svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .file-input-label.drag-over .file-input-icon {
            animation: pulse 1s ease-in-out infinite;
        }

        @media (max-width: 768px) {
            .settings-form {
                flex-direction: column;
            }

            .settings-form .form-group {
                width: 100%;
            }

            .btn-update {
                width: 100%;
                margin-top: 16px;
                min-width: auto;
            }

            .file-input-label {
                padding: 16px;
                flex-direction: column;
                text-align: center;
            }

            .file-input-text {
                align-items: center;
                text-align: center;
            }

            .file-input-icon {
                width: 32px;
                height: 32px;
            }

            .current-file {
                flex-wrap: wrap;
                gap: 8px;
            }

            .current-file span {
                width: 100%;
                order: 2;
            }

            .btn-view-doc {
                order: 3;
                width: 100%;
                justify-content: center;
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
    <button class="menu-toggle" id="menuToggle" aria-label="Toggle Menu">☰</button>
    
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
            <div class="faq-header">
                <div>
                    <h2>FAQ Management</h2>
                    <p class="dashboard-subtitle">Manage frequently asked questions displayed on the FAQ page</p>
                </div>
                <button class="btn-add-faq" onclick="openAddModal()">
                    + Add New FAQ
                </button>
            </div>

            <div id="alertContainer"></div>

            <!-- FAQ TABLE -->
            <div class="faq-table">
                <?php if (mysqli_num_rows($result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 60px;">Order</th>
                            <th>Question</th>
                            <th>Answer</th>
                            <th style="width: 100px;">Status</th>
                            <th style="width: 150px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        mysqli_data_seek($result, 0);
                        while($row = mysqli_fetch_assoc($result)): 
                        ?>
                        <tr>
                            <td style="text-align: center;">
                                <div style="display: flex; flex-direction: column; gap: 4px; align-items: center;">
                                    <button class="btn-up" onclick="reorderFAQ(<?php echo $row['id']; ?>, 'up')" title="Move Up">↑</button>
                                    <span style="font-weight: bold;"><?php echo $row['display_order']; ?></span>
                                    <button class="btn-down" onclick="reorderFAQ(<?php echo $row['id']; ?>, 'down')" title="Move Down">↓</button>
                                </div>
                            </td>
                            <td class="faq-question-cell"><?php echo htmlspecialchars($row['question']); ?></td>
                            <td class="faq-answer-cell"><?php echo htmlspecialchars(substr($row['answer'], 0, 100)) . (strlen($row['answer']) > 100 ? '...' : ''); ?></td>
                            <td style="text-align: center;">
                                <div class="status-toggle-container">
                                    <label class="toggle-switch" 
                                        data-faq-id="<?php echo $row['id']; ?>"
                                        data-status="<?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>">
                                        <input 
                                            type="checkbox" 
                                            class="status-toggle"
                                            <?php echo $row['is_active'] ? 'checked' : ''; ?>
                                            data-faq-id="<?php echo $row['id']; ?>"
                                            onchange="toggleFAQ(<?php echo $row['id']; ?>)">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </td>
                            <td>
                                <div class='actions-cell'>
                                    <button class='action-btn edit' 
                                        onclick='openEditModal(<?php echo json_encode($row); ?>)'
                                        title='Edit FAQ'>
                                        <span>&#9998;</span>
                                    </button>
                                    <button class='action-btn delete' 
                                        onclick="deleteFAQ(<?php echo $row['id']; ?>)"
                                        title='Delete FAQ'>
                                        <span>&#128465;</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <h3>No FAQs Yet</h3>
                    <p>Click "Add New FAQ" to create your first frequently asked question.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- CONTACT SETTINGS SECTION -->
            <section class="settings-section">
                <h3>
                    <span>📞</span> Contact Settings
                </h3>
                <form class="settings-form" id="phoneForm">
                    <div class="form-group">
                        <label for="supportPhone">Support Phone Number *</label>
                        <input 
                            type="tel" 
                            id="supportPhone" 
                            name="phone" 
                            value="<?php echo htmlspecialchars($current_phone); ?>"
                            placeholder="+60 11-11577404"
                            required>
                        <div class="phone-preview">
                            <p><strong>Current Display:</strong> <span id="phoneDisplay"><?php echo htmlspecialchars($current_phone); ?></span></p>
                            <p style="margin-top: 4px; font-size: 12px; color: #718096;">This number will be displayed on the FAQ page help section</p>
                        </div>
                    </div>
                    <button type="submit" class="btn-update">Update Phone Number</button>
                </form>
            </section>

            <!-- DOCUMENTATION MANAGEMENT SECTION -->
            <section class="settings-section">
                <h3>
                    <span>📄</span> Documentation Management
                </h3>
                <form class="settings-form" id="docForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="userManual">User Manual / Documentation File</label>
                        
                        <div class="file-input-wrapper">
                            <input 
                                type="file" 
                                id="userManual" 
                                name="documentation" 
                                accept=".pdf,.doc,.docx">
                            
                            <label for="userManual" class="file-input-label" id="fileInputLabel">
                                <svg class="file-input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="17 8 12 3 7 8"/>
                                    <line x1="12" y1="3" x2="12" y2="15"/>
                                </svg>
                                <div class="file-input-text">
                                    <strong>Choose a file or drag it here</strong>
                                    <span>PDF, DOC, DOCX up to 10MB</span>
                                </div>
                            </label>
                        </div>
                        
                        <div class="selected-file-name" id="selectedFileName">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="file-details">
                                <span class="file-name" id="fileName"></span>
                                <span class="file-size" id="fileSize"></span>
                            </div>
                            <button type="button" class="btn-remove-file" onclick="clearFileInput()">Remove</button>
                        </div>
                        
                        <div class="file-info">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                            Accepted formats: PDF, DOC, DOCX (Max 10MB)
                        </div>
                        
                        <?php if (file_exists($current_doc)): ?>
                        <div class="current-file">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/>
                                <path d="M14 2v6h6"/>
                                <path d="M12 18v-6"/>
                                <path d="m9 15 3 3 3-3"/>
                            </svg>
                            <span id="currentDocName"><?php echo basename($current_doc); ?></span>
                            <a href="<?php echo htmlspecialchars($current_doc); ?>" target="_blank" class="btn-view-doc">View</a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="doc-preview">
                            <p><strong>Usage:</strong> This file will be available in the FAQ page "Documentation" section</p>
                            <p style="margin-top: 4px; font-size: 12px; color: #718096;">Users can access this file by clicking "View Docs" button</p>
                        </div>
                    </div>
                    <button type="submit" class="btn-update">Update Documentation</button>
                </form>
            </section>

            <!-- EMAIL MANAGEMENT SECTION -->
            <section class="settings-section">
                <h3>
                    <span>✉️</span> Email Management
                </h3>
                <form class="settings-form" id="emailForm">
                    <div class="form-group">
                        <label for="supportEmail">Support Email Address *</label>
                        <input 
                            type="email" 
                            id="supportEmail" 
                            name="email" 
                            value="<?php echo htmlspecialchars($current_email); ?>"
                            placeholder="support@facilityops.com"
                            required>
                        <div class="email-preview">
                            <p><strong>Current Email:</strong> <span id="emailDisplay"><?php echo htmlspecialchars($current_email); ?></span></p>
                            <p style="margin-top: 4px; font-size: 12px; color: #718096;">This email will receive all support requests submitted through the contact form</p>
                        </div>
                    </div>
                    <button type="submit" class="btn-update">Update Email Address</button>
                </form>
            </section>
            
            <!-- SMTP CONFIGURATION SECTION -->
            <section class="settings-section">
                <h3>
                    <span>⚙️</span> SMTP Configuration
                </h3>
                <form class="settings-form" id="smtpForm">
                    <div class="form-group">
                        <label for="smtpUsername">Gmail Address (SMTP Username) *</label>
                        <input 
                            type="email" 
                            id="smtpUsername" 
                            name="smtp_username" 
                            value="<?php echo htmlspecialchars($current_smtp_user); ?>"
                            placeholder="your-email@gmail.com"
                            required>
                        <div class="email-preview">
                            <p style="font-size: 12px; color: #718096;">This Gmail account will be used to send support emails</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="smtpPassword">Gmail App Password *</label>
                        <input 
                            type="password" 
                            id="smtpPassword" 
                            name="smtp_password" 
                            value="<?php echo htmlspecialchars($current_smtp_pass); ?>"
                            placeholder="xxxx xxxx xxxx xxxx"
                            required>
                        <div class="email-preview">
                            <p style="font-size: 12px; color: #718096;">
                                <strong>Note:</strong> Use Gmail App Password, not your regular password. 
                                <a href="https://myaccount.google.com/apppasswords" target="_blank" style="color: #2c5282;">Generate App Password →</a>
                            </p>
                        </div>
                    </div>
                    <button type="submit" class="btn-update">Update SMTP Settings</button>
                </form>
            </section>
        </main>
    </div>

    <!-- ADD/EDIT MODAL -->
    <div id="faqModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New FAQ</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>

            <form id="faqForm">
                <input type="hidden" id="faqId" name="id">
                <input type="hidden" id="formAction" name="action" value="add">

                <div class="form-group">
                    <label for="question">Question *</label>
                    <input type="text" id="question" name="question" required>
                </div>

                <div class="form-group">
                    <label for="answer">Answer *</label>
                    <textarea id="answer" name="answer" required></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Save FAQ</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../assets/js/admin.js"></script>
    <script src="../assets/js/manage-faq.js"></script>
    
    <script>
        // Enhanced file input handling with drag & drop
        const fileInput = document.getElementById('userManual');
        const fileLabel = document.getElementById('fileInputLabel');
        const selectedFileDiv = document.getElementById('selectedFileName');

        fileInput.addEventListener('change', function() {
            updateFileDisplay(this.files[0]);
        });

        // Drag and drop functionality
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileLabel.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            fileLabel.addEventListener(eventName, () => {
                fileLabel.classList.add('drag-over');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileLabel.addEventListener(eventName, () => {
                fileLabel.classList.remove('drag-over');
            }, false);
        });

        fileLabel.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
            updateFileDisplay(files[0]);
        }, false);

        function updateFileDisplay(file) {
            if (file) {
                fileLabel.classList.add('has-file');
                selectedFileDiv.classList.add('show');
                document.getElementById('fileName').textContent = file.name;
                document.getElementById('fileSize').textContent = `${(file.size / 1024 / 1024).toFixed(2)} MB`;
                
                fileLabel.querySelector('strong').textContent = 'File selected';
                fileLabel.querySelector('span').textContent = 'Click to change file';
            }
        }

        function clearFileInput() {
            fileInput.value = '';
            fileLabel.classList.remove('has-file');
            selectedFileDiv.classList.remove('show');
            fileLabel.querySelector('strong').textContent = 'Choose a file or drag it here';
            fileLabel.querySelector('span').textContent = 'PDF, DOC, DOCX up to 10MB';
        }

        // Handle phone number update
        document.getElementById('phoneForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_phone');
            
            fetch('faq_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    document.getElementById('phoneDisplay').textContent = data.phone;
                } else {
                    showAlert('error', data.message);
                }
            })
            .catch(error => {
                showAlert('error', 'An error occurred while updating the phone number');
                console.error('Error:', error);
            });
        });

        // Update phone display as user types
        document.getElementById('supportPhone').addEventListener('input', function() {
            document.getElementById('phoneDisplay').textContent = this.value || 'Not set';
        });

        // Handle documentation upload
        document.getElementById('docForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const file = fileInput.files[0];
            
            if (!file) {
                showAlert('error', 'Please select a file to upload');
                return;
            }
            
            // Validate file size (10MB max)
            if (file.size > 10 * 1024 * 1024) {
                showAlert('error', 'File size must be less than 10MB');
                return;
            }
            
            // Validate file type
            const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (!allowedTypes.includes(file.type)) {
                showAlert('error', 'Only PDF, DOC, and DOCX files are allowed');
                return;
            }
            
            const formData = new FormData(this);
            formData.append('action', 'update_documentation');
            
            // Show loading state
            const submitBtn = this.querySelector('.btn-update');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Uploading...';
            submitBtn.disabled = true;
            
            fetch('faq_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
                
                if (data.success) {
                    showAlert('success', data.message);
                    // Update current file display
                    if (data.filename) {
                        const currentFileDiv = document.querySelector('.current-file');
                        if (currentFileDiv) {
                            document.getElementById('currentDocName').textContent = data.filename;
                            currentFileDiv.querySelector('.btn-view-doc').href = data.filepath;
                        } else {
                            // Create new current file display
                            const fileInfo = document.querySelector('.file-info');
                            const newCurrentFile = document.createElement('div');
                            newCurrentFile.className = 'current-file';
                            newCurrentFile.innerHTML = `
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/>
                                    <path d="M14 2v6h6"/>
                                    <path d="M12 18v-6"/>
                                    <path d="m9 15 3 3 3-3"/>
                                </svg>
                                <span id="currentDocName">${data.filename}</span>
                                <a href="${data.filepath}" target="_blank" class="btn-view-doc">View</a>
                            `;
                            fileInfo.parentNode.insertBefore(newCurrentFile, fileInfo.nextSibling);
                        }
                    }
                    // Clear the file input
                    clearFileInput();
                } else {
                    showAlert('error', data.message);
                }
            })
            .catch(error => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
                showAlert('error', 'An error occurred while uploading the file');
                console.error('Error:', error);
            });
        });

        // Handle email update
        document.getElementById('emailForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_email');
            
            fetch('faq_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    document.getElementById('emailDisplay').textContent = data.email;
                } else {
                    showAlert('error', data.message);
                }
            })
            .catch(error => {
                showAlert('error', 'An error occurred while updating the email address');
                console.error('Error:', error);
            });
        });

        // Update email display as user types
        document.getElementById('supportEmail').addEventListener('input', function() {
            document.getElementById('emailDisplay').textContent = this.value || 'Not set';
        });
        
        // Handle SMTP update
        document.getElementById('smtpForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_smtp');
            
            fetch('faq_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                } else {
                    showAlert('error', data.message);
                }
            })
            .catch(error => {
                showAlert('error', 'An error occurred while updating SMTP settings');
                console.error('Error:', error);
            });
        });
    </script>
</body>
</html>