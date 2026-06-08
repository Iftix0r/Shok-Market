const tg = window.Telegram.WebApp;
tg.expand(); // Expand app to full height

// Initialize User
const user = tg.initDataUnsafe?.user || {
    id: 123456,
    first_name: "Mehmon",
    username: "foydalanuvchi",
    language_code: "uz"
};

// Dummy Products
const products = [
    { id: 1, name: "Aqlli Soat Pro Max", price: 350000, category: "electronics", image: "https://images.unsplash.com/photo-1546868871-7041f2a55e12?auto=format&fit=crop&q=80&w=200&h=200" },
    { id: 2, name: "Simsiz Quloqchin 5.0", price: 150000, category: "electronics", image: "https://images.unsplash.com/photo-1590658268037-6bf12165a8df?auto=format&fit=crop&q=80&w=200&h=200" },
    { id: 3, name: "Zamonaviy Futbolka", price: 85000, category: "clothing", image: "https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&q=80&w=200&h=200" },
    { id: 4, name: "Krossovka Sport", price: 250000, category: "clothing", image: "https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&q=80&w=200&h=200" },
    { id: 5, name: "Tabiiy Asal 1kg", price: 60000, category: "food", image: "https://images.unsplash.com/photo-1587049352847-4d4b127a5655?auto=format&fit=crop&q=80&w=200&h=200" },
    { id: 6, name: "Meva qoqi to'plami", price: 45000, category: "food", image: "https://images.unsplash.com/photo-1622245532560-a29288f61ce3?auto=format&fit=crop&q=80&w=200&h=200" }
];

let cart = {};

// DOM Elements
const productsContainer = document.getElementById('products-container');
const cartItemsContainer = document.getElementById('cart-items');
const cartSummary = document.querySelector('.cart-summary');
const cartTotalEl = document.getElementById('cart-total');
const cartBadge = document.getElementById('cart-badge');
const toastEl = document.getElementById('toast');

// Init setup
function init() {
    // Header setup
    document.getElementById('user-name').innerText = user.first_name;
    
    // Profile setup
    document.getElementById('profile-name').innerText = user.first_name + (user.last_name ? ' ' + user.last_name : '');
    document.getElementById('profile-username').innerText = user.username ? '@' + user.username : 'Kiritilmagan';
    document.getElementById('profile-id').innerText = `ID: ${user.id}`;
    document.getElementById('profile-lang').innerText = `Til: ${user.language_code || 'uz'}`;

    renderProducts('all');
    setupNavigation();
    setupCategoryFilter();
}

function renderProducts(category) {
    productsContainer.innerHTML = '';
    const filtered = category === 'all' ? products : products.filter(p => p.category === category);
    
    filtered.forEach(p => {
        const card = document.createElement('div');
        card.className = 'product-card';
        card.innerHTML = `
            <img src="${p.image}" class="product-img" alt="${p.name}">
            <div class="product-title">${p.name}</div>
            <div class="product-price">${p.price.toLocaleString('uz-UZ')} so'm</div>
            <button class="add-to-cart-btn" onclick="addToCart(${p.id})">
                <i class="fas fa-cart-plus"></i> Qo'shish
            </button>
        `;
        productsContainer.appendChild(card);
    });
}

function setupCategoryFilter() {
    const btns = document.querySelectorAll('.cat-btn');
    btns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            btns.forEach(b => b.classList.remove('active'));
            e.target.classList.add('active');
            renderProducts(e.target.dataset.cat);
        });
    });
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

// Cart functionality
window.addToCart = function(productId) {
    if (cart[productId]) {
        cart[productId].quantity += 1;
    } else {
        const product = products.find(p => p.id === productId);
        cart[productId] = { ...product, quantity: 1 };
    }
    updateCartBadge();
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
        cartItemsContainer.innerHTML = `<p class="empty-cart" style="text-align:center; padding: 40px 0; color: var(--hint-color);"><i class="fas fa-shopping-basket" style="font-size: 40px; margin-bottom:10px;"></i><br>Savatchangiz bo'sh</p>`;
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
            <div class="cart-item-info">
                <div class="cart-item-title">${item.name}</div>
                <div class="cart-item-price">${(item.price * item.quantity).toLocaleString('uz-UZ')} so'm</div>
            </div>
            <div class="cart-qty-controls">
                <button class="qty-btn" onclick="updateQty(${item.id}, -1)">-</button>
                <span>${item.quantity}</span>
                <button class="qty-btn" onclick="updateQty(${item.id}, 1)">+</button>
            </div>
        `;
        cartItemsContainer.appendChild(div);
    });

    cartTotalEl.innerText = total.toLocaleString('uz-UZ');
    cartSummary.style.display = 'block';

    // Update TG Main Button
    tg.MainButton.text = `BUYURTMA BERISH (${total.toLocaleString('uz-UZ')} so'm)`;
    tg.MainButton.color = tg.themeParams.button_color || '#50a8eb';
    tg.MainButton.textColor = tg.themeParams.button_text_color || '#ffffff';
    tg.MainButton.show();
}

function showToast(message) {
    toastEl.innerText = message;
    toastEl.classList.add('show');
    setTimeout(() => {
        toastEl.classList.remove('show');
    }, 2000);
}

// Handle TG Main Button click (Checkout)
tg.onEvent('mainButtonClicked', function() {
    processCheckout();
});

// Also bind custom checkout button
document.getElementById('btn-checkout')?.addEventListener('click', () => {
    processCheckout();
});

function processCheckout() {
    const cartArray = Object.values(cart);
    if(cartArray.length === 0) return;

    tg.MainButton.showProgress();

    fetch('api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            user: user,
            cart: cartArray
        })
    })
    .then(response => response.json())
    .then(data => {
        tg.MainButton.hideProgress();
        if(data.status === 'success') {
            tg.showAlert("✅ Buyurtmangiz muvaffaqiyatli qabul qilindi!", () => {
                cart = {}; // savatchani tozalash
                tg.close(); // web appni yopish
            });
        } else {
            tg.showAlert("❌ Xatolik yuz berdi: " + data.message);
        }
    })
    .catch(error => {
        tg.MainButton.hideProgress();
        // Backend ulanmagan bo'lsa local testing uchun
        if (!window.location.protocol.includes('http')) {
             tg.showAlert("✅ Buyurtmangiz qabul qilindi! (Test rejimi)", () => {
                tg.close();
            });
        } else {
             tg.showAlert("Ulanishda xatolik yuz berdi.");
        }
    });
}

// Initialize the app
init();
