<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $pdo->prepare("INSERT INTO products (name,price,unit,category,image,status) VALUES(?,?,?,?,?,1)")->execute([$_POST['name'],$_POST['price'],$_POST['unit'],$_POST['category'],$_POST['image']]);
    } elseif (isset($_POST['edit_product'])) {
        $pdo->prepare("UPDATE products SET name=?,price=?,unit=?,category=?,image=? WHERE id=?")->execute([$_POST['name'],$_POST['price'],$_POST['unit'],$_POST['category'],$_POST['image'],$_POST['product_id']]);
    } elseif (isset($_POST['delete_product'])) {
        $pdo->prepare("UPDATE products SET status=0 WHERE id=?")->execute([$_POST['product_id']]);
    }
    header('Location: products.php'); exit;
}
$products = $pdo->query("SELECT * FROM products WHERE status=1 ORDER BY id DESC")->fetchAll();
$cats = ['fruits'=>'🍎 Mevalar','meat'=>'🥩 Go\'sht','bakery'=>'🍞 Non','drinks'=>'🥤 Ichimlik'];
include 'layout_top.php';
?>
<script>document.getElementById('pageTitle').innerText = 'Mahsulotlar';</script>

<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted m-0">Jami <strong><?= count($products) ?></strong> ta faol mahsulot</p>
    <button class="btn btn-warning fw-bold px-4" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="fas fa-plus me-2"></i>Yangi mahsulot
    </button>
</div>

<div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-4">
    <?php foreach($products as $p): ?>
    <div class="col">
        <div class="stat-card p-0 overflow-hidden h-100 d-flex flex-column">
            <div class="text-center p-3" style="height:160px; background:#f9fafb;">
                <img src="<?= htmlspecialchars($p['image']) ?>" class="h-100" style="object-fit:contain; max-width:100%;">
            </div>
            <div class="p-3 flex-grow-1 d-flex flex-column">
                <span class="badge bg-light text-dark border mb-2 align-self-start"><?= $cats[$p['category']] ?? $p['category'] ?></span>
                <h6 class="fw-bold mb-1 flex-grow-1"><?= htmlspecialchars($p['name']) ?></h6>
                <p class="text-success fw-bold mb-0"><?= number_format($p['price'],0,'','  ') ?> so'm <small class="text-muted fw-normal"><?= htmlspecialchars($p['unit']) ?></small></p>
            </div>
            <div class="d-flex border-top">
                <button class="btn btn-sm btn-light w-50 rounded-0 fw-bold py-2 border-end" onclick='editProduct(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>'><i class="fas fa-edit me-1"></i>Tahrir</button>
                <form method="post" class="w-50 m-0" onsubmit="return confirm('O\'chirasizmi?')">
                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                    <button type="submit" name="delete_product" class="btn btn-sm btn-light w-100 rounded-0 fw-bold py-2 text-danger"><i class="fas fa-trash me-1"></i>O'chirish</button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Yangi mahsulot</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label fw-bold small text-muted">NOMI</label><input type="text" name="name" class="form-control" required></div>
                <div class="row mb-3">
                    <div class="col-6"><label class="form-label fw-bold small text-muted">NARX (so'm)</label><input type="number" name="price" class="form-control" required></div>
                    <div class="col-6"><label class="form-label fw-bold small text-muted">BIRLIGI</label><input type="text" name="unit" class="form-control" value="so'm/kg" required></div>
                </div>
                <div class="mb-3"><label class="form-label fw-bold small text-muted">KATEGORIYA</label>
                    <select name="category" class="form-select"><?php foreach($cats as $k=>$v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?></select>
                </div>
                <div class="mb-3"><label class="form-label fw-bold small text-muted">RASM URL</label><input type="url" name="image" class="form-control" required placeholder="https://..."></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Bekor</button><button type="submit" name="add_product" class="btn btn-warning fw-bold px-4">Qo'shish</button></div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Mahsulotni tahrirlash</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="product_id" id="edit_id">
                <div class="mb-3"><label class="form-label fw-bold small text-muted">NOMI</label><input type="text" name="name" id="edit_name" class="form-control" required></div>
                <div class="row mb-3">
                    <div class="col-6"><label class="form-label fw-bold small text-muted">NARX (so'm)</label><input type="number" name="price" id="edit_price" class="form-control" required></div>
                    <div class="col-6"><label class="form-label fw-bold small text-muted">BIRLIGI</label><input type="text" name="unit" id="edit_unit" class="form-control" required></div>
                </div>
                <div class="mb-3"><label class="form-label fw-bold small text-muted">KATEGORIYA</label>
                    <select name="category" id="edit_category" class="form-select"><?php foreach($cats as $k=>$v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?></select>
                </div>
                <div class="mb-3"><label class="form-label fw-bold small text-muted">RASM URL</label><input type="url" name="image" id="edit_image" class="form-control" required></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Bekor</button><button type="submit" name="edit_product" class="btn btn-primary fw-bold px-4">Saqlash</button></div>
        </form>
    </div>
</div>
<script>
function editProduct(p) {
    document.getElementById('edit_id').value=p.id;
    document.getElementById('edit_name').value=p.name;
    document.getElementById('edit_price').value=p.price;
    document.getElementById('edit_unit').value=p.unit;
    document.getElementById('edit_category').value=p.category;
    document.getElementById('edit_image').value=p.image;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
<?php include 'layout_bottom.php'; ?>
