<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_GET['id'];
    $guest_name = $_POST['guest_name'];
    $phone = $_POST['phone'];
    $event_date = $_POST['event_date'];
    $checkout = $_POST['checkout'];
    $adults = $_POST['adults'];
    $kids = $_POST['kids'];
    $package = $_POST['package'];
    $total_amount = $_POST['total_amount'];
    $advance_amount = $_POST['advance_amount'];

    // Update events table
    $sql1 = "UPDATE events SET guest_name=?, phone=?, event_date=?, checkout=?, adults=?, kids=?, package=? WHERE id=?";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->bind_param("ssssiisi", $guest_name, $phone, $event_date, $checkout, $adults, $kids, $package, $id);
    
    // Update payments table
    $sql2 = "UPDATE mhr_event_payments SET amount=? WHERE event_id=? AND payment_type=1";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("di", $advance_amount, $id);

    if ($stmt1->execute() && $stmt2->execute()) {
        $_SESSION['message'] = "Booking updated successfully!";
        $_SESSION['message_type'] = 'success';
        header('Location: events_list.php');
        exit;
    }
}

// Fetch event data
$id = $_GET['id'];
$sql = "SELECT e.id, e.guest_name, e.phone, e.event_date, e.checkout, e.adults, e.kids, e.package, e.total_amount, p.amount as advance_amount 
        FROM events e 
        LEFT JOIN mhr_event_payments p ON e.id = p.event_id AND p.payment_type = 1 
        WHERE e.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($event_id, $guest_name, $phone, $event_date, $checkout, $adults, $kids, $package, $total_amount, $advance_amount);
$stmt->fetch();
$stmt->close();
?>
<?php

include 'header.php';
?>
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-warning text-white">
            <h3>Edit Event</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label>Guest Name:</label>
                    <input type="text" name="guest_name" class="form-control" value="<?php echo $guest_name; ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone:</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo $phone; ?>" required>
                </div>
                <div class="form-group">
                    <label>Event Date:</label>
                    <input type="date" name="event_date" class="form-control" value="<?php echo $event_date; ?>" required>
                </div>
                <div class="form-group">
                    <label>Checkout:</label>
                    <input type="date" name="checkout" class="form-control" value="<?php echo $checkout; ?>" required>
                </div>
                <div class="form-group">
                    <label>Adults:</label>
                    <input type="number" name="adults" class="form-control" value="<?php echo $adults; ?>" required>
                </div>
                <div class="form-group">
                    <label>Kids:</label>
                    <input type="number" name="kids" class="form-control" value="<?php echo $kids; ?>" required>
                </div>
                <div class="form-group">
                    <label>Package:</label>
                    <input type="text" name="package" class="form-control" value="<?php echo $package; ?>" required>
                </div>
                <div class="form-group">
                    <label>Total Amount:</label>
                    <input type="number" name="total_amount" class="form-control" value="<?php echo $total_amount; ?>" required>
                </div>
                <div class="form-group">
                    <label>Advance Amount:</label>
                    <input type="number" name="advance_amount" class="form-control" value="<?php echo $advance_amount ?? 0; ?>" required>
                </div>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Changes</button>
                <a href="events_list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </form>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>