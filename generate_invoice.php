<?php
require_once 'config.php';
require('fpdf/fpdf.php');

class InvoiceGenerator {
    private $payment_id;
    private $payment_data;
    private $conn;
    private $pdf;

    public function __construct($payment_id = null) {
        global $conn;
        $this->conn = $conn;
        $this->payment_id = $payment_id;
        $this->loadPaymentData();
        $this->initializePDF();
    }

    private function loadPaymentData() {
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
                    e.package,
                    e.adults,
                    e.kids,
                    e.total_amount,
                    (SELECT SUM(amount) FROM mhr_event_payments 
                        WHERE event_id = e.id AND id <= p.id) as total_paid
                    FROM mhr_event_payments p
                    JOIN events e ON p.event_id = e.id
                    WHERE p.id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $this->payment_id);
        $stmt->execute();

        $stmt->bind_result(
            $payment_id, $event_id, $amount, $payment_type, $payment_date,
            $transaction_id, $payment_status, $payment_notes, $created_at, $created_by,
            $guest_name, $phone, $event_date, $checkout, $package,
            $adults, $kids, $total_amount, $total_paid
        );

        if ($stmt->fetch()) {
            $this->payment_data = array(
                'payment_id' => $payment_id,
                'event_id' => $event_id,
                'amount' => $amount,
                'payment_type' => $payment_type,
                'payment_date' => $payment_date,
                'transaction_id' => $transaction_id,
                'payment_status' => $payment_status,
                'payment_notes' => $payment_notes,
                'guest_name' => $guest_name,
                'phone' => $phone,
                'event_date' => $event_date,
                'checkout' => $checkout,
                'package' => $package,
                'adults' => $adults,
                'kids' => $kids,
                'total_amount' => $total_amount,
                'total_paid' => $total_paid
            );
        }
        $stmt->close();
        return $this->payment_data;
    }

    private function initializePDF() {
        $this->pdf = new FPDF();
        $this->pdf->AddPage();
    }
    
    public function generateInvoice() {
        // Header Section
      
        $this->pdf->SetFont('Arial', 'B', 16);
        $this->pdf->Cell(0, 10, 'Mumbra Hill Resort', 0, 1, 'C');
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Cell(0, 5, 'Premier Family-Oriented Resort', 0, 1, 'C');
        $this->pdf->Cell(0, 5, 'Contact: +91 9904074848', 0, 1, 'C');
        
        // Receipt Title
        $this->pdf->Ln(10);
        $this->pdf->SetFont('Arial', 'B', 14);
        $this->pdf->Cell(0, 10, 'PAYMENT RECEIPT', 0, 1, 'C');
        $this->pdf->SetFont('Arial', '', 10);
        $receipt_no = 'Receipt No: MHR-' . str_pad($this->payment_id, 6, '0', STR_PAD_LEFT);
        $this->pdf->Cell(0, 5, $receipt_no, 0, 1, 'C');
        $this->pdf->Cell(0, 5, 'Date: ' . date('d M Y', strtotime($this->payment_data['payment_date'])), 0, 1, 'C');

        // Guest Details Section
        $this->pdf->Ln(10);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 8, 'Guest Details', 0, 1, 'L', true);
        $this->pdf->Ln(5);
        
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Cell(30, 6, 'Name:', 0);
        $this->pdf->Cell(70, 6, $this->payment_data['guest_name'], 0);
        $this->pdf->Cell(30, 6, 'Phone:', 0);
        $this->pdf->Cell(0, 6, $this->payment_data['phone'], 0, 1);

        // Payment Details
        $this->pdf->Ln(10);
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 8, 'Payment Details', 0, 1, 'L', true);
        $this->pdf->Ln(5);

        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Cell(50, 6, 'Payment Amount:', 0);
        $this->pdf->Cell(0, 6, 'Rs. ' . number_format($this->payment_data['amount'], 2), 0, 1);
        
        $this->pdf->Cell(50, 6, 'Payment Type:', 0);
        $this->pdf->Cell(0, 6, ucfirst($this->payment_data['payment_type']), 0, 1);

        if ($this->payment_data['transaction_id']) {
            $this->pdf->Cell(50, 6, 'Transaction ID:', 0);
            $this->pdf->Cell(0, 6, $this->payment_data['transaction_id'], 0, 1);
        }

        // Booking Details
        $this->pdf->Ln(10);
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 8, 'Booking Details', 0, 1, 'L', true);
        $this->pdf->Ln(5);

        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Cell(50, 6, 'Check In:', 0);
        $this->pdf->Cell(0, 6, date('d M Y', strtotime($this->payment_data['event_date'])), 0, 1);
        
        $this->pdf->Cell(50, 6, 'Check Out:', 0);
        $this->pdf->Cell(0, 6, date('d M Y', strtotime($this->payment_data['checkout'])), 0, 1);
        
        $this->pdf->Cell(50, 6, 'Package:', 0);
        $this->pdf->Cell(0, 6, $this->payment_data['package'], 0, 1);
        
        $this->pdf->Cell(50, 6, 'Guests:', 0);
        $this->pdf->Cell(0, 6, $this->payment_data['adults'] . ' Adults, ' . $this->payment_data['kids'] . ' Kids', 0, 1);

        // Payment Summary
        $this->pdf->Ln(10);
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 8, 'Payment Summary', 0, 1, 'L', true);
        $this->pdf->Ln(5);

        $remaining = $this->payment_data['total_amount'] - $this->payment_data['total_paid'];
        
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Cell(100, 6, 'Total Package Amount:', 0);
        $this->pdf->Cell(0, 6, 'Rs. ' . number_format($this->payment_data['total_amount'], 2), 0, 1, 'R');
        
        $this->pdf->Cell(100, 6, 'Total Amount Paid:', 0);
        $this->pdf->Cell(0, 6, 'Rs. ' . number_format($this->payment_data['total_paid'], 2), 0, 1, 'R');
        
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Cell(100, 6, 'Remaining Balance:', 0);
        $this->pdf->Cell(0, 6, 'Rs. ' . number_format($remaining, 2), 0, 1, 'R');

        // Footer
        $this->pdf->Ln(10);
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->MultiCell(0, 5, 'This is a computer-generated receipt and does not require a signature. For any queries, please contact us at +91 9904074848 or email at booking@mumbrahillresort.com', 0, 'L');

        // Save PDF
        $invoice_dir = 'invoices/';
        if (!file_exists($invoice_dir)) {
            mkdir($invoice_dir, 0777, true);
        }

        $filename = $invoice_dir . 'RECEIPT-' . str_pad($this->payment_id, 6, '0', STR_PAD_LEFT) . '.pdf';
        $this->pdf->Output('F', $filename);
        return $filename;
    }
}

// Usage
$payment_id = $_GET['payment_id'] ?? null;
if ($payment_id) {
    $generator = new InvoiceGenerator($payment_id);
    echo '<pre>';
    print_r($this->payment_data);
echo '</pre>';
    // $invoice_path = $generator->generateInvoice();
    // print_r($invoice_path);
    // If it's a direct download request
    if (isset($_GET['download'])) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($invoice_path) . '"');
        readfile($invoice_path);
        exit;
    }
    
    // If it's an AJAX request
    // if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    //     echo json_encode(['success' => true, 'path' => $invoice_path]);
    //     exit;
    // }
    
    // Regular request - redirect back
    //header('Location: ' . $_SERVER['HTTP_REFERER']);
}
?>