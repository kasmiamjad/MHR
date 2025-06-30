<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Location: login.php');
    exit;
}

include 'config.php';
$id = $_GET['id'];

// Fetch maintenance logs for this event
$maintenance_sql = "SELECT ml.*, c.item_name 
                    FROM mhr_maintenance_event_logs ml 
                    JOIN mhr_maintenance_checklist c ON ml.checklist_item_id = c.id 
                    WHERE ml.event_id = ?
                    ORDER BY ml.id ASC";

$maintenance_stmt = $conn->prepare($maintenance_sql);
$maintenance_stmt->bind_param("i", $id);
$maintenance_stmt->execute();
$maintenance_result = $maintenance_stmt->get_result();
$maintenance_logs = $maintenance_result->fetch_all(MYSQLI_ASSOC);
$maintenance_stmt->close();



$sql = "SELECT * FROM events WHERE id = $id";
$result = $conn->query($sql);
$event = $result->fetch_assoc();

$documents_sql = "SELECT * FROM mhr_event_documents WHERE event_id = $id";
$documents_result = $conn->query($documents_sql);

// Get payment details
// Get payment details
// Get payment details
$payments_sql = "SELECT DISTINCT ep.id, ep.amount, ep.payment_type, 
                        ep.payment_notes, ep.created_at, ep.created_by, u.username as paid_by 
                FROM mhr_event_payments ep 
                LEFT JOIN mhr_users u ON ep.created_by = u.username 
                WHERE ep.event_id = ? 
                GROUP BY ep.id  -- Group by the payment ID
                ORDER BY ep.created_at DESC";

$stmt = $conn->prepare($payments_sql);
$stmt->bind_param("i", $id);
$stmt->execute();

// Bind the result variables
$stmt->bind_result(
    $payment_id,
    $payment_amount,
    $payment_type,
    $payment_notes,
    $payment_created_at,
    $payment_created_by,
    $payment_paid_by
);

// Create an array to store all payments
$payments = array();
$total_paid = 0;

// Fetch all results
while ($stmt->fetch()) {
    $payments[] = array(
        'id' => $payment_id,
        'amount' => $payment_amount,
        'payment_type' => $payment_type,
        'payment_notes' => $payment_notes,
        'created_at' => $payment_created_at,
        'created_by' => $payment_created_by,
        'paid_by' => $payment_paid_by
    );
    $total_paid += $payment_amount;
}
$stmt->close();


?>
<?php include 'header.php'; ?>

<style>
    :root {
        --primary: #2a2a72;
        --success: #28a745;
        --info: #17a2b8;
        --danger: #dc3545;
        --light: #f8f9fa;
        --dark: #343a40;
        --border-radius: 12px;
        --transition: all 0.3s ease;
    }

    .event-header {
        background: linear-gradient(135deg, var(--primary), #45458b);
        padding: 2rem 0;
        color: white;
        margin-bottom: -3rem;
        position: relative;
    }

    .event-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 4rem;
        background: inherit;
        transform: skewY(-3deg);
        transform-origin: 100%;
    }

    .details-container {
        position: relative;
        z-index: 1;
        padding: 0 1rem;
        margin-bottom: 2rem;
    }

    .details-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        padding: 2rem;
    }

    .detail-item {
        padding: 1.5rem;
        background: var(--light);
        border-radius: var(--border-radius);
        transition: var(--transition);
    }

    .detail-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .detail-label {
        color: #666;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 0.5rem;
    }

    .detail-value {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--dark);
    }

    .amount-value {
        color: var(--success);
    }

    .gallery-section {
        padding: 2rem;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }

    .gallery-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        color: var(--primary);
    }

    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .gallery-item {
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: var(--transition);
        position: relative;
    }

    .gallery-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .gallery-img {
        width: 100%;
        height: 250px;
        object-fit: cover;
        transition: var(--transition);
    }

    .gallery-item:hover .gallery-img {
        transform: scale(1.05);
    }

    .gallery-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 1rem;
        background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        opacity: 0;
        transition: var(--transition);
    }

    .gallery-item:hover .gallery-overlay {
        opacity: 1;
    }

    .gallery-btn {
        background: rgba(255, 255, 255, 0.9);
        color: var(--dark);
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: var(--transition);
    }

    .gallery-btn:hover {
        background: white;
        transform: translateY(-2px);
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.8rem 1.5rem;
        background: var(--primary);
        color: white;
        border-radius: var(--border-radius);
        transition: var(--transition);
        margin: 2rem;
    }

    .back-btn:hover {
        background: #3a3a92;
        transform: translateX(-5px);
        color: white;
        text-decoration: none;
    }

    @media (max-width: 768px) {
        .details-grid {
            grid-template-columns: 1fr;
            padding: 1rem;
        }

        .gallery-grid {
            grid-template-columns: 1fr;
        }

        .event-header {
            text-align: center;
            padding: 1.5rem 0;
        }
    }
    /* Payment Section Styles */
