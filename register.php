<?php
$nav_items = [
'Home' => 'landingpage.php',
'About' => 'about.php'
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - CCS Sit-in Monitoring System</title>
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

.registration-container {
max-width: 600px;
margin: 50px auto;
}

.registration-card {
background: white;
border-radius: 20px;
padding: 40px;
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
transition: 0.3s;
}

.btn-back:hover {
background-color: #a71d2a;
color: white;
}

.form-label {
font-weight: 600;
color: var(--ccs-purple);
margin-top: 15px;
}

.form-control {
border-radius: 8px;
border: 1px solid #ced4da;
padding: 10px;
}

.btn-register-submit {
background-color: #007bff;
color: white;
font-weight: 600;
width: 100%;
padding: 12px;
border-radius: 8px;
margin-top: 30px;
border: none;
transition: 0.3s;
}

.btn-register-submit:hover {
background-color: #0056b3;
transform: translateY(-2px);
}

@media (max-width: 768px) {
.registration-container { margin: 20px; }
.registration-card { padding: 25px; }
}
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
<div class="registration-container">
<div class="registration-card">
<a href="landingpage.php" class="btn-back">Back</a>
<?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
<div class="alert alert-success mt-3" role="alert">
Registration successful. You may now <a href="landingpage.php" class="alert-link">return</a>.
</div>
<?php endif; ?>

<h2 class="text-center fw-bold mb-4" style="color: var(--ccs-purple);">Sign up</h2>

<form action="process_registration.php" method="POST">
<div class="mb-3">
<label class="form-label">ID Number</label>
<input type="text" class="form-control" name="id_number" placeholder="Enter ID Number" required>
</div>

<div class="row">
<div class="col-md-6 mb-3">
<label class="form-label">Last Name</label>
<input type="text" class="form-control" name="last_name" required>
</div>
<div class="col-md-6 mb-3">
<label class="form-label">First Name</label>
<input type="text" class="form-control" name="first_name" required>
</div>
</div>

<div class="mb-3">
<label class="form-label">Middle Name</label>
<input type="text" class="form-control" name="middle_name">
</div>

<div class="mb-3">
<label class="form-label">Course</label>
<select class="form-select form-control" name="course" required>
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

<div class="mb-3">
<label class="form-label">Year Level</label>
<select class="form-select form-control" name="year_level" required>
<option value="1">1</option>
<option value="2">2</option>
<option value="3">3</option>
<option value="4">4</option>
</select>
</div>

<div class="mb-3">
<label class="form-label">Email Address</label>
<input type="email" class="form-control" name="email" placeholder="example@email.com" required>
</div>

<div class="mb-3">
<label class="form-label">Password</label>
<input type="password" class="form-control" name="password" minlength="6" required>
<div class="form-text">Password must be at least 6 characters.</div>
</div>

<div class="mb-3">
<label class="form-label">Repeat your password</label>
<input type="password" class="form-control" name="confirm_password" minlength="6" required>
</div>

<div class="mb-3">
<label class="form-label">Address</label>
<textarea class="form-control" name="address" rows="2" required></textarea>
</div>

<button type="submit" class="btn-register-submit">Register</button>
</form>
</div>
</div>
</main>

<footer class="text-center py-4">
<p class="small text-muted">&copy; <?php echo date("Y"); ?> College of Computer Studies</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>