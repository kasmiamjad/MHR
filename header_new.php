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
        <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- jQuery & Bootstrap JS (for dropdowns) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    
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
    <style>
  .navbar-nav .nav-link {
    font-weight: 500;
    padding-right: 1rem;
    padding-left: 1rem;
  }

  .dropdown-menu {
    min-width: 200px;
  }

  .navbar-nav .nav-item .dropdown-menu .dropdown-item {
    padding: 10px 20px;
  }

  .navbar-nav .nav-link i {
    margin-right: 6px;
  }

  @media (max-width: 768px) {
    .navbar-nav {
      padding-top: 1rem;
    }

    .navbar-nav .nav-item {
      width: 100%;
    }

    .navbar-nav .nav-link {
      padding: 0.75rem 1rem;
    }

    .dropdown-menu {
      position: static;
      float: none;
    }
  }

  .navbar-brand i {
    font-size: 1.5rem;
  }
  .nav-link {
    font-weight: 500;
    transition: color 0.2s ease;
  }
  .nav-link:hover, .nav-link.active {
    color: #0d6efd;
  }
  .dropdown-menu a {
    display: flex;
    align-items: center;
    gap: 8px;
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
        <!-- Modern Responsive Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top px-3 py-2">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <i class="fas fa-mountain text-primary"></i> <strong>MHR</strong>
            </a>

            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ml-auto flex-wrap">
                    <li class="nav-item"><a class="nav-link" href="events_list.php"><i class="fas fa-list"></i> Bookings</a></li>
                    <li class="nav-item"><a class="nav-link" href="booking.php"><i class="fas fa-plus"></i> Add Booking</a></li>
                    <li class="nav-item"><a class="nav-link" href="expenses_list.php"><i class="fas fa-receipt"></i> Expenses</a></li>
                    <li class="nav-item"><a class="nav-link" href="calculator.php"><i class="fas fa-calculator"></i> Calculator</a></li>
                    <li class="nav-item"><a class="nav-link" href="calender.php"><i class="fas fa-calendar-alt"></i> Calendar</a></li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="attendanceMenu" role="button" data-toggle="dropdown">
                        <i class="fas fa-user-check"></i> Attendance
                        </a>
                        <div class="dropdown-menu">
                        <a class="dropdown-item" href="attendance.php">Daily Attendance</a>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                            <a class="dropdown-item" href="all_attendance_list.php">Attendance Report</a>
                        <?php endif; ?>
                        </div>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="maintenanceMenu" role="button" data-toggle="dropdown">
                        <i class="fas fa-tools"></i> Maintenance
                        </a>
                        <div class="dropdown-menu">
                        <a class="dropdown-item" href="maintenance_manage_items.php">Checklist Items</a>
                        <a class="dropdown-item" href="calender.php">Fill Checklist</a>
                        </div>
                    </li>

                    <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="reportsMenu" role="button" data-toggle="dropdown">
                        <i class="fas fa-chart-line"></i> Reports
                        </a>
                        <div class="dropdown-menu">
                        <a class="dropdown-item" href="reports.php">General Reports</a>
                        <a class="dropdown-item" href="booking_reports.php">Booking Reports</a>
                        <a class="dropdown-item" href="expense_reports.php">Expense Reports</a>
                        <a class="dropdown-item" href="payment_activity.php">Payment Activity</a>
                        </div>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="accountMenu" role="button" data-toggle="dropdown">
                        <i class="fas fa-wallet"></i> Accounts
                        </a>
                        <div class="dropdown-menu">
                        <a class="dropdown-item" href="account_data.php">Account Status</a>
                        <a class="dropdown-item" href="shareholder_account.php">Shareholder Account</a>
                        </div>
                    </li>

                    <li class="nav-item"><a class="nav-link" href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                    <li class="nav-item"><a class="nav-link" href="user_registration.php"><i class="fas fa-user-plus"></i> Users</a></li>
                    <?php endif; ?>

                    <li class="nav-item">
                        <a class="nav-link text-danger font-weight-bold" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                    </ul>

            </div>
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
                            Chcklist
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
