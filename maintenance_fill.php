<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
if (!$event_id) {
    echo "Invalid event ID.";
    exit;
}

// Fetch event info
$event_stmt = $conn->prepare("SELECT guest_name, event_date FROM events WHERE id = ?");
$event_stmt->bind_param("i", $event_id);
$event_stmt->execute();
$event_stmt->bind_result($guest_name, $event_date);
$event_stmt->fetch();
$event_stmt->close();

// Fetch checklist items
$items_result = $conn->query("SELECT id, item_name FROM mhr_maintenance_checklist WHERE is_active = 1 ORDER BY id");
$items = $items_result->fetch_all(MYSQLI_ASSOC);

// Fetch existing log values
$existing_logs = [];
$logs_result = $conn->query("SELECT checklist_item_id, status, remarks FROM mhr_maintenance_event_logs WHERE event_id = $event_id");
while ($log = $logs_result->fetch_assoc()) {
    $existing_logs[$log['checklist_item_id']] = $log;
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statuses = $_POST['status'] ?? [];
    $remarks = $_POST['remarks'] ?? [];
    $checked_by = $_SESSION['username'] ?? 'Unknown';

    foreach ($statuses as $item_id => $status) {
        $remark = $remarks[$item_id] ?? null;

        // Check if this log already exists
        $check_stmt = $conn->prepare("SELECT id FROM mhr_maintenance_event_logs WHERE event_id = ? AND checklist_item_id = ?");
        $check_stmt->bind_param("ii", $event_id, $item_id);
        $check_stmt->execute();
        $check_stmt->store_result();



        if ($check_stmt->num_rows > 0) {
            // UPDATE
            $update_stmt = $conn->prepare("UPDATE mhr_maintenance_event_logs SET status = ?, remarks = ?, checked_by = ?, updated_at = NOW() WHERE event_id = ? AND checklist_item_id = ?");
            if (!$update_stmt) {
                die("Update prepare failed: " . $conn->error);
            }
            $update_stmt->bind_param("sssii", $status, $remark, $checked_by, $event_id, $item_id);
            $update_stmt->execute();
            $update_stmt->close();
        } else {
            // INSERT
            $insert_stmt = $conn->prepare("INSERT INTO mhr_maintenance_event_logs (event_id, checklist_item_id, status, remarks, checked_by) VALUES (?, ?, ?, ?, ?)");
            if (!$insert_stmt) {
                die("Insert prepare failed: " . $conn->error);
            }
            $insert_stmt->bind_param("iisss", $event_id, $item_id, $status, $remark, $checked_by);
            $insert_stmt->execute();
            $insert_stmt->close();
        }

        $check_stmt->close();
    }

    $_SESSION['message'] = "Maintenance checklist saved.";
    $_SESSION['message_type'] = "success";
    header("Location: events_view.php?id=$event_id");
    exit;
}

include 'header.php';
?>

<style>
@media (max-width: 768px) {
    .card-body select,
    .card-body input {
        font-size: 0.95rem;
    }
    .card-body label {
        font-size: 0.85rem;
        font-weight: 500;
    }
}
</style>

<div class="container mt-5">
    <h3 class="mb-3">Daily Maintenance Checklist</h3>
    <p><strong>Guest:</strong> <?= htmlspecialchars($guest_name) ?> | <strong>Date:</strong> <?= htmlspecialchars($event_date) ?></p>

    <form method="POST">
        <!-- Desktop View -->
        <div class="d-none d-md-block table-responsive">
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Checklist Item</th>
                        <th>Status</th>
                        <th>Remarks (optional)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['item_name']) ?></td>
                            <td>
                                <select name="status[<?= $item['id'] ?>]" class="form-control" required>
                                    <?php
                                    $currentStatus = $existing_logs[$item['id']]['status'] ?? '';
                                    ?>
                                    <option value="ok" <?= $currentStatus === 'ok' ? 'selected' : '' ?>>OK</option>
                                    <option value="not_ok" <?= $currentStatus === 'not_ok' ? 'selected' : '' ?>>Not OK</option>
                                    <option value="na" <?= $currentStatus === 'na' ? 'selected' : '' ?>>N/A</option>
                                </select>

                            </td>
                            <td>
                                <input type="text" name="remarks[<?= $item['id'] ?>]" class="form-control" placeholder="Remarks if any">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile View -->
        <div class="d-block d-md-none">
            <?php foreach ($items as $item): ?>
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <h6 class="mb-2 font-weight-bold"><?= htmlspecialchars($item['item_name']) ?></h6>
                        <div class="form-group mb-2">
                            <label for="status_<?= $item['id'] ?>" class="small text-muted">Status</label>
                            <select name="status[<?= $item['id'] ?>]" class="form-control" required>
                                    <?php
                                    $currentStatus = $existing_logs[$item['id']]['status'] ?? '';
                                    ?>
                                    <option value="ok" <?= $currentStatus === 'ok' ? 'selected' : '' ?>>OK</option>
                                    <option value="not_ok" <?= $currentStatus === 'not_ok' ? 'selected' : '' ?>>Not OK</option>
                                    <option value="na" <?= $currentStatus === 'na' ? 'selected' : '' ?>>N/A</option>
                                </select>
                        </div>
                        <div class="form-group mb-0">
                            <label for="remarks_<?= $item['id'] ?>" class="small text-muted">Remarks (optional)</label>
                            <input type="text" name="remarks[<?= $item['id'] ?>]" class="form-control"
                                value="<?= htmlspecialchars($existing_logs[$item['id']]['remarks'] ?? '') ?>"
                                placeholder="Remarks if any">

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="btn btn-primary">Save Checklist</button>
        <a href="events_view.php?id=<?= $event_id ?>" class="btn btn-secondary">Back</a>
    </form>
</div>

<?php include 'footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const isMobile = window.innerWidth < 768;

    if (isMobile) {
        // Remove desktop form from DOM
        const desktopForm = document.querySelector('.d-none.d-md-block');
        if (desktopForm) desktopForm.remove();
    } else {
        // Remove mobile form from DOM
        const mobileForm = document.querySelector('.d-block.d-md-none');
        if (mobileForm) mobileForm.remove();
    }
});
</script>

