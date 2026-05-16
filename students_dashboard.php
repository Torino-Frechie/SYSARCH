<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("their-host", "their-username", "their-password", "their-dbname");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$id_number = $_SESSION['user'];

/* ── Handle Profile Update ─────────────────────────────────────────── */
if (isset($_POST['update_profile'])) {
    $fname   = $conn->real_escape_string(trim($_POST['first_name'] ?? ''));
    $mname   = $conn->real_escape_string(trim($_POST['middle_name'] ?? ''));
    $lname   = $conn->real_escape_string(trim($_POST['last_name'] ?? ''));
    $email   = $conn->real_escape_string(trim($_POST['email'] ?? ''));
    $course  = $conn->real_escape_string(trim($_POST['course'] ?? ''));
    $level   = intval($_POST['year_level'] ?? 1);
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

/* ── Handle Reservation ───────────────────────────────────────────── */
if (isset($_POST['submit_reservation'])) {
    $res_setting = $conn->query("SELECT value FROM system_settings WHERE setting_key = 'reservations_enabled'")->fetch_assoc();
    $res_enabled = !$res_setting || $res_setting['value'] !== '0';

    if (!$res_enabled) {
        $res_error = "Reservations are currently disabled by the administrator.";
    } else {
        $purpose   = $conn->real_escape_string(trim($_POST['res_purpose'] ?? ''));
        $lab       = $conn->real_escape_string(trim($_POST['res_lab'] ?? ''));
        $time_slot = $conn->real_escape_string(trim($_POST['res_time_slot'] ?? ''));
        $date      = $conn->real_escape_string(trim($_POST['res_date'] ?? ''));
        $pc_number = intval($_POST['res_pc'] ?? 0);

        $pc_check  = $conn->query("SELECT status FROM pc_status WHERE lab_name = '$lab' AND pc_number = $pc_number");
        $pc_status = $pc_check ? $pc_check->fetch_assoc() : null;

        if (!$pc_status || $pc_status['status'] !== 'available') {
            $res_error = "Selected PC is not available.";
        } else {
            $slot_check = $conn->query("SELECT id FROM reservations WHERE lab='$lab' AND reservation_date='$date' AND preferred_time='$time_slot' AND seat_number=$pc_number AND status IN ('Pending','Approved')");
            if ($slot_check && $slot_check->num_rows > 0) {
                $res_error = "This PC is already booked for the selected time slot.";
            } else {
                $dup = $conn->query("SELECT id FROM sitin_records WHERE id_number='$id_number' AND login_time LIKE '$date%' AND logout_time IS NULL");
                if ($dup && $dup->num_rows > 0) {
                    $res_error = "You already have an active sit-in on that date.";
                } else {
                    $conn->query("INSERT INTO reservations (id_number, purpose, lab, preferred_time, reservation_date, status, seat_number, created_at)
                                  VALUES ('$id_number','$purpose','$lab','$time_slot','$date','Pending',$pc_number,NOW())");

                    $_SESSION['success'] = "Reservation submitted successfully! PC #$pc_number reserved for $time_slot.";
                    header("Location: student_dashboard.php");
                    exit();
                }
            }
        }
    }
}

/* ── Cancel Reservation ───────────────────────────────────────────── */
if (isset($_POST['cancel_reservation'])) {
    $rid = intval($_POST['res_id'] ?? 0);
    $conn->query("DELETE FROM reservations WHERE id=$rid AND id_number='$id_number'");
    $res_success = "Reservation cancelled.";
}

/* ── Fetch user ────────────────────────────────────────────────────── */
$stmt = $conn->prepare("SELECT * FROM users WHERE id_number = ?");
$stmt->bind_param("s", $id_number);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "User not found.";
    exit;
}

$profile_pic = !empty($user['profile_pic'])
    ? "uploads/" . $user['profile_pic']
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . ' ' . ($user['last_name'] ?? '')) . '&background=9757d6&color=fff&size=150';

/* ── Session credits ───────────────────────────────────────────────── */
$rem             = intval($user['remaining_session'] ?? 30);
$max_credits     = 30;
$used_sessions   = $max_credits - $rem;
$credits_percent = $max_credits > 0 ? round(($rem / $max_credits) * 100) : 0;
$credits_color   = $rem > 15 ? '#27ae60' : ($rem > 5 ? '#f39c12' : '#e74c3c');

