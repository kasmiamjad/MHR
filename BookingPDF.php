<?php
class BookingPDF {
    private $pdf;
    
    public function __construct() {
        $this->pdf = new FPDF();
        $this->pdf->AddPage();
        $this->pdf->SetMargins(15, 15, 15);
        $this->pdf->AddFont('DejaVu', '', 'DejaVuSansCondensed.ttf', true);  // Add Unicode font support
    }
    
    // Helper function to format currency
    private function formatCurrency($amount) {
        return 'Rs. ' . number_format($amount, 0, '.', ',') . '/-';
    }
    
    private function addHeader() {
        if (file_exists('images/logo.png')) {
            $this->pdf->Image('images/logo.png', 85, 10, 40);
            $this->pdf->Ln(45);
        }
        
        $this->pdf->SetFont('Arial', 'B', 20);
        $this->pdf->Cell(0, 10, 'MUMBRA HILL RESORT', 0, 1, 'C');
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Cell(0, 5, 'Contact: 9904074848', 0, 1, 'C');
        $this->pdf->Ln(10);
    }
    
    private function addBookingDetails($data) {
        $this->pdf->SetFont('Arial', 'B', 16);
        $this->pdf->Cell(0, 10, 'BOOKING CONFIRMATION', 0, 1, 'C');
        $this->pdf->Ln(5);
        
        $this->pdf->SetFont('Arial', '', 12);
        $this->pdf->Cell(0, 10, 'Dear ' . $data['name'] . ',', 0, 1);
        $this->pdf->MultiCell(0, 6, 'Thank you for choosing Mumbra Hill Resort. We are pleased to confirm your reservation details as follows:', 0);
        $this->pdf->Ln(5);
        
        // Stay Details
        $this->addSection('STAY DETAILS', [
            ['Check-In', date('j F Y', strtotime($data['checkin'])) . ' at 12:00 PM'],
            ['Check-Out', date('j F Y', strtotime($data['checkout'])) . ' at 10:00 AM']
        ]);
        
        // Guest Information
        $this->addSection('GUEST INFORMATION & CHARGES', [
            ['Confirmed Guests', $data['adults'] . ' adults'],
            ['Extra Guest Charge', 'Rs. 700/- per additional person per night']
        ]);
        
        // Payment Details
        $this->addSection('PAYMENT SUMMARY', [
            ['Total Amount', $this->formatCurrency($data['total_amount']) . ' (without food)'],
            ['Advance Paid', $this->formatCurrency($data['advance_amount'])],
            ['Balance Due', $this->formatCurrency($data['bal_amount'])],
            ['Security Deposit', 'Rs. 3,000/- (refundable, to be paid at check-in)'],
            ['Payment Method', 'GPay only (No Credit/Debit cards accepted)']
        ]);
        
        // Additional Charges
        $this->addSection('ADDITIONAL CHARGES', [
            ['Early Check-in/Late Check-out', 'Half day rate'],
            ['Cooking Gas', 'Rs. 600/- per day'],
            ['Kitchen Cleaning', 'Rs. 500/- per night']
        ]);
    }
    
    private function addSection($title, $items) {
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 10, $title, 0, 1);
        $this->pdf->SetFont('Arial', '', 11);
        
        foreach ($items as $item) {
            // Calculate width for first column (40% of available space)
            $firstColumnWidth = ($this->pdf->GetPageWidth() - 30) * 0.4;
            $secondColumnWidth = ($this->pdf->GetPageWidth() - 30) * 0.6;
            
            $this->pdf->Cell($firstColumnWidth, 6, $item[0] . ':', 0, 0);
            $this->pdf->Cell($secondColumnWidth, 6, $item[1], 0, 1);
        }
        $this->pdf->Ln(5);
    }
    
    private function addRules() {
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 10, 'IMPORTANT RULES AND POLICIES', 0, 1);
        $this->pdf->SetFont('Arial', '', 11);
        
        $rules = [
            'Alcohol is strictly prohibited',
            'Unmarried couples are not allowed',
            'Mixed groups are not allowed',
            'Security deposit of Rs. 3,000/- is required at check-in (refundable)',
            'Fine of Rs. 1,000/- for plucking flowers and fruits',
            'Smoking/Hookah not allowed inside rooms',
            'Volume must be lowered after 11 PM',
            'Pool maximum capacity: 8-10 persons',
            'Pool closing time: 11 PM',
            'Kitchen charges extra as per usage'
        ];
        
        foreach ($rules as $rule) {
            $this->pdf->Cell(8, 6, chr(149), 0, 0); // bullet point
            $this->pdf->MultiCell(0, 6, $rule, 0);
        }
        $this->pdf->Ln(5);
    }
    
    private function addCancellationPolicy() {
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 10, 'CANCELLATION POLICY', 0, 1);
        $this->pdf->SetFont('Arial', '', 11);
        $this->pdf->MultiCell(0, 6, 'In case of cancellation, 25% of the total amount will be deducted. Refunds will be issued only if the same date is rebooked by another guest.');
        $this->pdf->Ln(5);
    }
    
    public function generate($booking_data) {
        $this->addHeader();
        $this->addBookingDetails($booking_data);
        $this->addRules();
        $this->addCancellationPolicy();
        
        $this->pdf->SetFont('Arial', 'I', 10);
        $this->pdf->Cell(0, 10, 'This is an official booking confirmation. Please retain for your records.', 0, 1, 'C');
        
        // Create directory if it doesn't exist
        $upload_dir = 'uploads/pdf';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate filename with guest name and date
        $safe_name = preg_replace('/[^A-Za-z0-9\-]/', '_', $booking_data['name']);
        $date_str = date('Y-m-d', strtotime($booking_data['checkin']));
        $file_name = $upload_dir . '/' . $safe_name . '_' . $date_str . '.pdf';
        
        $this->pdf->Output('F', $file_name);
        return $file_name;
    }
}