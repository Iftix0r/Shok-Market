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

// Barcha buyurtma qilingan mahsulotlarni (rasmlari bilan) olish
$stmt = $pdo->query("SELECT oi.*, p.name, p.image, p.unit FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id");
$allItems = $stmt->fetchAll();
$orderItems = [];
foreach($allItems as $item) {
    $orderItems[$item['order_id']][] = $item;
}

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
    <title>Buyurtmalar - Uzum uslubida</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f5f7; font-family: 'Inter', sans-serif; }
        .order-card { border: none; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); margin-bottom: 20px; background: #fff; overflow: hidden; }
        .order-header { background: #fafafa; border-bottom: 1px solid #eee; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; }
        .order-body { padding: 20px; }
        .product-item { display: flex; align-items: center; padding: 12px 0; border-bottom: 1px dashed #eee; }
        .product-item:last-child { border-bottom: none; padding-bottom: 0; }
        .product-img { width: 60px; height: 60px; object-fit: contain; border-radius: 8px; background: #f9f9f9; padding: 5px; border: 1px solid #eaeaea; margin-right: 15px; }
        .product-info { flex-grow: 1; }
        .stat-card { border-radius: 16px; padding: 24px; color: #fff; border: none; }
        .badge-status { padding: 6px 12px; border-radius: 8px; font-weight: 600; font-size: 13px; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand text-warning fw-bold" href="index.php"><i class="fas fa-bolt"></i> SHOK MARKET ADMIN</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="index.php"><i class="fas fa-shopping-bag"></i> Buyurtmalar</a></li>
                    <li class="nav-item"><a class="nav-link" href="products.php"><i class="fas fa-box"></i> Mahsulotlar</a></li>
                    <li class="nav-item"><a class="nav-link" href="users.php"><i class="fas fa-users"></i> Foydalanuvchilar</a></li>
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
                <div class="stat-card" style="background: linear-gradient(135deg, #FFD500, #F7931A);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 text-dark fw-bold text-uppercase">Tushum (Daromad)</h6>
                            <h2 class="mb-0 text-dark fw-bold"><?= number_format($totalRevenue, 0, '', ' ') ?> <small>so'm</small></h2>
                        </div>
                        <i class="fas fa-chart-line fa-3x text-dark opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #2C3E50, #000000);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 text-white-50 text-uppercase">Jami buyurtmalar</h6>
                            <h2 class="mb-0 fw-bold"><?= $totalOrders ?> ta</h2>
                        </div>
                        <i class="fas fa-shopping-basket fa-3x text-white opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <h4 class="fw-bold m-0">Mijozlar buyurtmalari</h4>
            <div class="position-relative" style="max-width: 300px; width:100%;">
                <i class="fas fa-search position-absolute text-muted" style="top:12px; left:15px;"></i>
                <input type="text" id="searchOrder" class="form-control rounded-pill bg-white border-0 shadow-sm ps-5" placeholder="ID, ism yoki raqam qidirish...">
            </div>
        </div>

        <!-- Filter Tabs -->
        <ul class="nav nav-pills mb-4 gap-2 border-bottom pb-3" id="orderTabs">
            <li class="nav-item">
                <button class="nav-link active rounded-pill px-4 fw-bold shadow-sm border" data-filter="all">Barchasi</button>
            </li>
            <li class="nav-item">
                <button class="nav-link bg-white text-dark rounded-pill px-4 fw-bold shadow-sm border" data-filter="new">Yangi <span class="badge bg-danger ms-1 rounded-pill"></span></button>
            </li>
            <li class="nav-item">
                <button class="nav-link bg-white text-dark rounded-pill px-4 fw-bold shadow-sm border" data-filter="accepted">Jarayonda</button>
            </li>
            <li class="nav-item">
                <button class="nav-link bg-white text-dark rounded-pill px-4 fw-bold shadow-sm border" data-filter="completed">Yetkazildi</button>
            </li>
            <li class="nav-item">
                <button class="nav-link bg-white text-dark rounded-pill px-4 fw-bold shadow-sm border" data-filter="cancelled">Bekor qilingan</button>
            </li>
        </ul>

        <?php if(count($orders) == 0): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-box-open fa-4x mb-3 text-light"></i>
                <h5>Hozircha buyurtmalar yo'q</h5>
            </div>
        <?php endif; ?>
        
        <?php foreach($orders as $o): ?>
        <?php
            $colors = ['new'=>'primary', 'accepted'=>'warning', 'completed'=>'success', 'cancelled'=>'danger'];
            $labels = ['new'=>'Yangi', 'accepted'=>'Tayyorlanmoqda', 'completed'=>'Yetkazildi', 'cancelled'=>'Bekor qilingan'];
            $col = $colors[$o['status']] ?? 'secondary';
            $lbl = $labels[$o['status']] ?? $o['status'];
            $items = $orderItems[$o['id']] ?? [];
        ?>
        <div class="order-card" data-status="<?= $o['status'] ?>">
            <div class="order-header">
                <div>
                    <span class="fs-5 fw-bold me-2">Buyurtma #<?= $o['id'] ?></span>
                    <span class="badge-status bg-<?= $col ?> bg-opacity-10 text-<?= $col ?> border border-<?= $col ?>"><?= $lbl ?></span>
                </div>
                <div class="text-end text-muted small">
                    <i class="far fa-clock"></i> <?= date('d.m.Y, H:i', strtotime($o['created_at'])) ?>
                </div>
            </div>
            <div class="order-body row">
                
                <!-- Mijoz ma'lumotlari -->
                <div class="col-md-4 mb-3 mb-md-0 border-end">
                    <h6 class="text-muted fw-bold small mb-3">MIJOZ MA'LUMOTLARI</h6>
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-light rounded-circle p-2 me-2"><i class="fas fa-user text-secondary"></i></div>
                        <div>
                            <div class="fw-bold"><?= htmlspecialchars($o['first_name']) ?></div>
                            <a href="https://t.me/<?= htmlspecialchars($o['username']) ?>" class="text-decoration-none small" target="_blank">@<?= htmlspecialchars($o['username']) ?></a>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="mb-1"><i class="fas fa-phone-alt text-success me-2"></i> <a href="tel:<?= htmlspecialchars($o['phone'] ?? '') ?>" class="text-dark fw-bold text-decoration-none"><?= htmlspecialchars($o['phone'] ?? 'Kiritilmagan') ?></a></div>
                        <div class="text-muted small"><i class="fas fa-map-marker-alt text-danger me-2"></i> <?= htmlspecialchars($o['address'] ?? 'Manzil kiritilmagan') ?></div>
                    </div>
                    
                    <hr>
                    <form method="post" class="mt-3">
                        <label class="form-label text-muted fw-bold small mb-1">HOLATNI O'ZGARTIRISH</label>
                        <div class="d-flex gap-2">
                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                            <select name="new_status" class="form-select form-select-sm">
                                <option value="new" <?= $o['status']=='new'?'selected':'' ?>>Yangi</option>
                                <option value="accepted" <?= $o['status']=='accepted'?'selected':'' ?>>Tayyorlanmoqda</option>
                                <option value="completed" <?= $o['status']=='completed'?'selected':'' ?>>Yetkazildi</option>
                                <option value="cancelled" <?= $o['status']=='cancelled'?'selected':'' ?>>Bekor qilish</option>
                            </select>
                            <button type="submit" name="change_status" class="btn btn-sm btn-dark">Saqlash</button>
                        </div>
                    </form>
                </div>

                <!-- Mahsulotlar ro'yxati -->
                <div class="col-md-8">
                    <h6 class="text-muted fw-bold small mb-3">BUYURTMA TARKIBI (<?= count($items) ?> xil mahsulot)</h6>
                    <div class="products-list">
                        <?php foreach($items as $item): ?>
                        <div class="product-item">
                            <img src="<?= htmlspecialchars($item['image'] ?? 'https://via.placeholder.com/60') ?>" class="product-img" alt="Rasm">
                            <div class="product-info">
                                <div class="fw-bold fs-6"><?= htmlspecialchars($item['name'] ?? 'Noma\'lum mahsulot') ?></div>
                                <div class="text-muted small"><?= number_format($item['price'], 0, '', ' ') ?> so'm / <?= htmlspecialchars($item['unit'] ?? 'dona') ?></div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold bg-light px-2 py-1 rounded border"><?= $item['quantity'] ?> ta</div>
                                <div class="fw-bold text-success mt-1"><?= number_format($item['price'] * $item['quantity'], 0, '', ' ') ?> so'm</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center bg-light p-3 rounded">
                        <span class="fw-bold text-muted text-uppercase">Jami to'lov:</span>
                        <span class="fs-4 fw-bold text-success"><?= number_format($o['total_price'], 0, '', ' ') ?> so'm</span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('searchOrder').addEventListener('input', filterOrders);
        
        document.querySelectorAll('#orderTabs .nav-link').forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active classes
                document.querySelectorAll('#orderTabs .nav-link').forEach(b => {
                    b.classList.remove('active');
                    b.classList.add('bg-white', 'text-dark');
                });
                // Add active to clicked
                this.classList.remove('bg-white', 'text-dark');
                this.classList.add('active');
                
                filterOrders();
            });
        });

        function filterOrders() {
            const query = document.getElementById('searchOrder').value.toLowerCase();
            const filter = document.querySelector('#orderTabs .nav-link.active').getAttribute('data-filter');
            
            document.querySelectorAll('.order-card').forEach(card => {
                const status = card.getAttribute('data-status');
                const text = card.innerText.toLowerCase();
                
                const matchesStatus = (filter === 'all' || status === filter);
                const matchesSearch = (text.includes(query));
                
                if (matchesStatus && matchesSearch) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Yangi buyurtmalar sonini chiqarish
        window.addEventListener('DOMContentLoaded', () => {
            const newCount = document.querySelectorAll('.order-card[data-status="new"]').length;
            if(newCount > 0) {
                document.querySelector('[data-filter="new"] .badge').innerText = newCount;
            } else {
                document.querySelector('[data-filter="new"] .badge').style.display = 'none';
            }
        });
    </script>
</body>
</html>
