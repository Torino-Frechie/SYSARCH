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

// EDIT reservation
if (isset($_POST['edit_reservation'])) {
    $id      = intval($_POST['id']);
    $purpose = $conn->real_escape_string(trim($_POST['purpose']));
    $lab     = $conn->real_escape_string(trim($_POST['lab']));
    $time    = $conn->real_escape_string(trim($_POST['preferred_time']));
    $date    = $_POST['reservation_date'];
    $stmt = $conn->prepare("UPDATE reservations 
        SET purpose=?, lab=?, preferred_time=?, reservation_date=? 
        WHERE id=?");
    $stmt->bind_param("ssssi", $purpose, $lab, $time, $date, $id);
    $stmt->execute();
    header("Location: admin_dashboard.php?tab=reservation");
    exit();
}

// DELETE reservation
if (isset($_POST['delete_reservation'])) {
    $id = intval($_POST['id']);
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
    
    // If approving, check if PC is still available
    if ($status === 'approved') {
        $res = $conn->query("SELECT lab, seat_number FROM reservations WHERE id = $res_id")->fetch_assoc();
        if ($res && $res['seat_number']) {
            $check = $conn->query("SELECT status FROM pc_status WHERE lab_name = '{$res['lab']}' AND pc_number = {$res['seat_number']}");
            $pc = $check->fetch_assoc();
            if ($pc && $pc['status'] !== 'available') {
                header("Location: admin_dashboard.php?tab=reservation&error=PC not available for approval");
                exit();
            }
            // Mark PC as reserved/maintenance temporarily
            $conn->query("UPDATE pc_status SET status = 'maintenance', notes = 'Reserved for {$res['lab']}' WHERE lab_name = '{$res['lab']}' AND pc_number = {$res['seat_number']}");
        }
    }
    
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

// ── LAB CONFIGURATION HANDLERS ──────────────────────────────────────

// Update lab configuration (total PCs)
if (isset($_POST['update_lab_config'])) {
    $lab_name = $conn->real_escape_string($_POST['lab_name']);
    $total_pcs = intval($_POST['total_pcs']);
    
    // Update lab config
    $conn->query("INSERT INTO lab_config (lab_name, total_pcs) VALUES ('$lab_name', $total_pcs)
                  ON DUPLICATE KEY UPDATE total_pcs = $total_pcs");
    
    // Add new PCs if needed
    for ($i = 1; $i <= $total_pcs; $i++) {
        $conn->query("INSERT IGNORE INTO pc_status (lab_name, pc_number, status) VALUES ('$lab_name', $i, 'available')");
    }
    
    header("Location: admin_dashboard.php?tab=lab_config&lab=" . urlencode($lab_name));
    exit();
}

// Update individual PC status
if (isset($_POST['update_pc_status'])) {
    $lab_name = $conn->real_escape_string($_POST['lab_name']);
    $pc_number = intval($_POST['pc_number']);
    $status = $conn->real_escape_string($_POST['status']);
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');
    
    $conn->query("UPDATE pc_status SET status = '$status', notes = '$notes', updated_by = 'Admin' 
                  WHERE lab_name = '$lab_name' AND pc_number = $pc_number");
    
    header("Location: admin_dashboard.php?tab=lab_config&lab=" . urlencode($lab_name));
    exit();
}

// Batch update PC statuses
if (isset($_POST['batch_update_pcs'])) {
    $lab_name = $conn->real_escape_string($_POST['lab_name']);
    $status = $conn->real_escape_string($_POST['batch_status']);
    $pc_range = $_POST['pc_range'];
    
    if (strpos($pc_range, '-') !== false) {
        list($start, $end) = explode('-', $pc_range);
        for ($i = intval($start); $i <= intval($end); $i++) {
            $conn->query("UPDATE pc_status SET status = '$status' WHERE lab_name = '$lab_name' AND pc_number = $i");
        }
    } elseif (strpos($pc_range, ',') !== false) {
        $pcs = explode(',', $pc_range);
        foreach ($pcs as $pc) {
            $pc = intval(trim($pc));
            $conn->query("UPDATE pc_status SET status = '$status' WHERE lab_name = '$lab_name' AND pc_number = $pc");
        }
    }
    
    header("Location: admin_dashboard.php?tab=lab_config&lab=" . urlencode($lab_name));
    exit();
}

// Bulk status update
if (isset($_POST['bulk_status_update'])) {
    $lab_name = $conn->real_escape_string($_POST['lab_name']);
    $status = $conn->real_escape_string($_POST['bulk_status_all']);
    
    $conn->query("UPDATE pc_status SET status = '$status' WHERE lab_name = '$lab_name'");
    
    header("Location: admin_dashboard.php?tab=lab_config&lab=" . urlencode($lab_name));
    exit();
}

// Sit-in a student
if (isset($_POST['do_sitin'])) {
    $sid     = $conn->real_escape_string(trim($_POST['sitin_id']));
    $purpose = $conn->real_escape_string(trim($_POST['sitin_purpose']));
    $lab     = $conn->real_escape_string(trim($_POST['sitin_lab']));
    $pc_num  = isset($_POST['pc_number']) ? intval($_POST['pc_number']) : null;
    $sitin_error = '';
    
    $chk = $conn->query("SELECT id, remaining_session FROM users WHERE id_number = '$sid'")->fetch_assoc();
    if (!$chk) {
        $sitin_error = "Student not found.";
    } elseif ($chk['remaining_session'] <= 0) {
        $sitin_error = "Student has no remaining sessions left.";
    } else {
        $active = $conn->query("SELECT id FROM sitin_records WHERE id_number = '$sid' AND logout_time IS NULL")->fetch_assoc();
        if ($active) {
            $sitin_error = "Student already has an active sit-in session.";
        } else {
            if ($pc_num) {
                // Check if PC is available
                $pc_check = $conn->query("SELECT status FROM pc_status WHERE lab_name = '$lab' AND pc_number = $pc_num")->fetch_assoc();
                if (!$pc_check || $pc_check['status'] !== 'available') {
                    $sitin_error = "Selected PC is not available.";
                    header("Location: admin_dashboard.php?tab=sitin&error=" . urlencode($sitin_error));
                    exit();
                }
                // Mark PC as in-use
                $conn->query("UPDATE pc_status SET status = 'in_use', notes = 'Used by $sid' WHERE lab_name = '$lab' AND pc_number = $pc_num");
            }
            
            $conn->query("INSERT INTO sitin_records (id_number, purpose, lab, pc_number, login_time) 
                          VALUES ('$sid', '$purpose', '$lab', " . ($pc_num ? $pc_num : "NULL") . ", NOW())");
            header("Location: admin_dashboard.php?tab=sitin");
            exit();
        }
    }
    if ($sitin_error) {
        header("Location: admin_dashboard.php?tab=sitin&error=" . urlencode($sitin_error));
        exit();
    }
}

// Logout a sit-in session
if (isset($_POST['logout_session'])) {
    $sit_id = intval($_POST['sit_id']);
    $rec = $conn->query("SELECT id_number, lab, pc_number FROM sitin_records WHERE id = $sit_id")->fetch_assoc();
    if ($rec) {
        $student_id = $conn->real_escape_string($rec['id_number']);
        $lab = $rec['lab'];
        $pc_num = $rec['pc_number'];
        
        $conn->query("UPDATE sitin_records SET logout_time = NOW() WHERE id = $sit_id");
        $conn->query("UPDATE users SET remaining_session = GREATEST(0, remaining_session - 1) WHERE id_number = '$student_id'");
        
        // Free up the PC
        if ($pc_num) {
            $conn->query("UPDATE pc_status SET status = 'available', notes = '' WHERE lab_name = '$lab' AND pc_number = $pc_num");
        }
    }
    header("Location: admin_dashboard.php?tab=sitin");
    exit();
}

// Reset all active sessions
if (isset($_POST['reset_all_sessions'])) {
    $active_students = $conn->query("SELECT DISTINCT id_number, lab, pc_number FROM sitin_records WHERE logout_time IS NULL");
    $conn->query("UPDATE sitin_records SET logout_time = NOW() WHERE logout_time IS NULL");
    if ($active_students && $active_students->num_rows > 0) {
        while ($s = $active_students->fetch_assoc()) {
            $sid = $conn->real_escape_string($s['id_number']);
            $conn->query("UPDATE users SET remaining_session = GREATEST(0, remaining_session - 1) WHERE id_number = '$sid'");
            if ($s['pc_number']) {
                $conn->query("UPDATE pc_status SET status = 'available' WHERE lab_name = '{$s['lab']}' AND pc_number = {$s['pc_number']}");
            }
        }
    }
    header("Location: admin_dashboard.php?tab=sitin");
    exit();
}

// Get PC status for a lab (AJAX)
if (isset($_GET['get_pc_status'])) {
    $lab = $conn->real_escape_string($_GET['lab']);
    $result = $conn->query("SELECT pc_number, status, notes FROM pc_status WHERE lab_name = '$lab' ORDER BY pc_number");
    $pcs = [];
    while ($row = $result->fetch_assoc()) {
        $pcs[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($pcs);
    exit();
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
$selected_lab = $_GET['lab'] ?? '524';
$error_msg = $_GET['error'] ?? '';
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

        /* ─── TOP NAVBAR ─── */
        .top-navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 60px;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            background: linear-gradient(160deg, var(--ccs-purple) 0%, #6a3fa0 50%, #2e6da4 100%);
            box-shadow: 0 4px 20px rgba(151,87,214,0.25);
        }

        .nav-left .brand-title {
            color: white;
            font-weight: 700;
            font-size: 0.95rem;
        }

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

        .nav-links a:hover {
            background: rgba(255,255,255,0.12);
            color: white;
        }

        .nav-links a.active {
            background: rgba(255,255,255,0.18);
            color: white;
            font-weight: 600;
        }

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

        /* ── Main content ── */
        .main-content {
            margin-top: 60px;
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

        /* ── Content area ── */
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

        /* PC Status Grid */
        .pc-grid {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 8px;
            padding: 15px;
        }
        .pc-card {
            text-align: center;
            padding: 12px 5px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .pc-card.available {
            background: linear-gradient(135deg, #d5f5e3, #a9dfbf);
            color: #1a6835;
            border: 2px solid #82c99a;
        }
        .pc-card.maintenance {
            background: linear-gradient(135deg, #fdebd0, #f8c471);
            color: #784212;
            border: 2px solid #f39c12;
        }
        .pc-card.not_available {
            background: linear-gradient(135deg, #fadbd8, #f1948a);
            color: #7b241c;
            border: 2px solid #e57373;
            opacity: 0.7;
        }
        .pc-card.in_use {
            background: linear-gradient(135deg, #d4e6f1, #a9cce3);
            color: #1a5276;
            border: 2px solid #3498db;
        }
        .pc-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
        .badge-pending {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
        }
        .badge-approved {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
        }
        .badge-rejected {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
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
            padding: 24px;
            text-align: center;
            color: #aaa;
            font-size: 0.78rem;
            font-family: 'Poppins', sans-serif;
        }
        
        /* Lab selector */
        .lab-selector {
            background: white;
            border-radius: 12px;
            padding: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .lab-btn {
            padding: 8px 20px;
            border-radius: 8px;
            background: #f0f0f0;
            color: #666;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }
        .lab-btn.active {
            background: linear-gradient(135deg, var(--ccs-purple), #7c45b8);
            color: white;
        }
        .lab-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(151,87,214,0.2);
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
        <a href="admin_dashboard.php?tab=dashboard" class="<?= $active_tab==='dashboard'?'active':'' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="admin_dashboard.php?tab=students" class="<?= $active_tab==='students'?'active':'' ?>">
            <i class="bi bi-people-fill"></i> Students
        </a>
        <a href="admin_dashboard.php?tab=sitinform" class="<?= $active_tab==='sitinform'?'active':'' ?>">
            <i class="bi bi-box-arrow-in-right"></i> Sit-in
        </a>
        <a href="admin_dashboard.php?tab=sitin" class="<?= $active_tab==='sitin'?'active':'' ?>">
            <i class="bi bi-display"></i> Current
        </a>
        <a href="admin_dashboard.php?tab=lab_config" class="<?= $active_tab==='lab_config'?'active':'' ?>">
            <i class="bi bi-pc-display"></i> Lab Config
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
                    'lab_config'    => '<i class="bi bi-pc-display me-2"></i>Laboratory Configuration',
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
        
        <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-3" style="border-radius:12px;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>


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


    <!-- ══════════════ LAB CONFIGURATION TAB ══════════════ -->
    <?php elseif ($active_tab === 'lab_config'): ?>
    
    <!-- Get all labs -->
    <?php
    $labs = $conn->query("SELECT * FROM lab_config ORDER BY lab_name");
    $current_lab = $selected_lab;
    
    // Get PC status for current lab
    $pcs = $conn->query("SELECT * FROM pc_status WHERE lab_name = '$current_lab' ORDER BY pc_number");
    $available_count = $conn->query("SELECT COUNT(*) as cnt FROM pc_status WHERE lab_name = '$current_lab' AND status = 'available'")->fetch_assoc()['cnt'] ?? 0;
    $maintenance_count = $conn->query("SELECT COUNT(*) as cnt FROM pc_status WHERE lab_name = '$current_lab' AND status = 'maintenance'")->fetch_assoc()['cnt'] ?? 0;
    $not_available_count = $conn->query("SELECT COUNT(*) as cnt FROM pc_status WHERE lab_name = '$current_lab' AND status = 'not_available'")->fetch_assoc()['cnt'] ?? 0;
    $in_use_count = $conn->query("SELECT COUNT(*) as cnt FROM pc_status WHERE lab_name = '$current_lab' AND status = 'in_use'")->fetch_assoc()['cnt'] ?? 0;
    
    // Calculate total not available for display
    $total_not_available = $maintenance_count + $not_available_count + $in_use_count;
    $total_pcs = $conn->query("SELECT total_pcs FROM lab_config WHERE lab_name = '$current_lab'")->fetch_assoc()['total_pcs'] ?? 50;
    ?>
    
    <!-- Lab Selector -->
    <div class="lab-selector">
        <?php 
        $labs->data_seek(0);
        while ($lab = $labs->fetch_assoc()): 
        ?>
            <a href="admin_dashboard.php?tab=lab_config&lab=<?= urlencode($lab['lab_name']) ?>" 
               class="lab-btn <?= $current_lab === $lab['lab_name'] ? 'active' : '' ?>">
                <i class="bi bi-pc-display"></i> Lab <?= htmlspecialchars($lab['lab_name']) ?>
            </a>
        <?php endwhile; ?>
    </div>
    
    <!-- Lab Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: #27ae60;"><i class="bi bi-check-circle-fill"></i></div>
                <div>
                    <div class="stat-num"><?= $available_count ?></div>
                    <div class="stat-label">Available</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: #f39c12;"><i class="bi bi-tools"></i></div>
                <div>
                    <div class="stat-num"><?= $maintenance_count ?></div>
                    <div class="stat-label">Maintenance</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: #3498db;"><i class="bi bi-person-workspace"></i></div>
                <div>
                    <div class="stat-num"><?= $in_use_count ?></div>
                    <div class="stat-label">In Use</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: #e74c3c;"><i class="bi bi-x-lg"></i></div>
                <div>
                    <div class="stat-num"><?= $not_available_count ?></div>
                    <div class="stat-label">Not Available</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bulk Actions -->
    <div class="dash-card mb-4">
        <div class="card-header-purple">
            <span><i class="bi bi-lightning-charge-fill me-2"></i>Bulk Actions - Lab <?= htmlspecialchars($current_lab) ?></span>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="lab_name" value="<?= $current_lab ?>">
                        <select name="bulk_status_all" class="form-select" required>
                            <option value="">Select Status</option>
                            <option value="available">Set All Available</option>
                            <option value="maintenance">Set All Maintenance</option>
                            <option value="not_available">Set All Not Available</option>
                        </select>
                        <button type="submit" name="bulk_status_update" class="btn btn-purple">Apply to All</button>
                    </form>
                </div>
                <div class="col-md-4">
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="lab_name" value="<?= $current_lab ?>">
                        <input type="text" name="pc_range" class="form-control" placeholder="e.g., 1-10 or 1,3,5,7" required>
                        <select name="batch_status" class="form-select" required>
                            <option value="">Set Status</option>
                            <option value="available">Available</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="not_available">Not Available</option>
                        </select>
                        <button type="submit" name="batch_update_pcs" class="btn btn-purple">Apply to Range</button>
                    </form>
                </div>
                <div class="col-md-4">
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="lab_name" value="<?= $current_lab ?>">
                        <input type="number" name="total_pcs" class="form-control" placeholder="Total PCs" 
                               value="<?= $total_pcs ?>" required>
                        <button type="submit" name="update_lab_config" class="btn btn-purple">Update Total</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- PC Status Grid -->
    <div class="dash-card">
        <div class="card-header-purple">
            <span><i class="bi bi-grid-3x3-gap-fill me-2"></i>PC Status Map - Lab <?= htmlspecialchars($current_lab) ?></span>
        </div>
        <div class="card-body p-3">
            <div class="pc-grid">
                <?php
                $total = $total_pcs;
                for ($i = 1; $i <= $total; $i++):
                    $pc_data = null;
                    if ($pcs) {
                        $pcs->data_seek(0);
                        while ($row = $pcs->fetch_assoc()) {
                            if ($row['pc_number'] == $i) {
                                $pc_data = $row;
                                break;
                            }
                        }
                    }
                    $status = $pc_data ? $pc_data['status'] : 'available';
                    $notes = $pc_data ? $pc_data['notes'] : '';
                    $statusClass = $status;
                    $statusText = ucfirst(str_replace('_', ' ', $status));
                ?>
                <div class="pc-card <?= $statusClass ?>" onclick="openPCEditModal('<?= $current_lab ?>', <?= $i ?>, '<?= $status ?>', '<?= htmlspecialchars($notes) ?>')">
                    <i class="bi bi-pc-display-horizontal d-block mb-1" style="font-size:1.2rem;"></i>
                    <span><?= $i ?></span>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
    
    <!-- Edit PC Modal -->
    <div class="modal fade" id="editPcModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-purple d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-pencil-fill me-2"></i>Edit PC Status</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="lab_name" id="edit_lab_name">
                        <input type="hidden" name="pc_number" id="edit_pc_number">
                        
                        <div class="mb-3 text-center">
                            <h4>PC #<span id="edit_pc_display"></span></h4>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label" style="font-size:0.78rem;color:#777;">Status</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="available">Available</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="not_available">Not Available</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label" style="font-size:0.78rem;color:#777;">Notes (Optional)</label>
                            <textarea name="notes" id="edit_notes" class="form-control" rows="2" placeholder="Reason for maintenance/not available status..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_pc_status" class="btn btn-purple px-4">
                            <i class="bi bi-save-fill me-1"></i> Update PC
                        </button>
                    </div>
                </form>
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
                        <div class="mb-3">
                            <label class="form-label" style="font-size:0.8rem;color:#777;">Lab</label>
                            <select name="sitin_lab" id="sitin_lab" class="form-select" required>
                                <option value="">-- Select Lab --</option>
                                <?php
                                $lab_list = $conn->query("SELECT lab_name FROM lab_config ORDER BY lab_name");
                                while ($lab = $lab_list->fetch_assoc()):
                                ?>
                                <option value="<?= $lab['lab_name'] ?>">Lab <?= $lab['lab_name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" style="font-size:0.8rem;color:#777;">Select PC</label>
                            <div id="pcSelectionArea">
                                <div class="alert alert-info" id="pcSelectHint">Please select a lab first</div>
                                <div id="pcGridSelection" style="display: none;">
                                    <div class="mb-2">
                                        <div class="d-flex gap-2 mb-3">
                                            <button type="button" class="btn btn-sm btn-outline-success" onclick="filterPCs('all')">All</button>
                                            <button type="button" class="btn btn-sm btn-outline-success" onclick="filterPCs('available')">Available Only</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearPCSelection()">Clear</button>
                                        </div>
                                        <div id="pcGridMini" style="display: grid; grid-template-columns: repeat(10, 1fr); gap: 6px; max-height: 300px; overflow-y: auto; padding: 10px; background: #f8f9fa; border-radius: 10px;"></div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="pc_number" id="selected_pc">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary btn-sm px-3" onclick="resetSitinForm()">
                                <i class="bi bi-x-circle me-1"></i> Cancel
                            </button>
                            <button type="submit" name="do_sitin" class="btn btn-purple px-4" id="submitSitinBtn" disabled>
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
                        <th>PC #</th>
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
                        <td><?= $r['pc_number'] ? '<span class="badge bg-primary">#' . $r['pc_number'] . '</span>' : '—' ?></td>
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
                <?php endwhile; else: ?>
                    <tr><td colspan="10" class="text-center text-muted py-3">No active sit-in sessions.</span></td></tr>
                <?php endif; ?>
                </tbody>
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
                        <th>PC #</th>
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
                        <td><?= $r['pc_number'] ? '#' . $r['pc_number'] : '—' ?></td>
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
                <?php endwhile; else: ?>
                    <tr><td colspan="9" class="text-center text-muted py-3">No sit-in records found.</span></td></tr>
                <?php endif; ?>
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


    <!-- ══════════════ REPORTS TAB ══════════════ -->
    <?php elseif ($active_tab === 'reports'): ?>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="dash-card">
                <div class="card-header-purple">
                    <span><i class="bi bi-pie-chart-fill me-2"></i>Sessions by Purpose</span>
                </div>
                <div class="card-body p-3">
                    <div class="chart-wrapper">
                        <canvas id="reportPurposeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="dash-card">
                <div class="card-header-purple">
                    <span><i class="bi bi-bar-chart-fill me-2"></i>Sessions by Lab</span>
                </div>
                <div class="card-body p-3">
                    <div class="chart-wrapper">
                        <canvas id="reportLabChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    $lab_result = $conn->query("SELECT lab, COUNT(*) as cnt FROM sitin_records GROUP BY lab");
    $lab_labels = []; $lab_data = [];
    if ($lab_result) while ($row = $lab_result->fetch_assoc()) {
        $lab_labels[] = $row['lab'];
        $lab_data[]   = $row['cnt'];
    }
    ?>
    <script>
    new Chart(document.getElementById('reportPurposeChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($lang_labels ?: ['No Data']) ?>,
            datasets: [{
                data: <?= json_encode($lang_data ?: [1]) ?>,
                backgroundColor: ['#9757d6','#a1cbf7','#27ae60','#f39c12','#e74c3c','#3498db','#FFD700'],
                borderWidth: 2, borderColor: '#fff'
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { font:{ family:'Poppins', size:11 }, boxWidth:14 } } }
        }
    });
    new Chart(document.getElementById('reportLabChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($lab_labels ?: ['No Data']) ?>,
            datasets: [{
                label: 'Sessions',
                data: <?= json_encode($lab_data ?: [0]) ?>,
                backgroundColor: '#9757d6',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
    </script>


  <!-- ══════════════ FEEDBACK TAB ══════════════ -->
<?php elseif ($active_tab === 'feedback'): ?>

<div class="dash-card">
    <div class="card-header-purple">
        <span><i class="bi bi-chat-left-text-fill me-2"></i>Student Feedback</span>
    </div>
    <div class="card-body p-3">
        <?php
        $feedback_check = $conn->query("SHOW TABLES LIKE 'feedback'");
        if ($feedback_check && $feedback_check->num_rows > 0):
            $fb = $conn->query("
                SELECT f.*, 
                    CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS student_name,
                    sr.purpose, sr.lab, sr.login_time
                FROM feedback f
                LEFT JOIN users u ON f.id_number = u.id_number
                LEFT JOIN sitin_records sr ON f.sitin_id = sr.id
                ORDER BY f.created_at DESC
            ");
            if ($fb && $fb->num_rows > 0):
        ?>
            <table id="feedbackTable" class="table table-bordered table-hover w-100">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>ID Number</th>
                        <th>Lab</th>
                        <th>Purpose</th>
                        <th>Session Date</th>
                        <th>Feedback</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($f = $fb->fetch_assoc()): ?>
                    <tr>
                        <td><?= $f['id'] ?></td>
                        <td><?= htmlspecialchars(trim($f['student_name']) ?: '—') ?></td>
                        <td><?= htmlspecialchars($f['id_number']) ?></td>
                        <td><?= htmlspecialchars($f['lab'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($f['purpose'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($f['login_time'] ?? '—') ?></td>
                        <td style="max-width:260px;">
                            <div style="font-size:0.83rem;color:#333;font-style:italic;">
                                "<?= htmlspecialchars($f['message']) ?>"
                            </div>
                          </td>
                        <td><?= htmlspecialchars($f['created_at']) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                <i class="bi bi-inbox me-2"></i>No feedback submitted yet.
            </p>
        <?php endif; else: ?>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                <i class="bi bi-exclamation-triangle me-2"></i>Feedback table not found. Please run the SQL setup.
            </p>
        <?php endif; ?>
    </div>
</div>


    <!-- ══════════════ RESERVATION TAB ══════════════ -->
    <?php elseif ($active_tab === 'reservation'): ?>

    <div class="dash-card">
        <div class="card-header-purple">
            <span><i class="bi bi-calendar-check-fill me-2"></i>Student Reservations</span>
        </div>
        <div class="card-body p-3">
            <table id="reservationTable" class="table table-bordered table-hover w-100">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student ID</th>
                        <th>Purpose</th>
                        <th>Lab</th>
                        <th>PC #</th>
                        <th>Time</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $res = $conn->query("SELECT * FROM reservations ORDER BY id DESC");
                if ($res && $res->num_rows > 0):
                    while ($r = $res->fetch_assoc()):
                ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= htmlspecialchars($r['id_number']) ?></td>
                        <td><?= htmlspecialchars($r['purpose']) ?></td>
                        <td><?= htmlspecialchars($r['lab']) ?></td>
                        <td><?= !empty($r['seat_number']) ? '#' . $r['seat_number'] : '—' ?></td>
                        <td><?= htmlspecialchars($r['preferred_time']) ?></td>
                        <td><?= htmlspecialchars($r['reservation_date']) ?></td>
                        <td>
                            <?php
                            $status = $r['status'];
                            if ($status === 'pending'):
                            ?><span class="badge-pending">Pending</span><?php
                            elseif ($status === 'approved'):
                            ?><span class="badge-approved">Approved</span><?php
                            elseif ($status === 'rejected'):
                            ?><span class="badge-rejected">Rejected</span><?php
                            else:
                            ?><span class="badge-done"><?= htmlspecialchars($status) ?></span><?php
                            endif;
                            ?>
                          </td>
                          <td>
                            <?php if ($r['status'] === 'pending'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="res_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="status" value="approved">
                                    <button name="update_reservation" class="btn btn-success btn-sm">
                                        <i class="bi bi-check-lg"></i> Approve
                                    </button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="res_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="status" value="rejected">
                                    <button name="update_reservation" class="btn btn-danger btn-sm">
                                        <i class="bi bi-x-lg"></i> Reject
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted" style="font-size:0.82rem;">—</span>
                            <?php endif; ?>
                            <button class="btn btn-warning btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#editResModal<?= $r['id'] ?>">
                                <i class="bi bi-pencil-fill"></i> Edit
                            </button>
                            <form method="POST" style="display:inline;"
                                onsubmit="return confirm('Delete this reservation?')">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button name="delete_reservation" class="btn btn-danger btn-sm">
                                    <i class="bi bi-trash-fill"></i> Delete
                                </button>
                            </form>
                          </td>
                    </tr>

                    <!-- Edit Reservation Modal -->
                    <div class="modal fade" id="editResModal<?= $r['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header-purple d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 fw-bold"><i class="bi bi-pencil-fill me-2"></i>Edit Reservation #<?= $r['id'] ?></h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <div class="mb-3">
                                            <label class="form-label" style="font-size:0.78rem;color:#777;">Purpose</label>
                                            <input type="text" name="purpose" value="<?= htmlspecialchars($r['purpose']) ?>" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" style="font-size:0.78rem;color:#777;">Lab</label>
                                            <input type="text" name="lab" value="<?= htmlspecialchars($r['lab']) ?>" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" style="font-size:0.78rem;color:#777;">Preferred Time</label>
                                            <input type="text" name="preferred_time" value="<?= htmlspecialchars($r['preferred_time']) ?>" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" style="font-size:0.78rem;color:#777;">Reservation Date</label>
                                            <input type="date" name="reservation_date" value="<?= htmlspecialchars($r['reservation_date']) ?>" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                        <button name="edit_reservation" class="btn btn-purple px-4">
                                            <i class="bi bi-save-fill me-1"></i> Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                <?php endwhile; else: ?>
                    <tr><td colspan="9" class="text-center text-muted py-3">No reservations found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php endif; ?>

    </div><!-- end content-area -->
</div><!-- end main-content -->

<footer>&copy; <?= date('Y') ?> College of Computer Studies | CCS Sit-in Monitoring System</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let currentPCFilter = 'all';
let currentPCMap = {};

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
    if ($('#studentTable').length)     $('#studentTable').DataTable({ pageLength:10, order:[[0,'asc']], language: dtLang });
    if ($('#sitinTable').length) {
        $.fn.dataTable.ext.errMode = 'none';
        $('#sitinTable').DataTable({
            pageLength: 10,
            order: [[0, 'desc']],
            language: dtLang,
            columnDefs: [
                { targets: -1, orderable: false, searchable: false }
            ]
        });
    }
    $.fn.dataTable.ext.errMode = 'none';
    if ($('#recordTable').length)      $('#recordTable').DataTable({ pageLength:10, order:[[0,'desc']], language: dtLang });
    if ($('#reservationTable').length) $('#reservationTable').DataTable({ pageLength:10, order:[[0,'desc']], language: dtLang });
    if ($('#feedbackTable').length)    $('#feedbackTable').DataTable({ pageLength:10, order:[[0,'desc']], language: dtLang });
});

function openPCEditModal(lab, pc, status, notes) {
    document.getElementById('edit_lab_name').value = lab;
    document.getElementById('edit_pc_number').value = pc;
    document.getElementById('edit_pc_display').textContent = pc;
    document.getElementById('edit_status').value = status;
    document.getElementById('edit_notes').value = notes;
    new bootstrap.Modal(document.getElementById('editPcModal')).show();
}

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
    document.getElementById('sitin_id').value = id;
    document.getElementById('display_id').innerText = id;
    document.getElementById('display_name').innerText = first + ' ' + last;
    document.getElementById('display_course').innerText = course;
    document.getElementById('display_level').innerText = 'Year ' + level;

    const rem = parseInt(remaining) || 0;
    const color = rem > 10 ? '#27ae60' : (rem > 5 ? '#f39c12' : '#e74c3c');
    const remEl = document.getElementById('display_remaining');
    remEl.innerText = rem;
    remEl.style.color = color;
    remEl.style.fontSize = '1rem';

    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('studentLookup').value = first + ' ' + last;
    document.getElementById('sitinForm').style.display = 'block';
    
    // Trigger lab selection check
    const labSelect = document.getElementById('sitin_lab');
    if (labSelect.value) {
        loadPCsForLab(labSelect.value);
    }
}

// Load PCs for selected lab
document.getElementById('sitin_lab').addEventListener('change', function() {
    const lab = this.value;
    if (lab) {
        loadPCsForLab(lab);
    } else {
        document.getElementById('pcGridSelection').style.display = 'none';
        document.getElementById('pcSelectHint').style.display = 'block';
    }
});

function loadPCsForLab(lab) {
    fetch('admin_dashboard.php?get_pc_status&lab=' + encodeURIComponent(lab))
        .then(r => r.json())
        .then(data => {
            currentPCMap = {};
            data.forEach(pc => {
                currentPCMap[pc.pc_number] = pc;
            });
            displayPCGrid();
            document.getElementById('pcSelectHint').style.display = 'none';
            document.getElementById('pcGridSelection').style.display = 'block';
        });
}

function displayPCGrid() {
    const grid = document.getElementById('pcGridMini');
    const lab = document.getElementById('sitin_lab').value;
    if (!lab) return;
    
    // Get total PCs for this lab
    fetch('admin_dashboard.php?get_pc_status&lab=' + encodeURIComponent(lab))
        .then(r => r.json())
        .then(data => {
            const totalPCs = data.length || 50;
            let html = '';
            for (let i = 1; i <= totalPCs; i++) {
                const pc = currentPCMap[i];
                const status = pc ? pc.status : 'available';
                const isAvailable = status === 'available';
                const statusClass = isAvailable ? 'available' : (status === 'maintenance' ? 'maintenance' : 'not_available');
                const selected = document.getElementById('selected_pc').value == i;
                
                if (currentPCFilter === 'available' && !isAvailable) continue;
                
                html += `
                    <div class="pc-card ${statusClass} ${selected ? 'selected' : ''}" 
                         style="cursor: ${isAvailable ? 'pointer' : 'not-allowed'}; padding: 8px 5px; text-align: center; border-radius: 8px;"
                         onclick="${isAvailable ? `selectPC(${i}, '${status}')` : ''}">
                        <i class="bi bi-pc-display-horizontal d-block mb-1" style="font-size: 1rem;"></i>
                        <span style="font-size: 0.7rem;">${i}</span>
                        ${!isAvailable ? `<div style="font-size: 0.6rem; color: #666;">${status === 'maintenance' ? 'Maintenance' : 'Not Available'}</div>` : ''}
                    </div>
                `;
            }
            grid.innerHTML = html;
        });
}

function selectPC(pcNumber, status) {
    if (status !== 'available') {
        Swal.fire('Not Available', 'This PC is currently ' + (status === 'maintenance' ? 'under maintenance' : 'not available'), 'warning');
        return;
    }
    document.getElementById('selected_pc').value = pcNumber;
    document.getElementById('submitSitinBtn').disabled = false;
    
    // Highlight selected PC
    document.querySelectorAll('#pcGridMini .pc-card').forEach(card => {
        card.classList.remove('selected');
    });
    Swal.fire('PC Selected', `PC #${pcNumber} has been selected for sit-in.`, 'success');
}

function filterPCs(filter) {
    currentPCFilter = filter;
    displayPCGrid();
}

function clearPCSelection() {
    document.getElementById('selected_pc').value = '';
    document.getElementById('submitSitinBtn').disabled = true;
    displayPCGrid();
    Swal.fire('Cleared', 'PC selection has been cleared.', 'info');
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
    if (!confirm('Log out ALL active sessions? This will deduct session credits for all active students.')) return;
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
    document.getElementById('selected_pc').value = '';
    document.getElementById('submitSitinBtn').disabled = true;
    document.getElementById('pcGridSelection').style.display = 'none';
    document.getElementById('pcSelectHint').style.display = 'block';
}
</script>
</body>
</html>