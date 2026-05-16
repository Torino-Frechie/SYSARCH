<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) && !isset($_SESSION['admin'])) {
    echo json_encode(['updates' => []]);
    exit();
}

$conn = new mysqli("localhost", "root", "", "sysarch");
if ($conn->connect_error) {
    echo json_encode(['updates' => []]);
    exit();
}

$last_check = $_GET['last_check'] ?? date('Y-m-d H:i:s', strtotime('-1 minute'));
// Convert ISO 8601 from JS to MySQL datetime
$last_check = date('Y-m-d H:i:s', strtotime($last_check));

$stmt = $conn->prepare("
    SELECT lab_name, pc_number, new_status, notes, changed_at 
    FROM pc_status_history 
    WHERE changed_at > ?
    ORDER BY changed_at DESC
");
$stmt->bind_param("s", $last_check);
$stmt->execute();
$result = $stmt->get_result();

$updates = [];
while ($row = $result->fetch_assoc()) {
    $updates[] = $row;
}

echo json_encode(['updates' => $updates]);