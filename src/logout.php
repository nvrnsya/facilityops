<?php
session_start();
include('config.php');

// kosongkan remember_token di DB
if(isset($_SESSION['user_id'])) {
    $stmt = $connect->prepare("UPDATE users SET remember_token=NULL WHERE users_id=?");
    $stmt->bind_param("i", $_SESSION['user_id']); 
    $stmt->execute();
}

session_destroy();

// padam cookie
setcookie("remember_token", "", time() - 3600, "/");

header("Location: index.php");
exit();

?>