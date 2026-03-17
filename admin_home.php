<?php
session_start();

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Database Connection
$host = "localhost";
$username = "root";
$password = "";
$database = "sysarch";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch Statistics
$total_students = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CCS Monitoring</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --uc-blue: #a1cbf7;
            --ccs-purple: #4b2a6d;
        }
        body { font-family: 'Poppins', sans-serif; background-color: #f4f7f6; margin: 0; }
        .navbar { background-color: var(--uc-blue); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .navbar-brand { font-weight: 300; color: white !important; font-size: 0.9rem; }
        .nav-link { color: white !important; font-weight: 400; font-size: 0.9rem; opacity: 0.9; }
        .btn-logout { background-color: #dc3545; color: white !important; font-weight: 600; border-radius: 5px; font-size: 0.8rem; padding: 5px 15px; }
        .card { border: none; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.08); background: white; }
        .card-header { background-color: transparent; color: var(--ccs-purple); border-bottom: 1px solid rgba(0,0,0,0.05); font-weight: 700; padding: 20px; }
        .btn-submit { background-color: #007bff; color: white; font-weight: 600; padding: 10px 25px; border-radius: 8px; border: none; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="uclogo.png" alt="Logo" height="30" class="me-2">
            <span>CCS Sit-in Monitoring</span>
        </a>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link px-3" href="admin_home.php">Home</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="search_student.php" data-bs-toggle="modal" data-bs-target="#searchModal">Search</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="admin_Student.php">Students</a></li>
                <li class="nav-item"><a class="btn btn-logout ms-lg-3" href="landingpage.php">Log out</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-md-5">
            <div class="card h-100">
                <div class="card-header"><i class="fa-solid fa-chart-pie me-2"></i>Statistics</div>
                <div class="card-body">
                    <p class="mb-1 text-muted small fw-bold">REGISTERED STUDENTS: <?php echo $total_students; ?></p>
                    <canvas id="sitInChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card mb-4">
                <div class="card-header"><i class="fa-solid fa-bullhorn me-2"></i>New Announcement</div>
                <div class="card-body">
                    <form action="post_announcement.php" method="POST">
                        <textarea name="content" class="form-control mb-3" rows="3" placeholder="Post an update..."></textarea>
                        <div class="text-end"><button type="submit" class="btn-submit">Post</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="searchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header border-0 bg-light rounded-top">
                <h5 class="modal-title fw-bold" style="color: var(--ccs-purple);">Search Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="input-group mb-3">
                    <input type="text" id="searchInput" class="form-control" placeholder="ID Number...">
                    <button class="btn btn-primary" type="button" id="btnSearch">Search</button>
                </div>
                
                <div id="searchResult" style="display: none;">
                    <hr>
                    <div class="text-center mb-3">
                        <img id="res_pic" src="" class="rounded-circle border" width="90" height="90" onerror="this.src='default_avatar.png'">
                    </div>
                    <div class="p-2 bg-light rounded shadow-sm">
                        <p class="mb-1 small"><strong>Name:</strong> <span id="res_name"></span></p>
                        <p class="mb-1 small"><strong>Course:</strong> <span id="res_course"></span></p>
                        <p class="mb-1 small"><strong>Year:</strong> <span id="res_year"></span></p>
                        <p class="mb-0 small"><strong>Email:</strong> <span id="res_email"></span></p>
                    </div>
                </div>
                <div id="searchError" class="alert alert-danger small mt-2" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

<script>
    // Search Functionality
    document.getElementById('btnSearch').addEventListener('click', function() {
        const idNumber = document.getElementById('searchInput').value;
        if (!idNumber) return;

        fetch('search_student.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id_number=' + encodeURIComponent(idNumber)
        })
        .then(response => response.json())
        .then(res => {
            const resultDiv = document.getElementById('searchResult');
            const errorDiv = document.getElementById('searchError');
            
            if (res.success) {
                document.getElementById('res_name').innerText = res.data.first_name + ' ' + res.data.last_name;
                document.getElementById('res_course').innerText = res.data.course;
                document.getElementById('res_year').innerText = res.data.year_level;
                document.getElementById('res_email').innerText = res.data.email || 'N/A';
                document.getElementById('res_pic').src = 'uploads/' + (res.data.profile_pic || 'default_avatar.png');
                
                resultDiv.style.display = 'block';
                errorDiv.style.display = 'none';
            } else {
                errorDiv.innerText = res.message;
                errorDiv.style.display = 'block';
                resultDiv.style.display = 'none';
            }
        });
    });

    // Chart logic remains here...
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>