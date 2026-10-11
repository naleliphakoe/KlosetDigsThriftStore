<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_name = !empty($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Guest User';
$user_email = !empty($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : 'guest@example.com';
// Load current product listings so the page can display them, excluding the current uploader.
$products = [];
$categories = [];
$current_user_id = intval($_SESSION['user_id'] ?? 0);
$prod_db = new mysqli('localhost', 'root', '', 'productsdb');
if (!$prod_db->connect_error) {
$query = "SELECT p.*, u.fullname AS seller_name, u.email AS seller_email, COALESCE((SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1), 'images/clothes2.webp') AS display_image FROM products p LEFT JOIN logindb.users u ON p.user_id = u.id";

    $query .= " WHERE p.user_id != " . $current_user_id . " AND COALESCE(p.is_sold, 0) = 0";
    $query .= " ORDER BY p.created_at DESC";
    $res = $prod_db->query($query);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $products[] = $r;
        }
        $res->free();
    }

    $categories = [];
    $categoryRes = $prod_db->query("SELECT DISTINCT category FROM products ORDER BY category ASC");
    if ($categoryRes) {
        while ($row = $categoryRes->fetch_assoc()) {
            $categories[] = $row['category'];
        }
        $categoryRes->free();
    }

    $prod_db->close();

}
?>
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title> Kloset Digs </title>  
<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* BODY */
body {
    font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: linear-gradient(180deg, #eef7ff 0%, #f8f4ee 100%);
    color: #111111;
    margin: 0;
    min-height: 100vh;
    padding-top: 150px;
}

.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    background: #ffffff;
    padding: 20px 24px;
    margin-bottom: 0;
    border-radius: 24px;
    box-shadow: 0 24px 70px rgba(15,44,99,0.08);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    width: 100%;
    z-index: 32;
}

.topbar-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 50px;
    justify-content: center;
    flex: 1;
    min-width: 220px;
    margin: 12px 0;
}

.topbar-nav a {
    padding: 10px 14px;
    color: #0f2c63;
    text-decoration: none;
    border-radius: 999px;
    background: #ffffff;
    font-weight: 600;
    transition: transform 0.2s ease, background 0.2s ease;
}

.topbar-nav.nav-below {
    width: 100%;
    position: fixed;
    top: 72px;
    left: 0;
    right: 0;
    margin: 15px 0;
    padding: 8px 24px;
    background: white;
    border-bottom: 1px solid #f8f8f8;
    display: flex;
    justify-content: center;
    z-index: 31;
}

.topbar-nav a:hover {
    background: #d3e7ff;
    transform: translateY(-1px);
}

.banner {
    position: relative;
    overflow: hidden;
    min-height: 280px;
    border-radius: 40px;
    background: linear-gradient(90deg, #12228f 0%, #344bab 18%, rgba(133, 152, 210, 0.95) 55%, rgba(251, 252, 253, 0.88) 100%), url("Logo.png") 30px center / 200px auto no-repeat;
    box-shadow: 0 30px 90px rgba(15,44,99,0.15);
    margin: 0 20px 20px 20px;
    padding: 30px 34px 30px;
    color: white;
}


.banner-copy {
    position: relative;
    max-width: 620px;
    z-index: 1;
    margin-top: 0;
}

.banner-copy h2 {
    margin: 0 0 18px;
    font-size: clamp(2rem, 3vw, 3.4rem);
    line-height: 1.02;
    color: white;
}

.banner-copy p {
    margin: 0 0 24px;
    color: rgba(255,255,255,0.92);
    line-height: 1.7;
    max-width: 560px;
    font-size: 1rem;
}

.banner-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
}

.banner-btn {
    padding: 14px 24px;
    border-radius: 999px;
    border: none;
    cursor: pointer;
    font-weight: 700;
    transition: transform 0.2s ease, background 0.2s ease;
}

.banner-btn.primary {
    background: #f6c93b;
    color: #0f2c63;
}

.banner-btn.secondary {
    background: rgba(255,255,255,0.18);
    color: white;
    border: 1px solid rgba(255,255,255,0.35);
}

.banner-btn:hover {
    transform: translateY(-2px);
}

@media (max-width: 900px) {
    .banner {
        padding: 24px 22px 20px;
        min-height: 260px;
        background-size: 160px auto;
        margin: 140px 12px 0 12px;
    }

    .banner-copy h2 {
        font-size: 2.2rem;
    }

    .logo-img { height: 42px; }
    .logo { font-size: 22px; }
    .topbar-nav a { padding: 8px 10px; font-size: 13px; }
    .sidebar { left: 12px; }
    .main { margin-left: 300px; width: calc(100% - 312px); }
}

