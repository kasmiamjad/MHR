<?php
require_once 'config.php';

$sql = "
    SELECT 
        p.id,
        p.event_id,
        p.amount,
        p.payment_type,
        p.payment_date,
        p.transaction_id,
        p.payment_status,
        p.created_at,
        p.created_by,
        e.guest_name,
        e.event_date as booking_date,
        'PAYMENT' as type,
        CONCAT('Payment of ₹', p.amount, ' received for booking #', p.event_id) as description
    FROM mhr_event_payments p
    JOIN events e ON p.event_id = e.id
    WHERE p.deleted_at IS NULL
    ORDER BY p.created_at DESC
    LIMIT 10
";

$result = $conn->query($sql);
$activities = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($activities);
?>