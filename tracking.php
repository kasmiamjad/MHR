<?php
// Using your access token to check message status
$access_token = 'EAAGyaZBVJt7kBOz3lNqQpR69we0Yvl7BJSRrRrKwv456uWI4HCASTeZAd2x2AURwrGYjCHHoCw6cXfdywb5ZCCqfNTipQrka1V9BxZCIxRorDFviawpHBNEAkDa37R0DfuEXMxhjNIKJWqmtNqTT3q27dzrVqWf3BIVqzoqe9YhnUiygPbZBZC2ZCzLjhZCm51u3VgZDZD';
$message_id = 'wamid.HBgMOTE5OTY3NTc1NjQzFQIAERgSQTRGRTdDODBDMzNERDIyOTRFAA==';
$url = "https://graph.facebook.com/v17.0/$message_id?access_token=$access_token";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

print_r($result);
// Process and store result