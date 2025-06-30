<?php
require_once 'config.php';

// Function to get all dates between start and end dates
function getDatesInRange($start, $end) {
    $dates = [];
    $current = new DateTime($start);
    $end_date = new DateTime($end);

    // Exclude the checkout date
    $end_date->modify('-1 day');

    while ($current <= $end_date) {
        $dates[] = $current->format('Y-m-d');
        $current->modify('+1 day');
    }

    return $dates;
}

// Fetch events with their full date range
$sql = "SELECT event_date, checkout, guest_name 
        FROM events 
        WHERE deleted_at IS NULL";
$result = $conn->query($sql);

$events = [];
while ($row = $result->fetch_assoc()) {
    // Get all dates for this booking (excluding checkout date)
    $booking_dates = getDatesInRange($row['event_date'], $row['checkout']);
    
    // Mark all dates as booked
    foreach ($booking_dates as $date) {
        $events[$date] = [
            'booked' => true,
            'guest_name' => $row['guest_name']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($events);
?>