<?php
// Include FPDF library
require('fpdf/fpdf.php');
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Location: login.php');
    // echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    // exit;
}
require_once 'config.php';
// if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
//     http_response_code(401);
//     echo json_encode(['success' => false, 'message' => 'Unauthorized']);
//     exit;
// }
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$id = $_GET['id'];
$sql = "SELECT * FROM events WHERE id = $id";
$result = $conn->query($sql);
$event = $result->fetch_assoc();
$name = $event['name'];
$total_amount = $event['package'];
$advance_amount = $event['advance_amount'];
$start_date = new DateTime($event['event_date']);
$end_date = new DateTime($event['checkout']); // Do not modify checkout date
$bal_amount = $total_amount - $advance_amount;
$adults = $event['adults'];

$phone = $event['phone'];

$kids = $event['kids'];

$package = $total_amount;
    $days_interval = $start_date->diff($end_date);
    $days = $days_interval->days; // Get the total number of days
    // Handle plural/singular "day" text
    $days_text = $days === 1 ? "1 day" : "$days days";
    $total_days = $end_date->diff($start_date)->days;
    $daily_amount = $total_amount / $total_days;
    $daily_advance = $advance_amount / $total_days;
// Assuming $checkin and $checkout are provided in 'Y-m-d' format


$checkin_formatted = $start_date->format('j F l') . " at 12 PM check-in";
            $checkout_formatted = $end_date->format('j F l') . " at 10 AM check-out";
 
            $message = "Hello $name,\n\nYour booking at Mumbra Hill Resort has been confirmed for the dates below:\n" .
            "$checkin_formatted - $checkout_formatted\n\n" .
            "Early check-in or late check-out will be charged a half day rate.\n\n" .
            "$days_text charges without food will be Rs.$total_amount\n" .
            "Payment Rs.$advance_amount - Advance.\n" .
            "Balance Rs.$bal_amount - to be paid one day before arrival.\n" .
            "Number of persons to stay confirmed is $adults adults and $kids kids.\n" .
            "After that Rs.700 per head extra, per night.\n\n" .
            "Gpay No: 9904074848\n\n" .
            "Please make a note of these simple rules and regulations for your own safety and the smooth functioning of the property:\n\n" .
            "- Alcohol Strictly not allowed.\n" .
            "- Unmarried couples are not allowed.\n" .
            "- Mixed groups are not allowed.\n" .
            "- Refundable deposit of 3000/- at the time of Check-in is compulsory.\n" .
            "- Please note that fireworks are strictly prohibited on the property.\n" .
            "- There will be a fine of 1000 for plucking flowers and fruits.\n" .
            "- Smoking and Hukka is not allowed inside the room for security reasons.\n" .
            "- In keeping with Government regulations, we request all the guests to carry their identity card (Driving licence, passport, Aadhaar card, or voter's ID card) and to present it while checking in.\n" .
            "- Turn down the volume after 11 pm as per government rules and regulations.\n" .
            "- Smoking is not allowed inside the room for security reasons.\n" .
            "- Chewing tobacco, pan masala etc., inside the pool and rooms premises is strictly prohibited.\n" .
            "- Spitting, spouting of water, blowing nose in the pool are prohibited.\n" .
            "- Pool max capacity is 8-10 persons.\n" .
            "- Pool will be shut for usage at 11 pm, please co-operate with the caretaker.\n" .
            "- Cooking gas charges are 600 extra per day.\n" .
            "- There is a charge of 500/- per night for kitchen cleaning.\n" .
            "- Credit/Debit cards not accepted at the property.\n" .
            "- Any damages to the villa or the pool shall be recovered from the guests before check-out.\n" .
            "- Pets are not allowed.\n" .
            "- Does not allow private parties or events.\n" .
            "- Shower before entering the pool.\n" .
            "- Strictly no diving in the pool.\n" .
            "- Take care of your belongings at all times; management will not be responsible for any loss or damages to personal belongings and valuables.\n" .
            "- Kids should be in constant supervision while using the pool.\n" .
            "- No eatables and drinks allowed in the pool, a fine will be levied in case the guest is found not adhering to the policy.\n" .
            "- Do not keep any leftover food items outside the villa premise as it will attract dogs and other elements.\n" .
            "- Foreign nationals are required to present their valid passport and visa.\n" .
            "- These rules and regulations are subject to change any time without notice.\n\n" .
            "Have as much fun as you can and give us your genuine feedback upon checkout.\n\n" .
            "Cancellation Policy:\n" .
            "In case of cancellation, 25% of the total amount will be deducted. Refunds will be issued only if the same date is rebooked by another guest.\n\n" .
            "Thank you for considering Mumbra Hill Resort, we wish you a memorable holiday experience.";

            


            // WhatsApp URL

           // $message = "Thank you for booking with Mumbra Hill Resort. Your booking details are attached.";

            $whatsappUrl = "https://api.whatsapp.com/send?phone=+91$phone&text=" . urlencode($message);

?>
<!-- index.php -->
<?php include 'header.php'; ?>


<div class="container mt-5">
    <div class="card">
        <div class="card-body text-center">
            
                <!-- Success Message -->
                <h3 class="card-title text-success">Booking Successfully Recorded!</h3>
                <p class="card-text">Your booking details have been saved. You can now share the details via WhatsApp or download the confirmation as a PDF.</p>

                <!-- WhatsApp Button -->
                <a href="<?= $whatsappUrl ?>" target="_blank" class="btn btn-success btn-lg mt-3">
                    <i class="fab fa-whatsapp"></i> Send Details on WhatsApp
                </a>

                <!-- PDF Download Link -->
                
        </div>
    </div>
</div>


<?php include 'footer.php'; ?>
