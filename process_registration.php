<?php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'sysarch';

if ($_SERVER["REQUEST_METHOD"] !== 'POST') {
die('Invalid request method.');
}

$id_number = isset($_POST['id_number']) ? trim($_POST['id_number']) : '';
$last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
$first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$middle_name = isset($_POST['middle_name']) ? trim($_POST['middle_name']) : '';
$course = isset($_POST['course']) ? trim($_POST['course']) : '';
$year_level = isset($_POST['year_level']) ? trim($_POST['year_level']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : '';

$required = [$id_number, $last_name, $first_name, $course, $year_level, $email, $password, $confirm_password, $address];
foreach ($required as $field) {
if ($field === '') {
die('Error: Please fill out all required fields.');
}
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
die('Error: Invalid email address.');
}

if (strlen($password) < 6) {
die('Error: Password must be 6 or more characters long.');
}

if ($password !== $confirm_password) {
die('Error: Passwords do not match.');
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$mysqli = new mysqli($db_host, $db_user, $db_pass);
if ($mysqli->connect_errno) {
die('Database connection failed: ' . $mysqli->connect_error);
}

$create_db_sql = "CREATE DATABASE IF NOT EXISTS `" . $mysqli->real_escape_string($db_name) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (!$mysqli->query($create_db_sql)) {
die('Failed to create/select database: ' . $mysqli->error);
}

if (!$mysqli->select_db($db_name)) {
die('Failed to select database: ' . $mysqli->error);
}

$table_check = $mysqli->query("SHOW TABLES LIKE 'users'");
if ($table_check->num_rows === 0) {

$create_sql = "CREATE TABLE users (
id INT AUTO_INCREMENT PRIMARY KEY,
id_number VARCHAR(100) NOT NULL UNIQUE,
last_name VARCHAR(150) NOT NULL,
first_name VARCHAR(150) NOT NULL,
middle_name VARCHAR(150),
course VARCHAR(50),
year_level VARCHAR(10),
email VARCHAR(255) NOT NULL UNIQUE,
password VARCHAR(255) NOT NULL,
address TEXT,
created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if (!$mysqli->query($create_sql)) {
die('Failed to create users table: ' . $mysqli->error);
}
}

$check_stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? OR id_number = ? LIMIT 1");
if (!$check_stmt) {
die('Prepare failed: ' . $mysqli->error);
}
$check_stmt->bind_param('ss', $email, $id_number);
$check_stmt->execute();
$check_stmt->store_result();
if ($check_stmt->num_rows > 0) {
$check_stmt->close();
$mysqli->close();
die('Error: A user with that email or ID number already exists.');
}
$check_stmt->close();

$insert_sql = "INSERT INTO users (id_number, last_name, first_name, middle_name, course, year_level, email, password, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $mysqli->prepare($insert_sql);
if (!$stmt) {
die('Prepare failed: ' . $mysqli->error);
}
$stmt->bind_param('sssssssss', $id_number, $last_name, $first_name, $middle_name, $course, $year_level, $email, $hashed_password, $address);

if ($stmt->execute()) {
$stmt->close();
$mysqli->close();
header('Location: register.php?success=1');
exit;
} else {
$err = $stmt->error;
$stmt->close();
$mysqli->close();
die('Registration failed: ' . $err);
}

?>
