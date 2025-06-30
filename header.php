<?php
require_once 'config.php';

// Only fetch notifications if user is admin
$user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['role'] == 'admin';
$cash_query = "SELECT 
    c.id,
    c.amount,
    c.collected_date,
    u.username
FROM mhr_cash_collections c
JOIN mhr_users u ON c.user_id = u.id
WHERE status = 'pending'";

if (!$is_admin) {
    $cash_query .= " AND c.user_id = " . $user_id;
}

$cash_result = $conn->query($cash_query);
$pending_cash = array();
$total_pending = 0;

while ($row = $cash_result->fetch_assoc()) {
    $pending_cash[] = $row;
    $total_pending += $row['amount'];
}

$notifications = [];
if ($_SESSION['role'] === 'admin') {
    try {
        // Get today's new bookings
        $booking_sql = "SELECT COUNT(*) as count 
                       FROM events 
                       WHERE DATE(created_at) = CURDATE() 
                       AND deleted_at IS NULL";
        $stmt = $conn->prepare($booking_sql);
        $stmt->execute();
        $stmt->bind_result($new_bookings);
        $stmt->fetch();
        $stmt->close();

        // Get today's new expenses
        $expense_sql = "SELECT COUNT(*) as count 
                       FROM mhr_expenses 
                       WHERE DATE(created_at) = CURDATE() 
                       AND deleted_at IS NULL";
        $stmt = $conn->prepare($expense_sql);
        $stmt->execute();
        $stmt->bind_result($new_expenses);
        $stmt->fetch();
        $stmt->close();

        $notifications = [
            'bookings' => $new_bookings,
            'expenses' => $new_expenses
        ];
    } catch (Exception $e) {
        error_log("Error fetching notifications: " . $e->getMessage());
    }
   
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <title>Mumbra Hill Resort</title>

    <style>
        :root {
            --primary-color: #2196F3;
            --primary-dark: #1976D2;
            --surface-color: #ffffff;
            --background-color: #f8f9fa;
            --text-primary: #212121;
            --text-secondary: #757575;
            --border-radius: 16px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            background-color: var(--background-color);
            min-height: 100vh;
            padding-bottom: env(safe-area-inset-bottom);
        }

        /* Header Styles */
        .app-header {
            background: var(--surface-color);
            top: 0;
            z-index: 1000;
            padding: env(safe-area-inset-top) 0 0 0;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }

        .navbar {
            background: var(--surface-color);
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary) !important;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        /* Mobile Toggle Button */
        .navbar-toggler {
            width: 44px;
            height: 44px;
            padding: 0;
            border: none;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
        }

        /* Mobile Menu */
        .mobile-menu {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--surface-color);
            z-index: 2000;
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
            display: flex;
            flex-direction: column;
            padding: env(safe-area-inset-top) 16px 16px 16px;
        }

        .mobile-menu.show {
            transform: translateX(0);
        }

        .mobile-menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            margin-bottom: 16px;
        }

        .close-menu {
            width: 44px;
            height: 44px;
            border: none;
            background: transparent;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            overflow-y: auto;
            flex: 1;
        }

        .nav-item {
            margin-bottom: 8px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 16px;
            color: var(--text-primary) !important;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.2s;
            gap: 12px;
            font-weight: 500;
        }

        .nav-link i {
            width: 24px;
            text-align: center;
            font-size: 20px;
            color: var(--text-secondary);
        }

        .nav-link.active {
            background: var(--primary-color);
            color: white !important;
        }

        .nav-link.active i {
            color: white;
        }

        .nav-link:active {
            background: rgba(0,0,0,0.05);
            transform: scale(0.98);
        }

        .logout-button {
            margin-top: auto;
            padding: 16px;
            background: var(--primary-color);
            color: white !important;
            text-decoration: none;
            border-radius: 12px;
            text-align: center;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .logout-button:active {
            background: var(--primary-dark);
            transform: scale(0.98);
        }

        /* Desktop Navigation */
        .desktop-nav {
            display: none;
            margin-left: auto;
        }

        .desktop-nav .nav-menu {
            display: flex;
            gap: 8px;
        }

        .desktop-nav .nav-link {
            padding: 8px 16px;
            white-space: nowrap;
        }

        .desktop-nav .nav-link i {
            font-size: 16px;
            width: auto;
        }

        .desktop-logout {
            margin-left: 16px;
            padding: 8px 16px;
            background: var(--primary-color);
            color: white !important;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .desktop-logout:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        /* Desktop Media Query */
        @media (min-width: 992px) {
            .navbar {
                padding: 16px 24px;
                max-width: 1400px;
                margin: 0 auto;
            }

            .navbar-toggler {
                display: none;
            }

            .desktop-nav {
                display: flex;
                align-items: center;
            }

            .mobile-menu {
                display: none;
            }
        }
        .dropdown-menu {
    min-width: 280px;
    padding: 0.5rem 0;
    margin-top: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    border: none;
    border-radius: 0.5rem;
}

.dropdown-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #eee;
}

.dropdown-item:last-child {
    border-bottom: none;
}

.dropdown-item i {
    margin-right: 0.5rem;
    width: 20px;
    text-align: center;
}

.badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    margin-left: 0.25rem;
    transform: translate(25%, -50%) !important;
}

