<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }
require_once '../db.php';

// Rasm yuklash funksiyasi
function uploadImage($file) {
    if (empty($file['name'])) return null;
    $allowed = ['jpg','jpeg','png','webp','gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return ['error' => 'Faqat JPG, PNG, WEBP, GIF ruxsat etiladi'];
    if ($file['size'] > 5 * 1024 * 1024) return ['error' => "Rasm 5MB dan kichik bo'lishi kerak"];
    $uploadDir = '../uploads/';
    $filename = uniqid('prod_', true) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return '/uploads/' . $filename;
    }
    return ['error' => "Rasm yuklanmadi"];
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $imageUrl = '';
        if (!empty($_FILES['image_file']['name'])) {
            $result = uploadImage($_FILES['image_file']);
            if (is_array($result)) { $error = $result['error']; }
            else { $imageUrl = $result; }
        } elseif (!empty($_POST['image_url'])) {
            $imageUrl = trim($_POST['image_url']);
        }
        if (!$error) {
            if (empty($_POST['name']) || empty($_POST['price']) || empty($_POST['unit']) || empty($_POST['category'])) {
                $error = "Barcha maydonlarni to'ldiring!";
            } elseif (empty($imageUrl)) {
                $error = "Rasm yuklang yoki URL kiriting!";
            } else {
                $pdo->prepare("INSERT INTO products (name,price,unit,category,image,status) VALUES(?,?,?,?,?,1)")
                    ->execute([$_POST['name'], $_POST['price'], $_POST['unit'], $_POST['category'], $imageUrl]);
                $success = "Mahsulot muvaffaqiyatli qo'shildi!";
            }
        }
    } elseif (isset($_POST['edit_product'])) {
        $imageUrl = $_POST['old_image'];
        if (!empty($_FILES['edit_image_file']['name'])) {
            $result = uploadImage($_FILES['edit_image_file']);
            if (is_array($result)) { $error = $result['error']; }
            else {
                // Eski faylni o'chirish (agar server fayli bo'lsa)
                if (strpos($_POST['old_image'], '/uploads/') === 0) {
                    @unlink('../' . ltrim($_POST['old_image'], '/'));
                }
                $imageUrl = $result;
            }
        } elseif (!empty($_POST['image_url'])) {
            $imageUrl = trim($_POST['image_url']);
        }
        if (!$error) {
            $pdo->prepare("UPDATE products SET name=?,price=?,unit=?,category=?,image=? WHERE id=?")
                ->execute([$_POST['name'], $_POST['price'], $_POST['unit'], $_POST['category'], $imageUrl, $_POST['product_id']]);
            $success = "Mahsulot yangilandi!";
        }
    } elseif (isset($_POST['toggle_status'])) {
        $p = $pdo->prepare("SELECT status FROM products WHERE id=?")->execute([$_POST['product_id']]);
        $cur = $pdo->prepare("SELECT status FROM products WHERE id=?");
        $cur->execute([$_POST['product_id']]);
        $newStatus = $cur->fetchColumn() == 1 ? 0 : 1;
        $pdo->prepare("UPDATE products SET status=? WHERE id=?")->execute([$newStatus, $_POST['product_id']]);
        $success = "Holat o'zgartirildi!";
    } elseif (isset($_POST['delete_product'])) {
        // Rasmni o'chirish
        $img = $pdo->prepare("SELECT image FROM products WHERE id=?");
        $img->execute([$_POST['product_id']]);
        $imgPath = $img->fetchColumn();
        if ($imgPath && strpos($imgPath, '/uploads/') === 0) {
            @unlink('../' . ltrim($imgPath, '/'));
        }
        $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$_POST['product_id']]);
        $success = "Mahsulot o'chirildi!";
    }
    if (!$error) { header('Location: products.php' . ($success ? '?msg='.urlencode($success) : '')); exit; }
}

if (isset($_GET['msg'])) $success = htmlspecialchars($_GET['msg']);

$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$where = "WHERE 1=1";
if ($filter === 'active') $where .= " AND status=1";
elseif ($filter === 'inactive') $where .= " AND status=0";
if ($search) $where .= " AND name LIKE " . $pdo->quote('%' . $search . '%');
$products = $pdo->query("SELECT * FROM products $where ORDER BY id DESC")->fetchAll();

$activeCount = $pdo->query("SELECT COUNT(*) FROM products WHERE status=1")->fetchColumn();
$inactiveCount = $pdo->query("SELECT COUNT(*) FROM products WHERE status=0")->fetchColumn();

