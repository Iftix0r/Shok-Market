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

// Umumiy statistika
$totalRevenue = 0;
$totalOrders = count($orders);
foreach($orders as $o) {
    if($o['status'] == 'completed' || $o['status'] == 'accepted') {
        $totalRevenue += $o['total_price'];
    }
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <title>Buyurtmalar - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .info-card { border-radius: 16px; padding: 20px; color: #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .bg-gradient-warning { background: linear-gradient(45deg, #FFD500, #F7931A); color: #000; }
        .bg-gradient-dark { background: linear-gradient(45deg, #2C3E50, #000000); }
    </style>
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
        
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="info-card bg-gradient-warning d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-1 text-dark fw-bold">UMUMIY DAROMAD</h6>
                        <h2 class="mb-0 text-dark fw-bold"><?= number_format($totalRevenue, 0, '', ' ') ?> so'm</h2>
                    </div>
                    <i class="fas fa-wallet fa-3x text-dark opacity-50"></i>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="info-card bg-gradient-dark d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-1 text-white-50">JAMI BUYURTMALAR</h6>
                        <h2 class="mb-0 fw-bold"><?= $totalOrders ?> ta</h2>
                    </div>
                    <i class="fas fa-shopping-bag fa-3x text-white opacity-25"></i>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold m-0">So'nggi buyurtmalar ro'yxati</h4>
        </div>
        
        <div class="table-responsive bg-white rounded-4 shadow-sm p-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted">
                    <tr>
                        <th>№</th>
                        <th>Mijoz</th>
                        <th>Manzil & Telefon</th>
                        <th>Summa</th>
                        <th>Sana</th>
                        <th>Holati</th>
                        <th>Boshqarish</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($orders) == 0): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted"><i class="fas fa-inbox fa-3x mb-3 d-block"></i>Hozircha buyurtmalar yo'q</td></tr>
                    <?php endif; ?>
                    
                    <?php foreach($orders as $o): ?>
                    <tr>
                        <td class="fw-bold text-secondary">#<?= $o['id'] ?></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($o['first_name']) ?></div>
                            <small class="text-primary">@<?= htmlspecialchars($o['username']) ?></small>
                        </td>
                        <td style="max-width: 250px;">
                            <div class="fw-bold"><i class="fas fa-phone-alt text-success"></i> <?= htmlspecialchars($o['phone'] ?? 'Kiritilmagan') ?></div>
                            <small class="text-muted d-block text-truncate" title="<?= htmlspecialchars($o['address'] ?? '') ?>"><i class="fas fa-map-marker-alt text-danger"></i> <?= htmlspecialchars($o['address'] ?? 'Kiritilmagan') ?></small>
                        </td>
                        <td class="fw-bold text-success fs-5"><?= number_format($o['total_price'], 0, '', ' ') ?> <small>uzs</small></td>
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
