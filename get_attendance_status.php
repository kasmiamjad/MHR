<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

try {
    // Fetch the attendance status
    $stmt = $conn->prepare("SELECT
        e.id AS attendance_id,
        DATE_FORMAT(e.check_in, '%h:%i %p') AS check_in,
        DATE_FORMAT(e.check_out, '%h:%i %p') AS check_out,
        CASE
            WHEN e.check_in IS NULL THEN 'Not Marked'
            WHEN e.check_out IS NULL THEN 'Checked In'
            ELSE 'Checked Out'
        END AS status,
        e.check_in_ip AS check_in_ip,
        e.check_out_ip AS check_out_ip
    FROM mhr_employee_attendance e
    WHERE e.employee_id = ? AND DATE(e.check_in) = CURRENT_DATE");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();

    // Manual result handling for older mysqli versions
    $stmt->bind_result(
        $attendance_id, $check_in, $check_out, $status, $check_in_ip, $check_out_ip
    );

    if ($stmt->fetch()) {
        echo json_encode([
            'attendance_id' => $attendance_id,
            'check_in' => $check_in,
            'check_out' => $check_out,
            'status' => $status,
            'check_in_ip' => $check_in_ip,
            'check_out_ip' => $check_out_ip,
            'is_office_network' => true
        ]);
    } else {
        echo json_encode([
            'check_in' => null,
            'check_out' => null,
            'status' => 'Not Marked',
            'is_office_network' => true
        ]);
    }

    $stmt->close();
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}