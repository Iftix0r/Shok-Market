<?php
require_once '../db.php';
header('Content-Type: application/json');

$userId = $_GET['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'User ID kiritilmagan']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll();
    
    $orderIds = array_column($orders, 'id');
    $items = [];
    
    if (count($orderIds) > 0) {
        $in = str_repeat('?,', count($orderIds) - 1) . '?';
        // Left join ishlatamiz (o'chirilgan mahsulot bo'lsa ham nomi ko'rinishi uchun)
        $stmtItems = $pdo->prepare("SELECT oi.*, p.name, p.image FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id IN ($in)");
        $stmtItems->execute($orderIds);
        $fetchedItems = $stmtItems->fetchAll();
        foreach($fetchedItems as $fi) {
            $items[$fi['order_id']][] = $fi;
        }
    }
    
    foreach ($orders as &$o) {
        $o['items'] = $items[$o['id']] ?? [];
    }
    
    echo json_encode(['status' => 'success', 'data' => $orders]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
