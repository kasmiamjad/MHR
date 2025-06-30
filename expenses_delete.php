<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Location: login.php');
    // echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    // exit;
}
require_once 'config.php';

// Validate the ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $sql = "UPDATE mhr_expenses SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Expense deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete the expense.";
    }
} else {
    $_SESSION['error'] = "Invalid expense ID.";
}

header('Location: expenses_list.php');
exit;
