<?php 
session_start(); 
require 'config.php';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$today = date('Y-m-d');

$booking_sql = "SELECT COUNT(*) as total_bookings, 
                       COALESCE(SUM(adults), 0) as total_adults, 
                       COALESCE(SUM(kids), 0) as total_kids, 
                       COALESCE(SUM(total_amount), 0) as total_due 
                FROM events 
                WHERE event_date = ? AND deleted_at IS NULL";

$stmt = $conn->prepare($booking_sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$stmt->bind_result($total_bookings, $total_adults, $total_kids, $total_due);
$stmt->fetch();
$stmt->close();


$payment_sql = "SELECT COALESCE(SUM(p.amount), 0) 
                FROM mhr_event_payments p
                INNER JOIN events e ON p.event_id = e.id
                WHERE e.event_date = ? AND e.deleted_at IS NULL";

$stmt = $conn->prepare($payment_sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$stmt->bind_result($total_paid);
$stmt->fetch();
$stmt->close();

$pending_amount = $total_due - $total_paid;


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Resort Booking System</title>
    <!-- Add iOS meta tags and icons -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #2196F3;
            --primary-dark: #1976D2;
            --surface-color: #ffffff;
            --background-color: #f8f9fa;
            --text-primary: #212121;
            --text-secondary: #757575;
            --error-color: #f44336;
            --success-color: #4CAF50;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            background: var(--background-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .app-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 20px;
            max-width: 100%;
            width: 100%;
            margin: 0 auto;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-header {
            text-align: center;
            margin: 40px 0;
        }

        .login-header h1 {
            font-size: 24px;
            color: var(--text-primary);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .login-header p {
            color: var(--text-secondary);
            font-size: 16px;
        }

        .login-form {
            background: var(--surface-color);
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            max-width: 400px;
            width: 100%;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: var(--text-secondary);
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
        }

        .input-container {
            position: relative;
        }

        .input-container i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .form-control {
            width: 100%;
            padding: 12px 16px 12px 40px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            outline: none;
            background: var(--surface-color);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(33, 150, 243, 0.1);
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary-color);
        }

        .remember-me label {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .login-btn:active {
            transform: scale(0.98);
            background: var(--primary-dark);
        }

        .public-link {
            text-align: center;
            margin-top: 24px;
        }

        .public-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .error-message {
            background: #ffebee;
            color: var(--error-color);
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Add iOS-style tap effect */
        @media (hover: none) {
            .login-btn:active {
                background: var(--primary-dark);
            }
        }

        /* Prevent zoom on input focus for iOS */
        @supports (-webkit-touch-callout: none) {
            input, select, textarea {
                font-size: 16px !important;
            }
        }

        /* Loading indicator */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .loading::after {
            content: "";
            width: 20px;
            height: 20px;
            border: 2px solid #fff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: loading 0.8s linear infinite;
        }

        @keyframes loading {
            to { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <div class="app-container">
        <div class="login-header">
            <h1>Mumbra Hill Resort Booking System new</h1>
            <p>Welcome back! Please login to continue</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="process_login.php" method="POST" class="login-form" id="loginForm">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-container">
                    <i class="fas fa-user"></i>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           value="<?= isset($_COOKIE['username']) ? htmlspecialchars($_COOKIE['username']) : '' ?>" 
                           required 
                           autocomplete="username"
                           placeholder="Enter your username">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-container">
                    <i class="fas fa-lock"></i>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           value="<?= isset($_COOKIE['password']) ? htmlspecialchars($_COOKIE['password']) : '' ?>" 
                           required 
                           autocomplete="current-password"
                           placeholder="Enter your password">
                </div>
            </div>

            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember" <?= isset($_COOKIE['username']) ? 'checked' : '' ?>>
                <label for="remember">Remember me</label>
            </div>

            <button type="submit" class="login-btn" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i>
                Login
            </button>
        </form>

        <div class="public-link">
            <a href="calender.php">
                <i class="fas fa-calendar-alt"></i>
                View Availability Calendar MHR
            </a>
        </div>
        <?php if ($total_bookings > 0): ?>
            <div class="login-form mt-4" style="background:#e3f2fd; border-left: 4px solid #2196f3;">
                <h3 style="font-size:18px; margin-bottom:10px;">Today's Booking Snapshot</h3>
                <ul style="padding-left: 20px; line-height: 1.6;">
                    <li><strong>Total Bookings:</strong> <?= $total_bookings ?></li>
                    <li><strong>Guests:</strong> <?= $total_adults + $total_kids ?> (<?= $total_adults ?> adults, <?= $total_kids ?> kids)</li>
                    <li><strong>Total Amount:</strong> ₹<?= number_format($total_due, 2) ?></li>
                    <li><strong>Payments Received:</strong> ₹<?= number_format($total_paid, 2) ?></li>
                    <li><strong>Pending Balance:</strong> ₹<?= number_format($pending_amount, 2) ?></li>
                </ul>
            </div>
        <?php endif; ?>


    </div>

    <script>
        // Add loading state to button on form submit
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const button = document.getElementById('loginBtn');
            button.classList.add('loading');
            button.disabled = true;
        });

        // Add iOS-style touch feedback
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('touchstart', function() {
                this.style.backgroundColor = '#f5f5f5';
            });
            input.addEventListener('touchend', function() {
                this.style.backgroundColor = '';
            });
        });
    </script>
</body>
</html>