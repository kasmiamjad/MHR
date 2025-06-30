<?php
require_once 'config.php';
require_once 'whatsapp_config.php';
require_once 'WhatsAppAPI.php';
function logWhatsAppResponse($response, $event_id = null, $payment_id = null, $message_type = 'booking') {
    global $conn;
    
    try {
        $wa_id = $response['contacts'][0]['wa_id'] ?? null;
        $input_number = $response['contacts'][0]['input'] ?? null;
        $message_id = $response['messages'][0]['id'] ?? null;
        $message_status = $response['messages'][0]['message_status'] ?? null;
        $response_data = json_encode($response);

        $sql = "INSERT INTO mhr_whatsapp_logs 
                (event_id, payment_id, message_type, wa_id, message_id, message_status, input_number, response_data) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissssss", 
            $event_id,
            $payment_id,
            $message_type,
            $wa_id,
            $message_id,
            $message_status,
            $input_number,
            $response_data
        );
        
        $stmt->execute();
        $stmt->close();
        
        return true;
    } catch (Exception $e) {
        error_log("Error logging WhatsApp response: " . $e->getMessage());
        return false;
    }
}
function sendPaymentConfirmation($payment_id) {
    try {
        global $conn;
        
        // Get payment and booking details
        $sql = "SELECT 
                    p.id,
                    p.event_id,
                    p.amount,
                    p.payment_type,
                    p.payment_date,
                    p.transaction_id,
                    p.payment_status,
                    p.payment_notes,
                    p.created_at,
                    p.created_by,
                    e.guest_name,
                    e.phone,
                    e.event_date,
                    e.checkout,
                    e.adults,
                    e.kids,
                    e.total_amount,
                    (SELECT SUM(amount) FROM mhr_event_payments 
                        WHERE event_id = e.id AND id <= p.id) as total_paid
                    FROM mhr_event_payments p
                    JOIN events e ON p.event_id = e.id
                    WHERE p.id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        
        $stmt->bind_result(
            $payment_id, $event_id, $amount, $payment_type, $payment_date,
            $transaction_id, $payment_status, $payment_notes, $created_at, $created_by,
            $guest_name, $phone, $event_date, $checkout, $adults, $kids,
            $total_amount, $total_paid
        );
        
        if (!$stmt->fetch()) {
            throw new Exception("Payment not found");
        }
        
        $stmt->close();
        
        // Calculate remaining balance
        $balance_amount = $total_amount - $total_paid;
        
        // Initialize WhatsApp API
        $whatsapp = new WhatsAppAPI();

        // Format guest count
        $guest_count = $adults . " Adults, " . $kids . " Kids";
        
        // Prepare components
        $components = [
            [
                "type" => "body",
                "parameters" => [
                    ["type" => "text", "text" => $guest_name],                    // {{customer_name}}
                    ["type" => "text", "text" => number_format($amount, 2)],      // {{payment_amount}}
                    ["type" => "text", "text" => ucfirst($payment_type)],         // {{payment_type}}
                    ["type" => "text", "text" => $transaction_id ?: "-"],         // {{transaction_id}}
                    ["type" => "text", "text" => date('d M Y', strtotime($event_date))],  // {{checkin_date}}
                    ["type" => "text", "text" => date('d M Y', strtotime($checkout))],    // {{checkout_date}}
                    ["type" => "text", "text" => $guest_count],                   // {{guest_count}}
                    ["type" => "text", "text" => number_format($total_amount, 2)], // {{total_package}}
                    ["type" => "text", "text" => number_format($total_paid, 2)],   // {{amount_paid}}
                    ["type" => "text", "text" => number_format($balance_amount, 2)] // {{balance_amount}}
                ]
            ]
        ];

        // Debug log
        error_log("WhatsApp Request Payload: " . json_encode([
            "messaging_product" => "whatsapp",
            "to" => $phone,
            "type" => "template",
            "template" => [
                "name" => "payment_confirmation",
                "language" => [
                    "code" => "en"
                ],
                "components" => $components
            ]
        ], JSON_PRETTY_PRINT));

       
        // Send WhatsApp message
        $response = $whatsapp->sendTemplateMessage(
            $phone,
            'payment_confirmation',
            $components
        );

        return $response;
        
    } catch (Exception $e) {
        error_log("WhatsApp Error: " . $e->getMessage());
        throw $e;
    }
}

