<?php
session_start();
require_once 'config.php';

try {
    // Get current month's dates
    $first_day_this_month = date('Y-m-01');
    $last_day_this_month = date('Y-m-t');
    
    // Current Month Total Bookings Amount
    $bookings_sql = "SELECT COALESCE(SUM(total_amount), 0) as total_bookings,
                            COUNT(*) as booking_count
                     FROM events 
                     WHERE event_date BETWEEN ? AND ?
                     AND deleted_at IS NULL
                     ";
    
    $stmt = $conn->prepare($bookings_sql);
    $stmt->bind_param("ss", $first_day_this_month, $last_day_this_month);
    $stmt->execute();
    $stmt->bind_result($current_month_bookings, $booking_count);
    $stmt->fetch();
    $stmt->close();
    // echo $first_day_this_month.'<br>';
    // echo $last_day_this_month;
    // Current Month Received Payments
    $payments_sql = "SELECT COALESCE(SUM(p.amount), 0) as total_received 
                 FROM mhr_event_payments p
                 JOIN events e ON p.event_id = e.id
                 WHERE e.event_date BETWEEN ? AND ?
                 AND e.deleted_at IS NULL";

$stmt = $conn->prepare($payments_sql);
$stmt->bind_param("ss", $first_day_this_month, $last_day_this_month);
    $stmt->execute();
    $stmt->bind_result($current_month_received);
    $stmt->fetch();
    $stmt->close();
    
    // Current Month Expenses
    $expenses_sql = "SELECT COALESCE(SUM(amount), 0) as total_expenses 
                    FROM mhr_expenses 
                    WHERE expense_date BETWEEN ? AND ?
                    AND deleted_at IS NULL";
    
    $stmt = $conn->prepare($expenses_sql);
    $stmt->bind_param("ss", $first_day_this_month, $last_day_this_month);
    $stmt->execute();
    $stmt->bind_result($current_month_expenses);
    $stmt->fetch();
    $stmt->close();
    
    // Monthly Data for Last 6 Months
    $monthly_data = array();
$totals = array(
    'total_bookings' => 0,
    'received_amount' => 0,
    'expenses' => 0,
    'booking_count' => 0,
    'profit' => 0
);

for ($i = -1; $i < 6; $i++) {
    $month_start = date('Y-m-01', strtotime("$i months"));
    $month_end = date('Y-m-t', strtotime("$i months"));
    
    // Get Total Bookings for Month
    $stmt = $conn->prepare($bookings_sql);
    $stmt->bind_param("ss", $month_start, $month_end);
    $stmt->execute();
    $stmt->bind_result($month_bookings, $month_booking_count);
    $stmt->fetch();
    $stmt->close();
    
    // Get Received Payments for Month
    $stmt = $conn->prepare($payments_sql);
    $stmt->bind_param("ss", $month_start, $month_end);
    $stmt->execute();
    $stmt->bind_result($month_received);
    $stmt->fetch();
    $stmt->close();
    
    // Get Expenses for Month
    $stmt = $conn->prepare($expenses_sql);
    $stmt->bind_param("ss", $month_start, $month_end);
    $stmt->execute();
    $stmt->bind_result($month_expenses);
    $stmt->fetch();
    $stmt->close();
    
    // Calculate profit
    $profit = $month_received - $month_expenses;
    
    // Add to monthly data
    $monthly_data[] = array(
        'month' => date('M Y', strtotime($month_start)),
        'total_bookings' => $month_bookings,
        'received_amount' => $month_received,
        'expenses' => $month_expenses,
        'booking_count' => $month_booking_count,
        'profit' => $profit
    );
    
    // Add to totals
    $totals['total_bookings'] += $month_bookings;
    $totals['received_amount'] += $month_received;
    $totals['expenses'] += $month_expenses;
    $totals['booking_count'] += $month_booking_count;
    $totals['profit'] += $profit;
}

// Add totals row to the monthly_data array
$monthly_data[] = array(
    'month' => 'Total',
    'total_bookings' => $totals['total_bookings'],
    'received_amount' => $totals['received_amount'],
    'expenses' => $totals['expenses'],
    'booking_count' => $totals['booking_count'],
    'profit' => $totals['profit']
);
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header("Location: dashboard.php");
    exit;
}
?>

<?php include 'header.php'; ?>
<style>
.report-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    padding: 20px;
    margin-bottom: 20px;
}

.financial-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.summary-item {
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    transition: transform 0.2s;
}

.summary-item:hover {
    transform: translateY(-2px);
}

