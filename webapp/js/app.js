const tg = window.Telegram.WebApp;
tg.expand();
tg.setHeaderColor('#000000');

const user = tg.initDataUnsafe?.user || {
    id: 123456,
    first_name: "Mehmon",
    username: "foydalanuvchi",
    photo_url: ""
};

let products = [];
let cart = {};
let favorites = JSON.parse(localStorage.getItem('shok_favorites') || '{}');

// DOM Elements
const productsContainer = document.getElementById('products-container');
const cartItemsContainer = document.getElementById('cart-items');
const cartSummary = document.querySelector('.cart-summary');
const cartTotalEl = document.getElementById('cart-total');
const cartSubtotalEl = document.getElementById('cart-subtotal');
const cartBadge = document.getElementById('cart-badge');
const toastEl = document.getElementById('toast');
const searchInput = document.getElementById('search-input');

// ─── INIT ────────────────────────────────────────────────────────
function init() {
    // Profil
    const firstName = user.first_name || 'Mehmon';
    document.getElementById('profile-name').innerText = firstName;
    document.getElementById('profile-username').innerText = user.username ? '@' + user.username : 'ID: ' + user.id;

    if (user.photo_url) {
        ['header-avatar-img', 'profile-avatar-img'].forEach(id => {
            const el = document.getElementById(id);
            if (el) { el.src = user.photo_url; el.style.display = 'block'; }
        });
        document.getElementById('header-avatar-icon').style.display = 'none';
        document.getElementById('profile-avatar-icon').style.display = 'none';
    }

    document.getElementById('checkout-name').value = firstName;

    loadProducts();
    setupNavigation();
    setupCategoryFilter();
    startBannerTimer();

    searchInput.addEventListener('input', e => {
        const term = e.target.value.toLowerCase().trim();
        if (!term) { renderProducts(getActiveCategory()); return; }
        renderFilteredProducts(products.filter(p => p.name.toLowerCase().includes(term)));
    });
}

// ─── BANNER TIMER ─────────────────────────────────────────────────
function startBannerTimer() {
    const spans = document.querySelectorAll('.timer span');
    if (!spans.length) return;
    let totalSecs = 2 * 3600 + 50 * 60 + 23;
    setInterval(() => {
        if (totalSecs <= 0) return;
        totalSecs--;
        const h = String(Math.floor(totalSecs / 3600)).padStart(2, '0');
        const m = String(Math.floor((totalSecs % 3600) / 60)).padStart(2, '0');
        const s = String(totalSecs % 60).padStart(2, '0');
        if (spans[1]) spans[1].innerText = h;
        if (spans[2]) spans[2].innerText = m;
        if (spans[3]) spans[3].innerText = s;
    }, 1000);
}

// ─── PRODUCTS ─────────────────────────────────────────────────────
function loadProducts() {
    productsContainer.innerHTML = `<div style="grid-column:span 2;text-align:center;padding:50px 20px;color:var(--text-muted)"><i class="fas fa-spinner fa-spin" style="font-size:32px"></i><p style="margin-top:12px;font-weight:600">Yuklanmoqda...</p></div>`;

    fetch('get_products.php')
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                products = data.data;
                renderProducts('all');
            } else {
                showError(productsContainer, 'Ma\'lumot yuklashda xatolik: ' + data.message);
            }
        })
        .catch(() => showError(productsContainer, 'Internet yoki server bilan ulanishda xatolik.'));
}

function showError(container, msg) {
    container.innerHTML = `<div style="grid-column:span 2;text-align:center;padding:30px;color:var(--text-muted)"><i class="fas fa-wifi" style="font-size:40px;opacity:0.4;margin-bottom:10px"></i><p>${msg}</p><button onclick="loadProducts()" style="margin-top:10px;background:var(--primary);border:none;padding:10px 20px;border-radius:99px;font-weight:700;cursor:pointer">Qaytadan urinish</button></div>`;
}

function getActiveCategory() {
    const active = document.querySelector('.cat-item.active');
    return active ? active.dataset.cat : 'all';
}

function renderProducts(category) {
    const filtered = category === 'all' ? products : products.filter(p => p.category === category);
    renderFilteredProducts(filtered);
}

