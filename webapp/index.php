<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Shok Market</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts: Outfit for a modern, rounded, vibrant look -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div id="app">
        <!-- Main Content -->
        <main id="main-content">
            
            <!-- Home Page -->
            <section id="page-home" class="page active">
                <!-- Header -->
                <header class="app-header">
                    <div class="logo">
                        <div class="logo-icon"><i class="fas fa-bolt"></i></div>
                        <span>SHOK<br><b style="font-size:16px;">MARKET</b></span>
                    </div>
                    <div class="header-profile" onclick="document.querySelector('[data-target=\'page-profile\']').click()">
                        <img src="" id="header-avatar-img" style="display:none; width:100%; height:100%; border-radius:50%; object-fit:cover;">
                        <i class="fas fa-user" id="header-avatar-icon"></i>
                    </div>
                </header>

                <!-- Search -->
                <div class="search-container">
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="search-input" placeholder="Katalogdan qidirish...">
                        <i class="fas fa-microphone mic-icon"></i>
                    </div>
                </div>

                <!-- Banner Slider -->
                <div class="banner-slider">
                    <div class="banner">
                        <div class="banner-content">
                            <h3>YANGI TAKLIFLAR!</h3>
                            <p>Sariqroq, sharbatli va yangi!<br>Shok Chegirmalar!</p>
                            <div class="timer">
                                <span>00</span>:<span>02</span>:<span>50</span>:<span>23</span>
                            </div>
                            <button class="banner-btn">Xarid qilish</button>
                        </div>
                        <img src="https://images.unsplash.com/photo-1610832958506-aa56368176cf?auto=format&fit=crop&q=80&w=300&h=200" alt="Banner fruits" class="banner-img">
                    </div>
                </div>

                <!-- Categories -->
                <div class="section-title">
                    <h4>Kategoriyalar</h4>
                </div>
                <div class="categories-grid">
                    <div class="cat-item active" data-cat="all">
                        <div class="cat-icon"><i class="fas fa-border-all"></i></div>
                        <span>Barchasi</span>
                    </div>
                    <div class="cat-item" data-cat="fruits">
                        <div class="cat-icon"><i class="fas fa-apple-alt"></i></div>
                        <span>Mevalar &<br>Sabzavotlar</span>
                    </div>
                    <div class="cat-item" data-cat="meat">
                        <div class="cat-icon"><i class="fas fa-drumstick-bite"></i></div>
                        <span>Go'sht &<br>Sut</span>
                    </div>
                    <div class="cat-item" data-cat="bakery">
                        <div class="cat-icon"><i class="fas fa-bread-slice"></i></div>
                        <span>Non &<br>Shirinliklar</span>
                    </div>
                    <div class="cat-item" data-cat="drinks">
                        <div class="cat-icon"><i class="fas fa-tint"></i></div>
                        <span>Ichimliklar</span>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="section-header">
                    <h4>Ommabop Mahsulotlar</h4>
                    <a href="#" class="view-all" onclick="document.querySelector('[data-target=\'page-catalog\']').click()">Barchasi <i class="fas fa-chevron-right"></i></a>
                </div>
                <div class="products-grid" id="products-container">
                    <!-- Products injected by JS -->
                </div>
                <div style="height: 30px;"></div>
            </section>

            <!-- Catalog Page -->
            <section id="page-catalog" class="page">
                <header class="page-header">
                    <h2>Katalog</h2>
                </header>
                <div class="catalog-list">
                    <div class="cat-list-item" onclick="filterByCat('fruits')">
                        <i class="fas fa-apple-alt"></i> Mevalar va Sabzavotlar <i class="fas fa-chevron-right arrow"></i>
                    </div>
                    <div class="cat-list-item" onclick="filterByCat('meat')">
                        <i class="fas fa-drumstick-bite"></i> Go'sht va Sut mahsulotlari <i class="fas fa-chevron-right arrow"></i>
                    </div>
                    <div class="cat-list-item" onclick="filterByCat('bakery')">
                        <i class="fas fa-bread-slice"></i> Non va Shirinliklar <i class="fas fa-chevron-right arrow"></i>
                    </div>
                    <div class="cat-list-item" onclick="filterByCat('drinks')">
                        <i class="fas fa-wine-bottle"></i> Ichimliklar <i class="fas fa-chevron-right arrow"></i>
                    </div>
                </div>
            </section>

            <!-- Cart Page -->
            <section id="page-cart" class="page">
                <header class="page-header">
                    <h2>Savatcha</h2>
                </header>
                <div id="cart-items" class="cart-container">
                    <!-- Cart items -->
                </div>
                <div class="cart-summary" style="display:none;">
                    <div class="summary-row">
                        <span>Mahsulotlar:</span>
                        <span id="cart-subtotal">0 so'm</span>
                    </div>
                    <div class="summary-row total-row">
                        <span>Jami:</span>
                        <span id="cart-total">0 so'm</span>
                    </div>
                    <button id="btn-checkout" class="btn-checkout">Buyurtma berish</button>
                </div>
            </section>

            <!-- Promos Page -->
            <section id="page-promo" class="page">
                <header class="page-header">
                    <h2>Aksiyalar</h2>
                </header>
                <div class="promo-empty">
                    <i class="fas fa-tags"></i>
                    <h3>Shok Chegirmalar</h3>
                    <p>Hozircha aksiyalar mavjud emas</p>
                </div>
            </section>

            <!-- Profile Page -->
            <section id="page-profile" class="page">
                <header class="page-header">
                    <h2>Profil</h2>
                </header>
                <div class="profile-wrap">
                    <div class="profile-card">
                        <div class="avatar-large">
                            <img src="" id="profile-avatar-img" style="display:none; width:100%; height:100%; border-radius:50%; object-fit:cover;">
                            <i class="fas fa-user" id="profile-avatar-icon"></i>
                        </div>
                        <h2 id="profile-name">Mehmon</h2>
                        <p id="profile-username">@username</p>
                    </div>
                    
                    <div class="profile-menu">
                        <div class="menu-item">
                            <div class="menu-icon"><i class="fas fa-history"></i></div>
                            <span>Buyurtmalar tarixi</span>
                            <i class="fas fa-chevron-right arrow"></i>
                        </div>
                        <div class="menu-item">
                            <div class="menu-icon"><i class="fas fa-map-marker-alt"></i></div>
                            <span>Manzillarim</span>
                            <i class="fas fa-chevron-right arrow"></i>
                        </div>
                        <div class="menu-item">
                            <div class="menu-icon"><i class="fas fa-cog"></i></div>
                            <span>Sozlamalar</span>
                            <i class="fas fa-chevron-right arrow"></i>
                        </div>
                        <div class="menu-item">
                            <div class="menu-icon"><i class="fas fa-headset"></i></div>
                            <span>Qo'llab-quvvatlash</span>
                            <i class="fas fa-chevron-right arrow"></i>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Bottom Navigation -->
        <nav class="bottom-nav">
            <a href="#" class="nav-item active" data-target="page-home">
                <i class="fas fa-home"></i>
                <span>Bosh sahifa</span>
            </a>
            <a href="#" class="nav-item" data-target="page-catalog">
                <i class="fas fa-search"></i>
                <span>Katalog</span>
            </a>
            <a href="#" class="nav-item" data-target="page-cart">
                <div class="cart-icon-wrapper">
                    <i class="fas fa-shopping-cart"></i>
                    <span id="cart-badge" class="badge">0</span>
                </div>
                <span>Savatcha</span>
            </a>
            <a href="#" class="nav-item" data-target="page-promo">
                <i class="fas fa-percent"></i>
                <span>Aksiyalar</span>
            </a>
            <a href="#" class="nav-item" data-target="page-profile">
                <i class="fas fa-user"></i>
                <span>Profil</span>
            </a>
        </nav>
    </div>

    <div id="toast" class="toast"></div>

    <script src="js/app.js"></script>
</body>
</html>
