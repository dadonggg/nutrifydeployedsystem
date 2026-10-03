<?php
declare(strict_types=1);
$pageTitle = 'Welcome to Nutrify';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="Nutrify — A modern, all-in-one gym management platform for fitness enthusiasts, gym owners, and staff.">
    <title>Nutrify — Gym Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --nf-green: #1B6B2A;
            --nf-green-light: #2E8B3E;
            --nf-green-dark: #145420;
            --nf-accent: #4CAF50;
            --nf-accent-glow: rgba(76,175,80,.25);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #060e08;
            color: #fff;
            overflow-x: hidden;
        }

        /* ─── Animated background ─── */
        .hero-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(27,107,42,.15) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(76,175,80,.1) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 80%, rgba(27,107,42,.08) 0%, transparent 50%);
        }
        .hero-bg::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%231B6B2A' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        /* ─── Floating particles ─── */
        .particles {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }
        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(76,175,80,.15);
            animation: float-up linear infinite;
        }
        @keyframes float-up {
            0% { transform: translateY(100vh) scale(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-10vh) scale(1); opacity: 0; }
        }

        /* ─── Layout ─── */
        .landing-wrapper {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ─── Nav ─── */
        .landing-nav {
            padding: 1.25rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(10px);
            background: rgba(6,14,8,.5);
            border-bottom: 1px solid rgba(76,175,80,.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .landing-nav .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        .landing-nav .brand img {
            height: 42px;
            width: auto;
            filter: drop-shadow(0 0 20px rgba(76,175,80,.3));
        }
        .landing-nav .brand span {
            font-size: 1.5rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.03em;
        }
        .landing-nav .nav-btns {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        /* ─── Buttons ─── */
        .btn-landing {
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: .9rem;
            text-decoration: none;
            transition: all .3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border: none;
        }
        .btn-outline {
            background: transparent;
            border: 1.5px solid rgba(255,255,255,.25);
            color: #fff;
        }
        .btn-outline:hover {
            border-color: var(--nf-accent);
            color: var(--nf-accent);
            background: rgba(76,175,80,.08);
            transform: translateY(-1px);
        }
        .btn-primary-landing {
            background: linear-gradient(135deg, var(--nf-green), var(--nf-green-light));
            color: #fff;
            box-shadow: 0 4px 20px rgba(27,107,42,.4);
        }
        .btn-primary-landing:hover {
            background: linear-gradient(135deg, var(--nf-green-light), var(--nf-accent));
            transform: translateY(-2px);
            box-shadow: 0 6px 30px rgba(27,107,42,.5);
            color: #fff;
        }
        .btn-large {
            padding: 14px 36px;
            font-size: 1rem;
            border-radius: 12px;
        }

        /* ─── Hero ─── */
        .hero {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4rem 2rem;
        }
        .hero-content {
            max-width: 900px;
            text-align: center;
        }
        .hero-logo {
            width: 140px;
            height: 140px;
            margin: 0 auto 2rem;
            animation: pulse-glow 3s ease-in-out infinite;
            filter: drop-shadow(0 0 40px rgba(76,175,80,.4));
        }
        @keyframes pulse-glow {
            0%, 100% { filter: drop-shadow(0 0 40px rgba(76,175,80,.3)); transform: scale(1); }
            50% { filter: drop-shadow(0 0 60px rgba(76,175,80,.5)); transform: scale(1.03); }
        }
        .hero-badge {
            display: inline-block;
            background: rgba(76,175,80,.12);
            border: 1px solid rgba(76,175,80,.25);
            color: var(--nf-accent);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: .8rem;
            font-weight: 600;
            letter-spacing: .5px;
            margin-bottom: 1.5rem;
        }
        .hero h1 {
            font-size: clamp(2.2rem, 5vw, 3.8rem);
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -0.04em;
            margin-bottom: 1.25rem;
        }
        .hero h1 .gradient-text {
            background: linear-gradient(135deg, var(--nf-accent), #81C784, var(--nf-green-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero .subtitle {
            font-size: clamp(1rem, 2vw, 1.2rem);
            color: rgba(255,255,255,.6);
            max-width: 600px;
            margin: 0 auto 2.5rem;
            line-height: 1.7;
            font-weight: 400;
        }
        .hero-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 3rem;
        }

        /* ─── Features grid ─── */
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            max-width: 900px;
            margin: 0 auto;
        }
        .feature-card {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 14px;
            padding: 1.25rem;
            text-align: center;
            transition: all .3s ease;
            backdrop-filter: blur(5px);
        }
        .feature-card:hover {
            background: rgba(76,175,80,.08);
            border-color: rgba(76,175,80,.2);
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(27,107,42,.15);
        }
        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(76,175,80,.12);
            color: var(--nf-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin: 0 auto .75rem;
        }
        .feature-card h3 {
            font-size: .85rem;
            font-weight: 700;
            margin-bottom: .35rem;
            color: #fff;
        }
        .feature-card p {
            font-size: .75rem;
            color: rgba(255,255,255,.45);
            line-height: 1.5;
            margin: 0;
        }

        /* ─── Footer ─── */
        .landing-footer {
            text-align: center;
            padding: 2rem;
            color: rgba(255,255,255,.25);
            font-size: .8rem;
            border-top: 1px solid rgba(255,255,255,.05);
        }

        /* ─── Responsive ─── */
        @media (max-width: 640px) {
            .landing-nav { padding: 1rem; }
            .landing-nav .brand span { font-size: 1.2rem; }
            .hero { padding: 2rem 1rem; }
            .hero-logo { width: 100px; height: 100px; }
            .btn-large { padding: 12px 24px; font-size: .9rem; }
        }

        /* ─── Entrance animation ─── */
        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeIn .8s ease forwards;
        }
        .fade-in-delay-1 { animation-delay: .15s; }
        .fade-in-delay-2 { animation-delay: .3s; }
        .fade-in-delay-3 { animation-delay: .45s; }
        .fade-in-delay-4 { animation-delay: .6s; }
        .fade-in-delay-5 { animation-delay: .75s; }
        @keyframes fadeIn {
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<!-- Background -->
<div class="hero-bg"></div>
<div class="particles">
    <?php for ($i = 0; $i < 15; $i++): ?>
    <div class="particle" style="
        left: <?= rand(5,95) ?>%;
        width: <?= rand(3,8) ?>px;
        height: <?= rand(3,8) ?>px;
        animation-duration: <?= rand(8,20) ?>s;
        animation-delay: <?= rand(0,10) ?>s;
    "></div>
    <?php endfor; ?>
</div>

<div class="landing-wrapper">
    <!-- Nav -->
    <nav class="landing-nav">
        <a href="index.php" class="brand">
            <img src="public/assets/logo.png" alt="Nutrify Logo">
            <span>Nutrify</span>
        </a>
        <div class="nav-btns">
            <a href="index.php?r=auth/login" class="btn-landing btn-outline">
                <i class="bi bi-box-arrow-in-right"></i> Login
            </a>
            <a href="index.php?r=auth/register" class="btn-landing btn-primary-landing">
                <i class="bi bi-person-plus"></i> Sign Up
            </a>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="hero-content">
            <img src="public/assets/logo.png" alt="Nutrify" class="hero-logo fade-in">

            <div class="hero-badge fade-in fade-in-delay-1">
                <i class="bi bi-lightning-charge-fill me-1"></i> All-in-One Gym Management
            </div>

            <h1 class="fade-in fade-in-delay-2">
                Elevate Your Gym<br>
                with <span class="gradient-text">Nutrify</span>
            </h1>

            <p class="subtitle fade-in fade-in-delay-3">
                A modern platform that connects fitness enthusiasts, gym owners, and staff
                in one seamless ecosystem. Manage memberships, track finances, handle permits,
                and grow your fitness business — all in one place.
            </p>

            <div class="hero-buttons fade-in fade-in-delay-4">
                <a href="index.php?r=auth/register" class="btn-landing btn-primary-landing btn-large">
                    <i class="bi bi-rocket-takeoff"></i> Get Started Free
                </a>
                <a href="index.php?r=auth/login" class="btn-landing btn-outline btn-large">
                    <i class="bi bi-box-arrow-in-right"></i> Sign In
                </a>
            </div>

            <!-- Features -->
            <div class="features fade-in fade-in-delay-5">
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-people-fill"></i></div>
                    <h3>Membership</h3>
                    <p>Apply, verify, and manage gym memberships with ease</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-wallet2"></i></div>
                    <h3>Financials</h3>
                    <p>Track investments, revenue, expenses &amp; monthly profit</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-box-seam"></i></div>
                    <h3>Inventory</h3>
                    <p>List and manage all gym equipment in one place</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-shield-check"></i></div>
                    <h3>Permits</h3>
                    <p>Submit &amp; verify legal documents with per-document review</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="landing-footer">
        &copy; <?= date('Y') ?> Nutrify Gym Management System. All rights reserved.
    </footer>
</div>

</body>
</html>
