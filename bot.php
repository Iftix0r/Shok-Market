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

// Guruhdagi tugmalarni (callback) bosish hodisasi
if (isset($update['callback_query'])) {
    require_once 'db.php';
    
    $query = $update['callback_query'];
    $data = $query['data'];
    $chatId = $query['message']['chat']['id'];
    $messageId = $query['message']['message_id'];
    $callbackQueryId = $query['id'];
    
    if (strpos($data, 'status_') === 0) {
        $parts = explode('_', $data);
        $status = $parts[1]; // accepted, completed, cancelled
        $orderId = $parts[2];
        
        try {
            // DB dagi holatni o'zgartirish
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $orderId]);
            
            // Mijozning ID sini olish (unga xabar yuborish uchun)
            $stmt = $pdo->prepare("SELECT user_id FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            
            // Holatlarga mos matnlar
            $statusTexts = [
                'accepted' => '⏳ QABUL QILINDI',
                'completed' => '✅ YETKAZILDI',
                'cancelled' => '❌ BEKOR QILINDI'
            ];
            $statusText = $statusTexts[$status] ?? 'Noma\'lum';
            $adminName = htmlspecialchars($query['from']['first_name']);
            
            // Guruhdagi xabarni o'zgartirish (Tugmalarni olib tashlab, kim tasdiqlaganini yozish)
            $newText = $query['message']['text'] . "\n\n➖➖➖➖➖➖➖➖\nℹ️ <b>Holat:</b> " . $statusText . "\n👨‍💼 <b>Admin:</b> " . $adminName;
            editMessageText($chatId, $messageId, $newText, null, 'HTML');
            
            // Mijozga yangi holat haqida xabar yuborish
            if ($order && $order['user_id']) {
                $userMsg = "🔔 <b>Buyurtma holati o'zgardi!</b>\n\n🔖 Buyurtma raqami: <b>#$orderId</b>\n📊 Yangi holat: <b>$statusText</b>";
                sendMessage($order['user_id'], $userMsg, null, 'HTML');
            }
            
            // Ekranda popup chiqishi uchun
            answerCallbackQuery($callbackQueryId, "Holat o'zgartirildi: " . $statusText);
            
        } catch(PDOException $e) {
            answerCallbackQuery($callbackQueryId, "Xatolik: Baza bilan bog'lanib bo'lmadi");
        }
    }
}

function sendMessage($chatId, $text, $keyboard = null, $parseMode = null) {
    global $botToken;
    $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
    $data = ['chat_id' => $chatId, 'text' => $text];
    if ($keyboard) $data['reply_markup'] = json_encode($keyboard);
    if ($parseMode) $data['parse_mode'] = $parseMode;
    $options = ['http' => ['header' => "Content-type: application/x-www-form-urlencoded\r\n", 'method' => 'POST', 'content' => http_build_query($data)]];
    @file_get_contents($url, false, stream_context_create($options));
}

function editMessageText($chatId, $messageId, $text, $keyboard = null, $parseMode = null) {
    global $botToken;
    $url = "https://api.telegram.org/bot" . $botToken . "/editMessageText";
    $data = ['chat_id' => $chatId, 'message_id' => $messageId, 'text' => $text];
    if ($keyboard) $data['reply_markup'] = json_encode($keyboard);
    if ($parseMode) $data['parse_mode'] = $parseMode;
    $options = ['http' => ['header' => "Content-type: application/x-www-form-urlencoded\r\n", 'method' => 'POST', 'content' => http_build_query($data)]];
    @file_get_contents($url, false, stream_context_create($options));
}

function answerCallbackQuery($callbackQueryId, $text) {
    global $botToken;
    $url = "https://api.telegram.org/bot" . $botToken . "/answerCallbackQuery";
    $data = ['callback_query_id' => $callbackQueryId, 'text' => $text, 'show_alert' => true];
    $options = ['http' => ['header' => "Content-type: application/x-www-form-urlencoded\r\n", 'method' => 'POST', 'content' => http_build_query($data)]];
    @file_get_contents($url, false, stream_context_create($options));
}
?>