.summary-item.bookings { 
    background: linear-gradient(145deg, #e3f2fd, #bbdefb);
}
.summary-item.received { 
    background: linear-gradient(145deg, #e8f5e9, #c8e6c9);
}
.summary-item.expenses { 
    background: linear-gradient(145deg, #ffebee, #ffcdd2);
}
.summary-item.profit { 
    background: linear-gradient(145deg, #f3e5f5, #e1bee7);
}

.label {
    font-size: 0.9rem;
    font-weight: 500;
    color: #555;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.amount {
    font-size: 1.8rem;
    font-weight: bold;
    margin: 12px 0;
    color: #2c3e50;
}

.sub-text {
    font-size: 0.85rem;
    color: #666;
    background: rgba(255, 255, 255, 0.5);
    padding: 4px 8px;
    border-radius: 12px;
    display: inline-block;
}

.chart-container {
    height: 400px;
    margin: 30px 0;
    padding: 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.table-responsive {
    background: white;
    border-radius: 12px;
    padding: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.table {
    margin: 0;
}

.table thead th {
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    color: #495057;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.table td {
    vertical-align: middle;
    padding: 12px 8px;
    color: #2c3e50;
}

@media (max-width: 768px) {
    .container {
        padding: 10px;
    }
    
    .report-card {
        padding: 15px;
        margin-bottom: 15px;
    }

    .financial-summary {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .amount {
        font-size: 1.5rem;
    }

    /* Mobile table styling */
    .table-responsive {
        margin: 0 -15px;
        border-radius: 0;
        padding: 0;
    }

    .table th {
        white-space: nowrap;
    }

    /* Optional: Card view for mobile table */
    @media (max-width: 576px) {
        .table thead {
            display: none;
        }

        .table, .table tbody, .table tr, .table td {
            display: block;
            width: 100%;
        }

        .table tr {
            margin-bottom: 15px;
            padding: 10px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .table td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: none;
            padding: 8px;
        }

        .table td:before {
            content: attr(data-label);
            font-weight: 600;
            color: #495057;
        }
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .report-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .financial-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .summary-item {
        padding: 15px;
        border-radius: 8px;
        text-align: center;
    }
    
    .summary-item.bookings { background: #e3f2fd; }
    .summary-item.received { background: #e8f5e9; }
    .summary-item.expenses { background: #ffebee; }
    .summary-item.profit { background: #f3e5f5; }
    
    .amount {
        font-size: 1.5rem;
        font-weight: bold;
        margin: 10px 0;
    }
    
    .sub-text {
        font-size: 0.9rem;
        color: #666;
    }
    
    .chart-container {
        height: 400px;
        margin-top: 30px;
    }
    
    @media (max-width: 768px) {
        .financial-summary {
            grid-template-columns: 1fr;
        }
    }
</style>
<div class="container mt-4">
    <div class="report-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Financial Reports</h2>
        </div>
        
        <div class="financial-summary mt-4">
            <div class="summary-item bookings">
                <div class="label">Total Bookings Value</div>
                <div class="amount">₹<?= number_format($current_month_bookings, 2) ?></div>
                <div class="sub-text"><?= $booking_count ?> bookings this month</div>
            </div>
            <div class="summary-item received">
                <div class="label">Received Payments</div>
                <div class="amount">₹<?= number_format($current_month_received, 2) ?></div>
                <div class="sub-text">Advance/Installments</div>
            </div>
            <div class="summary-item expenses">
                <div class="label">Total Expenses</div>
                <div class="amount">₹<?= number_format($current_month_expenses, 2) ?></div>
            </div>
            <div class="summary-item profit">
                <div class="label">Net Profit</div>
                <div class="amount">₹<?= number_format($current_month_received - $current_month_expenses, 2) ?></div>
                <div class="sub-text">Based on received payments</div>
            </div>
        </div>
        
        <div class="chart-container">
            <canvas id="financialChart"></canvas>
        </div>

        <div class="table-responsive mt-4">
            <table class="table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Bookings</th>
                        <th>Total Value</th>
                        <th>Received</th>
                        <th>Expenses</th>
                        <th>Net Profit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monthly_data as $data): ?>
                        <tr <?= $data['month'] === 'Total' ? 'class="table-active font-weight-bold"' : '' ?>>
                            <td data-label="Month"><?= $data['month'] ?></td>
                            <td data-label="Bookings"><?= $data['booking_count'] ?></td>
                            <td data-label="Total Value">₹<?= number_format($data['total_bookings'], 2) ?></td>
                            <td data-label="Received">₹<?= number_format($data['received_amount'], 2) ?></td>
                            <td data-label="Expenses">₹<?= number_format($data['expenses'], 2) ?></td>
                            <td data-label="Net Profit" class="<?= $data['profit'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                ₹<?= number_format($data['profit'], 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('financialChart').getContext('2d');
    
    const monthlyData = <?= json_encode($monthly_data) ?>;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: monthlyData.map(data => data.month),
            datasets: [
                {
                    label: 'Total Bookings Value',
                    data: monthlyData.map(data => data.total_bookings),
                    backgroundColor: '#2196f3',
                    borderColor: '#2196f3',
                    borderWidth: 1
                },
                {
                    label: 'Received Payments',
                    data: monthlyData.map(data => data.received_amount),
                    backgroundColor: '#4caf50',
                    borderColor: '#4caf50',
                    borderWidth: 1
                },
                {
                    label: 'Expenses',
                    data: monthlyData.map(data => data.expenses),
                    backgroundColor: '#f44336',
                    borderColor: '#f44336',
                    borderWidth: 1
                },
                {
                    label: 'Net Profit',
                    data: monthlyData.map(data => data.profit),
                    backgroundColor: '#9c27b0',
                    borderColor: '#9c27b0',
                    borderWidth: 2,
                    type: 'line'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₹' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ₹' + 
                                   context.raw.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php include 'footer.php'; ?>