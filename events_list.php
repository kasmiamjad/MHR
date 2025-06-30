<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Location: login.php');
    exit;
}
?>
<?php include 'header.php'; ?>
<!-- Add Delete Confirmation Modal -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="confirmDeleteModalLabel">Confirm Deletion</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this booking?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
      </div>
    </div>
  </div>
</div>
<style>
    :root {
        --primary: #2a2a72;
        --primary-light: #3d3d9d;
        --secondary: #4CAF50;
        --danger: #dc3545;
        --warning: #ffc107;
        --info: #17a2b8;
        --dark: #343a40;
        --light: #f8f9fa;
        --white: #ffffff;
        --border-radius: 12px;
        --shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
        --transition: all 0.3s ease;
    }

    /* Global Styles */
    body {
        background-color: #f4f6f9;
        color: var(--dark);
    }

    /* Dashboard Header */
    .dashboard-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        color: var(--white);
        padding: 2rem 0;
        margin-bottom: -3rem;
        position: relative;
    }

    .dashboard-header::after {
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

    /* Stats Cards */
    .stats-container {
        position: relative;
        z-index: 1;
        padding: 0 1rem;
        margin-bottom: 2rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }

    .stat-card {
        background: var(--white);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        box-shadow: var(--shadow);
        transition: var(--transition);
        overflow: hidden;
        position: relative;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--primary-light));
    }

    .stat-value {
        font-size: 2.2rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 0.5rem;
    }

    .stat-label {
        color: #6c757d;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Navigation Buttons */
    .nav-buttons {
        display: flex;
        gap: 1rem;
    }

    .nav-btn {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: var(--white);
        padding: 0.6rem 1.2rem;
        border-radius: var(--border-radius);
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .nav-btn:hover {
        background: var(--white);
        color: var(--primary);
        transform: translateY(-2px);
    }

    /* Main Content Card */
    .content-card {
        background: var(--white);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        margin: 0 1rem 2rem;
        overflow: hidden;
    }

    /* Table Styles */
    .table {
        margin-bottom: 0;
    }

    .table thead th {
        background: var(--light);
        border-bottom: 2px solid var(--primary);
        padding: 1rem;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        color: var(--primary);
    }

    .table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .action-btn {
        padding: 0.4rem 0.8rem;
        border-radius: var(--border-radius);
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }

    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    /* Mobile Cards */
    .mobile-card {
        background: var(--white);
        border-radius: var(--border-radius);
        margin-bottom: 1rem;
        padding: 1.5rem;
        box-shadow: var(--shadow);
    }

    .mobile-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .guest-name {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--primary);
    }

    .booking-details {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .detail-label {
        font-size: 0.85rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.3rem;
    }

    .detail-value {
        font-weight: 500;
        color: var(--dark);
    }

    .mobile-actions {
        display: flex;
        gap: 0.5rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        flex-wrap: wrap;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .dashboard-header {
            padding: 1.5rem 0;
            text-align: center;
        }

        .nav-buttons {
            justify-content: center;
            margin-top: 1rem;
        }

        .booking-details {
            grid-template-columns: 1fr;
        }

        .action-buttons, .mobile-actions {
            justify-content: center;
        }

        .desktop-table {
            display: none;
        }

        .mobile-view {
            display: block;
        }
    }

    .action-buttons {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}

.action-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    border: none;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.action-btn i {
    font-size: 14px;
    position: relative;
    z-index: 2;
}

.action-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: currentColor;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.action-btn:hover::before {
    opacity: 0.1;
}

/* Button variants */
.action-btn.whatsapp {
    background: #25D366;
    color: white;
}

.action-btn.edit {
    background: #FFC107;
    color: white;
}

.action-btn.view {
    background: #17a2b8;
    color: white;
}

.action-btn.delete {
    background: #dc3545;
    color: white;
}

.action-btn.upload {
    background: #6610f2;
    color: white;
}

/* Tooltip */
.action-btn .tooltip-text {
    position: absolute;
    bottom: -30px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.action-btn:hover .tooltip-text {
    opacity: 1;
    visibility: visible;
    bottom: -35px;
}
.action-buttons {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
}

.action-buttons .btn {
    width: 32px;
    height: 32px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.action-buttons .btn:hover {
    transform: translateY(-2px);
}

/* Button colors */
.btn-info { background: #17a2b8; }
.btn-warning { background: #ffc107; }
.btn-success { background: #28a745; }
.btn-primary { background: #007bff; }
.btn-danger { background: #dc3545; }
.btn-secondary { background: #6c757d; }

/* Icon colors */
.action-buttons .btn i {
    font-size: 14px;
    color: white;
}

@media (max-width: 768px) {
    .action-buttons {
        justify-content: center;
        gap: 8px;
    }

    .action-buttons .btn {
        width: 40px;
        height: 40px;
    }

    .action-buttons .btn i {
        font-size: 16px;
    }
}
.container, .container-fluid, .container-lg, .container-md, .container-sm, .container-xl {
    width: 100%;
    padding-right: 15px;
    padding-left: 15px;
    margin-right: auto;
    margin-left: auto;
    margin-bottom: 30px;
}

.mobile-table-card {
    background: var(--surface-color);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: transform 0.2s ease;
    border-left: 4px solid #e0e0e0;
}

/* Booking Status Colors */
.mobile-table-card.status-completed {
    border-left-color: #9e9e9e;
    background: #f5f5f5;
}

.mobile-table-card.status-today {
    border-left-color: #2196F3;
    background: #e3f2fd;
}

.mobile-table-card.status-pending {
    border-left-color: #f44336;
    background: #ffebee;
}

.mobile-table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(0,0,0,0.08);
}

.guest-name {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
}

.booking-status {
    font-size: 12px;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 500;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.status-badge.completed {
    background: #eeeeee;
    color: #616161;
}

.status-badge.today {
    background: #e3f2fd;
    color: #1976D2;
}

.status-badge.pending {
    background: #ffebee;
    color: #d32f2f;
}

.mobile-table-details {
    display: grid;
    gap: 12px;
    margin-bottom: 16px;
}

.detail-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.detail-icon {
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-secondary);
}

.label {
    color: var(--text-secondary);
    font-size: 14px;
    font-weight: 500;
    min-width: 80px;
}

.value {
    color: var(--text-primary);
    font-weight: 500;
}

/* Desktop Table Styles */
.table tbody tr {
    transition: all 0.2s ease;
}

.table tbody tr.status-completed {
    background: #f5f5f5;
}

.table tbody tr.status-today {
    background: #e3f2fd;
}

.table tbody tr.status-pending {
    background: #ffebee;
}

/* Add Event Button */
.header-actions {
    margin-top: 16px;
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.add-event-btn {
    background: white;
    color: var(--primary-color);
    padding: 12px 24px;
    border-radius: 12px;
    border: none;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.add-event-btn:active {
    transform: scale(0.98);
    background: rgba(255,255,255,0.9);
}
@media (max-width: 768px) {
    .dashboard-header {
        padding: 1rem;
    }
    
    .header-actions {
        flex-direction: column;
        width: 100%;
        margin-top: 1rem;
    }
    
    .add-event-btn {
        width: 100%;
        justify-content: center;
    }
    
    .nav-buttons {
        width: 100%;
        display: flex;
        gap: 8px;
    }
    
    .nav-btn {
        flex: 1;
        justify-content: center;
    }
}
/* Payment Status Styles */
.payment-status {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .payment-pending {
        background: #fff3cd;
        color: #856404;
        border-left: 3px solid #ffc107;
    }
    
    .payment-overdue {
        background: #f8d7da;
        color: #721c24;
        border-left: 3px solid #dc3545;
        animation: pulse 2s infinite;
    }
    
    .payment-complete {
        background: #d4edda;
        color: #155724;
        border-left: 3px solid #28a745;
    }
    
    tr.payment-pending-row td:first-child {
        border-left: 4px solid #ffc107;
    }
    
    tr.payment-overdue-row td:first-child {
        border-left: 4px solid #dc3545;
        animation: pulse-border 2s infinite;
    }
    
    @keyframes pulse-border {
        0% { border-left-color: #dc3545; }
        50% { border-left-color: #ff6b6b; }
        100% { border-left-color: #dc3545; }
    }

/* Marquee Alert */
.payment-alert-marquee {
        background: #dc3545;
        color: white;
        padding: 10px 0;
        margin: 0 1rem 1rem;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .payment-alert-marquee .content {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        white-space: nowrap;
        animation: marquee 20s linear infinite;
        width: max-content;
    }
    
    @keyframes marquee {
        0% { transform: translateX(100%); }
        100% { transform: translateX(-100%); }
    }


    .maintenance-ok {
    border-left: 4px solid #28a745 !important;
}

.maintenance-issue {
    border-left: 4px solid #dc3545 !important;
    background-color: #fff5f5;
}

.maintenance-pending {
    border-left: 4px solid #6c757d !important;
    background-color: #f8f9fa;
}

</style>

<div class="dashboard-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <a href="booking.php" class="add-event-btn">
                        <i class="fas fa-plus"></i>
                        Add Booking
                    </a>
                <div id="monthDisplay" class="mt-2 h4 mb-0 text-white-50"></div>
            </div>
            
            <div class="nav-buttons">
                <button id="prevMonth" class="nav-btn">
                    <i class="fas fa-arrow-left"></i> Previous
                </button>
                <button id="nextMonth" class="nav-btn">
                    Next <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container">
    
    <!-- Stats Cards -->
    <div class="stats-container">
        <div class="stats-grid">
            
            <div class="stat-card">
                <div class="stat-value" id="totalBookings">0</div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <?php if ($_SESSION['role'] == 'admin'): ?>
                <div class="stat-card">
                    <div class="stat-value" id="totalRevenue">₹0</div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="totalAdvance">₹0</div>
                    <div class="stat-label">Total Advance</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show mx-3">
            <?= $_SESSION['message']; unset($_SESSION['message']); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
        <!-- Payment Alerts Marquee -->
    <div id="paymentAlertsContainer" class="payment-alert-marquee d-none">
        <div class="content">
            <i class="fas fa-exclamation-triangle"></i>
            <span id="paymentAlertText"></span>
            <i class="fas fa-exclamation-triangle"></i>
        </div>
    </div>
    <!-- Main Content -->
    <div class="content-card">
        <!-- Desktop Table View -->
        <div class="table-responsive desktop-table">
            <table id="eventsTable" class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Guest Name</th>
                        <th>Phone</th>
                        <th>Booking Date</th>
                        <th>Checkout</th>
                        <th>Adults</th>
                        <th>Kids</th>
                        <th>Payment Status</th>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                            <th>Total</th>
                            <th>Advance</th>
                        <?php endif; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="eventsTableBody">
                    <!-- Rows will be dynamically appended here -->
                </tbody>
                <tfoot id="eventsTableFoot">
                    <!-- Totals will be dynamically appended here -->
                </tfoot>
            </table>
        </div>

        <!-- Mobile Cards View -->
        <div id="mobileEvents" class="d-none px-3 py-3">
            <!-- Mobile cards will be dynamically appended here -->
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
<script>
$(document).ready(function () {
let userRole = '<?php echo $_SESSION['role']; ?>'; 
let currentDate = new Date();
let isMobile = window.innerWidth <= 576;
updateCalendar();

    // Then set up your buttons as you did:
    $('#prevMonth').on('click', function () {
        currentDate.setMonth(currentDate.getMonth() - 1);
        updateCalendar();
    });

    $('#nextMonth').on('click', function () {
        currentDate.setMonth(currentDate.getMonth() + 1);
        updateCalendar();
    });
function formatDate(date) {
    return date.toLocaleString('default', { month: 'long', year: 'numeric' });
}
function getPaymentStatus(event) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const checkoutDate = new Date(event.checkout);
    checkoutDate.setHours(0, 0, 0, 0);
    const balance = parseFloat(event.total_amount) - parseFloat(event.advance_amount);
    
    if (balance <= 0) {
        return {
            text: 'Paid',
            class: 'payment-complete',
            icon: 'fa-check-circle',
            rowClass: ''
        };
    } else if (checkoutDate <= today) {
        return {
            text: 'Overdue!',
            class: 'payment-overdue',
            icon: 'fa-exclamation-circle',
            rowClass: 'payment-overdue-row'
        };
    } else {
        return {
            text: 'Pending',
            class: 'payment-pending',
            icon: 'fa-clock',
            rowClass: 'payment-pending-row'
        };
    }
}
function createMobileCard(event, srNo) {
    const eventDate = new Date(event.event_date);
    const checkoutDate = new Date(event.checkout);
    const today = new Date();
    const formattedEventDate = eventDate.toLocaleDateString('en-GB');
    const formattedCheckoutDate = checkoutDate.toLocaleDateString('en-GB');
    const paymentStatus = getPaymentStatus(event);
    // Determine booking status
    let statusClass = '';
    let statusBadge = '';
    
    if (checkoutDate < today) {
        statusClass = 'status-completed';
        statusBadge = '<span class="status-badge completed"><i class="fas fa-check"></i> Completed</span>';
    } else if (eventDate.toDateString() === today.toDateString()) {
        statusClass = 'status-today';
        statusBadge = '<span class="status-badge today"><i class="fas fa-clock"></i> Today</span>';
    } else if (eventDate > today) {
        statusClass = 'status-pending';
        statusBadge = '<span class="status-badge pending"><i class="fas fa-hourglass-half"></i> Pending</span>';
    }

    let maintenanceClass = '';
    let maintenanceBadge = '';

    if (event.is_maintenance_filled) {
        if (event.has_maintenance_issue) {
            maintenanceClass = 'maintenance-issue';
            maintenanceBadge = `<span class="badge badge-danger">Maintenance Issue</span>`;
        } else {
            maintenanceClass = 'maintenance-ok';
            maintenanceBadge = `<span class="badge badge-success">Maintained</span>`;
        }
    } else {
        maintenanceClass = 'maintenance-pending';
        maintenanceBadge = `<span class="badge badge-secondary">Not Checked</span>`;
    }
    statusClass += ' ' + maintenanceClass;

    return `
        <div class="mobile-table-card ${statusClass} ${paymentStatus.rowClass}">
            <div class="mobile-table-header">
                <div>
                    <div class="guest-name">${event.guest_name}</div>
                    ${statusBadge}${maintenanceBadge}
                </div>
                <div class="booking-number">#${srNo}</div>
            </div>
            <div class="mobile-table-details">
                <div class="detail-row">
                    <div class="detail-icon"><i class="fas fa-phone"></i></div>
                    <span class="value">${event.phone}</span>
                </div>
                <div class="detail-row">
                    <div class="detail-icon"><i class="fas fa-calendar-check"></i></div>
                    <span class="label">Check In:</span>
                    <span class="value">${formattedEventDate}</span>
                </div>
                <div class="detail-row">
                    <div class="detail-icon"><i class="fas fa-calendar-times"></i></div>
                    <span class="label">Check Out:</span>
                    <span class="value">${formattedCheckoutDate}</span>
                </div>
                <div class="detail-row">
                    <div class="detail-icon"><i class="fas fa-users"></i></div>
                    <span class="label">Guests:</span>
                    <span class="value">${event.adults} Adults, ${event.kids} Kids</span>
                </div>
                <div class="detail-row">
                        <div class="detail-icon"><i class="fas fa-info-circle"></i></div>
                        <span class="label">Payment:</span>
                        <span class="payment-status ${paymentStatus.class}">
                            <i class="fas ${paymentStatus.icon}"></i> ${paymentStatus.text}
                        </span>
                </div>
                ${userRole === 'admin' ? `
                    <div class="detail-row">
                        <div class="detail-icon"><i class="fas fa-money-bill-wave"></i></div>
                        <span class="label">Amount:</span>
                        <span class="value">₹${event.total_amount}</span>
                    </div>
                    <div class="detail-row">
                        <div class="detail-icon"><i class="fas fa-hand-holding-usd"></i></div>
                        <span class="label">Advance:</span>
                        <span class="value">₹${event.advance_amount}</span>
                    </div>
                ` : ''}
            </div>
            <div class="action-buttons">
                <a href='resend_confirmation.php?id=${event.id}' class='action-btn whatsapp'>
                    <i class='fab fa-whatsapp'></i>
                </a>
                <a href='events_edit.php?id=${event.id}' class='action-btn edit'>
                    <i class='fas fa-edit'></i>
                </a>
                <a href='payment.php?id=${event.id}' class='action-btn'>
                    <i class='fas fa-money-bill-wave'></i>
                </a>
                <a href='events_view.php?id=${event.id}' class='action-btn view'>
                    <i class='fas fa-eye'></i>
                </a>
                <a href='events_delete.php?id=${event.id}' onclick='return confirm("Are you sure?")' class='action-btn delete'>
                    <i class='fas fa-trash'></i>
                </a>
                <a href="upload_image.php?event_id=${event.id}" class='action-btn upload'>
                    <i class="fas fa-upload"></i>
                </a>
                <a href='maintenance_fill.php?event_id=${event.id}' class='btn btn-info btn-sm' title="Daily Maintenance">
                    <i class='fas fa-tools'></i>
                </a>

            </div>
        </div>
    `;
}
function updatePaymentAlerts(events) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const alertContainer = document.getElementById('paymentAlertsContainer');
    const alertText = document.getElementById('paymentAlertText');
    
    // Get all bookings with pending payments that are due or overdue
    const pendingPayments = events.filter(event => {
        const eventDate = new Date(event.event_date);
        eventDate.setHours(0, 0, 0, 0);
        const balance = parseFloat(event.total_amount) - parseFloat(event.advance_amount);
        return balance > 0 && eventDate <= today;
    });
    
    if (pendingPayments.length > 0) {
        // Calculate total pending amount
        const totalPending = pendingPayments.reduce((sum, event) => {
            return sum + (parseFloat(event.total_amount) - parseFloat(event.advance_amount));
        }, 0);
        
        // Create list of pending payments with amounts and due status
        const paymentList = pendingPayments.map(e => {
            const balance = parseFloat(e.total_amount) - parseFloat(e.advance_amount);
            const eventDate = new Date(e.event_date);
            const isOverdue = eventDate < today;
            
            return `${e.guest_name} (₹${balance.toFixed(2)} ${isOverdue ? 'OVERDUE' : 'due today'})`;
        }).join(', ');
        
        // Determine if all are overdue or some are due today
        const overdueCount = pendingPayments.filter(e => new Date(e.event_date) < today).length;
        let statusText;
        
        if (overdueCount === pendingPayments.length) {
            statusText = 'OVERDUE PAYMENTS';
        } else if (overdueCount > 0) {
            statusText = 'PENDING PAYMENTS (Some overdue)';
        } else {
            statusText = 'PAYMENTS DUE TODAY';
        }
        
        alertText.textContent = `${statusText}: ₹${totalPending.toFixed(2)} total - ${paymentList}`;
        alertContainer.classList.remove('d-none');
    } else {
        alertContainer.classList.add('d-none');
    }
}


function updateTableRow(event, srNo) {
    const eventDate = new Date(event.event_date);
    const checkoutDate = new Date(event.checkout);
    const today = new Date();
    
    let statusClass = '';
    if (checkoutDate < today) {
        statusClass = 'status-completed';
    } else if (eventDate.toDateString() === today.toDateString()) {
        statusClass = 'status-today';
    } else if (eventDate > today) {
        statusClass = 'status-pending';
    }
    
    return `<tr class="${statusClass}">
        <!-- Your existing table row content -->
    </tr>`;
}
function fetchEvents(month, year) {
    // Show loading state
    document.getElementById('eventsTableBody').innerHTML = '<tr><td colspan="11" class="text-center">Loading...</td></tr>';
    document.getElementById('mobileEvents').innerHTML = '<div class="text-center">Loading...</div>';
    
    $.ajax({
        url: 'fetch_events.php',
        method: 'GET',
        data: { month: month + 1, year: year },
        success: function(response) {
        const events = JSON.parse(response);
        const sortedEvents = sortEvents(events);
        updateStats(events);
        updatePaymentAlerts(events);

        const tbody = document.getElementById('eventsTableBody');
        const tfoot = document.getElementById('eventsTableFoot');
        const mobileEvents = document.getElementById('mobileEvents');

        tbody.innerHTML = '';
        tfoot.innerHTML = '';
        mobileEvents.innerHTML = '';

        if (events.length > 0) {
            let srNo = 1;
            let totalAmount = 0;
            let totalAdvance = 0;

            sortedEvents.forEach(event => {
                const eventDate = new Date(event.event_date);
                const checkoutDate = new Date(event.checkout);
                const formattedEventDate = eventDate.toLocaleDateString('en-GB');
                const formattedCheckoutDate = checkoutDate.toLocaleDateString('en-GB');
                const paymentStatus = getPaymentStatus(event);

                // ✅ Maintenance Logic
                let maintenanceClass = '';
                let maintenanceBadge = '';
                if (event.is_maintenance_filled) {
                    if (event.has_maintenance_issue) {
                        maintenanceClass = 'maintenance-issue';
                        maintenanceBadge = `<span class="badge badge-danger">Issue</span>`;
                    } else {
                        maintenanceClass = 'maintenance-ok';
                        maintenanceBadge = `<span class="badge badge-success">Maintained</span>`;
                    }
                } else {
                    maintenanceClass = 'maintenance-pending';
                    maintenanceBadge = `<span class="badge badge-secondary">Not Checked</span>`;
                }

                totalAmount += parseFloat(event.total_amount);
                totalAdvance += parseFloat(event.advance_amount);

                tbody.innerHTML += `
                    <tr class="${maintenanceClass}">
                        <td>${srNo}</td>
                        <td>${event.guest_name} <br/> ${maintenanceBadge}</td>
                        <td>${event.phone}</td>
                        <td>${formattedEventDate}</td>
                        <td>${formattedCheckoutDate}</td>
                        <td>${event.adults}</td>
                        <td>${event.kids}</td>
                        <td>
                            <span class="payment-status ${paymentStatus.class}">
                                <i class="fas ${paymentStatus.icon}"></i> ${paymentStatus.text}
                            </span>
                        </td>
                        ${userRole === 'admin' ? `<td>₹${event.total_amount}</td>` : ''}
                        ${userRole === 'admin' ? `<td>₹${event.advance_amount}</td>` : ''}
                        <td class="action-buttons">
                            <a href='resend_confirmation.php?id=${event.id}' class='btn btn-warning btn-sm'><i class='fas fa-whatsapp'></i></a>
                            <a href='events_edit.php?id=${event.id}' class='btn btn-warning btn-sm'><i class='fas fa-edit'></i></a>
                            <a href='events_view.php?id=${event.id}' class='btn btn-warning btn-sm'><i class='fas fa-eye'></i></a>
                            <a href='payment.php?id=${event.id}' class='btn btn-success btn-sm'><i class='fas fa-money-bill-wave'></i></a>
                            <a href='maintenance_fill.php?event_id=${event.id}' class='btn btn-info btn-sm'><i class='fas fa-tools'></i></a>
                            <a href='events_delete.php?id=${event.id}' class='btn btn-danger btn-sm'><i class='fas fa-trash'></i></a>
                            <a href='upload_image.php?event_id=${event.id}' class='btn btn-primary btn-sm'><i class='fas fa-upload'></i></a>
                        </td>
                    </tr>
                `;

                // 👇 Also pass maintenance data to mobile card
                mobileEvents.innerHTML += createMobileCard(event, srNo);
                srNo++;
            });

            // Totals
            if (userRole === 'admin') {
                tfoot.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-end"><strong>Totals:</strong></td>
                        <td><strong>₹${totalAmount.toFixed(2)}</strong></td>
                        <td><strong>₹${totalAdvance.toFixed(2)}</strong></td>
                        <td></td>
                    </tr>
                `;
            }
        } else {
            tbody.innerHTML = `<tr><td colspan="11" class="text-center">No bookings found for this month.</td></tr>`;
            mobileEvents.innerHTML = `<div class="text-center p-3">No bookings found for this month.</div>`;
        }

        // Toggle views
        if (isMobile) {
            document.querySelector('.desktop-table').style.display = 'none';
            mobileEvents.classList.remove('d-none');
            mobileEvents.style.display = 'block';
        } else {
            document.querySelector('.desktop-table').style.display = 'block';
            mobileEvents.classList.add('d-none');
        }
    },

        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            const errorMessage = '<div class="text-center text-danger">Error loading data. Please try again.</div>';
            document.getElementById('eventsTableBody').innerHTML = `<tr><td colspan="11" class="text-center text-danger">Error loading data. Please try again.</td></tr>`;
            document.getElementById('mobileEvents').innerHTML = errorMessage;
        }
    });
}
function sortEvents(events) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return events.sort((a, b) => {
        const dateA = new Date(a.event_date);
        const dateB = new Date(b.event_date);
        const checkoutA = new Date(a.checkout);
        const checkoutB = new Date(b.checkout);

        // First sort by status
        if (dateA >= today && checkoutA >= today && dateB < today) return -1;
        if (dateB >= today && checkoutB >= today && dateA < today) return 1;

        // Then sort by date
        return dateA - dateB;
    });
}
function updateDashboardHeader() {
    // Find the existing header content
    const headerContent = document.querySelector('.dashboard-header .container');
    if (headerContent) {
        headerContent.innerHTML = `
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1 class="mb-0">Villa Bookings</h1>
                    <div id="monthDisplay" class="mt-2 h4 mb-0 text-white-50"></div>
                </div>
                <div class="header-actions">
                    <a href="booking.php" class="add-event-btn">
                        <i class="fas fa-plus"></i>
                        Add Booking
                    </a>
                    <div class="nav-buttons">
                        <button id="prevMonth" class="nav-btn">
                            <i class="fas fa-arrow-left"></i>
                            Previous
                        </button>
                        <button id="nextMonth" class="nav-btn">
                            Next
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
}
function updateCalendar() {
    document.getElementById('monthDisplay').textContent = formatDate(currentDate);
    fetchEvents(currentDate.getMonth(), currentDate.getFullYear());
}

document.addEventListener('DOMContentLoaded', function() {
    updateDashboardHeader();
    updateCalendar();

    document.getElementById('prevMonth').addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        updateCalendar();
    });

    document.getElementById('nextMonth').addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        updateCalendar();
    });

    window.addEventListener('resize', function() {
        isMobile = window.innerWidth <= 576;
        if (isMobile) {
            document.querySelector('.desktop-table').style.display = 'none';
            document.getElementById('mobileEvents').classList.remove('d-none');
            document.getElementById('mobileEvents').style.display = 'block';
        } else {
            document.querySelector('.desktop-table').style.display = 'block';
            document.getElementById('mobileEvents').classList.add('d-none');
        }
    });
});
// Add this function to your JavaScript

function updateStats(events) {
    // Initialize totals
    let totalBookings = events.length;
    let totalRevenue = 0;
    let totalAdvance = 0;

    // Calculate totals
    events.forEach(event => {
        // Convert string amounts to numbers and sum them up
        totalRevenue += parseFloat(event.total_amount || 0);
        totalAdvance += parseFloat(event.advance_amount || 0);
    });

    // Update the display
    document.getElementById('totalBookings').textContent = totalBookings;
    
    if (userRole === 'admin') {
        // Format currency values
        const formattedRevenue = new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: 'INR',
            maximumFractionDigits: 0
        }).format(totalRevenue);
        
        const formattedAdvance = new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: 'INR',
            maximumFractionDigits: 0
        }).format(totalAdvance);

        document.getElementById('totalRevenue').textContent = formattedRevenue;
        document.getElementById('totalAdvance').textContent = formattedAdvance;
    }
}

let deleteUrl = '';

  // Intercept delete button clicks
  $(document).on('click', 'a.action-btn.delete, a.btn-danger', function(e) {
    const href = $(this).attr('href');
    if (href.includes('events_delete.php')) {
      e.preventDefault();
      deleteUrl = href;
      $('#confirmDeleteModal').modal('show');
    }
  });

  // Handle confirmation button click
  $('#confirmDeleteBtn').click(function() {
    if (deleteUrl) {
      window.location.href = deleteUrl;
    }
  });


// Then modify your fetchEvents success handler:


    // // Example variables, replace if needed
    // let month = new Date().getMonth();
    // let year = new Date().getFullYear();

    // $.ajax({
    //     url: 'fetch_events.php',
    //     method: 'GET',
    //     data: { month: month + 1, year: year },
    //     success: function(response) {
    //         const events = JSON.parse(response);

    //         // Update stats first
    //         updateStats(events);
            
    //         // Continue processing events...
    //         // Your existing table/mobile view update code here
    //     },
    //     error: function(xhr, status, error) {
    //         console.error('AJAX Error:', status, error);
    //         // Clear stats on error
    //         document.getElementById('totalBookings').textContent = '0';
    //         if (userRole === 'admin') {
    //             document.getElementById('totalRevenue').textContent = '₹0';
    //             document.getElementById('totalAdvance').textContent = '₹0';
    //         }
    //     }
    // });
});

</script>
