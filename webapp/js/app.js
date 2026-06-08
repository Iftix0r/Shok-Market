const tg = window.Telegram.WebApp;
tg.expand();

const user = tg.initDataUnsafe?.user || {
    id: 123456,
    first_name: "Mehmon",
    username: "foydalanuvchi",
    language_code: "uz",
    photo_url: ""
};

let products = [];
let cart = {};

const productsContainer = document.getElementById('products-container');
const cartItemsContainer = document.getElementById('cart-items');
const cartSummary = document.querySelector('.cart-summary');
const cartTotalEl = document.getElementById('cart-total');
const cartSubtotalEl = document.getElementById('cart-subtotal');
const cartBadge = document.getElementById('cart-badge');
const toastEl = document.getElementById('toast');
const searchInput = document.getElementById('search-input');

function init() {
    // Profil ism va username
    document.getElementById('profile-name').innerText = user.first_name;
    document.getElementById('profile-username').innerText = user.username ? '@' + user.username : '';

    // Profil rasmini o'rnatish
    if (user.photo_url) {
        document.getElementById('header-avatar-img').src = user.photo_url;
        document.getElementById('header-avatar-img').style.display = 'block';
        document.getElementById('header-avatar-icon').style.display = 'none';

        document.getElementById('profile-avatar-img').src = user.photo_url;
        document.getElementById('profile-avatar-img').style.display = 'block';
        document.getElementById('profile-avatar-icon').style.display = 'none';
    }

    // Mahsulotlarni bazadan yuklash
    loadProducts();

    setupNavigation();
    setupCategoryFilter();

    searchInput.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        const filtered = products.filter(p => p.name.toLowerCase().includes(term));
        renderFilteredProducts(filtered);
    });
}

function loadProducts() {
    productsContainer.innerHTML = '<div style="grid-column: span 2; text-align: center; padding: 40px; color: var(--text-muted);"><i class="fas fa-spinner fa-spin" style="font-size:30px;"></i><p style="margin-top:10px;">Yuklanmoqda...</p></div>';
    
    fetch('get_products.php')
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                products = data.data;
                renderProducts('all');
            } else {
                productsContainer.innerHTML = `<p style="grid-column: span 2; color: red;">Xatolik: ${data.message}</p>`;
            }
        })
        .catch(err => {
            productsContainer.innerHTML = `<p style="grid-column: span 2; color: red;">Ulanishda xatolik yuz berdi.</p>`;
        });
}

function renderProducts(category) {
    const filtered = category === 'all' ? products : products.filter(p => p.category === category);
    renderFilteredProducts(filtered);
}

function renderFilteredProducts(productsList) {
    productsContainer.innerHTML = '';
    
    if (productsList.length === 0) {
        productsContainer.innerHTML = '<p style="grid-column: span 2; text-align: center; color: var(--text-muted); padding: 20px;">Mahsulot topilmadi.</p>';
        return;
    }

    productsList.forEach(p => {
        const qtyInCart = cart[p.id] ? cart[p.id].quantity : 0;
        const btnHtml = qtyInCart > 0 
            ? `<button class="add-btn added" onclick="addToCart(${p.id})"><i class="fas fa-check"></i> Savatchada (${qtyInCart})</button>`
            : `<button class="add-btn" onclick="addToCart(${p.id})">Savatchaga <i class="fas fa-plus"></i></button>`;

        const card = document.createElement('div');
        card.className = 'product-card';
        card.innerHTML = `
            <button class="fav-btn"><i class="fas fa-bookmark" style="color:var(--primary)"></i></button>
            <img src="${p.image}" class="product-img" alt="${p.name}">
            <div class="product-price">${Number(p.price).toLocaleString('uz-UZ')} <span style="font-size:10px; font-weight:600">${p.unit}</span></div>
            <div class="product-title">${p.name}</div>
            ${btnHtml}
        `;
        productsContainer.appendChild(card);
    });
}

function setupCategoryFilter() {
    const btns = document.querySelectorAll('.cat-item');
    btns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            btns.forEach(b => b.classList.remove('active'));
            const targetBtn = e.target.closest('.cat-item');
            targetBtn.classList.add('active');
            renderProducts(targetBtn.dataset.cat);
        });
    });
}

window.filterByCat = function(cat) {
    document.querySelectorAll('.bottom-nav .nav-item').forEach(nav => nav.classList.remove('active'));
    document.querySelector('.nav-item[data-target="page-home"]').classList.add('active');
    
    document.querySelectorAll('.page').forEach(page => page.classList.remove('active'));
    document.getElementById('page-home').classList.add('active');

    document.querySelectorAll('.cat-item').forEach(b => b.classList.remove('active'));
    const targetBtn = document.querySelector(`.cat-item[data-cat="${cat}"]`);
    if(targetBtn) targetBtn.classList.add('active');
    
    renderProducts(cat);
    window.scrollTo(0,0);
}

