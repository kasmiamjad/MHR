<?php
require_once 'config.php';

header('Content-Type: application/json');

$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-01');
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-d');

// Get summary stats
$summaryQuery = "
    SELECT 
        COALESCE(SUM(amount), 0) as total,
        COALESCE(SUM(CASE WHEN LOWER(payment_type) = 'cash' THEN amount ELSE 0 END), 0) as cash,
        COALESCE(SUM(CASE WHEN LOWER(payment_type) = 'online' THEN amount ELSE 0 END), 0) as online
    FROM mhr_event_payments 
    WHERE deleted_at IS NULL 
    AND payment_date BETWEEN '$startDate' AND '$endDate 23:59:59'
";

$summaryResult = $conn->query($summaryQuery);
$summary = $summaryResult->fetch_assoc();

// Get user-wise summary
$userSummaryQuery = "
        SELECT 
        COALESCE(u.username, 'System') as created_by,
        COALESCE(SUM(amount), 0) as total,
        COALESCE(SUM(CASE WHEN LOWER(payment_type) = 'cash' THEN amount ELSE 0 END), 0) as cash,
        COALESCE(SUM(CASE WHEN LOWER(payment_type) = 'online' THEN amount ELSE 0 END), 0) as online
    FROM mhr_event_payments 
    LEFT JOIN mhr_users u ON mhr_event_payments.created_by = u.id
    WHERE deleted_at IS NULL 
    AND payment_date BETWEEN '$startDate' AND '$endDate 23:59:59'
    GROUP BY created_by
";

$userSummaryResult = $conn->query($userSummaryQuery);
$userSummary = [];
while ($row = $userSummaryResult->fetch_assoc()) {
    $userSummary[] = $row;
}

// Get detailed payment records
$paymentsQuery = "
   SELECT 
    p.id,
    p.event_id,
    p.amount,
    COALESCE(p.payment_type, 'Not Specified') as payment_type,
    p.payment_date,
    p.transaction_id,
    p.payment_status,
    p.created_at,
    COALESCE(u.username, 'System') as created_by,
    e.guest_name,
    e.event_date as booking_date,
    CONCAT(
        'Payment of ₹', FORMAT(p.amount, 2),
        ' received via ', 
        CASE 
            WHEN p.payment_type = '' OR p.payment_type IS NULL THEN 'Not Specified'
            ELSE p.payment_type
        END,
        ' for booking #', p.event_id
    ) as description
FROM mhr_event_payments p
JOIN events e ON p.event_id = e.id
LEFT JOIN mhr_users u ON p.created_by = u.id
WHERE p.deleted_at IS NULL 
AND p.payment_date BETWEEN '$startDate' AND '$endDate 23:59:59'
ORDER BY p.payment_date DESC
";

$paymentsResult = $conn->query($paymentsQuery);
$payments = [];
while ($row = $paymentsResult->fetch_assoc()) {
    $payments[] = $row;
}

$response = [
    'summary' => $summary,
    'userSummary' => $userSummary,
    'payments' => $payments
];

echo json_encode($response);
die();
?>