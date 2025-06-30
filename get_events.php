<?php

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Location: login.php');
    // echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    // exit;
}

require_once 'config.php';



// Prepare events array

$events = [];



// Execute SQL query and handle errors

$sql = "SELECT event_date, guest_name, event_details FROM events";

if ($result = $conn->query($sql)) {

    while ($row = $result->fetch_assoc()) {

        $date = $row['event_date'];

        $events[$date] = [

            'name' => $row['guest_name'],

            'event' => $row['event_details']

        ];

    }

} else {

    // Log error or send a JSON error message

    error_log("Database query failed: " . $conn->error);

    echo json_encode(["error" => "Database query failed"]);

    exit;

}



header('Content-Type: application/json');

echo json_encode($events);

?>

