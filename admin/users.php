<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }
require_once '../config.php';
require_once '../db.php';

try { $pdo->exec("ALTER TABLE users ADD COLUMN is_banned TINYINT DEFAULT 0"); } catch(Exception $e) {}

if (isset($_POST['toggle_ban'])) {
    $pdo->prepare("UPDATE users SET is_banned = 1 - is_banned WHERE id = ?")->execute([$_POST['user_id']]);
    header('Location: users.php'); exit;
}
if (isset($_POST['send_message'])) {
    $uid = $_POST['user_id'];
    $text = trim($_POST['message_text']);
    if (!empty($text)) {
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $d = ['chat_id'=>$uid,'text'=>"📨 <b>Admindan xabar:</b>\n\n".$text,'parse_mode'=>'HTML'];
        $o = ['http'=>['method'=>'POST','header'=>"Content-type: application/x-www-form-urlencoded\r\n",'content'=>http_build_query($d)]];
        @file_get_contents($url, false, stream_context_create($o));
        $_SESSION['alert'] = "Xabar yuborildi!";
    }
    header('Location: users.php'); exit;
}

$users = $pdo->query("SELECT u.*, COUNT(o.id) as orders_count, COALESCE(SUM(CASE WHEN o.status IN('completed','accepted') THEN o.total_price ELSE 0 END),0) as total_spent FROM users u LEFT JOIN orders o ON u.id=o.user_id GROUP BY u.id ORDER BY u.created_at DESC")->fetchAll();

$alert = $_SESSION['alert'] ?? ''; unset($_SESSION['alert']);
include 'layout_top.php';
?>
<script>document.getElementById('pageTitle').innerText = 'Foydalanuvchilar';</script>

<?php if($alert): ?>
    <div class="alert alert-success rounded-3 fw-bold d-flex align-items-center gap-2 mb-4">
        <i class="fas fa-check-circle"></i> <?= $alert ?>
    </div>
<?php endif; ?>

<p class="text-muted mb-4">Jami <strong><?= count($users) ?></strong> ta ro'yxatdan o'tgan foydalanuvchi</p>

<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
    <?php foreach($users as $u): ?>
    <div class="col">
        <div class="user-card <?= $u['is_banned']?'border border-danger border-2':'' ?>">
            <div class="d-flex align-items-center gap-3 mb-3">
                <?php if(!empty($u['photo_url'])): ?>
                    <img src="<?= htmlspecialchars($u['photo_url']) ?>" class="user-avatar">
                <?php else: ?>
                    <div class="user-avatar-placeholder"><i class="fas fa-user"></i></div>
                <?php endif; ?>
                <div class="flex-grow-1">
                    <h6 class="mb-0 fw-bold">
                        <?= htmlspecialchars($u['first_name']) ?>
                        <?php if($u['is_banned']): ?><span class="badge bg-danger ms-1">BLOKLANGAN</span><?php endif; ?>
                    </h6>
                    <?php if(!empty($u['username'])): ?>
                        <a href="https://t.me/<?= htmlspecialchars($u['username']) ?>" target="_blank" class="text-primary small fw-semibold">@<?= htmlspecialchars($u['username']) ?></a>
                    <?php else: ?>
                        <span class="text-muted small">Username yo'q</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <div class="rounded-3 p-2 text-center" style="background:#f9fafb;">
                        <div class="fw-bold fs-5 text-primary"><?= $u['orders_count'] ?></div>
                        <div class="text-muted" style="font-size:11px; font-weight:600;">BUYURTMALAR</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="rounded-3 p-2 text-center" style="background:#f9fafb;">
                        <div class="fw-bold text-success" style="font-size:14px;"><?= number_format($u['total_spent']/1000,0,'','') ?>K</div>
                        <div class="text-muted" style="font-size:11px; font-weight:600;">SO'M XARID</div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-sm fw-bold flex-grow-1" style="background:#eff6ff; color:#2563eb;" onclick="openMsg(<?= $u['id'] ?>, '<?= htmlspecialchars($u['first_name'], ENT_QUOTES) ?>')">
                    <i class="fas fa-paper-plane me-1"></i> Xabar yozish
                </button>
                <form method="post" class="m-0">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <?php if($u['is_banned']): ?>
                        <button type="submit" name="toggle_ban" class="btn btn-sm btn-success fw-bold px-3"><i class="fas fa-unlock"></i></button>
                    <?php else: ?>
                        <button type="submit" name="toggle_ban" class="btn btn-sm fw-bold px-3" style="background:#fef2f2; color:#dc2626;" onclick="return confirm('Bloklaysizmi?')"><i class="fas fa-ban"></i></button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Message Modal -->
<div class="modal fade" id="msgModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold"><i class="fas fa-comment-dots me-2 text-primary"></i>Xabar yuborish</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="text-muted small mb-2">Qabul qiluvchi: <strong id="msg_name"></strong></p>
                <input type="hidden" name="user_id" id="msg_uid">
                <textarea name="message_text" class="form-control" rows="5" placeholder="Xabar matnini yozing..." required></textarea>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Bekor</button><button type="submit" name="send_message" class="btn btn-primary fw-bold px-4"><i class="fas fa-paper-plane me-1"></i>Yuborish</button></div>
        </form>
    </div>
</div>
<script>
function openMsg(id, name) {
    document.getElementById('msg_uid').value = id;
    document.getElementById('msg_name').innerText = name;
    new bootstrap.Modal(document.getElementById('msgModal')).show();
}
</script>
<?php include 'layout_bottom.php'; ?>
