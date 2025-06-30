<?php
session_start();
require_once 'config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: events_list.php');
    exit;
}

try {
    $event_id = $_POST['event_id'];
    $amount = $_POST['amount'];
    $payment_type = $_POST['payment_type'];
    $transaction_id = $_POST['transaction_id'] ?? null;
    $payment_notes = $_POST['payment_notes'] ?? null;
    
    // Start transaction
    $conn->begin_transaction();
    
    // Insert payment record
    $sql = "INSERT INTO mhr_event_payments (event_id, amount, payment_type, transaction_id, payment_notes, created_by) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idsssi", $event_id, $amount, $payment_type, $transaction_id, $payment_notes, $_SESSION['user_id']);
    $stmt->execute();
    
    $payment_id = $conn->insert_id;
    
    // Generate invoice
    require_once 'generate_invoice.php';
    //$invoice_path = generateInvoice($payment_id);
    
    // Get event details for WhatsApp message
   // Get event details for WhatsApp message
   $event_sql = "SELECT guest_name, phone FROM events WHERE id = ?";
   $stmt = $conn->prepare($event_sql);
   $stmt->bind_param("i", $event_id);
   $stmt->execute();
   
   // Use bind_result instead of get_result
   $stmt->bind_result($guest_name, $phone);
   $stmt->fetch();
   $stmt->close();
    //$phone = $event['phone'];
    // Send WhatsApp message with invoice
    //require_once 'whatsapp_api.php';
    $message = "Dear {$guest_name},\n\n";
    $message .= "Thank you for your payment of ₹" . number_format($amount, 2) . ".\n";
    $message .= "Please find your payment receipt attached.\n\n";
    $message .= "Regards,\nMumbra Hill Resort";

    $payment_id = $conn->insert_id;

    // Generate invoice
    require_once 'generate_invoice.php';
    $generator = new InvoiceGenerator($payment_id);
   
    //$invoice_path = $generator->generateInvoice();

    // Send WhatsApp confirmation
    require_once 'send_payment_confirmation.php';
    
    $msg = '';
    try {
        if (strtolower($payment_type) === 'cash') {
            updateCashCollection($conn, $amount, $_SESSION['user_id']);
        }
        // Add this before sending the message

        $whatsapp_response = sendPaymentConfirmation($payment_id);
        logWhatsAppResponse($whatsapp_response, $event_id, $payment_id, 'payment');
        //error_log("WhatsApp message sent successfully: " . print_r($whatsapp_response, true));
        //$msg = "WhatsApp message sent successfully: " . print_r($whatsapp_response, true);
    } catch (Exception $e) {
        error_log("Failed to send WhatsApp message: " . $e->getMessage());
        ///$msg = "Failed to send WhatsApp message: " . $e->getMessage();
    }

    //print_r($msg); 

   
    // Commit transaction
    $conn->commit();
    
    $_SESSION['message'] = "Payment recorded and invoice sent successfully!";
    header("Location: payment.php?id=$event_id");
    
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['errors'] = ["Error processing payment: " . $e->getMessage()];
    print_r($_SESSION['errors']);
   // header("Location: payment_details.php?id=$event_id");
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


?>