.nav-link .badge {
    border: 2px solid #fff;
}

@media (max-width: 768px) {
    .dropdown-menu {
        position: fixed;
        top: 60px;
        left: 0;
        right: 0;
        width: 100%;
        margin: 0;
        border-radius: 0;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
}
/* Submenu Styles */
.nav-submenu {
    display: none;
    padding-left: 20px;
    background: rgba(0,0,0,0.02);
    border-radius: 8px;
    margin: 4px 0;
}

.nav-submenu.show {
    display: block;
}

.nav-submenu .nav-link {
    padding: 12px 16px;
    font-size: 0.95em;
}

.nav-item-with-submenu > .nav-link {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.submenu-toggle {
    margin-left: 8px;
    transition: transform 0.3s;
}

.submenu-toggle.rotated {
    transform: rotate(90deg);
}
    </style>
    
    <script>
        function toggleMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('show');
            document.body.style.overflow = menu.classList.contains('show') ? 'hidden' : '';
        }

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('mobileMenu');
            const menuButton = document.querySelector('.navbar-toggler');
            
            if (!menu.contains(event.target) && !menuButton.contains(event.target) && menu.classList.contains('show')) {
                toggleMenu();
            }
        });
    </script>
    <script>
// Optional: Add this if you want to auto-update notifications
document.addEventListener('DOMContentLoaded', function() {
    // Update notifications every 5 minutes
    setInterval(function() {
        fetch('get_notifications.php')
            .then(response => response.json())
            .then(data => {
                // Update notification count and content
                const badge = document.querySelector('#notificationsDropdown .badge');
                const dropdownMenu = document.querySelector('#notificationsDropdown + .dropdown-menu');
                
                if (data.total > 0) {
                    if (badge) {
                        badge.textContent = data.total;
                    } else {
                        // Create badge if it doesn't exist
                    }
                } else if (badge) {
                    badge.remove();
                }
                
                // Update dropdown content
                // ... update content based on data.bookings and data.expenses
            });
    }, 300000); // 5 minutes
});


function toggleSubmenu(event, submenuId) {
    event.preventDefault();
    const submenu = document.getElementById(submenuId);
    const toggle = event.currentTarget.querySelector('.submenu-toggle');
    submenu.classList.toggle('show');
    toggle.classList.toggle('rotated');
}

// Close submenus when closing mobile menu
document.querySelector('.close-menu')?.addEventListener('click', function() {
    document.querySelectorAll('.nav-submenu').forEach(submenu => {
        submenu.classList.remove('show');
    });
    document.querySelectorAll('.submenu-toggle').forEach(toggle => {
        toggle.classList.remove('rotated');
    });
});
</script>

<?php if ($pending_cash): ?>
<style>
.cash-alert {
    background: #dc3545;
    color: white;
    padding: 0.5rem 1rem;
    position: relative;
    z-index: 1000;
}

.cash-alert a {
    color: white;
    text-decoration: underline;
}

.cash-details {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    min-width: 300px;
    z-index: 1001;
    color:#000;
}

.cash-details.show {
    display: block;
}

.cash-item {
    padding: 1rem;
    border-bottom: 1px solid #eee;
}

.cash-item:last-child {
    border-bottom: none;
}
</style>

