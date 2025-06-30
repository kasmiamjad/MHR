<?php
session_start();
require_once 'config.php';

$event_id = $_GET['id'] ?? 0;

try {
    // First, get event details
    $event_sql = "SELECT e.id, e.guest_name, e.phone, e.event_date, e.checkout, 
                         e.package, e.total_amount, e.advance_amount,
                         COALESCE((SELECT SUM(amount) FROM mhr_event_payments WHERE event_id = e.id), 0) as total_paid
                  FROM events e 
                  WHERE e.id = ?";
    
    $stmt = $conn->prepare($event_sql);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    
    $stmt->bind_result(
        $id, 
        $guest_name, 
        $phone, 
        $event_date, 
        $checkout, 
        $package, 
        $total_amount, 
        $advance_amount, 
        $total_paid
    );
    
    $stmt->fetch();
    $stmt->close();

    $remaining_amount = $total_amount - $total_paid;

    // Now get payment history
    $payments = array();
    $payments_sql = "SELECT id, event_id, amount, payment_type, payment_date, 
                           transaction_id, payment_status, payment_notes 
                    FROM mhr_event_payments 
                    WHERE event_id = ? 
                    ORDER BY payment_date DESC";
    
    $stmt = $conn->prepare($payments_sql);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    
    $stmt->bind_result(
        $payment_id, 
        $payment_event_id, 
        $payment_amount, 
        $payment_type, 
        $payment_date, 
        $transaction_id, 
        $payment_status, 
        $payment_notes
    );
    
    while ($stmt->fetch()) {
        $payments[] = array(
            'id' => $payment_id,
            'amount' => $payment_amount,
            'payment_type' => $payment_type,
            'payment_date' => $payment_date,
            'transaction_id' => $transaction_id,
            'payment_notes' => $payment_notes,
            'payment_status' => $payment_status
        );
    }
    $stmt->close();

} catch (Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header("Location: events_list.php");
    exit;
}
?>

<?php include 'header.php'; ?>

<style>
    .payment-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .payment-summary {
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
    
    .summary-item.total { background: #e3f2fd; }
    .summary-item.paid { background: #e8f5e9; }
    .summary-item.remaining { background: #fff3e0; }
    
    .amount {
        font-size: 1.5rem;
        font-weight: bold;
        margin: 10px 0;
    }
    
    .payment-type-selector {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .payment-type-btn {
        flex: 1;
        padding: 15px;
        border: 2px solid #ddd;
        border-radius: 8px;
        background: white;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .payment-type-btn.active {
        border-color: #2196F3;
        background: #e3f2fd;
    }

    @media (max-width: 768px) {
        .payment-summary {
            grid-template-columns: 1fr;
        }
        
        .payment-type-selector {
            flex-direction: column;
        }
    }
</style>

<div class="container mt-4">
    <div class="payment-card">
        <h2>Payment Details - <?= htmlspecialchars($guest_name ?? '') ?></h2>
        <p>Booking Date: <?= isset($event_date) ? date('d M Y', strtotime($event_date)) : '' ?></p>
        
        <div class="payment-summary mt-4">
            <div class="summary-item total">
                <div class="label">Total Amount</div>
                <div class="amount">₹<?= number_format($total_amount ?? 0, 2) ?></div>
            </div>
            <div class="summary-item paid">
                <div class="label">Total Paid</div>
                <div class="amount">₹<?= number_format($total_paid ?? 0, 2) ?></div>
            </div>
            <div class="summary-item remaining">
                <div class="label">Remaining Amount</div>
                <div class="amount">₹<?= number_format($remaining_amount ?? 0, 2) ?></div>
            </div>
        </div>
        
        <?php if (isset($remaining_amount) && $remaining_amount > 0): ?>
        <div class="payment-form">
            <h4>Add New Payment</h4>
            <form id="paymentForm" action="process_payment.php" method="POST">
                <input type="hidden" name="event_id" value="<?= $event_id ?>">
                
                <div class="payment-type-selector">
                    <button type="button" class="payment-type-btn active" data-type="cash">
                        <i class="fas fa-money-bill-wave"></i> Cash
                    </button>
                    <button type="button" class="payment-type-btn" data-type="online">
                        <i class="fas fa-mobile-alt"></i> Online
                    </button>
                </div>
                
                <div class="form-group">
                    <label>Amount</label>
                    <input type="number" value="<?= $remaining_amount; ?>" name="amount" class="form-control" max="<?= $remaining_amount ?>" required>
                </div>
                
                <div class="transaction-details" style="display: none;">
                    <div class="form-group">
                        <label>Transaction ID</label>
                        <input type="text" name="transaction_id" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="payment_notes" class="form-control" rows="3"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    Record Payment & Send Invoice
                </button>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Payment History Table -->
        <div class="payment-history mt-4">
            <h4>Payment History</h4>
            <?php if (!empty($payments)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Transaction ID</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($payment['payment_date'])) ?></td>
                            <td>₹<?= number_format($payment['amount'], 2) ?></td>
                            <td><?= ucfirst($payment['payment_type']) ?></td>
                            <td><?= $payment['transaction_id'] ?? '-' ?></td>
                            <td><?= $payment['payment_notes'] ?? '-' ?></td>
                            <td>
                                <a href="generate_invoice.php?payment_id=<?= $payment['id'] ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-file-invoice"></i>
                                </a>
                                <button class="btn btn-sm btn-success send-invoice" 
                                        data-payment-id="<?= $payment['id'] ?>">
                                    <i class="fab fa-whatsapp"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted">No payment history available.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentForm = document.getElementById('paymentForm');
    const paymentBtns = document.querySelectorAll('.payment-type-btn');
    const transactionDetails = document.querySelector('.transaction-details');
    
    // Add hidden input for payment type
    const paymentTypeInput = document.createElement('input');
    paymentTypeInput.type = 'hidden';
    paymentTypeInput.name = 'payment_type';
    paymentTypeInput.value = 'cash'; // Default value
    paymentForm.appendChild(paymentTypeInput);
    
    paymentBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            // Remove active class from all buttons
            paymentBtns.forEach(b => b.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            const paymentType = this.dataset.type;
            // Update hidden input value
            paymentTypeInput.value = paymentType;
            
            // Show/hide transaction details based on payment type
            if (paymentType === 'online') {
                transactionDetails.style.display = 'block';
            } else {
                transactionDetails.style.display = 'none';
            }
        });
    });
    
    // Form submission validation
    paymentForm.addEventListener('submit', function(e) {
        const amount = document.querySelector('input[name="amount"]').value;
        const paymentType = paymentTypeInput.value;
        
        if (!amount || amount <= 0) {
            e.preventDefault();
            alert('Please enter a valid amount');
            return;
        }
        
        if (paymentType === 'online' && !document.querySelector('input[name="transaction_id"]').value.trim()) {
            e.preventDefault();
            alert('Please enter the transaction ID for online payment');
            return;
        }
    });
});
</script>

<?php include 'footer.php'; ?>