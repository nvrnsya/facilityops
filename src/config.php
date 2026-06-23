<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Real credentials live in credentials.php (git-ignored, not in this repo).
// See credentials.example.php for the template.
require_once __DIR__ . '/credentials.php';

$connect = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if($connect->connect_errno)
{
	die('Connection failed : '.$connect->connect_error);
}

$conn = $connect;

// Settings
$link = 'https://facilityops.org/';
$title = 'Facility Operation System';

// SMTP Settings
$smtp_debug = '';
$smtp_host = '';
$smtp_icnum = '';
$smtp_staffid = '';
$smtp_port = '';

// Variables
$_SESSION['message'] = $_SESSION['message'] ?? '';

$name    = $connect->real_escape_string($_POST['name'] ?? '');
$email   = $connect->real_escape_string($_POST['email'] ?? '');
$icnum   = $connect->real_escape_string($_POST['icnum'] ?? '');
$staffid = $connect->real_escape_string($_POST['staffid'] ?? '');
$role    = $connect->real_escape_string($_POST['role'] ?? '');
$programe_name    = $connect->real_escape_string($_POST['programe_name'] ?? '');
$depart   = $connect->real_escape_string($_POST['depart'] ?? '');
$ext_office   = $connect->real_escape_string($_POST['ext_office'] ?? '');
$set_num   = $connect->real_escape_string($_POST['set_num'] ?? '');
$add_notes    = $connect->real_escape_string($_POST['add_notes'] ?? '');
$select_date   = $connect->real_escape_string($_POST['select_date'] ?? '');
$tel_phone   = $connect->real_escape_string($_POST['tel_phone'] ?? '');
$key_collect   = $connect->real_escape_string($_POST['key_collect'] ?? '');
$key_delivery   = $connect->real_escape_string($_POST['key_delivery'] ?? '');
?>