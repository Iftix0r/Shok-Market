<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
require_once '../config.php';
require_once '../db.php';

$alert = '';
$alertType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_password'])) {
        $oldPass = $_POST['old_password'];
        $newPass = $_POST['new_password'];
        $confirmPass = $_POST['confirm_password'];
        
        // Hard-coded parolni fayldan o'qish (yoki config'da bo'lishi mumkin)
        $adminPass = $GLOBALS['adminPass'] ?? 'admin123';
        
        if ($oldPass !== $adminPass) {
            $alert = '❌ Eski parol noto\'g\'ri!';
            $alertType = 'danger';
        } elseif ($newPass !== $confirmPass) {
            $alert = '❌ Yangi parollar mos kelmadi!';
            $alertType = 'danger';
        } elseif (strlen($newPass) < 6) {
            $alert = '❌ Parol kamida 6 ta belgidan iborat bo\'lishi kerak!';
            $alertType = 'danger';
        } else {
            // Parolni settings.json ga yozish
            file_put_contents(__DIR__ . '/settings.json', json_encode(['admin_pass' => $newPass]));
            $alert = '✅ Parol muvaffaqiyatli o\'zgartirildi!';
        }
    }
    
    if (isset($_POST['save_settings'])) {
        $settings = [
            'shop_name' => $_POST['shop_name'],
            'shop_phone' => $_POST['shop_phone'],
            'delivery_info' => $_POST['delivery_info'],
            'admin_pass' => $_POST['admin_pass_hidden']
        ];
        file_put_contents(__DIR__ . '/settings.json', json_encode($settings, JSON_UNESCAPED_UNICODE));
        $alert = '✅ Sozlamalar saqlandi!';
    }
}

$settings = [];
if (file_exists(__DIR__ . '/settings.json')) {
    $settings = json_decode(file_get_contents(__DIR__ . '/settings.json'), true);
}

include 'layout_top.php';
?>
<script>document.getElementById('pageTitle').innerText = 'Sozlamalar';</script>

<?php if($alert): ?>
    <div class="alert alert-<?= $alertType ?> rounded-3 fw-bold d-flex align-items-center gap-2">
        <i class="fas fa-info-circle"></i> <?= $alert ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Do'kon sozlamalari -->
    <div class="col-md-7">
        <div class="stat-card">
            <h6 class="fw-bold mb-4"><i class="fas fa-store text-warning me-2"></i> Do'kon ma'lumotlari</h6>
            <form method="post">
                <input type="hidden" name="admin_pass_hidden" value="<?= htmlspecialchars($settings['admin_pass'] ?? 'admin123') ?>">
                <div class="mb-3">
                    <label class="form-label fw-bold text-muted small text-uppercase">Do'kon nomi</label>
                    <input type="text" name="shop_name" class="form-control" value="<?= htmlspecialchars($settings['shop_name'] ?? 'Shok Market') ?>" placeholder="Shok Market">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-muted small text-uppercase">Aloqa telefoni</label>
                    <input type="text" name="shop_phone" class="form-control" value="<?= htmlspecialchars($settings['shop_phone'] ?? '') ?>" placeholder="+998 90 000 00 00">
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold text-muted small text-uppercase">Yetkazib berish haqida ma'lumot</label>
                    <textarea name="delivery_info" class="form-control" rows="3" placeholder="Masalan: Samarqand shahar bo'ylab 1-2 soat ichida yetkazamiz."><?= htmlspecialchars($settings['delivery_info'] ?? '') ?></textarea>
                </div>
                <button type="submit" name="save_settings" class="btn btn-warning px-4 fw-bold">
                    <i class="fas fa-save me-2"></i> Saqlash
                </button>
            </form>
        </div>
    </div>

    <!-- Parol o'zgartirish -->
    <div class="col-md-5">
        <div class="stat-card">
            <h6 class="fw-bold mb-4"><i class="fas fa-lock text-warning me-2"></i> Parolni o'zgartirish</h6>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label fw-bold text-muted small text-uppercase">Eski parol</label>
                    <input type="password" name="old_password" class="form-control" required placeholder="••••••••">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-muted small text-uppercase">Yangi parol</label>
                    <input type="password" name="new_password" class="form-control" required placeholder="••••••••">
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold text-muted small text-uppercase">Yangi parolni tasdiqlang</label>
                    <input type="password" name="confirm_password" class="form-control" required placeholder="••••••••">
                </div>
                <button type="submit" name="change_password" class="btn btn-dark px-4 fw-bold w-100">
                    <i class="fas fa-key me-2"></i> Parolni yangilash
                </button>
            </form>
        </div>

        <!-- Tizim ma'lumotlari -->
        <div class="stat-card mt-4">
            <h6 class="fw-bold mb-3"><i class="fas fa-info-circle text-warning me-2"></i> Tizim ma'lumotlari</h6>
            <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="text-muted small">PHP versiyasi</span>
                <span class="fw-bold small"><?= phpversion() ?></span>
            </div>
            <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="text-muted small">Web App URL</span>
                <a href="<?= $webAppUrl ?>" target="_blank" class="fw-bold small text-truncate ms-2" style="max-width:180px;"><?= $webAppUrl ?></a>
            </div>
            <div class="d-flex justify-content-between py-2">
                <span class="text-muted small">Webhook</span>
                <a href="https://api.telegram.org/bot<?= $botToken ?>/setWebhook?url=https://shokmarket.bigsaver.ru/bot.php" target="_blank" class="btn btn-sm btn-outline-success fw-bold px-3" style="font-size:11px;">
                    <i class="fas fa-link me-1"></i> O'rnatish
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'layout_bottom.php'; ?>
