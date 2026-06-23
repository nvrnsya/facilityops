<?php
session_start();
include('config.php'); 

if (isset($_POST['register'])) {
    $name      = $connect->real_escape_string($_POST['name'] ?? '');
    $icnum     = $connect->real_escape_string($_POST['icnum'] ?? '');
    $staffid   = $connect->real_escape_string($_POST['staffid'] ?? '');
    $email     = $connect->real_escape_string($_POST['email'] ?? '');
    $phone_num = $connect->real_escape_string($_POST['phone_num'] ?? '');
    $depart    = $connect->real_escape_string($_POST['depart'] ?? '');

    // Check if staffid exists in admin_list
    $adminCheck = $connect->prepare("SELECT staffid FROM admin_list WHERE staffid = ?");
    $adminCheck->bind_param("s", $staffid);
    $adminCheck->execute();
    $adminResult = $adminCheck->get_result();

    // Tentukan role berdasarkan admin_list
    if ($adminResult->num_rows > 0) {
        $role = "admin";
    } else {
        $role = "staff";
    }

    // Check kalau user sudah wujud
    $check = $connect->prepare("SELECT * FROM users WHERE icnum = ? OR staffid = ? OR email = ?");
    $check->bind_param("sss", $icnum, $staffid, $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['message'] = "User already exists with same IC/StaffID/Email.";
        header("Location: ".$link."publicregister.php");
        exit();
    }

    // Insert user baru dengan phone_num dan depart dari form
    $stmt = $connect->prepare("INSERT INTO users (name, icnum, staffid, email, phone_num, depart, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $name, $icnum, $staffid, $email, $phone_num, $depart, $role);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Registration successful! Please login.";
        header("Location: ".$link."index.php");
        exit();
    } else {
        $_SESSION['message'] = "Error: Could not register user.";
        header("Location: ".$link."publicregister.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/images/favicon.png">
    <title>Register | FacilityOps</title>

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/publicregister.css">
</head>
<body>
    <!-- HEADER -->
    <div class="header" data-role="header" id="header">
        <header>
            <a class="logo-container">
                <img src="assets/images/favicon.png" alt="Logo" style="width: 32px; height: 32px; margin-right: 8px;">
                <h1>FacilityOps</h1>
            </a>
            <nav>
                <ul>
                    <li><a href="FAQpage.php">FAQ</a></li>
                    <li>|</li>
                    <li><a href="index.php">Login</a></li>
                </ul>
            </nav>
        </header>
    </div>

    <!-- MAIN CONTENT -->
    <div class="login-wrapper">
        <!-- Left side illustration -->
        <div class="login-image">
            <img src="assets/images/register-illustration.jpg" alt="Register Illustration">
        </div>

        <div class="glass-container">
            <h2>Register</h2>
            <?php 
            if (isset($_SESSION['message'])) {
                echo '<p style="color: #e53e3e; text-align: center; margin-bottom: 20px; font-size: 14px;">' . $_SESSION['message'] . '</p>';
                unset($_SESSION['message']);
            }
            ?>
            <form action="publicregister.php" method="post" class="login-form">
                <div class="input-group">
                    <input type="text" id="userName" name="name" required>
                    <label for="userName">Full Name</label>
                </div>

                <div class="input-group">
                    <input type="text" id="icNum" name="icnum" required>
                    <label for="icNum">NRIC Number</label>
                </div>

                <div class="input-group">
                    <input type="text" id="staffID" name="staffid" required>
                    <label for="staffID">Staff ID</label>
                </div>

                <div class="input-group">
                    <input type="email" id="emailAdd" name="email" required>
                    <label for="emailAdd">Email Address</label>
                </div>

                <div class="input-group">
                    <input type="tel" id="phoneNum" name="phone_num" required pattern="[0-9]{10,11}" placeholder=" ">
                    <label for="phoneNum">Phone Number</label>
                </div>

                <div class="input-group">
                    <input 
                        type="text" 
                        id="depart" 
                        name="depart" 
                        required
                        pattern="[A-Z]{2,8}(\/[A-Z]{2,8})*"
                        title="Abbreviation only (e.g: JKM, JKM/JSKK)"
                        placeholder=" "
                        oninput="this.value = this.value.toUpperCase()"
                    >
                    <label for="depart">Department <span style="font-size:11px; color:#aaa;">(e.g: JKM, JKM/JSKK)</span></label>
                </div>

                <button type="submit" name="register" class="register-btn">Register</button>
                <p class="login-link">
                    Already have an account? <a href="index.php">Login</a>
                </p>
            </form>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="simple-footer">
        <p>&copy; 2025 FacilityOps | Designed by Team Toman | All rights reserved.</p>
    </footer>
</body>
</html>