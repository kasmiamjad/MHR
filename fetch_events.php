<?php
session_start();
require_once 'config.php';

$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Format month as two digits
$month = str_pad($month, 2, '0', STR_PAD_LEFT);

// Fetch events with proper total and advance amounts
$sql = "
    SELECT
        e.id, e.guest_name, e.phone, e.event_date, e.checkout,
        e.adults, e.kids, e.package,
        COALESCE(e.total_amount, 0) AS total_amount,
        (
            SELECT COALESCE(SUM(p.amount), 0)
            FROM mhr_event_payments p
            WHERE p.event_id = e.id
        ) AS advance_amount,
        (
            SELECT COUNT(*) 
            FROM mhr_maintenance_event_logs m 
            WHERE m.event_id = e.id
        ) AS maintenance_count,
        (
            SELECT COUNT(*) 
            FROM mhr_maintenance_event_logs m 
            WHERE m.event_id = e.id AND m.status = 'not_ok'
        ) AS maintenance_issues
    FROM events e
    WHERE DATE_FORMAT(e.event_date, '%Y-%m') = '$year-$month'
      AND e.deleted_at IS NULL
    ORDER BY e.event_date ASC
";

// $sql = "
//     SELECT
//         e.id, e.guest_name, e.phone, e.event_date, e.checkout,
//         e.adults, e.kids, e.package,
//         COALESCE(e.total_amount, 0) AS total_amount,
//         (
//             SELECT COALESCE(SUM(p.amount), 0)
//             FROM mhr_event_payments p
//             WHERE p.event_id = e.id
//         ) AS advance_amount
//     FROM events e
//     WHERE DATE_FORMAT(e.event_date, '%Y-%m') = '$year-$month'
//       AND e.deleted_at IS NULL
//     ORDER BY e.event_date ASC
// ";

$result = $conn->query($sql);

$events = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Ensure numeric values for calculations
        $row['total_amount'] = floatval($row['total_amount']);
        $row['advance_amount'] = floatval($row['advance_amount']);

        // Add maintenance status flags
        $row['is_maintenance_filled'] = intval($row['maintenance_count']) > 0;
        $row['has_maintenance_issue'] = intval($row['maintenance_issues']) > 0;
        
        $events[] = $row;
    }
}

echo json_encode($events);
?>