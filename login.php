<?php
session_start();

$admin_username = "admin";
$admin_password = "admin123";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_number = trim($_POST['id_number']);
    $password  = trim($_POST['password']);

    if ($id_number === $admin_username && $password === $admin_password) {
        $_SESSION['admin'] = $admin_username;
        $_SESSION['login_success'] = [
            'name'     => 'Admin',
            'redirect' => 'admin_dashboard.php'
        ];
        header("Location: login.php");
        exit();
    } else {
        $conn = new mysqli("localhost", "root", "", "sysarch");
        if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

        $stmt = $conn->prepare("SELECT * FROM users WHERE id_number = ?");
        $stmt->bind_param("s", $id_number);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user']      = $user['id_number'];
            $_SESSION['user_data'] = $user;
            $_SESSION['login_success'] = [
                'name'     => $user['first_name'],
                'redirect' => 'students_dashboard.php'
            ];
            header("Location: login.php");
            exit();
        } else {
            $error = 'invalid';
        }

        $stmt->close();
        $conn->close();
    }
}

$showSuccess = isset($_GET['success']) && $_GET['success'] == '1';
$error       = isset($error) ? $error : (isset($_GET['error']) ? $_GET['error'] : '');

$login_success = null;
if (isset($_SESSION['login_success'])) {
    $login_success = $_SESSION['login_success'];
    unset($_SESSION['login_success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — CCS Sit-in Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--gray-50);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── NAVBAR ── */
        .navbar {
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            box-shadow: 0 1px 10px rgba(37,99,235,0.07);
            height: 64px;
            padding: 0;
        }
        .navbar .container { height: 100%; }
        .navbar-brand {
            font-weight: 700;
            font-size: .9rem;
            color: var(--gray-800) !important;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -0.01em;
        }

        /* ── MAIN LAYOUT ── */
        .page-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .login-shell {
            display: flex;
            width: 100%;
            max-width: 900px;
            min-height: 520px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(37,99,235,0.13);
            border: 1px solid var(--gray-200);
        }

        /* ── LEFT PANEL ── */
        .login-left {
            flex: 1;
            background: linear-gradient(145deg, var(--blue-deeper) 0%, var(--blue) 60%, var(--blue-mid) 100%);
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        .login-left::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
        }
        .login-left-inner { position: relative; z-index: 1; }
    
        .login-left h2 {
            color: var(--white);
            font-size: 1.55rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.25;
            margin-bottom: 12px;
        }
        .login-left p {
            color: rgba(255,255,255,0.7);
            font-size: .82rem;
            line-height: 1.7;
        }
        .login-left-features {
            margin-top: 32px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .lf-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255,255,255,0.85);
            font-size: .8rem;
            font-weight: 500;
        }
        .lf-dot {
            width: 28px; height: 28px;
            background: rgba(255,255,255,0.15);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: .85rem;
            flex-shrink: 0;
        }
        .login-left-footer {
            color: rgba(255,255,255,0.4);
            font-size: .72rem;
            position: relative;
            z-index: 1;
        }

        /* ── RIGHT PANEL ── */
        .login-right {
            flex: 1;
            background: var(--white);
            padding: 48px 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-right-tag {
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--blue);
            margin-bottom: 8px;
        }
        .login-right h3 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--gray-800);
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }
        .login-right .login-sub {
            font-size: .83rem;
            color: var(--gray-400);
            margin-bottom: 28px;
        }

        /* Form elements */
        .field-group { margin-bottom: 18px; }
        .field-label {
            display: block;
            font-size: .78rem;
            font-weight: 600;
            color: var(--gray-600);
            margin-bottom: 6px;
        }
        .field-input {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid var(--gray-200);
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: .85rem;
            color: var(--gray-800);
            background: var(--gray-50);
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }
        .field-input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
            background: var(--white);
        }
        .field-input::placeholder { color: var(--gray-400); }

        /* Password wrapper */
        .pw-wrap { position: relative; }
        .pw-toggle {
            position: absolute; right: 12px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--gray-400); font-size: .85rem; padding: 0;
            line-height: 1;
        }
        .pw-toggle:hover { color: var(--blue); }

        .btn-signin {
            width: 100%;
            padding: 12px;
            background: var(--blue);
            color: var(--white);
            border: none;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: .9rem;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s, transform .15s, box-shadow .2s;
            margin-top: 6px;
            letter-spacing: 0.01em;
        }
        .btn-signin:hover {
            background: var(--blue-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(37,99,235,0.3);
        }
        .btn-signin:active { transform: translateY(0); }

        .login-divider {
            display: flex; align-items: center; gap: 12px;
            margin: 20px 0;
            color: var(--gray-400); font-size: .75rem;
        }
        .login-divider::before, .login-divider::after {
            content: ''; flex: 1; height: 1px; background: var(--gray-200);
        }

        .login-right-footer {
            margin-top: 20px;
            text-align: center;
            font-size: .8rem;
            color: var(--gray-600);
        }
        .login-right-footer a {
            color: var(--blue);
            font-weight: 600;
            text-decoration: none;
        }
        .login-right-footer a:hover { text-decoration: underline; }

        .btn-back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--gray-400);
            font-size: .78rem;
            font-weight: 500;
            text-decoration: none;
            margin-bottom: 24px;
            transition: color .2s;
        }
        .btn-back-link:hover { color: var(--blue); }

        /* Alerts */
        .alert-custom {
            border-radius: 10px;
            padding: 10px 14px;
            font-size: .8rem;
            font-weight: 500;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-success-custom { background: #DCFCE7; color: #166534; border: 1px solid #BBF7D0; }
        .alert-error-custom   { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }

        /* Footer */
        footer {
            background: var(--gray-800);
            color: var(--gray-400);
            text-align: center;
            padding: 18px;
            font-size: .75rem;
        }

        @media (max-width: 768px) {
            .login-left { display: none; }
            .login-shell { max-width: 440px; border-radius: 20px; }
            .login-right { padding: 36px 28px; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="landingpage.php">
            <img src="uclogo.png" alt="UC Logo" height="34">
            CCS Sit-in Monitoring
        </a>
    </div>
</nav>

<!-- MAIN -->
<div class="page-wrapper">
    <div class="login-shell">

        <!-- LEFT PANEL -->
        <div class="login-left">
            <div class="login-left-inner">
                <h2>CCS Sit-in<br>Monitoring System</h2>
                <p>University of Cebu — College of Computer Studies. Manage and monitor laboratory sessions with ease.</p>
                <div class="login-left-features">
                    <div class="lf-item"><div class="lf-dot">💻</div>Real-time lab monitoring</div>
                    <div class="lf-item"><div class="lf-dot">📊</div>Session analytics & reports</div>
                    <div class="lf-item"><div class="lf-dot">📅</div>PC reservation system</div>
                    <div class="lf-item"><div class="lf-dot">🔒</div>Secure role-based access</div>
                </div>
            </div>
            <div class="login-left-footer">
                &copy; <?= date('Y') ?> University of Cebu — CCS
            </div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="login-right">
            <a href="landingpage.php" class="btn-back-link">← Back to Home</a>

            <div class="login-right-tag">Student &amp; Admin Portal</div>
            <h3>Welcome back</h3>
            <div class="login-sub">Sign in to your account to continue</div>

            <?php if ($showSuccess): ?>
                <div class="alert-custom alert-success-custom">✅ Registration successful! Please log in.</div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert-custom alert-error-custom">
                    ⚠️
                    <?php
                        if ($error === 'invalid')    echo 'Invalid ID number or password. Please try again.';
                        elseif ($error === 'nouser') echo 'No account found with that ID.';
                        else echo htmlspecialchars($error);
                    ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="field-group">
                    <label class="field-label">ID Number</label>
                    <input type="text" name="id_number" class="field-input" placeholder="Enter your ID number" required autofocus>
                </div>
                <div class="field-group">
                    <label class="field-label">Password</label>
                    <div class="pw-wrap">
                        <input type="password" name="password" id="pwField" class="field-input" placeholder="Enter your password" required style="padding-right:40px;">
                        <button type="button" class="pw-toggle" onclick="togglePw()" id="pwToggleBtn" title="Show/hide password">👁</button>
                    </div>
                </div>
                <button type="submit" class="btn-signin">Sign In →</button>
            </form>

            <div class="login-divider">or</div>

            <div class="login-right-footer">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </div>

    </div>
</div>

<!-- FOOTER -->
<footer>&copy; <?= date('Y') ?> University of Cebu — College of Computer Studies</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePw() {
    const f = document.getElementById('pwField');
    const b = document.getElementById('pwToggleBtn');
    if (f.type === 'password') { f.type = 'text'; b.textContent = '🙈'; }
    else { f.type = 'password'; b.textContent = '👁'; }
}
</script>

<?php if ($login_success): ?>
<script>
Swal.fire({
    title: 'Welcome back! 👋',
    text: 'Hello, <?= htmlspecialchars($login_success['name']) ?>! Redirecting you now...',
    icon: 'success',
    timer: 2000,
    showConfirmButton: false,
    timerProgressBar: true,
    confirmButtonColor: '#2563EB',
}).then(() => {
    window.location.href = '<?= htmlspecialchars($login_success['redirect']) ?>';
});
</script>
<?php endif; ?>

</body>
</html>
