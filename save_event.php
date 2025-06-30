<?php

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
require_once 'config.php';
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json'); // Ensure the content is JSON
// Fetch data from POST request
$date = $_POST['date'] ?? null;
$name = $_POST['name'] ?? null;
//$event = $_POST['event'] ?? null;
$phone = $_POST['phone'];
$checkout = $_POST['checkout'];
$adults = $_POST['adults'];
$kids = $_POST['kids'];
$package = $_POST['package'];
$total_amount = $_POST['total_amount'];
$advance_amount = $_POST['advance_amount'];
$bal_amount = $total_amount - $advance_amount;

if (!$date || !$name || !$event) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}
try {
   // Check if event already exists for this date
$sql = "SELECT id FROM events WHERE event_date = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $date);
$stmt->execute();
// Bind the result to a variable
$stmt->bind_result($id);
$stmt->store_result(); // Store the result so we can check num_rows



if ($stmt->num_rows > 0) {

    // Update existing event

    $sql = "UPDATE events SET guest_name = ?, event_details = ? WHERE event_date = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("sss", $name, $event, $date);

} else {

    // Insert new event
// SQL query with placeholders for prepared statements
$sql = "INSERT INTO events (name, phone, event_date, checkout, adults, kids, package, total_amount, advance_amount) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

// Prepare the statement
$stmt = $conn->prepare($sql);

// Bind the parameters
$stmt->bind_param(
    "ssssiiidi",  // Data types: s - string, i - integer, d - double/float
    $name,
    $phone,
    $date,
    $checkout,
    $adults,
    $kids,
    $package,
    $total_amount,
    $advance_amount
);
// // Execute the statement
// $stmt->execute();
//     $sql = "INSERT INTO events (event_date, guest_name, event_details) VALUES (?, ?, ?)";
    
//     $stmt = $conn->prepare($sql);

//     $stmt->bind_param("sss", $date, $name, $event);

}
    $success = $stmt->execute();
    echo json_encode(['success' => $success]);
} catch (Exception $e) {
    // Return error in JSON format
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);

}

