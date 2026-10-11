
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kloset Digs | Index</title>
    <style>
        :root {
            --bg: #f7f6f2;
            --surface: #ffffff;
            --primary: #0f2c63;
            --primary-soft: #d3e7ff;
            --accent: #74b3ff;
            --text: #111111;
            --muted: #5b6478;
            --border: rgba(15, 44, 99, 0.08);
            --shadow: 0 24px 70px rgba(15, 44, 99, 0.08);
            --radius: 24px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(180deg, #eef7ff 0%, #f8f4ee 100%);
            color: var(--text);
            min-height: 100vh;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        img {
            max-width: 100%;
            display: block;
        }

        button {
            font: inherit;
        }

        .page-shell {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
        }

        header {
            background: #ffffff;
            border-radius: 24px;
            padding: 20px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            box-shadow: var(--shadow);
            position: sticky;
            top: 16px;
            z-index: 20;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .brand-logo {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: radial-gradient(circle at top left, #6eb5ff, #1c3f7e);
            display: grid;
            place-items: center;
            color: white;
            font-weight: 800;
            letter-spacing: -0.04em;
            font-size: 1.2rem;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.2);
        }

        .brand-text {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .brand-text h1 {
            margin: 0;
            font-size: 1.25rem;
        }

        .brand-text span {
            color: var(--muted);
            font-size: 0.92rem;
        }

        .search-bar {
            flex: 1;
            max-width: 640px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            border-radius: 999px;
            background: #f5f7fb;
            border: 1px solid var(--border);
            transition: box-shadow 0.25s ease;
            cursor: pointer;
        }

        .search-bar:hover,
        .search-bar:focus-within {
            box-shadow: 0 0 0 4px rgba(116, 179, 255, 0.16);
        }

        .search-bar input {
            border: none;
            background: transparent;
            outline: none;
            width: 100%;
            font-size: 1rem;
            color: var(--text);
            cursor: pointer;
        }

        .search-bar .icon {
            width: 20px;
            height: 20px;
            fill: var(--muted);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .icon-button {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            background: #f6f8ff;
            border: 1px solid var(--border);
            cursor: pointer;
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .icon-button:hover {
            transform: translateY(-2px);
            background: #edf4ff;
        }

        .icon-button svg {
            width: 22px;
            height: 22px;
            fill: #0f2c63;
        }

        nav {
            margin-top: 18px;
            background: #ffffff;
            border-radius: 20px;
            padding: 12px 18px;
            box-shadow: var(--shadow);
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .nav-link {
            padding: 12px 18px;
            border-radius: 999px;
            color: var(--primary);
            font-weight: 600;
            transition: background 0.25s ease, color 0.25s ease;
            cursor: pointer;
        }

        .nav-link:hover {
            background: var(--primary-soft);
            color: var(--primary);
        }

        .hero-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
            margin-top: 28px;
        }

        .category-card {
            position: relative;
            border-radius: 28px;
            overflow: hidden;
            min-height: 240px;
            cursor: pointer;
            box-shadow: var(--shadow);
            display: flex;
            align-items: flex-end;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .category-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 35px 90px rgba(15, 44, 99, 0.14);
        }

        .category-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 40%, rgba(6, 26, 51, 0.76) 100%);
        }

        .category-card img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.35s ease;
        }

        .category-card:hover img {
            transform: scale(1.06);
        }

        .category-label {
            position: relative;
            z-index: 1;
            color: white;
            font-size: 1.25rem;
            font-weight: 700;
            padding: 22px;
        }

        .promo-panel {
            margin-top: 32px;
            padding: 40px;
            border-radius: 28px;
            background: linear-gradient(135deg, rgba(116, 179, 255, 0.14), rgba(255, 249, 238, 0.95));
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            box-shadow: var(--shadow);
        }

        .promo-copy {
            max-width: 720px;
        }

        .promo-copy h2 {
            margin: 0 0 14px;
            font-size: 2rem;
            color: var(--primary);
        }

        .promo-copy p {
            margin: 0;
            color: var(--muted);
            line-height: 1.8;
            font-size: 1rem;
        }

        .promo-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 16px 24px;
            border-radius: 999px;
            background: var(--primary);
            color: white;
            font-weight: 700;
            transition: transform 0.25s ease;
            cursor: pointer;
        }

        .promo-cta:hover {
            transform: translateY(-2px);
        }

        .feature-grid {
            margin-top: 32px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .feature-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 22px;
            display: flex;
            align-items: center;
            gap: 16px;
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
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .section {
            margin-top: 46px;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px;
        }

        .section-header h2 {
            margin: 0;
            font-size: 1.6rem;
        }

        .section-header a {
            color: var(--primary);
            font-weight: 600;
        }

        .product-grid,
        .recent-grid,
        .review-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
        }

        .product-card,
        .recent-card,
        .review-card {
            background: #ffffff;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }

        .product-card:hover,
        .recent-card:hover,
        .review-card:hover {
            transform: translateY(-6px);
        }

        .product-card img,
        .recent-card img {
            width: 100%;
            height: 240px;
            object-fit: cover;
        }

        .product-content,
        .recent-content {
            padding: 22px;
        }

        .product-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            color: var(--muted);
            font-size: 0.95rem;
        }

        .product-title {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--primary);
        }

        .product-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .price {
            font-size: 1.2rem;
            font-weight: 800;
            color: #12254c;
        }

        .add-button {
            padding: 12px 18px;
            border-radius: 18px;
            border: none;
            background: #0f2c63;
            color: white;
            cursor: pointer;
            transition: background 0.25s ease;
        }

        .add-button:hover {
            background: #1d3f88;
        }

        .pill-list {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
        }

        .pill {
            padding: 12px 18px;
            border-radius: 999px;
            background: #f7f6f2;
            color: var(--primary);
            font-weight: 600;
            cursor: pointer;
            transition: background 0.25s ease;
        }

        .pill:hover {
            background: #d9e8ff;
        }

        .review-card {
            padding: 24px;
        }

        .review-card h3 {
            margin: 0 0 14px;
            font-size: 1.05rem;
        }

        .review-stars {
            display: flex;
            gap: 4px;
            margin-bottom: 14px;
            color: #f2b930;
        }

        .review-stars span {
            font-size: 1rem;
        }

        .review-quote {
            color: var(--muted);
            line-height: 1.75;
            margin-bottom: 18px;
        }

        .reviewer {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .reviewer-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #dbe7ff;
            display: grid;
            place-items: center;
            color: #0f2c63;
            font-weight: 700;
        }

        .reviewer-name {
            color: var(--text);
            font-weight: 700;
        }

        .reviewer-role {
            color: var(--muted);
            font-size: 0.92rem;
        }

        .auth-modal {
            position: fixed;
            inset: 0;
            background: rgba(9, 30, 66, 0.55);
            display: none;
            justify-content: center;
            align-items: center;
            padding: 24px;
            z-index: 50;
        }

        .auth-modal.active {
            display: flex;
        }

        .auth-panel {
            width: min(540px, 100%);
            padding: 32px;
            background: #ffffff;
            border-radius: 32px;
            box-shadow: 0 30px 80px rgba(15, 44, 99, 0.18);
            text-align: center;
        }

        .auth-panel h3 {
            margin: 0 0 14px;
            font-size: 1.45rem;
        }

        .auth-panel p {
            margin: 0 0 24px;
            color: var(--muted);
            line-height: 1.75;
        }

        .auth-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            justify-content: center;
        }

        .auth-button {
            padding: 14px 24px;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            font-weight: 700;
            min-width: 150px;
            transition: transform 0.2s ease;
        }

        .auth-button:hover {
            transform: translateY(-2px);
        }

        .auth-login {
            background: var(--primary);
            color: white;
        }

        .auth-register {
            background: #eef4ff;
            color: var(--primary);
        }

        .auth-close {
            margin-top: 20px;
            color: var(--muted);
            background: transparent;
            border: none;
            cursor: pointer;
            text-decoration: underline;
        }

        @media (max-width: 1120px) {
            .hero-grid,
            .feature-grid,
            .product-grid,
            .recent-grid,
            .review-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            header {
                flex-wrap: wrap;
            }

            .search-bar {
                order: 3;
                width: 100%;
            }

            .header-actions {
                width: 100%;
                justify-content: flex-end;
            }
        }

        @media (max-width: 760px) {
            .page-shell {
                padding: 18px;
            }

            header {
                padding: 18px;
            }

            nav {
                flex-direction: column;
                align-items: stretch;
            }

            .promo-panel,
            .feature-card,
            .product-card,
            .recent-card,
            .review-card {
                border-radius: 24px;
            }

            .promo-panel {
                flex-direction: column;
            }

            .hero-grid,
            .feature-grid,
            .product-grid,
            .recent-grid,
            .review-grid {
                grid-template-columns: 1fr;
            }

            .category-card {
                min-height: 200px;
            }

            .auth-panel {
                padding: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <header>
            <div class="brand">
<div class="brand-logo"><img src="Logo.png" alt="Kloset Digs" style="width:100%;height:100%;object-fit:contain;border-radius:14px;"/></div>
                <div class="brand-text">
                    <h1>Kloset Digs</h1>
                    <span>Curated thrift fashion</span>
                </div>
            </div>

            <div class="search-bar" data-auth="true">
                <svg class="icon" viewBox="0 0 24 24"><path d="M10.5 3a7.5 7.5 0 015.96 12.12l4.7 4.7a1 1 0 01-1.42 1.42l-4.7-4.7A7.5 7.5 0 1110.5 3zm0 2a5.5 5.5 0 100 11 5.5 5.5 0 000-11z"></path></svg>
                <input type="text" placeholder="Search vintage, denim, bags, and more" readonly>
            </div>

            <div class="header-actions">
                <button class="icon-button" data-auth="true" aria-label="Account">
                    <svg viewBox="0 0 24 24"><path d="M12 12a5 5 0 100-10 5 5 0 000 10zm-7.5 9.5a7.5 7.5 0 0115 0H4.5z"></path></svg>
                </button>
                <button class="icon-button" data-auth="true" aria-label="Cart">
                    <svg viewBox="0 0 24 24"><path d="M7 18a2 2 0 100 4 2 2 0 000-4zm10 0a2 2 0 100 4 2 2 0 000-4zM7.2 8l.9 4.5h8.2l1.4-6.5H6.1"></path></svg>
                </button>
            </div>
        </header>

<nav>
            <a class="nav-link" data-auth="true" href="Thrift.php">Home</a>
            <span class="nav-link" data-auth="true">Shop</span>
            <span class="nav-link" data-auth="true">Sell</span>
            <span class="nav-link" data-auth="true">Blog</span>
            <span class="nav-link" data-auth="true">About</span>
            <span class="nav-link" data-auth="true">Contact</span>
        </nav>

        <section class="hero-grid">
            <article class="category-card" data-auth="true">
                <img src="images/ForHim.jpeg" alt="Clothes for Him" />
                <div class="category-label">Clothes for Him</div>
            </article>
            <article class="category-card" data-auth="true">
                <img src="images/ForHer.jpg" alt="Clothes for Her" />
                <div class="category-label">Clothes for Her</div>
            </article>

            <article class="category-card" data-auth="true">
                <img src="images/Shoes.jpeg" alt="Shoes" />
                <div class="category-label">Shoes</div>
            </article>

            <article class="category-card" data-auth="true">
                <img src="images/Bags.jpeg" alt="Bags" />
                <div class="category-label">Bags</div>
            </article>

            <article class="category-card" data-auth="true">
                <img src="images/Jewelry.jpeg" alt="Jewelry" />
                <div class="category-label">Jewelry</div>
            </article>
            <article class="category-card" data-auth="true">
                <img src="images/KIds.webp" alt="Kids Clothes" />
                <div class="category-label">Kids Clothes</div>
            </article>



            <article class="category-card" data-auth="true">
                <img src="images/Accessories.jpg" alt="Accessories" />
                <div class="category-label">Accessories</div>
            </article>

            <article class="category-card" data-auth="true">
                <img src="images/HomeDecor.jpeg" alt="Home Decor" />
                <div class="category-label">Home Decor</div>
            </article>

        </section>

        <section class="promo-panel">
            <div class="promo-copy">
                <h2>Ready to Find Your Next Treasure?</h2>
                <p>Discover premium thrift fashion and lifestyle pieces at affordable prices. Shop sustainably with secure checkout, fast delivery, and easy returns on every order.</p>
            </div>
            <button class="promo-cta" data-auth="true">Explore the Collection</button>
        </section>

        <section class="feature-grid">
            <article class="feature-card" data-auth="true">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24"><path d="M5 12l5 5L20 7" /></svg>
                </div>
                <div class="feature-text">
                    <h3>Secure Payments</h3>
                    <p>Encrypted checkout with trusted payment methods for every purchase.</p>
                </div>
            </article>
            <article class="feature-card" data-auth="true">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24"><path d="M4 6h16v2H4zm0 5h12v2H4zm0 5h8v2H4z" /></svg>
                </div>
                <div class="feature-text">
                    <h3>Fast Delivery</h3>
                    <p>Quick shipping options to get your vintage finds delivered without delay.</p>
                </div>
            </article>
            <article class="feature-card" data-auth="true">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24"><path d="M12 4v4m0 4v8m-4-4h8" /></svg>
                </div>
                <div class="feature-text">
                    <h3>Order Tracking</h3>
                    <p>Stay updated with real-time shipping status from checkout to delivery.</p>
                </div>
            </article>
            <article class="feature-card" data-auth="true">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24"><path d="M5 7h14v14H5z" /></svg>
                </div>
                <div class="feature-text">
                    <h3>Easy Returns</h3>
                    <p>Flexible return policies make shopping secondhand effortless and safe.</p>
                </div>
            </article>
        </section>


    <div class="auth-modal" id="authModal">
        <div class="auth-panel">
        <a href="#" class="logo">
        <img src="Logo.png" alt="KlosetDigs" class="logo-img">
            <h3>Welcome to Kloset Digs</h3>
            <p>To explore the full store and access your cart, please login or register first.</p>
            <div class="auth-actions">
                <button class="auth-button auth-login" onclick="redirectTo('login.php')">Login</button>
                <button class="auth-button auth-register" onclick="redirectTo('Registration.php')">Register</button>
            </div>
            <button class="auth-close" onclick="closeModal()">Continue browsing without signing in</button>
        </div>
    </div>

    <script>
        const authModal = document.getElementById('authModal');

        function openModal() {
            authModal.classList.add('active');
        }

        function closeModal() {
            authModal.classList.remove('active');
        }

        function redirectTo(url) {
            window.location.href = url;
        }

        document.addEventListener('click', event => {
            const target = event.target.closest('[data-auth]');
            if (target) {
                event.preventDefault();
                openModal();
            }
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>