function setupNavigation() {
    const navItems = document.querySelectorAll('.nav-item');
    const pages = document.querySelectorAll('.page');

    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = item.closest('.nav-item').dataset.target;
            
            navItems.forEach(nav => nav.classList.remove('active'));
            item.closest('.nav-item').classList.add('active');

            pages.forEach(page => {
                page.classList.remove('active');
                if(page.id === targetId) {
                    page.classList.add('active');
                }
            });

            if(targetId === 'page-cart') {
                renderCart();
            }
        });
    });
}

window.addToCart = function(productId) {
    if (cart[productId]) {
        cart[productId].quantity += 1;
    } else {
        const product = products.find(p => p.id == productId);
        cart[productId] = { ...product, quantity: 1 };
    }
    updateCartBadge();
    
    // Re-render home list to show 'added' state
    const activeCatBtn = document.querySelector('.cat-item.active');
    renderProducts(activeCatBtn ? activeCatBtn.dataset.cat : 'all');
    
    showToast("Savatchaga qo'shildi!");
};

window.updateQty = function(productId, delta) {
    if (cart[productId]) {
        cart[productId].quantity += delta;
        if (cart[productId].quantity <= 0) {
            delete cart[productId];
        }
        renderCart();
        updateCartBadge();
        
        // Re-render home list if visible
        if(document.getElementById('page-home').classList.contains('active')) {
            const activeCatBtn = document.querySelector('.cat-item.active');
            renderProducts(activeCatBtn ? activeCatBtn.dataset.cat : 'all');
        }
    }
};

function updateCartBadge() {
    const totalItems = Object.values(cart).reduce((sum, item) => sum + item.quantity, 0);
    if (totalItems > 0) {
        cartBadge.innerText = totalItems;
        cartBadge.classList.add('show');
    } else {
        cartBadge.classList.remove('show');
    }
}

function renderCart() {
    cartItemsContainer.innerHTML = '';
    const cartArray = Object.values(cart);
    
    if (cartArray.length === 0) {
        cartItemsContainer.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-shopping-bag"></i>
                <h3>Savatchangiz bo'sh</h3>
                <p>Katalogdan mahsulotlarni tanlang</p>
            </div>`;
        cartSummary.style.display = 'none';
        tg.MainButton.hide();
        return;
    }

    let total = 0;
    cartArray.forEach(item => {
        total += item.price * item.quantity;
        const div = document.createElement('div');
        div.className = 'cart-item';
        div.innerHTML = `
            <img src="${item.image}" class="cart-item-img" alt="${item.name}">
            <div class="cart-info">
                <div class="cart-title">${item.name}</div>
                <div class="cart-price">${Number(item.price).toLocaleString('uz-UZ')} so'm</div>
            </div>
            <div class="qty-controls">
                <button class="qty-btn" onclick="updateQty(${item.id}, -1)">-</button>
                <span class="qty-val">${item.quantity}</span>
                <button class="qty-btn" onclick="updateQty(${item.id}, 1)">+</button>
            </div>
        `;
        cartItemsContainer.appendChild(div);
    });

    cartSubtotalEl.innerText = total.toLocaleString('uz-UZ') + " so'm";
    cartTotalEl.innerText = total.toLocaleString('uz-UZ') + " so'm";
    cartSummary.style.display = 'block';

    tg.MainButton.text = `BUYURTMA BERISH (${total.toLocaleString('uz-UZ')} so'm)`;
    tg.MainButton.color = '#FFD500';
    tg.MainButton.textColor = '#000000';
    tg.MainButton.show();
}

function showToast(message) {
    toastEl.innerText = message;
    toastEl.classList.add('show');
    setTimeout(() => toastEl.classList.remove('show'), 2000);
}

tg.onEvent('mainButtonClicked', function() {
    processCheckout();
});

document.getElementById('btn-checkout')?.addEventListener('click', () => {
    processCheckout();
});

function processCheckout() {
    const cartArray = Object.values(cart);
    if(cartArray.length === 0) return;

    tg.MainButton.showProgress();

    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user: user, cart: cartArray })
    })
    .then(response => response.json())
    .then(data => {
        tg.MainButton.hideProgress();
        if(data.status === 'success') {
            tg.showAlert("✅ Buyurtmangiz qabul qilindi! Buyurtma raqami: #" + data.order_id, () => {
                cart = {}; 
                renderCart();
                updateCartBadge();
                tg.close();
            });
        } else {
            tg.showAlert("❌ Xatolik yuz berdi: " + data.message);
        }
    })
    .catch(error => {
        tg.MainButton.hideProgress();
        if (!window.location.protocol.includes('http')) {
             tg.showAlert("✅ Buyurtma qabul qilindi (Test)", () => {
                 cart = {};
                 renderCart();
                 updateCartBadge();
                 tg.close();
             });
        } else {
             tg.showAlert("Ulanishda xatolik yuz berdi.");
        }
    });
}

init();
