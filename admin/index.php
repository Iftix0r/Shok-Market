<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
require_once '../db.php';

if (isset($_POST['change_status']) && isset($_POST['order_id'])) {
    $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$_POST['new_status'], $_POST['order_id']]);
    header('Location: index.php');
    exit;
}

$orders = $pdo->query("SELECT o.*, u.first_name, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetchAll();

$allItems = $pdo->query("SELECT oi.*, p.name, p.image, p.unit FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id")->fetchAll();
$orderItems = [];
foreach($allItems as $item) { $orderItems[$item['order_id']][] = $item; }

$totalRevenue = array_sum(array_column(array_filter($orders, fn($o) => in_array($o['status'], ['completed','accepted'])), 'total_price'));
$newCount = count(array_filter($orders, fn($o) => $o['status'] === 'new'));

include 'layout_top.php';
?>
<script>document.getElementById('pageTitle').innerText = 'Buyurtmalar';</script>

<!-- Stats row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card text-center p-3">
            <div class="stat-number" style="color:#2563eb;"><?= count($orders) ?></div>
            <div class="stat-label">Jami</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card text-center p-3">
            <div class="stat-number" style="color:#dc2626;"><?= $newCount ?></div>
            <div class="stat-label">Yangi</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card text-center p-3">
            <div class="stat-number" style="color:#16a34a;"><?= count(array_filter($orders, fn($o) => $o['status']==='completed')) ?></div>
            <div class="stat-label">Yetkazildi</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card text-center p-3">
            <div class="stat-number fs-5 text-success"><?= number_format($totalRevenue/1000, 0, '', ' ') ?> K</div>
            <div class="stat-label">Daromad (so'm)</div>
        </div>
    </div>
</div>

<!-- Filter bar -->
<div class="d-flex flex-wrap gap-2 align-items-center mb-4">
    <div class="nav gap-2" id="orderTabs">
        <button class="btn btn-dark btn-sm rounded-pill px-4 fw-bold active" data-filter="all">Barchasi</button>
        <button class="btn btn-outline-secondary btn-sm rounded-pill px-4 fw-bold" data-filter="new">Yangi <?php if($newCount): ?><span class="badge bg-danger ms-1"><?= $newCount ?></span><?php endif; ?></button>
        <button class="btn btn-outline-secondary btn-sm rounded-pill px-4 fw-bold" data-filter="accepted">Jarayonda</button>
        <button class="btn btn-outline-secondary btn-sm rounded-pill px-4 fw-bold" data-filter="completed">Yetkazildi</button>
        <button class="btn btn-outline-secondary btn-sm rounded-pill px-4 fw-bold" data-filter="cancelled">Bekor</button>
    </div>
    <div class="ms-auto" style="max-width:260px; width:100%;">
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
            <input type="text" id="searchOrder" class="form-control border-start-0 ps-0" placeholder="Qidirish...">
        </div>
    </div>
</div>

<?php if(count($orders) == 0): ?>
    <div class="text-center py-5 text-muted"><i class="fas fa-box-open fa-4x mb-3"></i><h5>Buyurtmalar yo'q</h5></div>
<?php endif; ?>

<?php foreach($orders as $o):
    $colors = ['new'=>'status-new','accepted'=>'status-accepted','completed'=>'status-completed','cancelled'=>'status-cancelled'];
    $labels = ['new'=>'Yangi','accepted'=>'Jarayonda','completed'=>'Yetkazildi','cancelled'=>'Bekor qilingan'];
    $col = $colors[$o['status']] ?? '';
    $lbl = $labels[$o['status']] ?? $o['status'];
    $items = $orderItems[$o['id']] ?? [];
?>
<div class="order-card" data-status="<?= $o['status'] ?>">
    <div class="order-card-header">
        <div class="d-flex align-items-center gap-3">
            <span class="fw-bold fs-5">#<?= $o['id'] ?></span>
            <span class="status-badge <?= $col ?>"><?= $lbl ?></span>
        </div>
        <div class="text-muted" style="font-size:13px;"><i class="far fa-clock me-1"></i><?= date('d.m.Y, H:i', strtotime($o['created_at'])) ?></div>
    </div>
    <div class="order-card-body">
        <div class="row">
            <!-- Mijoz -->
            <div class="col-md-3 mb-3 mb-md-0 border-end">
                <p class="text-muted fw-bold mb-3" style="font-size:11px; text-transform:uppercase; letter-spacing:1px;">Mijoz</p>
                <div class="fw-bold"><?= htmlspecialchars($o['first_name'] ?? 'Noma\'lum') ?></div>
                <?php if(!empty($o['username'])): ?>
                    <a href="https://t.me/<?= htmlspecialchars($o['username']) ?>" target="_blank" class="text-primary small">@<?= htmlspecialchars($o['username']) ?></a>
                <?php endif; ?>
                <div class="mt-2">
                    <a href="tel:<?= htmlspecialchars($o['phone'] ?? '') ?>" class="text-dark fw-bold d-block"><i class="fas fa-phone-alt text-success me-1"></i><?= htmlspecialchars($o['phone'] ?? 'Kiritilmagan') ?></a>
                    <div class="text-muted small mt-1"><i class="fas fa-map-marker-alt text-danger me-1"></i><?= htmlspecialchars($o['address'] ?? 'Manzil yo\'q') ?></div>
                </div>
                <hr>
                <form method="post">
                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                    <select name="new_status" class="form-select form-select-sm mb-2">
                        <option value="new" <?= $o['status']=='new'?'selected':'' ?>>Yangi</option>
                        <option value="accepted" <?= $o['status']=='accepted'?'selected':'' ?>>Jarayonda</option>
                        <option value="completed" <?= $o['status']=='completed'?'selected':'' ?>>Yetkazildi</option>
                        <option value="cancelled" <?= $o['status']=='cancelled'?'selected':'' ?>>Bekor qilish</option>
                    </select>
                    <button type="submit" name="change_status" class="btn btn-dark btn-sm w-100 fw-bold">Saqlash</button>
                </form>
            </div>
            <!-- Mahsulotlar -->
            <div class="col-md-9">
                <p class="text-muted fw-bold mb-3" style="font-size:11px; text-transform:uppercase; letter-spacing:1px;">Mahsulotlar (<?= count($items) ?> xil)</p>
                <?php foreach($items as $item): ?>
                <div class="product-row">
                    <img src="<?= htmlspecialchars($item['image'] ?? '') ?>" alt="">
                    <div class="flex-grow-1">
                        <div class="fw-semibold"><?= htmlspecialchars($item['name'] ?? 'Noma\'lum') ?></div>
                        <div class="text-muted small"><?= number_format($item['price'], 0, '', ' ') ?> so'm / <?= htmlspecialchars($item['unit'] ?? 'dona') ?></div>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-light text-dark border fw-bold px-3 py-2"><?= $item['quantity'] ?> ta</span>
                        <div class="fw-bold text-success mt-1"><?= number_format($item['price']*$item['quantity'], 0, '', ' ') ?> so'm</div>
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top bg-light rounded p-3">
                    <span class="fw-bold text-muted">JAMI TO'LOV:</span>
                    <span class="fs-4 fw-bold text-success"><?= number_format($o['total_price'], 0, '', ' ') ?> so'm</span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
document.getElementById('searchOrder').addEventListener('input', filterOrders);
document.querySelectorAll('#orderTabs button').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('#orderTabs button').forEach(b => {
            b.classList.remove('active', 'btn-dark');
            b.classList.add('btn-outline-secondary');
        });
        this.classList.add('active', 'btn-dark');
        this.classList.remove('btn-outline-secondary');
        filterOrders();
    });
});
function filterOrders() {
    const q = document.getElementById('searchOrder').value.toLowerCase();
    const f = document.querySelector('#orderTabs button.active').dataset.filter;
    document.querySelectorAll('.order-card').forEach(c => {
        const match = (f==='all' || c.dataset.status===f) && c.innerText.toLowerCase().includes(q);
        c.style.display = match ? 'block' : 'none';
    });
}
</script>

<?php include 'layout_bottom.php'; ?>
