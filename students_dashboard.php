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
    $purpose  = $conn->real_escape_string(trim($_POST['res_purpose']));
    $lab      = $conn->real_escape_string(trim($_POST['res_lab']));
    $timein   = $conn->real_escape_string(trim($_POST['res_timein']));
    $date     = $conn->real_escape_string(trim($_POST['res_date']));
    $seat_num = intval($_POST['res_seat'] ?? 0);

    $dup = $conn->query("SELECT id FROM sitin_records WHERE id_number='$id_number' AND login_time LIKE '$date%' AND logout_time IS NULL");
    if ($dup && $dup->num_rows > 0) {
        $res_error = "You already have an active sit-in on that date.";
    } else {
        if ($seat_num > 0) {
            $conn->query("INSERT INTO reservations (id_number, purpose, lab, preferred_time, reservation_date, status, seat_number, created_at)
                          VALUES ('$id_number','$purpose','$lab','$timein','$date','Pending',$seat_num,NOW())");
        } else {
            $conn->query("INSERT INTO reservations (id_number, purpose, lab, preferred_time, reservation_date, status, created_at)
                          VALUES ('$id_number','$purpose','$lab','$timein','$date','Pending',NOW())");
        }
        $res_success = $seat_num > 0 ? "Reservation submitted! Seat #$seat_num reserved." : "Reservation submitted successfully!";
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

if (!$stmt3) {
    die("Prepare failed: " . $conn->error);
}

    $stmt3->bind_param("s", $id_number);
    $stmt3->execute();
    $reservations = $stmt3->get_result();
    $stmt3->close();
}

// ── Fetch occupied seats per lab/date ────────────────────────────────
$occupied_seats = [];
if ($res_table_exists) {
    $col_check = $conn->query("SHOW COLUMNS FROM reservations LIKE 'seat_number'");
    if ($col_check && $col_check->num_rows > 0) {
        $oq = $conn->query("SELECT lab, reservation_date, seat_number FROM reservations WHERE status IN ('Pending','Approved') AND seat_number IS NOT NULL AND seat_number > 0");
        if ($oq) {
            while ($row = $oq->fetch_assoc()) {
                $occupied_seats[$row['lab']][$row['reservation_date']][] = intval($row['seat_number']);
            }
        }
    }
}

// ── Submit Feedback ──────────────────────────────────────────────────
if (isset($_POST['submit_feedback'])) {
    $sitin_id = intval($_POST['sitin_id']);
    $message  = $conn->real_escape_string(trim($_POST['feedback_message']));
    if ($message !== '') {
        // Check if feedback already exists for this session
        $existing = $conn->query("SELECT id FROM feedback WHERE sitin_id = $sitin_id AND id_number = '$id_number'");
        if ($existing && $existing->num_rows === 0) {
            $conn->query("INSERT INTO feedback (sitin_id, id_number, message, created_at) VALUES ($sitin_id, '$id_number', '$message', NOW())");
            $feedback_success = "Feedback submitted!";
        } else {
            $feedback_error = "You already submitted feedback for this session.";
        }
    }
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
        body { font-family: 'Poppins', sans-serif; background-color: #f4f7f6; margin: 0; overflow-x: hidden; }

        /* ── Navbar ── */
        .navbar { background-color: var(--uc-blue); box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 8px 20px; position: sticky; top: 0; z-index: 100; }
        .navbar-brand { font-weight: 300; color: white !important; font-size: 0.9rem; letter-spacing: 0.3px; }
        .nav-action-btn { display: flex; align-items: center; gap: 5px; color: rgba(30,80,120,0.9) !important; font-weight: 600; font-size: 0.78rem; border-radius: 8px; padding: 6px 10px !important; transition: background 0.2s, color 0.2s; text-decoration: none; white-space: nowrap; border: 1.5px solid transparent; }
        .nav-action-btn i { font-size: 1rem; }
        .nav-action-btn:hover { background: rgba(255,255,255,0.4); color: #1a3e6e !important; border-color: rgba(255,255,255,0.5); }
        .nav-action-btn.purple i { color: var(--ccs-purple); }
        .nav-action-btn.orange i { color: #d35400; }
        .nav-action-btn.blue   i { color: #1a6fad; }
        .nav-action-btn.green  i { color: #1e8449; }
        .nav-divider { width: 1px; height: 22px; background: rgba(255,255,255,0.45); margin: 0 2px; }
        .btn-logout-nav { background-color: var(--ccs-gold); color: #333 !important; font-weight: 700; border: none; border-radius: 8px; padding: 6px 14px; font-size: 0.8rem; text-decoration: none; transition: background 0.2s; white-space: nowrap; }
        .btn-logout-nav:hover { background-color: #e6c200; color: #222 !important; }
        .nav-welcome { color: rgba(30,80,120,0.85); font-size: 0.8rem; font-weight: 500; white-space: nowrap; }
        .navbar-toggler { border: none; background: rgba(255,255,255,0.3); border-radius: 8px; padding: 4px 8px; }
        .navbar-toggler-icon { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(30,80,120,0.9)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e"); }

        /* ── Hero ── */
        .hero-section { background: linear-gradient(135deg, var(--ccs-purple) 0%, var(--uc-blue) 100%); color: white; padding: 40px 20px 70px; border-bottom-left-radius: 35px; border-bottom-right-radius: 35px; text-align: center; }
        .hero-section h2 { font-weight: 800; font-size: 1.6rem; text-transform: uppercase; margin-bottom: 4px; }
        .hero-section p { opacity: 0.8; font-size: 0.88rem; margin: 0; }
        .main-wrapper { margin-top: -40px; padding: 0 20px 40px; }

        /* ── Cards ── */
        .dash-card { background: white; border-radius: 18px; box-shadow: 0 6px 24px rgba(151,87,214,0.08); border: 1px solid rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 20px; }
        .card-header-purple { background: linear-gradient(135deg, var(--ccs-purple), #7c45b8); color: white; font-weight: 600; font-size: 0.88rem; padding: 11px 18px; }
        .card-header-gold { background: var(--ccs-gold); color: #4a3800; font-weight: 700; font-size: 0.88rem; padding: 11px 18px; }
        .card-header-blue { background: linear-gradient(135deg, #3a9bd5, #5ab4f0); color: white; font-weight: 600; font-size: 0.88rem; padding: 11px 18px; }
        .avatar-wrap { position: relative; width: 100px; height: 100px; margin: 0 auto 12px; }
        .avatar-img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 4px solid white; box-shadow: 0 4px 14px rgba(151,87,214,0.25); }
        .info-row { display: flex; justify-content: space-between; align-items: flex-start; padding: 7px 0; border-bottom: 1px solid #f5f5f5; font-size: 0.84rem; gap: 8px; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #aaa; white-space: nowrap; }
        .info-value { font-weight: 600; color: #333; text-align: right; word-break: break-word; }
        .credits-box { background: linear-gradient(135deg, #f8f1fe, #eef6ff); border-radius: 12px; padding: 14px 16px; margin: 14px 0; border: 1px solid rgba(151,87,214,0.1); }
        .credits-title { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--ccs-purple); font-weight: 700; margin-bottom: 8px; }
        .credits-nums { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 8px; }
        .credits-big { font-size: 1.8rem; font-weight: 800; line-height: 1; }
        .credits-sub { font-size: 0.72rem; color: #aaa; }
        .credits-bar { height: 8px; border-radius: 4px; background: #e8e0f0; overflow: hidden; }
        .credits-fill { height: 100%; border-radius: 4px; transition: width 0.5s ease; }
        .rules-list { padding-left: 1.2rem; margin: 0; }
        .rules-list li { padding: 5px 0; border-bottom: 1px solid #f5f5f5; font-size: 0.84rem; color: #444; }
        .rules-list li:last-child { border-bottom: none; }
        .rules-list li::marker { color: var(--ccs-purple); }
        .ann-item { padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
        .ann-item:last-child { border-bottom: none; }
        .ann-item .ann-admin { font-size: 0.78rem; font-weight: 700; color: var(--ccs-purple); margin-bottom: 3px; }
        .ann-item .ann-text { font-size: 0.84rem; color: #444; margin: 0; }
        .table thead th { background: linear-gradient(135deg, var(--ccs-purple), #7c45b8); color: white; font-size: 0.8rem; font-weight: 600; border: none; padding: 9px 12px; }
        .table tbody td { font-size: 0.82rem; vertical-align: middle; padding: 8px 12px; }
        .table tbody tr:hover { background: #f8f1fe; }
        .table { margin-bottom: 0; }
        .badge-active    { background: #27ae60; color: white; padding: 3px 9px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; }
        .badge-done      { background: #bdc3c7; color: #555;  padding: 3px 9px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; }
        .badge-pending   { background: #f39c12; color: white; padding: 3px 9px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; }
        .badge-approved  { background: #27ae60; color: white; padding: 3px 9px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; }
        .badge-cancelled { background: #95a5a6; color: white; padding: 3px 9px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; }

        /* ── Modals ── */
        .modal-header-purple { background: linear-gradient(135deg, var(--ccs-purple), #7c45b8); color: white; border-radius: 16px 16px 0 0; padding: 14px 20px; }
        .modal-header-purple .btn-close { filter: invert(1); }
        .modal-content { border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(151,87,214,0.2); }
        .form-control:focus, .form-select:focus { border-color: var(--ccs-purple); box-shadow: 0 0 0 3px rgba(151,87,214,0.12); }
        .form-control, .form-select { border-radius: 8px; font-size: 0.85rem; }
        .field-label { font-size: 0.75rem; color: #888; font-weight: 500; margin-bottom: 3px; }
        .btn-purple { background: linear-gradient(135deg, var(--ccs-purple), #7c45b8); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 0.85rem; transition: opacity 0.2s; }
        .btn-purple:hover { opacity: 0.88; color: white; }
        .res-form-box { background: linear-gradient(135deg, #f8f1fe, #eef6ff); border-radius: 12px; padding: 1.2rem; margin-bottom: 1.2rem; border: 1px solid rgba(151,87,214,0.12); }
        .res-form-box .res-title { font-size: 0.68rem; color: var(--ccs-purple); font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 12px; }

        footer { padding: 24px 0; text-align: center; color: #bbb; font-size: 0.78rem; }

        /* ════ SEAT PLAN ════ */
        #seatPlanModal .modal-dialog { max-width: 700px; }
        #seatPlanModal .modal-content { border-radius: 20px; overflow: hidden; border: none; }

        .seat-plan-header {
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            color: white;
            padding: 18px 24px;
        }
        .seat-plan-header .lab-chip {
            background: rgba(255,215,0,0.2);
            border: 1px solid rgba(255,215,0,0.5);
            color: #FFD700;
            border-radius: 20px;
            padding: 3px 14px;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.06em;
        }

        .seat-legend {
            display: flex; gap: 16px; align-items: center; flex-wrap: wrap;
            padding: 10px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            font-size: 0.77rem; color: #555;
        }
        .legend-dot { width: 13px; height: 13px; border-radius: 3px; display: inline-block; margin-right: 4px; vertical-align: middle; }
        .legend-available { background: #27ae60; }
        .legend-occupied  { background: #e74c3c; }
        .legend-selected  { background: #f39c12; }

        .seat-room-wrapper { padding: 18px 20px 10px; background: #fff; }

        .front-board {
            background: linear-gradient(90deg, #1a1a2e, #2c3e6b);
            color: #a1cbf7;
            text-align: center;
            padding: 8px 20px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.15);
        }

        .pc-grid {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 6px;
        }

        /* Row labels area */
        .pc-grid-wrapper { position: relative; }

        .pc-seat {
            aspect-ratio: 1;
            border-radius: 7px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 0.58rem;
            font-weight: 700;
            cursor: pointer;
            border: 2px solid transparent;
            transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
            position: relative;
            user-select: none;
        }
        .pc-seat i { font-size: 0.9rem; margin-bottom: 1px; }

        .pc-seat.available {
            background: linear-gradient(145deg, #d5f5e3, #a9dfbf);
            color: #1a6835;
            border-color: #82c99a;
        }
        .pc-seat.available:hover {
            transform: scale(1.12);
            box-shadow: 0 5px 16px rgba(39,174,96,0.45);
            border-color: #27ae60;
            z-index: 5;
        }
        .pc-seat.occupied {
            background: linear-gradient(145deg, #fadbd8, #f1948a);
            color: #7b241c;
            border-color: #e57373;
            cursor: not-allowed;
            opacity: 0.8;
        }
        .pc-seat.selected {
            background: linear-gradient(145deg, #fdebd0, #f8c471);
            color: #784212;
            border-color: #f39c12;
            transform: scale(1.1);
            box-shadow: 0 5px 16px rgba(243,156,18,0.5);
            z-index: 5;
        }

        /* Dropdown popover */
        .seat-popover {
            display: none;
            position: absolute;
            bottom: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%);
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 32px rgba(0,0,0,0.2);
            padding: 12px 14px;
            width: 140px;
            z-index: 99;
            text-align: center;
            border: 2px solid #f39c12;
            animation: popIn 0.15s ease;
        }
        @keyframes popIn {
            from { opacity: 0; transform: translateX(-50%) scale(0.85); }
            to   { opacity: 1; transform: translateX(-50%) scale(1); }
        }
        .seat-popover::after {
            content: '';
            position: absolute;
            top: 100%; left: 50%;
            transform: translateX(-50%);
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-top: 8px solid #f39c12;
        }
        .seat-popover .pop-title { font-size: 0.73rem; font-weight: 800; color: #333; margin-bottom: 8px; }
        .seat-popover .pop-title i { color: var(--ccs-purple); margin-right: 3px; }
        .pop-btn-sit {
            width: 100%; background: linear-gradient(135deg, #27ae60, #1e8449);
            color: white; border: none; border-radius: 7px;
            font-size: 0.72rem; font-weight: 700; padding: 6px;
            cursor: pointer; transition: opacity 0.2s; margin-bottom: 4px;
        }
        .pop-btn-sit:hover { opacity: 0.85; }
        .pop-btn-cancel {
            width: 100%; background: #f5f5f5; color: #888;
            border: none; border-radius: 7px;
            font-size: 0.68rem; padding: 4px;
            cursor: pointer; transition: background 0.2s;
        }
        .pop-btn-cancel:hover { background: #eee; }

        /* Selected seat info bar */
        .selected-seat-bar {
            display: none;
            background: linear-gradient(135deg, #fef9e7, #fdebd0);
            border-top: 2px solid #f39c12;
            padding: 10px 20px;
            font-size: 0.83rem;
            font-weight: 600;
            color: #784212;
            align-items: center;
            gap: 10px;
        }
        .selected-seat-bar.show { display: flex; }
        .selected-seat-bar i { font-size: 1.1rem; color: #f39c12; }

        .seat-plan-footer {
            padding: 12px 20px 16px;
            background: #fafafa;
            border-top: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .avail-counter { font-size: 0.75rem; color: #888; }
        .avail-counter strong { color: #27ae60; }

        /* Seat display in form */
        .seat-chosen-box {
            background: linear-gradient(135deg, #f3e9fd, #e8f0fe);
            border: 2px solid var(--ccs-purple);
            border-radius: 10px;
            padding: 9px 14px;
            font-size: 0.83rem;
            font-weight: 700;
            color: var(--ccs-purple);
            display: none;
            align-items: center;
            gap: 8px;
        }
        .seat-chosen-box.show { display: flex; }
        .seat-chosen-box .change-btn {
            margin-left: auto;
            background: var(--ccs-purple);
            color: white; border: none; border-radius: 6px;
            font-size: 0.7rem; padding: 3px 10px; cursor: pointer;
        }

        .lab-hint {
            font-size: 0.72rem; color: var(--ccs-purple);
            margin-top: 3px; display: none;
        }
        .lab-hint.show { display: block; }
    </style>
</head>
<body>

<!-- ── Navbar ── -->
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><i class="bi bi-pc-display-horizontal me-2"></i>CCS Sit-in Monitoring</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMenu">
            <div class="d-flex align-items-center gap-1 ms-auto flex-wrap py-1 py-lg-0">
                <a href="#" class="nav-action-btn purple" data-bs-toggle="modal" data-bs-target="#editProfileModal"><i class="bi bi-pencil-square"></i><span>Edit Profile</span></a>
                <div class="nav-divider d-none d-lg-block"></div>
                <a href="#" class="nav-action-btn orange" data-bs-toggle="modal" data-bs-target="#notifModal"><i class="bi bi-bell-fill"></i><span>Notifications</span></a>
                <div class="nav-divider d-none d-lg-block"></div>
                <a href="#" class="nav-action-btn blue" data-bs-toggle="modal" data-bs-target="#historyModal"><i class="bi bi-clock-history"></i><span>History</span></a>
                <div class="nav-divider d-none d-lg-block"></div>
                <a href="#" class="nav-action-btn green" data-bs-toggle="modal" data-bs-target="#reservationModal"><i class="bi bi-calendar-check-fill"></i><span>Reservation</span></a>
                <div class="nav-divider d-none d-lg-block"></div>
                <span class="nav-welcome ms-1">Welcome, <strong><?= htmlspecialchars($user['first_name']) ?></strong></span>
                <a href="#" class="btn-logout-nav ms-1" onclick="confirmLogout(event)"><i class="bi bi-box-arrow-right me-1"></i>Log out</a>
            </div>
        </div>
    </div>
</nav>

<!-- ── Hero ── -->
<div class="hero-section">
    <h2>Student Dashboard</h2>
    <p>College of Computer Studies · Sit-in Monitoring System</p>
</div>

<!-- ── Main ── -->
<div class="main-wrapper">
    <div class="container-fluid px-2 px-md-3">
        <?php if (isset($profile_success)): ?>
            <div class="alert alert-success alert-dismissible fade show py-2 px-3 mb-3" style="border-radius:10px;font-size:0.85rem;">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($profile_success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <div class="row g-3">
            <!-- LEFT -->
            <div class="col-lg-3 col-md-4">
                <div class="dash-card">
                    <div class="card-header-purple"><i class="bi bi-person-fill me-2"></i>Student Information</div>
                    <div class="card-body p-3 text-center">
                        <div class="avatar-wrap">
                            <img src="<?= $profile_pic ?>" id="avatarPreview" class="avatar-img"
                                onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['first_name'].' '.$user['last_name']) ?>&background=9757d6&color=fff&size=150'">
                        </div>
                        <div style="font-weight:700;font-size:1rem;color:#222;"><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></div>
                        <div style="font-size:0.75rem;color:#aaa;margin-bottom:12px;"><?= htmlspecialchars($user['course']) ?> &mdash; Year <?= $user['year_level'] ?></div>
                        <div class="info-row"><span class="info-label"><i class="bi bi-card-text me-1"></i>Name</span><span class="info-value"><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></span></div>
                        <div class="info-row"><span class="info-label"><i class="bi bi-book me-1"></i>Course</span><span class="info-value"><?= htmlspecialchars($user['course']) ?></span></div>
                        <div class="info-row"><span class="info-label"><i class="bi bi-calendar3 me-1"></i>Year</span><span class="info-value">Year <?= $user['year_level'] ?></span></div>
                        <div class="info-row"><span class="info-label"><i class="bi bi-envelope me-1"></i>Email</span><span class="info-value" style="font-size:0.75rem;"><?= htmlspecialchars($user['email']) ?></span></div>
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
                            <div class="credits-bar"><div class="credits-fill" style="width:<?= $credits_percent ?>%;background:<?= $credits_color ?>;"></div></div>
                            <?php if ($rem <= 5): ?>
                                <div style="font-size:0.73rem;color:#e74c3c;margin-top:6px;"><i class="bi bi-exclamation-triangle-fill me-1"></i>Low credits! Contact admin.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="dash-card">
                    <div class="card-header-gold"><i class="bi bi-journal-text me-2"></i>Rules &amp; Regulations</div>
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

            <!-- CENTER -->
            <div class="col-lg-5 col-md-8">
                <div class="dash-card h-100">
                    <div class="card-header-purple"><i class="bi bi-megaphone-fill me-2"></i>Announcement</div>
                    <div class="card-body p-3">
                        <?php if ($announcements && $announcements->num_rows > 0):
                            while ($a = $announcements->fetch_assoc()):
                                $adate = date('Y-M-d', strtotime($a['created_at'])); ?>
                            <div class="ann-item">
                                <div class="ann-admin"><?= htmlspecialchars($a['admin_name']) ?> <span class="text-muted fw-normal">| <?= $adate ?></span></div>
                                <p class="ann-text"><?= htmlspecialchars($a['message']) ?></p>
                            </div>
                        <?php endwhile; else: ?>
                            <p class="text-muted mb-0" style="font-size:0.85rem;">No announcements yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT -->
            <div class="col-lg-4 d-none d-lg-block">
                <div class="dash-card h-100">
                    <div class="card-header-blue"><i class="bi bi-shield-check me-2"></i>Laboratory Rules &amp; Regulations</div>
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
                        <div class="col-6"><label class="field-label">First Name</label><input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required></div>
                        <div class="col-6"><label class="field-label">Last Name</label><input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required></div>
                    </div>
                    <div class="mb-2"><label class="field-label">Middle Name</label><input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($user['middle_name'] ?? '') ?>"></div>
                    <div class="mb-2"><label class="field-label">Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required></div>
                    <div class="row g-2 mb-2">
                        <div class="col-8">
                            <label class="field-label">Course</label>
                            <select name="course" class="form-select" required>
                                <option value="">-- Select Course --</option>
                                <?php $courses = ['Information Technology','Computer Engineering','Civil Engineering','Mechanical Engineering','Electrical Engineering','Industrial Engineering','Naval Architecture and Marine Engineering','Elementary Education (BEEd)','Secondary Education (BSEd)','Criminology','Commerce','Accountancy','Hotel and Restaurant Management','Customs Administration','Computer Secretarial','Industrial Psychology','AB Political Science','AB English']; foreach($courses as $c): ?>
                                <option value="<?=$c?>" <?=$user['course']===$c?'selected':''?>><?=$c?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="field-label">Year Level</label>
                            <select name="year_level" class="form-select" required>
                                <option value="1" <?=$user['year_level']==1?'selected':''?>>1st Year</option>
                                <option value="2" <?=$user['year_level']==2?'selected':''?>>2nd Year</option>
                                <option value="3" <?=$user['year_level']==3?'selected':''?>>3rd Year</option>
                                <option value="4" <?=$user['year_level']==4?'selected':''?>>4th Year</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3"><label class="field-label">New Password <span class="text-muted">(leave blank to keep current)</span></label><input type="password" name="password" class="form-control" placeholder="••••••••"></div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_profile" class="btn btn-purple px-4"><i class="bi bi-save-fill me-1"></i> Save Changes</button>
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
                <?php $notif = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5");
                if ($notif && $notif->num_rows > 0):
                    while ($n = $notif->fetch_assoc()):
                        $nd = date('Y-M-d', strtotime($n['created_at'])); ?>
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
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header-purple d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Sit-in History</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <?php if (isset($feedback_success)): ?>
                    <div class="alert alert-success py-2 px-3 m-3 mb-0" style="border-radius:8px;font-size:0.83rem;">
                        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($feedback_success) ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($feedback_error)): ?>
                    <div class="alert alert-warning py-2 px-3 m-3 mb-0" style="border-radius:8px;font-size:0.83rem;">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($feedback_error) ?>
                    </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Login Time</th>
                                <th>Purpose</th>
                                <th>Lab</th>
                                <th>Logout Time</th>
                                <th>Status</th>
                                <th>Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $sessions->data_seek(0);
                        // Fetch all existing feedback for this student
                        $fb_map = [];
                        $fb_res = $conn->query("SELECT sitin_id, message FROM feedback WHERE id_number = '$id_number'");
                        if ($fb_res) while ($fb = $fb_res->fetch_assoc()) $fb_map[$fb['sitin_id']] = $fb['message'];

                        if ($sessions && $sessions->num_rows > 0):
                            while ($s = $sessions->fetch_assoc()):
                                $is_active = empty($s['logout_time']);
                                $has_feedback = isset($fb_map[$s['id']]);
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($s['login_time']) ?></td>
                                <td><?= htmlspecialchars($s['purpose']) ?></td>
                                <td><?= htmlspecialchars($s['lab']) ?></td>
                                <td><?= $is_active ? '<span class="text-muted">—</span>' : htmlspecialchars($s['logout_time']) ?></td>
                                <td><?= $is_active ? '<span class="badge-active">Active</span>' : '<span class="badge-done">Done</span>' ?></td>
                                <td>
                                    <?php if ($is_active): ?>
                                        <span class="text-muted" style="font-size:0.75rem;">Session ongoing</span>
                                    <?php elseif ($has_feedback): ?>
                                        <span style="font-size:0.75rem;color:#27ae60;font-weight:600;">
                                            <i class="bi bi-check-circle-fill me-1"></i>Submitted
                                        </span>
                                        <div style="font-size:0.72rem;color:#888;font-style:italic;max-width:160px;">
                                            "<?= htmlspecialchars($fb_map[$s['id']]) ?>"
                                        </div>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-purple"
                                            style="font-size:0.72rem;padding:3px 10px;border-radius:6px;"
                                            onclick="openFeedbackForm(<?= $s['id'] ?>)">
                                            <i class="bi bi-chat-left-text me-1"></i>Give Feedback
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">
                                <i class="bi bi-inbox me-2"></i>No sit-in history found.
                            </td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ════ FEEDBACK MODAL ════ -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-hidden="true" style="z-index:1070;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header-purple d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-chat-left-text-fill me-2"></i>Submit Feedback</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST" id="feedbackForm">
                    <input type="hidden" name="sitin_id" id="feedback_sitin_id">
                    <div class="mb-3">
                        <label class="field-label">Your Feedback</label>
                        <textarea name="feedback_message" class="form-control" rows="4"
                            placeholder="Share your experience about this sit-in session..."
                            style="border-radius:10px;font-size:0.85rem;resize:none;" required></textarea>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="submit_feedback" class="btn btn-purple px-4">
                            <i class="bi bi-send-fill me-2"></i>Submit Feedback
                        </button>
                    </div>
                </form>
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
                    <div class="alert alert-danger py-2 px-3 mb-3" style="border-radius:8px;font-size:0.83rem;"><i class="bi bi-exclamation-circle-fill me-2"></i><?= htmlspecialchars($res_error) ?></div>
                <?php endif; ?>
                <?php if (isset($res_success)): ?>
                    <div class="alert alert-success py-2 px-3 mb-3" style="border-radius:8px;font-size:0.83rem;"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($res_success) ?></div>
                <?php endif; ?>

                <div class="res-form-box">
                    <div class="res-title"><i class="bi bi-plus-circle-fill me-1"></i>New Reservation</div>
                    <form method="POST" id="reservationForm">
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
                                    <option>C Programming</option><option>C++</option><option>Java</option>
                                    <option>ASP.Net</option><option>PHP</option><option>Python</option><option>Other</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="field-label">Lab</label>
                                <select name="res_lab" id="res_lab" class="form-select" required>
                                    <option value="">-- Select Lab --</option>
                                    <option>524</option><option>526</option><option>528</option>
                                    <option>530</option><option>542</option><option>Mac Lab</option>
                                </select>
                                <div class="lab-hint" id="labHint">
                                    <i class="bi bi-arrow-up-circle-fill me-1"></i>Lab selected — view seat plan above
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label class="field-label">Preferred Time</label>
                                <input type="time" name="res_timein" class="form-control" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="field-label">Date</label>
                                <input type="date" name="res_date" id="res_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                            </div>

                            <!-- Selected Seat display -->
                            <div class="col-12" id="seatDisplayCol">
                                <label class="field-label">Selected Seat</label>
                                <div class="seat-chosen-box" id="seatChosenBox">
                                    <i class="bi bi-pc-display-horizontal"></i>
                                    <span id="seatChosenText">No seat selected</span>
                                    <button type="button" class="change-btn" onclick="openSeatPlan()">Change Seat</button>
                                </div>
                                <input type="hidden" name="res_seat" id="res_seat_input" value="">
                                <div id="seatRequiredNote" style="font-size:0.73rem;color:#e74c3c;margin-top:4px;display:none;">
                                    <i class="bi bi-exclamation-circle me-1"></i>Please select a seat from the seat plan.
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" name="submit_reservation" class="btn btn-purple px-4">
                                <i class="bi bi-calendar-plus me-2"></i>Submit Reservation
                            </button>
                        </div>
                    </form>
                </div>

                <!-- My Reservations table -->
                <div style="font-size:0.68rem;color:#aaa;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">
                    <i class="bi bi-list-ul me-1"></i>My Reservations
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size:0.82rem;">
                        <thead><tr><th>Date</th><th>Purpose</th><th>Lab</th><th>Time</th><th>Seat</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php if ($res_table_exists && $reservations && $reservations->num_rows > 0):
                            while ($r = $reservations->fetch_assoc()):
                                $rbadge = match(strtolower($r['status'])) {
                                    'pending'   => 'badge-pending',
                                    'approved'  => 'badge-approved',
                                    'cancelled' => 'badge-cancelled',
                                    default     => 'badge-pending'
                                }; ?>
                            <tr>
                                <td><?= htmlspecialchars($r['reservation_date']) ?></td>
                                <td><?= htmlspecialchars($r['purpose']) ?></td>
                                <td><?= htmlspecialchars($r['lab']) ?></td>
                                <td><?= htmlspecialchars($r['preferred_time']) ?></td>
                                <td><?= !empty($r['seat_number']) ? '<span style="font-weight:700;color:var(--ccs-purple);">#'.intval($r['seat_number']).'</span>' : '<span class="text-muted">—</span>' ?></td>
                                <td><span class="<?= $rbadge ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                                <td>
                                    <?php if (strtolower($r['status']) === 'pending'): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this reservation?')">
                                            <input type="hidden" name="res_id" value="<?= $r['id'] ?>">
                                            <button name="cancel_reservation" class="btn btn-sm btn-danger" style="font-size:0.73rem;padding:2px 8px;border-radius:6px;"><i class="bi bi-x-circle me-1"></i>Cancel</button>
                                        </form>
                                    <?php else: ?><span class="text-muted" style="font-size:0.75rem;">—</span><?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-3"><i class="bi bi-calendar-x me-2"></i>No reservations yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- ════ SEAT PLAN MODAL ════ -->
<div class="modal fade" id="seatPlanModal" tabindex="-1" aria-hidden="true" style="z-index:1065;">
    <div class="modal-dialog modal-dialog-centered" style="max-width:700px;">
        <div class="modal-content" style="border-radius:20px;overflow:hidden;border:none;">

            <!-- Header -->
            <div class="seat-plan-header d-flex justify-content-between align-items-center">
                <div>
                    <div style="font-weight:800;font-size:1.05rem;letter-spacing:0.01em;">
                        <i class="bi bi-pc-display me-2" style="color:#a1cbf7;"></i>Computer Lab Seat Plan
                    </div>
                    <div style="font-size:0.75rem;opacity:0.6;margin-top:3px;">Click an available (green) seat to select it</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="lab-chip" id="seatPlanLabLabel">Lab —</span>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <!-- Legend -->
            <div class="seat-legend">
                <div><span class="legend-dot legend-available"></span>Available</div>
                <div><span class="legend-dot legend-occupied"></span>Occupied / Reserved</div>
                <div><span class="legend-dot legend-selected"></span>Your Pick</div>
                <div class="ms-auto" style="font-size:0.75rem;color:#27ae60;font-weight:700;">
                    <i class="bi bi-pc-display me-1"></i><span id="availableCount">50</span> available
                </div>
            </div>

            <!-- Room layout -->
            <div class="seat-room-wrapper">
                <div class="front-board">
                    <i class="bi bi-easel me-2"></i>INSTRUCTOR'S STATION &nbsp;·&nbsp; FRONT OF ROOM
                </div>
                <div class="pc-grid" id="pcGrid"><!-- JS generated --></div>
            </div>

            <!-- Selected bar -->
            <div class="selected-seat-bar" id="selectedSeatBar">
                <i class="bi bi-check-circle-fill"></i>
                <div>
                    <div id="selectedSeatBarText" style="font-weight:800;">PC #— selected</div>
                    <div style="font-size:0.7rem;color:#a04000;font-weight:500;">Click "Confirm Seat" to save your choice</div>
                </div>
            </div>

            <!-- Footer -->
            <div class="seat-plan-footer">
                <div class="avail-counter">
                    Total PCs: 50 &nbsp;|&nbsp; Available: <strong id="availableCount2">—</strong>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </button>
                    <button type="button" class="btn btn-purple px-4" id="confirmSeatBtn" disabled onclick="confirmSeatSelection()">
                        <i class="bi bi-check2-circle me-2"></i>Confirm Seat
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// ── PHP data ──────────────────────────────────────────────────────────
const occupiedData = <?= json_encode($occupied_seats) ?>;

let currentLab   = '';
let currentDate  = '';
let selectedSeat = null;
let seatModal    = null;

// ── Lab change: open seat plan immediately ────────────────────────────
document.getElementById('res_lab').addEventListener('change', function () {
    const lab  = this.value;
    const date = document.getElementById('res_date').value;

    // Reset previous seat if lab changes
    resetSeatSelection();

    if (lab) {
        currentLab  = lab;
        currentDate = date;
        document.getElementById('labHint').classList.add('show');
        openSeatPlan();
    } else {
        document.getElementById('labHint').classList.remove('show');
    }
});

// Date change: warn user to re-pick seat
document.getElementById('res_date').addEventListener('change', function () {
    currentDate = this.value;
    if (selectedSeat) {
        resetSeatSelection();
        const hint = document.getElementById('labHint');
        hint.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1" style="color:#e74c3c;"></i>Date changed — please re-select your seat.';
        hint.classList.add('show');
    }
});

// ── Open seat plan modal ──────────────────────────────────────────────
function openSeatPlan() {
    const lab  = document.getElementById('res_lab').value;
    const date = document.getElementById('res_date').value;

    if (!lab) {
        Swal.fire({ icon: 'warning', title: 'Select a lab first', text: 'Please choose a laboratory before picking a seat.', confirmButtonColor: '#9757d6' });
        return;
    }

    currentLab  = lab;
    currentDate = date;

    document.getElementById('seatPlanLabLabel').textContent = 'Lab ' + lab;
    buildSeatGrid(lab, date);

    if (!seatModal) {
        seatModal = new bootstrap.Modal(document.getElementById('seatPlanModal'), { backdrop: 'static' });
    }
    seatModal.show();
}

// ── Build 50-seat grid ────────────────────────────────────────────────
function buildSeatGrid(lab, date) {
    const grid     = document.getElementById('pcGrid');
    const occupied = (occupiedData[lab] && occupiedData[lab][date]) ? occupiedData[lab][date] : [];
    grid.innerHTML = '';

    let availCount = 0;

    for (let i = 1; i <= 50; i++) {
        const isOccupied = occupied.includes(i);
        const isSelected = (selectedSeat === i);
        if (!isOccupied) availCount++;

        const div = document.createElement('div');
        div.className = 'pc-seat ' + (isSelected ? 'selected' : (isOccupied ? 'occupied' : 'available'));
        div.dataset.num = i;
        div.title = isOccupied ? 'PC #' + i + ' — Occupied' : 'PC #' + i + ' — Available';

        div.innerHTML = `
            <i class="bi bi-pc-display-horizontal"></i>
            <span>${i}</span>
            ${!isOccupied ? `
            <div class="seat-popover" id="pop_${i}" onclick="event.stopPropagation()">
                <div class="pop-title"><i class="bi bi-pc-display-horizontal"></i>PC #${i}</div>
                <button class="pop-btn-sit" onclick="selectThisSeat(${i})">
                    <i class="bi bi-cursor-fill me-1"></i>Sit Here
                </button>
                <button class="pop-btn-cancel" onclick="closePop(${i}); event.stopPropagation()">✕ Cancel</button>
            </div>` : ''}
        `;

        if (!isOccupied) {
            div.addEventListener('click', function(e) {
                e.stopPropagation();
                togglePop(i);
            });
        }

        grid.appendChild(div);
    }

    document.getElementById('availableCount').textContent  = availCount;
    document.getElementById('availableCount2').textContent = availCount;
}

// ── Popover toggle/close ──────────────────────────────────────────────
function togglePop(num) {
    document.querySelectorAll('.seat-popover').forEach(p => {
        if (p.id !== 'pop_' + num) p.style.display = 'none';
    });
    const pop = document.getElementById('pop_' + num);
    if (pop) pop.style.display = (pop.style.display === 'block') ? 'none' : 'block';
}
function closePop(num) {
    const pop = document.getElementById('pop_' + num);
    if (pop) pop.style.display = 'none';
}

// Close popovers on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('.pc-seat')) {
        document.querySelectorAll('.seat-popover').forEach(p => p.style.display = 'none');
    }
});

// ── Select a seat ─────────────────────────────────────────────────────
function selectThisSeat(num) {
    selectedSeat = num;
    closePop(num);

    // Update grid highlights
    document.querySelectorAll('.pc-seat').forEach(s => {
        s.classList.remove('selected');
        if (parseInt(s.dataset.num) === num) s.classList.add('selected');
    });

    // Show bottom bar
    const bar = document.getElementById('selectedSeatBar');
    bar.classList.add('show');
    document.getElementById('selectedSeatBarText').textContent = 'PC #' + num + ' selected';

    // Enable confirm button
    document.getElementById('confirmSeatBtn').disabled = false;
}

// ── Confirm: bring seat back to form ─────────────────────────────────
function confirmSeatSelection() {
    if (!selectedSeat) return;

    document.getElementById('res_seat_input').value = selectedSeat;

    // Show seat chosen box
    const box = document.getElementById('seatChosenBox');
    box.classList.add('show');
    document.getElementById('seatChosenText').textContent = 'PC #' + selectedSeat + '  —  Lab ' + currentLab;

    // Reset hint
    const hint = document.getElementById('labHint');
    hint.classList.remove('show');
    document.getElementById('seatRequiredNote').style.display = 'none';

    // Close seat plan modal
    bootstrap.Modal.getInstance(document.getElementById('seatPlanModal')).hide();
}

// ── Reset seat ────────────────────────────────────────────────────────
function resetSeatSelection() {
    selectedSeat = null;
    document.getElementById('res_seat_input').value = '';
    document.getElementById('seatChosenBox').classList.remove('show');
    document.getElementById('seatRequiredNote').style.display = 'none';

    const bar = document.getElementById('selectedSeatBar');
    if (bar) bar.classList.remove('show');

    const btn = document.getElementById('confirmSeatBtn');
    if (btn) btn.disabled = true;
}

// ── Form submit validation ────────────────────────────────────────────
document.getElementById('reservationForm').addEventListener('submit', function(e) {
    const lab  = document.getElementById('res_lab').value;
    const seat = document.getElementById('res_seat_input').value;
    if (lab && !seat) {
        e.preventDefault();
        document.getElementById('seatRequiredNote').style.display = 'block';
        openSeatPlan();
    }
});

// ── Profile picture preview ───────────────────────────────────────────
document.getElementById('profile_pic_input').addEventListener('change', function() {
    const [file] = this.files;
    if (file) {
        const url = URL.createObjectURL(file);
        document.getElementById('editAvatarPreview').src = url;
        document.getElementById('avatarPreview').src = url;
    }
});

// ── Auto-open reservation modal ───────────────────────────────────────
<?php if (isset($res_error) || isset($res_success)): ?>
document.addEventListener('DOMContentLoaded', function() {
    new bootstrap.Modal(document.getElementById('reservationModal')).show();
});
<?php endif; ?>

// ── Logout ────────────────────────────────────────────────────────────
function confirmLogout(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Logging out?',
        text: 'Are you sure you want to log out?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-box-arrow-right me-1"></i> Yes, Logout',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#9757d6',
        cancelButtonColor: '#6c757d',
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Logged out!',
                text: 'You have been logged out successfully.',
                icon: 'success', timer: 1500,
                showConfirmButton: false, timerProgressBar: true,
            }).then(() => { window.location.href = 'landingpage.php'; });
        }
    });
}

// ── Feedback modal opener ─────────────────────────────────────────────
function openFeedbackForm(sitinId) {
    document.getElementById('feedback_sitin_id').value = sitinId;
    // Hide history modal first, then show feedback modal
    bootstrap.Modal.getInstance(document.getElementById('historyModal')).hide();
    setTimeout(() => {
        new bootstrap.Modal(document.getElementById('feedbackModal')).show();
    }, 400);
}

<?php if (isset($feedback_success) || isset($feedback_error)): ?>
document.addEventListener('DOMContentLoaded', function() {
    new bootstrap.Modal(document.getElementById('historyModal')).show();
});
<?php endif; ?>

</script>
</body>
</html>