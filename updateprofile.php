<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "sysarch";

$conn = new mysqli($host, $username, $password, $database);

if (isset($_POST['update'])) {
    $user_id = 1; // Assuming static ID for now
    $firstname = mysqli_real_escape_string($conn, $_POST['firstname']);
    $lastname = mysqli_real_escape_string($conn, $_POST['lastname']);
    $middlename = mysqli_real_escape_string($conn, $_POST['middlename']);

    $image_update_sql = "";

    // Check if an image was uploaded
    if (!empty($_FILES['profile_pic']['name'])) {
        $target_dir = "uploads/";
        
        // Ensure folder exists
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

        $file_extension = pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION);
        $new_filename = "user_" . $user_id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
            $image_update_sql = ", profile_pic = '$new_filename'";
        }
    }

    $sql = "UPDATE users SET 
            first_name = '$firstname', 
            last_name = '$lastname', 
            middle_name = '$middlename' 
            $image_update_sql 
            WHERE id = $user_id";

    if ($conn->query($sql) === TRUE) {
        header("Location: userprofile.php?success=1");
    } else {
        echo "Error: " . $conn->error;
    }
}
$conn->close();
?>