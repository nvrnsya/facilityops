<?php
session_start();
include('config.php');

// ======= CHECK REMEMBER TOKEN =======
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $connect->real_escape_string($_COOKIE['remember_token']);
    
    $stmt = $connect->prepare("SELECT * FROM users WHERE remember_token = ? AND remember_token != ''");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['users_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['staffid'] = $user['staffid']; // ✅ ADDED
        $_SESSION['name'] = $user['name']; // ✅ ADDED
        redirectByRole($user, $connect);
        exit();
    }
}

// ======= HANDLE LOGIN FORM =======
if (isset($_POST['login'])) {
    $icnum = $connect->real_escape_string($_POST['icnum'] ?? '');
    $staffid = $connect->real_escape_string($_POST['staffid'] ?? '');

    $stmt = $connect->prepare("SELECT * FROM users WHERE icnum = ? AND staffid = ?");
    $stmt->bind_param("ss", $icnum, $staffid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['users_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['staffid'] = $user['staffid']; // ✅ ADDED - INI PENTING!
        $_SESSION['name'] = $user['name']; // ✅ ADDED
        $_SESSION['email'] = $user['email']; // ✅ ADDED
        $_SESSION['depart'] = $user['depart']; // ✅ ADDED

        // ======= REMEMBER ME =======
        if (isset($_POST['remember'])) {
            $token = bin2hex(random_bytes(16));
            $stmt = $connect->prepare("UPDATE users SET remember_token = ? WHERE users_id = ?");
            $stmt->bind_param("si", $token, $user['users_id']);
            $stmt->execute();
            setcookie("remember_token", $token, time() + (86400 * 30), "/", "", false, true);
        } else {
            $stmt = $connect->prepare("UPDATE users SET remember_token = NULL WHERE users_id = ?");
            $stmt->bind_param("i", $user['users_id']);
            $stmt->execute();
            if (isset($_COOKIE['remember_token'])) {
                setcookie("remember_token", "", time() - 3600, "/");
            }
        }

        // ======= REDIRECT BERDASARKAN ROLE & ADMIN_LIST =======
        redirectByRole($user, $connect);
        exit();

    } else {
        $_SESSION['message'] = 'Login failed: Invalid IC number or Staff ID';
        header('Location: '.$link.'index.php');
        exit();
    }
}

// ======= FUNCTION REDIRECT =======
function redirectByRole($user, $connect) {
    global $link;

    $role = $user['role'];
    $staffid = $user['staffid'];

    // Semak admin_list jika role = admin / administration
    $isAdminAllowed = false;
    if ($role === 'admin' || $role === 'administration') {
        $check = $connect->prepare("SELECT * FROM admin_list WHERE staffid = ?");
        $check->bind_param("s", $staffid);
        $check->execute();
        $res = $check->get_result();
        if ($res->num_rows > 0) {
            $isAdminAllowed = true;
        }
    }

    if ($role === 'staff') {
        header('Location: '.$link.'staff/landpage.php');
    } elseif (($role === 'admin' || $role === 'administration') && $isAdminAllowed) {
        // admin & administration yang tersenarai dalam admin_list
        $redirects = [
            'admin'          => $link.'admin/landpage.php',
            'administration' => $link.'administration/landpage.php'
        ];
        header('Location: '.$redirects[$role]);
    } else {
        // access denied untuk admin yg tak disenaraikan
        $_SESSION['message'] = "Access denied: You are not authorized as admin.";
        header('Location: '.$link.'index.php');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/images/favicon.png">
    <title>Login | FacilityOps</title>

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/logininternal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <li><a href="publicregister.php">Register</a></li>
                </ul>
            </nav>
        </header>
    </div>

    <!-- MAIN CONTENT -->
    <div class="login-wrapper">
        <!-- Left side illustration -->
        <div class="login-image">
            <img src="assets/images/login-illustration.jpg" alt="Login Illustration">
        </div>

        <!-- Right side form -->
        <div class="glass-container">
            <h2>Login</h2>
            <?php if(isset($_SESSION['message']) && !empty($_SESSION['message'])): ?>
                <div class="error-message">
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>
            
            <form action="index.php" method="post" id="loginForm">
                <div class="input-group">
                    <input type="text" name="icnum" id="icnum" required>
                    <label>I/C Number</label>
                </div>

                <div class="input-group">
                    <input type="text" name="staffid" id="staffid" required>
                    <label>Staff Number</label>
                </div>

                <div class="remember-forgot">
                    <label><input type="checkbox" name="remember" id="remember">Remember me</label>
                </div>

                <button type="submit" name="login" class="login-btn">Login</button>

                <div class="register-link">
                    <p>Don't have an account? <a href="publicregister.php">Register</a></p>
                </div>
            </form>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="simple-footer">
        <p>&copy; 2025 FacilityOps | Designed by Team Toman | All rights reserved.</p>
    </footer>

    <script>
        // Check for saved credentials on page load
        window.addEventListener('load', function() {
            // Check if there's a saved preference to remember credentials
            const savedIC = localStorage.getItem('saved_ic');
            const savedStaffID = localStorage.getItem('saved_staffid');
            const rememberChecked = localStorage.getItem('remember_checked');
            
            if (rememberChecked === 'true' && savedIC && savedStaffID) {
                document.getElementById('icnum').value = savedIC;
                document.getElementById('staffid').value = savedStaffID;
                document.getElementById('remember').checked = true;
            }
        });

        // Save credentials when form is submitted with remember me checked
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const icnum = document.getElementById('icnum').value;
            const staffid = document.getElementById('staffid').value;
            const remember = document.getElementById('remember').checked;
            
            if (remember) {
                localStorage.setItem('saved_ic', icnum);
                localStorage.setItem('saved_staffid', staffid);
                localStorage.setItem('remember_checked', 'true');
            } else {
                localStorage.removeItem('saved_ic');
                localStorage.removeItem('saved_staffid');
                localStorage.removeItem('remember_checked');
            }
        });
    </script>
</body>
</html>