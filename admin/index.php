<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
require_once '../db.php';

// Buyurtma holatini o'zgartirish
if (isset($_POST['change_status']) && isset($_POST['order_id'])) {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['new_status'], $_POST['order_id']]);
    header('Location: index.php');
    exit;
}

// Barcha buyurtmalarni olish
$stmt = $pdo->query("SELECT o.*, u.first_name, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <title>Buyurtmalar - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand text-warning fw-bold" href="index.php"><i class="fas fa-bolt"></i> SHOK MARKET</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="index.php"><i class="fas fa-shopping-cart"></i> Buyurtmalar</a></li>
                    <li class="nav-item"><a class="nav-link" href="products.php"><i class="fas fa-box"></i> Mahsulotlar</a></li>
                </ul>
                <a href="logout.php" class="btn btn-outline-light btn-sm"><i class="fas fa-sign-out-alt"></i> Chiqish</a>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <div class="container mt-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold m-0">So'nggi buyurtmalar</h3>
            <span class="badge bg-warning text-dark fs-6"><?= count($orders) ?> ta</span>
        </div>
        
        <div class="table-responsive bg-white rounded-3 shadow-sm p-2">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted">
                    <tr>
                        <th>№</th>
                        <th>Mijoz</th>
                        <th>Summa</th>
                        <th>Sana</th>
                        <th>Holati</th>
                        <th>Boshqarish</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($orders) == 0): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Hozircha buyurtmalar yo'q</td></tr>
                    <?php endif; ?>
                    
                    <?php foreach($orders as $o): ?>
                    <tr>
                        <td class="fw-bold text-secondary">#<?= $o['id'] ?></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($o['first_name']) ?></div>
                            <small class="text-primary">@<?= htmlspecialchars($o['username']) ?></small>
                        </td>
                        <td class="fw-bold text-success"><?= number_format($o['total_price'], 0, '', ' ') ?> so'm</td>
                        <td><?= date('d.m.Y', strtotime($o['created_at'])) ?><br><small class="text-muted"><?= date('H:i', strtotime($o['created_at'])) ?></small></td>
                        <td>
                            <?php
                            $colors = ['new'=>'primary', 'accepted'=>'warning', 'completed'=>'success', 'cancelled'=>'danger'];
                            $labels = ['new'=>'Yangi', 'accepted'=>'Tayyorlanmoqda', 'completed'=>'Yetkazildi', 'cancelled'=>'Bekor qilingan'];
                            $col = $colors[$o['status']] ?? 'secondary';
                            $lbl = $labels[$o['status']] ?? $o['status'];
                            echo "<span class='badge bg-$col bg-opacity-75 text-dark fw-bold border border-$col'>$lbl</span>";
                            ?>
                        </td>
                        <td>
                            <form method="post" class="d-flex gap-2">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <select name="new_status" class="form-select form-select-sm" style="width: auto;">
                                    <option value="new" <?= $o['status']=='new'?'selected':'' ?>>Yangi</option>
                                    <option value="accepted" <?= $o['status']=='accepted'?'selected':'' ?>>Tayyorlanmoqda</option>
                                    <option value="completed" <?= $o['status']=='completed'?'selected':'' ?>>Yetkazildi</option>
                                    <option value="cancelled" <?= $o['status']=='cancelled'?'selected':'' ?>>Bekor qilish</option>
                                </select>
                                <button type="submit" name="change_status" class="btn btn-sm btn-dark">Saqlash</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
