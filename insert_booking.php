<?php
class BookingPDF {
    private $pdf;
    
    public function __construct() {
        $this->pdf = new FPDF();
        $this->pdf->AddPage();
        $this->pdf->SetMargins(15, 15, 15);
    }
    
    private function addHeader() {
        // Add logo if exists
        if (file_exists('images/logo.png')) {
            $this->pdf->Image('images/logo.png', 85, 10, 40);
            $this->pdf->Ln(45);
        }
        
        $this->pdf->SetFont('Arial', 'B', 20);
        $this->pdf->Cell(0, 10, 'MUMBRA HILL RESORT', 0, 1, 'C');
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Cell(0, 5, 'GPay: 9904074848', 0, 1, 'C');
        $this->pdf->Ln(10);
    }
    
    private function addBookingDetails($data) {
        $this->pdf->SetFont('Arial', 'B', 16);
        $this->pdf->Cell(0, 10, 'BOOKING CONFIRMATION', 0, 1, 'C');
        $this->pdf->Ln(5);
        
        // Guest Info
        $this->pdf->SetFont('Arial', '', 12);
        $this->pdf->Cell(0, 10, 'Dear ' . $data['guest_name'] . ',', 0, 1);
        $this->pdf->MultiCell(0, 6, 'Thank you for choosing Mumbra Hill Resort. We are pleased to confirm your reservation details as follows:', 0);
        $this->pdf->Ln(5);
        
        // Reservation Details Section
        $this->addSection('RESERVATION DETAILS', [
            ['Check-In', $data['checkin'] . ' at 12:00 PM'],
            ['Check-Out', $data['checkout'] . ' at 10:00 AM'],
            ['Duration', '1 day']
        ]);
        
        // Guest Information Section
        $this->addSection('GUEST INFORMATION & CHARGES', [
            ['Confirmed Guests', $data['adults'] . ' adults'],
            ['Extra Guest Charge', '₹700 per additional person per night']
        ]);
        
        // Payment Summary Section
        $this->addSection('PAYMENT SUMMARY', [
            ['Total Amount', '₹' . number_format($data['total_amount']) . ' (without food)'],
            ['Advance Paid', '₹' . number_format($data['advance_amount'])],
            ['Balance Due', '₹' . number_format($data['total_amount'] - $data['advance_amount'])],
            ['Security Deposit', '₹3,000 (refundable, to be paid at check-in)'],
            ['Payment Method', 'GPay only (No Credit/Debit cards accepted)']
        ]);
    }
    
    private function addSection($title, $items) {
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 10, $title, 0, 1);
        $this->pdf->SetFont('Arial', '', 11);
        
        foreach ($items as $item) {
            $this->pdf->Cell(60, 6, $item[0] . ':', 0);
            $this->pdf->Cell(0, 6, $item[1], 0, 1);
        }
        $this->pdf->Ln(5);
    }
    
    private function addRules() {
        $this->addSection('ADDITIONAL CHARGES', [
            ['Early Check-in/Late Check-out', 'Half day rate'],
            ['Cooking Gas', '₹600 per day'],
            ['Kitchen Cleaning', '₹500 per night']
        ]);
        
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 10, 'POOL REGULATIONS', 0, 1);
        $this->pdf->SetFont('Arial', '', 11);
        $rules = [
            'Maximum Capacity: 8-10 persons',
            'Operating Hours: Until 11:00 PM',
            'Mandatory shower before entering',
            'No diving allowed',
            'No eatables/drinks in pool area',
            'Children must be supervised at all times'
        ];
        foreach ($rules as $rule) {
            $this->pdf->Cell(10, 6, chr(149), 0); // Bullet point
            $this->pdf->Cell(0, 6, $rule, 0, 1);
        }
        $this->pdf->Ln(5);
        
        // Add other rules sections...
        $this->addPropertyRules();
        $this->addCancellationPolicy();
    }

    
    private function addPropertyRules() {
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 10, 'PROPERTY RULES AND REGULATIONS', 0, 1);
        $this->pdf->SetFont('Arial', '', 11);
        
        $rules = [
            'Alcohol is strictly prohibited',
            'Unmarried couples are not allowed',
            'Mixed groups are not allowed',
            'Fireworks are strictly prohibited',
            'Fine of ₹1,000 for plucking flowers/fruits',
            'Smoking/Hookah not allowed inside rooms',
            'Volume must be lowered after 11:00 PM',
            'No tobacco/pan masala in pool/room premises',
            'Private parties/events not allowed',
            'Pets are not allowed'
        ];
        
        foreach ($rules as $index => $rule) {
            $this->pdf->Cell(15, 6, ($index + 1) . '.', 0);
            $this->pdf->MultiCell(0, 6, $rule, 0);
        }
        $this->pdf->Ln(5);
    }
    
    private function addCancellationPolicy() {
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 10, 'CANCELLATION POLICY', 0, 1);
        $this->pdf->SetFont('Arial', '', 11);
        $this->pdf->MultiCell(0, 6, '- 25% of total amount will be deducted\n- Refunds issued only if same date is rebooked by another guest');
        $this->pdf->Ln(10);
    }
    
    public function generate($booking_data) {
        $this->addHeader();
        $this->addBookingDetails($booking_data);
        $this->addRules();
        
        // Footer
        $this->pdf->SetFont('Arial', 'I', 10);
        $this->pdf->Cell(0, 10, 'This is an official booking confirmation. Please retain for your records.', 0, 1, 'C');
        
        // Create directory if it doesn't exist
        $upload_dir = 'uploads/pdf';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate filename with guest name and date
        $safe_name = preg_replace('/[^A-Za-z0-9\-]/', '_', $booking_data['guest_name']);
        $date_str = date('Y-m-d', strtotime($booking_data['checkin']));
        $file_name = $upload_dir . '/' . $safe_name . '_' . $date_str . '.pdf';
        
        // Save PDF
        $this->pdf->Output('F', $file_name);
        return $file_name;
    }
}

