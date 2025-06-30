<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Get today's new bookings
    $booking_sql = "SELECT COUNT(*) as count 
                   FROM events 
                   WHERE DATE(created_at) = CURDATE() 
                   AND deleted_at IS NULL";
    $stmt = $conn->prepare($booking_sql);
    $stmt->execute();
    $stmt->bind_result($new_bookings);
    $stmt->fetch();
    $stmt->close();

    // Get today's new expenses
    $expense_sql = "SELECT COUNT(*) as count 
                   FROM mhr_expenses 
                   WHERE DATE(created_at) = CURDATE() 
                   AND deleted_at IS NULL";
    $stmt = $conn->prepare($expense_sql);
    $stmt->execute();
    $stmt->bind_result($new_expenses);
    $stmt->fetch();
    $stmt->close();

    echo json_encode([
        'bookings' => $new_bookings,
        'expenses' => $new_expenses,
        'total' => $new_bookings + $new_expenses
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>