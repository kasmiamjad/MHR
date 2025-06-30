<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    exit;
}

require_once 'config.php';

$date = $_POST['date'];

$sql = "DELETE FROM events WHERE event_date = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $date);
$success = $stmt->execute();

header('Content-Type: application/json');
echo json_encode(['success' => $success]);
