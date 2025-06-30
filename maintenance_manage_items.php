<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Add new item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_name']) && empty($_POST['edit_id'])) {
    $item_name = trim($_POST['item_name']);
    if ($item_name !== '') {
        $stmt = $conn->prepare("INSERT INTO mhr_maintenance_checklist (item_name) VALUES (?)");
        $stmt->bind_param("s", $item_name);
        $stmt->execute();
        $_SESSION['message'] = "Checklist item added.";
        $_SESSION['message_type'] = "success";
        header("Location: maintenance_manage_items.php");
        exit;
    }
}

// Update item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id = (int)$_POST['edit_id'];
    $new_name = trim($_POST['item_name']);
    if ($new_name !== '') {
        $stmt = $conn->prepare("UPDATE mhr_maintenance_checklist SET item_name = ? WHERE id = ?");
        $stmt->bind_param("si", $new_name, $edit_id);
        $stmt->execute();
        $_SESSION['message'] = "Checklist item updated.";
        $_SESSION['message_type'] = "info";
        header("Location: maintenance_manage_items.php");
        exit;
    }
}

// Delete item
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM mhr_maintenance_checklist WHERE id = $id");
    $_SESSION['message'] = "Checklist item deleted.";
    $_SESSION['message_type'] = "danger";
    header("Location: maintenance_manage_items.php");
    exit;
}

// Toggle active status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $conn->query("UPDATE mhr_maintenance_checklist SET is_active = NOT is_active WHERE id = $id");
    $_SESSION['message'] = "Item status updated.";
    $_SESSION['message_type'] = "info";
    header("Location: maintenance_manage_items.php");
    exit;
}

// Fetch all items
$result = $conn->query("SELECT * FROM mhr_maintenance_checklist ORDER BY id DESC");
$items = $result->fetch_all(MYSQLI_ASSOC);
$edit_item = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $edit_result = $conn->query("SELECT * FROM mhr_maintenance_checklist WHERE id = $id LIMIT 1");
    if ($edit_result && $edit_result->num_rows === 1) {
        $edit_item = $edit_result->fetch_assoc();
    }
}

include 'header.php';
?>
<div class="container mt-5">
    <h2 class="mb-4">Manage Maintenance Checklist Items</h2>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?>">
            <?= $_SESSION['message']; unset($_SESSION['message'], $_SESSION['message_type']); ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="form-inline mb-4">
        <input type="hidden" name="edit_id" value="<?= $edit_item['id'] ?? '' ?>">
        <div class="form-group mr-2">
            <input type="text" name="item_name" class="form-control" placeholder="Enter item name" required value="<?= htmlspecialchars($edit_item['item_name'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-<?= $edit_item ? 'info' : 'primary' ?>">
            <?= $edit_item ? 'Update Item' : 'Add Item' ?>
        </button>
        <?php if ($edit_item): ?>
            <a href="maintenance_manage_items.php" class="btn btn-secondary ml-2">Cancel</a>
        <?php endif; ?>
    </form>

    <table class="table table-bordered">
        <thead class="thead-light">
            <tr>
                <th>#</th>
                <th>Item Name</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $index => $item): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                    <td>
                        <span class="badge badge-<?= $item['is_active'] ? 'success' : 'secondary' ?>">
                            <?= $item['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td>
                        <a href="?toggle=<?= $item['id'] ?>" class="btn btn-sm btn-warning">Toggle</a>
                        <a href="?edit=<?= $item['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                        <a href="#" 
                            class="btn btn-sm btn-danger" 
                            data-toggle="modal" 
                            data-target="#deleteModal"
                            data-id="<?= $item['id'] ?>"
                            data-name="<?= htmlspecialchars($item['item_name']) ?>">
                            Delete
                            </a>

                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete <strong id="deleteItemName"></strong>?
      </div>
      <div class="modal-footer">
        <a href="#" class="btn btn-danger" id="confirmDeleteBtn">Yes, Delete</a>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    $('#deleteModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var name = button.data('name');

        var modal = $(this);
        modal.find('#deleteItemName').text(name);
        modal.find('#confirmDeleteBtn').attr('href', '?delete=' + id);
    });
});
</script>
<?php include 'footer.php'; ?>
