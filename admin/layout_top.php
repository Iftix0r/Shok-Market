<?php
// Admin panel uchun umumiy layout (header + sidebar)
// Har bir sahifadan shu faylni include qiladi

$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Yangi buyurtmalar soni (Badge uchun)
$newOrdersCount = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'new'");
    $newOrdersCount = $stmt->fetchColumn();
} catch(Exception $e) {}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shok Market Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background-color: #f0f2f5; margin: 0; }

        /* SIDEBAR */
        .sidebar {
            width: 260px; min-height: 100vh; background: #1a1a2e;
            position: fixed; top: 0; left: 0; z-index: 100;
            display: flex; flex-direction: column;
            transition: all 0.3s ease;
        }
        .sidebar-logo {
            padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .sidebar-logo .brand {
            color: #FFD500; font-size: 20px; font-weight: 800; display: flex; align-items: center; gap: 10px;
        }
        .sidebar-logo .brand i { background: #FFD500; color: #000; padding: 6px 8px; border-radius: 8px; font-size: 14px;}
        .sidebar-nav { padding: 16px 12px; flex-grow: 1; }
        .nav-section-title { color: rgba(255,255,255,0.3); font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; padding: 8px 12px; margin-top: 8px;}
        .sidebar-link {
            display: flex; align-items: center; gap: 12px; padding: 12px 14px;
            color: rgba(255,255,255,0.55); border-radius: 12px; text-decoration: none;
            font-weight: 500; font-size: 14px; margin-bottom: 4px; transition: all 0.2s;
            position: relative;
        }
        .sidebar-link:hover { background: rgba(255,255,255,0.07); color: #fff; }
        .sidebar-link.active { background: linear-gradient(135deg, #FFD500, #F7931A); color: #000; font-weight: 700; }
        .sidebar-link .icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.08); font-size: 15px; flex-shrink: 0; }
        .sidebar-link.active .icon { background: rgba(0,0,0,0.12); }
        .sidebar-link .badge-count { background: #ef4444; color: #fff; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 999px; margin-left: auto; }
        .sidebar-bottom { padding: 16px 12px; border-top: 1px solid rgba(255,255,255,0.05); }

        /* MAIN CONTENT */
        .main-wrapper { margin-left: 260px; min-height: 100vh; }
        .top-bar {
            background: #fff; padding: 16px 28px; border-bottom: 1px solid #eee;
            display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50;
        }
        .top-bar .page-title { font-size: 20px; font-weight: 800; margin: 0; }
        .top-bar .admin-info { display: flex; align-items: center; gap: 10px; }
        .admin-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #FFD500, #F7931A); display: flex; align-items: center; justify-content: center; color: #000; font-weight: 800; }

        .content-area { padding: 28px; }

        /* STAT CARDS */
        .stat-card {
            background: #fff; border-radius: 18px; padding: 24px; border: none;
            box-shadow: 0 1px 8px rgba(0,0,0,0.04); height: 100%;
        }
        .stat-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 16px; }
        .stat-number { font-size: 28px; font-weight: 800; margin-bottom: 4px; }
        .stat-label { font-size: 13px; color: #6b7280; font-weight: 500; }
        .stat-change { font-size: 12px; margin-top: 6px; }

        /* TABLE STYLES */
        .admin-table { background: #fff; border-radius: 18px; box-shadow: 0 1px 8px rgba(0,0,0,0.04); overflow: hidden; }
        .admin-table-header { padding: 20px 24px; border-bottom: 1px solid #f3f4f6; display: flex; justify-content: space-between; align-items: center; }
        .admin-table thead th { background: #f9fafb; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; border: none; padding: 12px 16px; }
        .admin-table tbody td { padding: 14px 16px; border-top: 1px solid #f3f4f6; vertical-align: middle; border-bottom: none; }
        .admin-table tbody tr:hover td { background: #fafbfc; }

        /* ORDER CARD */
        .order-card { background: #fff; border-radius: 18px; margin-bottom: 16px; box-shadow: 0 1px 8px rgba(0,0,0,0.04); overflow: hidden;}
        .order-card-header { padding: 18px 24px; background: #f9fafb; border-bottom: 1px solid #f3f4f6; display: flex; justify-content: space-between; align-items: center; }
        .order-card-body { padding: 20px 24px; }
        .product-row { display: flex; align-items: center; gap: 14px; padding: 10px 0; border-bottom: 1px dashed #f0f0f0; }
        .product-row:last-child { border-bottom: none; padding-bottom: 0; }
        .product-row img { width: 56px; height: 56px; border-radius: 10px; object-fit: contain; background: #f9f9f9; padding: 4px; border: 1px solid #eee;}

        /* STATUS BADGES */
        .status-badge { padding: 5px 14px; border-radius: 999px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; }
        .status-new { background: #eff6ff; color: #2563eb; }
        .status-accepted { background: #fffbeb; color: #d97706; }
        .status-completed { background: #f0fdf4; color: #16a34a; }
        .status-cancelled { background: #fef2f2; color: #dc2626; }

        /* USER CARD */
        .user-card { background: #fff; border-radius: 18px; padding: 20px; box-shadow: 0 1px 8px rgba(0,0,0,0.04); height: 100%; transition: 0.2s; }
        .user-card:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,0,0,0.08); }
        .user-avatar { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 3px solid #f3f4f6; }
        .user-avatar-placeholder { width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 22px; }

        /* MODAL */
        .modal-content { border: none; border-radius: 20px; }
        .modal-header { border-bottom: 1px solid #f3f4f6; padding: 20px 24px; }
        .modal-body { padding: 24px; }
        .modal-footer { border-top: 1px solid #f3f4f6; padding: 16px 24px; }
        .form-control, .form-select { border-radius: 12px; border: 1.5px solid #e5e7eb; padding: 10px 14px; font-size: 14px; }
        .form-control:focus, .form-select:focus { border-color: #FFD500; box-shadow: 0 0 0 3px rgba(255,213,0,0.15); }
        .btn-primary { background: #1a1a2e; border: none; border-radius: 12px; font-weight: 700; }
        .btn-primary:hover { background: #16213e; }
        .btn-warning { background: #FFD500; border: none; color: #000; border-radius: 12px; font-weight: 700; }
        .btn-warning:hover { background: #F7931A; }

        /* MOBILE */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-wrapper { margin-left: 0; }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="brand"><i class="fas fa-bolt"></i> Shok Market</div>
            <div style="color:rgba(255,255,255,0.3); font-size:11px; margin-top:4px;">Seller Center</div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-title">Asosiy</div>
            <a href="dashboard.php" class="sidebar-link <?= $currentPage=='dashboard'?'active':'' ?>">
                <span class="icon"><i class="fas fa-chart-pie"></i></span> Dashboard
            </a>
            <a href="index.php" class="sidebar-link <?= $currentPage=='index'?'active':'' ?>">
                <span class="icon"><i class="fas fa-shopping-bag"></i></span> Buyurtmalar
                <?php if($newOrdersCount > 0): ?>
                    <span class="badge-count"><?= $newOrdersCount ?></span>
                <?php endif; ?>
            </a>
            <a href="products.php" class="sidebar-link <?= $currentPage=='products'?'active':'' ?>">
                <span class="icon"><i class="fas fa-box"></i></span> Mahsulotlar
            </a>
            <a href="users.php" class="sidebar-link <?= $currentPage=='users'?'active':'' ?>">
                <span class="icon"><i class="fas fa-users"></i></span> Foydalanuvchilar
            </a>
            <div class="nav-section-title">Boshqaruv</div>
            <a href="broadcast.php" class="sidebar-link <?= $currentPage=='broadcast'?'active':'' ?>">
                <span class="icon"><i class="fas fa-bullhorn"></i></span> Xabar tarqatish
            </a>
            <a href="settings.php" class="sidebar-link <?= $currentPage=='settings'?'active':'' ?>">
                <span class="icon"><i class="fas fa-cog"></i></span> Sozlamalar
            </a>
        </nav>
        <div class="sidebar-bottom">
            <a href="logout.php" class="sidebar-link" style="color: #ef4444;">
                <span class="icon" style="background: rgba(239,68,68,0.1);"><i class="fas fa-sign-out-alt" style="color:#ef4444;"></i></span> Chiqish
            </a>
        </div>
    </div>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">
        <div class="top-bar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn d-md-none" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars fa-lg"></i></button>
                <h5 class="page-title" id="pageTitle">Dashboard</h5>
            </div>
            <div class="admin-info">
                <span class="text-muted d-none d-md-block" style="font-size:13px;">Admin</span>
                <div class="admin-avatar">A</div>
            </div>
        </div>
        <div class="content-area">
