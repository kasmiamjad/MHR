<?php
// Start output buffering
ob_start();
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    ob_end_clean();
    header('Location: login.php');
    exit;
}

require_once 'config.php';

// Fetch categories for the dropdown
$categories_query = "SELECT * FROM mhr_expense_categories ORDER BY category_name";
$categories_result = $conn->query($categories_query);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $expense_name = $_POST['expense_name'];
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $expense_date = $_POST['expense_date'] ?: date('Y-m-d');
    $payment_mode = $_POST['payment_mode'];
    $description = $_SESSION['username'];

    $sql = "INSERT INTO mhr_expenses (expense_name, category, amount, expense_date, description, payment_mode)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdsss", 
        $expense_name,
        $category,
        $amount,
        $expense_date,
        $description,
        $payment_mode
    );

    if ($stmt->execute()) {
        ob_end_clean();
        $_SESSION['expense_message'] = $expense_name . " Expense of " . $amount . " has been added successfully!";
        $_SESSION['expense_message_type'] = 'success';
        header("Location: expenses_list.php");
        exit;
    } else {
        $_SESSION['expense_message'] = "Error adding expense: " . $stmt->error;
        $_SESSION['expense_message_type'] = 'danger';
        ob_end_clean();
        header("Location: expenses_list.php");
        exit;
    }
}

include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expense</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <h2 class="mb-4">Add Expense</h2>
    <form method="POST" action="">
        <div class="form-group">
            <label for="expense_name">Expense Name</label>
            <input type="text" class="form-control" id="expense_name" name="expense_name" required>
        </div>

        <div class="form-group">
            <label for="category">Category</label>
            <select class="form-control" id="category" name="category" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= htmlspecialchars($category['category_name']) ?>">
                        <?= htmlspecialchars($category['category_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="amount">Amount</label>
            <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
        </div>

        <div class="form-group">
            <label for="expense_date">Expense Date</label>
            <input type="date" class="form-control" id="expense_date" name="expense_date" value="<?= date('Y-m-d'); ?>">
        </div>

        <div class="form-group">
            <label for="payment_mode">Payment Mode</label>
            <select class="form-control" id="payment_mode" name="payment_mode" required>
                <option value="">Select Mode</option>
                <option value="cash">Cash</option>
                <option value="online">Online</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Save</button>
        <a href="expenses_list.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php
include 'footer.php';
ob_end_flush();
?>
</body>
</html>
