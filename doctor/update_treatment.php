<?php
session_start();
require '../config.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $treatment_id = $_POST['treatment_id'] ?? '';
    $patient_id = $_POST['patient_id'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $status = $_POST['status'] ?? 'ongoing';

    // Validate required fields
    if (empty($treatment_id) || empty($patient_id) || empty($name) || empty($start_date)) {
        $_SESSION['error'] = "Please fill all required fields";
        header("Location: patient_management.php?patient_id=$patient_id");
        exit();
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE treatments SET 
            name = ?, 
            description = ?, 
            start_date = ?, 
            end_date = ?, 
            status = ?,
            updated_at = NOW()
            WHERE id = ? AND doctor_id = ?
        ");
        
        $stmt->execute([
            htmlspecialchars($name),
            htmlspecialchars($description),
            $start_date,
            !empty($end_date) ? $end_date : null,
            $status,
            $treatment_id,
            $_SESSION['user']['id']
        ]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['message'] = "Treatment updated successfully!";
        } else {
            $_SESSION['error'] = "No changes made or treatment not found";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }

    header("Location: patient_management.php?patient_id=$patient_id");
    exit();
}
?>