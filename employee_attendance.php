<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT 
        DATE_FORMAT(check_in, '%h:%i %p') AS check_in,
        DATE_FORMAT(check_out, '%h:%i %p') AS check_out,
        CASE 
            WHEN check_in IS NULL THEN 'Not Marked'
            WHEN check_out IS NULL THEN 'Checked In'
            ELSE 'Checked Out'
        END AS status
    FROM employee_attendance
    WHERE employee_id = ? AND DATE(check_in) = CURRENT_DATE");
    $stmt->execute([$_SESSION['user_id']]);

    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode($row);
    } else {
        echo json_encode([
            'check_in' => null,
            'check_out' => null,
            'status' => 'Not Marked'
        ]);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}