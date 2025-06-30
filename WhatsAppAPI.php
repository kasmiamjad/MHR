<?php
class WhatsAppAPI {
    private $token;
    private $phoneNumberId;
    
    public function __construct() {
        $this->token = WHATSAPP_TOKEN;
        $this->phoneNumberId = WHATSAPP_PHONE_ID;
    }
    
    public function sendTemplateMessage($recipient_number, $template_name, $components) {
        $url = "https://graph.facebook.com/v21.0/{$this->phoneNumberId}/messages";
        
        // Format the phone number
        $recipient_number = $this->formatPhoneNumber($recipient_number);
        
        $payload = [
            "messaging_product" => "whatsapp",
            "to" => $recipient_number,
            "type" => "template",
            "template" => [
                "name" => $template_name,
                "language" => [
                    "code" => "en"
                ],
                "components" => $components
            ]
        ];

        $response = $this->makeRequest($url, $payload);
        return $response;
    }
    
    private function formatPhoneNumber($phone) {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add country code if not present (assuming India)
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }
        
        return $phone;
    }
    
    private function makeRequest($url, $payload) {
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
            ],
        ]);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            throw new Exception("cURL Error #: " . $err);
        }
        
        return json_decode($response, true);
    }
}