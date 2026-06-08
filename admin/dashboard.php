<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
require_once '../db.php';

// --- STATISTIKA HISOBLASH ---
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE status='completed'")->fetchColumn();
$todayRevenue = $pdo->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE status='completed' AND DATE(created_at)=CURDATE()")->fetchColumn();
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$newOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='new'")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE status=1")->fetchColumn();

// So'nggi 7 kunlik daromad (grafik uchun)
$stmt = $pdo->query("
    SELECT DATE(created_at) as day, COALESCE(SUM(total_price),0) as revenue
    FROM orders WHERE status='completed' AND created_at >= CURDATE() - INTERVAL 7 DAY
    GROUP BY DATE(created_at) ORDER BY day ASC
");
$chartData = $stmt->fetchAll();
$chartLabels = [];
$chartValues = [];
for($i=6; $i>=0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('d.m', strtotime($date));
    $val = 0;
    foreach($chartData as $cd) {
        if($cd['day'] == $date) { $val = $cd['revenue']; break; }
    }
    $chartLabels[] = $label;
    $chartValues[] = $val;
}

// Top mahsulotlar
$topProducts = $pdo->query("
    SELECT p.name, p.image, SUM(oi.quantity) as sold_count
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    GROUP BY oi.product_id ORDER BY sold_count DESC LIMIT 5
")->fetchAll();

// So'nggi 5 buyurtma
$lastOrders = $pdo->query("
    SELECT o.*, u.first_name, u.username FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC LIMIT 5
")->fetchAll();

include 'layout_top.php';
?>

<script>document.getElementById('pageTitle').innerText = 'Dashboard';</script>

<!-- STAT CARDS -->
<div class="row g-4 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fffbeb; color:#d97706;"><i class="fas fa-wallet"></i></div>
            <div class="stat-number" style="color:#d97706;"><?= number_format($totalRevenue/1000000, 1) ?> mln</div>
            <div class="stat-label">Umumiy daromad</div>
            <div class="stat-change text-success"><i class="fas fa-arrow-up"></i> Bugun: <?= number_format($todayRevenue, 0, '', ' ') ?> so'm</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#eff6ff; color:#2563eb;"><i class="fas fa-shopping-bag"></i></div>
            <div class="stat-number" style="color:#2563eb;"><?= $totalOrders ?></div>
            <div class="stat-label">Jami buyurtmalar</div>
            <div class="stat-change text-danger"><i class="fas fa-clock"></i> Yangi kutilmoqda: <?= $newOrders ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#f0fdf4; color:#16a34a;"><i class="fas fa-users"></i></div>
            <div class="stat-number" style="color:#16a34a;"><?= $totalUsers ?></div>
            <div class="stat-label">Foydalanuvchilar</div>
            <div class="stat-change text-muted"><i class="fas fa-user-plus"></i> Ro'yxatdan o'tgan</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fdf4ff; color:#7c3aed;"><i class="fas fa-box"></i></div>
            <div class="stat-number" style="color:#7c3aed;"><?= $totalProducts ?></div>
            <div class="stat-label">Faol mahsulotlar</div>
            <div class="stat-change text-muted"><i class="fas fa-store"></i> Katalogdagi</div>
        </div>
    </div>
</div>

<!-- CHART + TOP PRODUCTS -->
<div class="row g-4 mb-4">
    <div class="col-md-7">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="fw-bold m-0">So'nggi 7 kunlik daromad</h6>
                <span class="badge bg-warning text-dark rounded-pill">Yetkazilgan buyurtmalar</span>
            </div>
            <canvas id="revenueChart" height="100"></canvas>
        </div>
    </div>
    <div class="col-md-5">
        <div class="stat-card h-100">
            <h6 class="fw-bold mb-4">🔥 Eng ko'p sotilganlar</h6>
            <?php if(count($topProducts) == 0): ?>
                <p class="text-muted text-center py-3">Hali buyurtmalar yo'q</p>
            <?php endif; ?>
            <?php foreach($topProducts as $i => $p): ?>
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="fw-bold text-muted" style="width:20px;"><?= $i+1 ?></div>
                <img src="<?= htmlspecialchars($p['image'] ?? '') ?>" style="width:40px; height:40px; border-radius:10px; object-fit:contain; background:#f9f9f9; border:1px solid #eee;">
                <div class="flex-grow-1">
                    <div class="fw-semibold" style="font-size:13px;"><?= htmlspecialchars($p['name'] ?? 'Noma\'lum') ?></div>
                </div>
                <span class="badge bg-warning text-dark fw-bold"><?= $p['sold_count'] ?> ta</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- LAST ORDERS TABLE -->
<div class="admin-table">
    <div class="admin-table-header">
        <h6 class="fw-bold m-0">So'nggi buyurtmalar</h6>
        <a href="index.php" class="btn btn-warning btn-sm px-3">Barchasi <i class="fas fa-arrow-right ms-1"></i></a>
    </div>
    <table class="table admin-table mb-0">
        <thead>
            <tr>
                <th>Buyurtma</th>
                <th>Mijoz</th>
                <th>Summa</th>
                <th>Sana</th>
                <th>Holat</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($lastOrders as $o):
                $statusClass = ['new'=>'status-new', 'accepted'=>'status-accepted', 'completed'=>'status-completed', 'cancelled'=>'status-cancelled'][$o['status']] ?? '';
                $statusLabel = ['new'=>'Yangi', 'accepted'=>'Jarayonda', 'completed'=>'Yetkazildi', 'cancelled'=>'Bekor'][$o['status']] ?? $o['status'];
            ?>
            <tr>
                <td><span class="fw-bold">#<?= $o['id'] ?></span></td>
                <td>
                    <div class="fw-semibold"><?= htmlspecialchars($o['first_name'] ?? 'Noma\'lum') ?></div>
                    <small class="text-primary">@<?= htmlspecialchars($o['username'] ?? '') ?></small>
                </td>
                <td class="fw-bold text-success"><?= number_format($o['total_price'], 0, '', ' ') ?> so'm</td>
                <td class="text-muted" style="font-size:13px;"><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
                <td><span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Daromad (so\'m)',
                data: <?= json_encode($chartValues) ?>,
                borderColor: '#FFD500',
                backgroundColor: 'rgba(255,213,0,0.1)',
                borderWidth: 3,
                pointBackgroundColor: '#FFD500',
                pointRadius: 5,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: '#f3f4f6' }, ticks: { font: { size: 12 } } },
                x: { grid: { display: false }, ticks: { font: { size: 12 } } }
            }
        }
    });
</script>

<?php include 'layout_bottom.php'; ?>
