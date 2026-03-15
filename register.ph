<?php
// Define the navigation items 
$nav_items = [
    'Home' => 'index.php',
    'About' => 'about.php'
];

$community_links = [
    'Announcements' => 'announcements.php',
    'Student Forum' => 'forum.php',
    'Lab Rules' => 'rules.php',
    'Schedule' => 'schedule.php'
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

        /* Navbar Match */
        .navbar {
            background-color: var(--uc-blue);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .navbar-brand {
            font-weight: 300;
            color: white !important;
            font-size: 0.9rem; 
        }

        /* Registration Card */
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
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="uclogo.png" alt="UC Logo" height="30" class="me-2">
                <span>CCS Sit-in Monitoring</span>
            </a>
        </div>
    </nav>

    <main class="container">
        <div class="registration-container">
            <div class="registration-card">
                <a href="index.php" class="btn-back">Back</a>
                
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
                            <option value="BSIT">BSIT</option>
                            <option value="BSCS">BSCS</option>
                            <option value="BSIS">BSIS</option>
                            <option value="ACT">ACT</option>
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
