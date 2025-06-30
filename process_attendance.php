<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$user_id = $_SESSION['user_id'];
$current_date = date('Y-m-d');
$wifi_ssid = $_GET['wifi_ssid'] ?? '';

try {
    if ($action === 'check_in') {
        // Check if already checked in today
        $stmt = $conn->prepare("SELECT id FROM mhr_employee_attendance 
                             WHERE employee_id = ? AND DATE(check_in) = CURRENT_DATE");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Manual result handling for older mysqli versions
        $stmt->bind_result($id);
        if ($stmt->fetch() && $id !== null) {
            echo json_encode(['success' => false, 'message' => 'Already checked in today']);
            $stmt->close();
            exit;
        }

        // Process check-in
        $stmt = $conn->prepare("INSERT INTO mhr_employee_attendance 
                             (employee_id, check_in, check_in_ip, check_in_wifi_ssid) 
                             VALUES (?, NOW(), ?, ?)");
        $stmt->bind_param("iss", $user_id, $_SERVER['REMOTE_ADDR'], $wifi_ssid);
        $stmt->execute();

        echo json_encode(['success' => true]);
        $stmt->close();

    } elseif ($action === 'check_out') {
        $stmt = $conn->prepare("SELECT id FROM mhr_employee_attendance 
                             WHERE employee_id = ? AND DATE(check_in) = CURRENT_DATE 
                             AND check_out IS NULL");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'No active check-in found']);
            exit;
        }

        // Process check-out
        $stmt = $conn->prepare("UPDATE mhr_employee_attendance 
                             SET check_out = NOW(), 
                                 check_out_ip = ?, 
                                 check_out_wifi_ssid = ? 
                             WHERE employee_id = ? AND DATE(check_in) = CURRENT_DATE");
        $stmt->bind_param("ssi", $_SERVER['REMOTE_ADDR'], $wifi_ssid, $user_id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}