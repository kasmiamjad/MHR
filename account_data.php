<?php
session_start();
require_once 'config.php';

$opening_account_balance = 8825;
$opening_cash_balance = 000;
$opening_date = '2025-06-01';

try {
    $payment_account_sql = "SELECT COALESCE(SUM(amount), 0) as total_account FROM mhr_event_payments WHERE payment_date >= ? AND payment_type = 'online' AND deleted_at IS NULL";
    $payment_cash_sql = "SELECT COALESCE(SUM(amount), 0) as total_cash FROM mhr_event_payments WHERE payment_date >= ? AND payment_type = 'cash' AND deleted_at IS NULL";
    $expense_account_sql = "SELECT COALESCE(SUM(amount), 0) FROM mhr_expenses WHERE expense_date >= ? AND payment_mode = 'online' AND deleted_at IS NULL";
    $expense_cash_sql = "SELECT COALESCE(SUM(amount), 0) FROM mhr_expenses WHERE expense_date >= ? AND payment_mode = 'cash' AND deleted_at IS NULL";
    $shareholder_sql = "SELECT 
                            COALESCE(SUM(CASE WHEN transaction_type IN ('withdrawal') AND mode = 'online' THEN amount ELSE 0 END), 0) as account_withdrawals,
                            COALESCE(SUM(CASE WHEN transaction_type IN ('withdrawal') AND mode = 'cash' THEN amount ELSE 0 END), 0) as cash_withdrawals
                        FROM mhr_shareholder_transactions 
                        WHERE date >= ?";

    $stmt = $conn->prepare($payment_account_sql);
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param("s", $opening_date);
    $stmt->execute();
    $stmt->bind_result($total_account_received);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare($payment_cash_sql);
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param("s", $opening_date);
    $stmt->execute();
    $stmt->bind_result($total_cash_received);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare($expense_account_sql);
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param("s", $opening_date);
    $stmt->execute();
    $stmt->bind_result($total_account_expenses);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare($expense_cash_sql);
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param("s", $opening_date);
    $stmt->execute();
    $stmt->bind_result($total_cash_expenses);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare($shareholder_sql);
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param("s", $opening_date);
    $stmt->execute();
    $stmt->bind_result($account_withdrawals, $cash_withdrawals);
    $stmt->fetch();
    $stmt->close();

    $current_account_balance = $opening_account_balance + $total_account_received - $total_account_expenses - $account_withdrawals;
    $current_cash_balance = $opening_cash_balance + $total_cash_received - $total_cash_expenses - $cash_withdrawals;
    $total_balance = $current_account_balance + $current_cash_balance;

} catch (Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header("Location: dashboard.php");
    exit;
}
?>

<?php include 'header.php'; ?>
<style>
.account-summary-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
    padding: 20px;
    background: #f4f6f9;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    margin-top: 40px;
}
.summary-card {
    background: white;
    border-radius: 12px;
    padding: 16px;
    text-align: center;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 2px 10px rgba(0,0,0,0.04);
}
.summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}
.summary-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #777;
    text-transform: uppercase;
    margin-bottom: 6px;
}
.summary-amount {
    font-size: 1.4rem;
    font-weight: bold;
    color: #333;
}
</style>

<div class="container">
    <h2 class="text-center mt-4">Account & Cash Summary</h2>
    <div class="account-summary-container">
        <div class="summary-card">
            <div class="summary-label">Opening Account Balance</div>
            <div class="summary-amount">₹<?= number_format($opening_account_balance, 2) ?></div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Received via Account</div>
            <div class="summary-amount">₹<?= number_format($total_account_received, 2) ?></div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Expenses via Account</div>
            <div class="summary-amount">₹<?= number_format($total_account_expenses, 2) ?></div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Shareholder Withdrawals (Account)</div>
            <div class="summary-amount">₹<?= number_format($account_withdrawals, 2) ?></div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Current Account Balance</div>
            <div class="summary-amount">₹<?= number_format($current_account_balance, 2) ?></div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Opening Cash Balance</div>
            <div class="summary-amount">₹<?= number_format($opening_cash_balance, 2) ?></div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Received in Cash</div>
            <div class="summary-amount">₹<?= number_format($total_cash_received, 2) ?></div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Cash Expenses</div>
            <div class="summary-amount">₹<?= number_format($total_cash_expenses, 2) ?></div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Shareholder Withdrawals (Cash)</div>
            <div class="summary-amount">₹<?= number_format($cash_withdrawals, 2) ?></div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Cash in Hand</div>
            <div class="summary-amount">₹<?= number_format($current_cash_balance, 2) ?></div>
        </div>
        <div class="summary-card" style="background: #d1ffd1;">
            <div class="summary-label">Total Balance (Cash + Account)</div>
            <div class="summary-amount">₹<?= number_format($total_balance, 2) ?></div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>