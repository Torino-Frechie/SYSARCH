<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if password is at least 6 characters
    if (strlen($password) < 6) {
        die("Error: Password must be 6 or more characters long.");
    }

    // Check if passwords match
    if ($password !== $confirm_password) {
        die("Error: Passwords do not match.");
    }

    // If validation passes, proceed to hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // ... rest of your database insertion code ...
}
?>