@media (max-width: 680px) {
    body {
        padding-top: 130px;
    }

    .sidebar {
        position: static;
        width: 100%;
        margin: 0 0 20px 0;
    }

    .main {
        margin-left: 0;
        width: 100%;
        padding: 0 12px 20px 12px;
    }

    .banner {
        margin: 0 12px 20px 12px;
        min-height: 240px;
        background-size: 140px auto;
        padding: 20px 16px;
    }

    .banner-copy h2 {
        font-size: 1.8rem;
    }

    .topbar {
        padding: 14px 12px;
    }

    .topbar-nav.nav-below {
        padding: 6px 12px;
    }

    .topbar-nav a {
        padding: 6px 8px;
        font-size: 11px;
    }
}

.hero-image {
    position: relative;
    border-radius: 24px;
    overflow: hidden;
    min-height: 300px;
}

.hero-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.hero-inner-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    margin-top: 18px;
}

.hero-card {
    position: relative;
    border-radius: 22px;
    overflow: hidden;
    min-height: 140px;
    cursor: pointer;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.hero-card:hover {
    bottom: 16px;
    z-index: 1;
    color: white;
    font-weight: 700;
    font-size: 1rem;
}

.hero-card::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, transparent 40%, rgba(15, 44, 99, 0.8) 100%);
}

.icon-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: none;
    background: #0f2c63;
    color: white;
    padding: 10px 14px;
    border-radius: 999px;
    cursor: pointer;
    font-weight: bold;
}

.icon-btn:hover {
    background: #0c2450;
}

.logo {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-size: 26px;
    font-weight: bold;
    text-decoration: none;
    color: #3a3939;
}

.logo-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #417cd0;
    color: white;
    font-size: 18px;
}

.logo-img {
    height: 48px;
    width: auto;
    object-fit: contain;
    border-radius: 6px;
    vertical-align: middle;
    margin-right: 8px;
}

.topbar .search-container {
    display: flex;
    flex: 1;
    max-width: 420px;
    margin: 0 20px;
}

.topbar-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.language-dropdown {
    position: relative;
}

.language-dropdown .icon-btn {
    min-width: 120px;
}

.language-menu {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    background: #f4f6fb;
    padding: 8px 12px;
    border-radius: 999px;
    border: 1px solid #d7ddea;
    box-shadow: 0 10px 24px rgba(0,0,0,0.12);
    z-index: 30;
}

.language-menu span {
    font-size: 13px;
    font-weight: 700;
    color: #334155;
}

.language-menu a {
    font-size: 13px;
    color: #417cd0;
    text-decoration: none;
    padding: 4px 8px;
    border-radius: 999px;
    background: white;
    border: 1px solid #d7ddea;
}

.language-menu a:hover {
    background: #e2e8f0;
}

.icon-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: none;
    background: #417cd0;
    color: white;
    padding: 10px 14px;
    border-radius: 999px;
    cursor: pointer;
    font-weight: bold;
}

.icon-btn:hover {
    background: #315f99;
}

.profile-panel,
.cart-panel {
    position: absolute;
    right: 20px;
    top: 100%;
    width: 280px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 18px 40px rgba(0,0,0,0.12);
    padding: 16px;
    margin-top: 12px;
    display: none;
    z-index: 20;
}

.profile-panel.visible,
.cart-panel.visible {
    display: block;
}

.profile-panel h4,
.cart-panel h4 {
    margin-bottom: 12px;
    font-size: 16px;
}

.profile-panel p,
.cart-panel p {
    margin-bottom: 10px;
    color: #444;
    line-height: 1.5;
}

.profile-panel a {
    color: #417cd0;
    text-decoration: none;
}

.profile-panel a:hover {
    text-decoration: underline;
}

.search-input {
    flex: 1;
    padding: 10px;
    border-radius: 20px 0 0 20px;
    border: 1px solid #ccc;
}

.search-btn {
    padding: 10px 15px;
    border: none;
    background: #007bff;
    color: white;
    border-radius: 0 20px 20px 0;
    cursor: pointer;
}

.cart {
    font-weight: bold;
    cursor: pointer;
}

/* LAYOUT */
.container {
    display: flex;
    align-items: flex-start;
    gap: 25px;
}

/* SIDEBAR */
.sidebar {
    width: 250px;
    flex-shrink: 0;
    background-color: #9eb3ec;
    padding: 20px;
    border-radius: 12px;
    height: fit-content;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    margin-top: 0;
}

.sidebar h2 {
    text-align: center;
    margin-bottom: 20px;
}

