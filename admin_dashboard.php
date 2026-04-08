<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "sysarch");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ── Handle POST actions ──────────────────────────────────────────────

  $date = $_POST['reservation_date'];

    $stmt = $conn->prepare("UPDATE reservations 
        SET purpose=?, lab=?, preferred_time=?, reservation_date=? 
        WHERE id=?");
    $stmt->bind_param("ssssi", $purpose, $lab, $time, $date, $id);
    $stmt->execute();

    header("Location: admin_dashboard.php?tab=reservation");
    exit();


// DELETE reservation
if (isset($_POST['delete_reservation'])) {
    $id = $_POST['id'];

    $stmt = $conn->prepare("DELETE FROM reservations WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: admin_dashboard.php?tab=reservation");
    exit();
}

// Approve / Reject reservation
if (isset($_POST['update_reservation'])) {
    $res_id = intval($_POST['res_id']);
    $status = $conn->real_escape_string($_POST['status']);

    $conn->query("UPDATE reservations SET status = '$status' WHERE id = $res_id");

    header("Location: admin_dashboard.php?tab=reservation");
    exit();
}

// Post announcement
if (isset($_POST['post_announcement'])) {
    $msg = $conn->real_escape_string(trim($_POST['announcement']));
    if ($msg !== '') {
        $conn->query("INSERT INTO announcements (admin_name, message, created_at) VALUES ('CCS Admin', '$msg', NOW())");
    }
    header("Location: admin_dashboard.php?tab=dashboard"); exit();
}

// Delete announcement
if (isset($_POST['delete_announcement'])) {
    $aid = intval($_POST['ann_id']);
    $conn->query("DELETE FROM announcements WHERE id = $aid");
    header("Location: admin_dashboard.php?tab=announcements"); exit();
}

// Add student
if (isset($_POST['add_student'])) {
    $id_num  = $conn->real_escape_string(trim($_POST['add_idnumber']));
    $fname   = $conn->real_escape_string(trim($_POST['add_firstname']));
    $lname   = $conn->real_escape_string(trim($_POST['add_lastname']));
    $mname   = $conn->real_escape_string(trim($_POST['add_middlename']));
    $course  = $conn->real_escape_string(trim($_POST['add_course']));
    $level   = intval($_POST['add_yearlevel']);
    $email   = $conn->real_escape_string(trim($_POST['add_email']));
    $pass    = password_hash(trim($_POST['add_password']), PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (id_number, first_name, last_name, middle_name, course, year_level, email, password, remaining_session)
                  VALUES ('$id_num','$fname','$lname','$mname','$course',$level,'$email','$pass', 30)");
    header("Location: admin_dashboard.php?tab=students"); exit();
}

// Edit student
if (isset($_POST['edit_student'])) {
    $id_num = $conn->real_escape_string(trim($_POST['edit_idnumber']));
    $fname  = $conn->real_escape_string(trim($_POST['edit_firstname']));
    $lname  = $conn->real_escape_string(trim($_POST['edit_lastname']));
    $mname  = $conn->real_escape_string(trim($_POST['edit_middlename']));
    $course = $conn->real_escape_string(trim($_POST['edit_course']));
    $level  = intval($_POST['edit_yearlevel']);
    $email  = $conn->real_escape_string(trim($_POST['edit_email']));
    $rem    = intval($_POST['edit_remaining']);
    $conn->query("UPDATE users SET first_name='$fname', last_name='$lname', middle_name='$mname',
                  course='$course', year_level=$level, email='$email', remaining_session=$rem
                  WHERE id_number='$id_num'");
    header("Location: admin_dashboard.php?tab=students"); exit();
}

// Delete student
if (isset($_POST['delete_student'])) {
    $id_num = $conn->real_escape_string(trim($_POST['del_idnumber']));
    $conn->query("DELETE FROM users WHERE id_number = '$id_num'");
    header("Location: admin_dashboard.php?tab=students"); exit();
}

// Reset all sessions (students tab)
if (isset($_POST['reset_all_student_sessions'])) {
    $conn->query("UPDATE users SET remaining_session = 30");
    header("Location: admin_dashboard.php?tab=students"); exit();
}

// Sit-in a student
if (isset($_POST['do_sitin'])) {
    $sid     = $conn->real_escape_string(trim($_POST['sitin_id']));
    $purpose = $conn->real_escape_string(trim($_POST['sitin_purpose']));
    $lab     = $conn->real_escape_string(trim($_POST['sitin_lab']));
    $sitin_error = '';
    $chk = $conn->query("SELECT id FROM users WHERE id_number = '$sid'")->fetch_assoc();
    if (!$chk) {
        $sitin_error = "Student not found.";
    } else {
        $active = $conn->query("SELECT id FROM sitin_records WHERE id_number = '$sid' AND logout_time IS NULL")->fetch_assoc();
        if ($active) {
            $sitin_error = "Student already has an active sit-in session.";
        } else {
            $conn->query("INSERT INTO sitin_records (id_number, purpose, lab, login_time) VALUES ('$sid', '$purpose', '$lab', NOW())");
            header("Location: admin_dashboard.php?tab=sitin"); exit();
        }
    }
}

// Logout a sit-in session
if (isset($_POST['logout_session'])) {
    $sit_id = intval($_POST['sit_id']);
    // Get the student's id_number from this sit-in record
    $rec = $conn->query("SELECT id_number FROM sitin_records WHERE id = $sit_id")->fetch_assoc();
    if ($rec) {
        $student_id = $conn->real_escape_string($rec['id_number']);
        // Set logout time
        $conn->query("UPDATE sitin_records SET logout_time = NOW() WHERE id = $sit_id");
        // Decrement remaining_session (floor at 0)
        $conn->query("UPDATE users SET remaining_session = GREATEST(0, remaining_session - 1) WHERE id_number = '$student_id'");
    }
    header("Location: admin_dashboard.php?tab=sitin"); exit();
}

// Reset all active sessions
if (isset($_POST['reset_all_sessions'])) {
    // Get all active student IDs before resetting
    $active_students = $conn->query("SELECT DISTINCT id_number FROM sitin_records WHERE logout_time IS NULL");
    // Set logout on all
    $conn->query("UPDATE sitin_records SET logout_time = NOW() WHERE logout_time IS NULL");
    // Decrement each student's remaining_session
    if ($active_students && $active_students->num_rows > 0) {
        while ($s = $active_students->fetch_assoc()) {
            $sid = $conn->real_escape_string($s['id_number']);
            $conn->query("UPDATE users SET remaining_session = GREATEST(0, remaining_session - 1) WHERE id_number = '$sid'");
        }
    }
    header("Location: admin_dashboard.php?tab=sitin"); exit();
}

// Search student via AJAX
if (isset($_GET['search_student'])) {
    $q = '%' . $conn->real_escape_string(trim($_GET['search_student'])) . '%';
    $result = $conn->query("
        SELECT id_number, first_name, last_name, course, year_level, remaining_session
        FROM users
        WHERE id_number LIKE '$q' OR first_name LIKE '$q' OR last_name LIKE '$q'
        LIMIT 10
    ");
    $rows = [];
    if ($result) while ($r = $result->fetch_assoc()) $rows[] = $r;
    header('Content-Type: application/json');
    echo json_encode($rows);
    exit();
}

// ── Stats ────────────────────────────────────────────────────────────
$total_students  = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'] ?? 0;
$currently_sitin = $conn->query("SELECT COUNT(*) as c FROM sitin_records WHERE logout_time IS NULL")->fetch_assoc()['c'] ?? 0;
$total_sitin     = $conn->query("SELECT COUNT(*) as c FROM sitin_records")->fetch_assoc()['c'] ?? 0;

$lang_result = $conn->query("SELECT purpose, COUNT(*) as cnt FROM sitin_records GROUP BY purpose");
$lang_labels = []; $lang_data = [];
if ($lang_result) while ($row = $lang_result->fetch_assoc()) {
    $lang_labels[] = $row['purpose'];
    $lang_data[]   = $row['cnt'];
}

$active_tab = $_GET['tab'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | CCS Sit-in</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

        /* ─── TOP NAVBAR (MATCH SIDEBAR STYLE) ─── */
.top-navbar {
    position: fixed;
    top: 0;
    left: 0;           /* Ensures it starts at the very left */
    width: 100%;       /* Spans the full width of the screen */
    height: 60px;
    z-index: 1000;     /* Keeps it above other elements */

    display: flex;
    align-items: center;
    justify-content: space-between;

    /* Reduced padding to ensure links don't wrap and colors align */
    padding: 0 20px; 

    background: linear-gradient(160deg, var(--ccs-purple) 0%, #6a3fa0 50%, #2e6da4 100%);
    box-shadow: 0 4px 20px rgba(151,87,214,0.25);
}
/* LEFT TITLE */
.nav-left .brand-title {
    color: white;
    font-weight: 700;
    font-size: 0.95rem;
}

/* NAV LINKS */
.nav-links {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.nav-links a {
    display: flex;
    align-items: center;
    gap: 6px;

    color: rgba(255,255,255,0.75);
    text-decoration: none;

    padding: 6px 10px;
    border-radius: 8px;
    font-size: 0.78rem;

    transition: all 0.2s ease;
}

/* HOVER (same feel as sidebar) */
.nav-links a:hover {
    background: rgba(255,255,255,0.12);
    color: white;
}

/* ACTIVE TAB */
.nav-links a.active {
    background: rgba(255,255,255,0.18);
    color: white;
    font-weight: 600;
}

/* LOGOUT BUTTON */
.btn-logout-top {
    display: flex;
    align-items: center;
    gap: 6px;

    background: rgba(255,215,0,0.15);
    border: 1px solid rgba(255,215,0,0.35);
    color: var(--ccs-gold);

    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 600;
    text-decoration: none;

    transition: all 0.2s;
}

.btn-logout-top:hover {
    background: var(--ccs-gold);
    color: #333;
}

/* ─── FIX CONTENT POSITION ─── */
.main-content {
    margin-left: var(--sidebar-width);
    margin-top: 60px; /* space for navbar */
    padding: 0;
}
        /* ── Main content ── */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 0;
            min-height: 100vh;
        }

        /* ── Top hero bar ── */
        .top-hero {
            background: linear-gradient(135deg, var(--ccs-purple) 0%, var(--uc-blue) 100%);
            padding: 22px 28px 60px;
            color: white;
        }
        .top-hero h4 {
            font-weight: 800;
            font-size: 1.3rem;
            margin: 0;
            color: white;
        }
        .admin-badge {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 20px;
            padding: 4px 14px;
            font-size: 0.78rem;
            font-weight: 600;
            backdrop-filter: blur(4px);
        }

        /* ── Content area (lifted card effect) ── */
        .content-area {
            padding: 0 24px 30px;
            margin-top: -36px;
        }

        /* ── Stat cards ── */
        .stat-card {
            background: white;
            border-radius: 18px;
            padding: 1.2rem 1.4rem;
            box-shadow: 0 8px 24px rgba(151,87,214,0.12);
            display: flex;
            align-items: center;
            gap: 16px;
            border: 1px solid rgba(0,0,0,0.04);
        }
        .stat-icon {
            width: 52px; height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }
        .stat-icon.purple { background: linear-gradient(135deg, var(--ccs-purple), #b47ee8); color: white; }
        .stat-icon.blue   { background: linear-gradient(135deg, #4da8f5, var(--uc-blue)); color: white; }
        .stat-icon.gold   { background: linear-gradient(135deg, #f7c948, var(--ccs-gold)); color: #7a5c00; }
        .stat-num   { font-size: 1.8rem; font-weight: 800; color: #222; line-height: 1; }
        .stat-label { font-size: 0.74rem; color: #aaa; margin-top: 3px; }

        /* ── Dashboard cards ── */
        .dash-card {
            background: white;
            border-radius: 18px;
            box-shadow: 0 4px 20px rgba(151,87,214,0.08);
            border: 1px solid rgba(0,0,0,0.04);
            overflow: hidden;
        }
        .card-header-purple {
            background: linear-gradient(135deg, var(--ccs-purple), #7c45b8);
            color: white;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 12px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-header-gold {
            background: var(--ccs-gold);
            color: #4a3800;
            font-weight: 700;
            font-size: 0.88rem;
            padding: 12px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-header-blue {
            background: linear-gradient(135deg, #3a9bd5, #5ab4f0);
            color: white;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 12px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* ── Tables ── */
        .table thead th {
            background: linear-gradient(135deg, var(--ccs-purple), #7c45b8);
            color: white;
            font-size: 0.8rem;
            font-weight: 600;
            border: none;
            padding: 10px 12px;
        }
        .table tbody td {
            font-size: 0.83rem;
            vertical-align: middle;
            color: #333;
        }
        .table tbody tr:hover { background: #f8f1fe; }
        .table { margin-bottom: 0; }

        /* ── DataTables overrides ── */
        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 5px 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.83rem;
        }
        .dataTables_wrapper .dataTables_filter input:focus,
        .dataTables_wrapper .dataTables_length select:focus {
            outline: none;
            border-color: var(--ccs-purple);
            box-shadow: 0 0 0 3px rgba(151,87,214,0.12);
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: var(--ccs-purple) !important;
            color: white !important;
            border-radius: 6px;
            border: none !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f3e9fd !important;
            color: var(--ccs-purple) !important;
            border-radius: 6px;
            border: none !important;
        }

        /* ── Badges ── */
        .badge-active {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
        }
        .badge-done {
            background: #bdc3c7;
            color: #555;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
        }

        /* ── Buttons ── */
        .btn-purple {
            background: linear-gradient(135deg, var(--ccs-purple), #7c45b8);
            color: white; border: none; border-radius: 8px;
            font-size: 0.83rem; font-weight: 600;
            transition: opacity 0.2s;
        }
        .btn-purple:hover { opacity: 0.88; color: white; }

        .btn-gold-action {
            background: var(--ccs-gold); color: #4a3800;
            border: none; border-radius: 8px;
            font-size: 0.83rem; font-weight: 600;
        }
        .btn-gold-action:hover { background: #e6c200; color: #333; }

        .btn-logout-tbl {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white; border: none; border-radius: 6px;
            padding: 4px 12px; font-size: 0.77rem;
            font-weight: 600; cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-logout-tbl:hover { opacity: 0.85; }

        /* ── Announcement items ── */
        .ann-item {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .ann-item:last-child { border-bottom: none; }
        .ann-item h6 {
            color: var(--ccs-purple);
            font-weight: 700;
            margin-bottom: 3px;
            font-size: 0.88rem;
        }

        /* ── Modal ── */
        .modal-header-purple {
            background: linear-gradient(135deg, var(--ccs-purple), #7c45b8);
            color: white;
            border-radius: 12px 12px 0 0;
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

        /* ── Sit-in search results ── */
        .result-item {
            padding: 10px 12px;
            border-radius: 8px;
            cursor: pointer;
            border: 1px solid #eee;
            margin-bottom: 6px;
            background: white;
            transition: background 0.15s;
        }
        .result-item:hover { background: #f8f1fe; border-color: #d9b8f7; }
        .result-item .r-name { font-weight: 600; font-size: 0.88rem; color: #333; }
        .result-item .r-sub  { font-size: 0.77rem; color: #999; }

        /* ── Selected student card ── */
        .selected-student-card {
            background: linear-gradient(135deg, #f8f1fe, #eef6ff);
            border-radius: 12px;
            padding: 1rem 1.2rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(151,87,214,0.15);
        }
        .selected-student-card .label {
            font-size: 0.68rem;
            color: var(--ccs-purple);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 8px;
        }
        .selected-student-card .val-title { font-size: 0.7rem; color: #999; }
        .selected-student-card .val { font-weight: 700; color: #222; font-size: 0.88rem; }

        /* ── Announce textarea ── */
        .announce-textarea {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 10px 12px;
            width: 100%;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            resize: vertical;
            min-height: 80px;
            transition: border 0.2s, box-shadow 0.2s;
        }
        .announce-textarea:focus {
            outline: none;
            border-color: var(--ccs-purple);
            box-shadow: 0 0 0 3px rgba(151,87,214,0.12);
        }

        /* ── Chart wrapper ── */
        .chart-wrapper { position: relative; height: 250px; }

        /* ── Footer ── */
        footer {
            margin-left: var(--sidebar-width);
            padding: 24px;
            text-align: center;
            color: #aaa;
            font-size: 0.78rem;
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body>

<!-- ════ TOP NAVBAR ════ -->
<nav class="top-navbar">

    <div class="nav-left">
        <span class="brand-title">
            <i class="bi bi-cpu-fill me-1"></i> CCS Sit-in Monitoring
        </span>
    </div>

    <div class="nav-links">
        <a href="admin_dashboard.php?tab=students" class="<?= $active_tab==='students'?'active':'' ?>">
            <i class="bi bi-people-fill"></i> Students
        </a>

        <a href="admin_dashboard.php?tab=sitinform" class="<?= $active_tab==='sitinform'?'active':'' ?>">
            <i class="bi bi-box-arrow-in-right"></i> Sit-in
        </a>

        <a href="admin_dashboard.php?tab=sitin" class="<?= $active_tab==='sitin'?'active':'' ?>">
            <i class="bi bi-display"></i> Current
        </a>

        <a href="admin_dashboard.php?tab=records" class="<?= $active_tab==='records'?'active':'' ?>">
            <i class="bi bi-table"></i> Records
        </a>

        <a href="admin_dashboard.php?tab=announcements" class="<?= $active_tab==='announcements'?'active':'' ?>">
            <i class="bi bi-megaphone-fill"></i> Announcements
        </a>

        <a href="admin_dashboard.php?tab=reports" class="<?= $active_tab==='reports'?'active':'' ?>">
            <i class="bi bi-bar-chart-fill"></i> Reports
        </a>

        <a href="admin_dashboard.php?tab=feedback" class="<?= $active_tab==='feedback'?'active':'' ?>">
            <i class="bi bi-chat-left-text-fill"></i> Feedback
        </a>

        <a href="admin_dashboard.php?tab=reservation" class="<?= $active_tab==='reservation'?'active':'' ?>">
            <i class="bi bi-calendar-check-fill"></i> Reservation
        </a>
    </div>

  <a href="#" class="btn-logout-top" onclick="confirmLogout(event)">
    <i class="bi bi-box-arrow-right"></i> Logout
</a>

</nav>
<!-- ════ MAIN CONTENT ════ -->
<div class="main-content">

    <!-- Top Hero Bar -->
    <div class="top-hero">
        <div class="d-flex align-items-center justify-content-between">
            <h4>
                <?php
                $titles = [
                    'dashboard'     => '<i class="bi bi-speedometer2 me-2"></i>Dashboard',
                    'students'      => '<i class="bi bi-people-fill me-2"></i>Students',
                    'sitinform'     => '<i class="bi bi-box-arrow-in-right me-2"></i>Sit-in Student',
                    'sitin'         => '<i class="bi bi-display me-2"></i>Current Sit-in',
                    'records'       => '<i class="bi bi-table me-2"></i>Sit-in Records',
                    'announcements' => '<i class="bi bi-megaphone-fill me-2"></i>Announcements',
                    'reports'       => '<i class="bi bi-bar-chart-fill me-2"></i>Sit-in Reports',
                    'feedback'      => '<i class="bi bi-chat-left-text-fill me-2"></i>Feedback Reports',
                    'reservation'   => '<i class="bi bi-calendar-check-fill me-2"></i>Reservation',
                ];
                echo $titles[$active_tab] ?? $titles['dashboard'];
                ?>
            </h4>
            <span class="admin-badge"><i class="bi bi-shield-fill-check me-1"></i>CCS Admin</span>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">


    <!-- ══════════════ DASHBOARD TAB ══════════════ -->
    <?php if ($active_tab === 'dashboard'): ?>

    <!-- Stat cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="bi bi-people-fill"></i></div>
                <div>
                    <div class="stat-num"><?= $total_students ?></div>
                    <div class="stat-label">Students Registered</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="bi bi-display"></i></div>
                <div>
                    <div class="stat-num"><?= $currently_sitin ?></div>
                    <div class="stat-label">Currently Sit-in</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="stat-num"><?= $total_sitin ?></div>
                    <div class="stat-label">Total Sit-in Sessions</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart + Announcements -->
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="dash-card h-100">
                <div class="card-header-purple">
                    <span><i class="bi bi-pie-chart-fill me-2"></i>Sessions by Purpose</span>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center p-3">
                    <div class="chart-wrapper w-100">
                        <canvas id="purposeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="dash-card h-100">
                <div class="card-header-gold">
                    <span><i class="bi bi-megaphone-fill me-2"></i>Announcements</span>
                    <a href="admin_dashboard.php?tab=announcements" class="btn btn-sm btn-purple px-3" style="font-size:0.75rem;">Manage</a>
                </div>
                <div class="card-body p-3">
                    <form method="POST" action="admin_dashboard.php?tab=dashboard" class="mb-3">
                        <textarea class="announce-textarea" name="announcement" placeholder="Write a new announcement..."></textarea>
                        <button type="submit" name="post_announcement" class="btn btn-sm btn-purple mt-2 px-3">
                            <i class="bi bi-send-fill me-1"></i> Post
                        </button>
                    </form>
                    <hr class="my-2">
                    <?php
                    $ann = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 4");
                    if ($ann && $ann->num_rows > 0):
                        while ($a = $ann->fetch_assoc()):
                            $date = date('Y-M-d', strtotime($a['created_at']));
                    ?>
                        <div class="ann-item">
                            <h6><?= htmlspecialchars($a['admin_name']) ?>
                                <small class="text-muted fw-normal" style="font-size:0.75rem;">| <?= $date ?></small>
                            </h6>
                            <p class="mb-0 text-muted" style="font-size:0.82rem;"><?= htmlspecialchars($a['message']) ?></p>
                        </div>
                    <?php endwhile; else: ?>
                        <p class="text-muted mb-0" style="font-size:0.85rem;">No announcements yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    new Chart(document.getElementById('purposeChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode($lang_labels ?: ['C Programming','C++','Java','PHP','Python']) ?>,
            datasets: [{
                data: <?= json_encode($lang_data ?: [10,20,15,25,30]) ?>,
                backgroundColor: ['#9757d6','#a1cbf7','#27ae60','#f39c12','#e74c3c','#3498db','#FFD700'],
                borderWidth: 2, borderColor: '#fff'
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { font:{ family:'Poppins', size:11 }, boxWidth:14 } } }
        }
    });

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'logout') {
        Swal.fire({
            title: 'Success!',
            text: 'Logout successfully',
            icon: 'success',
            confirmButtonColor: '#9757d6', // Matches your CCS Purple
            timer: 3000
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    </script>


    <!-- ══════════════ STUDENTS TAB ══════════════ -->
    <?php elseif ($active_tab === 'students'): ?>

    <!-- Action buttons -->
    <div class="d-flex gap-2 mb-3">
        <button class="btn btn-purple px-4" onclick="openAddModal()">
            <i class="bi bi-person-plus-fill me-2"></i>Add Students
        </button>
        <form method="POST" onsubmit="return confirm('Reset ALL students sessions to 30?')">
            <button name="reset_all_student_sessions" class="btn btn-danger px-4">
                <i class="bi bi-arrow-counterclockwise me-2"></i>Reset All Session
            </button>
        </form>
    </div>

    <div class="dash-card">
        <div class="card-header-purple">
            <span><i class="bi bi-people-fill me-2"></i>All Students</span>
        </div>
        <div class="card-body p-3">
            <table id="studentTable" class="table table-bordered table-hover w-100">
                <thead>
                    <tr>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th>Year Level</th>
                        <th>Course</th>
                        <th>Remaining Session</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $stu = $conn->query("SELECT * FROM users ORDER BY last_name ASC");
                if ($stu && $stu->num_rows > 0):
                    while ($s = $stu->fetch_assoc()):
                        $rem = intval($s['remaining_session'] ?? 30);
                        $color = $rem > 10 ? '#27ae60' : ($rem > 5 ? '#f39c12' : '#e74c3c');
                ?>
                    <tr>
                        <td><?= htmlspecialchars($s['id_number']) ?></td>
                        <td><?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?></td>
                        <td><?= htmlspecialchars($s['year_level']) ?></td>
                        <td><?= htmlspecialchars($s['course']) ?></td>
                        <td><span style="font-weight:700;color:<?= $color ?>;"><?= $rem ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-purple me-1"
                                onclick="openEditModal(
                                    '<?= htmlspecialchars($s['id_number'],ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($s['first_name'],ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($s['last_name'],ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($s['middle_name']??'',ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($s['course'],ENT_QUOTES) ?>',
                                    '<?= $s['year_level'] ?>',
                                    '<?= htmlspecialchars($s['email'],ENT_QUOTES) ?>',
                                    '<?= $rem ?>'
                                )">
                                <i class="bi bi-pencil-fill"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger"
                                onclick="deleteStudent('<?= htmlspecialchars($s['id_number'],ENT_QUOTES) ?>')">
                                <i class="bi bi-trash-fill"></i> Delete
                            </button>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

            <!-- Hidden delete form -->
            <form method="POST" id="deleteStudentForm">
                <input type="hidden" name="del_idnumber" id="del_idnumber">
                <input type="hidden" name="delete_student" value="1">
            </form>

            <div class="modal fade" id="editModal<?= $r['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                <h5>Edit Reservation</h5>
                </div>

                <div class="modal-body">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">

                <input type="text" name="purpose" value="<?= $r['purpose'] ?>" class="form-control mb-2" required>
                <input type="text" name="lab" value="<?= $r['lab'] ?>" class="form-control mb-2" required>
                <input type="text" name="preferred_time" value="<?= $r['preferred_time'] ?>" class="form-control mb-2" required>
                <input type="date" name="reservation_date" value="<?= $r['reservation_date'] ?>" class="form-control mb-2" required>
                </div>

                <div class="modal-footer">
                <button name="edit_reservation" class="btn btn-primary">Save</button>
                </div>
            </form>
            </div>
        </div>
        </div>

    <!-- ── Add Student Modal ── -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-purple d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-person-plus-fill me-2"></i>Add Student</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-sm-12">
                                <label class="form-label" style="font-size:0.78rem;color:#777;">ID Number</label>
                                <input type="text" name="add_idnumber" class="form-control" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" style="font-size:0.78rem;color:#777;">First Name</label>
                                <input type="text" name="add_firstname" class="form-control" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" style="font-size:0.78rem;color:#777;">Last Name</label>
                                <input type="text" name="add_lastname" class="form-control" required>
                            </div>
                            <div class="col-sm-12">
                                <label class="form-label" style="font-size:0.78rem;color:#777;">Middle Name</label>
                                <input type="text" name="add_middlename" class="form-control">
                            </div>
                            <div class="col-sm-8">
                                <label class="form-label" style="font-size:0.78rem;color:#777;">Course</label>
                                <input type="text" name="add_course" class="form-control" required>
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label" style="font-size:0.78rem;color:#777;">Year Level</label>
                                <select name="add_yearlevel" class="form-select" required>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>
                            <div class="col-sm-12">
                                <label class="form-label" style="font-size:0.78rem;color:#777;">Email</label>
                                <input type="email" name="add_email" class="form-control" required>
                            </div>
                            <div class="col-sm-12">
                                <label class="form-label" style="font-size:0.78rem;color:#777;">Password</label>
                                <input type="password" name="add_password" class="form-control" required>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_student" class="btn btn-purple px-4">
                                <i class="bi bi-person-plus-fill me-1"></i> Add Student
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Edit Student Modal ── -->
    <div class="modal fade" id="editStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-purple d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-pencil-fill me-2"></i>Edit Student</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST">
                        <input type="hidden" name="edit_idnumber" id="edit_idnumber">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label" style="font-size:0.78rem;color:#777;">First Name</label>
                                <input type="text" name="edit_firstname" id="edit_firstname" class="form-control" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" style="font-size:0.78rem;color:#777;">Last Name</label>
                                <input type="text" name="edit_lastname" id="edit_lastname" class="form-control" required>
                            </div>
                            <div class="col-sm-12">
                                <label class="form-label" style="font-size:0.78rem;color:#777;">Middle Name</label>
                                <input type="text" name="edit_middlename" id="edit_middlename" class="form-control">
                            </div>
                            <div class="col-sm-8">
                                <label class="form-label" style="font-size:0.78rem;color:#777;">Course</label>
                                <input type="text" name="edit_course" id="edit_course" class="form-control" required>
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label" style="font-size:0.78rem;color:#777;">Year Level</label>
                                <select name="edit_yearlevel" id="edit_yearlevel" class="form-select">
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>
                            <div class="col-sm-12">
                                <label class="form-label" style="font-size:0.78rem;color:#777;">Email</label>
                                <input type="email" name="edit_email" id="edit_email" class="form-control" required>
                            </div>
                            <div class="col-sm-12">
                                <label class="form-label" style="font-size:0.78rem;color:#777;">Remaining Sessions</label>
                                <input type="number" name="edit_remaining" id="edit_remaining" class="form-control" min="0" max="30" required>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="edit_student" class="btn btn-purple px-4">
                                <i class="bi bi-save-fill me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- ══════════════ SIT-IN FORM TAB ══════════════ -->
    <?php elseif ($active_tab === 'sitinform'): ?>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-7">
            <div class="dash-card">
                <div class="card-header-purple">
                    <span><i class="bi bi-search me-2"></i>Search & Sit-in Student</span>
                </div>
                <div class="card-body p-4">

                    <?php if (!empty($sitin_error)): ?>
                        <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:0.85rem;border-radius:10px;">
                            <i class="bi bi-exclamation-circle-fill me-2"></i><?= htmlspecialchars($sitin_error) ?>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.8rem;color:#777;">Search by ID Number or Name</label>
                        <input type="text" id="studentLookup" class="form-control" placeholder="e.g. 123456 or Juan Dela Cruz" autocomplete="off">
                    </div>

                    <div id="searchResults" style="display:none;" class="mb-3">
                        <div style="font-size:0.75rem;color:#999;margin-bottom:6px;">Select a student:</div>
                        <div id="resultsList"></div>
                    </div>

                    <form method="POST" id="sitinForm" style="display:none;">
                        <div class="selected-student-card">
                            <div class="label">Selected Student</div>
                            <div class="row g-2">
                                <div class="col-sm-6">
                                    <div class="val-title">ID Number</div>
                                    <div class="val" id="display_id"></div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="val-title">Name</div>
                                    <div class="val" id="display_name"></div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="val-title">Course</div>
                                    <div class="val" id="display_course"></div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="val-title">Year Level</div>
                                    <div class="val" id="display_level"></div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="val-title">Remaining Sessions</div>
                                    <div class="val" id="display_remaining">—</div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="sitin_id" id="sitin_id">

                        <div class="mb-3">
                            <label class="form-label" style="font-size:0.8rem;color:#777;">Purpose</label>
                            <select name="sitin_purpose" class="form-select" required>
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
                        <div class="mb-4">
                            <label class="form-label" style="font-size:0.8rem;color:#777;">Lab</label>
                            <select name="sitin_lab" class="form-select" required>
                                <option value="">-- Select Lab --</option>
                                <option>524</option>
                                <option>526</option>
                                <option>528</option>
                                <option>530</option>
                                <option>542</option>
                                <option>Mac Lab</option>
                            </select>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary btn-sm px-3" onclick="resetSitinForm()">
                                <i class="bi bi-x-circle me-1"></i> Cancel
                            </button>
                            <button type="submit" name="do_sitin" class="btn btn-purple px-4">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Sit In
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- ══════════════ CURRENT SIT-IN TAB ══════════════ -->
    <?php elseif ($active_tab === 'sitin'): ?>

    <div class="dash-card">
        <div class="card-header-purple">
            <span><i class="bi bi-display me-2"></i>Currently Active Sessions</span>
            <button class="btn btn-sm btn-gold-action px-3" onclick="resetAllSessions()">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset All
            </button>
        </div>
        <div class="card-body p-3">
            <table id="sitinTable" class="table table-bordered table-hover w-100">
                <thead>
                    <tr>
                        <th>Sit ID</th>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th>Purpose</th>
                        <th>Lab</th>
                        <th>Login Time</th>
                        <th>Remaining Sessions</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sit = $conn->query("
                    SELECT sr.*, 
                        CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS student_name,
                        u.remaining_session
                    FROM sitin_records sr
                    LEFT JOIN users u ON sr.id_number COLLATE utf8mb4_unicode_ci = u.id_number COLLATE utf8mb4_unicode_ci
                    WHERE sr.logout_time IS NULL
                    ORDER BY sr.login_time DESC
                ");
                if ($sit && $sit->num_rows > 0):
                    while ($r = $sit->fetch_assoc()):
                ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= htmlspecialchars($r['id_number']) ?></td>
                        <td><?= htmlspecialchars(trim($r['student_name']) ?: '—') ?></td>
                        <td><?= htmlspecialchars($r['purpose']) ?></td>
                        <td><?= htmlspecialchars($r['lab']) ?></td>
                        <td><?= htmlspecialchars($r['login_time']) ?></td>
                        <td>
                            <?php
                            $rem = intval($r['remaining_session'] ?? 30);
                            $color = $rem > 10 ? '#27ae60' : ($rem > 5 ? '#f39c12' : '#e74c3c');
                            ?>
                            <span style="font-weight:700; color:<?= $color ?>;"><?= $rem ?></span>
                        </td>
                        <td><span class="badge-active">Active</span></td>
                        <td>
                            <button class="btn-logout-tbl"
                                onclick="if(confirm('Log out this student?')){
                                    var f=document.createElement('form');
                                    f.method='POST';
                                    f.action='admin_dashboard.php?tab=sitin';
                                    var i1=document.createElement('input');i1.type='hidden';i1.name='sit_id';i1.value='<?= $r['id'] ?>';
                                    var i2=document.createElement('input');i2.type='hidden';i2.name='logout_session';i2.value='1';
                                    f.appendChild(i1);f.appendChild(i2);
                                    document.body.appendChild(f);f.submit();
                                }">
                                <i class="bi bi-box-arrow-right me-1"></i>Log out
                            </button>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

        <!-- ══════════════ Reservations TAB ══════════════ -->

    <?php elseif ($active_tab === 'reservation'): ?>

<div class="dash-card">
    <div class="card-header-purple">
        <span>Student Reservations</span>
    </div>

    <div class="card-body">
        <table class="table table-bordered">
            <tr>
                <th>ID</th>
                <th>Student ID</th>
                <th>Purpose</th>
                <th>Lab</th>
                <th>Time</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>

            <?php
            $res = $conn->query("SELECT * FROM reservations");

            while ($r = $res->fetch_assoc()):
            ?>

            <tr>
                <td><?= $r['id'] ?></td>
                <td><?= $r['id_number'] ?></td>
                <td><?= $r['purpose'] ?></td>
                <td><?= $r['lab'] ?></td>
                <td><?= $r['preferred_time'] ?></td>
                <td><?= $r['reservation_date'] ?></td>
                <td><?= $r['status'] ?></td>

                <td>
                    <?php if ($r['status'] === 'pending'): ?>

                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="res_id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="status" value="approved">
                        <button name="update_reservation">Approve</button>
                    </form>

                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="res_id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="status" value="rejected">
                        <button name="update_reservation">Reject</button>
                    </form>

                    <?php endif; ?>
                </td>
            </tr>
            
            <td>
    <!-- EDIT -->
    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $r['id'] ?>">
        Edit
    </button>

    <!-- DELETE -->
    <form method="POST" style="display:inline;">
        <input type="hidden" name="id" value="<?= $r['id'] ?>">
        <button name="delete_reservation" class="btn btn-danger btn-sm">
            Delete
        </button>
    </form>
</td>
            <?php endwhile; ?>



        </table>
    </div>
</div>


    <!-- ══════════════ SIT-IN RECORDS TAB ══════════════ -->
    <?php elseif ($active_tab === 'records'): ?>

    <div class="dash-card">
        <div class="card-header-purple">
            <span><i class="bi bi-table me-2"></i>All Sit-in Records</span>
        </div>
        <div class="card-body p-3">
            <table id="recordTable" class="table table-bordered table-hover w-100">
                <thead>
                    <tr>
                        <th>Sit ID</th>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th>Purpose</th>
                        <th>Lab</th>
                        <th>Login Time</th>
                        <th>Logout Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $rec = $conn->query("
                    SELECT sr.*, CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS student_name
                    FROM sitin_records sr
                    LEFT JOIN users u ON sr.id_number COLLATE utf8mb4_unicode_ci = u.id_number COLLATE utf8mb4_unicode_ci
                    ORDER BY sr.login_time DESC
                ");
                if ($rec && $rec->num_rows > 0):
                    while ($r = $rec->fetch_assoc()):
                        $is_active = empty($r['logout_time']);
                ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= htmlspecialchars($r['id_number']) ?></td>
                        <td><?= htmlspecialchars(trim($r['student_name']) ?: '—') ?></td>
                        <td><?= htmlspecialchars($r['purpose']) ?></td>
                        <td><?= htmlspecialchars($r['lab']) ?></td>
                        <td><?= htmlspecialchars($r['login_time']) ?></td>
                        <td><?= $is_active ? '<span class="text-muted">—</span>' : htmlspecialchars($r['logout_time']) ?></td>
                        <td>
                            <?php if ($is_active): ?>
                                <span class="badge-active">Active</span>
                            <?php else: ?>
                                <span class="badge-done">Done</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>


    <!-- ══════════════ ANNOUNCEMENTS TAB ══════════════ -->
    <?php elseif ($active_tab === 'announcements'): ?>

    <div class="row g-4">
        <div class="col-md-5">
            <div class="dash-card">
                <div class="card-header-purple">
                    <span><i class="bi bi-plus-circle-fill me-2"></i>Post New Announcement</span>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="admin_dashboard.php?tab=announcements">
                        <div class="mb-3">
                            <label class="form-label" style="font-size:0.8rem;color:#777;">Message</label>
                            <textarea name="announcement" class="announce-textarea" placeholder="Write your announcement..." required></textarea>
                        </div>
                        <button type="submit" name="post_announcement" class="btn btn-purple w-100">
                            <i class="bi bi-send-fill me-2"></i>Post Announcement
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="dash-card">
                <div class="card-header-gold">
                    <span><i class="bi bi-list-ul me-2"></i>Posted Announcements</span>
                </div>
                <div class="card-body p-3">
                    <?php
                    $ann = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
                    if ($ann && $ann->num_rows > 0):
                        while ($a = $ann->fetch_assoc()):
                            $date = date('Y-M-d', strtotime($a['created_at']));
                    ?>
                        <div class="ann-item d-flex justify-content-between align-items-start">
                            <div>
                                <h6><?= htmlspecialchars($a['admin_name']) ?>
                                    <small class="text-muted fw-normal" style="font-size:0.75rem;">| <?= $date ?></small>
                                </h6>
                                <p class="mb-0 text-muted" style="font-size:0.82rem;"><?= htmlspecialchars($a['message']) ?></p>
                            </div>
                            <form method="POST" onsubmit="return confirm('Delete this announcement?')" class="ms-3 flex-shrink-0">
                                <input type="hidden" name="ann_id" value="<?= $a['id'] ?>">
                                <button name="delete_announcement" class="btn btn-sm btn-danger" style="border-radius:8px;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    <?php endwhile; else: ?>
                        <p class="text-muted mb-0" style="font-size:0.85rem;">No announcements posted yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>


    <!-- ══════════════ REPORTS / FEEDBACK / RESERVATION STUBS ══════════════ -->
    <!-- REJECT -->
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="res_id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="status" value="rejected">
                            <button name="update_reservation" class="btn btn-danger btn-sm">
                                Reject
                            </button>
                        </form>

                        <?php else: ?>
                            <span class="text-muted">Done</span>
                        <?php endif; ?>
                    </td>
                </tr>

           <?php while ($r = $res->fetch_assoc()): ?>
    <tr>
        <td><?= $r['id'] ?></td>
    </tr>
<?php endwhile; ?>

            </tbody>
        </table>
    </div>
</div>

    </div><!-- end content-area -->
</div><!-- end main-content -->

<footer>&copy; <?= date('Y') ?> College of Computer Studies | CCS Sit-in Monitoring System</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

    function confirmLogout(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Logging out?',
        text: 'Are you sure you want to log out of the admin panel?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-box-arrow-right me-1"></i> Yes, Logout',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#9757d6',
        cancelButtonColor: '#6c757d',
        background: '#fff',
        customClass: {
            popup: 'rounded-4',
            confirmButton: 'fw-bold',
            cancelButton: 'fw-bold'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Logged out!',
                text: 'You have been logged out successfully.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
                confirmButtonColor: '#9757d6',
            }).then(() => {
                window.location.href = 'landingpage.php';
            });
        }
    });
}
$(document).ready(function () {
    const dtLang = { emptyTable: "No data available", zeroRecords: "No matching records found" };
    if ($('#studentTable').length)  $('#studentTable').DataTable({ pageLength:10, order:[[0,'asc']], language: dtLang });
    if ($('#sitinTable').length)    $('#sitinTable').DataTable({ pageLength:10, order:[[0,'desc']], language: dtLang });
    if ($('#recordTable').length)   $('#recordTable').DataTable({ pageLength:10, order:[[0,'desc']], language: dtLang });
});

// ── Sit-in student search ──
const lookup = document.getElementById('studentLookup');
if (lookup) {
    let debounce;
    lookup.addEventListener('input', function () {
        clearTimeout(debounce);
        const q = this.value.trim();
        if (q.length < 2) { document.getElementById('searchResults').style.display = 'none'; return; }
        debounce = setTimeout(() => {
            fetch('admin_dashboard.php?search_student=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    const list = document.getElementById('resultsList');
                    const box  = document.getElementById('searchResults');
                    list.innerHTML = !data.length
                        ? '<div style="font-size:0.85rem;color:#999;padding:8px;">No students found.</div>'
                        : data.map(s => `
                            <div class="result-item"
                                onclick="selectStudent('${s.id_number}','${s.first_name}','${s.last_name}','${s.course}','${s.year_level}','${s.remaining_session ?? 30}')">
                                <div class="r-name">${s.first_name} ${s.last_name}</div>
                                <div class="r-sub">${s.id_number} &mdash; ${s.course}, Year ${s.year_level} &mdash; <span style="font-weight:600;">Sessions: ${s.remaining_session ?? 30}</span></div>
                            </div>`).join('');
                    box.style.display = 'block';
                });
        }, 300);
    });
}

function selectStudent(id, first, last, course, level, remaining) {
    document.getElementById('sitin_id').value           = id;
    document.getElementById('display_id').innerText     = id;
    document.getElementById('display_name').innerText   = first + ' ' + last;
    document.getElementById('display_course').innerText = course;
    document.getElementById('display_level').innerText  = 'Year ' + level;

    const rem = parseInt(remaining) || 0;
    const color = rem > 10 ? '#27ae60' : (rem > 5 ? '#f39c12' : '#e74c3c');
    const remEl = document.getElementById('display_remaining');
    remEl.innerText = rem;
    remEl.style.color = color;
    remEl.style.fontSize = '1rem';

    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('studentLookup').value = first + ' ' + last;
    document.getElementById('sitinForm').style.display = 'block';
}

function openAddModal() {
    new bootstrap.Modal(document.getElementById('addStudentModal')).show();
}

function openEditModal(id, first, last, middle, course, level, email, remaining) {
    document.getElementById('edit_idnumber').value   = id;
    document.getElementById('edit_firstname').value  = first;
    document.getElementById('edit_lastname').value   = last;
    document.getElementById('edit_middlename').value = middle;
    document.getElementById('edit_course').value     = course;
    document.getElementById('edit_yearlevel').value  = level;
    document.getElementById('edit_email').value      = email;
    document.getElementById('edit_remaining').value  = remaining;
    new bootstrap.Modal(document.getElementById('editStudentModal')).show();
}

function deleteStudent(id) {
    if (!confirm('Delete student ' + id + '? This cannot be undone.')) return;
    document.getElementById('del_idnumber').value = id;
    document.getElementById('deleteStudentForm').submit();
}

function resetAllSessions() {
    if (!confirm('Log out ALL active sessions?')) return;
    var f = document.createElement('form');
    f.method = 'POST';
    f.action = 'admin_dashboard.php?tab=sitin';
    var i = document.createElement('input');
    i.type = 'hidden'; i.name = 'reset_all_sessions'; i.value = '1';
    f.appendChild(i);
    document.body.appendChild(f);
    f.submit();
}

function resetSitinForm() {
    document.getElementById('sitinForm').style.display = 'none';
    document.getElementById('studentLookup').value = '';
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('display_remaining').innerText = '—';
    document.getElementById('display_remaining').style.color = '';
}
</script>
</body>
</html>