.payment-section {
    padding: 2rem;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.payment-cards {
    display: grid;
    gap: 1rem;
    margin-top: 1rem;
}

.payment-card {
    background: var(--light);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    display: grid;
    gap: 1rem;
    transition: var(--transition);
    border-left: 4px solid var(--primary);
}

.payment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.payment-amount {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--success);
}

.payment-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.payment-badge.cash {
    background: #e8f5e9;
    color: #2e7d32;
}

.payment-badge.online {
    background: #e3f2fd;
    color: #1976d2;
}

.payment-info {
    display: grid;
    gap: 0.5rem;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.payment-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
    font-size: 0.85rem;
    color: var(--text-secondary);
}
</style>

<div class="event-header">
    <div class="container">
        <h1>Event Details</h1>
        <p class="mb-0">Booking Information for <?php echo $event['guest_name']; ?></p>
    </div>
</div>

<div class="container details-container">
    <div class="details-card">
        <div class="details-grid">
            <div class="detail-item">
                <div class="detail-label">Guest Name</div>
                <div class="detail-value"><?php echo $event['guest_name']; ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Phone</div>
                <div class="detail-value"><?php echo $event['phone']; ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Check-in Date</div>
                <div class="detail-value"><?php echo date('d M Y', strtotime($event['event_date'])); ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Check-out Date</div>
                <div class="detail-value"><?php echo date('d M Y', strtotime($event['checkout'])); ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Package</div>
                <div class="detail-value"><?php echo $event['package']; ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Guests</div>
                <div class="detail-value"><?php echo $event['adults']; ?> Adults, <?php echo $event['kids']; ?> Kids</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Total Amount</div>
                <div class="detail-value amount-value">₹<?php echo number_format($event['total_amount'], 2); ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Advance Paid</div>
                <div class="detail-value amount-value">₹<?php echo number_format($event['advance_amount'], 2); ?></div>
            </div>
        </div>
        <!-- Payment Details Section -->

        <!-- Payment Details Section -->
