<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Fetch active checklist items
$items = [];
$result = $conn->query("SELECT id, item_name FROM mhr_maintenance_checklist WHERE is_active = 1 ORDER BY item_name");
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $log_date = $_POST['log_date'] ?: date('Y-m-d');
    $checked_by = $_SESSION['username'];

    foreach ($_POST['status'] as $item_id => $status) {
        $remarks = $_POST['remarks'][$item_id] ?? '';

        $stmt = $conn->prepare("INSERT INTO mhr_maintenance_logs (checklist_item_id, log_date, status, remarks, checked_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $item_id, $log_date, $status, $remarks, $checked_by);
        $stmt->execute();
    }

    $_SESSION['message'] = "Maintenance checklist submitted successfully!";
    $_SESSION['message_type'] = "success";
    header("Location: maintenance_daily_checklist.php");
    exit;
}

include 'header.php';
?>
<div class="container mt-5">
    <h2 class="mb-4">Daily Villa Maintenance Checklist</h2>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?>">
            <?= $_SESSION['message']; unset($_SESSION['message'], $_SESSION['message_type']); ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="log_date">Checklist Date</label>
            <input type="date" class="form-control" name="log_date" id="log_date" value="<?= date('Y-m-d') ?>" required>
        </div>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                        <td>
                            <select name="status[<?= $item['id'] ?>]" class="form-control" required>
                                <option value="ok">OK</option>
                                <option value="not_ok">Not OK</option>
                                <option value="na">N/A</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="remarks[<?= $item['id'] ?>]" class="form-control" placeholder="Optional">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button type="submit" class="btn btn-success">Submit Checklist</button>
    </form>
</div>
<?php include 'footer.php'; ?>