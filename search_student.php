<?php
// search_student.php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "sysarch");

if (isset($_POST['id_number'])) {
    $id = $conn->real_escape_string($_POST['id_number']);
    $sql = "SELECT * FROM users WHERE id_number = '$id' LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        echo json_encode(['success' => true, 'data' => $result->fetch_assoc()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Student record not found.']);
    }
}
?>