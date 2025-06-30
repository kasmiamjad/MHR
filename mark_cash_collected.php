<?php
// mark_cash_collected.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('Unauthorized');
}

if (!isset($_POST['id'])) {
    http_response_code(400);
    die('Missing ID');
}

$id = $_POST['id'];
$admin_id = $_SESSION['user_id'];
$current_time = date('Y-m-d H:i:s');

$update_sql = "UPDATE mhr_cash_collections 
               SET status = 'collected', 
                   collected_by = ?, 
                   collected_at = ?
               WHERE id = ?";

$stmt = mysqli_prepare($conn, $update_sql);
mysqli_stmt_bind_param($stmt, "isi", $admin_id, $current_time, $id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Update failed']);
}
?>