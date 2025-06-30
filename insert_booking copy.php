<?php
// Include FPDF library
//require('fpdf/fpdf.php');
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Location: login.php');
}

//require_once 'config.php';
require_once 'generate_invoice.php';
require_once 'send_payment_confirmation.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Collect data from the form
$name = $_POST['name'];
$phone = $_POST['phone'];
$checkin = $_POST['checkin'];
$checkout = $_POST['checkout'];
$adults = $_POST['adults'];
$kids = $_POST['kids'];
$package = $_POST['package'];
$total_amount = $_POST['total_amount'];
$advance_amount = $_POST['advance_amount'];
$bal_amount = $total_amount - $advance_amount;
$username = $_SESSION['username'];

// Assuming $checkin and $checkout are provided in 'Y-m-d' format
$start_date = new DateTime($checkin);
$end_date = new DateTime($checkout);

$conflict_found = false;
$whatsappUrl = '';
$success = false;
$error_message = '';

// First check for conflicts
// Replace the conflict check section with this:
while ($start_date < $end_date) {
    $event_date = $start_date->format('Y-m-d');
    
    // Check for duplicate event_date
    $check_query = "SELECT COUNT(*) as count FROM events WHERE event_date = ? AND deleted_at IS NULL";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "s", $event_date);
    mysqli_stmt_execute($stmt);
    
    // Use bind_result instead of get_result
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    
    if ($count > 0) {
        $conflict_found = true;
        break;
    }
    $start_date->modify('+1 day');
}

if (!$conflict_found) {
    // Reset start_date for actual booking
    $start_date = new DateTime($checkin);
    $days_interval = $start_date->diff($end_date);
    $days = $days_interval->days;
    $days_text = $days === 1 ? "1 day" : "$days days";
    
    // Prepare booking message
    $checkin_formatted = $start_date->format('j F l') . " at 12 PM check-in";
    $checkout_formatted = $end_date->format('j F l') . " at 10 AM check-out";
    
    $message = "Hello,\n\nYour booking at Mumbra Hill Resort has been confirmed for the dates below:\n" .
        "$checkin_formatted - $checkout_formatted\n\n" .
        "Early check-in or late check-out will be charged a half day rate.\n\n" .
        "$days_text charges without food will be Rs.$total_amount\n" .
        "Payment Rs.$advance_amount - Advance.\n" .
        "Balance Rs.$bal_amount - to be paid one day before arrival.\n" .
        "Number of persons to stay confirmed is $adults adults and $kids kids.\n" .
        "After that Rs.700 per head extra, per night.\n\n" .
        "Gpay No: 9904074848\n" .
        "Contact No: 8591188522\n\n" .
        // ... [Rest of your message content remains the same]
        "Thank you for considering Mumbra Hill Resort, we wish you a memorable holiday experience.";

    try {
        mysqli_begin_transaction($conn);

        while ($start_date < $end_date) {
            $event_date = $start_date->format('Y-m-d');
            $checkout_date = $end_date->format('Y-m-d');

            // Insert event
            $event_sql = "INSERT INTO events (guest_name, name, phone, event_date, checkout, adults, kids, package, total_amount, advance_amount, user_id) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = mysqli_prepare($conn, $event_sql);
            mysqli_stmt_bind_param($stmt, "ssssssssdds", 
                $name, $name, $phone, $event_date, $checkout_date,
                $adults, $kids, $package, $total_amount, $advance_amount, $username
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error inserting event: " . mysqli_stmt_error($stmt));
            }

            $event_id = mysqli_insert_id($conn);

            // If it's the first iteration and there's an advance payment, record it
            if ($advance_amount > 0 && $event_date === $checkin) {
                $payment_sql = "INSERT INTO mhr_event_payments 
                               (event_id, amount, payment_type, payment_notes, created_by)
                               VALUES (?, ?, 'advance', 'Initial advance payment', ?)";
                
                $stmt = mysqli_prepare($conn, $payment_sql);
                mysqli_stmt_bind_param($stmt, "ids", 
                    $event_id, $advance_amount, $username
                );
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error recording payment: " . mysqli_stmt_error($stmt));
                }

                $payment_id = mysqli_insert_id($conn);

                // Generate and send payment confirmation
                if (isset($payment_id)) {
                    try {
                        $whatsapp_response = sendPaymentConfirmation($payment_id);
                        error_log("Payment WhatsApp sent: " . print_r($whatsapp_response, true));
                    } catch (Exception $e) {
                        error_log("Payment WhatsApp error: " . $e->getMessage());
                        // Don't throw, continue with booking
                    }
                }
            }

            $start_date->modify('+1 day');
        }

        // Generate booking PDF
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, "Mumbra Hill Resort Booking Confirmation", 0, 1, 'C');
        $pdf->Ln(10);
        $pdf->SetFont('Arial', '', 12);
        $pdf->MultiCell(0, 10, $message);

        // Save PDF file
        $file_name = "booking_confirmation_$phone.pdf";
        $pdf->Output('F', $file_name);
        // Send WhatsApp confirmation
        require_once 'send_payment_confirmation.php';
        // Create WhatsApp URL for booking details
        //$whatsappUrl = "https://api.whatsapp.com/send?phone=+91$phone&text=" . urlencode($message);
        $whatsapp_response = sendBookingConfirmation($event_id);
        logWhatsAppResponse($whatsapp_response, $event_id, null, 'booking');

        mysqli_commit($conn);
        $success = true;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Booking Error: " . $e->getMessage());
        $error_message = $e->getMessage();
        $success = false;
    }
}

