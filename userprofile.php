<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "sysarch";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = 1; 
$sql = "SELECT * FROM users WHERE id = $user_id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "User not found";
    exit;
}

$profile_pic = !empty($user['profile_pic']) ? $user['profile_pic'] : 'default_avatar.png';

$nav_items = [
    "Home" => "userprofile.php",
    "History" => "history.php",
    "Reservation" => "reservation.php",
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - CCS Monitoring</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        :root {
            --uc-blue: #a1cbf7;
            --ccs-purple: #9757d6;
            --ccs-gold: #FFD700;
        }

        body { font-family: 'Poppins', sans-serif; background-color: #f4f7f6; margin: 0; overflow-x: hidden; }
        
        /* Navbar Styling Restored */
        .navbar { background-color: var(--uc-blue); box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 10px; }
        .navbar-brand { font-weight: 300; color: white !important; font-size: 0.9rem; }
        .nav-link { color: rgba(43,94,124,0.9) !important; font-weight: 500; }
        .btn-logout { background-color: var(--ccs-gold); color: #000 !important; font-weight: 600; border-radius: 8px; margin-left: 10px; }
        
        .hero-section {
            background: linear-gradient(135deg, var(--ccs-purple) 0%, var(--uc-blue) 100%);
            color: white; padding: 60px 20px 100px 20px;
            border-bottom-left-radius: 40px; border-bottom-right-radius: 40px; text-align: center;
        }

        .welcome-card {
            background: white; border-radius: 25px; padding: 40px;
            margin-top: -60px; box-shadow: 0 15px 35px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
        }

        /* Profile Picture UI */
        .avatar-container { position: relative; width: 140px; height: 140px; margin: 0 auto 15px; }
        .avatar-preview { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 5px solid #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.1); background: #eee; }
        .file-input-label {
            position: absolute; bottom: 5px; right: 5px; background: var(--ccs-purple);
            color: white; width: 35px; height: 35px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; cursor: pointer; border: 3px solid #fff;
        }

        .form-control { border: 1px solid #ddd; background-color: #f9f9f9; border-radius: 10px; padding: 12px; }
        .btn-update { background: linear-gradient(135deg, var(--ccs-purple), var(--uc-blue)); border: none; color: white; padding: 12px 30px; border-radius: 10px; font-weight: 600; }
        footer { padding: 40px 0; color: #888; }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="uclogo.png" alt="UC Logo" height="30" class="me-2">
            <span>CCS Sit-in Monitoring</span>
        </a>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <?php foreach ($nav_items as $name => $url): ?>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="<?php echo $url; ?>"><?php echo $name; ?></a>
                    </li>
                <?php endforeach; ?>
                <li class="nav-item">
                    <a class="btn btn-logout px-4" href="landingpage.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<header class="hero-section">
    <div class="container">
        <h1 class="display-5 fw-bold text-uppercase">My Profile</h1>
        <p class="opacity-75">Manage your student information</p>
    </div>
</header>

<main class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="welcome-card">
                
                <?php if(isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Profile updated successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="updateprofile.php" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-4 text-center border-end">
                            <div class="avatar-container">
                                <img src="uploads/<?php echo $profile_pic; ?>" id="preview" class="avatar-preview" onerror="this.src='https://via.placeholder.com/150'">
                                <label for="profile_pic_input" class="file-input-label">
                                    <i class="fa-solid fa-camera"></i>
                                </label>
                                <input type="file" name="profile_pic" id="profile_pic_input" hidden accept="image/*">
                            </div>
                            <h4 class="fw-bold text-dark mb-0"><?php echo $user['first_name']; ?></h4>
                            <p class="text-muted small"><?php echo $user['course']; ?></p>
                            <span class="badge bg-light text-dark border">Year <?php echo $user['year_level']; ?></span>
                        </div>

                        <div class="col-md-8 ps-md-5">
                            <div class="mb-3">
                                <label class="small fw-bold text-muted">STUDENT ID</label>
                                <input type="text" class="form-control" value="<?php echo $user['id_number']; ?>" readonly>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="small fw-bold text-muted">FIRST NAME</label>
                                    <input type="text" class="form-control" name="firstname" value="<?php echo $user['first_name']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="small fw-bold text-muted">LAST NAME</label>
                                    <input type="text" class="form-control" name="lastname" value="<?php echo $user['last_name']; ?>" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="small fw-bold text-muted">MIDDLE NAME</label>
                                <input type="text" class="form-control" name="middlename" value="<?php echo $user['middle_name']; ?>">
                            </div>

                            <div class="text-end">
                                <button type="submit" name="update" class="btn btn-update px-5">Save Changes</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<footer class="text-center">
    <p class="mb-1 small">&copy; <?php echo date("Y"); ?> University of Cebu - Main Campus</p>
</footer>

<script>
    // Live preview
    document.getElementById('profile_pic_input').onchange = function (evt) {
        const [file] = this.files;
        if (file) { document.getElementById('preview').src = URL.createObjectURL(file); }
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>