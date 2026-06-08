<?php
require_once '../config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Ma\'lumot topilmadi']);
    exit;
}

$user = $data['user'];
$cart = $data['cart'];

$orderText = "<b>🛒 Yangi Buyurtma (Shok Market)!</b>\n\n";
$orderText .= "👤 <b>Mijoz:</b> " . htmlspecialchars($user['first_name'] ?? 'Noma\'lum') . "\n";
if (!empty($user['username'])) {
    $orderText .= "🔗 <b>Username:</b> @" . htmlspecialchars($user['username']) . "\n";
}
$orderText .= "🆔 <b>ID:</b> <code>" . htmlspecialchars($user['id']) . "</code>\n\n";

$totalPrice = 0;
$orderText .= "<b>📦 Mahsulotlar:</b>\n";
foreach ($cart as $item) {
    $price = $item['price'];
    $qty = $item['quantity'];
    $sum = $price * $qty;
    $orderText .= "- <b>" . htmlspecialchars($item['name']) . "</b> x {$qty} ta = " . number_format($sum, 0, '', ' ') . " so'm\n";
    $totalPrice += $sum;
}

$orderText .= "\n💰 <b>Jami summa:</b> " . number_format($totalPrice, 0, '', ' ') . " so'm";

// Buyurtmani guruhga yuborish
sendMessage($orderGroupId, $orderText, null, 'HTML');

// Mijozga tasdiq xabarini yuborish
sendMessage($user['id'], "✅ <b>Buyurtmangiz muvaffaqiyatli qabul qilindi!</b>\n\nTez orada siz bilan bog'lanamiz.", null, 'HTML');

echo json_encode(['status' => 'success']);

function sendMessage($chatId, $text, $keyboard = null, $parseMode = null) {
    global $botToken;
    $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
    
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
    ];
    if ($keyboard) $data['reply_markup'] = json_encode($keyboard);
    if ($parseMode) $data['parse_mode'] = $parseMode;

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ],
    ];
    $context  = stream_context_create($options);
    file_get_contents($url, false, $context);
}
?>