function renderFilteredProducts(list) {
    productsContainer.innerHTML = '';
    if (!list.length) {
        productsContainer.innerHTML = `<div style="grid-column:span 2;text-align:center;padding:40px;color:var(--text-muted)"><i class="fas fa-search" style="font-size:36px;opacity:0.3;margin-bottom:10px"></i><p style="font-weight:600">Mahsulot topilmadi</p></div>`;
        return;
    }
    list.forEach(p => {
        const qty = cart[p.id]?.quantity || 0;
        const isFav = favorites[p.id] ? true : false;
        const card = document.createElement('div');
        card.className = 'product-card';
        card.innerHTML = `
            <button class="fav-btn" onclick="toggleFav(${p.id}, this)">
                <i class="${isFav ? 'fas' : 'far'} fa-heart" style="color:${isFav ? '#ff3b30' : 'var(--text-muted)'}"></i>
            </button>
            <img src="${p.image}" class="product-img" alt="${p.name}" loading="lazy" onerror="this.src='https://via.placeholder.com/150'">
            <div class="product-price">${Number(p.price).toLocaleString()} <span style="font-size:10px;font-weight:600">${p.unit}</span></div>
            <div class="product-title">${p.name}</div>
            ${qty > 0
                ? `<div class="qty-row">
                    <button class="qty-btn-sm" onclick="changeQtyDirect(${p.id}, -1)">−</button>
                    <span class="qty-label">${qty}</span>
                    <button class="qty-btn-sm" onclick="changeQtyDirect(${p.id}, 1)">+</button>
                   </div>`
                : `<button class="add-btn" onclick="addToCart(${p.id})">Savatchaga <i class="fas fa-plus"></i></button>`
            }
        `;
        productsContainer.appendChild(card);
    });
}

// ─── CATEGORY ─────────────────────────────────────────────────────
function setupCategoryFilter() {
    document.querySelectorAll('.cat-item').forEach(btn => {
        btn.addEventListener('click', e => {
            document.querySelectorAll('.cat-item').forEach(b => b.classList.remove('active'));
            e.currentTarget.classList.add('active');
            renderProducts(e.currentTarget.dataset.cat);
        });
    });
}

window.filterByCat = function(cat) {
    openPage('page-home');
    document.querySelectorAll('.cat-item').forEach(b => b.classList.remove('active'));
    const target = document.querySelector(`.cat-item[data-cat="${cat}"]`);
    if (target) target.classList.add('active');
    renderProducts(cat);
    window.scrollTo(0, 0);
};

// ─── NAVIGATION ───────────────────────────────────────────────────
window.openPage = function(pageId) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    const target = document.getElementById(pageId);
    if (target) target.classList.add('active');
    document.querySelectorAll('.bottom-nav .nav-item').forEach(nav => {
        nav.classList.toggle('active', nav.dataset.target === pageId);
    });
    window.scrollTo(0, 0);
};

function setupNavigation() {
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', e => {
            e.preventDefault();
            const targetId = item.closest('.nav-item').dataset.target;
            openPage(targetId);
            if (targetId === 'page-cart') renderCart();
        });
    });
}

// ─── CART ─────────────────────────────────────────────────────────
window.addToCart = function(productId) {
    const product = products.find(p => p.id == productId);
    if (!product) return;
    if (cart[productId]) {
        cart[productId].quantity += 1;
    } else {
        cart[productId] = { ...product, quantity: 1 };
    }
    updateCartBadge();
    renderProducts(getActiveCategory());
    showToast(`✅ "${product.name}" savatchaga qo'shildi`);
};

window.changeQtyDirect = function(productId, delta) {
    if (!cart[productId]) return;
    cart[productId].quantity += delta;
    if (cart[productId].quantity <= 0) delete cart[productId];
    updateCartBadge();
    renderProducts(getActiveCategory());
    if (document.getElementById('page-cart').classList.contains('active')) renderCart();
};

window.updateQty = function(productId, delta) {
    if (cart[productId]) {
        cart[productId].quantity += delta;
        if (cart[productId].quantity <= 0) delete cart[productId];
    }
    renderCart();
    updateCartBadge();
};

function updateCartBadge() {
    const total = Object.values(cart).reduce((s, i) => s + i.quantity, 0);
    cartBadge.innerText = total;
    cartBadge.classList.toggle('show', total > 0);
}

