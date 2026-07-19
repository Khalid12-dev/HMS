<?php
// DB Connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "hms"; // Change to your database name

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Handle form submit
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit'])) {
    $name = $conn->real_escape_string($_POST['department_name']);
    $code = $conn->real_escape_string($_POST['department_code']);
    $head = $conn->real_escape_string($_POST['department_head']);
    $desc = $conn->real_escape_string($_POST['description']);
    $status = $conn->real_escape_string($_POST['status']);

    $sql = "INSERT INTO departments (department_name, department_code, department_head, description, status)
            VALUES ('$name', '$code', '$head', '$desc', '$status')";

    if ($conn->query($sql)) {
        echo "<script>alert('Department added successfully!'); window.location.href='add_department.html';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }

    $conn->close();
}
?>
