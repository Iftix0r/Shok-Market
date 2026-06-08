<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Shok Market</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div id="app">
        <!-- Header -->
        <header>
            <div class="user-info">
                <img id="user-avatar" src="https://ui-avatars.com/api/?name=User&background=random" alt="Avatar" style="display:none;">
                <span id="user-name">Mehmon</span>
            </div>
            <h1>Shok Market</h1>
        </header>

        <!-- Main Content -->
        <main id="main-content">
            <!-- Categories / Products (Home) -->
            <section id="page-home" class="page active">
                <div class="categories">
                    <button class="cat-btn active" data-cat="all">Barchasi</button>
                    <button class="cat-btn" data-cat="electronics">Elektronika</button>
                    <button class="cat-btn" data-cat="clothing">Kiyimlar</button>
                    <button class="cat-btn" data-cat="food">Oziq-ovqat</button>
                </div>
                
                <div class="products-grid" id="products-container">
                    <!-- Products injected by JS -->
                </div>
            </section>

            <!-- Cart -->
            <section id="page-cart" class="page">
                <h2>Savatcha</h2>
                <div id="cart-items" style="margin-top: 15px;">
                    <p class="empty-cart" style="text-align:center; padding: 40px 0; color: var(--hint-color);">
                        <i class="fas fa-shopping-basket" style="font-size: 40px; margin-bottom:10px;"></i><br>
                        Savatchangiz bo'sh
                    </p>
                </div>
                <div class="cart-summary" style="display:none;">
                    <div class="total">Jami: <span id="cart-total">0</span> so'm</div>
                    <button id="btn-checkout" class="btn-primary">Buyurtma berish</button>
                </div>
            </section>

            <!-- Profile -->
            <section id="page-profile" class="page">
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="avatar-large" id="profile-avatar-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                        <h2 id="profile-name">Telegram Foydalanuvchi</h2>
                        <p id="profile-username">@username</p>
                    </div>
                    <div class="profile-details">
                        <div class="detail-item">
                            <i class="fas fa-id-badge"></i>
                            <span id="profile-id">ID: 000000</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-language"></i>
                            <span id="profile-lang">Til: uz</span>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Bottom Navigation -->
        <nav class="bottom-nav">
            <a href="#" class="nav-item active" data-target="page-home">
                <i class="fas fa-home"></i>
                <span>Asosiy</span>
            </a>
            <a href="#" class="nav-item" data-target="page-cart">
                <div class="cart-icon-wrapper">
                    <i class="fas fa-shopping-cart"></i>
                    <span id="cart-badge" class="badge">0</span>
                </div>
                <span>Savatcha</span>
            </a>
            <a href="#" class="nav-item" data-target="page-profile">
                <i class="fas fa-user"></i>
                <span>Profil</span>
            </a>
        </nav>
    </div>

    <!-- Notification Toast -->
    <div id="toast" class="toast"></div>

    <script src="js/app.js"></script>
</body>
</html>
