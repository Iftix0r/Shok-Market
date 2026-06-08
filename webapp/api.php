<?php
require_once '../config.php';
require_once '../db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Ma\'lumot topilmadi']);
    exit;
}

$user = $data['user'];
$cart = $data['cart'];
$phone = $data['phone'] ?? '';
$address = $data['address'] ?? '';

try {
    // Bloklangan foydalanuvchini tekshirish
    $stmt = $pdo->prepare("SELECT is_banned FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $existingUser = $stmt->fetch();
    if ($existingUser && isset($existingUser['is_banned']) && $existingUser['is_banned'] == 1) {
        echo json_encode(['status' => 'error', 'message' => 'Siz tizimdan bloklangansiz. Buyurtma bera olmaysiz.']);
        exit;
    }
    // 1. Foydalanuvchini bazaga qo'shish yoki yangilash
    $stmt = $pdo->prepare("INSERT INTO users (id, first_name, username, photo_url) VALUES (?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE first_name=VALUES(first_name), username=VALUES(username), photo_url=VALUES(photo_url)");
    
    $photoUrl = $user['photo_url'] ?? '';
    $stmt->execute([
        $user['id'], 
        $user['first_name'] ?? 'Mehmon', 
        $user['username'] ?? '', 
        $photoUrl
    ]);

    // 2. Umumiy summani hisoblash
    $totalPrice = 0;
    foreach ($cart as $item) {
        $totalPrice += ($item['price'] * $item['quantity']);
    }

    // 3. Buyurtmani bazaga yozish (Telefon va manzil qo'shilgan)
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, phone, address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user['id'], $totalPrice, $phone, $address]);
    $orderId = $pdo->lastInsertId();

    // 4. Buyurtma mahsulotlarini yozish
    $orderText = "<b>🛒 Yangi Buyurtma (Shok Market)! #$orderId</b>\n\n";
    $orderText .= "👤 <b>Mijoz:</b> " . htmlspecialchars($user['first_name'] ?? 'Noma\'lum') . "\n";
    if (!empty($user['username'])) {
        $orderText .= "🔗 <b>Username:</b> @" . htmlspecialchars($user['username']) . "\n";
    }
    $orderText .= "📞 <b>Telefon:</b> " . htmlspecialchars($phone) . "\n";
    $orderText .= "📍 <b>Manzil:</b> " . htmlspecialchars($address) . "\n\n";
    
    $orderText .= "<b>📦 Mahsulotlar:</b>\n";

    $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    
    foreach ($cart as $item) {
        $price = $item['price'];
        $qty = $item['quantity'];
        $sum = $price * $qty;
        
        $stmtItem->execute([$orderId, $item['id'], $qty, $price]);
        
        $orderText .= "- <b>" . htmlspecialchars($item['name']) . "</b> x {$qty} ta = " . number_format($sum, 0, '', ' ') . " so'm\n";
    }

    $orderText .= "\n💰 <b>Jami summa:</b> " . number_format($totalPrice, 0, '', ' ') . " so'm";

    // 5. Buyurtmani Telegram guruhga yuborish
    $groupKeyboard = [
        'inline_keyboard' => [
            [
                ['text' => '⏳ Qabul qilish', 'callback_data' => 'status_accepted_' . $orderId],
                ['text' => '✅ Yetkazildi', 'callback_data' => 'status_completed_' . $orderId]
            ],
            [
                ['text' => '❌ Bekor qilish', 'callback_data' => 'status_cancelled_' . $orderId]
            ]
        ]
    ];
    sendMessage($orderGroupId, $orderText, $groupKeyboard, 'HTML');

    // Mijozga tasdiq xabarini yuborish
    $userMsg = "✅ <b>Buyurtmangiz muvaffaqiyatli qabul qilindi!</b>\n\n";
    $userMsg .= "🔖 <b>Buyurtma raqami:</b> #$orderId\n";
    $userMsg .= "📍 <b>Manzil:</b> " . htmlspecialchars($address) . "\n";
    $userMsg .= "📞 <b>Telefoningiz:</b> " . htmlspecialchars($phone) . "\n";
    $userMsg .= "📊 <b>Holati:</b> ⏳ Qabul qilindi, tayyorlanmoqda\n\n";
    $userMsg .= "<b>📦 Sizning buyurtmalaringiz:</b>\n";
    
    foreach ($cart as $item) {
        $price = $item['price'];
        $qty = $item['quantity'];
        $sum = $price * $qty;
        $userMsg .= "🔸 " . htmlspecialchars($item['name']) . " x {$qty} = " . number_format($sum, 0, '', ' ') . " so'm\n";
    }
    
    $userMsg .= "\n💰 <b>Jami to'lov:</b> " . number_format($totalPrice, 0, '', ' ') . " so'm\n\n";
    $userMsg .= "<i>📞 Tez orada yetkazib beruvchilarimiz ko'rsatilgan raqamga aloqaga chiqishadi. Haridingiz uchun rahmat!</i>";

    $userKeyboard = [
        'inline_keyboard' => [
            [
                ['text' => '👨‍💻 Biz bilan aloqa', 'url' => 'https://t.me/admin'] // Admin username yoziladi
            ]
        ]
    ];

    sendMessage($user['id'], $userMsg, $userKeyboard, 'HTML');

    echo json_encode(['status' => 'success', 'order_id' => $orderId]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => "Baza xatosi: " . $e->getMessage()]);
}

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
    @file_get_contents($url, false, $context);
}
?>
