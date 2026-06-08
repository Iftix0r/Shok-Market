<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
require_once '../config.php';
require_once '../db.php';

// Agar yo'q bo'lsa, users jadvaliga 'is_banned' ustunini avtomat qo'shamiz
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN is_banned TINYINT DEFAULT 0");
} catch(PDOException $e) {}

// Bloklash yoki blokdan yechish
if (isset($_POST['toggle_ban'])) {
    $stmt = $pdo->prepare("UPDATE users SET is_banned = 1 - is_banned WHERE id = ?");
    $stmt->execute([$_POST['user_id']]);
    header('Location: users.php');
    exit;
}

// Xabar yuborish
if (isset($_POST['send_message'])) {
    $userId = $_POST['user_id'];
    $text = trim($_POST['message_text']);
    if (!empty($text)) {
        sendTelegramMessage($userId, "📨 <b>Admindan xabar:</b>\n\n" . $text);
        $_SESSION['alert'] = "Xabar foydalanuvchiga muvaffaqiyatli yuborildi!";
    }
    header('Location: users.php');
    exit;
}

// Barcha foydalanuvchilarni olish va ularning jami qancha xarid qilganini hisoblash
$stmt = $pdo->query("
    SELECT u.*, 
           COUNT(o.id) as orders_count, 
           SUM(CASE WHEN o.status IN ('completed', 'accepted') THEN o.total_price ELSE 0 END) as total_spent 
    FROM users u 
    LEFT JOIN orders o ON u.id = o.user_id 
    GROUP BY u.id 
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();

function sendTelegramMessage($chatId, $text) {
    global $botToken;
    $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
    $data = ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'HTML'];
    $options = ['http' => ['header' => "Content-type: application/x-www-form-urlencoded\r\n", 'method' => 'POST', 'content' => http_build_query($data)]];
    @file_get_contents($url, false, stream_context_create($options));
}

$alert = $_SESSION['alert'] ?? '';
unset($_SESSION['alert']);
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <title>Foydalanuvchilar - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f5f7; font-family: 'Inter', sans-serif; }
        .user-card { background: #fff; border-radius: 16px; border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.03); transition: 0.2s;}
        .user-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .avatar-img { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #eaeaea; }
        .avatar-placeholder { width: 60px; height: 60px; border-radius: 50%; background: #e9ecef; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #adb5bd; }
        .banned-overlay { position: absolute; inset: 0; background: rgba(255,255,255,0.7); z-index: 1; border-radius: 16px; pointer-events: none;}
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
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-shopping-bag"></i> Buyurtmalar</a></li>
                    <li class="nav-item"><a class="nav-link" href="products.php"><i class="fas fa-box"></i> Mahsulotlar</a></li>
                    <li class="nav-item"><a class="nav-link active" href="users.php"><i class="fas fa-users"></i> Foydalanuvchilar</a></li>
                </ul>
                <a href="logout.php" class="btn btn-outline-light btn-sm"><i class="fas fa-sign-out-alt"></i> Chiqish</a>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <div class="container mt-4 mb-5">
        <?php if($alert): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?= $alert ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold m-0">Foydalanuvchilar ro'yxati</h4>
                <span class="text-muted small">Jami: <?= count($users) ?> ta foydalanuvchi</span>
            </div>
        </div>
        
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
            <?php foreach($users as $u): ?>
            <div class="col position-relative">
                <?php if($u['is_banned']): ?>
                    <div class="banned-overlay"></div>
                <?php endif; ?>
                
                <div class="user-card p-3 h-100">
                    <div class="d-flex align-items-center mb-3 position-relative" style="z-index: 2;">
                        <?php if(!empty($u['photo_url'])): ?>
                            <img src="<?= htmlspecialchars($u['photo_url']) ?>" class="avatar-img me-3">
                        <?php else: ?>
                            <div class="avatar-placeholder me-3"><i class="fas fa-user"></i></div>
                        <?php endif; ?>
                        
                        <div class="flex-grow-1">
                            <h6 class="mb-0 fw-bold"><?= htmlspecialchars($u['first_name']) ?> <?php if($u['is_banned']) echo '<span class="badge bg-danger ms-1">BLOKLANGAN</span>'; ?></h6>
                            <?php if(!empty($u['username'])): ?>
                                <a href="https://t.me/<?= htmlspecialchars($u['username']) ?>" target="_blank" class="text-decoration-none small">@<?= htmlspecialchars($u['username']) ?></a>
                            <?php else: ?>
                                <span class="text-muted small">Username yo'q</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row g-2 mb-3 position-relative" style="z-index: 2;">
                        <div class="col-6">
                            <div class="bg-light rounded p-2 text-center">
                                <div class="small text-muted fw-bold">Buyurtmalar</div>
                                <div class="fw-bold fs-5"><?= $u['orders_count'] ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light rounded p-2 text-center">
                                <div class="small text-muted fw-bold">Jami xaridi</div>
                                <div class="fw-bold fs-6 text-success mt-1"><?= number_format($u['total_spent'] ?? 0, 0, '', ' ') ?> <small>uzs</small></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 position-relative" style="z-index: 2;">
                        <button class="btn btn-primary btn-sm flex-grow-1 fw-bold" onclick="openMsgModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['first_name'], ENT_QUOTES) ?>')">
                            <i class="fas fa-paper-plane"></i> Xabar yozish
                        </button>
                        
                        <form method="post" class="m-0">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <?php if($u['is_banned']): ?>
                                <button type="submit" name="toggle_ban" class="btn btn-success btn-sm px-3 fw-bold" title="Blokdan yechish"><i class="fas fa-unlock"></i> Yechish</button>
                            <?php else: ?>
                                <button type="submit" name="toggle_ban" class="btn btn-outline-danger btn-sm px-3 fw-bold" title="Bloklash" onclick="return confirm('Foydalanuvchini rostdan ham bloklaysizmi?');"><i class="fas fa-ban"></i></button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Message Modal -->
    <div class="modal fade" id="msgModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-0 bg-primary rounded-top-4 text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-comment-dots"></i> Xabar yuborish</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-2">Qabul qiluvchi: <strong id="msg_recipient" class="text-dark"></strong></p>
                    <input type="hidden" name="user_id" id="msg_user_id">
                    <div class="form-group">
                        <textarea name="message_text" class="form-control form-control-lg bg-light" rows="4" placeholder="Xabar matnini yozing..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Bekor qilish</button>
                    <button type="submit" name="send_message" class="btn btn-primary px-4 fw-bold"><i class="fas fa-paper-plane"></i> Yuborish</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openMsgModal(id, name) {
            document.getElementById('msg_user_id').value = id;
            document.getElementById('msg_recipient').innerText = name;
            var modal = new bootstrap.Modal(document.getElementById('msgModal'));
            modal.show();
        }
    </script>
</body>
</html>