.sidebar a {
    display: block;
    color: white;
    text-decoration: none;
    margin: 8px 0;
    padding: 10px;
    border-radius: 8px;
}

.sidebar a.active-category {
    background-color: #315f99;
    font-weight: bold;
}

.sidebar a:hover {
    background-color: #3e99e9;
}

.filters {
    margin-bottom: 20px;
}

.filters select {
    width: 100%;
    margin-top: 5px;
    margin-bottom: 10px;
    padding: 8px;
}

/* MAIN */
.main {
    flex: 1;
    width: auto;
    margin-left: 25px;
    padding: 0 20px 20px;
}

/* CAROUSEL */
.carousel-container{
    width:100%;
    height:320px;
    overflow:hidden;
    border-radius:15px;
    position:relative;
    margin-bottom: 30px;
}

.carousel{
    display:flex;
    height:100%;
    width:300%;
    animation:slide 20s infinite;
}

.slide{
    width:100%;
    height:100%;
    flex:0 0 100%;
    position:relative;
}

.slide img{
    width:100%;
    height:100%;
    object-fit:contain;
    display:block;
    background: #f8f8f8;
    opacity: 1;
    visibility: visible;
    transition: opacity 0.25s ease, visibility 0s linear 0.25s;
}

.slide-text{
    position:absolute;
    bottom:20px;
    left:20px;
    background:rgba(0,0,0,0.5);
    color:white;
    padding:15px;
    border-radius:10px;
}

@keyframes slide{

    0%, 25%{
    transform:translateX(0%);
    }
    
    33%, 58%{
    transform:translateX(-100%);
    }
    
    66%, 91%{
    transform:translateX(-200%);
    }
    
    100%{
    transform:translateX(0);}
    }


/* CARDS */
.card {
    flex: 0 0 220px;
    background: white;
    border-radius: 15px;
    padding: 10px;
    scroll-snap-align: start;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    text-align: center;
    gap: 10px;
}

.card img {
    width: 100%;
    border-radius: 10px;
}

.card h3 {
    margin: 10px 0 5px;
}

.card p {
    color: #007bff;
    font-weight: bold;
}

/* ADD TO CART BUTTON */
.add-to-cart {
    margin-top: 10px;
    padding: 8px;
    width: 100%;
    border: none;
    background: #417cd0;
    color: white;
    border-radius: 8px;
    cursor: pointer;
}

.add-to-cart:hover {
    background: #3163a0;
}
.cart-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    border-bottom: 1px solid #ddd;
    gap: 10px;
}

.cart-item button {
    background: crimson;
    color: white;
    border: none;
    padding: 6px 10px;
    border-radius: 6px;
    cursor: pointer;
}

.cart-item button:hover {
    background: #3163a0;
}
.browse-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    background: white;
    padding: 20px;
    border-radius: 16px;
    margin: 20px 0;
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
}

.browse-header h2 {
    margin-bottom: 8px;
}