$cats = ['fruits'=>'🍎 Mevalar','meat'=>'🥩 Go\'sht','bakery'=>'🍞 Non','drinks'=>'🥤 Ichimlik','dairy'=>'🧀 Sut mahsulotlari','vegetables'=>'🥦 Sabzavotlar'];
include 'layout_top.php';
?>
<script>document.getElementById('pageTitle').innerText = 'Mahsulotlar';</script>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert"><?= $success ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Header -->
<div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-4">
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <a href="products.php" class="btn btn-sm rounded-pill fw-bold px-4 <?= $filter==='all'?'btn-dark':'btn-outline-secondary' ?>">Barchasi <span class="badge bg-secondary ms-1"><?= $activeCount+$inactiveCount ?></span></a>
        <a href="?filter=active<?= $search?'&search='.urlencode($search):'' ?>" class="btn btn-sm rounded-pill fw-bold px-4 <?= $filter==='active'?'btn-success':'btn-outline-secondary' ?>">Faol <span class="badge bg-secondary ms-1"><?= $activeCount ?></span></a>
        <a href="?filter=inactive<?= $search?'&search='.urlencode($search):'' ?>" class="btn btn-sm rounded-pill fw-bold px-4 <?= $filter==='inactive'?'btn-secondary':'btn-outline-secondary' ?>">Yashirilgan <span class="badge bg-secondary ms-1"><?= $inactiveCount ?></span></a>
    </div>
    <div class="d-flex gap-2">
        <form method="get" class="d-flex gap-2">
            <?php if($filter!=='all'): ?><input type="hidden" name="filter" value="<?= $filter ?>"> <?php endif; ?>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Qidirish..." value="<?= htmlspecialchars($search) ?>" style="width:200px;">
            <button class="btn btn-sm btn-outline-secondary"><i class="fas fa-search"></i></button>
        </form>
        <button class="btn btn-warning fw-bold px-4" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus me-2"></i>Yangi mahsulot
        </button>
    </div>
</div>

<?php if(count($products) === 0): ?>
<div class="text-center py-5 text-muted"><i class="fas fa-box-open fa-4x mb-3 d-block"></i><h5>Mahsulotlar topilmadi</h5></div>
<?php endif; ?>

