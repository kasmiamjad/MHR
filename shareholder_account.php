<?php
session_start();
require_once 'config.php';

// Ensure user is authenticated
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Fetch shareholders
$shareholders = [];
$result = $conn->query("SELECT id, name FROM mhr_shareholders ORDER BY name");
if (!$result) {
    die("Query failed: " . $conn->error);
}
while ($row = $result->fetch_assoc()) {
    $shareholders[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shareholder_id = $_POST['shareholder_id'];
    $amount = $_POST['amount'];
    $transaction_type = $_POST['transaction_type'];
    $mode = $_POST['mode'];
    $description = $_POST['description'];
    $date = $_POST['date'] ?: date('Y-m-d');

    $stmt = $conn->prepare("INSERT INTO mhr_shareholder_transactions (shareholder_id, date, amount, transaction_type, mode, description) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isdsss", $shareholder_id, $date, $amount, $transaction_type, $mode, $description);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Transaction recorded successfully.";
        $_SESSION['message_type'] = "success";
        header("Location: shareholder_summary.php");
        exit;
    } else {
        $error = "Error saving transaction: " . $stmt->error;
    }
}

include 'header.php';
?>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Add Shareholder Transaction</h2>
        <a href="shareholder_summary.php" class="btn btn-info">View Shareholder Summary</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="shareholder_id">Select Shareholder</label>
            <select name="shareholder_id" id="shareholder_id" class="form-control" required>
                <option value="">Select</option>
                <?php foreach ($shareholders as $sh): ?>
                    <option value="<?= $sh['id'] ?>"><?= htmlspecialchars($sh['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="transaction_type">Transaction Type</label>
            <select name="transaction_type" id="transaction_type" class="form-control" required>
                <option value="">Select</option>
                <option value="withdrawal">Withdrawal</option>
                <option value="deposit">Deposit</option>
                <option value="share">Profit Share</option>
                <option value="adjustment">Adjustment</option>
            </select>
        </div>

        <div class="form-group">
            <label for="mode">Mode</label>
            <select name="mode" id="mode" class="form-control" required>
                <option value="">Select Mode</option>
                <option value="cash">Cash</option>
                <option value="account">Account</option>
            </select>
        </div>

        <div class="form-group">
            <label for="amount">Amount</label>
            <input type="number" step="0.01" name="amount" id="amount" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="date">Date</label>
            <input type="date" name="date" id="date" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" class="form-control"></textarea>
        </div>

        <button type="submit" class="btn btn-success">Save Transaction</button>
        <a href="shareholder_summary.php" class="btn btn-secondary">Back</a>
    </form>
</div>
<?php include 'footer.php'; ?>
