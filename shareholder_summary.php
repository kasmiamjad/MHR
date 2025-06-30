<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}


// Delete logic if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM mhr_shareholders WHERE id = $id");
    $conn->query("DELETE FROM mhr_shareholder_transactions WHERE shareholder_id = $id");
    $_SESSION['message'] = "Shareholder and related transactions deleted successfully.";
    $_SESSION['message_type'] = "success";
    header("Location: shareholder_summary.php");
    exit;
}

// Fetch all shareholders and their balances
$sql = "SELECT s.id, s.name,
        COALESCE(SUM(CASE WHEN t.transaction_type IN ('deposit','share') THEN t.amount ELSE 0 END), 0) as total_in,
        COALESCE(SUM(CASE WHEN t.transaction_type = 'withdrawal' THEN t.amount ELSE 0 END), 0) as total_out,
        COALESCE(SUM(CASE WHEN t.transaction_type IN ('deposit','share') THEN t.amount ELSE 0 END), 0) - 
        COALESCE(SUM(CASE WHEN t.transaction_type = 'withdrawal' THEN t.amount ELSE 0 END), 0) as balance
        FROM mhr_shareholders s
        LEFT JOIN mhr_shareholder_transactions t ON s.id = t.shareholder_id AND YEAR(t.date) = YEAR(CURDATE()) AND MONTH(t.date) = MONTH(CURDATE())
        GROUP BY s.id";

$result = $conn->query($sql);
$shareholders = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $shareholders[] = $row;
    }
} else {
    die("Query failed: " . $conn->error);
}

include 'header.php';
?>
<!-- existing CSS & JS includes -->

<div class="container mt-5">
    <h2 class="mb-4">Shareholder Account Summary</h2>
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?>">
            <?= $_SESSION['message']; unset($_SESSION['message'], $_SESSION['message_type']); ?>
        </div>
    <?php endif; ?>
    <table class="table table-bordered table-hover" id="shareholderTable">
        <thead class="thead-light">
            <tr>
                <th>#</th>
                <th>Shareholder Name</th>
                <th>Total In (Deposit + Share)</th>
                <th>Total Out (Withdrawals)</th>
                <th>Current Balance</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($shareholders as $index => $s): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($s['name']) ?></td>
                    <td>₹<?= number_format($s['total_in'], 2) ?></td>
                    <td>₹<?= number_format($s['total_out'], 2) ?></td>
                    <td><strong>₹<?= number_format($s['balance'], 2) ?></strong></td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="loadDetails(<?= $s['id'] ?>)">Details</button>
                        <a href="?delete=<?= $s['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div id="detailsSection" class="mt-4"></div>

    <a href="shareholder_account.php" class="btn btn-primary mt-3">Add New Transaction</a>
</div>

<script>
function loadDetails(id) {
    $.ajax({
        url: 'fetch_shareholder_transactions.php',
        type: 'GET',
        data: { id },
        success: function(data) {
            $('#detailsSection').html(data);
        }
    });
}

$(document).ready(function() {
    $('#shareholderTable').DataTable();
});
</script>
<?php include 'footer.php'; ?>