function updateCashCollection($conn, $amount, $user_id) {
    $date = date('Y-m-d');
    
    // Check if there's already an entry for this user and date
    $check_sql = "SELECT id, amount FROM mhr_cash_collections 
                 WHERE user_id = ? AND collected_date = ? AND status = 'pending'";
    
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "is", $user_id, $date);
    mysqli_stmt_execute($stmt);
    
    // Use bind_result instead of get_result
    mysqli_stmt_bind_result($stmt, $existing_id, $existing_amount);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($existing_id) {
        // Update existing record
        $update_sql = "UPDATE mhr_cash_collections 
                      SET amount = amount + ? 
                      WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "di", $amount, $existing_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        // Insert new record
        $insert_sql = "INSERT INTO mhr_cash_collections 
                      (user_id, amount, collected_date) 
                      VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "ids", $user_id, $amount, $date);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Location: login.php');
}

require_once 'config.php';
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
$payment_mode = $_POST['payment_mode'];

// Initialize variables
$conflict_found = false;
$conflicting_dates = [];
$whatsappUrl = '';
$success = false;
$error_message = '';

// Conflict check SQL
$conflict_sql = "SELECT 
    id, 
    event_date AS booking_start, 
    checkout AS booking_end, 
    guest_name
FROM events 
WHERE 
    deleted_at IS NULL AND 
    (
        # Case 1: New booking start date falls within existing booking period
        (? BETWEEN event_date AND DATE_SUB(checkout, INTERVAL 1 DAY)) OR 
        
        # Case 2: Existing booking overlaps with new booking
        (event_date < ? AND DATE_SUB(checkout, INTERVAL 1 DAY) >= ?)
    )";

$stmt = mysqli_prepare($conn, $conflict_sql);
mysqli_stmt_bind_param($stmt, "sss", 
    $checkin,   // Check if start of new booking is within existing booking period
    $checkin,   // Check for booking overlap
    $checkin    // Same as above
);
mysqli_stmt_execute($stmt);

// Manual result handling for older mysqli versions
mysqli_stmt_bind_result($stmt, $id, $booking_start, $booking_end, $guest_name);

// Reset conflicting dates array
$conflicting_dates = [];

// Collect conflicting bookings
while (mysqli_stmt_fetch($stmt)) {
    $conflict_found = true;
    $conflicting_dates[] = [
        'start' => date('j F Y', strtotime($booking_start)),
        'end' => date('j F Y', strtotime($booking_end)),
        'guest_name' => $guest_name
    ];
}

mysqli_stmt_close($stmt);