.browse-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(180px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

/* Responsive product grid */
@media (max-width: 1100px) {
    .products-grid { grid-template-columns: repeat(3, minmax(170px, 1fr)); }
}
@media (max-width: 900px) {
    .products-grid { grid-template-columns: repeat(2, minmax(160px, 1fr)); }
}
@media (max-width: 560px) {
    .products-grid { grid-template-columns: repeat(1, minmax(0, 1fr)); }
}


.product-card {
    background: white;
    border-radius: 18px;
    box-shadow: 0 10px 28px rgba(0,0,0,0.08);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.product-card img {
    width: 100%;
    height: 140px;
    object-fit: cover;
}

.product-card-content {
    padding: 12px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.product-card h3 {
    font-size: 15px;
    margin: 0;
    color: #1f2937;
    line-height: 1.3;
}

.product-meta {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    color: #475569;
    font-size: 12px;
    flex-wrap: wrap;
}

.product-price {
    font-size: 16px;
    font-weight: bold;
    color: #1d4ed8;
}

.product-card p {
    margin: 0;
    color: #475569;
    line-height: 1.4;
    font-size: 12px;
}

.button-row {
    margin-top: auto;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.button-row button,
.view-product,
.add-to-cart {
    flex: 1;
    min-width: 130px;
    padding: 12px 16px;
    height: 46px;
    border: none;
    border-radius: 8px;
    color: white;
    cursor: pointer;
    text-align: center;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.view-product {
    background: #417cd0;
}

.add-to-cart {
    background: #518ee3;
}

.view-product:hover {
    background: #315f99;
}

.add-to-cart:hover {
    background: #5986d5;
}

.product-modal {
    position: fixed;
    inset: 0;
    display: none;
    justify-content: center;
    align-items: center;
    background: rgba(0,0,0,0.45);
    padding: 20px;
    z-index: 50;
}

.product-modal.open {
    display: flex;
}

.modal-panel {
    background: white;
    border-radius: 24px;
    overflow: hidden;
    width: min(960px, 100%);
    max-height: 90vh;
    display: grid;
    grid-template-columns: 1.2fr 1fr;
}

.modal-image {
    min-height: 100%;
    background-size: cover;
    background-position: center;
}

.modal-details {
    padding: 28px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.modal-details h3 {
    margin: 0;
    font-size: 26px;
    color: #111827;
}

.modal-meta,
.modal-description {
    color: #475569;
    font-size: 14px;
}

.modal-description {
    line-height: 1.8;
}

.modal-close {
    position: absolute;
    top: 20px;
    right: 20px;
    border: none;
    background: rgba(255,255,255,0.9);
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 24px;
    color: #1f2937;
    line-height: 0;
}

.product-modal.open {
    display: flex;
}

.modal-panel {
    background: white;
    border-radius: 24px;
    overflow: hidden;
    width: min(960px, 100%);
    max-height: 90vh;
    display: grid;
    grid-template-columns: 1.2fr 1fr;
}

.modal-image {
    min-height: 100%;
    background-size: cover;
    background-position: center;
}

.modal-details {
    padding: 28px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.modal-details h3 {
    margin: 0;
    font-size: 26px;
    color: #111827;
}

.modal-meta,
.modal-time,
.modal-seller {
    color: #475569;
    font-size: 14px;
}

.modal-description {
    color: #374151;
    line-height: 1.8;
}

.modal-close {
    position: absolute;
    top: 20px;
    right: 20px;
    border: none;
    background: rgba(255,255,255,0.9);
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 24px;
    color: #1f2937;
    line-height: 0;
}

@media (max-width: 900px) {
    .modal-panel {
        grid-template-columns: 1fr;
        max-height: 100vh;
    }

    .modal-image {
        min-height: 250px;
    }
}

/* Feature grid */
.feature-grid {
    margin-top: 32px;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 15px;
    padding: 0 20px;
}

.feature-card {
    background: #ffffff;
    border: 1px solid rgba(15, 44, 99, 0.08);
    border-radius: 24px;
    padding: 22px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 20px 50px rgba(15, 44, 99, 0.05);
    cursor: pointer;
    transition: transform 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-4px);
}

.feature-icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    background: #eef5ff;
    display: grid;
    place-items: center;
    color: #2c4b8a;
    flex-shrink: 0;
}

.feature-icon svg {
    width: 24px;
    height: 24px;
    fill: currentColor;
}

.feature-text h3 {
    margin: 0 0 6px;
    font-size: 1rem;
}

.feature-text p {
    margin: 0;
    color: #5b6478;
    font-size: 0.95rem;
    line-height: 1.6;
}

 /* 🔻 FOOTER */
 .footer {
     background:white ;
    color: #222;
    margin-top: 20px;
    padding: 30px 20px 10px;
    border-radius: 12px;
}

/* Columns */
.footer-columns {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
}

/* Each section */
.footer-section {
    flex: 1;
    min-width: 200px;
}

.footer-section h3 {
    margin-bottom: 10px;
}

.footer-section p {
    margin: 5px 0;
    font-size: 14px;
    color: #ccc;
}

.footer-section a {
    display: inline-block;
    margin: 5px 0;
    font-size: 14px;
    color: #ccc;
    text-decoration: none;
}
.footer-section p:hover {
    color: rgb(96, 156, 229);
}
/* Bottom slogan */
.footer-bottom {
    text-align: center;
    margin-top: 20px;
    padding-top: 10px;
    border-top: 1px solid #444;
    font-weight: bold;
    letter-spacing: 2px;
}
/* RESPONSIVE */
@media (max-width: 768px) {
    .container {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
    }

    .topbar {
        flex-direction: column;
        gap: 10px;
    }
    .topbar .search-container {
        width: 100%;
        margin: 0;
    }

    .banner {
        padding: 26px 22px 24px;
        min-height: 320px;
    }

    .banner-copy {
        margin-top: 60px;
    }

    .hero-image {
        min-height: 220px;
    }

    .hero-inner-grid {
        grid-template-columns: 1fr;
    }

    .footer-columns {
        flex-direction: column;
    }
}

</style>
</head>
<body>

<!-- 🔝 TOP BAR -->
<div class="topbar">
    <a href="#" class="logo">
        <img src="Logo.png" alt="KlosetDigs" class="logo-img">
        <span>KlosetDigs</span>
    </a>

    <div class="search-container">
        <input type="text" placeholder="Search..." class="search-input">
        <button class="search-btn"></button>
    </div>

        <div class="topbar-actions">
        <button class="icon-btn" id="chat-topbar-btn" type="button" aria-label="Chat" onclick="openTopbarChat()">
            💬
        </button>
        <button class="icon-btn" id="cart-button" type="button" aria-label="Cart">🛍️ <span id="cart-count">0</span></button>


       
        <button class="icon-btn" id="profile-button" type="button" aria-label="Profile">🧑‍💼</button>

    </div>

    <div class="cart-panel" id="cart-panel">
        <h4>Your Cart</h4>
        <p id="cart-message">Your cart is empty.</p>
        <button class="icon-btn" id="view-cart-button" onclick="window.location.href='viewCart.php'" type="button">View Cart</button>
       
    </div>

    <div class="profile-panel" id="profile-panel">
        <h4>Your Profile</h4>
        <p><strong>Name:</strong> <?= $user_name ?></p>
        <p><strong>Email:</strong> <?= $user_email ?></p>
        <p><strong>Status:</strong> Signed in</p>
        <a href="logout.php">Logout</a>
    </div>
</div>

<nav class="topbar-nav nav-below">
    <a href="Thrift.php">Home</a>
    <a href="Thrift.php">Shop</a>
    <a href="Selling.php">Sell</a>
    <a href="Support.html">Blog</a>
    <a href="Support.html">About</a>
    <a href="Support.html">Contact</a>
</nav>

<div class="banner">
    <div class="banner-copy">
        <small>Welcome to Kloset Digs</small>
        <h2>Discover Premium Thrift Fashion & Timeless Finds</h2>
        <p>Shop curated secondhand clothing, accessories, and home pieces in a sophisticated marketplace tailored for modern and vintage style seekers.</p>

    </div>
</div>

    <div class="container">

    <!-- SIDEBAR -->
    <aside class="sidebar">


        <h2>Categories</h2>
        <a href="#" class="category-link active-category" data-category="all">All</a>
        <?php foreach ($categories as $category): ?>
            <a href="#" class="category-link" data-category="<?= htmlspecialchars($category, ENT_QUOTES) ?>"><?= htmlspecialchars($category) ?></a>
        <?php endforeach; ?>
        <br><br>

       
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main">

        <section class="products-panel">
                <?php if (empty($products)): ?>
                    <p>No products listed yet. Use the form to add your first item.</p>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <article class="product-card" data-id="<?= intval($product['id']) ?>" data-category="<?= htmlspecialchars($product['category'] ?? 'Other', ENT_QUOTES) ?>" data-name="<?= htmlspecialchars($product['name'], ENT_QUOTES) ?>" data-image="<?= htmlspecialchars($product['display_image'] ?? 'images/clothes2.webp', ENT_QUOTES) ?>" data-price="<?= number_format((float)$product['price'], 2) ?>" data-seller="<?= htmlspecialchars($product['seller_name'] ?: 'Unknown Seller', ENT_QUOTES) ?>" data-brand="<?= htmlspecialchars($product['brand'], ENT_QUOTES) ?>" data-location="<?= htmlspecialchars($product['location'], ENT_QUOTES) ?>" data-size="<?= htmlspecialchars($product['size'] ?? '', ENT_QUOTES) ?>" data-gender="<?= htmlspecialchars($product['gender'] ?? '', ENT_QUOTES) ?>" data-description="<?= str_replace(["\r","\n"], ' ', htmlspecialchars($product['description'], ENT_QUOTES)) ?>" data-buyer-notes="<?= str_replace(["\r","\n"], ' ', htmlspecialchars($product['buyer_notes'] ?: 'No additional notes provided.', ENT_QUOTES)) ?>" onclick="openProductModal(this)">
                                <img src="<?= htmlspecialchars($product['display_image'] ?? 'images/clothes2.webp') ?>" alt="<?= htmlspecialchars($product['name']) ?>" />
                                <div class="product-card-content">
                                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                                    <div class="product-meta">
                                        <span class="product-price">R<?= number_format((float)$product['price'], 2) ?></span>
                                        <span class="seller"><small><?= htmlspecialchars($product['seller_name'] ?: 'Unknown Seller') ?></small></span>
                                    </div>
                                    <div class="product-meta"><small><?= htmlspecialchars($product['category'] ?? 'Other') ?></small></div>
                                    <div class="button-row">
        <button class="view-product" type="button" onclick="event.stopPropagation(); startSellerChat(<?= intval($product['user_id'] ?? 0) ?>, '<?= htmlspecialchars($product['seller_name'] ?: 'Unknown Seller', ENT_QUOTES) ?>', '<?= htmlspecialchars($product['name'], ENT_QUOTES) ?>')">Chat</button>
                                        <button class="add-to-cart" type="button" onclick="event.stopPropagation(); addToCart(<?= intval($product['id']) ?>, '<?= htmlspecialchars(addslashes($product['name'])) ?>', <?= floatval($product['price']) ?>)">Add to Cart</button>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <div class="product-modal" id="product-modal">
                <div class="modal-panel">
                    <div class="modal-image" id="modal-image"></div>
                    <div class="modal-details">
                        <button class="modal-close" id="modal-close" type="button">×</button>
                        <h3 id="modal-name"></h3>
                        <div class="modal-meta" id="modal-price"></div>
                        <div class="modal-meta" id="modal-seller"></div>
                        <div class="modal-meta" id="modal-brand"></div>
                        <div class="modal-meta" id="modal-location"></div>
                        <div class="modal-meta" id="modal-gender"></div>
                        <div class="modal-meta" id="modal-size"></div>
                        <div class="modal-meta" id="modal-buyer-notes"></div>
                        <div class="modal-description" id="modal-description"></div>
                        <button class="add-to-cart" type="button" id="modal-add-cart">Add to Cart</button>
                    </div>
                </div>
            </div>
    </main>
    </div>
    <!-- Feature grid (style/layout copied from Homepage.html) -->
    <section class="feature-grid" aria-label="Features">
        <div class="chat-bubble-area" aria-label="Chat bubble" style="padding: 0 20px; margin: 0 0 18px;">
            <div class="chat-bubble" style="max-width: 920px; margin: 0 auto; background: #ffffff; border: 1px solid rgba(15, 44, 99, 0.08); border-radius: 24px; padding: 18px 22px; box-shadow: 0 20px 50px rgba(15, 44, 99, 0.05); display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <strong style="color:#0f2c63; font-size:1rem;">Need help?</strong>
                    <span style="color:#5b6478; font-size:0.95rem;">Message us anytime — our support team replies via chat.</span>
                </div>
<button type="button" style="background:#0f2c63; color:#fff; border:none; border-radius:999px; padding:12px 18px; font-weight:700; cursor:pointer;" onclick="window.location.href='message.php'">Chat</button>
            </div>
        </div>


       <article class="feature-card" data-auth="true"
         onclick="window.location.href='Buyers.html#delivery'"
         role="link" tabindex="0">

    <div class="feature-icon">
        <svg viewBox="0 0 24 24">
            <path d="M4 6h16v2H4zm0 5h12v2H4zm0 5h8v2H4z"/>
        </svg>
    </div>

    <div class="feature-text">
        <h3>Fast Delivery</h3>
        <p>Quick shipping options to get your vintage finds delivered without delay.</p>
    </div>

</article>


<!-- Order Tracking -->
<article class="feature-card" data-auth="true"
         onclick="window.location.href='OrderTracking.php'"
         role="link" tabindex="0">

    <div class="feature-icon">
        <svg viewBox="0 0 24 24">
            <path d="M12 4v4m0 4v8m-4-4h8"/>
        </svg>
    </div>

    <div class="feature-text">
        <h3>Order Tracking</h3>
        <p>Stay updated with real-time shipping status from checkout to delivery.</p>
    </div>

</article>


<!-- Refunds & Returns -->
<article class="feature-card" data-auth="true"
         onclick="window.location.href='Buyers.html#returns'"
         role="link" tabindex="0">

    <div class="feature-icon">
        <svg viewBox="0 0 24 24">
            <path d="M5 7h14v14H5z"/>
        </svg>
    </div>

    <div class="feature-text">
        <h3>Easy Returns</h3>
        <p>Learn about our refund and return process for items bought through Kloset Digs.</p>
    </div>

</article>
    </section>

    <div class="footer">

    <div class="footer-columns">

        <!-- SELLERS -->
        <div class="footer-section">
            <h3>SELLERS</h3>
            <p><a href="Selling.php">Sell your products</a></p>
            <p><a href="SellerHelp.html">Seller help center</a></p>
        </div>

        <!-- BUYERS -->
        <div class="footer-section">
            <h3>BUYERS</h3>
            <p><a href="Thrift.php">Shop</a></p>
            <p><a href="Buyers.html">How buying works</a></p>
            <p><a href="Buyers.html">Delivery & shipping</a></p>
            <p><a href="Buyers.html">Refunds & returns</a></p>
        </div>

        <!-- SUPPORT -->
        <div class="footer-section">
            <h3>SUPPORT</h3>
            <p><a href="Support.html">Terms of service</a></p>
            <p><a href="Support.html">Privacy policy</a></p>
            <p><a href="Support.html">Help centre</a></p>
            <p><a href="Support.html">Selling & pricing guide</a></p>

        </div>
    </div>

    <div class="footer-bottom">
        BUY. SELL. CHAT.
    </div>
</div>

<script>
    // Per-user cart key, injected by PHP from the logged-in session.
    // Must match the key used in viewCart.php / address.php / checkout
    // so a single account's cart never mixes with another account's.
    const CART_KEY = <?php echo json_encode('thrift-cart-' . $current_user_id); ?>;

    function addToCart(productId, productName, price) {
        const cart = JSON.parse(localStorage.getItem(CART_KEY) || '[]');
        const itemIndex = cart.findIndex(item => item.id === productId);
        if (itemIndex >= 0) {
            cart[itemIndex].quantity += 1;
        } else {
            cart.push({ id: productId, name: productName, price: price, quantity: 1 });
        }
        localStorage.setItem(CART_KEY, JSON.stringify(cart));
        updateCartCount();
        alert(productName + ' added to cart');
    }
    
    function updateCartCount() {
        const cart = JSON.parse(localStorage.getItem(CART_KEY) || '[]');
        const count = cart.reduce((sum, item) => sum + item.quantity, 0);
        document.getElementById('cart-count').textContent = count;
        document.getElementById('cart-message').textContent = count > 0 ? 'You have ' + count + ' item(s) in your cart.' : 'Your cart is empty.';
    }

    function openProductModal(card) {
        const modal = document.getElementById('product-modal');
        const modalImage = document.getElementById('modal-image');
        const modalName = document.getElementById('modal-name');
        const modalPrice = document.getElementById('modal-price');
        const modalSeller = document.getElementById('modal-seller');
        const modalBrand = document.getElementById('modal-brand');
        const modalLocation = document.getElementById('modal-location');
        const modalGender = document.getElementById('modal-gender');
        const modalSize = document.getElementById('modal-size');
        const modalBuyerNotes = document.getElementById('modal-buyer-notes');
        const modalDescription = document.getElementById('modal-description');
        const modalAddCart = document.getElementById('modal-add-cart');

        modalImage.style.backgroundImage = `url('${card.dataset.image}')`;
        modalName.textContent = card.dataset.name;
        modalPrice.textContent = `Price: R${card.dataset.price}`;
        modalSeller.textContent = `Seller: ${card.dataset.seller}`;

        const descriptionText = card.dataset.description || 'No additional description provided.';
        modalDescription.textContent = descriptionText;

        const brandText = card.dataset.brand || '';
        modalBrand.textContent = brandText ? `Brand: ${brandText}` : '';
        modalBrand.style.display = brandText ? '' : 'none';

        const locationText = card.dataset.location || '';
        modalLocation.textContent = locationText ? `Location: ${locationText}` : '';
        modalLocation.style.display = locationText ? '' : 'none';

        const genderText = card.dataset.gender || '';
        modalGender.textContent = genderText ? `Gender: ${genderText}` : '';
        modalGender.style.display = genderText ? '' : 'none';

        const sizeText = card.dataset.size || '';
        modalSize.textContent = sizeText ? `Size: ${sizeText}` : '';
        modalSize.style.display = sizeText ? '' : 'none';

        const buyerNotesText = card.dataset.buyerNotes || '';
        modalBuyerNotes.textContent = buyerNotesText ? `Buyer notes: ${buyerNotesText}` : '';
        modalBuyerNotes.style.display = buyerNotesText ? '' : 'none';

        modalAddCart.onclick = function (event) {
            event.stopPropagation();
            addToCart(parseInt(card.dataset.id, 10), card.dataset.name, parseFloat(card.dataset.price));
        };

        modal.classList.add('open');
    }

    const cartButton = document.getElementById('cart-button');
    const viewCartButton = document.getElementById('view-cart-button');
    const profileButton = document.getElementById('profile-button');
    const cartPanel = document.getElementById('cart-panel');
    const profilePanel = document.getElementById('profile-panel');
    const languageSelect = document.getElementById('language-select');
    const productModal = document.getElementById('product-modal');
    const modalClose = document.getElementById('modal-close');

    cartButton.addEventListener('click', () => {
        cartPanel.classList.toggle('visible');
        profilePanel.classList.remove('visible');
    });

    modalClose.addEventListener('click', () => {
        productModal.classList.remove('open');
    });

    productModal.addEventListener('click', (event) => {
        if (event.target === productModal) {
            productModal.classList.remove('open');
        }
    });

    profileButton.addEventListener('click', () => {
        profilePanel.classList.toggle('visible');
        cartPanel.classList.remove('visible');
    });


    languageSelect.addEventListener('change', () => {
        const selectedLanguage = languageSelect.value;
        localStorage.setItem('selectedLanguage', selectedLanguage);
        alert('Language selected: ' + selectedLanguage + '. Full translation support can be added separately.');
    });

    window.addEventListener('DOMContentLoaded', () => {
        const savedLang = localStorage.getItem('selectedLanguage') || 'en';
        languageSelect.value = savedLang;
        updateCartCount();

        });
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.carousel .slide img').forEach(img => {
            if (img.complete && img.naturalWidth > 0) {
                img.classList.add('loaded');
            } else {
                img.addEventListener('load', () => img.classList.add('loaded'));
                img.addEventListener('error', () => {
                    // Intentionally do nothing: keep the <img> element so alt text can appear.
                });
            }
        });
    });

    function getCardText(card) {
        const text = [
            card.dataset.name,
            card.dataset.brand,
            card.dataset.location,
            card.dataset.size,
            card.dataset.gender,
            card.dataset.category,
            card.dataset.seller,
            card.dataset.description,
            card.dataset.buyerNotes
        ].filter(Boolean).join(' ').toLowerCase();
        return text;
    }

    function applyFilters() {
        const selectedCategoryLink = document.querySelector('.category-link.active-category');
        const selectedCategory = selectedCategoryLink ? (selectedCategoryLink.dataset.category || 'all') : 'all';

        const genderSelect = document.getElementById('gender-filter');
        const sizeSelect = document.getElementById('size-filter');
        const selectedGender = genderSelect ? genderSelect.value : 'All';
        const selectedSize = sizeSelect ? sizeSelect.value : 'All';

        const searchInput = document.querySelector('.search-input');
        const searchValue = searchInput ? searchInput.value.trim().toLowerCase() : '';

        const productCards = document.querySelectorAll('.product-card');
        productCards.forEach(card => {
            const cardCategory = card.dataset.category || 'Other';
            const cardGender = (card.dataset.gender || '').trim();
            const cardSize = (card.dataset.size || '').trim();

            const matchesCategory = (selectedCategory === 'all' || cardCategory.toLowerCase() === selectedCategory.toLowerCase());

            const matchesGender = (selectedGender === 'All' || cardGender.toLowerCase() === selectedGender.toLowerCase());
            const matchesSize = (selectedSize === 'All' || cardSize.toLowerCase() === selectedSize.toLowerCase());

            let matchesSearch = true;
            if (searchValue) {
                matchesSearch = getCardText(card).includes(searchValue);
            }

            card.style.display = (matchesCategory && matchesGender && matchesSize && matchesSearch) ? '' : 'none';
        });
    }

    document.querySelectorAll('.category-link').forEach(link => {
        link.addEventListener('click', event => {
            event.preventDefault();
            document.querySelectorAll('.category-link').forEach(l => l.classList.remove('active-category'));
            link.classList.add('active-category');
            applyFilters();
        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        const genderSelect = document.getElementById('gender-filter');
        const sizeSelect = document.getElementById('size-filter');
        const searchInput = document.querySelector('.search-input');

        if (genderSelect) genderSelect.addEventListener('change', applyFilters);
        if (sizeSelect) sizeSelect.addEventListener('change', applyFilters);
        if (searchInput) {
        // Your search input is currently not wired; enable typing filtering.
            searchInput.removeAttribute('readonly');
            searchInput.addEventListener('input', applyFilters);
        }

        // Apply on load so defaults render correctly.
        applyFilters();
    });


    document.addEventListener('click', (event) => {
        const isInsideTopbar = event.target.closest('.topbar');
        if (!isInsideTopbar) {
            cartPanel.classList.remove('visible');
            profilePanel.classList.remove('visible');
        }
    });

    // Seller chat - opens (or starts) a direct conversation with the seller
    // on message.php, identified by their real numeric user id.
    let lastChatSellerId = null;

    function startSellerChat(sellerId, sellerName, productName) {
        if (!sellerId || sellerId <= 0) {
            alert('Unable to start a chat: seller information is missing.');
            return;
        }
        lastChatSellerId = sellerId;
        localStorage.setItem('lastChatSellerId', sellerId);
        window.location.href = 'message.php?user_id=' + sellerId;
    }

    function openTopbarChat() {
        const savedSellerId = localStorage.getItem('lastChatSellerId');
        if (savedSellerId) {
            window.location.href = 'message.php?user_id=' + savedSellerId;
        } else {
            window.location.href = 'message.php';
        }
    }
</script>

</body>
</html>