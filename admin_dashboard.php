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

// ── Ensure system_settings table exists ──────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
$conn->query("INSERT IGNORE INTO system_settings (setting_key, value) VALUES ('reservations_enabled', '1')");

// ── Ensure lab_software table exists ────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS lab_software (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lab_name VARCHAR(50) NOT NULL,
    software_name VARCHAR(150) NOT NULL,
    version VARCHAR(50),
    category VARCHAR(100),
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ── Handle POST actions ──────────────────────────────────────────────

// Approve / Reject reservation
if (isset($_POST['update_reservation'])) {

    $res_id = intval($_POST['res_id']);
    $status = strtolower(trim($_POST['status']));

    // Only allow approved or rejected
    if ($status !== 'approved' && $status !== 'rejected') {
        exit();
    }

    // If approving reservation
    if ($status === 'approved') {

        $res = $conn->query("
            SELECT lab, seat_number
            FROM reservations
            WHERE id = $res_id
        ")->fetch_assoc();

        if ($res && $res['seat_number']) {

            $lab  = $conn->real_escape_string($res['lab']);
            $seat = intval($res['seat_number']);

            // Check PC status
            $check = $conn->query("
                SELECT status
                FROM pc_status
                WHERE lab_name = '$lab'
                AND pc_number = $seat
            ");

            $pc = $check->fetch_assoc();

            if ($pc && $pc['status'] !== 'available') {

                header("Location: admin_dashboard.php?tab=reservation&error=pc_unavailable");
                exit();
            }

            // Mark PC as reserved
            $conn->query("
                UPDATE pc_status
                SET status='maintenance',
                    notes='Reserved'
                WHERE lab_name='$lab'
                AND pc_number=$seat
            ");
        }
    }

    // Update reservation status
    $conn->query("
        UPDATE reservations
        SET status='".ucfirst($status)."'
        WHERE id=$res_id
    ");

    $_SESSION['success'] = "Reservation ".$status." successfully.";

    header("Location: admin_dashboard.php?tab=reservation");
    exit();
}

// Post announcement
if (isset($_POST['post_announcement'])) {
    $msg = $conn->real_escape_string(trim($_POST['announcement']));
    if ($msg !== '') $conn->query("INSERT INTO announcements (admin_name, message, created_at) VALUES ('CCS Admin','$msg',NOW())");
    header("Location: admin_dashboard.php?tab=dashboard"); exit();
}

// Delete announcement
if (isset($_POST['delete_announcement'])) {
    $aid = intval($_POST['ann_id']);
    $conn->query("DELETE FROM announcements WHERE id=$aid");
    header("Location: admin_dashboard.php?tab=announcements"); exit();
}

// Add student
if (isset($_POST['add_student'])) {
    $id_num = $conn->real_escape_string(trim($_POST['add_idnumber']));
    $fname  = $conn->real_escape_string(trim($_POST['add_firstname']));
    $lname  = $conn->real_escape_string(trim($_POST['add_lastname']));
    $mname  = $conn->real_escape_string(trim($_POST['add_middlename']));
    $course = $conn->real_escape_string(trim($_POST['add_course']));
    $level  = intval($_POST['add_yearlevel']);
    $email  = $conn->real_escape_string(trim($_POST['add_email']));
    $pass   = password_hash(trim($_POST['add_password']), PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (id_number,first_name,last_name,middle_name,course,year_level,email,password,remaining_session) VALUES ('$id_num','$fname','$lname','$mname','$course',$level,'$email','$pass',30)");
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
    $conn->query("UPDATE users SET first_name='$fname',last_name='$lname',middle_name='$mname',course='$course',year_level=$level,email='$email',remaining_session=$rem WHERE id_number='$id_num'");
    header("Location: admin_dashboard.php?tab=students"); exit();
}

// Delete student
if (isset($_POST['delete_student'])) {
    $id_num = $conn->real_escape_string(trim($_POST['del_idnumber']));
    $conn->query("DELETE FROM users WHERE id_number='$id_num'");
    header("Location: admin_dashboard.php?tab=students"); exit();
}

// Reset all sessions (students)
if (isset($_POST['reset_all_student_sessions'])) {
    $conn->query("UPDATE users SET remaining_session=30");
    header("Location: admin_dashboard.php?tab=students"); exit();
}

// ── Toggle reservations enabled/disabled ─────────────────────────────
if (isset($_POST['toggle_reservations'])) {
    $current = $conn->query("SELECT value FROM system_settings WHERE setting_key='reservations_enabled'")->fetch_assoc();
    $new_val = ($current && $current['value'] === '1') ? '0' : '1';
    $conn->query("UPDATE system_settings SET value='$new_val' WHERE setting_key='reservations_enabled'");
    header("Location: admin_dashboard.php?tab=reservation"); exit();
}

// ── Software management ──────────────────────────────────────────────
if (isset($_POST['add_software'])) {
    $lab_name = $conn->real_escape_string(trim($_POST['sw_lab']));
    $name     = $conn->real_escape_string(trim($_POST['sw_name']));
    $ver      = $conn->real_escape_string(trim($_POST['sw_version']));
    $cat      = $conn->real_escape_string(trim($_POST['sw_category']));
    $avail    = isset($_POST['sw_available']) ? 1 : 0;
    $conn->query("INSERT INTO lab_software (lab_name, software_name, version, category, is_available) VALUES ('$lab_name','$name','$ver','$cat',$avail)");
    header("Location: admin_dashboard.php?tab=software"); exit();
}

if (isset($_POST['delete_software'])) {
    $sw_id = intval($_POST['sw_id']);
    $conn->query("DELETE FROM lab_software WHERE id=$sw_id");
    header("Location: admin_dashboard.php?tab=software"); exit();
}

if (isset($_POST['toggle_software'])) {
    $sw_id  = intval($_POST['sw_id']);
    $cur    = $conn->query("SELECT is_available FROM lab_software WHERE id=$sw_id")->fetch_assoc();
    $new_av = $cur ? ($cur['is_available'] ? 0 : 1) : 1;
    $conn->query("UPDATE lab_software SET is_available=$new_av WHERE id=$sw_id");
    header("Location: admin_dashboard.php?tab=software"); exit();
}

// ── LAB CONFIGURATION HANDLERS ──────────────────────────────────────
if (isset($_POST['update_lab_config'])) {
    $lab_name  = $conn->real_escape_string($_POST['lab_name']);
    $total_pcs = intval($_POST['total_pcs']);
    $conn->query("INSERT INTO lab_config (lab_name,total_pcs) VALUES ('$lab_name',$total_pcs) ON DUPLICATE KEY UPDATE total_pcs=$total_pcs");
    for ($i = 1; $i <= $total_pcs; $i++) {
        $conn->query("INSERT IGNORE INTO pc_status (lab_name,pc_number,status) VALUES ('$lab_name',$i,'available')");
    }
    header("Location: admin_dashboard.php?tab=lab_config&lab=".urlencode($lab_name)); exit();
}

if (isset($_POST['update_pc_status'])) {
    $lab_name  = $conn->real_escape_string($_POST['lab_name']);
    $pc_number = intval($_POST['pc_number']);
    $new_status = $conn->real_escape_string($_POST['status']);
    $notes     = $conn->real_escape_string($_POST['notes'] ?? '');
    $old_row   = $conn->query("SELECT status FROM pc_status WHERE lab_name='$lab_name' AND pc_number=$pc_number");
    $old_status = ($old_row && $old_row->num_rows > 0) ? $old_row->fetch_assoc()['status'] : 'unknown';
    $conn->query("UPDATE pc_status SET status='$new_status',notes='$notes',updated_by='Admin' WHERE lab_name='$lab_name' AND pc_number=$pc_number");
    $conn->query("INSERT INTO pc_status_history (lab_name,pc_number,old_status,new_status,notes,changed_by,changed_at) VALUES ('$lab_name',$pc_number,'$old_status','$new_status','$notes','Admin',NOW())");
    header("Location: admin_dashboard.php?tab=lab_config&lab=".urlencode($lab_name)); exit();
}

if (isset($_POST['batch_update_pcs'])) {
    $lab_name = $conn->real_escape_string($_POST['lab_name']);
    $status   = $conn->real_escape_string($_POST['batch_status']);
    $pc_range = $_POST['pc_range'];
    $pcs_to_update = [];
    if (strpos($pc_range,'-') !== false) { list($start,$end) = explode('-',$pc_range); for ($i=intval($start);$i<=intval($end);$i++) $pcs_to_update[] = $i; }
    elseif (strpos($pc_range,',') !== false) foreach (explode(',',$pc_range) as $pc) $pcs_to_update[] = intval(trim($pc));
    foreach ($pcs_to_update as $pc_num) {
        $old_row   = $conn->query("SELECT status FROM pc_status WHERE lab_name='$lab_name' AND pc_number=$pc_num");
        $old_status = ($old_row && $old_row->num_rows > 0) ? $old_row->fetch_assoc()['status'] : 'unknown';
        $conn->query("UPDATE pc_status SET status='$status' WHERE lab_name='$lab_name' AND pc_number=$pc_num");
        $conn->query("INSERT INTO pc_status_history (lab_name,pc_number,old_status,new_status,notes,changed_by,changed_at) VALUES ('$lab_name',$pc_num,'$old_status','$status','Batch update','Admin',NOW())");
    }
    header("Location: admin_dashboard.php?tab=lab_config&lab=".urlencode($lab_name)); exit();
}

if (isset($_POST['bulk_status_update'])) {
    $lab_name = $conn->real_escape_string($_POST['lab_name']);
    $status   = $conn->real_escape_string($_POST['bulk_status_all']);
    $all_pcs  = $conn->query("SELECT pc_number,status FROM pc_status WHERE lab_name='$lab_name'");
    if ($all_pcs) while ($pc = $all_pcs->fetch_assoc()) {
        $old_s = $conn->real_escape_string($pc['status']);
        $pc_n  = intval($pc['pc_number']);
        $conn->query("INSERT INTO pc_status_history (lab_name,pc_number,old_status,new_status,notes,changed_by,changed_at) VALUES ('$lab_name',$pc_n,'$old_s','$status','Bulk update','Admin',NOW())");
    }
    $conn->query("UPDATE pc_status SET status='$status' WHERE lab_name='$lab_name'");
    header("Location: admin_dashboard.php?tab=lab_config&lab=".urlencode($lab_name)); exit();
}

// Sit-in
if (isset($_POST['do_sitin'])) {
    $sid     = $conn->real_escape_string(trim($_POST['sitin_id']));
    $purpose = $conn->real_escape_string(trim($_POST['sitin_purpose']));
    $lab     = $conn->real_escape_string(trim($_POST['sitin_lab']));
    $pc_num  = isset($_POST['pc_number']) ? intval($_POST['pc_number']) : null;
    $sitin_error = '';
    $chk = $conn->query("SELECT id,remaining_session FROM users WHERE id_number='$sid'")->fetch_assoc();
    if (!$chk) $sitin_error = "Student not found.";
    elseif ($chk['remaining_session'] <= 0) $sitin_error = "Student has no remaining sessions left.";
    else {
        $active = $conn->query("SELECT id FROM sitin_records WHERE id_number='$sid' AND logout_time IS NULL")->fetch_assoc();
        if ($active) $sitin_error = "Student already has an active sit-in session.";
        else {
            if ($pc_num) {
                $pc_check = $conn->query("SELECT status FROM pc_status WHERE lab_name='$lab' AND pc_number=$pc_num")->fetch_assoc();
                if (!$pc_check || $pc_check['status'] !== 'available') {
                    $sitin_error = "Selected PC is not available.";
                    header("Location: admin_dashboard.php?tab=sitinform&error=".urlencode($sitin_error)); exit();
                }
                $conn->query("UPDATE pc_status SET status='in_use',notes='Used by $sid' WHERE lab_name='$lab' AND pc_number=$pc_num");
                $conn->query("INSERT INTO pc_status_history (lab_name,pc_number,old_status,new_status,notes,changed_by,changed_at) VALUES ('$lab',$pc_num,'available','in_use','Sit-in by $sid','Admin',NOW())");
            }
            $conn->query("INSERT INTO sitin_records (id_number,purpose,lab,pc_number,login_time) VALUES ('$sid','$purpose','$lab',".($pc_num ? $pc_num : "NULL").",NOW())");
            header("Location: admin_dashboard.php?tab=sitin"); exit();
        }
    }
    if ($sitin_error) { header("Location: admin_dashboard.php?tab=sitinform&error=".urlencode($sitin_error)); exit(); }
}

// Logout sit-in
if (isset($_POST['logout_session'])) {
    $sit_id = intval($_POST['sit_id']);
    $rec    = $conn->query("SELECT id_number,lab,pc_number FROM sitin_records WHERE id=$sit_id")->fetch_assoc();
    if ($rec) {
        $student_id = $conn->real_escape_string($rec['id_number']);
        $conn->query("UPDATE sitin_records SET logout_time=NOW() WHERE id=$sit_id");
        $conn->query("UPDATE users SET remaining_session=GREATEST(0,remaining_session-1) WHERE id_number='$student_id'");
        if ($rec['pc_number']) {
            $conn->query("UPDATE pc_status SET status='available',notes='' WHERE lab_name='{$rec['lab']}' AND pc_number={$rec['pc_number']}");
            $conn->query("INSERT INTO pc_status_history (lab_name,pc_number,old_status,new_status,notes,changed_by,changed_at) VALUES ('{$rec['lab']}',{$rec['pc_number']},'in_use','available','Student logged out','Admin',NOW())");
        }
    }
    header("Location: admin_dashboard.php?tab=sitin"); exit();
}

// Reset all active sessions
if (isset($_POST['reset_all_sessions'])) {
    $active_students = $conn->query("SELECT DISTINCT id_number,lab,pc_number FROM sitin_records WHERE logout_time IS NULL");
    $conn->query("UPDATE sitin_records SET logout_time=NOW() WHERE logout_time IS NULL");
    if ($active_students && $active_students->num_rows > 0) {
        while ($s = $active_students->fetch_assoc()) {
            $sid = $conn->real_escape_string($s['id_number']);
            $conn->query("UPDATE users SET remaining_session=GREATEST(0,remaining_session-1) WHERE id_number='$sid'");
            if ($s['pc_number']) {
                $conn->query("UPDATE pc_status SET status='available' WHERE lab_name='{$s['lab']}' AND pc_number={$s['pc_number']}");
                $conn->query("INSERT INTO pc_status_history (lab_name,pc_number,old_status,new_status,notes,changed_by,changed_at) VALUES ('{$s['lab']}',{$s['pc_number']},'in_use','available','Session reset by admin','Admin',NOW())");
            }
        }
    }
    header("Location: admin_dashboard.php?tab=sitin"); exit();
}

// AJAX: PC status
if (isset($_GET['get_pc_status'])) {
    $lab    = $conn->real_escape_string($_GET['lab']);
    $result = $conn->query("SELECT pc_number,status,notes FROM pc_status WHERE lab_name='$lab' ORDER BY pc_number");
    $pcs    = [];
    while ($row = $result->fetch_assoc()) $pcs[] = $row;
    header('Content-Type: application/json');
    echo json_encode($pcs); exit();
}

// AJAX: Search student
if (isset($_GET['search_student'])) {
    $q      = '%'.$conn->real_escape_string(trim($_GET['search_student'])).'%';
    $result = $conn->query("SELECT id_number,first_name,last_name,course,year_level,remaining_session FROM users WHERE id_number LIKE '$q' OR first_name LIKE '$q' OR last_name LIKE '$q' LIMIT 10");
    $rows   = [];
    if ($result) while ($r = $result->fetch_assoc()) $rows[] = $r;
    header('Content-Type: application/json');
    echo json_encode($rows); exit();
}

// ── Stats ────────────────────────────────────────────────────────────
$total_students  = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'] ?? 0;
$currently_sitin = $conn->query("SELECT COUNT(*) as c FROM sitin_records WHERE logout_time IS NULL")->fetch_assoc()['c'] ?? 0;
$total_sitin     = $conn->query("SELECT COUNT(*) as c FROM sitin_records")->fetch_assoc()['c'] ?? 0;

$lang_result = $conn->query("SELECT purpose,COUNT(*) as cnt FROM sitin_records GROUP BY purpose");
$lang_labels = []; $lang_data = [];
if ($lang_result) while ($row = $lang_result->fetch_assoc()) { $lang_labels[] = $row['purpose']; $lang_data[] = $row['cnt']; }

// Reservations enabled?
$res_enabled_row = $conn->query("SELECT value FROM system_settings WHERE setting_key='reservations_enabled'")->fetch_assoc();
$reservations_enabled = !$res_enabled_row || $res_enabled_row['value'] !== '0';

$active_tab   = $_GET['tab'] ?? 'dashboard';
$selected_lab = $_GET['lab'] ?? '524';
$error_msg    = $_GET['error'] ?? '';
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
        :root { --uc-blue:#a1cbf7; --ccs-purple:#9757d6; --ccs-gold:#FFD700; }
        * { box-sizing:border-box; }
        body { font-family:'Poppins',sans-serif; background:#f4f7f6; margin:0; overflow-x:hidden; }

        .top-navbar { position:fixed; top:0; left:0; width:100%; height:60px; z-index:1000; display:flex; align-items:center; justify-content:space-between; padding:0 12px; background:var(--ccs-purple); box-shadow:0 4px 12px rgba(0,0,0,0.15); gap:10px; }
        .nav-left { flex-shrink:0; }
        .nav-left .brand-title { color:white; font-weight:700; font-size:.9rem; white-space:nowrap; }
        .nav-links { display:flex; align-items:center; gap:2px; overflow:hidden; flex:1; justify-content:flex-end; }
        .nav-links a { display:flex; flex-direction:column; align-items:center; justify-content:center; color:rgba(255,255,255,.75); text-decoration:none; padding:4px 6px; border-radius:8px; font-size:.6rem; transition:all .2s; white-space:nowrap; min-width:44px; }
        .nav-links a i { font-size:.95rem; margin-bottom:1px; }
        .nav-links a span { font-size:.58rem; line-height:1; }
        .nav-links a:hover { background:rgba(255,255,255,.15); color:white; }
        .nav-links a.active { background:rgba(255,255,255,.22); color:white; font-weight:600; }
        .btn-logout-top { display:flex; flex-direction:column; align-items:center; justify-content:center; flex-shrink:0; background:rgba(0,0,0,.15); border:1px solid rgba(255,255,255,.25); color:#000; padding:4px 10px; border-radius:8px; font-size:.6rem; font-weight:600; text-decoration:none; transition:all .2s; min-width:44px; }
        .btn-logout-top i { font-size:.95rem; margin-bottom:1px; }
        .btn-logout-top:hover { background:var(--ccs-purple); color:white; }

        .main-content { margin-top:60px; padding:0; min-height:100vh; }
        .top-hero { background:linear-gradient(135deg,var(--ccs-purple) 0%,var(--uc-blue) 100%); padding:22px 28px 60px; color:white; }
        .top-hero h4 { font-weight:800; font-size:1.3rem; margin:0; color:white; }
        .admin-badge { background:rgba(255,255,255,.2); color:white; border:1px solid rgba(255,255,255,.3); border-radius:20px; padding:4px 14px; font-size:.78rem; font-weight:600; }
        .content-area { padding:0 24px 30px; margin-top:-36px; }

        .stat-card { background:white; border-radius:18px; padding:1.1rem 1.3rem; box-shadow:0 8px 24px rgba(151,87,214,.12); display:flex; align-items:center; gap:14px; border:1px solid rgba(0,0,0,.04); }
        .stat-icon { width:50px; height:50px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; }
        .stat-icon.purple { background:linear-gradient(135deg,var(--ccs-purple),#b47ee8); color:white; }
        .stat-icon.blue   { background:linear-gradient(135deg,#4da8f5,var(--uc-blue)); color:white; }
        .stat-icon.gold   { background:linear-gradient(135deg,#f7c948,var(--ccs-gold)); color:#7a5c00; }
        .stat-num   { font-size:1.7rem; font-weight:800; color:#222; line-height:1; }
        .stat-label { font-size:.72rem; color:#aaa; margin-top:2px; }

        .dash-card { background:white; border-radius:18px; box-shadow:0 4px 20px rgba(151,87,214,.08); border:1px solid rgba(0,0,0,.04); overflow:hidden; }
        .card-header-purple { background:linear-gradient(135deg,var(--ccs-purple),#7c45b8); color:white; font-weight:600; font-size:.88rem; padding:12px 18px; display:flex; align-items:center; justify-content:space-between; }
        .card-header-gold   { background:var(--ccs-gold); color:#4a3800; font-weight:700; font-size:.88rem; padding:12px 18px; display:flex; align-items:center; justify-content:space-between; }
        .card-header-blue   { background:linear-gradient(135deg,#3a9bd5,#5ab4f0); color:white; font-weight:600; font-size:.88rem; padding:12px 18px; display:flex; align-items:center; justify-content:space-between; }
        .card-header-green  { background:linear-gradient(135deg,#27ae60,#1e8449); color:white; font-weight:600; font-size:.88rem; padding:12px 18px; display:flex; align-items:center; justify-content:space-between; }

        .pc-grid { display:grid; grid-template-columns:repeat(10,1fr); gap:8px; padding:15px; }
        .pc-card { text-align:center; padding:11px 5px; border-radius:10px; cursor:pointer; transition:all .2s; font-weight:600; font-size:.83rem; }
        .pc-card.available   { background:linear-gradient(135deg,#d5f5e3,#a9dfbf); color:#1a6835; border:2px solid #82c99a; }
        .pc-card.maintenance { background:linear-gradient(135deg,#fdebd0,#f8c471); color:#784212; border:2px solid #f39c12; }
        .pc-card.not_available { background:linear-gradient(135deg,#fadbd8,#f1948a); color:#7b241c; border:2px solid #e57373; opacity:.7; }
        .pc-card.in_use      { background:linear-gradient(135deg,#d4e6f1,#a9cce3); color:#1a5276; border:2px solid #3498db; }
        .pc-card:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,.15); }

        .table thead th { background:linear-gradient(135deg,var(--ccs-purple),#7c45b8); color:white; font-size:.78rem; font-weight:600; border:none; padding:9px 11px; }
        .table tbody td { font-size:.81rem; vertical-align:middle; color:#333; }
        .table tbody tr:hover { background:#f8f1fe; }
        .table { margin-bottom:0; }

        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select { border:1px solid #ddd; border-radius:8px; padding:5px 10px; font-family:'Poppins',sans-serif; font-size:.81rem; }

        .badge-active   { background:linear-gradient(135deg,#27ae60,#2ecc71); color:white; padding:3px 9px; border-radius:20px; font-size:.7rem; font-weight:600; }
        .badge-done     { background:#bdc3c7; color:#555; padding:3px 9px; border-radius:20px; font-size:.7rem; font-weight:600; }
        .badge-pending  { background:linear-gradient(135deg,#f39c12,#e67e22); color:white; padding:3px 9px; border-radius:20px; font-size:.7rem; font-weight:600; }
        .badge-approved { background:linear-gradient(135deg,#27ae60,#2ecc71); color:white; padding:3px 9px; border-radius:20px; font-size:.7rem; font-weight:600; }
        .badge-rejected { background:linear-gradient(135deg,#e74c3c,#c0392b); color:white; padding:3px 9px; border-radius:20px; font-size:.7rem; font-weight:600; }

        .btn-purple { background:linear-gradient(135deg,var(--ccs-purple),#7c45b8); color:white; border:none; border-radius:8px; font-size:.81rem; font-weight:600; transition:opacity .2s; }
        .btn-purple:hover { opacity:.88; color:white; }
        .btn-gold-action { background:var(--ccs-gold); color:#4a3800; border:none; border-radius:8px; font-size:.81rem; font-weight:600; }
        .btn-gold-action:hover { background:#e6c200; color:#333; }
        .btn-logout-tbl { background:linear-gradient(135deg,#e74c3c,#c0392b); color:white; border:none; border-radius:6px; padding:3px 10px; font-size:.75rem; font-weight:600; cursor:pointer; }

        .ann-item { padding:11px 0; border-bottom:1px solid #f0f0f0; }
        .ann-item:last-child { border-bottom:none; }
        .ann-item h6 { color:var(--ccs-purple); font-weight:700; margin-bottom:2px; font-size:.86rem; }

        .modal-header-purple { background:linear-gradient(135deg,var(--ccs-purple),#7c45b8); color:white; border-radius:12px 12px 0 0; padding:14px 20px; }
        .modal-header-purple .btn-close { filter:invert(1); }
        .modal-content { border-radius:16px; border:none; box-shadow:0 20px 60px rgba(151,87,214,.2); }
        .form-control:focus,.form-select:focus { border-color:var(--ccs-purple); box-shadow:0 0 0 3px rgba(151,87,214,.12); }

        .result-item { padding:9px 11px; border-radius:8px; cursor:pointer; border:1px solid #eee; margin-bottom:5px; background:white; transition:background .15s; }
        .result-item:hover { background:#f8f1fe; border-color:#d9b8f7; }
        .result-item .r-name { font-weight:600; font-size:.86rem; color:#333; }
        .result-item .r-sub  { font-size:.75rem; color:#999; }

        .selected-student-card { background:linear-gradient(135deg,#f8f1fe,#eef6ff); border-radius:12px; padding:.95rem 1.1rem; margin-bottom:.95rem; border:1px solid rgba(151,87,214,.15); }
        .selected-student-card .label { font-size:.67rem; color:var(--ccs-purple); font-weight:700; text-transform:uppercase; letter-spacing:.08em; margin-bottom:7px; }
        .selected-student-card .val-title { font-size:.68rem; color:#999; }
        .selected-student-card .val { font-weight:700; color:#222; font-size:.86rem; }

        .announce-textarea { border:1px solid #ddd; border-radius:10px; padding:9px 11px; width:100%; font-family:'Poppins',sans-serif; font-size:.83rem; resize:vertical; min-height:75px; transition:border .2s,box-shadow .2s; }
        .announce-textarea:focus { outline:none; border-color:var(--ccs-purple); box-shadow:0 0 0 3px rgba(151,87,214,.12); }
        .chart-wrapper { position:relative; height:240px; }

        .lab-selector { background:white; border-radius:12px; padding:10px; margin-bottom:18px; display:flex; gap:9px; flex-wrap:wrap; }
        .lab-btn { padding:7px 18px; border-radius:8px; background:#f0f0f0; color:#666; text-decoration:none; font-weight:600; transition:all .2s; font-size:.83rem; }
        .lab-btn.active { background:linear-gradient(135deg,var(--ccs-purple),#7c45b8); color:white; }
        .lab-btn:hover { transform:translateY(-2px); box-shadow:0 2px 8px rgba(151,87,214,.2); }

        /* ── Leaderboard ── */
        .lb-row { display:flex; align-items:center; gap:12px; padding:10px 14px; border-radius:12px; margin-bottom:7px; border:1px solid #f0f0f0; transition:background .15s; }
        .lb-row:hover { background:#f8f1fe; }
        .lb-rank { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:.88rem; flex-shrink:0; }
        .lb-rank.gold-rank   { background:linear-gradient(135deg,#f7c948,var(--ccs-gold)); color:#7a5c00; }
        .lb-rank.silver-rank { background:linear-gradient(135deg,#bdc3c7,#95a5a6); color:white; }
        .lb-rank.bronze-rank { background:linear-gradient(135deg,#e67e22,#d35400); color:white; }
        .lb-rank.normal-rank { background:#f0f0f0; color:#666; }
        .lb-name { font-weight:700; font-size:.85rem; color:#333; flex:1; }
        .lb-sub  { font-size:.72rem; color:#aaa; }
        .lb-count { font-size:1.2rem; font-weight:800; color:var(--ccs-purple); }
        .lb-bar-wrap { flex:1; height:6px; background:#ede8f6; border-radius:3px; overflow:hidden; }
        .lb-bar { height:100%; border-radius:3px; background:linear-gradient(90deg,var(--ccs-purple),#b47ee8); }

        /* ── Reservation toggle banner ── */
        .res-status-banner { border-radius:12px; padding:11px 16px; font-weight:600; font-size:.85rem; display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; }
        .res-status-banner.enabled  { background:linear-gradient(135deg,#d5f5e3,#a9dfbf); border:1px solid #82c99a; color:#1a6835; }
        .res-status-banner.disabled { background:linear-gradient(135deg,#fadbd8,#f1948a); border:1px solid #e57373; color:#7b241c; }

        /* ── Software tab ── */
        .sw-badge-avail { background:#d5f5e3; color:#1a6835; padding:2px 9px; border-radius:20px; font-size:.7rem; font-weight:700; }
        .sw-badge-no    { background:#fadbd8; color:#7b241c; padding:2px 9px; border-radius:20px; font-size:.7rem; font-weight:700; }

        footer { padding:24px; text-align:center; color:#aaa; font-size:.78rem; }
    </style>
</head>
<body>

<!-- ════ TOP NAVBAR ════ -->
<nav class="top-navbar">
    <div class="nav-left">
        <span class="brand-title"><i class="bi bi-cpu-fill me-1"></i>CCS Sit-in</span>
    </div>
    <div class="nav-links">
        <a href="admin_dashboard.php?tab=dashboard"     class="<?=$active_tab==='dashboard'    ?'active':''?>" title="Dashboard"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
        <a href="admin_dashboard.php?tab=students"      class="<?=$active_tab==='students'     ?'active':''?>" title="Students"><i class="bi bi-people-fill"></i><span>Students</span></a>
        <a href="admin_dashboard.php?tab=sitinform"     class="<?=$active_tab==='sitinform'    ?'active':''?>" title="Sit-in"><i class="bi bi-box-arrow-in-right"></i><span>Sit-in</span></a>
        <a href="admin_dashboard.php?tab=sitin"         class="<?=$active_tab==='sitin'        ?'active':''?>" title="Current"><i class="bi bi-display"></i><span>Current</span></a>
        <a href="admin_dashboard.php?tab=lab_config"    class="<?=$active_tab==='lab_config'   ?'active':''?>" title="Lab Config"><i class="bi bi-pc-display"></i><span>Lab</span></a>
        <a href="admin_dashboard.php?tab=records"       class="<?=$active_tab==='records'      ?'active':''?>" title="Records"><i class="bi bi-table"></i><span>Records</span></a>
        <a href="admin_dashboard.php?tab=leaderboard"   class="<?=$active_tab==='leaderboard'  ?'active':''?>" title="Leaderboard"><i class="bi bi-trophy-fill"></i><span>Leaders</span></a>
        <a href="admin_dashboard.php?tab=announcements" class="<?=$active_tab==='announcements'?'active':''?>" title="Announcements"><i class="bi bi-megaphone-fill"></i><span>Announce</span></a>
        <a href="admin_dashboard.php?tab=reports"       class="<?=$active_tab==='reports'      ?'active':''?>" title="Reports"><i class="bi bi-bar-chart-fill"></i><span>Reports</span></a>
        <a href="admin_dashboard.php?tab=software"      class="<?=$active_tab==='software'     ?'active':''?>" title="Software"><i class="bi bi-app-indicator"></i><span>Software</span></a>
        <a href="admin_dashboard.php?tab=feedback"      class="<?=$active_tab==='feedback'     ?'active':''?>" title="Feedback"><i class="bi bi-chat-left-text-fill"></i><span>Feedback</span></a>
        <a href="admin_dashboard.php?tab=reservation"   class="<?=$active_tab==='reservation'  ?'active':''?>" title="Reservation"><i class="bi bi-calendar-check-fill"></i><span>Reserve</span></a>
    </div>
    <a href="#" class="btn-logout-top" onclick="confirmLogout(event)"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a>
</nav>

<!-- ════ MAIN CONTENT ════ -->
<div class="main-content">
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
                    'leaderboard'   => '<i class="bi bi-trophy-fill me-2"></i>Leaderboard',
                    'announcements' => '<i class="bi bi-megaphone-fill me-2"></i>Announcements',
                    'reports'       => '<i class="bi bi-bar-chart-fill me-2"></i>Sit-in Reports',
                    'software'      => '<i class="bi bi-app-indicator me-2"></i>Lab Software',
                    'feedback'      => '<i class="bi bi-chat-left-text-fill me-2"></i>Feedback Reports',
                    'reservation'   => '<i class="bi bi-calendar-check-fill me-2"></i>Reservation',
                ];
                echo $titles[$active_tab] ?? $titles['dashboard'];
                ?>
            </h4>
            <span class="admin-badge"><i class="bi bi-shield-fill-check me-1"></i>CCS Admin</span>
        </div>
    </div>

    <div class="content-area">
        <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-3" style="border-radius:12px;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error_msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

<!-- ══════════════ DASHBOARD TAB ══════════════ -->
<?php if ($active_tab === 'dashboard'): ?>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="stat-card"><div class="stat-icon purple"><i class="bi bi-people-fill"></i></div><div><div class="stat-num"><?=$total_students?></div><div class="stat-label">Students Registered</div></div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-icon blue"><i class="bi bi-display"></i></div><div><div class="stat-num"><?=$currently_sitin?></div><div class="stat-label">Currently Sit-in</div></div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-icon gold"><i class="bi bi-clock-history"></i></div><div><div class="stat-num"><?=$total_sitin?></div><div class="stat-label">Total Sit-in Sessions</div></div></div></div>
</div>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="dash-card h-100">
            <div class="card-header-purple"><span><i class="bi bi-pie-chart-fill me-2"></i>Sessions by Purpose</span></div>
            <div class="card-body d-flex align-items-center justify-content-center p-3">
                <div class="chart-wrapper w-100"><canvas id="purposeChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="dash-card h-100">
            <div class="card-header-purple"><span><i class="bi bi-megaphone-fill me-2"></i>Announcements</span><a href="admin_dashboard.php?tab=announcements" class="btn btn-sm btn-purple px-3" style="font-size:.73rem;">Manage</a></div>
            <div class="card-body p-3">
                <form method="POST" action="admin_dashboard.php?tab=dashboard" class="mb-3">
                    <textarea class="announce-textarea" name="announcement" placeholder="Write a new announcement..."></textarea>
                    <button type="submit" name="post_announcement" class="btn btn-sm btn-purple mt-2 px-3"><i class="bi bi-send-fill me-1"></i>Post</button>
                </form>
                <hr class="my-2">
                <?php
                $ann = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 4");
                if ($ann && $ann->num_rows > 0): while ($a = $ann->fetch_assoc()):
                    $date = date('Y-M-d', strtotime($a['created_at'])); ?>
                    <div class="ann-item">
                        <h6><?=htmlspecialchars($a['admin_name'])?> <small class="text-muted fw-normal" style="font-size:.73rem;">| <?=$date?></small></h6>
                        <p class="mb-0 text-muted" style="font-size:.8rem;"><?=htmlspecialchars($a['message'])?></p>
                    </div>
                <?php endwhile; else: ?><p class="text-muted mb-0" style="font-size:.83rem;">No announcements yet.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
new Chart(document.getElementById('purposeChart'),{type:'pie',data:{labels:<?=json_encode($lang_labels?:['No Data'])?>,datasets:[{data:<?=json_encode($lang_data?:[1])?>,backgroundColor:['#9757d6','#a1cbf7','#27ae60','#f39c12','#e74c3c','#3498db','#FFD700'],borderWidth:2,borderColor:'#fff'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{font:{family:'Poppins',size:11},boxWidth:14}}}}});
</script>

<!-- ══════════════ STUDENTS TAB ══════════════ -->
<?php elseif ($active_tab === 'students'): ?>

<?php
// Show entries value
$show = isset($_GET['show']) ? (int)$_GET['show'] : 10;

// Allowed values only
$allowed = [10, 25, 50, 100];

if (!in_array($show, $allowed)) {
    $show = 10;
}
?>

<style>
/* Hide default DataTables show entries */
.dataTables_length{
    display:none;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">

    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-purple px-4" onclick="openAddModal()">
            <i class="bi bi-person-plus-fill me-2"></i>Add Students
        </button>

        <form method="POST" onsubmit="return confirm('Reset ALL students sessions to 30?')">
            <button name="reset_all_student_sessions" class="btn btn-danger px-4">
                <i class="bi bi-arrow-counterclockwise me-2"></i>Reset All Sessions
            </button>
        </form>
    </div>

    <!-- Custom Show Entries -->
    <form method="GET" class="d-flex align-items-center gap-2">
        <input type="hidden" name="tab" value="students">

        <label class="fw-bold mb-0">Show Entries:</label>

        <select
            name="show"
            class="form-select"
            style="width:110px;"
            onchange="this.form.submit()"
        >
            <option value="10" <?= $show == 10 ? 'selected' : '' ?>>10</option>
            <option value="25" <?= $show == 25 ? 'selected' : '' ?>>25</option>
            <option value="50" <?= $show == 50 ? 'selected' : '' ?>>50</option>
            <option value="100" <?= $show == 100 ? 'selected' : '' ?>>100</option>
        </select>
    </form>

</div>

<div class="dash-card">

    <div class="card-header-purple">
        <span>
            <i class="bi bi-people-fill me-2"></i>All Students
        </span>
    </div>

    <div class="card-body p-3">

        <table id="studentTable" class="table table-bordered table-hover w-100">

            <thead>
                <tr>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Year Level</th>
                    <th>Course</th>
                    <th>Remaining Sessions</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

            <?php
            // LIMIT rows depending on selected show entries
            $stu = $conn->query("
                SELECT *
                FROM users
                ORDER BY last_name ASC
                LIMIT $show
            ");

            if ($stu && $stu->num_rows > 0):

                while ($s = $stu->fetch_assoc()):

                    $rem = intval($s['remaining_session'] ?? 30);

                    $color = $rem > 10
                        ? '#27ae60'
                        : ($rem > 5 ? '#f39c12' : '#e74c3c');
            ?>

                <tr>

                    <td>
                        <?= htmlspecialchars($s['id_number']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($s['year_level']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($s['course']) ?>
                    </td>

                    <td>
                        <span style="font-weight:700;color:<?= $color ?>;">
                            <?= $rem ?>
                        </span>
                    </td>

                    <td>

                        <button
                            class="btn btn-sm btn-purple me-1"

                            onclick="openEditModal(
                                '<?= htmlspecialchars($s['id_number'], ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($s['first_name'], ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($s['last_name'], ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($s['middle_name'] ?? '', ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($s['course'], ENT_QUOTES) ?>',
                                '<?= $s['year_level'] ?>',
                                '<?= htmlspecialchars($s['email'], ENT_QUOTES) ?>',
                                '<?= $rem ?>'
                            )"
                        >
                            <i class="bi bi-pencil-fill"></i> Edit
                        </button>

                        <button
                            class="btn btn-sm btn-danger"

                            onclick="deleteStudent(
                                '<?= htmlspecialchars($s['id_number'], ENT_QUOTES) ?>'
                            )"
                        >
                            <i class="bi bi-trash-fill"></i> Delete
                        </button>

                    </td>

                </tr>

            <?php
                endwhile;

            endif;
            ?>

            </tbody>

        </table>

    </div>

</div>

<form method="POST" id="deleteStudentForm">
    <input type="hidden" name="del_idnumber" id="del_idnumber">
    <input type="hidden" name="delete_student" value="1">
</form>

<!-- DataTables -->
<script>
$(document).ready(function () {

    $('#studentTable').DataTable({

        pageLength: <?= $show ?>,

        lengthMenu: [10, 25, 50, 100],

        responsive: true

    });

});
</script>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header-purple d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="bi bi-person-plus-fill me-2"></i>Add Student</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4"><form method="POST">
            <div class="row g-2">
                <div class="col-12"><label style="font-size:.77rem;color:#777;">ID Number</label><input type="text" name="add_idnumber" class="form-control" required></div>
                <div class="col-6"><label style="font-size:.77rem;color:#777;">First Name</label><input type="text" name="add_firstname" class="form-control" required></div>
                <div class="col-6"><label style="font-size:.77rem;color:#777;">Last Name</label><input type="text" name="add_lastname" class="form-control" required></div>
                <div class="col-12"><label style="font-size:.77rem;color:#777;">Middle Name</label><input type="text" name="add_middlename" class="form-control"></div>
                <div class="mb-3">
                    <label class="form-label">Course</label>
                    <select class="form-select form-control" name="add_course" id="add_course" required>
                        <option value="Information Technology">Information Technology</option>
                        <option value="Computer Engineering">Computer Engineering</option>
                        <option value="Civil Engineering">Civil Engineering</option>
                        <option value="Mechanical Engineering">Mechanical Engineering</option>
                        <option value="Electrical Engineering">Electrical Engineering</option>
                        <option value="Industrial Engineering">Industrial Engineering</option>
                        <option value="Naval Architecture and Marine Engineering">Naval Architecture and Marine Engineering</option>
                        <option value="Elementary Education (BEEd)">Elementary Education (BEEd)</option>
                        <option value="Secondary Education (BSEd)">Secondary Education (BSEd)</option>
                        <option value="Criminology">Criminology</option>
                        <option value="Commerce">Commerce</option>
                        <option value="Accountancy">Accountancy</option>
                        <option value="Hotel and Restaurant Management">Hotel and Restaurant Management</option>
                        <option value="Customs Administration">Customs Administration</option>
                        <option value="Computer Secretarial">Computer Secretarial</option>
                        <option value="Industrial Psychology">Industrial Psychology</option>
                        <option value="AB Political Science">AB Political Science</option>
                        <option value="AB English">AB English</option>
                    </select>
                </div>

                <div class="col-4"><label style="font-size:.77rem;color:#777;">Year Level</label><select name="add_yearlevel" class="form-select" required><option value="1">1st</option><option value="2">2nd</option><option value="3">3rd</option><option value="4">4th</option></select></div>
                <div class="col-12"><label style="font-size:.77rem;color:#777;">Email</label><input type="email" name="add_email" class="form-control" required></div>
                <div class="col-12"><label style="font-size:.77rem;color:#777;">Password</label><input type="password" name="add_password" class="form-control" required></div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-3">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_student" class="btn btn-purple px-4"><i class="bi bi-person-plus-fill me-1"></i>Add Student</button>
            </div>
        </form></div>
    </div></div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header-purple d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="bi bi-pencil-fill me-2"></i>Edit Student</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4"><form method="POST">
            <input type="hidden" name="edit_idnumber" id="edit_idnumber">
            <div class="row g-2">
                <div class="col-6"><label style="font-size:.77rem;color:#777;">First Name</label><input type="text" name="edit_firstname" id="edit_firstname" class="form-control" required></div>
                <div class="col-6"><label style="font-size:.77rem;color:#777;">Last Name</label><input type="text" name="edit_lastname" id="edit_lastname" class="form-control" required></div>
                <div class="col-12"><label style="font-size:.77rem;color:#777;">Middle Name</label><input type="text" name="edit_middlename" id="edit_middlename" class="form-control"></div>
               <div class="mb-3">
                    <label class="form-label">Course</label>
                    <select class="form-select form-control" name="edit_course" id="edit_course" required>
                        <option value="Information Technology">Information Technology</option>
                        <option value="Computer Engineering">Computer Engineering</option>
                        <option value="Civil Engineering">Civil Engineering</option>
                        <option value="Mechanical Engineering">Mechanical Engineering</option>
                        <option value="Electrical Engineering">Electrical Engineering</option>
                        <option value="Industrial Engineering">Industrial Engineering</option>
                        <option value="Naval Architecture and Marine Engineering">Naval Architecture and Marine Engineering</option>
                        <option value="Elementary Education (BEEd)">Elementary Education (BEEd)</option>
                        <option value="Secondary Education (BSEd)">Secondary Education (BSEd)</option>
                        <option value="Criminology">Criminology</option>
                        <option value="Commerce">Commerce</option>
                        <option value="Accountancy">Accountancy</option>
                        <option value="Hotel and Restaurant Management">Hotel and Restaurant Management</option>
                        <option value="Customs Administration">Customs Administration</option>
                        <option value="Computer Secretarial">Computer Secretarial</option>
                        <option value="Industrial Psychology">Industrial Psychology</option>
                        <option value="AB Political Science">AB Political Science</option>
                        <option value="AB English">AB English</option>
                    </select>
                </div>
                <div class="col-4"><label style="font-size:.77rem;color:#777;">Year Level</label><select name="edit_yearlevel" id="edit_yearlevel" class="form-select"><option value="1">1st</option><option value="2">2nd</option><option value="3">3rd</option><option value="4">4th</option></select></div>
                <div class="col-12"><label style="font-size:.77rem;color:#777;">Email</label><input type="email" name="edit_email" id="edit_email" class="form-control" required></div>
                <div class="col-12"><label style="font-size:.77rem;color:#777;">Remaining Sessions</label><input type="number" name="edit_remaining" id="edit_remaining" class="form-control" min="0" max="30" required></div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-3">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="edit_student" class="btn btn-purple px-4"><i class="bi bi-save-fill me-1"></i>Save Changes</button>
            </div>
        </form></div>
    </div></div>
</div>

<!-- ══════════════ LEADERBOARD TAB ══════════════ -->
<?php elseif ($active_tab === 'leaderboard'): ?>
<?php
$leaderboard = $conn->query("
    SELECT u.id_number, u.first_name, u.last_name, u.course, u.year_level,
           COUNT(sr.id) as session_count,
           SUM(TIMESTAMPDIFF(MINUTE, sr.login_time, IFNULL(sr.logout_time, NOW()))) as total_minutes
    FROM users u
    LEFT JOIN sitin_records sr ON u.id_number = sr.id_number
    GROUP BY u.id_number, u.first_name, u.last_name, u.course, u.year_level
    HAVING session_count > 0
    ORDER BY session_count DESC, total_minutes DESC
    LIMIT 20
");
$max_sessions = 1;
$lb_rows = [];
if ($leaderboard && $leaderboard->num_rows > 0) {
    while ($r = $leaderboard->fetch_assoc()) $lb_rows[] = $r;
    if (!empty($lb_rows)) $max_sessions = max(1, $lb_rows[0]['session_count']);
}
?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="dash-card h-100">
            <div class="card-header-purple">
                <span><i class="bi bi-trophy-fill me-2"></i>Top Students — Most Sit-in Sessions</span>
            </div>
            <div class="card-body p-3">
                <?php if (empty($lb_rows)): ?>
                    <p class="text-muted text-center py-3"><i class="bi bi-inbox me-2"></i>No sit-in data yet.</p>
                <?php else: ?>
                    <?php foreach ($lb_rows as $rank_idx => $lb):
                        $rank     = $rank_idx + 1;
                        $rank_cls = $rank === 1 ? 'gold-rank' : ($rank === 2 ? 'silver-rank' : ($rank === 3 ? 'bronze-rank' : 'normal-rank'));
                        $pct      = round(($lb['session_count'] / $max_sessions) * 100);
                        $hours    = $lb['total_minutes'] ? round($lb['total_minutes']/60,1) : 0;
                    ?>
                    <div class="lb-row">
                        <div class="lb-rank <?=$rank_cls?>"><?=$rank?></div>
                        <div style="flex:1;">
                            <div class="lb-name"><?=htmlspecialchars($lb['first_name'].' '.$lb['last_name'])?></div>
                            <div class="lb-sub"><?=htmlspecialchars($lb['course'])?>, Year <?=$lb['year_level']?> &mdash; <?=$hours?>h total</div>
                            <div class="lb-bar-wrap mt-1"><div class="lb-bar" style="width:<?=$pct?>%;"></div></div>
                        </div>
                        <div class="lb-count"><?=$lb['session_count']?><small style="font-size:.65rem;color:#aaa;display:block;text-align:center;">sessions</small></div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="dash-card mb-4">
            <div class="card-header-purple"><span><i class="bi bi-bar-chart-fill me-2"></i>Sessions by Lab</span></div>
            <div class="card-body p-3"><div class="chart-wrapper"><canvas id="lbLabChart"></canvas></div></div>
        </div>
        <div class="dash-card">
            <div class="card-header-purple"><span><i class="bi bi-pie-chart-fill me-2"></i>Sessions by Purpose</span></div>
            <div class="card-body p-3"><div class="chart-wrapper"><canvas id="lbPurposeChart"></canvas></div></div>
        </div>
    </div>
</div>
<?php
$lab_r = $conn->query("SELECT lab, COUNT(*) as cnt FROM sitin_records GROUP BY lab");
$lb_lab_labels = []; $lb_lab_data = [];
if ($lab_r) while ($r = $lab_r->fetch_assoc()) { $lb_lab_labels[] = 'Lab '.$r['lab']; $lb_lab_data[] = $r['cnt']; }
?>
<script>
new Chart(document.getElementById('lbLabChart'),{type:'bar',data:{labels:<?=json_encode($lb_lab_labels?:['No Data'])?>,datasets:[{label:'Sessions',data:<?=json_encode($lb_lab_data?:[0])?>,backgroundColor:'#9757d6',borderRadius:8}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}});
new Chart(document.getElementById('lbPurposeChart'),{type:'doughnut',data:{labels:<?=json_encode($lang_labels?:['No Data'])?>,datasets:[{data:<?=json_encode($lang_data?:[1])?>,backgroundColor:['#9757d6','#a1cbf7','#27ae60','#f39c12','#e74c3c','#3498db','#FFD700'],borderWidth:2,borderColor:'#fff'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{font:{family:'Poppins',size:10},boxWidth:12}}}}});
</script>

<!-- ══════════════ LAB CONFIG TAB ══════════════ -->
<?php elseif ($active_tab === 'lab_config'): ?>
<?php
$labs        = $conn->query("SELECT * FROM lab_config ORDER BY lab_name");
$current_lab = $selected_lab;
$pcs = $conn->query("SELECT * FROM pc_status WHERE lab_name='$current_lab' ORDER BY pc_number");
$available_count = $maintenance_count = $not_avail_count = $in_use_count = 0;
$pc_data_array = [];
if ($pcs && $pcs->num_rows > 0) {
    while ($row = $pcs->fetch_assoc()) {
        $pc_data_array[$row['pc_number']] = $row;
        switch($row['status']) {
            case 'available':    $available_count++; break;
            case 'maintenance':  $maintenance_count++; break;
            case 'not_available':$not_avail_count++; break;
            case 'in_use':       $in_use_count++; break;
        }
    }
}
$total_pcs = $conn->query("SELECT total_pcs FROM lab_config WHERE lab_name='$current_lab'")->fetch_assoc()['total_pcs'] ?? 50;
?>
<div class="lab-selector">
    <?php $labs->data_seek(0); while ($lab = $labs->fetch_assoc()): ?>
    <a href="admin_dashboard.php?tab=lab_config&lab=<?=urlencode($lab['lab_name'])?>" class="lab-btn <?=$current_lab===$lab['lab_name']?'active':''?>">
        <i class="bi bi-pc-display"></i> Lab <?=htmlspecialchars($lab['lab_name'])?>
    </a>
    <?php endwhile; ?>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card"><div class="stat-icon" style="background:#27ae60;color:white;"><i class="bi bi-check-circle-fill"></i></div><div><div class="stat-num"><?=$available_count?></div><div class="stat-label">Available</div></div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="stat-icon" style="background:#f39c12;color:white;"><i class="bi bi-tools"></i></div><div><div class="stat-num"><?=$maintenance_count?></div><div class="stat-label">Maintenance</div></div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="stat-icon" style="background:#3498db;color:white;"><i class="bi bi-person-workspace"></i></div><div><div class="stat-num"><?=$in_use_count?></div><div class="stat-label">In Use</div></div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="stat-icon" style="background:#e74c3c;color:white;"><i class="bi bi-x-lg"></i></div><div><div class="stat-num"><?=$not_avail_count?></div><div class="stat-label">Not Available</div></div></div></div>
</div>
<div class="dash-card mb-4">
    <div class="card-header-purple"><span><i class="bi bi-lightning-charge-fill me-2"></i>Bulk Actions — Lab <?=htmlspecialchars($current_lab)?></span></div>
    <div class="card-body p-3">
        <div class="row g-3">
            <div class="col-md-4">
                <form method="POST" class="d-flex gap-2" onsubmit="return confirm('Update ALL PCs in this lab?')">
                    <input type="hidden" name="lab_name" value="<?=$current_lab?>">
                    <select name="bulk_status_all" class="form-select" required><option value="">Select Status</option><option value="available">All Available</option><option value="maintenance">All Maintenance</option><option value="not_available">All Not Available</option></select>
                    <button type="submit" name="bulk_status_update" class="btn btn-purple">Apply</button>
                </form>
            </div>
            <div class="col-md-4">
                <form method="POST" class="d-flex gap-2">
                    <input type="hidden" name="lab_name" value="<?=$current_lab?>">
                    <input type="text" name="pc_range" class="form-control" placeholder="1-10 or 1,3,5" required>
                    <select name="batch_status" class="form-select" required><option value="">Status</option><option value="available">Available</option><option value="maintenance">Maintenance</option><option value="not_available">Not Available</option></select>
                    <button type="submit" name="batch_update_pcs" class="btn btn-purple">Apply</button>
                </form>
            </div>
            <div class="col-md-4">
                <form method="POST" class="d-flex gap-2">
                    <input type="hidden" name="lab_name" value="<?=$current_lab?>">
                    <input type="number" name="total_pcs" class="form-control" placeholder="Total PCs" value="<?=$total_pcs?>" required>
                    <button type="submit" name="update_lab_config" class="btn btn-purple">Update Total</button>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="dash-card mb-4">
    <div class="card-header-purple">
        <span><i class="bi bi-grid-3x3-gap-fill me-2"></i>PC Status Map — Lab <?=htmlspecialchars($current_lab)?></span>
        <div class="d-flex gap-3" style="font-size:.73rem;">
            <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#27ae60;margin-right:3px;"></span>Available</span>
            <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#f39c12;margin-right:3px;"></span>Maintenance</span>
            <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#3498db;margin-right:3px;"></span>In Use</span>
            <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#e74c3c;margin-right:3px;"></span>Not Available</span>
        </div>
    </div>
    <div class="card-body p-3">
        <div class="pc-grid">
            <?php for ($i=1;$i<=$total_pcs;$i++):
                $pc_data = $pc_data_array[$i] ?? null;
                $status  = $pc_data ? $pc_data['status'] : 'available';
                $notes   = $pc_data ? $pc_data['notes'] : '';
                switch($status) {
                    case 'available':    $icon='<i class="bi bi-check-circle-fill" style="font-size:1.1rem;color:#27ae60;"></i>'; $sd='Available';    $bc='available';    break;
                    case 'maintenance':  $icon='<i class="bi bi-tools" style="font-size:1.1rem;color:#f39c12;"></i>';              $sd='Maintenance';   $bc='maintenance';  break;
                    case 'in_use':       $icon='<i class="bi bi-person-workspace" style="font-size:1.1rem;color:#3498db;"></i>';   $sd='In Use';        $bc='in_use';       break;
                    case 'not_available':$icon='<i class="bi bi-x-lg" style="font-size:1.1rem;color:#e74c3c;"></i>';              $sd='Not Available'; $bc='not_available'; break;
                    default:             $icon='<i class="bi bi-pc-display-horizontal"></i>';                                       $sd=ucfirst($status);$bc=$status;
                }
            ?>
            <div class="pc-card <?=$bc?>" onclick="openPCEditModal('<?=$current_lab?>',<?=$i?>,'<?=$status?>','<?=htmlspecialchars($notes,ENT_QUOTES)?>')">
                <?=$icon?><span class="d-block mt-1 fw-bold"><?=$i?></span>
                <small style="font-size:.58rem;"><?=$sd?></small>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</div>
<!-- Edit PC Modal -->
<div class="modal fade" id="editPcModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header-purple d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="bi bi-pencil-fill me-2"></i>Edit PC Status</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST"><div class="modal-body p-4">
            <input type="hidden" name="lab_name" id="edit_lab_name">
            <input type="hidden" name="pc_number" id="edit_pc_number">
            <div class="text-center mb-3"><div style="font-size:2rem;">🖥️</div><h5 class="mb-0">PC #<span id="edit_pc_display"></span></h5><small class="text-muted" id="edit_current_status_label"></small></div>
            <div class="mb-3"><label style="font-size:.77rem;color:#777;">New Status</label><select name="status" id="edit_status" class="form-select" required><option value="available">🟢 Available</option><option value="maintenance">🟠 Maintenance</option><option value="not_available">🔴 Not Available</option></select></div>
            <div class="mb-3"><label style="font-size:.77rem;color:#777;">Notes</label><textarea name="notes" id="edit_notes" class="form-control" rows="2" placeholder="e.g., Broken keyboard, GPU failure..."></textarea></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="update_pc_status" class="btn btn-purple px-4"><i class="bi bi-save-fill me-1"></i>Update PC</button>
        </div></form>
    </div></div>
</div>
<div class="dash-card">
    <div class="card-header-purple"><span><i class="bi bi-clock-history me-2"></i>Recent PC Status Changes — Lab <?=htmlspecialchars($current_lab)?></span></div>
    <div class="card-body p-3">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="historyTable">
                <thead><tr><th>Date &amp; Time</th><th>PC #</th><th>Old Status</th><th>New Status</th><th>Notes</th><th>Changed By</th></tr></thead>
                <tbody>
                <?php
                $history = $conn->query("SELECT * FROM pc_status_history WHERE lab_name='$current_lab' ORDER BY changed_at DESC LIMIT 50");
                if ($history && $history->num_rows > 0): while ($h = $history->fetch_assoc()):
                    $status_badges = ['available'=>'bg-success','maintenance'=>'bg-warning text-dark','in_use'=>'bg-primary','not_available'=>'bg-danger','unknown'=>'bg-secondary'];
                    $ob = $status_badges[$h['old_status']] ?? 'bg-secondary';
                    $nb = $status_badges[$h['new_status']] ?? 'bg-secondary';
                ?>
                    <tr>
                        <td style="font-size:.78rem;white-space:nowrap;"><?=date('Y-m-d H:i:s',strtotime($h['changed_at']))?></td>
                        <td><strong>PC #<?=intval($h['pc_number'])?></strong></td>
                        <td><span class="badge <?=$ob?>"><?=ucfirst(str_replace('_',' ',$h['old_status']))?></span></td>
                        <td><span class="badge <?=$nb?>"><?=ucfirst(str_replace('_',' ',$h['new_status']))?></span></td>
                        <td style="max-width:200px;font-size:.78rem;"><?=htmlspecialchars($h['notes']?:'—')?></td>
                        <td style="font-size:.78rem;"><?=htmlspecialchars($h['changed_by'])?></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="6" class="text-center text-muted py-3"><i class="bi bi-inbox me-2"></i>No status changes recorded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ══════════════ SIT-IN FORM TAB ══════════════ -->
<?php elseif ($active_tab === 'sitinform'): ?>
<div class="row g-4 justify-content-center">
    <div class="col-lg-7">
        <div class="dash-card">
            <div class="card-header-purple"><span><i class="bi bi-search me-2"></i>Search &amp; Sit-in Student</span></div>
            <div class="card-body p-4">
                <div class="mb-3"><label style="font-size:.78rem;color:#777;">Search by ID Number or Name</label><input type="text" id="studentLookup" class="form-control" placeholder="e.g. 123456 or Juan Dela Cruz" autocomplete="off"></div>
                <div id="searchResults" style="display:none;" class="mb-3"><div style="font-size:.73rem;color:#999;margin-bottom:5px;">Select a student:</div><div id="resultsList"></div></div>
                <form method="POST" id="sitinForm" style="display:none;">
                    <div class="selected-student-card">
                        <div class="label">Selected Student</div>
                        <div class="row g-2">
                            <div class="col-sm-6"><div class="val-title">ID Number</div><div class="val" id="display_id"></div></div>
                            <div class="col-sm-6"><div class="val-title">Name</div><div class="val" id="display_name"></div></div>
                            <div class="col-sm-6"><div class="val-title">Course</div><div class="val" id="display_course"></div></div>
                            <div class="col-sm-6"><div class="val-title">Remaining Sessions</div><div class="val" id="display_remaining">—</div></div>
                        </div>
                    </div>
                    <input type="hidden" name="sitin_id" id="sitin_id">
                    <div class="mb-3"><label style="font-size:.78rem;color:#777;">Purpose</label><select name="sitin_purpose" class="form-select" required><option value="">-- Select Purpose --</option><option>C Programming</option><option>C++</option><option>Java</option><option>ASP.Net</option><option>PHP</option><option>Python</option><option>Other</option></select></div>
                    <div class="mb-3"><label style="font-size:.78rem;color:#777;">Lab</label><select name="sitin_lab" id="sitin_lab" class="form-select" required><option value="">-- Select Lab --</option><?php $lab_list=$conn->query("SELECT lab_name FROM lab_config ORDER BY lab_name"); while($lab=$lab_list->fetch_assoc()): ?><option value="<?=$lab['lab_name']?>">Lab <?=$lab['lab_name']?></option><?php endwhile; ?></select></div>
                    <div class="mb-4">
                        <label style="font-size:.78rem;color:#777;">Select PC</label>
                        <div id="pcSelectionArea"><div class="alert alert-info" id="pcSelectHint">Please select a lab first</div><div id="pcGridSelection" style="display:none;"><div class="d-flex gap-2 mb-2"><button type="button" class="btn btn-sm btn-outline-success" onclick="filterPCs('all')">All</button><button type="button" class="btn btn-sm btn-outline-success" onclick="filterPCs('available')">Available Only</button><button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearPCSelection()">Clear</button></div><div id="pcGridMini" style="display:grid;grid-template-columns:repeat(10,1fr);gap:5px;max-height:280px;overflow-y:auto;padding:10px;background:#f8f9fa;border-radius:10px;"></div></div></div>
                        <input type="hidden" name="pc_number" id="selected_pc">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary btn-sm px-3" onclick="resetSitinForm()"><i class="bi bi-x-circle me-1"></i>Cancel</button>
                        <button type="submit" name="do_sitin" class="btn btn-purple px-4" id="submitSitinBtn" disabled><i class="bi bi-box-arrow-in-right me-2"></i>Sit In</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════ CURRENT SIT-IN TAB ══════════════ -->
<?php elseif ($active_tab === 'sitin'): ?>
<div class="dash-card">
    <div class="card-header-purple"><span><i class="bi bi-display me-2"></i>Currently Active Sessions</span><button class="btn btn-sm btn-gold-action px-3" onclick="resetAllSessions()"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset All</button></div>
    <div class="card-body p-3">
        <table id="sitinTable" class="table table-bordered table-hover w-100">
            <thead><tr><th>Sit ID</th><th>ID Number</th><th>Name</th><th>Purpose</th><th>Lab</th><th>PC #</th><th>Login Time</th><th>Remaining</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php
            $sit = $conn->query("SELECT sr.*, CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS student_name, u.remaining_session FROM sitin_records sr LEFT JOIN users u ON sr.id_number COLLATE utf8mb4_unicode_ci = u.id_number COLLATE utf8mb4_unicode_ci WHERE sr.logout_time IS NULL ORDER BY sr.login_time DESC");
            if ($sit && $sit->num_rows > 0): while ($r = $sit->fetch_assoc()):
                $rem = intval($r['remaining_session'] ?? 30);
                $color = $rem > 10 ? '#27ae60' : ($rem > 5 ? '#f39c12' : '#e74c3c'); ?>
                <tr>
                    <td><?=$r['id']?></td><td><?=htmlspecialchars($r['id_number'])?></td>
                    <td><?=htmlspecialchars(trim($r['student_name'])?:'—')?></td>
                    <td><?=htmlspecialchars($r['purpose'])?></td><td><?=htmlspecialchars($r['lab'])?></td>
                    <td><?=$r['pc_number']?'<span class="badge bg-primary">#'.$r['pc_number'].'</span>':'—'?></td>
                    <td><?=htmlspecialchars($r['login_time'])?></td>
                    <td><span style="font-weight:700;color:<?=$color?>;"><?=$rem?></span></td>
                    <td><span class="badge-active">Active</span></td>
                    <td><button class="btn-logout-tbl" onclick="if(confirm('Log out this student?')){var f=document.createElement('form');f.method='POST';f.action='admin_dashboard.php?tab=sitin';var i1=document.createElement('input');i1.type='hidden';i1.name='sit_id';i1.value='<?=$r['id']?>';var i2=document.createElement('input');i2.type='hidden';i2.name='logout_session';i2.value='1';f.appendChild(i1);f.appendChild(i2);document.body.appendChild(f);f.submit();}"><i class="bi bi-box-arrow-right me-1"></i>Log out</button></td>
                </tr>
            <?php endwhile; else: ?><tr><td colspan="10" class="text-center text-muted py-3">No active sit-in sessions.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══════════════ RECORDS TAB ══════════════ -->
<?php elseif ($active_tab === 'records'): ?>
<div class="dash-card">
    <div class="card-header-purple"><span><i class="bi bi-table me-2"></i>All Sit-in Records</span></div>
    <div class="card-body p-3">
        <table id="recordTable" class="table table-bordered table-hover w-100">
            <thead><tr><th>Sit ID</th><th>ID Number</th><th>Name</th><th>Purpose</th><th>Lab</th><th>PC #</th><th>Login Time</th><th>Logout Time</th><th>Duration</th><th>Status</th></tr></thead>
            <tbody>
            <?php
            $rec = $conn->query("SELECT sr.*, CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS student_name FROM sitin_records sr LEFT JOIN users u ON sr.id_number COLLATE utf8mb4_unicode_ci = u.id_number COLLATE utf8mb4_unicode_ci ORDER BY sr.login_time DESC");
            if ($rec && $rec->num_rows > 0): while ($r = $rec->fetch_assoc()):
                $is_active = empty($r['logout_time']);
                $login_dt  = new DateTime($r['login_time']);
                $logout_dt = $is_active ? new DateTime() : new DateTime($r['logout_time']);
                $diff      = $login_dt->diff($logout_dt);
                $dur_str   = $is_active ? '<em style="color:#f39c12;">Ongoing</em>' : ($diff->h > 0 ? $diff->h.'h '.$diff->i.'m' : $diff->i.'m'); ?>
                <tr>
                    <td><?=$r['id']?></td><td><?=htmlspecialchars($r['id_number'])?></td>
                    <td><?=htmlspecialchars(trim($r['student_name'])?:'—')?></td>
                    <td><?=htmlspecialchars($r['purpose'])?></td><td><?=htmlspecialchars($r['lab'])?></td>
                    <td><?=$r['pc_number']?'#'.$r['pc_number']:'—'?></td>
                    <td><?=htmlspecialchars($r['login_time'])?></td>
                    <td><?=$is_active?'<span class="text-muted">—</span>':htmlspecialchars($r['logout_time'])?></td>
                    <td><?=$dur_str?></td>
                    <td><?=$is_active?'<span class="badge-active">Active</span>':'<span class="badge-done">Done</span>'?></td>
                </tr>
            <?php endwhile; else: ?><tr><td colspan="10" class="text-center text-muted py-3">No records found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══════════════ ANNOUNCEMENTS TAB ══════════════ -->
<?php elseif ($active_tab === 'announcements'): ?>
<div class="row g-4">
    <div class="col-md-5">
        <div class="dash-card">
            <div class="card-header-purple"><span><i class="bi bi-plus-circle-fill me-2"></i>Post New Announcement</span></div>
            <div class="card-body p-4">
                <form method="POST" action="admin_dashboard.php?tab=announcements">
                    <div class="mb-3"><label style="font-size:.78rem;color:#777;">Message</label><textarea name="announcement" class="announce-textarea" placeholder="Write your announcement..." required></textarea></div>
                    <button type="submit" name="post_announcement" class="btn btn-purple w-100"><i class="bi bi-send-fill me-2"></i>Post Announcement</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="dash-card">
            <div class="card-header-gold"><span><i class="bi bi-list-ul me-2"></i>Posted Announcements</span></div>
            <div class="card-body p-3">
                <?php $ann = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
                if ($ann && $ann->num_rows > 0): while ($a = $ann->fetch_assoc()):
                    $date = date('Y-M-d', strtotime($a['created_at'])); ?>
                    <div class="ann-item d-flex justify-content-between align-items-start">
                        <div><h6><?=htmlspecialchars($a['admin_name'])?> <small class="text-muted fw-normal" style="font-size:.73rem;">| <?=$date?></small></h6><p class="mb-0 text-muted" style="font-size:.8rem;"><?=htmlspecialchars($a['message'])?></p></div>
                        <form method="POST" onsubmit="return confirm('Delete this announcement?')" class="ms-3 flex-shrink-0">
                            <input type="hidden" name="ann_id" value="<?=$a['id']?>">
                            <button name="delete_announcement" class="btn btn-sm btn-danger" style="border-radius:8px;"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                <?php endwhile; else: ?><p class="text-muted mb-0" style="font-size:.83rem;">No announcements yet.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════ REPORTS TAB ══════════════ -->
<?php elseif ($active_tab === 'reports'): ?>
<div class="row g-4">
    <div class="col-lg-6"><div class="dash-card"><div class="card-header-purple"><span><i class="bi bi-pie-chart-fill me-2"></i>Sessions by Purpose</span></div><div class="card-body p-3"><div class="chart-wrapper"><canvas id="reportPurposeChart"></canvas></div></div></div></div>
    <div class="col-lg-6"><div class="dash-card"><div class="card-header-purple"><span><i class="bi bi-bar-chart-fill me-2"></i>Sessions by Lab</span></div><div class="card-body p-3"><div class="chart-wrapper"><canvas id="reportLabChart"></canvas></div></div></div></div>
</div>
<?php
$lab_result = $conn->query("SELECT lab,COUNT(*) as cnt FROM sitin_records GROUP BY lab");
$lab_labels = []; $lab_data = [];
if ($lab_result) while ($row = $lab_result->fetch_assoc()) { $lab_labels[] = $row['lab']; $lab_data[] = $row['cnt']; }
?>
<script>
new Chart(document.getElementById('reportPurposeChart'),{type:'doughnut',data:{labels:<?=json_encode($lang_labels?:['No Data'])?>,datasets:[{data:<?=json_encode($lang_data?:[1])?>,backgroundColor:['#9757d6','#a1cbf7','#27ae60','#f39c12','#e74c3c','#3498db','#FFD700'],borderWidth:2,borderColor:'#fff'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{font:{family:'Poppins',size:11},boxWidth:14}}}}});
new Chart(document.getElementById('reportLabChart'),{type:'bar',data:{labels:<?=json_encode($lab_labels?:['No Data'])?>,datasets:[{label:'Sessions',data:<?=json_encode($lab_data?:[0])?>,backgroundColor:'#9757d6',borderRadius:8}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}});
</script>

<!-- ══════════════ SOFTWARE TAB ══════════════ -->
<?php elseif ($active_tab === 'software'): ?>
<div class="row g-4">
    <div class="col-md-4">
        <div class="dash-card">
            <div class="card-header-purple"><span><i class="bi bi-plus-circle-fill me-2"></i>Add Software</span></div>
            <div class="card-body p-3">
                <form method="POST">
                    <div class="mb-2">
                        <label style="font-size:.77rem;color:#777;">Lab</label>
                        <select name="sw_lab" class="form-select" required>
                            <option value="">-- Select Lab --</option>
                            <?php $sw_labs = $conn->query("SELECT lab_name FROM lab_config ORDER BY lab_name"); while($sl=$sw_labs->fetch_assoc()): ?>
                            <option value="<?=$sl['lab_name']?>">Lab <?=$sl['lab_name']?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-2"><label style="font-size:.77rem;color:#777;">Software Name</label><input type="text" name="sw_name" class="form-control" placeholder="e.g. Visual Studio Code" required></div>
                    <div class="mb-2"><label style="font-size:.77rem;color:#777;">Version</label><input type="text" name="sw_version" class="form-control" placeholder="e.g. 1.85"></div>
                    <div class="mb-2"><label style="font-size:.77rem;color:#777;">Category</label><input type="text" name="sw_category" class="form-control" placeholder="e.g. IDE, Browser, Office"></div>
                    <div class="mb-3 form-check"><input type="checkbox" name="sw_available" class="form-check-input" id="sw_avail" checked><label class="form-check-label" for="sw_avail" style="font-size:.83rem;">Available</label></div>
                    <button type="submit" name="add_software" class="btn btn-purple w-100"><i class="bi bi-plus-lg me-1"></i>Add Software</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="dash-card h-100">
            <div class="card-header-purple"><span><i class="bi bi-app-indicator me-2"></i>Lab Software List</span></div>
            <div class="card-body p-3">
                <table id="softwareTable" class="table table-bordered table-hover w-100">
                    <thead><tr><th>Lab</th><th>Software</th><th>Version</th><th>Category</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php
                    $sw_all = $conn->query("SELECT * FROM lab_software ORDER BY lab_name, software_name");
                    if ($sw_all && $sw_all->num_rows > 0): while ($sw = $sw_all->fetch_assoc()): ?>
                        <tr>
                            <td>Lab <?=htmlspecialchars($sw['lab_name'])?></td>
                            <td><?=htmlspecialchars($sw['software_name'])?></td>
                            <td><?=htmlspecialchars($sw['version']?:'—')?></td>
                            <td><?=htmlspecialchars($sw['category']?:'—')?></td>
                            <td><?=$sw['is_available']?'<span class="sw-badge-avail"><i class="bi bi-check-circle-fill me-1"></i>Available</span>':'<span class="sw-badge-no"><i class="bi bi-x-circle-fill me-1"></i>Not Available</span>'?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="sw_id" value="<?=$sw['id']?>">
                                    <button name="toggle_software" class="btn btn-sm <?=$sw['is_available']?'btn-warning':'btn-success'?>" style="font-size:.7rem;padding:2px 8px;"><?=$sw['is_available']?'Disable':'Enable'?></button>
                                </form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this software?')">
                                    <input type="hidden" name="sw_id" value="<?=$sw['id']?>">
                                    <button name="delete_software" class="btn btn-sm btn-danger" style="font-size:.7rem;padding:2px 8px;"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; else: ?><tr><td colspan="6" class="text-center text-muted py-3"><i class="bi bi-inbox me-2"></i>No software added yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════ FEEDBACK TAB ══════════════ -->
<?php elseif ($active_tab === 'feedback'): ?>
<div class="dash-card">
    <div class="card-header-purple"><span><i class="bi bi-chat-left-text-fill me-2"></i>Student Feedback</span></div>
    <div class="card-body p-3">
        <?php
        $feedback_check = $conn->query("SHOW TABLES LIKE 'feedback'");
        if ($feedback_check && $feedback_check->num_rows > 0):
            $fb = $conn->query("SELECT f.*, CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS student_name, sr.purpose,sr.lab,sr.login_time FROM feedback f LEFT JOIN users u ON f.id_number=u.id_number LEFT JOIN sitin_records sr ON f.sitin_id=sr.id ORDER BY f.created_at DESC");
            if ($fb && $fb->num_rows > 0): ?>
            <table id="feedbackTable" class="table table-bordered table-hover w-100">
                <thead><tr><th>#</th><th>Student</th><th>ID Number</th><th>Lab</th><th>Purpose</th><th>Session Date</th><th>Feedback</th><th>Submitted</th></tr></thead>
                <tbody>
                <?php while ($f = $fb->fetch_assoc()): ?>
                    <tr>
                        <td><?=$f['id']?></td><td><?=htmlspecialchars(trim($f['student_name'])?:'—')?></td><td><?=htmlspecialchars($f['id_number'])?></td>
                        <td><?=htmlspecialchars($f['lab']??'—')?></td><td><?=htmlspecialchars($f['purpose']??'—')?></td><td><?=htmlspecialchars($f['login_time']??'—')?></td>
                        <td style="max-width:240px;"><div style="font-size:.81rem;color:#333;font-style:italic;">"<?=htmlspecialchars($f['message'])?>"</div></td>
                        <td><?=htmlspecialchars($f['created_at'])?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?><p class="text-muted mb-0"><i class="bi bi-inbox me-2"></i>No feedback submitted yet.</p><?php endif;
        else: ?><p class="text-muted mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Feedback table not found.</p><?php endif; ?>
    </div>
</div>

<!-- ══════════════ RESERVATION TAB ══════════════ -->
<?php elseif ($active_tab === 'reservation'): ?>

<!-- Reservation Toggle Banner -->
<div class="res-status-banner <?=$reservations_enabled?'enabled':'disabled'?>">
    <div>
        <?php if ($reservations_enabled): ?>
            <i class="bi bi-unlock-fill me-2"></i>Reservations are currently <strong>ENABLED</strong> — students can submit reservations.
        <?php else: ?>
            <i class="bi bi-lock-fill me-2"></i>Reservations are currently <strong>DISABLED</strong> — students cannot submit new reservations.
        <?php endif; ?>
    </div>
    <form method="POST" onsubmit="return confirm('<?=$reservations_enabled?'Disable reservations? Students will not be able to book.':'Enable reservations?'?>')">
        <button name="toggle_reservations" class="btn btn-sm <?=$reservations_enabled?'btn-danger':'btn-success'?> px-3" style="font-weight:700;">
            <?php if ($reservations_enabled): ?>
                <i class="bi bi-lock-fill me-1"></i>Disable Reservations
            <?php else: ?>
                <i class="bi bi-unlock-fill me-1"></i>Enable Reservations
            <?php endif; ?>
        </button>
    </form>
</div>

<div class="dash-card">
    <div class="card-header-purple"><span><i class="bi bi-calendar-check-fill me-2"></i>Student Reservations</span></div>
    <div class="card-body p-3">
        <table id="reservationTable" class="table table-bordered table-hover w-100">
            <thead><tr><th>ID</th><th>Student ID</th><th>Purpose</th><th>Lab</th><th>PC #</th><th>Time Slot</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php
            $res = $conn->query("SELECT r.*, CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) as student_name FROM reservations r LEFT JOIN users u ON r.id_number=u.id_number ORDER BY r.id DESC");
            if ($res && $res->num_rows > 0): while ($r = $res->fetch_assoc()):
            ?>
                <tr>
                    <td><?=$r['id']?></td>
                    <td><div style="font-weight:600;font-size:.82rem;"><?=htmlspecialchars($r['id_number'])?></div><div style="font-size:.72rem;color:#aaa;"><?=htmlspecialchars(trim($r['student_name'])?:'—')?></div></td>
                    <td><?=htmlspecialchars($r['purpose'])?></td><td><?=htmlspecialchars($r['lab'])?></td>
                    <td><?=!empty($r['seat_number'])?'<strong style="color:var(--ccs-purple);">#'.$r['seat_number'].'</strong>':'—'?></td>
                    <td style="font-size:.78rem;"><?=htmlspecialchars($r['preferred_time'])?></td>
                    <td><?=htmlspecialchars($r['reservation_date'])?></td>
                    <td>
                        <?php $st = strtolower($r['status']);
                        if ($st==='pending') echo '<span class="badge-pending">Pending</span>';
                        elseif ($st==='approved') echo '<span class="badge-approved">Approved</span>';
                        elseif ($st==='rejected') echo '<span class="badge-rejected">Rejected</span>';
                        else echo '<span class="badge-done">'.htmlspecialchars($r['status']).'</span>'; ?>
                    </td>
                    <td>
                        <?php if ($st === 'pending'): ?>
                        <form method="POST" style="display:inline;"><input type="hidden" name="res_id" value="<?=$r['id']?>"><input type="hidden" name="status" value="approved"><button name="update_reservation" class="btn btn-success btn-sm"><i class="bi bi-check-lg"></i></button></form>
                        <form method="POST" style="display:inline;"><input type="hidden" name="res_id" value="<?=$r['id']?>"><input type="hidden" name="status" value="rejected"><button name="update_reservation" class="btn btn-danger btn-sm"><i class="bi bi-x-lg"></i></button></form>
                        <?php else: ?><span class="text-muted" style="font-size:.78rem;">—</span><?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; else: ?><tr><td colspan="9" class="text-center text-muted py-3">No reservations found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

    </div><!-- end content-area -->
</div><!-- end main-content -->

<footer>&copy; <?=date('Y')?> College of Computer Studies | CCS Sit-in Monitoring System</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let currentPCFilter = 'all';
let currentPCMap    = {};

function confirmLogout(e) {
    e.preventDefault();
    Swal.fire({ title:'Logging out?', text:'Are you sure?', icon:'warning', showCancelButton:true, confirmButtonText:'<i class="bi bi-box-arrow-right me-1"></i> Yes, Logout', cancelButtonText:'Cancel', confirmButtonColor:'#9757d6', cancelButtonColor:'#6c757d' })
    .then(r => { if (r.isConfirmed) Swal.fire({ title:'Logged out!', icon:'success', timer:1500, showConfirmButton:false }).then(() => { window.location.href='landingpage.php'; }); });
}

$(document).ready(function() {
    const dtOpts = { pageLength:10, language:{ emptyTable:'No data available', zeroRecords:'No matching records found' } };
    $.fn.dataTable.ext.errMode = 'none';
    if ($('#studentTable').length)     $('#studentTable').DataTable({ ...dtOpts, order:[[0,'asc']], columnDefs:[{targets:-1,orderable:false,searchable:false}] });
    if ($('#sitinTable').length)       $('#sitinTable').DataTable({ ...dtOpts, order:[[0,'desc']], columnDefs:[{targets:-1,orderable:false,searchable:false}] });
    if ($('#recordTable').length)      $('#recordTable').DataTable({ ...dtOpts, order:[[0,'desc']] });
    if ($('#reservationTable').length) $('#reservationTable').DataTable({ ...dtOpts, order:[[0,'desc']] });
    if ($('#feedbackTable').length)    $('#feedbackTable').DataTable({ ...dtOpts, order:[[0,'desc']] });
    if ($('#historyTable').length)     $('#historyTable').DataTable({ pageLength:25, order:[[0,'desc']] });
    if ($('#softwareTable').length)    $('#softwareTable').DataTable({ ...dtOpts, order:[[0,'asc']] });
});

function openPCEditModal(lab, pc, status, notes) {
    document.getElementById('edit_lab_name').value  = lab;
    document.getElementById('edit_pc_number').value = pc;
    document.getElementById('edit_pc_display').textContent = pc;
    document.getElementById('edit_status').value    = status;
    document.getElementById('edit_notes').value     = notes;
    const statusLabels = { available:'✅ Currently: Available', maintenance:'🔧 Currently: Maintenance', not_available:'❌ Currently: Not Available', in_use:'🔵 Currently: In Use' };
    document.getElementById('edit_current_status_label').textContent = statusLabels[status] || 'Currently: ' + status;
    new bootstrap.Modal(document.getElementById('editPcModal')).show();
}

// Student search
const lookup = document.getElementById('studentLookup');
if (lookup) {
    let debounce;
    lookup.addEventListener('input', function() {
        clearTimeout(debounce);
        const q = this.value.trim();
        if (q.length < 2) { document.getElementById('searchResults').style.display='none'; return; }
        debounce = setTimeout(() => {
            fetch('admin_dashboard.php?search_student='+encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    const list = document.getElementById('resultsList');
                    const box  = document.getElementById('searchResults');
                    list.innerHTML = !data.length ? '<div style="font-size:.83rem;color:#999;padding:7px;">No students found.</div>' :
                        data.map(s => `<div class="result-item" onclick="selectStudent('${s.id_number}','${s.first_name}','${s.last_name}','${s.course}','${s.year_level}','${s.remaining_session??30}')"><div class="r-name">${s.first_name} ${s.last_name}</div><div class="r-sub">${s.id_number} — ${s.course}, Year ${s.year_level} — Sessions: <strong>${s.remaining_session??30}</strong></div></div>`).join('');
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
    const rem   = parseInt(remaining) || 0;
    const color = rem > 10 ? '#27ae60' : (rem > 5 ? '#f39c12' : '#e74c3c');
    const el    = document.getElementById('display_remaining');
    el.innerText = rem; el.style.color = color;
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('studentLookup').value = first + ' ' + last;
    document.getElementById('sitinForm').style.display = 'block';
    const labSelect = document.getElementById('sitin_lab');
    if (labSelect && labSelect.value) loadPCsForLab(labSelect.value);
}

const sitinLabEl = document.getElementById('sitin_lab');
if (sitinLabEl) {
    sitinLabEl.addEventListener('change', function() {
        if (this.value) loadPCsForLab(this.value);
        else { document.getElementById('pcGridSelection').style.display='none'; document.getElementById('pcSelectHint').style.display='block'; }
    });
}

function loadPCsForLab(lab) {
    fetch('admin_dashboard.php?get_pc_status&lab='+encodeURIComponent(lab))
        .then(r => r.json())
        .then(data => {
            currentPCMap = {};
            data.forEach(pc => { currentPCMap[pc.pc_number] = pc; });
            renderSitinPCGrid(data.length || 50);
            document.getElementById('pcSelectHint').style.display = 'none';
            document.getElementById('pcGridSelection').style.display = 'block';
        });
}

function renderSitinPCGrid(totalPCs) {
    const grid = document.getElementById('pcGridMini');
    let html = '';
    for (let i = 1; i <= totalPCs; i++) {
        const pc = currentPCMap[i];
        const status = pc ? pc.status : 'available';
        const isAvail = status === 'available';
        const selected = document.getElementById('selected_pc').value == i;
        if (currentPCFilter === 'available' && !isAvail) continue;
        const sc = isAvail ? 'available' : (status === 'maintenance' ? 'maintenance' : 'not_available');
        html += `<div style="text-align:center;padding:6px 4px;border-radius:7px;cursor:${isAvail?'pointer':'not-allowed'};font-weight:700;font-size:.7rem;background:${isAvail?'linear-gradient(135deg,#d5f5e3,#a9dfbf)':(status==='maintenance'?'linear-gradient(135deg,#fdebd0,#f8c471)':'linear-gradient(135deg,#fadbd8,#f1948a)')};border:2px solid ${selected?'#9757d6':(isAvail?'#82c99a':(status==='maintenance'?'#f39c12':'#e57373'))};${selected?'box-shadow:0 0 0 3px rgba(151,87,214,.3);':''}" onclick="${isAvail?`selectSitinPC(${i})`:``}">
            <i class="bi bi-pc-display-horizontal d-block mb-1" style="font-size:.9rem;"></i>${i}</div>`;
    }
    grid.innerHTML = html;
}

function selectSitinPC(n) {
    document.getElementById('selected_pc').value = n;
    document.getElementById('submitSitinBtn').disabled = false;
    renderSitinPCGrid(Object.keys(currentPCMap).length || 50);
    Swal.fire({ title:'PC Selected', text:`PC #${n} selected.`, icon:'success', timer:1200, showConfirmButton:false });
}

function filterPCs(f) { currentPCFilter = f; renderSitinPCGrid(Object.keys(currentPCMap).length || 50); }
function clearPCSelection() {
    document.getElementById('selected_pc').value = '';
    document.getElementById('submitSitinBtn').disabled = true;
    renderSitinPCGrid(Object.keys(currentPCMap).length || 50);
}

function openAddModal() { new bootstrap.Modal(document.getElementById('addStudentModal')).show(); }
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
    var f = document.createElement('form'); f.method='POST'; f.action='admin_dashboard.php?tab=sitin';
    var i = document.createElement('input'); i.type='hidden'; i.name='reset_all_sessions'; i.value='1';
    f.appendChild(i); document.body.appendChild(f); f.submit();

}
function resetSitinForm() {
    document.getElementById('sitinForm').style.display='none';
    document.getElementById('studentLookup').value='';
    document.getElementById('searchResults').style.display='none';
    document.getElementById('selected_pc').value='';
    document.getElementById('submitSitinBtn').disabled=true;
    document.getElementById('pcGridSelection').style.display='none';
    document.getElementById('pcSelectHint').style.display='block';
}
</script>
</body>
</html>