/* ── Sit-in Summary Stats ──────────────────────────────────────────── */
$summary = $conn->query("
    SELECT
        COUNT(*) as total_sessions,
        SUM(TIMESTAMPDIFF(MINUTE, login_time, IFNULL(logout_time, NOW()))) as total_minutes,
        AVG(TIMESTAMPDIFF(MINUTE, login_time, IFNULL(logout_time, NOW()))) as avg_minutes,
        MAX(TIMESTAMPDIFF(MINUTE, login_time, IFNULL(logout_time, NOW()))) as longest_minutes
    FROM sitin_records
    WHERE id_number = '$id_number'
")->fetch_assoc();

$total_sessions  = intval($summary['total_sessions'] ?? 0);
$total_hours     = !empty($summary['total_minutes']) ? round($summary['total_minutes'] / 60, 1) : 0;
$avg_duration    = !empty($summary['avg_minutes']) ? round($summary['avg_minutes']) : 0;
$longest_session = !empty($summary['longest_minutes']) ? round($summary['longest_minutes'] / 60, 1) : 0;

/* ── Software availability ────────────────────────────────────────── */
$software_table_exists = $conn->query("SHOW TABLES LIKE 'lab_software'")->num_rows > 0;

/* ── Reservation toggle setting ───────────────────────────────────── */
$settings_table_exists = $conn->query("SHOW TABLES LIKE 'system_settings'")->num_rows > 0;
$reservations_enabled  = true;
if ($settings_table_exists) {
    $res_setting = $conn->query("SELECT value FROM system_settings WHERE setting_key = 'reservations_enabled'")->fetch_assoc();
    if ($res_setting) $reservations_enabled = ($res_setting['value'] !== '0');
}

/* ── Fetch announcements ──────────────────────────────────────────── */
$announcements = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");

/* ── Fetch sit-in history ─────────────────────────────────────────── */
$stmt2 = $conn->prepare("SELECT * FROM sitin_records WHERE id_number = ? ORDER BY login_time DESC");
$stmt2->bind_param("s", $id_number);
$stmt2->execute();
$sessions = $stmt2->get_result();
$stmt2->close();

/* ── Fetch reservations ───────────────────────────────────────────── */
$res_table_exists = $conn->query("SHOW TABLES LIKE 'reservations'")->num_rows > 0;
$reservations = null;
if ($res_table_exists) {
    $stmt3 = $conn->prepare("SELECT * FROM reservations WHERE id_number = ? ORDER BY reservation_date DESC");
    $stmt3->bind_param("s", $id_number);
    $stmt3->execute();
    $reservations = $stmt3->get_result();
    $stmt3->close();
}

/* ── Fetch occupied PCs ────────────────────────────────────────────── */
$occupied_pcs = [];
if ($res_table_exists) {
    $oq = $conn->query("SELECT lab, reservation_date, preferred_time, seat_number FROM reservations WHERE status IN ('Pending','Approved') AND seat_number IS NOT NULL AND seat_number > 0");
    if ($oq) {
        while ($row = $oq->fetch_assoc()) {
            $occupied_pcs[$row['lab']][$row['reservation_date']][$row['preferred_time']][] = intval($row['seat_number']);
        }
    }
}

/* ── Submit Feedback ──────────────────────────────────────────────── */
if (isset($_POST['submit_feedback'])) {
    $sitin_id = intval($_POST['sitin_id'] ?? 0);
    $message  = $conn->real_escape_string(trim($_POST['feedback_message'] ?? ''));

    if ($message !== '') {
        $existing = $conn->query("SELECT id FROM feedback WHERE sitin_id = $sitin_id AND id_number = '$id_number'");
        if ($existing && $existing->num_rows === 0) {
            $conn->query("INSERT INTO feedback (sitin_id, id_number, message, created_at) VALUES ($sitin_id, '$id_number', '$message', NOW())");
            $feedback_success = "Feedback submitted!";
        } else {
            $feedback_error = "You already submitted feedback for this session.";
        }
    }
}

$time_slots = [
    '7:00 AM - 9:00 AM',
    '9:00 AM - 11:00 AM',
    '11:00 AM - 1:00 PM',
    '1:00 PM - 3:00 PM',
    '3:00 PM - 5:00 PM'
];
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
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        :root {
            --blue:        #2564ebbb;
            --blue-dark:    #1d4fd8ce;
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
            --blue-500:   var(--blue);
            --blue-600:   var(--blue-dark);
            --uc-blue:    var(--blue);
            --ccs-purple: var(--blue-mid);
            --ccs-gold:   var(--blue-mid);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
       body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--gray-50);
            color: var(--gray-800);
            overflow-x: hidden;
            animation: fadeIn .6s ease;
        }
        .navbar {
            background: var(--white);
            box-shadow: 0 1px 12px rgba(57, 103, 255, 0.92);
            padding: 8px 20px;
            height: 64px;
            display: flex;
            position: sticky; top: 0; z-index: 100;
        }
        .navbar-brand { 
            font-weight: 700; 
            color: black !important; 
            font-size: 0.95rem; 
            display: flex;
        }
        .nav-action-btn {
            display: flex; align-items: center; gap: 5px;
            color: rgba(0, 0, 0, 0.8) !important;
            font-weight: 600; font-size: 0.78rem; border-radius: 8px;
            padding: 6px 10px !important; transition: all 0.2s; text-decoration: none;
        }
        .nav-action-btn:hover { background: rgba(59, 79, 255, 0.66); color: white !important; }
        .nav-divider { width: 1px; height: 22px; background: rgba(36, 121, 248, 0.53); margin: 0 2px; }
        .btn-logout-nav {
            background: #ffffffa3; border: 1px solid rgba(17, 92, 253, 0.7);
            color: var(--uc-blue) !important; padding: 6px 14px; border-radius: 8px;
            font-size: 0.8rem; font-weight: 700; text-decoration: none; transition: all 0.2s;
        }
        .btn-logout-nav:hover { background: var(--ccs-gold); color: #000000 !important; }
        .nav-welcome { color: rgba(0, 0, 0, 0.75); font-size: 0.8rem; font-weight: 500; }

        .hero-section {
            background: linear-gradient(135deg, var(--ccs-purple) 0%, var(--uc-blue) 100%);
            color: white; padding: 32px 20px 60px;
            border-bottom-left-radius: 35px; border-bottom-right-radius: 35px; text-align: center;
        }
        .hero-section h2 { font-weight: 800; font-size: 1.5rem; text-transform: uppercase; margin-bottom: 4px; }
        .hero-section p { opacity: 0.8; font-size: 0.85rem; margin: 0; }
        .main-wrapper { margin-top: -36px; padding: 0 20px 40px; }

        .dash-card {
            background: white; border-radius: 18px;
            box-shadow: 0 6px 24px #c5c2f162;
            border: 1px solid rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 20px;
        }
        .card-header-purple { background: linear-gradient(135deg, var(--ccs-purple), rgba(21, 43, 241, 0.8)); color: white; font-weight: 600; font-size: 0.88rem; padding: 11px 18px; }
        .card-header-gold   { background: var(--ccs-purple); color: #ffffff; font-weight: 700; font-size: 0.88rem; padding: 11px 18px; }
        .card-header-blue   { background: linear-gradient(135deg, var(--ccs-purple), rgba(21, 43, 241, 0.8));  color: white; font-weight: 600; font-size: 0.88rem; padding: 11px 18px; }

        .avatar-wrap { position: relative; width: 90px; height: 90px; margin: 0 auto 10px; }
        .avatar-img  { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 4px solid white; box-shadow: 0 4px 14px rgba(151,87,214,0.25); }

        .info-row { display: flex; justify-content: space-between; align-items: flex-start; padding: 6px 0; border-bottom: 1px solid #f5f5f5; font-size: 0.82rem; gap: 8px; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #071ff5; white-space: nowrap; }
        .info-value { font-weight: 600; color: #333; text-align: right; word-break: break-word; }

        .credits-box { background: linear-gradient(135deg,#f8f1fe,#eef6ff); border-radius: 12px; padding: 12px 14px; margin: 12px 0; border: 1px solid rgba(151,87,214,0.1); }
        .credits-title { font-size: 0.67rem; text-transform: uppercase; letter-spacing: .08em; color: var(--ccs-purple); font-weight: 700; margin-bottom: 7px; }
        .credits-nums { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 7px; }
        .credits-big { font-size: 1.7rem; font-weight: 800; line-height: 1; }
        .credits-sub { font-size: 0.7rem; color: #aaa; }
        .credits-bar { height: 7px; border-radius: 4px; background: #e8e0f0; overflow: hidden; }
        .credits-fill { height: 100%; border-radius: 4px; transition: width .5s ease; }

        .summary-stat {
            background: linear-gradient(135deg,#f8f1fe,#eef6ff);
            border-radius: 12px; padding: 10px 12px; text-align: center;
            border: 1px solid rgb(10, 64, 243);
        }
        .summary-stat .s-num { font-size: 1.4rem; font-weight: 800; color: var(--uc-blue); line-height: 1; }
        .summary-stat .s-lbl { font-size: 0.67rem; color: #000000; margin-top: 3px; }

        .table thead th { background: linear-gradient(135deg, var(--ccs-purple), var(--blue-dark)); color: white; font-size: 0.78rem; font-weight: 600; border: none; padding: 9px 10px; }
        .table tbody td { font-size: 0.8rem; vertical-align: middle; padding: 7px 10px; }
        .table tbody tr:hover { background: #f8f1fe; }
        .table { margin-bottom: 0; }

        .badge-active    { background:#27ae60; color:white; padding:2px 8px; border-radius:20px; font-size:0.7rem; font-weight:600; }
        .badge-done      { background:#bdc3c7; color:#555; padding:2px 8px; border-radius:20px; font-size:0.7rem; font-weight:600; }
        .badge-pending   { background:#f39c12; color:white; padding:2px 8px; border-radius:20px; font-size:0.7rem; font-weight:600; }
        .badge-approved  { background:#27ae60; color:white; padding:2px 8px; border-radius:20px; font-size:0.7rem; font-weight:600; }
        .badge-cancelled { background:#95a5a6; color:white; padding:2px 8px; border-radius:20px; font-size:0.7rem; font-weight:600; }

        .modal-header-purple { background: linear-gradient(135deg, var(--blue-500), #1e40af); color:white; border-radius:16px 16px 0 0; padding:14px 20px; }
        .modal-header-purple .btn-close { filter: invert(1); }
        .modal-content { border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(37, 99, 235, 0.14); }
        .form-control:focus, .form-select:focus { border-color: var(--blue-500); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }
        .btn-purple { background: var(--blue-500); color:white; border:none; border-radius:8px; font-weight:600; font-size:0.85rem; transition:all .2s; }
        .btn-purple:hover { background: var(--blue-600); color: #fff !important;  opacity:.94; }
        .field-label { font-size:.75rem; color:#888; font-weight:500; margin-bottom:3px; }

        .ann-item { padding: 11px 0; border-bottom: 1px solid #f0f0f0; }
        .ann-item:last-child { border-bottom: none; }
        .ann-item .ann-admin { font-size: 0.77rem; font-weight: 700; color: var(--ccs-purple); margin-bottom: 2px; }
        .ann-item .ann-text  { font-size: 0.82rem; color: #444; margin: 0; }

        .rules-list { padding-left: 1.2rem; margin: 0; }
        .rules-list li { padding: 5px 0; border-bottom: 1px solid #f5f5f5; font-size: 0.82rem; color: #444; }
        .rules-list li:last-child { border-bottom: none; }

        .res-form-box { background: linear-gradient(135deg,#f8faff,#f1f5ff); border-radius:12px; padding:1.1rem; margin-bottom:1.1rem; border:1px solid #dbeafe; }
        .res-form-box .res-title { font-size:.67rem; color: var(--blue-500); font-weight:700; text-transform:uppercase; letter-spacing:.08em; margin-bottom:10px; }
        #historyModal .table,
        #reservationModal .table {
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            letter-spacing: 0.01em;
            color: var(--gray-700);
        }
        #historyModal .table thead th,
        #reservationModal .table thead th {
            background: var(--blue-500);
            color: #fff;
            font-weight: 700;
            border: none;
            padding: 12px 16px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        #historyModal .table tbody td,
        #reservationModal .table tbody td {
            padding: 14px 16px;
            border-top: 1px solid var(--gray-200);
            color: var(--gray-700);
        }
        #historyModal .table tbody tr:nth-child(even),
        #reservationModal .table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        #historyModal .table tbody tr:hover,
        #reservationModal .table tbody tr:hover {
            background: #eef4ff;
        }
        #historyModal .table-responsive,
        #reservationModal .table-responsive {
            background: #ffffff;
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            padding: 10px;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.08);
        }
        .modal-content {
            border-radius: 20px;
            border: 1px solid var(--gray-200);
            background: #ffffff;
        }
        .modal-body {
            background: #fbfdff;
        }
        .time-slot-option { flex:1; min-width:110px; }
        .time-slot-option input[type="radio"] { display:none; }
        .time-slot-option label { display:block; padding:8px 10px; background:#f8f9fa; border:2px solid #e0e0e0; border-radius:10px; text-align:center; cursor:pointer; transition:all .25s; font-size:0.78rem; font-weight:600; color:#666; }
        .time-slot-option input[type="radio"]:checked + label { background:linear-gradient(135deg,var(--uc-blue),var(--blue-dark)); border-color:var(--uc-blue); color:white; box-shadow:0 4px 12px rgba(37,99,235,0.18); }
        .time-slot-option label:hover { border-color:var(--ccs-purple); background:#f3e9fd; }

        .pc-card-sel { aspect-ratio:1; border-radius:8px; display:flex; flex-direction:column; align-items:center; justify-content:center; font-size:0.68rem; font-weight:700; cursor:pointer; border:2px solid transparent; transition:all .2s; }
        .pc-card-sel i { font-size:.95rem; margin-bottom:2px; }
        .pc-card-sel.available    { background:linear-gradient(145deg,#d5f5e3,#a9dfbf); color:#1a6835; border-color:#82c99a; }
        .pc-card-sel.available:hover { transform:scale(1.06); box-shadow:0 4px 12px rgba(39,174,96,.3); }
        .pc-card-sel.occupied     { background:linear-gradient(145deg,#fadbd8,#f1948a); color:#7b241c; border-color:#e57373; cursor:not-allowed; opacity:.7; }
        .pc-card-sel.maintenance  { background:linear-gradient(145deg,#fdebd0,#f8c471); color:#784212; border-color:#f39c12; cursor:not-allowed; }
        .pc-card-sel.selected     { background:linear-gradient(145deg,var(--uc-blue),var(--blue-dark)); color:white; border-color:var(--ccs-gold); box-shadow:0 4px 14px rgba(37,99,235,0.25); }
        .legend-dot { width:12px; height:12px; border-radius:3px; display:inline-block; margin-right:4px; vertical-align:middle; }

        .res-disabled-banner { background:linear-gradient(135deg,#fadbd8,#f1948a); color:#7b241c; border-radius:12px; padding:12px 16px; font-weight:600; font-size:0.85rem; border:1px solid #e57373; }

        .sitin-summary-section { padding: 0 20px 40px; }
        .sitin-summary-section .section-divider {
            display: flex; align-items: center; gap: 14px; margin-bottom: 20px;
        }
        .sitin-summary-section .section-divider::before,
        .sitin-summary-section .section-divider::after {
            content: '';
            flex: 1;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(46, 77, 252, 0.51), transparent);
        }
        .sitin-summary-section .section-divider span {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--ccs-purple);
            white-space: nowrap;
        }

        .scroll-hint {
            margin-top: 18px;
            animation: bounce 2s infinite;
            opacity: 0.7;
            font-size: 0.75rem;
            color: white;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(6px); }
        }

        /* Modal header gradient */
        .modal-header-purple {
            background: linear-gradient(135deg, #2395ff 0%, #1f40ffc9 100%);
            padding: 16px 22px;
            color: #fff;
        }
        .modal-header-purple h6 { font-size: 0.97rem; }
        
        /* Lab filter tab pills */
        .sw-lab-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .sw-lab-tab {
            padding: 5px 16px;
            border-radius: 20px;
            border: 1.5px solid #d5d8e8;
            background: #fff;
            color: #555;
            font-size: 0.81rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s;
            font-family: inherit;
        }
        .sw-lab-tab:hover { border-color: #3363ffcb; color: #0207ff; }
        .sw-lab-tab.active { background: #3284ffe1; color: #fff; border-colorrgb(82, 70, 253)80; }
        
        /* Card grid */
        .sw-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
            gap: 14px;
        }
        
        /* Individual card */
        .sw-card-item {
            border: 1.5px solid #e8eaf0;
            border-radius: 12px;
            padding: 14px 14px 12px;
            background: #fff;
            display: flex;
            flex-direction: column;
            gap: 8px;
            transition: box-shadow 0.18s, border-color 0.18s;
        }
        .sw-card-item:hover {
            box-shadow: 0 4px 18px rgba(80,40,140,0.09);
            border-color: #c5b8e8;
        }
        
        /* Card top row: name + badge */
        .sw-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 6px;
        }
        .sw-card-name {
            font-size: 0.87rem;
            font-weight: 700;
            color: hsl(0, 0%, 0%);
            line-height: 1.25;
        }
        
        /* Availability badges */
        .sw-badge-available {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            background: #e6f9ef;
            color: #1a7a44;
            border-radius: 6px;
            padding: 2px 7px;
            font-size: 0.70rem;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .sw-badge-unavailable {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            background: #fde8e8;
            color: #b91c1c;
            border-radius: 6px;
            padding: 2px 7px;
            font-size: 0.70rem;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
        }
        
        /* Version text */
        .sw-card-version {
            font-size: 0.78rem;
            color: #888;
        }
        
        /* Lab tag chip */
        .sw-card-lab-tag {
            display: inline-block;
            background: #eef0fd;
            color: #3363ffcb;
            border-radius: 6px;
            padding: 2px 10px;
            font-size: 0.74rem;
            font-weight: 600;
            width: fit-content;
        }

        footer { padding:24px 0; text-align:center; color:#bbb; font-size:0.78rem; }

    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><i class="bi bi-pc-display-horizontal me-2"></i>CCS Sit-in Monitoring</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu"
            style="border:none;background:rgba(0, 0, 0, 0.15);border-radius:8px;padding:4px 8px;">
            <span class="navbar-toggler-icon" style="background-image:url(&quot;data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='white' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e&quot;);"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMenu">
            <div class="d-flex align-items-center gap-1 ms-auto flex-wrap py-1 py-lg-0">
                <a href="#" class="nav-action-btn" data-bs-toggle="modal" data-bs-target="#editProfileModal"><i class="bi bi-pencil-square"></i><span>Edit Profile</span></a>
                <div class="nav-divider d-none d-lg-block"></div>
                <a href="#" class="nav-action-btn" data-bs-toggle="modal" data-bs-target="#notifModal"><i class="bi bi-bell-fill"></i><span>Notifications</span></a>
                <div class="nav-divider d-none d-lg-block"></div>
                <a href="#" class="nav-action-btn" data-bs-toggle="modal" data-bs-target="#historyModal"><i class="bi bi-clock-history"></i><span>History</span></a>
                <div class="nav-divider d-none d-lg-block"></div>
                <a href="#" class="nav-action-btn" data-bs-toggle="modal" data-bs-target="#softwareModal"><i class="bi bi-app-indicator"></i><span>Lab Software</span></a>
                <div class="nav-divider d-none d-lg-block"></div>
                <?php if ($reservations_enabled): ?>
                    <a href="#" class="nav-action-btn" data-bs-toggle="modal" data-bs-target="#reservationModal"><i class="bi bi-calendar-check-fill"></i><span>Reservation</span></a>
                <?php else: ?>
                    <span class="nav-action-btn" style="opacity:.5;cursor:not-allowed;" title="Reservations are disabled"><i class="bi bi-calendar-x-fill"></i><span>Reservation</span></span>
                <?php endif; ?>
                <div class="nav-divider d-none d-lg-block"></div>
                <span class="nav-welcome ms-1">Hi, <strong><?= htmlspecialchars($user['first_name']) ?></strong></span>
                <a href="#" class="btn-logout-nav ms-1" onclick="confirmLogout(event)"><i class="bi bi-box-arrow-right me-1"></i>Log out</a>
            </div>
        </div>
    </div>
</nav>

<div class="hero-section">
    <h2>Student Dashboard</h2>
    <p>College of Computer Studies · Sit-in Monitoring System</p>
    <div class="scroll-hint">
        <i class="bi bi-chevron-double-down d-block mb-1" style="font-size:1rem;"></i>
        Scroll down for your sit-in summary
    </div>
</div>

<div class="main-wrapper">
    <div class="container-fluid px-0 px-md-2">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show py-2 px-3 mb-3" style="border-radius:10px;font-size:0.85rem;">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($profile_success)): ?>
            <div class="alert alert-success alert-dismissible fade show py-2 px-3 mb-3" style="border-radius:10px;font-size:0.85rem;">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($profile_success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!$reservations_enabled): ?>
            <div class="res-disabled-banner mb-3">
                <i class="bi bi-lock-fill me-2"></i>
                Reservations are currently <strong>disabled</strong> by the administrator.
            </div>
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-lg-3 col-md-4">
                <div class="dash-card">
                    <div class="card-header-purple"><i class="bi bi-person-fill me-2"></i>Student Information</div>
                    <div class="card-body p-3 text-center">
                        <div class="avatar-wrap">
                            <img src="<?= htmlspecialchars($profile_pic) ?>" id="avatarPreview" class="avatar-img"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['first_name'].' '.$user['last_name']) ?>&background=9757d6&color=fff&size=150'">
                        </div>
                        <div style="font-weight:700;font-size:0.95rem;color:#222;"><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></div>
                        <div style="font-size:0.72rem;color:#aaa;margin-bottom:10px;"><?= htmlspecialchars($user['course']) ?> &mdash; Year <?= htmlspecialchars($user['year_level']) ?></div>
                        <div class="info-row"><span class="info-label"><i class="bi bi-card-text me-1"></i>ID</span><span class="info-value"><?= htmlspecialchars($user['id_number']) ?></span></div>
                        <div class="info-row"><span class="info-label"><i class="bi bi-book me-1"></i>Course</span><span class="info-value"><?= htmlspecialchars($user['course']) ?></span></div>
                        <div class="info-row"><span class="info-label"><i class="bi bi-calendar3 me-1"></i>Year</span><span class="info-value">Year <?= htmlspecialchars($user['year_level']) ?></span></div>
                        <div class="info-row"><span class="info-label"><i class="bi bi-envelope me-1"></i>Email</span><span class="info-value" style="font-size:0.72rem;"><?= htmlspecialchars($user['email']) ?></span></div>

                        <div class="credits-box">
                            <div class="credits-title"><i class="bi bi-ticket-perforated me-1"></i>Session Credits</div>
                            <div class="credits-nums">
                                <div>
                                    <div class="credits-big" style="color:<?= $credits_color ?>;"><?= $rem ?></div>
                                    <div class="credits-sub">remaining</div>
                                </div>
                                <div class="text-end">
                                    <div style="font-size:0.7rem;color:#bbb;">Used</div>
                                    <div style="font-size:0.9rem;font-weight:700;color:#555;"><?= $used_sessions ?> / <?= $max_credits ?></div>
                                </div>
                            </div>
                            <div class="credits-bar">
                                <div class="credits-fill" style="width:<?= $credits_percent ?>%;background:<?= $credits_color ?>;"></div>
                            </div>
                            <?php if ($rem <= 5): ?>
                                <div style="font-size:0.72rem;color:#e74c3c;margin-top:5px;">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Low credits! Contact admin.
                                </div>
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

            <div class="col-lg-5 col-md-8">
                <div class="dash-card h-100">
                    <div class="card-header-purple"><i class="bi bi-megaphone-fill me-2"></i>Announcements</div>
                    <div class="card-body p-3">
                        <?php if ($announcements && $announcements->num_rows > 0): ?>
                            <?php while ($a = $announcements->fetch_assoc()): ?>
                                <div class="ann-item">
                                    <div class="ann-admin"><?= htmlspecialchars($a['admin_name'] ?? 'Admin') ?></div>
                                    <p class="ann-text"><?= htmlspecialchars($a['message'] ?? '') ?></p>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted mb-0" style="font-size:0.85rem;">No announcements yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 d-none d-lg-block">
                <div class="dash-card h-100">
                    <div class="card-header-blue"><i class="bi bi-shield-check me-2"></i>Laboratory Rules &amp; Regulations</div>
                    <div class="card-body p-3">
                        <p style="font-weight:700;text-align:center;color:var(--ccs-purple);font-size:0.88rem;">University of Cebu</p>
                        <p style="font-weight:600;text-align:center;color:#555;font-size:0.78rem;margin-bottom:12px;">COLLEGE OF COMPUTER STUDIES</p>
                        <p style="font-weight:700;font-size:0.82rem;color:#333;margin-bottom:7px;">LABORATORY RULES AND REGULATIONS</p>
                        <p style="font-size:0.78rem;color:#555;margin-bottom:8px;">To avoid embarrassment and maintain camaraderie with your friends and superiors at our laboratories, please observe the following:</p>
                        <ol style="padding-left:1.2rem;">
                            <li style="font-size:0.78rem;color:#444;padding:4px 0;border-bottom:1px solid #f5f5f5;">Maintain silence, proper decorum, and discipline inside the laboratory.</li>
                            <li style="font-size:0.78rem;color:#444;padding:4px 0;border-bottom:1px solid #f5f5f5;">Games are not allowed inside the lab.</li>
                            <li style="font-size:0.78rem;color:#444;padding:4px 0;border-bottom:1px solid #f5f5f5;">Surfing the Internet is allowed only with the permission of the instructor.</li>
                            <li style="font-size:0.78rem;color:#444;padding:4px 0;border-bottom:1px solid #f5f5f5;">Eating, drinking, and smoking are strictly prohibited.</li>
                            <li style="font-size:0.78rem;color:#444;padding:4px 0;border-bottom:1px solid #f5f5f5;">Observe proper sitting posture at all times.</li>
                            <li style="font-size:0.78rem;color:#444;padding:4px 0;border-bottom:1px solid #f5f5f5;">Report any damaged equipment to the laboratory attendant.</li>
                            <li style="font-size:0.78rem;color:#444;padding:4px 0;">Seats are to be arranged neatly after use.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Sit-in Summary ── -->
        <div class="row g-3 mt-1">
            <div class="col-12">
                <div class="dash-card">
                    <div class="card-header-purple">
                        <i class="bi bi-bar-chart-fill me-2"></i>Sit-in Summary
                    </div>
                    <div class="card-body p-4">
                        <?php if ($total_sessions === 0): ?>
                            <div style="text-align:center;padding:40px 20px;">
                                <i class="bi bi-clipboard2" style="font-size:3rem;color:#ccc;display:block;margin-bottom:12px;"></i>
                                <div style="font-weight:700;font-size:0.95rem;color:#444;">No sit-in data yet.</div>
                                <div style="font-size:0.82rem;color:#aaa;margin-top:4px;">Your statistics will appear here after your first completed sit-in session.</div>
                            </div>
                        <?php else: ?>
                            <div class="row g-3 text-center">
                                <div class="col-6 col-md-3">
                                    <div class="summary-stat">
                                        <div class="s-num"><?= $total_sessions ?></div>
                                        <div class="s-lbl"><i class="bi bi-person-check me-1"></i>Total Sessions</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="summary-stat">
                                        <div class="s-num"><?= $total_hours ?>h</div>
                                        <div class="s-lbl"><i class="bi bi-clock me-1"></i>Total Hours</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="summary-stat">
                                        <div class="s-num"><?= $avg_duration ?>m</div>
                                        <div class="s-lbl"><i class="bi bi-hourglass-split me-1"></i>Avg Duration</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="summary-stat">
                                        <div class="s-num"><?= $longest_session ?>h</div>
                                        <div class="s-lbl"><i class="bi bi-trophy me-1"></i>Longest Session</div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>


<footer>&copy; <?= date('Y') ?> University of Cebu &mdash; College of Computer Studies | CCS Sit-in Monitoring System</footer>

<!-- Edit Profile Modal -->
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
                        <div style="position:relative;width:80px;height:80px;margin:0 auto;">
                            <img src="<?= htmlspecialchars($profile_pic) ?>" id="editAvatarPreview"
                                 style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:4px solid white;box-shadow:0 4px 14px rgba(151,87,214,0.25);"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['first_name'].' '.$user['last_name']) ?>&background=9757d6&color=fff&size=80'">
                            <label for="profile_pic_input" style="position:absolute;bottom:2px;right:2px;background:var(--ccs-purple);color:white;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;border:2px solid white;font-size:0.7rem;">
                                <i class="bi bi-camera-fill"></i>
                            </label>
                        </div>
                        <input type="file" name="profile_pic" id="profile_pic_input" hidden accept="image/*">
                        <div style="font-size:0.7rem;color:#aaa;margin-top:5px;">Click camera to change photo</div>
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
                                <option value="">-- Select --</option>
                                <?php
                                $courses = [
                                    'Information Technology',
                                    'Computer Engineering',
                                    'Civil Engineering',
                                    'Mechanical Engineering',
                                    'Electrical Engineering',
                                    'Industrial Engineering',
                                    'Naval Architecture and Marine Engineering',
                                    'Elementary Education (BEEd)',
                                    'Secondary Education (BSEd)',
                                    'Criminology',
                                    'Commerce',
                                    'Accountancy',
                                    'Hotel and Restaurant Management',
                                    'Customs Administration',
                                    'Computer Secretarial',
                                    'Industrial Psychology',
                                    'AB Political Science',
                                    'AB English'
                                ];
                                foreach ($courses as $c):
                                ?>
                                    <option value="<?= $c ?>" <?= $user['course'] === $c ? 'selected' : '' ?>><?= $c ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="field-label">Year Level</label>
                            <select name="year_level" class="form-select" required>
                                <option value="1" <?= $user['year_level'] == 1 ? 'selected' : '' ?>>1st</option>
                                <option value="2" <?= $user['year_level'] == 2 ? 'selected' : '' ?>>2nd</option>
                                <option value="3" <?= $user['year_level'] == 3 ? 'selected' : '' ?>>3rd</option>
                                <option value="4" <?= $user['year_level'] == 4 ? 'selected' : '' ?>>4th</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="field-label">New Password <span class="text-muted">(leave blank to keep)</span></label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••">
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_profile" class="btn btn-purple px-4"><i class="bi bi-save-fill me-1"></i>Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Notifications Modal -->
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
                        <div style="width:36px;height:36px;min-width:36px;border-radius:50%;background:#f3e9fd;display:flex;align-items:center;justify-content:center;">
                            <i class="bi bi-megaphone-fill" style="color:var(--ccs-purple);font-size:0.85rem;"></i>
                        </div>
                        <div>
                            <div style="font-size:0.83rem;font-weight:600;color:#333;"><?= htmlspecialchars($n['admin_name']) ?></div>
                            <div style="font-size:0.78rem;color:#666;"><?= htmlspecialchars($n['message']) ?></div>
                            <div style="font-size:0.7rem;color:#bbb;margin-top:2px;"><i class="bi bi-calendar3 me-1"></i><?= $nd ?></div>
                        </div>
                    </div>
                <?php endwhile; else: ?>
                    <p class="text-muted text-center mb-0"><i class="bi bi-bell-slash me-2"></i>No notifications yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;">

            <!-- Header -->
            <div class="modal-header-purple d-flex justify-content-between align-items-start px-4 py-3">
                <div>
                    <h6 class="mb-0 fw-bold" style="font-size:1.1rem;"><i class="bi bi-clipboard2-data me-2"></i>History Information</h6>
                    <small style="opacity:0.8;font-size:0.78rem;">Your previous sit-in sessions</small>
                </div>
                <button type="button" class="btn-close btn-close-white mt-1" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">

                <?php if (isset($feedback_success)): ?>
                    <div class="alert alert-success py-2 px-3 mb-3" style="border-radius:8px;font-size:0.83rem;"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($feedback_success) ?></div>
                <?php endif; ?>
                <?php if (isset($feedback_error)): ?>
                    <div class="alert alert-warning py-2 px-3 mb-3" style="border-radius:8px;font-size:0.83rem;"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($feedback_error) ?></div>
                <?php endif; ?>

                <?php
                // Build sessions array for JS-powered search/pagination
                $history_rows = [];
                if ($sessions) {
                    $sessions->data_seek(0);
                    $fb_map = [];
                    $fb_res = $conn->query("SELECT sitin_id, message FROM feedback WHERE id_number = '$id_number'");
                    if ($fb_res) while ($fb = $fb_res->fetch_assoc()) $fb_map[$fb['sitin_id']] = $fb['message'];

                    while ($s = $sessions->fetch_assoc()) {
                        $is_active  = empty($s['logout_time']);
                        $login_dt   = new DateTime($s['login_time']);
                        $logout_dt  = $is_active ? new DateTime() : new DateTime($s['logout_time']);
                        $diff       = $login_dt->diff($logout_dt);
                        $dur_str    = $is_active ? 'Ongoing' : ($diff->h > 0 ? $diff->h.'h '.$diff->i.'m' : $diff->i.'m');
                        $has_fb     = isset($fb_map[$s['id']]);
                        $history_rows[] = [
                            'id'         => $s['id'],
                            'date'       => date('M d, Y', strtotime($s['login_time'])),
                            'login'      => date('h:i A', strtotime($s['login_time'])),
                            'logout'     => $is_active ? '—' : date('h:i A', strtotime($s['logout_time'])),
                            'duration'   => $dur_str,
                            'purpose'    => htmlspecialchars($s['purpose']),
                            'lab'        => htmlspecialchars($s['lab']),
                            'pc'         => !empty($s['pc_number']) ? '#'.htmlspecialchars($s['pc_number']) : '—',
                            'is_active'  => $is_active,
                            'has_fb'     => $has_fb,
                            'fb_msg'     => $has_fb ? htmlspecialchars($fb_map[$s['id']]) : '',
                            'status'     => $is_active ? 'Active' : 'Done',
                        ];
                    }
                }
                ?>

                <!-- Controls: Show entries + Search -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2" style="font-size:0.85rem;color:#555;">
                        Show
                        <select id="histPageSize" class="form-select form-select-sm" style="width:70px;" onchange="histRender()">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        entries per page
                    </div>
                    <div style="position:relative;">
                        <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#aaa;font-size:0.8rem;"></i>
                        <input type="text" id="histSearch" class="form-control form-control-sm" placeholder="Search..." style="padding-left:30px;width:200px;border-radius:20px;" oninput="histRender()">
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive" style="border:1px solid #e8eaf0;border-radius:10px;overflow:hidden;">
                    <table class="table mb-0" style="font-size:0.82rem;">
                        <thead>
                            <tr style="background:linear-gradient(135deg,#3a2080,#7c4fc4);color:#fff;">
                                <th style="padding:12px 14px;font-weight:600;cursor:pointer;white-space:nowrap;" onclick="histSort('date')">DATE <i class="bi bi-arrow-down-up ms-1" style="font-size:0.7rem;opacity:0.7;"></i></th>
                                <th style="padding:12px 14px;font-weight:600;cursor:pointer;white-space:nowrap;" onclick="histSort('login')">LOGIN <i class="bi bi-arrow-down-up ms-1" style="font-size:0.7rem;opacity:0.7;"></i></th>
                                <th style="padding:12px 14px;font-weight:600;white-space:nowrap;">LOGOUT</th>
                                <th style="padding:12px 14px;font-weight:600;white-space:nowrap;">DURATION</th>
                                <th style="padding:12px 14px;font-weight:600;cursor:pointer;white-space:nowrap;" onclick="histSort('purpose')">SIT PURPOSE <i class="bi bi-arrow-down-up ms-1" style="font-size:0.7rem;opacity:0.7;"></i></th>
                                <th style="padding:12px 14px;font-weight:600;cursor:pointer;white-space:nowrap;" onclick="histSort('lab')">LABORATORY <i class="bi bi-arrow-down-up ms-1" style="font-size:0.7rem;opacity:0.7;"></i></th>
                                <th style="padding:12px 14px;font-weight:600;white-space:nowrap;">PC #</th>
                                <th style="padding:12px 14px;font-weight:600;white-space:nowrap;">STATUS</th>
                                <th style="padding:12px 14px;font-weight:600;white-space:nowrap;">ACTION</th>
                            </tr>
                        </thead>
                        <tbody id="histTbody">
                            <!-- Filled by JS -->
                        </tbody>
                    </table>
                </div>

                <!-- Empty State (shown by JS when no rows) -->
                <div id="histEmptyState" style="display:none;text-align:center;padding:48px 20px;">
                    <i class="bi bi-clipboard2 " style="font-size:3rem;color:#ccc;display:block;margin-bottom:12px;"></i>
                    <div style="font-weight:700;font-size:0.95rem;color:#444;">No sit-in history yet.</div>
                    <div style="font-size:0.82rem;color:#aaa;margin-top:4px;">Your completed sit-in sessions will appear here.</div>
                </div>

                <!-- Footer: entry count + pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div id="histEntryInfo" style="font-size:0.82rem;color:#777;"></div>
                    <div id="histPagination" class="d-flex gap-1"></div>
                </div>
            </div>
        </div>
    </div>
</div>

                <script>
                // ── History table data from PHP ──
                var histData = <?= json_encode(array_values($history_rows)) ?>;
                var histPage = 1;
                var histSortKey = 'date';
                var histSortAsc = false;
                var histFiltered = [];

                function histRender() {
                    var search   = document.getElementById('histSearch').value.toLowerCase();
                    var pageSize = parseInt(document.getElementById('histPageSize').value);

                    // Filter
                    histFiltered = histData.filter(function(r) {
                        return (r.date + r.login + r.logout + r.purpose + r.lab + r.pc + r.status)
                            .toLowerCase().indexOf(search) > -1;
                    });

                    // Sort
                    histFiltered.sort(function(a, b) {
                        var va = a[histSortKey] || '', vb = b[histSortKey] || '';
                        return histSortAsc ? va.localeCompare(vb) : vb.localeCompare(va);
                    });

                    // Paginate
                    var total   = histFiltered.length;
                    var pages   = Math.max(1, Math.ceil(total / pageSize));
                    if (histPage > pages) histPage = 1;
                    var start   = (histPage - 1) * pageSize;
                    var pageRows = histFiltered.slice(start, start + pageSize);

                    // Render rows
                    var tbody = document.getElementById('histTbody');
                    var empty = document.getElementById('histEmptyState');

                    if (pageRows.length === 0) {
                        tbody.innerHTML = '';
                        empty.style.display = '';
                    } else {
                        empty.style.display = 'none';
                        tbody.innerHTML = pageRows.map(function(r, i) {
                            var rowBg = i % 2 === 0 ? '#fff' : '#fafafa';
                            var statusBadge = r.is_active
                                ? '<span style="background:#d4efdf;color:#1e8449;border-radius:6px;padding:2px 8px;font-size:0.72rem;font-weight:600;">Active</span>'
                                : '<span style="background:#eaecf0;color:#555;border-radius:6px;padding:2px 8px;font-size:0.72rem;font-weight:600;">Done</span>';

                            var actionCell;
                            if (r.is_active) {
                                actionCell = '<span style="font-size:0.72rem;color:#aaa;">Ongoing</span>';
                            } else if (r.has_fb) {
                                actionCell = '<span style="font-size:0.72rem;color:#27ae60;font-weight:600;"><i class="bi bi-check-circle-fill me-1"></i>Submitted</span>'
                                        + (r.fb_msg ? '<div style="font-size:0.7rem;color:#888;font-style:italic;max-width:140px;">"' + r.fb_msg + '"</div>' : '');
                            } else {
                                actionCell = '<button class="btn btn-sm btn-purple" style="font-size:0.7rem;padding:2px 10px;border-radius:6px;" onclick="openFeedbackForm(' + r.id + ')">'
                                        + '<i class="bi bi-chat-left-text me-1"></i>Feedback</button>';
                            }

                            return '<tr style="background:' + rowBg + ';border-bottom:1px solid #f0f0f0;">'
                                + '<td style="padding:10px 14px;">' + r.date + '</td>'
                                + '<td style="padding:10px 14px;">' + r.login + '</td>'
                                + '<td style="padding:10px 14px;">' + r.logout + '</td>'
                                + '<td style="padding:10px 14px;">' + r.duration + '</td>'
                                + '<td style="padding:10px 14px;">' + r.purpose + '</td>'
                                + '<td style="padding:10px 14px;">' + r.lab + '</td>'
                                + '<td style="padding:10px 14px;">' + r.pc + '</td>'
                                + '<td style="padding:10px 14px;">' + statusBadge + '</td>'
                                + '<td style="padding:10px 14px;">' + actionCell + '</td>'
                                + '</tr>';
                        }).join('');
                    }

                    // Entry info
                    var info = document.getElementById('histEntryInfo');
                    if (total === 0) {
                        info.textContent = 'No entries';
                    } else {
                        info.textContent = 'Showing ' + (start + 1) + ' to ' + Math.min(start + pageSize, total) + ' of ' + total + ' entries';
                    }

                    // Pagination buttons
                    var pag = document.getElementById('histPagination');
                    var btnStyle = 'border:1px solid #ddd;background:#fff;border-radius:6px;padding:3px 9px;font-size:0.78rem;cursor:pointer;color:#555;';
                    var activeBtnStyle = 'border:1px solid #3a2080;background:#3a2080;border-radius:6px;padding:3px 9px;font-size:0.78rem;cursor:pointer;color:#fff;font-weight:600;';
                    var html = '';
                    html += '<button style="' + btnStyle + '" onclick="histGoPage(1)" ' + (histPage===1?'disabled':'') + '>«</button>';
                    html += '<button style="' + btnStyle + '" onclick="histGoPage(' + (histPage-1) + ')" ' + (histPage===1?'disabled':'') + '>‹</button>';
                    for (var p = 1; p <= pages; p++) {
                        html += '<button style="' + (p===histPage ? activeBtnStyle : btnStyle) + '" onclick="histGoPage(' + p + ')">' + p + '</button>';
                    }
                    html += '<button style="' + btnStyle + '" onclick="histGoPage(' + (histPage+1) + ')" ' + (histPage===pages?'disabled':'') + '>›</button>';
                    html += '<button style="' + btnStyle + '" onclick="histGoPage(' + pages + ')" ' + (histPage===pages?'disabled':'') + '>»</button>';
                    pag.innerHTML = html;
                }

                function histGoPage(p) {
                    var pageSize = parseInt(document.getElementById('histPageSize').value);
                    var pages = Math.max(1, Math.ceil(histFiltered.length / pageSize));
                    histPage = Math.min(Math.max(1, p), pages);
                    histRender();
                }

                function histSort(key) {
                    if (histSortKey === key) { histSortAsc = !histSortAsc; }
                    else { histSortKey = key; histSortAsc = true; }
                    histRender();
                }

                // Init on modal open
                document.getElementById('historyModal').addEventListener('show.bs.modal', function() {
                    histPage = 1;
                    document.getElementById('histSearch').value = '';
                    histRender();
                });
                </script>

<!-- Feedback Modal -->
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
                        <button type="submit" name="submit_feedback" class="btn btn-purple px-4"><i class="bi bi-send-fill me-2"></i>Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Software Availability Modal -->
<div class="modal fade" id="softwareModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;">
            <div class="modal-header-purple d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-display me-2"></i>Lab Software Availability</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <?php if ($software_table_exists): ?>
                    <?php
                    $labs_sw = $conn->query("SELECT lab_name FROM lab_config ORDER BY lab_name");
                    if ($labs_sw && $labs_sw->num_rows > 0):
                        $all_sw = $conn->query("SELECT * FROM lab_software ORDER BY lab_name, software_name");
                        $sw_rows = [];
                        while ($row = $all_sw->fetch_assoc()) {
                            $sw_rows[] = $row;
                        }
                        $lab_names = [];
                        $labs_sw->data_seek(0);
                        while ($lsw = $labs_sw->fetch_assoc()) {
                            $lab_names[] = $lsw['lab_name'];
                        }
                    ?>

                    <!-- Lab Filter Tabs -->
                    <div class="sw-lab-filters mb-3">
                        <button class="sw-lab-tab active" onclick="filterSwLab('all', this)">All Labs</button>
                        <?php foreach ($lab_names as $ln): ?>
                            <button class="sw-lab-tab" data-labval="<?= htmlspecialchars($ln) ?>" onclick="filterSwLab('<?= htmlspecialchars($ln) ?>', this)">
                                Lab <?= htmlspecialchars($ln) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Software Card Grid -->
                    <div class="sw-card-grid" id="swCardGrid">
                        <?php if (!empty($sw_rows)): ?>
                            <?php foreach ($sw_rows as $sw): ?>
                                <div class="sw-card-item" data-lab="<?= htmlspecialchars($sw['lab_name']) ?>">
                                    <div class="sw-card-top">
                                        <div class="sw-card-name"><?= htmlspecialchars($sw['software_name']) ?></div>
                                        <?php if ($sw['is_available']): ?>
                                            <span class="sw-badge-available"><i class="bi bi-check-square-fill me-1"></i>Available</span>
                                        <?php else: ?>
                                            <span class="sw-badge-unavailable"><i class="bi bi-x-square-fill me-1"></i>Not Available</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="sw-card-version"><?= htmlspecialchars($sw['version'] ?? '—') ?></div>
                                    <span class="sw-card-lab-tag">Lab <?= htmlspecialchars($sw['lab_name']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Empty state message -->
                    <p id="swEmptyMsg" style="display:none;" class="text-muted text-center py-4">
                        <i class="bi bi-inbox me-2"></i>No software added for this lab yet.
                    </p>

                    <?php else: ?>
                        <p class="text-muted text-center py-4"><i class="bi bi-inbox me-2"></i>No labs configured yet.</p>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Fallback: show PC availability per lab as cards -->
                    <p class="text-muted mb-3" style="font-size:0.83rem;"><i class="bi bi-info-circle me-1"></i>Software list not yet configured. Showing current PC availability per lab.</p>
                    <?php
                    $labs_avail = $conn->query("
                        SELECT lc.lab_name,
                            SUM(CASE WHEN ps.status='available' THEN 1 ELSE 0 END) as available_count,
                            SUM(CASE WHEN ps.status='in_use' THEN 1 ELSE 0 END) as in_use_count,
                            SUM(CASE WHEN ps.status='maintenance' THEN 1 ELSE 0 END) as maintenance_count,
                            lc.total_pcs
                        FROM lab_config lc
                        LEFT JOIN pc_status ps ON lc.lab_name=ps.lab_name
                        GROUP BY lc.lab_name
                        ORDER BY lc.lab_name
                    ");
                    if ($labs_avail && $labs_avail->num_rows > 0): ?>
                    <div class="sw-card-grid">
                        <?php while ($la = $labs_avail->fetch_assoc()): ?>
                            <div class="sw-card-item" data-lab="<?= htmlspecialchars($la['lab_name']) ?>">
                                <div class="sw-card-top">
                                    <div class="sw-card-name">Lab <?= htmlspecialchars($la['lab_name']) ?></div>
                                    <span class="sw-badge-available"><?= intval($la['available_count']) ?> PCs</span>
                                </div>
                                <div class="sw-card-version">In Use: <?= intval($la['in_use_count']) ?> &nbsp;|&nbsp; Maintenance: <?= intval($la['maintenance_count']) ?></div>
                                <span class="sw-card-lab-tag">Total: <?= intval($la['total_pcs']) ?> PCs</span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function filterSwLab(lab, btn) {
    // Update active tab
    document.querySelectorAll('.sw-lab-tab').forEach(function(t) {
        t.classList.remove('active');
    });
    btn.classList.add('active');

    // Show/hide cards
    var cards = document.querySelectorAll('#swCardGrid .sw-card-item');
    var visibleCount = 0;

    cards.forEach(function(card) {
        var cardLab = card.getAttribute('data-lab').trim();
        var labStr  = String(lab).trim();
        var show    = (labStr === 'all' || cardLab === labStr);
        card.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });

    // Show/hide empty message
    var emptyMsg = document.getElementById('swEmptyMsg');
    if (emptyMsg) {
        emptyMsg.style.display = (visibleCount === 0) ? '' : 'none';
    }
}
</script>

<!-- Reservation Modal -->
<div class="modal fade" id="reservationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header-purple d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-calendar-check-fill me-2"></i>Reservations</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <?php if (!$reservations_enabled): ?>
                    <div class="res-disabled-banner mb-3">
                        <i class="bi bi-lock-fill me-2"></i>
                        Reservations are currently <strong>disabled</strong> by the administrator. Please check back later.
                    </div>
                <?php else: ?>
                    <?php if (isset($res_error)): ?><div class="alert alert-danger py-2 px-3 mb-3" style="border-radius:8px;font-size:0.83rem;"><i class="bi bi-exclamation-circle-fill me-2"></i><?= htmlspecialchars($res_error) ?></div><?php endif; ?>
                    <?php if (isset($res_success)): ?><div class="alert alert-success py-2 px-3 mb-3" style="border-radius:8px;font-size:0.83rem;"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($res_success) ?></div><?php endif; ?>

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
                                        <?php
                                        $lab_list = $conn->query("SELECT lab_name FROM lab_config ORDER BY lab_name");
                                        while ($lab = $lab_list->fetch_assoc()):
                                        ?>
                                            <option value="<?= htmlspecialchars($lab['lab_name']) ?>">Lab <?= htmlspecialchars($lab['lab_name']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="field-label">Preferred Time Slot</label>
                                    <div class="d-flex gap-2 flex-wrap mt-1">
                                        <?php foreach ($time_slots as $slot): ?>
                                            <?php $sid = preg_replace('/[^a-zA-Z0-9]/', '_', $slot); ?>
                                            <div class="time-slot-option">
                                                <input type="radio" name="res_time_slot" value="<?= htmlspecialchars($slot) ?>" id="slot_<?= $sid ?>" required>
                                                <label for="slot_<?= $sid ?>"><?= htmlspecialchars($slot) ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="field-label">Date</label>
                                    <input type="date" name="res_date" id="res_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="field-label">Select PC</label>
                                    <div id="selectedPcDisplay" onclick="openPCSelectionModal()"
                                         style="cursor:pointer;background:linear-gradient(135deg,#f3e9fd,#e8f0fe);border:2px solid var(--ccs-purple);border-radius:10px;padding:10px 14px;font-size:0.85rem;font-weight:700;color:var(--ccs-purple);display:flex;align-items:center;gap:10px;">
                                        <i class="bi bi-pc-display-horizontal" style="font-size:1.1rem;"></i>
                                        <span id="selectedPcText">Click to select a PC</span>
                                    </div>
                                    <input type="hidden" name="res_pc" id="res_pc_input" value="">
                                    <div id="pcRequiredNote" style="font-size:0.72rem;color:#e74c3c;margin-top:3px;display:none;"><i class="bi bi-exclamation-circle me-1"></i>Please select a PC.</div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" name="submit_reservation" class="btn btn-purple px-4">
                                    <i class="bi bi-calendar-plus me-2"></i>Submit Reservation
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <div style="font-size:0.67rem;color:#aaa;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:7px;">
                    <i class="bi bi-list-ul me-1"></i>My Reservations
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size:0.8rem;">
                        <thead><tr><th>Date</th><th>Purpose</th><th>Lab</th><th>Time Slot</th><th>PC #</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php if ($res_table_exists && $reservations && $reservations->num_rows > 0): ?>
                            <?php while ($r = $reservations->fetch_assoc()):
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
                                <td><?= !empty($r['seat_number']) ? '<strong style="color:var(--ccs-purple);">#'.intval($r['seat_number']).'</strong>' : '—' ?></td>
                                <td><span class="<?= $rbadge ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                                <td>
                                    <?php if (strtolower($r['status']) === 'pending'): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this reservation?')">
                                            <input type="hidden" name="res_id" value="<?= intval($r['id']) ?>">
                                            <button name="cancel_reservation" class="btn btn-sm btn-danger" style="font-size:0.7rem;padding:2px 8px;border-radius:6px;"><i class="bi bi-x-circle me-1"></i>Cancel</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size:0.73rem;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-3"><i class="bi bi-calendar-x me-2"></i>No reservations yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="pcSelectionModal" tabindex="-1" aria-hidden="true" style="z-index:1065;">
    <div class="modal-dialog modal-dialog-centered" style="max-width:820px;">
        <div class="modal-content" style="border-radius:20px;overflow:hidden;">
            <div class="modal-header-purple d-flex justify-content-between align-items-center">
                <div>
                    <div style="font-weight:800;font-size:1rem;"><i class="bi bi-pc-display me-2" style="color:var(--blue-light);"></i>Select Your PC — Lab <span id="pcModalLabLabel">—</span></div>
                    <div style="font-size:0.72rem;opacity:.85;margin-top:2px;">Click on any available (green) PC</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;padding:9px 18px;background:#f8f9fa;border-bottom:1px solid #eee;font-size:0.75rem;color:#555;">
                <div><span class="legend-dot" style="background:#27ae60;"></span>Available</div>
                <div><span class="legend-dot" style="background:#e74c3c;"></span>Booked/In Use</div>
                <div><span class="legend-dot" style="background:#f39c12;"></span>Maintenance</div>
                <div><span class="legend-dot" style="background:var(--blue-mid);"></span>Your Selection</div>
                <div class="ms-auto">Available: <strong id="availablePcCount">—</strong></div>
            </div>
            <div style="padding:16px 18px 10px;background:#fff;">
                <div style="background:linear-gradient(90deg,var(--blue-deeper),var(--blue-dark));color:var(--blue-light);text-align:center;padding:7px 18px;border-radius:8px;font-size:0.68rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase;margin-bottom:14px;"><i class="bi bi-easel me-2"></i>FRONT — COMPUTER LAB</div>
                <div id="pcSelectionGrid" style="display:grid;grid-template-columns:repeat(10,1fr);gap:7px;"></div>
            </div>
            <div style="padding:11px 18px 14px;background:#fafafa;border-top:1px solid #f0f0f0;display:flex;justify-content:flex-end;gap:10px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><i class="bi bi-arrow-left me-1"></i>Cancel</button>
                <button type="button" class="btn btn-purple px-4" id="confirmPcBtn" disabled onclick="confirmPCSelection()"><i class="bi bi-check2-circle me-2"></i>Confirm Selection</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    if ($('#sessionsSummaryTable').length) {
        $('#sessionsSummaryTable').DataTable({
            pageLength: 5,
            order: [[0,'desc']],
            language: { emptyTable: 'No sessions yet.' }
        });
    }
});

(function() {
    const section = document.getElementById('sitinSummary');
    if (!section) return;
    section.style.opacity = '0';
    section.style.transform = 'translateY(28px)';
    section.style.transition = 'opacity 0.55s ease, transform 0.55s ease';

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                section.style.opacity = '1';
                section.style.transform = 'translateY(0)';
                observer.unobserve(section);
            }
        });
    }, { threshold: 0.1 });

    observer.observe(section);
})();

const pcStatusData = <?= json_encode($pc_data ?? []) ?>;
const occupiedPCs = <?= json_encode($occupied_pcs ?? []) ?>;
let currentSelectedPC = null;
let pcSelectionModal  = null;

function openPCSelectionModal() {
    const lab      = document.getElementById('res_lab').value;
    const date     = document.getElementById('res_date').value;
    const slotEl   = document.querySelector('input[name="res_time_slot"]:checked');

    if (!lab)    { Swal.fire({ icon:'warning', title:'Select Lab First', confirmButtonColor:'#3B82F6' }); return; }
    if (!date)   { Swal.fire({ icon:'warning', title:'Select Date First', confirmButtonColor:'#3B82F6' }); return; }
    if (!slotEl) { Swal.fire({ icon:'warning', title:'Select Time Slot First', confirmButtonColor:'#3B82F6' }); return; }

    document.getElementById('pcModalLabLabel').textContent = lab;
    buildPCGrid(lab, date, slotEl.value);
    if (!pcSelectionModal) pcSelectionModal = new bootstrap.Modal(document.getElementById('pcSelectionModal'));
    pcSelectionModal.show();
}

function buildPCGrid(lab, date, timeSlot) {
    const grid   = document.getElementById('pcSelectionGrid');
    const labPCs = pcStatusData[lab] || {};
    const booked = (occupiedPCs[lab] && occupiedPCs[lab][date] && occupiedPCs[lab][date][timeSlot]) ? occupiedPCs[lab][date][timeSlot] : [];
    grid.innerHTML = '';
    let avail = 0;

    for (let i = 1; i <= 50; i++) {
        const pcStatus = labPCs[i] || 'available';
        const isBooked = booked.includes(i);
        const isSel    = currentSelectedPC === i;
        let cls = '', selectable = false;

        if (isSel) { cls = 'selected'; selectable = true; }
        else if (isBooked || pcStatus === 'in_use' || pcStatus === 'not_available') { cls = 'occupied'; }
        else if (pcStatus === 'maintenance') { cls = 'maintenance'; }
        else { cls = 'available'; selectable = true; avail++; }

        const el = document.createElement('div');
        el.className = 'pc-card-sel ' + cls;
        el.innerHTML = `<i class="bi bi-pc-display-horizontal"></i><span>${i}</span>`;
        el.title = `PC #${i}`;
        if (selectable && !isSel) el.onclick = () => selectPC(i);
        grid.appendChild(el);
    }
    document.getElementById('availablePcCount').textContent = avail;
}

function selectPC(n) {
    currentSelectedPC = n;
    const lab  = document.getElementById('res_lab').value;
    const date = document.getElementById('res_date').value;
    const slot = document.querySelector('input[name="res_time_slot"]:checked').value;
    buildPCGrid(lab, date, slot);
    document.getElementById('confirmPcBtn').disabled = false;
    Swal.fire({ title:'PC #'+n+' Selected', text:'Click Confirm to proceed.', icon:'success', timer:1200, showConfirmButton:false });
}

function confirmPCSelection() {
    if (!currentSelectedPC) return;
    document.getElementById('res_pc_input').value = currentSelectedPC;
    document.getElementById('selectedPcText').innerHTML = '<strong>PC #' + currentSelectedPC + ' Selected</strong>';
    document.getElementById('selectedPcDisplay').style.borderColor = '#27ae60';
    document.getElementById('pcRequiredNote').style.display = 'none';
    pcSelectionModal.hide();
    Swal.fire({ title:'PC Selected!', text:`PC #${currentSelectedPC} confirmed.`, icon:'success', confirmButtonColor:'#3B82F6' });
}

function resetPCSelection() {
    currentSelectedPC = null;
    document.getElementById('res_pc_input').value = '';
    document.getElementById('selectedPcText').innerHTML = 'Click to select a PC';
    document.getElementById('selectedPcDisplay').style.borderColor = '';
    if (document.getElementById('confirmPcBtn')) document.getElementById('confirmPcBtn').disabled = true;
}

['res_lab','res_date'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', resetPCSelection);
});
document.querySelectorAll('input[name="res_time_slot"]').forEach(r => r.addEventListener('change', resetPCSelection));

const resForm = document.getElementById('reservationForm');
if (resForm) resForm.addEventListener('submit', function(e) {
    const pc = document.getElementById('res_pc_input').value;
    if (!pc) {
        e.preventDefault();
        document.getElementById('pcRequiredNote').style.display = 'block';
        Swal.fire({ icon:'warning', title:'PC Not Selected', text:'Please select a PC for your reservation.', confirmButtonColor:'#3B82F6' });
    }
});

document.getElementById('profile_pic_input')?.addEventListener('change', function() {
    const [file] = this.files;
    if (file) {
        const url = URL.createObjectURL(file);
        document.getElementById('editAvatarPreview').src = url;
        document.getElementById('avatarPreview').src = url;
    }
});

<?php if (isset($res_error) || isset($res_success)): ?>
document.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('reservationModal')).show());
<?php endif; ?>

<?php if (isset($feedback_success) || isset($feedback_error)): ?>
document.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('historyModal')).show());
<?php endif; ?>

function confirmLogout(e) {
    e.preventDefault();
    Swal.fire({
        title:'Logging out?', text:'Are you sure?', icon:'warning',
        showCancelButton:true,
        confirmButtonText:'<i class="bi bi-box-arrow-right me-1"></i> Yes, Logout',
        cancelButtonText:'Cancel', confirmButtonColor:'#3B82F6', cancelButtonColor:'#6c757d'
    }).then(r => {
        if (r.isConfirmed) {
            Swal.fire({ title:'Logged out!', icon:'success', timer:1500, showConfirmButton:false })
                .then(() => { window.location.href = 'landingpage.php'; });
        }
    });
}

function openFeedbackForm(sitinId) {
    document.getElementById('feedback_sitin_id').value = sitinId;
    const hist = bootstrap.Modal.getInstance(document.getElementById('historyModal'));
    if (hist) hist.hide();
    setTimeout(() => new bootstrap.Modal(document.getElementById('feedbackModal')).show(), 400);
}
</script>
</body>
</html>