<div class="payment-section">
    <h2 class="gallery-title">Payment History</h2>
    <?php if (!empty($payments)): ?>
        <div class="payment-cards">
            <?php foreach ($payments as $payment): ?>
                <div class="payment-card">
                    <div class="payment-header">
                        <div class="payment-amount">₹<?php echo number_format($payment['amount'], 2); ?></div>
                        <div class="payment-badge <?php echo strtolower($payment['payment_type']); ?>">
                            <i class="fas fa-<?php echo strtolower($payment['payment_type']) === 'cash' ? 'money-bill-wave' : 'credit-card'; ?>"></i>
                            <?php echo ucfirst(strtolower($payment['payment_type'])); ?>
                        </div>
                    </div>
                    <div class="payment-info">
                        <?php if ($payment['payment_notes']): ?>
                        <div>
                            <i class="fas fa-comment"></i>
                            <?php echo $payment['payment_notes']; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="payment-meta">
                        <div>
                            <i class="fas fa-user"></i>
                            Recorded by <?php echo $payment['paid_by']; ?>
                        </div>
                        <div>
                            <i class="fas fa-clock"></i>
                            <?php echo date('d M Y, h:i A', strtotime($payment['created_at'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Payment Summary -->
        <div class="detail-item" style="margin-top: 1rem;">
            <div class="detail-label">Total Amount Paid</div>
            <div class="detail-value amount-value">
                ₹<?php echo number_format($total_paid, 2); ?> / ₹<?php echo number_format($event['total_amount'], 2); ?>
            </div>
            <div class="progress mt-2" style="height: 10px; border-radius: 5px;">
                <?php $percentage = min(($total_paid / $event['total_amount']) * 100, 100); ?>
                <div class="progress-bar bg-success" role="progressbar" 
                     style="width: <?php echo $percentage; ?>%;" 
                     aria-valuenow="<?php echo $percentage; ?>" 
                     aria-valuemin="0" 
                     aria-valuemax="100">
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center text-muted">
            <p>No payment records found.</p>
        </div>
    <?php endif; ?>
</div>
<div class="payment-section">
    <h2 class="gallery-title">Maintenance Checklist Log</h2>

    <?php if (!empty($maintenance_logs)): ?>
        <div class="d-none d-md-block table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Item</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Checked By</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($maintenance_logs as $index => $log): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($log['item_name']) ?></td>
                            <td>
                                <?php
                                    $badgeClass = [
                                        'ok' => 'success',
                                        'not_ok' => 'danger',
                                        'na' => 'secondary'
                                    ][$log['status']];
                                ?>
                                <span class="badge badge-<?= $badgeClass ?>">
                                    <?= strtoupper($log['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($log['remarks'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($log['checked_by']) ?></td>
                            <td><?= date('d M Y, h:i A', strtotime($log['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Version -->
        <div class="d-block d-md-none">
            <?php foreach ($maintenance_logs as $index => $log): ?>
                <?php
                    $badgeClass = [
                        'ok' => 'success',
                        'not_ok' => 'danger',
                        'na' => 'secondary'
                    ][$log['status']];
                ?>
                <div class="card mb-3 shadow-sm border-left-<?= $badgeClass ?>">
                    <div class="card-body py-2 px-3">
                        <h6 class="mb-1 font-weight-bold"><?= htmlspecialchars($log['item_name']) ?></h6>
                        <p class="mb-1">
                            <span class="badge badge-<?= $badgeClass ?>"><?= strtoupper($log['status']) ?></span>
                            <br><small class="text-muted"><?= htmlspecialchars($log['remarks'] ?? '-') ?></small>
                        </p>
                        <div class="d-flex justify-content-between text-muted" style="font-size: 0.85rem;">
                            <span><i class="fas fa-user"></i> <?= htmlspecialchars($log['checked_by']) ?></span>
                            <span><i class="fas fa-clock"></i> <?= date('d M Y, h:i A', strtotime($log['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-muted">No maintenance checklist submitted for this event.</p>
    <?php endif; ?>
</div>


        <?php if ($documents_result->num_rows > 0): ?>
        <div class="gallery-section">
            <h2 class="gallery-title">Event Gallery</h2>
            <div class="gallery-grid">
                <?php while ($doc = $documents_result->fetch_assoc()): ?>
                    <?php
                    $fileExt = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
                    if (in_array($fileExt, ['jpg', 'jpeg', 'png'])):
                    ?>
                    <div class="gallery-item">
                        <img src="<?php echo $doc['file_path']; ?>" alt="Event Photo" class="gallery-img">
                        <div class="gallery-overlay">
                            <a href="<?php echo $doc['file_path']; ?>" target="_blank" class="gallery-btn">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endwhile; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="gallery-section text-center">
            <h2 class="gallery-title">Event Gallery</h2>
            <p class="text-muted">No images uploaded for this event yet.</p>
        </div>
        <?php endif; ?>

        <a href="events_list.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Back to List
        </a>
    </div>
</div>

<?php include 'footer.php'; ?>