<?php
    $nav_items = [
        "About" => "about.php",
    ];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - CCS Sit-in Monitoring System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --blue: #2564ebb9;
            --blue-dark: #1d4fd8ce;
            --blue-deeper: #1E3A8A;
            --blue-light: #DBEAFE;
            --blue-mid: #3B82F6;
            --white: #FFFFFF;
            --gray-50: #F8FAFC;
            --gray-100: #F1F5F9;
            --gray-200: #E2E8F0;
            --gray-600: #475569;
            --gray-800: #1E293B;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--gray-50);
            margin: 0;
            overflow-x: hidden;
        }

        .navbar {
            background: linear-gradient(135deg, var(--white) 0%, var(--white) 100%);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            font-family: 'Poppins', sans-serif;
        }

        .navbar-brand {
            font-weight: 700;
            color: black !important;
            letter-spacing: 0.5px;
            font-size: 0.95rem;
            font-family: 'Poppins', sans-serif;
        }

        .nav-link {
            color: rgba(0, 0, 0, 0.85) !important;
            font-weight: 600;
            transition: all 0.3s;
            position: relative;
            font-family: 'Poppins', sans-serif;
        }

        .nav-link:hover { color: var(--blue) !important; }

        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--blue-light);
            border-radius: 2px;
        }

        /* HERO SECTION */
        .hero-section {
            background: linear-gradient(135deg, var(--blue-deeper) 0%, var(--blue) 100%);
            color: white;
            padding: 80px 20px 120px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            bottom: -20%;
            left: 5%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero-content { position: relative; z-index: 2; }

        .hero-title {
            font-size: 2.8rem;
            font-weight: 800;
            margin: 20px 0;
            line-height: 1.2;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .hero-subtitle {
            font-size: 1.1rem;
            opacity: 0.95;
            margin-bottom: 30px;
            font-weight: 300;
            letter-spacing: 0.5px;
        }

        .scroll-hint {
            margin-top: 40px;
            font-size: 0.9rem;
            opacity: 0.8;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(8px); }
        }

        /* SECTION CARDS */
        .about-section {
            padding: 70px 20px;
            background: var(--white);
            margin: 30px 0;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.06);
        }

        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-tag {
            display: inline-block;
            background: var(--blue-light);
            color: var(--blue-dark);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
        }

        .section-title {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--blue-deeper);
            margin-bottom: 15px;
            line-height: 1.3;
        }

        .section-description {
            font-size: 1rem;
            color: var(--gray-600);
            line-height: 1.7;
            margin-bottom: 30px;
        }

        /* WHAT IS SECTION */
        .what-is-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        @media (min-width: 768px) {
            .what-is-grid { grid-template-columns: 1fr 1fr; }
        }

        .what-card {
            background: linear-gradient(135deg, var(--blue-light) 0%, rgba(219, 234, 254, 0.5) 100%);
            border-left: 4px solid var(--blue-mid);
            padding: 25px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .what-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.15);
            border-left-color: var(--blue-dark);
        }

        .what-card h4 {
            color: var(--blue-dark);
            font-weight: 700;
            margin-bottom: 12px;
            font-size: 1.1rem;
        }

        .what-card p {
            color: var(--gray-600);
            font-size: 0.95rem;
            line-height: 1.6;
            margin: 0;
        }

        /* INSTITUTION SECTION */
        .institution-cards {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
            margin-top: 40px;
        }

        @media (min-width: 768px) {
            .institution-cards { grid-template-columns: 1fr 1fr; }
        }

        .institution-card {
            background: linear-gradient(135deg, var(--blue-light) 0%, var(--gray-100) 100%);
            padding: 35px;
            border-radius: 16px;
            border: 2px solid rgba(59, 130, 246, 0.2);
            transition: all 0.3s ease;
            text-align: center;
        }

        .institution-card:hover {
            border-color: var(--blue-mid);
            box-shadow: 0 15px 35px rgba(37, 99, 235, 0.12);
            transform: translateY(-8px);
        }

        .institution-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--blue-mid), var(--blue-dark));
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
        }

        .institution-card h3 {
            color: var(--blue-darker);
            font-weight: 800;
            font-size: 1.3rem;
            margin-bottom: 12px;
        }

        .institution-card p {
            color: var(--gray-600);
            font-size: 0.95rem;
            line-height: 1.7;
            margin: 0;
        }

        /* HOW IT WORKS SECTION */
        .steps-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            margin-top: 40px;
        }

        @media (min-width: 768px) {
            .steps-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (min-width: 1024px) {
            .steps-grid { grid-template-columns: repeat(4, 1fr); }
        }

        .step-card {
            background: white;
            border: 2px solid var(--blue-light);
            border-radius: 16px;
            padding: 30px 20px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .step-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--blue-mid), var(--blue-dark));
            transform: scaleX(0);
            transition: transform 0.3s ease;
            transform-origin: left;
        }

        .step-card:hover::before { transform: scaleX(1); }

        .step-card:hover {
            border-color: var(--blue-dark);
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.15);
            transform: translateY(-5px);
        }

        .step-number {
            background: linear-gradient(135deg, var(--blue), var(--blue-mid));
            color: white;
            font-size: 2rem;
            font-weight: 800;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }

        .step-emoji {
            font-size: 2.5rem;
            margin: 15px 0;
        }

        .step-card h4 {
            color: var(--blue-dark);
            font-weight: 700;
            font-size: 1.15rem;
            margin-bottom: 12px;
        }

        .step-card p {
            color: var(--gray-600);
            font-size: 0.9rem;
            line-height: 1.6;
            margin: 0;
        }

      /* DEVELOPER SECTION */
        .dev-card {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 20px 60px rgba(30,58,138,0.18);
            margin: 30px 0;
        }
        .dev-left {
            background: linear-gradient(145deg, var(--blue-deeper) 0%, var(--blue-dark) 100%);
            padding: 40px 32px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            text-align: center;
            position: relative;
            overflow: hidden;
            font-family: 'Poppins', sans-serif;
        }
        .dev-left::before { content:''; position:absolute; width:140px; height:140px; border-radius:50%; background:rgba(255,255,255,0.05); top:-50px; right:-50px; }
        .dev-left::after  { content:''; position:absolute; width:100px; height:100px; border-radius:50%; background:rgba(255,255,255,0.05); bottom:-35px; left:-35px; }
        .dev-profile-pic {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255,255,255,0.25);
            display: block;
            transition: transform .3s ease;
            position: relative;
            z-index: 1;
        }
        .dev-profile-pic:hover { transform: scale(1.07); }
        .dev-left-text { position: relative; z-index: 1; }
        .dev-eyebrow {
            font-size: 0.68rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 6px;
        }
        .developer-name {
            font-size: 0.78rem;
            color: rgba(255,255,255,0.9);
            margin-top: 4px;
        }
        .dev-tags {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
            justify-content: center;
            position: relative;
            z-index: 1;
        }
        .dev-tag {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.18);
            color: rgba(255,255,255,0.8);
            font-size: 0.7rem;
            font-weight: 600;
            padding: 3px 11px;
            border-radius: 20px;
        }
        .dev-right {
            background: #fff;
            padding: 40px 32px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 24px;
        }
        .dev-section-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #475569;
            background: #F1F5F9;
            border: 1px solid #E2E8F0;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 12px;
        }
        .developer-bio {
            font-size: 0.88rem;
            color: #475569;
            line-height: 1.75;
            margin: 0;
        }
        .tech-stack { display: flex; flex-wrap: wrap; gap: 8px; }
        .tech-badge {
            display: inline-flex;
            align-items: center;
            font-size: 0.75rem;
            font-weight: 600;
            color: #1E293B;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 5px 12px;
            transition: .2s;
        }
        .tech-badge:hover { background: #DBEAFE; border-color: #BFDBFE; color: #1D4ED8; transform: translateY(-2px); }
        .dev-footer-row {
            font-size: 0.75rem;
            color: #94A3B8;
            padding-top: 16px;
            border-top: 1px solid #F1F5F9;
            display: flex;
            align-items: center;
        }
        @media (max-width: 768px) {
            .dev-card { grid-template-columns: 1fr; }
            .dev-left  { padding: 32px 20px; }
            .dev-right { padding: 28px 20px; }
        }

        /* FOOTER */
        footer {
            background: var(--gray-800);
            color: var(--gray-200);
            padding: 30px;
            text-align: center;
            font-size: 0.9rem;
            margin-top: 50px;
        }

        .container-wide { max-width: 1200px; margin: 0 auto; }

        /* ANIMATIONS */
        @media (prefers-reduced-motion: no-preference) {
            .about-section { animation: fadeInUp 0.6s ease-out; }

            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        }

        @media (max-width: 768px) {
            .hero-title { font-size: 2rem; }
            .section-title { font-size: 1.7rem; }
            .about-section { padding: 40px 15px; margin: 20px 0; }
        }
    </style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg sticky-top" style="background:var(--white);border-bottom:1px solid var(--gray-200);box-shadow:0 1px 12px rgba(37,99,235,0.07);height:64px;">
    <div class="container" style="height:100%;">
        <a class="navbar-brand" href="landingpage.php" style="font-weight:700;font-size:.92rem;color:var(--gray-800);letter-spacing:-0.01em;display:flex;align-items:center;gap:10px;text-decoration:none;">
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
                    <a href="login.php" style="background:var(--white);border:1.5px solid var(--blue);color:var(--blue);border-radius:8px;font-weight:600;font-size:.84rem;padding:6px 18px;text-decoration:none;">Log In</a>
                </li>
                <li class="nav-item ms-2">
                    <a href="register.php" style="background:var(--blue);border:1.5px solid var(--blue);color:var(--white);border-radius:8px;font-weight:600;font-size:.84rem;padding:6px 18px;text-decoration:none;">Register</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- HERO SECTION -->
<section class="hero-section">
    <div class="hero-content">
        <h1 class="hero-title">Track. Manage. Improve.</h1>
        <p class="hero-subtitle">A clean, lightweight monitoring system purpose-built for the daily operations of the University of Cebu's computer laboratories.</p>
        <div class="scroll-hint"><i class="bi bi-arrow-down"></i> Explore ↓</div>
    </div>
</section>

<!-- WHAT IS SECTION -->
<section class="container-wide">
    <div class="about-section">
        <div class="section-header">
            <div class="section-tag">The System</div>
            <h2 class="section-title">What is the Sit-in Monitoring System?</h2>
        </div>
        <div class="what-is-grid">
            <div class="what-card">
                <h4><i class="bi bi-gear-fill me-2"></i>Core Purpose</h4>
                <p>A web-based application designed to manage and track student sit-in sessions in the CCS computer laboratories. It replaces manual logbooks with a fast, reliable digital solution.</p>
            </div>
            <div class="what-card">
                <h4><i class="bi bi-speedometer2 me-2"></i>Built for Speed</h4>
                <p>Built with Flask and SQLite, it is lightweight, fast, and runs entirely on the university's local network infrastructure.</p>
            </div>
            <div class="what-card">
                <h4><i class="bi bi-person-badge me-2"></i>For Administrators</h4>
                <p>Administrators can monitor active sessions, view attendance history, and manage student records — all from a single dashboard.</p>
            </div>
            <div class="what-card">
                <h4><i class="bi bi-person-check me-2"></i>For Students</h4>
                <p>Students simply register once and log in to check in to any available lab. Quick, simple, and efficient.</p>
            </div>
        </div>
    </div>
</section>

<!-- INSTITUTION SECTION -->
<section class="container-wide">
    <div class="about-section">
        <div class="section-header">
            <div class="section-tag">The Institution</div>
            <h2 class="section-title">University of Cebu — College of Computer Studies</h2>
            <p class="section-description">This system was developed as part of an academic project under the CCS department, serving the needs of students and faculty in the computer laboratories.</p>
        </div>
        <div class="institution-cards">
            <div class="institution-card">
                <div class="institution-badge">UC MAIN</div>
                <h3>University of Cebu</h3>
                <p>A leading institution in Cebu City offering quality education across multiple disciplines. UC is committed to academic excellence, innovation, and the holistic development of its students.</p>
            </div>
            <div class="institution-card">
                <div class="institution-badge">CCS DEPT</div>
                <h3>College of Computer Studies</h3>
                <p>The CCS department offers programs in BS Computer Science, BS Information Technology, BS Information Systems, and Associate in Computer Technology — shaping the next generation of tech professionals.</p>
            </div>
        </div>
    </div>
</section>

<!-- HOW IT WORKS SECTION -->
<section class="container-wide">
    <div class="about-section">
        <div class="section-header">
            <div class="section-tag">How It Works</div>
            <h2 class="section-title">Using the System — Step by Step</h2>
            <p class="section-description">Getting started is simple. Follow these steps to access the Sit-in Monitoring System as a student.</p>
        </div>
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">01</div>
                <div class="step-emoji">📝</div>
                <h4>Register</h4>
                <p>Create your account using your student ID number, name, course, year level, and a secure password. Registration is a one-time process.</p>
            </div>
            <div class="step-card">
                <div class="step-number">02</div>
                <div class="step-emoji">🔑</div>
                <h4>Log In</h4>
                <p>Enter your registered ID number and password to securely access your personal dashboard on any device connected to the network.</p>
            </div>
            <div class="step-card">
                <div class="step-number">03</div>
                <div class="step-emoji">🖥️</div>
                <h4>Check In</h4>
                <p>Once logged in, initiate a sit-in session in the available laboratory. Your session will be recorded automatically.</p>
            </div>
            <div class="step-card">
                <div class="step-number">04</div>
                <div class="step-emoji">📋</div>
                <h4>Track History</h4>
                <p>View your past sit-in sessions, remaining session allowance, and attendance records from your dashboard anytime.</p>
            </div>
        </div>
    </div>
</section>

<!-- DEVELOPER SECTION -->
<section class="container-wide">
    <div class="dev-card">

        <!-- LEFT -->
        <div class="dev-left">
            <img src="uploads/ems.jpg" alt="Frechie Ann Torino" class="dev-profile-pic">
            <div class="dev-left-text">
                <div class="dev-eyebrow">Meet the Developer</div>
                <h2>Frechie Ann Torino</h2>
                <div class="developer-name">3rd Year BSIT &middot; UC Main</div>
            </div>
            <div class="dev-tags">
                <span class="dev-tag">Full-Stack</span>
                <span class="dev-tag">Web Dev</span>
                <span class="dev-tag">CCS</span>
            </div>
        </div>

        <!-- RIGHT -->
        <div class="dev-right">
            <div>
                <div class="dev-section-label">
                    <i class="bi bi-code-slash"></i> About
                </div>
                <p class="developer-bio">
                    3rd year BS Information Technology student at the University of Cebu. Built this Sit-in Monitoring System as an academic project to bring a smarter, digital solution to CCS laboratory management.
                </p>
            </div>
            <div>
                <div class="dev-section-label">
                    <i class="bi bi-stack"></i> Tech Stack
                </div>
                <div class="tech-stack">
                    <span class="tech-badge"><i class="bi bi-file-code me-1"></i>Python</span>
                    <span class="tech-badge"><i class="bi bi-server me-1"></i>Flask</span>
                    <span class="tech-badge"><i class="bi bi-database me-1"></i>SQLite</span>
                    <span class="tech-badge"><i class="bi bi-filetype-html me-1"></i>HTML / CSS</span>
                    <span class="tech-badge"><i class="bi bi-filetype-js me-1"></i>JavaScript</span>
                </div>
            </div>
            <div class="dev-footer-row">
                <i class="bi bi-building me-2"></i>
                University of Cebu — College of Computer Studies
            </div>
        </div>

    </div>
</section>

<!-- FOOTER -->
<footer>
    <p>&copy; <?php echo date("Y"); ?> CCS Sit-in Monitoring System. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
