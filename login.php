<?php
$nav_items = [
'Home' => 'landingpage.php',
'About' => 'about.php'
];

$showSuccess = isset($_GET['success']) && $_GET['success'] == '1';
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - CCS Sit-in Monitoring System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root {
--uc-blue: #a1cbf7;
--ccs-purple: #4b2a6d;
}
body {
font-family: 'Poppins', sans-serif;
background-color: #f4f7f6;
margin: 0;
}
.navbar {
background-color: var(--uc-blue);
box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.navbar-brand {
font-weight: 300;
color: white !important;
font-size: 0.9rem;
}
.login-container {
max-width: 520px;
margin: 60px auto;
}
.login-card {
background: white;
border-radius: 20px;
padding: 36px;
box-shadow: 0 15px 35px rgba(0,0,0,0.08);
border: 1px solid rgba(0,0,0,0.05);
}
.btn-back {
background-color: #dc3545;
color: white;
border: none;
padding: 5px 15px;
border-radius: 5px;
text-decoration: none;
font-size: 0.8rem;
}
.form-label {
font-weight: 600;
color: var(--ccs-purple);
margin-top: 12px;
}
.form-control {
border-radius: 8px;
border: 1px solid #ced4da;
padding: 10px;
}
.btn-login {
background-color: #007bff;
color: white;
font-weight: 600;
width: 100%;
padding: 12px;
border-radius: 8px;
margin-top: 20px;
border: none;
}
.btn-login:hover {
background-color: #0056b3;
}
@media
(max-width: 768px) {
.login-container { margin: 20px; }
.login-card { padding: 20px; } }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
<div class="container">
<a class="navbar-brand d-flex align-items-center" href="landingpage.php">
<img src="uclogo.png" alt="UC Logo" height="30" class="me-2">
<span>CCS Sit-in Monitoring</span>
</a>
</div>
</nav>

<main class="container">
<div class="login-container">
<div class="login-card">
<a href="landingpage.php" class="btn-back">Back</a>
<?php if ($showSuccess): ?>
<div class="alert alert-success mt-3" role="alert">Registration successful. Please log in.</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger mt-3" role="alert">
<?php if ($error === 'invalid') echo 'Invalid credentials.'; elseif ($error === 'nouser') echo 'No account found with that email.'; else echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<h2 class="text-center fw-bold mb-4" style="color: var(--ccs-purple);">Log in</h2>

<form action="userprofile.php" method="POST">
<div class="mb-3">
<label class="form-label">Email Address</label>
<input type="email" class="form-control" name="email" placeholder="example@email.com" required>
</div>

<div class="mb-3">
<label class="form-label">Password</label>
<input type="password" class="form-control" name="password" minlength="6" required>
</div>

<button type="submit" class="btn-login">Sign in</button>
</form>

<p class="mt-3 small text-center">Don't have an account? <a href="register.php">Register here</a>.</p>
</div>
</div>
</main>

<footer class="text-center py-4">
<p class="small text-muted">&copy; <?php echo date("Y"); ?> College of Computer Studies</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>