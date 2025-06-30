<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Location: login.php');
}
include 'header.php'; 

// Handle messages
$message = '';
$message_type = '';
if (isset($_SESSION['expense_message'])) {
    $message = $_SESSION['expense_message'];
    $message_type = $_SESSION['expense_message_type'];
    unset($_SESSION['expense_message']);
    unset($_SESSION['expense_message_type']);
}

// Get filters
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$selected_category = isset($_GET['category']) ? $_GET['category'] : '';

// Fetch all categories for filter dropdown
$categories_query = "SELECT DISTINCT category FROM mhr_expenses WHERE category IS NOT NULL ORDER BY category";
$categories_result = $conn->query($categories_query);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    if (!empty($row['category'])) {
        $categories[] = $row['category'];
    }
}

// Build the SQL query with category filter
$sql = "SELECT * FROM mhr_expenses 
        WHERE deleted_at IS NULL 
        AND MONTH(expense_date) = $month 
        AND YEAR(expense_date) = $year";
if (!empty($selected_category)) {
    $sql .= " AND category = '" . $conn->real_escape_string($selected_category) . "'";
}
$sql .= " ORDER BY expense_date DESC";
$result = $conn->query($sql);

// Calculate totals (overall and by category)
$total_sql = "SELECT 
                SUM(amount) as total,
                category,
                COUNT(*) as count
              FROM mhr_expenses 
              WHERE deleted_at IS NULL 
              AND MONTH(expense_date) = $month 
              AND YEAR(expense_date) = $year
              GROUP BY category";
$total_result = $conn->query($total_sql);
$total_amount = 0;
$category_totals = [];
while ($row = $total_result->fetch_assoc()) {
    $total_amount += $row['total'];
    if (!empty($row['category'])) {
        $category_totals[$row['category']] = [
            'amount' => $row['total'],
            'count' => $row['count']
        ];
    }
}