// If no conflicts, proceed with booking
if (!$conflict_found) {
    // Reset start_date for actual booking
    $start_date = new DateTime($checkin);
    $end_date = new DateTime($checkout);
    $days_interval = $start_date->diff($end_date);
    $days = $days_interval->days;
    $days_text = $days === 1 ? "1 day" : "$days days";
    
    // Prepare booking message
    $checkin_formatted = $start_date->format('j F l') . " at 12 PM check-in";
    $checkout_formatted = $end_date->format('j F l') . " at 10 AM check-out";

    try {
        mysqli_begin_transaction($conn);

        // Prepare an array to store all dates to be blocked
        $blocked_dates = [];
        $current_date = clone $start_date;
        while ($current_date < $end_date) {
            $blocked_dates[] = $current_date->format('Y-m-d');
            $current_date->modify('+1 day');
        }

        // Insert a single event entry covering the entire booking period
        $event_sql = "INSERT INTO events (
            guest_name, name, phone, 
            event_date, checkout, 
            adults, kids, 
            package, total_amount, advance_amount, 
            user_id, blocked_dates
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // Serialize blocked dates
        $blocked_dates_string = implode(',', $blocked_dates);
        
        $stmt = mysqli_prepare($conn, $event_sql);
        
        // Update the parameter types to match the columns
        mysqli_stmt_bind_param($stmt, "sssssssssdss", 
            $name,           // guest_name (string)
            $name,           // name (string)
            $phone,          // phone (string)
            $checkin,        // event_date (string)
            $checkout,       // checkout (string)
            $adults,         // adults (string)
            $kids,           // kids (string)
            $package,        // package (string)
            $total_amount,   // total_amount (double)
            $advance_amount, // advance_amount (double)
            $username,       // user_id (string)
            $blocked_dates_string // blocked_dates (string)
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error inserting event: " . mysqli_stmt_error($stmt));
        }

        $event_id = mysqli_insert_id($conn);

        // Record advance payment
        if ($advance_amount > 0) {
            // Prepare the payment data insertion
            $payment_sql = "INSERT INTO mhr_event_payments (
                event_id,
                amount,
                payment_type,
                payment_date,
                payment_notes,
                created_at,
                created_by,
                payment_status
            ) VALUES (
                ?,
                ?,
                ?,
                NOW(),
                'Initial advance payment',
                NOW(),
                ?,
                'pending'
            )";

            // Prepare the statement
            $stmt = mysqli_prepare($conn, $payment_sql);

            if ($stmt === false) {
                // Handle preparation error
                die('Error preparing payment statement: ' . mysqli_error($conn));
            }

            // Bind parameters with proper type specifiers
            // i - integer (event_id)
            // d - double (amount)
            // s - string (payment_type, created_by)
            mysqli_stmt_bind_param($stmt, "idss",
                $event_id,
                $advance_amount,
                $payment_mode,
                $username
            );

            // Execute the statement
            if (!mysqli_stmt_execute($stmt)) {
                // Handle execution error
                die('Error executing payment statement: ' . mysqli_stmt_error($stmt));
            }

            // Close the statement
            mysqli_stmt_close($stmt);

            $payment_id = mysqli_insert_id($conn);

            // Generate and send payment confirmation
            // if (isset($payment_id)) {
            //     try {
            //         //$whatsapp_payment_response = sendPaymentConfirmation($payment_id);
            //         //error_log("Payment WhatsApp sent: " . print_r($whatsapp_payment_response, true));
            //     } catch (Exception $e) {
            //         error_log("Payment WhatsApp error: " . $e->getMessage());
            //     }
            // }
        }

        // Usage in your existing code:
            try {
                // Create booking data array
                $booking_data = [
                    'guest_name' => $name,
                    'checkin' => $checkin,
                    'checkout' => $checkout,
                    'adults' => $adults,
                    'kids' => $kids,
                    'total_amount' => $total_amount,
                    'advance_amount' => $advance_amount,
                    // Add other booking details as needed
                ];
                
                // Generate PDF
                $pdfGenerator = new BookingPDF();
                $pdf_file = $pdfGenerator->generate($booking_data);
                
                // Continue with WhatsApp confirmation and other processes...
                $whatsapp_booking_response = sendBookingConfirmation($event_id, $pdf_file);
                if (strtolower($payment_mode) === 'cash') {
                    //echo $payment_mode;
                    updateCashCollection($conn, $advance_amount, $_SESSION['user_id']);
                }
                logWhatsAppResponse($whatsapp_booking_response, $event_id, null, 'booking');
                
            } catch (Exception $e) {
                error_log("PDF Generation Error: " . $e->getMessage());
                throw $e;
            }

        // // Send WhatsApp booking confirmation
        // $whatsapp_booking_response = sendBookingConfirmation($event_id);
        // logWhatsAppResponse($whatsapp_booking_response, $event_id, null, 'booking');

        mysqli_commit($conn);
        $success = true;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Booking Error: " . $e->getMessage());
        $error_message = $e->getMessage();
        $success = false;
    }
}

//mysqli_close($conn);
// Start output buffering to prevent header issues
ob_start();
?>

<?php include 'header.php'; ?>

<!-- Rest of the HTML remains the same as in the previous file -->

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-body text-center">
            <?php if ($conflict_found): ?>
                <div class="alert alert-danger">
                    <h3 class="card-title">Date Conflict Detected!</h3>
                    <p class="card-text">
                        The villa is already booked during the following period(s):
                        <ul>
                            <?php foreach ($conflicting_dates as $conflict): ?>
                                <li>
                                    <strong><?= $conflict['start'] ?> to <?= $conflict['end'] ?></strong>
                                    <?php if (!empty($conflict['guest_name'])): ?>
                                        (Booked by <?= htmlspecialchars($conflict['guest_name']) ?>)
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        Please choose different dates.
                    </p>
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