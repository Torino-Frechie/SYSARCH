<?php
$nav_items = [
    'Home' => 'landingpage.php',
    'About' => 'about.php'
];

/* DATABASE */
$host = 'localhost';
$db   = 'students';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

$top10 = [];

if (!$conn->connect_error) {

    $result = $conn->query("
        SELECT student_id,
               student_name,
               SUM(TIMESTAMPDIFF(MINUTE, time_in, time_out)) AS total_minutes
        FROM sitin
        WHERE time_in IS NOT NULL
        AND time_out IS NOT NULL
        GROUP BY student_id, student_name
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

$av_colors = [
    'av-gold',
    'av-blue',
    'av-teal',
    'av-red',
    'av-green',
    'av-purple',
    'av-bronze',
    'av-pink',
    'av-orange',
    'av-cyan'
];

function getInitials($name)
{
    $parts = explode(' ', trim($name));
    $out = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $out .= strtoupper(mb_substr($p, 0, 1));
    }
    return $out;
}

function shortName($name)
{
    $parts = explode(' ', trim($name));
    $first = $parts[0];
    $last = count($parts) > 1
        ? strtoupper(substr(end($parts), 0, 1)) . '.'
        : '';
    return $last ? "$first $last" : $first;
}

function formatDuration($minutes)
{
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
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>CCS Sit-in Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap"
          rel="stylesheet">

    <style>
        :root {
            --uc-blue: #a1cbf7;
            --ccs-purple: #9757d6;
            --navy: #9757d6;
            --navy-light: #2452a0;
            --gold: #f0a500;
            --gold-light: #ffc84a;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f7f6;
            margin: 0;
            overflow-x: hidden;
            animation: fadeIn .7s ease;
        }
        body.fade-out {
            opacity: 0;
            transition: opacity .5s ease;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        /* NAVBAR */
        .navbar {
            background: var(--uc-blue);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 10px;
        }
        .navbar-brand {
            color: white !important;
            font-weight: 600;
            font-size: .95rem;
        }
        .nav-link {
            color: rgba(43,94,124,0.9) !important;
            font-weight: 500;
        }
        .btn-login,
        .btn-register {
            border: 2px solid white !important;
            color: white !important;
            border-radius: 10px;
            font-weight: 600;
            margin-left: 10px;
            transition: .3s;
        }
        .btn-login:hover,
        .btn-register:hover {
            background: white;
            color: var(--uc-blue) !important;
        }
        /* HERO */
        .hero-section {
            background: linear-gradient(
                135deg,
                var(--ccs-purple),
                var(--uc-blue)
            );
            color: white;
            padding: 70px 20px 120px;
            border-bottom-left-radius: 40px;
            border-bottom-right-radius: 40px;
            text-align: center;
        }
        .logo-container img {
            max-height: 120px;
            filter: drop-shadow(
                0 5px 15px rgba(0,0,0,.2)
            );
            transition: .4s;
        }
        .logo-container img:hover {
            transform: scale(1.08) rotate(2deg);
        }
        .system-title {
            font-weight: 800;
            text-transform: uppercase;
            margin-top: 15px;
        }
        /* WELCOME CARD */
        .welcome-card {
            background: white;
            border-radius: 25px;
            padding: 50px;
            margin-top: -70px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
        }
        /* FEATURES */
        .feature-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            transition: .3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .feature-card:hover {
            transform: translateY(-8px);
        }
        .feature-icon {
            font-size: 3rem;
        }
        /* LIVE */
        .live-indicator {
            font-size: 12px;
            color: #28a745;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .live-dot {
            width: 8px;
            height: 8px;
            background: #28a745;
            border-radius: 50%;
            animation: pulse 1s infinite;
        }
        @keyframes pulse {
            0% {
                opacity: 1;
            }
            50% {
                opacity: .3;
            }
            100% {
                opacity: 1;
            }
        }
        /* LEADERBOARD */
        .leaderboard-card {
            background: white;
            border-radius: 25px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .lb-body {
            padding: 30px;
        }
        .podium {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 15px;
            margin-bottom: 25px;
        }
        .podium-item {
            flex: 1;
            text-align: center;
        }
        .podium-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: auto;
            font-weight: 700;
            position: relative;
        }
        .podium-crown {
            position: absolute;
            top: -15px;
            font-size: 16px;
            animation: crownFloat 2s infinite ease-in-out;
        }
        @keyframes crownFloat {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-4px);
            }
            100% {
                transform: translateY(0);
            }
        }
        .podium-name {
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
        }
        .podium-duration {
            font-size: 11px;
            color: #777;
        }
        .podium-bar {
            margin-top: 10px;
            border-radius: 10px 10px 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
        }
        .p1-bar {
            height: 70px;
            background: linear-gradient(
                180deg,
                var(--gold),
                var(--gold-light)
            );
            color: #7a4800;
        }
        .p2-bar {
            height: 55px;
            background: #dfe6f5;
        }
        .p3-bar {
            height: 40px;
            background: #e7c5a0;
        }
        .lb-rank-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 14px;
            background: #f4f7f6;
            margin-bottom: 10px;
            transition: .3s;
        }
        .lb-rank-item:hover {
            transform: translateX(5px);
            background: #eef4ff;
        }
        .lb-rank-num {
            font-weight: 700;
            min-width: 35px;
        }
        .lb-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        .lb-rank-name {
            font-size: 13px;
            font-weight: 600;
        }
        .lb-rank-time {
            font-size: 12px;
            font-weight: 700;
            color: var(--navy);
        }
        .lb-footer {
            border-top: 1px solid #eee;
            padding: 15px;
            text-align: center;
        }
        .lb-footer button {
            border: none;
            background: none;
            color: #3b6fd4;
            font-weight: 600;
        }
        .progress {
            height: 6px;
        }
        /* AVATAR COLORS */
        .av-gold {
            background: #fef3d0;
            color: #7a4800;
        }
        .av-blue {
            background: #e0eaff;
            color: #1a3a80;
        }
        .av-teal {
            background: #d8f5f0;
            color: #0f5a50;
        }
        .av-red {
            background: #ffe8e8;
            color: #801a1a;
        }
        .av-green {
            background: #e2f5e0;
            color: #1a6010;
        }
        .av-purple {
            background: #ede8ff;
            color: #3a1a80;
        }
        .av-bronze {
            background: #f5e8d8;
            color: #7a5a30;
        }
        .av-pink {
            background: #ffe0f0;
            color: #801a50;
        }
        .av-orange {
            background: #fff0e0;
            color: #804000;
        }
        .av-cyan {
            background: #e0f8ff;
            color: #006080;
        }
        /* FOOTER */
        footer {
            padding: 40px 0;
            color: #888;
        }
        /* RESPONSIVE */
        @media(max-width:768px) {
            .logo-container img {
                max-height: 70px;
            }
            .welcome-card {
                padding: 30px 20px;
            }
            .btn-login,
            .btn-register {
                width: 100%;
                margin: 5px 0;
            }
            .podium {
                gap: 8px;
            }
        }

    </style>
