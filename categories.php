<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Location: login.php');
    exit;
}

// Handle category operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $category_name = trim($_POST['category_name']);
                if (!empty($category_name)) {
                    $stmt = $conn->prepare("INSERT INTO mhr_expense_categories (category_name) VALUES (?)");
                    $stmt->bind_param('s', $category_name);
                    if ($stmt->execute()) {
                        $_SESSION['category_message'] = "Category added successfully!";
                        $_SESSION['category_message_type'] = 'success';
                    } else {
                        $_SESSION['category_message'] = "Error adding category.";
                        $_SESSION['category_message_type'] = 'danger';
                    }
                }
                break;

            case 'edit':
                $category_id = (int)$_POST['category_id'];
                $category_name = trim($_POST['category_name']);
                if (!empty($category_name)) {
                    $stmt = $conn->prepare("UPDATE mhr_expense_categories SET category_name = ? WHERE id = ?");
                    $stmt->bind_param('si', $category_name, $category_id);
                    if ($stmt->execute()) {
                        $_SESSION['category_message'] = "Category updated successfully!";
                        $_SESSION['category_message_type'] = 'success';
                    } else {
                        $_SESSION['category_message'] = "Error updating category.";
                        $_SESSION['category_message_type'] = 'danger';
                    }
                }
                break;

            case 'delete':
                $category_id = (int)$_POST['category_id'];
                // First check if category is in use
                $check_stmt = $conn->prepare("SELECT COUNT(*) FROM mhr_expenses WHERE category = (SELECT category_name FROM mhr_expense_categories WHERE id = ?)");
                $check_stmt->bind_param('i', $category_id);
                $check_stmt->execute();
                $check_stmt->bind_result($count);
                $check_stmt->fetch();
                $check_stmt->close();

                if ($count > 0) {
                    $_SESSION['category_message'] = "Cannot delete category that is in use.";
                    $_SESSION['category_message_type'] = 'danger';
                } else {
                    $stmt = $conn->prepare("DELETE FROM mhr_expense_categories WHERE id = ?");
                    $stmt->bind_param('i', $category_id);
                    if ($stmt->execute()) {
                        $_SESSION['category_message'] = "Category deleted successfully!";
                        $_SESSION['category_message_type'] = 'success';
                    } else {
                        $_SESSION['category_message'] = "Error deleting category.";
                        $_SESSION['category_message_type'] = 'danger';
                    }
                }
                break;
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Fetch all categories
$categories_query = "SELECT c.*, 
                           COUNT(e.id) as expense_count,
                           SUM(e.amount) as total_amount
                    FROM mhr_expense_categories c
                    LEFT JOIN mhr_expenses e ON c.category_name = e.category
                    WHERE e.deleted_at IS NULL 
                    GROUP BY c.id
                    ORDER BY c.category_name";
$categories_result = $conn->query($categories_query);

include 'header.php';
?>

<style>
    .categories-container {
        background: #ffffff;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        margin: 20px auto;
    }

    .category-card {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
        transition: transform 0.2s;
    }

    .category-card:hover {
        transform: translateY(-2px);
    }

    .category-stats {
        font-size: 0.9rem;
        color: #6c757d;
    }

    .add-category-form {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .action-btn {
        padding: 6px 12px;
        border-radius: 6px;
        transition: all 0.2s;
        margin: 0 3px;
    }

    .action-btn:hover {
        transform: translateY(-1px);
    }

    .modal-content {
        border-radius: 15px;
        border: none;
    }

    .modal-header {
        background: #f8f9fa;
        border-radius: 15px 15px 0 0;
    }

    .category-usage {
        font-size: 0.85rem;
        color: #6c757d;
        margin-top: 5px;
    }
</style>

<div class="container">
    <?php if (isset($_SESSION['category_message'])): ?>
        <div class="alert alert-<?= $_SESSION['category_message_type'] ?> alert-dismissible fade show mt-4" role="alert">
            <?= htmlspecialchars($_SESSION['category_message']) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php 
        unset($_SESSION['category_message']);
        unset($_SESSION['category_message_type']);
        ?>
    <?php endif; ?>

    <div class="categories-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Categories</h2>
            <button class="btn btn-primary" data-toggle="modal" data-target="#addCategoryModal">
                <i class="fas fa-plus mr-2"></i>Add Category
            </button>
        </div>

        <!-- Categories List -->
        <div class="row">
            <?php while ($category = $categories_result->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="category-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-2"><?= htmlspecialchars($category['category_name']) ?></h5>
                                <div class="category-stats">
                                    <div>
                                        <i class="fas fa-receipt mr-2"></i>
                                        <?= $category['expense_count'] ?> expenses
                                    </div>
                                    <?php if ($category['total_amount']): ?>
                                        <div>
                                            <i class="fas fa-rupee-sign mr-2"></i>
                                            ₹<?= number_format($category['total_amount'], 2) ?> total
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <button class="btn btn-warning btn-sm action-btn edit-category" 
                                        data-id="<?= $category['id'] ?>"
                                        data-name="<?= htmlspecialchars($category['category_name']) ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($category['expense_count'] == 0): ?>
                                    <button class="btn btn-danger btn-sm action-btn delete-category"
                                            data-id="<?= $category['id'] ?>"
                                            data-name="<?= htmlspecialchars($category['category_name']) ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label>Category Name</label>
                        <input type="text" class="form-control" name="category_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <div class="form-group">
                        <label>Category Name</label>
                        <input type="text" class="form-control" name="category_name" id="edit_category_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Category Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Category</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="category_id" id="delete_category_id">
                    <p>Are you sure you want to delete the category "<span id="delete_category_name"></span>"?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit Category
    document.querySelectorAll('.edit-category').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            document.getElementById('edit_category_id').value = id;
            document.getElementById('edit_category_name').value = name;
            $('#editCategoryModal').modal('show');
        });
    });

    // Delete Category
    document.querySelectorAll('.delete-category').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            document.getElementById('delete_category_id').value = id;
            document.getElementById('delete_category_name').textContent = name;
            $('#deleteCategoryModal').modal('show');
        });
    });
});
</script>

<?php include 'footer.php'; ?>