<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "sysarch");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$id_number = $_SESSION['user'];

// ── Handle Profile Update ────────────────────────────────────────────
if (isset($_POST['update_profile'])) {
    $fname   = $conn->real_escape_string(trim($_POST['first_name']));
    $mname   = $conn->real_escape_string(trim($_POST['middle_name']));
    $lname   = $conn->real_escape_string(trim($_POST['last_name']));
    $email   = $conn->real_escape_string(trim($_POST['email']));
    $course  = $conn->real_escape_string(trim($_POST['course']));
    $level   = intval($_POST['year_level']);
    $newpw   = trim($_POST['password'] ?? '');
    $img_sql = "";

    // Handle profile picture upload
    if (!empty($_FILES['profile_pic']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $ext          = pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION);
        $new_filename = "user_" . $id_number . "_" . time() . "." . $ext;
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_dir . $new_filename)) {
            $img_sql = ", profile_pic = '$new_filename'";
        }
    }

    if (!empty($newpw)) {
        $hashed = password_hash($newpw, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET first_name='$fname', middle_name='$mname', last_name='$lname', email='$email', course='$course', year_level=$level, password='$hashed' $img_sql WHERE id_number='$id_number'");
    } else {
        $conn->query("UPDATE users SET first_name='$fname', middle_name='$mname', last_name='$lname', email='$email', course='$course', year_level=$level $img_sql WHERE id_number='$id_number'");
    }

    $_SESSION['user_name'] = $fname . ' ' . $lname;
    $profile_success = "Profile updated successfully.";
}

// ── Handle Reservation ───────────────────────────────────────────────
if (isset($_POST['submit_reservation'])) {
    $purpose = $conn->real_escape_string(trim($_POST['res_purpose']));
    $lab     = $conn->real_escape_string(trim($_POST['res_lab']));
    $timein  = $conn->real_escape_string(trim($_POST['res_timein']));
    $date    = $conn->real_escape_string(trim($_POST['res_date']));

    $dup = $conn->query("SELECT id FROM sitin_records WHERE id_number='$id_number' AND login_time LIKE '$date%' AND logout_time IS NULL");
    if ($dup && $dup->num_rows > 0) {
        $res_error = "You already have an active sit-in on that date.";
    } else {
        $conn->query("INSERT INTO reservations (id_number, purpose, lab, preferred_time, reservation_date, status, created_at)
                      VALUES ('$id_number','$purpose','$lab','$timein','$date','Pending',NOW())");
        $res_success = "Reservation submitted successfully!";
    }
}

// ── Cancel Reservation ───────────────────────────────────────────────
if (isset($_POST['cancel_reservation'])) {
    $rid = intval($_POST['res_id']);
    $conn->query("DELETE FROM reservations WHERE id=$rid AND id_number='$id_number'");
    $res_success = "Reservation cancelled.";
}

// ── Fetch user ───────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM users WHERE id_number = ?");
$stmt->bind_param("s", $id_number);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) { echo "User not found."; exit; }

$profile_pic = !empty($user['profile_pic'])
    ? "uploads/" . $user['profile_pic']
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'].'+'.($user['last_name']??'')) . '&background=9757d6&color=fff&size=150';

// ── Session credits ──────────────────────────────────────────────────
$rem             = intval($user['remaining_session'] ?? 30);
$max_credits     = 30;
$used_sessions   = $max_credits - $rem;
$credits_percent = round(($rem / $max_credits) * 100);
$credits_color   = $rem > 15 ? '#27ae60' : ($rem > 5 ? '#f39c12' : '#e74c3c');

// ── Fetch announcements ──────────────────────────────────────────────
$announcements = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");

// ── Fetch sit-in history ─────────────────────────────────────────────
$stmt2 = $conn->prepare("SELECT * FROM sitin_records WHERE id_number = ? ORDER BY login_time DESC");
$stmt2->bind_param("s", $id_number);
$stmt2->execute();
$sessions = $stmt2->get_result();
$stmt2->close();