function renderCart() {
    const items = Object.values(cart);
    cartItemsContainer.innerHTML = '';

    if (!items.length) {
        cartItemsContainer.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-shopping-bag"></i>
                <h3>Savatchangiz bo'sh</h3>
                <p>Katalogdan mahsulotlarni tanlang</p>
                <button onclick="openPage('page-home')" style="margin-top:16px;background:var(--primary);border:none;padding:12px 24px;border-radius:99px;font-weight:800;font-size:14px;cursor:pointer">Xarid qilish</button>
            </div>`;
        cartSummary.style.display = 'none';
        tg.MainButton.hide();
        return;
    }

    let total = 0;
    items.forEach(item => {
        total += item.price * item.quantity;
        const div = document.createElement('div');
        div.className = 'cart-item';
        div.innerHTML = `
            <img src="${item.image}" class="cart-item-img" alt="${item.name}" onerror="this.src='https://via.placeholder.com/70'">
            <div class="cart-info">
                <div class="cart-title">${item.name}</div>
                <div class="cart-price">${Number(item.price).toLocaleString()} so'm</div>
                <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Jami: ${(item.price * item.quantity).toLocaleString()} so'm</div>
            </div>
            <div class="qty-controls">
                <button class="qty-btn" onclick="updateQty(${item.id}, -1)">−</button>
                <span class="qty-val">${item.quantity}</span>
                <button class="qty-btn" onclick="updateQty(${item.id}, 1)">+</button>
            </div>
        `;
        cartItemsContainer.appendChild(div);
    });

    cartSubtotalEl.innerText = total.toLocaleString() + " so'm";
    cartTotalEl.innerText = total.toLocaleString() + " so'm";
    cartSummary.style.display = 'block';

    tg.MainButton.setText(`BUYURTMA BERISH (${total.toLocaleString()} so'm)`);
    tg.MainButton.color = '#FFD500';
    tg.MainButton.textColor = '#000000';
    tg.MainButton.show();
}

// ─── FAVORITES ────────────────────────────────────────────────────
window.toggleFav = function(productId, btn) {
    if (favorites[productId]) {
        delete favorites[productId];
        btn.innerHTML = `<i class="far fa-heart" style="color:var(--text-muted)"></i>`;
    } else {
        const p = products.find(x => x.id == productId);
        favorites[productId] = p;
        btn.innerHTML = `<i class="fas fa-heart" style="color:#ff3b30"></i>`;
        showToast("❤️ Sevimlilar ro'yxatiga qo'shildi");
    }
    localStorage.setItem('shok_favorites', JSON.stringify(favorites));
};

// ─── ORDERS HISTORY ───────────────────────────────────────────────
window.loadOrdersHistory = function() {
    openPage('page-orders');
    const container = document.getElementById('orders-container');
    container.innerHTML = `<div style="text-align:center;padding:50px;color:var(--text-muted)"><i class="fas fa-spinner fa-spin" style="font-size:30px"></i><p style="margin-top:12px;font-weight:600">Yuklanmoqda...</p></div>`;

    fetch('get_orders.php?user_id=' + user.id)
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') renderOrdersHistory(data.data);
            else container.innerHTML = `<p style="color:red;text-align:center;padding:20px">Xatolik: ${data.message}</p>`;
        })
        .catch(() => container.innerHTML = `<p style="text-align:center;color:var(--text-muted);padding:20px">Ulanishda xatolik</p>`);
};

