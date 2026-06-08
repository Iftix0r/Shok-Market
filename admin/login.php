<?php
session_start();
$error = '';

// settings.json dan parolni o'qish
$adminPass = 'admin123';
$settingsFile = __DIR__ . '/settings.json';
if (file_exists($settingsFile)) {
    $s = json_decode(file_get_contents($settingsFile), true);
    if (!empty($s['admin_pass'])) $adminPass = $s['admin_pass'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['username'] === 'admin' && $_POST['password'] === $adminPass) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Login yoki parol noto'g'ri!";
    }
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shok Market - Admin Kirish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            display: flex; align-items: center; justify-content: center;
        }
        .login-box {
            background: #fff; border-radius: 24px; padding: 48px 40px;
            width: 100%; max-width: 420px; box-shadow: 0 25px 60px rgba(0,0,0,0.3);
        }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 32px; }
        .brand-icon { background: #FFD500; color: #000; width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 900; }
        .brand-text { font-size: 22px; font-weight: 800; color: #1a1a2e; }
        .brand-sub { font-size: 13px; color: #6b7280; font-weight: 500; }
        .form-control { border: 1.5px solid #e5e7eb; border-radius: 12px; padding: 12px 16px; font-size: 15px; }
        .form-control:focus { border-color: #FFD500; box-shadow: 0 0 0 3px rgba(255,213,0,0.15); }
        .btn-login { background: linear-gradient(135deg, #FFD500, #F7931A); color: #000; font-weight: 800; border: none; border-radius: 12px; padding: 14px; font-size: 16px; width: 100%; }
        .btn-login:hover { opacity: 0.9; color: #000; }
        .input-icon { position: relative; }
        .input-icon i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
        .input-icon .form-control { padding-left: 42px; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="brand">
            <div class="brand-icon"><i class="fas fa-bolt"></i></div>
            <div>
                <div class="brand-text">Shok Market</div>
                <div class="brand-sub">Seller Center — Admin Panel</div>
            </div>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger rounded-3 d-flex align-items-center gap-2 mb-4">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label class="form-label fw-bold text-muted" style="font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">Login</label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" class="form-control" placeholder="admin" value="admin" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold text-muted" style="font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">Parol</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt me-2"></i> Kirish
            </button>
        </form>
    </div>
</body>
</html>
