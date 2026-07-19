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
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $date_recorded = $_POST['date_recorded'] ?? '';

    // Validate required fields
    if (empty($patient_id) || empty($title) || empty($description) || empty($date_recorded)) {
        $_SESSION['error'] = "Please fill all required fields";
        header("Location: patient_management.php?patient_id=$patient_id");
        exit();
    }

    // Handle file upload
    $attachment = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/medical_history/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = uniqid() . '_' . basename($_FILES['attachment']['name']);
        $targetPath = $uploadDir . $filename;

        // Validate file type and size
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (in_array($_FILES['attachment']['type'], $allowedTypes) && 
            $_FILES['attachment']['size'] <= $maxSize) {
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
                $attachment = $filename;
            } else {
                $_SESSION['error'] = "Failed to upload file";
                header("Location: patient_management.php?patient_id=$patient_id");
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid file type or size (max 5MB allowed)";
            header("Location: patient_management.php?patient_id=$patient_id");
            exit();
        }
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO medical_history 
            (patient_id, title, description, date_recorded, attachment) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $patient_id,
            htmlspecialchars($title),
            htmlspecialchars($description),
            $date_recorded,
            $attachment
        ]);

        $_SESSION['message'] = "Medical history added successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }

    header("Location: patient_management.php?patient_id=$patient_id");
    exit();
}
?>