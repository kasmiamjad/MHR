<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    exit('Unauthorized');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    exit('Invalid shareholder ID');
}

$id = (int)$_GET['id'];

$sql = "SELECT date, transaction_type, mode, amount, description FROM mhr_shareholder_transactions WHERE shareholder_id = $id AND YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE()) ORDER BY date DESC";
$result = $conn->query($sql);

if (!$result) {
    echo '<p>Error: ' . $conn->error . '</p>';
    exit;
}

if ($result->num_rows === 0) {
    echo '<p>No transactions found for this shareholder.</p>';
} else {
    echo '<h4>Transaction Details</h4>';
    echo '<table class="table table-bordered">';
    echo '<thead><tr><th>Date</th><th>Type</th><th>Mode</th><th>Amount</th><th>Note</th></tr></thead><tbody>';
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['date']) . '</td>';
        echo '<td>' . htmlspecialchars($row['transaction_type']) . '</td>';
        echo '<td>' . htmlspecialchars($row['mode']) . '</td>';
        echo '<td>₹' . number_format($row['amount'], 2) . '</td>';
        echo '<td>' . htmlspecialchars($row['description']) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