<div class="cash-alert">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <?php if ($is_admin): ?>
                <i class="fas fa-money-bill-wave"></i> Total Pending Cash Collections: ₹<?= number_format($total_pending, 2) ?>
            <?php else: ?>
                <i class="fas fa-exclamation-triangle"></i> You have pending cash collection of ₹<?= number_format($total_pending, 2) ?>
            <?php endif; ?>
        </div>
        <div class="position-relative">
            <button class="btn btn-outline-light btn-sm" onclick="toggleCashDetails()">
                View Details
            </button>
            <div class="cash-details" id="cashDetails">
                <?php foreach ($pending_cash as $cash): ?>
                    <div class="cash-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= $cash['username'] ?></strong><br>
                                Date: <?= date('d M Y', strtotime($cash['collected_date'])) ?><br>
                                Amount: ₹<?= number_format($cash['amount'], 2) ?>
                            </div>
                            <?php if ($is_admin): ?>
                                <button class="btn btn-success btn-sm" onclick="markCollected(<?= $cash['id'] ?>)">
                                    Mark Collected
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleCashDetails() {
    document.getElementById('cashDetails').classList.toggle('show');
}

function markCollected(id) {
    if (confirm('Confirm that you have collected this cash?')) {
        $.ajax({
            url: 'mark_cash_collected.php',
            method: 'POST',
            data: { id: id },
            success: function(response) {
                location.reload();
            },
            error: function() {
                alert('Error updating cash collection status');
            }
        });
    }
}

