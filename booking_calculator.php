<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Input data
    $booking_date = $_POST['booking_date'];
    $total_persons = (int) $_POST['total_persons'];
    $discount_input = $_POST['discount'];

    // Check if date is a weekend or weekday
    $day_of_week = date('N', strtotime($booking_date)); // 1 (Monday) to 7 (Sunday)
    $is_weekend = $day_of_week == 6 || $day_of_week == 7;

    // Pricing logic
    $base_price = $is_weekend ? 7000 : 6000;
    $extra_price = $is_weekend ? 800 : 700;

    if ($total_persons > 14) {
        // Flat per-head pricing for 14+ persons
        $total_amount = $total_persons * $extra_price;
    } else {
        // Base pricing for up to 7 persons
        $total_amount = $base_price;
        if ($total_persons > 7) {
            $extra_persons = $total_persons - 7;
            $total_amount += $extra_persons * $extra_price;
        }
    }

    // Apply discount if any
    $discount = 0;
    if (!empty($discount_input)) {
        if (strpos($discount_input, '%') !== false) {
            // Percentage discount
            $discount_percent = (float) str_replace('%', '', $discount_input);
            $discount = ($discount_percent / 100) * $total_amount;
        } else {
            // Fixed amount discount
            $discount = (float) $discount_input;
        }
    }

    $final_amount = max(0, $total_amount - $discount); // Prevent negative amounts

    // Output result
    echo "
    <div class='alert alert-info'>
        <strong>Booking Date:</strong> " . date('d/m/Y', strtotime($booking_date)) . "<br>
        <strong>Total Persons:</strong> $total_persons<br>
        <strong>Base Price:</strong> ₹$base_price<br>
        <strong>Extra Price per Person:</strong> ₹$extra_price<br>
        <strong>Discount Applied:</strong> ₹" . number_format($discount, 2) . "<br>
        <strong>Total Amount:</strong> ₹" . number_format($final_amount, 2) . "
    </div>";
}
?>
