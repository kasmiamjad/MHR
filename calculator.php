<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Input data
    $booking_date = $_POST['booking_date'];
    $total_adults = (int) $_POST['total_adults'];
    $total_kids = (int) $_POST['total_kids'];
    $discount_input = $_POST['discount'];

    // Calculate total persons based on adults only (kids are handled separately)
    $total_persons = $total_adults;

    // Check if date is a weekend or weekday
    $day_of_week = date('N', strtotime($booking_date)); // 1 (Monday) to 7 (Sunday)
    $is_weekend = $day_of_week == 6 || $day_of_week == 7;

    // Pricing logic
    $base_price = $is_weekend ? 8000 : 7000;
    $extra_price_adult = $is_weekend ? 1000 : 900;
    $extra_price_kid = $extra_price_adult / 2; // Kids' price is half

    if ($total_persons > 14) {
        // Flat per-head pricing for 14+ adults
        $total_amount = $total_adults * $extra_price_adult;
    } else {
        // Base pricing for up to 7 adults
        $total_amount = $base_price;
        if ($total_persons > 7) {
            $extra_adults = $total_persons - 7;
            $total_amount += $extra_adults * $extra_price_adult;
        }
    }

    // Add kids' extra price separately
    $total_amount += $total_kids * $extra_price_kid;

    // Optionally, apply discount if provided (you can modify the discount logic here)
    if ($discount_input) {
        $total_amount -= ($total_amount * ($discount_input / 100));
    }

    // Final amount
    $final_amount = max(0, $total_amount); // Prevent negative amounts
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Booking Calculator - Resort</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
            --border-radius: 16px;
            --input-bg: #f8f9fa;
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
            padding: 20px;
        }

        .app-container {
            max-width: 600px;
            margin: 0 auto;
            width: 100%;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .calculator-card {
            background: var(--surface-color);
            border-radius: var(--border-radius);
            padding: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .card-header {
            margin-bottom: 24px;
        }

        .card-header h2 {
            color: var(--text-primary);
            font-size: 24px;
            font-weight: 600;
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
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 16px;
            background: var(--input-bg);
            transition: all 0.3s ease;
            outline: none;
            -webkit-appearance: none;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            background: var(--surface-color);
            box-shadow: 0 0 0 4px rgba(33, 150, 243, 0.1);
        }

        .btn {
            width: 100%;
            padding: 16px;
            border-radius: 12px;
            border: none;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .result-card {
            background: var(--surface-color);
            border-radius: var(--border-radius);
            padding: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .result-header {
            margin-bottom: 20px;
        }

        .result-header h4 {
            color: var(--text-primary);
            font-size: 20px;
            font-weight: 600;
        }

        .result-content {
            background: var(--input-bg);
            border-radius: 12px;
            padding: 20px;
        }

        .result-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .result-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .result-label {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .result-value {
            color: var(--text-primary);
            font-weight: 500;
        }

        .total-row {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 2px solid rgba(0,0,0,0.1);
        }

        .total-row .result-label,
        .total-row .result-value {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            margin-top: 20px;
        }

        /* Flatpickr customization */
        .flatpickr-calendar {
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border: none;
        }

        .flatpickr-day.selected {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        @media (max-width: 600px) {
            body {
                padding: 16px;
            }

            .calculator-card,
            .result-card {
                padding: 20px;
            }

            .form-control {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="calculator-card">
            <div class="card-header">
                <h2>Booking Calculator</h2>
            </div>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="bookingDate">Booking Date</label>
                    <div class="input-container">
                        <i class="fas fa-calendar"></i>
                        <input type="text" class="form-control" id="bookingDate" name="booking_date" placeholder="Select date" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="totalAdults">Number of Adults</label>
                    <div class="input-container">
                        <i class="fas fa-user"></i>
                        <input type="number" class="form-control" id="totalAdults" name="total_adults" min="0" placeholder="Enter number of adults" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="totalKids">Number of Kids</label>
                    <div class="input-container">
                        <i class="fas fa-child"></i>
                        <input type="number" class="form-control" id="totalKids" name="total_kids" min="0" placeholder="Enter number of kids" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="discount">Discount</label>
                    <div class="input-container">
                        <i class="fas fa-tag"></i>
                        <input type="text" class="form-control" id="discount" name="discount" placeholder="Enter discount (₹ or %)">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-calculator"></i>
                    Calculate Amount
                </button>
            </form>
        </div>

        <?php if ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
        <div class="result-card">
            <div class="result-header">
                <h4>Booking Details</h4>
            </div>
            <div class="result-content">
                <div class="result-row">
                    <span class="result-label">Booking Date</span>
                    <span class="result-value"><?= date('d/m/Y', strtotime($booking_date)); ?></span>
                </div>
                <div class="result-row">
                    <span class="result-label">Adults</span>
                    <span class="result-value"><?= $total_adults; ?></span>
                </div>
                <div class="result-row">
                    <span class="result-label">Kids</span>
                    <span class="result-value"><?= $total_kids; ?></span>
                </div>
                <div class="result-row">
                    <span class="result-label">Base Price</span>
                    <span class="result-value">₹<?= number_format($base_price, 2); ?></span>
                </div>
                <div class="result-row">
                    <span class="result-label">Extra Price (Adult)</span>
                    <span class="result-value">₹<?= number_format($extra_price_adult, 2); ?></span>
                </div>
                <div class="result-row">
                    <span class="result-label">Extra Price (Kid)</span>
                    <span class="result-value">₹<?= number_format($extra_price_kid, 2); ?></span>
                </div>
                <div class="result-row">
                    <span class="result-label">Discount Applied</span>
                    <span class="result-value">₹<?= number_format($total_amount - $final_amount, 2); ?></span>
                </div>
                <div class="result-row total-row">
                    <span class="result-label">Total Amount</span>
                    <span class="result-value">₹<?= number_format($final_amount, 2); ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <a href="calender.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Availability Calendar
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr("#bookingDate", {
            dateFormat: "Y-m-d",
            minDate: "today",
            altInput: true,
            altFormat: "F j, Y",
            disableMobile: "true",
            theme: "light"
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