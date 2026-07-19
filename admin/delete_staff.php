<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hms";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if ID parameter exists
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid staff ID";
    header("Location: hospital_management.php#staff");
    exit();
}

$staff_id = intval($_GET['id']);

// Delete staff member
$sql = "DELETE FROM staff WHERE id = $staff_id";

if ($conn->query($sql) === TRUE) {
    $_SESSION['success'] = "Staff member deleted successfully!";
} else {
    $_SESSION['error'] = "Error deleting staff: " . $conn->error;
}

$conn->close();

header("Location: hospital_management.php#staff");
exit();
?>