function getMonthOptions($currentMonth, $currentYear) {
    $options = '';
    for ($i = 0; $i < 12; $i++) {
        $date = strtotime("-$i month");
        $monthValue = date('m', $date);
        $yearValue = date('Y', $date);
        $selected = ($currentMonth == $monthValue && $currentYear == $yearValue) ? 'selected' : '';
        $options .= "<option value='$yearValue-$monthValue' $selected>" . date('F Y', $date) . "</option>";
    }
    return $options;
}
?>
<style>
    .expenses-container {
        background: #ffffff;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.05);
    }

    .stats-card {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        transition: transform 0.2s;
    }

    .category-summary {
        display: none;
        margin-top: 15px;
        background: white;
        border-radius: 10px;
        padding: 15px;
    }

    .category-summary.show {
        display: block;
    }

    .category-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 10px;
        margin-top: 10px;
    }

    .category-item {
        background: white;
        padding: 10px;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }

    .stats-amount {
        font-size: 2rem;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .stats-label {
        color: #6c757d;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .view-categories-btn {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 8px 15px;
        color: #495057;
        transition: all 0.2s;
    }

    .view-categories-btn:hover {
        background: #e9ecef;
        color: #212529;
    }

    .filter-section {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }

    .search-input {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 8px 15px;
        width: 100%;
        transition: border-color 0.2s;
    }

    .search-input:focus {
        border-color: #6c757d;
        outline: none;
    }

    .table-top-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .expense-table {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }

    .expense-table thead {
        background: #f8f9fa;
    }

    .expense-table th {
        color: #495057;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        padding: 15px;
    }

    .expense-table td {
        padding: 15px;
        vertical-align: middle;
    }

    .category-badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.85rem;
        background-color: #e9ecef;
        color: #495057;
        display: inline-block;
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

    @media (max-width: 768px) {
        .expense-table {
            display: none;
        }

        .expense-card {
            display: block;
            margin-bottom: 15px;
        }

        .filter-section {
            padding: 15px;
        }

        .stats-card {
            padding: 15px;
        }
    }
</style>

<div class="container mt-4">
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    
    <div class="expenses-container">
        <!-- Stats Card with Collapsible Categories -->
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stats-amount">₹<?= number_format($total_amount, 2); ?></div>
                    <div class="stats-label">Total Expenses This Month</div>
                </div>
                <button class="view-categories-btn" id="viewCategoriesBtn">
                    <i class="fas fa-chart-pie mr-2"></i>View by Category
                </button>
            </div>
            
            <!-- Collapsible Category Summary -->
            <div class="category-summary" id="categorySummary">
                <h6 class="mb-3">Expenses by Category</h6>
                <div class="category-grid">
                    <?php foreach ($category_totals as $cat => $data): ?>
                        <div class="category-item">
                            <div class="font-weight-bold"><?= htmlspecialchars($cat) ?></div>
                            <div class="text-muted">₹<?= number_format($data['amount'], 2) ?></div>
                            <small class="text-muted"><?= $data['count'] ?> items</small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row">
                <div class="col-md-4">
                    <label class="font-weight-bold mb-2">Select Month:</label>
                    <select name="year-month" id="monthSelect" class="form-control custom-select">
                        <?= getMonthOptions($month, $year); ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="font-weight-bold mb-2">Search:</label>
                    <input type="text" id="searchInput" class="search-input" placeholder="Search expenses...">
                </div>
                <div class="col-md-4">
                    <label class="font-weight-bold mb-2">Actions:</label>
                    <a href="expenses_add.php" class="btn btn-primary w-100">
                        <i class="fas fa-plus mr-2"></i>Add Expense
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Table with Built-in Filtering -->
        <div class="expense-table table-responsive">
            <table class="table table-hover mb-0" id="expenseTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Expense Name</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $i++; ?></td>
                                <td><?= htmlspecialchars($row['expense_name']); ?></td>
                                <td>
                                    <span class="category-badge">
                                        <?= htmlspecialchars($row['category'] ?? 'Uncategorized'); ?>
                                    </span>
                                </td>
                                <td>₹<?= number_format($row['amount'], 2); ?></td>
                                <td><?= date('d/m/Y', strtotime($row['expense_date'])); ?></td>
                                <td>
                                    <a href="expenses_edit.php?id=<?= $row['id']; ?>" 
                                       class="btn btn-warning btn-sm action-btn">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="expenses_delete.php?id=<?= $row['id']; ?>" 
                                       onclick="return confirm('Are you sure?')" 
                                       class="btn btn-danger btn-sm action-btn">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">No expenses found for this month.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards View remains the same -->
        <?php 
        if ($result->num_rows > 0) {
            $result->data_seek(0);
            while ($row = $result->fetch_assoc()): 
        ?>
            <div class="expense-card">
                <div class="expense-card-header">
                    <div>
                        <h6 class="mb-1"><?= htmlspecialchars($row['expense_name']); ?></h6>
                        <div class="category-badge mb-2">
                            <?= htmlspecialchars($row['category'] ?? 'Uncategorized'); ?>
                        </div>
                        <div class="expense-card-date">
                            <?= date('d/m/Y', strtotime($row['expense_date'])); ?>
                        </div>
                    </div>
                    <div class="expense-card-amount">
                        ₹<?= number_format($row['amount'], 2); ?>
                    </div>
                </div>
                <div class="expense-card-actions">
                    <a href="expenses_edit.php?id=<?= $row['id']; ?>" 
                       class="btn btn-warning btn-sm action-btn flex-grow-1">
                        <i class="fas fa-edit mr-2"></i>Edit
                    </a>
                    <a href="expenses_delete.php?id=<?= $row['id']; ?>" 
                       onclick="return confirm('Are you sure you want to delete this expense?')" 
                       class="btn btn-danger btn-sm action-btn flex-grow-1">
                        <i class="fas fa-trash mr-2"></i>Delete
                    </a>
                </div>
            </div>
        <?php 
            endwhile;
        } else {
            echo '<div class="expense-card text-center py-4">No expenses found for this month.</div>';
        }
        ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle category summary
    const viewCategoriesBtn = document.getElementById('viewCategoriesBtn');
    const categorySummary = document.getElementById('categorySummary');
    
    viewCategoriesBtn.addEventListener('click', function() {
        categorySummary.classList.toggle('show');
        viewCategoriesBtn.innerHTML = categorySummary.classList.contains('show') 
            ? '<i class="fas fa-times mr-2"></i>Hide Categories'
            : '<i class="fas fa-chart-pie mr-2"></i>View by Category';
    });

    // Table search functionality
    const searchInput = document.getElementById('searchInput');
    const table = document.getElementById('expenseTable');
    const mobileCards = document.querySelectorAll('.searchable-card');

    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();

        // Filter table rows
        const rows = table.getElementsByTagName('tr');
        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        }

        // Filter mobile cards
        mobileCards.forEach(card => {
            const cardText = card.textContent.toLowerCase();
            card.style.display = cardText.includes(searchTerm) ? 'block' : 'none';
        });
    });

    // Month select handler
    document.getElementById('monthSelect').addEventListener('change', function() {
        const [year, month] = this.value.split('-');
        const searchParams = new URLSearchParams(window.location.search);
        searchParams.set('year', year);
        searchParams.set('month', month);
        window.location.search = searchParams.toString();
    });
});
</script>

<?php include 'footer.php'; ?>