</head>

<body>
<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center"
           href="#">
            <img src="uclogo.png"
                 height="32"
                 class="me-2">
            <span>
                CCS Sit-in Monitoring System
            </span>
        </a>
        <button class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse"
             id="mainNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <?php foreach ($nav_items as $name => $url): ?>
                    <li class="nav-item">
                        <a class="nav-link px-3"
                           href="<?= $url ?>">
                            <?= $name ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <li class="nav-item">
                    <a class="btn btn-login px-4"
                       href="login.php">
                        Login
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-register px-4"
                       href="register.php">
                        Register
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- HERO -->
<header class="hero-section">
    <div class="container">
        <div class="logo-container d-flex justify-content-center align-items-center gap-5 mb-3">
            <img src="uclogo.png">
            <img src="ucmainccslogo.png">
        </div>

        <h1 class="display-4 system-title">
            Sit-in Monitoring
        </h1>
    </div>

</header>

<!-- MAIN -->
<main class="container">

    <!-- WELCOME -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="welcome-card text-center">
                <h2 class="fw-bold mb-4"
                    style="color:var(--ccs-purple);">

                    Greetings, CCS!

                </h2>

                <p class="text-muted fs-6">

                    Welcome to the official monitoring portal
                    for the College of Computer Studies.

                    Monitor student laboratory sit-ins,
                    manage sessions, and track activity.

                </p>

                <a href="login.php"
                   class="btn mt-3 px-4 py-2 fw-bold text-white"
                   style="background:linear-gradient(135deg,var(--navy),var(--navy-light));border-radius:10px;">

                    Get Started

                </a>
            </div>
        </div>
    </div>

    <!-- FEATURES -->
    <section class="py-5 mt-4">
        <div class="text-center mb-5">
            <h2 class="fw-bold"
                style="color:var(--ccs-purple);">

                System Features

            </h2>

            <p class="text-muted">
                Powerful tools for efficient monitoring.
            </p>

        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="feature-card text-center p-4 h-100">
                    <div class="feature-icon">💻</div>
                    <h4 class="fw-bold mt-3">
                        Real-time Monitoring
                    </h4>

                    <p class="text-muted mt-2">
                        Monitor laboratory sit-ins instantly.
                    </p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="feature-card text-center p-4 h-100">
                    <div class="feature-icon">📊</div>
                    <h4 class="fw-bold mt-3">
                        Student Analytics
                    </h4>
                    <p class="text-muted mt-2">
                        Track student laboratory activity.
                    </p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="feature-card text-center p-4 h-100">
                    <div class="feature-icon">🔒</div>
                    <h4 class="fw-bold mt-3">
                        Secure System
                    </h4>
                    <p class="text-muted mt-2">
                        Reliable and secure management.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- LEADERBOARD -->
    <section class="pb-5">
        <div class="text-center mb-4">
            <h2 class="fw-bold"
                style="color:var(--ccs-purple);">

                Top Sit-in Students

            </h2>

            <div class="live-indicator mt-2">
                <span class="live-dot"></span>

                Live Rankings

            </div>
        </div>
        <div class="leaderboard-card">
            <div class="lb-body">
                <?php if(count($top10) >= 3):

                    $p1 = $top10[0];
                    $p2 = $top10[1];
                    $p3 = $top10[2];

                ?>
                <div class="podium">

                    <!-- SECOND -->
                    <div class="podium-item">
                        <div class="podium-avatar av-blue">
                            <?= getInitials($p2['student_name']) ?>
                        </div>

                        <div class="podium-name">
                            <?= shortName($p2['student_name']) ?>
                        </div>

                        <div class="podium-duration">
                            <?= formatDuration($p2['total_minutes']) ?>
                        </div>
                        <div class="podium-bar p2-bar">2</div>
                    </div>

                    <!-- FIRST -->
                    <div class="podium-item">
                        <div class="podium-avatar av-gold">
                            <span class="podium-crown">👑</span>
                            <?= getInitials($p1['student_name']) ?>
                        </div>

                        <div class="podium-name">
                            <?= shortName($p1['student_name']) ?>
                        </div>
                        <div class="podium-duration">
                            <?= formatDuration($p1['total_minutes']) ?>
                        </div>
                        <div class="podium-bar p1-bar">1</div>
                    </div>

                    <!-- THIRD -->
                    <div class="podium-item">
                        <div class="podium-avatar av-bronze">
                            <?= getInitials($p3['student_name']) ?>
                        </div>
                        <div class="podium-name">
                            <?= shortName($p3['student_name']) ?>
                        </div>
                        <div class="podium-duration">
                            <?= formatDuration($p3['total_minutes']) ?>
                        </div>
                        <div class="podium-bar p3-bar">3</div>
                    </div>
                </div>

                <!-- 4-10 -->
                <?php foreach(array_slice($top10,3) as $i => $s):
                    $rank = $i + 4;

                    $avClass = $av_colors[$rank % count($av_colors)];

                ?>
                <div class="lb-rank-item">
                    <div class="lb-rank-num">
                        #<?= $rank ?>
                    </div>
                    <div class="lb-avatar <?= $avClass ?>">
                        <?= getInitials($s['student_name']) ?>
                    </div>
                    <div class="flex-grow-1">
                        <div class="lb-rank-name">
                            <?= shortName($s['student_name']) ?>
                        </div>
                        <div class="progress mt-2">
                            <div class="progress-bar"
                                 style="width:<?= min(100, ($s['total_minutes'] / $top10[0]['total_minutes']) * 100) ?>%">
                            </div>
                        </div>
                    </div>
                    <div class="lb-rank-time">
                        <?= formatDuration($s['total_minutes']) ?>
                    </div>

                </div>
                <?php endforeach; ?>
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
<footer class="text-center">
    <div class="container">
        <hr class="w-25 mx-auto mb-4">
        <p class="mb-1 small">
            &copy; <?= date("Y") ?> University of Cebu
        </p>
        <p class="small text-uppercase">
            College of Computer Studies
        </p>
    </div>