// ── Fetch reservations ───────────────────────────────────────────────
$res_table_exists = $conn->query("SHOW TABLES LIKE 'reservations'")->num_rows > 0;
$reservations = null;
if ($res_table_exists) {
    $stmt3 = $conn->prepare("SELECT * FROM reservations WHERE id_number = ? ORDER BY reservation_date DESC");
    $stmt3->bind_param("s", $id_number);
    $stmt3->execute();
    $reservations = $stmt3->get_result();
    $stmt3->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | CCS Sit-in</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --uc-blue: #a1cbf7;
            --ccs-purple: #9757d6;
            --ccs-gold: #FFD700;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            overflow-x: hidden;
        }

        /* ── Navbar ── */
        .navbar {
            background-color: var(--uc-blue);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 10px 20px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .navbar-brand {
            font-weight: 300;
            color: white !important;
            font-size: 0.9rem;
            letter-spacing: 0.3px;
        }
        .nav-link {
            color: rgba(43,94,124,0.9) !important;
            font-weight: 500;
            font-size: 0.85rem;
            border-radius: 6px;
            padding: 0.4rem 0.75rem !important;
            transition: background 0.2s;
        }
        .nav-link:hover {
            background: rgba(255,255,255,0.3);
            color: #1a5276 !important;
        }
        .btn-logout-nav {
            background-color: var(--ccs-gold);
            color: #333 !important;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            padding: 6px 18px;
            font-size: 0.85rem;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-logout-nav:hover { background-color: #e6c200; color: #222 !important; }

        /* ── Hero ── */
        .hero-section {
            background: linear-gradient(135deg, var(--ccs-purple) 0%, var(--uc-blue) 100%);
            color: white;
            padding: 40px 20px 70px;
            border-bottom-left-radius: 35px;
            border-bottom-right-radius: 35px;
            text-align: center;
        }
        .hero-section h2 {
            font-weight: 800;
            font-size: 1.6rem;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .hero-section p { opacity: 0.8; font-size: 0.88rem; margin: 0; }

        /* ── Main lifted card ── */
        .main-wrapper {
            margin-top: -40px;
            padding: 0 20px 40px;
        }

        /* ── Cards ── */
        .dash-card {
            background: white;
            border-radius: 18px;
            box-shadow: 0 6px 24px rgba(151,87,214,0.08);
            border: 1px solid rgba(0,0,0,0.04);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .card-header-purple {
            background: linear-gradient(135deg, var(--ccs-purple), #7c45b8);
            color: white;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 11px 18px;
        }
        .card-header-gold {
            background: var(--ccs-gold);
            color: #4a3800;
            font-weight: 700;
            font-size: 0.88rem;
            padding: 11px 18px;
        }
        .card-header-blue {
            background: linear-gradient(135deg, #3a9bd5, #5ab4f0);
            color: white;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 11px 18px;
        }

        /* ── Avatar ── */
        .avatar-wrap {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto 12px;
        }
        .avatar-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 14px rgba(151,87,214,0.25);
        }

        /* ── Info rows ── */
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 7px 0;
            border-bottom: 1px solid #f5f5f5;
            font-size: 0.84rem;
            gap: 8px;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #aaa; white-space: nowrap; }
        .info-value { font-weight: 600; color: #333; text-align: right; word-break: break-word; }

        /* ── Session credits ── */
        .credits-box {
            background: linear-gradient(135deg, #f8f1fe, #eef6ff);
            border-radius: 12px;
            padding: 14px 16px;
            margin: 14px 0;
            border: 1px solid rgba(151,87,214,0.1);
        }
        .credits-title {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--ccs-purple);
            font-weight: 700;
            margin-bottom: 8px;
        }
        .credits-nums { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 8px; }
        .credits-big  { font-size: 1.8rem; font-weight: 800; line-height: 1; }
        .credits-sub  { font-size: 0.72rem; color: #aaa; }
        .credits-bar  { height: 8px; border-radius: 4px; background: #e8e0f0; overflow: hidden; }
        .credits-fill { height: 100%; border-radius: 4px; transition: width 0.5s ease; }

        /* ── Action buttons ── */
        .action-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 14px;
        }
        .btn-action {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 12px 6px;
            border-radius: 12px;
            border: 1.5px solid #eee;
            background: white;
            color: #555;
            font-size: 0.73rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-action i { font-size: 1.15rem; }
        .btn-action:hover {
            border-color: var(--ccs-purple);
            background: #f8f1fe;
            color: var(--ccs-purple);
        }
        .btn-action.purple i { color: var(--ccs-purple); }
        .btn-action.orange i { color: #e67e22; }
        .btn-action.blue   i { color: #2980b9; }
        .btn-action.green  i { color: #27ae60; }

        /* ── Rules ── */
        .rules-list { padding-left: 1.2rem; margin: 0; }
        .rules-list li {
            padding: 5px 0;
            border-bottom: 1px solid #f5f5f5;
            font-size: 0.84rem;
            color: #444;
        }
        .rules-list li:last-child { border-bottom: none; }
        .rules-list li::marker { color: var(--ccs-purple); }

        /* ── Announcements ── */
        .ann-item { padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
        .ann-item:last-child { border-bottom: none; }
        .ann-item .ann-admin {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--ccs-purple);
            margin-bottom: 3px;
        }
        .ann-item .ann-text { font-size: 0.84rem; color: #444; margin: 0; }

        /* ── Tables ── */
        .table thead th {
            background: linear-gradient(135deg, var(--ccs-purple), #7c45b8);
            color: white;
            font-size: 0.8rem;
            font-weight: 600;
            border: none;
            padding: 9px 12px;
        }
        .table tbody td {
            font-size: 0.82rem;
            vertical-align: middle;
            padding: 8px 12px;
        }
        .table tbody tr:hover { background: #f8f1fe; }
        .table { margin-bottom: 0; }

        /* ── Badges ── */
        .badge-active    { background: #27ae60; color: white; padding: 3px 9px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; }
        .badge-done      { background: #bdc3c7; color: #555; padding: 3px 9px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; }
        .badge-pending   { background: #f39c12; color: white; padding: 3px 9px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; }
        .badge-approved  { background: #27ae60; color: white; padding: 3px 9px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; }
        .badge-cancelled { background: #95a5a6; color: white; padding: 3px 9px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; }

        /* ── Modal ── */
        .modal-header-purple {
            background: linear-gradient(135deg, var(--ccs-purple), #7c45b8);
            color: white;
            border-radius: 16px 16px 0 0;
            padding: 14px 20px;
        }
        .modal-header-purple .btn-close { filter: invert(1); }
        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 20px 60px rgba(151,87,214,0.2);
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--ccs-purple);
            box-shadow: 0 0 0 3px rgba(151,87,214,0.12);
        }
        .form-control, .form-select { border-radius: 8px; font-size: 0.85rem; }
        .field-label { font-size: 0.75rem; color: #888; font-weight: 500; margin-bottom: 3px; }

        /* ── Btn Purple ── */
        .btn-purple {
            background: linear-gradient(135deg, var(--ccs-purple), #7c45b8);
            color: white; border: none; border-radius: 8px;
            font-weight: 600; font-size: 0.85rem;
            transition: opacity 0.2s;
        }
        .btn-purple:hover { opacity: 0.88; color: white; }
        .btn-gold {
            background: var(--ccs-gold); color: #4a3800;
            border: none; border-radius: 8px;
            font-weight: 700; font-size: 0.85rem;
        }
        .btn-gold:hover { background: #e6c200; color: #333; }

        /* ── Reservation form card ── */
        .res-form-box {
            background: linear-gradient(135deg, #f8f1fe, #eef6ff);
            border-radius: 12px;
            padding: 1.2rem;
            margin-bottom: 1.2rem;
            border: 1px solid rgba(151,87,214,0.12);
        }
        .res-form-box .res-title {
            font-size: 0.68rem;
            color: var(--ccs-purple);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 12px;
        }

        footer {
            padding: 24px 0;
            text-align: center;
            color: #bbb;
            font-size: 0.78rem;
        }
    </style>
</head>
<body>

<!-- ── Navbar ── -->
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <i class="bi bi-pc-display-horizontal me-2"></i>CCS Sit-in Monitoring
        </a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <span style="color:rgba(43,94,124,0.9);font-size:0.85rem;font-weight:500;">
                Welcome, <strong><?= htmlspecialchars($user['first_name']) ?></strong>
            </span>
            <a href="landingpage.php" class="btn-logout-nav">
                <i class="bi bi-box-arrow-right me-1"></i>Log out
            </a>
        </div>
    </div>
</nav>

<!-- ── Hero ── -->
<div class="hero-section">
    <h2>Student Dashboard</h2>
    <p>College of Computer Studies · Sit-in Monitoring System</p>
</div>

<!-- ── Main Content ── -->
<div class="main-wrapper">
    <div class="container-fluid px-2 px-md-3">

        <?php if (isset($profile_success)): ?>
            <div class="alert alert-success alert-dismissible fade show py-2 px-3 mb-3" style="border-radius:10px;font-size:0.85rem;">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($profile_success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-3">

            <!-- ══ LEFT: Profile ══ -->
            <div class="col-lg-3 col-md-4">

                <!-- Profile Card -->
                <div class="dash-card">
                    <div class="card-header-purple">
                        <i class="bi bi-person-fill me-2"></i>Student Information
                    </div>
                    <div class="card-body p-3 text-center">
                        <div class="avatar-wrap">
                            <img src="<?= $profile_pic ?>" id="avatarPreview" class="avatar-img"
                                onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['first_name'].' '.$user['last_name']) ?>&background=9757d6&color=fff&size=150'">
                        </div>
                        <div style="font-weight:700;font-size:1rem;color:#222;"><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></div>
                        <div style="font-size:0.75rem;color:#aaa;margin-bottom:12px;"><?= htmlspecialchars($user['course']) ?> &mdash; Year <?= $user['year_level'] ?></div>

                        <div class="info-row">
                            <span class="info-label"><i class="bi bi-card-text me-1"></i>Name</span>
                            <span class="info-value"><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="bi bi-book me-1"></i>Course</span>
                            <span class="info-value"><?= htmlspecialchars($user['course']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="bi bi-calendar3 me-1"></i>Year</span>
                            <span class="info-value">Year <?= $user['year_level'] ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="bi bi-envelope me-1"></i>Email</span>
                            <span class="info-value" style="font-size:0.75rem;"><?= htmlspecialchars($user['email']) ?></span>
                        </div>

                        <!-- Session Credits -->
                        <div class="credits-box">
                            <div class="credits-title"><i class="bi bi-ticket-perforated me-1"></i>Session Credits</div>
                            <div class="credits-nums">
                                <div>
                                    <div class="credits-big" style="color:<?= $credits_color ?>;"><?= $rem ?></div>
                                    <div class="credits-sub">remaining</div>
                                </div>
                                <div class="text-end">
                                    <div style="font-size:0.72rem;color:#bbb;">Used</div>
                                    <div style="font-size:0.95rem;font-weight:700;color:#555;"><?= $used_sessions ?> / <?= $max_credits ?></div>
                                </div>
                            </div>
                            <div class="credits-bar">
                                <div class="credits-fill" style="width:<?= $credits_percent ?>%;background:<?= $credits_color ?>;"></div>
                            </div>
                            <?php if ($rem <= 5): ?>
                                <div style="font-size:0.73rem;color:#e74c3c;margin-top:6px;">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Low credits! Contact admin.
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Action Buttons -->
                        <div class="action-grid">
                            <a href="#" class="btn-action purple" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="bi bi-pencil-square"></i>Edit Profile
                            </a>
                            <a href="#" class="btn-action orange" data-bs-toggle="modal" data-bs-target="#notifModal">
                                <i class="bi bi-bell-fill"></i>Notifications
                            </a>
                            <a href="#" class="btn-action blue" data-bs-toggle="modal" data-bs-target="#historyModal">
                                <i class="bi bi-clock-history"></i>History
                            </a>
                            <a href="#" class="btn-action green" data-bs-toggle="modal" data-bs-target="#reservationModal">
                                <i class="bi bi-calendar-check-fill"></i>Reservation
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Rules Card -->
                <div class="dash-card">
                    <div class="card-header-gold">
                        <i class="bi bi-journal-text me-2"></i>Rules &amp; Regulations
                    </div>
                    <div class="card-body p-3">
                        <ul class="rules-list">
                            <li>No food or drinks inside the laboratory.</li>
                            <li>Maintain silence and proper decorum.</li>
                            <li>Switch off personal devices.</li>
                            <li>Games are not allowed in the lab.</li>
                            <li>Maximum sit-in session is 3 hours.</li>
                            <li>Always log your sit-in properly.</li>
                            <li>Respect all laboratory equipment.</li>
                        </ul>
                    </div>
                </div>

            </div>
            <!-- END LEFT -->

            <!-- ══ CENTER: Announcements ══ -->
            <div class="col-lg-5 col-md-8">
                <div class="dash-card h-100">
                    <div class="card-header-purple">
                        <i class="bi bi-megaphone-fill me-2"></i>Announcement
                    </div>
                    <div class="card-body p-3">
                        <?php
                        if ($announcements && $announcements->num_rows > 0):
                            while ($a = $announcements->fetch_assoc()):
                                $date = date('Y-M-d', strtotime($a['created_at']));
                        ?>
                            <div class="ann-item">
                                <div class="ann-admin">
                                    <?= htmlspecialchars($a['admin_name']) ?>
                                    <span class="text-muted fw-normal">| <?= $date ?></span>
                                </div>
                                <p class="ann-text"><?= htmlspecialchars($a['message']) ?></p>
                            </div>
                        <?php endwhile; else: ?>
                            <p class="text-muted mb-0" style="font-size:0.85rem;">No announcements yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ══ RIGHT: Rules (desktop extra) ══ -->
            <div class="col-lg-4 d-none d-lg-block">
                <div class="dash-card h-100">
                    <div class="card-header-blue">
                        <i class="bi bi-shield-check me-2"></i>Laboratory Rules &amp; Regulations
                    </div>
                    <div class="card-body p-3">
                        <p style="font-weight:700;text-align:center;color:var(--ccs-purple);font-size:0.88rem;">University of Cebu</p>
                        <p style="font-weight:600;text-align:center;color:#555;font-size:0.78rem;margin-bottom:14px;">COLLEGE OF COMPUTER STUDIES</p>
                        <p style="font-weight:700;font-size:0.82rem;color:#333;margin-bottom:8px;">LABORATORY RULES AND REGULATIONS</p>
                        <p style="font-size:0.8rem;color:#555;margin-bottom:8px;">To avoid embarrassment and maintain camaraderie with your friends and superiors at our laboratories, please observe the following:</p>
                        <ol style="padding-left:1.2rem;">
                            <li style="font-size:0.8rem;color:#444;padding:4px 0;border-bottom:1px solid #f5f5f5;">Maintain silence, proper decorum, and discipline inside the laboratory.</li>
                            <li style="font-size:0.8rem;color:#444;padding:4px 0;border-bottom:1px solid #f5f5f5;">Games are not allowed inside the lab.</li>
                            <li style="font-size:0.8rem;color:#444;padding:4px 0;border-bottom:1px solid #f5f5f5;">Surfing the Internet is allowed only with the permission of the instructor.</li>
                            <li style="font-size:0.8rem;color:#444;padding:4px 0;border-bottom:1px solid #f5f5f5;">Eating, drinking, and smoking are strictly prohibited.</li>
                            <li style="font-size:0.8rem;color:#444;padding:4px 0;border-bottom:1px solid #f5f5f5;">Observe proper sitting posture at all times.</li>
                            <li style="font-size:0.8rem;color:#444;padding:4px 0;border-bottom:1px solid #f5f5f5;">Report any damaged equipment to the laboratory attendant.</li>
                            <li style="font-size:0.8rem;color:#444;padding:4px 0;">Seats are to be arranged neatly after use.</li>
                        </ol>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<footer>&copy; <?= date('Y') ?> University of Cebu &mdash; College of Computer Studies | CCS Sit-in Monitoring System</footer>


<!-- ════ EDIT PROFILE MODAL ════ -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header-purple d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Profile</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST" enctype="multipart/form-data">

                    <!-- Profile picture preview -->
                    <div class="text-center mb-3">
                        <div style="position:relative;width:90px;height:90px;margin:0 auto;">
                            <img src="<?= $profile_pic ?>" id="editAvatarPreview"
                                style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:4px solid white;box-shadow:0 4px 14px rgba(151,87,214,0.25);"
                                onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['first_name'].' '.$user['last_name']) ?>&background=9757d6&color=fff&size=90'">
                            <label for="profile_pic_input" style="position:absolute;bottom:2px;right:2px;background:var(--ccs-purple);color:white;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;border:2px solid white;font-size:0.75rem;">
                                <i class="bi bi-camera-fill"></i>
                            </label>
                        </div>
                        <input type="file" name="profile_pic" id="profile_pic_input" hidden accept="image/*">
                        <div style="font-size:0.72rem;color:#aaa;margin-top:6px;">Click camera to change photo</div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="field-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="field-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="field-label">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($user['middle_name'] ?? '') ?>">
                    </div>
                    <div class="mb-2">
                        <label class="field-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-8">
                            <label class="field-label">Course</label>
                            <select name="course" class="form-select" required>
                                <option value="">-- Select Course --</option>
                                <option value="Information Technology" <?= $user['course']==='Information Technology'?'selected':'' ?>>Information Technology</option>
                                <option value="Computer Engineering" <?= $user['course']==='Computer Engineering'?'selected':'' ?>>Computer Engineering</option>
                                <option value="Civil Engineering" <?= $user['course']==='Civil Engineering'?'selected':'' ?>>Civil Engineering</option>
                                <option value="Mechanical Engineering" <?= $user['course']==='Mechanical Engineering'?'selected':'' ?>>Mechanical Engineering</option>
                                <option value="Electrical Engineering" <?= $user['course']==='Electrical Engineering'?'selected':'' ?>>Electrical Engineering</option>
                                <option value="Industrial Engineering" <?= $user['course']==='Industrial Engineering'?'selected':'' ?>>Industrial Engineering</option>
                                <option value="Naval Architecture and Marine Engineering" <?= $user['course']==='Naval Architecture and Marine Engineering'?'selected':'' ?>>Naval Architecture and Marine Engineering</option>
                                <option value="Elementary Education (BEEd)" <?= $user['course']==='Elementary Education (BEEd)'?'selected':'' ?>>Elementary Education (BEEd)</option>
                                <option value="Secondary Education (BSEd)" <?= $user['course']==='Secondary Education (BSEd)'?'selected':'' ?>>Secondary Education (BSEd)</option>
                                <option value="Criminology" <?= $user['course']==='Criminology'?'selected':'' ?>>Criminology</option>
                                <option value="Commerce" <?= $user['course']==='Commerce'?'selected':'' ?>>Commerce</option>
                                <option value="Accountancy" <?= $user['course']==='Accountancy'?'selected':'' ?>>Accountancy</option>
                                <option value="Hotel and Restaurant Management" <?= $user['course']==='Hotel and Restaurant Management'?'selected':'' ?>>Hotel and Restaurant Management</option>
                                <option value="Customs Administration" <?= $user['course']==='Customs Administration'?'selected':'' ?>>Customs Administration</option>
                                <option value="Computer Secretarial" <?= $user['course']==='Computer Secretarial'?'selected':'' ?>>Computer Secretarial</option>
                                <option value="Industrial Psychology" <?= $user['course']==='Industrial Psychology'?'selected':'' ?>>Industrial Psychology</option>
                                <option value="AB Political Science" <?= $user['course']==='AB Political Science'?'selected':'' ?>>AB Political Science</option>
                                <option value="AB English" <?= $user['course']==='AB English'?'selected':'' ?>>AB English</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="field-label">Year Level</label>
                            <select name="year_level" class="form-select" required>
                                <option value="1" <?= $user['year_level']==1?'selected':'' ?>>1st Year</option>
                                <option value="2" <?= $user['year_level']==2?'selected':'' ?>>2nd Year</option>
                                <option value="3" <?= $user['year_level']==3?'selected':'' ?>>3rd Year</option>
                                <option value="4" <?= $user['year_level']==4?'selected':'' ?>>4th Year</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="field-label">New Password <span class="text-muted">(leave blank to keep current)</span></label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••">
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_profile" class="btn btn-purple px-4">
                            <i class="bi bi-save-fill me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- ════ NOTIFICATIONS MODAL ════ -->
<div class="modal fade" id="notifModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header-purple d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-bell-fill me-2"></i>Notifications</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <?php
                $notif = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5");
                if ($notif && $notif->num_rows > 0):
                    while ($n = $notif->fetch_assoc()):
                        $nd = date('Y-M-d', strtotime($n['created_at']));
                ?>
                    <div class="d-flex gap-3 align-items-start pb-3 mb-3 border-bottom">
                        <div style="width:38px;height:38px;min-width:38px;border-radius:50%;background:#f3e9fd;display:flex;align-items:center;justify-content:center;">
                            <i class="bi bi-megaphone-fill" style="color:var(--ccs-purple);font-size:0.88rem;"></i>
                        </div>
                        <div>
                            <div style="font-size:0.85rem;font-weight:600;color:#333;"><?= htmlspecialchars($n['admin_name']) ?></div>
                            <div style="font-size:0.8rem;color:#666;"><?= htmlspecialchars($n['message']) ?></div>
                            <div style="font-size:0.72rem;color:#bbb;margin-top:3px;"><i class="bi bi-calendar3 me-1"></i><?= $nd ?></div>
                        </div>
                    </div>
                <?php endwhile; else: ?>
                    <p class="text-muted text-center mb-0"><i class="bi bi-bell-slash me-2"></i>No notifications yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<!-- ════ HISTORY MODAL ════ -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header-purple d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Sit-in History</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Login Time</th>
                                <th>Purpose</th>
                                <th>Lab</th>
                                <th>Logout Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $sessions->data_seek(0);
                        if ($sessions && $sessions->num_rows > 0):
                            while ($s = $sessions->fetch_assoc()):
                                $is_active = empty($s['logout_time']);
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($s['login_time']) ?></td>
                                <td><?= htmlspecialchars($s['purpose']) ?></td>
                                <td><?= htmlspecialchars($s['lab']) ?></td>
                                <td><?= $is_active ? '<span class="text-muted">—</span>' : htmlspecialchars($s['logout_time']) ?></td>
                                <td>
                                    <?php if ($is_active): ?>
                                        <span class="badge-active">Active</span>
                                    <?php else: ?>
                                        <span class="badge-done">Done</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="5" class="text-center text-muted py-3"><i class="bi bi-inbox me-2"></i>No sit-in history found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- ════ RESERVATION MODAL ════ -->
<div class="modal fade" id="reservationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header-purple d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-calendar-check-fill me-2"></i>Reservations</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">

                <?php if (isset($res_error)): ?>
                    <div class="alert alert-danger py-2 px-3 mb-3" style="border-radius:8px;font-size:0.83rem;">
                        <i class="bi bi-exclamation-circle-fill me-2"></i><?= htmlspecialchars($res_error) ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($res_success)): ?>
                    <div class="alert alert-success py-2 px-3 mb-3" style="border-radius:8px;font-size:0.83rem;">
                        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($res_success) ?>
                    </div>
                <?php endif; ?>

                <!-- New Reservation Form -->
                <div class="res-form-box">
                    <div class="res-title"><i class="bi bi-plus-circle-fill me-1"></i>New Reservation</div>
                    <form method="POST">
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <label class="field-label">ID Number</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($user['id_number']) ?>" readonly style="background:#ede6f5;font-weight:600;">
                            </div>
                            <div class="col-sm-6">
                                <label class="field-label">Name</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?>" readonly style="background:#ede6f5;font-weight:600;">
                            </div>
                            <div class="col-sm-6">
                                <label class="field-label">Purpose</label>
                                <select name="res_purpose" class="form-select" required>
                                    <option value="">-- Select Purpose --</option>
                                    <option>C Programming</option>
                                    <option>C++</option>
                                    <option>Java</option>
                                    <option>ASP.Net</option>
                                    <option>PHP</option>
                                    <option>Python</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="field-label">Lab</label>
                                <select name="res_lab" class="form-select" required>
                                    <option value="">-- Select Lab --</option>
                                    <option>524</option>
                                    <option>526</option>
                                    <option>528</option>
                                    <option>530</option>
                                    <option>542</option>
                                    <option>Mac Lab</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="field-label">Preferred Time</label>
                                <input type="time" name="res_timein" class="form-control" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="field-label">Date</label>
                                <input type="date" name="res_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" name="submit_reservation" class="btn btn-purple px-4">
                                <i class="bi bi-calendar-plus me-2"></i>Submit Reservation
                            </button>
                        </div>
                    </form>
                </div>

                <!-- My Reservations -->
                <div style="font-size:0.68rem;color:#aaa;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">
                    <i class="bi bi-list-ul me-1"></i>My Reservations
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size:0.82rem;">
                        <thead>
                            <tr>
                                <th>Date</th><th>Purpose</th><th>Lab</th><th>Time</th><th>Status</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($res_table_exists && $reservations && $reservations->num_rows > 0):
                            while ($r = $reservations->fetch_assoc()):
                                $rbadge = match(strtolower($r['status'])) {
                                    'pending'   => 'badge-pending',
                                    'approved'  => 'badge-approved',
                                    'cancelled' => 'badge-cancelled',
                                    default     => 'badge-pending'
                                };
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($r['reservation_date']) ?></td>
                                <td><?= htmlspecialchars($r['purpose']) ?></td>
                                <td><?= htmlspecialchars($r['lab']) ?></td>
                                <td><?= htmlspecialchars($r['preferred_time']) ?></td>
                                <td><span class="<?= $rbadge ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                                <td>
                                    <?php if (strtolower($r['status']) === 'pending'): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this reservation?')">
                                            <input type="hidden" name="res_id" value="<?= $r['id'] ?>">
                                            <button name="cancel_reservation" class="btn btn-sm btn-danger" style="font-size:0.73rem;padding:2px 8px;border-radius:6px;">
                                                <i class="bi bi-x-circle me-1"></i>Cancel
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size:0.75rem;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">
                                <i class="bi bi-calendar-x me-2"></i>No reservations yet.
                            </td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Profile picture preview
document.getElementById('profile_pic_input').addEventListener('change', function () {
    const [file] = this.files;
    if (file) {
        const url = URL.createObjectURL(file);
        document.getElementById('editAvatarPreview').src = url;
        document.getElementById('avatarPreview').src = url;
    }
});

// Auto-open reservation modal if form was submitted
<?php if (isset($res_error) || isset($res_success)): ?>
document.addEventListener('DOMContentLoaded', function () {
    new bootstrap.Modal(document.getElementById('reservationModal')).show();
});
<?php endif; ?>

// Auto-open edit modal if profile was updated
<?php if (isset($profile_success)): ?>
// Profile success - just show the alert at top
<?php endif; ?>
</script>
</body>
</html>