mysqli_close($conn);
?>

<?php include 'header.php'; ?>

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-body text-center">
            <?php if ($conflict_found): ?>
                <div class="alert alert-danger">
                    <h3 class="card-title">Date Conflict Detected!</h3>
                    <p class="card-text">The villa is already booked on <strong><?= $start_date->format('j F l'); ?></strong>.</p>
                    <a href="booking.php" class="btn btn-warning mt-3">
                        <i class="fas fa-calendar-alt"></i> Choose Different Dates
                    </a>
                </div>
            <?php elseif (!$success): ?>
                <div class="alert alert-danger">
                    <h3 class="card-title">Booking Failed</h3>
                    <p class="card-text"><?= htmlspecialchars($error_message) ?></p>
                    <a href="booking.php" class="btn btn-warning mt-3">
                        <i class="fas fa-redo"></i> Try Again
                    </a>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <h3 class="card-title">Booking Successful!</h3>
                    <p class="card-text">Your villa has been booked successfully.</p>
                    
                    <div class="booking-details mt-4">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Check-in</h5>
                                <p><?= $checkin_formatted ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5>Check-out</h5>
                                <p><?= $checkout_formatted ?></p>
                            </div>
                        </div>
                        
                        <div class="payment-details mt-4">
                            <h5>Payment Details</h5>
                            <p>Total Amount: ₹<?= number_format($total_amount, 2) ?></p>
                            <p>Advance Paid: ₹<?= number_format($advance_amount, 2) ?></p>
                            <p>Balance Due: ₹<?= number_format($bal_amount, 2) ?></p>
                        </div>
                    </div>

                    <div class="action-buttons mt-4">
                        <a href="<?= $whatsappUrl ?>" target="_blank" class="btn btn-success btn-lg m-2">
                            <i class="fab fa-whatsapp"></i> Send Details on WhatsApp
                        </a>

                        <a href="<?= $file_name ?>" download class="btn btn-primary btn-lg m-2">
                            <i class="fas fa-file-download"></i> Download Confirmation
                        </a>

                        <a href="events_list.php" class="btn btn-info btn-lg m-2">
                            <i class="fas fa-list"></i> View All Bookings
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>