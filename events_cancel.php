<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Location: login.php');
    exit;
}

require_once 'config.php';
require_once 'send_payment_confirmation.php'; // Assuming you have a function to send WhatsApp messages

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'];
    $guest_name = $_POST['guest_name'];
    $checkin = $_POST['checkin'];
    $checkout = $_POST['checkout'];
    $refund_amount = $_POST['refund_amount'];
    print_r($_POST);
    exit;
    try {
        // Update the event status to "Cancelled"
        $sql = "UPDATE events SET status = 'Cancelled' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $event_id);
        $stmt->execute();

        // Send WhatsApp cancellation message
        $message = "Dear $guest_name,\nYour booking has been cancelled for Mumbra Hill Resort Private Villa.\n\nCancelled Booking Details:\nCheck-in Date: $checkin\nCheck-out Date: $checkout\nRefund Amount: ₹$refund_amount\nRefund Status: Cancelled\n\nIf you have any questions about your cancellation or refund, please contact us:\n📞 9904074848\n📞 8591188522\n✉️ booking@mumbrahillresort.com\n\nThank you for considering Mumbra Hill Resort.\nRegards,\nMumbra Hill Resort";

        $whatsapp_response = sendCancelMessage($guest_name, $message);
        error_log("Cancellation WhatsApp sent: " . print_r($whatsapp_response, true));

        // Redirect to the events list page with a success message
        $_SESSION['message'] = "Booking cancelled and WhatsApp message sent successfully.";
        $_SESSION['message_type'] = 'success';
        header('Location: events_list.php');
        exit;
    } catch (Exception $e) {
        // Handle any errors
        $_SESSION['message'] = "Error cancelling the booking: " . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
        header('Location: events_list.php');
        exit;
    }
}
?>
<?php include 'header.php'; ?>
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-danger text-white">
            <h3>Cancel Booking</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label>Event ID:</label>
                    <input type="text" name="event_id" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Guest Name:</label>
                    <input type="text" name="guest_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Check-in Date:</label>
                    <input type="date" name="checkin" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Check-out Date:</label>
                    <input type="date" name="checkout" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Refund Amount:</label>
                    <input type="number" name="refund_amount" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Cancel Booking</button>
                <a href="events_list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </form>
        </div>
    </div>
</div>