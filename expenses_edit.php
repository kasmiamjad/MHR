<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Location: login.php');
    exit;
}

require_once 'config.php';

// Fetch all categories for the dropdown
$categories_query = "SELECT * FROM mhr_expense_categories ORDER BY category_name";
$categories_result = $conn->query($categories_query);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}

// Fetch the expense by ID using prepared statement
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expense_name = $_POST['expense_name'];
    $category = $_POST['category'];
    $amount = (float)$_POST['amount'];
    $payment_mode = $_POST['payment_mode'];
    $description = $_SESSION['username'];

    $sql = "UPDATE mhr_expenses 
            SET expense_name = ?, 
                category = ?,
                amount = ?, 
                description = ?,
                payment_mode = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND deleted_at IS NULL";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssdssi', 
        $expense_name, 
        $category,
        $amount, 
        $description,
        $payment_mode,
        $id
    );

    if ($stmt->execute()) {
        $_SESSION['expense_message'] = "Expense updated successfully.";
        $_SESSION['expense_message_type'] = 'success';
        header('Location: expenses_list.php');
        exit;
    } else {
        $error = "Failed to update the expense: " . $stmt->error;
    }
} else {
    $sql = "SELECT * FROM mhr_expenses WHERE id = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($expense = $result->fetch_assoc()) {
        // fetched successfully
    } else {
        $_SESSION['expense_message'] = "Expense not found.";
        $_SESSION['expense_message_type'] = 'danger';
        header('Location: expenses_list.php');
        exit;
    }
    $stmt->close();
}

include 'header.php';
?>

<div class="container mt-5">
    <h2 class="mb-4">Edit Expense</h2>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="expense_name">Expense Name</label>
            <input type="text" 
                   id="expense_name" 
                   name="expense_name" 
                   class="form-control" 
                   required 
                   value="<?= htmlspecialchars($expense['expense_name']); ?>">
        </div>

        <div class="form-group">
            <label for="category">Category</label>
            <select class="form-control" id="category" name="category" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['category_name']) ?>"
                            <?= ($expense['category'] == $cat['category_name']) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($cat['category_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="amount">Amount</label>
            <input type="number" 
                   step="0.01" 
                   id="amount" 
                   name="amount" 
                   class="form-control" 
                   required 
                   value="<?= htmlspecialchars($expense['amount']); ?>">
        </div>
        <div class="form-group">
            <label for="payment_mode">Payment Mode</label>
            <select class="form-control" id="payment_mode" name="payment_mode" required>
                <option value="">Select Mode</option>
                <option value="cash" <?= ($expense['payment_mode'] == 'cash') ? 'selected' : '' ?>>Cash</option>
                <option value="online" <?= ($expense['payment_mode'] == 'online') ? 'selected' : '' ?>>Online</option>
            </select>
        </div>
            
        <div class="form-group">
            <label for="expense_date">Expense Date</label>
            <input type="date" 
                   class="form-control" 
                   id="expense_date" 
                   name="expense_date" 
                   value="<?= htmlspecialchars($expense['expense_date']); ?>" 
                   readonly>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save Changes
        </button>
        <a href="expenses_list.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </form>
</div>

<?php include 'footer.php'; ?>