</footer>

<!-- MODAL -->
<div id="lbModal"
     style="display:none;position:fixed;inset:0;background:rgba(10,20,50,0.55);backdrop-filter:blur(3px);z-index:1050;align-items:center;justify-content:center;padding:20px;"
     onclick="if(event.target===this){this.style.display='none';}">

    <div style="background:white;border-radius:20px;width:100%;max-width:500px;max-height:90vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(15,38,83,0.35);">
        <div style="background:var(--uc-blue);padding:20px 24px;display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h5 class="fw-bold mb-0"
                    style="color:var(--navy);">

                    🏆 Full Leaderboard

                </h5>
                <p class="mb-0"
                   style="font-size:12px;color:#555;">

                    Top 10 students by total lab time

                </p>
            </div>
            <button onclick="document.getElementById('lbModal').style.display='none'"
                    style="border:none;background:white;width:35px;height:35px;border-radius:10px;font-size:18px;">

                ✕
            </button>
        </div>
        <div style="padding:20px;overflow-y:auto;">
            <?php foreach($top10 as $i => $s):

                $rank = $i + 1;

                $avClass = $av_colors[$i % count($av_colors)];
            ?>

            <div class="lb-rank-item">
                <div class="lb-rank-num">
                    <?php
                    if($rank == 1) echo "🥇";
                    elseif($rank == 2) echo "🥈";
                    elseif($rank == 3) echo "🥉";
                    else echo "#".$rank;
                    ?>
                </div>
                <div class="lb-avatar <?= $avClass ?>">
                    <?= getInitials($s['student_name']) ?>
                </div>
                <div class="flex-grow-1">
                    <div class="lb-rank-name">
                        <?= $s['student_name'] ?>
                    </div>
                    <small class="text-muted">
                        <?= $s['student_id'] ?>
                    </small>
                </div>
                <div class="lb-rank-time">
                    <?= formatDuration($s['total_minutes']) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>

/* PAGE TRANSITION */

document.querySelectorAll('a[href]').forEach(link => {
    link.addEventListener('click', function(e) {
        const href = this.getAttribute('href');

        if(href && !href.startsWith('#')) {
            e.preventDefault();
            document.body.classList.add('fade-out');
            setTimeout(() => {
                window.location.href = href;

            }, 500);
        }
    });
});

/* ESC CLOSE MODAL */

document.addEventListener('keydown', e => {
    if(e.key === 'Escape') {
        document.getElementById('lbModal').style.display = 'none';
    }
});

</script>

</body>
</html>