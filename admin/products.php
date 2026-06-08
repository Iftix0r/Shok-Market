<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
require_once '../db.php';

// Mahsulot qo'shish, tahrirlash yoki o'chirish (yashirish)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $stmt = $pdo->prepare("INSERT INTO products (name, price, unit, category, image, status) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$_POST['name'], $_POST['price'], $_POST['unit'], $_POST['category'], $_POST['image']]);
    } elseif (isset($_POST['edit_product'])) {
        $stmt = $pdo->prepare("UPDATE products SET name=?, price=?, unit=?, category=?, image=? WHERE id=?");
        $stmt->execute([$_POST['name'], $_POST['price'], $_POST['unit'], $_POST['category'], $_POST['image'], $_POST['product_id']]);
    } elseif (isset($_POST['delete_product'])) {
        // Yumshoq o'chirish
        $stmt = $pdo->prepare("UPDATE products SET status = 0 WHERE id = ?");
        $stmt->execute([$_POST['product_id']]);
    }
    header('Location: products.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM products WHERE status = 1 ORDER BY id DESC");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <title>Mahsulotlar - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f5f7; font-family: 'Inter', sans-serif; }
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
                    <li class="nav-item"><a class="nav-link active" href="products.php"><i class="fas fa-box"></i> Mahsulotlar</a></li>
                    <li class="nav-item"><a class="nav-link" href="users.php"><i class="fas fa-users"></i> Foydalanuvchilar</a></li>
                </ul>
                <a href="logout.php" class="btn btn-outline-light btn-sm"><i class="fas fa-sign-out-alt"></i> Chiqish</a>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <div class="container mt-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold m-0">Mahsulotlar katalogi</h4>
                <span class="text-muted small">Jami: <?= count($products) ?> ta mahsulot</span>
            </div>
            <button class="btn btn-warning fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Yangi qo'shish</button>
        </div>
        
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach($products as $p): ?>
            <div class="col">
                <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="bg-white text-center p-3" style="height: 180px;">
                        <img src="<?= htmlspecialchars($p['image']) ?>" class="img-fluid h-100" style="object-fit: contain;">
                    </div>
                    <div class="card-body border-top bg-light">
                        <span class="badge bg-secondary mb-2"><?= htmlspecialchars($p['category']) ?></span>
                        <h6 class="card-title fw-bold mb-1"><?= htmlspecialchars($p['name']) ?></h6>
                        <p class="card-text text-success fw-bold m-0"><?= number_format($p['price'], 0, '', ' ') ?> so'm <small class="text-muted fw-normal">(<?= htmlspecialchars($p['unit']) ?>)</small></p>
                    </div>
                    <div class="card-footer bg-light border-0 pt-0 pb-3">
                        <div class="row g-2">
                            <div class="col-6">
                                <button class="btn btn-outline-primary btn-sm w-100 fw-bold" onclick="editProduct(<?= htmlspecialchars(json_encode($p)) ?>)"><i class="fas fa-edit"></i> Tahrir</button>
                            </div>
                            <div class="col-6">
                                <form method="post" onsubmit="return confirm('Mahsulotni o\'chirasizmi? (Eski buyurtmalarda ko\'rinib turadi, faqat mijozlarga ko\'rinmaydi)');">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <button type="submit" name="delete_product" class="btn btn-outline-danger btn-sm w-100 fw-bold"><i class="fas fa-trash"></i> O'chirish</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 bg-warning rounded-top-4">
                    <h5 class="modal-title fw-bold text-dark"><i class="fas fa-box-open"></i> Yangi mahsulot qo'shish</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small">NOMI</label>
                        <input type="text" name="name" class="form-control form-control-lg bg-light" required placeholder="Masalan: Qizil Olma">
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label text-muted fw-bold small">NARXI (so'm)</label>
                            <input type="number" name="price" class="form-control form-control-lg bg-light" required placeholder="15000">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted fw-bold small">O'LCHOV BIRLIGI</label>
                            <input type="text" name="unit" class="form-control form-control-lg bg-light" value="so'm/kg" required placeholder="so'm/dona">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small">KATEGORIYA</label>
                        <select name="category" class="form-select form-select-lg bg-light">
                            <option value="fruits">Mevalar va Sabzavotlar</option>
                            <option value="meat">Go'sht va Sut</option>
                            <option value="bakery">Non va Shirinliklar</option>
                            <option value="drinks">Ichimliklar</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small">RASM (Ssilka/URL)</label>
                        <input type="url" name="image" class="form-control bg-light" placeholder="https://..." required>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Bekor qilish</button>
                    <button type="submit" name="add_product" class="btn btn-dark px-4 fw-bold">Qo'shish</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 bg-primary bg-opacity-25 rounded-top-4">
                    <h5 class="modal-title fw-bold text-dark"><i class="fas fa-edit"></i> Mahsulotni tahrirlash</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="product_id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small">NOMI</label>
                        <input type="text" name="name" id="edit_name" class="form-control form-control-lg bg-light" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label text-muted fw-bold small">NARXI (so'm)</label>
                            <input type="number" name="price" id="edit_price" class="form-control form-control-lg bg-light" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted fw-bold small">O'LCHOV BIRLIGI</label>
                            <input type="text" name="unit" id="edit_unit" class="form-control form-control-lg bg-light" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small">KATEGORIYA</label>
                        <select name="category" id="edit_category" class="form-select form-select-lg bg-light">
                            <option value="fruits">Mevalar va Sabzavotlar</option>
                            <option value="meat">Go'sht va Sut</option>
                            <option value="bakery">Non va Shirinliklar</option>
                            <option value="drinks">Ichimliklar</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small">RASM (Ssilka/URL)</label>
                        <input type="url" name="image" id="edit_image" class="form-control bg-light" required>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Bekor qilish</button>
                    <button type="submit" name="edit_product" class="btn btn-primary px-4 fw-bold">Saqlash</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editProduct(product) {
            document.getElementById('edit_id').value = product.id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_unit').value = product.unit;
            document.getElementById('edit_category').value = product.category;
            document.getElementById('edit_image').value = product.image;
            var editModal = new bootstrap.Modal(document.getElementById('editModal'));
            editModal.show();
        }
    </script>
</body>
</html>
