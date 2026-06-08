<?php
require_once '../config.php';
require_once '../db.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM products WHERE status = 1 ORDER BY id ASC");
    $products = $stmt->fetchAll();

    // /uploads/ rasmlari uchun to'liq URL
    $baseUrl = preg_replace('/\/webapp\/index\.php.*/', '', $webAppUrl);
    foreach ($products as &$p) {
        if (!empty($p['image']) && strpos($p['image'], '/uploads/') === 0) {
            $p['image'] = $baseUrl . $p['image'];
        }
    }
    echo json_encode(['status' => 'success', 'data' => $products]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
