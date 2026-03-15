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
<title>CCS Sit-in Monitoring System</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>

:root{
--uc-blue:#a1cbf7;
--ccs-purple:#9757d6;
--ccs-gold:#FFD700;
}

body{
font-family:'Poppins',sans-serif;
background-color:#f4f7f6;
margin:0;
overflow-x:hidden;
}

.navbar{
background-color:var(--uc-blue);
box-shadow:0 4px 12px rgba(0,0,0,0.15);
padding:10px;
}

.navbar-brand{
font-weight:300;
color:white !important;
letter-spacing:0.5px;
font-size:0.9rem;
white-space:normal;
}

.nav-link{
color:rgba(43,94,124,0.9) !important;
font-weight:500;
transition:0.3s;
}

.hero-section{
background:linear-gradient(135deg,var(--ccs-purple) 0%,var(--uc-blue) 100%);
color:white;
padding:60px 20px 80px 20px;
border-bottom-left-radius:40px;
border-bottom-right-radius:40px;
text-align:center;
}

.logo-container img{
max-height:120px;
filter:drop-shadow(0 5px 15px rgba(0,0,0,0.2));
transition:transform 0.4s cubic-bezier(0.175,0.885,0.32,1.275);
}

.logo-container img:hover{
transform:scale(1.1) rotate(2deg);
}

@media(max-width:768px){

.navbar-brand span{
display:none;
}

.navbar-brand::after{
content:"CCS Sit-in";
font-weight:300;
}

.logo-container{
gap:20px !important;
}

.logo-container img{
max-height:70px;
}

.system-title{
font-size:1.8rem !important;
}

.welcome-card{
padding:30px 20px !important;
margin-top:-40px !important;
margin-left:15px;
margin-right:15px;
}

.btn-login,.btn-register{
margin:5px 0 !important;
width:100%;
}

}

@media(min-width:769px){
.navbar-brand{
font-size:1.1rem;
}
}

.btn-login,.btn-register{
border:2px solid white !important;
color:white !important;
font-weight:600;
border-radius:8px;
margin-left:10px;
}

.btn-login:hover,.btn-register:hover{
background:white;
color:var(--uc-blue) !important;
}

.welcome-card{
background:white;
border-radius:25px;
padding:50px;
margin-top:-60px;
box-shadow:0 15px 35px rgba(0,0,0,0.08);
border:1px solid rgba(0,0,0,0.05);
}

.system-title{
font-weight:800;
text-transform:uppercase;
color:white;
margin-top:15px;
}

footer{
padding:40px 0;
color:#888;
}

</style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
<div class="container">

<a class="navbar-brand d-flex align-items-center" href="#">
<img src="uclogo.png" alt="UC Logo" height="30" class="me-2">
<span>College of Computer Studies Sit-in Monitoring System</span>
</a>

<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
<span class="navbar-toggler-icon"></span>
</button>

<div class="collapse navbar-collapse" id="mainNav">

<ul class="navbar-nav ms-auto align-items-center">

<?php foreach($nav_items as $name => $url): ?>
<li class="nav-item">
<a class="nav-link px-3" href="<?php echo $url; ?>"><?php echo $name; ?></a>
</li>
<?php endforeach; ?>

<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle px-3" href="#" id="commLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">
Community
</a>

<ul class="dropdown-menu">
<li><a class="dropdown-item" href="#">Announcements</a></li>
<li><a class="dropdown-item" href="#">Events</a></li>
<li><a class="dropdown-item" href="#">Updates</a></li>
</ul>

</li>

<li class="nav-item">
<a class="btn btn-login px-4" href="login.php">Login</a>
</li>

<li class="nav-item">
<a class="btn btn-register px-4" href="register.php">Register</a>
</li>

</ul>

</div>
</div>
</nav>

<header class="hero-section">

<div class="container">

<div class="logo-container d-flex justify-content-center align-items-center gap-5 mb-3">
<img src="uclogo.png" alt="University of Cebu">
<img src="ucmainccslogo.png" alt="College of Computer Studies">
</div>

<h1 class="display-4 system-title">Sit-in Monitoring</h1>

</div>

</header>

<main class="container">

<div class="row justify-content-center">

<div class="col-lg-8">

<div class="welcome-card text-center">

<h2 class="fw-bold mb-4" style="color:var(--ccs-purple);">
Greetings, CCS!
</h2>

<p class="text-muted fs-6 fs-md-5">
Welcome to the official monitoring portal for the College of Computer Studies.
</p>

</div>

</div>

</div>

</main>

<footer class="text-center">

<div class="container">

<hr class="w-25 mx-auto mb-4">

<p class="mb-1 small">
&copy; <?php echo date("Y"); ?> University of Cebu - Main Campus
</p>

<p class="small text-uppercase">
College of Computer Studies
</p>

</div>

</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>