<div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-4">
    <?php foreach($products as $p): ?>
    <div class="col">
        <div class="stat-card p-0 overflow-hidden h-100 d-flex flex-column <?= $p['status']==0 ? 'opacity-60' : '' ?>" style="<?= $p['status']==0 ? 'opacity:0.6;' : '' ?>">
            <div class="text-center p-3 position-relative" style="height:160px; background:#f9fafb;">
                <?php if($p['image']): ?>
                <img src="<?= htmlspecialchars($p['image']) ?>" class="h-100" style="object-fit:contain; max-width:100%;" onerror="this.src='https://placehold.co/200x200?text=Rasm+yo%27q'">
                <?php else: ?>
                <div class="h-100 d-flex align-items-center justify-content-center text-muted"><i class="fas fa-image fa-3x"></i></div>
                <?php endif; ?>
                <?php if($p['status']==0): ?>
                <span class="position-absolute top-0 start-0 badge bg-secondary m-2">Yashirilgan</span>
                <?php endif; ?>
            </div>
            <div class="p-3 flex-grow-1 d-flex flex-column">
                <span class="badge bg-light text-dark border mb-2 align-self-start"><?= $cats[$p['category']] ?? $p['category'] ?></span>
                <h6 class="fw-bold mb-1 flex-grow-1"><?= htmlspecialchars($p['name']) ?></h6>
                <p class="text-success fw-bold mb-0"><?= number_format($p['price'],0,'','  ') ?> so'm <small class="text-muted fw-normal"><?= htmlspecialchars($p['unit']) ?></small></p>
            </div>
            <div class="d-flex border-top">
                <button class="btn btn-sm btn-light w-50 rounded-0 fw-bold py-2 border-end" onclick='editProduct(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)'><i class="fas fa-edit me-1"></i>Tahrir</button>
                <div class="dropdown w-50">
                    <button class="btn btn-sm btn-light w-100 rounded-0 fw-bold py-2 dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li>
                            <form method="post" class="m-0">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <button type="submit" name="toggle_status" class="dropdown-item <?= $p['status']==1?'text-warning':'text-success' ?>">
                                    <i class="fas <?= $p['status']==1?'fa-eye-slash':'fa-eye' ?> me-2"></i><?= $p['status']==1?'Yashirish':'Ko\'rsatish' ?>
                                </button>
                            </form>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="post" class="m-0" onsubmit="return confirm('Mahsulotni butunlay o\'chirasizmi?')">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <button type="submit" name="delete_product" class="dropdown-item text-danger"><i class="fas fa-trash me-2"></i>O'chirish</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ADD Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Yangi mahsulot qo'shish</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label fw-bold small text-muted">NOMI *</label><input type="text" name="name" class="form-control" required></div>
                <div class="row mb-3">
                    <div class="col-6"><label class="form-label fw-bold small text-muted">NARX (so'm) *</label><input type="number" name="price" class="form-control" min="0" required></div>
                    <div class="col-6"><label class="form-label fw-bold small text-muted">BIRLIGI *</label><input type="text" name="unit" class="form-control" value="so'm/kg" required></div>
                </div>
                <div class="mb-3"><label class="form-label fw-bold small text-muted">KATEGORIYA *</label>
                    <select name="category" class="form-select"><?php foreach($cats as $k=>$v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?></select>
                </div>
                <!-- Rasm yuklash -->
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">RASM</label>
                    <div class="border rounded-3 p-3" style="background:#f9fafb;">
                        <div class="mb-2">
                            <label class="small fw-semibold text-muted mb-1 d-block"><i class="fas fa-upload me-1 text-warning"></i> Fayldan yuklash (5MB gacha)</label>
                            <input type="file" name="image_file" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this,'add_preview')">
                        </div>
                        <div class="text-center text-muted small my-2">— yoki —</div>
                        <div>
                            <label class="small fw-semibold text-muted mb-1 d-block"><i class="fas fa-link me-1 text-primary"></i> URL dan kiriting</label>
                            <input type="url" name="image_url" class="form-control form-control-sm" placeholder="https://...">
                        </div>
                        <img id="add_preview" src="" class="mt-3 rounded d-none" style="max-height:120px; max-width:100%; object-fit:contain;">
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Bekor</button><button type="submit" name="add_product" class="btn btn-warning fw-bold px-4"><i class="fas fa-plus me-2"></i>Qo'shish</button></div>
        </form>
    </div>
</div>

<!-- EDIT Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Mahsulotni tahrirlash</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="product_id" id="edit_id">
                <input type="hidden" name="old_image" id="edit_old_image">
                <div class="mb-3"><label class="form-label fw-bold small text-muted">NOMI *</label><input type="text" name="name" id="edit_name" class="form-control" required></div>
                <div class="row mb-3">
                    <div class="col-6"><label class="form-label fw-bold small text-muted">NARX (so'm) *</label><input type="number" name="price" id="edit_price" class="form-control" required></div>
                    <div class="col-6"><label class="form-label fw-bold small text-muted">BIRLIGI *</label><input type="text" name="unit" id="edit_unit" class="form-control" required></div>
                </div>
                <div class="mb-3"><label class="form-label fw-bold small text-muted">KATEGORIYA *</label>
                    <select name="category" id="edit_category" class="form-select"><?php foreach($cats as $k=>$v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?></select>
                </div>
                <!-- Rasm -->
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">RASM</label>
                    <div class="border rounded-3 p-3" style="background:#f9fafb;">
                        <div class="text-center mb-3">
                            <img id="edit_preview" src="" style="max-height:100px; max-width:100%; object-fit:contain; border-radius:10px; background:#fff; border:1px solid #eee;">
                        </div>
                        <div class="mb-2">
                            <label class="small fw-semibold text-muted mb-1 d-block"><i class="fas fa-upload me-1 text-warning"></i> Yangi fayl yuklash</label>
                            <input type="file" name="edit_image_file" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this,'edit_preview')">
                        </div>
                        <div class="text-center text-muted small my-2">— yoki —</div>
                        <div>
                            <label class="small fw-semibold text-muted mb-1 d-block"><i class="fas fa-link me-1 text-primary"></i> URL o'zgartirish</label>
                            <input type="url" name="image_url" id="edit_image_url" class="form-control form-control-sm" placeholder="https://...">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Bekor</button><button type="submit" name="edit_product" class="btn btn-primary fw-bold px-4"><i class="fas fa-save me-2"></i>Saqlash</button></div>
        </form>
    </div>
</div>

<script>
function editProduct(p) {
    document.getElementById('edit_id').value = p.id;
    document.getElementById('edit_name').value = p.name;
    document.getElementById('edit_price').value = p.price;
    document.getElementById('edit_unit').value = p.unit;
    document.getElementById('edit_category').value = p.category;
    document.getElementById('edit_old_image').value = p.image;
    document.getElementById('edit_image_url').value = '';
    const prev = document.getElementById('edit_preview');
    prev.src = p.image || '';
    prev.style.display = p.image ? 'inline-block' : 'none';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function previewImage(input, previewId) {
    const file = input.files[0];
    if (!file) return;
    const img = document.getElementById(previewId);
    img.src = URL.createObjectURL(file);
    img.classList.remove('d-none');
    img.style.display = 'inline-block';
}
</script>
<?php include 'layout_bottom.php'; ?>