// Close details when clicking outside
document.addEventListener('click', function(e) {
    const details = document.getElementById('cashDetails');
    const btn = e.target.closest('button');
    if (!details.contains(e.target) && !btn?.matches('[onclick="toggleCashDetails()"]')) {
        details.classList.remove('show');
    }
});
</script>
<?php endif; ?>
</head>
<body>
    <div class="app-header">
        <nav class="navbar">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-mountain"></i>
                MHR
            </a>

            <!-- Desktop Navigation -->
            <div class="desktop-nav">
                <ul class="nav-menu">
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/index.php' ? ' active' : '') ?>" href="index.php">
                            <i class="fas fa-home"></i>
                            Home
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/events_list.php' ? ' active' : '') ?>" href="events_list.php">
                            <i class="fas fa-list"></i>
                            Booking List
                        </a>
                    </li>
                    <li class="nav-item nav-item-with-submenu">
                        <a class="nav-link" href="#" onclick="toggleSubmenu(event, 'attendanceSubmenu')">
                            <div>
                                <i class="fas fa-clock"></i>
                                Attendance
                            </div>
                            <i class="fas fa-chevron-right submenu-toggle"></i>
                        </a>
                        <div class="nav-submenu" id="attendanceSubmenu">
                            <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/attendance.php' ? ' active' : '') ?>" href="attendance.php">
                                <i class="fas fa-user-clock"></i>
                                Daily Attendance
                            </a>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/all_attendance_list.php' ? ' active' : '') ?>" href="all_attendance_list.php">
                                <i class="fas fa-list-alt"></i>
                                Attendance Report
                            </a>
                            <?php endif; ?>
                        </div>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/calculator.php' ? ' active' : '') ?>" href="calculator.php">
                            <i class="fas fa-calculator"></i>
                            Calculator
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/calender.php' ? ' active' : '') ?>" href="calender.php">
                            <i class="fas fa-calendar-alt"></i>
                            Calendar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/expenses_list.php' ? ' active' : '') ?>" href="expenses_list.php">
                            <i class="fas fa-receipt"></i>
                            Expenses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/categories.php' ? ' active' : '') ?>" href="categories.php">
                            <i class="fas fa-receipt"></i>
                            Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/maintenance_manage_items.php' ? ' active' : '') ?>" href="maintenance_manage_items.php">
                            <i class="fas fa-receipt"></i>
                            Checklist
                        </a>
                    </li>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    
                    <li class="nav-item nav-item-with-submenu">
                        <a class="nav-link" href="#" onclick="toggleSubmenu(event, 'reportsSubmenu')">
                            <div>
                                <i class="fas fa-chart-bar"></i>
                                Reports
                            </div>
                            <i class="fas fa-chevron-right submenu-toggle"></i>
                        </a>
                        <div class="nav-submenu" id="reportsSubmenu">
                            <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/reports.php' ? ' active' : '') ?>" href="reports.php">
                                <i class="fas fa-chart-line"></i>
                                General Reports
                            </a>
                            <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/payment_activity.php' ? ' active' : '') ?>" href="payment_activity.php">
                                <i class="fas fa-credit-card"></i>
                                Payment Activity
                            </a>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/expense_reports.php' ? ' active' : '') ?>" href="expense_reports.php">
                                <i class="fas fa-file-invoice-dollar"></i>
                                Expense Reports
                            </a>
                            <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/booking_reports.php' ? ' active' : '') ?>" href="booking_reports.php">
                                <i class="fas fa-calendar-check"></i>
                                Booking Reports
                            </a>
                            <?php endif; ?>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/all_attendance_list.php' ? ' active' : '') ?>" href="all_attendance_list.php">
                            <i class="fas fa-clock"></i>
                            Attendance Report
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/account_data.php' ? ' active' : '') ?>" href="account_data.php">
                            <i class="fas fa-clock"></i>
                            Account Status
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/shareholder_account.php' ? ' active' : '') ?>" href="shareholder_account.php">
                            <i class="fas fa-clock"></i>
                           Shareholder Account
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/payment_activity.php' ? ' active' : '') ?>" href="payment_activity.php">
                            <i class="fas fa-credit-card"></i>
                            Payment Activity
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/user_registration.php' ? ' active' : '') ?>" href="user_registration.php">
                            <i class="fas fa-user-plus"></i>
                            Registration
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <a href="logout.php" class="desktop-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    <?php if (($notifications['bookings'] + $notifications['expenses']) > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= $notifications['bookings'] + $notifications['expenses'] ?>
                        </span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="notificationsDropdown">
                    <?php if ($notifications['bookings'] > 0): ?>
                        <a class="dropdown-item" href="events_list.php">
                            <i class="fas fa-calendar-plus text-primary"></i>
                            <?= $notifications['bookings'] ?> New Booking<?= $notifications['bookings'] > 1 ? 's' : '' ?> Today
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($notifications['expenses'] > 0): ?>
                        <a class="dropdown-item" href="expenses_list.php">
                            <i class="fas fa-money-bill text-success"></i>
                            <?= $notifications['expenses'] ?> New Expense<?= $notifications['expenses'] > 1 ? 's' : '' ?> Today
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($notifications['bookings'] + $notifications['expenses'] === 0): ?>
                        <div class="dropdown-item text-muted">
                            No new notifications
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

            <!-- Mobile Menu Toggle -->
            <button class="navbar-toggler" type="button" onclick="toggleMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </nav>
    </div>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-header">
            <div class="navbar-brand">
                <i class="fas fa-mountain"></i>
                Mumbra Hill Resort
            </div>
            <button class="close-menu" onclick="toggleMenu()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <ul class="nav-menu">
            <?php if ($_SESSION['role'] == 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/index.php' ? ' active' : '') ?>" href="index.php">
                    <i class="fas fa-home"></i>
                    Home
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/events_list.php' ? ' active' : '') ?>" href="events_list.php">
                    <i class="fas fa-list"></i>
                    Booking List
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/expenses_list.php' ? ' active' : '') ?>" href="expenses_list.php">
                    <i class="fas fa-receipt"></i>
                    Expenses
                </a>
            </li>
            <li class="nav-item">
                        <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/maintenance_manage_items.php' ? ' active' : '') ?>" href="maintenance_manage_items.php">
                            <i class="fas fa-receipt"></i>
                            Checklist
                        </a>
            </li>            
            <li class="nav-item">
                <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/attendance.php' ? ' active' : '') ?>" href="attendance.php">
                    <i class="fas fa-clock"></i>
                    Attendance
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/calculator.php' ? ' active' : '') ?>" href="calculator.php">
                    <i class="fas fa-calculator"></i>
                    Calculator
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/calender.php' ? ' active' : '') ?>" href="calender.php">
                    <i class="fas fa-calendar-alt"></i>
                    Calendar
                </a>
            </li>
            
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li class="nav-item">
                        <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/account_data.php' ? ' active' : '') ?>" href="account_data.php">
                            <i class="fas fa-clock"></i>
                            Account Status
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/shareholder_account.php' ? ' active' : '') ?>" href="shareholder_account.php">
                            <i class="fas fa-clock"></i>
                           Shareholder Account
                        </a>
                    </li>

                <li class="nav-item">
                    <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/reports.php' ? ' active' : '') ?>" href="reports.php">
                        <i class="fas fa-chart-bar"></i>
                        Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/all_attendance_list.php' ? ' active' : '') ?>" href="all_attendance_list.php">
                        <i class="fas fa-clock"></i>
                        Attendance Report
                    </a>
                </li>
                <li class="nav-item">
                        <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/payment_activity.php' ? ' active' : '') ?>" href="payment_activity.php">
                            <i class="fas fa-credit-card"></i>
                            Payment Activity
                        </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($_SERVER['PHP_SELF'] == '/user_registration.php' ? ' active' : '') ?>" href="user_registration.php">
                        <i class="fas fa-user-plus"></i>
                        Registration
                    </a>
                </li>        
            <?php endif; ?>
        </ul>

        <a href="logout.php" class="logout-button">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </div>
