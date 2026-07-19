<?php
session_start();
require '../config.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $doctor_id = $_SESSION['user']['id'];

    // Validate required fields
    if (empty($patient_id) || empty($name) || empty($start_date)) {
        $_SESSION['error'] = "Please fill all required fields";
        header("Location: patient_management.php?patient_id=$patient_id");
        exit();
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO treatments 
            (patient_id, doctor_id, name, description, start_date, end_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'ongoing')
        ");
        
        $stmt->execute([
            $patient_id,
            $doctor_id,
            htmlspecialchars($name),
            htmlspecialchars($description),
            $start_date,
            !empty($end_date) ? $end_date : null
        ]);

        $_SESSION['message'] = "Treatment added successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }

    header("Location: patient_management.php?patient_id=$patient_id");
    exit();
}
?>