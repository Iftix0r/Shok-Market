<?php
require_once 'config.php';

$update = json_decode(file_get_contents('php://input'), true);

if (isset($update['message'])) {
    $message = $update['message'];
    $chatId = $message['chat']['id'];
    $text = $message['text'] ?? '';

    if ($text === '/start') {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🛒 Shok Market\'ga kirish', 'web_app' => ['url' => $webAppUrl]]
                ]
            ]
        ];

        $welcomeText = "👋 <b>Salom! Shok Marketga xush kelibsiz.</b>\n\nZamonaviy va qulay Web Ilovamiz orqali buyurtma berishingiz mumkin. Quyidagi tugmani bosing va xaridlarni boshlang!";
        sendMessage($chatId, $welcomeText, $keyboard, 'HTML');
    }
}

function sendMessage($chatId, $text, $keyboard = null, $parseMode = null) {
    global $botToken;
    $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
    
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
    ];
    
    if ($keyboard) {
        $data['reply_markup'] = json_encode($keyboard);
    }
    if ($parseMode) {
        $data['parse_mode'] = $parseMode;
    }

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ],
    ];
    $context  = stream_context_create($options);
    @file_get_contents($url, false, $context);
}
?>
