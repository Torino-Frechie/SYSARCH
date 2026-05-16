<?php
$nav_items = [
    'Home' => 'landingpage.php',
    'About' => 'about.php'
];

/* DATABASE */
$host = 'localhost';
$db   = 'sysarch';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

$top10 = [];

if (!$conn->connect_error) {
    $result = $conn->query("
        SELECT u.id_number AS student_id,
               CONCAT(u.first_name, ' ', u.last_name) AS student_name,
               SUM(TIMESTAMPDIFF(MINUTE, sr.login_time, sr.logout_time)) AS total_minutes
        FROM sitin_records sr
        JOIN users u ON sr.id_number = u.id_number
        WHERE sr.login_time IS NOT NULL
        AND sr.logout_time IS NOT NULL
        GROUP BY u.id_number, student_name
        ORDER BY total_minutes DESC
        LIMIT 10
    ");

    if ($result && $result !== false) {
        while ($row = $result->fetch_assoc()) {
            $top10[] = $row;
        }
    }
    $conn->close();
}

$av_colors = ['av-blue','av-indigo','av-teal','av-cyan','av-slate','av-sky','av-violet','av-emerald','av-amber','av-rose'];

function getInitials($name) {
    $parts = explode(' ', trim($name));
    $out = '';
    foreach (array_slice($parts, 0, 2) as $p) $out .= strtoupper(mb_substr($p, 0, 1));
    return $out;
}

function shortName($name) {
    $parts = explode(' ', trim($name));
    $first = $parts[0];
    $last = count($parts) > 1 ? strtoupper(substr(end($parts), 0, 1)) . '.' : '';
    return $last ? "$first $last" : $first;
}

function formatDuration($minutes) {
    $minutes = (int)$minutes;
    if ($minutes <= 0) return '0m';
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    if ($h > 0 && $m > 0) return "{$h}h {$m}m";
    if ($h > 0) return "{$h}h";
    return "{$m}m";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS Sit-in Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --blue:        #2564ebbb;
            --blue-dark:   #1D4ED8;
            --blue-deeper: #1E3A8A;
            --blue-light:  #DBEAFE;
            --blue-mid:    #3B82F6;
            --white:       #FFFFFF;
            --gray-50:     #F8FAFC;
            --gray-100:    #F1F5F9;
            --gray-200:    #E2E8F0;
            --gray-400:    #94A3B8;
            --gray-600:    #475569;
            --gray-800:    #1E293B;
            --gold:        #F59E0B;
            --gold-light:  #FCD34D;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--gray-50);
            color: var(--gray-800);
            overflow-x: hidden;
            animation: fadeIn .6s ease;
        }
        body.fade-out { opacity: 0; transition: opacity .4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

        /* ── NAVBAR ── */
        .navbar {
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            box-shadow: 0 1px 12px rgba(37,99,235,0.07);
            padding: 0 0;
            height: 64px;
        }
        .navbar .container { height: 100%; }
        .navbar-brand {
            font-weight: 700;
            font-size: .92rem;
            color: var(--gray-800) !important;
            letter-spacing: -0.01em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-link {
            color: var(--gray-600) !important;
            font-weight: 500;
            font-size: .85rem;
            padding: 6px 14px !important;
            border-radius: 8px;
            transition: .2s;
        }
        .nav-link:hover { color: var(--blue) !important; background: var(--blue-light); }
        .btn-nav-login {
            background: var(--white);
            border: 1.5px solid var(--blue);
            color: var(--blue) !important;
            border-radius: 8px;
            font-weight: 600;
            font-size: .84rem;
            padding: 6px 18px;
            transition: .2s;
            text-decoration: none;
        }
        .btn-nav-login:hover { background: var(--blue-light); }
        .btn-nav-register {
            background: var(--blue);
            border: 1.5px solid var(--blue);
            color: var(--white) !important;
            border-radius: 8px;
            font-weight: 600;
            font-size: .84rem;
            padding: 6px 18px;
            transition: .2s;
            text-decoration: none;
        }
        .btn-nav-register:hover { background: var(--blue-dark); border-color: var(--blue-dark); }

        /* ── HERO ── */
        .hero {
            background: linear-gradient(135deg, var(--blue-deeper) 0%, var(--blue) 60%, var(--blue-mid) 100%);
            padding: 80px 20px 130px;
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
        }
        .hero::after {
            content: '';
            position: absolute;
            bottom: -2px; left: 0; right: 0;
            height: 80px;
            background: var(--gray-50);
            clip-path: ellipse(55% 100% at 50% 100%);
        }
        .hero-inner { position: relative; z-index: 1; }
        .hero-logos { display: flex; justify-content: center; align-items: center; gap: 40px; margin-bottom: 28px; }
        .hero-logos img {
            max-height: 100px;
            filter: drop-shadow(0 4px 20px rgba(0,0,0,0.25));
            transition: transform .35s ease;
        }
        .hero-logos img:hover { transform: scale(1.07); }
        .hero-tag {
            display: inline-block;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.25);
            color: rgba(255,255,255,0.9);
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .12em;
            text-transform: uppercase;
            padding: 4px 14px;
            border-radius: 20px;
            margin-bottom: 16px;
        }
        .hero h1 {
            font-size: 2.6rem;
            font-weight: 800;
            color: var(--white);
            letter-spacing: -0.02em;
            line-height: 1.15;
            margin-bottom: 14px;
        }
        .hero p {
            color: rgba(255,255,255,0.75);
            font-size: .95rem;
            font-weight: 400;
            max-width: 460px;
            margin: 0 auto 28px;
            line-height: 1.7;
        }
        .hero-cta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--white);
            color: var(--blue) !important;
            font-weight: 700;
            font-size: .9rem;
            padding: 12px 28px;
            border-radius: 10px;
            text-decoration: none;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            transition: .25s;
        }
        .hero-cta:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(0,0,0,0.25); }
        .hero-cta-arrow { font-size: 1rem; transition: transform .2s; }
        .hero-cta:hover .hero-cta-arrow { transform: translateX(4px); }

        /* ── WELCOME CARD ── */
        .welcome-card {
            background: var(--white);
            border-radius: 20px;
            padding: 48px 44px;
            margin-top: -60px;
            box-shadow: 0 8px 32px rgba(37,99,235,0.10);
            border: 1px solid var(--gray-200);
            position: relative;
            z-index: 2;
        }
        .welcome-card h2 {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--blue);
            letter-spacing: -0.02em;
            margin-bottom: 14px;
        }
        .welcome-card p { color: var(--gray-600); font-size: .9rem; line-height: 1.75; }
        .welcome-divider {
            width: 48px;
            height: 4px;
            background: var(--blue);
            border-radius: 2px;
            margin: 16px auto 20px;
        }

        /* ── STATS BAR ── */
        .stats-bar {
            background: var(--blue);
            border-radius: 16px;
            padding: 24px 32px;
            display: flex;
            justify-content: space-around;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            margin: 32px 0;
        }
        .stat-item { text-align: center; color: var(--white); }
        .stat-num { font-size: 1.8rem; font-weight: 800; line-height: 1; }
        .stat-lbl { font-size: .72rem; font-weight: 500; opacity: .75; margin-top: 4px; text-transform: uppercase; letter-spacing: .08em; }
        .stat-divider { width: 1px; height: 40px; background: rgba(255,255,255,.2); }

        /* ── FEATURES ── */
        .section-label {
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--blue);
            margin-bottom: 8px;
        }
        .section-title {
            font-size: 1.55rem;
            font-weight: 800;
            color: var(--gray-800);
            letter-spacing: -0.02em;
        }
        .feature-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            padding: 32px 28px;
            transition: .25s;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: var(--blue);
            transform: scaleX(0);
            transition: transform .3s ease;
            transform-origin: left;
        }
        .feature-card:hover { transform: translateY(-5px); box-shadow: 0 12px 32px rgba(37,99,235,0.12); border-color: var(--blue-light); }
        .feature-card:hover::before { transform: scaleX(1); }
        .feature-icon-wrap {
            width: 52px; height: 52px;
            background: var(--blue-light);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 18px;
        }
        .feature-card h5 { font-weight: 700; font-size: .97rem; color: var(--gray-800); margin-bottom: 8px; }
        .feature-card p { font-size: .83rem; color: var(--gray-600); line-height: 1.65; margin: 0; }

        /* ── LEADERBOARD ── */
        .lb-section-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 24px;
            gap: 12px;
            flex-wrap: wrap;
        }
        .lb-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--blue-light);
            color: var(--blue);
            font-size: .73rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            letter-spacing: .04em;
        }
        .leaderboard-card {
            background: var(--white);
            border-radius: 20px;
            border: 1px solid var(--gray-200);
            box-shadow: 0 4px 24px rgba(37,99,235,0.07);
            overflow: hidden;
        }
        .lb-header-bar {
            background: linear-gradient(135deg, var(--blue-deeper), var(--blue));
            padding: 20px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .lb-header-bar h5 { color: var(--white); font-weight: 700; font-size: 1rem; margin: 0; }
        .lb-header-bar small { color: rgba(255,255,255,.65); font-size: .75rem; }
        .lb-body { padding: 28px; }

        /* Podium */
        .podium { display: flex; align-items: flex-end; justify-content: center; gap: 12px; margin-bottom: 28px; }
        .podium-item { flex: 1; text-align: center; max-width: 130px; }
        .podium-avatar {
            width: 52px; height: 52px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: auto;
            font-weight: 700;
            font-size: .9rem;
            position: relative;
            border: 3px solid var(--white);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        .podium-crown { position: absolute; top: -18px; font-size: 18px; animation: crownFloat 2s infinite ease-in-out; }
        @keyframes crownFloat { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
        .podium-name { font-size: .75rem; font-weight: 600; margin-top: 8px; color: var(--gray-800); }
        .podium-duration { font-size: .68rem; color: var(--gray-400); margin-top: 2px; }
        .podium-bar {
            margin-top: 10px;
            border-radius: 8px 8px 0 0;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: .9rem;
        }
        .p1-bar { height: 72px; background: linear-gradient(180deg, var(--gold), var(--gold-light)); color: #7a4800; }
        .p2-bar { height: 54px; background: linear-gradient(180deg, #CBD5E1, #E2E8F0); color: #334155; }
        .p3-bar { height: 40px; background: linear-gradient(180deg, #C9A06C, #DEB887); color: #6b4c2a; }

        /* Rank rows */
        .lb-rank-item {
            display: flex; align-items: center; gap: 14px;
            padding: 12px 14px;
            border-radius: 12px;
            background: var(--gray-50);
            border: 1px solid var(--gray-100);
            margin-bottom: 8px;
            transition: .2s;
        }
        .lb-rank-item:hover { background: var(--blue-light); border-color: #BFDBFE; transform: translateX(4px); }
        .lb-rank-num { font-weight: 700; font-size: .82rem; min-width: 28px; color: var(--gray-600); }
        .lb-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .75rem; flex-shrink: 0;
        }
        .lb-rank-name { font-size: .83rem; font-weight: 600; color: var(--gray-800); }
        .lb-rank-time { font-size: .8rem; font-weight: 700; color: var(--blue); margin-left: auto; white-space: nowrap; }
        .progress { height: 5px; background: var(--gray-200); border-radius: 3px; }
        .progress-bar { background: var(--blue); border-radius: 3px; }

        .lb-footer {
            border-top: 1px solid var(--gray-200);
            padding: 14px 20px;
            text-align: center;
        }
        .lb-footer button {
            border: none; background: none;
            color: var(--blue); font-weight: 600; font-size: .84rem;
            cursor: pointer; transition: .2s;
            font-family: 'Poppins', sans-serif;
        }
        .lb-footer button:hover { color: var(--blue-dark); }

        /* Empty leaderboard */
        .lb-empty {
            text-align: center; padding: 48px 20px;
            color: var(--gray-400);
        }
        .lb-empty .lb-empty-icon { font-size: 2.5rem; margin-bottom: 12px; }
        .lb-empty p { font-size: .85rem; margin: 0; }

        /* Avatar colors */
        .av-blue    { background: #DBEAFE; color: #1D4ED8; }
        .av-indigo  { background: #E0E7FF; color: #3730A3; }
        .av-teal    { background: #CCFBF1; color: #0F766E; }
        .av-cyan    { background: #CFFAFE; color: #0E7490; }
        .av-slate   { background: #F1F5F9; color: #334155; }
        .av-sky     { background: #E0F2FE; color: #0369A1; }
        .av-violet  { background: #EDE9FE; color: #5B21B6; }
        .av-emerald { background: #D1FAE5; color: #065F46; }
        .av-amber   { background: #FEF3C7; color: #92400E; }
        .av-rose    { background: #FFE4E6; color: #9F1239; }
        .av-gold    { background: #FEF3C7; color: #92400E; }
        .av-bronze  { background: #FEE2C8; color: #7C3A1A; }

        /* Footer */
        footer {
            background: var(--gray-800);
            color: var(--gray-400);
            padding: 36px 0;
            text-align: center;
            font-size: .8rem;
        }
        footer .footer-brand { color: var(--white); font-weight: 600; font-size: .9rem; margin-bottom: 4px; }
        footer hr { border-color: #334155; margin: 16px auto; width: 60px; }

        /* Modal */
        .lb-modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(15,23,42,0.6);
            backdrop-filter: blur(4px);
            z-index: 1050;
            align-items: center; justify-content: center;
            padding: 20px;
        }
        .lb-modal-box {
            background: var(--white);
            border-radius: 20px;
            width: 100%; max-width: 480px;
            max-height: 90vh;
            overflow: hidden;
            display: flex; flex-direction: column;
            box-shadow: 0 24px 60px rgba(15,23,42,0.35);
        }
        .lb-modal-head {
            background: linear-gradient(135deg, var(--blue-deeper), var(--blue));
            padding: 20px 24px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .lb-modal-head h5 { color: var(--white); font-weight: 700; margin: 0; font-size: .97rem; }
        .lb-modal-head small { color: rgba(255,255,255,.6); font-size: .72rem; }
        .lb-modal-close {
            border: none; background: rgba(255,255,255,.15);
            color: var(--white); width: 32px; height: 32px;
            border-radius: 8px; font-size: 1rem; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: .2s;
        }
        .lb-modal-close:hover { background: rgba(255,255,255,.25); }
        .lb-modal-body { padding: 20px; overflow-y: auto; }

        @media(max-width:768px) {
            .hero h1 { font-size: 1.8rem; }
            .hero-logos img { max-height: 65px; }
            .welcome-card { padding: 30px 20px; margin-top: -40px; }
            .stats-bar { padding: 18px 16px; }
            .stat-divider { display: none; }
            .podium-avatar { width: 42px; height: 42px; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="#">
            <img src="uclogo.png" height="34" alt="UC Logo">
            CCS Sit-in Monitoring
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
            style="border:1px solid var(--gray-200);border-radius:8px;padding:6px 10px;">
            <span style="display:block;width:18px;height:2px;background:var(--gray-600);margin:4px 0;border-radius:2px;"></span>
            <span style="display:block;width:18px;height:2px;background:var(--gray-600);margin:4px 0;border-radius:2px;"></span>
            <span style="display:block;width:18px;height:2px;background:var(--gray-600);margin:4px 0;border-radius:2px;"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-center gap-1 py-2 py-lg-0">
                <li class="nav-item">
                    <a href="landingpage.php" style="color:var(--gray-600);font-weight:500;font-size:.85rem;padding:6px 14px;border-radius:8px;text-decoration:none;display:flex;align-items:center;gap:5px;">
                        <i class="bi bi-house"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a href="about.php" style="color:var(--gray-600);font-weight:500;font-size:.85rem;padding:6px 14px;border-radius:8px;text-decoration:none;display:flex;align-items:center;gap:5px;">
                        <i class="bi bi-info-circle"></i> About
                    </a>
                </li>
                <li class="nav-item ms-2">
                    <a class="btn-nav-login" href="login.php">Log In</a>
                </li>
                <li class="nav-item ms-2">
                    <a class="btn-nav-register" href="register.php">Register</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- HERO -->
<header class="hero">
    <div class="hero-inner container">
        <div class="hero-logos">
            <img src="uclogo.png" alt="UC Logo">
            <img src="ucmainccslogo.png" alt="CCS Logo">
        </div>
        <div class="hero-tag">College of Computer Studies</div>
        <h1>Sit-in Monitoring<br>System</h1>
        <p>A centralized platform for managing and tracking student laboratory sessions at the University of Cebu.</p>
        <a href="login.php" class="hero-cta">
            Get Started <span class="hero-cta-arrow">→</span>
        </a>
    </div>
</header>

<!-- MAIN -->
<main class="container" style="padding-top:20px;padding-bottom:60px;">

    <!-- WELCOME CARD -->
    <div class="row justify-content-center mb-4">
        <div class="col-lg-8">
            <div class="welcome-card text-center">
                <h2>Welcome to CCS Portal</h2>
                <div class="welcome-divider"></div>
                <p>Monitor laboratory sit-ins, manage student sessions, and track activity across all CCS computer labs. Built for faculty and staff to manage with ease.</p>
    
            </div>
        </div>
    </div>

    <!-- STATS BAR -->
    <?php
    $conn2 = new mysqli('localhost', 'root', '', 'sysarch');
    $total_sessions = 0; $total_students = 0; $total_labs = 0;
    if (!$conn2->connect_error) {
        $r1 = $conn2->query("SELECT COUNT(*) as c FROM sitin_records WHERE logout_time IS NOT NULL");
        if ($r1) $total_sessions = $r1->fetch_assoc()['c'];
        $r2 = $conn2->query("SELECT COUNT(DISTINCT id_number) as c FROM users");
        if ($r2) $total_students = $r2->fetch_assoc()['c'];
        $r3 = $conn2->query("SELECT COUNT(*) as c FROM lab_config");
        if ($r3) $total_labs = $r3->fetch_assoc()['c'];
        $conn2->close();
    }
    ?>
    <div class="stats-bar">
        <div class="stat-item">
            <div class="stat-num"><?= number_format($total_sessions) ?></div>
            <div class="stat-lbl">Total Sessions</div>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <div class="stat-num"><?= number_format($total_students) ?></div>
            <div class="stat-lbl">Registered Students</div>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <div class="stat-num"><?= number_format($total_labs) ?></div>
            <div class="stat-lbl">Computer Labs</div>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <div class="stat-num">10</div>
            <div class="stat-lbl">PCs per Lab</div>
        </div>
    </div>

    <!-- FEATURES -->
    <section class="py-4 mb-2">
        <div class="text-center mb-5">
            <div class="section-label">What We Offer</div>
            <div class="section-title">System Features</div>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrap">💻</div>
                    <h5>Real-time Monitoring</h5>
                    <p>Track student sit-ins as they happen. View live occupancy across all computer labs instantly.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrap">📊</div>
                    <h5>Student Analytics</h5>
                    <p>Detailed reports on lab usage, session durations, and student activity patterns over time.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrap">🔒</div>
                    <h5>Secure & Reliable</h5>
                    <p>Role-based access for administrators and students. All data is securely managed and logged.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrap">📅</div>
                    <h5>PC Reservation</h5>
                    <p>Students can reserve specific PCs in advance for their preferred time slots and labs.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrap">🖥️</div>
                    <h5>Software Inventory</h5>
                    <p>See which software is available in each lab before arriving — no more guessing.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrap">💬</div>
                    <h5>Session Feedback</h5>
                    <p>Students can leave feedback after each session to help improve the lab experience.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- LEADERBOARD -->
    <section class="pb-4">
        <div class="lb-section-header">
            <div>
                <div class="section-label">Hall of Fame</div>
                <div class="section-title">Top Sit-in Students</div>
            </div>
            <div class="lb-badge">🏆 Based on total lab hours</div>
        </div>

        <div class="leaderboard-card">
            <div class="lb-header-bar">
                <div>
                    <h5>Student Leaderboard</h5>
                    <small>Ranked by cumulative lab session time</small>
                </div>
            </div>
            <div class="lb-body">
                <?php if (count($top10) >= 3):
                    $p1 = $top10[0]; $p2 = $top10[1]; $p3 = $top10[2];
                ?>
                <!-- PODIUM -->
                <div class="podium">
                    <div class="podium-item">
                        <div class="podium-avatar av-slate">
                            <?= getInitials($p2['student_name']) ?>
                        </div>
                        <div class="podium-name"><?= shortName($p2['student_name']) ?></div>
                        <div class="podium-duration"><?= formatDuration($p2['total_minutes']) ?></div>
                        <div class="podium-bar p2-bar">2</div>
                    </div>
                    <div class="podium-item">
                        <div class="podium-avatar av-gold">
                            <span class="podium-crown">👑</span>
                            <?= getInitials($p1['student_name']) ?>
                        </div>
                        <div class="podium-name"><?= shortName($p1['student_name']) ?></div>
                        <div class="podium-duration"><?= formatDuration($p1['total_minutes']) ?></div>
                        <div class="podium-bar p1-bar">1</div>
                    </div>
                    <div class="podium-item">
                        <div class="podium-avatar av-bronze">
                            <?= getInitials($p3['student_name']) ?>
                        </div>
                        <div class="podium-name"><?= shortName($p3['student_name']) ?></div>
                        <div class="podium-duration"><?= formatDuration($p3['total_minutes']) ?></div>
                        <div class="podium-bar p3-bar">3</div>
                    </div>
                </div>

                <!-- RANKS 4-10 -->
                <?php foreach (array_slice($top10, 3) as $i => $s):
                    $rank = $i + 4;
                    $avClass = $av_colors[$rank % count($av_colors)];
                ?>
                <div class="lb-rank-item">
                    <div class="lb-rank-num">#<?= $rank ?></div>
                    <div class="lb-avatar <?= $avClass ?>"><?= getInitials($s['student_name']) ?></div>
                    <div class="flex-grow-1">
                        <div class="lb-rank-name"><?= shortName($s['student_name']) ?></div>
                        <div class="progress mt-1">
                            <div class="progress-bar" style="width:<?= min(100, ($s['total_minutes'] / $top10[0]['total_minutes']) * 100) ?>%"></div>
                        </div>
                    </div>
                    <div class="lb-rank-time"><?= formatDuration($s['total_minutes']) ?></div>
                </div>
                <?php endforeach; ?>

                <?php elseif (count($top10) > 0): ?>
                <!-- Fewer than 3 students -->
                <?php foreach ($top10 as $i => $s):
                    $rank = $i + 1;
                    $avClass = $av_colors[$i % count($av_colors)];
                ?>
                <div class="lb-rank-item">
                    <div class="lb-rank-num"><?= $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : '#'.$rank) ?></div>
                    <div class="lb-avatar <?= $avClass ?>"><?= getInitials($s['student_name']) ?></div>
                    <div class="flex-grow-1">
                        <div class="lb-rank-name"><?= $s['student_name'] ?></div>
                    </div>
                    <div class="lb-rank-time"><?= formatDuration($s['total_minutes']) ?></div>
                </div>
                <?php endforeach; ?>

                <?php else: ?>
                <div class="lb-empty">
                    <div class="lb-empty-icon">🏅</div>
                    <p>No sit-in data yet. Rankings will appear once sessions are recorded.</p>
                </div>
                <?php endif; ?>
            </div>
            <div class="lb-footer">
                <button onclick="document.getElementById('lbModal').style.display='flex'">
                    View Full Leaderboard →
                </button>
            </div>
        </div>
    </section>

</main>

<!-- FOOTER -->
<footer>
    <div class="container">
        <div class="footer-brand">CCS Sit-in Monitoring System</div>
        <div>University of Cebu — College of Computer Studies</div>
        <hr>
        <div>&copy; <?= date('Y') ?> University of Cebu. All rights reserved.</div>
    </div>
</footer>

<!-- LEADERBOARD MODAL -->
<div id="lbModal" class="lb-modal-overlay"
     onclick="if(event.target===this)this.style.display='none'">
    <div class="lb-modal-box">
        <div class="lb-modal-head">
            <div>
                <h5>🏆 Full Leaderboard</h5>
                <small>Top 10 students by total lab time</small>
            </div>
            <button class="lb-modal-close" onclick="document.getElementById('lbModal').style.display='none'">✕</button>
        </div>
        <div class="lb-modal-body">
            <?php foreach ($top10 as $i => $s):
                $rank = $i + 1;
                $avClass = $av_colors[$i % count($av_colors)];
            ?>
            <div class="lb-rank-item">
                <div class="lb-rank-num">
                    <?php
                    if ($rank == 1) echo '🥇';
                    elseif ($rank == 2) echo '🥈';
                    elseif ($rank == 3) echo '🥉';
                    else echo '#'.$rank;
                    ?>
                </div>
                <div class="lb-avatar <?= $avClass ?>"><?= getInitials($s['student_name']) ?></div>
                <div class="flex-grow-1">
                    <div class="lb-rank-name"><?= htmlspecialchars($s['student_name']) ?></div>
                    <small style="color:var(--gray-400);font-size:.72rem;"><?= htmlspecialchars($s['student_id']) ?></small>
                </div>
                <div class="lb-rank-time"><?= formatDuration($s['total_minutes']) ?></div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($top10)): ?>
                <div class="lb-empty">
                    <div class="lb-empty-icon">🏅</div>
                    <p>No data available yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('a[href]').forEach(link => {
    link.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href && !href.startsWith('#')) {
            e.preventDefault();
            document.body.classList.add('fade-out');
            setTimeout(() => window.location.href = href, 400);
        }
    });
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.getElementById('lbModal').style.display = 'none';
});
</script>
</body>
</html>