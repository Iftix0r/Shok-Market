<?php
session_start();
if (isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    // XAVFSIZLIK UCHUN: parolni o'zingizga qulay qilib o'zgartiring
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Login yoki parol xato!';
    }
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <title>Admin Kirish</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <span class="bg-warning text-dark px-3 py-1 rounded fw-bold fs-4">SHOK MARKET</span>
                            <p class="text-muted mt-2">Boshqaruv paneli</p>
                        </div>
                        <?php if($error): ?><div class="alert alert-danger py-2"><?= $error ?></div><?php endif; ?>
                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">LOGIN</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-muted small fw-bold">PAROL</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-warning w-100 fw-bold py-2">Kirish</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
