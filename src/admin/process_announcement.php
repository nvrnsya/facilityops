<?php
include("../config.php");

$action = $_REQUEST['action'] ?? '';

// ========== ADD NEW ANNOUNCEMENT ==========
if ($action == 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $start_date = $_POST['start_date'];
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : NULL;
    $status = $_POST['status'];
    $announcement_type = $_POST['announcement_type']; // Get from hidden field
    
    $sql = "INSERT INTO announcements (title, content, start_date, end_date, status, announcement_type) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $title, $content, $start_date, $end_date, $status, $announcement_type);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Announcement added successfully!";
    } else {
        $_SESSION['error'] = "Failed to add announcement: " . $connect->error;
    }
    
    $stmt->close();
    header("Location: edit-announcement.php");
    exit();
}

// ========== UPDATE ANNOUNCEMENT ==========
elseif ($action == 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $start_date = $_POST['start_date'];
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : NULL;
    $status = $_POST['status'];
    $announcement_type = $_POST['announcement_type'];
    
    $sql = "UPDATE announcements 
            SET title = ?, content = ?, start_date = ?, end_date = ?, status = ? 
            WHERE announcement_id = ? AND announcement_type = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssis", $title, $content, $start_date, $end_date, $status, $id, $announcement_type);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Announcement updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update announcement: " . $conn->error;
    }
    
    $stmt->close();
    header("Location: edit-announcement.php");
    exit();
}

// ========== DELETE ANNOUNCEMENT ==========
elseif ($action == 'delete') {
    $id = intval($_GET['id']); 
    
    $sql = "DELETE FROM announcements WHERE announcement_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Announcement deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete announcement: " . $connect->error;
    }
    
    $stmt->close();
    header("Location: edit-announcement.php");
    exit();
}

// ========== INVALID ACTION ==========
else {
    $_SESSION['error'] = "Invalid action!";
    header("Location: edit-announcement.php");
    exit();
}
?>