function sendBookingConfirmation_pdf($event_id, $pdf_path) {
    try {
        global $conn;
        // Get booking details
        $sql = "SELECT e.id, e.guest_name, e.phone, e.event_date, e.checkout, e.adults, e.kids, e.package, e.total_amount, (SELECT COALESCE(SUM(amount), 0) FROM mhr_event_payments WHERE event_id = e.id) as total_paid FROM events e WHERE e.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        // Use bind_result for older mysqli versions
        $stmt->bind_result(
            $id, $guest_name, $phone, $event_date, $checkout, $adults, $kids, $package, $total_amount, $total_paid
        );
        if (!$stmt->fetch()) {
            $stmt->close();
            throw new Exception("Booking not found");
        }
        $stmt->close();

        // Validate phone number
        if (empty($phone)) {
            throw new Exception("Phone number is missing for the booking");
        }

        // Ensure phone number is in international format
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (substr($phone, 0, 2) !== '91') {
            $phone = '91' . $phone;
        }

        // Calculate remaining balance
        $balance_amount = $total_amount - $total_paid;

        // Initialize WhatsApp API
        $whatsapp = new WhatsAppAPI();
        $pdf_filename = basename($pdf_path);
        $pdf_url = "https://mumbrahillresort.com/calender/" . $pdf_filename;
        
        // Define components based on template structure
        $components = [
            [
                "type" => "HEADER",
                "parameters" => [
                    [
                        "type" => "document",
                        "document" => [
                            "link" => $pdf_url, // Your PDF URL or path
                            "filename" => "Booking_Confirmation_" . $id . ".pdf"
                        ]
                    ]
                ]
            ],
            [
                "type" => "BODY",
                "parameters" => [
                    ["type" => "text", "text" => $guest_name], // Guest Name
                    ["type" => "text", "text" => date('d F Y', strtotime($event_date)) . ' at 12 PM'], // Check-In
                    ["type" => "text", "text" => date('d F Y', strtotime($checkout)) . ' at 10 AM'], // Check-Out
                    ["type" => "text", "text" => $adults], // Total Adults
                    ["type" => "text", "text" => $kids], // Total Kids
                    ["type" => "text", "text" => $total_amount], // Package
                    ["type" => "text", "text" => number_format($total_paid, 2)], // Amount Paid
                    ["type" => "text", "text" => number_format($balance_amount, 2)] // Balance
                ]
            ]
        ];

        // Debug log
        error_log("Booking WhatsApp Request Payload: " . json_encode([
            "messaging_product" => "whatsapp",
            "pdf" =>  $pdf_url,
            "to" => $phone,
            "type" => "template",
            "template" => [
                "name" => "booking_confirmation",
                "language" => [
                    "code" => "en"
                ],
                "components" => $components
            ]
        ], JSON_PRETTY_PRINT));

        // Send WhatsApp message
        $response = $whatsapp->sendTemplateMessage(
            $phone, 
            'booking_confirmation', 
            $components
        );
        
        // Log the response for debugging
        error_log("WhatsApp API Response: " . json_encode($response));
        
        return $response;
    } catch (Exception $e) {
        error_log("Booking Confirmation WhatsApp Error: " . $e->getMessage());
        throw $e;
    }
}


function sendBookingConfirmation($event_id, $path = null) {
    try {
        global $conn;
        
        // Get booking details
        $sql = "SELECT e.id, e.guest_name, e.phone, e.event_date, e.checkout, 
                       e.adults, e.kids, e.package, e.total_amount, 
                       (SELECT COALESCE(SUM(amount), 0) FROM mhr_event_payments 
                        WHERE event_id = e.id) as total_paid
                FROM events e 
                WHERE e.id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        
        // Use bind_result for older mysqli versions
        $stmt->bind_result(
            $id, $guest_name, $phone, $event_date, $checkout, 
            $adults, $kids, $package, $total_amount, $total_paid
        );
        
        if (!$stmt->fetch()) {
            $stmt->close();
            throw new Exception("Booking not found");
        }
        
        $stmt->close();
        
        // Validate phone number
        if (empty($phone)) {
            throw new Exception("Phone number is missing for the booking");
        }
        
        // Ensure phone number is in international format
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (substr($phone, 0, 2) !== '91') {
            $phone = '91' . $phone;
        }
        
        // Calculate remaining balance
        $balance_amount = $total_amount - $total_paid;
        
        // Initialize WhatsApp API
        $whatsapp = new WhatsAppAPI();

        // Prepare components exactly matching the template
        $components = [
            [
                "type" => "body",
                "parameters" => [
                    ["type" => "text", "text" => $guest_name],                    // Guest Name
                    ["type" => "text", "text" => date('d F Y', strtotime($event_date)) . ' at 12 PM'],  // Check-In
                    ["type" => "text", "text" => date('d F Y', strtotime($checkout)) . ' at 10 AM'],    // Check-Out
                    ["type" => "text", "text" => $adults],                        // Total Adults
                    ["type" => "text", "text" => $kids],                          // Total Kids
                    ["type" => "text", "text" => $total_amount],                       // Package
                    ["type" => "text", "text" => number_format($total_paid, 2)],  // Amount Paid
                    ["type" => "text", "text" => number_format($balance_amount, 2)] // Balance
                ]
            ]
        ];

        // Debug log
        error_log("Booking WhatsApp Request Payload: " . json_encode([
            "messaging_product" => "whatsapp",
            "to" => $phone,
            "type" => "template",
            "template" => [
                "name" => "booking_confirmation",
                "language" => [
                    "code" => "en"
                ],
                "components" => $components
            ]
        ], JSON_PRETTY_PRINT));

        // Send WhatsApp message
        $response = $whatsapp->sendTemplateMessage(
            $phone,
            'booking_confirmation_new',
            $components
        );

        $fixedNumber = '9904074848'; // Replace with your actual number in international format
        $responseAdmin = $whatsapp->sendTemplateMessage(
            $fixedNumber,
            'booking_confirmation_new',
            $components
        );

        return $response;
        
    } catch (Exception $e) {
        error_log("Booking Confirmation WhatsApp Error: " . $e->getMessage());
        throw $e;
    }
}

function sendCancelMessage($phone, $message) {
    try {
        // Initialize WhatsApp API
        $whatsapp = new WhatsAppAPI();

        // Prepare WhatsApp API payload
        $payload = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $phone,
            "type" => "text",
            "text" => [
                "body" => $message
            ]
        ];

        // Send WhatsApp message
        $response = $whatsapp->sendTextMessage($payload);
        return $response;
    } catch (Exception $e) {
        error_log("WhatsApp Error: " . $e->getMessage());
        throw $e;
    }
}

// Usage:
try {
    // After successful payment processing...
    //$whatsapp_response = sendPaymentConfirmation($payment_id);
   // $whatsapp_response = sendBookingConfirmation($payment_id);
    
    // Log the response
   // error_log("WhatsApp Response: " . print_r($whatsapp_response, true));
    
} catch (Exception $e) {
    error_log("Error sending payment confirmation: " . $e->getMessage());
}