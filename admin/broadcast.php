<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
require_once '../config.php';
require_once '../db.php';

// Barcha foydalanuvchilarga xabar yuborish
if (isset($_POST['broadcast'])) {
    $text = trim($_POST['message_text']);
    $users = $pdo->query("SELECT id FROM users WHERE is_banned = 0")->fetchAll();
    $sent = 0;
    foreach ($users as $u) {
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $payload = ['chat_id' => $u['id'], 'text' => "📢 <b>Shok Market yangiligi:</b>\n\n" . $text, 'parse_mode' => 'HTML'];
        $opts = ['http' => ['method' => 'POST', 'header' => "Content-type: application/x-www-form-urlencoded\r\n", 'content' => http_build_query($payload)]];
        @file_get_contents($url, false, stream_context_create($opts));
        $sent++;
        usleep(50000); // Spam limitiga tushmaslik uchun
    }
    $_SESSION['alert'] = "✅ Xabar {$sent} ta foydalanuvchiga yuborildi!";
    header('Location: broadcast.php');
    exit;
}

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE is_banned=0")->fetchColumn();
$alert = $_SESSION['alert'] ?? '';
unset($_SESSION['alert']);

include 'layout_top.php';
?>
<script>document.getElementById('pageTitle').innerText = 'Xabar tarqatish';</script>

<?php if($alert): ?>
    <div class="alert alert-success fw-bold rounded-3 d-flex align-items-center gap-2">
        <i class="fas fa-check-circle"></i> <?= $alert ?>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="stat-card">
            <h5 class="fw-bold mb-2"><i class="fas fa-bullhorn text-warning me-2"></i> Xabar tarqatish</h5>
            <p class="text-muted mb-4">Yozgan xabaringiz barcha faol foydalanuvchilarga (<strong><?= $totalUsers ?> ta</strong>) Telegram orqali yuboriladi.</p>

            <div class="p-3 mb-4 rounded-3" style="background:#fffbeb; border: 1px solid #fde68a;">
                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                <strong>Ehtiyot bo'ling!</strong> Bu funksiya barcha foydalanuvchilarga bir vaqtda xabar yuboradi. Faqat muhim xabarlar uchun foydalaning.
            </div>

            <form method="post">
                <div class="mb-4">
                    <label class="form-label fw-bold">Xabar matni</label>
                    <textarea name="message_text" class="form-control" rows="6" placeholder="Misol: 🎉 Yangi mahsulotlar kelib tushdi! Buyurtma bering va chegirma oling..." required style="font-size:15px; line-height: 1.6;"></textarea>
                    <small class="text-muted mt-2 d-block"><i class="fas fa-info-circle"></i> Matn formatlash: &lt;b&gt;qalin&lt;/b&gt;, &lt;i&gt;kursiv&lt;/i&gt;, 🎉 emoji ishlaydi</small>
                </div>
                <div class="d-flex gap-3">
                    <button type="submit" name="broadcast" class="btn btn-warning px-5 py-2 fw-bold" onclick="return confirm('Rostdan ham barcha <?= $totalUsers ?> ta foydalanuvchiga xabar yuborasizmi?')">
                        <i class="fas fa-paper-plane me-2"></i> Yuborish (<?= $totalUsers ?> ta kishi)
                    </button>
                    <a href="dashboard.php" class="btn btn-light px-4 py-2 fw-bold">Bekor qilish</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'layout_bottom.php'; ?>