function renderOrdersHistory(orders) {
    const container = document.getElementById('orders-container');
    if (!orders.length) {
        container.innerHTML = `<div style="text-align:center;padding:50px;color:var(--text-muted)"><i class="fas fa-box-open" style="font-size:50px;opacity:0.3;margin-bottom:12px"></i><p style="font-weight:600;font-size:16px">Hali buyurtmalar yo'q</p><p style="font-size:13px">Birinchi buyurtmangizni bering!</p></div>`;
        return;
    }

    const statusMap = {
        'new':       { label: '⏳ Kutilmoqda',      color: '#2563eb' },
        'accepted':  { label: '🔄 Tayyorlanmoqda',  color: '#d97706' },
        'completed': { label: '✅ Yetkazildi',       color: '#16a34a' },
        'cancelled': { label: '❌ Bekor qilindi',    color: '#dc2626' }
    };

    container.innerHTML = orders.map(o => {
        const s = statusMap[o.status] || { label: o.status, color: '#666' };
        const date = new Date(o.created_at).toLocaleDateString('uz-UZ', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        const itemsHtml = o.items.map(item => `
            <div class="order-history-item">
                <img src="${item.image || 'https://via.placeholder.com/40'}" onerror="this.src='https://via.placeholder.com/40'">
                <div style="flex-grow:1">
                    <div style="font-weight:600;font-size:13px">${item.name || 'Noma\'lum'}</div>
                    <div style="color:var(--text-muted);font-size:12px">${item.quantity} × ${Number(item.price).toLocaleString()} so'm</div>
                </div>
                <div style="font-weight:700;font-size:13px">${(item.quantity * item.price).toLocaleString()} so'm</div>
            </div>
        `).join('');

        return `
            <div class="order-history-card">
                <div class="order-history-header">
                    <div>
                        <div style="font-weight:800;font-size:15px">Buyurtma #${o.id}</div>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:2px">${date}</div>
                    </div>
                    <div style="font-weight:800;font-size:13px;color:${s.color}">${s.label}</div>
                </div>
                <div class="order-history-items">${itemsHtml}</div>
                <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--border-color);padding-top:10px;margin-top:4px">
                    <span style="font-weight:700;color:var(--text-muted);font-size:13px">JAMI TO'LOV:</span>
                    <span style="font-size:18px;font-weight:800;color:var(--primary)">${Number(o.total_price).toLocaleString()} so'm</span>
                </div>
            </div>
        `;
    }).join('');
}

// ─── CHECKOUT ─────────────────────────────────────────────────────
function openCheckout() {
    if (!Object.keys(cart).length) return;
    document.getElementById('checkoutModal').classList.add('active');
    tg.MainButton.hide();
}

window.closeCheckout = function() {
    document.getElementById('checkoutModal').classList.remove('active');
    renderCart();
};

tg.onEvent('mainButtonClicked', openCheckout);
document.getElementById('btn-checkout')?.addEventListener('click', openCheckout);

window.submitOrder = function() {
    const name    = document.getElementById('checkout-name').value.trim();
    const phone   = document.getElementById('checkout-phone').value.trim();
    const address = document.getElementById('checkout-address').value.trim();

    if (!name || !phone || !address) {
        tg.showAlert("Iltimos, barcha maydonlarni to'ldiring!");
        return;
    }
    if (!/^\+?[\d\s\-\(\)]{9,15}$/.test(phone)) {
        tg.showAlert("Telefon raqam noto'g'ri formatda! Masalan: +998901234567");
        return;
    }

    const cartArray = Object.values(cart);
    if (!cartArray.length) return;

    const btn = document.querySelector('.checkout-submit');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Yuborilmoqda...';
    btn.disabled = true;
    user.first_name = name;

    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user, cart: cartArray, phone, address })
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            tg.showAlert(`✅ Buyurtmangiz qabul qilindi!\n\n🔖 Buyurtma raqami: #${data.order_id}\n\nTez orada operator siz bilan bog'lanadi.`, () => {
                cart = {};
                closeCheckout();
                renderCart();
                updateCartBadge();
                tg.close();
            });
        } else {
            tg.showAlert("❌ Xatolik: " + data.message);
            btn.innerHTML = 'Tasdiqlash va Buyurtma berish';
            btn.disabled = false;
        }
    })
    .catch(() => {
        tg.showAlert("Internet bilan ulanishda xatolik yuz berdi. Iltimos, qaytadan urinib ko'ring.");
        btn.innerHTML = 'Tasdiqlash va Buyurtma berish';
        btn.disabled = false;
    });
};

// ─── TOAST ────────────────────────────────────────────────────────
function showToast(msg) {
    toastEl.innerText = msg;
    toastEl.classList.add('show');
    clearTimeout(toastEl._timer);
    toastEl._timer = setTimeout(() => toastEl.classList.remove('show'), 2500);
}

// ─── START ────────────────────────────────────────────────────────
init();
