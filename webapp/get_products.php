<?php
require_once '../db.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM products WHERE status = 1 ORDER BY id ASC");
    $products = $stmt->fetchAll();
    echo json_encode(['status' => 'success', 'data' => $products]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
