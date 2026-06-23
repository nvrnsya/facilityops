<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include("../config.php");

// Pastikan user dah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get redirect parameter
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

// Fetch data user dari table users
$sql = "SELECT * FROM users WHERE users_id='$user_id'";
$result = $connect->query($sql);
$user = $result->fetch_assoc();

// Update bila klik save
if (isset($_POST['save'])) {
    $name      = $connect->real_escape_string($_POST['name']);
    $icnum      = $connect->real_escape_string($_POST['icnum']);
    $email     = $connect->real_escape_string($_POST['email']);
    $staffid   = $connect->real_escape_string($_POST['staffid']);
    $phone_num = $connect->real_escape_string($_POST['phone_num']);
    $depart    = $connect->real_escape_string($_POST['depart']);
    $role      = $connect->real_escape_string($_POST['role']);
    
    // Get redirect from hidden field
    $redirect_to = isset($_POST['redirect']) ? $_POST['redirect'] : '';

    // Validate email is not empty
    if (empty($email)) {
        echo "<script>alert('Email address is required!'); window.location.href='edit-profile.php" . ($redirect_to ? "?redirect=$redirect_to" : "") . "';</script>";
        exit();
    }

    $update = "UPDATE users 
               SET name='$name', email='$email', staffid='$staffid', phone_num='$phone_num', depart='$depart', role='$role'
               WHERE users_id='$user_id'";

    if ($connect->query($update) === TRUE) {
        // Redirect based on parameter
        if ($redirect_to === 'bookingpage') {
            echo "<script>alert('Profile updated successfully! You can now proceed with booking.'); window.location.href='bookingpage.php';</script>";
        } else {
            echo "<script>alert('Profile updated successfully'); window.location.href='profile.php';</script>";
        }
    } else {
        echo "Error: " . $connect->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/images/favicon.png">
    <title>Edit Profile | FacilityOps</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/edit-profile.css">
    <link rel="stylesheet" href="../assets/css/profile-menu.css">
    <link rel="stylesheet" href="../assets/css/base.css">
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

    <!-- BACK BUTTON -->
    <div class="back-button-container">
        <button onclick="history.back()" class="back-btn">
            ← 
        </button>
    </div>       

    <!-- CONTENT -->
    <h2>Edit Profile</h2>
    <div class="container">
        <div class="card">
            <h3>USER INFORMATION</h3>
            
            <!-- Show reminder if came from bookingpage -->
            <?php if ($redirect === 'bookingpage'): ?>
                <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #856404; font-weight: 600;">
                        ⚠️ Please add your email address to proceed with booking. Email is required for booking notifications.
                    </p>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <!-- Hidden field to preserve redirect parameter -->
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?php echo $user['name']; ?>">
                </div>
    
                <div class="form-group">
                    <label>NRIC Number</label>
                    <input type="text" name="icnum" value="<?php echo htmlspecialchars($user['icnum']); ?>">
                </div>
            
                <div class="form-group">
                    <label>Email Address <span style="color: red;">*</span></label>
                    <input type="email" name="email" value="<?php echo $user['email']; ?>" 
                           placeholder="Enter your email address" required>
                    <!-- Add helper text -->
                    <small style="color: #666; font-size: 12px;">Required for booking notifications</small>
                </div>
    
                <div class="form-group">
                    <label>Staff ID<span>*</span></label>
                    <input type="text" value="<?php echo $user['staffid']; ?>">
                    <input type="hidden" name="staffid" value="<?php echo $user['staffid']; ?>">
                </div>
                
                <div class="form-group">
                    <label>Phone Number<span>*</span></label>
                    <input type="text" name="phone_num" value="<?php echo $user['phone_num']; ?>">
                </div>
    
                <div class="form-group">
                    <label>Department<span>*</span></label>
                    <input type="text" name="depart" value="<?php echo $user['depart']; ?>">
                </div>
    
                <div class="form-group">
                    <label>Role<span>*</span></label>
                    <input type="text" value="<?php echo $user['role']; ?>" disabled>
                    <input type="hidden" name="role" value="<?php echo $user['role']; ?>">
                </div>
    
                <!-- SAVE BUTTON -->
                <div class="form-actions">
                    <button type="submit" name="save" class="save-btn">Save Changes</button>
                    
                    <!--  Add cancel button if came from bookingpage -->
                    <?php if ($redirect === 'bookingpage'): ?>
                        <button type="button" onclick="window.location.href='bookingpage.php'" 
                                style="background: #6c757d; margin-left: 10px;" class="save-btn">
                            Cancel
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script>
        // =======================
        // Back to Top 
        // =======================
        const backToTopBtn = document.createElement('button');
        backToTopBtn.id = 'backToTop';
        backToTopBtn.innerHTML = '↑';
        backToTopBtn.title = 'Back to Top';
        document.body.appendChild(backToTopBtn);

        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        });

        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html>